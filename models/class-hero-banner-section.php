<?php
/**
 * Hero Banner Section class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Models;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hero Banner section type implementation
 */
class HeroBannerSection extends Section {
    
    /**
     * Get section type identifier
     *
     * @return string
     */
    public function get_type(): string {
        return 'hero_banner';
    }
    
    /**
     * Get configuration schema for hero banner section
     *
     * @return array
     */
    public function get_config_schema(): array {
        return [
            'title' => [
                'type' => 'string',
                'required' => false,
                'default' => 'Welcome to Our Website'
            ],
            'subtitle' => [
                'type' => 'string',
                'required' => false,
                'default' => 'Discover amazing products and services'
            ],
            'background_image' => [
                'type' => 'string',
                'required' => false,
                'default' => ''
            ],
            'background_video' => [
                'type' => 'string',
                'required' => false,
                'default' => ''
            ],
            'background_type' => [
                'type' => 'string',
                'required' => false,
                'default' => 'image',
                'options' => ['image', 'video', 'color']
            ],
            'background_color' => [
                'type' => 'string',
                'required' => false,
                'default' => '#007cba'
            ],
            'overlay_enabled' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ],
            'overlay_color' => [
                'type' => 'string',
                'required' => false,
                'default' => '#000000'
            ],
            'overlay_opacity' => [
                'type' => 'integer',
                'required' => false,
                'default' => 50,
                'min' => 0,
                'max' => 100
            ],
            'text_alignment' => [
                'type' => 'string',
                'required' => false,
                'default' => 'center',
                'options' => ['left', 'center', 'right']
            ],
            'vertical_alignment' => [
                'type' => 'string',
                'required' => false,
                'default' => 'center',
                'options' => ['top', 'center', 'bottom']
            ],
            'title_color' => [
                'type' => 'string',
                'required' => false,
                'default' => '#ffffff'
            ],
            'subtitle_color' => [
                'type' => 'string',
                'required' => false,
                'default' => '#ffffff'
            ],
            'title_size' => [
                'type' => 'string',
                'required' => false,
                'default' => 'large',
                'options' => ['small', 'medium', 'large', 'extra-large']
            ],
            'subtitle_size' => [
                'type' => 'string',
                'required' => false,
                'default' => 'medium',
                'options' => ['small', 'medium', 'large']
            ],
            'cta_buttons' => [
                'type' => 'array',
                'required' => false,
                'default' => [],
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'text' => [
                            'type' => 'string',
                            'required' => true
                        ],
                        'link' => [
                            'type' => 'string',
                            'required' => true
                        ],
                        'style' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => 'primary',
                            'options' => ['primary', 'secondary', 'outline']
                        ],
                        'target' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => '_self',
                            'options' => ['_self', '_blank']
                        ],
                        'color' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => '#007cba'
                        ],
                        'text_color' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => '#ffffff'
                        ]
                    ]
                ]
            ],
            'height' => [
                'type' => 'string',
                'required' => false,
                'default' => 'medium',
                'options' => ['small', 'medium', 'large', 'full-screen', 'custom']
            ],
            'custom_height' => [
                'type' => 'integer',
                'required' => false,
                'default' => 500,
                'min' => 200,
                'max' => 1200
            ],
            'parallax_enabled' => [
                'type' => 'boolean',
                'required' => false,
                'default' => false
            ],
            'animation_enabled' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ],
            'animation_type' => [
                'type' => 'string',
                'required' => false,
                'default' => 'fade-in',
                'options' => ['fade-in', 'slide-up', 'slide-down', 'zoom-in']
            ],
            'visible' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ],
            'wc_categories' => [
                'type' => 'array',
                'required' => false,
                'default' => [],
                'description' => 'WooCommerce product categories'
            ]
        ];
    }
    
    /**
     * Validate hero banner section configuration
     *
     * @param array $config Configuration data
     * @return bool
     */
    public function validate_config(array $config): bool {
        // First run parent validation
        if (!parent::validate_config($config)) {
            return false;
        }
        
        // Validate background type
        if (isset($config['background_type']) && !in_array($config['background_type'], ['image', 'video', 'color'])) {
            return false;
        }
        
        // Validate text alignment
        if (isset($config['text_alignment']) && !in_array($config['text_alignment'], ['left', 'center', 'right'])) {
            return false;
        }
        
        // Validate vertical alignment
        if (isset($config['vertical_alignment']) && !in_array($config['vertical_alignment'], ['top', 'center', 'bottom'])) {
            return false;
        }
        
        // Validate title size
        if (isset($config['title_size']) && !in_array($config['title_size'], ['small', 'medium', 'large', 'extra-large'])) {
            return false;
        }
        
        // Validate subtitle size
        if (isset($config['subtitle_size']) && !in_array($config['subtitle_size'], ['small', 'medium', 'large'])) {
            return false;
        }
        
        // Validate height option
        if (isset($config['height']) && !in_array($config['height'], ['small', 'medium', 'large', 'full-screen', 'custom'])) {
            return false;
        }
        
        // Validate custom height range
        if (isset($config['custom_height'])) {
            $height = (int) $config['custom_height'];
            if ($height < 200 || $height > 1200) {
                return false;
            }
        }
        
        // Validate overlay opacity range
        if (isset($config['overlay_opacity'])) {
            $opacity = (int) $config['overlay_opacity'];
            if ($opacity < 0 || $opacity > 100) {
                return false;
            }
        }
        
        // Validate animation type
        if (isset($config['animation_type']) && !in_array($config['animation_type'], ['fade-in', 'slide-up', 'slide-down', 'zoom-in'])) {
            return false;
        }
        
        // Validate CTA buttons
        if (isset($config['cta_buttons']) && is_array($config['cta_buttons'])) {
            foreach ($config['cta_buttons'] as $button) {
                if (!$this->validate_cta_button($button)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Validate individual CTA button data
     *
     * @param array $button Button data
     * @return bool
     */
    private function validate_cta_button(array $button): bool {
        // Required fields
        if (empty($button['text']) || empty($button['link'])) {
            return false;
        }
        
        // Validate button style
        if (isset($button['style']) && !in_array($button['style'], ['primary', 'secondary', 'outline'])) {
            return false;
        }
        
        // Validate target
        if (isset($button['target']) && !in_array($button['target'], ['_self', '_blank'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Render admin template for hero banner configuration
     *
     * @return string
     */
    public function render_admin_template(): string {
        ob_start();
        
        // Set section variable for template
        $section = $this;
        
        // Include the template file
        $template_path = plugin_dir_path(__FILE__) . '../templates/admin/section-templates/hero-banner.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error">Hero Banner template not found.</div>';
        }
        
        // Add JavaScript and CSS
        $this->render_admin_assets();
        
        return ob_get_clean();
    }
    
    /**
     * Render admin assets (JavaScript and CSS)
     *
     * @return void
     */
    private function render_admin_assets(): void {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Background type toggle
            $('#hero-background-type').on('change', function() {
                var type = $(this).val();
                $('.background-option').hide();
                $('.background-' + type).show();
            }).trigger('change');
            
            // Height type toggle
            $('#hero-height').on('change', function() {
                var height = $(this).val();
                if (height === 'custom') {
                    $('.custom-height-option').show();
                } else {
                    $('.custom-height-option').hide();
                }
            }).trigger('change');
            
            // Animation toggle
            $('#hero-animation-enabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.animation-options').show();
                } else {
                    $('.animation-options').hide();
                }
            }).trigger('change');
            
            // Overlay toggle
            $('#hero-overlay-enabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.overlay-options').show();
                } else {
                    $('.overlay-options').hide();
                }
            }).trigger('change');
            
            // Add CTA button functionality
            $('#add-cta-button').on('click', function() {
                var index = $('#cta-buttons-list .cta-button-item').length;
                var template = `
                    <div class="cta-button-item" data-index="${index}">
                        <div class="button-header">
                            <span class="button-number"><?php _e('Button', 'custom-page-builder'); ?> ${index + 1}</span>
                            <button type="button" class="remove-cta-button"><?php _e('Remove', 'custom-page-builder'); ?></button>
                        </div>
                        
                        <div class="config-row">
                            <div class="config-group">
                                <label><?php _e('Button Text', 'custom-page-builder'); ?></label>
                                <input type="text" name="config[cta_buttons][${index}][text]" 
                                       placeholder="<?php _e('Get Started', 'custom-page-builder'); ?>" />
                            </div>
                            
                            <div class="config-group">
                                <label><?php _e('Button Link', 'custom-page-builder'); ?></label>
                                <input type="url" name="config[cta_buttons][${index}][link]" 
                                       placeholder="<?php _e('https://example.com', 'custom-page-builder'); ?>" />
                            </div>
                        </div>
                        
                        <div class="config-row">
                            <div class="config-group">
                                <label><?php _e('Button Style', 'custom-page-builder'); ?></label>
                                <select name="config[cta_buttons][${index}][style]">
                                    <option value="primary" selected><?php _e('Primary', 'custom-page-builder'); ?></option>
                                    <option value="secondary"><?php _e('Secondary', 'custom-page-builder'); ?></option>
                                    <option value="outline"><?php _e('Outline', 'custom-page-builder'); ?></option>
                                </select>
                            </div>
                            
                            <div class="config-group">
                                <label><?php _e('Open In', 'custom-page-builder'); ?></label>
                                <select name="config[cta_buttons][${index}][target]">
                                    <option value="_self" selected><?php _e('Same Window', 'custom-page-builder'); ?></option>
                                    <option value="_blank"><?php _e('New Window', 'custom-page-builder'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="config-row">
                            <div class="config-group">
                                <label><?php _e('Button Color', 'custom-page-builder'); ?></label>
                                <input type="color" name="config[cta_buttons][${index}][color]" value="#007cba" />
                            </div>
                            
                            <div class="config-group">
                                <label><?php _e('Text Color', 'custom-page-builder'); ?></label>
                                <input type="color" name="config[cta_buttons][${index}][text_color]" value="#ffffff" />
                            </div>
                        </div>
                    </div>
                `;
                
                $('#cta-buttons-list').append(template);
                updateButtonNumbers();
            });
            
            // Remove CTA button functionality
            $(document).on('click', '.remove-cta-button', function() {
                $(this).closest('.cta-button-item').remove();
                updateButtonNumbers();
                reindexButtons();
            });
            
            // Update button numbers
            function updateButtonNumbers() {
                $('#cta-buttons-list .cta-button-item').each(function(index) {
                    $(this).find('.button-number').text('<?php _e('Button', 'custom-page-builder'); ?> ' + (index + 1));
                });
            }
            
            // Reindex button form fields
            function reindexButtons() {
                $('#cta-buttons-list .cta-button-item').each(function(index) {
                    $(this).attr('data-index', index);
                    $(this).find('input, select').each(function() {
                        var name = $(this).attr('name');
                        if (name) {
                            name = name.replace(/\[\d+\]/, '[' + index + ']');
                            $(this).attr('name', name);
                        }
                    });
                });
            }
            
            // Image upload and edit functionality
            $(document).on('click', '#upload-hero-background, #edit-hero-background', function() {
                var button = $(this);
                var container = button.closest('.image-upload-field');
                var field = $('#hero-background-image');
                
                var mediaUploader = wp.media({
                    title: '<?php _e('Select Background Image', 'custom-page-builder'); ?>',
                    button: {
                        text: '<?php _e('Use this image', 'custom-page-builder'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    field.val(attachment.url);
                    
                    // Update or create preview container
                    var previewContainer = container.find('.image-preview-container');
                    if (previewContainer.length === 0) {
                        container.find('.image-button-group').replaceWith(`
                            <div class="image-preview-container">
                                <div class="image-preview">
                                    <img src="${attachment.url}" alt="<?php _e('Background Image Preview', 'custom-page-builder'); ?>" />
                                </div>
                                <div class="image-button-group">
                                    <button type="button" id="edit-hero-background" class="button edit-image-btn" data-target="hero-background-image"><?php _e('✏️ Edit Image', 'custom-page-builder'); ?></button>
                                    <button type="button" id="remove-hero-background" class="button remove-image-btn" data-target="hero-background-image"><?php _e('🗑️ Delete Image', 'custom-page-builder'); ?></button>
                                </div>
                            </div>
                        `);
                    } else {
                        previewContainer.find('img').attr('src', attachment.url);
                    }
                });
                
                mediaUploader.open();
            });
            
            // Remove image functionality
            $(document).on('click', '#remove-hero-background', function() {
                var button = $(this);
                var container = button.closest('.image-upload-field');
                var field = $('#hero-background-image');
                var previewContainer = container.find('.image-preview-container');
                
                field.val('');
                previewContainer.replaceWith(`
                    <div class="image-button-group">
                        <button type="button" id="upload-hero-background" class="button upload-image-btn" data-target="hero-background-image"><?php _e('📷 Upload Background Image', 'custom-page-builder'); ?></button>
                    </div>
                `);
            });
        });
        </script>
        
        <style>
        .hero-banner-section-config .config-group {
            margin-bottom: 15px;
        }
        
        .hero-banner-section-config .config-row {
            display: flex;
            gap: 15px;
        }
        
        .hero-banner-section-config .config-row .config-group {
            flex: 1;
        }
        
        .hero-banner-section-config label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .hero-banner-section-config input[type="text"],
        .hero-banner-section-config input[type="url"],
        .hero-banner-section-config input[type="color"],
        .hero-banner-section-config input[type="number"],
        .hero-banner-section-config select,
        .hero-banner-section-config textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .background-option {
            display: none;
        }
        
        .custom-height-option,
        .animation-options,
        .overlay-options {
            display: none;
        }
        
        .cta-button-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .button-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .button-number {
            font-weight: bold;
            font-size: 16px;
        }
        
        .remove-cta-button {
            background: #dc3232;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .image-upload-field {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .image-preview img {
            max-width: 100px;
            max-height: 60px;
            border-radius: 4px;
        }
        
        .upload-image-btn,
        .remove-image-btn {
            padding: 5px 10px;
            border: 1px solid #ddd;
            background: #f7f7f7;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .remove-image-btn {
            background: #dc3232;
            color: white;
            border-color: #dc3232;
        }
        
        .config-section {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            background: #fafafa;
        }
        
        .config-section h4 {
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        </style>
        <?php
    }
    
    /**
     * Convert to API response format
     *
     * @return array
     */
    public function to_api_response(): array {
        $response = parent::to_api_response();
        
        // Process CTA buttons for API response
        $cta_buttons = [];
        foreach ($this->config['cta_buttons'] ?? [] as $button) {
            $processed_button = [
                'text' => sanitize_text_field($button['text']),
                'link' => esc_url($button['link']),
                'style' => sanitize_text_field($button['style'] ?? 'primary'),
                'target' => sanitize_text_field($button['target'] ?? '_self'),
                'color' => sanitize_hex_color($button['color'] ?? '#007cba'),
                'text_color' => sanitize_hex_color($button['text_color'] ?? '#ffffff')
            ];
            
            $cta_buttons[] = $processed_button;
        }
        
        $response['config']['cta_buttons'] = $cta_buttons;
        
        // Handle secure image URLs
        if (!empty($this->config['background_image'])) {
            $image_data = $this->process_image_field($this->config['background_image']);
            $response['config']['background_image'] = $image_data['url'];
            $response['config']['background_image_data'] = $image_data;
        }
        
        if (!empty($this->config['background_video'])) {
            $response['config']['background_video'] = esc_url($this->config['background_video']);
        }

        // WooCommerce categories for hero banner targeting
        $wc_categories = $this->config['wc_categories'] ?? [];
        if (is_string($wc_categories)) {
            $wc_categories = json_decode($wc_categories, true) ?: [];
        }

        $response['config']['wc_categories'] = $wc_categories;

        $wc_category_details = [];
        if (!empty($wc_categories)) {
            if (in_array('all', $wc_categories, true)) {
                $wc_category_details = \Custom_Page_Builder\Category_Manager::get_woocommerce_categories();
            } else {
                $wc_category_details = \Custom_Page_Builder\Category_Manager::get_woocommerce_categories_by_ids($wc_categories);
            }
        }

        $response['config']['wc_category_details'] = $wc_category_details;
        
        return $response;
    }
    
    /**
     * Get default configuration
     *
     * @return array
     */
    public function get_default_config(): array {
        return [
            'title' => 'Welcome to Our Website',
            'subtitle' => 'Discover amazing products and services',
            'background_image' => '',
            'background_video' => '',
            'background_type' => 'image',
            'background_color' => '#007cba',
            'overlay_enabled' => true,
            'overlay_color' => '#000000',
            'overlay_opacity' => 50,
            'text_alignment' => 'center',
            'vertical_alignment' => 'center',
            'title_color' => '#ffffff',
            'subtitle_color' => '#ffffff',
            'title_size' => 'large',
            'subtitle_size' => 'medium',
            'cta_buttons' => [
                [
                    'text' => 'Get Started',
                    'link' => '#',
                    'style' => 'primary',
                    'target' => '_self',
                    'color' => '#007cba',
                    'text_color' => '#ffffff'
                ]
            ],
            'height' => 'medium',
            'custom_height' => 500,
            'parallax_enabled' => false,
            'animation_enabled' => true,
            'animation_type' => 'fade-in',
            'visible' => true
        ];
    }
}