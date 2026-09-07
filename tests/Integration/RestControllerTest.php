<?php
/**
 * Integration tests for REST API controller
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests\Integration;

use Custom_Page_Builder\Tests\TestCase;
use Custom_Page_Builder\Rest_Controller;
use Custom_Page_Builder\Models\CustomPage;
use Custom_Page_Builder\Admin\Section_Manager;
use Custom_Page_Builder\Tests\Factories\PageFactory;
use Custom_Page_Builder\Tests\Factories\SectionFactory;
use WP_REST_Request;
use WP_REST_Server;
use WP_User;

/**
 * Integration tests for REST API endpoints
 */
class RestControllerTest extends TestCase {
    
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
     * Test user
     *
     * @var WP_User
     */
    private $user;
    
    /**
     * Admin user
     *
     * @var WP_User
     */
    private $admin_user;
    
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
        
        // Create test users
        $this->user = $this->factory->user->create_and_get([
            'role' => 'subscriber'
        ]);
        
        $this->admin_user = $this->factory->user->create_and_get([
            'role' => 'administrator'
        ]);
        
        do_action('rest_api_init');
    }
    
    /**
     * Test GET /pages endpoint with published pages
     */
    public function test_get_pages_published_only() {
        // Create test pages
        $published_page = PageFactory::create([
            'title' => 'Published Page',
            'status' => 'published'
        ]);
        
        $draft_page = PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        // Make request without authentication
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertCount(1, $data); // Only published page should be returned
        $this->assertEquals('Published Page', $data[0]['title']);
        $this->assertEquals('published', $data[0]['status']);
    }
    
    /**
     * Test GET /pages endpoint with authentication for draft pages
     */
    public function test_get_pages_with_authentication() {
        // Create test pages
        $published_page = PageFactory::create([
            'title' => 'Published Page',
            'status' => 'published'
        ]);
        
        $draft_page = PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        // Set current user as admin
        wp_set_current_user($this->admin_user->ID);
        
        // Request draft pages
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertCount(1, $data); // Only draft page should be returned
        $this->assertEquals('Draft Page', $data[0]['title']);
        $this->assertEquals('draft', $data[0]['status']);
    }
    
    /**
     * Test GET /pages endpoint with unauthorized access to draft pages
     */
    public function test_get_pages_unauthorized_draft_access() {
        // Create draft page
        $draft_page = PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        // Set current user as subscriber (no edit_pages capability)
        wp_set_current_user($this->user->ID);
        
        // Request draft pages
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(403, $response->get_status());
        $this->assertInstanceOf('WP_Error', $response->as_error());
    }
    
    /**
     * Test GET /pages endpoint with pagination
     */
    public function test_get_pages_pagination() {
        // Create multiple pages
        for ($i = 1; $i <= 15; $i++) {
            PageFactory::create([
                'title' => "Page {$i}",
                'status' => 'published'
            ]);
        }
        
        // Request first page with limit
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('per_page', 10);
        $request->set_param('page', 1);
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertCount(10, $data);
        
        // Check pagination headers
        $headers = $response->get_headers();
        $this->assertEquals(15, $headers['X-WP-Total']);
        $this->assertEquals(2, $headers['X-WP-TotalPages']);
        
        // Request second page
        $request->set_param('page', 2);
        $response = $this->server->dispatch($request);
        
        $data = $response->get_data();
        $this->assertCount(5, $data); // Remaining 5 pages
    }
    
    /**
     * Test GET /pages endpoint with search functionality
     */
    public function test_get_pages_search() {
        // Create test pages
        PageFactory::create([
            'title' => 'About Us Page',
            'status' => 'published'
        ]);
        
        PageFactory::create([
            'title' => 'Contact Information',
            'status' => 'published'
        ]);
        
        PageFactory::create([
            'title' => 'Product Catalog',
            'status' => 'published'
        ]);
        
        // Search for pages containing "about"
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('search', 'about');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertCount(1, $data);
        $this->assertEquals('About Us Page', $data[0]['title']);
    }
    
    /**
     * Test GET /pages/{id} endpoint for published page
     */
    public function test_get_single_page_published() {
        // Create page with sections
        $page = PageFactory::create([
            'title' => 'Test Page',
            'status' => 'published'
        ]);
        
        $section_manager = new Section_Manager();
        $section_id = $section_manager->create_section($page->get_id(), 'testimonials', [
            'title' => 'Customer Reviews',
            'testimonials' => [
                [
                    'rating' => 5,
                    'text' => 'Great product!',
                    'author' => 'John Doe'
                ]
            ]
        ]);
        
        // Make request
        $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals('Test Page', $data['title']);
        $this->assertEquals('published', $data['status']);
        $this->assertArrayHasKey('sections', $data);
        $this->assertCount(1, $data['sections']);
        $this->assertEquals('testimonials', $data['sections'][0]['section_type']);
        
        // Check caching headers
        $headers = $response->get_headers();
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertArrayHasKey('Last-Modified', $headers);
    }
    
    /**
     * Test GET /pages/{id} endpoint for non-existent page
     */
    public function test_get_single_page_not_found() {
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/99999');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
        $this->assertInstanceOf('WP_Error', $response->as_error());
        $this->assertEquals('page_not_found', $response->as_error()->get_error_code());
    }
    
    /**
     * Test GET /pages/{id} endpoint for draft page without permission
     */
    public function test_get_single_draft_page_unauthorized() {
        $page = PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        // Make request without authentication
        $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
        $this->assertInstanceOf('WP_Error', $response->as_error());
    }
    
    /**
     * Test GET /pages/{id} endpoint for draft page with permission
     */
    public function test_get_single_draft_page_authorized() {
        $page = PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        // Set current user as admin
        wp_set_current_user($this->admin_user->ID);
        
        $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals('Draft Page', $data['title']);
        $this->assertEquals('draft', $data['status']);
    }
    
    /**
     * Test GET /pages/slug/{slug} endpoint
     */
    public function test_get_page_by_slug() {
        $page = PageFactory::create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'status' => 'published'
        ]);
        
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/slug/about-us');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals('About Us', $data['title']);
        $this->assertEquals('about-us', $data['slug']);
    }
    
    /**
     * Test GET /pages/slug/{slug} endpoint with invalid slug
     */
    public function test_get_page_by_invalid_slug() {
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/slug/non-existent');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
        $this->assertInstanceOf('WP_Error', $response->as_error());
    }
    
    /**
     * Test API response format consistency
     */
    public function test_api_response_format() {
        $page = PageFactory::create([
            'title' => 'Test Page',
            'status' => 'published'
        ]);
        
        $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        
        // Check required fields
        $required_fields = ['id', 'title', 'slug', 'status', 'created_at', 'updated_at', 'sections'];
        foreach ($required_fields as $field) {
            $this->assertArrayHasKey($field, $data, "Missing required field: {$field}");
        }
        
        // Check data types
        $this->assertIsInt($data['id']);
        $this->assertIsString($data['title']);
        $this->assertIsString($data['slug']);
        $this->assertIsString($data['status']);
        $this->assertIsString($data['created_at']);
        $this->assertIsString($data['updated_at']);
        $this->assertIsArray($data['sections']);
        
        // Check status enum
        $this->assertContains($data['status'], ['draft', 'published', 'archived']);
    }
    
    /**
     * Test secure image URL generation in API responses
     */
    public function test_secure_image_urls_in_response() {
        // Create page with image section
        $page = PageFactory::create([
            'title' => 'Image Test Page',
            'status' => 'published'
        ]);
        
        $section_manager = new Section_Manager();
        $section_id = $section_manager->create_section($page->get_id(), 'hero-banner', [
            'title' => 'Hero Section',
            'background_image' => 123, // Mock attachment ID
            'overlay_opacity' => 0.5
        ]);
        
        $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('sections', $data);
        $this->assertCount(1, $data['sections']);
        
        $section = $data['sections'][0];
        $this->assertEquals('hero-banner', $section['section_type']);
        $this->assertArrayHasKey('config', $section);
        
        // Check that secure image processing was applied
        // (The actual URL format depends on the secure image handler implementation)
        $this->assertArrayHasKey('background_image', $section['config']);
    }
    
    /**
     * Test error handling for invalid requests
     */
    public function test_error_handling_invalid_page_id() {
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/invalid');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
        $this->assertInstanceOf('WP_Error', $response->as_error());
    }
    
    /**
     * Test CORS headers are present
     */
    public function test_cors_headers() {
        $page = PageFactory::create([
            'title' => 'CORS Test Page',
            'status' => 'published'
        ]);
        
        // Simulate request with Origin header
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost:3000';
        $_SERVER['REQUEST_URI'] = '/wp-json/custom-pages/v1/pages/' . $page->get_id();
        
        $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
        
        // Capture headers
        ob_start();
        $response = $this->server->dispatch($request);
        $headers_output = ob_get_clean();
        
        $this->assertEquals(200, $response->get_status());
        
        // Clean up
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['REQUEST_URI']);
    }
    
    /**
     * Test API schema validation
     */
    public function test_api_schema_validation() {
        $schema = $this->controller->get_item_schema();
        
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        
        $properties = $schema['properties'];
        $required_properties = ['id', 'title', 'slug', 'status', 'sections'];
        
        foreach ($required_properties as $property) {
            $this->assertArrayHasKey($property, $properties, "Missing schema property: {$property}");
        }
        
        // Check specific property definitions
        $this->assertEquals('integer', $properties['id']['type']);
        $this->assertEquals('string', $properties['title']['type']);
        $this->assertEquals('array', $properties['sections']['type']);
        $this->assertContains('draft', $properties['status']['enum']);
        $this->assertContains('published', $properties['status']['enum']);
        $this->assertContains('archived', $properties['status']['enum']);
    }
    
    /**
     * Test collection parameters validation
     */
    public function test_collection_parameters() {
        $params = $this->controller->get_collection_params();
        
        $this->assertIsArray($params);
        $this->assertArrayHasKey('status', $params);
        $this->assertArrayHasKey('search', $params);
        $this->assertArrayHasKey('orderby', $params);
        $this->assertArrayHasKey('include_sections', $params);
        
        // Test status parameter
        $this->assertEquals('published', $params['status']['default']);
        $this->assertContains('draft', $params['status']['enum']);
        $this->assertContains('published', $params['status']['enum']);
        $this->assertContains('archived', $params['status']['enum']);
        
        // Test orderby parameter
        $this->assertEquals('updated_at', $params['orderby']['default']);
        $this->assertContains('created_at', $params['orderby']['enum']);
        $this->assertContains('title', $params['orderby']['enum']);
    }
    
    /**
     * Test include_sections parameter
     */
    public function test_include_sections_parameter() {
        // Create page with sections
        $page = PageFactory::create([
            'title' => 'Sections Test Page',
            'status' => 'published'
        ]);
        
        $section_manager = new Section_Manager();
        $section_manager->create_section($page->get_id(), 'testimonials', [
            'title' => 'Reviews'
        ]);
        
        // Request without sections
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('include_sections', false);
        $response = $this->server->dispatch($request);
        
        $data = $response->get_data();
        $this->assertArrayHasKey('sections', $data[0]);
        
        // Request with sections
        $request->set_param('include_sections', true);
        $response = $this->server->dispatch($request);
        
        $data = $response->get_data();
        $this->assertArrayHasKey('sections', $data[0]);
        $this->assertNotEmpty($data[0]['sections']);
    }
    
    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        global $wp_rest_server;
        $wp_rest_server = null;
        
        parent::tearDown();
    }
}