<?php
/**
 * Unit tests for Section model
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests\Unit\Models;

use Custom_Page_Builder\Tests\TestCase;
use Custom_Page_Builder\Models\Section;
use Custom_Page_Builder\Models\SectionFactory;

/**
 * Test Section model functionality
 */
class SectionTest extends TestCase {
    
    /**
     * Test section creation with valid data
     */
    public function test_create_section_with_valid_data() {
        $page = $this->page_factory->create();
        
        $section_data = [
            'page_id' => $page->get_id(),
            'section_type' => 'testimonials',
            'section_order' => 1,
            'config' => [
                'title' => 'Test Section',
                'visible' => true
            ]
        ];
        
        $section = Section::create($section_data);
        
        $this->assertInstanceOf(Section::class, $section);
        $this->assertEquals($page->get_id(), $section->get_page_id());
        $this->assertEquals('testimonials', $section->get_section_type());
        $this->assertEquals(1, $section->get_section_order());
        $this->assertNotNull($section->get_id());
    }
    
    /**
     * Test section creation fails with invalid data
     */
    public function test_create_section_fails_with_invalid_data() {
        // Test with missing page_id
        $section_data = [
            'section_type' => 'testimonials',
            'config' => []
        ];
        
        $section = Section::create($section_data);
        $this->assertFalse($section);
        
        // Test with empty section_type
        $section_data = [
            'page_id' => 1,
            'section_type' => '',
            'config' => []
        ];
        
        $section = Section::create($section_data);
        $this->assertFalse($section);
    }
    
    /**
     * Test section validation
     */
    public function test_section_validation() {
        $page = $this->page_factory->create();
        
        // Valid section
        $valid_section = new Section([
            'page_id' => $page->get_id(),
            'section_type' => 'testimonials',
            'config' => ['title' => 'Valid Section']
        ]);
        
        $this->assertTrue($valid_section->validate());
        
        // Invalid section - missing page_id
        $invalid_section = new Section([
            'section_type' => 'testimonials',
            'config' => []
        ]);
        
        $this->assertFalse($invalid_section->validate());
        
        // Invalid section - empty section_type
        $invalid_section = new Section([
            'page_id' => $page->get_id(),
            'section_type' => '',
            'config' => []
        ]);
        
        $this->assertFalse($invalid_section->validate());
    }
    
    /**
     * Test section configuration validation
     */
    public function test_section_config_validation() {
        $section = new Section();
        
        // Valid config
        $valid_config = [
            'title' => 'Test Title',
            'visible' => true
        ];
        
        $this->assertTrue($section->validate_config($valid_config));
        
        // Invalid config - wrong type
        $invalid_config = [
            'title' => 123, // Should be string
            'visible' => 'yes' // Should be boolean
        ];
        
        $this->assertFalse($section->validate_config($invalid_config));
    }
    
    /**
     * Test getting sections by page ID
     */
    public function test_get_sections_by_page_id() {
        $page = $this->page_factory->create();
        
        // Create multiple sections for the page
        $section1 = $this->section_factory->create('testimonials', [
            'page_id' => $page->get_id(),
            'section_order' => 1
        ]);
        
        $section2 = $this->section_factory->create('hero_banner', [
            'page_id' => $page->get_id(),
            'section_order' => 2
        ]);
        
        $sections = Section::get_by_page_id($page->get_id());
        
        $this->assertCount(2, $sections);
        $this->assertEquals(1, $sections[0]->get_section_order());
        $this->assertEquals(2, $sections[1]->get_section_order());
    }
    
    /**
     * Test section duplication
     */
    public function test_section_duplication() {
        $page1 = $this->page_factory->create();
        $page2 = $this->page_factory->create();
        
        $original_section = $this->section_factory->create('testimonials', [
            'page_id' => $page1->get_id(),
            'config' => ['title' => 'Original Section']
        ]);
        
        // Duplicate to same page
        $duplicated_section = $original_section->duplicate();
        $this->assertInstanceOf(Section::class, $duplicated_section);
        $this->assertEquals($page1->get_id(), $duplicated_section->get_page_id());
        $this->assertNotEquals($original_section->get_id(), $duplicated_section->get_id());
        
        // Duplicate to different page
        $moved_section = $original_section->duplicate($page2->get_id());
        $this->assertEquals($page2->get_id(), $moved_section->get_page_id());
    }
    
