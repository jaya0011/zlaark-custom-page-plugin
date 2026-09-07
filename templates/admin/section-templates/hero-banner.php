<?php
/**
 * Hero Banner section admin template
 *
 * @package Custom_Page_Builder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// This template is included by the HeroBannerSection class
// Variables available: $section (HeroBannerSection instance)
$config = $section->get_config();
?>

<div class="hero-banner-section-config">
    <div class="section-header">
        <h3><?php _e('Hero Banner Section Configuration', 'custom-page-builder'); ?></h3>
    </div>
    
    <!-- Content Section -->
    <div class="config-section">
        <h4><?php _e('Content', 'custom-page-builder'); ?></h4>
        
        <div class="config-group">
            <label for="hero-title"><?php _e('Title', 'custom-page-builder'); ?></label>
            <input type="text" id="hero-title" name="config[title]" 
                   value="<?php echo esc_attr($config['title'] ?? 'Welcome to Our Website'); ?>" 
                   placeholder="<?php _e('Welcome to Our Website', 'custom-page-builder'); ?>" />
        </div>
        
        <div class="config-group">
            <label for="hero-subtitle"><?php _e('Subtitle', 'custom-page-builder'); ?></label>
            <textarea id="hero-subtitle" name="config[subtitle]" rows="2" 
                      placeholder="<?php _e('Discover amazing products and services', 'custom-page-builder'); ?>"><?php echo esc_textarea($config['subtitle'] ?? ''); ?></textarea>
        </div>
        
        <div class="config-row">
            <div class="config-group">
                <label for="hero-text-alignment"><?php _e('Text Alignment', 'custom-page-builder'); ?></label>
                <select id="hero-text-alignment" name="config[text_alignment]">
                    <option value="left" <?php selected($config['text_alignment'] ?? 'center', 'left'); ?>><?php _e('Left', 'custom-page-builder'); ?></option>
                    <option value="center" <?php selected($config['text_alignment'] ?? 'center', 'center'); ?>><?php _e('Center', 'custom-page-builder'); ?></option>
                    <option value="right" <?php selected($config['text_alignment'] ?? 'center', 'right'); ?>><?php _e('Right', 'custom-page-builder'); ?></option>
                </select>
            </div>
            
            <div class="config-group">
                <label for="hero-text-color"><?php _e('Text Color', 'custom-page-builder'); ?></label>
                <input type="color" id="hero-text-color" name="config[text_color]" 
                       value="<?php echo esc_attr($config['text_color'] ?? '#ffffff'); ?>" />
            </div>
        </div>
    </div>
    
    <!-- Background Section -->
    <div class="config-section">
        <h4><?php _e('Background', 'custom-page-builder'); ?></h4>
        
        <div class="config-group">
            <label><?php _e('Background Image', 'custom-page-builder'); ?></label>
            <input type="hidden" id="hero-background-image" name="config[background_image]" 
                   value="<?php echo esc_attr($config['background_image'] ?? ''); ?>" 
                   class="image-url-input" />
            
            <div class="image-upload-field">
                <?php if (!empty($config['background_image'])): ?>
                <div class="image-preview-container">
                    <div class="image-preview">
                        <img src="<?php echo esc_url($config['background_image']); ?>" alt="<?php _e('Background Image Preview', 'custom-page-builder'); ?>" />
                    </div>
                    <div class="image-button-group">
                        <button type="button" id="edit-hero-background" class="button edit-image-btn" data-target="hero-background-image"><?php _e('✏️ Edit Image', 'custom-page-builder'); ?></button>
                        <button type="button" id="remove-hero-background" class="button remove-image-btn" data-target="hero-background-image"><?php _e('🗑️ Delete Image', 'custom-page-builder'); ?></button>
                    </div>
                </div>
                <?php else: ?>
                <div class="image-button-group">
                    <button type="button" id="upload-hero-background" class="button upload-image-btn" data-target="hero-background-image"><?php _e('📷 Upload Background Image', 'custom-page-builder'); ?></button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="config-row">
            <div class="config-group">
                <label for="hero-background-color"><?php _e('Background Color', 'custom-page-builder'); ?></label>
                <input type="color" id="hero-background-color" name="config[background_color]" 
                       value="<?php echo esc_attr($config['background_color'] ?? '#0073aa'); ?>" />
            </div>
            
            <div class="config-group">
                <label for="hero-overlay-opacity"><?php _e('Overlay Opacity', 'custom-page-builder'); ?></label>
                <input type="range" id="hero-overlay-opacity" name="config[overlay_opacity]" 
                       min="0" max="100" step="5"
                       value="<?php echo esc_attr($config['overlay_opacity'] ?? '50'); ?>" />
                <span class="range-value"><?php echo esc_html($config['overlay_opacity'] ?? '50'); ?>%</span>
            </div>
        </div>
        
        <div class="config-group">
            <label for="hero-background-size"><?php _e('Background Size', 'custom-page-builder'); ?></label>
            <select id="hero-background-size" name="config[background_size]">
                <option value="cover" <?php selected($config['background_size'] ?? 'cover', 'cover'); ?>><?php _e('Cover', 'custom-page-builder'); ?></option>
                <option value="contain" <?php selected($config['background_size'] ?? 'cover', 'contain'); ?>><?php _e('Contain', 'custom-page-builder'); ?></option>
                <option value="auto" <?php selected($config['background_size'] ?? 'cover', 'auto'); ?>><?php _e('Auto', 'custom-page-builder'); ?></option>
            </select>
        </div>
    </div>
    
    <!-- Call to Action Section -->
    <div class="config-section">
        <h4><?php _e('Call to Action', 'custom-page-builder'); ?></h4>
        
        <div class="config-group">
            <label>
                <input type="checkbox" id="hero-cta-enabled" name="config[cta_enabled]" value="1" 
                       <?php checked($config['cta_enabled'] ?? false); ?> />
                <?php _e('Enable Call to Action Button', 'custom-page-builder'); ?>
            </label>
        </div>
        
        <div class="cta-fields" style="<?php echo empty($config['cta_enabled']) ? 'display:none;' : ''; ?>">
            <div class="config-row">
                <div class="config-group">
                    <label for="hero-cta-text"><?php _e('Button Text', 'custom-page-builder'); ?></label>
                    <input type="text" id="hero-cta-text" name="config[cta_text]" 
                           value="<?php echo esc_attr($config['cta_text'] ?? 'Get Started'); ?>" 
                           placeholder="<?php _e('Get Started', 'custom-page-builder'); ?>" />
                </div>
                
                <div class="config-group">
                    <label for="hero-cta-url"><?php _e('Button URL', 'custom-page-builder'); ?></label>
                    <input type="url" id="hero-cta-url" name="config[cta_url]" 
                           value="<?php echo esc_attr($config['cta_url'] ?? ''); ?>" 
                           placeholder="<?php _e('https://example.com', 'custom-page-builder'); ?>" />
                </div>
            </div>
            
            <div class="config-row">
                <div class="config-group">
                    <label for="hero-cta-style"><?php _e('Button Style', 'custom-page-builder'); ?></label>
                    <select id="hero-cta-style" name="config[cta_style]">
                        <option value="primary" <?php selected($config['cta_style'] ?? 'primary', 'primary'); ?>><?php _e('Primary', 'custom-page-builder'); ?></option>
                        <option value="secondary" <?php selected($config['cta_style'] ?? 'primary', 'secondary'); ?>><?php _e('Secondary', 'custom-page-builder'); ?></option>
                        <option value="outline" <?php selected($config['cta_style'] ?? 'primary', 'outline'); ?>><?php _e('Outline', 'custom-page-builder'); ?></option>
                    </select>
                </div>
                
                <div class="config-group">
                    <label for="hero-cta-target"><?php _e('Link Target', 'custom-page-builder'); ?></label>
                    <select id="hero-cta-target" name="config[cta_target]">
                        <option value="_self" <?php selected($config['cta_target'] ?? '_self', '_self'); ?>><?php _e('Same Window', 'custom-page-builder'); ?></option>
                        <option value="_blank" <?php selected($config['cta_target'] ?? '_self', '_blank'); ?>><?php _e('New Window', 'custom-page-builder'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Layout Section -->
    <div class="config-section">
        <h4><?php _e('Layout', 'custom-page-builder'); ?></h4>
        
        <div class="config-row">
            <div class="config-group">
                <label for="hero-height"><?php _e('Banner Height', 'custom-page-builder'); ?></label>
                <select id="hero-height" name="config[height]">
                    <option value="small" <?php selected($config['height'] ?? 'medium', 'small'); ?>><?php _e('Small (400px)', 'custom-page-builder'); ?></option>
                    <option value="medium" <?php selected($config['height'] ?? 'medium', 'medium'); ?>><?php _e('Medium (600px)', 'custom-page-builder'); ?></option>
                    <option value="large" <?php selected($config['height'] ?? 'medium', 'large'); ?>><?php _e('Large (800px)', 'custom-page-builder'); ?></option>
                    <option value="fullscreen" <?php selected($config['height'] ?? 'medium', 'fullscreen'); ?>><?php _e('Full Screen', 'custom-page-builder'); ?></option>
                </select>
            </div>
            
            <div class="config-group">
                <label for="hero-content-width"><?php _e('Content Width', 'custom-page-builder'); ?></label>
                <input type="text" id="hero-content-width" name="config[content_width]" 
                       value="<?php echo esc_attr($config['content_width'] ?? '800px'); ?>" 
                       placeholder="<?php _e('800px', 'custom-page-builder'); ?>" />
            </div>
        </div>
        
        <div class="config-group">
            <label>
                <input type="checkbox" name="config[parallax]" value="1" 
                       <?php checked($config['parallax'] ?? false); ?> />
                <?php _e('Enable Parallax Effect', 'custom-page-builder'); ?>
            </label>
        </div>
    </div>
    
    <!-- Categories -->
    <div class="config-section">
        <h4><?php _e('Categories', 'custom-page-builder'); ?></h4>
        <div class="config-group">
            <?php
            $field_name = 'config[categories]';
            $selected_categories = $config['categories'] ?? [];
            $label = __('Select Categories', 'custom-page-builder');
            include CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/category-selector.php';
            ?>
        </div>
    </div>
    
    <!-- WooCommerce Categories -->
    <div class="config-section" style="background: #ffeb3b; padding: 20px; border: 5px solid #ff0000; margin: 20px 0;">
        <h4 style="color: #ff0000; font-size: 24px; font-weight: bold;">
            🛍️ <?php _e('WooCommerce Product Categories', 'custom-page-builder'); ?> 🛍️
        </h4>
        <div style="background: #fff; padding: 15px; border: 3px dashed #ff0000; margin: 10px 0;">
            <p style="color: #ff0000; font-weight: bold; font-size: 16px;">
                ⚠️ DEBUG: WooCommerce Category Selector Section - If you see this, the section is rendering!
            </p>
        </div>
        <div class="config-group">
            <?php
            $field_name = 'config[wc_categories]';
            $selected_wc_categories = $config['wc_categories'] ?? [];
            $label = __('Select WooCommerce Product Categories', 'custom-page-builder');
            $show_all_option = true;
            
            // Debug output
            echo '<div style="background: #e3f2fd; padding: 10px; margin: 10px 0; border: 2px solid #2196f3;">';
            echo '<strong>DEBUG INFO:</strong><br>';
            echo 'Plugin Dir: ' . CUSTOM_PAGE_BUILDER_PLUGIN_DIR . '<br>';
            echo 'Template Path: ' . CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-category-selector.php<br>';
            echo 'File Exists: ' . (file_exists(CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-category-selector.php') ? 'YES' : 'NO') . '<br>';
            echo 'WooCommerce Active: ' . (class_exists('WooCommerce') ? 'YES' : 'NO') . '<br>';
            echo '</div>';
            
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
            if (is_string($selected_wc_categories)) {
                $selected_wc_categories = json_decode($selected_wc_categories, true) ?: [];
            }
            
            // Normalize selected categories to integers for consistent comparison
            $selected_wc_categories = array_map(function($val) {
                return $val === 'all' ? 'all' : intval($val);
            }, $selected_wc_categories);
            
            // Check if "all" is selected
            $all_selected = in_array('all', $selected_wc_categories, true);
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
                               value="<?php echo esc_attr(json_encode($selected_wc_categories)); ?>" />
                        
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
                                                    <?php checked(in_array(intval($cat->term_id), $selected_wc_categories, true)); ?>
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
                                <?php elseif (!empty($selected_wc_categories)): ?>
                                    <?php 
                                    foreach ($selected_wc_categories as $cat_id):
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
    </div>
    
    <!-- WooCommerce Tags -->
    <div class="config-section" style="background: #d1fae5; padding: 20px; border: 5px solid #10b981; margin: 20px 0;">
        <h4 style="color: #10b981; font-size: 24px; font-weight: bold;">
            🏷️ <?php _e('WooCommerce Product Tags', 'custom-page-builder'); ?> 🏷️
        </h4>
        <div style="background: #fff; padding: 15px; border: 3px dashed #10b981; margin: 10px 0;">
            <p style="color: #10b981; font-weight: bold; font-size: 16px;">
                ⚠️ DEBUG: WooCommerce Tag Selector Section - If you see this, the section is rendering!
            </p>
        </div>
        <div class="config-group">
            <?php
            $field_name = 'config[wc_tags]';
            $selected_wc_tags = $config['wc_tags'] ?? [];
            $label = __('Select WooCommerce Product Tags', 'custom-page-builder');
            $show_all_option = true;
            
            // Debug output
            echo '<div style="background: #e8f5e8; padding: 10px; margin: 10px 0; border: 2px solid #4caf50;">';
            echo '<strong>DEBUG INFO:</strong><br>';
            echo 'Plugin Dir: ' . CUSTOM_PAGE_BUILDER_PLUGIN_DIR . '<br>';
            echo 'Template Path: ' . CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-tag-selector.php<br>';
            echo 'File Exists: ' . (file_exists(CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-tag-selector.php') ? 'YES' : 'NO') . '<br>';
            echo 'WooCommerce Active: ' . (class_exists('WooCommerce') ? 'YES' : 'NO') . '<br>';
            echo '</div>';
            
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
            if (is_string($selected_wc_tags)) {
                $selected_wc_tags = json_decode($selected_wc_tags, true) ?: [];
            }
            
            // Normalize selected tags to integers for consistent comparison
            $selected_wc_tags = array_map(function($val) {
                return $val === 'all' ? 'all' : intval($val);
            }, $selected_wc_tags);
            
            // Check if "all" is selected
            $all_selected = in_array('all', $selected_wc_tags, true);
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
                    
                    <div id="taxonomy-product_tag" class="tagdiv">
                        <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #23282d; display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 18px;">🏷️</span> <?php echo esc_html($label); ?>
                        </h4>
                        
                        <!-- Hidden input to store selected tags as JSON -->
                        <input type="hidden" 
                               name="<?php echo esc_attr($field_name); ?>" 
                               class="cpb-selected-wc-tags" 
                               value="<?php echo esc_attr(json_encode($selected_wc_tags)); ?>" />
                        
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
                            <ul class="tagchecklist form-no-clear" style="max-height: 280px; overflow-y: auto; border: 1px solid #d0d7de; padding: 8px; background: #f6f8fa; margin: 0 0 15px 0; list-style: none; border-radius: 6px;">
                                <?php if (!empty($wc_tags)): ?>
                                    <?php foreach ($wc_tags as $tag): ?>
                                        <li style="margin: 0 0 4px 0; padding: 0;">
                                            <label class="selectit" style="display: flex; align-items: center; cursor: pointer; padding: 8px 10px; background: #fff; border: 1px solid #d0d7de; border-radius: 6px; transition: all 0.2s; margin: 0;">
                                                <input type="radio" 
                                                    name="<?php echo esc_attr( str_replace(array('[',']'), array('_',''), $field_name) ); ?>_single" 
                                                    class="wc-tag-radio" 
                                                    value="<?php echo esc_attr($tag->term_id); ?>" 
                                                    <?php checked(in_array(intval($tag->term_id), $selected_wc_tags, true)); ?>
                                                    style="margin: 0 10px 0 0; width: 16px; height: 16px; cursor: pointer; accent-color: #0969da;" />
                                                <span class="tag-name" style="flex: 1; font-weight: 500; font-size: 13px; color: #24292f;">
                                                    <?php echo esc_html($tag->name); ?>
                                                </span>
                                                <?php if ($tag->count > 0): ?>
                                                    <span class="tag-count" style="color: #57606a; font-size: 12px; background: #f6f8fa; padding: 2px 8px; border-radius: 12px;">
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
                                <?php elseif (!empty($selected_wc_tags)): ?>
                                    <?php 
                                    foreach ($selected_wc_tags as $tag_id):
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
    </div>
    
    <!-- Visibility -->
    <div class="config-group">
        <label>
            <input type="checkbox" name="config[visible]" value="1" 
                   <?php checked($config['visible'] ?? true); ?> />
            <?php _e('Section Visible', 'custom-page-builder'); ?>
        </label>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle CTA fields
    $('#hero-cta-enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('.cta-fields').slideDown();
        } else {
            $('.cta-fields').slideUp();
        }
    });
    
    // Update overlay opacity display
    $('#hero-overlay-opacity').on('input', function() {
        $(this).siblings('.range-value').text($(this).val() + '%');
    });
});
</script>
