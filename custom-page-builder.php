<?php
/**
 * Plugin Name: Zlaark Custom Page Builder
 * Plugin URI: https://zlaark.com/
 * Description: A WordPress plugin that enables administrators to create and manage custom pages through a visual page builder interface with section-based content management.
 * Version: 1.0.3
 * Author: Zlaark
 * Author URI: https://zlaark.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-page-builder
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check PHP version compatibility
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Custom Page Builder:</strong> This plugin requires PHP 7.4 or higher. You are running PHP ' . PHP_VERSION . '</p></div>';
    });
    return;
}

// Define plugin constants
define('CUSTOM_PAGE_BUILDER_VERSION', '1.0.0');
define('CUSTOM_PAGE_BUILDER_PLUGIN_FILE', __FILE__);
define('CUSTOM_PAGE_BUILDER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CUSTOM_PAGE_BUILDER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CUSTOM_PAGE_BUILDER_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load the Secure Image Handler class
require_once CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'integrations/class-secure-image-handler.php';

// Load Category Manager
require_once CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'includes/class-category-manager.php';
require_once CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'includes/category-selector-template.php';
require_once CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'includes/class-polylang-integrator.php';

// Load Category AJAX handlers (admin only)
if (is_admin()) {
    require_once CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'admin/class-category-ajax.php';
    require_once CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'admin/class-import-export.php';
}

// Activation hook - PRESERVE DATA on plugin updates/reactivation
register_activation_hook(__FILE__, function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_pages';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if table already exists - DO NOT DROP existing table to preserve data
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        // Only create table if it doesn't exist
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft',
            sections longtext,
            language varchar(10) DEFAULT 'en',
            translation_group bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug_lang (slug, language),
            KEY status (status),
            KEY language (language),
            KEY translation_group (translation_group)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    } else {
        // Table exists - check if we need to add any missing columns for updates
        $columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name");
        
        // Add language column if missing (for older versions)
        if (!in_array('language', $columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN language varchar(10) DEFAULT 'en' AFTER sections");
            $wpdb->query("ALTER TABLE $table_name ADD KEY language (language)");
        }
        
        // Add translation_group column if missing
        if (!in_array('translation_group', $columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN translation_group bigint(20) unsigned DEFAULT NULL AFTER language");
            $wpdb->query("ALTER TABLE $table_name ADD KEY translation_group (translation_group)");
        }
        
        // Update unique key if needed (slug + language combination)
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'slug_lang'");
        if (empty($indexes)) {
            // Remove old unique key on slug only if exists
            $old_index = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'slug'");
            if (!empty($old_index)) {
                $wpdb->query("ALTER TABLE $table_name DROP INDEX slug");
            }
            // Add new composite unique key
            $wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY slug_lang (slug, language)");
        }
    }
    
    // Update version options
    update_option('cpb_activated_at', current_time('mysql'));
    update_option('cpb_version', CUSTOM_PAGE_BUILDER_VERSION);
    update_option('cpb_db_version', '1.1');
    set_transient('cpb_activation_notice', true, 60);
    flush_rewrite_rules();
    
    error_log('Custom Page Builder: Plugin activated. Table preserved: ' . ($table_exists ? 'yes' : 'no (new install)'));
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Clean up activation notice (no display)
add_action('admin_init', function() {
    if (get_transient('cpb_activation_notice')) {
        delete_transient('cpb_activation_notice');
    }
}, 1);

// Handle database fix action - ONLY repairs table structure, preserves data when possible
add_action('admin_init', function() {
    if (isset($_GET['cpb_fix_database']) && current_user_can('manage_options')) {
        check_admin_referer('cpb_fix_database');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_pages';
        $charset_collate = $wpdb->get_charset_collate();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if ($table_exists) {
            // Table exists - try to repair/update structure without losing data
            $columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name");
            
            // Add missing columns
            if (!in_array('sections', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN sections longtext NULL AFTER status");
            }
            if (!in_array('language', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN language varchar(10) DEFAULT 'en' AFTER sections");
            }
            if (!in_array('translation_group', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN translation_group bigint(20) unsigned DEFAULT NULL AFTER language");
            }
            
            error_log('Custom Page Builder: Database repaired - data preserved');
        } else {
            // Table doesn't exist - create it
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                title varchar(255) NOT NULL,
                slug varchar(255) NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'draft',
                sections longtext NULL,
                language varchar(10) DEFAULT 'en',
                translation_group bigint(20) unsigned DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY slug_lang (slug, language),
                KEY status (status),
                KEY language (language),
                KEY translation_group (translation_group)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('Custom Page Builder: Database created (was missing)');
        }
        
        set_transient('cpb_db_fixed', true, 30);
        wp_redirect(admin_url('admin.php?page=custom-page-builder'));
        exit;
    }
    
    if (get_transient('cpb_db_fixed')) {
        delete_transient('cpb_db_fixed');
    }
});

// Ensure database schema is up to date (runs once per version)
add_action('admin_init', function() {
    $current_db_version = get_option('cpb_db_schema_version', '1.0');
    $required_db_version = '1.2'; // Increment this when schema changes
    
    if (version_compare($current_db_version, $required_db_version, '<')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_pages';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            // Ensure we have the composite unique key (slug + language)
            $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'slug_lang'");
            if (empty($indexes)) {
                // Remove old unique key on slug only if exists
                $old_index = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'slug'");
                if (!empty($old_index)) {
                    $wpdb->query("ALTER TABLE $table_name DROP INDEX slug");
                }
                // Add new composite unique key
                $wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY slug_lang (slug, language)");
                error_log('Custom Page Builder: Updated database to use composite unique key (slug, language)');
            }
            
            // Ensure language column exists
            $columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name");
            if (!in_array('language', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN language varchar(10) DEFAULT 'en' AFTER sections");
            }
        }
        
        update_option('cpb_db_schema_version', $required_db_version);
    }
}, 5);

// Add frontend template handling for secure images
add_action('template_redirect', function() {
    // Check if this is a custom page request
    $slug = get_query_var('pagename');
    if (!$slug) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_pages';
    
    // Check if this slug exists in our custom pages
    $page = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE slug = %s AND status = 'published'",
        $slug
    ));
    
    if ($page) {
        // This is a custom page - ensure secure image processing
        cpb_process_page_images_for_display($page);
    }
});

// Process page images for secure display
function cpb_process_page_images_for_display($page) {
    if (!$page || !$page->sections) {
        return;
    }
    
    $sections = json_decode($page->sections, true);
    if (!is_array($sections)) {
        return;
    }
    
    // Initialize secure image handler
    $secure_image_handler = null;
    if (class_exists('Custom_Page_Builder\\Secure_Image_Handler')) {
        $secure_image_handler = new \Custom_Page_Builder\Secure_Image_Handler();
    }
    
    // Process each section's images
    foreach ($sections as &$section) {
        cpb_process_section_images_recursive($section, $secure_image_handler);
    }
    
    // Store processed sections back (this ensures secure URLs are used)
    $page->sections = wp_json_encode($sections);
}

/**
 * Check if a URL is an R2 CDN signed URL
 *
 * @param string $url URL to check
 * @return bool
 */
function cpb_is_r2_signed_url($url) {
    // R2 signed URLs typically contain signature parameters
    return (strpos($url, 'X-Amz-Signature') !== false || 
            strpos($url, 'X-Amz-Credential') !== false ||
            strpos($url, 'r2.cloudflarestorage.com') !== false ||
            strpos($url, 'r2.dev') !== false);
}

/**
 * Extract expiration timestamp from R2 signed URL if available
 *
 * @param string $url R2 signed URL
 * @return int|null Unix timestamp or null if not found
 */
function cpb_get_r2_url_expiration($url) {
    // Check for X-Amz-Expires parameter
    if (preg_match('/[?&]X-Amz-Date=(\d{8}T\d{6}Z)/', $url, $date_match)) {
        if (preg_match('/[?&]X-Amz-Expires=(\d+)/', $url, $expires_match)) {
            $date = DateTime::createFromFormat('Ymd\THis\Z', $date_match[1], new DateTimeZone('UTC'));
            if ($date) {
                $expires_seconds = intval($expires_match[1]);
                return $date->getTimestamp() + $expires_seconds;
            }
        }
    }
    return null;
}

/**
 * Get R2 CDN signed URL for an attachment ID
 * Checks if the attachment is a WooCommerce product image with R2 URL
 *
 * @param int $attachment_id WordPress attachment ID
 * @return string|null R2 signed URL or null if not available
 */
function cpb_get_r2_url_for_attachment($attachment_id) {
    if (!$attachment_id || !is_numeric($attachment_id)) {
        return null;
    }
    
    $attachment_id = intval($attachment_id);
    
    // Check if this attachment has R2 URL stored as post meta
    $r2_url = get_post_meta($attachment_id, '_r2_cdn_url', true);
    if ($r2_url && cpb_is_r2_signed_url($r2_url)) {
        return $r2_url;
    }
    
    // Check if this is a WooCommerce product featured image
    $product_id = get_post_meta($attachment_id, '_wp_attachment_product_id', true);
    if (!$product_id) {
        // Try to find product by searching for this attachment as featured image
        global $wpdb;
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_thumbnail_id' AND meta_value = %d LIMIT 1",
            $attachment_id
        ));
    }
    
    // If we found a product, check for R2 URL in product meta
    if ($product_id) {
        $product_r2_url = get_post_meta($product_id, '_product_image_url', true);
        if ($product_r2_url && cpb_is_r2_signed_url($product_r2_url)) {
            return $product_r2_url;
        }
    }
    
    return null;
}

// Recursively process images in sections with enhanced nested structure support
// Now supports R2 CDN signed URLs via image_url and gallery_urls fields
function cpb_process_section_images_recursive(&$data, $secure_image_handler) {
    if (!is_array($data)) {
        return;
    }
    
    foreach ($data as $key => &$value) {
        if (is_array($value)) {
            // Recursively process nested arrays (slides, elements, etc.)
            cpb_process_section_images_recursive($value, $secure_image_handler);
        } 
        // Handle R2 CDN image_url field (priority)
        elseif ($key === 'image_url' && is_string($value) && !empty($value)) {
            // R2 CDN signed URL - use as is, it's self-contained and secure
            $value = esc_url($value);
        }
        // Handle R2 CDN gallery_urls field
        elseif ($key === 'gallery_urls' && is_array($value)) {
            // Array of R2 CDN signed URLs
            $value = array_map('esc_url', array_filter($value));
        }
        // Legacy image field support
        elseif (is_string($key) && strpos($key, 'image') !== false && $key !== 'image_url') {
            // Handle both numeric attachment IDs and URL strings
            if (is_numeric($value)) {
                $attachment_id = intval($value);
                if ($attachment_id > 0 && get_post($attachment_id)) {
                    // PRIORITY 1: Check for R2 CDN signed URL
                    $r2_url = cpb_get_r2_url_for_attachment($attachment_id);
                    if ($r2_url) {
                        $value = esc_url($r2_url);
                    }
                    // PRIORITY 2: Generate secure URL via secure image handler
                    elseif ($secure_image_handler) {
                        // Mark as protected if not already
                        update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
                        $secure_url = $secure_image_handler->get_secure_image_url($attachment_id, 'full');
                        if ($secure_url) {
                            $value = $secure_url;
                        }
                    }
                    // PRIORITY 3: Fallback to WordPress attachment URL
                    else {
                        $attachment_url = wp_get_attachment_url($attachment_id);
                        if ($attachment_url) {
                            $value = $attachment_url;
                        }
                    }
                }
            } elseif (is_string($value) && !empty($value)) {
                // If it's already a URL (including R2 signed URLs), leave it as is
                $value = esc_url($value);
            }
        }
    }
}

// Load required classes for REST API (with error handling)
$required_files = [
    'includes/class-wordpress-helper.php',
    'models/class-custom-page.php', 
    'includes/class-cache-manager.php',
    'includes/class-error-handler.php',
    'includes/class-error-logger.php',
    'admin/class-section-manager.php',
    'api/class-rest-controller.php'
];