    /**
     * Test section save functionality
     */
    public function test_section_save() {
        $page = $this->page_factory->create();
        $section = $this->section_factory->create('testimonials', [
            'page_id' => $page->get_id()
        ]);
        
        $section->set_config(['title' => 'Updated Title']);
        $section->set_section_order(5);
        
        $result = $section->save();
        $this->assertTrue($result);
        
        // Reload section from database
        $reloaded_section = Section::find($section->get_id());
        $this->assertEquals(['title' => 'Updated Title'], $reloaded_section->get_config());
        $this->assertEquals(5, $reloaded_section->get_section_order());
    }
    
    /**
     * Test section deletion
     */
    public function test_section_deletion() {
        $page = $this->page_factory->create();
        $section = $this->section_factory->create('testimonials', [
            'page_id' => $page->get_id()
        ]);
        
        $section_id = $section->get_id();
        
        $result = $section->delete();
        $this->assertTrue($result);
        
        // Verify section is deleted
        $deleted_section = Section::find($section_id);
        $this->assertNull($deleted_section);
    }
    
    /**
     * Test section array conversion
     */
    public function test_section_to_array() {
        $page = $this->page_factory->create();
        $section = $this->section_factory->create('testimonials', [
            'page_id' => $page->get_id(),
            'config' => ['title' => 'Array Test']
        ]);
        
        $array = $section->to_array();
        
        $expected_keys = [
            'id', 'page_id', 'section_type', 'section_order',
            'config', 'created_at', 'updated_at'
        ];
        
        $this->assertArrayStructure($expected_keys, $array);
        $this->assertEquals('testimonials', $array['section_type']);
        $this->assertEquals(['title' => 'Array Test'], $array['config']);
    }
    
    /**
     * Test section API response format
     */
    public function test_section_to_api_response() {
        $page = $this->page_factory->create();
        $section = $this->section_factory->create('testimonials', [
            'page_id' => $page->get_id(),
            'section_order' => 3,
            'config' => ['title' => 'API Test']
        ]);
        
        $response = $section->to_api_response();
        
        $expected_keys = ['id', 'type', 'order', 'config'];
        
        $this->assertArrayStructure($expected_keys, $response);
        $this->assertEquals('testimonials', $response['type']);
        $this->assertEquals(3, $response['order']);
        $this->assertEquals(['title' => 'API Test'], $response['config']);
    }
    
    /**
     * Test section configuration schema
     */
    public function test_section_config_schema() {
        $section = new Section();
        $schema = $section->get_config_schema();
        
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('title', $schema);
        $this->assertArrayHasKey('visible', $schema);
        
        // Test schema structure
        $this->assertEquals('string', $schema['title']['type']);
        $this->assertEquals('boolean', $schema['visible']['type']);
    }
    
    /**
     * Test default configuration
     */
    public function test_default_configuration() {
        $section = new Section();
        $default_config = $section->get_default_config();
        
        $this->assertIsArray($default_config);
        $this->assertArrayHasKey('title', $default_config);
        $this->assertArrayHasKey('visible', $default_config);
        $this->assertEquals('', $default_config['title']);
        $this->assertTrue($default_config['visible']);
    }
    
    /**
     * Test image field processing
     */
    public function test_image_field_processing() {
        $attachment_id = $this->create_test_attachment();
        $section = new Section();
        
        // Test with attachment ID
        $image_data = $section->process_image_field($attachment_id);
        
        $this->assertIsArray($image_data);
        $this->assertArrayHasKey('url', $image_data);
        $this->assertArrayHasKey('secure_url', $image_data);
        $this->assertArrayHasKey('fallback_url', $image_data);
        $this->assertArrayHasKey('is_protected', $image_data);
        
        // Test with URL string
        $image_data = $section->process_image_field('https://example.com/image.jpg');
        $this->assertEquals('https://example.com/image.jpg', $image_data['url']);
        $this->assertFalse($image_data['is_protected']);
        
        // Test with empty value
        $image_data = $section->process_image_field('');
        $this->assertEquals('', $image_data['url']);
    }
}