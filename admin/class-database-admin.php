<?php
/**
 * Database Admin interface class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Admin;

use Custom_Page_Builder\Query_Optimizer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Admin class for monitoring database performance
 */
class Database_Admin {
    
    /**
     * Initialize database admin functionality
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_database_submenu']);
        add_action('admin_init', [$this, 'handle_database_actions']);
        add_action('wp_ajax_cpb_optimize_database', [$this, 'ajax_optimize_database']);
        add_action('wp_ajax_cpb_get_query_stats', [$this, 'ajax_get_query_stats']);
    }
    
    /**
     * Add database management submenu
     */
    public function add_database_submenu() {
        add_submenu_page(
            'cpb-cache',
            __('Database Performance', 'custom-page-builder'),
            __('Database', 'custom-page-builder'),
            'manage_options',
            'cpb-database',
            [$this, 'render_database_page']
        );
    }
    
    /**
     * Handle database management actions
     */
    public function handle_database_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['cpb_db_action']) && wp_verify_nonce($_POST['cpb_db_nonce'], 'cpb_db_action')) {
            $action = sanitize_text_field($_POST['cpb_db_action']);
            
            switch ($action) {
                case 'optimize_tables':
                    Query_Optimizer::optimize_database_queries();
                    add_settings_error(
                        'cpb_database',
                        'tables_optimized',
                        __('Database tables optimized successfully.', 'custom-page-builder'),
                        'success'
                    );
                    break;
                    
                case 'enable_query_logging':
                    Query_Optimizer::enable_logging();
                    add_settings_error(
                        'cpb_database',
                        'logging_enabled',
                        __('Query logging enabled.', 'custom-page-builder'),
                        'success'
                    );
                    break;
                    
                case 'disable_query_logging':
                    Query_Optimizer::disable_logging();
                    add_settings_error(
                        'cpb_database',
                        'logging_disabled',
                        __('Query logging disabled.', 'custom-page-builder'),
                        'success'
                    );
                    break;
                    
                case 'clear_query_log':
                    Query_Optimizer::clear_query_log();
                    add_settings_error(
                        'cpb_database',
                        'log_cleared',
                        __('Query log cleared.', 'custom-page-builder'),
                        'success'
                    );
                    break;
            }
        }
    }
    
    /**
     * Render database management page
     */
    public function render_database_page() {
        $table_stats = Query_Optimizer::get_table_stats();
        $query_stats = Query_Optimizer::get_query_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Database Performance', 'custom-page-builder'); ?></h1>
            
            <?php settings_errors('cpb_database'); ?>
            
            <div class="cpb-database-dashboard">
                <div class="cpb-table-stats">
                    <h2><?php _e('Table Statistics', 'custom-page-builder'); ?></h2>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Table', 'custom-page-builder'); ?></th>
                                <th><?php _e('Rows', 'custom-page-builder'); ?></th>
                                <th><?php _e('Size (MB)', 'custom-page-builder'); ?></th>
                                <th><?php _e('Data (MB)', 'custom-page-builder'); ?></th>
                                <th><?php _e('Index (MB)', 'custom-page-builder'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table_stats as $table_name => $stats): ?>
                            <tr>
                                <td><strong><?php echo esc_html($table_name); ?></strong></td>
                                <td><?php echo number_format($stats['rows']); ?></td>
                                <td><?php echo number_format($stats['size_mb'], 2); ?></td>
                                <td><?php echo number_format($stats['data_mb'], 2); ?></td>
                                <td><?php echo number_format($stats['index_mb'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="cpb-query-stats">
                    <h2><?php _e('Query Statistics', 'custom-page-builder'); ?></h2>
                    <?php if (isset($query_stats['error'])): ?>
                        <p class="description"><?php echo esc_html($query_stats['error']); ?></p>
                        <form method="post" style="margin-top: 10px;">
                            <?php wp_nonce_field('cpb_db_action', 'cpb_db_nonce'); ?>
                            <input type="hidden" name="cpb_db_action" value="enable_query_logging">
                            <input type="submit" class="button button-secondary" 
                                   value="<?php _e('Enable Query Logging', 'custom-page-builder'); ?>">
                        </form>
                    <?php else: ?>
                        <table class="widefat">
                            <tbody>
                                <tr>
                                    <td><strong><?php _e('Total Queries', 'custom-page-builder'); ?></strong></td>
                                    <td><?php echo number_format($query_stats['total_queries']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('Total Time', 'custom-page-builder'); ?></strong></td>
                                    <td><?php echo $query_stats['total_time']; ?>s</td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('Average Time', 'custom-page-builder'); ?></strong></td>
                                    <td><?php echo $query_stats['average_time']; ?>s</td>
                                </tr>
                                <tr>
                                    <td><strong><?php _e('Slow Queries', 'custom-page-builder'); ?></strong></td>
                                    <td><?php echo number_format($query_stats['slow_queries']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 15px;">
                            <form method="post" style="display: inline-block; margin-right: 10px;">
                                <?php wp_nonce_field('cpb_db_action', 'cpb_db_nonce'); ?>
                                <input type="hidden" name="cpb_db_action" value="clear_query_log">
                                <input type="submit" class="button button-secondary" 
                                       value="<?php _e('Clear Query Log', 'custom-page-builder'); ?>">
                            </form>
                            
                            <form method="post" style="display: inline-block;">
                                <?php wp_nonce_field('cpb_db_action', 'cpb_db_nonce'); ?>
                                <input type="hidden" name="cpb_db_action" value="disable_query_logging">
                                <input type="submit" class="button button-secondary" 
                                       value="<?php _e('Disable Query Logging', 'custom-page-builder'); ?>">
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="cpb-database-actions">
                <h2><?php _e('Database Actions', 'custom-page-builder'); ?></h2>
                
                <form method="post" style="margin-bottom: 20px;">
                    <?php wp_nonce_field('cpb_db_action', 'cpb_db_nonce'); ?>
                    <input type="hidden" name="cpb_db_action" value="optimize_tables">
                    <p>
                        <input type="submit" class="button button-primary" 
                               value="<?php _e('Optimize Database Tables', 'custom-page-builder'); ?>">
                    </p>
                    <p class="description">
                        <?php _e('This will optimize database tables and add missing indexes for better performance.', 'custom-page-builder'); ?>
                    </p>
                </form>
            </div>
            
            <?php if (!isset($query_stats['error']) && !empty($query_stats['queries'])): ?>
            <div class="cpb-query-details">
                <h2><?php _e('Recent Queries', 'custom-page-builder'); ?></h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Query', 'custom-page-builder'); ?></th>
                            <th><?php _e('Time', 'custom-page-builder'); ?></th>
                            <th><?php _e('Backtrace', 'custom-page-builder'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($query_stats['queries'], -10) as $query): ?>
                        <tr>
                            <td>
                                <code style="font-size: 11px;">
                                    <?php echo esc_html(substr($query['query'], 0, 100)) . (strlen($query['query']) > 100 ? '...' : ''); ?>
                                </code>
                            </td>
                            <td>
                                <?php echo isset($query['execution_time']) ? $query['execution_time'] . 's' : 'N/A'; ?>
                            </td>
                            <td>
                                <small><?php echo esc_html($query['backtrace'] ?? 'N/A'); ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
        .cpb-database-dashboard {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .cpb-table-stats,
        .cpb-query-stats,
        .cpb-database-actions,
        .cpb-query-details {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-bottom: 20px;
        }
        
        .cpb-query-details {
            grid-column: 1 / -1;
        }
        
        @media (max-width: 768px) {
            .cpb-database-dashboard {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    /**
     * AJAX handler for optimizing database
     */
    public function ajax_optimize_database() {
        check_ajax_referer('cpb_db_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'custom-page-builder'));
        }
        
        Query_Optimizer::optimize_database_queries();
        
        wp_send_json_success([
            'message' => __('Database optimized successfully.', 'custom-page-builder')
        ]);
    }
    
    /**
     * AJAX handler for getting query statistics
     */
    public function ajax_get_query_stats() {
        check_ajax_referer('cpb_db_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'custom-page-builder'));
        }
        
        $query_stats = Query_Optimizer::get_query_stats();
        $table_stats = Query_Optimizer::get_table_stats();
        
        wp_send_json_success([
            'query_stats' => $query_stats,
            'table_stats' => $table_stats
        ]);
    }
}