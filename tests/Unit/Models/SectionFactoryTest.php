<?php
/**
 * Unit tests for SectionFactory
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests\Unit\Models;

use Custom_Page_Builder\Tests\TestCase;
use Custom_Page_Builder\Models\SectionFactory;
use Custom_Page_Builder\Models\Section;
use Custom_Page_Builder\Models\TestimonialsSection;

/**
 * Test SectionFactory functionality
 */
class SectionFactoryTest extends TestCase {
    
    /**
     * Set up test case
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize default section types
        SectionFactory::init_default_types();
    }
    
    /**
     * Test section type registration
     */
    public function test_section_type_registration() {
        // Clear existing types
        SectionFactory::clear_all_types();
        
        // Register a custom type
        SectionFactory::register_section_type('custom_type', 'Custom_Page_Builder\\Models\\Section');
        
        $registered_types = SectionFactory::get_registered_types();
        $this->assertContains('custom_type', $registered_types);
        
        $this->assertTrue(SectionFactory::is_type_registered('custom_type'));
        $this->assertFalse(SectionFactory::is_type_registered('non_existent_type'));
    }
    
    /**
     * Test section creation with registered type
     */
    public function test_create_section_with_registered_type() {
        $page = $this->page_factory->create();
        
        $section_data = [
            'page_id' => $page->get_id(),
            'section_order' => 1,
            'config' => ['title' => 'Test Testimonials']
        ];
        
        $section = SectionFactory::create_section('testimonials', $section_data);
        
        $this->assertInstanceOf(TestimonialsSection::class, $section);
        $this->assertEquals('testimonials', $section->get_type());
        $this->assertEquals($page->get_id(), $section->get_page_id());
    }
    
    /**
     * Test section creation with unregistered type falls back to base Section
     */
    public function test_create_section_with_unregistered_type() {
        $page = $this->page_factory->create();
        
        $section_data = [
            'page_id' => $page->get_id(),
            'section_order' => 1,
            'config' => ['title' => 'Test Section']
        ];
        
        $section = SectionFactory::create_section('unregistered_type', $section_data);
        
        $this->assertInstanceOf(Section::class, $section);
        $this->assertEquals('unregistered_type', $section->get_section_type());
    }
    
    /**
     * Test creating section from database data
     */
    public function test_create_from_database() {
        $database_data = [
            'id' => 1,
            'page_id' => 1,
            'section_type' => 'testimonials',
            'section_order' => 1,
            'config' => json_encode(['title' => 'DB Test']),
            'created_at' => '2023-01-01 12:00:00',
            'updated_at' => '2023-01-01 12:00:00'
        ];
        
        $section = SectionFactory::create_from_database($database_data);
        
        $this->assertInstanceOf(TestimonialsSection::class, $section);
        $this->assertEquals(1, $section->get_id());
        $this->assertEquals('testimonials', $section->get_section_type());
    }
    
    /**
     * Test getting type schema
     */
    public function test_get_type_schema() {
        $schema = SectionFactory::get_type_schema('testimonials');
        
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('title', $schema);
        $this->assertArrayHasKey('testimonials', $schema);
        $this->assertArrayHasKey('layout', $schema);
        
        // Test non-existent type
        $schema = SectionFactory::get_type_schema('non_existent');
        $this->assertNull($schema);
    }
    
    /**
     * Test getting default configuration
     */
    public function test_get_default_config() {
        $config = SectionFactory::get_default_config('testimonials');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('title', $config);
        $this->assertArrayHasKey('testimonials', $config);
        $this->assertEquals('What Our Customers Say', $config['title']);
        
        // Test non-existent type
        $config = SectionFactory::get_default_config('non_existent');
        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }
    
    /**
     * Test configuration validation
     */
    public function test_validate_config() {
        // Valid testimonials config
        $valid_config = [
            'title' => 'Test Testimonials',
            'testimonials' => [
                [
                    'rating' => 5,
                    'text' => 'Great product!',
                    'author_name' => 'John Doe'
                ]
            ]
        ];
        
        $result = SectionFactory::validate_config('testimonials', $valid_config);
        $this->assertTrue($result);
        
        // Invalid testimonials config
        $invalid_config = [
            'title' => 'Test Testimonials',
            'testimonials' => [
                [
                    'rating' => 6, // Invalid rating (max 5)
                    'text' => 'Great product!',
                    'author_name' => 'John Doe'
                ]
            ]
        ];
        
        $result = SectionFactory::validate_config('testimonials', $invalid_config);
        $this->assertFalse($result);
        
        // Test non-existent type
        $result = SectionFactory::validate_config('non_existent', []);
        $this->assertFalse($result);
    }
    
    /**
     * Test getting available types with metadata
     */
    public function test_get_available_types() {
        $available_types = SectionFactory::get_available_types();
        
        $this->assertIsArray($available_types);
        $this->assertArrayHasKey('testimonials', $available_types);
        
        $testimonials_type = $available_types['testimonials'];
        $this->assertEquals('testimonials', $testimonials_type['type']);
        $this->assertArrayHasKey('schema', $testimonials_type);
        $this->assertArrayHasKey('default_config', $testimonials_type);
        $this->assertArrayHasKey('class', $testimonials_type);
    }
    
    /**
     * Test unregistering section type
     */
    public function test_unregister_section_type() {
        // Ensure type is registered
        $this->assertTrue(SectionFactory::is_type_registered('testimonials'));
        
        // Unregister the type
        SectionFactory::unregister_section_type('testimonials');
        
        // Verify it's no longer registered
        $this->assertFalse(SectionFactory::is_type_registered('testimonials'));
    }
    
    /**
     * Test clearing all types
     */
    public function test_clear_all_types() {
        // Ensure we have registered types
        $types_before = SectionFactory::get_registered_types();
        $this->assertNotEmpty($types_before);
        
        // Clear all types
        SectionFactory::clear_all_types();
        
        // Verify all types are cleared
        $types_after = SectionFactory::get_registered_types();
        $this->assertEmpty($types_after);
    }
    
    /**
     * Test default types initialization
     */
    public function test_init_default_types() {
        // Clear all types first
        SectionFactory::clear_all_types();
        $this->assertEmpty(SectionFactory::get_registered_types());
        
        // Initialize default types
        SectionFactory::init_default_types();
        
        $registered_types = SectionFactory::get_registered_types();
        
        $expected_types = [
            'testimonials',
            'product_grid',
            'hero_banner',
            'category_showcase',
            'content_block'
        ];
        
        foreach ($expected_types as $type) {
            $this->assertContains($type, $registered_types);
        }
    }
    
    /**
     * Test section creation with invalid class
     */
    public function test_create_section_with_invalid_class() {
        // Register a type with non-existent class
        SectionFactory::register_section_type('invalid_class', 'NonExistentClass');
        
        $page = $this->page_factory->create();
        $section_data = [
            'page_id' => $page->get_id(),
            'config' => []
        ];
        
        // Should fall back to base Section class
        $section = SectionFactory::create_section('invalid_class', $section_data);
        
        $this->assertInstanceOf(Section::class, $section);
        $this->assertEquals('invalid_class', $section->get_section_type());
    }
}