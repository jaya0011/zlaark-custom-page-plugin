# R2 CDN URL Migration Guide

## ✅ ISSUE IDENTIFIED

Your custom page builder API is returning WordPress attachment IDs instead of R2 signed URLs:

```json
{
  "image": 61,  // ❌ WordPress attachment ID
  "image": 89   // ❌ WordPress attachment ID
}
```

But it should return:

```json
{
  "image_url": "https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/image.jpg?X-Amz-Algorithm=...",  // ✅ R2 signed URL
  "image": "https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/image.jpg?X-Amz-Algorithm=..."       // ✅ Backward compatibility
}
```

---

## 🔧 SOLUTION IMPLEMENTED

I've added a function `cpb_get_r2_url_for_attachment()` that checks for R2 URLs in:

1. **Attachment meta**: `_r2_cdn_url`
2. **Product meta**: `_product_image_url` (for WooCommerce product images)

The function automatically converts attachment IDs to R2 URLs when found.

---

## 📋 THREE WAYS TO FIX THIS

### Option 1: Add R2 URLs to WordPress Attachments (RECOMMENDED)

If your images are already uploaded to WordPress, add R2 URL metadata:

```php
<?php
/**
 * Add R2 CDN URLs to WordPress attachments
 * Run this once to migrate existing images
 */

// Example: Map attachment IDs to R2 URLs
$r2_url_mapping = [
    61 => 'https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/image-61.jpg?X-Amz-Algorithm=...',
    89 => 'https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/image-89.jpg?X-Amz-Algorithm=...',
    // Add more mappings
];

foreach ($r2_url_mapping as $attachment_id => $r2_url) {
    update_post_meta($attachment_id, '_r2_cdn_url', $r2_url);
    error_log("Added R2 URL for attachment $attachment_id");
}

echo "✅ R2 URLs added to " . count($r2_url_mapping) . " attachments!";
```

**Save as**: `add-r2-urls-to-attachments.php`  
**Run once**: Visit `https://yoursite.com/wp-content/plugins/custom-page-builder/add-r2-urls-to-attachments.php`

---

### Option 2: Fetch R2 URLs from Your Products API

If your WooCommerce products already have R2 URLs (like in your screenshot), link them:

```php
<?php
/**
 * Sync R2 URLs from WooCommerce products to attachments
 */

// Get all products
$args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish'
);

$products = get_posts($args);

foreach ($products as $product) {
    $product_id = $product->ID;
    
    // Get the R2 URL from product meta (your Zlaark API structure)
    $r2_url = get_post_meta($product_id, '_product_image_url', true);
    
    // Get the featured image attachment ID
    $attachment_id = get_post_thumbnail_id($product_id);
    
    // Link them together
    if ($r2_url && $attachment_id) {
        update_post_meta($attachment_id, '_r2_cdn_url', $r2_url);
        update_post_meta($attachment_id, '_wp_attachment_product_id', $product_id);
        error_log("Linked attachment $attachment_id to product $product_id with R2 URL");
    }
}

echo "✅ R2 URLs synced from products!";
```

**Save as**: `sync-r2-urls-from-products.php`  
**Run once**: Visit `https://yoursite.com/wp-content/plugins/custom-page-builder/sync-r2-urls-from-products.php`

---

### Option 3: Update Page Data Directly (EASIEST)

Instead of attachment IDs, save R2 URLs directly when editing pages:

**In Admin (Edit Page):**

When selecting images, store the R2 URL instead of attachment ID:

```javascript
// When user selects an image
const imageInput = document.querySelector('input[name*="[image_url]"]');
const r2Url = 'https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/your-image.jpg?X-Amz-Algorithm=...';

imageInput.value = r2Url;
```

**Or via REST API:**

```bash
curl -X POST https://int.dhawada.com/wp-json/custom-page-builder/v1/pages/home \
  -H "Content-Type: application/json" \
  -d '{
    "sections": [
      {
        "type": "hero-slider",
        "slides": [
          {
            "title": "Discover Your Signature Piece",
            "image_url": "https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/slide-1.jpg?X-Amz-Algorithm=...",
            "image": "https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/slide-1.jpg?X-Amz-Algorithm=..."
          }
        ]
      }
    ]
  }'
```

