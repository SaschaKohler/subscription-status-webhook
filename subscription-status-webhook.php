<?php
/*
Plugin Name: Subscription Status Webhook Notifier
Description: Sends webhook notifications for multiple subscription plans and status changes
Version: 2.0.0
Author: Sascha Kohler
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SSW_VERSION', '2.0.0');
define('SSW_PLUGIN_FILE', __FILE__);
define('SSW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SSW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SSW_ADMIN_DIR', SSW_PLUGIN_DIR . 'admin/');

// Autoloader für Klassen
spl_autoload_register(function ($class) {
    // Nur Klassen mit SSW_ Prefix laden
    if (strpos($class, 'SSW_') !== 0) {
        return;
    }

    $class_name = strtolower(str_replace('SSW_', '', $class));
    $file_path = SSW_PLUGIN_DIR . 'includes/class-ssw-' . str_replace('_', '-', $class_name) . '.php';

    if (file_exists($file_path)) {
        require_once $file_path;
    }
});

class Subscription_Status_Webhook {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function init() {
 // Initialisiere Klassen in der richtigen Reihenfolge
        SSW_Settings::get_instance();
        SSW_Logger::get_instance();
        SSW_Admin::get_instance();   // Admin nach Settings initialisieren
        SSW_Webhook::get_instance();

        // Hook für Statusänderungen
        add_action('pms_subscription_status_changed', array($this, 'handle_status_change'), 10, 3);
        
        // Aktiviere Übersetzungen
        load_plugin_textdomain('subscription-status-webhook', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_admin_assets($hook) {
        // Nur auf Plugin-Seiten laden
        if (strpos($hook, 'subscription-webhook') === false) {
            return;
        }

        // Admin CSS
        wp_enqueue_style(
            'ssw-admin-style',
            SSW_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            SSW_VERSION
        );

        // Select2 für bessere Dropdown-Auswahl
        wp_enqueue_style(
            'select2',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css',
            array(),
            '4.0.13'
        );

        // Admin JavaScript
        wp_enqueue_script(
            'select2',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js',
            array('jquery'),
            '4.0.13',
            true
        );

        wp_enqueue_script(
            'ssw-admin-script',
            SSW_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery', 'select2'),
            SSW_VERSION,
            true
        );

        // Lokalisierung für JavaScript
        wp_localize_script('ssw-admin-script', 'sswAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ssw-admin-nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to remove this webhook configuration?', 'subscription-status-webhook'),
                'savingChanges' => __('Saving changes...', 'subscription-status-webhook'),
                'changesSaved' => __('Changes saved successfully.', 'subscription-status-webhook'),
                'error' => __('An error occurred.', 'subscription-status-webhook')
            )
        ));
    }

    public function handle_status_change($subscription_id, $old_status, $new_status) {
        try {
            SSW_Logger::log("Status change detected", array(
                'subscription_id' => $subscription_id,
                'old_status' => $old_status,
                'new_status' => $new_status
            ));

            SSW_Webhook::get_instance()->handle_status_change($subscription_id, $old_status, $new_status);
        } catch (Exception $e) {
            SSW_Logger::log("Error handling status change: " . $e->getMessage(), array(
                'subscription_id' => $subscription_id,
                'exception' => $e
            ));
        }
    }

    public static function activate() {
      // Erstelle oder aktualisiere Datenbanktabellen
    SSW_Logger::get_instance()->ensure_table_structure();

    // Setze Standard-Konfiguration wenn keine existiert
    if (!get_option('ssw_webhook_configurations')) {
        update_option('ssw_webhook_configurations', array());
    }
        // Erstelle oder aktualisiere Datenbanktabellen
        require_once SSW_PLUGIN_DIR . 'includes/class-ssw-activator.php';
        SSW_Activator::activate();

        // Setze Standard-Konfiguration wenn keine existiert
        if (!get_option('ssw_webhook_configurations')) {
            update_option('ssw_webhook_configurations', array());
        }
    }

    public static function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('ssw_cleanup_logs');
    }

    public static function uninstall() {
        // Optional: Lösche Plugin-Daten
        if (get_option('ssw_remove_data_on_uninstall', false)) {
            require_once SSW_PLUGIN_DIR . 'includes/class-ssw-uninstaller.php';
            SSW_Uninstaller::uninstall();
        }
    }
}

// Initialisierung
function ssw() {
    return Subscription_Status_Webhook::get_instance();
}

// Activation/Deactivation Hooks
register_activation_hook(__FILE__, array('Subscription_Status_Webhook', 'activate'));
register_deactivation_hook(__FILE__, array('Subscription_Status_Webhook', 'deactivate'));
register_uninstall_hook(__FILE__, array('Subscription_Status_Webhook', 'uninstall'));

// Start the plugin
ssw();
