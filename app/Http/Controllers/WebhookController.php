<?php

namespace FluentMailbox\Http\Controllers;

use FluentMailbox\Models\Email;

class WebhookController
{
    public function handle($request)
    {
        $body = $request->get_body();
        $payload = json_decode($body, true);

        // SES sends a subscription confirmation first
        // SES sends a subscription confirmation first
        if (isset($payload['Type']) && $payload['Type'] === 'SubscriptionConfirmation') {
            // Auto-confirm the subscription
            $subscribeUrl = $payload['SubscribeURL'];
            error_log('SES Subscription URL: ' . $subscribeUrl);
            
            wp_remote_get($subscribeUrl);
            
            return rest_ensure_response(['message' => 'Subscription confirmed']);
        }

        if (isset($payload['Type']) && $payload['Type'] === 'Notification') {
            $message = json_decode($payload['Message'], true);

            // Check if it's a receipt notification
            if (isset($message['notificationType']) && $message['notificationType'] === 'Received') {
                $mail = $message['mail'];
                $receipt = $message['receipt'];

                // Check for S3 action
                if (isset($receipt['action']['type']) && $receipt['action']['type'] === 'S3') {
                    $bucket = $receipt['action']['bucketName'];
                    $key = $receipt['action']['objectKey'];

                    $service = new \FluentMailbox\Services\InboundService();
                    $result = $service->processFromS3($bucket, $key);

                    if (is_wp_error($result)) {
                        return rest_ensure_response(['message' => 'Error processing inbound: ' . $result->get_error_message()], 500);
                    }
                    
                    return rest_ensure_response(['message' => 'Email processed successfully']);
                }
                
                // Fallback (no S3 info?)
                return rest_ensure_response(['message' => 'Unsupported action type'], 200);
            }
        }

        // Allow direct posting for simple testing/integration (e.g. from a custom form or other service)
        $params = $request->get_params();
        if (!empty($params['subject']) && !empty($params['sender'])) {
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
