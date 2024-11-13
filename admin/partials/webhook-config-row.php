<?php
if (!defined('ABSPATH')) exit;

// Hole alle verfügbaren Subscription Plans
$subscription_plans = SSW_Settings::get_instance()->get_subscription_plans();

// Debug-Ausgabe
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Available subscription plans: ' . print_r($subscription_plans, true));
}

// Hole aktuelle Webhook-Konfigurationen
$webhook_configs = get_option('ssw_webhook_configurations', array());

// Verfügbare Status
$available_statuses = array(
    'active' => __('Active', 'subscription-status-webhook'),
    'pending' => __('Pending', 'subscription-status-webhook'),
    'canceled' => __('Canceled', 'subscription-status-webhook'),
    'abandoned' => __('Abandoned', 'subscription-status-webhook'),
    'expired' => __('Expired', 'subscription-status-webhook')
);
?>
<div class="wrap">
    <h1><?php _e('Webhook Settings', 'subscription-status-webhook'); ?></h1>

    <form method="post" action="options.php" id="ssw-webhook-config-form">
        <?php settings_fields('ssw_webhook_settings'); ?>

        <div class="ssw-webhook-configurations">
            <?php 
            if (!empty($webhook_configs)) {
                foreach ($webhook_configs as $index => $config) {
                    include SSW_PLUGIN_DIR . 'admin/partials/webhook-config-row.php';
                }
            }
            ?>
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
                        <th scope="row"><?php _e('Subscription Plans', 'subscription-status-webhook'); ?></th>
                        <td>
  <select name="ssw_webhook_configurations[new][plans][]" 
                                multiple="multiple" 
                                class="ssw-plan-select" 
                                style="width: 100%;">
                            <?php foreach ($subscription_plans as $plan): ?>
                                <option value="<?php echo esc_attr($plan['id']); ?>">
                                    <?php echo esc_html($plan['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                            <p class="description">
                                <?php _e('Select one or more subscription plans for this webhook', 'subscription-status-webhook'); ?>
                            </p>
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
                            <p class="description">
                                <?php _e('The URL that will receive the webhook data', 'subscription-status-webhook'); ?>
                            </p>
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
    // Initialisiere Select2 für bestehende Selects
    $('.ssw-plan-select').select2({
        placeholder: '<?php _e('Select subscription plans', 'subscription-status-webhook'); ?>',
        width: '100%'
    });

    // Handler für "Add New Webhook"
    var webhookIndex = $('.ssw-webhook-config').length;
    
    $('#ssw-add-webhook').on('click', function() {
        var template = $('#ssw-webhook-template').html();
        template = template.replace(/{{index}}/g, webhookIndex++);
        
        $('.ssw-webhook-configurations').append(template);
        
        // Initialisiere Select2 für das neue Select
        $('.ssw-webhook-configurations .ssw-webhook-config:last-child .ssw-plan-select').select2({
            placeholder: '<?php _e('Select subscription plans', 'subscription-status-webhook'); ?>',
            width: '100%'
        });
    });

    // Handler für "Remove Configuration"
    $(document).on('click', '.ssw-remove-webhook', function() {
        if (confirm('<?php _e('Are you sure you want to remove this webhook configuration?', 'subscription-status-webhook'); ?>')) {
            $(this).closest('.ssw-webhook-config').remove();
        }
    });
});
</script>
