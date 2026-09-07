<?php
/**
 * Mock secure image handler for testing
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Tests\Mocks;

/**
 * Mock secure image handler class
 */
class MockSecureImageHandler {
    
    /**
     * Mock plugin active status
     *
     * @var bool
     */
    private $is_plugin_active = true;
    
    /**
     * Set plugin active status
     *
     * @param bool $active
     */
    public function set_plugin_active(bool $active) {
        $this->is_plugin_active = $active;
    }
    
    /**
     * Check if secure image plugin is active
     *
     * @return bool
     */
    public function is_secure_image_plugin_active() {
        return $this->is_plugin_active;
    }
    
    /**
     * Process uploaded image for secure handling
     *
     * @param int $attachment_id Attachment ID
     * @param array $options Processing options
     * @return array Processing result
     */
    public function process_uploaded_image($attachment_id, $options = []) {
        if (!$attachment_id) {
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
            'is_protected' => $this->is_plugin_active
        ];
        
        $sizes = ['thumbnail', 'medium', 'large', 'full'];
        
        if ($this->is_plugin_active) {
            foreach ($sizes as $size) {
                $result['secure_urls'][$size] = "https://example.com/secure-img/{$attachment_id}/{$size}";
            }
        }
        
        foreach ($sizes as $size) {
            $result['fallback_urls'][$size] = "https://example.com/wp-content/uploads/test-image-{$size}.jpg";
        }
        
        return $result;
    }
    
    /**
     * Get secure image URL
     *
     * @param int $attachment_id Attachment ID
     * @param string $size Image size
     * @param array $options URL options
     * @return string
     */
    public function get_secure_image_url($attachment_id, $size = 'full', $options = []) {
        if ($this->is_plugin_active) {
            return "https://example.com/secure-img/{$attachment_id}/{$size}";
        }
        
        return "https://example.com/wp-content/uploads/test-image-{$size}.jpg";
    }
    
    /**
     * Handle image in section configuration
     *
     * @param array $section_config Section configuration array
     */
    public function handle_image_in_section(&$section_config) {
        // Mock implementation - just add _processed flag
        if (is_array($section_config)) {
            $section_config['_images_processed'] = true;
        }
    }
}