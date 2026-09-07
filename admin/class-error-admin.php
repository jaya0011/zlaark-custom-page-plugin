<?php
/**
 * Error Administration Interface
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin interface for comprehensive error reporting system
 */
class Error_Admin {
    
    /**
     * Initialize error admin interface
     */
    public static function init() {
        add_action('admin_menu', [self::class, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_scripts']);
        add_action('wp_ajax_cpb_clear_error_reports', [self::class, 'ajax_clear_error_reports']);
        add_action('wp_ajax_cpb_export_error_reports', [self::class, 'ajax_export_error_reports']);
    }
    
    /**
     * Add admin menu for error reporting
     */
    public static function add_admin_menu() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        add_submenu_page(
            'tools.php',
            __('Custom Page Builder - Error Reports', 'custom-page-builder'),
            __('CPB Error Reports', 'custom-page-builder'),
            'manage_options',
            'cpb-error-reports',
            [self::class, 'render_error_reports_page']
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook_suffix Current admin page hook suffix
     */
    public static function enqueue_admin_scripts($hook_suffix) {
        if ($hook_suffix !== 'tools_page_cpb-error-reports') {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        // Inline script for AJAX functionality
        $inline_script = "
        jQuery(document).ready(function($) {
            // Clear error reports
            $('#clear-error-reports').on('click', function(e) {
                e.preventDefault();
                
                // Proceed without confirmation
                
                $.post(ajaxurl, {
                    action: 'cpb_clear_error_reports',
                    nonce: '" . wp_create_nonce('cpb_clear_error_reports') . "'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
            
            // Export error reports
            $('#export-error-reports').on('click', function(e) {
                e.preventDefault();
                
                window.location.href = ajaxurl + '?action=cpb_export_error_reports&nonce=" . wp_create_nonce('cpb_export_error_reports') . "';
            });
            
            // Auto-refresh functionality
            var autoRefresh = false;
            $('#auto-refresh').on('change', function() {
                autoRefresh = $(this).is(':checked');
                if (autoRefresh) {
                    setInterval(function() {
                        if (autoRefresh) {
                            location.reload();
                        }
                    }, 30000); // Refresh every 30 seconds
                }
            });
            
            // Filter functionality
            $('#error-filter').on('change', function() {
                var filter = $(this).val();
                $('.error-row').show();
                
                if (filter && filter !== 'all') {
                    $('.error-row').hide();
                    $('.error-row[data-category=\"' + filter + '\"]').show();
                }
            });
            
            // Context filter
            $('#context-filter').on('change', function() {
                var filter = $(this).val();
                $('.error-row').show();
                
                if (filter && filter !== 'all') {
                    $('.error-row').hide();
                    $('.error-row[data-context=\"' + filter + '\"]').show();
                }
            });
        });
        ";
        
        wp_add_inline_script('jquery', $inline_script);
        
        // Add CSS styles
        $inline_style = "
        .error-reports-container {
            max-width: 100%;
            margin: 20px 0;
        }
        
        .error-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .error-stat-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 15px;
            min-width: 150px;
            text-align: center;
        }
        
        .error-stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #1d2327;
        }
        
        .error-stat-label {
            font-size: 12px;
            color: #646970;
            text-transform: uppercase;
        }
        
        .error-category {
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .error-category-fatal { background: #dc3232; color: white; }
        .error-category-warning { background: #ffb900; color: black; }
        .error-category-notice { background: #00a0d2; color: white; }
        .error-category-debug { background: #00a32a; color: white; }
        
        .error-severity {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            font-size: 10px;
            font-weight: bold;
            color: white;
        }
        
        .severity-1, .severity-2, .severity-3 { background: #00a32a; }
        .severity-4, .severity-5, .severity-6 { background: #ffb900; }
        .severity-7, .severity-8 { background: #ff6900; }
        .severity-9, .severity-10 { background: #dc3232; }
        
        .error-details {
            display: none;
            background: #f6f7f7;
            padding: 10px;
            margin-top: 5px;
            border-left: 3px solid #0073aa;
        }
        
        .error-details pre {
            background: #fff;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            overflow-x: auto;
            font-size: 12px;
        }
        
        .toggle-details {
            cursor: pointer;
            color: #0073aa;
            text-decoration: underline;
        }
        
        .filters-container {
            background: #fff;
            padding: 15px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .filters-container select {
            margin-right: 10px;
        }
        
        .actions-container {
            margin-bottom: 20px;
        }
        
        .actions-container .button {
            margin-right: 10px;
        }
        ";
        
        wp_add_inline_style('wp-admin', $inline_style);
    }
    
    /**
     * Render error reports admin page
     */
    public static function render_error_reports_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Get filter parameters
        $category_filter = $_GET['category'] ?? 'all';
        $context_filter = $_GET['context'] ?? 'all';
        $days_filter = (int) ($_GET['days'] ?? 7);
        
        // Build criteria for filtering
        $criteria = [];
        if ($category_filter !== 'all') {
            $criteria['category'] = $category_filter;
        }
        if ($context_filter !== 'all') {
            $criteria['context'] = $context_filter;
        }
        if ($days_filter > 0) {
            $criteria['date_from'] = date('Y-m-d H:i:s', strtotime("-{$days_filter} days"));
        }
        
        // Get error reports and statistics
        $error_reports = Error_Reporter::get_error_reports($criteria, 100);
        $error_stats = Error_Reporter::get_error_statistics($criteria);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Custom Page Builder - Error Reports', 'custom-page-builder'); ?></h1>
            
            <div class="error-reports-container">
                
                <!-- Error Statistics -->
                <div class="error-stats">
                    <div class="error-stat-box">
                        <div class="error-stat-number"><?php echo $error_stats['total_errors']; ?></div>
                        <div class="error-stat-label"><?php _e('Total Errors', 'custom-page-builder'); ?></div>
                    </div>
                    
                    <?php foreach ($error_stats['by_category'] as $category_stat): ?>
                        <div class="error-stat-box">
                            <div class="error-stat-number"><?php echo $category_stat['count']; ?></div>
                            <div class="error-stat-label"><?php echo ucfirst($category_stat['category']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Filters -->
                <div class="filters-container">
                    <h3><?php _e('Filters', 'custom-page-builder'); ?></h3>
                    
                    <select id="error-filter">
                        <option value="all"><?php _e('All Categories', 'custom-page-builder'); ?></option>
                        <option value="fatal" <?php selected($category_filter, 'fatal'); ?>><?php _e('Fatal', 'custom-page-builder'); ?></option>
                        <option value="warning" <?php selected($category_filter, 'warning'); ?>><?php _e('Warning', 'custom-page-builder'); ?></option>
                        <option value="notice" <?php selected($category_filter, 'notice'); ?>><?php _e('Notice', 'custom-page-builder'); ?></option>
                        <option value="debug" <?php selected($category_filter, 'debug'); ?>><?php _e('Debug', 'custom-page-builder'); ?></option>
                    </select>
                    
                    <select id="context-filter">
                        <option value="all"><?php _e('All Contexts', 'custom-page-builder'); ?></option>
                        <option value="plugin_activation" <?php selected($context_filter, 'plugin_activation'); ?>><?php _e('Plugin Activation', 'custom-page-builder'); ?></option>
                        <option value="admin_interface" <?php selected($context_filter, 'admin_interface'); ?>><?php _e('Admin Interface', 'custom-page-builder'); ?></option>
                        <option value="rest_api" <?php selected($context_filter, 'rest_api'); ?>><?php _e('REST API', 'custom-page-builder'); ?></option>
                        <option value="database" <?php selected($context_filter, 'database'); ?>><?php _e('Database', 'custom-page-builder'); ?></option>
                        <option value="file_system" <?php selected($context_filter, 'file_system'); ?>><?php _e('File System', 'custom-page-builder'); ?></option>
                    </select>
                    
                    <label>
                        <input type="checkbox" id="auto-refresh">
                        <?php _e('Auto-refresh (30s)', 'custom-page-builder'); ?>
                    </label>
                </div>
                
                <!-- Actions -->
                <div class="actions-container">
                    <button id="clear-error-reports" class="button button-secondary">
                        <?php _e('Clear All Reports', 'custom-page-builder'); ?>
                    </button>
                    <button id="export-error-reports" class="button button-secondary">
                        <?php _e('Export Reports', 'custom-page-builder'); ?>
                    </button>
                    <a href="<?php echo admin_url('tools.php?page=cpb-error-reports'); ?>" class="button button-secondary">
                        <?php _e('Refresh', 'custom-page-builder'); ?>
                    </a>
                </div>
                
                <!-- Error Reports Table -->
                <?php if (!empty($error_reports)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 140px;"><?php _e('Timestamp', 'custom-page-builder'); ?></th>
                                <th style="width: 80px;"><?php _e('Category', 'custom-page-builder'); ?></th>
                                <th style="width: 40px;"><?php _e('Severity', 'custom-page-builder'); ?></th>
                                <th style="width: 120px;"><?php _e('Context', 'custom-page-builder'); ?></th>
                                <th><?php _e('Message', 'custom-page-builder'); ?></th>
                                <th style="width: 80px;"><?php _e('User', 'custom-page-builder'); ?></th>
                                <th style="width: 60px;"><?php _e('Details', 'custom-page-builder'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($error_reports as $report): ?>
                                <tr class="error-row" 
                                    data-category="<?php echo esc_attr($report['category']); ?>" 
                                    data-context="<?php echo esc_attr($report['context']); ?>">
                                    
                                    <td><?php echo esc_html($report['timestamp']); ?></td>
                                    
                                    <td>
                                        <span class="error-category error-category-<?php echo esc_attr($report['category']); ?>">
                                            <?php echo esc_html(ucfirst($report['category'])); ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <span class="error-severity severity-<?php echo esc_attr($report['severity']); ?>">
                                            <?php echo esc_html($report['severity']); ?>
                                        </span>
                                    </td>
                                    
                                    <td><?php echo esc_html(str_replace('_', ' ', ucwords($report['context'], '_'))); ?></td>
                                    
                                    <td>
                                        <?php echo esc_html(wp_trim_words($report['message'], 15)); ?>
                                        <?php if (strlen($report['message']) > 100): ?>
                                            <span class="toggle-details" onclick="jQuery(this).closest('tr').next('.error-details').toggle();">
                                                <?php _e('Show more', 'custom-page-builder'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php 
                                        if ($report['user_id']) {
                                            $user = get_user_by('id', $report['user_id']);
                                            echo $user ? esc_html($user->display_name) : esc_html($report['user_id']);
                                        } else {
                                            echo __('System', 'custom-page-builder');
                                        }
                                        ?>
                                    </td>
                                    
                                    <td>
                                        <span class="toggle-details" onclick="jQuery(this).closest('tr').next('.error-details').toggle();">
                                            <?php _e('View', 'custom-page-builder'); ?>
                                        </span>
                                    </td>
                                </tr>
                                
                                <!-- Error Details Row -->
                                <tr class="error-details">
                                    <td colspan="7">
                                        <div class="error-details">
                                            <h4><?php _e('Full Message', 'custom-page-builder'); ?></h4>
                                            <p><?php echo esc_html($report['message']); ?></p>
                                            
                                            <?php if (!empty($report['additional_data'])): ?>
                                                <h4><?php _e('Additional Data', 'custom-page-builder'); ?></h4>
                                                <pre><?php echo esc_html(json_encode($report['additional_data'], JSON_PRETTY_PRINT)); ?></pre>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($report['debug_info'])): ?>
                                                <h4><?php _e('Debug Information', 'custom-page-builder'); ?></h4>
                                                <details>
                                                    <summary><?php _e('Click to expand debug info', 'custom-page-builder'); ?></summary>
                                                    <pre><?php echo esc_html(json_encode($report['debug_info'], JSON_PRETTY_PRINT)); ?></pre>
                                                </details>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($report['exception'])): ?>
                                                <h4><?php _e('Exception Details', 'custom-page-builder'); ?></h4>
                                                <p><strong><?php _e('Class:', 'custom-page-builder'); ?></strong> <?php echo esc_html($report['exception']['class']); ?></p>
                                                <p><strong><?php _e('File:', 'custom-page-builder'); ?></strong> <?php echo esc_html($report['exception']['file']); ?>:<?php echo esc_html($report['exception']['line']); ?></p>
                                                
                                                <?php if (!empty($report['exception']['trace'])): ?>
                                                    <details>
                                                        <summary><?php _e('Stack Trace', 'custom-page-builder'); ?></summary>
                                                        <pre><?php echo esc_html(json_encode($report['exception']['trace'], JSON_PRETTY_PRINT)); ?></pre>
                                                    </details>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="notice notice-info">
                        <p><?php _e('No error reports found for the selected criteria.', 'custom-page-builder'); ?></p>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler to clear error reports
     */
    public static function ajax_clear_error_reports() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.'));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'cpb_clear_error_reports')) {
            wp_send_json_error(__('Invalid nonce.'));
        }
        
        try {
            $deleted_count = Error_Reporter::clear_old_reports(0); // Clear all reports
            wp_send_json_success(sprintf(__('Cleared %d error reports.', 'custom-page-builder'), $deleted_count));
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX handler to export error reports
     */
    public static function ajax_export_error_reports() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.'));
        }
        
        if (!wp_verify_nonce($_GET['nonce'], 'cpb_export_error_reports')) {
            wp_die(__('Invalid nonce.'));
        }
        
        try {
            $error_reports = Error_Reporter::get_error_reports([], 1000); // Get up to 1000 reports
            
            $filename = 'cpb-error-reports-' . date('Y-m-d-H-i-s') . '.json';
            
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            
            echo json_encode([
                'export_date' => current_time('mysql'),
                'site_url' => home_url(),
                'plugin_version' => CUSTOM_PAGE_BUILDER_VERSION,
                'total_reports' => count($error_reports),
                'reports' => $error_reports
            ], JSON_PRETTY_PRINT);
            
            exit;
        } catch (\Exception $e) {
            wp_die(__('Export failed: ') . $e->getMessage());
        }
    }
}