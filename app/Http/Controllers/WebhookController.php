<?php

namespace FluentMailbox\Http\Controllers;

use FluentMailbox\Models\Email;

class WebhookController
{
    public function handle($request)
    {
        $body = $request->get_body();
        error_log('FluentMailbox Webhook Body: ' . $body);
        
        $payload = json_decode($body, true);

        if (!$payload) {
             error_log('FluentMailbox Webhook Error: Invalid JSON payload');
             return rest_ensure_response(['message' => 'Invalid JSON'], 400);
        }

        // SNS sends a subscription confirmation first
        if (isset($payload['Type']) && $payload['Type'] === 'SubscriptionConfirmation') {
            // Auto-confirm the subscription
            $subscribeUrl = $payload['SubscribeURL'];
            error_log('FluentMailbox SES Subscription URL: ' . $subscribeUrl);
            
            wp_remote_get($subscribeUrl);
            
            return rest_ensure_response(['message' => 'Subscription confirmed']);
        }

        if (isset($payload['Type']) && $payload['Type'] === 'Notification') {
            error_log('FluentMailbox SNS Notification Received');
            $message = json_decode($payload['Message'], true);

            // Check if it's a receipt notification
            if (isset($message['notificationType']) && $message['notificationType'] === 'Received') {
                $mail = $message['mail'];
                $receipt = $message['receipt'];

                // Check for S3 action
                if (isset($receipt['action']['type']) && $receipt['action']['type'] === 'S3') {
                    $bucket = $receipt['action']['bucketName'];
                    $key = $receipt['action']['objectKey'];

                    error_log("FluentMailbox Processing from S3. Bucket: $bucket, Key: $key");

                    $service = new \FluentMailbox\Services\InboundService();
                    // Pass true for checkDuplicate
                    $result = $service->processFromS3($bucket, $key, true);

                    if (is_wp_error($result)) {
                        error_log('FluentMailbox Error processing inbound: ' . $result->get_error_message());
                        return rest_ensure_response(['message' => 'Error processing inbound: ' . $result->get_error_message()], 500);
                    }
                    
                    if ($result === false) {
                        error_log('FluentMailbox Email already exists (Duplicate skipped)');
                        return rest_ensure_response(['message' => 'Email already exists']);
                    }

                    error_log('FluentMailbox Email processed successfully from S3');
                    return rest_ensure_response(['message' => 'Email processed successfully']);
                } elseif (isset($message['content'])) {
                    // Fallback to Direct SNS Content (approx < 150KB safe limit)
                    // Note: This is less reliable for large emails than S3 action
                    error_log("FluentMailbox Processing direct SNS content");

                    $service = new \FluentMailbox\Services\InboundService();
                    // Use SES message ID as fallback if email doesn't have Message-ID header
                    $fallbackId = $message['mail']['messageId'] ?? uniqid('sns_');
                    
                    // Pass true for checkDuplicate
                    $result = $service->processFromContent($message['content'], $fallbackId, true);

                    if (is_wp_error($result)) {
                         error_log('FluentMailbox Error processing SNS content: ' . $result->get_error_message());
                         return rest_ensure_response(['message' => 'Error processing content'], 500);
                    }
                    
                    if ($result === false) {
                        error_log('FluentMailbox Email already exists (Duplicate skipped)');
                        return rest_ensure_response(['message' => 'Email already exists']);
                    }

                    error_log('FluentMailbox Email processed successfully from SNS content');
                    return rest_ensure_response(['message' => 'Email processed successfully']);
                }
                
                error_log('FluentMailbox Webhook: Unsupported action type or missing content');
                return rest_ensure_response(['message' => 'Unsupported action type or missing content'], 200);
            }
        }

        // Allow direct posting for simple testing/integration (e.g. from a custom form or other service)
        $params = $request->get_params();
        if (!empty($params['subject']) && !empty($params['sender'])) {
             error_log('FluentMailbox Webhook: Direct Post Received');
             Email::create([
                'message_id' => uniqid('ext_'),
                'subject' => $params['subject'],
                'sender' => $params['sender'],
                'recipients' => json_encode($params['recipients'] ?? [get_site_url()]),
                'body' => $params['body'] ?? '',
                'status' => 'inbox',
                'is_read' => 0
            ]);
            return rest_ensure_response(['message' => 'Email received']);
        }

        return rest_ensure_response(['message' => 'Webhook processed']);
    }
}
