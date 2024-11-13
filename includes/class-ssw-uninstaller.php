<?php
class SSW_Uninstaller {
    public static function uninstall() {
        global $wpdb;

        // Nur ausführen wenn die Option gesetzt ist
        if (!get_option('ssw_remove_data_on_uninstall', false)) {
            return;
        }

        // Lösche Datenbanktabellen
        $tables = array(
            $wpdb->prefix . 'ssw_execution_log',
            $wpdb->prefix . 'ssw_status_tracking'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        // Lösche Plugin-Optionen
        $options = array(
            'ssw_webhook_configurations',
            'ssw_remove_data_on_uninstall',
            'ssw_version'
        );

        foreach ($options as $option) {
            delete_option($option);
        }

        // Lösche Log-Dateien
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/ssw-logs';
        if (is_dir($log_dir)) {
            self::delete_directory($log_dir);
        }
    }

    private static function delete_directory($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!self::delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
