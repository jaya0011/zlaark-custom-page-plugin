<?php
/**
 * Import/Export Handler for Custom Page Builder
 * 
 * Handles exporting pages to JSON and importing pages from JSON files
 */

namespace Custom_Page_Builder;

if (!defined('ABSPATH')) {
    exit;
}

class Import_Export {
    
    /**
     * Export a single page to JSON
     * 
     * @param int $page_id The page ID to export
     * @return array|WP_Error Export data or error
     */
    public static function export_page($page_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_pages';
        
        $page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $page_id
        ));
        
        if (!$page) {
            return new \WP_Error('page_not_found', 'Page not found');
        }
        
        // Prepare export data
        $export_data = array(
            'version' => CUSTOM_PAGE_BUILDER_VERSION,
            'export_date' => current_time('mysql'),
            'page' => array(
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
                'sections' => json_decode($page->sections, true),
                'language' => isset($page->language) ? $page->language : 'en',
                'created_at' => $page->created_at,
                'updated_at' => $page->updated_at
            )
        );
        
        return $export_data;
    }
    
    /**
     * Export multiple pages to JSON
     * 
     * @param array $page_ids Array of page IDs to export
     * @return array|WP_Error Export data or error
     */
    public static function export_pages($page_ids) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_pages';
        
        $pages = array();
        
        foreach ($page_ids as $page_id) {
            $page = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $page_id
            ));
            
            if ($page) {
                $pages[] = array(
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'status' => $page->status,
                    'sections' => json_decode($page->sections, true),
                    'language' => isset($page->language) ? $page->language : 'en',
                    'created_at' => $page->created_at,
                    'updated_at' => $page->updated_at
                );
            }
        }
        
        if (empty($pages)) {
            return new \WP_Error('no_pages_found', 'No pages found to export');
        }
        
        $export_data = array(
            'version' => CUSTOM_PAGE_BUILDER_VERSION,
            'export_date' => current_time('mysql'),
            'pages' => $pages
        );
        
        return $export_data;
    }
    
    /**
     * Import a page from JSON data
     * 
     * @param array $page_data The page data to import
     * @param bool $update_existing Whether to update if slug exists
     * @return int|WP_Error Page ID or error
     */
    public static function import_page($page_data, $update_existing = false) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_pages';
        
        // Validate required fields
        if (empty($page_data['title']) || empty($page_data['slug'])) {
            return new \WP_Error('invalid_data', 'Title and slug are required');
        }
        
        $title = sanitize_text_field($page_data['title']);
        $slug = sanitize_title($page_data['slug']);
        $status = sanitize_text_field($page_data['status'] ?? 'draft');
        $language = sanitize_text_field($page_data['language'] ?? 'en');
        $sections = isset($page_data['sections']) ? wp_json_encode($page_data['sections']) : '[]';
        
        // Check if language column exists in the table
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'language'");
        $has_language_column = !empty($columns);
        
        // Check if page with this slug already exists
        if ($has_language_column) {
            $existing_page = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_name WHERE slug = %s AND language = %s",
                $slug,
                $language
            ));
        } else {
            $existing_page = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_name WHERE slug = %s",
                $slug
            ));
        }
        
        if ($existing_page) {
            if ($update_existing) {
                // Update existing page
                $result = $wpdb->update(
                    $table_name,
                    array(
                        'title' => $title,
                        'status' => $status,
                        'sections' => $sections,
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $existing_page->id),
                    array('%s', '%s', '%s', '%s'),
                    array('%d')
                );
                
                if ($result === false) {
                    return new \WP_Error('update_failed', 'Failed to update page: ' . $wpdb->last_error);
                }
                
                return $existing_page->id;
            } else {
                // Generate unique slug
                $base_slug = $slug;
                $counter = 1;
                do {
                    $slug = $base_slug . '-' . $counter;
                    $counter++;
                    if ($has_language_column) {
                        $existing_page = $wpdb->get_row($wpdb->prepare(
                            "SELECT id FROM $table_name WHERE slug = %s AND language = %s",
                            $slug,
                            $language
                        ));
                    } else {
                        $existing_page = $wpdb->get_row($wpdb->prepare(
                            "SELECT id FROM $table_name WHERE slug = %s",
                            $slug
                        ));
                    }
                } while ($existing_page);
            }
        }
        
        // Prepare insert data based on available columns
        $insert_data = array(
            'title' => $title,
            'slug' => $slug,
            'status' => $status,
            'sections' => $sections,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $insert_format = array('%s', '%s', '%s', '%s', '%s', '%s');
        
        // Only add language if column exists
        if ($has_language_column) {
            $insert_data['language'] = $language;
            $insert_format[] = '%s';
        }
        
        // Insert new page
        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            $insert_format
        );
        
        if ($result === false) {
            return new \WP_Error('insert_failed', 'Failed to insert page: ' . $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Import multiple pages from JSON data
     * 
     * @param array $import_data The import data containing pages array
     * @param bool $update_existing Whether to update if slug exists
     * @return array Results array with success/error info
     */
    public static function import_pages($import_data, $update_existing = false) {
        $results = array(
            'success' => array(),
            'errors' => array()
        );
        
        if (!isset($import_data['pages']) || !is_array($import_data['pages'])) {
            $results['errors'][] = 'Invalid import data format';
            return $results;
        }
        
        foreach ($import_data['pages'] as $page_data) {
            $result = self::import_page($page_data, $update_existing);
            
            if (is_wp_error($result)) {
                $results['errors'][] = array(
                    'title' => $page_data['title'] ?? 'Unknown',
                    'error' => $result->get_error_message()
                );
            } else {
                $results['success'][] = array(
                    'id' => $result,
                    'title' => $page_data['title']
                );
            }
        }
        
        return $results;
    }
}
