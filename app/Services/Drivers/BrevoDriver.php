<?php

namespace FluentMailbox\Services\Drivers;

use FluentMailbox\Models\Mailbox;
use FluentMailbox\Services\Contracts\MailDriverInterface;
use FluentMailbox\Services\InboundService;
use FluentMailbox\Services\Logger;

/**
 * Brevo (formerly Sendinblue) API driver. Sends through the v3
 * transactional API and receives through Brevo Inbound Parsing: an
 * inbound webhook (created via POST /v3/webhooks, type "inbound")
 * posts parsed emails as a JSON `items` array. Attachment bodies are
 * not inlined in the notification — each carries a DownloadToken that
 * is exchanged against the attachments API during import.
 */
class BrevoDriver implements MailDriverInterface
{
    const API_BASE = 'https://api.brevo.com/v3';

    public static function fields()
    {
        return [
            ['key' => 'api_key', 'label' => __('API key', 'fluent-mailbox'), 'type' => 'password', 'group' => 'brevo', 'secret' => true,
             'help' => __('Brevo → SMTP & API → API Keys. For receiving, point your receiving (sub)domain\'s MX records at inbound1.sendinblue.com and inbound2.sendinblue.com, then create an inbound webhook (POST /v3/webhooks with type "inbound", event "inboundEmailProcessed", your domain, and this mailbox\'s webhook URL).', 'fluent-mailbox'), 'default' => ''],
        ];
    }

