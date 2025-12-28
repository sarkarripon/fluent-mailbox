<?php
/**
 * Plugin Name: Fluent Mailbox
 * Description: A Gmail-like mailbox plugin for WordPress using AWS SES.
 * Version: 1.0.0
 * Author: Fluent Mailbox Team
 * Text Domain: fluent-mailbox
 */

defined('ABSPATH') || exit;

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
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public static function activate()
    {
        if (file_exists(FLUENT_MAILBOX_PATH . 'vendor/autoload.php')) {
            require_once FLUENT_MAILBOX_PATH . 'vendor/autoload.php';
        }
        \FluentMailbox\Common\DatabaseMigration::migrate();
    }

    public function registerMenu()
    {
        add_menu_page(
            __('Fluent Mailbox', 'fluent-mailbox'),
            __('Fluent Mailbox', 'fluent-mailbox'),
            'manage_options',
            'fluent-mailbox',
            [$this, 'renderApp'],
            'dashicons-email',
            25
        );
    }

    public function renderApp()
    {
        echo '<div id="fluent-mailbox-app"></div>';
    }

    public function enqueueAssets($hook)
    {
        if ($hook !== 'toplevel_page_fluent-mailbox') {
            return;
        }

        $scriptLocation = FLUENT_MAILBOX_URL . 'assets/js/main.js';
        $styleLocation = FLUENT_MAILBOX_URL . 'assets/css/style.css';
        
        // Development mode check
        if (defined('WP_ENV') && WP_ENV === 'development' && file_exists(FLUENT_MAILBOX_PATH . 'hot')) {
             $scriptLocation = 'http://localhost:5173/resources/js/main.js';
             // Vite handles CSS injection in dev
             wp_enqueue_script('fluent-mailbox-vite-client', 'http://localhost:5173/@vite/client', [], null, false);
        }

        wp_enqueue_script('fluent-mailbox-app', $scriptLocation, [], FLUENT_MAILBOX_VERSION, true);
        
        if (!defined('WP_ENV') || WP_ENV !== 'development' || !file_exists(FLUENT_MAILBOX_PATH . 'hot')) {
             wp_enqueue_style('fluent-mailbox-style', $styleLocation, [], FLUENT_MAILBOX_VERSION);
        }

        wp_localize_script('fluent-mailbox-app', 'FluentMailbox', [
            'root' => esc_url_raw(rest_url('fluent-mailbox/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'assets' => FLUENT_MAILBOX_URL . 'assets/'
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
