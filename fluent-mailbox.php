<?php
/**
 * Plugin Name: Fluent Mailbox
 * Description: A Gmail-like mailbox plugin for WordPress using AWS SES.
 * Version: 1.0.0
 * Author: Fluent Mailbox Team
 * Text Domain: fluent-mailbox
 */

defined('ABSPATH') || exit;
defined('WP_ENV') || define('WP_ENV', 'production');

define('FLUENT_MAILBOX_VERSION', '1.0.0');
define('FLUENT_MAILBOX_PATH', plugin_dir_path(__FILE__));
define('FLUENT_MAILBOX_URL', plugin_dir_url(__FILE__));

final class FluentMailbox
{
    public function __construct()
    {
        $this->loadDependencies();
        $this->initHooks();
        (new \FluentMailbox\Http\Router())->init();
    }

    private function loadDependencies()
    {
        if (file_exists(FLUENT_MAILBOX_PATH . 'vendor/autoload.php')) {
            require_once FLUENT_MAILBOX_PATH . 'vendor/autoload.php';
        }
    }

    private function initHooks()
    {
        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        // Run migration check on init so REST requests are covered too
        add_action('init', [__CLASS__, 'checkMigration']);
    }

    public static function checkMigration()
    {
        global $wpdb;

        // Check if we need to run migration (for existing installations)
        $version = get_option('fluent_mailbox_db_version', '0');

        $emailsTable = $wpdb->prefix . 'fluent_mailbox_emails';
        $notesTable = $wpdb->prefix . 'fluent_mailbox_email_notes';
        $tagsTable = $wpdb->prefix . 'fluent_mailbox_tags';
        $emailTagsTable = $wpdb->prefix . 'fluent_mailbox_email_tags';

        $emailsTableExists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $emailsTable)) === $emailsTable);
        $notesTableExists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $notesTable)) === $notesTable);
        $tagsTableExists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tagsTable)) === $tagsTable);
        $emailTagsTableExists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $emailTagsTable)) === $emailTagsTable);

        $needsMigration = version_compare($version, FLUENT_MAILBOX_VERSION, '<') || !$emailsTableExists || !$notesTableExists || !$tagsTableExists || !$emailTagsTableExists;
        if ($needsMigration) {
            \FluentMailbox\Common\DatabaseMigration::migrate();
            update_option('fluent_mailbox_db_version', FLUENT_MAILBOX_VERSION);
        }
    }

    public static function activate()
    {
        if (file_exists(FLUENT_MAILBOX_PATH . 'vendor/autoload.php')) {
            require_once FLUENT_MAILBOX_PATH . 'vendor/autoload.php';
        }
        \FluentMailbox\Common\DatabaseMigration::migrate();
        update_option('fluent_mailbox_db_version', FLUENT_MAILBOX_VERSION);
    }

    public static function deactivate()
    {
        // Cleanup if needed
    }

    public function registerMenu()
    {
        $menu_slug = 'fluent-mailbox';

        add_menu_page(
            __('Fluent Mailbox', 'fluent-mailbox'),
            __('Fluent Mailbox', 'fluent-mailbox'),
            'manage_options',
            $menu_slug,
            [$this, 'renderApp'],
            'dashicons-email',
            25
        );

        // Submenu items - all use same slug since Vue router handles navigation
        add_submenu_page(
            $menu_slug,
            __('Inbox', 'fluent-mailbox'),
            __('Inbox', 'fluent-mailbox'),
            'manage_options',
            $menu_slug . '-inbox',
            [$this, 'renderApp']
        );

        add_submenu_page(
            $menu_slug,
            __('Sent', 'fluent-mailbox'),
            __('Sent', 'fluent-mailbox'),
            'manage_options',
            $menu_slug . '-sent',
            [$this, 'renderApp']
        );

        add_submenu_page(
            $menu_slug,
            __('Drafts', 'fluent-mailbox'),
            __('Drafts', 'fluent-mailbox'),
            'manage_options',
            $menu_slug . '-drafts',
            [$this, 'renderApp']
        );

        add_submenu_page(
            $menu_slug,
            __('Trash', 'fluent-mailbox'),
            __('Trash', 'fluent-mailbox'),
            'manage_options',
            $menu_slug . '-trash',
            [$this, 'renderApp']
        );

        add_submenu_page(
            $menu_slug,
            __('Settings', 'fluent-mailbox'),
            __('Settings', 'fluent-mailbox'),
            'manage_options',
            $menu_slug . '-settings',
            [$this, 'renderApp']
        );
    }

    public function renderApp()
    {
        // Detect which route to navigate to based on current page
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'fluent-mailbox';
        $route = '/inbox'; // Default route

        if (strpos($current_page, '-inbox') !== false) {
            $route = '/inbox';
        } elseif (strpos($current_page, '-sent') !== false) {
            $route = '/sent';
        } elseif (strpos($current_page, '-drafts') !== false) {
            $route = '/drafts';
        } elseif (strpos($current_page, '-trash') !== false) {
            $route = '/trash';
        } elseif (strpos($current_page, '-settings') !== false) {
            $route = '/settings';
        }

        echo '<div id="fluent-mailbox-app" data-initial-route="' . esc_attr($route) . '"></div>';
    }

    public function enqueueAssets($hook)
    {
        // Check if this is any of the Fluent Mailbox pages (main menu or submenu)
        $mailbox_pages = [
            'toplevel_page_fluent-mailbox',
            'fluent-mailbox_page_fluent-mailbox-inbox',
            'fluent-mailbox_page_fluent-mailbox-sent',
            'fluent-mailbox_page_fluent-mailbox-trash',
            'fluent-mailbox_page_fluent-mailbox-settings'
        ];

        if (!in_array($hook, $mailbox_pages, true)) {
            return;
        }

        // Enqueue WordPress editor scripts and styles
        wp_enqueue_editor();
        wp_enqueue_media();

        $scriptLocation = FLUENT_MAILBOX_URL . 'assets/js/main.js';
        $styleLocation = FLUENT_MAILBOX_URL . 'assets/css/style.css';

        // Development mode check
        if (defined('WP_ENV') && WP_ENV === 'development') {
             $scriptLocation = 'http://localhost:4005/resources/js/main.js';
             // Vite handles CSS injection in dev
             wp_enqueue_script('fluent-mailbox-vite-client', 'http://localhost:4005/@vite/client', [], null, false);
        }

        wp_enqueue_script('fluent-mailbox-app', $scriptLocation, ['jquery'], FLUENT_MAILBOX_VERSION, true);

        if (!defined('WP_ENV') || WP_ENV !== 'development') {
             wp_enqueue_style('fluent-mailbox-style', $styleLocation, [], FLUENT_MAILBOX_VERSION);
        }

        wp_localize_script('fluent-mailbox-app', 'FluentMailbox', [
            'root' => esc_url_raw(rest_url('fluent-mailbox/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'assets' => FLUENT_MAILBOX_URL . 'assets/',
            'is_configured' => (get_option('fluent_mailbox_aws_key') && get_option('fluent_mailbox_from_email'))
        ]);

        add_filter('script_loader_tag', [$this, 'addModuleType'], 10, 3);
    }

    public function addModuleType($tag, $handle, $src)
    {
        if ($handle === 'fluent-mailbox-app' || $handle === 'fluent-mailbox-vite-client') {
            return '<script type="module" src="' . esc_url($src) . '" id="' . esc_attr($handle . '-js') . '"></script>';
        }
        return $tag;
    }
}

new FluentMailbox();
