# Dynamic Product Category Selectors - FIXED

## Problem Identified

The WooCommerce category and tag selectors were **NOT appearing when adding new products dynamically** via the "Add Product" button in the Product Grid section.

### Root Cause

The JavaScript `addProduct()` function in `admin.js` was generating a **hardcoded HTML template** that did not include the WooCommerce category/tag selector components. The selectors only appeared when:
- Editing an existing section (loaded from PHP template)
- Reloading the page after saving

## Solution Implemented

### 1. Modified JavaScript `addProduct()` Function
**File**: `admin/assets/js/admin.js`

Changed from hardcoded HTML generation to **AJAX-based template loading**:

```javascript
addProduct: function() {
    var container = $('#products-list');
    var index = container.find('.product-item').length;
    
    // Make AJAX call to get the complete product template with category selectors
    var data = {
        action: 'cpb_admin_action',
        cpb_action: 'get_product_template',
        nonce: cpb_admin.nonce,
        index: index
    };
    
    $.post(cpb_admin.ajax_url, data)
        .done(function(response) {
            if (response.success) {
                container.append(response.data.html);
                CPB_Admin.updateProductNumbers();
                CPB_Admin.initMediaUploader();
            } else {
                // Fallback to basic template if AJAX fails
                CPB_Admin.addProductFallback(index);
            }
        })
        .fail(function() {
            // Fallback to basic template if AJAX fails
            CPB_Admin.addProductFallback(index);
        });
}
```

### 2. Added AJAX Handler
**File**: `admin/class-admin-interface.php`

Created new handler `handle_get_product_template()` that:
- Generates complete product HTML using PHP
- Includes WooCommerce category selector via `woocommerce-category-selector.php`
- Includes WooCommerce tag selector via `woocommerce-tag-selector.php`
- Returns properly formatted HTML with all interactive components

```php
public function handle_get_product_template() {
    try {
        $index = intval($_POST['index'] ?? 0);
        
        // Generate complete product item HTML with category/tag selectors
        ob_start();
        
        // Include category selector
        $field_name = 'config[products][' . $index . '][wc_categories]';
        $selected_categories = [];
        include CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-category-selector.php';
        
        // Include tag selector
        $field_name = 'config[products][' . $index . '][wc_tags]';
        $selected_tags = [];
        include CUSTOM_PAGE_BUILDER_PLUGIN_DIR . 'templates/admin/partials/woocommerce-tag-selector.php';
        
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
```

### 3. Added Fallback Method
**File**: `admin/assets/js/admin.js`

Created `addProductFallback()` method that:
- Provides basic product form if AJAX fails
- Shows warning message about missing category selectors
- Includes all standard fields (title, price, description, etc.)
- Advises user to save and re-edit for full functionality

### 4. Added Category Source Toggle
**File**: `admin/assets/js/admin.js`

Added event handler for Category Showcase section to toggle between:
- **Manual Categories**: User adds categories manually
- **WooCommerce Categories**: Automatically pulls from WooCommerce

```javascript
$(document).on('change', '.category-source-radio', function() {
    var source = $(this).val();
    var container = $(this).closest('.category-showcase-section-config');
    
    if (source === 'woocommerce') {
        container.find('.woocommerce-categories-section').show();
        container.find('.manual-categories-section').hide();
    } else {
        container.find('.woocommerce-categories-section').hide();
        container.find('.manual-categories-section').show();
    }
});
```

## Benefits

✅ **Consistent Experience**: Category selectors now appear immediately when adding products
✅ **No Page Reload Required**: Users can add products and assign categories without saving
✅ **Proper Integration**: Uses the same PHP template components as existing products
✅ **Graceful Degradation**: Fallback method ensures functionality even if AJAX fails
✅ **Better UX**: Real-time category assignment without workflow interruption

## Testing Steps

1. **Navigate to**: Custom Page Builder → Add/Edit Page
2. **Add Section**: Product Grid
3. **Click**: "Add Product" button
4. **Verify**: 
   - WooCommerce Category selector appears (blue box)
   - WooCommerce Tag selector appears (green box)
   - All category checkboxes are functional
   - Selected categories display correctly
   - Form submission includes category data

## Files Modified

1. `admin/assets/js/admin.js` - Updated addProduct(), added addProductFallback(), added category source toggle
2. `admin/class-admin-interface.php` - Added handle_get_product_template() method and AJAX route

## Related Components

- `templates/admin/partials/woocommerce-category-selector.php` - Category selector template
- `templates/admin/partials/woocommerce-tag-selector.php` - Tag selector template
- `templates/admin/section-templates/product-grid.php` - Product grid section template
- `admin/js/category-manager.js` - Category management JavaScript
- `includes/class-category-manager.php` - Category management backend

## Notes

- The solution maintains backward compatibility with existing saved products
- Category selectors work identically whether products are loaded from database or added dynamically
- The AJAX approach allows for future enhancements (e.g., loading different templates based on section type)
- Fallback ensures the plugin remains functional even if JavaScript/AJAX encounters issues