---

## 🎯 AUTOMATIC DETECTION

Once you add R2 URLs using any method above, the plugin will **automatically**:

1. ✅ Detect attachment IDs
2. ✅ Look for R2 URL in metadata
3. ✅ Convert to R2 signed URL in API response
4. ✅ Keep backward compatibility

**No code changes needed after migration!**

---

## 🧪 TESTING

### Test 1: Check Attachment Meta

```php
<?php
$attachment_id = 61;
$r2_url = get_post_meta($attachment_id, '_r2_cdn_url', true);

if ($r2_url) {
    echo "✅ Attachment $attachment_id has R2 URL: $r2_url";
} else {
    echo "❌ Attachment $attachment_id has NO R2 URL";
}
```

### Test 2: API Response

```bash
curl https://int.dhawada.com/wp-json/custom-page-builder/v1/pages/home | jq '.page.sections[0].slides[0]'
```

**Expected Output:**

```json
{
  "title": "Discover Your Signature Piece",
  "image_url": "https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/slide.jpg?X-Amz-Algorithm=...",
  "image": "https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/slide.jpg?X-Amz-Algorithm=..."
}
```

### Test 3: Debug Mode

Add this to your page template to see what's happening:

```php
<?php
$page = get_custom_page('home');
$sections = json_decode($page->sections, true);

echo '<pre>';
print_r($sections[0]['slides'][0]); // Should show image_url
echo '</pre>';
```

---

## 🚀 RECOMMENDED WORKFLOW

**For Your Site:**

Since you already have products with R2 URLs in your Zlaark API, I recommend **Option 2**:

1. Run `sync-r2-urls-from-products.php` once
2. This will link all product R2 URLs to their WordPress attachments
3. The custom page builder will automatically use R2 URLs in responses
4. No manual editing needed!

**Script to Run:**

```php
<?php
// File: sync-r2-urls-from-products.php
// Place in: wp-content/plugins/custom-page-builder/

require_once('../../../wp-load.php');

if (!is_admin() && !current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h2>Syncing R2 URLs from Products...</h2>";

$products = get_posts([
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

$synced = 0;

foreach ($products as $product) {
    $product_id = $product->ID;
    $r2_url = get_post_meta($product_id, '_product_image_url', true);
    $attachment_id = get_post_thumbnail_id($product_id);
    
    if ($r2_url && $attachment_id) {
        update_post_meta($attachment_id, '_r2_cdn_url', $r2_url);
        update_post_meta($attachment_id, '_wp_attachment_product_id', $product_id);
        
        echo "<p>✅ Synced: Product #{$product_id} → Attachment #{$attachment_id}</p>";
        echo "<p style='margin-left: 20px; color: #666;'>URL: " . esc_html(substr($r2_url, 0, 100)) . "...</p>";
        
        $synced++;
    }
}

echo "<hr>";
echo "<h3>✅ Done! Synced {$synced} product images with R2 URLs.</h3>";
echo "<p><a href='/wp-json/custom-page-builder/v1/pages/home'>Test API Response</a></p>";
```

**Steps:**

1. Save the script above as `sync-r2-urls-from-products.php`
2. Upload to `/wp-content/plugins/custom-page-builder/`
3. Visit: `https://int.dhawada.com/wp-content/plugins/custom-page-builder/sync-r2-urls-from-products.php`
4. Wait for sync to complete
5. Test API: `https://int.dhawada.com/wp-json/custom-page-builder/v1/pages/home`

---

## 📝 SUMMARY

**What I Fixed:**

✅ Added `cpb_get_r2_url_for_attachment()` function  
✅ Checks `_r2_cdn_url` attachment meta  
✅ Checks `_product_image_url` product meta  
✅ Auto-converts IDs to R2 URLs in API responses  
✅ Maintains backward compatibility  

**What You Need to Do:**

1. Choose one of the 3 options above
2. Add R2 URLs to your attachments/pages
3. Test the API response

**Best Option for You:**

Run the `sync-r2-urls-from-products.php` script to automatically link all your WooCommerce product R2 URLs to WordPress attachments.

---

**Status: ✅ CODE READY - MIGRATION REQUIRED**  
**Next Step: Run sync script to populate R2 URLs**
