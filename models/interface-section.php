<?php
/**
 * Section interface
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Models;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for all section types
 */
interface SectionInterface {
    
    /**
     * Get section type identifier
     *
     * @return string
     */
    public function get_type(): string;
    
    /**
     * Get configuration schema for the section
     *
     * @return array
     */
    public function get_config_schema(): array;
    
    /**
     * Validate section configuration
     *
     * @param array $config Configuration data
     * @return bool
     */
    public function validate_config(array $config): bool;
    
    /**
     * Render admin template for section configuration
     *
     * @return string
     */
    public function render_admin_template(): string;
    
    /**
     * Convert section to API response format
     *
     * @return array
     */
    public function to_api_response(): array;
    
    /**
     * Get default configuration for the section
     *
     * @return array
     */
    public function get_default_config(): array;
}