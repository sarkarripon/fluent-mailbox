<?php

namespace FluentMailbox\Services\Drivers;

use FluentMailbox\Models\Mailbox;
use FluentMailbox\Services\Contracts\MailDriverInterface;
use FluentMailbox\Services\InboundService;
use FluentMailbox\Services\Logger;

/**
 * Elastic Email API driver. Sends through the v4 transactional API and
 * receives through an Inbound Route (ActionType "NotifyViaHttp") pointed
 * at the plugin webhook. Elastic Email posts parsed fields rather than
 * raw MIME, so the message is rebuilt from header_list + bodies +
 * base64 attachment fields before import.
 */
class ElasticEmailDriver implements MailDriverInterface
{
    const API_BASE = 'https://api.elasticemail.com/v4';

    public static function fields()
    {
        return [
            ['key' => 'api_key', 'label' => __('API key', 'fluent-mailbox'), 'type' => 'password', 'group' => 'elasticemail', 'secret' => true,
             'help' => __('Elastic Email → Settings → Manage API Keys. The key needs at least "Send Emails" and "View Domains" permissions. For receiving, point your domain\'s MX at mx.inbound.elasticemail.com and create an Inbound Route with action "Notify via HTTP" targeting this mailbox\'s webhook URL.', 'fluent-mailbox'), 'default' => ''],
        ];
    }

