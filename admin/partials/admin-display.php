<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="ssw-dashboard">
        <div class="ssw-stats card">
            <h2><?php _e('Webhook Statistics', 'subscription-status-webhook'); ?></h2>
            <?php 
            $stats = SSW_Logger::get_instance()->get_execution_stats();
            ?>
            <div class="ssw-stats-grid">
                <div class="ssw-stat-item">
                    <span class="ssw-stat-label"><?php _e('Total Webhooks Sent', 'subscription-status-webhook'); ?></span>
                    <span class="ssw-stat-value"><?php echo intval($stats->total_webhooks_sent ?? 0); ?></span>
                </div>
                <div class="ssw-stat-item">
                    <span class="ssw-stat-label"><?php _e('Total Executions', 'subscription-status-webhook'); ?></span>
                    <span class="ssw-stat-value"><?php echo intval($stats->total_executions ?? 0); ?></span>
                </div>
                <div class="ssw-stat-item">
                    <span class="ssw-stat-label"><?php _e('Last Execution', 'subscription-status-webhook'); ?></span>
                    <span class="ssw-stat-value"><?php echo isset($stats->last_execution) ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($stats->last_execution)) : __('Never', 'subscription-status-webhook'); ?></span>
                </div>
            </div>
        </div>

        <div class="ssw-quick-actions card">
            <h2><?php _e('Quick Actions', 'subscription-status-webhook'); ?></h2>
            <div class="ssw-button-group">
                <a href="<?php echo admin_url('admin.php?page=subscription-webhooks-settings'); ?>" class="button button-primary">
                    <?php _e('Configure Webhooks', 'subscription-status-webhook'); ?>
                </a>
                <button type="button" class="button ssw-test-all-webhooks">
                    <?php _e('Test All Webhooks', 'subscription-status-webhook'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=subscription-webhooks-logs'); ?>" class="button">
                    <?php _e('View Logs', 'subscription-status-webhook'); ?>
                </a>
            </div>
        </div>

        <div class="ssw-recent-activity card">
            <h2><?php _e('Recent Activity', 'subscription-status-webhook'); ?></h2>
            <?php 
            $recent_logs = SSW_Logger::get_instance()->get_recent_logs(5);
            if (!empty($recent_logs)): ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Time', 'subscription-status-webhook'); ?></th>
                            <th><?php _e('Type', 'subscription-status-webhook'); ?></th>
                            <th><?php _e('Details', 'subscription-status-webhook'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->execution_time)); ?></td>
                                <td><?php echo esc_html($log->trigger_type); ?></td>
                                <td><?php echo esc_html($log->details); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No recent activity found.', 'subscription-status-webhook'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
