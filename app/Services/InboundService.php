<?php

namespace FluentMailbox\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use ZBateson\MailMimeParser\MailMimeParser;
use FluentMailbox\Models\Email;
use FluentMailbox\Models\Mailbox;

class InboundService
{
    private $s3Config;

    public function __construct($config = [])
    {
        // AWS config always comes from the mailbox row's driver_settings —
        // the legacy fluent_mailbox_aws_* options are migration-only
        $region = $config['region'] ?? 'us-east-1';
        $key = $config['key'] ?? '';
        $secret = $config['secret'] ?? '';

        if ($key && $secret) {
            $this->s3Config = [
                'version' => 'latest',
                'region'  => $region,
                'credentials' => [
                    'key'    => $key,
                    'secret' => $secret,
                ],
            ];
        }
    }

    public function processFromContent($rawContent, $fallbackId = null, $checkDuplicate = false, $mailboxId = null)
    {
        try {
            // 2. Parse MIME
            $parser = new MailMimeParser();
            // Convert stream to string to ensure compatibility, pass false for attached
            $message = $parser->parse((string) $rawContent, false);
            
            // Use fallback ID if header is missing
            $messageId = $message->getHeaderValue('message-id') ?: $fallbackId;

            $subject = $message->getHeaderValue('subject') ?: '(No Subject)';
            $from = $message->getHeaderValue('from');
            $toHeader = $message->getHeader('to');
            
            $recipients = [];
            if ($toHeader) {
                foreach ($toHeader->getAddresses() as $address) {
                    $recipients[] = $address->getEmail();
                }
            }

            // Assign to a mailbox: explicit id, recipient match, then default mailbox
            if (!$mailboxId) {
                $mailboxId = Mailbox::routeInbound($recipients);
            }

            if ($checkDuplicate && $messageId) {
                global $wpdb;
                $table = $wpdb->prefix . 'fluent_mailbox_emails';
                // Dedup per mailbox: the same Message-ID may legitimately arrive
                // in several mailboxes (e.g. an email CC'd to two connected addresses)
                if ($mailboxId) {
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $table WHERE message_id = %s AND mailbox_id = %d LIMIT 1",
                        $messageId,
                        (int) $mailboxId
                    ));
                } else {
                    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE message_id = %s LIMIT 1", $messageId));
                }
                if ($exists) {
                    return false; // Skipped
                }
            }

            // Prefer HTML, fallback to Text
            $body = $message->getHtmlContent();
            if (!$body) {
                $body = nl2br(esc_html($message->getTextContent()));
            }

            // Sender-controlled HTML is rendered in the admin (v-html) —
            // strip scripts/event handlers before it is ever persisted
            $body = wp_kses_post($body);

            $cc = [];
            $ccHeader = $message->getHeader('cc');
            if ($ccHeader && method_exists($ccHeader, 'getAddresses')) {
                foreach ($ccHeader->getAddresses() as $address) {
                    $cc[] = $address->getEmail();
                }
            }

            // Persist attachments as WP media BEFORE the email row exists,
            // so a storage failure aborts the import and the provider can
            // retry the whole message instead of half of it landing
            $attachmentIds = $this->storeAttachments($message);
            if (is_wp_error($attachmentIds)) {
                return $attachmentIds;
            }

            // 3. Save to DB
            $emailId = Email::create([
                'message_id' => $messageId,
                'subject' => $subject,
                'sender' => $from,
                'recipients' => json_encode($recipients),
                'cc' => $cc ? json_encode($cc) : null,
                'body' => $body,
                'attachments' => $attachmentIds ? json_encode($attachmentIds) : null,
                'status' => 'inbox',
                'is_read' => 0,
                'mailbox_id' => $mailboxId
            ]);
            
