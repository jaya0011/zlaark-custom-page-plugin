<?php
/**
 * Unit tests for Secure Image Handler
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests\Unit\Integrations;

use Custom_Page_Builder\Tests\TestCase;
use Custom_Page_Builder\Secure_Image_Handler;
use Custom_Page_Builder\Tests\Mocks\MockSecureImageHandler;

/**
 * Test Secure Image Handler functionality
 */
class SecureImageHandlerTest extends TestCase {
    
    /**
     * Secure image handler instance
     *
     * @var Secure_Image_Handler
     */
    private $handler;
    
    /**
     * Mock handler instance
     *
     * @var MockSecureImageHandler
     */
    private $mock_handler;
    
    /**
     * Set up test case
     */
    public function setUp(): void {
        parent::setUp();
        
        $this->handler = new Secure_Image_Handler();
        $this->mock_handler = new MockSecureImageHandler();
    }
    
    /**
     * Test plugin active detection
     */
    public function test_plugin_active_detection() {
        // Since we're in test environment, secure image plugin won't be active
        $this->assertFalse($this->handler->is_secure_image_plugin_active());
        
        // Test mock handler
        $this->assertTrue($this->mock_handler->is_secure_image_plugin_active());
        
        $this->mock_handler->set_plugin_active(false);
        $this->assertFalse($this->mock_handler->is_secure_image_plugin_active());
    }
    
    /**
     * Test processing uploaded image with valid attachment
     */
    public function test_process_uploaded_image_valid() {
        $attachment_id = $this->create_test_attachment();
        
        $result = $this->handler->process_uploaded_image($attachment_id);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($attachment_id, $result['attachment_id']);
        $this->assertArrayHasKey('secure_urls', $result);
        $this->assertArrayHasKey('fallback_urls', $result);
        $this->assertArrayHasKey('is_protected', $result);
        
        // Since plugin is not active in test, should not be protected
        $this->assertFalse($result['is_protected']);
        $this->assertNotEmpty($result['fallback_urls']);
    }
    
    /**
     * Test processing uploaded image with invalid attachment
     */
    public function test_process_uploaded_image_invalid() {
        $result = $this->handler->process_uploaded_image(0);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Invalid attachment ID', $result['error']);
    }
    
    /**
     * Test processing with mock handler (plugin active)
     */
    public function test_process_uploaded_image_with_mock() {
        $attachment_id = $this->create_test_attachment();
        
        $result = $this->mock_handler->process_uploaded_image($attachment_id);
        
        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_protected']);
        $this->assertNotEmpty($result['secure_urls']);
        $this->assertNotEmpty($result['fallback_urls']);
        
        // Test secure URLs format
        $this->assertStringContains('secure-img', $result['secure_urls']['full']);
        $this->assertStringContains((string)$attachment_id, $result['secure_urls']['full']);
    }
    
    /**
     * Test getting secure image URL
     */
    public function test_get_secure_image_url() {
        $attachment_id = $this->create_test_attachment();
        
        // Test with real handler (plugin inactive)
        $url = $this->handler->get_secure_image_url($attachment_id, 'medium');
        $this->assertIsString($url);
        $this->assertNotEmpty($url);
        
        // Test with mock handler (plugin active)
        $secure_url = $this->mock_handler->get_secure_image_url($attachment_id, 'medium');
        $this->assertStringContains('secure-img', $secure_url);
        $this->assertStringContains('medium', $secure_url);
        
        // Test with invalid attachment
        $empty_url = $this->handler->get_secure_image_url(0);
        $this->assertEquals('', $empty_url);
    }
    
    /**
     * Test image size variants
     */
    public function test_get_image_size_variants() {
        $attachment_id = $this->create_test_attachment();
        
        $variants = $this->handler->get_image_size_variants($attachment_id);
        
        $this->assertIsArray($variants);
        
        $expected_sizes = ['thumbnail', 'medium', 'large', 'full'];
        foreach ($expected_sizes as $size) {
            $this->assertArrayHasKey($size, $variants);
            $this->assertArrayHasKey('secure_url', $variants[$size]);
            $this->assertArrayHasKey('fallback_url', $variants[$size]);
            $this->assertArrayHasKey('url', $variants[$size]);
        }
    }
    
