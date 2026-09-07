<?php
/**
 * Plugin uninstaller class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Uninstaller class for cleanup on plugin deletion
 */
class Uninstaller {
    
    /**
     * Plugin uninstall
     */
    public function uninstall() {
        // Check if user has permission to delete plugins
        if (!current_user_can('delete_plugins')) {
            return;
        }
        
        // Check if this is a multisite installation
        if (is_multisite()) {
            $this->uninstall_multisite();
        } else {
            $this->uninstall_single_site();
        }
    }
    
    /**
     * Uninstall for single site
     */
    private function uninstall_single_site() {
        // Remove database tables
        $this->drop_tables();
        
        // Remove plugin options
        $this->remove_options();
        
        // Remove user meta
        $this->remove_user_meta();
        
        // Remove upload directories
        $this->remove_upload_directories();
        
        // Clear scheduled events
        $this->clear_scheduled_events();
        
        // Remove transients
        $this->remove_transients();
    }
    
    /**
     * Uninstall for multisite
     */
    private function uninstall_multisite() {
        // Database access handled by Database_Helper
        
        // Get all blog IDs
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            $this->uninstall_single_site();
            restore_current_blog();
        }
        
        // Remove network-wide options if any
        delete_site_option('custom_page_builder_network_settings');
    }
    
    /**
     * Drop database tables
     */
    private function drop_tables() {
        // Database access handled by Database_Helper
        
        $tables = [
            $wpdb->prefix . 'custom_page_sections',
            $wpdb->prefix . 'custom_pages'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Log any database errors
        if (!empty($wpdb->last_error)) {
            error_log('Custom Page Builder: Database table deletion error - ' . $wpdb->last_error);
        }
    }
    
    /**
     * Remove plugin options
     */
    private function remove_options() {
        $options_to_remove = [
            'custom_page_builder_settings',
            'custom_page_builder_db_version',
            'custom_page_builder_activated_at',
            'custom_page_builder_cache_settings',
            'custom_page_builder_api_settings',
            'custom_page_builder_secure_image_settings'
        ];
        
        foreach ($options_to_remove as $option) {
            delete_option($option);
        }
        
        // Remove any options with plugin prefix
        // Database access handled by Database_Helper
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                'custom_page_builder_%'
            )
        );
    }
    
    /**
     * Remove user meta data
     */
    private function remove_user_meta() {
        // Database access handled by Database_Helper
        
        $meta_keys_to_remove = [
            'cpb_user_preferences',
            'cpb_last_page_edited',
            'cpb_admin_notices_dismissed'
        ];
        
        foreach ($meta_keys_to_remove as $meta_key) {
            $wpdb->delete(
                $wpdb->usermeta,
                ['meta_key' => $meta_key]
            );
        }
        
        // Remove any user meta with plugin prefix
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
                'cpb_%'
            )
        );
    }
    
    /**
     * Remove upload directories
     */
    private function remove_upload_directories() {
        $upload_dir = WordPress_Helper::safe_wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/custom-page-builder';
        
        if (file_exists($plugin_upload_dir)) {
            $this->remove_directory_recursive($plugin_upload_dir);
        }
    }
    
    /**
     * Recursively remove directory and its contents
     *
     * @param string $dir Directory path
     */
    private function remove_directory_recursive($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $file_path = $dir . '/' . $file;
            
            if (is_dir($file_path)) {
                $this->remove_directory_recursive($file_path);
            } else {
                unlink($file_path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Clear scheduled events
     */
    private function clear_scheduled_events() {
        $scheduled_events = [
            'cpb_cleanup_temp_files',
            'cpb_update_page_status',
            'cpb_cache_cleanup',
            'cpb_database_optimization'
        ];
        
        foreach ($scheduled_events as $event) {
            wp_clear_scheduled_hook($event);
        }
    }
    
    /**
     * Remove transients
     */
    private function remove_transients() {
        // Database access handled by Database_Helper
        
        // Remove transients with plugin prefix
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_cpb_%',
                '_transient_timeout_cpb_%'
            )
        );
        
        // Remove site transients if multisite
        if (is_multisite()) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
                    '_site_transient_cpb_%',
                    '_site_transient_timeout_cpb_%'
                )
            );
        }
    }
    
    /**
     * Check if plugin data should be preserved
     *
     * @return bool
     */
    private function should_preserve_data() {
        // Check if there's a setting to preserve data on uninstall
        $settings = get_option('custom_page_builder_settings', []);
        return isset($settings['preserve_data_on_uninstall']) && $settings['preserve_data_on_uninstall'];
    }
    
    /**
     * Create backup before uninstall (optional)
     */
    private function create_backup() {
        // Database access handled by Database_Helper
        
        $backup_data = [
            'pages' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}custom_pages", ARRAY_A),
            'sections' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}custom_page_sections", ARRAY_A),
            'options' => []
        ];
        
        // Get plugin options
        $options = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                'custom_page_builder_%'
            ),
            ARRAY_A
        );
        
        foreach ($options as $option) {
            $backup_data['options'][$option['option_name']] = $option['option_value'];
        }
        
        // Save backup to uploads directory
        $upload_dir = WordPress_Helper::safe_wp_upload_dir();
        $backup_file = $upload_dir['basedir'] . '/custom-page-builder-backup-' . date('Y-m-d-H-i-s') . '.json';
        
        file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
        
        // Log backup creation
        error_log('Custom Page Builder: Backup created at ' . $backup_file);
    }
}