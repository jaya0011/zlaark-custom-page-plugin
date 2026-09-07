<?php
/**
 * Product Grid Section class
 *
 * @package Custom_Page_Builder
 */

namespace Custom_Page_Builder\Models;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Product Grid section type implementation
 */
class ProductGridSection extends Section {
    
    /**
     * Get section type identifier
     *
     * @return string
     */
    public function get_type(): string {
        return 'product_grid';
    }
    
    /**
     * Get configuration schema for product grid section
     *
     * @return array
     */
    public function get_config_schema(): array {
        return [
            'title' => [
                'type' => 'string',
                'required' => false,
                'default' => 'Featured Products'
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
                'options' => ['grid', 'list']
            ],
            'columns' => [
                'type' => 'integer',
                'required' => false,
                'default' => 3,
                'min' => 1,
                'max' => 5
            ],
            'products' => [
                'type' => 'array',
                'required' => true,
                'default' => [],
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'image' => [
                            'type' => 'string',
                            'required' => true
                        ],
                        'title' => [
                            'type' => 'string',
                            'required' => true
                        ],
                        'wc_categories' => [
                            'type' => 'array',
                            'required' => false,
                            'default' => [],
                            'description' => 'WooCommerce product categories'
                        ],
                        'wc_tags' => [
                            'type' => 'array',
                            'required' => false,
                            'default' => [],
                            'description' => 'WooCommerce product tags'
                        ],
                        'price' => [
                            'type' => 'string',
                            'required' => true
                        ],
                        'sale_price' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => ''
                        ],
                        'description' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => ''
                        ],
                        'link' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => ''
                        ],
                        'button_text' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => 'View Product'
                        ],
                        'badge' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => ''
                        ],
                        'sku' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => ''
                        ],
                        'featured' => [
                            'type' => 'boolean',
                            'required' => false,
                            'default' => false
                        ]
                    ]
                ]
            ],
            'show_prices' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ],
            'show_descriptions' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ],
            'show_buttons' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true
            ],
            'card_style' => [
                'type' => 'string',
                'required' => false,
                'default' => 'standard',
                'options' => ['standard', 'minimal', 'overlay']
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
            'button_color' => [
                'type' => 'string',
                'required' => false,
                'default' => '#007cba'
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
                'description' => 'WooCommerce product categories for section'
            ]
        ];
    }
    
    /**
     * Validate product grid section configuration
     *
     * @param array $config Configuration data
     * @return bool
     */
    public function validate_config(array $config): bool {
        // First run parent validation
        if (!parent::validate_config($config)) {
            return false;
        }
        
        // Validate products array
        if (!isset($config['products']) || !is_array($config['products'])) {
            return false;
        }
        
        // Validate each product
        foreach ($config['products'] as $product) {
            if (!$this->validate_product($product)) {
                return false;
            }
        }
        
        // Validate layout option
        if (isset($config['layout']) && !in_array($config['layout'], ['grid', 'list'])) {
            return false;
        }
        
        // Validate card style option
        if (isset($config['card_style']) && !in_array($config['card_style'], ['standard', 'minimal', 'overlay'])) {
            return false;
        }
        
        // Validate columns range
        if (isset($config['columns'])) {
            $columns = (int) $config['columns'];
            if ($columns < 1 || $columns > 5) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate individual product data
     *
     * @param array $product Product data
     * @return bool
     */
    private function validate_product(array $product): bool {
        // Required fields
        if (empty($product['image']) || empty($product['title']) || empty($product['price'])) {
            return false;
        }
        
        // Validate price format (basic check)
        if (!is_string($product['price'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Render admin template for product grid configuration
     *
     * @return string
     */
    public function render_admin_template(): string {
        ob_start();
        
        // Set section variable for template
        $section = $this;
        
        // Include the template file
        $template_path = plugin_dir_path(__FILE__) . '../templates/admin/section-templates/product-grid.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error">Product Grid template not found.</div>';
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
            // Add product functionality
            $('#add-product').on('click', function() {
                var index = $('#products-list .product-item').length;
                var template = `
                    <div class="product-item" data-index="${index}">
                        <div class="product-header">
                            <span class="product-number">${index + 1}</span>
                            <button type="button" class="remove-product"><?php _e('Remove', 'custom-page-builder'); ?></button>
                        </div>
                        
                        <div class="config-row">
                            <div class="config-group">
                                <label><?php _e('Product Image', 'custom-page-builder'); ?></label>
                                <div class="image-upload-field">
                                    <input type="hidden" name="config[products][${index}][image]" 
                                           class="image-url-input" />
                                    <div class="image-button-group">
                                        <button type="button" class="upload-image-btn button"><?php _e('📷 Upload Image', 'custom-page-builder'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="config-group">
                            <label><?php _e('Product Title', 'custom-page-builder'); ?></label>
                            <input type="text" name="config[products][${index}][title]" 
                                   placeholder="<?php _e('Product Name', 'custom-page-builder'); ?>" />
                        </div>
                        
                        <!-- Category Page Link -->
                        <div class="config-group">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Category Page Link', 'custom-page-builder'); ?></label>
                            <p style="color: #666; font-size: 12px; margin: 0 0 8px 0;"><?php _e('Select the category this banner image should link to on the frontend.', 'custom-page-builder'); ?></p>

                            <input type="hidden" name="config[products][${index}][wc_categories]" class="cpb-selected-wc-categories" value="[]" />

                            <ul class="categorychecklist form-no-clear" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff; margin: 0 0 8px 0; list-style: none;">
                                <?php
                                if (class_exists('WooCommerce')):
                                    $wc_categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name']);
                                    if (!empty($wc_categories) && !is_wp_error($wc_categories)):
                                        foreach ($wc_categories as $cat):
                                ?>
                                    <li style="margin: 0; padding: 6px 8px;">
                                        <label style="display: flex; align-items: center; cursor: pointer; font-weight: normal; font-size: 13px;">
                                            <input type="checkbox"
                                                   class="wc-category-checkbox"
                                                   value="<?php echo $cat->term_id; ?>"
                                                   data-product-index="${index}"
                                                   style="margin: 0 8px 0 0;" />
                                            <?php echo esc_html($cat->name); ?>
                                            <?php if ($cat->count > 0): ?>
                                                <span style="color: #999; margin-left: 4px; font-size: 11px;">(<?php echo $cat->count; ?>)</span>
                                            <?php endif; ?>
                                        </label>
                                    </li>
                                <?php
                                        endforeach;
                                    else:
                                ?>
                                    <li style="padding: 12px; color: #666;"><?php _e('No WooCommerce categories found.', 'custom-page-builder'); ?></li>
                                <?php
                                    endif;
                                else:
                                ?>
                                    <li style="padding: 12px; color: #666;"><?php _e('WooCommerce is not active.', 'custom-page-builder'); ?></li>
                                <?php endif; ?>
                            </ul>

                            <div class="selected-wc-categories-display" style="padding: 8px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
                                <strong><?php _e('Selected:', 'custom-page-builder'); ?></strong>
                                <span class="selected-wc-categories-tags" style="color: #999; font-style: italic; margin-left: 4px;"><?php _e('None', 'custom-page-builder'); ?></span>
                            </div>
                        </div>
                        
                        <div class="config-row">
                            <div class="config-group">
                                <label><?php _e('Price', 'custom-page-builder'); ?></label>
                                <input type="text" name="config[products][${index}][price]" 
                                       placeholder="<?php _e('$99.99', 'custom-page-builder'); ?>" />
                            </div>
                            
                            <div class="config-group">
                                <label><?php _e('Sale Price (Optional)', 'custom-page-builder'); ?></label>
                                <input type="text" name="config[products][${index}][sale_price]" 
                                       placeholder="<?php _e('$79.99', 'custom-page-builder'); ?>" />
                            </div>
                        </div>
                        
                        <div class="config-row">
                            <div class="config-group">
                                <label><?php _e('Badge (Optional)', 'custom-page-builder'); ?></label>
                                <input type="text" name="config[products][${index}][badge]" 
                                       placeholder="<?php _e('Sale, New, Featured', 'custom-page-builder'); ?>" />
                            </div>
                            
                            <div class="config-group">
                                <label><?php _e('SKU (Optional)', 'custom-page-builder'); ?></label>
                                <input type="text" name="config[products][${index}][sku]" 
                                       placeholder="<?php _e('Product SKU/Code', 'custom-page-builder'); ?>" />
                            </div>
                        </div>
                        
                        <div class="config-group">
                            <label><?php _e('Description', 'custom-page-builder'); ?></label>
                            <textarea name="config[products][${index}][description]" rows="3" 
                                      class="cpb-rich-textarea"
                                      placeholder="<?php _e('Product description...', 'custom-page-builder'); ?>"></textarea>
                        </div>
                        
                        <div class="config-row">
                            <div class="config-group">
                                <label><?php _e('Product Link', 'custom-page-builder'); ?></label>
                                <input type="url" name="config[products][${index}][link]" 
                                       placeholder="<?php _e('https://example.com/product', 'custom-page-builder'); ?>" />
                            </div>
                            
                            <div class="config-group">
                                <label><?php _e('Button Text', 'custom-page-builder'); ?></label>
                                <input type="text" name="config[products][${index}][button_text]" 
                                       value="<?php _e('View Product', 'custom-page-builder'); ?>" />
                            </div>
                        </div>
                        
                        <div class="config-group">
                            <label>
                                <input type="checkbox" name="config[products][${index}][featured]" value="1" />
                                <?php _e('Featured Product', 'custom-page-builder'); ?>
                            </label>
                        </div>
                    </div>
                `;
                
                $('#products-list').append(template);
                updateProductNumbers();
            });
            
            // Remove product functionality
            $(document).on('click', '.remove-product', function() {
                $(this).closest('.product-item').remove();
                updateProductNumbers();
                reindexProducts();
            });
            
            // Update product numbers
            function updateProductNumbers() {
                $('#products-list .product-item').each(function(index) {
                    $(this).find('.product-number').text(index + 1);
                });
            }
            
            // Reindex product form fields
            function reindexProducts() {
                $('#products-list .product-item').each(function(index) {
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
            
            // Add new category functionality
            $(document).on('click', '.cpb-add-new-category', function(e) {
                e.preventDefault();
                var categoryName = prompt('<?php _e('Enter new category name:', 'custom-page-builder'); ?>');
                
                if (categoryName && categoryName.trim() !== '') {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'cpb_add_category',
                            nonce: '<?php echo wp_create_nonce('cpb_admin_nonce'); ?>',
                            name: categoryName.trim()
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php _e('Category added successfully! Page will reload.', 'custom-page-builder'); ?>');
                                location.reload();
                            } else {
                                alert('<?php _e('Error:', 'custom-page-builder'); ?> ' + (response.data.message || 'Unknown error'));
                            }
                        },
                        error: function() {
                            alert('<?php _e('Failed to add category. Please try again.', 'custom-page-builder'); ?>');
                        }
                    });
                }
            });
            
            // Image upload functionality
            $(document).on('click', '.upload-image-btn, .edit-image-btn', function() {
                var button = $(this);
                var container = button.closest('.image-upload-field');
                var field = container.find('.image-url-input');
                
                var mediaUploader = wp.media({
                    title: '<?php _e('Select Product Image', 'custom-page-builder'); ?>',
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
                                    <img src="${attachment.url}" alt="<?php _e('Product Image Preview', 'custom-page-builder'); ?>" />
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
            
            // WooCommerce Category checkbox handler
            $(document).on('change', '.wc-category-checkbox', function() {
                var productIndex = $(this).data('product-index');
                var productItem = $(this).closest('.product-item');
                var hiddenInput = productItem.find('input[name="config[products][' + productIndex + '][wc_categories]"]');
                var displayContainer = productItem.find('.selected-wc-categories-tags');
                
                // Get all checked categories for this product
                var selected = [];
                productItem.find('.wc-category-checkbox:checked').each(function() {
                    selected.push(parseInt($(this).val()));
                });
                
                // Update hidden input
                hiddenInput.val(JSON.stringify(selected));
                
                // Update display
                if (selected.length > 0) {
                    var html = '';
                    productItem.find('.wc-category-checkbox:checked').each(function() {
                        var label = $(this).closest('label').find('strong').text();
                        html += '<span style="display: inline-block; background: #0073aa; color: white; padding: 5px 10px; margin: 2px; border-radius: 3px; font-size: 12px;">' + label + '</span>';
                    });
                    displayContainer.html(html);
                } else {
                    displayContainer.html('<span style="color: #999; font-style: italic;"><?php _e('No categories selected', 'custom-page-builder'); ?></span>');
                }
            });
            
            // WooCommerce Tag checkbox handler
            $(document).on('change', '.wc-tag-checkbox', function() {
                var productIndex = $(this).data('product-index');
                var productItem = $(this).closest('.product-item');
                var hiddenInput = productItem.find('input[name="config[products][' + productIndex + '][wc_tags]"]');
                var displayContainer = productItem.find('.selected-wc-tags-tags');
                
                // Get all checked tags for this product
                var selected = [];
                productItem.find('.wc-tag-checkbox:checked').each(function() {
                    selected.push(parseInt($(this).val()));
                });
                
                // Update hidden input
                hiddenInput.val(JSON.stringify(selected));
                
                // Update display
                if (selected.length > 0) {
                    var html = '';
                    productItem.find('.wc-tag-checkbox:checked').each(function() {
                        var label = $(this).closest('label').find('strong').text();
                        html += '<span style="display: inline-block; background: #10b981; color: white; padding: 5px 10px; margin: 2px; border-radius: 3px; font-size: 12px;">' + label + '</span>';
                    });
                    displayContainer.html(html);
                } else {
                    displayContainer.html('<span style="color: #999; font-style: italic;"><?php _e('No tags selected', 'custom-page-builder'); ?></span>');
                }
            });
        });
        </script>
        
        <style>
        .product-grid-section-config .config-group {
            margin-bottom: 15px;
        }
        
        .product-grid-section-config .config-row {
            display: flex;
            gap: 15px;
        }
        
        .product-grid-section-config .config-row .config-group {
            flex: 1;
        }
        
        .product-grid-section-config label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .product-grid-section-config input[type="text"],
        .product-grid-section-config input[type="url"],
        .product-grid-section-config input[type="color"],
        .product-grid-section-config select,
        .product-grid-section-config textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .product-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .product-number {
            font-weight: bold;
            font-size: 16px;
        }
        
        .remove-product {
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
        
        // Process products for API response
        $products = [];
        foreach ($this->config['products'] ?? [] as $product) {
            $description = $product['description'] ?? '';
            
            // Handle WooCommerce categories - decode if JSON string
            $wc_categories = $product['wc_categories'] ?? [];
            if (is_string($wc_categories)) {
                $wc_categories = json_decode($wc_categories, true) ?: [];
            }
            
            // Handle WooCommerce tags - decode if JSON string
            $wc_tags = $product['wc_tags'] ?? [];
            if (is_string($wc_tags)) {
                $wc_tags = json_decode($wc_tags, true) ?: [];
            }
            
            // Get WooCommerce category details
            $wc_category_details = [];
            if (!empty($wc_categories)) {
                $wc_category_details = \Custom_Page_Builder\Category_Manager::get_woocommerce_categories_by_ids($wc_categories);
            }
            
            // Get WooCommerce tag details
            $wc_tag_details = [];
            if (!empty($wc_tags)) {
                foreach ($wc_tags as $tag_id) {
                    $tag = get_term($tag_id, 'product_tag');
                    if ($tag && !is_wp_error($tag)) {
                        $wc_tag_details[] = [
                            'id' => (string) $tag->term_id,
                            'name' => $tag->name,
                            'slug' => $tag->slug,
                            'count' => $tag->count
                        ];
                    }
                }
            }
            
            $processed_product = [
                'title' => sanitize_text_field($product['title']),
                'wc_categories' => $wc_categories,
                'wc_category_details' => $wc_category_details,
                'wc_tags' => $wc_tags,
                'wc_tag_details' => $wc_tag_details,
                'price' => sanitize_text_field($product['price']),
                'sale_price' => sanitize_text_field($product['sale_price'] ?? ''),
                'description' => wp_kses_post(wpautop($description)),
                'link' => esc_url($product['link'] ?? ''),
                'button_text' => sanitize_text_field($product['button_text'] ?? 'View Product'),
                'badge' => sanitize_text_field($product['badge'] ?? ''),
                'sku' => sanitize_text_field($product['sku'] ?? ''),
                'featured' => (bool) ($product['featured'] ?? false),
                'image' => ''
            ];
            
            // Handle secure image URL if product image exists
            if (!empty($product['image'])) {
                $image_data = $this->process_image_field($product['image']);
                $processed_product['image'] = $image_data['url'];
                $processed_product['image_data'] = $image_data;
            }
            
            $products[] = $processed_product;
        }
        
        $response['config']['products'] = $products;

        // Section-level WooCommerce categories
        $section_wc_categories = $this->config['wc_categories'] ?? [];
        if (is_string($section_wc_categories)) {
            $section_wc_categories = json_decode($section_wc_categories, true) ?: [];
        }

        $response['config']['wc_categories'] = $section_wc_categories;

        $section_wc_category_details = [];
        if (!empty($section_wc_categories)) {
            if (in_array('all', $section_wc_categories, true)) {
                $section_wc_category_details = \Custom_Page_Builder\Category_Manager::get_woocommerce_categories();
            } else {
                $section_wc_category_details = \Custom_Page_Builder\Category_Manager::get_woocommerce_categories_by_ids($section_wc_categories);
            }
        }

        $response['config']['wc_category_details'] = $section_wc_category_details;
        
        return $response;
    }
    
    /**
     * Get default configuration
     *
     * @return array
     */
    public function get_default_config(): array {
        return [
            'title' => 'Featured Products',
            'subtitle' => '',
            'layout' => 'grid',
            'columns' => 3,
            'products' => [
                [
                    'image' => '',
                    'title' => 'Sample Product',
                    'wc_categories' => [],
                    'wc_tags' => [],
                    'price' => '$99.99',
                    'sale_price' => '',
                    'description' => 'This is a sample product description.',
                    'link' => '',
                    'button_text' => 'View Product',
                    'badge' => '',
                    'sku' => '',
                    'featured' => false
                ]
            ],
            'show_prices' => true,
            'show_descriptions' => true,
            'show_buttons' => true,
            'card_style' => 'standard',
            'background_color' => '#ffffff',
            'text_color' => '#333333',
            'button_color' => '#007cba',
            'visible' => true
        ];
    }
}