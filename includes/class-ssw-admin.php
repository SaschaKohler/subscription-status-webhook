<?php
class SSW_Admin {
    private $settings;
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings = SSW_Settings::get_instance();
        
        // Admin Hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // AJAX Handlers
        add_action('wp_ajax_ssw_test_webhook', array($this, 'ajax_test_webhook'));
        add_action('wp_ajax_ssw_get_subscription_plans', array($this, 'ajax_get_subscription_plans'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Subscription Webhooks', 'subscription-status-webhook'),
            __('Subscription Webhooks', 'subscription-status-webhook'),
            'manage_options',
            'subscription-webhooks',
            array($this, 'render_admin_page'),
            'dashicons-rss',
            30
        );

        add_submenu_page(
            'subscription-webhooks',
            __('Settings', 'subscription-status-webhook'),
            __('Settings', 'subscription-status-webhook'),
            'manage_options',
            'subscription-webhooks-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'subscription-webhooks',
            __('Logs', 'subscription-status-webhook'),
            __('Logs', 'subscription-status-webhook'),
            'manage_options',
            'subscription-webhooks-logs',
            array($this, 'render_logs_page')
        );
    }

    public function render_admin_page() {
        include SSW_PLUGIN_DIR . 'admin/partials/admin-display.php';
    }

    public function render_settings_page() {
        $webhook_configs = $this->settings->get_webhook_configurations();
        $subscription_plans = $this->settings->get_subscription_plans();
        $available_statuses = $this->settings->get_available_statuses();
        
        include SSW_PLUGIN_DIR . 'admin/partials/settings-display.php';
    }

    public function render_logs_page() {
        $logs = SSW_Logger::get_instance()->get_recent_logs(50);
        include SSW_PLUGIN_DIR . 'admin/partials/logs-display.php';
    }
    public function register_settings() {
        // Registriere die Einstellungen
        register_setting(
            'ssw_webhook_settings', // Option group
            'ssw_webhook_configurations', // Option name
            array($this, 'sanitize_webhook_configurations') // Sanitize callback
        );

        // Registriere zusätzliche Einstellungen
        register_setting(
            'ssw_webhook_settings',
            'ssw_remove_data_on_uninstall',
            array(
                'type' => 'boolean',
                'default' => false
            )
        );
    }

    public function sanitize_webhook_configurations($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            foreach ($input as $index => $config) {
                if (!is_array($config)) continue;
                
                $sanitized[$index] = array(
                    'plans' => isset($config['plans']) ? array_map('intval', (array)$config['plans']) : array(),
                    'statuses' => isset($config['statuses']) ? array_map('sanitize_text_field', (array)$config['statuses']) : array(),
                    'webhook_url' => esc_url_raw($config['webhook_url'] ?? ''),
                    'active' => isset($config['active']) ? (bool)$config['active'] : true
                );
            }
        }

        return $sanitized;
    }


    public function enqueue_admin_assets($hook) {
        // Nur auf Plugin-Seiten laden
        if (strpos($hook, 'subscription-webhooks') === false) {
            return;
        }

        wp_enqueue_style(
            'ssw-admin',
            SSW_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            SSW_VERSION
        );

        wp_enqueue_script(
            'ssw-admin',
            SSW_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            SSW_VERSION,
            true
        );

        // Select2 für verbesserte Dropdowns
        wp_enqueue_style('select2');
        wp_enqueue_script('select2');

        wp_localize_script('ssw-admin', 'sswAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ssw-admin-nonce'),
            'strings' => array(
                'testSuccess' => __('Webhook test successful!', 'subscription-status-webhook'),
                'testError' => __('Webhook test failed: ', 'subscription-status-webhook'),
                'confirmDelete' => __('Are you sure you want to delete this configuration?', 'subscription-status-webhook')
            )
        ));
    }

    public function ajax_test_webhook() {
        check_ajax_referer('ssw-admin-nonce', 'nonce');

        $webhook_url = sanitize_url($_POST['webhook_url']);
        $test_payload = array(
            'test' => true,
            'timestamp' => current_time('mysql'),
            'plugin_version' => SSW_VERSION
        );

        $response = wp_remote_post($webhook_url, array(
            'body' => json_encode($test_payload),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Webhook-Source' => 'ssw-test'
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        } else {
            wp_send_json_success(array(
                'status' => wp_remote_retrieve_response_code($response),
                'body' => wp_remote_retrieve_body($response)
            ));
        }
    }

    public function admin_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $webhook_configs = $this->settings->get_webhook_configurations();
        if (empty($webhook_configs)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . __('No webhook configurations found. Please add at least one configuration to start receiving notifications.', 'subscription-status-webhook') . '</p>';
            echo '</div>';
        }

        // Zeige Fehler aus dem Log
        $recent_errors = SSW_Logger::get_instance()->get_recent_errors();
        if (!empty($recent_errors)) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>' . __('Recent Webhook Errors:', 'subscription-status-webhook') . '</strong></p>';
            echo '<ul>';
            foreach ($recent_errors as $error) {
                echo '<li>' . esc_html($error->details) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }
}
