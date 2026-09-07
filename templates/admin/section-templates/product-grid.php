<?php
/**
 * Product Grid section admin template
 *
 * @package Custom_Page_Builder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// This template is included by the ProductGridSection class
// Variables available: $section (ProductGridSection instance)
$config = $section->get_config();
?>

<div class="product-grid-section-config">
    <div class="section-header">
        <h3><?php _e('Product Grid Section Configuration', 'custom-page-builder'); ?></h3>
    </div>
    
    <div class="config-group">
        <label for="product-grid-title"><?php _e('Section Title', 'custom-page-builder'); ?></label>
        <input type="text" id="product-grid-title" name="config[title]" 
               value="<?php echo esc_attr($config['title'] ?? 'Featured Products'); ?>" 
               placeholder="<?php _e('Featured Products', 'custom-page-builder'); ?>" />
    </div>
    
    <div class="config-group">
        <label for="product-grid-subtitle"><?php _e('Section Subtitle', 'custom-page-builder'); ?></label>
        <input type="text" id="product-grid-subtitle" name="config[subtitle]" 
               value="<?php echo esc_attr($config['subtitle'] ?? ''); ?>" 
               placeholder="<?php _e('Optional subtitle', 'custom-page-builder'); ?>" />
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label for="product-grid-layout"><?php _e('Layout', 'custom-page-builder'); ?></label>
            <select id="product-grid-layout" name="config[layout]">
                <option value="grid" <?php selected($config['layout'] ?? 'grid', 'grid'); ?>><?php _e('Grid', 'custom-page-builder'); ?></option>
                <option value="list" <?php selected($config['layout'] ?? 'grid', 'list'); ?>><?php _e('List', 'custom-page-builder'); ?></option>
            </select>
        </div>
        
        <div class="config-group">
            <label for="product-grid-columns"><?php _e('Columns', 'custom-page-builder'); ?></label>
            <select id="product-grid-columns" name="config[columns]">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php selected($config['columns'] ?? 3, $i); ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="config-group">
            <label for="product-grid-card-style"><?php _e('Card Style', 'custom-page-builder'); ?></label>
            <select id="product-grid-card-style" name="config[card_style]">
                <option value="standard" <?php selected($config['card_style'] ?? 'standard', 'standard'); ?>><?php _e('Standard', 'custom-page-builder'); ?></option>
                <option value="minimal" <?php selected($config['card_style'] ?? 'standard', 'minimal'); ?>><?php _e('Minimal', 'custom-page-builder'); ?></option>
                <option value="overlay" <?php selected($config['card_style'] ?? 'standard', 'overlay'); ?>><?php _e('Overlay', 'custom-page-builder'); ?></option>
            </select>
        </div>
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label>
                <input type="checkbox" name="config[show_prices]" value="1" 
                       <?php checked($config['show_prices'] ?? true); ?> />
                <?php _e('Show Prices', 'custom-page-builder'); ?>
            </label>
        </div>
        
        <div class="config-group">
            <label>
                <input type="checkbox" name="config[show_descriptions]" value="1" 
                       <?php checked($config['show_descriptions'] ?? true); ?> />
                <?php _e('Show Descriptions', 'custom-page-builder'); ?>
            </label>
        </div>
        
        <div class="config-group">
            <label>
                <input type="checkbox" name="config[show_buttons]" value="1" 
                       <?php checked($config['show_buttons'] ?? true); ?> />
                <?php _e('Show Buttons', 'custom-page-builder'); ?>
            </label>
        </div>
    </div>
    
    <div class="config-group">
        <h4><?php _e('Products', 'custom-page-builder'); ?></h4>
        <div id="products-list">
            <?php 
            $products = $config['products'] ?? [];
            foreach ($products as $index => $product): 
            ?>
                <div class="product-item" data-index="<?php echo $index; ?>">
                    <div class="product-header">
                        <span class="product-number"><?php echo $index + 1; ?></span>
                        <button type="button" class="remove-product button"><?php _e('🗑️ Remove', 'custom-page-builder'); ?></button>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label><?php _e('Product Image', 'custom-page-builder'); ?></label>
                            <div class="image-upload-field">
                                <input type="hidden" name="config[products][<?php echo $index; ?>][image]" 
                                       value="<?php echo esc_attr($product['image'] ?? ''); ?>" 
                                       class="image-url-input" />
                                <?php if (!empty($product['image'])): ?>
                                <div class="image-preview-container">
                                    <div class="image-preview">
                                        <img src="<?php echo esc_url($product['image']); ?>" alt="<?php _e('Product Image Preview', 'custom-page-builder'); ?>" />
                                    </div>
                                    <div class="image-button-group">
                                        <button type="button" class="edit-image-btn button"><?php _e('✏️ Edit Image', 'custom-page-builder'); ?></button>
                                        <button type="button" class="remove-image-btn button"><?php _e('🗑️ Delete Image', 'custom-page-builder'); ?></button>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="image-button-group">
                                    <button type="button" class="upload-image-btn button"><?php _e('📷 Upload Image', 'custom-page-builder'); ?></button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="config-group">
                        <label><?php _e('Product Title', 'custom-page-builder'); ?></label>
                        <input type="text" name="config[products][<?php echo $index; ?>][title]" 
                               value="<?php echo esc_attr($product['title'] ?? ''); ?>" 
                               placeholder="<?php _e('Product Name', 'custom-page-builder'); ?>" />
                    </div>
                    
                    <!-- Category Page Link -->
                    <div class="config-group">
                        <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                            <?php _e('Category Page Link', 'custom-page-builder'); ?>
                        </label>
                        <p style="color: #666; font-size: 12px; margin: 0 0 10px 0;">
                            <?php _e('Select the category this banner image should link to. Clicking the image on the frontend will navigate to that category page.', 'custom-page-builder'); ?>
                        </p>
                        <?php
                        $field_name = 'config[products][' . $index . '][wc_categories]';
                        $selected_categories = $product['wc_categories'] ?? [];
                        $label = __('Select Category', 'custom-page-builder');
                        $show_all_option = false;
                        include CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-category-selector.php';
                        ?>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label><?php _e('Price', 'custom-page-builder'); ?></label>
                            <input type="text" name="config[products][<?php echo $index; ?>][price]" 
                                   value="<?php echo esc_attr($product['price'] ?? ''); ?>" 
                                   placeholder="<?php _e('$99.99', 'custom-page-builder'); ?>" />
                        </div>
                        
                        <div class="config-group">
                            <label><?php _e('Sale Price (Optional)', 'custom-page-builder'); ?></label>
                            <input type="text" name="config[products][<?php echo $index; ?>][sale_price]" 
                                   value="<?php echo esc_attr($product['sale_price'] ?? ''); ?>" 
                                   placeholder="<?php _e('$79.99', 'custom-page-builder'); ?>" />
                        </div>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label><?php _e('Badge (Optional)', 'custom-page-builder'); ?></label>
                            <input type="text" name="config[products][<?php echo $index; ?>][badge]" 
                                   value="<?php echo esc_attr($product['badge'] ?? ''); ?>" 
                                   placeholder="<?php _e('Sale, New, Featured', 'custom-page-builder'); ?>" />
                        </div>
                        
                        <div class="config-group">
                            <label><?php _e('SKU (Optional)', 'custom-page-builder'); ?></label>
                            <input type="text" name="config[products][<?php echo $index; ?>][sku]" 
                                   value="<?php echo esc_attr($product['sku'] ?? ''); ?>" 
                                   placeholder="<?php _e('Product SKU/Code', 'custom-page-builder'); ?>" />
                        </div>
                    </div>
                    
                    <div class="config-group">
                        <label><?php _e('Description', 'custom-page-builder'); ?></label>
                        <textarea name="config[products][<?php echo $index; ?>][description]" rows="3" 
                                  class="cpb-rich-textarea"
                                  placeholder="<?php _e('Product description...', 'custom-page-builder'); ?>"><?php echo esc_textarea($product['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label><?php _e('Product Link', 'custom-page-builder'); ?></label>
                            <input type="url" name="config[products][<?php echo $index; ?>][link]" 
                                   value="<?php echo esc_attr($product['link'] ?? ''); ?>" 
                                   placeholder="<?php _e('https://example.com/product', 'custom-page-builder'); ?>" />
                        </div>
                        
                        <div class="config-group">
                            <label><?php _e('Button Text', 'custom-page-builder'); ?></label>
                            <input type="text" name="config[products][<?php echo $index; ?>][button_text]" 
                                   value="<?php echo esc_attr($product['button_text'] ?? 'View Product'); ?>" />
                        </div>
                    </div>
                    
                    <div class="config-group">
                        <label>
                            <input type="checkbox" name="config[products][<?php echo $index; ?>][featured]" value="1" 
                                   <?php checked($product['featured'] ?? false); ?> />
                            <?php _e('Featured Product', 'custom-page-builder'); ?>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button type="button" id="add-product" class="button"><?php _e('Add Product', 'custom-page-builder'); ?></button>
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label for="product-grid-bg-color"><?php _e('Background Color', 'custom-page-builder'); ?></label>
            <input type="color" id="product-grid-bg-color" name="config[background_color]" 
                   value="<?php echo esc_attr($config['background_color'] ?? '#ffffff'); ?>" />
        </div>
        
        <div class="config-group">
            <label for="product-grid-text-color"><?php _e('Text Color', 'custom-page-builder'); ?></label>
            <input type="color" id="product-grid-text-color" name="config[text_color]" 
                   value="<?php echo esc_attr($config['text_color'] ?? '#333333'); ?>" />
        </div>
        
        <div class="config-group">
            <label for="product-grid-button-color"><?php _e('Button Color', 'custom-page-builder'); ?></label>
            <input type="color" id="product-grid-button-color" name="config[button_color]" 
                   value="<?php echo esc_attr($config['button_color'] ?? '#007cba'); ?>" />
        </div>
    </div>
    
    <!-- WooCommerce Categories for Section (fallback / section-level) -->
    <div class="config-group">
        <label style="font-weight: 600; display: block; margin-bottom: 5px;">
            <?php _e('Section-Level Category (Optional)', 'custom-page-builder'); ?>
        </label>
        <p style="color: #666; font-size: 12px; margin: 0 0 10px 0;">
            <?php _e('Optionally associate this entire section with WooCommerce categories.', 'custom-page-builder'); ?>
        </p>
        <?php
        $field_name = 'config[wc_categories]';
        $selected_categories = $config['wc_categories'] ?? [];
        $label = __('Select WooCommerce Product Categories', 'custom-page-builder');
        $show_all_option = true;
        include CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-category-selector.php';
        ?>
    </div>
    
    <div class="config-group">
        <label>
            <input type="checkbox" name="config[visible]" value="1" 
                   <?php checked($config['visible'] ?? true); ?> />
            <?php _e('Section Visible', 'custom-page-builder'); ?>
        </label>
    </div>
</div>