<?php
/**
 * Main plugin class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class that handles initialization and dependency loading
 */
class Plugin {
    
    /**
     * Plugin version
     *
     * @var string
     */
    private $version;
    
    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private static $instance = null;
    
    /**
     * Admin interface instance
     *
     * @var Admin_Interface
     */
    private $admin_interface;
    
    /**
     * REST controller instance
     *
     * @var Rest_Controller
     */
    private $rest_controller;
    
    /**
     * Secure image handler instance
     *
     * @var Secure_Image_Handler
     */
    private $secure_image_handler;
    
    /**
     * Scheduler instance
     *
     * @var Scheduler
     */
    private $scheduler;
    
    /**
     * Cache admin instance
     *
     * @var Cache_Admin
     */
    private $cache_admin;
    
    /**
     * Database admin instance
     *
     * @var Database_Admin
     */
    private $database_admin;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->version = CUSTOM_PAGE_BUILDER_VERSION;
    }
    
    /**
     * Get plugin instance (singleton pattern)
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Ensure WordPress is ready
        if (!function_exists('add_action') || !function_exists('is_admin')) {
            return;
        }
        
        try {
            // Initialize error handling system first
            Error_Handler::init();
            
            // Load dependencies
            $this->load_dependencies();
            
            // Define admin hooks
            $this->define_admin_hooks();
            
            // Define API hooks
            $this->define_api_hooks();
            
            // Define public hooks
            $this->define_public_hooks();
            
            // Load text domain for translations
            add_action('init', [$this, 'load_textdomain']);
            
        } catch (Exception $e) {
            // Log the error
            if (function_exists('error_log')) {
                error_log('Custom Page Builder Plugin init error: ' . $e->getMessage());
            }
        } catch (Error $e) {
            // Log fatal errors
            if (function_exists('error_log')) {
                error_log('Custom Page Builder Plugin fatal error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        try {
            // Load admin interface if in admin area
            if (is_admin()) {
                $this->admin_interface = new Admin_Interface();
                $this->cache_admin = new Admin\Cache_Admin();
                $this->cache_admin->init();
                $this->database_admin = new Admin\Database_Admin();
                $this->database_admin->init();
            }
            
            // Load REST API controller (only if WordPress REST API is available)
            if (class_exists('WP_REST_Controller')) {
                $this->rest_controller = new Rest_Controller();
            }
            
            // Load secure image handler
            $this->secure_image_handler = new Secure_Image_Handler();
            
            // Load scheduler
            $this->scheduler = new Scheduler();
            $this->scheduler->init();
            
            // Initialize query optimizer
            Query_Optimizer::init();
            
        } catch (Exception $e) {
            // Log the error and continue with limited functionality
            if (function_exists('error_log')) {
                error_log('Custom Page Builder: Error loading dependencies - ' . $e->getMessage());
            }
            
            // Try to show admin notice if possible
            if (function_exists('add_action')) {
                add_action('admin_notices', function() use ($e) {
                    echo '<div class="notice notice-error"><p>';
                    echo '<strong>Custom Page Builder:</strong> Plugin initialization error. ';
                    echo esc_html($e->getMessage());
                    echo '</p></div>';
                });
            }
        }
    }
    
    /**
     * Define admin-specific hooks
     */
    private function define_admin_hooks() {
        if (!is_admin() || !$this->admin_interface) {
            return;
        }
        
        // Admin menu and pages
        add_action('admin_menu', [$this->admin_interface, 'add_admin_menu']);
        
        // Admin assets
        add_action('admin_enqueue_scripts', [$this->admin_interface, 'enqueue_admin_assets']);
        
        // AJAX handlers - use single handler for all actions
        add_action('wp_ajax_cpb_admin_action', [$this->admin_interface, 'handle_ajax_request']);
        
        // Admin notices
        add_action('admin_notices', [$this->admin_interface, 'display_admin_notices']);
    }
    
    /**
     * Define API-specific hooks
     */
    private function define_api_hooks() {
        if (!$this->rest_controller) {
            return;
        }
        
        // Register REST API routes
        add_action('rest_api_init', [$this->rest_controller, 'register_routes']);
    }
    
    /**
     * Define public hooks
     */
    private function define_public_hooks() {
        // Image processing hooks
        if ($this->secure_image_handler) {
            add_filter('cpb_process_image', [$this->secure_image_handler, 'process_uploaded_image'], 10, 2);
            add_filter('cpb_get_secure_image_url', [$this->secure_image_handler, 'get_secure_image_url'], 10, 3);
        }
    }
    
    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'custom-page-builder',
            false,
            dirname(CUSTOM_PAGE_BUILDER_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Get plugin version
     *
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Get admin interface instance
     *
     * @return Admin_Interface|null
     */
    public function get_admin_interface() {
        return $this->admin_interface;
    }
    
    /**
     * Get REST controller instance
     *
     * @return Rest_Controller|null
     */
    public function get_rest_controller() {
        return $this->rest_controller;
    }
    
    /**
     * Get secure image handler instance
     *
     * @return Secure_Image_Handler|null
     */
    public function get_secure_image_handler() {
        return $this->secure_image_handler;
    }
    
    /**
     * Get scheduler instance
     *
     * @return Scheduler|null
     */
    public function get_scheduler() {
        return $this->scheduler;
    }
}