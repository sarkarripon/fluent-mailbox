<?php

namespace FluentMailbox\Services\Drivers;

use FluentMailbox\Models\Mailbox;
use FluentMailbox\Services\Contracts\MailDriverInterface;
use FluentMailbox\Services\InboundService;
use FluentMailbox\Services\Logger;

/**
 * Mailgun API driver. Sends through the Messages API and receives
 * through a Mailgun Route that forwards to the plugin webhook
 * (signature-verified with the domain signing key).
 */
class MailgunDriver implements MailDriverInterface
{
    public static function fields()
    {
        return [
            ['key' => 'domain', 'label' => __('Mailgun domain', 'fluent-mailbox'), 'type' => 'text', 'group' => 'mailgun', 'placeholder' => 'mg.example.com', 'default' => ''],
            ['key' => 'region', 'label' => __('API region', 'fluent-mailbox'), 'type' => 'select', 'group' => 'mailgun',
             'options' => [
                 ['value' => 'us', 'label' => 'US (api.mailgun.net)'],
                 ['value' => 'eu', 'label' => 'EU (api.eu.mailgun.net)'],
             ], 'default' => 'us'],
            ['key' => 'api_key', 'label' => __('Private API key', 'fluent-mailbox'), 'type' => 'password', 'group' => 'mailgun', 'secret' => true, 'default' => ''],
            ['key' => 'signing_key', 'label' => __('Webhook signing key', 'fluent-mailbox'), 'type' => 'password', 'group' => 'mailgun', 'secret' => true,
             'help' => __('Used to verify inbound webhook posts. Found in Mailgun → Settings → API Security → "email signing key".', 'fluent-mailbox'), 'default' => ''],
        ];
    }

    public function testConnection(array $settings = [])
    {
        if (empty($settings['api_key']) || empty($settings['domain'])) {
            return new \WP_Error('missing_creds', __('Mailgun domain and API key are required.', 'fluent-mailbox'));
        }

        $response = wp_remote_get(
            'https://' . $this->apiHost($settings) . '/v3/domains/' . rawurlencode($settings['domain']),
            ['headers' => ['Authorization' => 'Basic ' . base64_encode('api:' . $settings['api_key'])], 'timeout' => 15]
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $message = $body['message'] ?? 'Mailgun rejected the credentials';
            return new \WP_Error('mailgun_error', $message);
        }

        return true;
    }

    public function send(array $args, array $settings = [])
    {
        if (empty($settings['api_key']) || empty($settings['domain'])) {
            return new \WP_Error('mailgun_error', __('Mailgun domain and API key are required.', 'fluent-mailbox'));
        }

        $fromEmail = $args['from_email'] ?: ('postmaster@' . $settings['domain']);
        $from = !empty($args['from_name']) ? $args['from_name'] . ' <' . $fromEmail . '>' : $fromEmail;

        $to = implode(',', (array) $args['to']);
        $data = [
            'from' => $from,
            'to' => $to,
            'subject' => $args['subject'],
            'html' => $args['body'],
            'text' => wp_strip_all_tags($args['body']),
        ];
        if (!empty($args['cc'])) {
            $data['cc'] = implode(',', (array) $args['cc']);
        }
        if (!empty($args['bcc'])) {
            $data['bcc'] = implode(',', (array) $args['bcc']);
        }
        if (!empty($args['reply_to'])) {
            $data['h:Reply-To'] = $args['reply_to'];
        }

        $attachments = array_filter((array) ($args['attachments'] ?? []), 'file_exists');
        if (!empty($attachments)) {
            $i = 0;
            foreach ($attachments as $filePath) {
                $data['attachment[' . $i++ . ']'] = base64_encode(file_get_contents($filePath));
            }
        }

        $response = wp_remote_post(
            'https://' . $this->apiHost($settings) . '/v3/' . rawurlencode($settings['domain']) . '/messages',
            [
                'headers' => ['Authorization' => 'Basic ' . base64_encode('api:' . $settings['api_key'])],
                'body' => $data,
                'timeout' => 30,
            ]
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $result = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || empty($result['id'])) {
            $message = $result['message'] ?? 'Mailgun send failed (HTTP ' . $code . ')';
            Logger::log('Mailgun send failed', ['error' => $message]);
            return new \WP_Error('mailgun_error', $message);
        }

        return $result['id'];
    }

