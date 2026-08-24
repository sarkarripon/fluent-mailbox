<?php

namespace FluentMailbox\Services;

use FluentMailbox\Services\Contracts\MailDriverInterface;
use FluentMailbox\Services\Drivers\ImapSmtpDriver;
use FluentMailbox\Services\Drivers\SesDriver;
use FluentMailbox\Services\Drivers\MailgunDriver;
use FluentMailbox\Services\Drivers\PostmarkDriver;

/**
 * Registry + factory for mail drivers. Third parties can register
 * additional drivers via the `fluent_mailbox_drivers` filter.
 */
class DriverManager
{
    private static $drivers = null;

    /**
     * All registered drivers keyed by slug:
     * [slug => ['label', 'description', 'class', 'capabilities' => [polling, push], 'fields' => [...]]]
     */
    public static function registeredDrivers()
    {
        if (self::$drivers !== null) {
            return self::$drivers;
        }

        $drivers = [
            'imap' => [
                'label' => __('IMAP / SMTP (any provider)', 'fluent-mailbox'),
                'description' => __('Connect any mailbox you already own — cPanel/hosting email, Zoho Mail, Gmail (app password) or a custom mail server. Receives via IMAP, sends via SMTP.', 'fluent-mailbox'),
                'class' => ImapSmtpDriver::class,
                'capabilities' => ['polling' => true, 'push' => false],
                'fields' => ImapSmtpDriver::fields(),
            ],
            'ses' => [
                'label' => __('Amazon SES', 'fluent-mailbox'),
                'description' => __('For power users already on AWS. Sends via SES and receives via SES receipt rules into S3 with SNS push.', 'fluent-mailbox'),
                'class' => SesDriver::class,
                'capabilities' => ['polling' => true, 'push' => true],
                'fields' => SesDriver::fields(),
            ],
            'mailgun' => [
                'label' => __('Mailgun (API)', 'fluent-mailbox'),
                'description' => __('Send via the Mailgun API and receive via Mailgun Routes forwarding to your webhook.', 'fluent-mailbox'),
                'class' => MailgunDriver::class,
                'capabilities' => ['polling' => false, 'push' => true],
                'fields' => MailgunDriver::fields(),
            ],
            'postmark' => [
                'label' => __('Postmark (API)', 'fluent-mailbox'),
                'description' => __('Send via the Postmark API and receive via an inbound stream webhook.', 'fluent-mailbox'),
                'class' => PostmarkDriver::class,
                'capabilities' => ['polling' => false, 'push' => true],
                'fields' => PostmarkDriver::fields(),
            ],
        ];

        self::$drivers = apply_filters('fluent_mailbox_drivers', $drivers);
        return self::$drivers;
    }

    /**
     * Driver metadata for one slug (or null).
     */
    public static function driverMeta($slug)
    {
        $drivers = self::registeredDrivers();
        return isset($drivers[$slug]) ? $drivers[$slug] : null;
    }

    /**
     * Public driver list for the frontend (labels, schemas, capabilities — no classes).
     */
    public static function driverList()
    {
        $list = [];
        foreach (self::registeredDrivers() as $slug => $meta) {
            $list[$slug] = [
                'slug' => $slug,
                'label' => $meta['label'],
                'description' => $meta['description'],
                'capabilities' => isset($meta['capabilities']) ? $meta['capabilities'] : [],
                'fields' => isset($meta['fields']) ? $meta['fields'] : [],
            ];
        }
        return array_values($list);
    }

    /**
     * Instantiate the driver for a mailbox row.
     *
     * @param object $mailbox Mailbox row with ->driver property.
     * @return MailDriverInterface|\WP_Error
     */
    public static function make($mailbox)
    {
        $slug = is_object($mailbox) && isset($mailbox->driver) ? $mailbox->driver : null;
        if (!$slug) {
            return new \WP_Error('invalid_mailbox', 'Mailbox has no driver');
        }

        $meta = self::driverMeta($slug);
        if (!$meta || empty($meta['class'])) {
            return new \WP_Error('unknown_driver', "Unknown mail driver: $slug");
        }

        $class = $meta['class'];
        if (!class_exists($class) || !in_array(MailDriverInterface::class, (array) class_implements($class), true)) {
            return new \WP_Error('invalid_driver', "Driver class does not implement MailDriverInterface: $class");
        }

        return new $class();
    }
}
