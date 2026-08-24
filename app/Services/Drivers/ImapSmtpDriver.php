<?php

namespace FluentMailbox\Services\Drivers;

use FluentMailbox\Models\Mailbox;
use FluentMailbox\Services\Contracts\MailDriverInterface;
use FluentMailbox\Services\InboundService;
use FluentMailbox\Services\Logger;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;

/**
 * Universal driver: pulls inbound mail from any IMAP server and
 * sends through the same account's SMTP server. Works with cPanel /
 * hosting email, Zoho Mail, Gmail (app password) and custom servers.
 *
 * Requires webklex/php-imap >= 5.3 (PHP 8.0+).
 */
class ImapSmtpDriver implements MailDriverInterface
{
    const INITIAL_SYNC_LIMIT = 20;

    public static function fields()
    {
        return [
            ['key' => 'preset', 'label' => __('Provider preset', 'fluent-mailbox'), 'type' => 'select', 'group' => 'imap',
             'options' => [
                 ['value' => 'gmail', 'label' => 'Gmail / Google Workspace'],
                 ['value' => 'zoho', 'label' => 'Zoho Mail'],
                 ['value' => 'cpanel', 'label' => 'cPanel / hosting email'],
                 ['value' => 'custom', 'label' => 'Custom / other'],
             ],
             'default' => 'cpanel',
             'help' => __('Auto-fills the server settings. Choose "Custom" for any other provider.', 'fluent-mailbox')],
            ['key' => 'imap_host', 'label' => __('IMAP host', 'fluent-mailbox'), 'type' => 'text', 'group' => 'imap', 'placeholder' => 'mail.example.com', 'default' => ''],
            ['key' => 'imap_port', 'label' => __('IMAP port', 'fluent-mailbox'), 'type' => 'number', 'group' => 'imap', 'default' => 993],
            ['key' => 'imap_encryption', 'label' => __('IMAP encryption', 'fluent-mailbox'), 'type' => 'select', 'group' => 'imap',
             'options' => [
                 ['value' => 'ssl', 'label' => 'SSL (port 993)'],
                 ['value' => 'tls', 'label' => 'TLS'],
                 ['value' => 'none', 'label' => 'None'],
             ],
             'default' => 'ssl'],
            ['key' => 'imap_username', 'label' => __('Username', 'fluent-mailbox'), 'type' => 'text', 'group' => 'imap', 'placeholder' => 'you@example.com', 'default' => ''],
            ['key' => 'imap_password', 'label' => __('Password', 'fluent-mailbox'), 'type' => 'password', 'group' => 'imap', 'secret' => true,
             'help' => __('For Gmail and Zoho use an app-specific password, not your main account password.', 'fluent-mailbox'), 'default' => ''],
            ['key' => 'folder', 'label' => __('Inbox folder', 'fluent-mailbox'), 'type' => 'text', 'group' => 'imap', 'default' => 'INBOX'],

            ['key' => 'smtp_host', 'label' => __('SMTP host', 'fluent-mailbox'), 'type' => 'text', 'group' => 'smtp', 'placeholder' => 'mail.example.com', 'default' => ''],
            ['key' => 'smtp_port', 'label' => __('SMTP port', 'fluent-mailbox'), 'type' => 'number', 'group' => 'smtp', 'default' => 587],
            ['key' => 'smtp_encryption', 'label' => __('SMTP encryption', 'fluent-mailbox'), 'type' => 'select', 'group' => 'smtp',
             'options' => [
                 ['value' => 'tls', 'label' => 'TLS (port 587)'],
                 ['value' => 'ssl', 'label' => 'SSL (port 465)'],
                 ['value' => 'none', 'label' => 'None'],
             ],
             'default' => 'tls'],
            ['key' => 'smtp_username', 'label' => __('SMTP username', 'fluent-mailbox'), 'type' => 'text', 'group' => 'smtp',
             'help' => __('Leave blank to reuse the IMAP username.', 'fluent-mailbox'), 'default' => ''],
            ['key' => 'smtp_password', 'label' => __('SMTP password', 'fluent-mailbox'), 'type' => 'password', 'group' => 'smtp', 'secret' => true,
             'help' => __('Leave blank to reuse the IMAP password.', 'fluent-mailbox'), 'default' => ''],

            ['key' => 'mark_as_read', 'label' => __('Mark messages as read on the server after importing', 'fluent-mailbox'), 'type' => 'checkbox', 'group' => 'options', 'default' => 0],
            ['key' => 'append_to_sent', 'label' => __('Save sent emails to the server\'s Sent folder', 'fluent-mailbox'), 'type' => 'checkbox', 'group' => 'options', 'default' => 0],
        ];
    }

