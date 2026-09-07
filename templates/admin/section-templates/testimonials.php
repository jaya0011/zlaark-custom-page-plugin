<?php
/**
 * Testimonials section admin template
 *
 * @package Custom_Page_Builder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// This template is included by the TestimonialsSection class
// Variables available: $section (TestimonialsSection instance)
$config = $section->get_config();
?>

<div class="testimonials-section-config">
    <div class="section-header">
        <h3><?php _e('Testimonials Section Configuration', 'custom-page-builder'); ?></h3>
    </div>
    
    <div class="config-group">
        <label for="testimonials-title"><?php _e('Section Title', 'custom-page-builder'); ?></label>
        <input type="text" id="testimonials-title" name="config[title]" 
               value="<?php echo esc_attr($config['title'] ?? 'What Our Customers Say'); ?>" 
               placeholder="<?php _e('What Our Customers Say', 'custom-page-builder'); ?>" />
    </div>
    
    <div class="config-group">
        <label for="testimonials-subtitle"><?php _e('Section Subtitle', 'custom-page-builder'); ?></label>
        <input type="text" id="testimonials-subtitle" name="config[subtitle]" 
               value="<?php echo esc_attr($config['subtitle'] ?? ''); ?>" 
               placeholder="<?php _e('Optional subtitle', 'custom-page-builder'); ?>" />
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label for="testimonials-layout"><?php _e('Layout', 'custom-page-builder'); ?></label>
            <select id="testimonials-layout" name="config[layout]">
                <option value="grid" <?php selected($config['layout'] ?? 'grid', 'grid'); ?>><?php _e('Grid', 'custom-page-builder'); ?></option>
                <option value="slider" <?php selected($config['layout'] ?? 'grid', 'slider'); ?>><?php _e('Slider', 'custom-page-builder'); ?></option>
            </select>
        </div>
        
        <div class="config-group">
            <label for="testimonials-columns"><?php _e('Columns', 'custom-page-builder'); ?></label>
            <select id="testimonials-columns" name="config[columns]">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php selected($config['columns'] ?? 3, $i); ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label>
                <input type="checkbox" name="config[show_ratings]" value="1" 
                       <?php checked($config['show_ratings'] ?? true); ?> />
                <?php _e('Show Star Ratings', 'custom-page-builder'); ?>
            </label>
        </div>
        
        <div class="config-group">
            <label>
                <input type="checkbox" name="config[show_author_images]" value="1" 
                       <?php checked($config['show_author_images'] ?? true); ?> />
                <?php _e('Show Author Images', 'custom-page-builder'); ?>
            </label>
        </div>
    </div>
    
    <div class="config-group">
        <h4><?php _e('Testimonials', 'custom-page-builder'); ?></h4>
        <div id="testimonials-list">
            <?php 
            $testimonials = $config['testimonials'] ?? [];
            foreach ($testimonials as $index => $testimonial): 
            ?>
                <div class="testimonial-item" data-index="<?php echo $index; ?>">
                    <div class="testimonial-header">
                        <span class="testimonial-number"><?php echo $index + 1; ?></span>
                        <button type="button" class="remove-testimonial button"><?php _e('🗑️ Remove', 'custom-page-builder'); ?></button>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label><?php _e('Rating (1-5 stars)', 'custom-page-builder'); ?></label>
                            <select name="config[testimonials][<?php echo $index; ?>][rating]">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php selected($testimonial['rating'] ?? 5, $i); ?>><?php echo $i; ?> <?php echo $i > 1 ? __('Stars', 'custom-page-builder') : __('Star', 'custom-page-builder'); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="config-group">
                        <label><?php _e('Testimonial Text', 'custom-page-builder'); ?></label>
                        <textarea name="config[testimonials][<?php echo $index; ?>][text]" rows="4" 
                                  class="cpb-rich-textarea"
                                  placeholder="<?php _e('Enter testimonial text...', 'custom-page-builder'); ?>"><?php echo esc_textarea($testimonial['text'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label><?php _e('Author Name', 'custom-page-builder'); ?></label>
                            <input type="text" name="config[testimonials][<?php echo $index; ?>][author_name]" 
                                   value="<?php echo esc_attr($testimonial['author_name'] ?? ''); ?>" 
                                   placeholder="<?php _e('John Doe', 'custom-page-builder'); ?>" />
                        </div>
                        
                        <div class="config-group">
                            <label><?php _e('Author Title', 'custom-page-builder'); ?></label>
                            <input type="text" name="config[testimonials][<?php echo $index; ?>][author_title]" 
                                   value="<?php echo esc_attr($testimonial['author_title'] ?? ''); ?>" 
                                   placeholder="<?php _e('CEO, Company Name', 'custom-page-builder'); ?>" />
                        </div>
                    </div>
                    
                    <div class="config-row">
                        <div class="config-group">
                            <label><?php _e('Company', 'custom-page-builder'); ?></label>
                            <input type="text" name="config[testimonials][<?php echo $index; ?>][company]" 
                                   value="<?php echo esc_attr($testimonial['company'] ?? ''); ?>" 
                                   placeholder="<?php _e('Company Name', 'custom-page-builder'); ?>" />
                        </div>
                        
                        <div class="config-group">
                            <label><?php _e('Author Image', 'custom-page-builder'); ?></label>
                            <div class="image-upload-field">
                                <input type="hidden" name="config[testimonials][<?php echo $index; ?>][author_image]" 
                                       value="<?php echo esc_attr($testimonial['author_image'] ?? ''); ?>" 
                                       class="image-url-input" />
                                <?php if (!empty($testimonial['author_image'])): ?>
                                <div class="image-preview-container">
                                    <div class="image-preview">
                                        <img src="<?php echo esc_url($testimonial['author_image']); ?>" alt="<?php _e('Author Image Preview', 'custom-page-builder'); ?>" />
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
                </div>
            <?php endforeach; ?>
        </div>
        
        <button type="button" id="add-testimonial" class="button"><?php _e('Add Testimonial', 'custom-page-builder'); ?></button>
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label for="testimonials-bg-color"><?php _e('Background Color', 'custom-page-builder'); ?></label>
            <input type="color" id="testimonials-bg-color" name="config[background_color]" 
                   value="<?php echo esc_attr($config['background_color'] ?? '#ffffff'); ?>" />
        </div>
        
        <div class="config-group">
            <label for="testimonials-text-color"><?php _e('Text Color', 'custom-page-builder'); ?></label>
            <input type="color" id="testimonials-text-color" name="config[text_color]" 
                   value="<?php echo esc_attr($config['text_color'] ?? '#333333'); ?>" />
        </div>
    </div>
    
    <!-- Categories -->
    <div class="config-group">
        <h4><?php _e('Categories', 'custom-page-builder'); ?></h4>
        <?php
        $field_name = 'config[section_categories]';
        $selected_categories = $config['section_categories'] ?? [];
        $label = __('Select Categories', 'custom-page-builder');
        include CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/category-selector.php';
        ?>
    </div>
    
    <!-- WooCommerce Categories -->
    <div class="config-group" style="background: #ffeb3b; padding: 20px; border: 5px solid #ff0000; margin: 20px 0;">
        <h4 style="color: #ff0000; font-size: 20px; font-weight: bold;">
            🛍️ <?php _e('WooCommerce Product Categories', 'custom-page-builder'); ?> 🛍️
        </h4>
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
                    
                    <!-- Selected Categories Display -->
                    <div class="selected-wc-categories-display" style="margin-top: 0; padding: 12px 15px; background: #f6f8fa; border: 1px solid #d0d7de; border-radius: 6px;">
                        <strong style="display: block; margin-bottom: 8px; color: #24292f; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?php _e('Currently Selected', 'custom-page-builder'); ?>
                        </strong>
                        <div class="selected-wc-categories-tags">
                            <?php if ($all_selected): ?>
                                <span class="category-tag" style="display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);">
                                    <span>✨</span> <?php _e('All Categories', 'custom-page-builder'); ?>
                                </span>
                            <?php elseif (!empty($selected_categories)): ?>
                                <?php 
                                foreach ($selected_categories as $cat_id):
                                    $cat = get_term($cat_id, 'product_cat');
                                    if ($cat && !is_wp_error($cat)):
                                ?>
                                    <span class="category-tag" style="display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #0969da 0%, #0550ae 100%); color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; box-shadow: 0 2px 4px rgba(9, 105, 218, 0.3);">
                                        <span>🛍️</span> <?php echo esc_html($cat->name); ?>
                                    </span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            <?php else: ?>
                                <span style="color: #656d76; font-style: italic; font-size: 13px;">
                                    <?php _e('No categories selected', 'custom-page-builder'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div style="margin: 15px 0 0; padding-top: 15px; border-top: 1px solid #d0d7de; text-align: center;">
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
    
    <!-- WooCommerce Tags -->
    <div class="config-group" style="background: #d1fae5; padding: 20px; border: 5px solid #10b981; margin: 20px 0;">
        <h4 style="color: #10b981; font-size: 20px; font-weight: bold;">
            🏷️ <?php _e('WooCommerce Product Tags', 'custom-page-builder'); ?> 🏷️
        </h4>
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
                    
                    <!-- Selected Tags Display -->
                    <div class="selected-wc-tags-display" style="margin-top: 0; padding: 12px 15px; background: #f6f8fa; border: 1px solid #d0d7de; border-radius: 6px;">
                        <strong style="display: block; margin-bottom: 8px; color: #24292f; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?php _e('Currently Selected', 'custom-page-builder'); ?>
                        </strong>
                        <div class="selected-wc-tags-tags">
                            <?php if ($all_selected): ?>
                                <span class="tag-tag" style="display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);">
                                    <span>✨</span> <?php _e('All Tags', 'custom-page-builder'); ?>
                                </span>
                            <?php elseif (!empty($selected_tags)): ?>
                                <?php 
                                foreach ($selected_tags as $tag_id):
                                    $tag = get_term($tag_id, 'product_tag');
                                    if ($tag && !is_wp_error($tag)):
                                ?>
                                    <span class="tag-tag" style="display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #0969da 0%, #0550ae 100%); color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; box-shadow: 0 2px 4px rgba(9, 105, 218, 0.3);">
                                        <span>🏷️</span> <?php echo esc_html($tag->name); ?>
                                    </span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            <?php else: ?>
                                <span style="color: #656d76; font-style: italic; font-size: 13px;">
                                    <?php _e('No tags selected', 'custom-page-builder'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div style="margin: 15px 0 0; padding-top: 15px; border-top: 1px solid #d0d7de; text-align: center;">
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
    
    <div class="config-group">
        <label>
            <input type="checkbox" name="config[visible]" value="1" 
                   <?php checked($config['visible'] ?? true); ?> />
            <?php _e('Section Visible', 'custom-page-builder'); ?>
        </label>
    </div>
</div>