    public function testConnection(array $settings = [])
    {
        if (empty($settings['api_key'])) {
            return new \WP_Error('missing_creds', __('Brevo API key is required.', 'fluent-mailbox'));
        }

        $response = wp_remote_get(self::API_BASE . '/account', [
            'headers' => ['api-key' => $settings['api_key'], 'Accept' => 'application/json'],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $message = $body['message'] ?? 'Brevo rejected the API key (HTTP ' . $code . ')';
            return new \WP_Error('brevo_error', $message);
        }

        return true;
    }

    public function send(array $args, array $settings = [])
    {
        if (empty($settings['api_key'])) {
            return new \WP_Error('brevo_error', __('Brevo API key is required.', 'fluent-mailbox'));
        }

        $sender = ['email' => $args['from_email']];
        if (!empty($args['from_name'])) {
            $sender['name'] = $args['from_name'];
        }

        $payload = [
            'sender' => $sender,
            'to' => $this->addressList($args['to']),
            'subject' => $args['subject'],
            'htmlContent' => $args['body'],
            'textContent' => wp_strip_all_tags($args['body']),
        ];
        if (!empty($args['cc'])) {
            $payload['cc'] = $this->addressList($args['cc']);
        }
        if (!empty($args['bcc'])) {
            $payload['bcc'] = $this->addressList($args['bcc']);
        }
        if (!empty($args['reply_to'])) {
            $payload['replyTo'] = ['email' => $args['reply_to']];
        }

        $attachments = array_filter((array) ($args['attachments'] ?? []), 'file_exists');
        if (!empty($attachments)) {
            $payload['attachment'] = array_map(function ($filePath) {
                return [
                    'content' => base64_encode(file_get_contents($filePath)),
                    'name' => basename($filePath),
                ];
            }, array_values($attachments));
        }

        $response = wp_remote_post(self::API_BASE . '/smtp/email', [
            'headers' => [
                'api-key' => $settings['api_key'],
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
        $result = is_array($result) ? $result : [];

        if ($code < 200 || $code >= 300) {
            $message = $result['message'] ?? 'Brevo send failed (HTTP ' . $code . ')';
            Logger::log('Brevo send failed', ['error' => $message]);
            return new \WP_Error('brevo_error', $message);
        }

        // Any 2xx means accepted — a missing/renamed id field must not
        // read as failure, or the user resends and delivers twice
        return $result['messageId'] ?? ($result['messageIds'][0] ?? ('brevo_sent_' . uniqid()));
    }

    /**
     * Normalize a to/cc/bcc value (comma-separated string or array of
     * addresses) into the [{email}, ...] objects the v3 API expects.
     */
    private function addressList($value)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        $list = [];
        foreach ((array) $value as $address) {
            $address = trim((string) $address);
            if ($address !== '') {
                $list[] = ['email' => $address];
            }
        }
        return $list;
    }

    /**
     * Brevo is push-only inbound; nothing to poll.
     */
    public function fetchNewEmails($mailbox)
    {
        return 0;
    }

    /**
     * Import a Brevo Inbound Parsing notification. The router has already
     * verified the per-mailbox secret in the webhook URL; Brevo offers no
     * provider signature of its own.
     *
     * The notification is a JSON post with an `items` array of parsed
     * emails (From/To/Cc mailbox objects, Subject, RawHtmlBody/RawTextBody,
     * MessageId, Attachments with DownloadTokens). Each item's MIME is
     * rebuilt from those fields — attachment bodies fetched through the
     * attachments API first — before running the shared import pipeline.
     *
     * @return \WP_Error|true|string WP_Error on failure, true if imported, 'duplicate' if skipped.
     */
    public function handleWebhook($request, $mailbox)
    {
        $payload = json_decode($request->get_body(), true);
        $items = is_array($payload) ? ($payload['items'] ?? null) : null;
        if (!is_array($items) || !$items) {
            return new \WP_Error('invalid_payload', 'Not a Brevo inbound notification', ['status' => 400]);
        }

        $settings = Mailbox::settingsOf($mailbox);

        $imported = false;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            // Fail closed on the first error: Brevo retries the whole post
            // and already-imported items dedup on the second pass
            $result = $this->importItem($item, $mailbox, $settings);
            if (is_wp_error($result)) {
                return $result;
            }
            if ($result === true) {
                $imported = true;
            }
        }

        return $imported ? true : 'duplicate';
    }

    /**
     * Rebuild and import one parsed email from the `items` array.
     *
     * @return \WP_Error|true|false WP_Error on failure, true if imported, false if duplicate.
     */
    private function importItem(array $item, $mailbox, array $settings)
    {
        // Values below come from an external sender — strip CR/LF before
        // splicing them into the header block (header injection)
        $sanitize = function ($value) {
            return trim(str_replace(["\r", "\n"], ' ', (string) $value));
        };

        $toList = $this->formatAddresses($item['To'] ?? []);
        $ccList = $this->formatAddresses($item['Cc'] ?? []);

        $headers = [
            'From: ' . $this->formatAddress($item['From'] ?? []),
            'To: ' . ($toList !== '' ? $toList : (string) $mailbox->email),
            'Subject: ' . $sanitize($item['Subject'] ?? '(No Subject)'),
        ];
        if ($ccList !== '') {
            $headers[] = 'Cc: ' . $ccList;
        }
        if (!empty($item['SentAtDate'])) {
            $headers[] = 'Date: ' . $sanitize($item['SentAtDate']);
        }
        if (!empty($item['InReplyTo'])) {
            $headers[] = 'In-Reply-To: ' . $sanitize($item['InReplyTo']);
        }

        $messageId = $sanitize($item['MessageId'] ?? '');
        if ($messageId === '') {
            // Stable content-derived id, written into the rebuilt MIME, so a
            // provider retry of the same notification dedups instead of
            // inserting the email again under a fresh uniqid
            $messageId = '<brevo-' . md5($this->formatAddress($item['From'] ?? []) . '|' . ($item['Subject'] ?? '')
                . '|' . (string) ($item['RawTextBody'] ?? $item['RawHtmlBody'] ?? '') . '|' . $toList) . '@inbound.brevo>';
        }
        $headers[] = 'Message-ID: ' . $messageId;

        $attachments = $this->downloadAttachments((array) ($item['Attachments'] ?? []), $settings);
        if (is_wp_error($attachments)) {
            return $attachments;
        }

        $html = $item['RawHtmlBody'] ?? '';
        if ($html === '' || $html === null) {
            $html = nl2br(esc_html((string) ($item['RawTextBody'] ?? '')));
        }

        $raw = InboundService::buildRawMime($headers, $html, $attachments);

        // A recipient address may belong to another connected mailbox (e.g. CC),
        // but the URL-addressed mailbox wins whenever it is itself among the
        // recipients — and stays authoritative when nothing matches
        // (routeInbound()'s default-mailbox fallback must not override it)
        $recipientMailbox = (int) $mailbox->id;
        $recipients = [];
        foreach (array_merge((array) ($item['To'] ?? []), (array) ($item['Cc'] ?? [])) as $entry) {
            $address = is_array($entry) ? trim((string) ($entry['Address'] ?? '')) : '';
            if ($address !== '' && strpos($address, '@') !== false) {
                $recipients[] = $address;
            }
        }
        if (!in_array(strtolower((string) $mailbox->email), array_map('strtolower', $recipients), true)) {
            foreach ($recipients as $recipient) {
                $match = Mailbox::findByEmail($recipient);
                if ($match) {
                    $recipientMailbox = (int) $match->id;
                    break;
                }
            }
        }

        return (new InboundService())->processFromContent($raw, $messageId, true, $recipientMailbox);
    }

    /**
     * Fetch attachment bodies through the attachments API using each
     * part's DownloadToken. Oversized parts are skipped up front (same
     * bounds buildRawMime enforces) so they are never downloaded; a
     * failed download aborts the import fail-closed so the provider
     * retry re-imports the whole message.
     *
     * @return array|\WP_Error [['name','content','type'], ...] with base64 content.
     */
    private function downloadAttachments(array $parts, array $settings)
    {
        if (!$parts) {
            return [];
        }
        if (empty($settings['api_key'])) {
            return new \WP_Error('brevo_error', 'Brevo API key is required to download inbound attachments');
        }

        $maxPart = (int) apply_filters('fluent_mailbox_max_attachment_bytes', 25 * MB_IN_BYTES);
        $maxTotal = (int) apply_filters('fluent_mailbox_max_attachments_total_bytes', 50 * MB_IN_BYTES);
        $totalBytes = 0;

        $attachments = [];
        foreach ($parts as $part) {
            if (!is_array($part) || empty($part['DownloadToken'])) {
                continue;
            }
            $declaredBytes = (int) ($part['ContentLength'] ?? 0);
            if ($declaredBytes > $maxPart || $totalBytes + $declaredBytes > $maxTotal) {
                Logger::log('Inbound attachment skipped: exceeds size limit', ['name' => (string) ($part['Name'] ?? ''), 'declared_bytes' => $declaredBytes, 'limit_bytes' => $maxPart]);
                continue;
            }

            $response = wp_remote_get(self::API_BASE . '/inbound/attachments/' . rawurlencode((string) $part['DownloadToken']), [
                'headers' => ['api-key' => $settings['api_key']],
                'timeout' => 30,
            ]);
            if (is_wp_error($response)) {
                return $response;
            }
            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                return new \WP_Error('brevo_error', 'Brevo attachment download failed (HTTP ' . $code . ')');
            }

            $body = wp_remote_retrieve_body($response);
            $totalBytes += strlen($body);
            $attachments[] = [
                'name' => (string) ($part['Name'] ?? ''),
                'content' => base64_encode($body),
                'type' => (string) ($part['ContentType'] ?? ''),
            ];
        }

        return $attachments;
    }

    /**
     * One Brevo mailbox object ({Name, Address}) as a header-safe
     * "Name" <address> string.
     */
    private function formatAddress($entry)
    {
        $address = is_array($entry) ? trim(str_replace(["\r", "\n"], '', (string) ($entry['Address'] ?? ''))) : '';
        $name = is_array($entry) ? trim(preg_replace('/[\x00-\x1f"\\\\]/', '', (string) ($entry['Name'] ?? ''))) : '';
        if ($address === '') {
            return '';
        }
        return $name !== '' ? '"' . $name . '" <' . $address . '>' : $address;
    }

    /**
     * A Brevo mailbox-object array as a comma-joined address header value.
     */
    private function formatAddresses($entries)
    {
        $formatted = [];
        foreach ((array) $entries as $entry) {
            $value = $this->formatAddress($entry);
            if ($value !== '') {
                $formatted[] = $value;
            }
        }
        return implode(', ', $formatted);
    }
}
