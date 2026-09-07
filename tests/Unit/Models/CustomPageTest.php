<?php
/**
 * Unit tests for CustomPage model
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests\Unit\Models;

use Custom_Page_Builder\Tests\TestCase;
use Custom_Page_Builder\Models\CustomPage;
use DateTime;

/**
 * Test CustomPage model functionality
 */
class CustomPageTest extends TestCase {
    
    /**
     * Test page creation with valid data
     */
    public function test_create_page_with_valid_data() {
        $page_data = [
            'title' => 'Test Page',
            'slug' => 'test-page',
            'status' => 'draft',
            'author_id' => get_current_user_id(),
            'meta_data' => ['key' => 'value']
        ];
        
        $page = CustomPage::create($page_data);
        
        $this->assertInstanceOf(CustomPage::class, $page);
        $this->assertEquals('Test Page', $page->get_title());
        $this->assertEquals('test-page', $page->get_slug());
        $this->assertEquals('draft', $page->get_status());
        $this->assertNotNull($page->get_id());
    }
    
    /**
     * Test page creation fails with invalid data
     */
    public function test_create_page_fails_with_invalid_data() {
        // Test with empty title
        $page_data = [
            'title' => '',
            'author_id' => get_current_user_id()
        ];
        
        $page = CustomPage::create($page_data);
        $this->assertFalse($page);
        
        // Test with invalid status
        $page_data = [
            'title' => 'Test Page',
            'status' => 'invalid_status',
            'author_id' => get_current_user_id()
        ];
        
        $page = CustomPage::create($page_data);
        $this->assertFalse($page);
    }
    
    /**
     * Test automatic slug generation
     */
    public function test_automatic_slug_generation() {
        $page_data = [
            'title' => 'Test Page With Spaces',
            'author_id' => get_current_user_id()
        ];
        
        $page = CustomPage::create($page_data);
        
        $this->assertInstanceOf(CustomPage::class, $page);
        $this->assertEquals('test-page-with-spaces', $page->get_slug());
    }
    
    /**
     * Test unique slug generation
     */
    public function test_unique_slug_generation() {
        // Create first page
        $page1 = $this->page_factory->create([
            'title' => 'Duplicate Title',
            'slug' => 'duplicate-title'
        ]);
        
        // Create second page with same title
        $page2_data = [
            'title' => 'Duplicate Title',
            'author_id' => get_current_user_id()
        ];
        
        $page2 = CustomPage::create($page2_data);
        
        $this->assertInstanceOf(CustomPage::class, $page2);
        $this->assertEquals('duplicate-title-1', $page2->get_slug());
    }
    
    /**
     * Test page validation
     */
    public function test_page_validation() {
        // Valid page
        $valid_page = new CustomPage([
            'title' => 'Valid Page',
            'status' => 'draft',
            'author_id' => get_current_user_id()
        ]);
        
        $this->assertTrue($valid_page->validate());
        
        // Invalid page - empty title
        $invalid_page = new CustomPage([
            'title' => '',
            'status' => 'draft',
            'author_id' => get_current_user_id()
        ]);
        
        $this->assertFalse($invalid_page->validate());
        
        // Invalid page - invalid status
        $invalid_page = new CustomPage([
            'title' => 'Test Page',
            'status' => 'invalid',
            'author_id' => get_current_user_id()
        ]);
        
        $this->assertFalse($invalid_page->validate());
    }
    
    /**
     * Test page status changes
     */
    public function test_page_status_changes() {
        $page = $this->page_factory->create(['status' => 'draft']);
        
        // Test publishing
        $result = $page->change_status('published');
        $this->assertTrue($result);
        $this->assertEquals('published', $page->get_status());
        $this->assertInstanceOf(DateTime::class, $page->get_published_at());
        
        // Test archiving
        $result = $page->change_status('archived');
        $this->assertTrue($result);
        $this->assertEquals('archived', $page->get_status());
        
        // Test invalid status
        $result = $page->change_status('invalid');
        $this->assertFalse($result);
    }
    
    /**
     * Test page scheduling
     */
    public function test_page_scheduling() {
        $page = $this->page_factory->create(['status' => 'draft']);
        
        $future_date = new DateTime('+1 hour');
        $result = $page->schedule_publication($future_date);
        
        $this->assertTrue($result);
        $this->assertEquals('scheduled', $page->get_status());
        $this->assertEquals($future_date->format('Y-m-d H:i:s'), $page->get_scheduled_at()->format('Y-m-d H:i:s'));
        
        // Test scheduling in the past (should fail)
        $past_date = new DateTime('-1 hour');
        $result = $page->schedule_publication($past_date);
        $this->assertFalse($result);
    }
    
    /**
     * Test page duplication
     */
    public function test_page_duplication() {
        $original_page = $this->page_factory->create([
            'title' => 'Original Page',
            'status' => 'published',
            'meta_data' => ['key' => 'value']
        ]);
        
        $duplicated_page = $original_page->duplicate('Duplicated Page');
        
        $this->assertInstanceOf(CustomPage::class, $duplicated_page);
        $this->assertEquals('Duplicated Page', $duplicated_page->get_title());
        $this->assertEquals('draft', $duplicated_page->get_status());
        $this->assertNull($duplicated_page->get_published_at());
        $this->assertEquals($original_page->get_meta_data(), $duplicated_page->get_meta_data());
        $this->assertNotEquals($original_page->get_id(), $duplicated_page->get_id());
    }
    