foreach ($required_files as $file) {
    $file_path = CUSTOM_PAGE_BUILDER_PLUGIN_DIR . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

/**
 * Convert attachment IDs to R2 signed URLs in sections data for API responses
 * This ensures the API returns actual URLs instead of attachment IDs
 *
 * @param array $data Sections data with attachment IDs
 * @return array Processed data with R2 signed URLs
 */
function cpb_convert_attachment_ids_to_urls($data) {
    if (!is_array($data)) {
        return $data;
    }
    
    foreach ($data as $key => &$value) {
        if (is_array($value)) {
            // Recursively process nested arrays (slides, products, testimonials, etc.)
            $value = cpb_convert_attachment_ids_to_urls($value);
        } 
        // Handle image fields with attachment IDs
        elseif (($key === 'image' || strpos($key, 'image') !== false) && is_numeric($value) && intval($value) > 0) {
            $attachment_id = intval($value);
            
            // wp_get_attachment_url automatically uses R2 filter if R2 CDN is enabled
            $url = wp_get_attachment_url($attachment_id);
            
            if ($url) {
                // Replace attachment ID with actual URL
                $value = $url;
                
                // Also add image_url field for clarity (R2 CDN convention)
                if ($key === 'image') {
                    $data['image_url'] = $url;
                }
            }
        }
    }
    
    return $data;
}

// CORS headers for frontend language detection.
//
// zlaark-wc-api owns CORS for this site and emits whitelisted headers on
// rest_pre_serve_request. When it is active we must not touch CORS at all:
// two filters writing the same headers means whichever runs last silently wins.
//
// The previous implementation here sent 'Access-Control-Allow-Origin: *' together
// with 'Access-Control-Allow-Credentials: true' on *every* REST response, not just
// this plugin's namespace. Browsers reject that pairing outright on credentialed
// requests, and it exposed every REST endpoint on the site to any origin.
add_action('rest_api_init', function() {
    if (class_exists('Zlaark_WC_API') && method_exists('Zlaark_WC_API', 'emit_cors_headers')) {
        return;
    }

    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        $allowed = apply_filters('cpb_api_cors_origins', [
            get_site_url(),
            'http://localhost:3000',
            'https://dhawada.com',
            'https://www.dhawada.com',
        ]);

        $origin = get_http_origin();

        header('Vary: Origin', false);

        if ($origin && in_array(rtrim($origin, '/'), $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Language, X-Requested-With');
        return $value;
    }, 15);
}, 15);

// Register REST API endpoints
add_action('rest_api_init', function() {
    // Skip the advanced REST controller for now to avoid 500 errors
    // We'll use the simple, reliable API implementation below
    error_log('Custom Page Builder: Registering simple REST API endpoints');
    
    // Get all pages - SIMPLIFIED and RELIABLE version with language detection
    register_rest_route('custom-page-builder/v1', '/pages', array(
        'methods' => 'GET',
        'callback' => function($request) {
            error_log('Custom Page Builder API: Request for all pages');
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'custom_pages';
            
            // Get language parameter with automatic detection
            $requested_lang = \Custom_Page_Builder\Polylang_Integrator::get_current_language($request);
            
            try {
                // Check if table exists
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                    error_log('Custom Page Builder API Error: Table does not exist');
                    return new WP_REST_Response(array(
                        'success' => false,
                        'message' => 'Database table not found'
                    ), 500);
                }
                
                // First try to get pages in the requested language
                $pages = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, title, slug, status, language, created_at, updated_at FROM $table_name WHERE status = 'published' AND language = %s ORDER BY created_at DESC",
                    $requested_lang
                ));
                
                // If no pages found in requested language, try English fallback
                $is_fallback = false;
                $used_lang = $requested_lang;
                
                if (empty($pages) && $requested_lang !== 'en') {
                    $pages = $wpdb->get_results(
                        "SELECT id, title, slug, status, language, created_at, updated_at FROM $table_name WHERE status = 'published' AND language = 'en' ORDER BY created_at DESC"
                    );
                    if (!empty($pages)) {
                        $is_fallback = true;
                        $used_lang = 'en';
                        error_log('Custom Page Builder API: No pages in ' . $requested_lang . ', falling back to English');
                    }
                }
                
                if ($wpdb->last_error) {
                    error_log('Custom Page Builder API Database Error: ' . $wpdb->last_error);
                    return new WP_REST_Response(array(
                        'success' => false,
                        'message' => 'Database query failed'
                    ), 500);
                }
                
                error_log('Custom Page Builder API: Found ' . count($pages) . ' published pages in language: ' . $used_lang);
                
                // Get all available languages
                $available_languages = $wpdb->get_col(
                    "SELECT DISTINCT language FROM $table_name WHERE status = 'published' ORDER BY language"
                );
                
                return new WP_REST_Response(array(
                    'success' => true,
                    'count' => count($pages),
                    'language' => $used_lang,
                    'requested_language' => $requested_lang,
                    'is_fallback' => $is_fallback,
                    'available_languages' => $available_languages,
                    'pages' => $pages
                ), 200);
                
            } catch (Exception $e) {
                error_log('Custom Page Builder API Exception: ' . $e->getMessage());
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Internal server error',
                    'error' => $e->getMessage()
                ), 500);
            }
        },
        'permission_callback' => '__return_true'
    ));
    
    // Get single page by slug - SIMPLIFIED and RELIABLE version with language fallback
    register_rest_route('custom-page-builder/v1', '/pages/(?P<slug>[a-zA-Z0-9_-]+)', array(
        'methods' => 'GET',
        'callback' => function($request) {
            // Log API request for debugging
            error_log('Custom Page Builder API: Request for slug: ' . $request['slug']);
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'custom_pages';
            $slug = sanitize_text_field($request['slug']);
            
            // Enhanced error handling with detailed logging
            try {
                // Check if table exists
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                    error_log('Custom Page Builder API Error: Table does not exist');
                    return new WP_REST_Response(array(
                        'success' => false,
                        'message' => 'Database table not found',
                        'slug_requested' => $slug
                    ), 500);
                }
                
                // First, get all available languages for this slug
                $available_languages = $wpdb->get_col($wpdb->prepare(
                    "SELECT DISTINCT language FROM $table_name WHERE slug = %s AND status = 'published'",
                    $slug
                ));
                
                error_log('Custom Page Builder API: Available languages for slug "' . $slug . '": ' . implode(', ', $available_languages));
                
                // If no pages found with this slug at all, return 404
                if (empty($available_languages)) {
                    error_log('Custom Page Builder API: No pages found for slug: ' . $slug);
                    return new WP_REST_Response(array(
                        'success' => false,
                        'message' => 'Page not found',
                        'slug_requested' => $slug,
                        'available_languages' => []
                    ), 404);
                }
                
                // Get requested language
                $requested_lang = \Custom_Page_Builder\Polylang_Integrator::get_current_language($request);
                error_log('Custom Page Builder API: Requested language: ' . $requested_lang);
                
                // Determine which language to use
                $lang = null;
                $is_fallback = false;
                
                // 1. Try exact match with requested language
                if (in_array($requested_lang, $available_languages)) {
                    $lang = $requested_lang;
                    $is_fallback = false;
                }
                // 2. Try English fallback
                elseif (in_array('en', $available_languages)) {
                    $lang = 'en';
                    $is_fallback = true;
                    error_log('Custom Page Builder API: Using English fallback');
                }
                // 3. Try en-us, en_us variants
                elseif (in_array('en-us', $available_languages)) {
                    $lang = 'en-us';
                    $is_fallback = true;
                }
                elseif (in_array('en_us', $available_languages)) {
                    $lang = 'en_us';
                    $is_fallback = true;
                }
                // 4. Use first available language
                else {
                    $lang = $available_languages[0];
                    $is_fallback = true;
                    error_log('Custom Page Builder API: Using first available language: ' . $lang);
                }
                
                // Get the page in the determined language
                $page = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE slug = %s AND status = 'published' AND language = %s",
                    $slug,
                    $lang
                ));
                
                if (!$page) {
                    error_log('Custom Page Builder API: Page not found for slug: ' . $slug . ' in language: ' . $lang);
                    return new WP_REST_Response(array(
                        'success' => false,
                        'message' => 'Page not found',
                        'slug_requested' => $slug,
                        'language_requested' => $requested_lang
                    ), 404);
                }
                
                error_log('Custom Page Builder API: Found page with ID: ' . $page->id . ' in language: ' . $lang . ($is_fallback ? ' (fallback)' : ''));
                
                // Process sections data safely
                $processed_sections = null;
                if ($page->sections) {
                    // Try to decode sections JSON
                    $sections = json_decode($page->sections, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($sections)) {
                        $processed_sections = $sections;
                        
                        // CRITICAL: Convert attachment IDs to R2 signed URLs for API response
                        $processed_sections = cpb_convert_attachment_ids_to_urls($processed_sections);
                        
                        error_log('Custom Page Builder API: Successfully decoded and processed ' . count($sections) . ' sections');
                    } else {
                        error_log('Custom Page Builder API: Failed to decode sections JSON: ' . json_last_error_msg());
                        $processed_sections = array();
                    }
                } else {
                    $processed_sections = array();
                }
                
                // Get available languages for this page
                $available_languages = \Custom_Page_Builder\Polylang_Integrator::get_available_languages_for_page($slug);
                
                // Create clean response data
                $response_data = array(
                    'id' => intval($page->id),
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'status' => $page->status,
                    'language' => $lang,
                    'requested_language' => $requested_lang,
                    'is_fallback' => $is_fallback,
                    'available_languages' => $available_languages,
                    'sections' => $processed_sections,
                    'created_at' => $page->created_at,
                    'updated_at' => $page->updated_at,
                    // R2 CDN metadata
                    'cdn_info' => array(
                        'uses_signed_urls' => true,
                        'url_refresh_notice' => 'R2 CDN signed URLs are self-contained and secure. Re-fetch page data if images fail to load (URLs may have expired).',
                        'image_fields' => array(
                            'main_image' => 'image_url',
                            'gallery' => 'gallery_urls',
                            'legacy_support' => 'image field still supported for backward compatibility'
                        )
                    )
                );
                
                error_log('Custom Page Builder API: Returning successful response');
                
                return new WP_REST_Response(array(
                    'success' => true,
                    'page' => $response_data
                ), 200);
                
            } catch (Exception $e) {
                error_log('Custom Page Builder API Exception: ' . $e->getMessage());
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Internal server error',
                    'error' => $e->getMessage()
                ), 500);
            } catch (Error $e) {
                error_log('Custom Page Builder API Fatal Error: ' . $e->getMessage());
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Fatal error occurred',
                    'error' => $e->getMessage()
                ), 500);
            }
        },
        'permission_callback' => '__return_true',
        'args' => array(
            'slug' => array(
                'description' => 'The slug of the page to retrieve',
                'type' => 'string',
                'required' => true,
                'validate_callback' => function($param) {
                    return !empty($param) && preg_match('/^[a-zA-Z0-9_-]+$/', $param);
                },
                'sanitize_callback' => 'sanitize_text_field'
            )
        )
    ));
    
    // Get all available languages in the system
    register_rest_route('custom-page-builder/v1', '/languages', array(
        'methods' => 'GET',
        'callback' => function($request) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'custom_pages';
            
            try {
                // Get all languages used in published pages with page count
                $available_languages = $wpdb->get_results(
                    "SELECT language, COUNT(*) as page_count FROM $table_name WHERE status = 'published' GROUP BY language ORDER BY language"
                );
                
                // Get current detected language
                $current_language = \Custom_Page_Builder\Polylang_Integrator::get_current_language($request);
                
                // Get configured languages from translation plugin (if any)
                $configured_languages = \Custom_Page_Builder\Polylang_Integrator::get_languages();
                
                // Get default language
                $default_language = \Custom_Page_Builder\Polylang_Integrator::get_default_language();
                
                // Language code to name mapping
                $language_names = [
                    'en' => 'English',
                    'en-us' => 'English (US)',
                    'en-gb' => 'English (UK)',
                    'fr' => 'French',
                    'de' => 'German',
                    'it' => 'Italian',
                    'es' => 'Spanish',
                    'ja' => 'Japanese',
                    'nl' => 'Dutch',
                    'pt' => 'Portuguese',
                    'zh' => 'Chinese',
                    'ar' => 'Arabic',
                    'ko' => 'Korean',
                    'ru' => 'Russian'
                ];
                
                // Add names to available languages
                foreach ($available_languages as &$lang) {
                    $lang->name = $language_names[$lang->language] ?? ucfirst($lang->language);
                }
                
                return new WP_REST_Response(array(
                    'success' => true,
                    'current_language' => $current_language,
                    'default_language' => $default_language,
                    'fallback_language' => 'en',
                    'available_languages' => $available_languages,
                    'configured_languages' => $configured_languages,
                    'translation_plugin_active' => \Custom_Page_Builder\Polylang_Integrator::is_active()
                ), 200);
                
            } catch (Exception $e) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Error fetching languages',
                    'error' => $e->getMessage()
                ), 500);
            }
        },
        'permission_callback' => '__return_true'
    ));
    
    // Get available languages for a specific page
    register_rest_route('custom-page-builder/v1', '/pages/(?P<slug>[a-zA-Z0-9_-]+)/languages', array(
        'methods' => 'GET',
        'callback' => function($request) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'custom_pages';
            $slug = sanitize_text_field($request['slug']);
            
            try {
                // Get all languages available for this specific page
                $page_languages = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, title, language, status, created_at, updated_at 
                     FROM $table_name 
                     WHERE slug = %s AND status = 'published' 
                     ORDER BY language",
                    $slug
                ));
                
                if (empty($page_languages)) {
                    return new WP_REST_Response(array(
                        'success' => false,
                        'message' => 'No pages found with this slug',
                        'slug' => $slug,
                        'available_languages' => []
                    ), 404);
                }
                
                // Language code to name mapping
                $language_names = [
                    'en' => 'English',
                    'en-us' => 'English (US)',
                    'en-gb' => 'English (UK)',
                    'fr' => 'French',
                    'de' => 'German',
                    'it' => 'Italian',
                    'es' => 'Spanish',
                    'ja' => 'Japanese',
                    'nl' => 'Dutch',
                    'pt' => 'Portuguese',
                    'zh' => 'Chinese',
                    'ar' => 'Arabic',
                    'ko' => 'Korean',
                    'ru' => 'Russian'
                ];
                
                // Build language list with details
                $languages = [];
                foreach ($page_languages as $page) {
                    $languages[] = [
                        'code' => $page->language,
                        'name' => $language_names[$page->language] ?? ucfirst($page->language),
                        'page_id' => intval($page->id),
                        'title' => $page->title,
                        'url' => "/wp-json/custom-page-builder/v1/pages/{$slug}?lang={$page->language}"
                    ];
                }
                
                // Get current detected language
                $current_language = \Custom_Page_Builder\Polylang_Integrator::get_current_language($request);
                
                // Check if current language is available
                $language_codes = array_column($languages, 'code');
                $has_current_language = in_array($current_language, $language_codes);
                
                return new WP_REST_Response(array(
                    'success' => true,
                    'slug' => $slug,
                    'current_language' => $current_language,
                    'has_current_language' => $has_current_language,
                    'available_languages' => $languages,
                    'language_count' => count($languages)
                ), 200);
                
            } catch (Exception $e) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Error fetching languages',
                    'error' => $e->getMessage()
                ), 500);
            }
        },
        'permission_callback' => '__return_true',
        'args' => array(
            'slug' => array(
                'description' => 'The slug of the page to check languages for',
                'type' => 'string',
                'required' => true
            )
        )
    ));
    
    // Debug endpoint - shows all pages with their languages
    register_rest_route('custom-page-builder/v1', '/debug/pages', array(
        'methods' => 'GET',
        'callback' => function($request) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'custom_pages';
            
            // Get all pages with their languages
            $all_pages = $wpdb->get_results(
                "SELECT id, title, slug, language, status FROM $table_name ORDER BY slug, language"
            );
            
            // Group by slug
            $grouped = [];
            foreach ($all_pages as $page) {
                if (!isset($grouped[$page->slug])) {
                    $grouped[$page->slug] = [];
                }
                $grouped[$page->slug][] = [
                    'id' => $page->id,
                    'title' => $page->title,
                    'language' => $page->language,
                    'status' => $page->status
                ];
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'total_pages' => count($all_pages),
                'unique_slugs' => count($grouped),
                'pages_by_slug' => $grouped,
                'all_pages' => $all_pages
            ), 200);
        },
        'permission_callback' => '__return_true'
    ));
});

// Add AJAX handler for category selector
add_action('wp_ajax_cpb_get_category_selector', function() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cpb_category_selector')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    $section_index = intval($_POST['section_index'] ?? 0);
    $product_index = isset($_POST['product_index']) && $_POST['product_index'] !== '' ? intval($_POST['product_index']) : null;
    $slide_index = isset($_POST['slide_index']) && $_POST['slide_index'] !== '' ? intval($_POST['slide_index']) : null;
    $type = sanitize_text_field($_POST['type'] ?? 'category');
    $selected = isset($_POST['selected']) ? wp_unslash($_POST['selected']) : '[]';

    $selected_values = [];
    if (!empty($selected)) {
        $decoded = json_decode($selected, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $selected_values = $decoded;
        } elseif (is_numeric($selected)) {
            $selected_values = [intval($selected)];
        } elseif ($selected === 'all') {
            $selected_values = ['all'];
        }
    }
    
    error_log("CPB: Loading $type selector for section $section_index, product $product_index, slide $slide_index");
    
    // Start output buffering
    ob_start();
    
    try {
        if ($type === 'category') {
            // Set field name based on whether it's a product or slide
            if ($product_index !== null) {
                $field_name = 'sections[' . $section_index . '][products][' . $product_index . '][wc_categories]';
            } elseif ($slide_index !== null) {
                $field_name = 'sections[' . $section_index . '][slides][' . $slide_index . '][wc_categories]';
            } else {
                $field_name = 'sections[' . $section_index . '][wc_categories]';
            }
            $selected_categories = $selected_values;
            $label = __('WooCommerce Product Categories', 'custom-page-builder');
            $show_all_option = false;
            
            // Include the category selector template
            $template_path = CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-category-selector.php';
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                echo '<p style="color:red;">Category selector template not found at: ' . $template_path . '</p>';
            }
        } else {
            // Set field name based on whether it's a product or slide
            if ($product_index !== null) {
                $field_name = 'sections[' . $section_index . '][products][' . $product_index . '][wc_tags]';
            } elseif ($slide_index !== null) {
                $field_name = 'sections[' . $section_index . '][slides][' . $slide_index . '][wc_tags]';
            } else {
                $field_name = 'sections[' . $section_index . '][wc_tags]';
            }
            $selected_tags = $selected_values;
            $label = __('WooCommerce Product Tags', 'custom-page-builder');
            $show_all_option = false;
            
            // Include the tag selector template
            $template_path = CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-tag-selector.php';
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                echo '<p style="color:red;">Tag selector template not found at: ' . $template_path . '</p>';
            }
        }
        
        $html = ob_get_clean();
        
        error_log("CPB: Generated HTML length: " . strlen($html));
        
        wp_send_json_success([
            'html' => $html,
            'section_index' => $section_index,
            'product_index' => $product_index,
            'slide_index' => $slide_index,
            'type' => $type
        ]);
        
    } catch (Exception $e) {
        ob_end_clean();
        error_log("CPB: Error generating selector: " . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
});

// Add AJAX handler for exporting pages
add_action('wp_ajax_cpb_export_page', function() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cpb_admin_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    
    if (!$page_id) {
        wp_send_json_error(['message' => 'Invalid page ID']);
        return;
    }
    
    $export_data = \Custom_Page_Builder\Import_Export::export_page($page_id);
    
    if (is_wp_error($export_data)) {
        wp_send_json_error(['message' => $export_data->get_error_message()]);
        return;
    }
    
    wp_send_json_success([
        'data' => $export_data,
        'filename' => sanitize_file_name($export_data['page']['slug'] . '-export.json')
    ]);
});

// Add AJAX handler for importing pages
add_action('wp_ajax_cpb_import_page', function() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cpb_admin_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    if (!isset($_FILES['import_file'])) {
        wp_send_json_error(['message' => 'No file uploaded']);
        return;
    }
    
    $file = $_FILES['import_file'];
    
    // Check file type
    if ($file['type'] !== 'application/json' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
        wp_send_json_error(['message' => 'Invalid file type. Please upload a JSON file.']);
        return;
    }
    
    // Read file contents
    $json_data = file_get_contents($file['tmp_name']);
    $import_data = json_decode($json_data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(['message' => 'Invalid JSON file: ' . json_last_error_msg()]);
        return;
    }
    
    $update_existing = isset($_POST['update_existing']) && $_POST['update_existing'] === 'true';
    
    // Check if it's a single page or multiple pages
    if (isset($import_data['page'])) {
        // Single page import
        $result = \Custom_Page_Builder\Import_Export::import_page($import_data['page'], $update_existing);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }
        
        wp_send_json_success([
            'message' => 'Page imported successfully',
            'page_id' => $result
        ]);
    } elseif (isset($import_data['pages'])) {
        // Multiple pages import
        $results = \Custom_Page_Builder\Import_Export::import_pages($import_data, $update_existing);
        
        $success_count = count($results['success']);
        $error_count = count($results['errors']);
        
        wp_send_json_success([
            'message' => sprintf(
                'Import complete: %d page(s) imported successfully, %d error(s)',
                $success_count,
                $error_count
            ),
            'results' => $results
        ]);
    } else {
        wp_send_json_error(['message' => 'Invalid import file format']);
    }
});

// Add admin menu
add_action('admin_menu', function() {
    // Main menu
    add_menu_page(
        'Custom Page Builder',
        'Page Builder',
        'manage_options',
        'custom-page-builder',
        'cpb_render_pages_list',
        'dashicons-layout',
        30
    );
    
    // Add submenu items
    add_submenu_page(
        'custom-page-builder',
        'All Pages',
        'All Pages',
        'manage_options',
        'custom-page-builder',
        'cpb_render_pages_list'
    );
    
    add_submenu_page(
        'custom-page-builder',
        'Add New Page',
        'Add New',
        'manage_options',
        'custom-page-builder-new',
        'cpb_render_new_page'
    );
});

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', function($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'custom-page-builder') === false && strpos($hook, 'page-builder') === false) {
        return;
    }
    
    // Enqueue WordPress media library
    wp_enqueue_media();
    
    // Enqueue our custom admin styles
    wp_enqueue_style(
        'cpb-admin-style',
        CUSTOM_PAGE_BUILDER_PLUGIN_URL . 'admin/assets/css/admin.css',
        array(),
        CUSTOM_PAGE_BUILDER_VERSION
    );
    
    // Enqueue import/export styles
    wp_enqueue_style(
        'cpb-import-export-style',
        CUSTOM_PAGE_BUILDER_PLUGIN_URL . 'admin/assets/css/import-export.css',
        array(),
        CUSTOM_PAGE_BUILDER_VERSION . '.' . time() // Add timestamp to force reload
    );
    
    // Enqueue our custom admin scripts
    wp_enqueue_script(
        'cpb-admin-script',
        CUSTOM_PAGE_BUILDER_PLUGIN_URL . 'admin/assets/js/admin.js',
        array('jquery', 'wp-media'),
        CUSTOM_PAGE_BUILDER_VERSION,
        true
    );
    
    // Enqueue import/export script
    wp_enqueue_script(
        'cpb-import-export-script',
        CUSTOM_PAGE_BUILDER_PLUGIN_URL . 'admin/assets/js/import-export.js',
        array('jquery'),  // Remove dependency on cpb-admin-script
        CUSTOM_PAGE_BUILDER_VERSION . '.' . time(), // Add timestamp to force reload
        true
    );
    
    // Localize script with data for BOTH scripts
    $localize_data = array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cpb_admin_nonce'),
        'strings' => array(
            'selectImage' => __('Select Image', 'custom-page-builder'),
            'useImage' => __('Use Image', 'custom-page-builder'),
            'uploadImage' => __('Upload Image', 'custom-page-builder'),
            'removeImage' => __('Remove Image', 'custom-page-builder'),
            'confirmDelete' => __('Are you sure you want to delete this?', 'custom-page-builder'),
        )
    );
    
    // Localize for admin script (if it exists)
    wp_localize_script('cpb-admin-script', 'cpbAdmin', $localize_data);
    
    // ALSO localize for import-export script (this is the fix!)
    wp_localize_script('cpb-import-export-script', 'cpbAdmin', $localize_data);
});

// Render pages list
function cpb_render_pages_list() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_pages';
    
    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['page_id'])) {
        check_admin_referer('delete_page_' . $_GET['page_id']);
        $wpdb->delete($table_name, array('id' => intval($_GET['page_id'])));
        echo '<div class="notice notice-success"><p>Page deleted successfully!</p></div>';
    }
    
    // Get all pages
    $pages = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Custom Pages</h1>
        <a href="<?php echo admin_url('admin.php?page=custom-page-builder-new'); ?>" class="page-title-action">Add New</a>
        <button type="button" class="page-title-action" id="cpb-import-page-btn">Import Page</button>
        <hr class="wp-header-end">
        
        <!-- Import Modal -->
        <div id="cpb-import-modal" style="display:none;">
            <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 4px; max-width: 500px; margin: 20px 0;">
                <h2>Import Page</h2>
                <form id="cpb-import-form" enctype="multipart/form-data">
                    <p>
                        <label for="cpb-import-file">Select JSON file to import:</label><br>
                        <input type="file" id="cpb-import-file" name="import_file" accept=".json" required>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" id="cpb-update-existing" name="update_existing" value="1">
                            Update existing pages (if slug matches)
                        </label>
                    </p>
                    <p>
                        <button type="submit" class="button button-primary">Import</button>
                        <button type="button" class="button" id="cpb-import-cancel">Cancel</button>
                    </p>
                </form>
                <div id="cpb-import-result" style="margin-top: 15px;"></div>
            </div>
        </div>
        
        <?php if (empty($pages)): ?>
            <p>No pages found. <a href="<?php echo admin_url('admin.php?page=custom-page-builder-new'); ?>">Create your first page</a></p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Slug</th>
                        <?php if (\Custom_Page_Builder\Polylang_Integrator::is_active()): ?>
                        <th>Language</th>
                        <?php endif; ?>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page): ?>
                        <tr>
                            <td><strong><?php echo esc_html($page->title); ?></strong></td>
                            <td><?php echo esc_html($page->slug); ?></td>
                            <?php if (\Custom_Page_Builder\Polylang_Integrator::is_active()): ?>
                            <td>
                                <?php 
                                $languages = \Custom_Page_Builder\Polylang_Integrator::get_languages();
                                foreach ($languages as $lang) {
                                    if ($lang['slug'] === ($page->language ?? 'en')) {
                                        if (!empty($lang['flag'])) {
                                            echo '<img src="' . esc_url($lang['flag']) . '" alt="' . esc_attr($lang['name']) . '" title="' . esc_attr($lang['name']) . '" style="height:16px;">';
                                        } else {
                                            echo esc_html($lang['name']);
                                        }
                                        break;
                                    }
                                }
                                ?>
                            </td>
                            <?php endif; ?>
                            <td><?php echo esc_html($page->status); ?></td>
                            <td><?php echo esc_html($page->created_at); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=custom-page-builder-new&edit=' . $page->id); ?>">Edit</a> |
                                <a href="#" class="cpb-export-page" data-page-id="<?php echo esc_attr($page->id); ?>" data-page-slug="<?php echo esc_attr($page->slug); ?>">Export</a> |
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=custom-page-builder&action=delete&page_id=' . $page->id), 'delete_page_' . $page->id); ?>">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

