<?php
/**
 * CustomPage model class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Models;

use DateTime;
use Exception;
use Custom_Page_Builder\Installer;
use Custom_Page_Builder\Error_Logger;
use Custom_Page_Builder\Exceptions\ValidationException;
use Custom_Page_Builder\Exceptions\DatabaseException;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CustomPage model class for managing custom pages
 */
class CustomPage {
    
    /**
     * Page ID
     *
     * @var int|null
     */
    private $id;
    
    /**
     * Page title
     *
     * @var string
     */
    private $title;
    
    /**
     * Page slug
     *
     * @var string
     */
    private $slug;
    
    /**
     * Page status
     *
     * @var string
     */
    private $status;
    
    /**
     * Created timestamp
     *
     * @var DateTime
     */
    private $created_at;
    
    /**
     * Updated timestamp
     *
     * @var DateTime
     */
    private $updated_at;
    
    /**
     * Published timestamp
     *
     * @var DateTime|null
     */
    private $published_at;
    
    /**
     * Scheduled publication timestamp
     *
     * @var DateTime|null
     */
    private $scheduled_at;
    
    /**
     * Author ID
     *
     * @var int
     */
    private $author_id;
    
    /**
     * Meta data
     *
     * @var array
     */
    private $meta_data;
    
    /**
     * Page sections
     *
     * @var array
     */
    private $sections;
    
    /**
     * Valid page statuses
     *
     * @var array
     */
    const VALID_STATUSES = ['draft', 'published', 'archived', 'scheduled'];
    
    /**
     * Constructor
     *
     * @param array $data Page data
     */
    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->title = $data['title'] ?? '';
        $this->slug = $data['slug'] ?? '';
        $this->status = $data['status'] ?? 'draft';
        $this->created_at = isset($data['created_at']) ? new DateTime($data['created_at']) : new DateTime();
        $this->updated_at = isset($data['updated_at']) ? new DateTime($data['updated_at']) : new DateTime();
        $this->published_at = isset($data['published_at']) ? new DateTime($data['published_at']) : null;
        $this->scheduled_at = isset($data['scheduled_at']) ? new DateTime($data['scheduled_at']) : null;
        $this->author_id = $data['author_id'] ?? get_current_user_id();
        $this->meta_data = $data['meta_data'] ?? [];
        $this->sections = $data['sections'] ?? [];
        
