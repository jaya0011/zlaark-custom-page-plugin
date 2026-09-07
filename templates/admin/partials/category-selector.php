<?php
/**
 * Category Selector Partial
 * Reusable category selector component
 *
 * Variables expected:
 * - $field_name: The name attribute for the hidden input
 * - $selected_categories: Array of selected category IDs
 * - $label: Label text (optional, defaults to 'Categories')
 *
 * @package Custom_Page_Builder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Set defaults
$label = isset($label) ? $label : __('Categories', 'custom-page-builder');
$selected_categories = isset($selected_categories) ? $selected_categories : [];

// Ensure selected_categories is an array
if (!is_array($selected_categories)) {
    $selected_categories = !empty($selected_categories) ? json_decode($selected_categories, true) : [];
}
if (!is_array($selected_categories)) {
    $selected_categories = [];
}

// Get all WordPress categories
$all_categories = [];
if (class_exists('\Custom_Page_Builder\Category_Manager')) {
    $all_categories = \Custom_Page_Builder\Category_Manager::get_categories();
}

$selected_json = json_encode($selected_categories);

// Get selected names for display
$selected_names = [];
if (!empty($selected_categories) && !empty($all_categories)) {
    foreach ($all_categories as $cat) {
        if (in_array($cat['id'], $selected_categories)) {
            $selected_names[] = $cat['name'];
        }
    }
}
$display_text = !empty($selected_names) ? implode(', ', $selected_names) : '<em>' . __('No categories selected', 'custom-page-builder') . '</em>';
?>

<div class="cpb-category-selector">
    <div class="cpb-category-selector-header">
        <label><?php echo esc_html($label); ?></label>
        <button type="button" class="button cpb-add-category-btn"><?php _e('+ Add New Category', 'custom-page-builder'); ?></button>
    </div>
    
    <input type="hidden" 
           name="<?php echo esc_attr($field_name); ?>" 
           class="cpb-selected-categories" 
           value="<?php echo esc_attr($selected_json); ?>" />
    
    <div class="cpb-category-list">
        <?php if (!empty($all_categories)): ?>
            <?php foreach ($all_categories as $category): ?>
                <div class="cpb-category-item">
                    <label>
                        <input type="checkbox" 
                               class="cpb-category-checkbox" 
                               value="<?php echo esc_attr($category['id']); ?>" 
                               <?php checked(in_array($category['id'], $selected_categories)); ?> />
                        <span><?php echo esc_html($category['name']); ?></span>
                        <?php if (isset($category['count']) && $category['count'] > 0): ?>
                            <span class="cpb-category-count">(<?php echo $category['count']; ?>)</span>
                        <?php endif; ?>
                    </label>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #666; font-style: italic; padding: 10px;">
                <?php _e('No categories available. Click "Add New Category" to create one, or add categories in WordPress Posts → Categories.', 'custom-page-builder'); ?>
            </p>
        <?php endif; ?>
    </div>
    
    <div class="cpb-selected-categories-display">
        <?php echo $display_text; ?>
    </div>
</div>