    /**
     * Test finding pages
     */
    public function test_find_pages() {
        $page = $this->page_factory->create([
            'title' => 'Findable Page',
            'slug' => 'findable-page'
        ]);
        
        // Test find by ID
        $found_by_id = CustomPage::find($page->get_id());
        $this->assertInstanceOf(CustomPage::class, $found_by_id);
        $this->assertEquals($page->get_id(), $found_by_id->get_id());
        
        // Test find by slug
        $found_by_slug = CustomPage::find_by_slug('findable-page');
        $this->assertInstanceOf(CustomPage::class, $found_by_slug);
        $this->assertEquals($page->get_id(), $found_by_slug->get_id());
        
        // Test find non-existent
        $not_found = CustomPage::find(99999);
        $this->assertNull($not_found);
    }
    
    /**
     * Test getting all pages with filtering
     */
    public function test_get_all_pages_with_filtering() {
        // Create test pages
        $this->page_factory->create(['status' => 'draft', 'title' => 'Draft Page']);
        $this->page_factory->create(['status' => 'published', 'title' => 'Published Page']);
        $this->page_factory->create(['status' => 'archived', 'title' => 'Archived Page']);
        
        // Test get all pages
        $all_pages = CustomPage::get_all();
        $this->assertCount(3, $all_pages);
        
        // Test filter by status
        $draft_pages = CustomPage::get_all(['status' => 'draft']);
        $this->assertCount(1, $draft_pages);
        $this->assertEquals('draft', $draft_pages[0]->get_status());
        
        // Test search
        $search_results = CustomPage::get_all(['search' => 'Published']);
        $this->assertCount(1, $search_results);
        $this->assertStringContains('Published', $search_results[0]->get_title());
        
        // Test limit
        $limited_results = CustomPage::get_all(['limit' => 2]);
        $this->assertCount(2, $limited_results);
    }
    
    /**
     * Test page array conversion
     */
    public function test_page_to_array() {
        $page = $this->page_factory->create([
            'title' => 'Array Test Page',
            'meta_data' => ['test' => 'data']
        ]);
        
        $array = $page->to_array();
        
        $expected_keys = [
            'id', 'title', 'slug', 'status', 'created_at', 'updated_at',
            'published_at', 'scheduled_at', 'author_id', 'meta_data', 'sections'
        ];
        
        $this->assertArrayStructure($expected_keys, $array);
        $this->assertEquals('Array Test Page', $array['title']);
        $this->assertEquals(['test' => 'data'], $array['meta_data']);
    }
    
    /**
     * Test page API response format
     */
    public function test_page_to_api_response() {
        $page = $this->page_factory->create([
            'title' => 'API Test Page',
            'slug' => 'api-test-page'
        ]);
        
        $response = $page->to_api_response();
        
        $expected_keys = [
            'id', 'title', 'slug', 'status', 'created_at', 'updated_at',
            'published_at', 'scheduled_at', 'author', 'meta_data', 'sections', 'url'
        ];
        
        $this->assertArrayStructure($expected_keys, $response);
        $this->assertArrayHasKey('id', $response['author']);
        $this->assertArrayHasKey('name', $response['author']);
        $this->assertStringContains('api-test-page', $response['url']);
    }
    
    /**
     * Test page deletion
     */
    public function test_page_deletion() {
        $page = $this->page_factory->create(['title' => 'Delete Me']);
        $page_id = $page->get_id();
        
        $result = $page->delete();
        $this->assertTrue($result);
        
        // Verify page is deleted
        $deleted_page = CustomPage::find($page_id);
        $this->assertNull($deleted_page);
    }
    
    /**
     * Test page save functionality
     */
    public function test_page_save() {
        $page = $this->page_factory->create(['title' => 'Original Title']);
        
        $page->set_title('Updated Title');
        $page->set_meta_data(['updated' => true]);
        
        $result = $page->save();
        $this->assertTrue($result);
        
        // Reload page from database
        $reloaded_page = CustomPage::find($page->get_id());
        $this->assertEquals('Updated Title', $reloaded_page->get_title());
        $this->assertEquals(['updated' => true], $reloaded_page->get_meta_data());
    }
    
    /**
     * Test pages ready for publication
     */
    public function test_get_pages_ready_for_publication() {
        // Create scheduled page in the past (ready for publication)
        $ready_page = $this->page_factory->create([
            'status' => 'scheduled',
            'scheduled_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);
        
        // Create scheduled page in the future (not ready)
        $future_page = $this->page_factory->create([
            'status' => 'scheduled',
            'scheduled_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);
        
        $ready_pages = CustomPage::get_pages_ready_for_publication();
        
        $this->assertCount(1, $ready_pages);
        $this->assertEquals($ready_page->get_id(), $ready_pages[0]->get_id());
    }
}