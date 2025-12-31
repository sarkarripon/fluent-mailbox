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

        register_rest_route($namespace, '/users', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new MailController(), 'getUsers'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

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

        register_rest_route($namespace, '/emails/fetch', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new MailController(), 'fetchEmails'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/emails/trash', [
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [new MailController(), 'emptyTrash'],
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
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [new MailController(), 'update'],
                'permission_callback' => [$this, 'checkPermission']
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [new MailController(), 'delete'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/emails/(?P<id>\d+)/workflow', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new MailController(), 'getWorkflow'],
                'permission_callback' => [$this, 'checkPermission']
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [new MailController(), 'updateWorkflow'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/emails/(?P<id>\d+)/notes', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new MailController(), 'getNotes'],
                'permission_callback' => [$this, 'checkPermission']
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new MailController(), 'addNote'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/notes/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [new MailController(), 'deleteNote'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        // Tag routes
        register_rest_route($namespace, '/tags', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new MailController(), 'getTags'],
                'permission_callback' => [$this, 'checkPermission']
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new MailController(), 'createTag'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/tags/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [new MailController(), 'updateTag'],
                'permission_callback' => [$this, 'checkPermission']
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [new MailController(), 'deleteTag'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/emails/(?P<id>\d+)/tags', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new MailController(), 'getEmailTags'],
                'permission_callback' => [$this, 'checkPermission']
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new MailController(), 'addEmailTag'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/emails/(?P<id>\d+)/tags/(?P<tag_id>\d+)', [
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [new MailController(), 'removeEmailTag'],
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

        register_rest_route($namespace, '/settings/verify', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'verifyCredentials'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/settings/save-connection', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'saveConnection'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/settings/setup-inbound', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'setupInbound'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/settings', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'getSettings'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/settings/disconnect', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'disconnect'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/settings/simulate-webhook', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'simulateWebhook'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/settings/debug-log', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'getDebugLog'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/settings/debug-log/clean', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'cleanDebugLog'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/attachments/upload', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\AttachmentController(), 'upload'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/drafts', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new MailController(), 'getDrafts'],
                'permission_callback' => [$this, 'checkPermission']
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new MailController(), 'saveDraft'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/drafts/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [new MailController(), 'deleteDraft'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/signatures', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'getSignatures'],
                'permission_callback' => [$this, 'checkPermission']
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'saveSignature'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/signatures/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'deleteSignature'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/templates', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'getTemplates'],
                'permission_callback' => [$this, 'checkPermission']
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'saveTemplate'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/templates/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\SettingsController(), 'deleteTemplate'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/attachments/(?P<id>\d+)/download', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\AttachmentController(), 'download'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);

        register_rest_route($namespace, '/attachments/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [new \FluentMailbox\Http\Controllers\AttachmentController(), 'getAttachmentInfo'],
                'permission_callback' => [$this, 'checkPermission']
            ]
        ]);
    }

    public function checkPermission()
    {
        return current_user_can('manage_options');
    }
}
