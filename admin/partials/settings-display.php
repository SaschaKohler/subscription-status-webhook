<?php
if (!defined('ABSPATH')) exit;

// Hole alle verfügbaren Subscription Plans
$subscription_plans = SSW_Settings::get_instance()->get_subscription_plans();

// Debug-Ausgabe
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Available subscription plans: ' . print_r($subscription_plans, true));
    error_log('Current webhook configurations: ' . print_r($webhook_configs, true));
}

// Verfügbare Status
$available_statuses = array(
    'active' => __('Active', 'subscription-status-webhook'),
    'pending' => __('Pending', 'subscription-status-webhook'),
    'canceled' => __('Canceled', 'subscription-status-webhook'),
    'abandoned' => __('Abandoned', 'subscription-status-webhook'),
    'expired' => __('Expired', 'subscription-status-webhook')
);

// Funktion zur Aufteilung der Konfigurationen nach Plans
function split_configs_by_plan($configs) {
    $split_configs = array();
    
    foreach ($configs as $index => $config) {
        if (!empty($config['plans'])) {
            foreach ($config['plans'] as $plan_id) {
                $new_config = $config;
                $new_config['plans'] = array($plan_id);
                $split_configs[] = $new_config;
            }
        }
    }
    
    return $split_configs;
}

// Konfigurationen nach Plans aufteilen
$split_webhook_configs = !empty($webhook_configs) ? split_configs_by_plan($webhook_configs) : array();
?>
<div class="wrap">
    <h1><?php _e('Webhook Settings', 'subscription-status-webhook'); ?></h1>

    <form method="post" action="options.php" id="ssw-webhook-config-form">
        <?php settings_fields('ssw_webhook_settings'); ?>

        <!-- Existierende Webhook-Konfigurationen -->
        <div class="ssw-webhook-configurations">
            <?php if (!empty($split_webhook_configs)): ?>
                <?php foreach ($split_webhook_configs as $index => $config): ?>
                    <div class="ssw-webhook-config card">
                        <h3>
                            <?php 
                            $plan_id = reset($config['plans']); // Hole den einzelnen Plan
                            $plan_title = '';
                            foreach ($subscription_plans as $plan) {
                                if ($plan['id'] == $plan_id) {
                                    $plan_title = $plan['title'];
                                    break;
                                }
                            }
                            printf(__('Webhook Configuration for %s', 'subscription-status-webhook'), 
                                  esc_html($plan_title));
                            ?>
                        </h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Subscription Plan', 'subscription-status-webhook'); ?></th>
                                <td>
                                    <select name="ssw_webhook_configurations[<?php echo $index; ?>][plans][]" 
                                            class="ssw-plan-select" 
                                            style="width: 100%;">
                                        <?php foreach ($subscription_plans as $plan): ?>
                                            <option value="<?php echo esc_attr($plan['id']); ?>"
                                                <?php selected($plan['id'] == $plan_id); ?>>
                                                <?php echo esc_html($plan['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Trigger Status', 'subscription-status-webhook'); ?></th>
                                <td>
                                    <?php foreach ($available_statuses as $status => $label): ?>
                                        <label style="display: block; margin-bottom: 5px;">
                                            <input type="checkbox" 
                                                   name="ssw_webhook_configurations[<?php echo $index; ?>][statuses][]" 
                                                   value="<?php echo esc_attr($status); ?>"
                                                   <?php checked(in_array($status, $config['statuses'] ?? array())); ?>>
                                            <?php echo esc_html($label); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Webhook URL', 'subscription-status-webhook'); ?></th>
                                <td>
                                    <input type="url" 
                                           name="ssw_webhook_configurations[<?php echo $index; ?>][webhook_url]" 
                                           value="<?php echo esc_url($config['webhook_url'] ?? ''); ?>"
                                           class="regular-text"
                                           placeholder="https://"
                                           required>
                                </td>
                            </tr>
                        </table>
                        <div class="ssw-webhook-actions">
                            <button type="button" class="button button-link-delete ssw-remove-webhook">
                                <?php _e('Remove Configuration', 'subscription-status-webhook'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button type="button" class="button button-secondary" id="ssw-add-webhook">
            <?php _e('Add New Webhook Configuration', 'subscription-status-webhook'); ?>
        </button>

        <!-- Template für neue Webhook-Konfigurationen -->
        <script type="text/template" id="ssw-webhook-template">
            <div class="ssw-webhook-config card">
                <h3><?php _e('New Webhook Configuration', 'subscription-status-webhook'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Subscription Plan', 'subscription-status-webhook'); ?></th>
                        <td>
                            <select name="ssw_webhook_configurations[{{index}}][plans][]" 
                                    class="ssw-plan-select" 
                                    style="width: 100%;">
                                <?php foreach ($subscription_plans as $plan): ?>
                                    <option value="<?php echo esc_attr($plan['id']); ?>">
                                        <?php echo esc_html($plan['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Trigger Status', 'subscription-status-webhook'); ?></th>
                        <td>
                            <?php foreach ($available_statuses as $status => $label): ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" 
                                           name="ssw_webhook_configurations[{{index}}][statuses][]" 
                                           value="<?php echo esc_attr($status); ?>">
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Webhook URL', 'subscription-status-webhook'); ?></th>
                        <td>
                            <input type="url" 
                                   name="ssw_webhook_configurations[{{index}}][webhook_url]" 
                                   class="regular-text"
                                   placeholder="https://"
                                   required>
                        </td>
                    </tr>
                </table>
                <div class="ssw-webhook-actions">
                    <button type="button" class="button button-link-delete ssw-remove-webhook">
                        <?php _e('Remove Configuration', 'subscription-status-webhook'); ?>
                    </button>
                </div>
            </div>
        </script>

        <?php submit_button(); ?>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    function initSelect2(element) {
        $(element).select2({
            placeholder: '<?php _e('Select subscription plan', 'subscription-status-webhook'); ?>',
            width: '100%'
        });
    }

    // Initialisiere Select2 für bestehende Selects
    $('.ssw-plan-select').each(function() {
        initSelect2(this);
    });

    // Handler für "Add New Webhook"
    var webhookIndex = $('.ssw-webhook-config').length;
    
    $('#ssw-add-webhook').on('click', function() {
        var template = $('#ssw-webhook-template').html();
        template = template.replace(/\{\{index\}\}/g, webhookIndex++);
        
        $('.ssw-webhook-configurations').append(template);
        
        // Initialisiere Select2 für das neue Select
        var newSelect = $('.ssw-webhook-configurations .ssw-webhook-config:last-child .ssw-plan-select');
        initSelect2(newSelect);
    });

    // Handler für "Remove Configuration"
    $(document).on('click', '.ssw-remove-webhook', function() {
        if (confirm('<?php _e('Are you sure you want to remove this webhook configuration?', 'subscription-status-webhook'); ?>')) {
            $(this).closest('.ssw-webhook-config').remove();
        }
    });
});
</script>
