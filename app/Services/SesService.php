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

    public function sendEmail($to, $subject, $body, $cc = null, $bcc = null, $attachments = [])
    {
        if (!$this->client) {
            return new \WP_Error('ses_error', 'AWS Credentials not configured');
        }

        try {
            $destination = [
                'ToAddresses' => is_array($to) ? $to : explode(',', $to),
            ];

            if ($cc) {
                $destination['CcAddresses'] = is_array($cc) ? $cc : explode(',', $cc);
            }

            if ($bcc) {
                $destination['BccAddresses'] = is_array($bcc) ? $bcc : explode(',', $bcc);
            }

            $params = [
                'Destination' => $destination,
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
            ];

            // For attachments, we need to use sendRawEmail instead
            if (!empty($attachments)) {
                return $this->sendRawEmail($to, $subject, $body, $cc, $bcc, $attachments);
            }

            $result = $this->client->sendEmail($params);
            return $result['MessageId'];
        } catch (AwsException $e) {
            return new \WP_Error('ses_error', $e->getAwsErrorMessage());
        }
    }

    private function sendRawEmail($to, $subject, $body, $cc, $bcc, $attachments)
    {
        // Build raw email message with attachments using MIME
        $boundary = uniqid('boundary_');
        $toAddresses = is_array($to) ? $to : explode(',', $to);
        $ccAddresses = $cc ? (is_array($cc) ? $cc : explode(',', $cc)) : [];
        $bccAddresses = $bcc ? (is_array($bcc) ? $bcc : explode(',', $bcc)) : [];

        $headers = "From: {$this->senderEmail}\r\n";
        $headers .= "Reply-To: {$this->senderEmail}\r\n";
        if (!empty($ccAddresses)) {
            $headers .= "Cc: " . implode(', ', $ccAddresses) . "\r\n";
        }
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $body . "\r\n";

        // Add attachments
        foreach ($attachments as $filePath) {
            if (file_exists($filePath)) {
                $fileName = basename($filePath);
                $fileContent = file_get_contents($filePath);
                $fileContentEncoded = chunk_split(base64_encode($fileContent));
                $fileMimeType = mime_content_type($filePath);

                $message .= "--{$boundary}\r\n";
                $message .= "Content-Type: {$fileMimeType}; name=\"{$fileName}\"\r\n";
                $message .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $message .= $fileContentEncoded . "\r\n";
            }
        }

        $message .= "--{$boundary}--";

        $allRecipients = array_merge($toAddresses, $ccAddresses, $bccAddresses);

        try {
            $result = $this->client->sendRawEmail([
                'RawMessage' => [
                    'Data' => $headers . "\r\n" . $message
                ],
                'Destinations' => $allRecipients
            ]);
            return $result['MessageId'];
        } catch (AwsException $e) {
            return new \WP_Error('ses_error', $e->getAwsErrorMessage());
        }
    }
}