            \FluentMailbox\Services\Logger::log('Email Saved to DB', ['id' => $emailId, 'subject' => $subject]);
            return $emailId;

        } catch (\Exception $e) {
            \FluentMailbox\Services\Logger::log('Parse/Save Error', ['error' => $e->getMessage()]);
            return new \WP_Error('parse_error', $e->getMessage());
        }
    }

    /**
     * Store every MIME attachment part as a WordPress media attachment and
     * return the ids (the emails table stores them as a JSON id array, the
     * same shape the compose upload flow writes).
     *
     * Fail-closed: on any storage error the already-saved parts are removed
     * and a WP_Error is returned, so the caller aborts before creating the
     * email row and the provider's retry re-imports the whole message.
     *
     * @param \ZBateson\MailMimeParser\IMessage $message
     * @return int[]|\WP_Error
     */
    private function storeAttachments($message)
    {
        $attachmentIds = [];

        foreach ($message->getAllAttachmentParts() as $index => $part) {
            $content = $part->getContent();
            if ($content === null || $content === '') {
                continue;
            }

            $filename = sanitize_file_name((string) $part->getFilename());
            if ($filename === '') {
                $filename = 'attachment-' . ($index + 1);
            }

            $upload = wp_upload_bits($filename, null, $content);
            if (!empty($upload['error'])) {
                \FluentMailbox\Services\Logger::log('Inbound attachment store failed', ['file' => $filename, 'error' => $upload['error']]);
                foreach ($attachmentIds as $savedId) {
                    wp_delete_attachment($savedId, true);
                }
                return new \WP_Error('attachment_error', 'Failed to store inbound attachment: ' . $upload['error']);
            }

            $attachmentId = wp_insert_attachment([
                'post_mime_type' => !empty($upload['type']) ? $upload['type'] : ((string) $part->getContentType() ?: 'application/octet-stream'),
                'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                'post_content' => '',
                'post_status' => 'inherit',
            ], $upload['file']);

            if (is_wp_error($attachmentId) || !$attachmentId) {
                foreach ($attachmentIds as $savedId) {
                    wp_delete_attachment($savedId, true);
                }
                return is_wp_error($attachmentId)
                    ? $attachmentId
                    : new \WP_Error('attachment_error', 'Failed to register inbound attachment: ' . $filename);
            }

            if (!function_exists('wp_generate_attachment_metadata')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
            }
            wp_update_attachment_metadata($attachmentId, wp_generate_attachment_metadata($attachmentId, $upload['file']));

            $attachmentIds[] = $attachmentId;
        }

        return $attachmentIds;
    }

    public function processFromS3($bucket, $key, $checkDuplicate = false, $mailboxId = null, $config = [])
    {
        $s3Config = $this->resolveS3Config($config);
        if (!$s3Config) {
            \FluentMailbox\Services\Logger::log('Error: AWS Credentials missing in InboundService');
            return new \WP_Error('config_error', 'AWS Credentials not configured');
        }

        try {
            $s3 = new S3Client($s3Config);

            \FluentMailbox\Services\Logger::log("Fetching from S3 via InboundService", ['bucket' => $bucket, 'key' => $key]);

            // 1. Get object from S3
            $result = $s3->getObject([
                'Bucket' => $bucket,
                'Key'    => $key
            ]);

            $rawContent = $result['Body'];

            // Use S3 key as fallback ID
            return $this->processFromContent($rawContent, $key, $checkDuplicate, $mailboxId);

        } catch (AwsException $e) {
            \FluentMailbox\Services\Logger::log('S3 Fetch Error', ['error' => $e->getMessage()]);
            return new \WP_Error('s3_error', $e->getMessage());
        }
    }

    public function fetchNewEmails($limit = 20, $mailbox = null)
    {
        $config = [];
        if ($mailbox) {
            $config = Mailbox::settingsOf($mailbox);
        }
        $s3Config = $this->resolveS3Config($config);
        if (!$s3Config) {
            return new \WP_Error('config_error', 'AWS Credentials not configured');
        }

        $bucket = $config['inbound_bucket'] ?? null;
        if (!$bucket) {
            return new \WP_Error('config_error', 'Inbound S3 Bucket not configured');
        }

        $mailboxId = $mailbox ? (int) $mailbox->id : null;

        try {
            $s3 = new S3Client($s3Config);

            // List objects in the bucket
            $objects = $s3->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => 'emails/',
                'MaxKeys' => $limit
            ]);

            if (!isset($objects['Contents'])) {
                return 0; // No emails found
            }

            $count = 0;
            foreach ($objects['Contents'] as $object) {
                $key = $object['Key'];

                // Check duplicate logic handled in processFromS3
                $result = $this->processFromS3($bucket, $key, true, $mailboxId, $config);

                if (!is_wp_error($result) && $result !== false) {
                    $count++;
                }
            }

            return $count;

        } catch (AwsException $e) {
            return new \WP_Error('s3_error', $e->getMessage());
        }
    }

    private function resolveS3Config(array $config)
    {
        if ($this->s3Config && empty($config)) {
            return $this->s3Config;
        }

        $region = $config['region'] ?? 'us-east-1';
        $key = $config['key'] ?? '';
        $secret = $config['secret'] ?? '';

        if (!$key || !$secret) {
            return null;
        }

        return [
            'version' => 'latest',
            'region'  => $region,
            'credentials' => [
                'key'    => $key,
                'secret' => $secret,
            ],
        ];
    }
}
