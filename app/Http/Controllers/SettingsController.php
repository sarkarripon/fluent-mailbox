<?php

namespace FluentMailbox\Http\Controllers;

class SettingsController
{
    public function getSettings($request)
    {
        $settings = [
            'region' => get_option('fluent_mailbox_aws_region', 'us-east-1'),
            'key' => $this->mask(get_option('fluent_mailbox_aws_key', '')),
            'secret' => $this->mask(get_option('fluent_mailbox_aws_secret', '')),
            'from_email' => get_option('fluent_mailbox_from_email', get_bloginfo('admin_email')),
        ];

        return rest_ensure_response($settings);
    }

    public function saveSettings($request)
    {
        $params = $request->get_params();

        // Only update if provided and not masked
        if (!empty($params['region'])) {
            update_option('fluent_mailbox_aws_region', sanitize_text_field($params['region']));
        }
        
        if (!empty($params['from_email'])) {
            update_option('fluent_mailbox_from_email', sanitize_email($params['from_email']));
        }

        if (!empty($params['key']) && !$this->isMasked($params['key'])) {
            update_option('fluent_mailbox_aws_key', sanitize_text_field($params['key']));
        }

        if (!empty($params['secret']) && !$this->isMasked($params['secret'])) {
            update_option('fluent_mailbox_aws_secret', sanitize_text_field($params['secret']));
        }

        return rest_ensure_response(['message' => 'Settings saved successfully']);
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