    /**
     * Provider presets used by the frontend to auto-fill server fields.
     */
    public static function presets()
    {
        $mailSubdomain = 'mail.' . wp_parse_url(home_url(), PHP_URL_HOST);

        return [
            'gmail' => [
                'label' => 'Gmail / Google Workspace',
                'imap_host' => 'imap.gmail.com', 'imap_port' => 993, 'imap_encryption' => 'ssl',
                'smtp_host' => 'smtp.gmail.com', 'smtp_port' => 587, 'smtp_encryption' => 'tls',
                'help' => __('In Gmail: enable IMAP (Settings → Forwarding and POP/IMAP), enable 2-Step Verification, then create an App Password (Accounts → Security → 2-Step Verification → App passwords) and use it here.', 'fluent-mailbox'),
            ],
            'zoho' => [
                'label' => 'Zoho Mail',
                'imap_host' => 'imap.zoho.com', 'imap_port' => 993, 'imap_encryption' => 'ssl',
                'smtp_host' => 'smtp.zoho.com', 'smtp_port' => 465, 'smtp_encryption' => 'ssl',
                'help' => __('In Zoho: enable IMAP/SMTP access (Settings → Mail Accounts → IMAP/POP), then generate an Application-specific Password and use it here.', 'fluent-mailbox'),
            ],
            'cpanel' => [
                'label' => 'cPanel / hosting email',
                'imap_host' => $mailSubdomain, 'imap_port' => 993, 'imap_encryption' => 'ssl',
                'smtp_host' => $mailSubdomain, 'smtp_port' => 465, 'smtp_encryption' => 'ssl',
                'help' => __('Use the full email address as username and the password you set in your hosting panel (cPanel → Email Accounts).', 'fluent-mailbox'),
            ],
            'custom' => [
                'label' => 'Custom / other',
                'help' => __('Enter the IMAP/SMTP server details from your email provider.', 'fluent-mailbox'),
            ],
        ];
    }

    public function testConnection(array $settings = [])
    {
        try {
            $client = $this->connect($settings);
            $folder = $client->getFolder($settings['folder'] ?? 'INBOX');
            if (!$folder) {
                return new \WP_Error('imap_error', __('IMAP connected, but the configured folder was not found.', 'fluent-mailbox'));
            }
            $client->disconnect();
        } catch (\Exception $e) {
            return new \WP_Error('imap_error', $e->getMessage());
        }

        $smtpError = $this->testSmtp($settings);
        if (is_wp_error($smtpError)) {
            return $smtpError;
        }

        return true;
    }

    public function send(array $args, array $settings = [])
    {
        $phpMailer = $this->makeMailer($settings);

        $fromEmail = !empty($args['from_email']) ? $args['from_email'] : '';
        $fromName = !empty($args['from_name']) ? $args['from_name'] : '';
        try {
            $phpMailer->setFrom($fromEmail, $fromName);
            if (!empty($args['reply_to'])) {
                $phpMailer->addReplyTo($args['reply_to']);
            }

            foreach ($this->toAddresses($args['to']) as $address) {
                $phpMailer->addAddress($address);
            }
            foreach ($this->toAddresses($args['cc'] ?? []) as $address) {
                $phpMailer->addCC($address);
            }
            foreach ($this->toAddresses($args['bcc'] ?? []) as $address) {
                $phpMailer->addBCC($address);
            }

            $phpMailer->isHTML(true);
            $phpMailer->Subject = $args['subject'];
            $phpMailer->Body = $args['body'];
            $phpMailer->AltBody = wp_strip_all_tags($args['body']);

            foreach ((array) ($args['attachments'] ?? []) as $filePath) {
                if (file_exists($filePath)) {
                    $phpMailer->addAttachment($filePath, basename($filePath));
                }
            }

            $phpMailer->send();
        } catch (\Exception $e) {
            Logger::log('SMTP send failed', ['error' => $e->getMessage(), 'mailbox' => $fromEmail]);
            return new \WP_Error('smtp_error', $e->getMessage());
        }

        $messageId = $phpMailer->getLastID();

        if (!empty($settings['append_to_sent'])) {
            $this->appendToSentFolder($settings, $phpMailer->getSentMIMEMessage(), $messageId);
        }

        return $messageId ?: ('smtp_' . uniqid());
    }

