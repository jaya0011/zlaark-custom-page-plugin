<?php
/**
 * Base section configuration template
 *
 * @package Custom_Page_Builder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// This template provides common configuration fields for all sections
$config = $section->get_config();
?>

<div class="cpb-section-config-base">
    <!-- Common Section Settings -->
    <div class="cpb-config-group">
        <h4><?php _e('General Settings', 'custom-page-builder'); ?></h4>
        
        <div class="cpb-form-field">
            <label for="section_title"><?php _e('Section Title', 'custom-page-builder'); ?></label>
            <input type="text" id="section_title" name="title" 
                   value="<?php echo esc_attr($config['title'] ?? ''); ?>" 
                   placeholder="<?php _e('Enter section title', 'custom-page-builder'); ?>">
            <p class="description"><?php _e('This title will be displayed above the section content.', 'custom-page-builder'); ?></p>
        </div>
        
        <div class="cpb-form-field">
            <label for="section_subtitle"><?php _e('Section Subtitle', 'custom-page-builder'); ?></label>
            <input type="text" id="section_subtitle" name="subtitle" 
                   value="<?php echo esc_attr($config['subtitle'] ?? ''); ?>" 
                   placeholder="<?php _e('Optional subtitle', 'custom-page-builder'); ?>">
        </div>
        
        <div class="cpb-form-field">
            <label for="section_description"><?php _e('Description', 'custom-page-builder'); ?></label>
            <textarea id="section_description" name="description" rows="3"
                      placeholder="<?php _e('Optional section description', 'custom-page-builder'); ?>"><?php echo esc_textarea($config['description'] ?? ''); ?></textarea>
        </div>
    </div>
    
    <!-- Visibility and Display Settings -->
    <div class="cpb-config-group">
        <h4><?php _e('Display Settings', 'custom-page-builder'); ?></h4>
        
        <div class="cpb-form-row">
            <div class="cpb-form-field">
                <label>
                    <input type="checkbox" name="visible" value="1" 
                           <?php checked($config['visible'] ?? true); ?>>
                    <?php _e('Section Visible', 'custom-page-builder'); ?>
                </label>
            </div>
            
            <div class="cpb-form-field">
                <label>
                    <input type="checkbox" name="show_title" value="1" 
                           <?php checked($config['show_title'] ?? true); ?>>
                    <?php _e('Show Section Title', 'custom-page-builder'); ?>
                </label>
            </div>
        </div>
        
        <div class="cpb-form-row">
            <div class="cpb-form-field">
                <label for="section_css_class"><?php _e('CSS Class', 'custom-page-builder'); ?></label>
                <input type="text" id="section_css_class" name="css_class" 
                       value="<?php echo esc_attr($config['css_class'] ?? ''); ?>" 
                       placeholder="<?php _e('custom-class-name', 'custom-page-builder'); ?>">
                <p class="description"><?php _e('Additional CSS class for custom styling.', 'custom-page-builder'); ?></p>
            </div>
            
            <div class="cpb-form-field">
                <label for="section_css_id"><?php _e('CSS ID', 'custom-page-builder'); ?></label>
                <input type="text" id="section_css_id" name="css_id" 
                       value="<?php echo esc_attr($config['css_id'] ?? ''); ?>" 
                       placeholder="<?php _e('section-id', 'custom-page-builder'); ?>">
                <p class="description"><?php _e('Unique CSS ID for this section.', 'custom-page-builder'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Spacing Settings -->
    <div class="cpb-config-group">
        <h4><?php _e('Spacing', 'custom-page-builder'); ?></h4>
        
        <div class="cpb-form-row">
            <div class="cpb-form-field">
                <label for="section_margin_top"><?php _e('Top Margin', 'custom-page-builder'); ?></label>
                <select id="section_margin_top" name="margin_top">
                    <option value=""><?php _e('Default', 'custom-page-builder'); ?></option>
                    <option value="none" <?php selected($config['margin_top'] ?? '', 'none'); ?>><?php _e('None', 'custom-page-builder'); ?></option>
                    <option value="small" <?php selected($config['margin_top'] ?? '', 'small'); ?>><?php _e('Small', 'custom-page-builder'); ?></option>
                    <option value="medium" <?php selected($config['margin_top'] ?? '', 'medium'); ?>><?php _e('Medium', 'custom-page-builder'); ?></option>
                    <option value="large" <?php selected($config['margin_top'] ?? '', 'large'); ?>><?php _e('Large', 'custom-page-builder'); ?></option>
                </select>
            </div>
            
            <div class="cpb-form-field">
                <label for="section_margin_bottom"><?php _e('Bottom Margin', 'custom-page-builder'); ?></label>
                <select id="section_margin_bottom" name="margin_bottom">
                    <option value=""><?php _e('Default', 'custom-page-builder'); ?></option>
                    <option value="none" <?php selected($config['margin_bottom'] ?? '', 'none'); ?>><?php _e('None', 'custom-page-builder'); ?></option>
                    <option value="small" <?php selected($config['margin_bottom'] ?? '', 'small'); ?>><?php _e('Small', 'custom-page-builder'); ?></option>
                    <option value="medium" <?php selected($config['margin_bottom'] ?? '', 'medium'); ?>><?php _e('Medium', 'custom-page-builder'); ?></option>
                    <option value="large" <?php selected($config['margin_bottom'] ?? '', 'large'); ?>><?php _e('Large', 'custom-page-builder'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="cpb-form-row">
            <div class="cpb-form-field">
                <label for="section_padding_top"><?php _e('Top Padding', 'custom-page-builder'); ?></label>
                <select id="section_padding_top" name="padding_top">
                    <option value=""><?php _e('Default', 'custom-page-builder'); ?></option>
                    <option value="none" <?php selected($config['padding_top'] ?? '', 'none'); ?>><?php _e('None', 'custom-page-builder'); ?></option>
                    <option value="small" <?php selected($config['padding_top'] ?? '', 'small'); ?>><?php _e('Small', 'custom-page-builder'); ?></option>
                    <option value="medium" <?php selected($config['padding_top'] ?? '', 'medium'); ?>><?php _e('Medium', 'custom-page-builder'); ?></option>
                    <option value="large" <?php selected($config['padding_top'] ?? '', 'large'); ?>><?php _e('Large', 'custom-page-builder'); ?></option>
                </select>
            </div>
            
            <div class="cpb-form-field">
                <label for="section_padding_bottom"><?php _e('Bottom Padding', 'custom-page-builder'); ?></label>
                <select id="section_padding_bottom" name="padding_bottom">
                    <option value=""><?php _e('Default', 'custom-page-builder'); ?></option>
                    <option value="none" <?php selected($config['padding_bottom'] ?? '', 'none'); ?>><?php _e('None', 'custom-page-builder'); ?></option>
                    <option value="small" <?php selected($config['padding_bottom'] ?? '', 'small'); ?>><?php _e('Small', 'custom-page-builder'); ?></option>
                    <option value="medium" <?php selected($config['padding_bottom'] ?? '', 'medium'); ?>><?php _e('Medium', 'custom-page-builder'); ?></option>
                    <option value="large" <?php selected($config['padding_bottom'] ?? '', 'large'); ?>><?php _e('Large', 'custom-page-builder'); ?></option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Background Settings -->
    <div class="cpb-config-group">
        <h4><?php _e('Background', 'custom-page-builder'); ?></h4>
        
        <div class="cpb-form-row">
            <div class="cpb-form-field">
                <label for="section_bg_color"><?php _e('Background Color', 'custom-page-builder'); ?></label>
                <input type="color" id="section_bg_color" name="background_color" 
                       value="<?php echo esc_attr($config['background_color'] ?? '#ffffff'); ?>" 
                       class="cpb-color-picker">
            </div>
            
            <div class="cpb-form-field">
                <label for="section_text_color"><?php _e('Text Color', 'custom-page-builder'); ?></label>
                <input type="color" id="section_text_color" name="text_color" 
                       value="<?php echo esc_attr($config['text_color'] ?? '#333333'); ?>" 
                       class="cpb-color-picker">
            </div>
        </div>
        
        <div class="cpb-form-field">
            <label for="section_bg_image"><?php _e('Background Image', 'custom-page-builder'); ?></label>
            <div class="cpb-image-upload-field">
                <input type="hidden" id="section_bg_image" name="background_image" 
                       value="<?php echo esc_attr($config['background_image'] ?? ''); ?>">
                <button type="button" class="cpb-btn cpb-btn-secondary cpb-upload-image">
                    <?php _e('Select Image', 'custom-page-builder'); ?>
                </button>
                <button type="button" class="cpb-btn cpb-btn-secondary cpb-remove-image" 
                        style="<?php echo empty($config['background_image']) ? 'display:none;' : ''; ?>">
                    <?php _e('Remove', 'custom-page-builder'); ?>
                </button>
                <div class="cpb-image-preview" style="<?php echo empty($config['background_image']) ? 'display:none;' : ''; ?>">
                    <?php if (!empty($config['background_image'])): ?>
                        <img src="<?php echo esc_url(wp_get_attachment_image_url($config['background_image'], 'medium')); ?>" 
                             alt="<?php _e('Background Preview', 'custom-page-builder'); ?>">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="cpb-form-row" data-conditional-show="background_image">
            <div class="cpb-form-field">
                <label for="section_bg_size"><?php _e('Background Size', 'custom-page-builder'); ?></label>
                <select id="section_bg_size" name="background_size">
                    <option value="cover" <?php selected($config['background_size'] ?? 'cover', 'cover'); ?>><?php _e('Cover', 'custom-page-builder'); ?></option>
                    <option value="contain" <?php selected($config['background_size'] ?? 'cover', 'contain'); ?>><?php _e('Contain', 'custom-page-builder'); ?></option>
                    <option value="auto" <?php selected($config['background_size'] ?? 'cover', 'auto'); ?>><?php _e('Auto', 'custom-page-builder'); ?></option>
                </select>
            </div>
            
            <div class="cpb-form-field">
                <label for="section_bg_position"><?php _e('Background Position', 'custom-page-builder'); ?></label>
                <select id="section_bg_position" name="background_position">
                    <option value="center center" <?php selected($config['background_position'] ?? 'center center', 'center center'); ?>><?php _e('Center', 'custom-page-builder'); ?></option>
                    <option value="top center" <?php selected($config['background_position'] ?? 'center center', 'top center'); ?>><?php _e('Top', 'custom-page-builder'); ?></option>
                    <option value="bottom center" <?php selected($config['background_position'] ?? 'center center', 'bottom center'); ?>><?php _e('Bottom', 'custom-page-builder'); ?></option>
                    <option value="left center" <?php selected($config['background_position'] ?? 'center center', 'left center'); ?>><?php _e('Left', 'custom-page-builder'); ?></option>
                    <option value="right center" <?php selected($config['background_position'] ?? 'center center', 'right center'); ?>><?php _e('Right', 'custom-page-builder'); ?></option>
                </select>
            </div>
        </div>
    </div>
</div>