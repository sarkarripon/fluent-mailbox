<?php

namespace FluentMailbox\Http\Controllers;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use FluentMailbox\Models\Mailbox;
use FluentMailbox\Services\AwsSetupService;
use FluentMailbox\Services\SyncService;

/**
 * The AWS wizard reads from and writes to a SES-type mailbox row —
 * the legacy fluent_mailbox_aws_* options are only ever read by the
 * database migration.
 */
class SettingsController
{
    /**
     * The SES mailbox the settings wizard manages: the first active
     * SES mailbox, else the first SES mailbox at all.
     */
    private function sesMailbox()
    {
        $all = Mailbox::findAllByDriver('ses');
        foreach ($all as $mailbox) {
            if ($mailbox->is_active) {
                return $mailbox;
            }
        }
        return $all ? $all[0] : null;
    }

    public function getSettings($request)
    {
        $mailbox = $this->sesMailbox();
        $settings = $mailbox ? Mailbox::settingsOf($mailbox) : [];

        return rest_ensure_response([
            'region' => $settings['region'] ?? 'us-east-1',
            'key' => !empty($settings['key']) ? $this->mask($settings['key']) : '',
            'secret' => !empty($settings['secret']) ? $this->mask($settings['secret']) : '',
            'from_email' => ($mailbox && $mailbox->is_active) ? $mailbox->email : '',
            'sender_name' => $mailbox->from_name ?? '',
            'mailbox_id' => $mailbox ? (int) $mailbox->id : null,
            'inbound_configured' => !empty($settings['inbound_bucket'])
        ]);
    }

    public function verifyCredentials($request)
    {
        $region = $request->get_param('region');
        $key = $request->get_param('key');
        $secret = $request->get_param('secret');

        // Masked values round-tripped from the UI mean "use what's stored"
        $stored = null;
        if (($key && $this->isMasked($key)) || ($secret && $this->isMasked($secret))) {
            $mailbox = $this->sesMailbox();
            $stored = $mailbox ? Mailbox::settingsOf($mailbox) : [];
        }
        if ($key && $this->isMasked($key) && !empty($stored['key'])) {
            $key = $stored['key'];
        }
        if ($secret && $this->isMasked($secret) && !empty($stored['secret'])) {
            $secret = $stored['secret'];
        }

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
        $mailbox = $this->sesMailbox();

        // The wizard's "Disconnect" sends empty values: deactivate the
        // SES mailbox instead of deleting it (emails keep their assignment,
        // reconnecting reactivates it)
        if (isset($params['key'], $params['from_email']) && $params['key'] === '' && $params['from_email'] === '') {
            if ($mailbox) {
                Mailbox::update($mailbox->id, ['is_active' => 0]);
                SyncService::ensureCronSchedule();
            }
            return rest_ensure_response(['message' => 'Disconnected']);
        }

        $fromEmail = !empty($params['from_email']) ? sanitize_email($params['from_email']) : null;

        // Merge driver settings; masked values mean "unchanged"
        $settings = $mailbox ? Mailbox::settingsOf($mailbox) : [];
        if (!empty($params['region'])) {
            $settings['region'] = sanitize_text_field($params['region']);
        }
        if (!empty($params['key']) && !$this->isMasked($params['key'])) {
            $settings['key'] = sanitize_text_field($params['key']);
        }
        if (!empty($params['secret']) && !$this->isMasked($params['secret'])) {
            $settings['secret'] = sanitize_text_field($params['secret']);
        }
        if ($fromEmail) {
            $settings['identity'] = $fromEmail;
        }

        if ($mailbox) {
            $data = ['driver_settings' => $settings, 'is_active' => 1];
            if ($fromEmail) {
                $data['email'] = $fromEmail;
            }
            if (isset($params['sender_name'])) {
                $data['from_name'] = sanitize_text_field($params['sender_name']);
            }
            Mailbox::update($mailbox->id, $data);
            $mailboxId = (int) $mailbox->id;
        } else {
            if (!$fromEmail || empty($settings['key']) || empty($settings['secret'])) {
                return new \WP_Error('missing_params', 'AWS credentials and a sender identity are required', ['status' => 400]);
            }
            $mailboxId = Mailbox::create([
                'name' => 'Amazon SES',
                'email' => $fromEmail,
                'from_name' => sanitize_text_field($params['sender_name'] ?? ''),
                'driver' => 'ses',
                'driver_settings' => $settings,
                'category' => 'business',
                'is_active' => 1,
                'is_default' => 0, // Mailbox::create promotes the first mailbox to default
            ]);
            if (!$mailboxId) {
                return new \WP_Error('db_error', 'Could not create mailbox', ['status' => 500]);
            }
        }

        SyncService::ensureCronSchedule();

        return rest_ensure_response(['message' => 'Settings saved successfully', 'mailbox_id' => (int) $mailboxId]);
    }

    public function setupInbound($request)
    {
        $mailbox = $this->sesMailbox();
        $settings = $mailbox ? Mailbox::settingsOf($mailbox) : [];

        if (!$mailbox || empty($settings['key']) || empty($settings['secret'])) {
            return new \WP_Error('params', 'Please save credentials first.', ['status' => 400]);
        }

        // SNS subscribes to the per-mailbox webhook endpoint, which
        // carries this mailbox's inbound secret
        $webhookUrl = Mailbox::webhookUrl($mailbox);
        if (!$webhookUrl) {
            return new \WP_Error('params', 'Mailbox has no inbound secret. Re-save the connection first.', ['status' => 400]);
        }

        $service = new AwsSetupService($settings['region'] ?? 'us-east-1', $settings['key'], $settings['secret']);
        $result = $service->setup($webhookUrl);

        if (is_wp_error($result)) {
            return $result;
        }

        // Persist the provisioned resources on the mailbox row
        $settings['inbound_bucket'] = $result['bucket'];
        $settings['sns_topic_arn'] = $result['topic'];
        Mailbox::update($mailbox->id, ['driver_settings' => $settings]);

        return rest_ensure_response($result);
    }

