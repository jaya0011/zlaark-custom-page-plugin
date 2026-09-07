<?php
/**
 * Integration tests for API authentication and permissions
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests\Integration;

use Custom_Page_Builder\Tests\TestCase;
use Custom_Page_Builder\Rest_Controller;
use Custom_Page_Builder\Tests\Factories\PageFactory;
use WP_REST_Request;
use WP_REST_Server;
use WP_User;

/**
 * Integration tests for authentication and permission checks
 */
class AuthenticationTest extends TestCase {
    
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
     * Test users with different roles
     *
     * @var array
     */
    private $users = [];
    
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
        
        // Create test users with different roles
        $this->users['subscriber'] = $this->factory->user->create_and_get([
            'role' => 'subscriber'
        ]);
        
        $this->users['contributor'] = $this->factory->user->create_and_get([
            'role' => 'contributor'
        ]);
        
        $this->users['author'] = $this->factory->user->create_and_get([
            'role' => 'author'
        ]);
        
        $this->users['editor'] = $this->factory->user->create_and_get([
            'role' => 'editor'
        ]);
        
        $this->users['administrator'] = $this->factory->user->create_and_get([
            'role' => 'administrator'
        ]);
        
        do_action('rest_api_init');
    }
    
    /**
     * Test public access to published pages
     */
    public function test_public_access_published_pages() {
        $page = PageFactory::create([
            'title' => 'Public Page',
            'status' => 'published'
        ]);
        
        // Ensure no user is logged in
        wp_set_current_user(0);
        
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertCount(1, $data);
        $this->assertEquals('Public Page', $data[0]['title']);
    }
    
    /**
     * Test public access denied to draft pages
     */
    public function test_public_access_denied_draft_pages() {
        PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        // Ensure no user is logged in
        wp_set_current_user(0);
        
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(403, $response->get_status());
        $this->assertInstanceOf('WP_Error', $response->as_error());
    }
    
    /**
     * Test subscriber role permissions
     */
    public function test_subscriber_permissions() {
        $page = PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        wp_set_current_user($this->users['subscriber']->ID);
        
        // Test access to draft pages list - should be denied
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(403, $response->get_status());
        
        // Test access to individual draft page - should be denied
        $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status()); // Returns 404 to hide existence
    }
    
    /**
     * Test contributor role permissions
     */
    public function test_contributor_permissions() {
        $page = PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        wp_set_current_user($this->users['contributor']->ID);
        
        // Contributors don't have edit_pages capability by default
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(403, $response->get_status());
    }
    
    /**
     * Test author role permissions
     */
    public function test_author_permissions() {
        $page = PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        wp_set_current_user($this->users['author']->ID);
        
        // Authors don't have edit_pages capability by default (only edit_posts)
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(403, $response->get_status());
    }
    
    /**
     * Test editor role permissions
     */
    public function test_editor_permissions() {
        $page = PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        wp_set_current_user($this->users['editor']->ID);
        
        // Editors have edit_pages capability
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertCount(1, $data);
        $this->assertEquals('Draft Page', $data[0]['title']);
        
        // Test access to individual draft page
        $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
    }
    
    /**
     * Test administrator role permissions
     */
    public function test_administrator_permissions() {
        $page = PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        wp_set_current_user($this->users['administrator']->ID);
        
        // Administrators have all capabilities
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertCount(1, $data);
        $this->assertEquals('Draft Page', $data[0]['title']);
        
        // Test access to individual draft page
        $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
    }
    
    /**
     * Test permission check methods directly
     */
    public function test_permission_check_methods() {
        // Test get_items_permissions_check for published pages
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'published');
        
        wp_set_current_user(0); // No user logged in
        $result = $this->controller->get_items_permissions_check($request);
        $this->assertTrue($result);
        
        // Test get_items_permissions_check for draft pages without permission
        $request->set_param('status', 'draft');
        $result = $this->controller->get_items_permissions_check($request);
        $this->assertInstanceOf('WP_Error', $result);
        
        // Test get_items_permissions_check for draft pages with permission
        wp_set_current_user($this->users['administrator']->ID);
        $result = $this->controller->get_items_permissions_check($request);
        $this->assertTrue($result);
        
        // Test get_item_permissions_check (always returns true, permission check is in method)
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/1');
        $result = $this->controller->get_item_permissions_check($request);
        $this->assertTrue($result);
    }
    
    /**
     * Test nonce verification for authenticated requests
     */
    public function test_nonce_verification() {
        wp_set_current_user($this->users['administrator']->ID);
        
        // Create a valid nonce
        $nonce = wp_create_nonce('wp_rest');
        
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $request->set_header('X-WP-Nonce', $nonce);
        
        $response = $this->server->dispatch($request);
        $this->assertEquals(200, $response->get_status());
    }
    
    /**
     * Test capability-based access control
     */
    public function test_capability_based_access() {
        // Create a custom user with specific capabilities
        $custom_user = $this->factory->user->create_and_get([
            'role' => 'subscriber'
        ]);
        
        // Add edit_pages capability to subscriber
        $custom_user->add_cap('edit_pages');
        
        wp_set_current_user($custom_user->ID);
        
        $page = PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        // Should now have access to draft pages
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // Remove capability
        $custom_user->remove_cap('edit_pages');
        
        // Should no longer have access
        $response = $this->server->dispatch($request);
        $this->assertEquals(403, $response->get_status());
    }
    
    /**
     * Test multisite capability checks
     */
    public function test_multisite_capabilities() {
        if (!is_multisite()) {
            $this->markTestSkipped('Multisite not enabled');
        }
        
        // Create a super admin user
        $super_admin = $this->factory->user->create_and_get([
            'role' => 'administrator'
        ]);
        
        grant_super_admin($super_admin->ID);
        wp_set_current_user($super_admin->ID);
        
        $page = PageFactory::create([
            'title' => 'Draft Page',
            'status' => 'draft'
        ]);
        
        // Super admin should have access
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
    }
    
    /**
     * Test rate limiting (if implemented)
     */
    public function test_rate_limiting() {
        // This test would be relevant if rate limiting is implemented
        // For now, we'll test that multiple requests work normally
        
        $page = PageFactory::create([
            'title' => 'Test Page',
            'status' => 'published'
        ]);
        
        // Make multiple requests
        for ($i = 0; $i < 10; $i++) {
            $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
            $response = $this->server->dispatch($request);
            
            $this->assertEquals(200, $response->get_status());
        }
    }
    
    /**
     * Test authentication with different HTTP methods
     */
    public function test_authentication_different_methods() {
        wp_set_current_user($this->users['administrator']->ID);
        
        // Test GET request (should work)
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // Test OPTIONS request (should work for CORS)
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['REQUEST_URI'] = '/wp-json/custom-pages/v1/pages';
        
        $request = new WP_REST_Request('OPTIONS', '/custom-pages/v1/pages');
        
        // The CORS handler should handle OPTIONS requests
        ob_start();
        $this->controller->add_cors_headers(false);
        ob_end_clean();
        
        // Clean up
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['REQUEST_URI']);
    }
    
    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        global $wp_rest_server;
        $wp_rest_server = null;
        
        // Reset current user
        wp_set_current_user(0);
        
        parent::tearDown();
    }
}