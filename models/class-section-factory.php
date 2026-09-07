<?php
/**
 * Section factory class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Models;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include the interface
require_once __DIR__ . '/interface-section.php';

/**
 * Factory class for creating different section types
 */
class SectionFactory {
    
    /**
     * Registered section types
     *
     * @var array
     */
    private static $section_types = [];
    
    /**
     * Register a section type
     *
     * @param string $type Section type identifier
     * @param string $class_name Section class name
     */
    public static function register_section_type(string $type, string $class_name) {
        self::$section_types[$type] = $class_name;
    }
    
    /**
     * Create a section instance
     *
     * @param string $type Section type
     * @param array $data Section data
     * @return SectionInterface|null
     */
    public static function create_section(string $type, array $data = []) {
        // Ensure section_type is set in data
        $data['section_type'] = $type;
        
        // Check if section type is registered
        if (!isset(self::$section_types[$type])) {
            // Fall back to base Section class
            return new Section($data);
        }
        
        $class_name = self::$section_types[$type];
        
        // Check if class exists
        if (!class_exists($class_name)) {
            error_log("Custom Page Builder: Section class {$class_name} not found for type {$type}");
            return new Section($data);
        }
        
        // Check if class implements SectionInterface
        if (!in_array(SectionInterface::class, class_implements($class_name))) {
            error_log("Custom Page Builder: Section class {$class_name} must implement SectionInterface");
            return new Section($data);
        }
        
        return new $class_name($data);
    }
    
    /**
     * Create section from database data
     *
     * @param array $database_data Database row data
     * @return SectionInterface|null
     */
    public static function create_from_database(array $database_data) {
        $type = $database_data['section_type'] ?? 'base';
        return self::create_section($type, $database_data);
    }
    
    /**
     * Get all registered section types
     *
     * @return array
     */
    public static function get_registered_types() {
        return array_keys(self::$section_types);
    }
    
    /**
     * Check if a section type is registered
     *
     * @param string $type Section type
     * @return bool
     */
    public static function is_type_registered(string $type) {
        return isset(self::$section_types[$type]);
    }
    
    /**
     * Get section type configuration schema
     *
     * @param string $type Section type
     * @return array|null
     */
    public static function get_type_schema(string $type) {
        $section = self::create_section($type);
        return $section ? $section->get_config_schema() : null;
    }
    
    /**
     * Get default configuration for a section type
     *
     * @param string $type Section type
     * @return array
     */
    public static function get_default_config(string $type) {
        $section = self::create_section($type);
        return $section ? $section->get_default_config() : [];
    }
    
    /**
     * Validate configuration for a section type
     *
     * @param string $type Section type
     * @param array $config Configuration data
     * @return bool
     */
    public static function validate_config(string $type, array $config) {
        $section = self::create_section($type, ['config' => $config]);
        return $section ? $section->validate_config($config) : false;
    }
    
    /**
     * Get available section types with metadata
     *
     * @return array
     */
    public static function get_available_types() {
        $types = [];
        
        foreach (self::$section_types as $type => $class_name) {
            $section = self::create_section($type);
            if ($section) {
                $types[$type] = [
                    'type' => $type,
                    'class' => $class_name,
                    'schema' => $section->get_config_schema(),
                    'default_config' => $section->get_default_config()
                ];
            }
        }
        
        return $types;
    }
    
    /**
     * Initialize default section types
     */
    public static function init_default_types() {
        // Register built-in section types
        self::register_section_type('testimonials', 'Custom_Page_Builder\\Models\\TestimonialsSection');
        self::register_section_type('product_grid', 'Custom_Page_Builder\\Models\\ProductGridSection');
        self::register_section_type('hero_banner', 'Custom_Page_Builder\\Models\\HeroBannerSection');
        self::register_section_type('category_showcase', 'Custom_Page_Builder\\Models\\CategoryShowcaseSection');
        self::register_section_type('content_block', 'Custom_Page_Builder\\Models\\ContentBlockSection');
        
        // Allow plugins to register additional section types
        do_action('cpb_register_section_types');
    }
    
    /**
     * Unregister a section type
     *
     * @param string $type Section type to unregister
     */
    public static function unregister_section_type(string $type) {
        unset(self::$section_types[$type]);
    }
    
    /**
     * Clear all registered section types
     */
    public static function clear_all_types() {
        self::$section_types = [];
    }
}