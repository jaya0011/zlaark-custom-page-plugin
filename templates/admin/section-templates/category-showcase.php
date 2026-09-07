<?php
/**
 * Category Showcase section admin template
 *
 * @package Custom_Page_Builder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// This template is included by the CategoryShowcaseSection class
// Variables available: $section (CategoryShowcaseSection instance)
$config = $section->get_config();
?>

<div class="category-showcase-section-config">
    <div class="section-header">
        <h3><?php _e('Category Showcase Section Configuration', 'custom-page-builder'); ?></h3>
    </div>
    
    <div class="config-group">
        <label for="category-showcase-title"><?php _e('Section Title', 'custom-page-builder'); ?></label>
        <input type="text" id="category-showcase-title" name="config[title]" 
               value="<?php echo esc_attr($config['title'] ?? 'Shop by Category'); ?>" 
               placeholder="<?php _e('Shop by Category', 'custom-page-builder'); ?>" />
    </div>
    
    <div class="config-group">
        <label for="category-showcase-subtitle"><?php _e('Section Subtitle', 'custom-page-builder'); ?></label>
        <input type="text" id="category-showcase-subtitle" name="config[subtitle]" 
               value="<?php echo esc_attr($config['subtitle'] ?? ''); ?>" 
               placeholder="<?php _e('Optional subtitle', 'custom-page-builder'); ?>" />
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label for="category-showcase-layout"><?php _e('Layout', 'custom-page-builder'); ?></label>
            <select id="category-showcase-layout" name="config[layout]">
                <option value="grid" <?php selected($config['layout'] ?? 'grid', 'grid'); ?>><?php _e('Grid', 'custom-page-builder'); ?></option>
                <option value="slider" <?php selected($config['layout'] ?? 'grid', 'slider'); ?>><?php _e('Slider', 'custom-page-builder'); ?></option>
            </select>
        </div>
        
        <div class="config-group">
            <label for="category-showcase-columns"><?php _e('Columns', 'custom-page-builder'); ?></label>
            <select id="category-showcase-columns" name="config[columns]">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php selected($config['columns'] ?? 4, $i); ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label for="category-showcase-card-style"><?php _e('Card Style', 'custom-page-builder'); ?></label>
            <select id="category-showcase-card-style" name="config[card_style]">
                <option value="overlay" <?php selected($config['card_style'] ?? 'overlay', 'overlay'); ?>><?php _e('Text Overlay', 'custom-page-builder'); ?></option>
                <option value="below" <?php selected($config['card_style'] ?? 'overlay', 'below'); ?>><?php _e('Text Below Image', 'custom-page-builder'); ?></option>
            </select>
        </div>
        
        <div class="config-group">
            <label>
                <input type="checkbox" name="config[show_descriptions]" value="1" 
                       <?php checked($config['show_descriptions'] ?? true); ?> />
                <?php _e('Show Category Descriptions', 'custom-page-builder'); ?>
            </label>
        </div>
    </div>
    
    <div class="config-group">
        <label>
            <input type="checkbox" name="config[hover_effect]" value="1" 
                   <?php checked($config['hover_effect'] ?? true); ?> />
            <?php _e('Enable Hover Effects', 'custom-page-builder'); ?>
        </label>
    </div>
    
    <!-- WooCommerce Category Source -->
    <div class="config-group" style="background: #f0f8ff; padding: 15px; border: 2px solid #0073aa; border-radius: 5px; margin: 20px 0;">
        <h4 style="margin: 0 0 15px 0; color: #0073aa;">
            🛍️ <?php _e('Category Source', 'custom-page-builder'); ?>
        </h4>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 10px;">
                <input type="radio" name="config[category_source]" value="manual" 
                       <?php checked($config['category_source'] ?? 'manual', 'manual'); ?> 
                       class="category-source-radio" />
                <strong><?php _e('Manual Categories', 'custom-page-builder'); ?></strong>
                <span style="color: #666; display: block; margin-left: 25px; font-size: 13px;">
                    <?php _e('Add and configure categories manually below', 'custom-page-builder'); ?>
                </span>
            </label>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 10px;">
                <input type="radio" name="config[category_source]" value="woocommerce" 
                       <?php checked($config['category_source'] ?? 'manual', 'woocommerce'); ?> 
                       class="category-source-radio" />
                <strong><?php _e('WooCommerce Categories', 'custom-page-builder'); ?></strong>
                <span style="color: #666; display: block; margin-left: 25px; font-size: 13px;">
                    <?php _e('Automatically pull from WooCommerce product categories', 'custom-page-builder'); ?>
                </span>
            </label>
        </div>
    </div>
    
    <!-- WooCommerce Category Selector (shown when woocommerce source is selected) -->
    <div class="woocommerce-categories-section" style="display: <?php echo ($config['category_source'] ?? 'manual') === 'woocommerce' ? 'block' : 'none'; ?>;">
        <?php
        $field_name = 'config[wc_categories]';
        $selected_categories = $config['wc_categories'] ?? [];
        $label = __('Select WooCommerce Product Categories', 'custom-page-builder');
        $show_all_option = true;
        
        // Directly embed the category selector code instead of include
        ?>
        <?php
        // Check if WooCommerce is active
        $wc_active = class_exists('WooCommerce');
        
        // Get WooCommerce categories
        $wc_categories = [];
        if ($wc_active) {
            $wc_categories = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ]);
            
            if (is_wp_error($wc_categories)) {
                $wc_categories = [];
            }
        }
        
        // Ensure selected_categories is an array
        if (is_string($selected_categories)) {
            $selected_categories = json_decode($selected_categories, true) ?: [];
        }
        
        // Normalize selected categories to integers for consistent comparison
        $selected_categories = array_map(function($val) {
            return $val === 'all' ? 'all' : intval($val);
        }, $selected_categories);
        
        // Check if "all" is selected
        $all_selected = in_array('all', $selected_categories, true);
        ?>
        
        <div class="wc-category-selector-wrapper" style="background: #fff; padding: 20px; border: 1px solid #ddd; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            
            <?php if (!$wc_active): ?>
                <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 10px;">⚠️</div>
                    <strong style="font-size: 16px; display: block; margin-bottom: 10px; color: #856404;">
                        <?php _e('WooCommerce Not Active', 'custom-page-builder'); ?>
                    </strong>
                    <p style="margin: 0; color: #856404;">
                        <?php _e('Install and activate WooCommerce to use product categories.', 'custom-page-builder'); ?>
                    </p>
                </div>
            <?php else: ?>
                
                <div id="taxonomy-product_cat" class="categorydiv">
                    <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #23282d; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 18px;">🛍️</span> <?php echo esc_html($label); ?>
                    </h4>
                    
                    <!-- Hidden input to store selected categories as JSON -->
                    <input type="hidden" 
                           name="<?php echo esc_attr($field_name); ?>" 
                           class="cpb-selected-wc-categories" 
                           value="<?php echo esc_attr(json_encode($selected_categories)); ?>" />
                    
                    <?php if ($show_all_option): ?>
                    <!-- All Categories Option -->
                    <div style="background: #f0f6fc; border: 1px solid #0969da; padding: 12px 15px; margin: 0 0 15px 0; border-radius: 6px;">
                        <label style="display: flex; align-items: center; cursor: pointer; font-weight: 500; font-size: 14px; margin: 0;">
                            <input type="checkbox" 
                                   class="wc-category-all-checkbox" 
                                   value="all"
                                   <?php checked($all_selected); ?>
                                   style="margin: 0 10px 0 0; width: 18px; height: 18px; cursor: pointer;" />
                            <span style="color: #0969da;">
                                ✨ <?php _e('All Categories', 'custom-page-builder'); ?>
                            </span>
                        </label>
                        <p style="margin: 8px 0 0 28px; color: #656d76; font-size: 12px; line-height: 1.5;">
                            <?php _e('Automatically include all current and future WooCommerce categories', 'custom-page-builder'); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Individual Categories List -->
                    <div class="wc-categories-list" style="<?php echo $all_selected ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                        <div style="font-size: 12px; font-weight: 600; color: #57606a; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?php _e('Select Category', 'custom-page-builder'); ?>
                        </div>
                        <ul class="categorychecklist form-no-clear" style="max-height: 280px; overflow-y: auto; border: 1px solid #d0d7de; padding: 8px; background: #f6f8fa; margin: 0 0 15px 0; list-style: none; border-radius: 6px;">
                            <?php if (!empty($wc_categories)): ?>
                                <?php foreach ($wc_categories as $cat): ?>
                                    <li style="margin: 0 0 4px 0; padding: 0;">
                                        <label class="selectit" style="display: flex; align-items: center; cursor: pointer; padding: 8px 10px; background: #fff; border: 1px solid #d0d7de; border-radius: 6px; transition: all 0.2s; margin: 0;">
                                            <input type="radio" 
                                                name="<?php echo esc_attr( str_replace(array('[',']'), array('_',''), $field_name) ); ?>_single" 
                                                class="wc-category-radio" 
                                                value="<?php echo esc_attr($cat->term_id); ?>" 
                                                <?php checked(in_array(intval($cat->term_id), $selected_categories, true)); ?>
                                                style="margin: 0 10px 0 0; width: 16px; height: 16px; cursor: pointer; accent-color: #0969da;" />
                                            <span style="flex: 1; font-weight: 500; font-size: 13px; color: #24292f;">
                                                <?php echo esc_html($cat->name); ?>
                                            </span>
                                            <?php if ($cat->count > 0): ?>
                                                <span style="color: #57606a; font-size: 12px; background: #f6f8fa; padding: 2px 8px; border-radius: 12px;">
                                                    <?php echo $cat->count; ?>
                                                </span>
                                            <?php endif; ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li style="padding: 40px 20px; text-align: center; background: #fff; border: 2px dashed #d0d7de; border-radius: 6px;">
                                    <div style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;">📦</div>
                                    <strong style="font-size: 14px; display: block; margin-bottom: 10px; color: #24292f;">
                                        <?php _e('No WooCommerce categories found!', 'custom-page-builder'); ?>
                                    </strong>
                                    <p style="color: #57606a; font-size: 13px; margin: 0 0 15px 0;">
                                        <?php _e('Create your first product category to get started', 'custom-page-builder'); ?>
                                    </p>
                                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=product_cat&post_type=product'); ?>" 
                                       target="_blank" 
                                       class="button button-primary" 
                                       style="text-decoration: none;">
                                        <?php _e('Add Product Categories', 'custom-page-builder'); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div style="margin: 15px 0 0; padding-top: 15px; border-top: 1px solid #d0d7de; text-align: center; display: flex; gap: 10px; justify-content: center;">
                        <button type="button" 
                                class="button wc-category-clear-btn" 
                                style="text-decoration: none; font-size: 13px; background: #dc3545; color: white; border-color: #dc3545;">
                            <span style="margin-right: 4px;">�️</span> <?php _e('Clear Selection', 'custom-page-builder'); ?>
                        </button>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=product_cat&post_type=product'); ?>" 
                           target="_blank" 
                           class="button" 
                           style="text-decoration: none; font-size: 13px;">
                            <span style="margin-right: 4px;">⚙️</span> <?php _e('Manage Categories', 'custom-page-builder'); ?>
                        </a>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
        <?php
        ?>
    </div>
    
    <!-- WooCommerce Tag Selector (shown when woocommerce source is selected) -->
    <div class="woocommerce-tags-section" style="display: <?php echo ($config['category_source'] ?? 'manual') === 'woocommerce' ? 'block' : 'none'; ?>;">
        <?php
        $field_name = 'config[wc_tags]';
        $selected_tags = $config['wc_tags'] ?? [];
        $label = __('Select WooCommerce Product Tags', 'custom-page-builder');
        $show_all_option = true;
        
        // Directly embed the tag selector code instead of include
        ?>
        <?php
        // Check if WooCommerce is active
        $wc_active = class_exists('WooCommerce');
        
        // Get WooCommerce tags
        $wc_tags = [];
        if ($wc_active) {
            $wc_tags = get_terms([
                'taxonomy' => 'product_tag',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ]);
            
            if (is_wp_error($wc_tags)) {
                $wc_tags = [];
            }
        }
        
        // Ensure selected_tags is an array
        if (is_string($selected_tags)) {
            $selected_tags = json_decode($selected_tags, true) ?: [];
        }
        
        // Normalize selected tags to integers for consistent comparison
        $selected_tags = array_map(function($val) {
            return $val === 'all' ? 'all' : intval($val);
        }, $selected_tags);
        
        // Check if "all" is selected
        $all_selected = in_array('all', $selected_tags, true);
        ?>
        
        <div class="wc-tag-selector-wrapper" style="background: #fff; padding: 20px; border: 1px solid #ddd; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            
            <?php if (!$wc_active): ?>
                <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 10px;">⚠️</div>
                    <strong style="font-size: 16px; display: block; margin-bottom: 10px; color: #856404;">
                        <?php _e('WooCommerce Not Active', 'custom-page-builder'); ?>
                    </strong>
                    <p style="margin: 0; color: #856404;">
                        <?php _e('Install and activate WooCommerce to use product tags.', 'custom-page-builder'); ?>
                    </p>
                </div>
            <?php else: ?>
                
                <div id="taxonomy-product_tag" class="categorydiv">
                    <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #23282d; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 18px;">🏷️</span> <?php echo esc_html($label); ?>
                    </h4>
                    
                    <!-- Hidden input to store selected tags as JSON -->
                    <input type="hidden" 
                           name="<?php echo esc_attr($field_name); ?>" 
                           class="cpb-selected-wc-tags" 
                           value="<?php echo esc_attr(json_encode($selected_tags)); ?>" />
                    
                    <?php if ($show_all_option): ?>
                    <!-- All Tags Option -->
                    <div style="background: #f0f6fc; border: 1px solid #0969da; padding: 12px 15px; margin: 0 0 15px 0; border-radius: 6px;">
                        <label style="display: flex; align-items: center; cursor: pointer; font-weight: 500; font-size: 14px; margin: 0;">
                            <input type="checkbox" 
                                   class="wc-tag-all-checkbox" 
                                   value="all"
                                   <?php checked($all_selected); ?>
                                   style="margin: 0 10px 0 0; width: 18px; height: 18px; cursor: pointer;" />
                            <span style="color: #0969da;">
                                ✨ <?php _e('All Tags', 'custom-page-builder'); ?>
                            </span>
                        </label>
                        <p style="margin: 8px 0 0 28px; color: #656d76; font-size: 12px; line-height: 1.5;">
                            <?php _e('Automatically include all current and future WooCommerce tags', 'custom-page-builder'); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Individual Tags List -->
                    <div class="wc-tags-list" style="<?php echo $all_selected ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                        <div style="font-size: 12px; font-weight: 600; color: #57606a; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?php _e('Select Tag', 'custom-page-builder'); ?>
                        </div>
                        <ul class="categorychecklist form-no-clear" style="max-height: 280px; overflow-y: auto; border: 1px solid #d0d7de; padding: 8px; background: #f6f8fa; margin: 0 0 15px 0; list-style: none; border-radius: 6px;">
                            <?php if (!empty($wc_tags)): ?>
                                <?php foreach ($wc_tags as $tag): ?>
                                    <li style="margin: 0 0 4px 0; padding: 0;">
                                        <label class="selectit" style="display: flex; align-items: center; cursor: pointer; padding: 8px 10px; background: #fff; border: 1px solid #d0d7de; border-radius: 6px; transition: all 0.2s; margin: 0;">
                                            <input type="radio" 
                                                name="<?php echo esc_attr( str_replace(array('[',']'), array('_',''), $field_name) ); ?>_single" 
                                                class="wc-tag-radio" 
                                                value="<?php echo esc_attr($tag->term_id); ?>" 
                                                <?php checked(in_array(intval($tag->term_id), $selected_tags, true)); ?>
                                                style="margin: 0 10px 0 0; width: 16px; height: 16px; cursor: pointer; accent-color: #0969da;" />
                                            <span style="flex: 1; font-weight: 500; font-size: 13px; color: #24292f;">
                                                <?php echo esc_html($tag->name); ?>
                                            </span>
                                            <?php if ($tag->count > 0): ?>
                                                <span style="color: #57606a; font-size: 12px; background: #f6f8fa; padding: 2px 8px; border-radius: 12px;">
                                                    <?php echo $tag->count; ?>
                                                </span>
                                            <?php endif; ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li style="padding: 40px 20px; text-align: center; background: #fff; border: 2px dashed #d0d7de; border-radius: 6px;">
                                    <div style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;">🏷️</div>
                                    <strong style="font-size: 14px; display: block; margin-bottom: 10px; color: #24292f;">
                                        <?php _e('No WooCommerce tags found!', 'custom-page-builder'); ?>
                                    </strong>
                                    <p style="color: #57606a; font-size: 13px; margin: 0 0 15px 0;">
                                        <?php _e('Create your first product tag to get started', 'custom-page-builder'); ?>
                                    </p>
                                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=product_tag&post_type=product'); ?>" 
                                       target="_blank" 
                                       class="button button-primary" 
                                       style="text-decoration: none;">
                                        <?php _e('Add Product Tags', 'custom-page-builder'); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div style="margin: 15px 0 0; padding-top: 15px; border-top: 1px solid #d0d7de; text-align: center; display: flex; gap: 10px; justify-content: center;">
                        <button type="button" 
                                class="button wc-tag-clear-btn" 
                                style="text-decoration: none; font-size: 13px; background: #dc3545; color: white; border-color: #dc3545;">
                            <span style="margin-right: 4px;">🗑️</span> <?php _e('Clear Selection', 'custom-page-builder'); ?>
                        </button>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=product_tag&post_type=product'); ?>" 
                           target="_blank" 
                           class="button" 
                           style="text-decoration: none; font-size: 13px;">
                            <span style="margin-right: 4px;">⚙️</span> <?php _e('Manage Tags', 'custom-page-builder'); ?>
                        </a>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
        <?php
        ?>
    </div>
    
    <!-- Manual Categories Section -->
    <div class="manual-categories-section" style="display: <?php echo ($config['category_source'] ?? 'manual') === 'manual' ? 'block' : 'none'; ?>;">
    <div class="config-group">
        <h4><?php _e('Manual Categories', 'custom-page-builder'); ?></h4>
        <div id="categories-list">
            <?php 
            $categories = $config['categories'] ?? [];
            foreach ($categories as $index => $category): 
            ?>
                <div class="category-item" data-index="<?php echo $index; ?>">
                    <div class="category-header">
                        <span class="category-number"><?php echo $index + 1; ?></span>
                        <button type="button" class="remove-category button"><?php _e('🗑️ Remove', 'custom-page-builder'); ?></button>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label><?php _e('Category Title', 'custom-page-builder'); ?></label>
                            <input type="text" name="config[categories][<?php echo $index; ?>][title]" 
                                   value="<?php echo esc_attr($category['title'] ?? ''); ?>" 
                                   placeholder="<?php _e('Category Name', 'custom-page-builder'); ?>" />
                        </div>
                        
                        <div class="config-group">
                            <label><?php _e('Link URL', 'custom-page-builder'); ?></label>
                            <input type="url" name="config[categories][<?php echo $index; ?>][link]" 
                                   value="<?php echo esc_attr($category['link'] ?? ''); ?>" 
                                   placeholder="<?php _e('https://example.com/category', 'custom-page-builder'); ?>" />
                        </div>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label><?php _e('Link Target', 'custom-page-builder'); ?></label>
                            <select name="config[categories][<?php echo $index; ?>][link_target]">
                                <option value="_self" <?php selected($category['link_target'] ?? '_self', '_self'); ?>><?php _e('Same Window', 'custom-page-builder'); ?></option>
                                <option value="_blank" <?php selected($category['link_target'] ?? '_self', '_blank'); ?>><?php _e('New Window', 'custom-page-builder'); ?></option>
                            </select>
                        </div>
                        
                        <div class="config-group">
                            <label><?php _e('Category Image', 'custom-page-builder'); ?></label>
                            <div class="image-upload-field">
                                <input type="hidden" name="config[categories][<?php echo $index; ?>][image]" 
                                       value="<?php echo esc_attr($category['image'] ?? ''); ?>" 
                                       class="image-url-input" />
                                <?php if (!empty($category['image'])): ?>
                                <div class="image-preview-container">
                                    <div class="image-preview">
                                        <img src="<?php echo esc_url($category['image']); ?>" alt="<?php _e('Category Image Preview', 'custom-page-builder'); ?>" />
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
                        <label><?php _e('Description (Optional)', 'custom-page-builder'); ?></label>
                        <textarea name="config[categories][<?php echo $index; ?>][description]" rows="3" 
                                  class="cpb-rich-textarea"
                                  placeholder="<?php _e('Brief category description...', 'custom-page-builder'); ?>"><?php echo esc_textarea($category['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button type="button" id="add-category" class="button"><?php _e('Add Category', 'custom-page-builder'); ?></button>
    </div>
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label for="category-showcase-bg-color"><?php _e('Background Color', 'custom-page-builder'); ?></label>
            <input type="color" id="category-showcase-bg-color" name="config[background_color]" 
                   value="<?php echo esc_attr($config['background_color'] ?? '#ffffff'); ?>" />
        </div>
        
        <div class="config-group">
            <label for="category-showcase-text-color"><?php _e('Text Color', 'custom-page-builder'); ?></label>
            <input type="color" id="category-showcase-text-color" name="config[text_color]" 
                   value="<?php echo esc_attr($config['text_color'] ?? '#333333'); ?>" />
        </div>
    </div>
    
    <div class="config-group">
        <label>
            <input type="checkbox" name="config[visible]" value="1" 
                   <?php checked($config['visible'] ?? true); ?> />
            <?php _e('Section Visible', 'custom-page-builder'); ?>
        </label>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // ========== CATEGORY SELECTOR LOGIC ==========
    
    // Handle "All Categories" checkbox
    $(document).on('change', '.wc-category-all-checkbox', function() {
        var isChecked = $(this).is(':checked');
        var wrapper = $(this).closest('.wc-category-selector-wrapper');
        var categoriesList = wrapper.find('.wc-categories-list');
        var hiddenInput = wrapper.find('.cpb-selected-wc-categories');
        
        if (isChecked) {
            // Disable individual checkboxes
            categoriesList.css({'opacity': '0.5', 'pointer-events': 'none'});
            
            // Set value to ["all"]
            hiddenInput.val(JSON.stringify(['all']));
        } else {
            // Enable individual checkboxes
            categoriesList.css({'opacity': '1', 'pointer-events': 'auto'});
            
            // Get currently selected individual categories
            var selected = [];
            wrapper.find('.wc-category-radio:checked').each(function() {
                selected.push(parseInt($(this).val(), 10));
            });
            
            hiddenInput.val(JSON.stringify(selected));
        }
    });
    
    // Handle individual category radio buttons (single selection)
    $(document).on('change', '.wc-category-radio', function() {
        var wrapper = $(this).closest('.wc-category-selector-wrapper');
        var hiddenInput = wrapper.find('.cpb-selected-wc-categories');

        // Get selected radio value - store as array with single integer
        var selectedValue = parseInt($(this).val(), 10);
        var selected = [selectedValue];

        // Update hidden input
        hiddenInput.val(JSON.stringify(selected));

        // Uncheck 'All' if any individual is selected
        wrapper.find('.wc-category-all-checkbox').prop('checked', false);
        wrapper.find('.wc-categories-list').css({'opacity': '1', 'pointer-events': 'auto'});
    });
    
    // Handle Clear Selection button for CATEGORIES
    $(document).on('click', '.wc-category-clear-btn', function(e) {
        e.preventDefault();
        console.log('Category Clear Selection clicked');
        
        var wrapper = $(this).closest('.wc-category-selector-wrapper');
        var hiddenInput = wrapper.find('.cpb-selected-wc-categories');
        
        console.log('Before clear:', hiddenInput.val());
        
        // Find all radio buttons
        var radios = wrapper.find('.wc-category-radio');
        console.log('Found radios:', radios.length);
        
        // Clear all selections - use multiple methods for browser compatibility
        radios.each(function() {
            this.checked = false; // Native JS
            $(this).prop('checked', false); // jQuery
            $(this).removeAttr('checked'); // Remove attribute
        });
        
        // Also target by name attribute as fallback
        if (radios.length > 0) {
            var radioName = radios.first().attr('name');
            if (radioName) {
                $('input[name="' + radioName + '"]').each(function() {
                    this.checked = false;
                    $(this).prop('checked', false).removeAttr('checked');
                });
            }
        }
        
        // Clear "All Categories" checkbox
        wrapper.find('.wc-category-all-checkbox').prop('checked', false).removeAttr('checked');
        wrapper.find('.wc-categories-list').css({'opacity': '1', 'pointer-events': 'auto'});
        
        // Clear hidden input
        hiddenInput.val('[]');
        
        console.log('After clear:', hiddenInput.val());
        
        // Force visual update
        wrapper.find('input[type="radio"]').prop('checked', false);
        wrapper.find('input[type="checkbox"]').prop('checked', false);
        
        // Trigger change to update any dependent UI
        hiddenInput.trigger('change');
        
        console.log('Category selection cleared successfully');
    });

    // Initialize on page load for all category selector instances
    $('.wc-category-selector-wrapper').each(function() {
        var wrapper = $(this);
        var hiddenInput = wrapper.find('.cpb-selected-wc-categories');
        var val = hiddenInput.val();

        // If hidden input is empty but radio is checked, sync hidden input
        if ((!val || val === '[]' || val === '') && wrapper.find('.wc-category-radio:checked').length) {
            var selectedValue = parseInt(wrapper.find('.wc-category-radio:checked').val(), 10);
            hiddenInput.val(JSON.stringify([selectedValue]));
        }

        // If 'all' is present, ensure UI reflects disabled list
        var parsed = JSON.parse(hiddenInput.val() || '[]');
        if (parsed.indexOf('all') !== -1) {
            wrapper.find('.wc-category-all-checkbox').prop('checked', true);
            wrapper.find('.wc-categories-list').css({'opacity': '0.5', 'pointer-events': 'none'});
        } else {
            wrapper.find('.wc-categories-list').css({'opacity': '1', 'pointer-events': 'auto'});
        }
    });
    
    // ========== TAG SELECTOR LOGIC ==========
    
    // Handle "All Tags" checkbox
    $(document).on('change', '.wc-tag-all-checkbox', function() {
        var isChecked = $(this).is(':checked');
        var wrapper = $(this).closest('.wc-tag-selector-wrapper');
        var tagsList = wrapper.find('.wc-tags-list');
        var hiddenInput = wrapper.find('.cpb-selected-wc-tags');
        
        if (isChecked) {
            // Disable individual checkboxes
            tagsList.css({'opacity': '0.5', 'pointer-events': 'none'});
            
            // Set value to ["all"]
            hiddenInput.val(JSON.stringify(['all']));
        } else {
            // Enable individual checkboxes
            tagsList.css({'opacity': '1', 'pointer-events': 'auto'});
            
            // Get currently selected individual tags
            var selected = [];
            wrapper.find('.wc-tag-radio:checked').each(function() {
                selected.push(parseInt($(this).val(), 10));
            });
            
            hiddenInput.val(JSON.stringify(selected));
        }
    });
    
    // Handle individual tag radio buttons (single selection)
    $(document).on('change', '.wc-tag-radio', function() {
        var wrapper = $(this).closest('.wc-tag-selector-wrapper');
        var hiddenInput = wrapper.find('.cpb-selected-wc-tags');

        // Get selected radio value - store as array with single integer
        var selectedValue = parseInt($(this).val(), 10);
        var selected = [selectedValue];

        // Update hidden input
        hiddenInput.val(JSON.stringify(selected));

        // Uncheck 'All' if any individual is selected
        wrapper.find('.wc-tag-all-checkbox').prop('checked', false);
        wrapper.find('.wc-tags-list').css({'opacity': '1', 'pointer-events': 'auto'});
    });
    
    // Handle Clear Selection button for TAGS
    $(document).on('click', '.wc-tag-clear-btn', function(e) {
        e.preventDefault();
        console.log('Tag Clear Selection clicked');
        
        var wrapper = $(this).closest('.wc-tag-selector-wrapper');
        var hiddenInput = wrapper.find('.cpb-selected-wc-tags');
        
        console.log('Before clear:', hiddenInput.val());
        
        // Find all radio buttons
        var radios = wrapper.find('.wc-tag-radio');
        console.log('Found radios:', radios.length);
        
        // Clear all selections - use multiple methods for browser compatibility
        radios.each(function() {
            this.checked = false; // Native JS
            $(this).prop('checked', false); // jQuery
            $(this).removeAttr('checked'); // Remove attribute
        });
        
        // Also target by name attribute as fallback
        if (radios.length > 0) {
            var radioName = radios.first().attr('name');
            if (radioName) {
                $('input[name="' + radioName + '"]').each(function() {
                    this.checked = false;
                    $(this).prop('checked', false).removeAttr('checked');
                });
            }
        }
        
        // Clear "All Tags" checkbox
        wrapper.find('.wc-tag-all-checkbox').prop('checked', false).removeAttr('checked');
        wrapper.find('.wc-tags-list').css({'opacity': '1', 'pointer-events': 'auto'});
        
        // Clear hidden input
        hiddenInput.val('[]');
        
        console.log('After clear:', hiddenInput.val());
        
        // Force visual update
        wrapper.find('input[type="radio"]').prop('checked', false);
        wrapper.find('input[type="checkbox"]').prop('checked', false);
        
        // Trigger change to update any dependent UI
        hiddenInput.trigger('change');
        
        console.log('Tag selection cleared successfully');
    });

    // Initialize on page load for all tag selector instances
    $('.wc-tag-selector-wrapper').each(function() {
        var wrapper = $(this);
        var hiddenInput = wrapper.find('.cpb-selected-wc-tags');
        var val = hiddenInput.val();

        // If hidden input is empty but radio is checked, sync hidden input
        if ((!val || val === '[]' || val === '') && wrapper.find('.wc-tag-radio:checked').length) {
            var selectedValue = parseInt(wrapper.find('.wc-tag-radio:checked').val(), 10);
            hiddenInput.val(JSON.stringify([selectedValue]));
        }

        // If 'all' is present, ensure UI reflects disabled list
        var parsed = JSON.parse(hiddenInput.val() || '[]');
        if (parsed.indexOf('all') !== -1) {
            wrapper.find('.wc-tag-all-checkbox').prop('checked', true);
            wrapper.find('.wc-tags-list').css({'opacity': '0.5', 'pointer-events': 'none'});
        } else {
            wrapper.find('.wc-tags-list').css({'opacity': '1', 'pointer-events': 'auto'});
        }
    });
});
</script>