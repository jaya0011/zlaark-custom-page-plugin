<?php
/**
 * Base test case class for Custom Page Builder tests
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests;

use WP_UnitTestCase;
use Custom_Page_Builder\Installer;
use Custom_Page_Builder\Tests\Factories\PageFactory;
use Custom_Page_Builder\Tests\Factories\SectionFactory;

/**
 * Base test case class
 */
class TestCase extends WP_UnitTestCase {
    
    /**
     * Page factory instance
     *
     * @var PageFactory
     */
    protected $page_factory;
    
    /**
     * Section factory instance
     *
     * @var SectionFactory
     */
    protected $section_factory;
    
    /**
     * Set up test case
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create database tables
        $this->create_test_tables();
        
        // Initialize factories
        $this->page_factory = new PageFactory();
        $this->section_factory = new SectionFactory();
        
        // Create test user
        $this->create_test_user();
    }
    
    /**
     * Tear down test case
     */
    public function tearDown(): void {
        // Clean up test data
        $this->clean_test_data();
        
        parent::tearDown();
    }
    
    /**
     * Create database tables for testing
     */
    protected function create_test_tables() {
        global $wpdb;
        
        // Create pages table
        $pages_table = $wpdb->prefix . 'custom_pages';
        $wpdb->query("DROP TABLE IF EXISTS {$pages_table}");
        
        $sql = "CREATE TABLE {$pages_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL UNIQUE,
            status enum('draft', 'published', 'archived', 'scheduled') DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            published_at datetime NULL,
            scheduled_at datetime NULL,
            author_id bigint(20) NOT NULL,
            meta_data longtext,
            PRIMARY KEY (id),
            KEY idx_slug (slug),
            KEY idx_status (status),
            KEY idx_author (author_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $wpdb->query($sql);
        
        // Create sections table
        $sections_table = $wpdb->prefix . 'custom_page_sections';
        $wpdb->query("DROP TABLE IF EXISTS {$sections_table}");
        
        $sql = "CREATE TABLE {$sections_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            page_id bigint(20) NOT NULL,
            section_type varchar(50) NOT NULL,
            section_order int(11) NOT NULL DEFAULT 0,
            config longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_page_id (page_id),
            KEY idx_page_order (page_id, section_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $wpdb->query($sql);
        
        // Create revisions table
        $revisions_table = $wpdb->prefix . 'custom_page_revisions';
        $wpdb->query("DROP TABLE IF EXISTS {$revisions_table}");
        
        $sql = "CREATE TABLE {$revisions_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            page_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            meta_data longtext,
            revision_note text,
            is_autosave tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) NOT NULL,
            PRIMARY KEY (id),
            KEY idx_page_id (page_id),
            KEY idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $wpdb->query($sql);
    }
    
    /**
     * Create test user
     */
    protected function create_test_user() {
        $user_id = $this->factory->user->create([
            'role' => 'administrator',
            'user_login' => 'test_admin',
            'user_email' => 'admin@test.com'
        ]);
        
        wp_set_current_user($user_id);
    }
    
    /**
     * Clean up test data
     */
    protected function clean_test_data() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'custom_pages',
            $wpdb->prefix . 'custom_page_sections',
            $wpdb->prefix . 'custom_page_revisions'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE {$table}");
        }
    }
    
    /**
     * Assert that an array has the expected structure
     *
     * @param array $expected_keys Expected keys
     * @param array $actual_array Actual array
     * @param string $message Optional message
     */
    protected function assertArrayStructure(array $expected_keys, array $actual_array, string $message = '') {
        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey($key, $actual_array, $message . " - Missing key: {$key}");
        }
    }
    
    /**
     * Assert that a validation error occurred
     *
     * @param callable $callback Callback that should trigger validation error
     * @param string $message Optional message
     */
    protected function assertValidationError(callable $callback, string $message = '') {
        $error_occurred = false;
        
        try {
            $callback();
        } catch (\Custom_Page_Builder\Exceptions\ValidationException $e) {
            $error_occurred = true;
        }
        
        $this->assertTrue($error_occurred, $message ?: 'Expected validation error did not occur');
    }
    
    /**
     * Create a test attachment
     *
     * @param array $args Attachment arguments
     * @return int Attachment ID
     */
    protected function create_test_attachment(array $args = []) {
        $defaults = [
            'post_title' => 'Test Image',
            'post_content' => 'Test image description',
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => 'image/jpeg'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        return $this->factory->attachment->create($args);
    }
}