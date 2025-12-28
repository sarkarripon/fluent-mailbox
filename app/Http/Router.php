<?php

namespace FluentMailbox\Http;

use FluentMailbox\Http\Controllers\MailController;

class Router
{
    public function init()
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes()
    {
        $namespace = 'fluent-mailbox/v1';

        register_rest_route($namespace, '/emails', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new MailController(), 'index'],
                'permission_callback' => [$this, 'checkPermission']
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new MailController(), 'send'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/emails/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new MailController(), 'get'],
                'permission_callback' => [$this, 'checkPermission']
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [new MailController(), 'delete'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/webhook', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\WebhookController(), 'handle'],
                'permission_callback' => '__return_true' // Public webhook
            ]
        ]);

        register_rest_route($namespace, '/settings', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'getSettings'],
                'permission_callback' => [$this, 'checkPermission']
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'saveSettings'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);
    }

    public function checkPermission()
    {
        return current_user_can('manage_options');
    }
}
