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
                // Fast-path dedup on the full-value hash key (the unique
                // index on it is the real guard — see the insert below).
                // Per mailbox: the same Message-ID may legitimately arrive
                // in several mailboxes (e.g. CC'd to two connected addresses)
                $hash = hash('sha256', (string) $messageId);
                if ($mailboxId) {
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $table WHERE dedup_hash = %s AND mailbox_id = %d LIMIT 1",
                        $hash,
                        (int) $mailboxId
                    ));
                } else {
                    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE dedup_hash = %s LIMIT 1", $hash));
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

            // 3. Save to DB. The uniq_message_mailbox unique index is the
            // real dedup guard: when concurrent deliveries both pass the
            // SELECT above, the second insert fails with a duplicate key —
            // treat that as "already imported" and release its attachments
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

            if ($emailId === false) {
                global $wpdb;
                foreach ((array) $attachmentIds as $savedId) {
                    \FluentMailbox\Http\Controllers\AttachmentController::deleteProtected($savedId);
                }
                if (stripos((string) $wpdb->last_error, 'duplicate') !== false) {
                    return false; // Concurrent delivery lost the race — duplicate
                }
                \FluentMailbox\Services\Logger::log('Email insert failed', ['error' => $wpdb->last_error]);
                return new \WP_Error('db_error', 'Failed to save inbound email: ' . $wpdb->last_error);
            }

            \FluentMailbox\Services\Logger::log('Email Saved to DB', ['id' => $emailId, 'subject' => $subject]);
            return $emailId;

        } catch (\Exception $e) {
            \FluentMailbox\Services\Logger::log('Parse/Save Error', ['error' => $e->getMessage()]);
            return new \WP_Error('parse_error', $e->getMessage());
        }
    }

    /**
     * Store every MIME attachment part as a PROTECTED WordPress media
     * attachment and return the ids (the emails table stores them as a
     * JSON id array, the same shape the compose upload flow writes).
     *
     * Files live under uploads/fluent-mailbox-private/, denied to direct
     * web access (.htaccess) and placed in an unguessable random subdir
     * for servers that ignore .htaccess (nginx). They are served only
     * through the authenticated admin-ajax download action and deleted
     * with their email.
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
        $dir = null;

        $abort = function ($error) use (&$attachmentIds) {
            foreach ($attachmentIds as $savedId) {
                \FluentMailbox\Http\Controllers\AttachmentController::deleteProtected($savedId);
            }
            \FluentMailbox\Services\Logger::log('Inbound attachment store failed', ['error' => $error]);
            return new \WP_Error('attachment_error', 'Failed to store inbound attachment: ' . $error);
        };

        foreach ($message->getAllAttachmentParts() as $index => $part) {
            $content = $part->getContent();
            if ($content === null || $content === '') {
                continue;
            }

            if ($dir === null) {
                $dir = self::protectedDir();
                if (is_wp_error($dir)) {
                    return $abort($dir->get_error_message());
                }
            }

            $filename = sanitize_file_name((string) $part->getFilename());
            if ($filename === '') {
                $filename = 'attachment-' . ($index + 1);
            }

            // Random on-disk name: even if the URL path leaks somewhere,
            // it names nothing recognizable; the display name lives in
            // meta and is restored on download
            $ext = (string) pathinfo($filename, PATHINFO_EXTENSION);
            $storedName = wp_generate_password(16, false, false) . ($ext !== '' ? '.' . $ext : '');
            $path = $dir . '/' . $storedName;

            if (file_put_contents($path, $content) === false) {
                return $abort('could not write ' . $filename);
            }

            $type = wp_check_filetype($filename);
            $attachmentId = wp_insert_attachment([
                'post_mime_type' => $type['type'] ?: ((string) $part->getContentType() ?: 'application/octet-stream'),
                'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                'post_content' => '',
                // private: keeps the attachment out of anonymous REST
                // (wp/v2/media), feeds, and sitemaps — admin-only surface
                'post_status' => 'private',
            ], $path);

            if (is_wp_error($attachmentId) || !$attachmentId) {
                @unlink($path);
                return $abort(is_wp_error($attachmentId) ? $attachmentId->get_error_message() : 'could not register ' . $filename);
            }

            update_post_meta($attachmentId, '_fluent_mailbox_protected', 1);
            update_post_meta($attachmentId, '_fluent_mailbox_filename', $filename);
            $attachmentIds[] = $attachmentId;
        }

        return $attachmentIds;
    }

    /**
     * Create (once) and return a fresh unguessable directory under the
     * access-denied private uploads root.
     *
     * @return string|\WP_Error
     */
    private static function protectedDir()
    {
        $base = wp_upload_dir()['basedir'] . '/fluent-mailbox-private';

        if (!is_dir($base)) {
            if (!wp_mkdir_p($base)) {
                return new \WP_Error('mkdir_failed', 'Cannot create ' . $base);
            }
            // Apache: hard deny. Nginx ignores this — the random subdir
            // below is the barrier there (plus no public listing anywhere).
            @file_put_contents($base . '/.htaccess', "Require all denied\nDeny from all\n");
            @file_put_contents($base . '/index.php', "<?php // Silence is golden\n");
        }

        $dir = $base . '/' . gmdate('Y/m') . '/' . wp_generate_password(20, false, false);
        if (!wp_mkdir_p($dir)) {
            return new \WP_Error('mkdir_failed', 'Cannot create ' . $dir);
        }

        return $dir;
    }

    /**
     * Build a raw MIME message from header lines, an HTML body, and
     * base64 attachment parts — the shared reconstruction path for push
     * providers (Elastic Email, Postmark parsed mode) whose webhooks
     * deliver parsed fields instead of the original raw message.
     *
     * Header lines must already be sanitized (no CR/LF in values);
     * attachment names/types are sanitized here.
     *
     * @param string[] $headers Complete header lines.
     * @param string $html HTML body.
     * @param array $attachments [['name' => string, 'content' => base64, 'type' => string|null], ...]
     * @return string
     */
    public static function buildRawMime(array $headers, $html, array $attachments = [])
    {
        if (!$attachments) {
            return implode("\r\n", $headers) . "\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
                . $html;
        }

        $boundary = 'fm-' . md5(uniqid('', true));
        $raw = implode("\r\n", $headers) . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . 'Content-Type: multipart/mixed; boundary="' . $boundary . "\"\r\n\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
            . $html . "\r\n";

        foreach (array_values($attachments) as $i => $att) {
            // Sender-controlled name/type land inside part headers —
            // strip quotes, backslashes, and control characters
            $filename = preg_replace('/[\x00-\x1f"\\\\]/', '', (string) ($att['name'] ?? ''));
            if ($filename === '') {
                $filename = 'attachment-' . ($i + 1);
            }
            $type = preg_replace('/[\x00-\x1f"\\\\]/', '', (string) ($att['type'] ?? ''));
            if ($type === '') {
                $detected = wp_check_filetype($filename);
                $type = $detected['type'] ?: 'application/octet-stream';
            }
            // Form-urlencoded parsing decodes literal '+' to a space —
            // map spaces back to '+' before stripping line breaks
            $content = str_replace(["\r", "\n"], '', str_replace(' ', '+', (string) ($att['content'] ?? '')));
            $raw .= '--' . $boundary . "\r\n"
                . 'Content-Type: ' . $type . '; name="' . $filename . "\"\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . 'Content-Disposition: attachment; filename="' . $filename . "\"\r\n\r\n"
                . chunk_split($content) . "\r\n";
        }

        return $raw . '--' . $boundary . "--\r\n";
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