// Render new/edit page form
function cpb_render_new_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_pages';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        echo '<div class="notice notice-error"><p><strong>Error:</strong> Database table not found. <a href="' . admin_url('plugins.php') . '">Please deactivate and reactivate the plugin</a>, or <a href="' . plugins_url('fix-database.php', __FILE__) . '" target="_blank">run the database fix script</a>.</p></div>';
        return;
    }
    
    // Check if sections column exists
    $column_check = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'sections'");
    if (empty($column_check)) {
        $fix_url = wp_nonce_url(admin_url('admin.php?cpb_fix_database=1'), 'cpb_fix_database');
        echo '<div class="notice notice-error"><p><strong>Database Error:</strong> The "sections" column is missing from the database table.</p>';
        echo '<p><a href="' . $fix_url . '" class="button button-primary">Fix Database Now</a> (This will recreate the table - existing pages will be lost)</p></div>';
        
        // Debug info
        echo '<details><summary>Debug Info (click to expand)</summary>';
        echo '<p>Table: ' . $table_name . '</p>';
        echo '<p>Columns found:</p><pre>';
        $all_columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        foreach ($all_columns as $col) {
            echo $col->Field . ' (' . $col->Type . ')' . "\n";
        }
        echo '</pre></details>';
        return;
    }
    
    $page_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $page = null;
    
    if ($page_id) {
        $page = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $page_id));
    }
    
    // Handle form submission
    if (isset($_POST['cpb_save_page'])) {
        check_admin_referer('cpb_save_page');
        
        $title = sanitize_text_field($_POST['title']);
        $slug = sanitize_title($_POST['slug']);
        $status = sanitize_text_field($_POST['status']);
        $language = sanitize_text_field($_POST['language'] ?? 'en');
        
        // Ensure slug is not empty - generate from title if needed
        if (empty($slug)) {
            $slug = sanitize_title($title);
        }
        
        // Ensure slug is unique FOR THE SAME LANGUAGE ONLY
        // Same slug can exist for different languages (multilingual support)
        $existing_page = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE slug = %s AND language = %s" . ($page_id ? " AND id != %d" : ""),
            $slug,
            $language,
            ...($page_id ? [$page_id] : [])
        ));
        
        if ($existing_page) {
            // Only add counter if same slug+language combination exists
            $base_slug = $slug;
            $counter = 1;
            do {
                $slug = $base_slug . '-' . $counter;
                $counter++;
                $existing_page = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE slug = %s AND language = %s" . ($page_id ? " AND id != %d" : ""),
                    $slug,
                    $language,
                    ...($page_id ? [$page_id] : [])
                ));
            } while ($existing_page);
        }
        
        error_log("Custom Page Builder: Saving page with slug: $slug, language: $language");
        
        // Initialize secure image handler
        $secure_image_handler = null;
        if (class_exists('Custom_Page_Builder\\Secure_Image_Handler')) {
            $secure_image_handler = new \Custom_Page_Builder\Secure_Image_Handler();
        }
        
        // Process sections with enhanced nested structure support
        $sections = array();
        if (isset($_POST['sections']) && is_array($_POST['sections'])) {
            error_log('Custom Page Builder: Processing ' . count($_POST['sections']) . ' sections from form data');
            foreach ($_POST['sections'] as $section) {
                $section_type = sanitize_text_field($section['type'] ?? '');
                
                // Base section data
                $section_data = array(
                    'type' => $section_type,
                    'title' => sanitize_text_field($section['title'] ?? ''),
                    'content' => wp_kses_post($section['content'] ?? ''),
                    'order' => intval($section['order'] ?? 0)
                );
                
                // Process main section image (supports R2 CDN signed URLs)
                if (!empty($section['image_url'])) {
                    // New R2 CDN signed URL approach - DON'T use esc_url_raw as it breaks signed URLs
                    $section_data['image_url'] = $section['image_url'];
                    $section_data['image'] = $section['image_url']; // Keep backward compatibility
                } elseif (!empty($section['image'])) {
                    // Legacy image field
                    $image_value = $section['image'];
                    if ($secure_image_handler && is_numeric($image_value) && intval($image_value) > 0) {
                        $attachment_id = intval($image_value);
                        update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
                        update_post_meta($attachment_id, '_custom_page_builder_image', '1');
                        $section_data['image'] = $attachment_id;
                    } else {
                        $section_data['image'] = esc_url_raw($image_value);
                    }
                }
                
                // Process section gallery images (R2 CDN signed URLs)
                if (!empty($section['gallery_urls']) && is_array($section['gallery_urls'])) {
                    // DON'T use esc_url_raw as it breaks signed URLs with & parameters
                    $section_data['gallery_urls'] = $section['gallery_urls'];
                } elseif (!empty($section['gallery_urls']) && is_string($section['gallery_urls'])) {
                    $gallery_decoded = json_decode($section['gallery_urls'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($gallery_decoded)) {
                        // DON'T use esc_url_raw as it breaks signed URLs
                        $section_data['gallery_urls'] = $gallery_decoded;
                    }
                }
                
                // Process WooCommerce categories for all sections
                if (isset($section['wc_categories'])) {
                    $wc_categories = $section['wc_categories'];
                    // Decode if JSON string
                    if (is_string($wc_categories)) {
                        $decoded = json_decode($wc_categories, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $wc_categories = $decoded;
                        } else {
                            $wc_categories = [];
                        }
                    }
                    // Ensure it's an array
                    if (is_array($wc_categories)) {
                        $section_data['wc_categories'] = array_map('intval', array_filter($wc_categories, function($val) {
                            return $val === 'all' || is_numeric($val);
                        }));
                    }
                }
                
                // Process WooCommerce tags for all sections
                if (isset($section['wc_tags'])) {
                    $wc_tags = $section['wc_tags'];
                    // Decode if JSON string
                    if (is_string($wc_tags)) {
                        $decoded = json_decode($wc_tags, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $wc_tags = $decoded;
                        } else {
                            $wc_tags = [];
                        }
                    }
                    // Ensure it's an array
                    if (is_array($wc_tags)) {
                        $section_data['wc_tags'] = array_map('intval', array_filter($wc_tags, function($val) {
                            return $val === 'all' || is_numeric($val);
                        }));
                    }
                }
                
                // Handle section-specific data structures
                switch ($section_type) {
                    case 'hero-slider':
                        // Process slides array
                        if (isset($section['slides']) && is_array($section['slides'])) {
                            $slides = array();
                            foreach ($section['slides'] as $slide) {
                                $slide_data = array(
                                    'title' => sanitize_text_field($slide['title'] ?? ''),
                                    'content' => wp_kses_post($slide['content'] ?? ''),
                                    'button_text' => sanitize_text_field($slide['button_text'] ?? ''),
                                    'button_link' => esc_url_raw($slide['button_link'] ?? '')
                                );
                                
                                // Process slide image
                                if (!empty($slide['image'])) {
                                    $slide_image = $slide['image'];
                                    if ($secure_image_handler && is_numeric($slide_image) && intval($slide_image) > 0) {
                                        $attachment_id = intval($slide_image);
                                        update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
                                        update_post_meta($attachment_id, '_custom_page_builder_image', '1');
                                        $slide_data['image'] = $attachment_id;
                                    } else {
                                        // DON'T use esc_url_raw for R2 signed URLs
                                        $slide_data['image'] = $slide_image;
                                    }
                                }
                                
                                // Process WooCommerce categories for this slide
                                if (isset($slide['wc_categories'])) {
                                    $slide_wc_categories = $slide['wc_categories'];
                                    if (is_string($slide_wc_categories)) {
                                        $decoded = json_decode($slide_wc_categories, true);
                                        if (json_last_error() === JSON_ERROR_NONE) {
                                            $slide_wc_categories = $decoded;
                                        } else {
                                            $slide_wc_categories = [];
                                        }
                                    }
                                    if (is_array($slide_wc_categories) && !empty($slide_wc_categories)) {
                                        $filtered = array_map('intval', array_filter($slide_wc_categories, function($val) {
                                            return $val === 'all' || is_numeric($val);
                                        }));
                                        // Only add if not empty after filtering
                                        if (!empty($filtered)) {
                                            $slide_data['wc_categories'] = $filtered;
                                        }
                                    }
                                }
                                
                                // Process WooCommerce tags for this slide
                                if (isset($slide['wc_tags'])) {
                                    $slide_wc_tags = $slide['wc_tags'];
                                    if (is_string($slide_wc_tags)) {
                                        $decoded = json_decode($slide_wc_tags, true);
                                        if (json_last_error() === JSON_ERROR_NONE) {
                                            $slide_wc_tags = $decoded;
                                        } else {
                                            $slide_wc_tags = [];
                                        }
                                    }
                                    if (is_array($slide_wc_tags) && !empty($slide_wc_tags)) {
                                        $filtered = array_map('intval', array_filter($slide_wc_tags, function($val) {
                                            return $val === 'all' || is_numeric($val);
                                        }));
                                        // Only add if not empty after filtering
                                        if (!empty($filtered)) {
                                            $slide_data['wc_tags'] = $filtered;
                                        }
                                    }
                                }
                                
                                $slides[] = $slide_data;
                            }
                            $section_data['slides'] = $slides;
                        }
                        break;
                        
                    case 'custom':
                        // Process elements array
                        if (isset($section['elements']) && is_array($section['elements'])) {
                            $elements = array();
                            foreach ($section['elements'] as $element) {
                                $element_type = sanitize_text_field($element['type'] ?? '');
                                $element_data = array(
                                    'type' => $element_type,
                                    'content' => wp_kses_post($element['content'] ?? '')
                                );
                                
                                // Handle element-specific fields based on type
                                switch ($element_type) {
                                    case 'heading':
                                        $element_data['heading_level'] = sanitize_text_field($element['heading_level'] ?? 'h3');
                                        break;
                                        
                                    case 'list':
                                        $element_data['list_type'] = sanitize_text_field($element['list_type'] ?? 'ul');
                                        break;
                                        
                                    case 'image':
                                        $element_data['alt_text'] = sanitize_text_field($element['alt_text'] ?? '');
                                        // Process element image
                                        if (!empty($element['image'])) {
                                            $element_image = $element['image'];
                                            if ($secure_image_handler && is_numeric($element_image) && intval($element_image) > 0) {
                                                $attachment_id = intval($element_image);
                                                update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
                                                update_post_meta($attachment_id, '_custom_page_builder_image', '1');
                                                $element_data['image'] = $attachment_id;
                                            } else {
                                                // DON'T use esc_url_raw for R2 signed URLs
                                                $element_data['image'] = $element_image;
                                            }
                                        }
                                        break;
                                        
                                    case 'button':
                                        $element_data['button_link'] = esc_url_raw($element['button_link'] ?? '');
                                        $element_data['button_style'] = sanitize_text_field($element['button_style'] ?? 'primary');
                                        $element_data['button_target'] = sanitize_text_field($element['button_target'] ?? '_self');
                                        break;
                                }
                                
                                $elements[] = $element_data;
                            }
                            $section_data['elements'] = $elements;
                        }
                        break;
                        
                    case 'products':
                        // Process products array
                        if (isset($section['products']) && is_array($section['products'])) {
                            $products = array();
                            foreach ($section['products'] as $product) {
                                $product_data = array(
                                    'title' => sanitize_text_field($product['title'] ?? ''),
                                    'description' => wp_kses_post($product['description'] ?? ''),
                                    'badge' => sanitize_text_field($product['badge'] ?? ''),
                                    'link' => esc_url_raw($product['link'] ?? ''),
                                    'button_text' => sanitize_text_field($product['button_text'] ?? 'View Product'),
                                    'featured' => !empty($product['featured']) ? 1 : 0
                                );
                                
                                // Process product image (supports both old 'image' and new R2 CDN 'image_url')
                                if (!empty($product['image_url'])) {
                                    // New R2 CDN signed URL approach - DON'T use esc_url_raw for signed URLs
                                    $product_data['image_url'] = $product['image_url'];
                                    $product_data['image'] = $product['image_url']; // Keep backward compatibility
                                } elseif (!empty($product['image'])) {
                                    // Legacy image field
                                    $product_image = $product['image'];
                                    if ($secure_image_handler && is_numeric($product_image) && intval($product_image) > 0) {
                                        $attachment_id = intval($product_image);
                                        update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
                                        update_post_meta($attachment_id, '_custom_page_builder_image', '1');
                                        $product_data['image'] = $attachment_id;
                                    } else {
                                        // DON'T use esc_url_raw for R2 signed URLs
                                        $product_data['image'] = $product_image;
                                    }
                                }
                                
                                // Process gallery images (R2 CDN signed URLs array)
                                if (!empty($product['gallery_urls']) && is_array($product['gallery_urls'])) {
                                    // DON'T use esc_url_raw for R2 signed URLs
                                    $product_data['gallery_urls'] = $product['gallery_urls'];
                                } elseif (!empty($product['gallery_urls']) && is_string($product['gallery_urls'])) {
                                    // Handle JSON string
                                    $gallery_decoded = json_decode($product['gallery_urls'], true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($gallery_decoded)) {
                                        // DON'T use esc_url_raw for R2 signed URLs
                                        $product_data['gallery_urls'] = $gallery_decoded;
                                    }
                                }
                                
                                // Process WooCommerce categories
                                if (isset($product['wc_categories'])) {
                                    $wc_categories = $product['wc_categories'];
                                    // Decode if JSON string
                                    if (is_string($wc_categories)) {
                                        $decoded = json_decode($wc_categories, true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                            $wc_categories = $decoded;
                                        } else {
                                            $wc_categories = [];
                                        }
                                    }
                                    // Ensure it's an array
                                    if (is_array($wc_categories)) {
                                        $product_data['wc_categories'] = array_map('intval', array_filter($wc_categories, function($val) {
                                            return $val === 'all' || is_numeric($val);
                                        }));
                                    }
                                }
                                
                                // Process WooCommerce tags
                                if (isset($product['wc_tags'])) {
                                    $wc_tags = $product['wc_tags'];
                                    // Decode if JSON string
                                    if (is_string($wc_tags)) {
                                        $decoded = json_decode($wc_tags, true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                            $wc_tags = $decoded;
                                        } else {
                                            $wc_tags = [];
                                        }
                                    }
                                    // Ensure it's an array
                                    if (is_array($wc_tags)) {
                                        $product_data['wc_tags'] = array_map('intval', array_filter($wc_tags, function($val) {
                                            return $val === 'all' || is_numeric($val);
                                        }));
                                    }
                                }
                                
                                $products[] = $product_data;
                            }
                            $section_data['products'] = $products;
                        }
                        break;
                        
                    case 'testimonials':
                        // Process testimonials array
                        if (isset($section['testimonials']) && is_array($section['testimonials'])) {
                            $testimonials = array();
                            foreach ($section['testimonials'] as $testimonial) {
                                $testimonial_data = array(
                                    'content' => wp_kses_post($testimonial['content'] ?? ''),
                                    'author_name' => sanitize_text_field($testimonial['author_name'] ?? ''),
                                    'author_title' => sanitize_text_field($testimonial['author_title'] ?? ''),
                                    'company' => sanitize_text_field($testimonial['company'] ?? ''),
                                    'rating' => sanitize_text_field($testimonial['rating'] ?? '')
                                );
                                
                                // Process author image
                                if (!empty($testimonial['author_image'])) {
                                    $author_image = $testimonial['author_image'];
                                    if ($secure_image_handler && is_numeric($author_image) && intval($author_image) > 0) {
                                        $attachment_id = intval($author_image);
                                        update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
                                        update_post_meta($attachment_id, '_custom_page_builder_image', '1');
                                        $testimonial_data['author_image'] = $attachment_id;
                                    } else {
                                        // DON'T use esc_url_raw for R2 signed URLs
                                        $testimonial_data['author_image'] = $author_image;
                                    }
                                }
                                
                                $testimonials[] = $testimonial_data;
                            }
                            $section_data['testimonials'] = $testimonials;
                        }
                        break;

                    case 'features':
                        // Process items array (generic handler for list-based sections)
                        if (isset($section['items']) && is_array($section['items'])) {
                            $items = array();
                            foreach ($section['items'] as $item) {
                                $item_data = array(
                                    'title' => sanitize_text_field($item['title'] ?? ''),
                                    'content' => wp_kses_post($item['content'] ?? ''),
                                    'image' => ''
                                );
                                
                                // Process item image
                                if (!empty($item['image'])) {
                                    $item_image = $item['image'];
                                    if ($secure_image_handler && is_numeric($item_image) && intval($item_image) > 0) {
                                        $attachment_id = intval($item_image);
                                        update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
                                        update_post_meta($attachment_id, '_custom_page_builder_image', '1');
                                        $item_data['image'] = $attachment_id;
                                    } else {
                                        // DON'T use esc_url_raw for R2 signed URLs
                                        $item_data['image'] = $item_image;
                                    }
                                }
                                
                                $items[] = $item_data;
                            }
                            $section_data['items'] = $items;
                        }
                        break;
                        
                    case 'content':
                        // Process content items array
                        if (isset($section['content_items']) && is_array($section['content_items'])) {
                            $content_items = array();
                            foreach ($section['content_items'] as $item) {
                                $item_data = array(
                                    'title' => sanitize_text_field($item['title'] ?? ''),
                                    'content' => wp_kses_post($item['content'] ?? ''),
                                    'type' => sanitize_text_field($item['type'] ?? 'text'),
                                    'link' => esc_url_raw($item['link'] ?? ''),
                                    'link_text' => sanitize_text_field($item['link_text'] ?? 'Learn More')
                                );
                                
                                // Process content item image
                                if (!empty($item['image'])) {
                                    $item_image = $item['image'];
                                    if ($secure_image_handler && is_numeric($item_image) && intval($item_image) > 0) {
                                        $attachment_id = intval($item_image);
                                        update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
                                        update_post_meta($attachment_id, '_custom_page_builder_image', '1');
                                        $item_data['image'] = $attachment_id;
                                    } else {
                                        // DON'T use esc_url_raw for R2 signed URLs
                                        $item_data['image'] = $item_image;
                                    }
                                }
                                
                                $content_items[] = $item_data;
                            }
                            $section_data['content_items'] = $content_items;
                        }
                        break;
                }
                
                // Process section button options (available for all section types)
                if (!empty($section['button_text'])) {
                    $section_data['button_text'] = sanitize_text_field($section['button_text']);
                    $section_data['button_link'] = esc_url_raw($section['button_link'] ?? '');
                    $section_data['button_style'] = sanitize_text_field($section['button_style'] ?? 'primary');
                    $section_data['button_target'] = sanitize_text_field($section['button_target'] ?? '_self');
                }
                
                $sections[] = $section_data;
            }
        }
        
        // Get language from form or default
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : \Custom_Page_Builder\Polylang_Integrator::get_default_language();
        $translation_of = isset($_POST['translation_of']) ? intval($_POST['translation_of']) : 0;
        
        $data = array(
            'title' => $title,
            'slug' => $slug,
            'status' => $status,
            'language' => $language,
            'sections' => json_encode($sections),
            'updated_at' => current_time('mysql')
        );
        
        // Debug: Log what we're about to save
        error_log('Custom Page Builder: Saving ' . count($sections) . ' sections to database');
        
        if ($page_id) {
            $result = $wpdb->update($table_name, $data, array('id' => $page_id));
            if ($result !== false) {
                // If this is a translation, link it to the original
                if ($translation_of > 0) {
                    \Custom_Page_Builder\Polylang_Integrator::link_translation($page_id, $translation_of);
                }

                // Flush any object cache
                wp_cache_flush();
                
                // Redirect to the same page to prevent form resubmission and load fresh data
                $redirect_url = admin_url('admin.php?page=custom-page-builder-new&edit=' . $page_id . '&updated=1');
                wp_redirect($redirect_url);
                exit;
            } else {
                echo '<div class="notice notice-error"><p>Error updating page: ' . $wpdb->last_error . '</p></div>';
            }
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table_name, $data);
            if ($result) {
                $page_id = $wpdb->insert_id;
                
                // If this is a translation, link it to the original
                if ($translation_of > 0) {
                    \Custom_Page_Builder\Polylang_Integrator::link_translation($page_id, $translation_of);
                }

                // Flush any object cache
                wp_cache_flush();
                
                // Redirect to edit mode
                $redirect_url = admin_url('admin.php?page=custom-page-builder-new&edit=' . $page_id . '&created=1');
                wp_redirect($redirect_url);
                exit;
            } else {
                echo '<div class="notice notice-error"><p>Error creating page: ' . $wpdb->last_error . '</p></div>';
            }
        }
        
        if ($page_id) {
            $page = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $page_id));
        }
    }
    
    // CRITICAL FIX: Strip slashes from database data to prevent accumulating backslashes
    $title = $page ? stripslashes($page->title) : '';
    $slug = $page ? stripslashes($page->slug) : '';
    $status = $page ? $page->status : 'draft';
    
    // Handle sections JSON carefully - only stripslashes if needed
    $sections = array();
    if ($page && $page->sections) {
        // First try to decode without stripslashes
        $decoded = json_decode($page->sections, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $sections = $decoded;
        } else {
            // If that fails, try with stripslashes
            $decoded = json_decode(stripslashes($page->sections), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $sections = $decoded;
            } else {
                // If both fail, log the error and use empty array
                error_log('Custom Page Builder: Failed to decode sections JSON for page ID ' . ($page->id ?? 'unknown'));
                $sections = array();
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo $page_id ? 'Edit Page' : 'Add New Page'; ?></h1>
        
        <?php 
        // Show success messages after redirect
        if (isset($_GET['updated']) && $_GET['updated'] == '1') {
            $api_url = get_rest_url(null, "custom-page-builder/v1/pages/$slug");
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Page updated successfully!</strong></p>';
            if ($status === 'published') {
                echo '<p>🌐 <strong>API URL:</strong> <a href="' . $api_url . '" target="_blank">' . $api_url . '</a></p>';
            } else {
                echo '<p>💡 <em>Set status to "Published" to make this page accessible via API</em></p>';
            }
            echo '</div>';
        }
        
        if (isset($_GET['created']) && $_GET['created'] == '1') {
            $api_url = get_rest_url(null, "custom-page-builder/v1/pages/$slug");
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Page created successfully!</strong></p>';
            if ($status === 'published') {
                echo '<p>🌐 <strong>API URL:</strong> <a href="' . $api_url . '" target="_blank">' . $api_url . '</a></p>';
            } else {
                echo '<p>💡 <em>Set status to "Published" to make this page accessible via API</em></p>';
            }
            echo '</div>';
        }
        ?>
        
        <form method="post" action="" id="cpb-page-form">
            <?php wp_nonce_field('cpb_save_page'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="title">Page Title</label></th>
                    <td>
                        <input type="text" id="title" name="title" value="<?php echo esc_attr($title); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="slug">Slug</label></th>
                    <td>
                        <input type="text" id="slug" name="slug" value="<?php echo esc_attr($slug); ?>" class="regular-text" required>
                        <p class="description">URL-friendly version of the title</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="status">Status</label></th>
                    <td>
                        <select id="status" name="status">
                            <option value="draft" <?php selected($status, 'draft'); ?>>Draft</option>
                            <option value="published" <?php selected($status, 'published'); ?>>Published</option>
                            <option value="archived" <?php selected($status, 'archived'); ?>>Archived</option>
                        </select>
                    </td>
                </tr>
                <?php 
                // Debug: Show Polylang status
                if (!\Custom_Page_Builder\Polylang_Integrator::is_active()) {
                    echo '<tr><td colspan="2">';
                    echo '<div class="notice notice-info inline"><p>';
                    echo '<strong>Multi-language support:</strong> Install and activate <a href="' . admin_url('plugin-install.php?s=polylang&tab=search&type=term') . '">Polylang plugin</a> to enable multi-language pages.';
                    echo '</p></div>';
                    echo '</td></tr>';
                }
                ?>
                <?php if (\Custom_Page_Builder\Polylang_Integrator::is_active()): ?>
                <tr>
                    <th><label for="language">Language</label></th>
                    <td>
                        <?php 
                        $current_lang = $page && isset($page->language) ? $page->language : \Custom_Page_Builder\Polylang_Integrator::get_default_language();
                        $languages = \Custom_Page_Builder\Polylang_Integrator::get_languages();
                        $translation_of = isset($_GET['translation_of']) ? intval($_GET['translation_of']) : 0;
                        ?>
                        
                        <?php if ($translation_of > 0): ?>
                            <!-- Creating a new translation -->
                            <input type="hidden" name="translation_of" value="<?php echo $translation_of; ?>">
                            <select id="language" name="language" required>
                                <?php foreach ($languages as $lang): ?>
                                    <option value="<?php echo esc_attr($lang['slug']); ?>" <?php selected($current_lang, $lang['slug']); ?>>
                                        <?php echo esc_html($lang['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Select the language for this translation</p>
                        <?php elseif ($page_id): ?>
                            <!-- Editing existing page - show current language -->
                            <strong><?php 
                                foreach ($languages as $lang) {
                                    if ($lang['slug'] === $current_lang) {
                                        echo esc_html($lang['name']);
                                        if (!empty($lang['flag'])) {
                                            echo ' <img src="' . esc_url($lang['flag']) . '" alt="" style="height:12px;">';
                                        }
                                        break;
                                    }
                                }
                            ?></strong>
                            <input type="hidden" name="language" value="<?php echo esc_attr($current_lang); ?>">
                            
                            <!-- Show translation links -->
                            <?php 
                            $translations = \Custom_Page_Builder\Polylang_Integrator::get_translations($page_id);
                            if (count($languages) > 1): 
                            ?>
                            <div style="margin-top:10px;">
                                <strong>Translations:</strong>
                                <ul style="margin:5px 0;">
                                    <?php foreach ($languages as $lang): ?>
                                        <?php if ($lang['slug'] !== $current_lang): ?>
                                            <li>
                                                <?php if (isset($translations[$lang['slug']])): ?>
                                                    <a href="<?php echo admin_url('admin.php?page=custom-page-builder-new&edit=' . $translations[$lang['slug']]['id']); ?>">
                                                        <?php if (!empty($lang['flag'])): ?>
                                                            <img src="<?php echo esc_url($lang['flag']); ?>" alt="" style="height:12px;"> 
                                                        <?php endif; ?>
                                                        <?php echo esc_html($lang['name']); ?>: <?php echo esc_html($translations[$lang['slug']]['title']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php if (!empty($lang['flag'])): ?>
                                                        <img src="<?php echo esc_url($lang['flag']); ?>" alt="" style="height:12px;"> 
                                                    <?php endif; ?>
                                                    <?php echo esc_html($lang['name']); ?>: 
                                                    <a href="<?php echo admin_url('admin.php?page=custom-page-builder-new&translation_of=' . $page_id . '&lang=' . $lang['slug']); ?>">
                                                        + Add translation
                                                    </a>
                                                <?php endif; ?>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Creating new page -->
                            <select id="language" name="language">
                                <?php foreach ($languages as $lang): ?>
                                    <option value="<?php echo esc_attr($lang['slug']); ?>" <?php selected($current_lang, $lang['slug']); ?>>
                                        <?php echo esc_html($lang['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Select the language for this page</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
            
            <h2>Page Sections</h2>
            <div id="sections-container">
                <?php if (!empty($sections)): ?>
                    <?php 
                    // Debug: Log sections count
                    error_log('Custom Page Builder: Rendering ' . count($sections) . ' sections');
                    foreach ($sections as $index => $section): 
                    ?>
                        <?php cpb_render_section_form($index, $section); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Debug: No sections found -->
                    <?php error_log('Custom Page Builder: No sections to render'); ?>
                <?php endif; ?>
            </div>
            
            <p>
                <button type="button" class="button" onclick="console.log('Button clicked'); cpbAddSection('hero-slider')">+ Hero Slider (Multiple Slides)</button>
                <button type="button" class="button" onclick="console.log('Button clicked'); cpbAddSection('content')">+ Content Block</button>
                <button type="button" class="button" onclick="console.log('Button clicked'); cpbAddSection('testimonials')">+ Testimonials</button>
                <button type="button" class="button" onclick="console.log('Button clicked'); cpbAddSection('products')">+ Product Grid</button>
                <button type="button" class="button" onclick="console.log('Button clicked'); cpbAddSection('custom')">+ Custom Section</button>
            </p>
            
            <p class="submit">
                <input type="submit" name="cpb_save_page" class="button button-primary" value="<?php echo $page_id ? 'Update Page' : 'Create Page'; ?>">
                <a href="<?php echo admin_url('admin.php?page=custom-page-builder'); ?>" class="button">Cancel</a>
            </p>
        </form>
        
        <script>
        let sectionIndex = <?php echo count($sections); ?>;
        console.log('Initial section count:', sectionIndex);
        
        function cpbAddSection(type) {
            console.log('cpbAddSection called with type:', type);
            
            const container = document.getElementById('sections-container');
            if (!container) {
                console.error('sections-container element not found!');
                alert('Error: sections-container not found. Please refresh the page.');
                return;
            }
            
            console.log('Container found, creating section...');
            
            const section = document.createElement('div');
            section.className = 'section-item';
            section.style.cssText = 'border:1px solid #ccc; padding:15px; margin:10px 0; background:#f9f9f9;';
            
            let html = '<h3>Section ' + (sectionIndex + 1) + ' - ' + type.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase()) + '</h3>';
            html += '<input type="hidden" name="sections[' + sectionIndex + '][type]" value="' + type + '">';
            html += '<input type="hidden" name="sections[' + sectionIndex + '][order]" value="' + sectionIndex + '">';
            
            if (type === 'hero-slider') {
                html += '<p><strong>Hero Slider - Add Multiple Slides</strong></p>';
                html += '<div id="slides-' + sectionIndex + '">';
                try {
                    html += cpbCreateSlideHTML(sectionIndex, 0);
                } catch (e) {
                    console.error('Error creating slide HTML:', e);
                    html += '<p>Error creating slide. Please refresh the page.</p>';
                }
                html += '</div>';
                html += '<p><button type="button" class="button" onclick="cpbAddSlide(' + sectionIndex + ')">+ Add Another Slide</button></p>';
                
            } else if (type === 'custom') {
                html += '<p><label>Section Title:</label><br><input type="text" name="sections[' + sectionIndex + '][title]" class="regular-text"></p>';
                html += '<div id="custom-elements-' + sectionIndex + '">';
                try {
                    html += cpbCreateElementHTML(sectionIndex, 0);
                } catch (e) {
                    console.error('Error creating element HTML:', e);
                    html += '<p>Error creating element. Please refresh the page.</p>';
                }
                html += '</div>';
                html += '<p><button type="button" class="button" onclick="cpbAddCustomElement(' + sectionIndex + ')">+ Add Element</button></p>';
            } else if (type === 'products') {
                html += '<p><strong>Product Grid - Add Multiple Products</strong></p>';
                html += '<p><label>Section Title:</label><br><input type="text" name="sections[' + sectionIndex + '][title]" class="regular-text"></p>';
                html += '<p><label>Section Description:</label><br><textarea name="sections[' + sectionIndex + '][content]" rows="3" class="large-text"></textarea></p>';
                html += '<div id="products-' + sectionIndex + '">';
                try {
                    html += cpbCreateProductHTML(sectionIndex, 0);
                } catch (e) {
                    console.error('Error creating product HTML:', e);
                    html += '<p>Error creating product. Please refresh the page.</p>';
                }
                html += '</div>';
                html += '<p><button type="button" class="button" onclick="cpbAddProduct(' + sectionIndex + ')">+ Add Another Product</button></p>';
            } else if (type === 'testimonials') {
                html += '<p><strong>Testimonials - Add Multiple Testimonials</strong></p>';
                html += '<p><label>Section Title:</label><br><input type="text" name="sections[' + sectionIndex + '][title]" class="regular-text"></p>';
                html += '<p><label>Section Description:</label><br><textarea name="sections[' + sectionIndex + '][content]" rows="3" class="large-text"></textarea></p>';
                html += '<div id="testimonials-' + sectionIndex + '">';
                try {
                    html += cpbCreateTestimonialHTML(sectionIndex, 0);
                } catch (e) {
                    console.error('Error creating testimonial HTML:', e);
                    html += '<p>Error creating testimonial. Please refresh the page.</p>';
                }
                html += '</div>';
                html += '<p><button type="button" class="button" onclick="cpbAddTestimonial(' + sectionIndex + ')">+ Add Another Testimonial</button></p>';
            } else if (type === 'content') {
                html += '<p><strong>Content Block - Add Multiple Content Items</strong></p>';
                html += '<p><label>Section Title:</label><br><input type="text" name="sections[' + sectionIndex + '][title]" class="regular-text"></p>';
                html += '<div id="content-items-' + sectionIndex + '">';
                try {
                    html += cpbCreateContentItemHTML(sectionIndex, 0);
                } catch (e) {
                    console.error('Error creating content item HTML:', e);
                    html += '<p>Error creating content item. Please refresh the page.</p>';
                }
                html += '</div>';
                html += '<p><button type="button" class="button" onclick="cpbAddContentItem(' + sectionIndex + ')">+ Add Another Content Item</button></p>';
            } else {
                html += '<p><label>Title:</label><br><input type="text" name="sections[' + sectionIndex + '][title]" class="regular-text"></p>';
                html += '<p><label>Content:</label><br><textarea name="sections[' + sectionIndex + '][content]" rows="5" class="large-text"></textarea></p>';
                html += '<p><label>Section Image:</label><br>';
                html += '<button type="button" class="button upload-image-btn" data-target="section-image-input-' + sectionIndex + '">📁 Upload Image</button><br>';
                html += '<label style="margin-top:10px; display:block;">Or enter image URL:</label>';
                html += '<input type="text" name="sections[' + sectionIndex + '][image]" class="regular-text image-url-input" id="section-image-input-' + sectionIndex + '" placeholder="https://example.com/image.jpg or attachment ID">';
                html += '<div class="image-preview" style="display:none; margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;"></div>';
                html += '</p>';
            }
            
            // Add button options for all section types (except products which have their own buttons)
            if (type !== 'products') {
                html += '<div style="border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px;">';
                html += '<h4>Section Button (Optional)</h4>';
                html += '<p><label>Button Text:</label><br><input type="text" name="sections[' + sectionIndex + '][button_text]" class="regular-text" placeholder="Learn More"></p>';
                html += '<p><label>Button Link:</label><br><input type="text" name="sections[' + sectionIndex + '][button_link]" class="regular-text" placeholder="https://example.com"></p>';
                html += '<p><label>Button Style:</label><br><select name="sections[' + sectionIndex + '][button_style]" class="regular-text">';
                html += '<option value="primary">Primary (Blue)</option>';
                html += '<option value="secondary">Secondary (Gray)</option>';
                html += '<option value="success">Success (Green)</option>';
                html += '<option value="warning">Warning (Orange)</option>';
                html += '<option value="danger">Danger (Red)</option>';
                html += '</select></p>';
                html += '<p><label>Button Target:</label><br><select name="sections[' + sectionIndex + '][button_target]" class="regular-text">';
                html += '<option value="_self">Same Window</option>';
                html += '<option value="_blank">New Window</option>';
                html += '</select></p>';
                html += '</div>';
            }
            
            html += '<p><button type="button" class="button button-link-delete" onclick="cpbRemoveSection(this)">Remove Section</button></p>';
            
            section.innerHTML = html;
            container.appendChild(section);
            sectionIndex++;
            
            console.log('Section successfully added to DOM. Total sections:', sectionIndex);
            
            // Update section index based on actual DOM elements
            const actualSectionCount = container.querySelectorAll('.section-item').length;
            if (actualSectionCount !== sectionIndex) {
                console.log('Section index mismatch. Updating from', sectionIndex, 'to', actualSectionCount);
                sectionIndex = actualSectionCount;
            }
            
            // Load WooCommerce category selectors for products and hero-slider sections
            if (type === 'products' || type === 'hero-slider') {
                console.log('Loading WooCommerce category selectors for ' + type + ' section...');
                cpbLoadCategorySelectors(section);
            }
            
            // Trigger event for when new section is added
            if (typeof cpbOnSectionAdded === 'function') {
                cpbOnSectionAdded(type, sectionIndex - 1);
            } else {
                console.log('cpbOnSectionAdded function not available');
            }
        }
        
        // Helper function to create slide HTML
        function cpbCreateSlideHTML(sectionIndex, slideIndex) {
            let html = '<div class="slide-item" style="border-left:3px solid #0073aa; padding-left:10px; margin:10px 0;">';
            html += '<p><strong>Slide ' + (slideIndex + 1) + '</strong></p>';
            html += '<p><label>Slide Title:</label><br><input type="text" name="sections[' + sectionIndex + '][slides][' + slideIndex + '][title]" class="regular-text"></p>';
            html += '<p><label>Slide Content:</label><br><textarea name="sections[' + sectionIndex + '][slides][' + slideIndex + '][content]" rows="3" class="large-text"></textarea></p>';
            html += '<p><label>Slide Image:</label><br>';
            html += '<button type="button" class="button upload-image-btn" data-target="slide-image-input-' + sectionIndex + '-' + slideIndex + '">📁 Upload Image</button><br>';
            html += '<label style="margin-top:10px; display:block;">Or enter image URL:</label>';
            html += '<input type="text" name="sections[' + sectionIndex + '][slides][' + slideIndex + '][image]" class="regular-text image-url-input" id="slide-image-input-' + sectionIndex + '-' + slideIndex + '" placeholder="https://example.com/image.jpg or attachment ID">';
            html += '<div class="image-preview" style="display:none; margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;"></div>';
            html += '</p>';
            html += '<p><label>Button Text:</label><br><input type="text" name="sections[' + sectionIndex + '][slides][' + slideIndex + '][button_text]" class="regular-text"></p>';
            html += '<p><label>Button Link:</label><br><input type="text" name="sections[' + sectionIndex + '][slides][' + slideIndex + '][button_link]" class="regular-text"></p>';
            
            // Add WooCommerce Category and Tag Placeholders for each slide
            html += '<div class="wc-category-placeholder" data-section="' + sectionIndex + '" data-slide="' + slideIndex + '" data-selected-categories="[]" style="background:#f0f8ff; padding:15px; margin:15px 0; border:1px solid #0969da; border-radius:8px; box-shadow: 0 2px 4px rgba(9,105,218,0.1);">';
            html += '<p style="margin:0; color:#0969da; font-weight:500; font-size:13px; display:flex; align-items:center; gap:8px;"><span style="font-size:18px;">🛍️</span> Loading WooCommerce Categories...</p>';
            html += '</div>';
            
            html += '<div class="wc-tag-placeholder" data-section="' + sectionIndex + '" data-slide="' + slideIndex + '" data-selected-tags="[]" style="background:#f0f8ff; padding:15px; margin:15px 0; border:1px solid #0969da; border-radius:8px; box-shadow: 0 2px 4px rgba(9,105,218,0.1);">';
            html += '<p style="margin:0; color:#0969da; font-weight:500; font-size:13px; display:flex; align-items:center; gap:8px;"><span style="font-size:18px;">🏷️</span> Loading WooCommerce Tags...</p>';
            html += '</div>';
            
            if (slideIndex > 0) {
                html += '<p><button type="button" class="button button-link-delete" onclick="cpbRemoveSlide(this)">Remove Slide</button></p>';
            }
            html += '</div>';
            return html;
        }
        
        // Helper function to create custom element HTML
        function cpbCreateElementHTML(sectionIndex, elementIndex, elementType = 'heading', elementData = {}) {
            let html = '<div class="custom-element" style="border-left:2px solid #46b450; padding-left:10px; margin:10px 0;">';
            html += '<p><strong>Element ' + (elementIndex + 1) + '</strong></p>';
            html += '<p><label>Element Type:</label><br><select name="sections[' + sectionIndex + '][elements][' + elementIndex + '][type]" class="regular-text element-type-select" onchange="cpbUpdateElementFields(this, ' + sectionIndex + ', ' + elementIndex + ')">';
            html += '<option value="heading"' + (elementType === 'heading' ? ' selected' : '') + '>Heading</option>';
            html += '<option value="paragraph"' + (elementType === 'paragraph' ? ' selected' : '') + '>Paragraph</option>';
            html += '<option value="list"' + (elementType === 'list' ? ' selected' : '') + '>List</option>';
            html += '<option value="image"' + (elementType === 'image' ? ' selected' : '') + '>Image</option>';
            html += '<option value="button"' + (elementType === 'button' ? ' selected' : '') + '>Button</option>';
            html += '</select></p>';
            
            // Dynamic fields container
            html += '<div class="element-fields-container" id="element-fields-' + sectionIndex + '-' + elementIndex + '">';
            html += cpbGetElementFields(elementType, sectionIndex, elementIndex, elementData);
            html += '</div>';
            
            if (elementIndex > 0) {
                html += '<p><button type="button" class="button button-link-delete" onclick="cpbRemoveElement(this)">Remove Element</button></p>';
            }
            html += '</div>';
            return html;
        }
        
        // Helper function to get element-specific fields
        function cpbGetElementFields(elementType, sectionIndex, elementIndex, elementData = {}) {
            let html = '';
            
            switch (elementType) {
                case 'heading':
                    html += '<p><label>Heading Text:</label><br>';
                    html += '<input type="text" name="sections[' + sectionIndex + '][elements][' + elementIndex + '][content]" value="' + (elementData.content || '') + '" class="regular-text" placeholder="Enter heading text"></p>';
                    html += '<p><label>Heading Level:</label><br>';
                    html += '<select name="sections[' + sectionIndex + '][elements][' + elementIndex + '][heading_level]" class="regular-text">';
                    html += '<option value="h1"' + (elementData.heading_level === 'h1' ? ' selected' : '') + '>H1 (Largest)</option>';
                    html += '<option value="h2"' + (elementData.heading_level === 'h2' ? ' selected' : '') + '>H2</option>';
                    html += '<option value="h3"' + (elementData.heading_level === 'h3' || !elementData.heading_level ? ' selected' : '') + '>H3 (Default)</option>';
                    html += '<option value="h4"' + (elementData.heading_level === 'h4' ? ' selected' : '') + '>H4</option>';
                    html += '<option value="h5"' + (elementData.heading_level === 'h5' ? ' selected' : '') + '>H5</option>';
                    html += '<option value="h6"' + (elementData.heading_level === 'h6' ? ' selected' : '') + '>H6 (Smallest)</option>';
                    html += '</select></p>';
                    break;
                    
                case 'paragraph':
                    html += '<p><label>Paragraph Text:</label><br>';
                    html += '<textarea name="sections[' + sectionIndex + '][elements][' + elementIndex + '][content]" rows="4" class="large-text" placeholder="Enter paragraph text...">' + (elementData.content || '') + '</textarea></p>';
                    break;
                    
                case 'list':
                    html += '<p><label>List Type:</label><br>';
                    html += '<select name="sections[' + sectionIndex + '][elements][' + elementIndex + '][list_type]" class="regular-text">';
                    html += '<option value="ul"' + (elementData.list_type === 'ul' || !elementData.list_type ? ' selected' : '') + '>Bulleted List</option>';
                    html += '<option value="ol"' + (elementData.list_type === 'ol' ? ' selected' : '') + '>Numbered List</option>';
                    html += '</select></p>';
                    html += '<p><label>List Items (one per line):</label><br>';
                    html += '<textarea name="sections[' + sectionIndex + '][elements][' + elementIndex + '][content]" rows="4" class="large-text" placeholder="Item 1&#10;Item 2&#10;Item 3">' + (elementData.content || '') + '</textarea></p>';
                    break;
                    
                case 'image':
                    html += '<p><label>Image:</label><br>';
                    html += '<button type="button" class="button upload-image-btn" data-target="element-image-input-' + sectionIndex + '-' + elementIndex + '">📁 Upload Image</button><br>';
                    html += '<label style="margin-top:10px; display:block;">Or enter image URL:</label>';
                    html += '<input type="text" name="sections[' + sectionIndex + '][elements][' + elementIndex + '][image]" value="' + (elementData.image || '') + '" class="regular-text image-url-input" id="element-image-input-' + sectionIndex + '-' + elementIndex + '" placeholder="https://example.com/image.jpg or attachment ID">';
                    html += '<div class="image-preview" style="' + (elementData.image ? 'display:block;' : 'display:none;') + ' margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;">';
                    if (elementData.image) {
                        html += '<img src="' + elementData.image + '" style="max-width:200px; height:auto;" alt="Element image">';
                    }
                    html += '</div></p>';
                    html += '<p><label>Alt Text:</label><br>';
                    html += '<input type="text" name="sections[' + sectionIndex + '][elements][' + elementIndex + '][alt_text]" value="' + (elementData.alt_text || '') + '" class="regular-text" placeholder="Describe the image"></p>';
                    html += '<p><label>Image Caption (Optional):</label><br>';
                    html += '<input type="text" name="sections[' + sectionIndex + '][elements][' + elementIndex + '][content]" value="' + (elementData.content || '') + '" class="regular-text" placeholder="Image caption"></p>';
                    break;
                    
                case 'button':
                    html += '<p><label>Button Text:</label><br>';
                    html += '<input type="text" name="sections[' + sectionIndex + '][elements][' + elementIndex + '][content]" value="' + (elementData.content || '') + '" class="regular-text" placeholder="Click Here"></p>';
                    html += '<p><label>Button Link:</label><br>';
                    html += '<input type="text" name="sections[' + sectionIndex + '][elements][' + elementIndex + '][button_link]" value="' + (elementData.button_link || '') + '" class="regular-text" placeholder="https://example.com"></p>';
                    html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">';
                    html += '<div><p><label>Button Style:</label><br>';
                    html += '<select name="sections[' + sectionIndex + '][elements][' + elementIndex + '][button_style]" class="regular-text">';
                    html += '<option value="primary"' + (elementData.button_style === 'primary' || !elementData.button_style ? ' selected' : '') + '>Primary (Blue)</option>';
                    html += '<option value="secondary"' + (elementData.button_style === 'secondary' ? ' selected' : '') + '>Secondary (Gray)</option>';
                    html += '<option value="success"' + (elementData.button_style === 'success' ? ' selected' : '') + '>Success (Green)</option>';
                    html += '<option value="warning"' + (elementData.button_style === 'warning' ? ' selected' : '') + '>Warning (Orange)</option>';
                    html += '<option value="danger"' + (elementData.button_style === 'danger' ? ' selected' : '') + '>Danger (Red)</option>';
                    html += '</select></p></div>';
                    html += '<div><p><label>Button Target:</label><br>';
                    html += '<select name="sections[' + sectionIndex + '][elements][' + elementIndex + '][button_target]" class="regular-text">';
                    html += '<option value="_self"' + (elementData.button_target === '_self' || !elementData.button_target ? ' selected' : '') + '>Same Window</option>';
                    html += '<option value="_blank"' + (elementData.button_target === '_blank' ? ' selected' : '') + '>New Window</option>';
                    html += '</select></p></div>';
                    html += '</div>';
                    break;
                    
                default:
                    html += '<p><label>Content:</label><br>';
                    html += '<textarea name="sections[' + sectionIndex + '][elements][' + elementIndex + '][content]" rows="3" class="large-text" placeholder="Enter content...">' + (elementData.content || '') + '</textarea></p>';
            }
            
            return html;
        }
        
        // Function to update element fields when type changes
        function cpbUpdateElementFields(selectElement, sectionIndex, elementIndex) {
            const elementType = selectElement.value;
            const fieldsContainer = document.getElementById('element-fields-' + sectionIndex + '-' + elementIndex);
            
            if (fieldsContainer) {
                fieldsContainer.innerHTML = cpbGetElementFields(elementType, sectionIndex, elementIndex);
            }
        }
        
        // Helper function to create testimonial HTML
        function cpbCreateTestimonialHTML(sectionIndex, testimonialIndex) {
            let html = '<div class="testimonial-item" style="border-left:3px solid #f56e28; padding-left:10px; margin:10px 0; background:#fff; padding:15px; border-radius:4px;">';
            html += '<p><strong>Testimonial ' + (testimonialIndex + 1) + '</strong></p>';
            
            // Testimonial Content
            html += '<p><label>Testimonial Text:</label><br>';
            html += '<textarea name="sections[' + sectionIndex + '][testimonials][' + testimonialIndex + '][content]" rows="4" class="large-text" placeholder="Enter the testimonial text..."></textarea></p>';
            
            // Author Details
            html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">';
            html += '<div>';
            html += '<p><label>Author Name:</label><br><input type="text" name="sections[' + sectionIndex + '][testimonials][' + testimonialIndex + '][author_name]" class="regular-text" placeholder="John Doe"></p>';
            html += '<p><label>Author Title:</label><br><input type="text" name="sections[' + sectionIndex + '][testimonials][' + testimonialIndex + '][author_title]" class="regular-text" placeholder="CEO, Company Name"></p>';
            html += '</div>';
            html += '<div>';
            html += '<p><label>Rating (1-5):</label><br><select name="sections[' + sectionIndex + '][testimonials][' + testimonialIndex + '][rating]" class="regular-text">';
            html += '<option value="">No Rating</option>';
            html += '<option value="5" selected>5 Stars</option>';
            html += '<option value="4">4 Stars</option>';
            html += '<option value="3">3 Stars</option>';
            html += '<option value="2">2 Stars</option>';
            html += '<option value="1">1 Star</option>';
            html += '</select></p>';
            html += '<p><label>Company/Location:</label><br><input type="text" name="sections[' + sectionIndex + '][testimonials][' + testimonialIndex + '][company]" class="regular-text" placeholder="Company Name or Location"></p>';
            html += '</div>';
            html += '</div>';
            
            // Author Image
            html += '<p><label>Author Photo (Optional):</label><br>';
            html += '<button type="button" class="button upload-image-btn" data-target="testimonial-image-input-' + sectionIndex + '-' + testimonialIndex + '">📁 Upload Photo</button><br>';
            html += '<label style="margin-top:10px; display:block;">Or enter image URL:</label>';
            html += '<input type="text" name="sections[' + sectionIndex + '][testimonials][' + testimonialIndex + '][author_image]" class="regular-text image-url-input" id="testimonial-image-input-' + sectionIndex + '-' + testimonialIndex + '" placeholder="https://example.com/author.jpg or attachment ID">';
            html += '<div class="image-preview" style="display:none; margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;"></div>';
            html += '</p>';
            
            if (testimonialIndex > 0) {
                html += '<p><button type="button" class="button button-link-delete" onclick="cpbRemoveTestimonial(this)">Remove Testimonial</button></p>';
            }
            html += '</div>';
            return html;
        }
        
        // Helper function to create content item HTML
        function cpbCreateContentItemHTML(sectionIndex, itemIndex) {
            let html = '<div class="content-item" style="border-left:3px solid #826eb4; padding-left:10px; margin:10px 0; background:#fff; padding:15px; border-radius:4px;">';
            html += '<p><strong>Content Item ' + (itemIndex + 1) + '</strong></p>';
            
            // Content Item Details
            html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">';
            html += '<div>';
            html += '<p><label>Item Title:</label><br><input type="text" name="sections[' + sectionIndex + '][content_items][' + itemIndex + '][title]" class="regular-text" placeholder="Content Title"></p>';
            html += '</div>';
            html += '<div>';
            html += '<p><label>Item Type:</label><br><select name="sections[' + sectionIndex + '][content_items][' + itemIndex + '][type]" class="regular-text">';
            html += '<option value="text">Text Content</option>';
            html += '<option value="feature">Feature Item</option>';
            html += '<option value="service">Service Item</option>';
            html += '<option value="benefit">Benefit Item</option>';
            html += '<option value="step">Process Step</option>';
            html += '</select></p>';
            html += '</div>';
            html += '</div>';
            
            html += '<p><label>Content Description:</label><br>';
            html += '<textarea name="sections[' + sectionIndex + '][content_items][' + itemIndex + '][content]" rows="4" class="large-text" placeholder="Enter the content description..."></textarea></p>';
            
            // Content Image
            html += '<p><label>Content Image (Optional):</label><br>';
            html += '<button type="button" class="button upload-image-btn" data-target="content-image-input-' + sectionIndex + '-' + itemIndex + '">📁 Upload Image</button><br>';
            html += '<label style="margin-top:10px; display:block;">Or enter image URL:</label>';
            html += '<input type="text" name="sections[' + sectionIndex + '][content_items][' + itemIndex + '][image]" class="regular-text image-url-input" id="content-image-input-' + sectionIndex + '-' + itemIndex + '" placeholder="https://example.com/image.jpg or attachment ID">';
            html += '<div class="image-preview" style="display:none; margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;"></div>';
            html += '</p>';
            
            // Content Link
            html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">';
            html += '<div>';
            html += '<p><label>Link URL (Optional):</label><br><input type="text" name="sections[' + sectionIndex + '][content_items][' + itemIndex + '][link]" class="regular-text" placeholder="https://example.com"></p>';
            html += '</div>';
            html += '<div>';
            html += '<p><label>Link Text:</label><br><input type="text" name="sections[' + sectionIndex + '][content_items][' + itemIndex + '][link_text]" class="regular-text" value="Learn More"></p>';
            html += '</div>';
            html += '</div>';
            
            if (itemIndex > 0) {
                html += '<p><button type="button" class="button button-link-delete" onclick="cpbRemoveContentItem(this)">Remove Content Item</button></p>';
            }
            html += '</div>';
            return html;
        }
        
        // Helper function to create product HTML
        function cpbCreateProductHTML(sectionIndex, productIndex) {
            let html = '<div class="product-item" style="border-left:3px solid #e1a948; padding-left:10px; margin:10px 0; background:#fff; padding:15px; border-radius:4px;">';
            html += '<p><strong>Product ' + (productIndex + 1) + '</strong></p>';
            
            // Product Image
            html += '<p><label>Product Image:</label><br>';
            html += '<button type="button" class="button upload-image-btn" data-target="product-image-input-' + sectionIndex + '-' + productIndex + '">📁 Upload Image</button><br>';
            html += '<label style="margin-top:10px; display:block;">Or enter image URL:</label>';
            html += '<input type="text" name="sections[' + sectionIndex + '][products][' + productIndex + '][image]" class="regular-text image-url-input" id="product-image-input-' + sectionIndex + '-' + productIndex + '" placeholder="https://example.com/product.jpg or attachment ID">';
            html += '<div class="image-preview" style="display:none; margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;"></div>';
            html += '</p>';
            
            // Product Details
            html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">';
            html += '<div>';
            html += '<p><label>Product Title:</label><br><input type="text" name="sections[' + sectionIndex + '][products][' + productIndex + '][title]" class="regular-text" placeholder="Product Name"></p>';
            html += '</div>';
            html += '<div>';
            html += '<p><label>Badge (Optional):</label><br><input type="text" name="sections[' + sectionIndex + '][products][' + productIndex + '][badge]" class="regular-text" placeholder="Sale, New, Featured"></p>';
            html += '</div>';
            html += '</div>';
            
            html += '<p><label>Product Description:</label><br><textarea name="sections[' + sectionIndex + '][products][' + productIndex + '][description]" rows="3" class="large-text" placeholder="Brief product description..."></textarea></p>';
            
            // WooCommerce Categories - Placeholder that will be populated via AJAX
            html += '<div class="wc-category-placeholder" data-section="' + sectionIndex + '" data-product="' + productIndex + '" data-selected-categories="[]" style="background:#f0f8ff; padding:15px; margin:15px 0; border:1px solid #0969da; border-radius:8px; box-shadow: 0 2px 4px rgba(9,105,218,0.1);">';
            html += '<p style="margin:0; color:#0969da; font-weight:500; font-size:13px; display:flex; align-items:center; gap:8px;"><span style="font-size:18px;">🛍️</span> Loading WooCommerce Categories...</p>';
            html += '</div>';
            
            // WooCommerce Tags - Placeholder that will be populated via AJAX
            html += '<div class="wc-tag-placeholder" data-section="' + sectionIndex + '" data-product="' + productIndex + '" data-selected-tags="[]" style="background:#f0f8ff; padding:15px; margin:15px 0; border:1px solid #0969da; border-radius:8px; box-shadow: 0 2px 4px rgba(9,105,218,0.1);">';
            html += '<p style="margin:0; color:#0969da; font-weight:500; font-size:13px; display:flex; align-items:center; gap:8px;"><span style="font-size:18px;">🏷️</span> Loading WooCommerce Tags...</p>';
            html += '</div>';
            
            // Product Button
            html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">';
            html += '<div>';
            html += '<p><label>Button Link:</label><br><input type="text" name="sections[' + sectionIndex + '][products][' + productIndex + '][link]" class="regular-text" placeholder="https://example.com/product"></p>';
            html += '</div>';
            html += '<div>';
            html += '<p><label>Button Text:</label><br><input type="text" name="sections[' + sectionIndex + '][products][' + productIndex + '][button_text]" class="regular-text" value="View Product"></p>';
            html += '</div>';
            html += '</div>';
            
            // Product Options
            html += '<p><label><input type="checkbox" name="sections[' + sectionIndex + '][products][' + productIndex + '][featured]" value="1"> Featured Product</label></p>';
            
            if (productIndex > 0) {
                html += '<p><button type="button" class="button button-link-delete" onclick="cpbRemoveProduct(this)">Remove Product</button></p>';
            }
            html += '</div>';
            return html;
        }
        
        // Enhanced remove functions
        function cpbRemoveSection(button) {
            const section = button.closest('.section-item');
            section.style.transition = 'opacity 0.3s ease';
            section.style.opacity = '0';
            setTimeout(() => {
                section.remove();
                cpbReindexSections();
            }, 300);
        }
        
        function cpbRemoveSlide(button) {
            const slide = button.closest('.slide-item');
            slide.style.transition = 'opacity 0.3s ease';
            slide.style.opacity = '0';
            setTimeout(() => {
                slide.remove();
            }, 300);
        }
        
        function cpbRemoveElement(button) {
            const element = button.closest('.custom-element');
            element.style.transition = 'opacity 0.3s ease';
            element.style.opacity = '0';
            setTimeout(() => {
                element.remove();
            }, 300);
        }
        
        function cpbRemoveProduct(button) {
            const product = button.closest('.product-item');
            product.style.transition = 'opacity 0.3s ease';
            product.style.opacity = '0';
            setTimeout(() => {
                product.remove();
            }, 300);
        }
        
        function cpbRemoveTestimonial(button) {
            const testimonial = button.closest('.testimonial-item');
            testimonial.style.transition = 'opacity 0.3s ease';
            testimonial.style.opacity = '0';
            setTimeout(() => {
                testimonial.remove();
            }, 300);
        }
        
        function cpbRemoveContentItem(button) {
            const item = button.closest('.content-item');
            item.style.transition = 'opacity 0.3s ease';
            item.style.opacity = '0';
            setTimeout(() => {
                item.remove();
            }, 300);
        }
        
        // Reindex sections after removal
        function cpbReindexSections() {
            const sections = document.querySelectorAll('.section-item');
            sections.forEach((section, index) => {
                const title = section.querySelector('h3');
                if (title) {
                    const type = section.querySelector('input[name*="[type]"]').value;
                    title.textContent = 'Section ' + (index + 1) + ' - ' + type.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
                }
            });
        }
        
        function cpbAddSlide(sectionIndex) {
            const container = document.getElementById('slides-' + sectionIndex);
            const slideCount = container.children.length;
            const slide = document.createElement('div');
            slide.innerHTML = cpbCreateSlideHTML(sectionIndex, slideCount);
            container.appendChild(slide);
            
            // Load WooCommerce category selectors for the new slide
            const slideElement = container.children[container.children.length - 1];
            cpbLoadCategorySelectors(slideElement);
            
            // Add animation
            slide.style.opacity = '0';
            slide.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                slide.style.opacity = '1';
            }, 10);
        }
        
        function cpbAddCustomElement(sectionIndex) {
            const container = document.getElementById('custom-elements-' + sectionIndex);
            const elementCount = container.children.length;
            const element = document.createElement('div');
            element.innerHTML = cpbCreateElementHTML(sectionIndex, elementCount);
            container.appendChild(element);
            
            // Add animation
            element.style.opacity = '0';
            element.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                element.style.opacity = '1';
            }, 10);
        }
        
        function cpbAddProduct(sectionIndex) {
            const container = document.getElementById('products-' + sectionIndex);
            const productCount = container.children.length;
            const product = document.createElement('div');
            product.innerHTML = cpbCreateProductHTML(sectionIndex, productCount);
            container.appendChild(product);
            
            // Add animation
            product.style.opacity = '0';
            product.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                product.style.opacity = '1';
            }, 10);
        }
        
        function cpbAddTestimonial(sectionIndex) {
            const container = document.getElementById('testimonials-' + sectionIndex);
            const testimonialCount = container.children.length;
            const testimonial = document.createElement('div');
            testimonial.innerHTML = cpbCreateTestimonialHTML(sectionIndex, testimonialCount);
            container.appendChild(testimonial);
            
            // Add animation
            testimonial.style.opacity = '0';
            testimonial.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                testimonial.style.opacity = '1';
            }, 10);
        }
        
        function cpbAddContentItem(sectionIndex) {
            const container = document.getElementById('content-items-' + sectionIndex);
            const itemCount = container.children.length;
            const item = document.createElement('div');
            item.innerHTML = cpbCreateContentItemHTML(sectionIndex, itemCount);
            container.appendChild(item);
            
            // Add animation
            item.style.opacity = '0';
            item.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                item.style.opacity = '1';
            }, 10);
        }
        
        // Initialize WordPress Media Uploader for image upload buttons
        jQuery(document).ready(function($) {
            console.log('Initializing media uploader...');
            console.log('wp.media available:', typeof wp !== 'undefined' && typeof wp.media !== 'undefined');
            
            // Handle upload button clicks
            $(document).on('click', '.upload-image-btn', function(e) {
                e.preventDefault();
                console.log('Upload button clicked!');
                
                var button = $(this);
                var targetId = button.data('target');
                var targetInput = $('#' + targetId);
                
                console.log('Target ID:', targetId);
                console.log('Target input found:', targetInput.length > 0);
                
                // Check if wp.media is available
                if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                    console.error('wp.media not available!');
                    return;
                }
                
                // Create media frame
                var mediaFrame = wp.media({
                    title: 'Select or Upload Image',
                    button: {
                        text: 'Use This Image'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                // When image is selected
                mediaFrame.on('select', function() {
                    var attachment = mediaFrame.state().get('selection').first().toJSON();
                    console.log('Image selected:', attachment.url);
                    console.log('Attachment ID:', attachment.id);
                    
                    // Store attachment ID (for secure image processing) or URL
                    // If it's from media library, store ID so it can be marked as protected
                    if (attachment.id) {
                        targetInput.val(attachment.id);
                        targetInput.attr('data-attachment-url', attachment.url);
                    } else {
                        targetInput.val(attachment.url);
                    }
                    
                    // Find and show preview
                    var previewDiv = targetInput.siblings('.image-preview').first();
                    if (!previewDiv.length) {
                        previewDiv = targetInput.next('.image-preview');
                    }
                    
                    if (previewDiv.length) {
                        previewDiv.html('<img src="' + attachment.url + '" style="max-width:200px; height:auto;" alt="Preview">').show();
                    } else {
                        // Create preview if doesn't exist
                        targetInput.after('<div class="image-preview" style="display:block; margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;"><img src="' + attachment.url + '" style="max-width:200px; height:auto;" alt="Preview"></div>');
                    }
                    
                    console.log('Image URL set and preview shown');
                });
                
                // Open media frame
                mediaFrame.open();
                console.log('Media frame opened');
            });
            
            console.log('Media uploader initialized successfully');
        });
        
        const cpbWooCategoryAllLabel = `✨ <?php echo esc_js(__('All Categories', 'custom-page-builder')); ?>`;
        const cpbWooCategoryNoneLabel = `<?php echo esc_js(__('No categories selected', 'custom-page-builder')); ?>`;
        window.cpbWooCategoryHandlersInitialized = window.cpbWooCategoryHandlersInitialized || false;
        
        function cpbUpdateWooCategoryDisplay($wrapper) {
            const hiddenInput = $wrapper.find('.cpb-selected-wc-categories');
            let selected = [];
            try {
                selected = JSON.parse(hiddenInput.val() || '[]');
            } catch (err) {
                console.error('Error parsing JSON from hidden input:', hiddenInput.val(), err);
                selected = [];
            }
            
            console.log('Updating display for selected:', selected);
            
            const displayContainer = $wrapper.find('.selected-wc-categories-tags');
            if (selected.includes('all')) {
                displayContainer.html(`<span class="category-tag" style="display: inline-block; background: #4caf50; color: white; padding: 5px 10px; margin: 2px; border-radius: 3px; font-size: 12px;">${cpbWooCategoryAllLabel}</span>`);
                return;
            }
            
            if (selected.length > 0) {
                let html = '';
                const $checked = $wrapper.find('.wc-category-radio:checked');
                if ($checked.length) {
                    const label = $checked.closest('label').find('strong').text();
                    console.log('Displaying tag:', label);
                    html = `<span class="category-tag" style="display: inline-block; background: #0073aa; color: white; padding: 5px 10px; margin: 2px; border-radius: 3px; font-size: 12px;">${label}</span>`;
                }
                displayContainer.html(html);
                return;
            }
            
            displayContainer.html(`<span style="color: #999; font-style: italic;">${cpbWooCategoryNoneLabel}</span>`);
        }
        
        function cpbSyncWooCategorySelection($wrapper) {
            const hiddenInput = $wrapper.find('.cpb-selected-wc-categories');
            const categoriesList = $wrapper.find('.wc-categories-list');
            const allCheckbox = $wrapper.find('.wc-category-all-checkbox');
            let currentValue = hiddenInput.val();
            
            console.log('Initial hidden input value:', currentValue);
            
            if ((!currentValue || currentValue === '[]' || currentValue === '') && $wrapper.find('.wc-category-radio:checked').length) {
                const checkedVal = $wrapper.find('.wc-category-radio:checked').val();
                hiddenInput.val(JSON.stringify([parseInt(checkedVal, 10)]));
                console.log('Synced hidden input from checked radio:', hiddenInput.val());
                currentValue = hiddenInput.val();
            }
            
            let parsed = [];
            try {
                parsed = JSON.parse(currentValue || '[]');
            } catch (err) {
                console.error('Error parsing initial JSON:', currentValue, err);
                parsed = [];
            }
            
            if (parsed.indexOf('all') !== -1) {
                allCheckbox.prop('checked', true);
                categoriesList.css({'opacity': '0.5', 'pointer-events': 'none'});
            } else {
                allCheckbox.prop('checked', false);
                categoriesList.css({'opacity': '1', 'pointer-events': 'auto'});
                
                if (parsed.length) {
                    const targetId = parseInt(parsed[0], 10);
                    $wrapper.find('.wc-category-radio').each(function() {
                        const isMatch = parseInt(jQuery(this).val(), 10) === targetId;
                        jQuery(this).prop('checked', isMatch);
                    });
                }
            }
            
            cpbUpdateWooCategoryDisplay($wrapper);
        }
        
        function cpbInitWooCategorySelectors(context) {
            const $context = jQuery(context || document);
            const wrappersFound = $context.find('.wc-category-selector-wrapper').length;
            console.log('Initializing WC Category Selector context; wrappers found:', wrappersFound);
            $context.find('.wc-category-selector-wrapper').each(function() {
                cpbSyncWooCategorySelection(jQuery(this));
            });
        }
        
        if (!window.cpbWooCategoryHandlersInitialized) {
            jQuery(document).on('change', '.wc-category-all-checkbox', function() {
                console.log('WC All Categories checkbox toggled');
                const $wrapper = jQuery(this).closest('.wc-category-selector-wrapper');
                const $categoriesList = $wrapper.find('.wc-categories-list');
                const $hiddenInput = $wrapper.find('.cpb-selected-wc-categories');
                
                if (jQuery(this).is(':checked')) {
                    $categoriesList.css({'opacity': '0.5', 'pointer-events': 'none'});
                    $hiddenInput.val(JSON.stringify(['all']));
                } else {
                    $categoriesList.css({'opacity': '1', 'pointer-events': 'auto'});
                    const $checked = $wrapper.find('.wc-category-radio:checked');
                    const selected = $checked.length ? [parseInt($checked.val(), 10)] : [];
                    $hiddenInput.val(JSON.stringify(selected));
                }
                
                cpbUpdateWooCategoryDisplay($wrapper);
            });
            
            jQuery(document).on('change', '.wc-category-radio', function() {
                console.log('WC Category Radio Changed');
                const $wrapper = jQuery(this).closest('.wc-category-selector-wrapper');
                const $hiddenInput = $wrapper.find('.cpb-selected-wc-categories');
                
                const selectedVal = jQuery(this).val();
                console.log('Selected value:', selectedVal);
                
                const selected = (typeof selectedVal !== 'undefined' && selectedVal !== null)
                    ? [parseInt(selectedVal, 10)]
                    : [];
                
                const jsonSelected = JSON.stringify(selected);
                $hiddenInput.val(jsonSelected);
                console.log('Hidden input updated with:', jsonSelected);
                
                $wrapper.find('.wc-category-all-checkbox').prop('checked', false);
                $wrapper.find('.wc-categories-list').css({'opacity': '1', 'pointer-events': 'auto'});
                
                cpbUpdateWooCategoryDisplay($wrapper);
            });
            
            window.cpbWooCategoryHandlersInitialized = true;
        }
        
        jQuery(function() {
            cpbInitWooCategorySelectors(document);
            const sections = document.querySelectorAll('.section-item');
            sections.forEach(function(section, idx) {
                const hasCategoryPlaceholder = !!section.querySelector('.wc-category-placeholder');
                const hasTagPlaceholder = !!section.querySelector('.wc-tag-placeholder');
                console.log('Section', idx, 'category placeholder:', hasCategoryPlaceholder, 'tag placeholder:', hasTagPlaceholder);
                cpbLoadCategorySelectors(section);
            });
        });
        
        // Callback system for section events
        window.cpbOnSectionAdded = function(type, index) {
            console.log('Section added:', type, 'at index:', index);
            
            // Auto-focus on the first input of the new section
            const newSection = document.querySelectorAll('.section-item')[index];
            if (newSection) {
                const firstInput = newSection.querySelector('input[type="text"], textarea');
                if (firstInput) {
                    setTimeout(() => {
                        firstInput.focus();
                        firstInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }
            }
        };
        
        // Enhanced form validation
        document.getElementById('cpb-page-form').addEventListener('submit', function(e) {
            const sections = document.querySelectorAll('.section-item');
            if (sections.length === 0) {
                e.preventDefault();
                alert('Please add at least one section to your page.');
                return false;
            }
            
            // Validate hero-slider sections have at least one slide
            const heroSliders = document.querySelectorAll('input[name*="[type]"][value="hero-slider"]');
            for (let slider of heroSliders) {
                const sectionContainer = slider.closest('.section-item');
                const slides = sectionContainer.querySelectorAll('.slide-item');
                if (slides.length === 0) {
                    e.preventDefault();
                    alert('Hero slider sections must have at least one slide.');
                    return false;
                }
            }
            
            // Validate custom sections have at least one element
            const customSections = document.querySelectorAll('input[name*="[type]"][value="custom"]');
            for (let section of customSections) {
                const sectionContainer = section.closest('.section-item');
                const elements = sectionContainer.querySelectorAll('.custom-element');
                if (elements.length === 0) {
                    e.preventDefault();
                    alert('Custom sections must have at least one element.');
                    return false;
                }
            }
            
            return true;
        });
        
        // Function to load WooCommerce category selectors via AJAX
        function cpbApplyPreselection(container, selectedJson, type) {
            let selected;
            try {
                selected = JSON.parse(selectedJson || '[]');
            } catch (err) {
                console.error('❌ Failed to parse preselection JSON:', selectedJson, err);
                selected = [];
            }
            if (!Array.isArray(selected) || selected.length === 0) {
                return;
            }

            const wrapperClass = type === 'tag' ? '.wc-tag-selector-wrapper' : '.wc-category-selector-wrapper';
            const checkboxSelector = type === 'tag' ? '.wc-tag-checkbox' : '.wc-category-radio';
            const allSelector = type === 'tag' ? '.wc-tag-all-checkbox' : '.wc-category-all-checkbox';
            const hiddenSelector = type === 'tag' ? '.cpb-selected-wc-tags' : '.cpb-selected-wc-categories';
            const listSelector = type === 'tag' ? '.wc-tags-list' : '.wc-categories-list';

            const wrapper = container.querySelector(wrapperClass);
            if (!wrapper) {
                return;
            }

            const hiddenInput = wrapper.querySelector(hiddenSelector);
            const allCheckbox = wrapper.querySelector(allSelector);
            const listContainer = wrapper.querySelector(listSelector);

            if (selected.includes('all') && allCheckbox) {
                allCheckbox.checked = true;
                if (listContainer) {
                    listContainer.style.opacity = '0.5';
                    listContainer.style.pointerEvents = 'none';
                }
                if (hiddenInput) {
                    hiddenInput.value = JSON.stringify(['all']);
                }
                return;
            }

            // For tags (multiple selection) or categories (single selection)
            const inputs = wrapper.querySelectorAll(checkboxSelector);
            if (type === 'tag') {
                // Multiple selection for tags - check all matching checkboxes
                inputs.forEach(function(input) {
                    const inputVal = parseInt(input.value, 10);
                    if (selected.includes(inputVal)) {
                        input.checked = true;
                    }
                });
            } else {
                // Single selection for categories - check only the first match
                const targetId = parseInt(selected[0], 10);
                inputs.forEach(function(input) {
                    const inputVal = parseInt(input.value, 10);
                    if (inputVal === targetId) {
                        input.checked = true;
                    }
                });
            }

            if (hiddenInput) {
                hiddenInput.value = JSON.stringify(selected);
            }
            if (allCheckbox) {
                allCheckbox.checked = false;
            }
        }

        function cpbLoadCategorySelectors(sectionElement) {
            console.log('🔵 cpbLoadCategorySelectors called');
            
            // Find all category and tag placeholders in this section
            const categoryPlaceholders = sectionElement.querySelectorAll('.wc-category-placeholder');
            const tagPlaceholders = sectionElement.querySelectorAll('.wc-tag-placeholder');
            
            console.log('🔵 Found', categoryPlaceholders.length, 'category placeholders');
            console.log('🔵 Found', tagPlaceholders.length, 'tag placeholders');
            
            // Load categories for each placeholder
            categoryPlaceholders.forEach(function(placeholder) {
                const sectionIndex = placeholder.getAttribute('data-section');
                const productIndex = placeholder.getAttribute('data-product');
                const slideIndex = placeholder.getAttribute('data-slide');
                const selectedCategories = placeholder.getAttribute('data-selected-categories') || '[]';
                
                console.log('🔵 Loading categories for section', sectionIndex, 'product', productIndex, 'slide', slideIndex, 'with selected', selectedCategories);
                
                placeholder.innerHTML = '<p style="margin:0; color:#0969da; font-weight:500; font-size:13px; display:flex; align-items:center; gap:8px;"><span style="font-size:18px; animation: spin 1s linear infinite;">⏳</span> Loading WooCommerce Categories...</p><style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>';
                
                const payload = new URLSearchParams({
                    action: 'cpb_get_category_selector',
                    nonce: '<?php echo wp_create_nonce('cpb_category_selector'); ?>',
                    section_index: sectionIndex,
                    product_index: productIndex || '',
                    slide_index: slideIndex || '',
                    type: 'category'
                });
                payload.append('selected', selectedCategories);
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: payload
                })
                .then(response => response.json())
                .then(data => {
                    console.log('✅ Category selector response:', data);
                    if (data.success) {
                        placeholder.innerHTML = data.data.html;
                        placeholder.style.background = 'transparent';
                        placeholder.style.border = 'none';
                        placeholder.style.padding = '0';
                        placeholder.style.margin = '15px 0';
                        if (selectedCategories && selectedCategories !== '[]') {
                            cpbApplyPreselection(placeholder, selectedCategories, 'category');
                        }
                        cpbInitWooCategorySelectors(placeholder);
                    } else {
                        placeholder.innerHTML = '<p style="margin:0; color:#d32f2f; background:#ffebee; padding:15px; border-radius:6px; border:1px solid #ef9a9a;">❌ Error loading categories: ' + (data.data ? data.data.message : 'Unknown error') + '</p>';
                    }
                })
                .catch(error => {
                    console.error('❌ Error loading categories:', error);
                    placeholder.innerHTML = '<p style="margin:0; color:#d32f2f;">❌ Error loading categories. Check console for details.</p>';
                });
            });
            
            // Load tags for each placeholder
            tagPlaceholders.forEach(function(placeholder) {
                const sectionIndex = placeholder.getAttribute('data-section');
                const productIndex = placeholder.getAttribute('data-product');
                const slideIndex = placeholder.getAttribute('data-slide');
                const selectedTags = placeholder.getAttribute('data-selected-tags') || '[]';
                
                console.log('🔵 Loading tags for section', sectionIndex, 'product', productIndex, 'slide', slideIndex, 'with selected', selectedTags);
                
                placeholder.innerHTML = '<p style="margin:0; color:#0969da; font-weight:500; font-size:13px; display:flex; align-items:center; gap:8px;"><span style="font-size:18px; animation: spin 1s linear infinite;">⏳</span> Loading WooCommerce Tags...</p><style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>';
                
                const payload = new URLSearchParams({
                    action: 'cpb_get_category_selector',
                    nonce: '<?php echo wp_create_nonce('cpb_category_selector'); ?>',
                    section_index: sectionIndex,
                    product_index: productIndex || '',
                    slide_index: slideIndex || '',
                    type: 'tag'
                });
                payload.append('selected', selectedTags);
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: payload
                })
                .then(response => response.json())
                .then(data => {
                    console.log('✅ Tag selector response:', data);
                    if (data.success) {
                        placeholder.innerHTML = data.data.html;
                        placeholder.style.background = 'transparent';
                        placeholder.style.border = 'none';
                        placeholder.style.padding = '0';
                        placeholder.style.margin = '15px 0';
                        if (selectedTags && selectedTags !== '[]') {
                            cpbApplyPreselection(placeholder, selectedTags, 'tag');
                        }
                        
                        // Execute any scripts in the loaded content
                        const scripts = placeholder.querySelectorAll('script');
                        scripts.forEach(function(script) {
                            const newScript = document.createElement('script');
                            newScript.textContent = script.textContent;
                            script.parentNode.replaceChild(newScript, script);
                        });
                    } else {
                        placeholder.innerHTML = '<p style="margin:0; color:#d32f2f; background:#ffebee; padding:15px; border-radius:6px; border:1px solid #ef9a9a;">❌ Error loading tags: ' + (data.data ? data.data.message : 'Unknown error') + '</p>';
                    }
                })
                .catch(error => {
                    console.error('❌ Error loading tags:', error);
                    placeholder.innerHTML = '<p style="margin:0; color:#d32f2f;">❌ Error loading tags. Check console for details.</p>';
                });
            });
        }
        </script>
    </div>
    <?php
}

