<?php
/**
 * Page Builder class
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
 * Page Builder class for page CRUD operations
 */
class Page_Builder {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor intentionally empty - no database operations during instantiation
    }
    
    /**
     * Create a new page
     *
     * @param array $data Page data
     * @return int Page ID
     * @throws \Exception If page creation fails
     */
    public function create_page(array $data): int {
        try {
            // Use safe database helper
            $wpdb = Database_Helper::get_wpdb();
        } catch (Database_Exception $e) {
            throw new \Exception(__('Database connection not available', 'custom-page-builder'));
        }
        
        // Validate required fields
        if (empty($data['title'])) {
            throw new \Exception(__('Page title is required', 'custom-page-builder'));
        }
        
        // Generate slug if not provided
        $slug = !empty($data['slug']) ? $data['slug'] : WordPress_Helper::safe_sanitize_title($data['title']);
        
        // Ensure slug is unique
        $slug = $this->ensure_unique_slug($slug);
        
        // Prepare data for insertion
        $insert_data = [
            'title' => WordPress_Helper::safe_sanitize_text_field($data['title']),
            'slug' => $slug,
            'status' => in_array($data['status'] ?? 'draft', ['draft', 'published', 'archived', 'scheduled']) ? $data['status'] : 'draft',
            'author_id' => WordPress_Helper::safe_get_current_user_id(),
            'created_at' => WordPress_Helper::safe_current_time('mysql'),
            'updated_at' => WordPress_Helper::safe_current_time('mysql'),
            'meta_data' => json_encode($data['meta_data'] ?? [])
        ];
        
        // Set published_at if status is published
        if ($insert_data['status'] === 'published') {
            $insert_data['published_at'] = WordPress_Helper::safe_current_time('mysql');
        }
        
        // Set scheduled_at if status is scheduled
        if ($insert_data['status'] === 'scheduled' && !empty($data['scheduled_at'])) {
            $scheduled_date = new \DateTime($data['scheduled_at']);
            if ($scheduled_date > new \DateTime()) {
                $insert_data['scheduled_at'] = $scheduled_date->format('Y-m-d H:i:s');
            } else {
                throw new \Exception(__('Scheduled date must be in the future', 'custom-page-builder'));
            }
        }
        
        // Insert into database using safe method
        try {
            $page_id = Database_Helper::safe_insert('custom_pages', $insert_data);
        } catch (Database_Exception $e) {
            throw new \Exception(__('Failed to create page', 'custom-page-builder') . ': ' . $e->getMessage());
        }
        
        // Fire action hook safely
        WordPress_Helper::safe_do_action('cpb_page_created', $page_id, $data);
        
        return $page_id;
    }
    
    /**
     * Update an existing page
     *
     * @param int $page_id Page ID
     * @param array $data Page data
     * @return bool Success status
     * @throws \Exception If page update fails
     */
    public function update_page(int $page_id, array $data): bool {
        // Database access handled by Database_Helper
        
        // Check if page exists
        $existing_page = $this->get_page($page_id);
        if (!$existing_page) {
            throw new \Exception(__('Page not found', 'custom-page-builder'));
        }
        
        // Prepare update data
        $update_data = [
            'updated_at' => current_time('mysql')
        ];
        
        // Update title if provided
        if (isset($data['title'])) {
            if (empty($data['title'])) {
                throw new \Exception(__('Page title is required', 'custom-page-builder'));
            }
            $update_data['title'] = sanitize_text_field($data['title']);
        }
        
        // Update slug if provided
        if (isset($data['slug'])) {
            $slug = !empty($data['slug']) ? $data['slug'] : sanitize_title($update_data['title'] ?? $existing_page->get_title());
            $slug = $this->ensure_unique_slug($slug, $page_id);
            $update_data['slug'] = $slug;
        }
        
        // Update status if provided
        if (isset($data['status'])) {
            $new_status = in_array($data['status'], ['draft', 'published', 'archived', 'scheduled']) ? $data['status'] : 'draft';
            $old_status = $existing_page->get_status();
            
            $update_data['status'] = $new_status;
            
            // Set published_at when changing to published
            if ($new_status === 'published' && $old_status !== 'published') {
                $update_data['published_at'] = current_time('mysql');
                $update_data['scheduled_at'] = null; // Clear scheduled date
            }
            
            // Clear published_at when changing from published
            if ($new_status !== 'published' && $old_status === 'published') {
                $update_data['published_at'] = null;
            }
            
            // Clear scheduled_at when changing from scheduled
            if ($old_status === 'scheduled' && $new_status !== 'scheduled') {
                $update_data['scheduled_at'] = null;
            }
        }
        
        // Update scheduled_at if provided
        if (isset($data['scheduled_at'])) {
            if (!empty($data['scheduled_at'])) {
                $scheduled_date = new \DateTime($data['scheduled_at']);
                if ($scheduled_date > new \DateTime()) {
                    $update_data['scheduled_at'] = $scheduled_date->format('Y-m-d H:i:s');
                    $update_data['status'] = 'scheduled';
                } else {
                    throw new \Exception(__('Scheduled date must be in the future', 'custom-page-builder'));
                }
            } else {
                $update_data['scheduled_at'] = null;
            }
        }
        
        // Update meta data if provided
        if (isset($data['meta_data'])) {
            $update_data['meta_data'] = json_encode($data['meta_data']);
        }
        
        // Update in database
        $table_name = $wpdb->prefix . 'custom_pages';
        $result = $wpdb->update(
            $table_name,
            $update_data,
            ['id' => $page_id],
            null,
            ['%d']
        );
        
        if ($result === false) {
            throw new \Exception(__('Failed to update page', 'custom-page-builder'));
        }
        
        // Fire action hook
        do_action('cpb_page_updated', $page_id, $data);
        
        return true;
    }
    
    /**
     * Delete a page
     *
     * @param int $page_id Page ID
     * @return bool Success status
     * @throws \Exception If page deletion fails
     */
    public function delete_page(int $page_id): bool {
        // Database access handled by Database_Helper
        
        // Check if page exists
        $existing_page = $this->get_page($page_id);
        if (!$existing_page) {
            throw new \Exception(__('Page not found', 'custom-page-builder'));
        }
        
        // Fire action hook before deletion
        do_action('cpb_before_page_deleted', $page_id);
        
        // Delete page (sections will be deleted by foreign key constraint)
        $table_name = $wpdb->prefix . 'custom_pages';
        $result = $wpdb->delete($table_name, ['id' => $page_id], ['%d']);
        
        if ($result === false) {
            throw new \Exception(__('Failed to delete page', 'custom-page-builder'));
        }
        
        // Fire action hook after deletion
        do_action('cpb_page_deleted', $page_id);
        
        return true;
    }
    
    /**
     * Get a page by ID
     *
     * @param int $page_id Page ID
     * @return Custom_Page|null Page object or null if not found
     */
    public function get_page(int $page_id): ?Custom_Page {
        // Database access handled by Database_Helper
        
        $table_name = $wpdb->prefix . 'custom_pages';
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $page_id),
            ARRAY_A
        );
        
        if (!$row) {
            return null;
        }
        
        return Custom_Page::from_database($row);
    }
    
    /**
     * Get all pages
     *
     * @param array $args Query arguments
     * @return array Array of Custom_Page objects
     */
    public function get_all_pages(array $args = []): array {
        // Database access handled by Database_Helper
        
        // Default arguments
        $defaults = [
            'status' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'search' => null
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Build query
        $table_name = $wpdb->prefix . 'custom_pages';
        $where_clauses = [];
        $where_values = [];
        
        // Status filter
        if (!empty($args['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        // Search filter
        if (!empty($args['search'])) {
            $where_clauses[] = '(title LIKE %s OR slug LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Build WHERE clause
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Build ORDER BY clause
        $allowed_orderby = ['id', 'title', 'slug', 'status', 'created_at', 'updated_at', 'published_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Build LIMIT clause
        $limit_sql = '';
        if ($args['limit'] > 0) {
            $limit_sql = $wpdb->prepare('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }
        
        // Execute query
        $sql = "SELECT * FROM {$table_name} {$where_sql} ORDER BY {$orderby} {$order} {$limit_sql}";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        $rows = $wpdb->get_results($sql, ARRAY_A);
        
        // Convert to Custom_Page objects
        $pages = [];
        foreach ($rows as $row) {
            $pages[] = Custom_Page::from_database($row);
        }
        
        return $pages;
    }
    
    /**
     * Duplicate a page
     *
     * @param int $page_id Page ID to duplicate
     * @return int New page ID
     * @throws \Exception If duplication fails
     */
    public function duplicate_page(int $page_id): int {
        // Database access handled by Database_Helper
        
        // Get original page
        $original_page = $this->get_page($page_id);
        if (!$original_page) {
            throw new \Exception(__('Original page not found', 'custom-page-builder'));
        }
        
        // Create new page data
        $new_page_data = [
            'title' => $original_page->get_title() . ' (Copy)',
            'slug' => $original_page->get_slug() . '-copy',
            'status' => 'draft', // Always create duplicates as draft
            'meta_data' => $original_page->get_meta_data()
        ];
        
        // Create new page
        $new_page_id = $this->create_page($new_page_data);
        
        // Get original sections
        $section_manager = new Section_Manager();
        $original_sections = $section_manager->get_sections_by_page($page_id);
        
        // Duplicate sections
        foreach ($original_sections as $section) {
            $section_data = [
                'section_type' => $section->get_section_type(),
                'config' => $section->get_config()
            ];
            
            $section_manager->create_section($new_page_id, $section_data['section_type'], $section_data['config']);
        }
        
        // Fire action hook
        do_action('cpb_page_duplicated', $new_page_id, $page_id);
        
        return $new_page_id;
    }
    
    /**
     * Get page count
     *
     * @param array $args Query arguments
     * @return int Page count
     */
    public function get_page_count(array $args = []): int {
        // Database access handled by Database_Helper
        
        // Build query similar to get_all_pages but for count
        $table_name = $wpdb->prefix . 'custom_pages';
        $where_clauses = [];
        $where_values = [];
        
        // Status filter
        if (!empty($args['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        // Search filter
        if (!empty($args['search'])) {
            $where_clauses[] = '(title LIKE %s OR slug LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Build WHERE clause
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Execute count query
        $sql = "SELECT COUNT(*) FROM {$table_name} {$where_sql}";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Ensure slug is unique for the given language
     * Same slug can exist for different languages (multilingual support)
     *
     * @param string $slug Desired slug
     * @param int $exclude_id Page ID to exclude from uniqueness check
     * @param string $language Language code (default 'en')
     * @return string Unique slug for the language
     */
    private function ensure_unique_slug(string $slug, int $exclude_id = 0, string $language = 'en'): string {
        global $wpdb;
        
        $original_slug = $slug;
        $counter = 1;
        
        $table_name = $wpdb->prefix . 'custom_pages';
        
        while (true) {
            // Check if slug exists FOR THE SAME LANGUAGE ONLY
            // Same slug can exist for different languages
            $query = "SELECT COUNT(*) FROM {$table_name} WHERE slug = %s AND language = %s";
            $params = [$slug, $language];
            
            if ($exclude_id > 0) {
                $query .= " AND id != %d";
                $params[] = $exclude_id;
            }
            
            $count = $wpdb->get_var($wpdb->prepare($query, $params));
            
            if ($count == 0) {
                break;
            }
            
            // Generate new slug with counter only if same slug+language combo exists
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Schedule a page for publication
     *
     * @param int $page_id Page ID
     * @param string $scheduled_date Scheduled publication date (Y-m-d H:i:s format)
     * @return bool Success status
     * @throws \Exception If scheduling fails
     */
    public function schedule_page(int $page_id, string $scheduled_date): bool {
        $page = $this->get_page($page_id);
        if (!$page) {
            throw new \Exception(__('Page not found', 'custom-page-builder'));
        }
        
        $scheduled_datetime = new \DateTime($scheduled_date);
        if ($scheduled_datetime <= new \DateTime()) {
            throw new \Exception(__('Scheduled date must be in the future', 'custom-page-builder'));
        }
        
        $success = $page->schedule_publication($scheduled_datetime);
        
        if ($success) {
            // Fire action hook
            do_action('cpb_page_scheduled', $page, $scheduled_datetime);
        }
        
        return $success;
    }
    
    /**
     * Unschedule a page
     *
     * @param int $page_id Page ID
     * @return bool Success status
     * @throws \Exception If unscheduling fails
     */
    public function unschedule_page(int $page_id): bool {
        $page = $this->get_page($page_id);
        if (!$page) {
            throw new \Exception(__('Page not found', 'custom-page-builder'));
        }
        
        if ($page->get_status() !== 'scheduled') {
            throw new \Exception(__('Page is not scheduled', 'custom-page-builder'));
        }
        
        $success = $page->change_status('draft');
        
        if ($success) {
            // Fire action hook
            do_action('cpb_page_unscheduled', $page);
        }
        
        return $success;
    }
    
    /**
     * Get scheduled pages
     *
     * @param array $args Query arguments
     * @return array Array of scheduled pages
     */
    public function get_scheduled_pages(array $args = []): array {
        $args['status'] = 'scheduled';
        return $this->get_all_pages($args);
    }
    
    /**
     * Get pages ready for publication
     *
     * @return array Array of pages ready for publication
     */
    public function get_pages_ready_for_publication(): array {
        return \Custom_Page_Builder\Models\CustomPage::get_pages_ready_for_publication();
    }
    
    /**
     * Publish scheduled pages
     *
     * @return int Number of pages published
     */
    public function publish_scheduled_pages(): int {
        $pages = $this->get_pages_ready_for_publication();
        $published_count = 0;
        
        foreach ($pages as $page) {
            try {
                if ($page->publish_scheduled()) {
                    $published_count++;
                    
                    // Fire action hook
                    do_action('cpb_page_auto_published', $page);
                }
            } catch (\Exception $e) {
                error_log(sprintf(
                    'Custom Page Builder: Failed to auto-publish page "%s" (ID: %d): %s',
                    $page->get_title(),
                    $page->get_id(),
                    $e->getMessage()
                ));
            }
        }
        
        return $published_count;
    }
    
    /**
     * Create a revision for a page
     *
     * @param int $page_id Page ID
     * @param string $note Optional revision note
     * @param bool $is_autosave Whether this is an autosave
     * @return int|false Revision ID or false on failure
     * @throws \Exception If revision creation fails
     */
    public function create_revision(int $page_id, string $note = '', bool $is_autosave = false) {
        $page = $this->get_page($page_id);
        if (!$page) {
            throw new \Exception(__('Page not found', 'custom-page-builder'));
        }
        
        $revision = \Custom_Page_Builder\Models\PageRevision::create_from_page($page, $note, $is_autosave);
        
        if (!$revision) {
            throw new \Exception(__('Failed to create revision', 'custom-page-builder'));
        }
        
        // Fire action hook
        do_action('cpb_revision_created', $revision->get_id(), $page_id, $is_autosave);
        
        return $revision->get_id();
    }
    
    /**
     * Get revisions for a page
     *
     * @param int $page_id Page ID
     * @param bool $include_autosaves Whether to include autosaves
     * @param int $limit Number of revisions to return
     * @return array Array of PageRevision objects
     */
    public function get_page_revisions(int $page_id, bool $include_autosaves = false, int $limit = 20): array {
        return \Custom_Page_Builder\Models\PageRevision::get_by_page($page_id, $include_autosaves, $limit);
    }
    
    /**
     * Get a specific revision
     *
     * @param int $revision_id Revision ID
     * @return \Custom_Page_Builder\Models\PageRevision|null
     */
    public function get_revision(int $revision_id): ?\Custom_Page_Builder\Models\PageRevision {
        return \Custom_Page_Builder\Models\PageRevision::find($revision_id);
    }
    
    /**
     * Restore a page from a revision
     *
     * @param int $page_id Page ID
     * @param int $revision_id Revision ID
     * @return bool Success status
     * @throws \Exception If restoration fails
     */
    public function restore_from_revision(int $page_id, int $revision_id): bool {
        $page = $this->get_page($page_id);
        if (!$page) {
            throw new \Exception(__('Page not found', 'custom-page-builder'));
        }
        
        $revision = $this->get_revision($revision_id);
        if (!$revision || $revision->get_page_id() !== $page_id) {
            throw new \Exception(__('Revision not found or does not belong to this page', 'custom-page-builder'));
        }
        
        $restored_page = $revision->restore_page();
        $success = $restored_page !== false;
        
        if ($success) {
            // Fire action hook
            do_action('cpb_page_restored_from_revision', $page_id, $revision_id);
        }
        
        return $success;
    }
    
    /**
     * Delete a revision
     *
     * @param int $revision_id Revision ID
     * @return bool Success status
     * @throws \Exception If deletion fails
     */
    public function delete_revision(int $revision_id): bool {
        $revision = $this->get_revision($revision_id);
        if (!$revision) {
            throw new \Exception(__('Revision not found', 'custom-page-builder'));
        }
        
        // Don't allow deletion of the only revision
        $page_revisions = $this->get_page_revisions($revision->get_page_id(), false, 2);
        if (count($page_revisions) <= 1) {
            throw new \Exception(__('Cannot delete the only revision', 'custom-page-builder'));
        }
        
        $success = $revision->delete();
        
        if ($success) {
            // Fire action hook
            do_action('cpb_revision_deleted', $revision_id, $revision->get_page_id());
        }
        
        return $success;
    }
    
    /**
     * Get latest autosave for a page
     *
     * @param int $page_id Page ID
     * @param int $user_id User ID (optional, defaults to current user)
     * @return \Custom_Page_Builder\Models\PageRevision|null
     */
    public function get_latest_autosave(int $page_id, int $user_id = null): ?\Custom_Page_Builder\Models\PageRevision {
        return \Custom_Page_Builder\Models\PageRevision::get_latest_autosave($page_id, $user_id);
    }
    
    /**
     * Create autosave for a page
     *
     * @param int $page_id Page ID
     * @return int|false Autosave revision ID or false on failure
     */
    public function create_autosave(int $page_id) {
        try {
            return $this->create_revision($page_id, 'Autosave', true);
        } catch (\Exception $e) {
            error_log('Custom Page Builder: Failed to create autosave - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Compare two revisions
     *
     * @param int $revision1_id First revision ID
     * @param int $revision2_id Second revision ID
     * @return array Comparison data
     * @throws \Exception If comparison fails
     */
    public function compare_revisions(int $revision1_id, int $revision2_id): array {
        $revision1 = $this->get_revision($revision1_id);
        $revision2 = $this->get_revision($revision2_id);
        
        if (!$revision1 || !$revision2) {
            throw new \Exception(__('One or both revisions not found', 'custom-page-builder'));
        }
        
        if ($revision1->get_page_id() !== $revision2->get_page_id()) {
            throw new \Exception(__('Revisions must belong to the same page', 'custom-page-builder'));
        }
        
        return $revision1->compare_with($revision2);
    }
}