<?php
class SSW_Activator {
    public static function activate() {
        global $wpdb;
        
        // Erstelle die notwendigen Datenbanktabellen
        $charset_collate = $wpdb->get_charset_collate();

        // Log Tabelle
        $log_table = $wpdb->prefix . 'ssw_execution_log';
        $sql = "CREATE TABLE IF NOT EXISTS $log_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            execution_time datetime DEFAULT CURRENT_TIMESTAMP,
            trigger_type varchar(50),
            webhooks_sent int,
            details text,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Setze initiale Plugin-Optionen
        add_option('ssw_webhook_configurations', array());
        add_option('ssw_remove_data_on_uninstall', false);

        // Setze Version für spätere Updates
        add_option('ssw_version', SSW_VERSION);

        // Erstelle Verzeichnisse falls nötig
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/ssw-logs';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
    }
}
