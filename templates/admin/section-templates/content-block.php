<?php
/**
 * Content Block section admin template
 *
 * @package Custom_Page_Builder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// This template is included by the ContentBlockSection class
// Variables available: $section (ContentBlockSection instance)
$config = $section->get_config();
$padding = $config['padding'] ?? ['top' => '40px', 'bottom' => '40px', 'left' => '20px', 'right' => '20px'];
$cta = $config['call_to_action'] ?? ['enabled' => false, 'text' => '', 'url' => '', 'target' => '_self', 'style' => 'button'];
?>

<div class="content-block-section-config">
    <div class="section-header">
        <h3><?php _e('Content Block Section Configuration', 'custom-page-builder'); ?></h3>
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label for="content-block-title"><?php _e('Section Title (Optional)', 'custom-page-builder'); ?></label>
            <input type="text" id="content-block-title" name="config[title]" 
                   value="<?php echo esc_attr($config['title'] ?? ''); ?>" 
                   placeholder="<?php _e('Optional section title', 'custom-page-builder'); ?>" />
        </div>
        
        <div class="config-group">
            <label for="content-block-subtitle"><?php _e('Section Subtitle (Optional)', 'custom-page-builder'); ?></label>
            <input type="text" id="content-block-subtitle" name="config[subtitle]" 
                   value="<?php echo esc_attr($config['subtitle'] ?? ''); ?>" 
                   placeholder="<?php _e('Optional subtitle', 'custom-page-builder'); ?>" />
        </div>
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label for="content-block-layout"><?php _e('Layout', 'custom-page-builder'); ?></label>
            <select id="content-block-layout" name="config[layout]">
                <option value="single_column" <?php selected($config['layout'] ?? 'single_column', 'single_column'); ?>><?php _e('Single Column', 'custom-page-builder'); ?></option>
                <option value="two_column" <?php selected($config['layout'] ?? 'single_column', 'two_column'); ?>><?php _e('Two Column', 'custom-page-builder'); ?></option>
                <option value="image_left" <?php selected($config['layout'] ?? 'single_column', 'image_left'); ?>><?php _e('Image Left, Text Right', 'custom-page-builder'); ?></option>
                <option value="image_right" <?php selected($config['layout'] ?? 'single_column', 'image_right'); ?>><?php _e('Image Right, Text Left', 'custom-page-builder'); ?></option>
            </select>
        </div>
        
        <div class="config-group">
            <label for="content-block-alignment"><?php _e('Content Alignment', 'custom-page-builder'); ?></label>
            <select id="content-block-alignment" name="config[content_alignment]">
                <option value="left" <?php selected($config['content_alignment'] ?? 'left', 'left'); ?>><?php _e('Left', 'custom-page-builder'); ?></option>
                <option value="center" <?php selected($config['content_alignment'] ?? 'left', 'center'); ?>><?php _e('Center', 'custom-page-builder'); ?></option>
                <option value="right" <?php selected($config['content_alignment'] ?? 'left', 'right'); ?>><?php _e('Right', 'custom-page-builder'); ?></option>
                <option value="justify" <?php selected($config['content_alignment'] ?? 'left', 'justify'); ?>><?php _e('Justify', 'custom-page-builder'); ?></option>
            </select>
        </div>
    </div>
    
    <div class="config-group">
        <label for="content-block-content"><?php _e('Main Content', 'custom-page-builder'); ?></label>
        <?php
        $content = $config['content'] ?? '<p>Enter your content here. You can use rich text formatting, add links, and include images.</p>';
        wp_editor($content, 'content-block-content', [
            'textarea_name' => 'config[content]',
            'media_buttons' => true,
            'textarea_rows' => 10,
            'teeny' => false,
            'dfw' => false,
            'tinymce' => [
                'resize' => false,
                'wp_autoresize_on' => true,
                'add_unload_trigger' => false
            ]
        ]);
        ?>
    </div>
    
    <div class="config-group secondary-content-field" style="display: none;">
        <label for="content-block-secondary-content"><?php _e('Secondary Content (Right Column)', 'custom-page-builder'); ?></label>
        <?php
        $secondary_content = $config['secondary_content'] ?? '';
        wp_editor($secondary_content, 'content-block-secondary-content', [
            'textarea_name' => 'config[secondary_content]',
            'media_buttons' => true,
            'textarea_rows' => 8,
            'teeny' => false,
            'dfw' => false,
            'tinymce' => [
                'resize' => false,
                'wp_autoresize_on' => true,
                'add_unload_trigger' => false
            ]
        ]);
        ?>
    </div>
    
    <div class="image-fields">
        <div class="config-group">
            <label><?php _e('Content Image', 'custom-page-builder'); ?></label>
            <input type="hidden" id="content-block-image" name="config[image]" 
                   value="<?php echo esc_attr($config['image'] ?? ''); ?>" 
                   class="image-url-input" />
            
            <div class="image-upload-field">
                <?php if (!empty($config['image'])): ?>
                <div class="image-preview-container">
                    <div class="image-preview">
                        <img src="<?php echo esc_url($config['image']); ?>" alt="<?php _e('Content Image Preview', 'custom-page-builder'); ?>" />
                    </div>
                    <div class="image-button-group">
                        <button type="button" id="edit-content-image" class="button edit-image-btn" data-target="content-block-image"><?php _e('✏️ Edit Image', 'custom-page-builder'); ?></button>
                        <button type="button" id="remove-content-image" class="button remove-image-btn" data-target="content-block-image"><?php _e('🗑️ Delete Image', 'custom-page-builder'); ?></button>
                    </div>
                </div>
                <?php else: ?>
                <div class="image-button-group">
                    <button type="button" id="upload-content-image" class="button upload-image-btn" data-target="content-block-image"><?php _e('📷 Upload Image', 'custom-page-builder'); ?></button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="config-row">
            <div class="config-group">
                <label for="content-block-image-alt"><?php _e('Image Alt Text', 'custom-page-builder'); ?></label>
                <input type="text" id="content-block-image-alt" name="config[image_alt]" 
                       value="<?php echo esc_attr($config['image_alt'] ?? ''); ?>" 
                       placeholder="<?php _e('Describe the image for accessibility', 'custom-page-builder'); ?>" />
            </div>
            
            <div class="config-group">
                <label for="content-block-image-position"><?php _e('Image Position', 'custom-page-builder'); ?></label>
                <select id="content-block-image-position" name="config[image_position]">
                    <option value="top" <?php selected($config['image_position'] ?? 'top', 'top'); ?>><?php _e('Above Content', 'custom-page-builder'); ?></option>
                    <option value="bottom" <?php selected($config['image_position'] ?? 'top', 'bottom'); ?>><?php _e('Below Content', 'custom-page-builder'); ?></option>
                    <option value="left" <?php selected($config['image_position'] ?? 'top', 'left'); ?>><?php _e('Left of Content', 'custom-page-builder'); ?></option>
                    <option value="right" <?php selected($config['image_position'] ?? 'top', 'right'); ?>><?php _e('Right of Content', 'custom-page-builder'); ?></option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="config-group">
        <label>
            <input type="checkbox" id="cta-enabled" name="config[call_to_action][enabled]" value="1" 
                   <?php checked($cta['enabled'] ?? false); ?> />
            <?php _e('Add Call to Action Button', 'custom-page-builder'); ?>
        </label>
        
        <div class="cta-fields" style="<?php echo empty($cta['enabled']) ? 'display:none;' : ''; ?>">
            <div class="config-row">
                <div class="config-group">
                    <label for="cta-text"><?php _e('Button Text', 'custom-page-builder'); ?></label>
                    <input type="text" id="cta-text" name="config[call_to_action][text]" 
                           value="<?php echo esc_attr($cta['text'] ?? ''); ?>" 
                           placeholder="<?php _e('Learn More', 'custom-page-builder'); ?>" />
                </div>
                
                <div class="config-group">
                    <label for="cta-url"><?php _e('Button URL', 'custom-page-builder'); ?></label>
                    <input type="url" id="cta-url" name="config[call_to_action][url]" 
                           value="<?php echo esc_attr($cta['url'] ?? ''); ?>" 
                           placeholder="<?php _e('https://example.com', 'custom-page-builder'); ?>" />
                </div>
            </div>
            
            <div class="config-row">
                <div class="config-group">
                    <label for="cta-target"><?php _e('Link Target', 'custom-page-builder'); ?></label>
                    <select id="cta-target" name="config[call_to_action][target]">
                        <option value="_self" <?php selected($cta['target'] ?? '_self', '_self'); ?>><?php _e('Same Window', 'custom-page-builder'); ?></option>
                        <option value="_blank" <?php selected($cta['target'] ?? '_self', '_blank'); ?>><?php _e('New Window', 'custom-page-builder'); ?></option>
                    </select>
                </div>
                
                <div class="config-group">
                    <label for="cta-style"><?php _e('Button Style', 'custom-page-builder'); ?></label>
                    <select id="cta-style" name="config[call_to_action][style]">
                        <option value="button" <?php selected($cta['style'] ?? 'button', 'button'); ?>><?php _e('Button', 'custom-page-builder'); ?></option>
                        <option value="link" <?php selected($cta['style'] ?? 'button', 'link'); ?>><?php _e('Text Link', 'custom-page-builder'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label for="content-block-max-width"><?php _e('Maximum Width', 'custom-page-builder'); ?></label>
            <input type="text" id="content-block-max-width" name="config[max_width]" 
                   value="<?php echo esc_attr($config['max_width'] ?? '1200px'); ?>" 
                   placeholder="<?php _e('1200px', 'custom-page-builder'); ?>" />
        </div>
        
        <div class="config-group">
            <label>
                <input type="checkbox" name="config[enable_rich_text]" value="1" 
                       <?php checked($config['enable_rich_text'] ?? true); ?> />
                <?php _e('Enable Rich Text Editor', 'custom-page-builder'); ?>
            </label>
        </div>
    </div>
    
    <div class="config-group">
        <label><?php _e('Section Padding', 'custom-page-builder'); ?></label>
        <div class="padding-controls">
            <div>
                <label for="padding-top"><?php _e('Top', 'custom-page-builder'); ?></label>
                <input type="text" id="padding-top" name="config[padding][top]" 
                       value="<?php echo esc_attr($padding['top']); ?>" 
                       placeholder="40px" />
            </div>
            <div>
                <label for="padding-bottom"><?php _e('Bottom', 'custom-page-builder'); ?></label>
                <input type="text" id="padding-bottom" name="config[padding][bottom]" 
                       value="<?php echo esc_attr($padding['bottom']); ?>" 
                       placeholder="40px" />
            </div>
            <div>
                <label for="padding-left"><?php _e('Left', 'custom-page-builder'); ?></label>
                <input type="text" id="padding-left" name="config[padding][left]" 
                       value="<?php echo esc_attr($padding['left']); ?>" 
                       placeholder="20px" />
            </div>
            <div>
                <label for="padding-right"><?php _e('Right', 'custom-page-builder'); ?></label>
                <input type="text" id="padding-right" name="config[padding][right]" 
                       value="<?php echo esc_attr($padding['right']); ?>" 
                       placeholder="20px" />
            </div>
        </div>
    </div>
    
    <div class="config-row">
        <div class="config-group">
            <label for="content-block-bg-color"><?php _e('Background Color', 'custom-page-builder'); ?></label>
            <input type="color" id="content-block-bg-color" name="config[background_color]" 
                   value="<?php echo esc_attr($config['background_color'] ?? '#ffffff'); ?>" />
        </div>
        
        <div class="config-group">
            <label for="content-block-text-color"><?php _e('Text Color', 'custom-page-builder'); ?></label>
            <input type="color" id="content-block-text-color" name="config[text_color]" 
                   value="<?php echo esc_attr($config['text_color'] ?? '#333333'); ?>" />
        </div>
    </div>
    
    <!-- Categories -->
    <div class="config-group">
        <h4><?php _e('Categories', 'custom-page-builder'); ?></h4>
        <?php
        $field_name = 'config[categories]';
        $selected_categories = $config['categories'] ?? [];
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