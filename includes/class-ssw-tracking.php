<?php
class SSW_Tracking {
    private static $instance = null;
    private $wpdb;
    private $tracking_table;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tracking_table = $wpdb->prefix . 'ssw_status_tracking';

        add_action('pms_member_subscription_inserted', array($this, 'track_initial_status'), 10, 2);
        add_action('pms_member_subscription_updated', array($this, 'track_status_change'), 10, 3);
    }

    public function track_initial_status($subscription_id, $data) {
        $subscription = $this->get_subscription($subscription_id);

        if ($subscription && $subscription->subscription_plan_id) {
            $tracking_id = $this->create_tracking_id($subscription);
            
            $this->update_tracking(
                $tracking_id,
                $subscription->status
            );

            SSW_Logger::log(sprintf(
                'Initial status tracked for plan_id %d, user_id %d: %s',
                $subscription->subscription_plan_id,
                $subscription->user_id,
                $subscription->status
            ));
        }
    }

    public function track_status_change($subscription_id, $data, $old_data) {
        $subscription = $this->get_subscription($subscription_id);

        if ($subscription && $subscription->subscription_plan_id) {
            $tracking_id = $this->create_tracking_id($subscription);
            
            $this->update_tracking(
                $tracking_id,
                $subscription->status
            );

            SSW_Logger::log(sprintf(
                'Status change tracked for plan_id %d, user_id %d: %s',
                $subscription->subscription_plan_id,
                $subscription->user_id,
                $subscription->status
            ));
        }
    }

    private function get_subscription($subscription_id) {
        $subscription_table = $this->wpdb->prefix . 'pms_member_subscriptions';
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT subscription_plan_id, user_id, status FROM $subscription_table WHERE id = %d",
            $subscription_id
        ));
    }

    private function create_tracking_id($subscription) {
        return $subscription->subscription_plan_id . '_' . $subscription->user_id;
    }

    private function update_tracking($tracking_id, $status) {
        $this->wpdb->replace(
            $this->tracking_table,
            array(
                'subscription_id' => $tracking_id,
                'previous_status' => $status,
                'last_updated' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
    }

    public function migrate_existing_subscriptions() {
        $subscription_table = $this->wpdb->prefix . 'pms_member_subscriptions';
        
        $existing_subscriptions = $this->wpdb->get_results(
            "SELECT subscription_plan_id, user_id, status FROM $subscription_table"
        );

        $migrated_count = 0;
        foreach ($existing_subscriptions as $subscription) {
            $tracking_id = $this->create_tracking_id($subscription);
            
            if (!$this->tracking_exists($tracking_id)) {
                $this->update_tracking($tracking_id, $subscription->status);
                $migrated_count++;
            }
        }

        SSW_Logger::log(sprintf(
            'Migration completed: %d subscription plan-user combinations migrated',
            $migrated_count
        ));

        return $migrated_count;
    }

    private function tracking_exists($tracking_id) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT subscription_id FROM {$this->tracking_table} WHERE subscription_id = %s",
            $tracking_id
        ));
    }

    public function get_tracking_status($tracking_id) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT previous_status FROM {$this->tracking_table} WHERE subscription_id = %s",
            $tracking_id
        ));
    }
}
