<?php

namespace FluentMailbox\Services\Drivers;

use FluentMailbox\Models\Mailbox;
use FluentMailbox\Services\Contracts\MailDriverInterface;
use FluentMailbox\Services\InboundService;
use FluentMailbox\Services\Logger;

/**
 * Postmark API driver. Sends through the Postmark Email API and
 * receives through an inbound webhook, verified by a per-mailbox
 * secret embedded in the webhook URL.
 */
class PostmarkDriver implements MailDriverInterface
{
    public static function fields()
    {
        return [
            ['key' => 'server_token', 'label' => __('Server token', 'fluent-mailbox'), 'type' => 'password', 'group' => 'postmark', 'secret' => true,
             'help' => __('Found in Postmark → Servers → your server → API tokens.', 'fluent-mailbox'), 'default' => ''],
            ['key' => 'message_stream', 'label' => __('Message stream (outbound)', 'fluent-mailbox'), 'type' => 'text', 'group' => 'postmark',
             'help' => __('Leave as "outbound" unless you use custom streams.', 'fluent-mailbox'), 'default' => 'outbound'],
        ];
    }

    public function testConnection(array $settings = [])
    {
        if (empty($settings['server_token'])) {
            return new \WP_Error('missing_creds', __('Postmark server token is required.', 'fluent-mailbox'));
        }

        $response = wp_remote_get('https://api.postmarkapp.com/server', [
            'headers' => ['X-Postmark-Server-Token' => $settings['server_token'], 'Accept' => 'application/json'],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $message = $body['Message'] ?? 'Postmark rejected the server token';
            return new \WP_Error('postmark_error', $message);
        }

        return true;
    }

    public function send(array $args, array $settings = [])
    {
        if (empty($settings['server_token'])) {
            return new \WP_Error('postmark_error', __('Postmark server token is required.', 'fluent-mailbox'));
        }

        $payload = [
            'From' => !empty($args['from_name'])
                ? $args['from_name'] . ' <' . $args['from_email'] . '>'
                : $args['from_email'],
            'To' => implode(',', (array) $args['to']),
            'Subject' => $args['subject'],
            'HtmlBody' => $args['body'],
            'TextBody' => wp_strip_all_tags($args['body']),
            'MessageStream' => $settings['message_stream'] ?? 'outbound',
        ];
        if (!empty($args['cc'])) {
            $payload['Cc'] = implode(',', (array) $args['cc']);
        }
        if (!empty($args['bcc'])) {
            $payload['Bcc'] = implode(',', (array) $args['bcc']);
        }
        if (!empty($args['reply_to'])) {
            $payload['ReplyTo'] = $args['reply_to'];
        }

        $attachments = array_filter((array) ($args['attachments'] ?? []), 'file_exists');
        if (!empty($attachments)) {
            $payload['Attachments'] = array_map(function ($filePath) {
                return [
                    'Name' => basename($filePath),
                    'Content' => base64_encode(file_get_contents($filePath)),
                    'ContentType' => 'application/octet-stream',
                ];
            }, array_values($attachments));
        }

        $response = wp_remote_post('https://api.postmarkapp.com/email', [
            'headers' => [
                'X-Postmark-Server-Token' => $settings['server_token'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $result = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || empty($result['MessageID'])) {
            $message = $result['Message'] ?? 'Postmark send failed (HTTP ' . $code . ')';
            Logger::log('Postmark send failed', ['error' => $message]);
            return new \WP_Error('postmark_error', $message);
        }

        return $result['MessageID'];
    }

    /**
     * Postmark is push-only inbound; nothing to poll.
     */
    public function fetchNewEmails($mailbox)
    {
        return 0;
    }

    /**
     * Import a Postmark inbound webhook post. The webhook URL carries
     * ?mailbox=ID&secret=... which is verified against the mailbox's
     * stored inbound secret before anything is imported.
     *
     * @return \WP_Error|true|string WP_Error on failure, true if imported, 'duplicate' if skipped.
     */
    public function handleWebhook($request)
    {
        $params = $request->get_params();
        $mailboxId = isset($params['mailbox']) ? (int) $params['mailbox'] : 0;
        $secret = (string) ($params['secret'] ?? '');

        $mailbox = $mailboxId ? Mailbox::find($mailboxId) : null;
        if (!$mailbox || $mailbox->driver !== 'postmark') {
            return new \WP_Error('invalid_mailbox', 'Unknown mailbox', ['status' => 404]);
        }

        $settings = Mailbox::settingsOf($mailbox);
        if (empty($settings['inbound_secret']) || !hash_equals($settings['inbound_secret'], $secret)) {
            Logger::log('Postmark webhook secret verification failed', ['mailbox' => $mailbox->email]);
            return new \WP_Error('invalid_signature', 'Invalid webhook secret', ['status' => 403]);
        }

        $payload = json_decode($request->get_body(), true);
        if (!is_array($payload)) {
            return new \WP_Error('invalid_payload', 'Invalid JSON', ['status' => 400]);
        }

        // With "Post raw email enabled" the full MIME is base64-encoded in RawEmail
        if (!empty($payload['RawEmail'])) {
            $raw = base64_decode($payload['RawEmail']);
            $fallbackId = $payload['MessageID'] ?? ('pm_' . uniqid());
            $result = (new InboundService())->processFromContent($raw, $fallbackId, true, (int) $mailbox->id);
        } else {
            $raw = 'From: ' . ($payload['From'] ?? '') . "\r\n"
                . 'To: ' . ($payload['To'] ?? $mailbox->email) . "\r\n"
                . 'Subject: ' . ($payload['Subject'] ?? '(No Subject)') . "\r\n"
                . 'Message-ID: ' . ($payload['MessageID'] ?? ('pm_' . uniqid())) . "\r\n"
                . 'Date: ' . ($payload['Date'] ?? '') . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
                . ($payload['HtmlBody'] ?? nl2br($payload['TextBody'] ?? ''));

            $result = (new InboundService())->processFromContent($raw, 'pm_' . uniqid(), true, (int) $mailbox->id);
        }

        if (is_wp_error($result)) {
            return $result;
        }

        return $result === false ? 'duplicate' : true;
    }
}
