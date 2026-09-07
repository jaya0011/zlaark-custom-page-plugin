<?php
/**
 * Plugin installer class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

use Custom_Page_Builder\WordPress_Helper;

use Custom_Page_Builder\Database_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Installer class for database table creation and default options
 */
class Installer {
    
    /**
     * Database version option name
     */
    const DB_VERSION_OPTION = 'custom_page_builder_db_version';
    
    /**
     * Current database version
     */
    const DB_VERSION = '1.2.0';
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Ensure WordPress is fully loaded
        if (!WordPress_Helper::is_wordpress_loaded()) {
            // Schedule activation for when WordPress is ready
            WordPress_Helper::safe_add_action('init', [$this, 'delayed_activation']);
            return;
        }
        
        $this->perform_activation();
    }
    
    /**
     * Perform the actual activation tasks
     */
    public function perform_activation() {
        try {
            // Create database tables
            $this->create_tables();
            
            // Set default options
            $this->set_default_options();
            
            // Create upload directories
            $this->create_upload_directories();
            
            // Set database version
            WordPress_Helper::safe_update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
            
            // Schedule cron events
            $this->schedule_cron_events();
            
            // Flush rewrite rules safely
            if (function_exists('flush_rewrite_rules')) {
                flush_rewrite_rules();
            }
        } catch (\Exception $e) {
            // Log activation error
            if (function_exists('error_log')) {
                error_log('Custom Page Builder activation error: ' . $e->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * Delayed activation when WordPress is fully loaded
     */
    public function delayed_activation() {
        // Remove the hook to prevent multiple executions
        if (function_exists('remove_action')) {
            remove_action('init', [$this, 'delayed_activation']);
        }
        
        // Perform activation
        $this->perform_activation();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('cpb_cleanup_temp_files');
        wp_clear_scheduled_hook('cpb_check_scheduled_pages');
        wp_clear_scheduled_hook('cpb_update_page_status');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        try {
            // Ensure database is available
            $wpdb = Database_Helper::get_wpdb();
            $charset_collate = Database_Helper::get_charset_collate()['charset_collate'];
        } catch (Database_Exception $e) {
            throw new \Exception('Database not available for table creation: ' . $e->getMessage());
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Custom pages table
        $pages_table = $wpdb->prefix . 'custom_pages';
        $pages_sql = "CREATE TABLE $pages_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            status enum('draft', 'published', 'archived', 'scheduled') DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            published_at datetime NULL,
            scheduled_at datetime NULL,
            author_id bigint(20) NOT NULL,
            meta_data longtext,
            PRIMARY KEY (id),
            UNIQUE KEY idx_slug (slug),
            KEY idx_status (status),
            KEY idx_author (author_id),
            KEY idx_published_at (published_at),
            KEY idx_scheduled_at (scheduled_at),
            KEY idx_status_published (status, published_at),
            KEY idx_status_scheduled (status, scheduled_at)
        ) $charset_collate;";
        
        // Page sections table
        $sections_table = $wpdb->prefix . 'custom_page_sections';
        $sections_sql = "CREATE TABLE $sections_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            page_id bigint(20) NOT NULL,
            section_type varchar(50) NOT NULL,
            section_order int(11) NOT NULL DEFAULT 0,
            config longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_page_id (page_id),
            KEY idx_page_order (page_id, section_order),
            KEY idx_section_type (section_type),
            FOREIGN KEY (page_id) REFERENCES $pages_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Page revisions table
        $revisions_table = $wpdb->prefix . 'custom_page_revisions';
        $revisions_sql = "CREATE TABLE $revisions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            page_id bigint(20) NOT NULL,
            revision_data longtext NOT NULL,
            revision_note varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) NOT NULL,
            is_autosave tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_page_id (page_id),
            KEY idx_created_at (created_at),
            KEY idx_created_by (created_by),
            KEY idx_is_autosave (is_autosave),
            FOREIGN KEY (page_id) REFERENCES $pages_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Include WordPress database upgrade functions
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Create tables
        dbDelta($pages_sql);
        dbDelta($sections_sql);
        dbDelta($revisions_sql);
        
        // Check for database errors
        if (!empty($wpdb->last_error)) {
            error_log('Custom Page Builder: Database table creation error - ' . $wpdb->last_error);
        }
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        // Ensure WordPress functions are available
        if (!function_exists('get_option') || !function_exists('add_option')) {
            return;
        }
        $default_options = [
            'custom_page_builder_settings' => [
                'enable_secure_images' => true,
                'default_page_status' => 'draft',
                'enable_page_revisions' => true,
                'max_revisions' => 10,
                'enable_auto_save' => true,
                'auto_save_interval' => 30, // seconds
                'api_cache_duration' => 3600, // 1 hour
                'allowed_section_types' => [
                    'testimonials',
                    'product_grid',
                    'hero_banner',
                    'category_showcase',
                    'content_block'
                ],
                'max_sections_per_page' => 50,
                'enable_page_scheduling' => true,
            ]
        ];
        
        foreach ($default_options as $option_name => $option_value) {
            if (!get_option($option_name)) {
                add_option($option_name, $option_value);
            }
        }
        
        // Set plugin activation timestamp
        if (!get_option('custom_page_builder_activated_at')) {
            add_option('custom_page_builder_activated_at', current_time('timestamp'));
        }
    }
    
    /**
     * Create upload directories
     */
    private function create_upload_directories() {
        // Ensure WordPress functions are available
        if (!function_exists('wp_upload_dir') || !function_exists('wp_mkdir_p')) {
            return;
        }
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/custom-page-builder';
        
        // Create main plugin directory
        if (!file_exists($plugin_upload_dir)) {
            wp_mkdir_p($plugin_upload_dir);
        }
        
        // Create subdirectories
        $subdirectories = [
            'temp',
            'cache',
            'exports'
        ];
        
        foreach ($subdirectories as $subdir) {
            $subdir_path = $plugin_upload_dir . '/' . $subdir;
            if (!file_exists($subdir_path)) {
                wp_mkdir_p($subdir_path);
            }
            
            // Add .htaccess for security
            $htaccess_file = $subdir_path . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                file_put_contents($htaccess_file, "deny from all\n");
            }
        }
        
        // Add index.php files to prevent directory browsing
        $index_content = "<?php\n// Silence is golden.\n";
        $directories_to_protect = array_merge([$plugin_upload_dir], array_map(function($subdir) use ($plugin_upload_dir) {
            return $plugin_upload_dir . '/' . $subdir;
        }, $subdirectories));
        
        foreach ($directories_to_protect as $dir) {
            $index_file = $dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, $index_content);
            }
        }
    }
    
    /**
     * Check if database needs updating
     *
     * @return bool
     */
    public function needs_database_update() {
        $installed_version = get_option(self::DB_VERSION_OPTION, '0.0.0');
        return version_compare($installed_version, self::DB_VERSION, '<');
    }
    
    /**
     * Update database if needed
     */
    public function maybe_update_database() {
        if ($this->needs_database_update()) {
            $this->create_tables();
            update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
        }
    }
    
    /**
     * Schedule cron events
     */
    private function schedule_cron_events() {
        // Ensure WordPress functions are available
        if (!function_exists('wp_next_scheduled') || !function_exists('wp_schedule_event')) {
            return;
        }
        // Schedule page publication check every 5 minutes
        if (!wp_next_scheduled('cpb_check_scheduled_pages')) {
            wp_schedule_event(time(), 'custom_page_builder_5min', 'cpb_check_scheduled_pages');
        }
        
        // Schedule cleanup of temp files daily
        if (!wp_next_scheduled('cpb_cleanup_temp_files')) {
            wp_schedule_event(time(), 'daily', 'cpb_cleanup_temp_files');
        }
    }
    
    /**
     * Get database table names
     *
     * @return array
     */
    public static function get_table_names() {
        // Database access handled by Database_Helper
        
        return [
            'pages' => $wpdb->prefix . 'custom_pages',
            'sections' => $wpdb->prefix . 'custom_page_sections',
            'revisions' => $wpdb->prefix . 'custom_page_revisions'
        ];
    }
}