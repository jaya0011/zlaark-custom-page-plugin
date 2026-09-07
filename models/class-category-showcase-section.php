<?php
/**
 * Category Showcase Section class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Models;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Category showcase section type implementation
 */
class CategoryShowcaseSection extends Section {
    
    /**
     * Get section type identifier
     *
     * @return string
     */
    public function get_type(): string {
        return 'category_showcase';
    }
    
    /**
     * Get configuration schema for category showcase section
     *
     * @return array
     */
    public function get_config_schema(): array {
        return [
            'title' => [
                'type' => 'string',
                'required' => false,
                'default' => 'Shop by Category'
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
                'default' => 4,
                'min' => 1,
                'max' => 6
            ],
            'category_source' => [
                'type' => 'string',
                'required' => false,
                'default' => 'manual',
                'options' => ['manual', 'woocommerce']
            ],
            'wc_categories' => [
                'type' => 'array',
                'required' => false,
                'default' => []
            ],
            'categories' => [
                'type' => 'array',
                'required' => true,
                'default' => [],
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'required' => true
                        ],
                        'image' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => ''
                        ],
                        'link' => [
                            'type' => 'string',
                            'required' => true
                        ],
                        'description' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => ''
                        ],
                        'link_target' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => '_self',
                            'options' => ['_self', '_blank']
                        ]
                    ]
                ]
            ],
            'show_descriptions' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ],
            'card_style' => [
                'type' => 'string',
                'required' => false,
                'default' => 'overlay',
                'options' => ['overlay', 'below']
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
            'hover_effect' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ],
            'visible' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ]
        ];
    }
    
    /**
     * Validate category showcase section configuration
     *
     * @param array $config Configuration data
     * @return bool
     */
    public function validate_config(array $config): bool {
        // First run parent validation
        if (!parent::validate_config($config)) {
            return false;
        }
        
        // Validate categories array
        if (!isset($config['categories']) || !is_array($config['categories'])) {
            return false;
        }
        
        // Validate each category
        foreach ($config['categories'] as $category) {
            if (!$this->validate_category($category)) {
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
            if ($columns < 1 || $columns > 6) {
                return false;
            }
        }
        
        // Validate card style option
        if (isset($config['card_style']) && !in_array($config['card_style'], ['overlay', 'below'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate individual category data
     *
     * @param array $category Category data
     * @return bool
     */
    private function validate_category(array $category): bool {
        // Required fields
        if (empty($category['title']) || empty($category['link'])) {
            return false;
        }
        
        // Validate link target if provided
        if (isset($category['link_target']) && !in_array($category['link_target'], ['_self', '_blank'])) {
            return false;
        }
        
        // Validate URL format for link
        if (!filter_var($category['link'], FILTER_VALIDATE_URL) && !preg_match('/^\//', $category['link'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Render admin template for category showcase configuration
     *
     * @return string
     */
    public function render_admin_template(): string {
        ob_start();
        
        // Set section variable for template
        $section = $this;
        
        // Include the template file
        $template_path = plugin_dir_path(__FILE__) . '../templates/admin/section-templates/category-showcase.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error">Category showcase template not found.</div>';
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
            // Handle category source toggle
            $(document).on('change', '.category-source-radio', function() {
                var source = $(this).val();
                var container = $(this).closest('.category-showcase-section-config');
                
                if (source === 'woocommerce') {
                    container.find('.woocommerce-categories-section').show();
                    container.find('.manual-categories-section').hide();
                } else {
                    container.find('.woocommerce-categories-section').hide();
                    container.find('.manual-categories-section').show();
                }
            });
            
            // Add category functionality
            $('#add-category').on('click', function() {
                var index = $('#categories-list .category-item').length;
                var template = `
                    <div class="category-item" data-index="${index}">
                        <div class="category-header">
                            <span class="category-number">${index + 1}</span>
                            <button type="button" class="remove-category"><?php _e('Remove', 'custom-page-builder'); ?></button>
                        </div>
                        
                        <div class="config-row">
                            <div class="config-group">
                                <label><?php _e('Category Title', 'custom-page-builder'); ?></label>
                                <input type="text" name="config[categories][${index}][title]" 
                                       placeholder="<?php _e('Category Name', 'custom-page-builder'); ?>" />
                            </div>
                            
                            <div class="config-group">
                                <label><?php _e('Link URL', 'custom-page-builder'); ?></label>
                                <input type="url" name="config[categories][${index}][link]" 
                                       placeholder="<?php _e('https://example.com/category', 'custom-page-builder'); ?>" />
                            </div>
                        </div>
                        
                        <div class="config-row">
                            <div class="config-group">
                                <label><?php _e('Link Target', 'custom-page-builder'); ?></label>
                                <select name="config[categories][${index}][link_target]">
                                    <option value="_self"><?php _e('Same Window', 'custom-page-builder'); ?></option>
                                    <option value="_blank"><?php _e('New Window', 'custom-page-builder'); ?></option>
                                </select>
                            </div>
                            
                            <div class="config-group">
                                <label><?php _e('Category Image', 'custom-page-builder'); ?></label>
                                <div class="image-upload-field">
                                    <input type="hidden" name="config[categories][${index}][image]" 
                                           class="image-url-input" />
                                    <div class="image-button-group">
                                        <button type="button" class="upload-image-btn button"><?php _e('📷 Upload Image', 'custom-page-builder'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="config-group">
                            <label><?php _e('Description (Optional)', 'custom-page-builder'); ?></label>
                            <textarea name="config[categories][${index}][description]" rows="3" 
                                      class="cpb-rich-textarea"
                                      placeholder="<?php _e('Brief category description...', 'custom-page-builder'); ?>"></textarea>
                        </div>
                    </div>
                `;
                
                $('#categories-list').append(template);
                updateCategoryNumbers();
            });
            
            // Remove category functionality
            $(document).on('click', '.remove-category', function() {
                $(this).closest('.category-item').remove();
                updateCategoryNumbers();
                reindexCategories();
            });
            
            // Update category numbers
            function updateCategoryNumbers() {
                $('#categories-list .category-item').each(function(index) {
                    $(this).find('.category-number').text(index + 1);
                });
            }
            
            // Reindex category form fields
            function reindexCategories() {
                $('#categories-list .category-item').each(function(index) {
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
                    title: '<?php _e('Select Category Image', 'custom-page-builder'); ?>',
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
                                    <img src="${attachment.url}" alt="<?php _e('Category Image Preview', 'custom-page-builder'); ?>" />
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
        .category-showcase-section-config .config-group {
            margin-bottom: 15px;
        }
        
        .category-showcase-section-config .config-row {
            display: flex;
            gap: 15px;
        }
        
        .category-showcase-section-config .config-row .config-group {
            flex: 1;
        }
        
        .category-showcase-section-config label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .category-showcase-section-config input[type="text"],
        .category-showcase-section-config input[type="url"],
        .category-showcase-section-config input[type="color"],
        .category-showcase-section-config select,
        .category-showcase-section-config textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .category-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .category-number {
            font-weight: bold;
            font-size: 16px;
        }
        
        .remove-category {
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
            max-width: 80px;
            max-height: 80px;
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
        
        $category_source = $this->config['category_source'] ?? 'manual';
        $response['config']['category_source'] = $category_source;
        
        // Process categories based on source
        $categories = [];
        
        if ($category_source === 'woocommerce') {
            // Get WooCommerce categories
            $wc_categories = $this->config['wc_categories'] ?? [];
            if (is_string($wc_categories)) {
                $wc_categories = json_decode($wc_categories, true) ?: [];
            }
            
            // Check if "all" categories selected
            if (in_array('all', $wc_categories)) {
                // Get all WooCommerce categories
                $all_wc_cats = \Custom_Page_Builder\Category_Manager::get_woocommerce_categories();
                foreach ($all_wc_cats as $cat) {
                    $categories[] = $this->format_woocommerce_category($cat);
                }
            } else {
                // Get selected WooCommerce categories
                $selected_cats = \Custom_Page_Builder\Category_Manager::get_woocommerce_categories_by_ids($wc_categories);
                foreach ($selected_cats as $cat) {
                    $categories[] = $this->format_woocommerce_category($cat);
                }
            }
            
            $response['config']['wc_categories'] = $wc_categories;
        } else {
            // Manual categories
            foreach ($this->config['categories'] ?? [] as $category) {
                $description = $category['description'] ?? '';
                $processed_category = [
                    'title' => sanitize_text_field($category['title']),
                    'link' => esc_url($category['link']),
                    'description' => wp_kses_post(wpautop($description)),
                    'link_target' => sanitize_text_field($category['link_target'] ?? '_self'),
                    'image' => ''
                ];
                
                // Handle secure image URL if category image exists
                if (!empty($category['image'])) {
                    $image_data = $this->process_image_field($category['image']);
                    $processed_category['image'] = $image_data['url'];
                    $processed_category['image_data'] = $image_data;
                }
                
                $categories[] = $processed_category;
            }
        }
        
        $response['config']['categories'] = $categories;
        
        return $response;
    }
    
    /**
     * Format WooCommerce category for API response
     *
     * @param array $cat WooCommerce category data
     * @return array
     */
    private function format_woocommerce_category(array $cat): array {
        // Get category thumbnail
        $thumbnail_id = get_term_meta($cat['id'], 'thumbnail_id', true);
        $image_url = '';
        if ($thumbnail_id) {
            $image_url = wp_get_attachment_url($thumbnail_id);
        }
        
        // Get category link
        $category_link = get_term_link((int) $cat['id'], 'product_cat');
        if (is_wp_error($category_link)) {
            $category_link = '';
        }
        
        return [
            'title' => $cat['name'],
            'link' => $category_link,
            'description' => wp_kses_post(wpautop($cat['description'])),
            'link_target' => '_self',
            'image' => $image_url,
            'wc_category_id' => $cat['id'],
            'product_count' => $cat['count']
        ];
    }
    
    /**
     * Get default configuration
     *
     * @return array
     */
    public function get_default_config(): array {
        return [
            'title' => 'Shop by Category',
            'subtitle' => '',
            'layout' => 'grid',
            'columns' => 4,
            'category_source' => 'manual',
            'wc_categories' => [],
            'categories' => [
                [
                    'title' => 'Electronics',
                    'image' => '',
                    'link' => '/category/electronics',
                    'description' => 'Latest gadgets and electronics',
                    'link_target' => '_self'
                ],
                [
                    'title' => 'Fashion',
                    'image' => '',
                    'link' => '/category/fashion',
                    'description' => 'Trendy clothing and accessories',
                    'link_target' => '_self'
                ]
            ],
            'show_descriptions' => true,
            'card_style' => 'overlay',
            'background_color' => '#ffffff',
            'text_color' => '#333333',
            'hover_effect' => true,
            'visible' => true
        ];
    }
}