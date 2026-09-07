# Category Management System Added

## Overview
Implemented a complete category management system similar to WooCommerce, allowing users to select from predefined categories with checkboxes and add new categories on-the-fly.

## Features

### 1. Category Manager
- **Centralized Storage** - Categories stored in WordPress options table
- **Default Categories** - Pre-loaded with jewelry categories (Necklaces, Rings, Earrings, Bracelets, Pendants)
- **CRUD Operations** - Create, Read, Update, Delete categories
- **Unique IDs** - Each category has a unique ID for reliable tracking

### 2. Category Selection UI
- **Checkbox Interface** - Select multiple categories like WooCommerce
- **Visual Feedback** - Shows selected categories below the list
- **Scrollable List** - Max height with scrollbar for many categories
- **Add New Button** - Quick add new categories without leaving the page

### 3. AJAX Integration
- **Real-time Updates** - Add/delete categories without page reload
- **Instant Sync** - All category selectors update automatically
- **Error Handling** - User-friendly error messages

## Files Created

### Backend Classes
1. **`includes/class-category-manager.php`**
   - Core category management logic
   - Get, add, update, delete categories
   - Default categories setup

2. **`admin/class-category-ajax.php`**
   - AJAX handlers for category operations
   - Security checks (nonce, capabilities)
   - JSON responses

3. **`includes/category-selector-template.php`**
   - PHP function to render category selector UI
   - Reusable across all sections

### Frontend Assets
4. **`admin/js/category-manager.js`**
   - JavaScript for category UI interactions
   - AJAX calls to backend
   - Dynamic UI updates

5. **`admin/css/category-manager.css`**
   - Styling for category selector
   - Checkbox list styling
   - Responsive design

## How It Works

### Category Storage
```php
// Categories stored as WordPress option
[
    [
        'id' => 'cat_abc123',
        'name' => 'Necklaces',
        'slug' => 'necklaces'
    ],
    [
        'id' => 'cat_def456',
        'name' => 'Rings',
        'slug' => 'rings'
    ]
]
```

### Product with Categories
```php
[
    'title' => 'Gold Necklace',
    'categories' => ['cat_abc123', 'cat_xyz789'], // Array of category IDs
    'category_details' => [
        ['id' => 'cat_abc123', 'name' => 'Necklaces', 'slug' => 'necklaces'],
        ['id' => 'cat_xyz789', 'name' => 'Gold', 'slug' => 'gold']
    ]
]
```

### API Response
```json
{
    "title": "Gold Necklace",
    "categories": ["cat_abc123", "cat_xyz789"],
    "category_details": [
        {
            "id": "cat_abc123",
            "name": "Necklaces",
            "slug": "necklaces"
        },
        {
            "id": "cat_xyz789",
            "name": "Gold",
            "slug": "gold"
        }
    ],
    "price": "$299.99"
}
```

## Usage

### Rendering Category Selector
```php
<?php
// In template file
$selected_categories = isset($product['categories']) ? $product['categories'] : [];
cpb_render_category_selector(
    "config[products][{$index}][categories]",
    $selected_categories,
    __('Product Categories', 'custom-page-builder')
);
?>
```

### Getting Category Details
```php
<?php
// Get categories by IDs
$category_ids = ['cat_abc123', 'cat_def456'];
$categories = \Custom_Page_Builder\Category_Manager::get_categories_by_ids($category_ids);

// Output: Array of category objects with id, name, slug
?>
```

### Adding New Category
```javascript
// Via JavaScript
CategoryManager.addCategory('New Category Name', $container);

// Via PHP
$category = \Custom_Page_Builder\Category_Manager::add_category('New Category');
```

## User Interface

### Category Selector Appearance
```
┌─────────────────────────────────────────┐
│ Product Categories    [+ Add New Category]│
├─────────────────────────────────────────┤
│ ☑ Necklaces                             │
│ ☐ Rings                                 │
│ ☑ Earrings                              │
│ ☐ Bracelets                             │
│ ☐ Pendants                              │
├─────────────────────────────────────────┤
│ Selected: Necklaces, Earrings           │
└─────────────────────────────────────────┘
```

