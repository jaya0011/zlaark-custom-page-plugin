<?php
/**
 * Secure image handler class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Secure image handler class for integration with Zlaark secure image plugin
 */
class Secure_Image_Handler {
    
    /**
     * Secure image plugin instance
     *
     * @var object|null
     */
    private $secure_image_plugin = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the handler
     *
     * @return void
     */
    private function init() {
        // Check if secure image plugin is available
        if ($this->is_secure_image_plugin_active()) {
            $this->secure_image_plugin = $this->get_secure_image_plugin_instance();
        }
    }
    
    /**
     * Check if secure image plugin is active
     *
     * @return bool
     */
    public function is_secure_image_plugin_active() {
        // Check if the secure image plugin class exists
        return class_exists('WooCommerce\\SecureImages\\Core\\Plugin');
    }
    
    /**
     * Get secure image plugin instance
     *
     * @return object|null
     */
    private function get_secure_image_plugin_instance() {
        if (class_exists('WooCommerce\\SecureImages\\Core\\Plugin')) {
            return \WooCommerce\SecureImages\Core\Plugin::get_instance();
        }
        return null;
    }
    
    /**
     * Process uploaded image for secure handling
     *
     * @param int $attachment_id Attachment ID
     * @param array $options Processing options
     * @return array Processing result with secure URLs and metadata
     */
    public function process_uploaded_image($attachment_id, $options = []) {
        if (!$attachment_id || !get_post($attachment_id)) {
            return [
                'success' => false,
                'error' => 'Invalid attachment ID'
            ];
        }
        
        $result = [
            'success' => true,
            'attachment_id' => $attachment_id,
            'secure_urls' => [],
            'fallback_urls' => [],
            'is_protected' => false
        ];
        
        // If secure image plugin is active, process the image
        if ($this->is_secure_image_plugin_active()) {
            try {
                // Mark image as protected
                update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
                update_post_meta($attachment_id, '_custom_page_builder_image', '1');
                
                // Generate secure URLs for different sizes
                $sizes = ['thumbnail', 'medium', 'large', 'full'];
                foreach ($sizes as $size) {
                    $secure_url = $this->get_secure_image_url($attachment_id, $size);
                    if ($secure_url) {
                        $result['secure_urls'][$size] = $secure_url;
                    }
                }
                
                $result['is_protected'] = true;
                
            } catch (Exception $e) {
                error_log('Custom Page Builder: Failed to process secure image: ' . $e->getMessage());
                $result['success'] = false;
                $result['error'] = 'Failed to process secure image: ' . $e->getMessage();
            }
        }
        
        // Always provide fallback URLs
        $result['fallback_urls'] = $this->get_fallback_image_urls($attachment_id);
        
        return $result;
    }
    
    /**
     * Get secure image URL
     *
     * @param int $attachment_id Attachment ID
     * @param string $size Image size
     * @param array $options URL options
     * @return string Secure URL or fallback URL
     */
    public function get_secure_image_url($attachment_id, $size = 'full', $options = []) {
        if (!$attachment_id || !get_post($attachment_id)) {
            return '';
        }
        
        // If secure image plugin is active and image is protected
        if ($this->is_secure_image_plugin_active() && $this->is_image_protected($attachment_id)) {
            try {
                // CRITICAL FIX: Use the main plugin's URL generation method
                if ($this->secure_image_plugin && method_exists($this->secure_image_plugin, 'generate_secure_image_url')) {
                    // Use the main plugin's secure URL generation
                    $reflection = new ReflectionClass($this->secure_image_plugin);
                    $method = $reflection->getMethod('generate_secure_image_url');
                    $method->setAccessible(true);
                    return $method->invoke($this->secure_image_plugin, $attachment_id, $size);
                }
                
                // Fallback: Generate our own secure URL
                $token = $this->generate_image_token($attachment_id, $size);
                if ($token) {
                    $plugin_url = defined('WC_SECURE_IMAGES_PLUGIN_URL') ? WC_SECURE_IMAGES_PLUGIN_URL : '';
                    if ($plugin_url) {
                        return $plugin_url . 'secure-image.php?' . http_build_query([
                            'token' => $token,
                            'size' => $size
                        ]);
                    }
                }
            } catch (Exception $e) {
                error_log('Custom Page Builder: Failed to generate secure URL: ' . $e->getMessage());
            }
        }
        
        // Fallback to standard WordPress image URL
        return $this->get_fallback_image_url($attachment_id, $size);
    }
    