// Helper function to render element fields based on type
function cpb_render_element_fields($element, $section_index, $elem_index) {
    $element_type = $element['type'] ?? 'heading';
    
    switch ($element_type) {
        case 'heading':
            ?>
            <p><label>Heading Text:</label><br>
            <input type="text" name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][content]" value="<?php echo esc_attr(stripslashes($element['content'] ?? '')); ?>" class="regular-text" placeholder="Enter heading text"></p>
            <p><label>Heading Level:</label><br>
            <select name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][heading_level]" class="regular-text">
                <option value="h1" <?php selected($element['heading_level'] ?? '', 'h1'); ?>>H1 (Largest)</option>
                <option value="h2" <?php selected($element['heading_level'] ?? '', 'h2'); ?>>H2</option>
                <option value="h3" <?php selected($element['heading_level'] ?? 'h3', 'h3'); ?>>H3 (Default)</option>
                <option value="h4" <?php selected($element['heading_level'] ?? '', 'h4'); ?>>H4</option>
                <option value="h5" <?php selected($element['heading_level'] ?? '', 'h5'); ?>>H5</option>
                <option value="h6" <?php selected($element['heading_level'] ?? '', 'h6'); ?>>H6 (Smallest)</option>
            </select></p>
            <?php
            break;
            
        case 'paragraph':
            ?>
            <p><label>Paragraph Text:</label><br>
            <textarea name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][content]" rows="4" class="large-text" placeholder="Enter paragraph text..."><?php echo esc_textarea(stripslashes($element['content'] ?? '')); ?></textarea></p>
            <?php
            break;
            
        case 'list':
            ?>
            <p><label>List Type:</label><br>
            <select name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][list_type]" class="regular-text">
                <option value="ul" <?php selected($element['list_type'] ?? 'ul', 'ul'); ?>>Bulleted List</option>
                <option value="ol" <?php selected($element['list_type'] ?? '', 'ol'); ?>>Numbered List</option>
            </select></p>
            <p><label>List Items (one per line):</label><br>
            <textarea name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][content]" rows="4" class="large-text" placeholder="Item 1&#10;Item 2&#10;Item 3"><?php echo esc_textarea(stripslashes($element['content'] ?? '')); ?></textarea></p>
            <?php
            break;
            
        case 'image':
            ?>
            <p><label>Image:</label><br>
            <button type="button" class="button upload-image-btn" data-target="existing-element-image-input-<?php echo $section_index; ?>-<?php echo $elem_index; ?>">📁 Upload Image</button><br>
            <label style="margin-top:10px; display:block;">Or enter image URL:</label>
            <input type="text" name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][image]" value="<?php echo esc_attr($element['image'] ?? ''); ?>" class="regular-text image-url-input" id="existing-element-image-input-<?php echo $section_index; ?>-<?php echo $elem_index; ?>" placeholder="https://example.com/image.jpg or attachment ID">
            <div class="image-preview" style="<?php echo empty($element['image']) ? 'display:none;' : 'display:block;'; ?> margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;">
                <?php if (!empty($element['image'])): ?>
                    <?php 
                    $element_image_url = $element['image'];
                    // If it's a numeric attachment ID, convert to URL
                    if (is_numeric($element_image_url)) {
                        $attachment_url = wp_get_attachment_url(intval($element_image_url));
                        if ($attachment_url) {
                            $element_image_url = $attachment_url;
                        }
                    }
                    ?>
                    <img src="<?php echo esc_url($element_image_url); ?>" style="max-width:200px; height:auto;" alt="Element image">
                <?php endif; ?>
            </div></p>
            <p><label>Alt Text:</label><br>
            <input type="text" name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][alt_text]" value="<?php echo esc_attr(stripslashes($element['alt_text'] ?? '')); ?>" class="regular-text" placeholder="Describe the image"></p>
            <p><label>Image Caption (Optional):</label><br>
            <input type="text" name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][content]" value="<?php echo esc_attr(stripslashes($element['content'] ?? '')); ?>" class="regular-text" placeholder="Image caption"></p>
            <?php
            break;
            
        case 'button':
            ?>
            <p><label>Button Text:</label><br>
            <input type="text" name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][content]" value="<?php echo esc_attr(stripslashes($element['content'] ?? '')); ?>" class="regular-text" placeholder="Click Here"></p>
            <p><label>Button Link:</label><br>
            <input type="text" name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][button_link]" value="<?php echo esc_attr(stripslashes($element['button_link'] ?? '')); ?>" class="regular-text" placeholder="https://example.com"></p>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div>
                    <p><label>Button Style:</label><br>
                    <select name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][button_style]" class="regular-text">
                        <option value="primary" <?php selected($element['button_style'] ?? 'primary', 'primary'); ?>>Primary (Blue)</option>
                        <option value="secondary" <?php selected($element['button_style'] ?? '', 'secondary'); ?>>Secondary (Gray)</option>
                        <option value="success" <?php selected($element['button_style'] ?? '', 'success'); ?>>Success (Green)</option>
                        <option value="warning" <?php selected($element['button_style'] ?? '', 'warning'); ?>>Warning (Orange)</option>
                        <option value="danger" <?php selected($element['button_style'] ?? '', 'danger'); ?>>Danger (Red)</option>
                    </select></p>
                </div>
                <div>
                    <p><label>Button Target:</label><br>
                    <select name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][button_target]" class="regular-text">
                        <option value="_self" <?php selected($element['button_target'] ?? '_self', '_self'); ?>>Same Window</option>
                        <option value="_blank" <?php selected($element['button_target'] ?? '', '_blank'); ?>>New Window</option>
                    </select></p>
                </div>
            </div>
            <?php
            break;
            
        default:
            ?>
            <p><label>Content:</label><br>
            <textarea name="sections[<?php echo $section_index; ?>][elements][<?php echo $elem_index; ?>][content]" rows="3" class="large-text" placeholder="Enter content..."><?php echo esc_textarea(stripslashes($element['content'] ?? '')); ?></textarea></p>
            <?php
    }
}

