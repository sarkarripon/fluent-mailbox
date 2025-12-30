<?php

namespace FluentMailbox\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use ZBateson\MailMimeParser\MailMimeParser;
use FluentMailbox\Models\Email;

class InboundService
{
    private $s3Config;

    public function __construct()
    {
        $region = get_option('fluent_mailbox_aws_region', 'us-east-1');
        $key = get_option('fluent_mailbox_aws_key', '');
        $secret = get_option('fluent_mailbox_aws_secret', '');

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

    public function processFromContent($rawContent, $fallbackId = null, $checkDuplicate = false)
    {
        try {
            // 2. Parse MIME
            $parser = new MailMimeParser();
            // Convert stream to string to ensure compatibility, pass false for attached
            $message = $parser->parse((string) $rawContent, false);
            
            // Use fallback ID if header is missing
            $messageId = $message->getHeaderValue('message-id') ?: $fallbackId;

            if ($checkDuplicate && $messageId) {
                global $wpdb;
                $table = $wpdb->prefix . 'fluent_mailbox_emails';
                // Check if already exists
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE message_id = %s LIMIT 1", $messageId));
                if ($exists) {
                    return false; // Skipped
                }
            }

            $subject = $message->getHeaderValue('subject') ?: '(No Subject)';
            $from = $message->getHeaderValue('from');
            $toHeader = $message->getHeader('to');
            
            $recipients = [];
            if ($toHeader) {
                foreach ($toHeader->getAddresses() as $address) {
                    $recipients[] = $address->getEmail();
                }
            }

            // Prefer HTML, fallback to Text
            $body = $message->getHtmlContent();
            if (!$body) {
                $body = nl2br(esc_html($message->getTextContent()));
            }

            // 3. Save to DB
            $emailId = Email::create([
                'message_id' => $messageId,
                'subject' => $subject,
                'sender' => $from,
                'recipients' => json_encode($recipients),
                'body' => $body,
                'status' => 'inbox',
                'is_read' => 0
            ]);

            return $emailId;

        } catch (\Exception $e) {
            error_log('FluentMailbox Parse Error: ' . $e->getMessage());
            return new \WP_Error('parse_error', $e->getMessage());
        }
    }

    public function processFromS3($bucket, $key, $checkDuplicate = false)
    {
        if (!$this->s3Config) {
            return new \WP_Error('config_error', 'AWS Credentials not configured');
        }

        try {
            $s3 = new S3Client($this->s3Config);
            
            error_log("FluentMailbox Inbound: Fetching from S3. Bucket: $bucket, Key: $key");

            // 1. Get object from S3
            $result = $s3->getObject([
                'Bucket' => $bucket,
                'Key'    => $key
            ]);

            $rawContent = $result['Body'];

            // Use S3 key as fallback ID
            return $this->processFromContent($rawContent, $key, $checkDuplicate);

        } catch (AwsException $e) {
            error_log('FluentMailbox S3 Error: ' . $e->getMessage());
            return new \WP_Error('s3_error', $e->getMessage());
        }
    }

    public function fetchNewEmails($limit = 20)
    {
        if (!$this->s3Config) {
            return new \WP_Error('config_error', 'AWS Credentials not configured');
        }

        $bucket = get_option('fluent_mailbox_s3_bucket');
        if (!$bucket) {
            return new \WP_Error('config_error', 'Inbound S3 Bucket not configured');
        }

        try {
            $s3 = new S3Client($this->s3Config);

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
                $result = $this->processFromS3($bucket, $key, true); 
                
                if (!is_wp_error($result) && $result !== false) {
                    $count++;
                }
            }

            return $count;

        } catch (AwsException $e) {
            return new \WP_Error('s3_error', $e->getMessage());
        }
    }
}
