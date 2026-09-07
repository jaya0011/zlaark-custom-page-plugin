<?php
/**
 * Unit tests for TestimonialsSection
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests\Unit\Models;

use Custom_Page_Builder\Tests\TestCase;
use Custom_Page_Builder\Models\TestimonialsSection;
use Custom_Page_Builder\Models\SectionFactory;

/**
 * Test TestimonialsSection functionality
 */
class TestimonialsSectionTest extends TestCase {
    
    /**
     * Set up test case
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize section types
        SectionFactory::init_default_types();
    }
    
    /**
     * Test testimonials section type
     */
    public function test_testimonials_section_type() {
        $section = new TestimonialsSection();
        $this->assertEquals('testimonials', $section->get_type());
    }
    
    /**
     * Test testimonials configuration schema
     */
    public function test_testimonials_config_schema() {
        $section = new TestimonialsSection();
        $schema = $section->get_config_schema();
        
        $expected_fields = [
            'title', 'subtitle', 'layout', 'columns', 'testimonials',
            'show_author_images', 'show_ratings', 'background_color',
            'text_color', 'visible'
        ];
        
        foreach ($expected_fields as $field) {
            $this->assertArrayHasKey($field, $schema);
        }
        
        // Test testimonials array structure
        $this->assertEquals('array', $schema['testimonials']['type']);
        $this->assertTrue($schema['testimonials']['required']);
        $this->assertArrayHasKey('items', $schema['testimonials']);
        
        // Test testimonial item properties
        $testimonial_props = $schema['testimonials']['items']['properties'];
        $this->assertArrayHasKey('rating', $testimonial_props);
        $this->assertArrayHasKey('text', $testimonial_props);
        $this->assertArrayHasKey('author_name', $testimonial_props);
    }
    
    /**
     * Test valid testimonials configuration
     */
    public function test_valid_testimonials_config() {
        $section = new TestimonialsSection();
        
        $valid_config = [
            'title' => 'Customer Reviews',
            'layout' => 'grid',
            'columns' => 3,
            'testimonials' => [
                [
                    'rating' => 5,
                    'text' => 'Excellent product!',
                    'author_name' => 'John Doe',
                    'author_title' => 'CEO',
                    'company' => 'Test Corp'
                ],
                [
                    'rating' => 4,
                    'text' => 'Very good service.',
                    'author_name' => 'Jane Smith'
                ]
            ]
        ];
        
        $this->assertTrue($section->validate_config($valid_config));
    }
    
    /**
     * Test invalid testimonials configuration
     */
    public function test_invalid_testimonials_config() {
        $section = new TestimonialsSection();
        
        // Missing testimonials array
        $invalid_config = [
            'title' => 'Customer Reviews',
            'layout' => 'grid'
        ];
        
        $this->assertFalse($section->validate_config($invalid_config));
        
        // Invalid rating
        $invalid_config = [
            'title' => 'Customer Reviews',
            'testimonials' => [
                [
                    'rating' => 6, // Invalid (max 5)
                    'text' => 'Great!',
                    'author_name' => 'John Doe'
                ]
            ]
        ];
        
        $this->assertFalse($section->validate_config($invalid_config));
        
        // Missing required fields
        $invalid_config = [
            'title' => 'Customer Reviews',
            'testimonials' => [
                [
                    'rating' => 5,
                    // Missing text and author_name
                ]
            ]
        ];
        
        $this->assertFalse($section->validate_config($invalid_config));
        
        // Invalid layout
        $invalid_config = [
            'title' => 'Customer Reviews',
            'layout' => 'invalid_layout',
            'testimonials' => [
                [
                    'rating' => 5,
                    'text' => 'Great!',
                    'author_name' => 'John Doe'
                ]
            ]
        ];
        
        $this->assertFalse($section->validate_config($invalid_config));
        
        // Invalid columns
        $invalid_config = [
            'title' => 'Customer Reviews',
            'columns' => 5, // Invalid (max 4)
            'testimonials' => [
                [
                    'rating' => 5,
                    'text' => 'Great!',
                    'author_name' => 'John Doe'
                ]
            ]
        ];
        
        $this->assertFalse($section->validate_config($invalid_config));
    }
    
    /**
     * Test default configuration
     */
    public function test_default_configuration() {
        $section = new TestimonialsSection();
        $default_config = $section->get_default_config();
        
        $this->assertIsArray($default_config);
        $this->assertEquals('What Our Customers Say', $default_config['title']);
        $this->assertEquals('grid', $default_config['layout']);
        $this->assertEquals(3, $default_config['columns']);
        $this->assertTrue($default_config['show_author_images']);
        $this->assertTrue($default_config['show_ratings']);
        $this->assertIsArray($default_config['testimonials']);
        $this->assertCount(1, $default_config['testimonials']);
        
        // Test default testimonial structure
        $default_testimonial = $default_config['testimonials'][0];
        $this->assertEquals(5, $default_testimonial['rating']);
        $this->assertNotEmpty($default_testimonial['text']);
        $this->assertNotEmpty($default_testimonial['author_name']);
    }
    