    /**
     * Test handling images in section configuration
     */
    public function test_handle_image_in_section() {
        $section_config = [
            'title' => 'Test Section',
            'background_image' => $this->create_test_attachment(),
            'hero_image' => 'https://example.com/image.jpg',
            'testimonials' => [
                [
                    'author_image' => $this->create_test_attachment(),
                    'text' => 'Great product!'
                ]
            ]
        ];
        
        $this->handler->handle_image_in_section($section_config);
        
        // Should process the configuration (exact behavior depends on implementation)
        $this->assertIsArray($section_config);
        
        // Test with mock handler
        $mock_config = ['test' => 'data'];
        $this->mock_handler->handle_image_in_section($mock_config);
        $this->assertTrue($mock_config['_images_processed']);
    }
    
    /**
     * Test image field detection
     */
    public function test_image_field_detection() {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('is_image_field');
        $method->setAccessible(true);
        
        // Test positive cases
        $image_fields = [
            'image',
            'background_image',
            'hero_image',
            'product_image',
            'author_image',
            'thumbnail'
        ];
        
        foreach ($image_fields as $field) {
            $this->assertTrue($method->invoke($this->handler, $field));
        }
        
        // Test negative cases
        $non_image_fields = [
            'title',
            'description',
            'text',
            'url',
            'link'
        ];
        
        foreach ($non_image_fields as $field) {
            $this->assertFalse($method->invoke($this->handler, $field));
        }
    }
    
    /**
     * Test processing section images for API response
     */
    public function test_process_section_images() {
        $section_config = [
            'title' => 'Test Section',
            'image' => $this->create_test_attachment(),
            'description' => 'Test description'
        ];
        
        $processed_config = $this->handler->process_section_images($section_config);
        
        $this->assertIsArray($processed_config);
        $this->assertEquals('Test Section', $processed_config['title']);
        $this->assertEquals('Test description', $processed_config['description']);
        
        // Test with non-array input
        $result = $this->handler->process_section_images('not an array');
        $this->assertEquals('not an array', $result);
    }
    
    /**
     * Test inactive plugin warning
     */
    public function test_inactive_plugin_warning() {
        $warning = $this->handler->get_inactive_plugin_warning();
        
        $this->assertIsString($warning);
        $this->assertNotEmpty($warning);
        $this->assertStringContains('Secure Image plugin', $warning);
    }
    
    /**
     * Test admin notice display
     */
    public function test_display_inactive_plugin_notice() {
        // This method adds an action hook, so we test that it doesn't throw errors
        $this->handler->display_inactive_plugin_notice();
        
        // Check that the action was added
        $this->assertTrue(has_action('admin_notices'));
    }
    
    /**
     * Test image protection status
     */
    public function test_is_image_protected() {
        $attachment_id = $this->create_test_attachment();
        
        // Initially should not be protected
        $this->assertFalse($this->handler->is_image_protected($attachment_id));
        
        // Mark as protected
        update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
        
        // Should now be protected
        $this->assertTrue($this->handler->is_image_protected($attachment_id));
    }
    
    /**
     * Test fallback image URL generation
     */
    public function test_fallback_image_urls() {
        $attachment_id = $this->create_test_attachment();
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('get_fallback_image_url');
        $method->setAccessible(true);
        
        $url = $method->invoke($this->handler, $attachment_id, 'medium');
        $this->assertIsString($url);
        
        // Test with invalid attachment
        $empty_url = $method->invoke($this->handler, 0, 'medium');
        $this->assertEquals('', $empty_url);
    }
    
    /**
     * Test token generation
     */
    public function test_token_generation() {
        $attachment_id = $this->create_test_attachment();
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('generate_image_token');
        $method->setAccessible(true);
        
        $token = $method->invoke($this->handler, $attachment_id, 'full');
        
        if ($token !== false) {
            $this->assertIsString($token);
            $this->assertNotEmpty($token);
            
            // Decode and verify token structure
            $decoded = json_decode(base64_decode($token), true);
            $this->assertIsArray($decoded);
            $this->assertEquals($attachment_id, $decoded['attachment_id']);
            $this->assertEquals('full', $decoded['size']);
            $this->assertArrayHasKey('expires', $decoded);
            $this->assertEquals('custom_page_builder', $decoded['source']);
        }
    }
}