    public function fetchNewEmails($mailbox)
    {
        $settings = Mailbox::settingsOf($mailbox);
        $state = Mailbox::syncStateOf($mailbox);

        try {
            $client = $this->connect($settings);
        } catch (\Exception $e) {
            Logger::log('IMAP connect failed', ['mailbox' => $mailbox->email, 'error' => $e->getMessage()]);
            return new \WP_Error('imap_error', $e->getMessage());
        }

        try {
            $folderName = $settings['folder'] ?: 'INBOX';
            $folder = $client->getFolder($folderName);
            if (!$folder) {
                throw new \Exception("Folder not found: $folderName");
            }

            $lastUid = (int) ($state['last_uid'] ?? 0);

            if ($lastUid > 0) {
                $messages = $folder->query()->leaveUnread()->getByUidGreater($lastUid);
            } else {
                // First sync: only pull the most recent messages, not the whole history
                $messages = $folder->query()->leaveUnread()->limit(self::INITIAL_SYNC_LIMIT)->get();
            }

            $inbound = new InboundService();
            $imported = 0;
            $maxUid = $lastUid;

            foreach ($messages as $message) {
                $uid = (int) $message->getUid();
                if ($uid <= $lastUid) {
                    continue;
                }

                $raw = trim($message->getHeader()->raw) . "\r\n\r\n" . $message->getRawBody();
                $result = $inbound->processFromContent($raw, 'imap_' . $mailbox->id . '_' . $uid, true, (int) $mailbox->id);

                if (!is_wp_error($result) && $result !== false) {
                    $imported++;
                    if (!empty($settings['mark_as_read']) && method_exists($message, 'setFlag')) {
                        try {
                            $message->setFlag('\\Seen');
                        } catch (\Exception $e) {
                            Logger::log('Could not mark message as read', ['uid' => $uid, 'error' => $e->getMessage()]);
                        }
                    }
                }

                $maxUid = max($maxUid, $uid);
            }

            $state['last_uid'] = $maxUid;
            Mailbox::updateSyncState($mailbox->id, $state);

            $client->disconnect();
            return $imported;
        } catch (\Exception $e) {
            Logger::log('IMAP fetch failed', ['mailbox' => $mailbox->email, 'error' => $e->getMessage()]);
            return new \WP_Error('imap_error', $e->getMessage());
        }
    }

    private function connect(array $settings)
    {
        if (empty($settings['imap_host']) || empty($settings['imap_username'])) {
            throw new \Exception(__('IMAP host and username are required.', 'fluent-mailbox'));
        }

        $encryption = ($settings['imap_encryption'] ?? 'ssl') === 'none' ? false : ($settings['imap_encryption'] ?? 'ssl');

        $client = ClientManager::make([
            'host' => $settings['imap_host'],
            'port' => (int) ($settings['imap_port'] ?? 993),
            'encryption' => $encryption,
            'validate_cert' => true,
            'username' => $settings['imap_username'],
            'password' => (string) ($settings['imap_password'] ?? ''),
            'protocol' => 'imap',
        ]);

        $client->connect();

        return $client;
    }

    /**
     * @return \PHPMailer\PHPMailer\PHPMailer|\WP_Error Configured mailer on success.
     */
    private function makeMailer(array $settings)
    {
        if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'] ?? '';
        $mail->Port = (int) ($settings['smtp_port'] ?? 587);

        $encryption = $settings['smtp_encryption'] ?? 'tls';
        $mail->SMTPSecure = $encryption === 'none' ? '' : $encryption;

        $username = !empty($settings['smtp_username']) ? $settings['smtp_username'] : ($settings['imap_username'] ?? '');
        $password = (string) (!empty($settings['smtp_password']) ? $settings['smtp_password'] : ($settings['imap_password'] ?? ''));
        if ($username !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
        }

        $mail->Timeout = 20;
        $mail->CharSet = 'UTF-8';

        return $mail;
    }

    private function testSmtp(array $settings)
    {
        if (empty($settings['smtp_host'])) {
            return new \WP_Error('smtp_error', __('SMTP host is required.', 'fluent-mailbox'));
        }

        $mail = $this->makeMailer($settings);
        try {
            if (!$mail->smtpConnect()) {
                return new \WP_Error('smtp_error', __('Could not connect to the SMTP server.', 'fluent-mailbox'));
            }
            $mail->smtpQuit();
        } catch (\Exception $e) {
            return new \WP_Error('smtp_error', $e->getMessage());
        }

        return true;
    }

    private function appendToSentFolder(array $settings, $mimeMessage)
    {
        try {
            $client = $this->connect($settings);
            $folder = $client->getFolderByName('Sent') ?: $client->getFolderByName('INBOX.Sent') ?: $client->getFolderByName('Sent Messages') ?: $client->getFolderByName('Sent Items');
            if ($folder && method_exists($folder, 'appendMessage')) {
                $folder->appendMessage($mimeMessage);
            }
            $client->disconnect();
        } catch (\Exception $e) {
            Logger::log('Could not append message to Sent folder', ['error' => $e->getMessage()]);
        }
    }

    private function toAddresses($value)
    {
        if (empty($value)) {
            return [];
        }
        return array_filter(array_map('trim', is_array($value) ? $value : explode(',', $value)));
    }
}
