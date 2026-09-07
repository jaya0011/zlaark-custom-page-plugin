<?php
/**
 * WooCommerce Category Selector Template
 * 
 * Reusable component for selecting WooCommerce product categories
 * 
 * Variables expected:
 * - $field_name: Name attribute for the form field (e.g., 'config[wc_categories]')
 * - $selected_categories: Array of selected category IDs
 * - $label: Label text for the selector
 * - $show_all_option: Whether to show "All Categories" option (default: true)
 * 
 * @package Custom_Page_Builder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
$wc_active = \Custom_Page_Builder\Category_Manager::is_woocommerce_active();

// Get WooCommerce categories
$wc_categories = $wc_active ? \Custom_Page_Builder\Category_Manager::get_woocommerce_categories() : [];

// Default values
$field_name = $field_name ?? 'config[wc_categories]';
$selected_categories = $selected_categories ?? [];
$label = $label ?? __('WooCommerce Product Categories', 'custom-page-builder');
$show_all_option = $show_all_option ?? true;

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
                                        value="<?php echo esc_attr($cat['id']); ?>" 
                                        <?php checked(in_array(intval($cat['id']), $selected_categories, true)); ?>
                                        style="margin: 0 10px 0 0; width: 16px; height: 16px; cursor: pointer; accent-color: #0969da;" />
                                    <span style="flex: 1; font-weight: 500; font-size: 13px; color: #24292f;">
                                        <?php echo esc_html($cat['name']); ?>
                                    </span>
                                    <?php if ($cat['count'] > 0): ?>
                                        <span style="color: #57606a; font-size: 12px; background: #f6f8fa; padding: 2px 8px; border-radius: 12px;">
                                            <?php echo $cat['count']; ?>
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
                    <span style="margin-right: 4px;">🗑️</span> <?php _e('Clear Selection', 'custom-page-builder'); ?>
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

<script type="text/javascript">
jQuery(document).ready(function($) {
    var isClearing = false; // Flag to prevent interference during clear
    
    // Handle "All Categories" checkbox
    $(document).on('change', '.wc-category-all-checkbox', function() {
        if (isClearing) return;
        var isChecked = $(this).is(':checked');
        var wrapper = $(this).closest('.wc-category-selector-wrapper');
        var categoriesList = wrapper.find('.wc-categories-list');
        var hiddenInput = wrapper.find('.cpb-selected-wc-categories');
        
        if (isChecked) {
            // Disable individual radio buttons
            categoriesList.css({'opacity': '0.5', 'pointer-events': 'none'});
            
            // Set value to ["all"]
            hiddenInput.val(JSON.stringify(['all']));
        } else {
            // Enable individual radio buttons
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
        if (isClearing) return;
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
    
    // Handle Clear Selection button
    $(document).on('click', '.wc-category-clear-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        isClearing = true; // Set flag to prevent change handlers
        
        var wrapper = $(this).closest('.wc-category-selector-wrapper');
        var hiddenInput = wrapper.find('.cpb-selected-wc-categories');
        
        // Clear hidden input FIRST
        hiddenInput.val('[]');
        
        // Find all radio buttons
        var radios = wrapper.find('.wc-category-radio');
        
        // Get the radio group name
        var radioName = radios.length > 0 ? radios.first().attr('name') : null;
        
        // Clear all radios - multiple methods
        radios.each(function() {
            this.checked = false;
            $(this).prop('checked', false);
            $(this).removeAttr('checked');
        });
        
        // Double-check by name
        if (radioName) {
            $('input[type="radio"][name="' + radioName + '"]').each(function() {
                this.checked = false;
                $(this).prop('checked', false);
                $(this).removeAttr('checked');
            });
        }
        
        // Clear "All Categories" checkbox
        wrapper.find('.wc-category-all-checkbox').each(function() {
            this.checked = false;
            $(this).prop('checked', false);
            $(this).removeAttr('checked');
        });
        
        // Re-enable list
        wrapper.find('.wc-categories-list').css({'opacity': '1', 'pointer-events': 'auto'});
        
        // Force complete DOM refresh
        wrapper.hide().show();
        
        // Reset flag after a short delay
        setTimeout(function() {
            isClearing = false;
        }, 100);
        
        return false;
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
});
</script>