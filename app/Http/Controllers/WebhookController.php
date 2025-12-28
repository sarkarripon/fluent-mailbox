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
        if (isset($payload['Type']) && $payload['Type'] === 'SubscriptionConfirmation') {
            // In a real scenario, you might auto-confirm, but usually you take the TokenUrl and visit it manually or log it
            error_log('SES Subscription URL: ' . $payload['SubscribeURL']);
            return rest_ensure_response(['message' => 'Subscription confirmation received']);
        }

        if (isset($payload['Type']) && $payload['Type'] === 'Notification') {
            $message = json_decode($payload['Message'], true);

            // Check if it's a receipt notification
            if (isset($message['notificationType']) && $message['notificationType'] === 'Received') {
                $mail = $message['mail'];
                // Receipt info is in $message['receipt'] (action, etc)
                // Content? SES doesn't send content in JSON unless you use S3 Action or WorkMail. 
                // However, if using SNS Action with encoding, it might be in 'content'.
                // Simplest integration: SES -> S3 -> SNS -> Here.
                // Or SES -> WorkMail -> Lambda -> Here. 
                
                // For this MVP plugin, we'll assume we get *some* notification.
                // If it's just a delivery notification of Sent items, we ignore for "Inbox" purposes.
                
                // To actually receive content via webhook effectively without S3 buffer, user often uses "SNS Action" with "Base64" content? No, SNS has size limit.
                // Correct path: SES Rule -> S3 Bucket. SES Rule -> Lambda (read S3) -> Push to WP Webhook.
                
                // For now, I'll log the receipt. The "Inbox" population via Webhook purely depends on the external infrastructure sending the body.
                // I will add a method to accept a generic "Inbox" POST that can be triggered by a Lambda or custom script.
                
                $subject = $mail['commonHeaders']['subject'] ?? '(No Subject)';
                $from = $mail['source'];
                $to = $mail['destination'];
                // Body is tricky without S3. Let's assume the webhook sender (e.g. Lambda) pushed the body in a custom field 'body_html'
                $bodyHtml = $payload['body_html'] ?? '<i>(Content stored in S3)</i>'; 

                Email::create([
                    'message_id' => $mail['messageId'],
                    'subject' => $subject,
                    'sender' => $from,
                    'recipients' => json_encode($to),
                    'body' => $bodyHtml,
                    'status' => 'inbox',
                    'is_read' => 0
                ]);
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
