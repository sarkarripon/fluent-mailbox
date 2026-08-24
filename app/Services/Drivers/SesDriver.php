<?php

namespace FluentMailbox\Services\Drivers;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use FluentMailbox\Models\Mailbox;
use FluentMailbox\Services\Contracts\MailDriverInterface;
use FluentMailbox\Services\InboundService;
use FluentMailbox\Services\Logger;
use FluentMailbox\Services\SesService;

/**
 * Amazon SES driver — wraps the existing SesService (send) and
 * InboundService (S3 pull) so legacy AWS installs keep working
 * behind the driver abstraction.
 */
class SesDriver implements MailDriverInterface
{
    public static function fields()
    {
        return [
            ['key' => 'region', 'label' => __('AWS region', 'fluent-mailbox'), 'type' => 'select', 'group' => 'aws',
             'options' => [
                 ['value' => 'us-east-1', 'label' => 'us-east-1'],
                 ['value' => 'us-west-2', 'label' => 'us-west-2'],
                 ['value' => 'eu-west-1', 'label' => 'eu-west-1'],
                 ['value' => 'eu-central-1', 'label' => 'eu-central-1'],
             ],
             'default' => 'us-east-1'],
            ['key' => 'key', 'label' => __('Access Key ID', 'fluent-mailbox'), 'type' => 'text', 'group' => 'aws', 'default' => ''],
            ['key' => 'secret', 'label' => __('Secret Access Key', 'fluent-mailbox'), 'type' => 'password', 'group' => 'aws', 'secret' => true, 'default' => ''],
            ['key' => 'identity', 'label' => __('Verified SES identity', 'fluent-mailbox'), 'type' => 'text', 'group' => 'aws',
             'help' => __('Email address or verified domain. For a bare domain, the mailbox address local-part is used as sender.', 'fluent-mailbox'), 'default' => ''],
        ];
    }

    public function testConnection(array $settings = [])
    {
        if (empty($settings['key']) || empty($settings['secret'])) {
            return new \WP_Error('missing_creds', __('AWS Key and Secret are required.', 'fluent-mailbox'));
        }

        try {
            $client = new SesClient([
                'version' => 'latest',
                'region'  => $settings['region'] ?? 'us-east-1',
                'credentials' => [
                    'key'    => $settings['key'],
                    'secret' => $settings['secret'],
                ],
            ]);
            $client->listIdentities();
            return true;
        } catch (AwsException $e) {
            return new \WP_Error('aws_error', $e->getAwsErrorMessage());
        }
    }

    public function send(array $args, array $settings = [])
    {
        $fromEmail = !empty($args['from_email']) ? $args['from_email'] : ($settings['identity'] ?? '');
        if (!$fromEmail) {
            return new \WP_Error('ses_error', __('No sender identity configured for this mailbox.', 'fluent-mailbox'));
        }

        $ses = new SesService([
            'region' => $settings['region'] ?? null,
            'key' => $settings['key'] ?? null,
            'secret' => $settings['secret'] ?? null,
            'from_email' => $fromEmail,
            'from_name' => $args['from_name'] ?? '',
        ]);

        return $ses->sendEmail(
            $args['to'],
            $args['subject'],
            $args['body'],
            $args['cc'] ?? null,
            $args['bcc'] ?? null,
            (array) ($args['attachments'] ?? [])
        );
    }

    public function fetchNewEmails($mailbox)
    {
        $settings = $this->settingsWithLegacyFallback($mailbox);

        $service = new InboundService($settings);
        return $service->fetchNewEmails(20, $mailbox);
    }

    /**
     * Handle an SNS push notification for this mailbox (receipt-rule S3
     * action or direct SNS content). The router has already verified the
     * webhook URL's per-mailbox secret.
     *
     * @return \WP_Error|true|string WP_Error on failure, true if handled, 'duplicate' if skipped.
     */
    public function handleWebhook($request, $mailbox)
    {
        $payload = json_decode($request->get_body(), true);
        if (!is_array($payload)) {
            return new \WP_Error('invalid_payload', 'Invalid JSON', ['status' => 400]);
        }

        // SNS sends a subscription confirmation first — auto-confirm it
        if (($payload['Type'] ?? '') === 'SubscriptionConfirmation' && !empty($payload['SubscribeURL'])) {
            Logger::log('SNS subscription confirmation', ['mailbox' => $mailbox->email]);
            wp_remote_get($payload['SubscribeURL']);
            return true;
        }

        if (($payload['Type'] ?? '') !== 'Notification') {
            return new \WP_Error('invalid_payload', 'Unsupported SNS message type', ['status' => 400]);
        }

        $message = json_decode($payload['Message'] ?? '', true);
        if (!is_array($message) || ($message['notificationType'] ?? '') !== 'Received') {
            return new \WP_Error('invalid_payload', 'Not a receipt notification', ['status' => 400]);
        }

        $settings = $this->settingsWithLegacyFallback($mailbox);
        $receipt = $message['receipt'] ?? [];

        if (($receipt['action']['type'] ?? '') === 'S3' && !empty($receipt['action']['bucketName'])) {
            $result = (new InboundService($settings))->processFromS3(
                $receipt['action']['bucketName'],
                $receipt['action']['objectKey'],
                true,
                (int) $mailbox->id,
                $settings
            );
        } elseif (isset($message['content'])) {
            // Direct SNS content (small emails, no S3 action configured)
            $fallbackId = $message['mail']['messageId'] ?? uniqid('sns_');
            $result = (new InboundService())->processFromContent($message['content'], $fallbackId, true, (int) $mailbox->id);
        } else {
            return new \WP_Error('invalid_payload', 'Unsupported receipt action', ['status' => 400]);
        }

        if (is_wp_error($result)) {
            return $result;
        }

        return $result === false ? 'duplicate' : true;
    }

    /**
     * Mailbox settings, falling back to the pre-1.1 global AWS options
     * for mailboxes migrated from single-account installs.
     */
    private function settingsWithLegacyFallback($mailbox)
    {
        $settings = Mailbox::settingsOf($mailbox);

        if (empty($settings['key']) && get_option('fluent_mailbox_aws_key')) {
            $settings['key'] = get_option('fluent_mailbox_aws_key');
            $settings['secret'] = get_option('fluent_mailbox_aws_secret');
            $settings['region'] = get_option('fluent_mailbox_aws_region', 'us-east-1');
        }

        return $settings;
    }
}
