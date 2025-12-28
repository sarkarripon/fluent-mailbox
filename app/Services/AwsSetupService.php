<?php

namespace FluentMailbox\Services;

use Aws\S3\S3Client;
use Aws\Sns\SnsClient;
use Aws\Ses\SesClient;
use Aws\Sts\StsClient;
use Aws\Exception\AwsException;

class AwsSetupService
{
    private $region;
    private $key;
    private $secret;

    public function __construct($region, $key, $secret)
    {
        $this->region = $region;
        $this->key = $key;
        $this->secret = $secret;
    }

    private function getCredentials()
    {
        return [
            'key'    => $this->key,
            'secret' => $this->secret,
        ];
    }

    public function setup($webhookUrl)
    {
        $log = [];
        $ruleSetName = 'FluentMailboxRules';
        $ruleName = 'StoreToS3AndNotify';

        try {
            // 1. S3 Setup
            $s3 = new S3Client([
                'version' => 'latest',
                'region'  => $this->region,
                'credentials' => $this->getCredentials()
            ]);
            
            $bucketName = 'fluent-mailbox-inbound-' . uniqid();
            $s3->createBucket(['Bucket' => $bucketName]);
            $log[] = "Created S3 Bucket: $bucketName";

            $accountId = $this->getAccountId();
            $sourceArn = "arn:aws:ses:{$this->region}:{$accountId}:receipt-rule-set/{$ruleSetName}:receipt-rule/{$ruleName}";
            
            // Add Policy for SES to write to bucket
            $policy = json_encode([
                'Version' => '2012-10-17',
                'Statement' => [
                    [
                        'Sid' => 'AllowSESPuts',
                        'Effect' => 'Allow',
                        'Principal' => ['Service' => 'ses.amazonaws.com'],
                        'Action' => 's3:PutObject',
                        'Resource' => "arn:aws:s3:::$bucketName/*",
                        'Condition' => [
                            'StringEquals' => [
                                'AWS:SourceAccount' => $accountId,
                                'AWS:SourceArn' => $sourceArn
                            ]
                        ]
                    ]
                ]
            ]);

            $s3->putBucketPolicy([
                'Bucket' => $bucketName,
                'Policy' => $policy
            ]);
            $log[] = "Applied Bucket Policy (Restricted to Account: $accountId)";
            
            update_option('fluent_mailbox_s3_bucket', $bucketName);


            // 2. SNS Setup
            $sns = new SnsClient([
                'version' => 'latest',
                'region'  => $this->region,
                'credentials' => $this->getCredentials()
            ]);

            $topic = $sns->createTopic(['Name' => 'FluentMailboxInbound']);
            $topicArn = $topic['TopicArn'];
            $log[] = "Created SNS Topic: $topicArn";

            try {
                $sns->subscribe([
                    'TopicArn' => $topicArn,
                    'Protocol' => 'https',
                    'Endpoint' => $webhookUrl
                ]);
                $log[] = "Subscribed Webhook to SNS";
            } catch (AwsException $e) {
                // Ignore unreachable endpoint error for localhost/dev environments
                if ($e->getAwsErrorCode() === 'InvalidParameter' && strpos($e->getMessage(), 'Unreachable Endpoint') !== false) {
                    $log[] = "WARNING: Could not subscribe webhook (Unreachable Endpoint). This is expected on localhost. Please subscribe manually if deploying live.";
                } else {
                    throw $e;
                }
            }
            
            update_option('fluent_mailbox_sns_topic_arn', $topicArn);


            // 3. SES Rule Setup
            $ses = new SesClient([
                'version' => 'latest',
                'region'  => $this->region,
                'credentials' => $this->getCredentials()
            ]);

            // $ruleSetName used from top
            
            try {
                $ses->createReceiptRuleSet(['RuleSetName' => $ruleSetName]);
                $log[] = "Created Receipt Rule Set: $ruleSetName";
            } catch (AwsException $e) {
                // Ignore if exists
                if ($e->getAwsErrorCode() !== 'AlreadyExists') {
                     throw $e;
                }
                 $log[] = "Receipt Rule Set already exists";
            }

            // Create Rule
            // $ruleName used from top
            $rule = [
                'Name' => $ruleName,
                'Enabled' => true,
                'Actions' => [
                    [
                        'S3Action' => [
                            'BucketName' => $bucketName,
                            'ObjectKeyPrefix' => 'emails/'
                        ]
                    ],
                    [
                        'SNSAction' => [
                            'TopicArn' => $topicArn,
                            'Encoding' => 'UTF-8'
                        ]
                    ]
                ],
            ];

            try {
                $ses->createReceiptRule([
                    'RuleSetName' => $ruleSetName,
                    'Rule' => $rule
                ]);
                $log[] = "Created Receipt Rule: $ruleName";
            } catch (AwsException $e) {
                 if ($e->getAwsErrorCode() === 'AlreadyExists') {
                     $ses->updateReceiptRule([
                        'RuleSetName' => $ruleSetName,
                        'Rule' => $rule
                     ]);
                     $log[] = "Updated Receipt Rule: $ruleName";
                 } else {
                     throw $e;
                 }
            }
            
            // Activate Rule Set
            $ses->setActiveReceiptRuleSet(['RuleSetName' => $ruleSetName]);
            $log[] = "Activated Receipt Rule Set: $ruleSetName";

            return ['success' => true, 'log' => $log, 'bucket' => $bucketName, 'topic' => $topicArn];

        } catch (\Exception $e) {
            return new \WP_Error('aws_setup_error', $e->getMessage(), ['log' => $log]);
        }
    }

    private function getAccountId() {
        try {
            $sts = new StsClient([
                'version' => 'latest',
                'region'  => $this->region,
                'credentials' => $this->getCredentials()
            ]);
            $result = $sts->getCallerIdentity();
            return $result['Account'];
        } catch (\Exception $e) {
            return '*';
        }
    }
}