        // Ensure meta_data is an array
        if (is_string($this->meta_data)) {
            $this->meta_data = json_decode($this->meta_data, true) ?: [];
        }
    }
    
    /**
     * Create a new page
     *
     * @param array $data Page data
     * @return CustomPage|false
     */
    public static function create(array $data) {
        // Database access handled by Database_Helper
        
        $page = new self($data);
        
        // Validate the page
        if (!$page->validate()) {
            return false;
        }
        
        // Generate slug if not provided
        if (empty($page->slug)) {
            $page->slug = $page->generate_slug($page->title);
        }
        
        $table_name = Installer::get_table_names()['pages'];
        
        $result = $wpdb->insert(
            $table_name,
            [
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
                'author_id' => $page->author_id,
                'meta_data' => json_encode($page->meta_data),
                'published_at' => $page->status === 'published' ? current_time('mysql') : null,
                'scheduled_at' => $page->scheduled_at ? $page->scheduled_at->format('Y-m-d H:i:s') : null
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            return false;
        }
        
        $page->id = $wpdb->insert_id;
        $page->created_at = new DateTime();
        $page->updated_at = new DateTime();
        
        if ($page->status === 'published') {
            $page->published_at = new DateTime();
        }
        
        return $page;
    }
    
    /**
     * Find a page by ID
     *
     * @param int $id Page ID
     * @return CustomPage|null
     */
    public static function find(int $id) {
        // Database access handled by Database_Helper
        
        $table_name = Installer::get_table_names()['pages'];
        
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
     * Find a page by slug
     *
     * @param string $slug Page slug
     * @return CustomPage|null
     */
    public static function find_by_slug(string $slug) {
        // Database access handled by Database_Helper
        
        $table_name = Installer::get_table_names()['pages'];
        
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE slug = %s", $slug),
            ARRAY_A
        );
        
        if (!$row) {
            return null;
        }
        
        return self::from_database($row);
    }
    
    /**
     * Get all pages with optional filtering
     *
     * @param array $args Query arguments
     * @return array
     */
    public static function get_all(array $args = []) {
        // Database access handled by Database_Helper
        
        // Use optimized query from Query_Optimizer
        if (class_exists('Custom_Page_Builder\Query_Optimizer')) {
            $sql = \Custom_Page_Builder\Query_Optimizer::get_optimized_pages_query($args);
        } else {
            // Fallback to original query logic
            $defaults = [
                'status' => null,
                'author_id' => null,
                'search' => null,
                'limit' => 20,
                'offset' => 0,
                'orderby' => 'updated_at',
                'order' => 'DESC'
            ];
            
            $args = wp_parse_args($args, $defaults);
            $table_name = Installer::get_table_names()['pages'];
            
            $where_clauses = [];
            $where_values = [];
            
            if ($args['status']) {
                $where_clauses[] = 'status = %s';
                $where_values[] = $args['status'];
            }
            
            if ($args['author_id']) {
                $where_clauses[] = 'author_id = %d';
                $where_values[] = $args['author_id'];
            }
            
            if ($args['search']) {
                $where_clauses[] = '(title LIKE %s OR slug LIKE %s)';
                $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
                $where_values[] = $search_term;
                $where_values[] = $search_term;
            }
            
            $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
            
            $sql = "SELECT * FROM {$table_name} {$where_sql} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
            $where_values[] = $args['limit'];
            $where_values[] = $args['offset'];
            
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        $rows = $wpdb->get_results($sql, ARRAY_A);
        
        $pages = [];
        foreach ($rows as $row) {
            $pages[] = self::from_database($row);
        }
        
        return $pages;
    }
    
    /**
     * Update the page
     *
     * @param bool $create_revision Whether to create a revision before saving
     * @param string $revision_note Optional revision note
     * @return bool
     */
    public function save($create_revision = true, $revision_note = '') {
        // Database access handled by Database_Helper
        
        if (!$this->validate()) {
            return false;
        }
        
        // Create revision before saving if requested and page exists
        if ($create_revision && $this->id && $this->should_create_revision()) {
            PageRevision::create_from_page($this, $revision_note);
        }
        
        $table_name = Installer::get_table_names()['pages'];
        
        $data = [
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status,
            'author_id' => $this->author_id,
            'meta_data' => json_encode($this->meta_data),
            'updated_at' => current_time('mysql'),
            'scheduled_at' => $this->scheduled_at ? $this->scheduled_at->format('Y-m-d H:i:s') : null
        ];
        
        // Set published_at if status is published and not already set
        if ($this->status === 'published' && !$this->published_at) {
            $data['published_at'] = current_time('mysql');
            $this->published_at = new DateTime();
        }
        
        $result = $wpdb->update(
            $table_name,
            $data,
            ['id' => $this->id],
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result !== false) {
            $this->updated_at = new DateTime();
            
            // Invalidate caches when page is updated
            if (class_exists('Custom_Page_Builder\Cache_Manager')) {
                \Custom_Page_Builder\Cache_Manager::invalidate_page_caches($this->id);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete the page
     *
     * @return bool
     */
    public function delete() {
        // Database access handled by Database_Helper
        
        if (!$this->id) {
            return false;
        }
        
        $table_name = Installer::get_table_names()['pages'];
        
        $result = $wpdb->delete(
            $table_name,
            ['id' => $this->id],
            ['%d']
        );
        
        if ($result !== false) {
            // Invalidate caches when page is deleted
            if (class_exists('Custom_Page_Builder\Cache_Manager')) {
                \Custom_Page_Builder\Cache_Manager::invalidate_page_caches($this->id);
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Duplicate the page
     *
     * @param string $new_title New page title
     * @return CustomPage|false
     */
    public function duplicate(string $new_title = '') {
        if (empty($new_title)) {
            $new_title = $this->title . ' (Copy)';
        }
        
        $data = $this->to_array();
        unset($data['id']);
        $data['title'] = $new_title;
        $data['slug'] = '';
        $data['status'] = 'draft';
        $data['published_at'] = null;
        
        return self::create($data);
    }
    
    /**
     * Change page status
     *
     * @param string $new_status New status
     * @return bool
     */
    public function change_status(string $new_status) {
        if (!in_array($new_status, self::VALID_STATUSES)) {
            return false;
        }
        
        $old_status = $this->status;
        $this->status = $new_status;
        
        // Set published_at when publishing
        if ($new_status === 'published' && $old_status !== 'published') {
            $this->published_at = new DateTime();
            $this->scheduled_at = null; // Clear scheduled date when manually published
        }
        
        // Clear scheduled_at when changing from scheduled status
        if ($old_status === 'scheduled' && $new_status !== 'scheduled') {
            $this->scheduled_at = null;
        }
        
        return $this->save();
    }
    
    /**
     * Schedule page for publication
     *
     * @param DateTime $scheduled_date Scheduled publication date
     * @return bool
     */
    public function schedule_publication(DateTime $scheduled_date) {
        // Can't schedule in the past
        if ($scheduled_date <= new DateTime()) {
            return false;
        }
        
        $this->scheduled_at = $scheduled_date;
        $this->status = 'scheduled';
        
        return $this->save();
    }
    
    /**
     * Publish scheduled page
     *
     * @return bool
     */
    public function publish_scheduled() {
        if ($this->status !== 'scheduled') {
            return false;
        }
        
        $this->status = 'published';
        $this->published_at = new DateTime();
        $this->scheduled_at = null;
        
        return $this->save();
    }
    
    /**
     * Check if page is ready for publication
     *
     * @return bool
     */
    public function is_ready_for_publication() {
        return $this->status === 'scheduled' 
            && $this->scheduled_at 
            && $this->scheduled_at <= new DateTime();
    }
    
    /**
     * Get pages ready for publication
     *
     * @return array
     */
    public static function get_pages_ready_for_publication() {
        // Database access handled by Database_Helper
        
        $table_name = Installer::get_table_names()['pages'];
        $current_time = current_time('mysql');
        
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE status = 'scheduled' AND scheduled_at <= %s",
                $current_time
            ),
            ARRAY_A
        );
        
        $pages = [];
        foreach ($rows as $row) {
            $pages[] = self::from_database($row);
        }
        
        return $pages;
    }
    
    /**
     * Validate page data
     *
     * @return bool
     */
    public function validate() {
        // Title is required
        if (empty($this->title)) {
            return false;
        }
        
        // Status must be valid
        if (!in_array($this->status, self::VALID_STATUSES)) {
            return false;
        }
        
        // Author ID must be valid
        if (!$this->author_id || !get_user_by('id', $this->author_id)) {
            return false;
        }
        
        // Slug must be unique
        if ($this->slug && $this->is_slug_taken($this->slug)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if slug is already taken
     *
     * @param string $slug Slug to check
     * @return bool
     */
    private function is_slug_taken(string $slug) {
        // Database access handled by Database_Helper
        
        $table_name = Installer::get_table_names()['pages'];
        
        $query = "SELECT id FROM {$table_name} WHERE slug = %s";
        $params = [$slug];
        
        if ($this->id) {
            $query .= " AND id != %d";
            $params[] = $this->id;
        }
        
        $existing_id = $wpdb->get_var($wpdb->prepare($query, $params));
        
        return $existing_id !== null;
    }
    
    /**
     * Generate unique slug from title
     *
     * @param string $title Page title
     * @return string
     */
    private function generate_slug(string $title) {
        $slug = sanitize_title($title);
        $original_slug = $slug;
        $counter = 1;
        
        while ($this->is_slug_taken($slug)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Create instance from database row
     *
     * @param array $data Database row data
     * @return CustomPage
     */
    public static function from_database(array $data) {
        return new self($data);
    }
    
    /**
     * Convert to array
     *
     * @return array
     */
    public function to_array() {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'published_at' => $this->published_at ? $this->published_at->format('Y-m-d H:i:s') : null,
            'scheduled_at' => $this->scheduled_at ? $this->scheduled_at->format('Y-m-d H:i:s') : null,
            'author_id' => $this->author_id,
            'meta_data' => $this->meta_data,
            'sections' => $this->sections
        ];
    }
    
    /**
     * Convert to API response format
     *
     * @return array
     */
    public function to_api_response() {
        $author = get_user_by('id', $this->author_id);
        
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status,
            'created_at' => $this->created_at->format('c'),
            'updated_at' => $this->updated_at->format('c'),
            'published_at' => $this->published_at ? $this->published_at->format('c') : null,
            'scheduled_at' => $this->scheduled_at ? $this->scheduled_at->format('c') : null,
            'author' => [
                'id' => $this->author_id,
                'name' => $author ? $author->display_name : 'Unknown',
                'email' => $author ? $author->user_email : ''
            ],
            'meta_data' => $this->meta_data,
            'sections' => $this->sections,
            'url' => home_url('/custom-page/' . $this->slug)
        ];
    }
    
    // Getters
    public function get_id() { return $this->id; }
    public function get_title() { return $this->title; }
    public function get_slug() { return $this->slug; }
    public function get_status() { return $this->status; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }
    public function get_published_at() { return $this->published_at; }
    public function get_scheduled_at() { return $this->scheduled_at; }
    public function get_author_id() { return $this->author_id; }
    public function get_meta_data() { return $this->meta_data; }
    public function get_sections() { return $this->sections; }
    
    /**
     * Create an autosave revision
     *
     * @return PageRevision|false
     */
    public function create_autosave() {
        if (!$this->id) {
            return false;
        }
        
        $settings = WordPress_Helper::safe_get_option('custom_page_builder_settings', []);
        if (!($settings['enable_auto_save'] ?? true)) {
            return false;
        }
        
        return PageRevision::create_from_page($this, 'Autosave', true);
    }
    
    /**
     * Get revisions for this page
     *
     * @param bool $include_autosaves Whether to include autosaves
     * @param int $limit Number of revisions to return
     * @return array
     */
    public function get_revisions($include_autosaves = false, $limit = 20) {
        if (!$this->id) {
            return [];
        }
        
        return PageRevision::get_by_page($this->id, $include_autosaves, $limit);
    }
    
    /**
     * Get latest autosave for current user
     *
     * @return PageRevision|null
     */
    public function get_latest_autosave() {
        if (!$this->id) {
            return null;
        }
        
        return PageRevision::get_latest_autosave($this->id);
    }
    
    /**
     * Restore from a revision
     *
     * @param int $revision_id Revision ID to restore from
     * @return bool
     */
    public function restore_from_revision($revision_id) {
        $revision = PageRevision::find($revision_id);
        if (!$revision || $revision->get_page_id() !== $this->id) {
            return false;
        }
        
        $restored_page = $revision->restore_page();
        if ($restored_page) {
            // Update current instance with restored data
            $this->title = $restored_page->get_title();
            $this->slug = $restored_page->get_slug();
            $this->meta_data = $restored_page->get_meta_data();
            $this->updated_at = new DateTime();
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if a revision should be created
     *
     * @return bool
     */
    private function should_create_revision() {
        $settings = WordPress_Helper::safe_get_option('custom_page_builder_settings', []);
        
        // Check if revisions are enabled
        if (!($settings['enable_page_revisions'] ?? true)) {
            return false;
        }
        
        // Don't create revision for new pages
        if (!$this->id) {
            return false;
        }
        
        // Check if enough time has passed since last revision
        $revisions = $this->get_revisions(false, 1);
        if (!empty($revisions)) {
            $last_revision = $revisions[0];
            $time_diff = time() - $last_revision->get_created_at()->getTimestamp();
            
            // Don't create revision if last one was created less than 5 minutes ago
            if ($time_diff < 300) {
                return false;
            }
        }
        
        return true;
    }
    
    // Setters
    public function set_title(string $title) { $this->title = $title; }
    public function set_slug(string $slug) { $this->slug = $slug; }
    public function set_status(string $status) { 
        if (in_array($status, self::VALID_STATUSES)) {
            $this->status = $status; 
        }
    }
    public function set_author_id(int $author_id) { $this->author_id = $author_id; }
    public function set_meta_data(array $meta_data) { $this->meta_data = $meta_data; }
    public function set_sections(array $sections) { $this->sections = $sections; }
    public function set_scheduled_at(?DateTime $scheduled_at) { $this->scheduled_at = $scheduled_at; }
}