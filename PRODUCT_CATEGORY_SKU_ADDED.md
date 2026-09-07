# Product Category and SKU Fields Added

## Overview
Added Category and SKU fields to the Product Grid section, allowing users to better organize and identify their products.

## New Fields Added

### 1. Category Field
**Purpose:** Categorize products by type (e.g., Necklaces, Rings, Earrings, Bracelets)

**Features:**
- Text input field
- Appears right after Product Title
- Optional field (not required)
- Placeholder: "e.g., Necklaces, Rings, Earrings"
- Helps organize products by category
- Can be used for filtering on frontend

**Use Cases:**
- Jewelry: Necklaces, Rings, Earrings, Bracelets, Pendants
- Clothing: Shirts, Pants, Dresses, Accessories
- Electronics: Phones, Laptops, Tablets, Accessories
- Any product categorization system

### 2. SKU Field
**Purpose:** Add product SKU (Stock Keeping Unit) or product code

**Features:**
- Text input field
- Appears with Badge field
- Optional field (not required)
- Placeholder: "Product SKU/Code"
- Useful for inventory management
- Can be displayed on frontend

**Use Cases:**
- Inventory tracking
- Product identification
- Order management
- Catalog reference

## Field Layout

### Product Form Structure (Updated)
```
Row 1:
- Product Title
- Category

Row 2:
- Price
- Sale Price

Row 3:
- Badge
- SKU

Followed by:
- Description (textarea with rich text)
- Product Link
- Button Text
- Featured checkbox
```

## Files Modified

### Template
**File:** `templates/admin/section-templates/product-grid.php`
- Added Category field after Product Title
- Moved Sale Price to same row as Price
- Added SKU field with Badge

### Model
**File:** `models/class-product-grid-section.php`

**Changes:**
1. **Config Schema** - Added category and sku properties
2. **JavaScript Template** - Updated dynamic product template
3. **API Response** - Added category and sku to processed products
4. **Default Config** - Added default category value

## Data Structure

### Product Object (Updated)
```php
[
    'image' => 'url',
    'title' => 'Product Name',
    'category' => 'Necklaces',        // NEW
    'price' => '$99.99',
    'sale_price' => '$79.99',
    'description' => 'Product description',
    'link' => 'https://example.com/product',
    'button_text' => 'View Product',
    'badge' => 'Sale',
    'sku' => 'PROD-001',              // NEW
    'featured' => false,
    'image_data' => [...]
]
```

## API Response

### Product in API Response
```json
{
    "title": "Gold Necklace",
    "category": "Necklaces",
    "price": "$299.99",
    "sale_price": "$249.99",
    "description": "<p>Beautiful gold necklace...</p>",
    "link": "https://example.com/product/gold-necklace",
    "button_text": "View Product",
    "badge": "Sale",
    "sku": "NK-GOLD-001",
    "featured": false,
    "image": "https://example.com/image.jpg",
    "image_data": {...}
}
```

## Frontend Usage

### Displaying Category
```php
<?php if (!empty($product['category'])): ?>
    <span class="product-category"><?php echo esc_html($product['category']); ?></span>
<?php endif; ?>
```

### Displaying SKU
```php
<?php if (!empty($product['sku'])): ?>
    <span class="product-sku">SKU: <?php echo esc_html($product['sku']); ?></span>
<?php endif; ?>
```

### Filtering by Category
```javascript
// Example: Filter products by category
const filterByCategory = (products, category) => {
    return products.filter(product => 
        product.category.toLowerCase() === category.toLowerCase()
    );
};
```

## Benefits

### For Users
1. **Better Organization** - Categorize products logically
2. **Easy Identification** - SKU for quick product lookup
3. **Inventory Management** - Track products by SKU
4. **Filtering** - Can filter products by category on frontend
5. **Professional** - SKU adds professional touch

### For Developers
1. **Structured Data** - Products have clear categorization
2. **Filtering Support** - Easy to implement category filters
3. **Search Enhancement** - Can search by category or SKU
4. **Inventory Integration** - SKU can link to inventory systems
5. **Analytics** - Track which categories perform best

## Example Use Cases

### Jewelry Store
```
Product: Gold Pendant
Category: Pendants
SKU: PD-GOLD-001
Price: $199.99
```

### Fashion Store
```
Product: Summer Dress
Category: Dresses
SKU: DR-SUM-2024-001
Price: $79.99
```

### Electronics Store
```
Product: Wireless Headphones
Category: Audio
SKU: AUD-WH-BT-001
Price: $149.99
```

## Backward Compatibility

- Existing products without category/SKU will work fine
- Both fields are optional
- Default values are empty strings
- No migration needed for existing data

## Notes

- Category is free-text (not a dropdown) for maximum flexibility
- Users can enter any category name they want
- SKU format is flexible (alphanumeric, dashes, etc.)
- Both fields are sanitized on save
- Category and SKU are included in API responses
- Fields are properly validated and escaped for security
