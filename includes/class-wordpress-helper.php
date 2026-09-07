<?php
/**
 * WordPress Helper class for safe WordPress function calls
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress Helper class that provides safe WordPress function access
 */
class WordPress_Helper {
    
    /**
     * Safe get_option call
     *
     * @param string $option Option name
     * @param mixed $default Default value
     * @return mixed Option value or default
     */
    public static function safe_get_option(string $option, $default = false) {
        if (!function_exists('get_option')) {
            return $default;
        }
        
        return get_option($option, $default);
    }
    
    /**
     * Safe update_option call
     *
     * @param string $option Option name
     * @param mixed $value Option value
     * @return bool Success status
     */
    public static function safe_update_option(string $option, $value): bool {
        if (!function_exists('update_option')) {
            return false;
        }
        
        return update_option($option, $value);
    }
    
    /**
     * Safe delete_option call
     *
     * @param string $option Option name
     * @return bool Success status
     */
    public static function safe_delete_option(string $option): bool {
        if (!function_exists('delete_option')) {
            return false;
        }
        
        return delete_option($option);
    }
    
    /**
     * Safe add_option call
     *
     * @param string $option Option name
     * @param mixed $value Option value
     * @param string $deprecated Deprecated parameter
     * @param string $autoload Autoload setting
     * @return bool Success status
     */
    public static function safe_add_option(string $option, $value = '', string $deprecated = '', string $autoload = 'yes'): bool {
        if (!function_exists('add_option')) {
            return false;
        }
        
        return add_option($option, $value, $deprecated, $autoload);
    }
    
    /**
     * Safe wp_upload_dir call
     *
     * @param string $time Optional time
     * @param bool $create_dir Whether to create directory
     * @param bool $refresh_cache Whether to refresh cache
     * @return array|false Upload directory info or false on failure
     */
    public static function safe_wp_upload_dir(?string $time = null, bool $create_dir = true, bool $refresh_cache = false) {
        if (!function_exists('wp_upload_dir')) {
            return false;
        }
        
        return wp_upload_dir($time, $create_dir, $refresh_cache);
    }
    
    /**
     * Safe add_action call
     *
     * @param string $hook_name Hook name
     * @param callable $callback Callback function
     * @param int $priority Priority
     * @param int $accepted_args Number of accepted arguments
     * @return bool Success status
     */
    public static function safe_add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool {
        if (!function_exists('add_action')) {
            return false;
        }
        
        add_action($hook_name, $callback, $priority, $accepted_args);
        return true;
    }
    
    /**
     * Safe add_filter call
     *
     * @param string $hook_name Hook name
     * @param callable $callback Callback function
     * @param int $priority Priority
     * @param int $accepted_args Number of accepted arguments
     * @return bool Success status
     */
    public static function safe_add_filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool {
        if (!function_exists('add_filter')) {
            return false;
        }
        
        add_filter($hook_name, $callback, $priority, $accepted_args);
        return true;
    }
    
    /**
     * Safe current_time call
     *
     * @param string $type Type of time
     * @param int|bool $gmt GMT offset
     * @return string|int Current time
     */
    public static function safe_current_time(string $type, $gmt = 0) {
        if (!function_exists('current_time')) {
            // Fallback to PHP time functions
            if ($type === 'mysql') {
                return date('Y-m-d H:i:s');
            }
            return time();
        }
        
        return current_time($type, $gmt);
    }
    
    /**
     * Safe get_current_user_id call
     *
     * @return int User ID or 0
     */
    public static function safe_get_current_user_id(): int {
        if (!function_exists('get_current_user_id')) {
            return 0;
        }
        
        return get_current_user_id();
    }
    
    /**
     * Safe sanitize_text_field call
     *
     * @param string $str String to sanitize
     * @return string Sanitized string
     */
    public static function safe_sanitize_text_field(string $str): string {
        if (!function_exists('sanitize_text_field')) {
            // Basic sanitization fallback
            return strip_tags(trim($str));
        }
        
        return sanitize_text_field($str);
    }
    
    /**
     * Safe sanitize_title call
     *
     * @param string $title Title to sanitize
     * @param string $fallback_title Fallback title
     * @param string $context Context
     * @return string Sanitized title
     */
    public static function safe_sanitize_title(string $title, string $fallback_title = '', string $context = 'save'): string {
        if (!function_exists('sanitize_title')) {
            // Basic sanitization fallback
            return strtolower(preg_replace('/[^a-zA-Z0-9-_]/', '-', trim($title)));
        }
        
        return sanitize_title($title, $fallback_title, $context);
    }
    
    /**
     * Safe wp_verify_nonce call
     *
     * @param string $nonce Nonce to verify
     * @param string $action Action name
     * @return bool|int Verification result
     */
    public static function safe_wp_verify_nonce(string $nonce, string $action) {
        if (!function_exists('wp_verify_nonce')) {
            return false;
        }
        
        return wp_verify_nonce($nonce, $action);
    }
    
    /**
     * Safe is_admin call
     *
     * @return bool True if in admin area
     */
    public static function safe_is_admin(): bool {
        if (!function_exists('is_admin')) {
            return false;
        }
        
        return is_admin();
    }
    
    /**
     * Safe current_user_can call
     *
     * @param string $capability Capability to check
     * @param mixed ...$args Additional arguments
     * @return bool True if user has capability
     */
    public static function safe_current_user_can(string $capability, ...$args): bool {
        if (!function_exists('current_user_can')) {
            return false;
        }
        
        return current_user_can($capability, ...$args);
    }
    
    /**
     * Safe do_action call
     *
     * @param string $hook_name Hook name
     * @param mixed ...$args Arguments to pass
     * @return bool Success status
     */
    public static function safe_do_action(string $hook_name, ...$args): bool {
        if (!function_exists('do_action')) {
            return false;
        }
        
        do_action($hook_name, ...$args);
        return true;
    }
    
    /**
     * Safe apply_filters call
     *
     * @param string $hook_name Hook name
     * @param mixed $value Value to filter
     * @param mixed ...$args Additional arguments
     * @return mixed Filtered value
     */
    public static function safe_apply_filters(string $hook_name, $value, ...$args) {
        if (!function_exists('apply_filters')) {
            return $value;
        }
        
        return apply_filters($hook_name, $value, ...$args);
    }
    
    /**
     * Check if WordPress is fully loaded
     *
     * @return bool True if WordPress is ready
     */
    public static function is_wordpress_loaded(): bool {
        return function_exists('add_action') && 
               function_exists('get_option') && 
               function_exists('is_admin') && 
               defined('ABSPATH');
    }
    
    /**
     * Check if we're in WordPress admin area safely
     *
     * @return bool True if in admin area
     */
    public static function is_admin_area(): bool {
        return self::is_wordpress_loaded() && self::safe_is_admin();
    }
}