    /**
     * Test API response format
     */
    public function test_api_response_format() {
        $page = $this->page_factory->create();
        
        $config = [
            'title' => 'API Test Testimonials',
            'testimonials' => [
                [
                    'rating' => 5,
                    'text' => 'Amazing product with <script>alert("xss")</script> great features!',
                    'author_name' => 'John Doe',
                    'author_title' => 'CEO',
                    'company' => 'Test Corp',
                    'author_image' => ''
                ]
            ]
        ];
        
        $section = new TestimonialsSection([
            'page_id' => $page->get_id(),
            'section_type' => 'testimonials',
            'config' => $config
        ]);
        
        $response = $section->to_api_response();
        
        $this->assertEquals('testimonials', $response['type']);
        $this->assertArrayHasKey('testimonials', $response['config']);
        
        $testimonial = $response['config']['testimonials'][0];
        $this->assertEquals(5, $testimonial['rating']);
        $this->assertEquals('John Doe', $testimonial['author_name']);
        $this->assertEquals('CEO', $testimonial['author_title']);
        $this->assertEquals('Test Corp', $testimonial['company']);
        
        // Test XSS protection
        $this->assertStringNotContains('<script>', $testimonial['text']);
        $this->assertStringContains('great features!', $testimonial['text']);
    }
    
    /**
     * Test API response with author images
     */
    public function test_api_response_with_author_images() {
        $page = $this->page_factory->create();
        $attachment_id = $this->create_test_attachment();
        
        $config = [
            'title' => 'Testimonials with Images',
            'testimonials' => [
                [
                    'rating' => 5,
                    'text' => 'Great product!',
                    'author_name' => 'John Doe',
                    'author_image' => $attachment_id
                ]
            ]
        ];
        
        $section = new TestimonialsSection([
            'page_id' => $page->get_id(),
            'section_type' => 'testimonials',
            'config' => $config
        ]);
        
        $response = $section->to_api_response();
        $testimonial = $response['config']['testimonials'][0];
        
        $this->assertArrayHasKey('author_image', $testimonial);
        $this->assertArrayHasKey('author_image_data', $testimonial);
        $this->assertIsArray($testimonial['author_image_data']);
    }
    
    /**
     * Test individual testimonial validation
     */
    public function test_individual_testimonial_validation() {
        $section = new TestimonialsSection();
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($section);
        $method = $reflection->getMethod('validate_testimonial');
        $method->setAccessible(true);
        
        // Valid testimonial
        $valid_testimonial = [
            'rating' => 5,
            'text' => 'Great product!',
            'author_name' => 'John Doe'
        ];
        
        $this->assertTrue($method->invoke($section, $valid_testimonial));
        
        // Invalid testimonial - missing text
        $invalid_testimonial = [
            'rating' => 5,
            'author_name' => 'John Doe'
        ];
        
        $this->assertFalse($method->invoke($section, $invalid_testimonial));
        
        // Invalid testimonial - missing author_name
        $invalid_testimonial = [
            'rating' => 5,
            'text' => 'Great product!'
        ];
        
        $this->assertFalse($method->invoke($section, $invalid_testimonial));
        
        // Invalid testimonial - invalid rating
        $invalid_testimonial = [
            'rating' => 0,
            'text' => 'Great product!',
            'author_name' => 'John Doe'
        ];
        
        $this->assertFalse($method->invoke($section, $invalid_testimonial));
    }
    
    /**
     * Test section creation through factory
     */
    public function test_section_creation_through_factory() {
        $page = $this->page_factory->create();
        
        $section_data = [
            'page_id' => $page->get_id(),
            'section_order' => 1,
            'config' => [
                'title' => 'Factory Test',
                'testimonials' => [
                    [
                        'rating' => 4,
                        'text' => 'Good product!',
                        'author_name' => 'Jane Smith'
                    ]
                ]
            ]
        ];
        
        $section = SectionFactory::create_section('testimonials', $section_data);
        
        $this->assertInstanceOf(TestimonialsSection::class, $section);
        $this->assertEquals('testimonials', $section->get_type());
        $this->assertEquals('Factory Test', $section->get_config()['title']);
    }
    
    /**
     * Test admin template rendering
     */
    public function test_admin_template_rendering() {
        $section = new TestimonialsSection();
        $template_output = $section->render_admin_template();
        
        $this->assertIsString($template_output);
        // Template should contain JavaScript and CSS for admin interface
        $this->assertStringContains('<script', $template_output);
        $this->assertStringContains('<style', $template_output);
    }
}