// Helper function to render section form
function cpb_render_section_form($index, $section) {
    $type = $section['type'] ?? 'content';
    ?>
    <div class="section-item" style="border:1px solid #ccc; padding:15px; margin:10px 0; background:#f9f9f9;">
        <h3>Section <?php echo $index + 1; ?> - <?php echo ucwords(str_replace('-', ' ', $type)); ?></h3>
        <input type="hidden" name="sections[<?php echo $index; ?>][type]" value="<?php echo esc_attr($type); ?>">
        <input type="hidden" name="sections[<?php echo $index; ?>][order]" value="<?php echo $index; ?>">
        
        <?php if ($type === 'hero-slider'): ?>
            <p><strong>Hero Slider - Multiple Slides</strong></p>
            <div id="slides-<?php echo $index; ?>">
                <?php 
                $slides = $section['slides'] ?? array();
                foreach ($slides as $slide_index => $slide): 
                ?>
                    <div class="slide-item" style="border-left:3px solid #0073aa; padding-left:10px; margin:10px 0;">
                        <p><strong>Slide <?php echo $slide_index + 1; ?></strong></p>
                        <p><label>Slide Title:</label><br>
                        <input type="text" name="sections[<?php echo $index; ?>][slides][<?php echo $slide_index; ?>][title]" value="<?php echo esc_attr(stripslashes($slide['title'] ?? '')); ?>" class="regular-text"></p>
                        <p><label>Slide Content:</label><br>
                        <textarea name="sections[<?php echo $index; ?>][slides][<?php echo $slide_index; ?>][content]" rows="3" class="large-text"><?php echo esc_textarea(stripslashes($slide['content'] ?? '')); ?></textarea></p>
                        <p><label>Slide Image:</label><br>
                        <button type="button" class="button upload-image-btn" data-target="existing-slide-image-input-<?php echo $index; ?>-<?php echo $slide_index; ?>">📁 Upload Image</button><br>
                        <label style="margin-top:10px; display:block;">Or enter image URL:</label>
                        <input type="text" name="sections[<?php echo $index; ?>][slides][<?php echo $slide_index; ?>][image]" value="<?php echo esc_attr($slide['image'] ?? ''); ?>" class="regular-text image-url-input" id="existing-slide-image-input-<?php echo $index; ?>-<?php echo $slide_index; ?>" placeholder="https://example.com/image.jpg or attachment ID">
                        <div class="image-preview" style="<?php echo empty($slide['image']) ? 'display:none;' : 'display:block;'; ?> margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;">
                            <?php if (!empty($slide['image'])): ?>
                                <?php 
                                $slide_image_url = $slide['image'];
                                // If it's a numeric attachment ID, convert to URL
                                if (is_numeric($slide_image_url)) {
                                    $attachment_url = wp_get_attachment_url(intval($slide_image_url));
                                    if ($attachment_url) {
                                        $slide_image_url = $attachment_url;
                                    }
                                }
                                ?>
                                <img src="<?php echo esc_url($slide_image_url); ?>" style="max-width:200px; height:auto;" alt="Slide image">
                            <?php endif; ?>
                        </div></p>
                        <p><label>Button Text:</label><br>
                        <input type="text" name="sections[<?php echo $index; ?>][slides][<?php echo $slide_index; ?>][button_text]" value="<?php echo esc_attr(stripslashes($slide['button_text'] ?? '')); ?>" class="regular-text"></p>
                        <p><label>Button Link:</label><br>
                        <input type="text" name="sections[<?php echo $index; ?>][slides][<?php echo $slide_index; ?>][button_link]" value="<?php echo esc_attr(stripslashes($slide['button_link'] ?? '')); ?>" class="regular-text"></p>
                        
                        <?php
                        // WooCommerce Category Selector for this slide
                        $slide_categories_raw = $slide['wc_categories'] ?? [];
                        if (is_string($slide_categories_raw)) {
                            $decoded_categories = json_decode($slide_categories_raw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_categories)) {
                                $slide_categories_raw = $decoded_categories;
                            } elseif ($slide_categories_raw === '' || $slide_categories_raw === null) {
                                $slide_categories_raw = [];
                            } else {
                                $slide_categories_raw = array($slide_categories_raw);
                            }
                        } elseif (!is_array($slide_categories_raw)) {
                            $slide_categories_raw = [];
                        }
                        $slide_categories_json = wp_json_encode($slide_categories_raw);
                        if (empty($slide_categories_json)) {
                            $slide_categories_json = '[]';
                        }

                        $slide_tags_raw = $slide['wc_tags'] ?? [];
                        if (is_string($slide_tags_raw)) {
                            $decoded_tags = json_decode($slide_tags_raw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_tags)) {
                                $slide_tags_raw = $decoded_tags;
                            } elseif ($slide_tags_raw === '' || $slide_tags_raw === null) {
                                $slide_tags_raw = [];
                            } else {
                                $slide_tags_raw = array($slide_tags_raw);
                            }
                        } elseif (!is_array($slide_tags_raw)) {
                            $slide_tags_raw = [];
                        }
                        $slide_tags_json = wp_json_encode($slide_tags_raw);
                        if (empty($slide_tags_json)) {
                            $slide_tags_json = '[]';
                        }
                        ?>

                        <div class="wc-category-placeholder" data-section="<?php echo $index; ?>" data-slide="<?php echo $slide_index; ?>" data-selected-categories="<?php echo esc_attr($slide_categories_json); ?>" style="background:#f0f8ff; padding:15px; margin:15px 0; border:1px solid #0969da; border-radius:8px; box-shadow: 0 2px 4px rgba(9,105,218,0.1);">
                            <p style="margin:0; color:#0969da; font-weight:500; font-size:13px; display:flex; align-items:center; gap:8px;"><span style="font-size:18px;">🛍️</span> Loading WooCommerce Categories...</p>
                        </div>

                        <div class="wc-tag-placeholder" data-section="<?php echo $index; ?>" data-slide="<?php echo $slide_index; ?>" data-selected-tags="<?php echo esc_attr($slide_tags_json); ?>" style="background:#f0f8ff; padding:15px; margin:15px 0; border:1px solid #0969da; border-radius:8px; box-shadow: 0 2px 4px rgba(9,105,218,0.1);">
                            <p style="margin:0; color:#0969da; font-weight:500; font-size:13px; display:flex; align-items:center; gap:8px;"><span style="font-size:18px;">🏷️</span> Loading WooCommerce Tags...</p>
                        </div>
                        
                        <p><button type="button" class="button button-link-delete" onclick="this.parentElement.remove()">Remove Slide</button></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button" onclick="cpbAddSlide(<?php echo $index; ?>)">+ Add Another Slide</button></p>
            
        <?php elseif ($type === 'custom'): ?>
            <p><label>Section Title:</label><br>
            <input type="text" name="sections[<?php echo $index; ?>][title]" value="<?php echo esc_attr(stripslashes($section['title'] ?? '')); ?>" class="regular-text"></p>
            <div id="custom-elements-<?php echo $index; ?>">
                <?php 
                $elements = $section['elements'] ?? array();
                foreach ($elements as $elem_index => $element): 
                ?>
                    <div class="custom-element" style="border-left:2px solid #46b450; padding-left:10px; margin:10px 0;">
                        <p><strong>Element <?php echo $elem_index + 1; ?></strong></p>
                        <p><label>Element Type:</label><br>
                        <select name="sections[<?php echo $index; ?>][elements][<?php echo $elem_index; ?>][type]" class="regular-text element-type-select" onchange="cpbUpdateElementFields(this, <?php echo $index; ?>, <?php echo $elem_index; ?>)">
                            <option value="heading" <?php selected($element['type'] ?? '', 'heading'); ?>>Heading</option>
                            <option value="paragraph" <?php selected($element['type'] ?? '', 'paragraph'); ?>>Paragraph</option>
                            <option value="list" <?php selected($element['type'] ?? '', 'list'); ?>>List</option>
                            <option value="image" <?php selected($element['type'] ?? '', 'image'); ?>>Image</option>
                            <option value="button" <?php selected($element['type'] ?? '', 'button'); ?>>Button</option>
                        </select></p>
                        
                        <!-- Dynamic fields container -->
                        <div class="element-fields-container" id="element-fields-<?php echo $index; ?>-<?php echo $elem_index; ?>">
                            <?php cpb_render_element_fields($element, $index, $elem_index); ?>
                        </div>
                        
                        <p><button type="button" class="button button-link-delete" onclick="this.parentElement.remove()">Remove Element</button></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button" onclick="cpbAddCustomElement(<?php echo $index; ?>)">+ Add Element</button></p>
            
        <?php elseif ($type === 'products'): ?>
            <p><strong>Product Grid - Multiple Products</strong></p>
            <p><label>Section Title:</label><br>
            <input type="text" name="sections[<?php echo $index; ?>][title]" value="<?php echo esc_attr(stripslashes($section['title'] ?? '')); ?>" class="regular-text"></p>
            <p><label>Section Description:</label><br>
            <textarea name="sections[<?php echo $index; ?>][content]" rows="3" class="large-text"><?php echo esc_textarea(stripslashes($section['content'] ?? '')); ?></textarea></p>
            <div id="products-<?php echo $index; ?>">
                <?php 
                $products = $section['products'] ?? array();
                foreach ($products as $product_index => $product): 
                ?>
                    <div class="product-item" style="border-left:3px solid #e1a948; padding-left:10px; margin:10px 0; background:#fff; padding:15px; border-radius:4px;">
                        <p><strong>Product <?php echo $product_index + 1; ?></strong></p>
                        
                        <!-- Product Image -->
                        <p><label>Product Image:</label><br>
                        <button type="button" class="button upload-image-btn" data-target="existing-product-image-input-<?php echo $index; ?>-<?php echo $product_index; ?>">📁 Upload Image</button><br>
                        <label style="margin-top:10px; display:block;">Or enter image URL:</label>
                        <input type="text" name="sections[<?php echo $index; ?>][products][<?php echo $product_index; ?>][image]" value="<?php echo esc_attr($product['image'] ?? ''); ?>" class="regular-text image-url-input" id="existing-product-image-input-<?php echo $index; ?>-<?php echo $product_index; ?>" placeholder="https://example.com/product.jpg or attachment ID">
                        <div class="image-preview" style="<?php echo empty($product['image']) ? 'display:none;' : 'display:block;'; ?> margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;">
                            <?php if (!empty($product['image'])): ?>
                                <?php 
                                $product_image_url = $product['image'];
                                // If it's a numeric attachment ID, convert to URL
                                if (is_numeric($product_image_url)) {
                                    $attachment_url = wp_get_attachment_url(intval($product_image_url));
                                    if ($attachment_url) {
                                        $product_image_url = $attachment_url;
                                    }
                                }
                                ?>
                                <img src="<?php echo esc_url($product_image_url); ?>" style="max-width:200px; height:auto;" alt="Product image">
                            <?php endif; ?>
                        </div></p>
                        
                        <!-- Product Details -->
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div>
                                <p><label>Product Title:</label><br>
                                <input type="text" name="sections[<?php echo $index; ?>][products][<?php echo $product_index; ?>][title]" value="<?php echo esc_attr(stripslashes($product['title'] ?? '')); ?>" class="regular-text" placeholder="Product Name"></p>
                            </div>
                            <div>
                                <p><label>Badge (Optional):</label><br>
                                <input type="text" name="sections[<?php echo $index; ?>][products][<?php echo $product_index; ?>][badge]" value="<?php echo esc_attr(stripslashes($product['badge'] ?? '')); ?>" class="regular-text" placeholder="Sale, New, Featured"></p>
                            </div>
                        </div>
                        
                        <p><label>Product Description:</label><br>
                        <textarea name="sections[<?php echo $index; ?>][products][<?php echo $product_index; ?>][description]" rows="3" class="large-text" placeholder="Brief product description..."><?php echo esc_textarea(stripslashes($product['description'] ?? '')); ?></textarea></p>

                        <?php
                        $product_categories_raw = $product['wc_categories'] ?? [];
                        if (is_string($product_categories_raw)) {
                            $decoded_categories = json_decode($product_categories_raw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_categories)) {
                                $product_categories_raw = $decoded_categories;
                            } elseif ($product_categories_raw === '' || $product_categories_raw === null) {
                                $product_categories_raw = [];
                            } else {
                                $product_categories_raw = array($product_categories_raw);
                            }
                        } elseif (!is_array($product_categories_raw)) {
                            $product_categories_raw = [];
                        }
                        $product_categories_json = wp_json_encode($product_categories_raw);
                        if (empty($product_categories_json)) {
                            $product_categories_json = '[]';
                        }

                        $product_tags_raw = $product['wc_tags'] ?? [];
                        if (is_string($product_tags_raw)) {
                            $decoded_tags = json_decode($product_tags_raw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_tags)) {
                                $product_tags_raw = $decoded_tags;
                            } elseif ($product_tags_raw === '' || $product_tags_raw === null) {
                                $product_tags_raw = [];
                            } else {
                                $product_tags_raw = array($product_tags_raw);
                            }
                        } elseif (!is_array($product_tags_raw)) {
                            $product_tags_raw = [];
                        }
                        $product_tags_json = wp_json_encode($product_tags_raw);
                        if (empty($product_tags_json)) {
                            $product_tags_json = '[]';
                        }
                        ?>

                        <div class="wc-category-placeholder" data-section="<?php echo $index; ?>" data-product="<?php echo $product_index; ?>" data-selected-categories="<?php echo esc_attr($product_categories_json); ?>" style="background:#f0f8ff; padding:15px; margin:15px 0; border:1px solid #0969da; border-radius:8px; box-shadow: 0 2px 4px rgba(9,105,218,0.1);">
                            <p style="margin:0; color:#0969da; font-weight:500; font-size:13px; display:flex; align-items:center; gap:8px;"><span style="font-size:18px;">🛍️</span> Loading WooCommerce Categories...</p>
                        </div>

                        <div class="wc-tag-placeholder" data-section="<?php echo $index; ?>" data-product="<?php echo $product_index; ?>" data-selected-tags="<?php echo esc_attr($product_tags_json); ?>" style="background:#f0f8ff; padding:15px; margin:15px 0; border:1px solid #0969da; border-radius:8px; box-shadow: 0 2px 4px rgba(9,105,218,0.1);">
                            <p style="margin:0; color:#0969da; font-weight:500; font-size:13px; display:flex; align-items:center; gap:8px;"><span style="font-size:18px;">🏷️</span> Loading WooCommerce Tags...</p>
                        </div>
                        
                        <!-- Product Button -->
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div>
                                <p><label>Button Link:</label><br>
                                <input type="text" name="sections[<?php echo $index; ?>][products][<?php echo $product_index; ?>][link]" value="<?php echo esc_attr(stripslashes($product['link'] ?? '')); ?>" class="regular-text" placeholder="https://example.com/product"></p>
                            </div>
                            <div>
                                <p><label>Button Text:</label><br>
                                <input type="text" name="sections[<?php echo $index; ?>][products][<?php echo $product_index; ?>][button_text]" value="<?php echo esc_attr(stripslashes($product['button_text'] ?? 'View Product')); ?>" class="regular-text"></p>
                            </div>
                        </div>
                        
                        <!-- Product Options -->
                        <p><label><input type="checkbox" name="sections[<?php echo $index; ?>][products][<?php echo $product_index; ?>][featured]" value="1" <?php checked(!empty($product['featured'])); ?>> Featured Product</label></p>
                        
                        <?php if ($product_index > 0): ?>
                            <p><button type="button" class="button button-link-delete" onclick="this.parentElement.remove()">Remove Product</button></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button" onclick="cpbAddProduct(<?php echo $index; ?>)">+ Add Another Product</button></p>
            
        <?php elseif ($type === 'testimonials'): ?>
            <p><strong>Testimonials - Multiple Testimonials</strong></p>
            <p><label>Section Title:</label><br>
            <input type="text" name="sections[<?php echo $index; ?>][title]" value="<?php echo esc_attr(stripslashes($section['title'] ?? '')); ?>" class="regular-text"></p>
            <p><label>Section Description:</label><br>
            <textarea name="sections[<?php echo $index; ?>][content]" rows="3" class="large-text"><?php echo esc_textarea(stripslashes($section['content'] ?? '')); ?></textarea></p>
            <div id="testimonials-<?php echo $index; ?>">
                <?php 
                $testimonials = $section['testimonials'] ?? array();
                foreach ($testimonials as $testimonial_index => $testimonial): 
                ?>
                    <div class="testimonial-item" style="border-left:3px solid #f56e28; padding-left:10px; margin:10px 0; background:#fff; padding:15px; border-radius:4px;">
                        <p><strong>Testimonial <?php echo $testimonial_index + 1; ?></strong></p>
                        
                        <p><label>Testimonial Text:</label><br>
                        <textarea name="sections[<?php echo $index; ?>][testimonials][<?php echo $testimonial_index; ?>][content]" rows="4" class="large-text" placeholder="Enter the testimonial text..."><?php echo esc_textarea(stripslashes($testimonial['content'] ?? '')); ?></textarea></p>
                        
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div>
                                <p><label>Author Name:</label><br>
                                <input type="text" name="sections[<?php echo $index; ?>][testimonials][<?php echo $testimonial_index; ?>][author_name]" value="<?php echo esc_attr(stripslashes($testimonial['author_name'] ?? '')); ?>" class="regular-text" placeholder="John Doe"></p>
                                <p><label>Author Title:</label><br>
                                <input type="text" name="sections[<?php echo $index; ?>][testimonials][<?php echo $testimonial_index; ?>][author_title]" value="<?php echo esc_attr(stripslashes($testimonial['author_title'] ?? '')); ?>" class="regular-text" placeholder="CEO, Company Name"></p>
                            </div>
                            <div>
                                <p><label>Rating (1-5):</label><br>
                                <select name="sections[<?php echo $index; ?>][testimonials][<?php echo $testimonial_index; ?>][rating]" class="regular-text">
                                    <option value="" <?php selected($testimonial['rating'] ?? '', ''); ?>>No Rating</option>
                                    <option value="5" <?php selected($testimonial['rating'] ?? '5', '5'); ?>>5 Stars</option>
                                    <option value="4" <?php selected($testimonial['rating'] ?? '', '4'); ?>>4 Stars</option>
                                    <option value="3" <?php selected($testimonial['rating'] ?? '', '3'); ?>>3 Stars</option>
                                    <option value="2" <?php selected($testimonial['rating'] ?? '', '2'); ?>>2 Stars</option>
                                    <option value="1" <?php selected($testimonial['rating'] ?? '', '1'); ?>>1 Star</option>
                                </select></p>
                                <p><label>Company/Location:</label><br>
                                <input type="text" name="sections[<?php echo $index; ?>][testimonials][<?php echo $testimonial_index; ?>][company]" value="<?php echo esc_attr(stripslashes($testimonial['company'] ?? '')); ?>" class="regular-text" placeholder="Company Name or Location"></p>
                            </div>
                        </div>
                        
                        <p><label>Author Photo (Optional):</label><br>
                        <button type="button" class="button upload-image-btn" data-target="existing-testimonial-image-input-<?php echo $index; ?>-<?php echo $testimonial_index; ?>">📁 Upload Photo</button><br>
                        <label style="margin-top:10px; display:block;">Or enter image URL:</label>
                        <input type="text" name="sections[<?php echo $index; ?>][testimonials][<?php echo $testimonial_index; ?>][author_image]" value="<?php echo esc_attr($testimonial['author_image'] ?? ''); ?>" class="regular-text image-url-input" id="existing-testimonial-image-input-<?php echo $index; ?>-<?php echo $testimonial_index; ?>" placeholder="https://example.com/author.jpg or attachment ID">
                        <div class="image-preview" style="<?php echo empty($testimonial['author_image']) ? 'display:none;' : 'display:block;'; ?> margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;">
                            <?php if (!empty($testimonial['author_image'])): ?>
                                <?php 
                                $testimonial_image_url = $testimonial['author_image'];
                                if (is_numeric($testimonial_image_url)) {
                                    $attachment_url = wp_get_attachment_url(intval($testimonial_image_url));
                                    if ($attachment_url) {
                                        $testimonial_image_url = $attachment_url;
                                    }
                                }
                                ?>
                                <img src="<?php echo esc_url($testimonial_image_url); ?>" style="max-width:200px; height:auto;" alt="Author photo">
                            <?php endif; ?>
                        </div></p>
                        
                        <?php if ($testimonial_index > 0): ?>
                            <p><button type="button" class="button button-link-delete" onclick="this.parentElement.remove()">Remove Testimonial</button></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button" onclick="cpbAddTestimonial(<?php echo $index; ?>)">+ Add Another Testimonial</button></p>
            
        <?php elseif ($type === 'content'): ?>
            <p><strong>Content Block - Multiple Content Items</strong></p>
            <p><label>Section Title:</label><br>
            <input type="text" name="sections[<?php echo $index; ?>][title]" value="<?php echo esc_attr($section['title'] ?? ''); ?>" class="regular-text"></p>
            <div id="content-items-<?php echo $index; ?>">
                <?php 
                $content_items = $section['content_items'] ?? array();
                foreach ($content_items as $item_index => $item): 
                ?>
                    <div class="content-item" style="border-left:3px solid #826eb4; padding-left:10px; margin:10px 0; background:#fff; padding:15px; border-radius:4px;">
                        <p><strong>Content Item <?php echo $item_index + 1; ?></strong></p>
                        
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div>
                                <p><label>Item Title:</label><br>
                                <input type="text" name="sections[<?php echo $index; ?>][content_items][<?php echo $item_index; ?>][title]" value="<?php echo esc_attr(stripslashes($item['title'] ?? '')); ?>" class="regular-text" placeholder="Content Title"></p>
                            </div>
                            <div>
                                <p><label>Item Type:</label><br>
                                <select name="sections[<?php echo $index; ?>][content_items][<?php echo $item_index; ?>][type]" class="regular-text">
                                    <option value="text" <?php selected($item['type'] ?? 'text', 'text'); ?>>Text Content</option>
                                    <option value="feature" <?php selected($item['type'] ?? '', 'feature'); ?>>Feature Item</option>
                                    <option value="service" <?php selected($item['type'] ?? '', 'service'); ?>>Service Item</option>
                                    <option value="benefit" <?php selected($item['type'] ?? '', 'benefit'); ?>>Benefit Item</option>
                                    <option value="step" <?php selected($item['type'] ?? '', 'step'); ?>>Process Step</option>
                                </select></p>
                            </div>
                        </div>
                        
                        <p><label>Content Description:</label><br>
                        <textarea name="sections[<?php echo $index; ?>][content_items][<?php echo $item_index; ?>][content]" rows="4" class="large-text" placeholder="Enter the content description..."><?php echo esc_textarea(stripslashes($item['content'] ?? '')); ?></textarea></p>
                        
                        <p><label>Content Image (Optional):</label><br>
                        <button type="button" class="button upload-image-btn" data-target="existing-content-image-input-<?php echo $index; ?>-<?php echo $item_index; ?>">📁 Upload Image</button><br>
                        <label style="margin-top:10px; display:block;">Or enter image URL:</label>
                        <input type="text" name="sections[<?php echo $index; ?>][content_items][<?php echo $item_index; ?>][image]" value="<?php echo esc_attr($item['image'] ?? ''); ?>" class="regular-text image-url-input" id="existing-content-image-input-<?php echo $index; ?>-<?php echo $item_index; ?>" placeholder="https://example.com/image.jpg or attachment ID">
                        <div class="image-preview" style="<?php echo empty($item['image']) ? 'display:none;' : 'display:block;'; ?> margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;">
                            <?php if (!empty($item['image'])): ?>
                                <?php 
                                $item_image_url = $item['image'];
                                if (is_numeric($item_image_url)) {
                                    $attachment_url = wp_get_attachment_url(intval($item_image_url));
                                    if ($attachment_url) {
                                        $item_image_url = $attachment_url;
                                    }
                                }
                                ?>
                                <img src="<?php echo esc_url($item_image_url); ?>" style="max-width:200px; height:auto;" alt="Content image">
                            <?php endif; ?>
                        </div></p>
                        
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div>
                                <p><label>Link URL (Optional):</label><br>
                                <input type="text" name="sections[<?php echo $index; ?>][content_items][<?php echo $item_index; ?>][link]" value="<?php echo esc_attr(stripslashes($item['link'] ?? '')); ?>" class="regular-text" placeholder="https://example.com"></p>
                            </div>
                            <div>
                                <p><label>Link Text:</label><br>
                                <input type="text" name="sections[<?php echo $index; ?>][content_items][<?php echo $item_index; ?>][link_text]" value="<?php echo esc_attr(stripslashes($item['link_text'] ?? 'Learn More')); ?>" class="regular-text"></p>
                            </div>
                        </div>
                        
                        <?php if ($item_index > 0): ?>
                            <p><button type="button" class="button button-link-delete" onclick="this.parentElement.remove()">Remove Content Item</button></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button" onclick="cpbAddContentItem(<?php echo $index; ?>)">+ Add Another Content Item</button></p>
            
        <?php else: ?>
            <p><label>Title:</label><br>
            <input type="text" name="sections[<?php echo $index; ?>][title]" value="<?php echo esc_attr(stripslashes($section['title'] ?? '')); ?>" class="regular-text"></p>
            <p><label>Content:</label><br>
            <textarea name="sections[<?php echo $index; ?>][content]" rows="5" class="large-text"><?php echo esc_textarea(stripslashes($section['content'] ?? '')); ?></textarea></p>
            <p><label>Section Image:</label><br>
            <button type="button" class="button upload-image-btn" data-target="existing-section-image-input-<?php echo $index; ?>">📁 Upload Image</button><br>
            <label style="margin-top:10px; display:block;">Or enter image URL:</label>
            <input type="text" name="sections[<?php echo $index; ?>][image]" value="<?php echo esc_attr($section['image'] ?? ''); ?>" class="regular-text image-url-input" id="existing-section-image-input-<?php echo $index; ?>" placeholder="https://example.com/image.jpg or attachment ID">
            <div class="image-preview" style="<?php echo empty($section['image']) ? 'display:none;' : 'display:block;'; ?> margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;">
                <?php if (!empty($section['image'])): ?>
                    <?php 
                    $image_url = $section['image'];
                    // If it's a numeric attachment ID, convert to URL
                    if (is_numeric($image_url)) {
                        $attachment_url = wp_get_attachment_url(intval($image_url));
                        if ($attachment_url) {
                            $image_url = $attachment_url;
                        }
                    }
                    ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width:200px; height:auto;" alt="Section image">
                <?php endif; ?>
            </div></p>
            
            <?php if ($type === 'testimonials'): ?>
                <p><label>Author Name:</label><br>
                <input type="text" name="sections[<?php echo $index; ?>][author]" value="<?php echo esc_attr(stripslashes($section['author'] ?? '')); ?>" class="regular-text"></p>
                <p><label>Author Title:</label><br>
                <input type="text" name="sections[<?php echo $index; ?>][author_title]" value="<?php echo esc_attr(stripslashes($section['author_title'] ?? '')); ?>" class="regular-text"></p>
            <?php endif; ?>
            
            <!-- Section Button Options (Available for all non-product section types) -->
            <div style="border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px;">
                <h4>Section Button (Optional)</h4>
                <p><label>Button Text:</label><br>
                <input type="text" name="sections[<?php echo $index; ?>][button_text]" value="<?php echo esc_attr(stripslashes($section['button_text'] ?? '')); ?>" class="regular-text" placeholder="Learn More"></p>
                <p><label>Button Link:</label><br>
                <input type="text" name="sections[<?php echo $index; ?>][button_link]" value="<?php echo esc_attr(stripslashes($section['button_link'] ?? '')); ?>" class="regular-text" placeholder="https://example.com"></p>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div>
                        <p><label>Button Style:</label><br>
                        <select name="sections[<?php echo $index; ?>][button_style]" class="regular-text">
                            <option value="primary" <?php selected($section['button_style'] ?? 'primary', 'primary'); ?>>Primary (Blue)</option>
                            <option value="secondary" <?php selected($section['button_style'] ?? 'primary', 'secondary'); ?>>Secondary (Gray)</option>
                            <option value="success" <?php selected($section['button_style'] ?? 'primary', 'success'); ?>>Success (Green)</option>
                            <option value="warning" <?php selected($section['button_style'] ?? 'primary', 'warning'); ?>>Warning (Orange)</option>
                            <option value="danger" <?php selected($section['button_style'] ?? 'primary', 'danger'); ?>>Danger (Red)</option>
                        </select></p>
                    </div>
                    <div>
                        <p><label>Button Target:</label><br>
                        <select name="sections[<?php echo $index; ?>][button_target]" class="regular-text">
                            <option value="_self" <?php selected($section['button_target'] ?? '_self', '_self'); ?>>Same Window</option>
                            <option value="_blank" <?php selected($section['button_target'] ?? '_self', '_blank'); ?>>New Window</option>
                        </select></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <p><button type="button" class="button button-link-delete" onclick="this.parentElement.parentElement.remove()">Remove Section</button></p>
    </div>
    <?php
}