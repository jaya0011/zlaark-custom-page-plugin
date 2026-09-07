<?php
/**
 * WordPress Function Availability Checker
 * 
 * Provides safe wrappers for WordPress functions with availability checks
 * and fallback mechanisms.
 *
 * @package Custom_Page_Builder
 * @since 1.0.0
 */

namespace Custom_Page_Builder;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress Function Checker Class
 * 
 * Handles WordPress function availability checks and provides safe wrappers
 * with fallback mechanisms for missing functions.
 */
class WordPress_Function_Checker {
    
    /**
     * Cache for function availability checks
     * @var array
     */
    private static $function_cache = [];
    
    /**
     * List of critical WordPress functions
     * @var array
     */
    private static $critical_functions = [
        'add_action',
        'add_filter',
        'remove_action',
        'remove_filter',
        'do_action',
        'apply_filters',
        'wp_die',
        'is_admin',
        'current_user_can',
        'get_option',
        'update_option',
        'delete_option'
    ];
    
    /**
     * List of admin-specific functions
     * @var array
     */
    private static $admin_functions = [
        'add_menu_page',
        'add_submenu_page',
        'admin_url',
        'wp_enqueue_script',
        'wp_enqueue_style',
        'wp_nonce_field',
        'wp_verify_nonce',
        'wp_redirect',
        'wp_safe_redirect'
    ];
    
    /**
     * List of database functions
     * @var array
     */
    private static $database_functions = [
        'wp_insert_post',
        'wp_update_post',
        'wp_delete_post',
        'get_posts',
        'get_post',
        'wp_query'
    ];
    
    /**
     * Check if a WordPress function exists and is available
     *
     * @param string $function_name The function name to check
     * @return bool True if function exists and is available
     */
    public static function is_function_available($function_name) {
        // Check cache first
        if (isset(self::$function_cache[$function_name])) {
            return self::$function_cache[$function_name];
        }
        
        // Check if function exists
        $available = function_exists($function_name);
        
        // Cache the result
        self::$function_cache[$function_name] = $available;
        
        return $available;
    }
    
    /**
     * Check if WordPress is fully loaded
     *
     * @return bool True if WordPress is fully loaded
     */
    public static function is_wordpress_loaded() {
        return defined('ABSPATH') && 
               function_exists('add_action') && 
               function_exists('apply_filters') &&
               function_exists('do_action');
    }
    
    /**
     * Check if WordPress admin is available
     *
     * @return bool True if admin functions are available
     */
    public static function is_admin_available() {
        return self::is_function_available('is_admin') &&
               self::is_function_available('current_user_can') &&
               self::is_function_available('admin_url');
    }
    
    /**
     * Check if database functions are available
     *
     * @return bool True if database functions are available
     */
    public static function is_database_available() {
        global $wpdb;
        
        return isset($wpdb) && 
               is_object($wpdb) &&
               self::is_function_available('get_option') &&
               self::is_function_available('update_option');
    }
    
    /**
     * Safe wrapper for WordPress function calls
     *
     * @param string $function_name The function to call
     * @param array $args Arguments to pass to the function
     * @param mixed $fallback Fallback value if function is not available
     * @return mixed Function result or fallback value
     */
    public static function safe_call($function_name, $args = [], $fallback = null) {
        if (!self::is_function_available($function_name)) {
            if (function_exists('cpb_log_error')) {
                cpb_log_error("WordPress function not available: $function_name", 'WordPress Function Check');
            }
            return $fallback;
        }
        
        try {
            return call_user_func_array($function_name, $args);
        } catch (\Exception $e) {
            if (function_exists('cpb_log_error')) {
                cpb_log_error("Error calling WordPress function $function_name: " . $e->getMessage(), 'WordPress Function Check');
            }
            return $fallback;
        } catch (\Error $e) {
            if (function_exists('cpb_log_error')) {
                cpb_log_error("Fatal error calling WordPress function $function_name: " . $e->getMessage(), 'WordPress Function Check');
            }
            return $fallback;
        }
    }
    
