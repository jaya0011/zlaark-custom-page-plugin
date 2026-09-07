<?php
/**
 * Base Section model class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Models;

use DateTime;
use Custom_Page_Builder\Includes\Installer;
use Custom_Page_Builder\Secure_Image_Handler;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include the interface
require_once __DIR__ . '/interface-section.php';

/**
 * Base Section class for all section types
 */
class Section implements SectionInterface {
    
    /**
     * Section ID
     *
     * @var int|null
     */
    protected $id;
    
    /**
     * Page ID this section belongs to
     *
     * @var int
     */
    protected $page_id;
    
    /**
     * Section type
     *
     * @var string
     */
    protected $section_type;
    
    /**
     * Section order within the page
     *
     * @var int
     */
    protected $section_order;
    
    /**
     * Section configuration
     *
     * @var array
     */
    protected $config;
    
    /**
     * Created timestamp
     *
     * @var DateTime
     */
    protected $created_at;
    
    /**
     * Updated timestamp
     *
     * @var DateTime
     */
    protected $updated_at;
    
    /**
     * Secure image handler instance
     *
     * @var Secure_Image_Handler|null
     */
    protected $secure_image_handler;
    
    /**
     * Constructor
     *
     * @param array $data Section data
     */
    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->page_id = $data['page_id'] ?? 0;
        $this->section_type = $data['section_type'] ?? $this->get_type();
        $this->section_order = $data['section_order'] ?? 0;
        $this->config = $data['config'] ?? $this->get_default_config();
        $this->created_at = isset($data['created_at']) ? new DateTime($data['created_at']) : new DateTime();
        $this->updated_at = isset($data['updated_at']) ? new DateTime($data['updated_at']) : new DateTime();
        
        // Ensure config is an array
        if (is_string($this->config)) {
            $this->config = json_decode($this->config, true) ?: [];
        }
        
        // Remove any slashes that were added during sanitization to prevent double-escaping
        if (is_array($this->config)) {
            $this->config = $this->unslash_array_recursively($this->config);
        }
        
