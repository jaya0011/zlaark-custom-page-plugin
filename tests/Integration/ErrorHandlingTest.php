<?php
/**
 * Integration tests for API error handling and response formatting
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests\Integration;

use Custom_Page_Builder\Tests\TestCase;
use Custom_Page_Builder\Rest_Controller;
use Custom_Page_Builder\Tests\Factories\PageFactory;
use Custom_Page_Builder\Error_Handler;
use Custom_Page_Builder\Exceptions\DatabaseException;
use Custom_Page_Builder\Exceptions\ApiException;
use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

/**
 * Integration tests for error handling and response formatting
 */
class ErrorHandlingTest extends TestCase {
    
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
        
        do_action('rest_api_init');
    }
    
    /**
     * Test 404 error for non-existent page
     */
    public function test_404_error_non_existent_page() {
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/99999');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
        $this->assertInstanceOf('WP_Error', $response->as_error());
        
        $error = $response->as_error();
        $this->assertEquals('page_not_found', $error->get_error_code());
        $this->assertStringContainsString('Page not found', $error->get_error_message());
    }
    
    /**
     * Test 404 error for invalid page ID format
     */
    public function test_404_error_invalid_page_id() {
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/invalid-id');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
    }
    
    /**
     * Test 403 error for unauthorized access
     */
    public function test_403_error_unauthorized_access() {
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
        
        $error = $response->as_error();
        $this->assertStringContainsString('permission', strtolower($error->get_error_message()));
    }
    
    /**
     * Test parameter validation errors
     */
    public function test_parameter_validation_errors() {
        // Test invalid status parameter
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'invalid_status');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(400, $response->get_status());
        $this->assertInstanceOf('WP_Error', $response->as_error());
        
        // Test invalid orderby parameter
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('orderby', 'invalid_field');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(400, $response->get_status());
        
        // Test invalid per_page parameter (negative number)
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('per_page', -5);
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(400, $response->get_status());
    }
    
    /**
     * Test slug validation errors
     */
    public function test_slug_validation_errors() {
        // Test invalid slug format (contains invalid characters)
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/slug/invalid@slug!');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
        
        // Test empty slug
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/slug/');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
    }
    
    /**
     * Test database error handling
     */
    public function test_database_error_handling() {
        // Mock a database error by temporarily corrupting the table name
        global $wpdb;
        $original_prefix = $wpdb->prefix;
        
        // This should cause database errors
        $wpdb->prefix = 'nonexistent_';
        
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $response = $this->server->dispatch($request);
        
        // Restore original prefix
        $wpdb->prefix = $original_prefix;
        
        // Should return 500 error for database issues
        $this->assertEquals(500, $response->get_status());
        $this->assertInstanceOf('WP_Error', $response->as_error());
    }
    
    /**
     * Test error response format consistency
     */
    public function test_error_response_format() {
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/99999');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
        $this->assertInstanceOf('WP_Error', $response->as_error());
        
        $error = $response->as_error();
        
        // Check error structure
        $this->assertIsString($error->get_error_code());
        $this->assertIsString($error->get_error_message());
        
        $error_data = $error->get_error_data();
        if ($error_data) {
            $this->assertIsArray($error_data);
            $this->assertArrayHasKey('status', $error_data);
            $this->assertEquals(404, $error_data['status']);
        }
    }
    
    /**
     * Test HTTP status codes for different error types
     */
    public function test_http_status_codes() {
        // 404 for not found
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/99999');
        $response = $this->server->dispatch($request);
        $this->assertEquals(404, $response->get_status());
        
        // 403 for forbidden
        wp_set_current_user(0);
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'draft');
        $response = $this->server->dispatch($request);
        $this->assertEquals(403, $response->get_status());
        
        // 400 for bad request
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('status', 'invalid_status');
        $response = $this->server->dispatch($request);
        $this->assertEquals(400, $response->get_status());
    }
    
    /**
     * Test error logging functionality
     */
    public function test_error_logging() {
        // Enable error logging for testing
        $original_debug = defined('WP_DEBUG') ? WP_DEBUG : false;
        $original_debug_log = defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false;
        
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        if (!defined('WP_DEBUG_LOG')) {
            define('WP_DEBUG_LOG', true);
        }
        
        // Trigger an error that should be logged
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/99999');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
        
        // Check that error was handled properly (we can't easily test actual logging without file system access)
        $this->assertInstanceOf('WP_Error', $response->as_error());
    }
    
    /**
     * Test exception handling for different exception types
     */
    public function test_exception_handling() {
        // This test would require mocking methods to throw specific exceptions
        // For now, we'll test that the error handler can handle different error types
        
        $database_error = new DatabaseException('Database connection failed');
        $api_error = new ApiException('Invalid API request');
        
        // Test that exceptions have proper structure
        $this->assertInstanceOf('Exception', $database_error);
        $this->assertInstanceOf('Exception', $api_error);
        $this->assertEquals('Database connection failed', $database_error->getMessage());
        $this->assertEquals('Invalid API request', $api_error->getMessage());
    }
    
    /**
     * Test rate limiting error responses (if implemented)
     */
    public function test_rate_limiting_errors() {
        // This would test rate limiting if implemented
        // For now, we'll test that multiple requests don't cause errors
        
        $page = PageFactory::create([
            'title' => 'Test Page',
            'status' => 'published'
        ]);
        
        // Make many requests quickly
        for ($i = 0; $i < 20; $i++) {
            $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
            $response = $this->server->dispatch($request);
            
            // Should not return rate limit errors (429) since not implemented
            $this->assertNotEquals(429, $response->get_status());
        }
    }
    
    /**
     * Test CORS error handling
     */
    public function test_cors_error_handling() {
        // Test with invalid origin
        $_SERVER['HTTP_ORIGIN'] = 'https://malicious-site.com';
        $_SERVER['REQUEST_URI'] = '/wp-json/custom-pages/v1/pages';
        
        $page = PageFactory::create([
            'title' => 'Test Page',
            'status' => 'published'
        ]);
        
        $request = new WP_REST_Request('GET', "/custom-pages/v1/pages/{$page->get_id()}");
        $response = $this->server->dispatch($request);
        
        // Should still return 200 but without CORS headers for invalid origin
        $this->assertEquals(200, $response->get_status());
        
        // Clean up
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['REQUEST_URI']);
    }
    
    /**
     * Test malformed request handling
     */
    public function test_malformed_request_handling() {
        // Test with malformed JSON in request body (if applicable)
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        
        // Add malformed parameters
        $request->set_param('page', 'not-a-number');
        $response = $this->server->dispatch($request);
        
        // Should handle gracefully
        $this->assertContains($response->get_status(), [200, 400]);
        
        // Test with extremely large page number
        $request->set_param('page', PHP_INT_MAX);
        $response = $this->server->dispatch($request);
        
        // Should handle gracefully
        $this->assertContains($response->get_status(), [200, 400]);
    }
    
    /**
     * Test timeout handling (simulated)
     */
    public function test_timeout_handling() {
        // This would test timeout scenarios if we had long-running operations
        // For now, we'll test that normal requests complete quickly
        
        $start_time = microtime(true);
        
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $response = $this->server->dispatch($request);
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        // Request should complete within reasonable time (1 second)
        $this->assertLessThan(1.0, $execution_time);
        $this->assertEquals(200, $response->get_status());
    }
    
    /**
     * Test memory limit handling
     */
    public function test_memory_limit_handling() {
        // Create many pages to test memory usage
        for ($i = 0; $i < 50; $i++) {
            PageFactory::create([
                'title' => "Test Page {$i}",
                'status' => 'published'
            ]);
        }
        
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages');
        $request->set_param('per_page', 50);
        $response = $this->server->dispatch($request);
        
        // Should handle large result sets without memory errors
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertCount(50, $data);
    }
    
    /**
     * Test error message sanitization
     */
    public function test_error_message_sanitization() {
        // Test that error messages don't contain sensitive information
        $request = new WP_REST_Request('GET', '/custom-pages/v1/pages/99999');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
        
        $error = $response->as_error();
        $message = $error->get_error_message();
        
        // Error message should be generic and not expose internal details
        $this->assertStringNotContainsString('database', strtolower($message));
        $this->assertStringNotContainsString('sql', strtolower($message));
        $this->assertStringNotContainsString('mysql', strtolower($message));
        $this->assertStringNotContainsString('table', strtolower($message));
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