    /**
     * Mailgun is push-only inbound; nothing to poll.
     */
    public function fetchNewEmails($mailbox)
    {
        return 0;
    }

    /**
     * Verify a Mailgun webhook post (timestamp+token signed with this
     * mailbox's signing key) and import the message it carries. Point the
     * Mailgun Route at the /mime variant of the webhook URL so the full
     * raw MIME (attachments, CC, headers) arrives in body-mime.
     *
     * @param \WP_REST_Request $request Multipart form POST from Mailgun Routes.
     * @param object $mailbox Mailbox row (secret already verified by the router).
     * @return \WP_Error|true|string WP_Error on failure, true if imported, 'duplicate' if skipped.
     */
    public function handleWebhook($request, $mailbox)
    {
        $params = $request->get_params();

        $signature = $params['signature'] ?? '';
        $timestamp = $params['timestamp'] ?? '';
        $token = $params['token'] ?? '';

        if (!$signature || !$timestamp || !$token) {
            return new \WP_Error('invalid_payload', 'Missing Mailgun signature fields', ['status' => 400]);
        }

        // Verify against this mailbox's signing key only — the URL already
        // identifies the mailbox, so no key needs to be guessed
        $settings = Mailbox::settingsOf($mailbox);
        if (empty($settings['signing_key'])) {
            Logger::log('Mailgun webhook rejected: mailbox has no signing key', ['mailbox' => $mailbox->email]);
            return new \WP_Error('invalid_signature', 'Mailbox has no signing key configured', ['status' => 403]);
        }

        $expected = hash_hmac('sha256', $timestamp . $token, $settings['signing_key']);
        if (!hash_equals($expected, $signature)) {
            Logger::log('Mailgun webhook signature verification failed', ['mailbox' => $mailbox->email]);
            return new \WP_Error('invalid_signature', 'Invalid webhook signature', ['status' => 403]);
        }

        // Reject replayed notifications (Mailgun signs a fresh timestamp per post)
        if (abs(time() - (int) $timestamp) > 5 * MINUTE_IN_SECONDS) {
            return new \WP_Error('invalid_signature', 'Stale webhook timestamp', ['status' => 403]);
        }

        // A recipient address may belong to another connected mailbox (e.g. CC)
        $recipientMailbox = Mailbox::routeInbound([$params['recipient'] ?? '']) ?: (int) $mailbox->id;

        if (!empty($params['body-mime'])) {
            // Preferred: full raw MIME — attachments, CC and headers intact
            $fallbackId = $params['Message-Id'] ?? ('mg_' . $token);
            $result = (new InboundService())->processFromContent($params['body-mime'], $fallbackId, true, $recipientMailbox);
        } else {
            // Fallback: rebuild a minimal MIME from Mailgun's parsed fields
            // (attachments are lost — the Route should target the /mime URL)
            if (empty($params['recipient']) || empty($params['from'])) {
                return new \WP_Error('invalid_payload', 'Missing recipient or from', ['status' => 400]);
            }

            Logger::log('Mailgun webhook without body-mime — attachments not imported; point the Route at the /mime webhook URL', ['mailbox' => $mailbox->email]);

            $messageId = $params['Message-Id'] ?? ('mg_' . $token);

            $raw = 'From: ' . $params['from'] . "\r\n"
                . 'To: ' . $params['recipient'] . "\r\n"
                . 'Subject: ' . ($params['subject'] ?? '(No Subject)') . "\r\n"
                . 'Message-ID: ' . $messageId . "\r\n"
                . 'Date: ' . ($params['Date'] ?? '') . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
                . ($params['body-html'] ?? nl2br($params['body-plain'] ?? ''));

            $result = (new InboundService())->processFromContent($raw, 'mg_' . $token, true, $recipientMailbox);
        }

        if (is_wp_error($result)) {
            return $result;
        }

        return $result === false ? 'duplicate' : true;
    }

    private function apiHost(array $settings)
    {
        return ($settings['region'] ?? 'us') === 'eu' ? 'api.eu.mailgun.net' : 'api.mailgun.net';
    }
}
