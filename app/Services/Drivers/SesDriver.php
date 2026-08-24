<?php

namespace FluentMailbox\Services\Drivers;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use FluentMailbox\Models\Mailbox;
use FluentMailbox\Services\Contracts\MailDriverInterface;
use FluentMailbox\Services\InboundService;
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
        $settings = Mailbox::settingsOf($mailbox);

        // Global options remain the fallback for mailboxes migrated from 1.0
        if (empty($settings['key']) && get_option('fluent_mailbox_aws_key')) {
            $settings['key'] = get_option('fluent_mailbox_aws_key');
            $settings['secret'] = get_option('fluent_mailbox_aws_secret');
            $settings['region'] = get_option('fluent_mailbox_aws_region', 'us-east-1');
        }

        $service = new InboundService($settings);
        return $service->fetchNewEmails(20, $mailbox);
    }
}
