<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1><?php _e('Webhook Logs', 'subscription-status-webhook'); ?></h1>

    <div class="ssw-log-filters card">
        <form method="get">
            <input type="hidden" name="page" value="subscription-webhooks-logs">
            
            <div class="ssw-filter-group">
                <label for="filter-date">
                    <?php _e('Date Range:', 'subscription-status-webhook'); ?>
                </label>
                <select name="filter_date" id="filter-date">
                    <option value="today"><?php _e('Today', 'subscription-status-webhook'); ?></option>
                    <option value="yesterday"><?php _e('Yesterday', 'subscription-status-webhook'); ?></option>
                    <option value="week"><?php _e('Last 7 Days', 'subscription-status-webhook'); ?></option>
                    <option value="month"><?php _e('Last 30 Days', 'subscription-status-webhook'); ?></option>
                </select>

                <label for="filter-type">
                    <?php _e('Trigger Type:', 'subscription-status-webhook'); ?>
                </label>
                <select name="filter_type" id="filter-type">
                    <option value=""><?php _e('All Types', 'subscription-status-webhook'); ?></option>
                    <option value="realtime"><?php _e('Realtime', 'subscription-status-webhook'); ?></option>
                    <option value="scheduled"><?php _e('Scheduled', 'subscription-status-webhook'); ?></option>
                    <option value="manual"><?php _e('Manual', 'subscription-status-webhook'); ?></option>
                </select>

                <?php submit_button(__('Apply Filters', 'subscription-status-webhook'), 'secondary', 'apply_filters', false); ?>
            </div>
        </form>
    </div>

    <div class="ssw-logs-table card">
        <?php if (!empty($logs)): ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'subscription-status-webhook'); ?></th>
                        <th><?php _e('Type', 'subscription-status-webhook'); ?></th>
                        <th><?php _e('Webhooks Sent', 'subscription-status-webhook'); ?></th>
                        <th><?php _e('Details', 'subscription-status-webhook'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->execution_time)); ?></td>
                            <td><?php echo esc_html($log->trigger_type); ?></td>
                            <td><?php echo intval($log->webhooks_sent); ?></td>
                            <td><?php echo esc_html($log->details); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No logs found.', 'subscription-status-webhook'); ?></p>
        <?php endif; ?>
    </div>

    <div class="ssw-log-actions card">
        <form method="post">
            <?php wp_nonce_field('ssw_clear_logs', 'ssw_clear_logs_nonce'); ?>
            <button type="submit" name="clear_logs" class="button" 
                    onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear all logs?', 'subscription-status-webhook'); ?>');">
                <?php _e('Clear Logs', 'subscription-status-webhook'); ?>
            </button>
        </form>
    </div>
</div>
