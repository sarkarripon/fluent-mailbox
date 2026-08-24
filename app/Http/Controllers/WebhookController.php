<?php

namespace FluentMailbox\Http\Controllers;

use FluentMailbox\Models\Mailbox;
use FluentMailbox\Services\DriverManager;
use FluentMailbox\Services\Logger;

class WebhookController
{
    /**
     * Unified per-mailbox webhook endpoint:
     * POST /webhook/{driver}/{mailbox_id}?secret=... (Mailgun Routes use
     * the /mime suffix variant to deliver raw MIME). The per-mailbox
     * inbound secret is verified before the driver sees the request.
     */
    public function handleDriver($request)
    {
        $driverSlug = (string) $request->get_param('driver');
        $mailboxId = (int) $request->get_param('mailbox_id');
        $secret = (string) $request->get_param('secret');

        $mailbox = Mailbox::find($mailboxId);
        if (!$mailbox || $mailbox->driver !== $driverSlug || !$mailbox->is_active) {
            Logger::log('Webhook rejected: unknown mailbox', ['driver' => $driverSlug, 'mailbox_id' => $mailboxId]);
            return new \WP_Error('invalid_mailbox', 'Unknown mailbox', ['status' => 404]);
        }

        $settings = Mailbox::settingsOf($mailbox);
        if (empty($settings['inbound_secret']) || !$secret || !hash_equals($settings['inbound_secret'], $secret)) {
            Logger::log('Webhook rejected: invalid secret', ['driver' => $driverSlug, 'mailbox_id' => $mailboxId]);
            return new \WP_Error('invalid_secret', 'Invalid webhook secret', ['status' => 403]);
        }

        $driver = DriverManager::make($mailbox);
        if (is_wp_error($driver)) {
            return $driver;
        }

        $result = $driver->handleWebhook($request, $mailbox);

        if (is_wp_error($result)) {
            Logger::log('Webhook processing failed', ['driver' => $driverSlug, 'mailbox_id' => $mailboxId, 'error' => $result->get_error_message()]);
            return $result;
        }

        if ($result === 'duplicate') {
            return rest_ensure_response(['message' => 'Email already exists']);
        }

        return rest_ensure_response(['message' => 'Webhook processed']);
    }

    public function handle($request)
    {
        $body = $request->get_body();
        // error_log('FluentMailbox Webhook Body: ' . $body);
        \FluentMailbox\Services\Logger::log('Webhook Received', ['body_length' => strlen($body)]);
        
        $payload = json_decode($body, true);

        if (!$payload) {
             \FluentMailbox\Services\Logger::log('Error: Invalid JSON payload');
             return rest_ensure_response(['message' => 'Invalid JSON'], 400);
        }

        // SNS sends a subscription confirmation first
        if (isset($payload['Type']) && $payload['Type'] === 'SubscriptionConfirmation') {
            // Auto-confirm the subscription
            $subscribeUrl = $payload['SubscribeURL'];
            \FluentMailbox\Services\Logger::log('Subscription Confirmation Request', ['url' => $subscribeUrl]);
            
            wp_remote_get($subscribeUrl);
            
            return rest_ensure_response(['message' => 'Subscription confirmed']);
        }

        if (isset($payload['Type']) && $payload['Type'] === 'Notification') {
            \FluentMailbox\Services\Logger::log('SNS Notification Received', ['message_id' => $payload['MessageId'] ?? 'unknown']);
            $message = json_decode($payload['Message'], true);

            // Check if it's a receipt notification
            if (isset($message['notificationType']) && $message['notificationType'] === 'Received') {
                $mail = $message['mail'];
                $receipt = $message['receipt'];

                // Check for S3 action
                if (isset($receipt['action']['type']) && $receipt['action']['type'] === 'S3') {
                    $bucket = $receipt['action']['bucketName'];
                    $key = $receipt['action']['objectKey'];

                    \FluentMailbox\Services\Logger::log("Processing from S3", ['bucket' => $bucket, 'key' => $key]);

                    $service = new \FluentMailbox\Services\InboundService();
                    // Pass true for checkDuplicate
                    $result = $service->processFromS3($bucket, $key, true);

                    if (is_wp_error($result)) {
                        \FluentMailbox\Services\Logger::log('Error processing inbound S3', ['error' => $result->get_error_message()]);
                        return rest_ensure_response(['message' => 'Error processing inbound: ' . $result->get_error_message()], 500);
                    }
                    
                    if ($result === false) {
                        \FluentMailbox\Services\Logger::log('Duplicate Email (Skipped)');
                        return rest_ensure_response(['message' => 'Email already exists']);
                    }

                    \FluentMailbox\Services\Logger::log('Email processed successfully from S3');
                    return rest_ensure_response(['message' => 'Email processed successfully']);
                } elseif (isset($message['content'])) {
                    // Fallback to Direct SNS Content (approx < 150KB safe limit)
                    // Note: This is less reliable for large emails than S3 action
                    \FluentMailbox\Services\Logger::log("Processing direct SNS content");

                    $service = new \FluentMailbox\Services\InboundService();
                    // Use SES message ID as fallback if email doesn't have Message-ID header
                    $fallbackId = $message['mail']['messageId'] ?? uniqid('sns_');
                    
                    // Pass true for checkDuplicate
                    $result = $service->processFromContent($message['content'], $fallbackId, true);

                    if (is_wp_error($result)) {
                         \FluentMailbox\Services\Logger::log('Error processing SNS content', ['error' => $result->get_error_message()]);
                         return rest_ensure_response(['message' => 'Error processing content'], 500);
                    }
                    
                    if ($result === false) {
                        \FluentMailbox\Services\Logger::log('Duplicate Email (Skipped)');
                        return rest_ensure_response(['message' => 'Email already exists']);
                    }

                    \FluentMailbox\Services\Logger::log('Email processed successfully from SNS content');
                    return rest_ensure_response(['message' => 'Email processed successfully']);
                }
                
                \FluentMailbox\Services\Logger::log('Unsupported action type or missing content', $message);
                return rest_ensure_response(['message' => 'Unsupported action type or missing content'], 200);
            }
        }

        return rest_ensure_response(['message' => 'Webhook processed']);
    }
}
