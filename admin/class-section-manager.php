<?php
/**
 * Section Manager class
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
 * Section Manager class for section CRUD operations
 */
class Section_Manager {
    
    /**
     * Section factory instance
     *
     * @var Section_Factory
     */
    private $section_factory;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->section_factory = new Section_Factory();
    }
    
    /**
     * Create a new section
     *
     * @param int $page_id Page ID
     * @param string $section_type Section type
     * @param array $config Section configuration
     * @return int Section ID
     * @throws \Exception If section creation fails
     */
    public function create_section(int $page_id, string $section_type, array $config): int {
        try {
            // Ensure database is available
            Database_Helper::get_wpdb();
        } catch (Database_Exception $e) {
            throw new \Exception(__('Database connection not available', 'custom-page-builder'));
        }
        
        // Validate page exists
        $page_builder = new Page_Builder();
        $page = $page_builder->get_page($page_id);
        if (!$page) {
            throw new \Exception(__('Page not found', 'custom-page-builder'));
        }
        
        // Validate section type
        if (!$this->section_factory->is_valid_section_type($section_type)) {
            throw new \Exception(__('Invalid section type', 'custom-page-builder'));
        }
        
        // Create section instance for validation
        $section_instance = $this->section_factory->create_section($section_type, $config);
        if (!$section_instance->validate()) {
            throw new \Exception(__('Invalid section configuration', 'custom-page-builder'));
        }
        
        // Get next section order
        $section_order = $this->get_next_section_order($page_id);
        
        // Prepare data for insertion
        $insert_data = [
            'page_id' => $page_id,
            'section_type' => $section_type,
            'section_order' => $section_order,
            'config' => json_encode($config),
            'created_at' => WordPress_Helper::safe_current_time('mysql'),
            'updated_at' => WordPress_Helper::safe_current_time('mysql')
        ];
        
        // Insert into database using safe method
        try {
            $section_id = Database_Helper::safe_insert('custom_page_sections', $insert_data);
        } catch (Database_Exception $e) {
            throw new \Exception(__('Failed to create section', 'custom-page-builder') . ': ' . $e->getMessage());
        }
        
        // Invalidate page caches
        if (class_exists('Custom_Page_Builder\Cache_Manager')) {
            \Custom_Page_Builder\Cache_Manager::invalidate_page_caches($page_id);
        }
        
        // Fire action hook safely
        WordPress_Helper::safe_do_action('cpb_section_created', $section_id, $page_id, $section_type, $config);
        
        return $section_id;
    }
    
    /**
     * Update an existing section
     *
     * @param int $section_id Section ID
     * @param array $config Section configuration
     * @return bool Success status
     * @throws \Exception If section update fails
     */
    public function update_section(int $section_id, array $config): bool {
        // Database access handled by Database_Helper
        
        // Get existing section
        $existing_section = $this->get_section($section_id);
        if (!$existing_section) {
            throw new \Exception(__('Section not found', 'custom-page-builder'));
        }
        
        // Create section instance for validation
        $section_instance = $this->section_factory->create_section($existing_section->get_section_type(), $config);
        if (!$section_instance->validate()) {
            throw new \Exception(__('Invalid section configuration', 'custom-page-builder'));
        }
        
        // Prepare update data
        $update_data = [
            'config' => json_encode($config),
            'updated_at' => current_time('mysql')
        ];
        
        // Update in database
        $table_name = $wpdb->prefix . 'custom_page_sections';
        $result = $wpdb->update(
            $table_name,
            $update_data,
            ['id' => $section_id],
            null,
            ['%d']
        );
        
        if ($result === false) {
            throw new \Exception(__('Failed to update section', 'custom-page-builder'));
        }
        
        // Invalidate page caches
        if (class_exists('Custom_Page_Builder\Cache_Manager')) {
            \Custom_Page_Builder\Cache_Manager::invalidate_page_caches($existing_section->get_page_id());
        }
        
        // Fire action hook
        do_action('cpb_section_updated', $section_id, $config);
        
        return true;
    }
    
    /**
     * Delete a section
     *
     * @param int $section_id Section ID
     * @return bool Success status
     * @throws \Exception If section deletion fails
     */
    public function delete_section(int $section_id): bool {
        // Database access handled by Database_Helper
        
        // Get existing section
        $existing_section = $this->get_section($section_id);
        if (!$existing_section) {
            throw new \Exception(__('Section not found', 'custom-page-builder'));
        }
        
        $page_id = $existing_section->get_page_id();
        
        // Fire action hook before deletion
        do_action('cpb_before_section_deleted', $section_id, $page_id);
        
        // Delete section
        $table_name = $wpdb->prefix . 'custom_page_sections';
        $result = $wpdb->delete($table_name, ['id' => $section_id], ['%d']);
        
        if ($result === false) {
            throw new \Exception(__('Failed to delete section', 'custom-page-builder'));
        }
        
        // Reorder remaining sections
        $this->reorder_sections_after_deletion($page_id, $existing_section->get_section_order());
        
        // Invalidate page caches
        if (class_exists('Custom_Page_Builder\Cache_Manager')) {
            \Custom_Page_Builder\Cache_Manager::invalidate_page_caches($page_id);
        }
        
        // Fire action hook after deletion
        do_action('cpb_section_deleted', $section_id, $page_id);
        
        return true;
    }
    
    /**
     * Get a section by ID
     *
     * @param int $section_id Section ID
     * @return Section|null Section object or null if not found
     */
    public function get_section(int $section_id): ?Section {
        // Database access handled by Database_Helper
        
        $table_name = $wpdb->prefix . 'custom_page_sections';
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $section_id),
            ARRAY_A
        );
        
        if (!$row) {
            return null;
        }
        
        return Section::from_database($row);
    }
    
    /**
     * Get sections by page ID
     *
     * @param int $page_id Page ID
     * @return array Array of Section objects
     */
    public function get_sections_by_page(int $page_id): array {
        // Database access handled by Database_Helper
        
        // Use optimized query from Query_Optimizer
        if (class_exists('Custom_Page_Builder\Query_Optimizer')) {
            $sql = \Custom_Page_Builder\Query_Optimizer::get_optimized_sections_query($page_id, true);
        } else {
            // Fallback to original query
            $table_name = $wpdb->prefix . 'custom_page_sections';
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE page_id = %d ORDER BY section_order ASC",
                $page_id
            );
        }
        
        $rows = $wpdb->get_results($sql, ARRAY_A);
        
        $sections = [];
        foreach ($rows as $row) {
            $sections[] = Section::from_database($row);
        }
        
        return $sections;
    }
    
    /**
     * Get sections for multiple pages efficiently
     *
     * @param array $page_ids Array of page IDs
     * @return array Associative array with page_id as key and sections array as value
     */
    public function get_sections_by_pages(array $page_ids): array {
        if (empty($page_ids)) {
            return [];
        }
        
        // Use optimized batch query
        if (class_exists('Custom_Page_Builder\Query_Optimizer')) {
            $grouped_sections = \Custom_Page_Builder\Query_Optimizer::get_batch_sections($page_ids);
        } else {
            // Fallback to individual queries
            $grouped_sections = [];
            foreach ($page_ids as $page_id) {
                $grouped_sections[$page_id] = $this->get_sections_by_page($page_id);
            }
            return $grouped_sections;
        }
        
        // Convert database rows to Section objects
        $result = [];
        foreach ($grouped_sections as $page_id => $sections_data) {
            $sections = [];
            foreach ($sections_data as $row) {
                $sections[] = Section::from_database($row);
            }
            $result[$page_id] = $sections;
        }
        
        return $result;
    }
    
    /**
     * Reorder sections
     *
     * @param int $page_id Page ID
     * @param array $section_order Array of section IDs in new order
     * @return bool Success status
     * @throws \Exception If reordering fails
     */
    public function reorder_sections(int $page_id, array $section_order): bool {
        // Database access handled by Database_Helper
        
        // Validate that all sections belong to the page
        $existing_sections = $this->get_sections_by_page($page_id);
        $existing_section_ids = array_map(function($section) {
            return $section->get_id();
        }, $existing_sections);
        
        // Check if all provided section IDs exist and belong to the page
        foreach ($section_order as $section_id) {
            if (!in_array($section_id, $existing_section_ids)) {
                throw new \Exception(__('Invalid section ID in order array', 'custom-page-builder'));
            }
        }
        
        // Check if all existing sections are included in the order
        if (count($section_order) !== count($existing_section_ids)) {
            throw new \Exception(__('Section order array must include all sections', 'custom-page-builder'));
        }
        
        // Update section orders
        $table_name = $wpdb->prefix . 'custom_page_sections';
        
        foreach ($section_order as $index => $section_id) {
            $new_order = $index + 1;
            
            $result = $wpdb->update(
                $table_name,
                [
                    'section_order' => $new_order,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $section_id],
                ['%d', '%s'],
                ['%d']
            );
            
            if ($result === false) {
                throw new \Exception(__('Failed to update section order', 'custom-page-builder'));
            }
        }
        
        // Fire action hook
        do_action('cpb_sections_reordered', $page_id, $section_order);
        
        return true;
    }
    
    /**
     * Copy section within the same page or to another page
     *
     * @param int $section_id Section ID to copy
     * @param int $target_page_id Target page ID
     * @param int $target_position Target position (0 = end of list)
     * @return int New section ID
     * @throws \Exception If copying fails
     */
    public function copy_section(int $section_id, int $target_page_id, int $target_position = 0): int {
        // Get original section
        $original_section = $this->get_section($section_id);
        if (!$original_section) {
            throw new \Exception(__('Original section not found', 'custom-page-builder'));
        }
        
        // Validate target page exists
        $page_builder = new Page_Builder();
        $target_page = $page_builder->get_page($target_page_id);
        if (!$target_page) {
            throw new \Exception(__('Target page not found', 'custom-page-builder'));
        }
        
        // Create new section with same configuration
        $config = $original_section->get_config();
        
        // Modify title if copying within the same page
        if ($original_section->get_page_id() === $target_page_id && isset($config['title'])) {
            $config['title'] = $config['title'] . ' (Copy)';
        }
        
        $new_section_id = $this->create_section(
            $target_page_id,
            $original_section->get_section_type(),
            $config
        );
        
        // Move to target position if specified
        if ($target_position > 0) {
            $this->move_section_to_position($new_section_id, $target_position);
        }
        
        // Fire action hook
        do_action('cpb_section_copied', $new_section_id, $section_id, $target_page_id);
        
        return $new_section_id;
    }
    
    /**
     * Move section to specific position
     *
     * @param int $section_id Section ID
     * @param int $new_position New position (1-based)
     * @return bool Success status
     * @throws \Exception If moving fails
     */
    public function move_section_to_position(int $section_id, int $new_position): bool {
        $section = $this->get_section($section_id);
        if (!$section) {
            throw new \Exception(__('Section not found', 'custom-page-builder'));
        }
        
        $page_id = $section->get_page_id();
        $sections = $this->get_sections_by_page($page_id);
        
        // Remove the section from its current position
        $sections = array_filter($sections, function($s) use ($section_id) {
            return $s->get_id() !== $section_id;
        });
        
        // Insert at new position
        $sections = array_values($sections); // Re-index
        array_splice($sections, $new_position - 1, 0, [$section]);
        
        // Create new order array
        $new_order = array_map(function($s) {
            return $s->get_id();
        }, $sections);
        
        return $this->reorder_sections($page_id, $new_order);
    }
    
    /**
     * Get next section order for a page
     *
     * @param int $page_id Page ID
     * @return int Next section order
     */
    private function get_next_section_order(int $page_id): int {
        // Database access handled by Database_Helper
        
        $table_name = $wpdb->prefix . 'custom_page_sections';
        $max_order = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(section_order) FROM {$table_name} WHERE page_id = %d",
                $page_id
            )
        );
        
        return ($max_order ?? 0) + 1;
    }
    
    /**
     * Reorder sections after deletion
     *
     * @param int $page_id Page ID
     * @param int $deleted_order Order of deleted section
     */
    private function reorder_sections_after_deletion(int $page_id, int $deleted_order): void {
        // Database access handled by Database_Helper
        
        // Update all sections with order greater than deleted section
        $table_name = $wpdb->prefix . 'custom_page_sections';
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table_name} SET section_order = section_order - 1, updated_at = %s WHERE page_id = %d AND section_order > %d",
                current_time('mysql'),
                $page_id,
                $deleted_order
            )
        );
    }
    
    /**
     * Get section count for a page
     *
     * @param int $page_id Page ID
     * @return int Section count
     */
    public function get_section_count(int $page_id): int {
        // Database access handled by Database_Helper
        
        $table_name = $wpdb->prefix . 'custom_page_sections';
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE page_id = %d",
                $page_id
            )
        );
    }
    
    /**
     * Get sections by type
     *
     * @param string $section_type Section type
     * @param int $limit Limit results
     * @return array Array of Section objects
     */
    public function get_sections_by_type(string $section_type, int $limit = 50): array {
        // Database access handled by Database_Helper
        
        $table_name = $wpdb->prefix . 'custom_page_sections';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE section_type = %s ORDER BY created_at DESC LIMIT %d",
                $section_type,
                $limit
            ),
            ARRAY_A
        );
        
        $sections = [];
        foreach ($rows as $row) {
            $sections[] = Section::from_database($row);
        }
        
        return $sections;
    }
}