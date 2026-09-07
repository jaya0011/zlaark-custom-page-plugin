<?php
/**
 * REST API controller class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

use Custom_Page_Builder\WordPress_Helper;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Custom_Page_Builder\Models\CustomPage;
use Custom_Page_Builder\Admin\Section_Manager;
use Custom_Page_Builder\Cache_Manager;
use Custom_Page_Builder\Error_Handler;
use Custom_Page_Builder\Error_Logger;
use Custom_Page_Builder\Exceptions\ApiException;
use Custom_Page_Builder\Exceptions\DatabaseException;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API controller class extending WordPress REST controller
 */
class Rest_Controller extends WP_REST_Controller {
    
    /**
     * API namespace
     *
     * @var string
     */
    protected $namespace = 'custom-page-builder/v1';
    
    /**
     * Resource name
     *
     * @var string
     */
    protected $rest_base = 'pages';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize controller properties
        $this->namespace = 'custom-page-builder/v1';
        $this->rest_base = 'pages';
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Add CORS headers for React frontend compatibility
        WordPress_Helper::safe_add_action('rest_pre_serve_request', [$this, 'add_cors_headers'], 0);
        
        // Register pages collection endpoint
        if (function_exists('register_rest_route')) {
            register_rest_route(
                $this->namespace,
                '/' . $this->rest_base,
                [
                    [
                        'methods' => 'GET',
                        'callback' => [$this, 'get_items'],
                        'permission_callback' => [$this, 'get_items_permissions_check'],
                        'args' => $this->get_collection_params(),
                    ],
                    'schema' => [$this, 'get_public_item_schema'],
                ]
            );
        
        // Register individual page endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args' => [
                        'id' => [
                            'description' => __('Unique identifier for the page.', 'custom-page-builder'),
                            'type' => 'integer',
                            'required' => true,
                            'validate_callback' => function($param) {
                                return is_numeric($param) && $param > 0;
                            },
                        ],
                    ],
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]
        );
        
        // Register page by slug endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<slug>[a-zA-Z0-9_-]+)',
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_item_by_slug'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args' => [
                        'slug' => [
                            'description' => __('Unique slug for the page.', 'custom-page-builder'),
                            'type' => 'string',
                            'required' => true,
                            'validate_callback' => function($param) {
                                return !empty($param) && preg_match('/^[a-zA-Z0-9_-]+$/', $param);
                            },
                        ],
                    ],
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]
        );
        }
    }
    
    /**
     * Get a collection of pages
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items($request) {
        try {
            $args = [
                'status' => $request->get_param('status') ?: 'published',
                'limit' => $request->get_param('per_page') ?: 10,
                'offset' => ($request->get_param('page') - 1) * ($request->get_param('per_page') ?: 10),
                'orderby' => $request->get_param('orderby') ?: 'updated_at',
                'order' => $request->get_param('order') ?: 'DESC'
            ];
            
            // Add search functionality
            if ($search = $request->get_param('search')) {
                $args['search'] = sanitize_text_field($search);
            }
            
            // Check cache first if caching is enabled
            $cache_key = Cache_Manager::get_page_list_cache_key($args);
            $cached_data = null;
            
            if (Cache_Manager::is_caching_enabled()) {
                $cached_data = Cache_Manager::get($cache_key);
            }
            
            if ($cached_data !== false) {
                $response = rest_ensure_response($cached_data['data']);
                $response->header('X-WP-Total', $cached_data['total']);
                $response->header('X-WP-TotalPages', $cached_data['max_pages']);
                $response->header('X-Cache-Status', 'HIT');
                return $response;
            }
            
            $pages = CustomPage::get_all($args);
            $data = [];
            
            // Optimize by batch loading sections if needed
            $include_sections = $request->get_param('include_sections');
            if ($include_sections && !empty($pages)) {
                $page_ids = array_map(function($page) {
                    return $page->get_id();
                }, $pages);
                
                $section_manager = new Section_Manager();
                $all_sections = $section_manager->get_sections_by_pages($page_ids);
                
                // Assign sections to pages
                foreach ($pages as $page) {
                    $page_sections = $all_sections[$page->get_id()] ?? [];
                    $page->set_sections($page_sections);
                }
            }
            
            foreach ($pages as $page) {
                $page_data = $this->prepare_item_for_response($page, $request);
                $data[] = $this->prepare_response_for_collection($page_data);
            }
            
            // Get pagination info
            $total_pages = $this->get_total_pages_count($args);
            $max_pages = ceil($total_pages / ($request->get_param('per_page') ?: 10));
            
            // Cache the response if caching is enabled
            if (Cache_Manager::is_caching_enabled()) {
                $cache_data = [
                    'data' => $data,
                    'total' => $total_pages,
                    'max_pages' => $max_pages
                ];
                Cache_Manager::set($cache_key, $cache_data, Cache_Manager::get_cache_expiration('list'));
            }
            
            $response = rest_ensure_response($data);
            $response->header('X-WP-Total', $total_pages);
            $response->header('X-WP-TotalPages', $max_pages);
            $response->header('X-Cache-Status', 'MISS');
            
            return $response;
            
        } catch (\Throwable $e) {
            return Error_Handler::handle_exception($e, 'rest');
        }
    }
    
    /**
     * Get a specific page by ID
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item($request) {
        try {
            $page_id = (int) $request->get_param('id');
            
            // Check cache first if caching is enabled
            $cache_key = Cache_Manager::get_page_cache_key($page_id, true);
            $cached_data = null;
            
            if (Cache_Manager::is_caching_enabled()) {
                $cached_data = Cache_Manager::get($cache_key);
            }
            
            if ($cached_data !== false) {
                // Still need to check permissions for cached data
                if ($cached_data['status'] !== 'published' && !$this->can_view_unpublished()) {
                    return new WP_Error(
                        'page_not_found',
                        __('Page not found.', 'custom-page-builder'),
                        ['status' => 404]
                    );
                }
                
                $response = rest_ensure_response($cached_data);
                $response->header('Cache-Control', 'public, max-age=300');
                $response->header('X-Cache-Status', 'HIT');
                return $response;
            }
            
            $page = CustomPage::find($page_id);
            
            if (!$page) {
                return new WP_Error(
                    'page_not_found',
                    __('Page not found.', 'custom-page-builder'),
                    ['status' => 404]
                );
            }
            
            // Check if page is published or user has permission to view drafts
            if ($page->get_status() !== 'published' && !$this->can_view_unpublished()) {
                return new WP_Error(
                    'page_not_found',
                    __('Page not found.', 'custom-page-builder'),
                    ['status' => 404]
                );
            }
            
            // Load sections for the page
            $section_manager = new Section_Manager();
            $sections = $section_manager->get_sections_by_page($page_id);
            $page->set_sections($sections);
            
            $data = $this->prepare_item_for_response($page, $request);
            
            // Cache the response if caching is enabled and page is published
            if (Cache_Manager::is_caching_enabled() && $page->get_status() === 'published') {
                Cache_Manager::set($cache_key, $data->get_data(), Cache_Manager::get_cache_expiration('page'));
            }
            
            $response = rest_ensure_response($data);
            
            // Add caching headers
            $response->header('Cache-Control', 'public, max-age=300');
            $response->header('Last-Modified', $page->get_updated_at()->format('D, d M Y H:i:s') . ' GMT');
            $response->header('X-Cache-Status', 'MISS');
            
            return $response;
            
        } catch (\Throwable $e) {
            return Error_Handler::handle_exception($e, 'rest');
        }
    }
    
    /**
     * Get a specific page by slug
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item_by_slug($request) {
        try {
            $slug = sanitize_text_field($request->get_param('slug'));
            
            // Check cache first if caching is enabled
            $cache_key = "page_slug_{$slug}";
            $cached_data = null;
            
            if (Cache_Manager::is_caching_enabled()) {
                $cached_data = Cache_Manager::get($cache_key);
            }
            
            if ($cached_data !== false) {
                // Still need to check permissions for cached data
                if ($cached_data['status'] !== 'published' && !$this->can_view_unpublished()) {
                    return new WP_Error(
                        'page_not_found',
                        __('Page not found.', 'custom-page-builder'),
                        ['status' => 404]
                    );
                }
                
                $response = rest_ensure_response($cached_data);
                $response->header('Cache-Control', 'public, max-age=300');
                $response->header('X-Cache-Status', 'HIT');
                return $response;
            }
            
            $page = CustomPage::find_by_slug($slug);
            
            if (!$page) {
                return new WP_Error(
                    'page_not_found',
                    __('Page not found.', 'custom-page-builder'),
                    ['status' => 404]
                );
            }
            
            // Check if page is published or user has permission to view drafts
            if ($page->get_status() !== 'published' && !$this->can_view_unpublished()) {
                return new WP_Error(
                    'page_not_found',
                    __('Page not found.', 'custom-page-builder'),
                    ['status' => 404]
                );
            }
            
            // Load sections for the page
            $section_manager = new Section_Manager();
            $sections = $section_manager->get_sections_by_page($page->get_id());
            $page->set_sections($sections);
            
            $data = $this->prepare_item_for_response($page, $request);
            
            // Cache the response if caching is enabled and page is published
            if (Cache_Manager::is_caching_enabled() && $page->get_status() === 'published') {
                Cache_Manager::set($cache_key, $data->get_data(), Cache_Manager::get_cache_expiration('page'));
            }
            
            $response = rest_ensure_response($data);
            
            // Add caching headers
            $response->header('Cache-Control', 'public, max-age=300');
            $response->header('Last-Modified', $page->get_updated_at()->format('D, d M Y H:i:s') . ' GMT');
            $response->header('X-Cache-Status', 'MISS');
            
            return $response;
            
        } catch (\Throwable $e) {
            return Error_Handler::handle_exception($e, 'rest');
        }
    }
    
    /**
     * Check permissions for getting items
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function get_items_permissions_check($request) {
        // Allow; public access for published pages
        $status = $request->get_param('status');
        
        if (!$status || $status === 'published') {
            return true;
        }
        
        // For non-published pages, require edit capability
        return current_user_can('edit_pages');
    }
    
    /**
     * Check permissions for getting a single item
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function get_item_permissions_check($request) {
        // Allow; public access - individual permission check is done in get_item method
        return true;
    }
    
    /**
     * Prepare a single page output for response
     *
     * @param CustomPage $page Page object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function prepare_item_for_response($page, $request) {
        $data = $page->to_api_response();
        
        // Process sections to include secure image URLs
        if (!empty($data['sections'])) {
            $data['sections'] = $this->process_sections_for_api($data['sections']);
        }
        
        $context = !empty($request['context']) ? $request['context'] : 'view';
        $data = $this->add_additional_fields_to_object($data, $request);
        $data = $this->filter_response_by_context($data, $context);
        
        $response = rest_ensure_response($data);
        $response->add_links($this->prepare_links($page));
        
        return $response;
    }
    
    /**
     * Process sections to include secure image URLs
     *
     * @param array $sections Array of sections
     * @return array Processed sections
     */
    private function process_sections_for_api($sections) {
        $secure_image_handler = new \Custom_Page_Builder\Secure_Image_Handler();
        
        foreach ($sections as &$section) {
            if (isset($section['config'])) {
                $section['config'] = $secure_image_handler->process_section_images($section['config']);
            }
        }
        
        return $sections;
    }
    
    /**
     * Prepare links for the request
     *
     * @param CustomPage $page Page object.
     * @return array Links for the given page.
     */
    protected function prepare_links($page) {
        $links = [
            'self' => [
                'href' => rest_url(sprintf('%s/%s/%d', $this->namespace, $this->rest_base, $page->get_id())),
            ],
            'collection' => [
                'href' => rest_url(sprintf('%s/%s', $this->namespace, $this->rest_base)),
            ],
        ];
        
        // Add author link if available
        if ($page->get_author_id()) {
            $links['author'] = [
                'href' => rest_url('wp/v2/users/' . $page->get_author_id()),
                'embeddable' => true,
            ];
        }
        
        return $links;
    }
    
    /**
     * Get the query params for collections
     *
     * @return array Collection parameters.
     */
    public function get_collection_params() {
        $params = parent::get_collection_params();
        
        $params['status'] = [
            'description' => __('Limit result set to pages with specific status.', 'custom-page-builder'),
            'type' => 'string',
            'enum' => ['draft', 'published', 'archived'],
            'default' => 'published',
            'sanitize_callback' => 'sanitize_text_field',
        ];
        
        $params['search'] = [
            'description' => __('Limit results to those matching a string.', 'custom-page-builder'),
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ];
        
        $params['orderby'] = [
            'description' => __('Sort collection by page attribute.', 'custom-page-builder'),
            'type' => 'string',
            'enum' => ['created_at', 'updated_at', 'published_at', 'title'],
            'default' => 'updated_at',
            'sanitize_callback' => 'sanitize_text_field',
        ];
        
        $params['include_sections'] = [
            'description' => __('Include page sections in the response.', 'custom-page-builder'),
            'type' => 'boolean',
            'default' => false,
        ];
        
        return $params;
    }
    
    /**
     * Get the page schema, conforming to JSON Schema
     *
     * @return array Item schema data.
     */
    public function get_item_schema() {
        $schema = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'custom-page',
            'type' => 'object',
            'properties' => [
                'id' => [
                    'description' => __('Unique identifier for the page.', 'custom-page-builder'),
                    'type' => 'integer',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'title' => [
                    'description' => __('The title for the page.', 'custom-page-builder'),
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                ],
                'slug' => [
                    'description' => __('An alphanumeric identifier for the page unique to its type.', 'custom-page-builder'),
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                ],
                'status' => [
                    'description' => __('A named status for the page.', 'custom-page-builder'),
                    'type' => 'string',
                    'enum' => ['draft', 'published', 'archived'],
                    'context' => ['view', 'edit'],
                ],
                'created_at' => [
                    'description' => __('The date the page was created, in the site\'s timezone.', 'custom-page-builder'),
                    'type' => 'string',
                    'format' => 'date-time',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'updated_at' => [
                    'description' => __('The date the page was last modified, in the site\'s timezone.', 'custom-page-builder'),
                    'type' => 'string',
                    'format' => 'date-time',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'published_at' => [
                    'description' => __('The date the page was published, in the site\'s timezone.', 'custom-page-builder'),
                    'type' => ['string', 'null'],
                    'format' => 'date-time',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'author' => [
                    'description' => __('The author of the page.', 'custom-page-builder'),
                    'type' => 'object',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                        'name' => [
                            'type' => 'string',
                        ],
                        'email' => [
                            'type' => 'string',
                        ],
                    ],
                ],
                'meta_data' => [
                    'description' => __('Meta data for the page.', 'custom-page-builder'),
                    'type' => 'object',
                    'context' => ['view', 'edit'],
                ],
                'sections' => [
                    'description' => __('The sections that make up the page content.', 'custom-page-builder'),
                    'type' => 'array',
                    'context' => ['view', 'edit'],
                    'items' => [
                        'type' => 'object',
                    ],
                ],
                'url' => [
                    'description' => __('The URL to the page.', 'custom-page-builder'),
                    'type' => 'string',
                    'format' => 'uri',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
            ],
        ];
        
        return $this->add_additional_fields_schema($schema);
    }
    
    /**
     * Check if current user can view unpublished pages
     *
     * @return bool
     */
    private function can_view_unpublished() {
        return current_user_can('edit_pages') || current_user_can('manage_options');
    }
    
    /**
     * Get total pages count for pagination
     *
     * @param array $args Query arguments
     * @return int Total count
     */
    private function get_total_pages_count($args) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'custom_pages';
        $where_clauses = [];
        $where_values = [];
        
        if (!empty($args['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['search'])) {
            $where_clauses[] = '(title LIKE %s OR slug LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        $sql = "SELECT COUNT(*) FROM {$table_name} {$where_sql}";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Handle errors consistently
     *
     * @param \Throwable $e Exception object
     * @param string $error_code Error code
     * @return WP_Error
     */
    private function handle_error(\Throwable $e, $error_code) {
        return Error_Handler::handle_exception($e, 'rest');
    }
    
    /**
     * Add CORS headers for React frontend compatibility
     *
     * @param bool $served Whether the request has already been served.
     * @return bool
     */
    public function add_cors_headers($served) {
        // zlaark-wc-api owns CORS for this site; defer to it rather than writing
        // the same headers twice and letting hook order decide the winner.
        if (class_exists('Zlaark_WC_API') && method_exists('Zlaark_WC_API', 'emit_cors_headers')) {
            return $served;
        }

        // Only add CORS headers for our API endpoints
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/wp-json/' . $this->namespace) === false) {
            return $served;
        }

        // Get allowed origins from WordPress settings or use default
        $allowed_origins = apply_filters('cpb_api_cors_origins', [
            get_site_url(),
            'http://localhost:3000', // Common React dev server
            'http://localhost:3001',
            'https://localhost:3000',
            'https://localhost:3001',
            'https://dhawada.com',
            'https://www.dhawada.com',
        ]);

        $origin = get_http_origin();

        header('Vary: Origin', false);

        if ($origin && in_array(rtrim($origin, '/'), $allowed_origins, true)) {
            header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
        header('Access-Control-Max-Age: 86400'); // 24 hours

        // NOTE: no exit() on OPTIONS. This filter runs at priority 0; exiting here
        // aborted the request before any later CORS filter could add the
        // Access-Control-Allow-Origin header, so every preflight to this namespace
        // failed. Core's rest_handle_options_request already answers preflights.

        return $served;
    }
}