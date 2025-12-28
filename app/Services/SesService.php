<?php

namespace FluentMailbox\Services;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

class SesService
{
    private $client;
    private $senderEmail;

    public function __construct()
    {
        // Settings should ideally come from WP Options
        $region = get_option('fluent_mailbox_aws_region', 'us-east-1');
        $key = get_option('fluent_mailbox_aws_key', '');
        $secret = get_option('fluent_mailbox_aws_secret', '');
        $sender = get_option('fluent_mailbox_from_email', get_bloginfo('admin_email'));
        
        // If sender is just a domain (verified domain identity), assume a default user
        if (strpos($sender, '@') === false) {
            $sender = 'contact@' . $sender;
        }

        // Add friendly name if set
        $name = get_option('fluent_mailbox_from_name', '');
        if ($name) {
            $sender = "$name <$sender>";
        }
        
        $this->senderEmail = $sender;

        if ($key && $secret) {
            $this->client = new SesClient([
                'version' => 'latest',
                'region'  => $region,
                'credentials' => [
                    'key'    => $key,
                    'secret' => $secret,
                ],
            ]);
        }
    }

    public function sendEmail($to, $subject, $body)
    {
        if (!$this->client) {
            return new \WP_Error('ses_error', 'AWS Credentials not configured');
        }

        try {
            $result = $this->client->sendEmail([
                'Destination' => [
                    'ToAddresses' => is_array($to) ? $to : [$to],
                ],
                'ReplyToAddresses' => [$this->senderEmail],
                'Source' => $this->senderEmail,
                'Message' => [
                  'Body' => [
                      'Html' => [
                          'Charset' => 'UTF-8',
                          'Data' => $body,
                      ],
                      'Text' => [
                          'Charset' => 'UTF-8',
                          'Data' => strip_tags($body),
                      ],
                  ],
                  'Subject' => [
                      'Charset' => 'UTF-8',
                      'Data' => $subject,
                  ],
                ],
            ]);
            return $result['MessageId'];
        } catch (AwsException $e) {
            return new \WP_Error('ses_error', $e->getAwsErrorMessage());
        }
    }
}