    public function testConnection(array $settings = [])
    {
        if (empty($settings['api_key'])) {
            return new \WP_Error('missing_creds', __('Elastic Email API key is required.', 'fluent-mailbox'));
        }

        $response = wp_remote_get(self::API_BASE . '/domains?limit=1', [
            'headers' => ['X-ElasticEmail-ApiKey' => $settings['api_key'], 'Accept' => 'application/json'],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $message = $body['Error'] ?? 'Elastic Email rejected the API key (HTTP ' . $code . ')';
            return new \WP_Error('elasticemail_error', $message);
        }

        return true;
    }

    public function send(array $args, array $settings = [])
    {
        if (empty($settings['api_key'])) {
            return new \WP_Error('elasticemail_error', __('Elastic Email API key is required.', 'fluent-mailbox'));
        }

        $payload = [
            'Recipients' => [
                'To' => $this->addressList($args['to']),
            ],
            'Content' => [
                'From' => !empty($args['from_name'])
                    ? $args['from_name'] . ' <' . $args['from_email'] . '>'
                    : $args['from_email'],
                'Subject' => $args['subject'],
                'Body' => [
                    ['ContentType' => 'HTML', 'Content' => $args['body'], 'Charset' => 'utf-8'],
                    ['ContentType' => 'PlainText', 'Content' => wp_strip_all_tags($args['body']), 'Charset' => 'utf-8'],
                ],
            ],
        ];
        if (!empty($args['cc'])) {
            $payload['Recipients']['CC'] = $this->addressList($args['cc']);
        }
        if (!empty($args['bcc'])) {
            $payload['Recipients']['BCC'] = $this->addressList($args['bcc']);
        }
        if (!empty($args['reply_to'])) {
            $payload['Content']['ReplyTo'] = $args['reply_to'];
        }

        $attachments = array_filter((array) ($args['attachments'] ?? []), 'file_exists');
        if (!empty($attachments)) {
            $payload['Content']['Attachments'] = array_map(function ($filePath) {
                $type = wp_check_filetype(basename($filePath));
                return [
                    'BinaryContent' => base64_encode(file_get_contents($filePath)),
                    'Name' => basename($filePath),
                    'ContentType' => $type['type'] ?: 'application/octet-stream',
                ];
            }, array_values($attachments));
        }

        $response = wp_remote_post(self::API_BASE . '/emails/transactional', [
            'headers' => [
                'X-ElasticEmail-ApiKey' => $settings['api_key'],
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
            $message = $result['Error'] ?? 'Elastic Email send failed (HTTP ' . $code . ')';
            Logger::log('Elastic Email send failed', ['error' => $message]);
            return new \WP_Error('elasticemail_error', $message);
        }

        // Any 2xx means accepted — a missing/renamed id field must not
        // read as failure, or the user resends and delivers twice
        return $result['MessageID'] ?? $result['TransactionID'] ?? ('ee_sent_' . uniqid());
    }

    /**
     * Normalize a to/cc/bcc value (comma-separated string or array)
     * into one address per element, as the v4 API expects.
     */
    private function addressList($value)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        return array_values(array_filter(array_map('trim', (array) $value)));
    }

    /**
     * Elastic Email is push-only inbound; nothing to poll.
     */
    public function fetchNewEmails($mailbox)
    {
        return 0;
    }

    /**
     * Import an Elastic Email inbound notification. The router has already
     * verified the per-mailbox secret in the webhook URL; Elastic Email
     * offers no provider signature of its own.
     *
     * The notification is a form POST of parsed fields (from_email,
     * subject, body_html/body_text, header_list, attN_name/attN_content),
     * so a MIME message is rebuilt from the original headers and the
     * base64 attachments before running the shared import pipeline.
     *
     * @return \WP_Error|true|string WP_Error on failure, true if imported, 'duplicate' if skipped.
     */
    public function handleWebhook($request, $mailbox)
    {
        $params = $request->get_params();

        $headerList = (string) ($params['header_list'] ?? '');
        if ($headerList === '' && empty($params['from_email'])) {
            return new \WP_Error('invalid_payload', 'Not an Elastic Email inbound notification', ['status' => 400]);
        }

        // Keep the original headers (From, To, Cc, Date, Message-ID, ...)
        // but drop the ones describing the original body structure — the
        // body is rebuilt below from the parsed fields
        $headers = [];
        $skipping = false;
        foreach (preg_split('/\r\n|\n/', $headerList) as $line) {
            if ($line === '') {
                continue;
            }
            if (preg_match('/^[ \t]/', $line)) { // folded continuation line
                if (!$skipping) {
                    $headers[] = $line;
                }
                continue;
            }
            $name = strtolower((string) strstr($line, ':', true));
            $skipping = in_array($name, ['content-type', 'content-transfer-encoding', 'mime-version'], true);
            if (!$skipping) {
                $headers[] = $line;
            }
        }

        $headerBlock = implode("\r\n", $headers);

        // Values below come from an external sender — strip CR/LF before
        // splicing them into the header block (header injection)
        $sanitize = function ($value) {
            return trim(str_replace(["\r", "\n"], ' ', (string) $value));
        };

        if (!preg_match('/^from:/im', $headerBlock)) {
            $from = !empty($params['from_name'])
                ? $sanitize($params['from_name']) . ' <' . $sanitize($params['from_email'] ?? '') . '>'
                : $sanitize($params['from_email'] ?? '');
            $headers[] = 'From: ' . $from;
        }
        if (!preg_match('/^to:/im', $headerBlock)) {
            $headers[] = 'To: ' . str_replace(["\r\n", "\r", "\n"], ', ', trim((string) ($params['to_list'] ?? $mailbox->email)));
        }
        if (!preg_match('/^subject:/im', $headerBlock) && isset($params['subject'])) {
            $headers[] = 'Subject: ' . $sanitize($params['subject']);
        }

        $messageId = '';
        if (preg_match('/^message-id:\s*(.+)$/im', $headerBlock, $m)) {
            $messageId = trim($m[1]);
        }
        if (!$messageId) {
            // Stable content-derived id, written into the rebuilt MIME, so a
            // provider retry of the same notification dedups instead of
            // inserting the email again under a fresh uniqid
            $messageId = '<ee-' . md5(($params['from_email'] ?? '') . '|' . ($params['subject'] ?? '')
                . '|' . (string) ($params['body_text'] ?? $params['body_html'] ?? '') . '|' . $headerBlock) . '@inbound.elasticemail>';
            $headers[] = 'Message-ID: ' . $messageId;
        }
        $fallbackId = $messageId;

        // Attachments arrive as att1_name/att1_content, att2_name/... (base64).
        // Scan keys rather than counting up so gapped numbering still imports
        $attachments = [];
        foreach ($params as $key => $value) {
            if (preg_match('/^att(\d+)_content$/', (string) $key, $m)) {
                $attachments[(int) $m[1]] = [
                    'name' => (string) ($params['att' . $m[1] . '_name'] ?? ('attachment-' . $m[1])),
                    'content' => (string) $value,
                ];
            }
        }
        // A multipart post can deliver attachments as file parts instead
        foreach ((array) $request->get_file_params() as $key => $file) {
            if (preg_match('/^att(\d+)_content$/', (string) $key, $m)
                && !empty($file['tmp_name']) && is_readable($file['tmp_name'])) {
                $attachments[(int) $m[1]] = [
                    'name' => (string) ($params['att' . $m[1] . '_name'] ?? ($file['name'] ?? ('attachment-' . $m[1]))),
                    'content' => base64_encode(file_get_contents($file['tmp_name'])),
                ];
            }
        }
        ksort($attachments);

        $html = $params['body_html'] ?? '';
        if ($html === '' || $html === null) {
            $html = nl2br(esc_html((string) ($params['body_text'] ?? '')));
        }

        if ($attachments) {
            $boundary = 'ee-' . md5($fallbackId . wp_rand());
            $raw = implode("\r\n", $headers) . "\r\n"
                . "MIME-Version: 1.0\r\n"
                . 'Content-Type: multipart/mixed; boundary="' . $boundary . "\"\r\n\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
                . $html . "\r\n";
            foreach ($attachments as $att) {
                // Sender-controlled filename lands inside part headers —
                // strip quotes and all control characters
                $filename = preg_replace('/[\x00-\x1f"\\\\]/', '', $att['name']);
                $type = wp_check_filetype($filename);
                // Form-urlencoded parsing decodes literal '+' to a space —
                // map spaces back to '+' before stripping line breaks
                $content = str_replace(["\r", "\n"], '', str_replace(' ', '+', $att['content']));
                $raw .= '--' . $boundary . "\r\n"
                    . 'Content-Type: ' . ($type['type'] ?: 'application/octet-stream') . '; name="' . $filename . "\"\r\n"
                    . "Content-Transfer-Encoding: base64\r\n"
                    . 'Content-Disposition: attachment; filename="' . $filename . "\"\r\n\r\n"
                    . chunk_split($content) . "\r\n";
            }
            $raw .= '--' . $boundary . "--\r\n";
        } else {
            $raw = implode("\r\n", $headers) . "\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
                . $html;
        }

        // A recipient address may belong to another connected mailbox (e.g. CC),
        // but the URL-addressed mailbox wins whenever it is itself among the
        // recipients — and stays authoritative when nothing matches
        // (routeInbound()'s default-mailbox fallback must not override it)
        $recipientMailbox = (int) $mailbox->id;
        $recipients = [];
        foreach ((array) preg_split('/\r\n|\n/', trim((string) ($params['env_to_list'] ?? $params['to_list'] ?? ''))) as $recipient) {
            if (preg_match('/<([^>]+)>/', $recipient, $m)) {
                $recipient = $m[1];
            }
            $recipient = trim($recipient);
            if ($recipient !== '' && strpos($recipient, '@') !== false) {
                $recipients[] = $recipient;
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

        $result = (new InboundService())->processFromContent($raw, $fallbackId, true, $recipientMailbox);

        if (is_wp_error($result)) {
            return $result;
        }

        return $result === false ? 'duplicate' : true;
    }
}
