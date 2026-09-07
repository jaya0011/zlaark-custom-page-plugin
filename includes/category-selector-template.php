<?php
/**
 * Category Selector Template
 * Renders the category selection UI
 *
 * @package Custom_Page_Builder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render category selector
 *
 * @param string $name Field name
 * @param array $selected_ids Selected category IDs
 * @param string $label Label text
 */
function cpb_render_category_selector($name, $selected_ids = [], $label = 'Categories') {
    // Check if class exists
    if (!class_exists('\Custom_Page_Builder\Category_Manager')) {
        echo '<div class="notice notice-error"><p>Category Manager class not found. Please check plugin installation.</p></div>';
        return;
    }
    
    $categories = \Custom_Page_Builder\Category_Manager::get_categories();
    
    // Ensure selected_ids is an array
    if (!is_array($selected_ids)) {
        if (is_string($selected_ids) && !empty($selected_ids)) {
            $selected_ids = json_decode($selected_ids, true) ?: [];
        } else {
            $selected_ids = [];
        }
    }
    
    $selected_json = json_encode($selected_ids);
    
    // Get selected category names for display
    $selected_names = [];
    if (!empty($selected_ids)) {
        $selected_categories = \Custom_Page_Builder\Category_Manager::get_categories_by_ids($selected_ids);
        $selected_names = array_map(function($cat) {
            return $cat['name'];
        }, $selected_categories);
    }
    $display_text = !empty($selected_names) ? implode(', ', $selected_names) : '<em>No categories selected</em>';
    ?>
    <div class="cpb-category-selector">
        <div class="cpb-category-selector-header">
            <label><?php echo esc_html($label); ?></label>
            <button type="button" class="button cpb-add-category-btn">+ Add New Category</button>
        </div>
        
        <input type="hidden" 
               name="<?php echo esc_attr($name); ?>" 
               class="cpb-selected-categories" 
               value="<?php echo esc_attr($selected_json); ?>" />
        
        <div class="cpb-category-list">
            <?php foreach ($categories as $category): ?>
                <div class="cpb-category-item">
                    <label>
                        <input type="checkbox" 
                               class="cpb-category-checkbox" 
                               value="<?php echo esc_attr($category['id']); ?>" 
                               <?php checked(in_array($category['id'], $selected_ids)); ?> />
                        <span><?php echo esc_html($category['name']); ?></span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="cpb-selected-categories-display">
            <?php echo $display_text; ?>
        </div>
    </div>
    <?php
}
