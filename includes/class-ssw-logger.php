<?php
class SSW_Logger {
    private static $instance = null;
    private $wpdb;
    private $log_table;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->log_table = $wpdb->prefix . 'ssw_execution_log';

        add_action('wp_scheduled_delete', array($this, 'cleanup_old_logs'));
    }

    public static function log($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            $output = '[' . current_time('Y-m-d H:i:s') . '] ' . $message;
            if ($data !== null) {
                $output .= "\nData: " . print_r($data, true);
            }
            error_log($output);
        }
    }

    public function log_execution($trigger_type, $webhooks_sent, $details = '') {
        return $this->wpdb->insert(
            $this->log_table,
            array(
                'execution_time' => current_time('mysql'),
                'trigger_type' => $trigger_type,
                'webhooks_sent' => $webhooks_sent,
                'details' => $details,
                'status' => strpos(strtolower($details), 'error') !== false ? 'error' : 'success'
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );
    }

    public function get_recent_logs($limit = 10) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->log_table} ORDER BY execution_time DESC LIMIT %d",
                $limit
            )
        );
    }

    public function get_recent_errors($limit = 5) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->log_table} 
                 WHERE status = 'error' OR details LIKE '%error%'
                 ORDER BY execution_time DESC 
                 LIMIT %d",
                $limit
            )
        );
    }

    public function cleanup_old_logs($days = 30) {
        return $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->log_table} WHERE execution_time < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }

    public function clear_logs() {
        return $this->wpdb->query("TRUNCATE TABLE {$this->log_table}");
    }

    public function get_execution_stats() {
        return $this->wpdb->get_row(
            "SELECT 
                COUNT(*) as total_executions,
                SUM(webhooks_sent) as total_webhooks_sent,
                MAX(execution_time) as last_execution,
                AVG(webhooks_sent) as avg_webhooks_per_execution,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as total_errors
            FROM {$this->log_table}"
        );
    }

    // Stelle sicher, dass die Tabelle die status-Spalte hat
    public function ensure_table_structure() {
        $table_exists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $this->log_table
            )
        );

        if (!$table_exists) {
            $this->create_log_table();
        } else {
            // Prüfe ob die status-Spalte existiert
            $status_column_exists = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(1) FROM information_schema.columns 
                     WHERE table_schema = %s 
                     AND table_name = %s 
                     AND column_name = 'status'",
                    DB_NAME,
                    $this->log_table
                )
            );

            if (!$status_column_exists) {
                $this->wpdb->query(
                    "ALTER TABLE {$this->log_table} 
                     ADD COLUMN status VARCHAR(20) DEFAULT 'success' 
                     AFTER details"
                );
            }
        }
    }

    private function create_log_table() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->log_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            execution_time datetime DEFAULT CURRENT_TIMESTAMP,
            trigger_type varchar(50),
            webhooks_sent int,
            details text,
            status varchar(20) DEFAULT 'success',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
