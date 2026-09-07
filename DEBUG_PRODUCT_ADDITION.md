# Debug Guide: Product Addition Not Working

## Issue: "I don't see any changes"

If you're not seeing the category selectors when adding products, follow these steps:

## Step 1: Clear Browser Cache

**The most common issue is browser caching of JavaScript files.**

### Chrome:
1. Press `Ctrl + Shift + Delete` (Windows) or `Cmd + Shift + Delete` (Mac)
2. Select "Cached images and files"
3. Click "Clear data"
4. **OR** Hard refresh: `Ctrl + F5` (Windows) or `Cmd + Shift + R` (Mac)

### Firefox:
1. Press `Ctrl + Shift + Delete`
2. Select "Cache"
3. Click "Clear Now"
4. **OR** Hard refresh: `Ctrl + F5`

### Alternative: Disable Cache in DevTools
1. Open DevTools (`F12`)
2. Go to Network tab
3. Check "Disable cache"
4. Keep DevTools open while testing

## Step 2: Verify Files Are Updated

### Check JavaScript File:
1. Open: `wp-content/plugins/Zlaark_custom-page/admin/assets/js/admin.js`
2. Search for: `get_product_template`
3. **Should find**: Line with `cpb_action: 'get_product_template'`
4. **If not found**: File wasn't updated properly

### Check PHP File:
1. Open: `wp-content/plugins/Zlaark_custom-page/admin/class-admin-interface.php`
2. Search for: `handle_get_product_template`
3. **Should find**: Method definition `public function handle_get_product_template()`
4. **If not found**: File wasn't updated properly

## Step 3: Test the Workflow

### Correct Testing Steps:
1. Go to: **WordPress Admin → Custom Pages → Add New Page**
2. Enter page title: "Test Page"
3. Click: **"Add Section"** button (this opens a modal)
4. In the modal, select: **"Product Grid"** from dropdown
5. Click: **"Save Section"** (this closes modal and adds section to page)
6. Now you should see the Product Grid section on the page
7. Inside that section, click: **"Add Product"** button
8. **Expected**: Category selectors should appear

### What You Should See:
```
┌─────────────────────────────────────────┐
│ Product #1                    [Remove]  │
├─────────────────────────────────────────┤
│ Product Image: [Upload Image]          │
│ Product Title: [____________]           │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ 🛍️ WooCommerce Product Categories   │ │
│ │ ☐ Electronics                       │ │
│ │ ☐ Clothing                          │ │
│ │ ☐ Accessories                       │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ 🏷️ WooCommerce Product Tags         │ │
│ │ ☐ New                               │ │
│ │ ☐ Sale                              │ │
│ └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

## Step 4: Check Browser Console

1. Open DevTools: Press `F12`
2. Go to **Console** tab
3. Click "Add Product" button
4. Look for:
   - ✅ No errors = Good
   - ❌ Errors = Problem found

### Expected Console Output:
```javascript
// Should see AJAX request
POST /wp-admin/admin-ajax.php
action: cpb_admin_action
cpb_action: get_product_template
index: 0
```

### Check Network Tab:
1. Go to **Network** tab in DevTools
2. Click "Add Product"
3. Look for request to `admin-ajax.php`
4. Click on it
5. Check **Response** tab
6. Should see: `{"success":true,"data":{"html":"<div class='product-item'>..."}}`

## Step 5: Common Issues

### Issue 1: JavaScript Not Updated
**Symptom**: No AJAX request in Network tab
**Solution**: 
```bash
# Clear WordPress cache if using caching plugin
# Deactivate and reactivate the plugin
# Or manually update the file
```

### Issue 2: PHP Not Updated
**Symptom**: AJAX request returns error
**Solution**: Check WordPress debug.log for PHP errors

### Issue 3: WooCommerce Not Active
**Symptom**: Category selector shows warning
**Solution**: Activate WooCommerce plugin

### Issue 4: No Categories Exist
**Symptom**: "No categories found" message
**Solution**: Add categories in WooCommerce → Products → Categories

## Step 6: Force File Refresh

### Option 1: Change Version Number
Edit `custom-page-builder.php`:
```php
// Find this line:
define('CUSTOM_PAGE_BUILDER_VERSION', '1.0.0');

// Change to:
define('CUSTOM_PAGE_BUILDER_VERSION', '1.0.1');
```

This forces WordPress to reload JavaScript files.

### Option 2: Deactivate/Reactivate Plugin
1. Go to: Plugins
2. Deactivate "Custom Page Builder"
3. Activate "Custom Page Builder"

### Option 3: Direct File Check
1. Open browser
2. Go to: `http://yoursite.com/wp-content/plugins/Zlaark_custom-page/admin/assets/js/admin.js`
3. Press `Ctrl + F` and search for: `get_product_template`
4. If found: File is updated on server
5. If not found: File upload failed

## Step 7: Manual Verification

### Test AJAX Endpoint Directly:
Open browser console and run:
```javascript
jQuery.post(ajaxurl, {
    action: 'cpb_admin_action',
    cpb_action: 'get_product_template',
    nonce: cpb_admin.nonce,
    index: 0
}, function(response) {
    console.log('Response:', response);
    if (response.success) {
        console.log('✅ AJAX endpoint working!');
        console.log('HTML length:', response.data.html.length);
    } else {
        console.log('❌ AJAX failed:', response.data.message);
    }
});
```

**Expected output**:
```
Response: {success: true, data: {html: "<div class='product-item'>..."}}
✅ AJAX endpoint working!
HTML length: 5234
```

## Step 8: Check File Permissions

### On Server:
```bash
# Check if files are readable
ls -la wp-content/plugins/Zlaark_custom-page/admin/assets/js/admin.js
ls -la wp-content/plugins/Zlaark_custom-page/admin/class-admin-interface.php

# Should show: -rw-r--r-- (644)
```

## Step 9: WordPress Debug Mode

### Enable Debug Mode:
Edit `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Debug Log:
1. Look at: `wp-content/debug.log`
2. Click "Add Product"
3. Check for PHP errors

## Step 10: Verify Plugin Structure

### Required Files Must Exist:
```
Zlaark_custom-page/
├── admin/
│   ├── assets/
│   │   └── js/
│   │       └── admin.js ← Must contain get_product_template
│   └── class-admin-interface.php ← Must contain handle_get_product_template
└── templates/
    └── admin/
        └── partials/
            ├── woocommerce-category-selector.php
            └── woocommerce-tag-selector.php
```

## Quick Test Command

Run this in browser console on the page:
```javascript
// Test 1: Check if function exists
console.log('addProduct function:', typeof CPB_Admin.addProduct);
// Should output: "function"

// Test 2: Check if AJAX URL is set
console.log('AJAX URL:', cpb_admin.ajax_url);
// Should output: "/wp-admin/admin-ajax.php"

// Test 3: Check if nonce is set
console.log('Nonce:', cpb_admin.nonce);
// Should output: some hash value

// Test 4: Manually trigger add product
CPB_Admin.addProduct();
// Should add a product with category selectors
```

## Still Not Working?

### Provide These Details:
1. Browser console errors (screenshot)
2. Network tab AJAX response (screenshot)
3. WordPress debug.log contents
4. Result of manual AJAX test
5. File modification dates
6. WordPress version
7. PHP version
8. WooCommerce version

### Emergency Fallback:
If nothing works, the fallback method should still provide a basic product form with a warning message. If you don't even see that, there's a JavaScript error preventing execution.