    public function disconnect($request)
    {
        // Clear the provisioned inbound resources from the SES mailbox
        $mailbox = $this->sesMailbox();
        if ($mailbox) {
            $settings = Mailbox::settingsOf($mailbox);
            unset($settings['inbound_bucket'], $settings['sns_topic_arn']);
            Mailbox::update($mailbox->id, ['driver_settings' => $settings]);
        }

        // Pre-1.1 installs also stored them in options
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

    public function getSignatures($request)
    {
        $signatures = get_option('fluent_mailbox_signatures', []);
        return rest_ensure_response($signatures);
    }

    public function saveSignature($request)
    {
        $name = $request->get_param('name');
        $content = $request->get_param('content');
        $is_default = $request->get_param('is_default');

        if (empty($name) || empty($content)) {
            return new \WP_Error('missing_params', 'Name and content are required', ['status' => 400]);
        }

        $signatures = get_option('fluent_mailbox_signatures', []);
        $id = count($signatures) + 1;

        $signature = [
            'id' => $id,
            'name' => sanitize_text_field($name),
            'content' => wp_kses_post($content),
            'is_default' => (bool) $is_default,
            'created_at' => current_time('mysql')
        ];

        // If this is default, unset others
        if ($is_default) {
            foreach ($signatures as &$sig) {
                $sig['is_default'] = false;
            }
        }

        $signatures[] = $signature;
        update_option('fluent_mailbox_signatures', $signatures);

        return rest_ensure_response($signature);
    }

    public function deleteSignature($request)
    {
        $id = (int) $request->get_param('id');
        $signatures = get_option('fluent_mailbox_signatures', []);
        $signatures = array_filter($signatures, function($sig) use ($id) {
            return $sig['id'] != $id;
        });
        update_option('fluent_mailbox_signatures', array_values($signatures));
        return rest_ensure_response(['message' => 'Signature deleted']);
    }

    public function getTemplates($request)
    {
        $templates = get_option('fluent_mailbox_templates', []);
        return rest_ensure_response($templates);
    }

    public function saveTemplate($request)
    {
        $name = $request->get_param('name');
        $subject = $request->get_param('subject');
        $body = $request->get_param('body');

        if (empty($name) || empty($body)) {
            return new \WP_Error('missing_params', 'Name and body are required', ['status' => 400]);
        }

        $templates = get_option('fluent_mailbox_templates', []);
        $id = count($templates) + 1;

        $template = [
            'id' => $id,
            'name' => sanitize_text_field($name),
            'subject' => sanitize_text_field($subject),
            'body' => wp_kses_post($body),
            'created_at' => current_time('mysql')
        ];

        $templates[] = $template;
        update_option('fluent_mailbox_templates', $templates);

        return rest_ensure_response($template);
    }

    public function deleteTemplate($request)
    {
        $id = (int) $request->get_param('id');
        $templates = get_option('fluent_mailbox_templates', []);
        $templates = array_filter($templates, function($tpl) use ($id) {
            return $tpl['id'] != $id;
        });
        update_option('fluent_mailbox_templates', array_values($templates));
        return rest_ensure_response(['message' => 'Template deleted']);
    }

    public function simulateWebhook($request)
    {
        $type = $request->get_param('type'); // 's3' or 'content'

        // Construct a fake payload similar to what AWS SES/SNS sends
        $payload = [
            'Type' => 'Notification',
            'MessageId' => 'sim_' . uniqid(),
            'Message' => ''
        ];

        $messageData = [
            'notificationType' => 'Received',
            'mail' => [
                'messageId' => 'sim_msg_' . uniqid(),
                'source' => 'sender@example.com',
                'commonHeaders' => [
                    'subject' => 'Test Simulation Email',
                    'from' => ['sender@example.com'],
                    'to' => ['recipient@example.com']
                ]
            ],
            'receipt' => [
                'action' => []
            ]
        ];

        if ($type === 's3') {
            // We need a real bucket/key for this to work fully, which is hard to simulate without uploading.
            // So for S3 simulation, we might fail unless we mock the S3 call or upload a test file first.
            // For now, let's simulates 'content' type primarily as it doesn't require S3 access.
            return new \WP_Error('not_implemented', 'S3 Simulation requires uploading a file first. Use "content" type.', ['status' => 501]);
        } else {
            // Simulate Direct Content
            $messageData['content'] = "Subject: Simulation Test\r\nFrom: sender@example.com\r\nTo: recipient@example.com\r\n\r\nThis is a simulated email body.";
        }

        $payload['Message'] = json_encode($messageData);
        
        // We need to call the WebhookController logic. 
        // We can instantiate it and call handle, wrapping our payload in a WP_REST_Request mock or just passing what it needs.
        // But WebhookController expects a request object.
        
        $simRequest = new \WP_REST_Request('POST', '/fluent-mailbox/v1/webhook');
        $simRequest->set_body(json_encode($payload));
        $simRequest->set_header('Content-Type', 'text/plain; charset=UTF-8');

        $controller = new \FluentMailbox\Http\Controllers\WebhookController();
        return $controller->handle($simRequest);
    }

    public function getDebugLog($request)
    {
        $log = \FluentMailbox\Services\Logger::getLog();
        return rest_ensure_response(['log' => $log]);
    }

    public function cleanDebugLog($request)
    {
        \FluentMailbox\Services\Logger::clean();
        return rest_ensure_response(['message' => 'Log cleaned']);
    }
}