## Integration Points

### Product Grid Section
- **Template**: `templates/admin/section-templates/product-grid.php`
- **Model**: `models/class-product-grid-section.php`
- **Schema**: Updated to use `categories` array instead of `category` string
- **API Response**: Includes both category IDs and full category details

### Future Sections
The category system is designed to be easily added to:
- Content Block section
- Custom sections
- Any section that needs categorization

## Default Categories

Pre-loaded categories for jewelry stores:
1. **Necklaces** - `necklaces`
2. **Rings** - `rings`
3. **Earrings** - `earrings`
4. **Bracelets** - `bracelets`
5. **Pendants** - `pendants`

Users can:
- Add new categories
- Delete existing categories
- Select multiple categories per product

## Frontend Display

### Display Category Names
```php
<?php if (!empty($product['category_details'])): ?>
    <div class="product-categories">
        <?php foreach ($product['category_details'] as $category): ?>
            <span class="category-badge"><?php echo esc_html($category['name']); ?></span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```

### Filter by Category
```javascript
// Filter products by category ID
const filterByCategory = (products, categoryId) => {
    return products.filter(product => 
        product.categories.includes(categoryId)
    );
};

// Filter by category name
const filterByCategoryName = (products, categoryName) => {
    return products.filter(product => 
        product.category_details.some(cat => 
            cat.name.toLowerCase() === categoryName.toLowerCase()
        )
    );
};
```

## Security

- **Nonce Verification** - All AJAX requests verified
- **Capability Checks** - Only admins can manage categories
- **Input Sanitization** - All inputs sanitized
- **Output Escaping** - All outputs escaped

## Benefits

### For Users
1. **Familiar Interface** - Like WooCommerce product categories
2. **Quick Selection** - Checkbox interface is fast
3. **Multiple Categories** - Select as many as needed
4. **Easy Management** - Add categories without leaving the page
5. **Visual Feedback** - See selected categories immediately

### For Developers
1. **Reusable** - Category selector can be used anywhere
2. **Extensible** - Easy to add to new sections
3. **Structured Data** - Categories have IDs, names, and slugs
4. **API Ready** - Full category details in API responses
5. **Filterable** - Easy to implement category filtering

## Migration Notes

### Existing Products
- Old `category` string field is replaced with `categories` array
- Existing products will have empty categories array
- No data loss - users can re-select categories

### Backward Compatibility
- API still includes category data
- Frontend can check for both old and new format
- Graceful degradation if categories empty

## Customization

### Change Default Categories
Edit `get_default_categories()` in `class-category-manager.php`:
```php
private static function get_default_categories(): array {
    return [
        ['id' => 'custom1', 'name' => 'Custom Category 1', 'slug' => 'custom-category-1'],
        ['id' => 'custom2', 'name' => 'Custom Category 2', 'slug' => 'custom-category-2'],
    ];
}
```

### Styling
Customize appearance in `admin/css/category-manager.css`

### Add to Other Sections
```php
// In any section template
cpb_render_category_selector(
    'config[field_name]',
    $selected_categories,
    'Label Text'
);
```

## Technical Details

### Data Flow
1. User checks/unchecks categories
2. JavaScript updates hidden input with JSON array of IDs
3. Form submission saves array to database
4. On load, PHP renders checkboxes based on saved IDs
5. API response includes both IDs and full category details

### Performance
- Categories cached in WordPress options
- Single database query to load all categories
- AJAX updates don't reload page
- Minimal overhead

## Notes

- Categories are global across all products/sections
- Deleting a category doesn't delete products using it
- Category IDs are permanent (don't change)
- Category names can be updated
- Maximum recommended categories: 50-100 for UI performance
