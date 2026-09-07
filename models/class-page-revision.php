<?php
/**
 * PageRevision model class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Models;

use DateTime;
use Exception;
use Custom_Page_Builder\Installer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PageRevision model class for managing page revisions
 */
class PageRevision {
    
    /**
     * Revision ID
     *
     * @var int|null
     */
    private $id;
    
    /**
     * Page ID
     *
     * @var int
     */
    private $page_id;
    
    /**
     * Revision data (JSON encoded page data)
     *
     * @var array
     */
    private $revision_data;
    
    /**
     * Revision note
     *
     * @var string
     */
    private $revision_note;
    
    /**
     * Created timestamp
     *
     * @var DateTime
     */
    private $created_at;
    
    /**
     * Created by user ID
     *
     * @var int
     */
    private $created_by;
    
    /**
     * Is autosave flag
     *
     * @var bool
     */
    private $is_autosave;
    
    /**
     * Constructor
     *
     * @param array $data Revision data
     */
    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->page_id = $data['page_id'] ?? 0;
        $this->revision_data = $data['revision_data'] ?? [];
        $this->revision_note = $data['revision_note'] ?? '';
        $this->created_at = isset($data['created_at']) ? new DateTime($data['created_at']) : new DateTime();
        $this->created_by = $data['created_by'] ?? get_current_user_id();
        $this->is_autosave = (bool) ($data['is_autosave'] ?? false);
        
