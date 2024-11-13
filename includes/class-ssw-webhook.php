<?php
class SSW_Webhook {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function handle_status_change($subscription_id, $old_status, $new_status) {
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) {
            SSW_Logger::log("Subscription not found", array('id' => $subscription_id));
            return;
        }

        $webhooks = SSW_Settings::get_instance()->get_webhooks_for_plan_status(
            $subscription->subscription_plan_id, 
            $new_status
        );

        if (empty($webhooks)) {
            SSW_Logger::log("No webhooks configured for this plan and status", array(
                'plan_id' => $subscription->subscription_plan_id,
                'status' => $new_status
            ));
            return;
        }

        $payload = $this->prepare_payload($subscription, $old_status, $new_status);
        
        foreach ($webhooks as $webhook_url) {
            $this->send_webhook($webhook_url, $payload);
        }
    }

    private function prepare_payload($subscription, $old_status, $new_status) {
        $user = get_userdata($subscription->user_id);
        $plan_details = $this->get_plan_details($subscription->subscription_plan_id);

        return array(
            'subscription_plan' => $plan_details,
            'subscription_details' => array(
                'user_id' => $subscription->user_id,
                'billing_amount' => $subscription->billing_amount,
                'billing_duration' => $subscription->billing_duration,
                'billing_duration_unit' => $subscription->billing_duration_unit,
                'billing_next_payment' => $subscription->billing_next_payment,
                'payment_gateway' => $subscription->payment_gateway,
                'status_change' => array(
                    'old_status' => $old_status,
                    'new_status' => $new_status,
                    'change_time' => current_time('mysql')
                )
            ),
            'user_details' => array(
                'id' => $user->ID,
                'email' => $user->user_email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => trim($user->first_name . ' ' . $user->last_name)
            )
        );
    }

    private function send_webhook($webhook_url, $payload) {
        $response = wp_remote_post($webhook_url, array(
            'body' => json_encode($payload),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Webhook-Source' => 'pms-realtime-webhook'
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            SSW_Logger::log("Webhook error", array(
                'url' => $webhook_url,
                'error' => $response->get_error_message()
            ));
            return false;
        }

        SSW_Logger::log("Webhook sent successfully", array(
            'url' => $webhook_url,
            'status' => wp_remote_retrieve_response_code($response)
        ));

        return true;
    }
}
