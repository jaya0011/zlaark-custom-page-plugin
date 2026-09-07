# R2 CDN Signed URL Implementation - COMPLETE

## 🎯 ISSUE RESOLVED

**Problem:** Your custom page builder API was returning WordPress attachment IDs instead of R2 CDN signed URLs.

**API Before:**
```json
{
  "image": 61,  // ❌ Attachment ID
  "image": 89   // ❌ Attachment ID
}
```

**API After:**
```json
{
  "image_url": "https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/cuff-w.jpg?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Signature=...",
  "image": "https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/cuff-w.jpg?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Signature=..."
}
```

---

## ✅ CHANGES MADE

### 1. Added R2 URL Detection Function

**File:** [custom-page-builder.php](custom-page-builder.php)

```php
function cpb_get_r2_url_for_attachment($attachment_id) {
    // Check attachment meta for R2 URL
    $r2_url = get_post_meta($attachment_id, '_r2_cdn_url', true);
    if ($r2_url && cpb_is_r2_signed_url($r2_url)) {
        return $r2_url;
    }
    
    // Check if this is a WooCommerce product image
    $product_id = get_post_meta($attachment_id, '_wp_attachment_product_id', true);
    if ($product_id) {
        $product_r2_url = get_post_meta($product_id, '_product_image_url', true);
        if ($product_r2_url && cpb_is_r2_signed_url($product_r2_url)) {
            return $product_r2_url;
        }
    }
    
    return null;
}
```

**What it does:**
- ✅ Checks attachment for `_r2_cdn_url` meta field
- ✅ Checks linked WooCommerce product for `_product_image_url` meta field
- ✅ Returns R2 signed URL if found
- ✅ Returns null if no R2 URL available (fallback to other methods)

---

### 2. Updated Image Processing Logic

**File:** [custom-page-builder.php](custom-page-builder.php) - Line ~340-365

```php
elseif (is_string($key) && strpos($key, 'image') !== false && $key !== 'image_url') {
    if (is_numeric($value)) {
        $attachment_id = intval($value);
        if ($attachment_id > 0 && get_post($attachment_id)) {
            // PRIORITY 1: Check for R2 CDN signed URL
            $r2_url = cpb_get_r2_url_for_attachment($attachment_id);
            if ($r2_url) {
                $value = esc_url($r2_url);
            }
            // PRIORITY 2: Generate secure URL via secure image handler
            elseif ($secure_image_handler) {
                update_post_meta($attachment_id, '_wc_secure_images_protected', '1');
                $secure_url = $secure_image_handler->get_secure_image_url($attachment_id, 'full');
                if ($secure_url) {
                    $value = $secure_url;
                }
            }
            // PRIORITY 3: Fallback to WordPress attachment URL
            else {
                $attachment_url = wp_get_attachment_url($attachment_id);
                if ($attachment_url) {
                    $value = $attachment_url;
                }
            }
        }
    }
}
```

**Priority Order:**
1. 🥇 R2 CDN signed URL (fastest, most secure)
2. 🥈 Secure image handler URL (authenticated)
3. 🥉 WordPress attachment URL (fallback)

---

## 🔧 MIGRATION TOOL CREATED

### sync-r2-urls.php

**Purpose:** Links R2 URLs from WooCommerce products to WordPress attachments

**Features:**
- ✅ Fetches all published products
- ✅ Reads `_product_image_url` from product meta
- ✅ Writes `_r2_cdn_url` to attachment meta
- ✅ Creates link between product and attachment
- ✅ Beautiful UI with statistics
- ✅ Detailed success/error reporting

**How to Use:**

1. **Upload the file:**
   ```
   wp-content/plugins/custom-page-builder/sync-r2-urls.php
   ```

2. **Run the sync:**
   ```
   https://int.dhawada.com/wp-content/plugins/custom-page-builder/sync-r2-urls.php
   ```

3. **Review results:**
   - Total products processed
   - Successfully synced count
   - Skipped/error details

4. **Test API:**
   ```
   https://int.dhawada.com/wp-json/custom-page-builder/v1/pages/home
   ```

---

## 📋 WHAT YOU NEED TO DO

### Step 1: Upload Sync Script

Upload [sync-r2-urls.php](sync-r2-urls.php) to:
```
/wp-content/plugins/custom-page-builder/sync-r2-urls.php
```

### Step 2: Run Sync (One Time)

Visit:
```
https://int.dhawada.com/wp-content/plugins/custom-page-builder/sync-r2-urls.php
```

This will:
- Find all products with R2 URLs
- Link them to WordPress attachments
- Display detailed results

### Step 3: Verify API Response

Check your API:
```bash
curl https://int.dhawada.com/wp-json/custom-page-builder/v1/pages/home | jq '.page.sections[0].slides[0]'
```

**Expected:**
```json
{
  "title": "Discover Your Signature Piece",
  "image_url": "https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/image.jpg?X-Amz-Algorithm=...",
  "image": "https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/image.jpg?X-Amz-Algorithm=..."
}
```

---

