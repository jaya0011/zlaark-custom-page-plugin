<?php
/**
 * Autoloader for Custom Page Builder plugin classes
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Autoloader class for plugin classes
 */
class Autoloader {
    
    /**
     * Register the autoloader
     */
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    /**
     * Convert CamelCase to kebab-case
     *
     * @param string $input CamelCase string
     * @return string kebab-case string
     */
    private static function camel_to_kebab($input) {
        // Handle underscores first
        $input = str_replace('_', '-', $input);
        
        // Convert CamelCase to kebab-case
        $result = preg_replace('/([a-z])([A-Z])/', '$1-$2', $input);
        
        return strtolower($result);
    }
    
    /**
     * Autoload plugin classes
     *
     * @param string $class_name The class name to load
     */
    public static function autoload($class_name) {
        // Check if this is a Custom_Page_Builder class
        if (strpos($class_name, 'Custom_Page_Builder\\') !== 0) {
            return;
        }
        
        // Remove namespace prefix
        $class_name = str_replace('Custom_Page_Builder\\', '', $class_name);
        
        // Handle exceptions namespace
        if (strpos($class_name, 'Exceptions\\') === 0) {
            $exception_class = str_replace('Exceptions\\', '', $class_name);
            $file_name = 'class-' . self::camel_to_kebab($exception_class) . '.php';
            $file_path = CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'includes/exceptions/' . $file_name;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }
        
        // Handle Admin namespace
        if (strpos($class_name, 'Admin\\') === 0) {
            $admin_class = str_replace('Admin\\', '', $class_name);
            $file_name = 'class-' . self::camel_to_kebab($admin_class) . '.php';
            $file_path = CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'admin/' . $file_name;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }
        
        // Handle Models namespace
        if (strpos($class_name, 'Models\\') === 0) {
            $model_class = str_replace('Models\\', '', $class_name);
            $file_name = 'class-' . self::camel_to_kebab($model_class) . '.php';
            $file_path = CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'models/' . $file_name;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }
        
        // Convert class name to file name for root namespace classes
        $file_name = 'class-' . self::camel_to_kebab($class_name) . '.php';
        
        // Define possible directories to search
        $directories = [
            CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'includes/',
            CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'admin/',
            CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'api/',
            CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'models/',
            CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'integrations/',
        ];
        
        // Search for the file in each directory
        foreach ($directories as $directory) {
            $file_path = $directory . $file_name;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }
    }
}

// Register the autoloader
Autoloader::register();