        // Ensure revision_data is an array
        if (is_string($this->revision_data)) {
            $this->revision_data = json_decode($this->revision_data, true) ?: [];
        }
    }
    
    /**
     * Create a new revision
     *
     * @param CustomPage $page Page to create revision for
     * @param string $note Optional revision note
     * @param bool $is_autosave Whether this is an autosave
     * @return PageRevision|false
     */
    public static function create_from_page(CustomPage $page, string $note = '', bool $is_autosave = false) {
        // Database access handled by Database_Helper
        
        if (!$page->get_id()) {
            return false;
        }
        
        // Get page data including sections
        $page_data = $page->to_array();
        
        // Get sections for this page
        $sections_table = Installer::get_table_names()['sections'];
        $sections = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$sections_table} WHERE page_id = %d ORDER BY section_order ASC",
                $page->get_id()
            ),
            ARRAY_A
        );
        
        $page_data['sections'] = $sections;
        
        $table_name = Installer::get_table_names()['revisions'];
        
        $result = $wpdb->insert(
            $table_name,
            [
                'page_id' => $page->get_id(),
                'revision_data' => json_encode($page_data),
                'revision_note' => $note,
                'created_by' => get_current_user_id(),
                'is_autosave' => $is_autosave ? 1 : 0
            ],
            ['%d', '%s', '%s', '%d', '%d']
        );
        
        if ($result === false) {
            return false;
        }
        
        $revision = new self([
            'id' => $wpdb->insert_id,
            'page_id' => $page->get_id(),
            'revision_data' => $page_data,
            'revision_note' => $note,
            'created_by' => get_current_user_id(),
            'is_autosave' => $is_autosave
        ]);
        
        // Clean up old revisions if needed
        if (!$is_autosave) {
            self::cleanup_old_revisions($page->get_id());
        }
        
        return $revision;
    }
    
    /**
     * Find a revision by ID
     *
     * @param int $id Revision ID
     * @return PageRevision|null
     */
    public static function find(int $id) {
        // Database access handled by Database_Helper
        
        $table_name = Installer::get_table_names()['revisions'];
        
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
     * Get all revisions for a page
     *
     * @param int $page_id Page ID
     * @param bool $include_autosaves Whether to include autosaves
     * @param int $limit Number of revisions to return
     * @return array
     */
    public static function get_by_page(int $page_id, bool $include_autosaves = false, int $limit = 20) {
        // Database access handled by Database_Helper
        
        $table_name = Installer::get_table_names()['revisions'];
        
        $where_clause = "WHERE page_id = %d";
        $params = [$page_id];
        
        if (!$include_autosaves) {
            $where_clause .= " AND is_autosave = 0";
        }
        
        $sql = "SELECT * FROM {$table_name} {$where_clause} ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;
        
        $rows = $wpdb->get_results(
            $wpdb->prepare($sql, $params),
            ARRAY_A
        );
        
        $revisions = [];
        foreach ($rows as $row) {
            $revisions[] = self::from_database($row);
        }
        
        return $revisions;
    }
    
    /**
     * Get latest autosave for a page
     *
     * @param int $page_id Page ID
     * @param int $user_id User ID (optional, defaults to current user)
     * @return PageRevision|null
     */
    public static function get_latest_autosave(int $page_id, int $user_id = null) {
        // Database access handled by Database_Helper
        
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        $table_name = Installer::get_table_names()['revisions'];
        
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} 
                WHERE page_id = %d AND created_by = %d AND is_autosave = 1 
                ORDER BY created_at DESC LIMIT 1",
                $page_id,
                $user_id
            ),
            ARRAY_A
        );
        
        if (!$row) {
            return null;
        }
        
        return self::from_database($row);
    }
    
    /**
     * Restore page from this revision
     *
     * @return CustomPage|false
     */
    public function restore_page() {
        if (empty($this->revision_data)) {
            return false;
        }
        
        // Get the current page
        $page = CustomPage::find($this->page_id);
        if (!$page) {
            return false;
        }
        
        // Create a revision of the current state before restoring
        self::create_from_page($page, 'Before restore to revision #' . $this->id);
        
        // Update page with revision data
        $revision_data = $this->revision_data;
        
        // Update page properties
        $page->set_title($revision_data['title'] ?? '');
        $page->set_slug($revision_data['slug'] ?? '');
        $page->set_meta_data($revision_data['meta_data'] ?? []);
        
        // Save the page
        if (!$page->save()) {
            return false;
        }
        
        // Restore sections
        if (isset($revision_data['sections']) && is_array($revision_data['sections'])) {
            $this->restore_sections($revision_data['sections']);
        }
        
        return $page;
    }
    
    /**
     * Restore sections from revision data
     *
     * @param array $sections_data Sections data from revision
     */
    private function restore_sections(array $sections_data) {
        // Database access handled by Database_Helper
        
        $sections_table = Installer::get_table_names()['sections'];
        
        // Delete current sections
        $wpdb->delete(
            $sections_table,
            ['page_id' => $this->page_id],
            ['%d']
        );
        
        // Restore sections from revision
        foreach ($sections_data as $section_data) {
            $wpdb->insert(
                $sections_table,
                [
                    'page_id' => $this->page_id,
                    'section_type' => $section_data['section_type'],
                    'section_order' => $section_data['section_order'],
                    'config' => $section_data['config']
                ],
                ['%d', '%s', '%d', '%s']
            );
        }
    }
    
    /**
     * Delete this revision
     *
     * @return bool
     */
    public function delete() {
        // Database access handled by Database_Helper
        
        if (!$this->id) {
            return false;
        }
        
        $table_name = Installer::get_table_names()['revisions'];
        
        $result = $wpdb->delete(
            $table_name,
            ['id' => $this->id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Clean up old revisions for a page
     *
     * @param int $page_id Page ID
     */
    public static function cleanup_old_revisions(int $page_id) {
        // Database access handled by Database_Helper
        
        $settings = WordPress_Helper::safe_get_option('custom_page_builder_settings', []);
        $max_revisions = $settings['max_revisions'] ?? 10;
        
        if ($max_revisions <= 0) {
            return; // No limit
        }
        
        $table_name = Installer::get_table_names()['revisions'];
        
        // Get revision IDs to delete (keep the most recent ones, exclude autosaves)
        $revisions_to_delete = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$table_name} 
                WHERE page_id = %d AND is_autosave = 0 
                ORDER BY created_at DESC 
                LIMIT %d, 999999",
                $page_id,
                $max_revisions
            )
        );
        
        if (!empty($revisions_to_delete)) {
            $ids_placeholder = implode(',', array_fill(0, count($revisions_to_delete), '%d'));
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table_name} WHERE id IN ({$ids_placeholder})",
                    $revisions_to_delete
                )
            );
        }
        
        // Also clean up old autosaves (keep only the latest 5 per user)
        $users_with_autosaves = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT created_by FROM {$table_name} 
                WHERE page_id = %d AND is_autosave = 1",
                $page_id
            )
        );
        
        foreach ($users_with_autosaves as $user_id) {
            $autosaves_to_delete = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT id FROM {$table_name} 
                    WHERE page_id = %d AND created_by = %d AND is_autosave = 1 
                    ORDER BY created_at DESC 
                    LIMIT 5, 999999",
                    $page_id,
                    $user_id
                )
            );
            
            if (!empty($autosaves_to_delete)) {
                $ids_placeholder = implode(',', array_fill(0, count($autosaves_to_delete), '%d'));
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$table_name} WHERE id IN ({$ids_placeholder})",
                        $autosaves_to_delete
                    )
                );
            }
        }
    }
    
    /**
     * Get revision comparison data
     *
     * @param PageRevision $other_revision Revision to compare with
     * @return array
     */
    public function compare_with(PageRevision $other_revision) {
        $this_data = $this->revision_data;
        $other_data = $other_revision->get_revision_data();
        
        $comparison = [
            'title_changed' => ($this_data['title'] ?? '') !== ($other_data['title'] ?? ''),
            'slug_changed' => ($this_data['slug'] ?? '') !== ($other_data['slug'] ?? ''),
            'meta_data_changed' => json_encode($this_data['meta_data'] ?? []) !== json_encode($other_data['meta_data'] ?? []),
            'sections_changed' => $this->compare_sections($this_data['sections'] ?? [], $other_data['sections'] ?? []),
            'changes' => []
        ];
        
        // Detailed changes
        if ($comparison['title_changed']) {
            $comparison['changes'][] = [
                'field' => 'title',
                'old_value' => $other_data['title'] ?? '',
                'new_value' => $this_data['title'] ?? ''
            ];
        }
        
        if ($comparison['slug_changed']) {
            $comparison['changes'][] = [
                'field' => 'slug',
                'old_value' => $other_data['slug'] ?? '',
                'new_value' => $this_data['slug'] ?? ''
            ];
        }
        
        return $comparison;
    }
    
    /**
     * Compare sections between revisions
     *
     * @param array $sections1 First set of sections
     * @param array $sections2 Second set of sections
     * @return bool
     */
    private function compare_sections(array $sections1, array $sections2) {
        if (count($sections1) !== count($sections2)) {
            return true;
        }
        
        // Sort by section_order for comparison
        usort($sections1, function($a, $b) {
            return ($a['section_order'] ?? 0) <=> ($b['section_order'] ?? 0);
        });
        
        usort($sections2, function($a, $b) {
            return ($a['section_order'] ?? 0) <=> ($b['section_order'] ?? 0);
        });
        
        for ($i = 0; $i < count($sections1); $i++) {
            $section1 = $sections1[$i];
            $section2 = $sections2[$i];
            
            if (($section1['section_type'] ?? '') !== ($section2['section_type'] ?? '') ||
                ($section1['section_order'] ?? 0) !== ($section2['section_order'] ?? 0) ||
                ($section1['config'] ?? '') !== ($section2['config'] ?? '')) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Create instance from database row
     *
     * @param array $data Database row data
     * @return PageRevision
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
            'page_id' => $this->page_id,
            'revision_data' => $this->revision_data,
            'revision_note' => $this->revision_note,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'is_autosave' => $this->is_autosave
        ];
    }
    
    /**
     * Convert to API response format
     *
     * @return array
     */
    public function to_api_response() {
        $author = get_user_by('id', $this->created_by);
        
        return [
            'id' => $this->id,
            'page_id' => $this->page_id,
            'revision_note' => $this->revision_note,
            'created_at' => $this->created_at->format('c'),
            'created_by' => [
                'id' => $this->created_by,
                'name' => $author ? $author->display_name : 'Unknown'
            ],
            'is_autosave' => $this->is_autosave,
            'has_changes' => !empty($this->revision_data)
        ];
    }
    
    // Getters
    public function get_id() { return $this->id; }
    public function get_page_id() { return $this->page_id; }
    public function get_revision_data() { return $this->revision_data; }
    public function get_revision_note() { return $this->revision_note; }
    public function get_created_at() { return $this->created_at; }
    public function get_created_by() { return $this->created_by; }
    public function is_autosave() { return $this->is_autosave; }
    
    // Setters
    public function set_revision_note(string $note) { $this->revision_note = $note; }
}