<?php
/**
 * Testimonials Section class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Models;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Testimonials section type implementation
 */
class TestimonialsSection extends Section {
    
    /**
     * Get section type identifier
     *
     * @return string
     */
    public function get_type(): string {
        return 'testimonials';
    }
    
    /**
     * Get configuration schema for testimonials section
     *
     * @return array
     */
    public function get_config_schema(): array {
        return [
            'title' => [
                'type' => 'string',
                'required' => false,
                'default' => 'What Our Customers Say'
            ],
            'subtitle' => [
                'type' => 'string',
                'required' => false,
                'default' => ''
            ],
            'layout' => [
                'type' => 'string',
                'required' => false,
                'default' => 'grid',
                'options' => ['grid', 'slider']
            ],
            'columns' => [
                'type' => 'integer',
                'required' => false,
                'default' => 3,
                'min' => 1,
                'max' => 4
            ],
            'testimonials' => [
                'type' => 'array',
                'required' => true,
                'default' => [],
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'rating' => [
                            'type' => 'integer',
                            'required' => true,
                            'min' => 1,
                            'max' => 5
                        ],
                        'text' => [
                            'type' => 'string',
                            'required' => true
                        ],
                        'author_name' => [
                            'type' => 'string',
                            'required' => true
                        ],
                        'author_title' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => ''
                        ],
                        'author_image' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => ''
                        ],
                        'company' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => ''
                        ]
                    ]
                ]
            ],
            'show_author_images' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ],
            'show_ratings' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ],
            'background_color' => [
                'type' => 'string',
                'required' => false,
                'default' => '#ffffff'
            ],
            'text_color' => [
                'type' => 'string',
                'required' => false,
                'default' => '#333333'
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
     * Validate testimonials section configuration
     *
     * @param array $config Configuration data
     * @return bool
     */
    public function validate_config(array $config): bool {
        // First run parent validation
        if (!parent::validate_config($config)) {
            return false;
        }
        
        // Validate testimonials array
        if (!isset($config['testimonials']) || !is_array($config['testimonials'])) {
            return false;
        }
        
        // Validate each testimonial
        foreach ($config['testimonials'] as $testimonial) {
            if (!$this->validate_testimonial($testimonial)) {
                return false;
            }
        }
        
        // Validate layout option
        if (isset($config['layout']) && !in_array($config['layout'], ['grid', 'slider'])) {
            return false;
        }
        
        // Validate columns range
        if (isset($config['columns'])) {
            $columns = (int) $config['columns'];
            if ($columns < 1 || $columns > 4) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate individual testimonial data
     *
     * @param array $testimonial Testimonial data
     * @return bool
     */
    private function validate_testimonial(array $testimonial): bool {
        // Required fields
        if (empty($testimonial['text']) || empty($testimonial['author_name'])) {
            return false;
        }
        
        // Rating validation
        if (!isset($testimonial['rating'])) {
            return false;
        }
        
        $rating = (int) $testimonial['rating'];
        if ($rating < 1 || $rating > 5) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Render admin template for testimonials configuration
     *
     * @return string
     */
    public function render_admin_template(): string {
        ob_start();
        
        // Set section variable for template
        $section = $this;
        
        // Include the template file
        $template_path = plugin_dir_path(__FILE__) . '../templates/admin/section-templates/testimonials.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error">Testimonials template not found.</div>';
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
            // Add testimonial functionality
            $('#add-testimonial').on('click', function() {
                var index = $('#testimonials-list .testimonial-item').length;
                var template = `
                    <div class="testimonial-item" data-index="${index}">
                        <div class="testimonial-header">
                            <span class="testimonial-number">${index + 1}</span>
                            <button type="button" class="remove-testimonial"><?php _e('Remove', 'custom-page-builder'); ?></button>
                        </div>
                        
                        <div class="config-row">
                            <div class="config-group">
                                <label><?php _e('Rating (1-5 stars)', 'custom-page-builder'); ?></label>
                                <select name="config[testimonials][${index}][rating]">
                                    <option value="5" selected>5 <?php _e('Stars', 'custom-page-builder'); ?></option>
                                    <option value="4">4 <?php _e('Stars', 'custom-page-builder'); ?></option>
                                    <option value="3">3 <?php _e('Stars', 'custom-page-builder'); ?></option>
                                    <option value="2">2 <?php _e('Stars', 'custom-page-builder'); ?></option>
                                    <option value="1">1 <?php _e('Star', 'custom-page-builder'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="config-group">
                            <label><?php _e('Testimonial Text', 'custom-page-builder'); ?></label>
                            <textarea name="config[testimonials][${index}][text]" rows="4" 
                                      class="cpb-rich-textarea"
                                      placeholder="<?php _e('Enter testimonial text...', 'custom-page-builder'); ?>"></textarea>
                        </div>
                        
                        <div class="config-row">
                            <div class="config-group">
                                <label><?php _e('Author Name', 'custom-page-builder'); ?></label>
                                <input type="text" name="config[testimonials][${index}][author_name]" 
                                       placeholder="<?php _e('John Doe', 'custom-page-builder'); ?>" />
                            </div>
                            
                            <div class="config-group">
                                <label><?php _e('Author Title', 'custom-page-builder'); ?></label>
                                <input type="text" name="config[testimonials][${index}][author_title]" 
                                       placeholder="<?php _e('CEO, Company Name', 'custom-page-builder'); ?>" />
                            </div>
                        </div>
                        
                        <div class="config-row">
                            <div class="config-group">
                                <label><?php _e('Company', 'custom-page-builder'); ?></label>
                                <input type="text" name="config[testimonials][${index}][company]" 
                                       placeholder="<?php _e('Company Name', 'custom-page-builder'); ?>" />
                            </div>
                            
                            <div class="config-group">
                                <label><?php _e('Author Image', 'custom-page-builder'); ?></label>
                                <div class="image-upload-field">
                                    <input type="hidden" name="config[testimonials][${index}][author_image]" 
                                           class="image-url-input" />
                                    <div class="image-button-group">
                                        <button type="button" class="upload-image-btn button"><?php _e('📷 Upload Image', 'custom-page-builder'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#testimonials-list').append(template);
                updateTestimonialNumbers();
            });
            
            // Remove testimonial functionality
            $(document).on('click', '.remove-testimonial', function() {
                $(this).closest('.testimonial-item').remove();
                updateTestimonialNumbers();
                reindexTestimonials();
            });
            
            // Update testimonial numbers
            function updateTestimonialNumbers() {
                $('#testimonials-list .testimonial-item').each(function(index) {
                    $(this).find('.testimonial-number').text(index + 1);
                });
            }
            
            // Reindex testimonial form fields
            function reindexTestimonials() {
                $('#testimonials-list .testimonial-item').each(function(index) {
                    $(this).attr('data-index', index);
                    $(this).find('input, select, textarea').each(function() {
                        var name = $(this).attr('name');
                        if (name) {
                            name = name.replace(/\[\d+\]/, '[' + index + ']');
                            $(this).attr('name', name);
                        }
                    });
                });
            }
            
            // Image upload functionality
            $(document).on('click', '.upload-image-btn, .edit-image-btn', function() {
                var button = $(this);
                var container = button.closest('.image-upload-field');
                var field = container.find('.image-url-input');
                
                var mediaUploader = wp.media({
                    title: '<?php _e('Select Author Image', 'custom-page-builder'); ?>',
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
                                    <img src="${attachment.url}" alt="<?php _e('Author Image Preview', 'custom-page-builder'); ?>" />
                                </div>
                                <div class="image-button-group">
                                    <button type="button" class="edit-image-btn button"><?php _e('✏️ Edit Image', 'custom-page-builder'); ?></button>
                                    <button type="button" class="remove-image-btn button"><?php _e('🗑️ Delete Image', 'custom-page-builder'); ?></button>
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
            $(document).on('click', '.remove-image-btn', function() {
                var button = $(this);
                var container = button.closest('.image-upload-field');
                var field = container.find('.image-url-input');
                var previewContainer = container.find('.image-preview-container');
                
                field.val('');
                previewContainer.replaceWith(`
                    <div class="image-button-group">
                        <button type="button" class="upload-image-btn button"><?php _e('📷 Upload Image', 'custom-page-builder'); ?></button>
                    </div>
                `);
            });
        });
        </script>
        
        <style>
        .testimonials-section-config .config-group {
            margin-bottom: 15px;
        }
        
        .testimonials-section-config .config-row {
            display: flex;
            gap: 15px;
        }
        
        .testimonials-section-config .config-row .config-group {
            flex: 1;
        }
        
        .testimonials-section-config label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .testimonials-section-config input[type="text"],
        .testimonials-section-config input[type="color"],
        .testimonials-section-config select,
        .testimonials-section-config textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .testimonial-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .testimonial-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .testimonial-number {
            font-weight: bold;
            font-size: 16px;
        }
        
        .remove-testimonial {
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
            max-width: 50px;
            max-height: 50px;
            border-radius: 50%;
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
        
        // Process testimonials for API response
        $testimonials = [];
        foreach ($this->config['testimonials'] ?? [] as $testimonial) {
            $text = $testimonial['text'] ?? '';
            $processed_testimonial = [
                'rating' => (int) $testimonial['rating'],
                'text' => wp_kses_post(wpautop($text)),
                'author_name' => sanitize_text_field($testimonial['author_name']),
                'author_title' => sanitize_text_field($testimonial['author_title'] ?? ''),
                'company' => sanitize_text_field($testimonial['company'] ?? ''),
                'author_image' => ''
            ];
            
            // Handle secure image URL if author image exists
            if (!empty($testimonial['author_image'])) {
                $image_data = $this->process_image_field($testimonial['author_image']);
                $processed_testimonial['author_image'] = $image_data['url'];
                $processed_testimonial['author_image_data'] = $image_data;
            }
            
            $testimonials[] = $processed_testimonial;
        }
        
        $response['config']['testimonials'] = $testimonials;

        // WooCommerce categories for testimonials targeting/filtering
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
            'title' => 'What Our Customers Say',
            'subtitle' => '',
            'layout' => 'grid',
            'columns' => 3,
            'testimonials' => [
                [
                    'rating' => 5,
                    'text' => 'This product exceeded my expectations. Highly recommended!',
                    'author_name' => 'John Doe',
                    'author_title' => 'CEO',
                    'company' => 'Example Corp',
                    'author_image' => ''
                ]
            ],
            'show_author_images' => true,
            'show_ratings' => true,
            'background_color' => '#ffffff',
            'text_color' => '#333333',
            'visible' => true
        ];
    }
}