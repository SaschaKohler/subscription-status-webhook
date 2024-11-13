<?php
class SSW_Settings {
    private $option_name = 'ssw_webhook_configurations';
    private static $instance = null;
    private $subscription_plans = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function get_available_statuses() {
        return array(
            'active' => __('Active', 'subscription-status-webhook'),
            'pending' => __('Pending', 'subscription-status-webhook'),
            'canceled' => __('Canceled', 'subscription-status-webhook'),
            'abandoned' => __('Abandoned', 'subscription-status-webhook'),
            'expired' => __('Expired', 'subscription-status-webhook')
        );
    }

    public function get_webhook_configurations() {
        $configs = get_option($this->option_name, array());
        return is_array($configs) ? $configs : array();
    }

   public function get_subscription_plans() {
        // Cache für die aktuelle Request
        if ($this->subscription_plans !== null) {
            return $this->subscription_plans;
        }

        // Versuche zuerst get_posts, wenn init schon gelaufen ist
        if (did_action('init')) {
            $plans = get_posts(array(
                'post_type' => 'pms-subscription',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'active'
            ));

            if (!empty($plans)) {
                $this->subscription_plans = array_map(function($plan) {
                    return array(
                        'id' => $plan->ID,
                        'title' => $plan->post_title,
                        'content' => wp_strip_all_tags($plan->post_content)
                    );
                }, $plans);
                return $this->subscription_plans;
            }
        }

        // Fallback: Direkte DB-Abfrage
        global $wpdb;
        $plans = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title as title, p.post_content as content
             FROM {$wpdb->posts} p 
             WHERE p.post_type = %s 
             AND p.post_status = 'active' 
             ORDER BY p.post_title ASC",
            'pms-subscription'
        ));

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Fetching subscription plans via DB query: ' . $wpdb->last_query);
            error_log('Found plans: ' . print_r($plans, true));
        }

        $this->subscription_plans = $plans;
        return $plans;
    }

    public function render_settings_page() {
        $configurations = $this->get_webhook_configurations();
        $plans = $this->get_subscription_plans();
        $statuses = $this->get_available_statuses();
        ?>
        <div class="wrap">
            <h1><?php _e('Subscription Webhook Settings', 'subscription-status-webhook'); ?></h1>

            <form method="post" action="options.php" id="ssw-webhook-config-form">
                <?php settings_fields('ssw_webhook_settings'); ?>

                <div class="ssw-webhook-configurations">
                    <?php 
                    if (!empty($configurations)) {
                        foreach ($configurations as $index => $config) {
                            $this->render_webhook_configuration($index, $config, $plans, $statuses);
                        }
                    }
                    ?>
                    <div id="ssw-webhook-template" style="display:none;">
                        <?php $this->render_webhook_configuration('TEMPLATE', array(), $plans, $statuses); ?>
                    </div>
                </div>

                <button type="button" class="button-secondary" id="add-webhook-config">
                    <?php _e('Add New Configuration', 'subscription-status-webhook'); ?>
                </button>

                <?php submit_button(); ?>
            </form>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#add-webhook-config').on('click', function() {
                    var template = $('#ssw-webhook-template').html();
                    var newIndex = $('.ssw-webhook-config').length;
                    template = template.replace(/TEMPLATE/g, newIndex);
                    $('.ssw-webhook-configurations').append(template);
                });

                $(document).on('click', '.ssw-remove-config', function() {
                    $(this).closest('.ssw-webhook-config').remove();
                });
            });
        </script>
        <?php
    }

    private function render_webhook_configuration($index, $config, $plans, $statuses) {
        ?>
        <div class="ssw-webhook-config card">
            <h3>
                <?php _e('Webhook Configuration', 'subscription-status-webhook'); ?>
                <?php if ($index !== 'TEMPLATE'): ?>
                <button type="button" class="button-link ssw-remove-config" style="float:right;color:red;">
                    <?php _e('Remove', 'subscription-status-webhook'); ?>
                </button>
                <?php endif; ?>
            </h3>

            <table class="form-table">
                <tr>
                    <th><?php _e('Subscription Plans', 'subscription-status-webhook'); ?></th>
                    <td>
                        <select name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][plans][]" 
                                multiple="multiple" 
                                class="ssw-plan-select" 
                                style="width: 100%; max-width: 400px;">
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?php echo $plan['id']; ?>"
                                    <?php echo in_array($plan['id'], isset($config['plans']) ? $config['plans'] : array()) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($plan['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Trigger Statuses', 'subscription-status-webhook'); ?></th>
                    <td>
                        <?php foreach ($statuses as $status_key => $status_label): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" 
                                       name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][statuses][]" 
                                       value="<?php echo $status_key; ?>"
                                       <?php echo in_array($status_key, isset($config['statuses']) ? $config['statuses'] : array()) ? 'checked' : ''; ?>>
                                <?php echo $status_label; ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Webhook URL', 'subscription-status-webhook'); ?></th>
                    <td>
                        <input type="url" 
                               name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][webhook_url]" 
                               value="<?php echo isset($config['webhook_url']) ? esc_url($config['webhook_url']) : ''; ?>"
                               class="regular-text"
                               required>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('ssw_webhook_settings', $this->option_name, array($this, 'sanitize_settings'));
    }

    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            foreach ($input as $index => $config) {
                $sanitized[$index] = array(
                    'plans' => isset($config['plans']) ? array_map('intval', $config['plans']) : array(),
                    'statuses' => isset($config['statuses']) ? array_map('sanitize_text_field', $config['statuses']) : array(),
                    'webhook_url' => esc_url_raw($config['webhook_url'])
                );
            }
        }

        return $sanitized;
    }

    public function get_webhooks_for_plan_status($plan_id, $status) {
        $configurations = $this->get_webhook_configurations();
        $matching_webhooks = array();

        foreach ($configurations as $config) {
            if (in_array($plan_id, $config['plans']) && 
                in_array($status, $config['statuses']) && 
                !empty($config['webhook_url'])) {
                $matching_webhooks[] = $config['webhook_url'];
            }
        }

        return $matching_webhooks;
    }
}