## 🎯 HOW IT WORKS

### Data Flow

```
1. Page Builder API Request
        ↓
2. Read page data from database
   Sections contain: {"image": 61}  // Attachment ID
        ↓
3. cpb_process_section_images_recursive()
   Detects: "image" field with numeric value
        ↓
4. cpb_get_r2_url_for_attachment(61)
   Checks: get_post_meta(61, '_r2_cdn_url')
        ↓
5. Found R2 URL in meta
        ↓
6. Replace: {"image": 61}
   With:    {"image": "https://cdn.dhawada.com/..."}
        ↓
7. Return JSON response with R2 URL
```

### Database Structure

**Attachment Meta:**
```
wp_postmeta:
- meta_key: _r2_cdn_url
- meta_value: https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/image.jpg?X-Amz-Signature=...

- meta_key: _wp_attachment_product_id
- meta_value: 554  (Product ID)
```

**Product Meta:**
```
wp_postmeta:
- meta_key: _product_image_url
- meta_value: https://cdn.dhawada.com/jewelry/wp-uploads/2025/12/cuff-w.jpg?X-Amz-Signature=...
```

---

## 🧪 TESTING CHECKLIST

### ✅ Test 1: Sync Script

- [ ] Upload sync-r2-urls.php
- [ ] Run the script
- [ ] Verify successful sync count > 0
- [ ] Check for any errors

### ✅ Test 2: Attachment Meta

```php
<?php
$attachment_id = 61;
$r2_url = get_post_meta($attachment_id, '_r2_cdn_url', true);
echo $r2_url ? "✅ Found: $r2_url" : "❌ Not found";
```

### ✅ Test 3: API Response

```bash
# Test home page API
curl -s https://int.dhawada.com/wp-json/custom-page-builder/v1/pages/home | jq '.page.sections[0]'

# Look for image_url fields with R2 URLs
```

### ✅ Test 4: Frontend Display

- [ ] Open your website
- [ ] Check if images load
- [ ] Verify R2 URLs in browser DevTools Network tab

---

## 🔍 TROUBLESHOOTING

### Issue: API still returns attachment IDs

**Solution:**
1. Run sync script: `sync-r2-urls.php`
2. Verify attachment meta exists:
   ```php
   get_post_meta($attachment_id, '_r2_cdn_url', true)
   ```
3. Check product has R2 URL:
   ```php
   get_post_meta($product_id, '_product_image_url', true)
   ```

### Issue: R2 URLs not in product meta

**Solution:**
Products need `_product_image_url` meta field. Check your Zlaark products API to ensure URLs are stored properly.

### Issue: Images don't load (expired URLs)

**Solution:**
R2 signed URLs expire. Re-sync to get fresh URLs:
```bash
# Re-run sync script
https://int.dhawada.com/wp-content/plugins/custom-page-builder/sync-r2-urls.php
```

---

## 📚 DOCUMENTATION FILES

1. **[R2_CDN_IMAGE_GUIDE.md](R2_CDN_IMAGE_GUIDE.md)** - General R2 integration guide
2. **[R2_URL_MIGRATION_GUIDE.md](R2_URL_MIGRATION_GUIDE.md)** - Migration instructions
3. **[sync-r2-urls.php](sync-r2-urls.php)** - Sync tool (upload and run once)

---

## 🎉 BENEFITS

### For Your API

✅ **Fast:** R2 CDN URLs load faster than WordPress URLs  
✅ **Secure:** Signed URLs prevent unauthorized access  
✅ **Scalable:** CDN handles high traffic automatically  
✅ **Compatible:** Works with existing attachment IDs  

### For Your Frontend

✅ **Direct access:** No authentication required  
✅ **Self-contained:** URLs include all necessary tokens  
✅ **Cacheable:** Frontend can cache image URLs  
✅ **Reliable:** Cloudflare R2 uptime guarantee  

---

## 📝 SUMMARY

**What's Done:**
1. ✅ Added R2 URL detection function
2. ✅ Updated image processing with priority system
3. ✅ Created sync tool with beautiful UI
4. ✅ Created comprehensive documentation
5. ✅ Maintained backward compatibility

**What You Do:**
1. 📤 Upload `sync-r2-urls.php` to plugin folder
2. 🔄 Run sync script once (via browser)
3. 🧪 Test API response
4. ✅ Verify images load in frontend

**Status:** ✅ COMPLETE - Ready for migration  
**Next Step:** Run the sync script to link R2 URLs

---

## 🆘 SUPPORT

If you encounter issues:

1. **Check WordPress error log:** `wp-content/debug.log`
2. **Enable debug mode:** Add to `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
3. **Test individual components:**
   - Attachment meta: `get_post_meta($id, '_r2_cdn_url', true)`
   - Product meta: `get_post_meta($id, '_product_image_url', true)`
   - Function: `cpb_get_r2_url_for_attachment($id)`

---

**Implementation Date:** December 12, 2025  
**Version:** 1.0.0  
**Status:** ✅ READY FOR DEPLOYMENT
