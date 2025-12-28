<?php

namespace FluentMailbox\Http\Controllers;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use FluentMailbox\Services\AwsSetupService;

class SettingsController
{
    public function getSettings($request)
    {
        $settings = [
            'region' => get_option('fluent_mailbox_aws_region', 'us-east-1'),
            'key' => $this->mask(get_option('fluent_mailbox_aws_key', '')),
            'secret' => $this->mask(get_option('fluent_mailbox_aws_secret', '')),
            'from_email' => get_option('fluent_mailbox_from_email', ''),
            'sender_name' => get_option('fluent_mailbox_from_name', ''),
            'inbound_configured' => (bool) get_option('fluent_mailbox_s3_bucket', false)
        ];

        return rest_ensure_response($settings);
    }

    public function verifyCredentials($request)
    {
        $region = $request->get_param('region');
        $key = $request->get_param('key');
        $secret = $request->get_param('secret');

        if (empty($key) || empty($secret)) {
            return new \WP_Error('missing_creds', 'Key and Secret are required', ['status' => 400]);
        }

        try {
            $client = new SesClient([
                'version' => 'latest',
                'region'  => $region,
                'credentials' => [
                    'key'    => $key,
                    'secret' => $secret,
                ],
            ]);

            $result = $client->listIdentities();
            return rest_ensure_response([
                'success' => true,
                'identities' => $result['Identities']
            ]);

        } catch (AwsException $e) {
            return new \WP_Error('aws_error', $e->getAwsErrorMessage(), ['status' => 400]);
        }
    }

    public function saveConnection($request)
    {
        $params = $request->get_params();

        // Save Credentials
        if (isset($params['region'])) {
            if (empty($params['region'])) {
                delete_option('fluent_mailbox_aws_region');
            } else {
                update_option('fluent_mailbox_aws_region', sanitize_text_field($params['region']));
            }
        }
        
        if (isset($params['key'])) {
            if (empty($params['key'])) {
                 delete_option('fluent_mailbox_aws_key');
            } elseif (!$this->isMasked($params['key'])) {
                 update_option('fluent_mailbox_aws_key', sanitize_text_field($params['key']));
            }
        }

        if (isset($params['secret'])) {
            if (empty($params['secret'])) {
                 delete_option('fluent_mailbox_aws_secret');
            } elseif (!$this->isMasked($params['secret'])) {
                 update_option('fluent_mailbox_aws_secret', sanitize_text_field($params['secret']));
            }
        }

        if (isset($params['from_email'])) {
            if (empty($params['from_email'])) {
                 delete_option('fluent_mailbox_from_email');
            } else {
                 update_option('fluent_mailbox_from_email', sanitize_email($params['from_email']));
            }
        }

        if (isset($params['sender_name'])) {
            if (empty($params['sender_name'])) {
                 delete_option('fluent_mailbox_from_name');
            } else {
                 update_option('fluent_mailbox_from_name', sanitize_text_field($params['sender_name']));
            }
        }

        return rest_ensure_response(['message' => 'Settings saved successfully']);
    }

    public function setupInbound($request)
    {
        $region = get_option('fluent_mailbox_aws_region');
        $key = get_option('fluent_mailbox_aws_key');
        $secret = get_option('fluent_mailbox_aws_secret');
        
        if (!$key || !$secret) {
            return new \WP_Error('params', 'Please save credentials first.', ['status' => 400]);
        }

        $service = new AwsSetupService($region, $key, $secret);
        $webhookUrl = rest_url('fluent-mailbox/v1/webhook');
        
        // For local dev, maybe allow overriding?
        // $webhookUrl = 'https://mysite.com/webhook';

        $result = $service->setup($webhookUrl);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    public function disconnect($request)
    {
        delete_option('fluent_mailbox_s3_bucket');
        delete_option('fluent_mailbox_sns_topic_arn');
        
        return rest_ensure_response(['message' => 'Inbound configuration reset.']);
    }

    private function mask($string)
    {
        if (strlen($string) < 8) return '********';
        return substr($string, 0, 4) . '********' . substr($string, -4);
    }

    private function isMasked($string)
    {
        return strpos($string, '********') !== false;
    }
}
