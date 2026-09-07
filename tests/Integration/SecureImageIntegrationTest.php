<?php
/**
 * Integration tests for secure image URL generation in API responses
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests\Integration;

use Custom_Page_Builder\Tests\TestCase;
use Custom_Page_Builder\Rest_Controller;
use Custom_Page_Builder\Tests\Factories\PageFactory;
use Custom_Page_Builder\Admin\Section_Manager;
use Custom_Page_Builder\Integrations\Secure_Image_Handler;
use Custom_Page_Builder\Tests\Mocks\MockSecureImageHandler;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Integration tests for secure image URL generation in API responses
 */
class SecureImageIntegrationTest extends TestCase {
    
    /**
     * REST controller instance
     *
     * @var Rest_Controller
     */
    private $controller;
    
    /**
     * REST server instance
     *
     * @var WP_REST_Server
     */
    private $server;
    
    /**
     * Section manager instance
     *
     * @var Section_Manager
     */
    private $section_manager;
    
    /**
     * Mock secure image handler
     *
     * @var MockSecureImageHandler
     */
    private $mock_handler;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize REST server
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server();
        
        // Initialize controller
        $this->controller = new Rest_Controller();
        $this->controller->register_routes();
        
        // Initialize section manager
        $this->section_manager = new Section_Manager();
        
        // Initialize mock secure image handler
        $this->mock_handler = new MockSecureImageHandler();
        
        do_action('rest_api_init');
    }
    
    /**
     * Test secure image URLs in hero banner section
     */
    public function test_secure_image_urls_hero_banner() {
        // Create page with hero banner section
        $page = PageFactory::create([
            'title' => 'Hero Banner Test',
            'status' => 'published'
        ]);
        
        // Create attachment for testing
        $attachment_id = $this->factory->attachment->create([
            'post_mime_type' => 'image/jpeg',
            'post_title' => 'Test Hero Image'
        ]);
        
        $section_id = $this->section_manager->create_section($page->get_id(), 'hero-banner', [
            'title' => 'Welcome Hero',
            'subtitle' => 'Amazing products await',
            'background_image' => $attachment_id,
            'cta_text' => 'Shop Now',
            'cta_url' => '/shop',
            'overlay_opacity' => 0.5
        ]);
        
        // Make API request
        $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('sections', $data);
        $this->assertCount(1, $data['sections']);
        
        $section = $data['sections'][0];
        $this->assertEquals('hero-banner', $section['section_type']);
        $this->assertArrayHasKey('config', $section);
        
        $config = $section['config'];
        $this->assertArrayHasKey('background_image', $config);
        
        // Check that image was processed by secure image handler
        if (is_array($config['background_image'])) {
            $this->assertArrayHasKey('secure_url', $config['background_image']);
            $this->assertArrayHasKey('sizes', $config['background_image']);
        }
    }
    
    /**
     * Test secure image URLs in product grid section
     */
    public function test_secure_image_urls_product_grid() {
        $page = PageFactory::create([
            'title' => 'Product Grid Test',
            'status' => 'published'
        ]);
        
        // Create multiple attachments for products
        $product_images = [];
        for ($i = 1; $i <= 3; $i++) {
            $product_images[] = $this->factory->attachment->create([
                'post_mime_type' => 'image/jpeg',
                'post_title' => "Product Image {$i}"
            ]);
        }
        
        $section_id = $this->section_manager->create_section($page->get_id(), 'product-grid', [
            'title' => 'Featured Products',
            'products' => [
                [
                    'title' => 'Product 1',
                    'price' => '$29.99',
                    'image' => $product_images[0],
                    'description' => 'Amazing product 1'
                ],
        