    /**
     * Safe add_action wrapper
     *
     * @param string $hook The hook name
     * @param callable $callback The callback function
     * @param int $priority Priority (default: 10)
     * @param int $accepted_args Number of accepted arguments (default: 1)
     * @return bool True if action was added successfully
     */
    public static function safe_add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        if (!self::is_function_available('add_action')) {
            return false;
        }
        
        try {
            add_action($hook, $callback, $priority, $accepted_args);
            return true;
        } catch (\Exception $e) {
            if (function_exists('cpb_log_error')) {
                cpb_log_error("Error adding action $hook: " . $e->getMessage(), 'WordPress Function Check');
            }
            return false;
        }
    }
    
    /**
     * Safe add_filter wrapper
     *
     * @param string $hook The hook name
     * @param callable $callback The callback function
     * @param int $priority Priority (default: 10)
     * @param int $accepted_args Number of accepted arguments (default: 1)
     * @return bool True if filter was added successfully
     */
    public static function safe_add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        if (!self::is_function_available('add_filter')) {
            return false;
        }
        
        try {
            add_filter($hook, $callback, $priority, $accepted_args);
            return true;
        } catch (\Exception $e) {
            if (function_exists('cpb_log_error')) {
                cpb_log_error("Error adding filter $hook: " . $e->getMessage(), 'WordPress Function Check');
            }
            return false;
        }
    }
    
    /**
     * Safe get_option wrapper
     *
     * @param string $option Option name
     * @param mixed $default Default value
     * @return mixed Option value or default
     */
    public static function safe_get_option($option, $default = false) {
        if (!self::is_function_available('get_option')) {
            return $default;
        }
        
        try {
            return get_option($option, $default);
        } catch (\Exception $e) {
            if (function_exists('cpb_log_error')) {
                cpb_log_error("Error getting option $option: " . $e->getMessage(), 'WordPress Function Check');
            }
            return $default;
        }
    }
    
    /**
     * Safe update_option wrapper
     *
     * @param string $option Option name
     * @param mixed $value Option value
     * @param string|bool $autoload Whether to autoload the option
     * @return bool True if option was updated successfully
     */
    public static function safe_update_option($option, $value, $autoload = null) {
        if (!self::is_function_available('update_option')) {
            return false;
        }
        
        try {
            return update_option($option, $value, $autoload);
        } catch (\Exception $e) {
            if (function_exists('cpb_log_error')) {
                cpb_log_error("Error updating option $option: " . $e->getMessage(), 'WordPress Function Check');
            }
            return false;
        }
    }
    
    /**
     * Safe delete_option wrapper
     *
     * @param string $option Option name
     * @return bool True if option was deleted successfully
     */
    public static function safe_delete_option($option) {
        if (!self::is_function_available('delete_option')) {
            return false;
        }
        
        try {
            return delete_option($option);
        } catch (\Exception $e) {
            if (function_exists('cpb_log_error')) {
                cpb_log_error("Error deleting option $option: " . $e->getMessage(), 'WordPress Function Check');
            }
            return false;
        }
    }
    
    /**
     * Safe current_user_can wrapper
     *
     * @param string $capability Capability to check
     * @param mixed ...$args Additional arguments
     * @return bool True if user has capability
     */
    public static function safe_current_user_can($capability, ...$args) {
        if (!self::is_function_available('current_user_can')) {
            return false; // Fail safe - no permissions if function unavailable
        }
        
        try {
            return current_user_can($capability, ...$args);
        } catch (\Exception $e) {
            if (function_exists('cpb_log_error')) {
                cpb_log_error("Error checking capability $capability: " . $e->getMessage(), 'WordPress Function Check');
            }
            return false;
        }
    }
    
    /**
     * Safe is_admin wrapper
     *
     * @return bool True if in admin area
     */
    public static function safe_is_admin() {
        if (!self::is_function_available('is_admin')) {
            // Fallback: check if we're in admin based on script name
            return isset($_SERVER['SCRIPT_NAME']) && 
                   strpos($_SERVER['SCRIPT_NAME'], '/wp-admin/') !== false;
        }
        
        try {
            return is_admin();
        } catch (\Exception $e) {
            if (function_exists('cpb_log_error')) {
                cpb_log_error("Error checking is_admin: " . $e->getMessage(), 'WordPress Function Check');
            }
            return false;
        }
    }
    
    /**
     * Safe wp_die wrapper
     *
     * @param string $message Error message
     * @param string $title Error title
     * @param array $args Additional arguments
     */
    public static function safe_wp_die($message, $title = '', $args = []) {
        if (!self::is_function_available('wp_die')) {
            // Fallback: use PHP die with HTML formatting
            if (headers_sent()) {
                echo '<div style="background: #fff; border: 1px solid #ccc; padding: 20px; margin: 20px; font-family: Arial, sans-serif;">';
                echo '<h2>' . esc_html($title ?: 'Error') . '</h2>';
                echo '<p>' . esc_html($message) . '</p>';
                echo '</div>';
            } else {
                header('HTTP/1.1 500 Internal Server Error');
                echo '<!DOCTYPE html><html><head><title>' . esc_html($title ?: 'Error') . '</title></head><body>';
                echo '<h1>' . esc_html($title ?: 'Error') . '</h1>';
                echo '<p>' . esc_html($message) . '</p>';
                echo '</body></html>';
            }
            exit;
        }
        
        try {
            wp_die($message, $title, $args);
        } catch (\Exception $e) {
            // Final fallback
            die('Fatal Error: ' . esc_html($message));
        }
    }
    
    /**
     * Safe wp_enqueue_script wrapper
     *
     * @param string $handle Script handle
     * @param string $src Script source URL
     * @param array $deps Dependencies
     * @param string|bool $ver Version
     * @param bool $in_footer Whether to enqueue in footer
     * @return bool True if script was enqueued successfully
     */
    public static function safe_wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
        if (!self::is_function_available('wp_enqueue_script')) {
            return false;
        }
        
        try {
            wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
            return true;
        } catch (\Exception $e) {
            if (function_exists('cpb_log_error')) {
                cpb_log_error("Error enqueuing script $handle: " . $e->getMessage(), 'WordPress Function Check');
            }
            return false;
        }
    }
    
    /**
     * Safe wp_enqueue_style wrapper
     *
     * @param string $handle Style handle
     * @param string $src Style source URL
     * @param array $deps Dependencies
     * @param string|bool $ver Version
     * @param string $media Media type
     * @return bool True if style was enqueued successfully
     */
    public static function safe_wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all') {
        if (!self::is_function_available('wp_enqueue_style')) {
            return false;
        }
        
        try {
            wp_enqueue_style($handle, $src, $deps, $ver, $media);
            return true;
        } catch (\Exception $e) {
            if (function_exists('cpb_log_error')) {
                cpb_log_error("Error enqueuing style $handle: " . $e->getMessage(), 'WordPress Function Check');
            }
            return false;
        }
    }
    
    /**
     * Get missing critical functions
     *
     * @return array List of missing critical functions
     */
    public static function get_missing_critical_functions() {
        $missing = [];
        
        foreach (self::$critical_functions as $function) {
            if (!self::is_function_available($function)) {
                $missing[] = $function;
            }
        }
        
        return $missing;
    }
    
    /**
     * Get missing admin functions
     *
     * @return array List of missing admin functions
     */
    public static function get_missing_admin_functions() {
        $missing = [];
        
        foreach (self::$admin_functions as $function) {
            if (!self::is_function_available($function)) {
                $missing[] = $function;
            }
        }
        
        return $missing;
    }
    
    /**
     * Validate WordPress environment
     *
     * @return array Validation results
     */
    public static function validate_environment() {
        return [
            'wordpress_loaded' => self::is_wordpress_loaded(),
            'admin_available' => self::is_admin_available(),
            'database_available' => self::is_database_available(),
            'missing_critical' => self::get_missing_critical_functions(),
            'missing_admin' => self::get_missing_admin_functions(),
            'can_continue' => empty(self::get_missing_critical_functions())
        ];
    }
    
    /**
     * Clear function cache
     */
    public static function clear_cache() {
        self::$function_cache = [];
    }
}