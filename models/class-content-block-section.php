<?php
/**
 * Content Block Section class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Models;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content block section type implementation
 */
class ContentBlockSection extends Section {
    
    /**
     * Get section type identifier
     *
     * @return string
     */
    public function get_type(): string {
        return 'content_block';
    }
    
    /**
     * Get configuration schema for content block section
     *
     * @return array
     */
    public function get_config_schema(): array {
        return [
            'title' => [
                'type' => 'string',
                'required' => false,
                'default' => ''
            ],
            'subtitle' => [
                'type' => 'string',
                'required' => false,
                'default' => ''
            ],
            'layout' => [
                'type' => 'string',
                'required' => false,
                'default' => 'single_column',
                'options' => ['single_column', 'two_column', 'image_left', 'image_right']
            ],
            'content' => [
                'type' => 'string',
                'required' => true,
                'default' => ''
            ],
            'secondary_content' => [
                'type' => 'string',
                'required' => false,
                'default' => ''
            ],
            'image' => [
                'type' => 'string',
                'required' => false,
                'default' => ''
            ],
            'image_alt' => [
                'type' => 'string',
                'required' => false,
                'default' => ''
            ],
            'image_position' => [
                'type' => 'string',
                'required' => false,
                'default' => 'top',
                'options' => ['top', 'bottom', 'left', 'right']
            ],
            'content_alignment' => [
                'type' => 'string',
                'required' => false,
                'default' => 'left',
                'options' => ['left', 'center', 'right', 'justify']
            ],
            'enable_rich_text' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ],
            'max_width' => [
                'type' => 'string',
                'required' => false,
                'default' => '1200px'
            ],
            'padding' => [
                'type' => 'object',
                'required' => false,
                'default' => [
                    'top' => '40px',
                    'bottom' => '40px',
                    'left' => '20px',
                    'right' => '20px'
                ],
                'properties' => [
                    'top' => ['type' => 'string', 'default' => '40px'],
                    'bottom' => ['type' => 'string', 'default' => '40px'],
                    'left' => ['type' => 'string', 'default' => '20px'],
                    'right' => ['type' => 'string', 'default' => '20px']
                ]
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
            'call_to_action' => [
                'type' => 'object',
                'required' => false,
                'default' => [
                    'enabled' => false,
                    'text' => '',
                    'url' => '',
                    'target' => '_self',
                    'style' => 'button'
                ],
                'properties' => [
                    'enabled' => ['type' => 'boolean', 'default' => false],
                    'text' => ['type' => 'string', 'default' => ''],
                    'url' => ['type' => 'string', 'default' => ''],
                    'target' => ['type' => 'string', 'default' => '_self', 'options' => ['_self', '_blank']],
                    'style' => ['type' => 'string', 'default' => 'button', 'options' => ['button', 'link']]
                ]
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
     * Validate content block section configuration
     *
     * @param array $config Configuration data
     * @return bool
     */
    public function validate_config(array $config): bool {
        // First run parent validation
        if (!parent::validate_config($config)) {
            return false;
        }
        
        // Content is required
        if (empty($config['content'])) {
            return false;
        }
        
        // Validate layout option
        if (isset($config['layout']) && !in_array($config['layout'], ['single_column', 'two_column', 'image_left', 'image_right'])) {
            return false;
        }
        
        // Validate image position option
        if (isset($config['image_position']) && !in_array($config['image_position'], ['top', 'bottom', 'left', 'right'])) {
            return false;
        }
        
        // Validate content alignment option
        if (isset($config['content_alignment']) && !in_array($config['content_alignment'], ['left', 'center', 'right', 'justify'])) {
            return false;
        }
        
        // Validate call to action if enabled
        if (isset($config['call_to_action']) && is_array($config['call_to_action'])) {
            $cta = $config['call_to_action'];
            if (!empty($cta['enabled']) && $cta['enabled']) {
                if (empty($cta['text']) || empty($cta['url'])) {
                    return false;
                }
                
                // Validate URL format
                if (!filter_var($cta['url'], FILTER_VALIDATE_URL) && !preg_match('/^\//', $cta['url'])) {
                    return false;
                }
                
                // Validate target option
                if (isset($cta['target']) && !in_array($cta['target'], ['_self', '_blank'])) {
                    return false;
                }
                
                // Validate style option
                if (isset($cta['style']) && !in_array($cta['style'], ['button', 'link'])) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Render admin template for content block configuration
     *
     * @return string
     */
    public function render_admin_template(): string {
        ob_start();
        
        // Set section variable for template
        $section = $this;
        
        // Include the template file
        $template_path = plugin_dir_path(__FILE__) . '../templates/admin/section-templates/content-block.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error">Content block template not found.</div>';
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
            // Layout change handler
            $('#content-block-layout').on('change', function() {
                var layout = $(this).val();
                var imageFields = $('.image-fields');
                var secondaryContentField = $('.secondary-content-field');
                
                if (layout === 'single_column') {
                    imageFields.show();
                    secondaryContentField.hide();
                } else if (layout === 'two_column') {
                    imageFields.hide();
                    secondaryContentField.show();
                } else if (layout === 'image_left' || layout === 'image_right') {
                    imageFields.show();
                    secondaryContentField.hide();
                }
            }).trigger('change');
            
            // CTA toggle handler
            $('#cta-enabled').on('change', function() {
                var ctaFields = $('.cta-fields');
                if ($(this).is(':checked')) {
                    ctaFields.show();
                } else {
                    ctaFields.hide();
                }
            }).trigger('change');
            
            // Image upload and edit functionality
            $(document).on('click', '#upload-content-image, #edit-content-image', function() {
                var button = $(this);
                var container = button.closest('.image-upload-field');
                var field = $('#content-block-image');
                
                var mediaUploader = wp.media({
                    title: '<?php _e('Select Content Image', 'custom-page-builder'); ?>',
                    button: {
                        text: '<?php _e('Use this image', 'custom-page-builder'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    field.val(attachment.url);
                    $('#content-block-image-alt').val(attachment.alt || '');
                    
                    // Update or create preview container
                    var previewContainer = container.find('.image-preview-container');
                    if (previewContainer.length === 0) {
                        container.find('.image-button-group').replaceWith(`
                            <div class="image-preview-container">
                                <div class="image-preview">
                                    <img src="${attachment.url}" alt="<?php _e('Content Image Preview', 'custom-page-builder'); ?>" />
                                </div>
                                <div class="image-button-group">
                                    <button type="button" id="edit-content-image" class="button edit-image-btn" data-target="content-block-image"><?php _e('✏️ Edit Image', 'custom-page-builder'); ?></button>
                                    <button type="button" id="remove-content-image" class="button remove-image-btn" data-target="content-block-image"><?php _e('🗑️ Delete Image', 'custom-page-builder'); ?></button>
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
            $(document).on('click', '#remove-content-image', function() {
                var button = $(this);
                var container = button.closest('.image-upload-field');
                var field = $('#content-block-image');
                var previewContainer = container.find('.image-preview-container');
                
                field.val('');
                $('#content-block-image-alt').val('');
                previewContainer.replaceWith(`
                    <div class="image-button-group">
                        <button type="button" id="upload-content-image" class="button upload-image-btn" data-target="content-block-image"><?php _e('📷 Upload Image', 'custom-page-builder'); ?></button>
                    </div>
                `);
            });
            
            // Initialize WordPress editor if available
            if (typeof wp !== 'undefined' && wp.editor) {
                // Initialize rich text editor for main content
                wp.editor.initialize('content-block-content', {
                    tinymce: {
                        wpautop: true,
                        plugins: 'charmap colorpicker hr lists paste tabfocus textcolor fullscreen wordpress wpautoresize wpeditimage wpemoji wpgallery wplink wptextpattern',
                        toolbar1: 'bold italic underline strikethrough | bullist numlist | blockquote hr | alignleft aligncenter alignright | link unlink | wp_more | spellchecker fullscreen',
                        toolbar2: 'formatselect | pastetext removeformat | charmap | outdent indent | undo redo | wp_help'
                    },
                    quicktags: true,
                    mediaButtons: true
                });
                
                // Initialize rich text editor for secondary content
                wp.editor.initialize('content-block-secondary-content', {
                    tinymce: {
                        wpautop: true,
                        plugins: 'charmap colorpicker hr lists paste tabfocus textcolor fullscreen wordpress wpautoresize wpeditimage wpemoji wpgallery wplink wptextpattern',
                        toolbar1: 'bold italic underline strikethrough | bullist numlist | blockquote hr | alignleft aligncenter alignright | link unlink | wp_more | spellchecker fullscreen',
                        toolbar2: 'formatselect | pastetext removeformat | charmap | outdent indent | undo redo | wp_help'
                    },
                    quicktags: true,
                    mediaButtons: true
                });
            }
        });
        </script>
        
        <style>
        .content-block-section-config .config-group {
            margin-bottom: 15px;
        }
        
        .content-block-section-config .config-row {
            display: flex;
            gap: 15px;
        }
        
        .content-block-section-config .config-row .config-group {
            flex: 1;
        }
        
        .content-block-section-config label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .content-block-section-config input[type="text"],
        .content-block-section-config input[type="url"],
        .content-block-section-config input[type="color"],
        .content-block-section-config select,
        .content-block-section-config textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .content-block-section-config .wp-editor-wrap {
            margin-bottom: 15px;
        }
        
        .content-image-preview img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .image-upload-field {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
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
        
        .cta-fields {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            background: #f9f9f9;
            margin-top: 10px;
        }
        
        .padding-controls {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .padding-controls input {
            width: 100%;
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
        
        // Process content for API response
        // Apply wpautop to convert line breaks to <p> and <br> tags, then sanitize
        $content = $this->config['content'] ?? '';
        $response['config']['content'] = wp_kses_post(wpautop($content));
        
        $secondary_content = $this->config['secondary_content'] ?? '';
        $response['config']['secondary_content'] = wp_kses_post(wpautop($secondary_content));
        
        // Handle secure image URL if image exists
        if (!empty($this->config['image'])) {
            $image_data = $this->process_image_field($this->config['image']);
            $response['config']['image'] = $image_data['url'];
            $response['config']['image_data'] = $image_data;
        }
        
        // Process call to action
        if (!empty($this->config['call_to_action']) && is_array($this->config['call_to_action'])) {
            $cta = $this->config['call_to_action'];
            $response['config']['call_to_action'] = [
                'enabled' => !empty($cta['enabled']),
                'text' => sanitize_text_field($cta['text'] ?? ''),
                'url' => esc_url($cta['url'] ?? ''),
                'target' => sanitize_text_field($cta['target'] ?? '_self'),
                'style' => sanitize_text_field($cta['style'] ?? 'button')
            ];
        }

        // WooCommerce categories for downstream filtering
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
            'title' => '',
            'subtitle' => '',
            'layout' => 'single_column',
            'content' => '<p>Enter your content here. You can use rich text formatting, add links, and include images.</p>',
            'secondary_content' => '',
            'image' => '',
            'image_alt' => '',
            'image_position' => 'top',
            'content_alignment' => 'left',
            'enable_rich_text' => true,
            'max_width' => '1200px',
            'padding' => [
                'top' => '40px',
                'bottom' => '40px',
                'left' => '20px',
                'right' => '20px'
            ],
            'background_color' => '#ffffff',
            'text_color' => '#333333',
            'call_to_action' => [
                'enabled' => false,
                'text' => '',
                'url' => '',
                'target' => '_self',
                'style' => 'button'
            ],
            'visible' => true
        ];
    }
}