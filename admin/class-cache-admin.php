<?php
/**
 * Cache Admin interface class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Admin;

use Custom_Page_Builder\Cache_Manager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache Admin class for managing cache through WordPress admin
 */
class Cache_Admin {
    
    /**
     * Initialize cache admin functionality
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_cache_submenu']);
        add_action('admin_init', [$this, 'handle_cache_actions']);
        add_action('wp_ajax_cpb_clear_cache', [$this, 'ajax_clear_cache']);
        add_action('wp_ajax_cpb_warm_cache', [$this, 'ajax_warm_cache']);
        add_action('wp_ajax_cpb_get_cache_stats', [$this, 'ajax_get_cache_stats']);
    }
    
    /**
     * Add cache management submenu
     */
    public function add_cache_submenu() {
        add_submenu_page(
            'custom-page-builder',
            __('Cache Management', 'custom-page-builder'),
            __('Cache', 'custom-page-builder'),
            'manage_options',
            'cpb-cache',
            [$this, 'render_cache_page']
        );
    }
    
    /**
     * Handle cache management actions
     */
    public function handle_cache_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['cpb_cache_action']) && wp_verify_nonce($_POST['cpb_cache_nonce'], 'cpb_cache_action')) {
            $action = sanitize_text_field($_POST['cpb_cache_action']);
            
            switch ($action) {
                case 'clear_all':
                    $cleared = Cache_Manager::clear_all();
                    add_settings_error(
                        'cpb_cache',
                        'cache_cleared',
                        sprintf(__('Cleared %d cache entries.', 'custom-page-builder'), $cleared),
                        'success'
                    );
                    break;
                    
                case 'warm_cache':
                    Cache_Manager::warm_cache();
                    add_settings_error(
                        'cpb_cache',
                        'cache_warmed',
                        __('Cache warmed for recent pages.', 'custom-page-builder'),
                        'success'
                    );
                    break;
                    
                case 'update_settings':
                    $this->update_cache_settings();
                    break;
            }
        }
    }
    
    /**
     * Update cache settings
     */
    private function update_cache_settings() {
        $settings = get_option('custom_page_builder_settings', []);
        
        $settings['enable_caching'] = isset($_POST['enable_caching']);
        $settings['cache_expiration_page'] = (int) ($_POST['cache_expiration_page'] ?? 3600);
        $settings['cache_expiration_list'] = (int) ($_POST['cache_expiration_list'] ?? 1800);
        $settings['cache_expiration_sections'] = (int) ($_POST['cache_expiration_sections'] ?? 3600);
        
        update_option('custom_page_builder_settings', $settings);
        
        add_settings_error(
            'cpb_cache',
            'settings_updated',
            __('Cache settings updated.', 'custom-page-builder'),
            'success'
        );
    }
    
    /**
     * Render cache management page
     */
    public function render_cache_page() {
        $settings = get_option('custom_page_builder_settings', []);
        $cache_info = Cache_Manager::get_cache_info();
        $cache_stats = Cache_Manager::get_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Cache Management', 'custom-page-builder'); ?></h1>
            
            <?php settings_errors('cpb_cache'); ?>
            
            <div class="cpb-cache-dashboard">
                <div class="cpb-cache-stats">
                    <h2><?php _e('Cache Statistics', 'custom-page-builder'); ?></h2>
                    <table class="widefat">
                        <tbody>
                            <tr>
                                <td><strong><?php _e('Cache Entries', 'custom-page-builder'); ?></strong></td>
                                <td><?php echo number_format($cache_info['count']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Cache Size', 'custom-page-builder'); ?></strong></td>
                                <td><?php echo $cache_info['size_mb']; ?> MB</td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Cache Hits', 'custom-page-builder'); ?></strong></td>
                                <td><?php echo number_format($cache_stats['hits']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Cache Misses', 'custom-page-builder'); ?></strong></td>
                                <td><?php echo number_format($cache_stats['misses']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Hit Rate', 'custom-page-builder'); ?></strong></td>
                                <td>
                                    <?php 
                                    $total = $cache_stats['hits'] + $cache_stats['misses'];
                                    $hit_rate = $total > 0 ? ($cache_stats['hits'] / $total) * 100 : 0;
                                    echo number_format($hit_rate, 1) . '%';
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="cpb-cache-actions">
                    <h2><?php _e('Cache Actions', 'custom-page-builder'); ?></h2>
                    
                    <form method="post" style="margin-bottom: 20px;">
                        <?php wp_nonce_field('cpb_cache_action', 'cpb_cache_nonce'); ?>
                        <input type="hidden" name="cpb_cache_action" value="clear_all">
                        <p>
                            <input type="submit" class="button button-secondary" 
                                   value="<?php _e('Clear All Cache', 'custom-page-builder'); ?>"
>
                        </p>
                        <p class="description">
                            <?php _e('This will clear all cached page data and API responses.', 'custom-page-builder'); ?>
                        </p>
                    </form>
                    
                    <form method="post">
                        <?php wp_nonce_field('cpb_cache_action', 'cpb_cache_nonce'); ?>
                        <input type="hidden" name="cpb_cache_action" value="warm_cache">
                        <p>
                            <input type="submit" class="button button-secondary" 
                                   value="<?php _e('Warm Cache', 'custom-page-builder'); ?>">
                        </p>
                        <p class="description">
                            <?php _e('Pre-load cache for the most recent published pages.', 'custom-page-builder'); ?>
                        </p>
                    </form>
                </div>
            </div>
            
            <form method="post" class="cpb-cache-settings">
                <h2><?php _e('Cache Settings', 'custom-page-builder'); ?></h2>
                
                <?php wp_nonce_field('cpb_cache_action', 'cpb_cache_nonce'); ?>
                <input type="hidden" name="cpb_cache_action" value="update_settings">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Caching', 'custom-page-builder'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_caching" value="1" 
                                       <?php checked($settings['enable_caching'] ?? true); ?>>
                                <?php _e('Enable API response caching', 'custom-page-builder'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Page Cache Expiration', 'custom-page-builder'); ?></th>
                        <td>
                            <input type="number" name="cache_expiration_page" 
                                   value="<?php echo esc_attr($settings['cache_expiration_page'] ?? 3600); ?>"
                                   min="60" max="86400" step="60">
                            <p class="description"><?php _e('Cache expiration for individual pages in seconds (60-86400).', 'custom-page-builder'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Page List Cache Expiration', 'custom-page-builder'); ?></th>
                        <td>
                            <input type="number" name="cache_expiration_list" 
                                   value="<?php echo esc_attr($settings['cache_expiration_list'] ?? 1800); ?>"
                                   min="60" max="86400" step="60">
                            <p class="description"><?php _e('Cache expiration for page lists in seconds (60-86400).', 'custom-page-builder'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Sections Cache Expiration', 'custom-page-builder'); ?></th>
                        <td>
                            <input type="number" name="cache_expiration_sections" 
                                   value="<?php echo esc_attr($settings['cache_expiration_sections'] ?? 3600); ?>"
                                   min="60" max="86400" step="60">
                            <p class="description"><?php _e('Cache expiration for page sections in seconds (60-86400).', 'custom-page-builder'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Settings', 'custom-page-builder')); ?>
            </form>
        </div>
        
        <style>
        .cpb-cache-dashboard {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .cpb-cache-stats table,
        .cpb-cache-actions {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .cpb-cache-settings {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .cpb-cache-dashboard {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    /**
     * AJAX handler for clearing cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('cpb_cache_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'custom-page-builder'));
        }
        
        $cleared = Cache_Manager::clear_all();
        
        wp_send_json_success([
            'message' => sprintf(__('Cleared %d cache entries.', 'custom-page-builder'), $cleared),
            'cleared' => $cleared
        ]);
    }
    
    /**
     * AJAX handler for warming cache
     */
    public function ajax_warm_cache() {
        check_ajax_referer('cpb_cache_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'custom-page-builder'));
        }
        
        Cache_Manager::warm_cache();
        
        wp_send_json_success([
            'message' => __('Cache warmed successfully.', 'custom-page-builder')
        ]);
    }
    
    /**
     * AJAX handler for getting cache statistics
     */
    public function ajax_get_cache_stats() {
        check_ajax_referer('cpb_cache_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'custom-page-builder'));
        }
        
        $cache_info = Cache_Manager::get_cache_info();
        $cache_stats = Cache_Manager::get_stats();
        
        wp_send_json_success([
            'info' => $cache_info,
            'stats' => $cache_stats
        ]);
    }
}