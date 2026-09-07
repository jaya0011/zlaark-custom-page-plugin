# WordPress Categories Integration

## Overview
The plugin now integrates with **WordPress's built-in categories** (Posts → Categories). All sections can now have category selectors that pull from WordPress categories.

## What Changed

### 1. Category Manager Updated
- Now pulls categories from WordPress instead of custom storage
- Uses `get_categories()` WordPress function
- Creates categories using `wp_insert_term()`
- Deletes categories using `wp_delete_term()`

### 2. Category Selector Component
- Created reusable partial: `templates/admin/partials/category-selector.php`
- Can be included in any section template
- Shows WordPress categories with post counts
- Allows adding new categories that go directly to WordPress

## How to Add Category Selector to Any Section

### Step 1: Add to Template
In your section template file (e.g., `templates/admin/section-templates/your-section.php`), add this code before the closing `</div>`:

```php
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
```

### Step 2: Add to Schema
In your section model file (e.g., `models/class-your-section.php`), add to `get_config_schema()`:

```php
'categories' => [
    'type' => 'array',
    'required' => false,
    'default' => []
],
```

### Step 3: Add to API Response
In your section model's `to_api_response()` method:

```php
// Handle categories
$categories = $this->config['categories'] ?? [];
if (is_string($categories)) {
    $categories = json_decode($categories, true) ?: [];
}

// Get category details
$category_details = [];
if (!empty($categories)) {
    $category_details = \Custom_Page_Builder\Category_Manager::get_categories_by_ids($categories);
}

$response['config']['categories'] = $categories;
$response['config']['category_details'] = $category_details;
```

### Step 4: Add to Default Config
In `get_default_config()`:

```php
'categories' => [],
```

## Sections Updated

### ✅ Product Grid
- **Template**: `templates/admin/section-templates/product-grid.php`
- **Model**: `models/class-product-grid-section.php`
- **Location**: Each product has its own category selector

### ✅ Hero Banner
- **Template**: `templates/admin/section-templates/hero-banner.php`
- **Model**: `models/class-hero-banner-section.php`
- **Location**: In "Categories" section before visibility checkbox

### 🔄 Pending Sections
The following sections need category selectors added:

1. **Content Block** - `templates/admin/section-templates/content-block.php`
2. **Category Showcase** - `templates/admin/section-templates/category-showcase.php`
3. **Testimonials** - `templates/admin/section-templates/testimonials.php`

## Using WordPress Categories

### View Categories
Go to: **WordPress Admin → Posts → Categories**

### Add New Category
Two ways:
1. **In WordPress**: Posts → Categories → Add New Category
2. **In Plugin**: Click "+ Add New Category" button in any section

### Category Data Structure
```php
[
    'id' => '5',              // WordPress term_id
    'name' => 'Jewelry',      // Category name
    'slug' => 'jewelry',      // URL-friendly slug
    'count' => 12             // Number of posts in category
]
```

## Frontend Display

### Display Selected Categories
```php
<?php if (!empty($section['category_details'])): ?>
    <div class="section-categories">
        <?php foreach ($section['category_details'] as $category): ?>
            <span class="category-badge">
                <?php echo esc_html($category['name']); ?>
            </span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```

### Filter by Category
```javascript
// Filter sections by category ID
const filterByCategory = (sections, categoryId) => {
    return sections.filter(section => 
        section.categories && section.categories.includes(categoryId)
    );
};

// Get all sections in a specific category
const jewelrySections = filterByCategory(allSections, '5');
```

## API Response Example

```json
{
    "type": "hero_banner",
    "config": {
        "title": "Welcome",
        "categories": ["5", "8", "12"],
        "category_details": [
            {
                "id": "5",
                "name": "Jewelry",
                "slug": "jewelry",
                "count": 12
            },
            {
                "id": "8",
                "name": "Necklaces",
                "slug": "necklaces",
                "count": 8
            }
        ]
    }
}
```

## Benefits

### For Users
1. **Familiar Interface** - Uses WordPress categories they already know
2. **Centralized Management** - Manage categories in one place
3. **Reusable** - Same categories across posts and custom pages
4. **Post Counts** - See how many posts use each category
5. **Quick Add** - Add categories without leaving the page

### For Developers
1. **WordPress Integration** - Uses native WordPress functions
2. **No Custom Tables** - Leverages existing WordPress infrastructure
3. **Taxonomy Support** - Can be extended to tags, custom taxonomies
4. **SEO Friendly** - Categories have proper slugs and structure
5. **Query Support** - Can query by category using WP_Query

## Advanced Usage

### Query Sections by Category
```php
// Get all custom pages with sections in "Jewelry" category
$jewelry_pages = get_pages_by_category('jewelry');

function get_pages_by_category($category_slug) {
    global $wpdb;
    $table = $wpdb->prefix . 'custom_pages';
    
    // Get category ID
    $category = get_term_by('slug', $category_slug, 'category');
    if (!$category) return [];
    
    // Query pages
    $pages = $wpdb->get_results("SELECT * FROM $table WHERE status = 'published'");
    
    $filtered = [];
    foreach ($pages as $page) {
        $sections = json_decode($page->sections, true);
        foreach ($sections as $section) {
            if (isset($section['config']['categories']) && 
                in_array($category->term_id, $section['config']['categories'])) {
                $filtered[] = $page;
                break;
            }
        }
    }
    
    return $filtered;
}
```

### Custom Taxonomy Support
To use custom taxonomies instead of categories:

```php
// In class-category-manager.php, change 'category' to your taxonomy
$wp_categories = get_terms([
    'taxonomy' => 'your_custom_taxonomy',
    'hide_empty' => false
]);
```

## Migration Notes

### From Custom Categories
If you were using the old custom category system:
1. Old category IDs were strings like `cat_abc123`
2. New category IDs are WordPress term IDs (integers as strings)
3. Existing selections will need to be remapped

### Backward Compatibility
- Empty categories array is safe
- Frontend checks for `category_details` existence
- Old `category` string field is replaced with `categories` array

## Troubleshooting

### Categories Not Showing
1. Check if WordPress has categories: Posts → Categories
2. Add at least one category in WordPress
3. Refresh the page builder page

### "Add New Category" Not Working
1. Check browser console for JavaScript errors
2. Verify AJAX nonce is valid
3. Check user has `manage_options` capability

### Categories Not Saving
1. Check field name matches: `config[categories]`
2. Verify hidden input has `cpb-selected-categories` class
3. Check JavaScript is loading: `category-manager.js`

## Files Modified

1. `includes/class-category-manager.php` - Now uses WordPress functions
2. `templates/admin/partials/category-selector.php` - Reusable component
3. `templates/admin/section-templates/product-grid.php` - Added selector
4. `templates/admin/section-templates/hero-banner.php` - Added selector
5. `models/class-product-grid-section.php` - Updated schema and API
6. `models/class-hero-banner-section.php` - Updated schema and API

## Next Steps

To complete the integration:
1. Add category selector to remaining sections
2. Update their models with schema and API changes
3. Test category filtering on frontend
4. Add category-based navigation/filtering UI