        // Initialize secure image handler
        $this->secure_image_handler = new Secure_Image_Handler();
    }
    
    /**
     * Create a new section
     *
     * @param array $data Section data
     * @return Section|false
     */
    public static function create(array $data) {
        global $wpdb;
        
        $section = new static($data);
        
        // Validate the section
        if (!$section->validate()) {
            return false;
        }
        
        $table_name = Installer::get_table_names()['sections'];
        
        $result = $wpdb->insert(
            $table_name,
            [
                'page_id' => $section->page_id,
                'section_type' => $section->section_type,
                'section_order' => $section->section_order,
                'config' => json_encode($section->config)
            ],
            ['%d', '%s', '%d', '%s']
        );
        
        if ($result === false) {
            return false;
        }
        
        $section->id = $wpdb->insert_id;
        $section->created_at = new DateTime();
        $section->updated_at = new DateTime();
        
        return $section;
    }
    
    /**
     * Find a section by ID
     *
     * @param int $id Section ID
     * @return Section|null
     */
    public static function find(int $id) {
        global $wpdb;
        
        $table_name = Installer::get_table_names()['sections'];
        
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $id),
            ARRAY_A
        );
        
        if (!$row) {
            return null;
        }
        
        return self::from_database($row);
    }
    
    /**
     * Get sections by page ID
     *
     * @param int $page_id Page ID
     * @return array
     */
    public static function get_by_page_id(int $page_id) {
        global $wpdb;
        
        $table_name = Installer::get_table_names()['sections'];
        
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE page_id = %d ORDER BY section_order ASC",
                $page_id
            ),
            ARRAY_A
        );
        
        $sections = [];
        foreach ($rows as $row) {
            $sections[] = self::from_database($row);
        }
        
        return $sections;
    }
    
    /**
     * Update the section
     *
     * @return bool
     */
    public function save() {
        global $wpdb;
        
        if (!$this->validate()) {
            return false;
        }
        
        $table_name = Installer::get_table_names()['sections'];
        
        $data = [
            'page_id' => $this->page_id,
            'section_type' => $this->section_type,
            'section_order' => $this->section_order,
            'config' => json_encode($this->config),
            'updated_at' => current_time('mysql')
        ];
        
        $result = $wpdb->update(
            $table_name,
            $data,
            ['id' => $this->id],
            ['%d', '%s', '%d', '%s', '%s'],
            ['%d']
        );
        
        if ($result !== false) {
            $this->updated_at = new DateTime();
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete the section
     *
     * @return bool
     */
    public function delete() {
        global $wpdb;
        
        if (!$this->id) {
            return false;
        }
        
        $table_name = Installer::get_table_names()['sections'];
        
        $result = $wpdb->delete(
            $table_name,
            ['id' => $this->id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Duplicate the section
     *
     * @param int $new_page_id New page ID (optional)
     * @return Section|false
     */
    public function duplicate(int $new_page_id = null) {
        $data = $this->to_array();
        unset($data['id']);
        
        if ($new_page_id) {
            $data['page_id'] = $new_page_id;
        }
        
        return static::create($data);
    }
    
    /**
     * Validate section data
     *
     * @return bool
     */
    public function validate() {
        // Page ID is required
        if (!$this->page_id) {
            return false;
        }
        
        // Section type is required
        if (empty($this->section_type)) {
            return false;
        }
        
        // Validate configuration against schema
        return $this->validate_config($this->config);
    }
    
    /**
     * Create instance from database row
     *
     * @param array $data Database row data
     * @return Section
     */
    public static function from_database(array $data) {
        // Use factory to create the appropriate section type
        return SectionFactory::create_from_database($data);
    }
    
    /**
     * Convert to array
     *
     * @return array
     */
    public function to_array() {
        return [
            'id' => $this->id,
            'page_id' => $this->page_id,
            'section_type' => $this->section_type,
            'section_order' => $this->section_order,
            'config' => $this->config,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get section type identifier (default implementation)
     *
     * @return string
     */
    public function get_type(): string {
        return 'base';
    }
    
    /**
     * Get configuration schema (default implementation)
     *
     * @return array
     */
    public function get_config_schema(): array {
        return [
            'title' => [
                'type' => 'string',
                'required' => false,
                'default' => ''
            ],
            'visible' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ]
        ];
    }
    
    /**
     * Validate section configuration (default implementation)
     *
     * @param array $config Configuration data
     * @return bool
     */
    public function validate_config(array $config): bool {
        $schema = $this->get_config_schema();
        
        foreach ($schema as $field => $rules) {
            // Check required fields
            if ($rules['required'] && !isset($config[$field])) {
                return false;
            }
            
            // Type validation
            if (isset($config[$field])) {
                $value = $config[$field];
                
                switch ($rules['type']) {
                    case 'string':
                        if (!is_string($value)) {
                            return false;
                        }
                        break;
                    case 'integer':
                        if (!is_int($value)) {
                            return false;
                        }
                        break;
                    case 'boolean':
                        if (!is_bool($value)) {
                            return false;
                        }
                        break;
                    case 'array':
                        if (!is_array($value)) {
                            return false;
                        }
                        break;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Render admin template (default implementation)
     *
     * @return string
     */
    public function render_admin_template(): string {
        return '<div class="section-config-placeholder">Base section configuration</div>';
    }
    
    /**
     * Convert to API response format (default implementation)
     *
     * @return array
     */
    public function to_api_response(): array {
        $config = $this->config;
        
        // Process images in configuration using secure image handler
        if ($this->secure_image_handler) {
            $this->secure_image_handler->handle_image_in_section($config);
        }
        
        return [
            'id' => $this->id,
            'type' => $this->section_type,
            'order' => $this->section_order,
            'config' => $config
        ];
    }
    
    /**
     * Get secure image handler
     *
     * @return Secure_Image_Handler|null
     */
    protected function get_secure_image_handler() {
        return $this->secure_image_handler;
    }
    
    /**
     * Process image field with secure image handler
     *
     * @param mixed $image_value Image value (URL or attachment ID)
     * @return array Image data with secure URLs
     */
    protected function process_image_field($image_value) {
        if (!$this->secure_image_handler || empty($image_value)) {
            return [
                'url' => is_string($image_value) ? esc_url($image_value) : '',
                'secure_url' => '',
                'fallback_url' => is_string($image_value) ? esc_url($image_value) : '',
                'is_protected' => false
            ];
        }
        
        // If it's a numeric value, treat as attachment ID
        if (is_numeric($image_value)) {
            $attachment_id = intval($image_value);
            $image_data = $this->secure_image_handler->process_uploaded_image($attachment_id);
            
            if ($image_data['success']) {
                return [
                    'url' => $image_data['secure_urls']['full'] ?? $image_data['fallback_urls']['full'] ?? '',
                    'secure_url' => $image_data['secure_urls']['full'] ?? '',
                    'fallback_url' => $image_data['fallback_urls']['full'] ?? '',
                    'is_protected' => $image_data['is_protected'],
                    'sizes' => $image_data['secure_urls'] ?: $image_data['fallback_urls']
                ];
            }
        }
        
        // If it's a URL string, return as-is (fallback)
        if (is_string($image_value)) {
            return [
                'url' => esc_url($image_value),
                'secure_url' => '',
                'fallback_url' => esc_url($image_value),
                'is_protected' => false
            ];
        }
        
        return [
            'url' => '',
            'secure_url' => '',
            'fallback_url' => '',
            'is_protected' => false
        ];
    }
    
    /**
     * Get default configuration (default implementation)
     *
     * @return array
     */
    public function get_default_config(): array {
        $schema = $this->get_config_schema();
        $defaults = [];
        
        foreach ($schema as $field => $rules) {
            if (isset($rules['default'])) {
                $defaults[$field] = $rules['default'];
            }
        }
        
        return $defaults;
    }
    
    // Getters
    public function get_id() { return $this->id; }
    public function get_page_id() { return $this->page_id; }
    public function get_section_type() { return $this->section_type; }
    public function get_section_order() { return $this->section_order; }
    public function get_config() { return $this->config; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }
    
    // Setters
    public function set_page_id(int $page_id) { $this->page_id = $page_id; }
    public function set_section_order(int $order) { $this->section_order = $order; }
    public function set_config(array $config) { $this->config = $config; }
    
    /**
     * Recursively remove slashes from array values
     *
     * @param array $array Array to unslash
     * @return array Unslashed array
     */
    private function unslash_array_recursively(array $array): array {
        $unslashed = [];
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $unslashed[$key] = $this->unslash_array_recursively($value);
            } elseif (is_string($value)) {
                $unslashed[$key] = wp_unslash($value);
            } else {
                $unslashed[$key] = $value;
            }
        }
        
        return $unslashed;
    }
}