    /**
     * Generate image token for secure access
     *
     * @param int $attachment_id Attachment ID
     * @param string $size Image size
     * @return string|false Token or false on failure
     */
    private function generate_image_token($attachment_id, $size) {
        try {
            // Create token data
            $token_data = [
                'attachment_id' => $attachment_id,
                'size' => $size,
                'expires' => time() + 3600, // 1 hour expiration
                'source' => 'custom_page_builder'
            ];
            
            // Encode token
            return base64_encode(wp_json_encode($token_data));
            
        } catch (Exception $e) {
            error_log('Custom Page Builder: Failed to generate token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if image is protected
     *
     * @param int $attachment_id Attachment ID
     * @return bool
     */
    public function is_image_protected($attachment_id) {
        return get_post_meta($attachment_id, '_wc_secure_images_protected', true) === '1';
    }
    
    /**
     * Get fallback image URL (standard WordPress)
     *
     * @param int $attachment_id Attachment ID
     * @param string $size Image size
     * @return string
     */
    private function get_fallback_image_url($attachment_id, $size = 'full') {
        $image_data = wp_get_attachment_image_src($attachment_id, $size);
        return $image_data ? $image_data[0] : '';
    }
    
    /**
     * Get fallback image URLs for all sizes
     *
     * @param int $attachment_id Attachment ID
     * @return array
     */
    private function get_fallback_image_urls($attachment_id) {
        $urls = [];
        $sizes = ['thumbnail', 'medium', 'large', 'full'];
        
        foreach ($sizes as $size) {
            $urls[$size] = $this->get_fallback_image_url($attachment_id, $size);
        }
        
        return $urls;
    }
    
    /**
     * Handle image in section configuration
     *
     * @param array $section_config Section configuration array (passed by reference)
     * @return void
     */
    public function handle_image_in_section(&$section_config) {
        if (!is_array($section_config)) {
            return;
        }
        
        // Process images in section configuration
        $this->process_images_in_config($section_config);
    }
    
    /**
     * Recursively process images in configuration array
     *
     * @param array $config Configuration array (passed by reference)
     * @return void
     */
    private function process_images_in_config(&$config) {
        foreach ($config as $key => &$value) {
            if (is_array($value)) {
                // Recursively process nested arrays
                $this->process_images_in_config($value);
            } elseif ($this->is_image_field($key) && is_numeric($value)) {
                // This is an image field with attachment ID
                $attachment_id = intval($value);
                if ($attachment_id > 0) {
                    // Process the image and add secure URL data
                    $image_data = $this->process_uploaded_image($attachment_id);
                    
                    // Add image data to config
                    $image_key = $key . '_data';
                    $config[$image_key] = $image_data;
                }
            }
        }
    }
    
    /**
     * Check if a field key represents an image field
     *
     * @param string $key Field key
     * @return bool
     */
    private function is_image_field($key) {
        $image_field_patterns = [
            'image',
            'background_image',
            'hero_image',
            'product_image',
            'category_image',
            'testimonial_image',
            'author_image',
            'banner_image',
            'thumbnail'
        ];
        
        $key_lower = strtolower($key);
        
        foreach ($image_field_patterns as $pattern) {
            if (strpos($key_lower, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get image size variants for an attachment
     *
     * @param int $attachment_id Attachment ID
     * @return array Array of size variants with URLs
     */
    public function get_image_size_variants($attachment_id) {
        $variants = [];
        $sizes = ['thumbnail', 'medium', 'large', 'full'];
        
        foreach ($sizes as $size) {
            $secure_url = $this->get_secure_image_url($attachment_id, $size);
            $fallback_url = $this->get_fallback_image_url($attachment_id, $size);
            
            $variants[$size] = [
                'secure_url' => $secure_url,
                'fallback_url' => $fallback_url,
                'url' => $secure_url ?: $fallback_url
            ];
        }
        
        return $variants;
    }
    
    /**
     * Get warning message when secure image plugin is inactive
     *
     * @return string
     */
    public function get_inactive_plugin_warning() {
        return __('Secure Image plugin is not active. Images will be served using standard WordPress URLs without protection.', 'custom-page-builder');
    }
    
    /**
     * Process section images for API response
     *
     * @param array $section_config Section configuration
     * @return array Processed section configuration with secure image URLs
     */
    public function process_section_images($section_config) {
        if (!is_array($section_config)) {
            return $section_config;
        }
        
        $processed_config = $section_config;
        $this->add_secure_urls_to_config($processed_config);
        
        return $processed_config;
    }
    
    /**
     * Add secure URLs to configuration array
     *
     * @param array $config Configuration array (passed by reference)
     * @return void
     */
    private function add_secure_urls_to_config(&$config) {
        foreach ($config as $key => &$value) {
            if (is_array($value)) {
                // Recursively process nested arrays
                $this->add_secure_urls_to_config($value);
            } elseif ($this->is_image_field($key)) {
                $attachment_id = 0;
                
                // Handle both attachment ID and URL formats
                if (is_numeric($value)) {
                    $attachment_id = intval($value);
                } elseif (is_string($value) && !empty($value)) {
                    // Try to extract attachment ID from URL
                    $attachment_id = attachment_url_to_postid($value);
                }
                
                if ($attachment_id > 0 && get_post($attachment_id)) {
                    // Mark as protected if not already
                    update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
                    
                    // Add secure URLs for different sizes
                    $image_urls = $this->get_image_size_variants($attachment_id);
                    $config[$key . '_urls'] = $image_urls;
                    
                    // CRITICAL FIX: Replace the original value with secure URL
                    $secure_url = $image_urls['full']['url'] ?? '';
                    if ($secure_url) {
                        $value = $secure_url;
                    }
                    
                    // IMPORTANT: Also add a direct URL field for easy frontend access
                    $config[$key . '_url'] = $secure_url;
                    
                    // Add image metadata for frontend
                    $config[$key . '_data'] = [
                        'id' => $attachment_id,
                        'url' => $secure_url,
                        'secure_url' => $image_urls['full']['secure_url'] ?? '',
                        'fallback_url' => $image_urls['full']['fallback_url'] ?? '',
                        'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                        'title' => get_the_title($attachment_id),
                        'sizes' => $image_urls
                    ];
                }
            }
        }
    }
    
    /**
     * Display admin notice when secure image plugin is inactive (disabled)
     *
     * @return void
     */
    public function display_inactive_plugin_notice() {
        // Notice disabled - no admin alerts
        return;
    }
}