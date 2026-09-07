# Troubleshooting: Category Selector Not Showing

## Problem
The category selector is not appearing in the product grid or other sections.

## Root Cause Analysis

The category selector might not show for these reasons:

### 1. **No WordPress Categories Exist** ⭐ MOST COMMON
- The plugin pulls categories from WordPress (Posts → Categories)
- If no categories exist, the selector shows a message
- **Solution**: Add at least one category in WordPress

### 2. **Plugin Not Fully Loaded**
- Files might not be loaded properly
- **Solution**: Deactivate and reactivate the plugin

### 3. **Browser Cache**
- Old CSS/JS files cached
- **Solution**: Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)

### 4. **CSS Not Loading**
- Category manager CSS not enqueued
- **Solution**: Check browser console for 404 errors

## Step-by-Step Fix

### Step 1: Add WordPress Categories
1. Go to **WordPress Admin → Posts → Categories**
2. Add at least one category (e.g., "Jewelry", "Products", "Uncategorized")
3. Click "Add New Category"

### Step 2: Run Test Script
1. Go to: `yoursite.com/wp-content/plugins/Zlaark_custom-page/test-categories.php`
2. This will show:
   - ✅ If Category Manager is loaded
   - ✅ How many WordPress categories exist
   - ✅ If files are in the right place
   - ✅ Create a test category

### Step 3: Deactivate & Reactivate Plugin
1. Go to **Plugins → Installed Plugins**
2. Find "Zlaark Custom Page Builder"
3. Click "Deactivate"
4. Wait 2 seconds
5. Click "Activate"

### Step 4: Clear Browser Cache
1. Press **Ctrl+Shift+Delete** (Windows) or **Cmd+Shift+Delete** (Mac)
2. Select "Cached images and files"
3. Click "Clear data"
4. Or just do a hard refresh: **Ctrl+F5** or **Cmd+Shift+R**

### Step 5: Check the Page
1. Go to **Custom Pages → Add New** or edit existing
2. Add a **Product Grid** section
3. Click "Add Product"
4. Scroll down - you should now see:
   - **Product Categories** label
   - A gray box with categories
   - "+ Add New Category" button
   - "Selected: No categories selected" at bottom

## What You Should See

### If Categories Exist:
```
Product Categories
┌─────────────────────────────────────┐
│ Select Categories:  [+ Add New Category] │
├─────────────────────────────────────┤
│ ☐ Jewelry (5)                       │
│ ☐ Necklaces (3)                     │
│ ☐ Rings (2)                         │
│ ☐ Uncategorized (0)                 │
├─────────────────────────────────────┤
│ Selected: No categories selected    │
└─────────────────────────────────────┘
```

### If No Categories Exist:
```
Product Categories
┌─────────────────────────────────────┐
│ Select Categories:  [+ Add New Category] │
├─────────────────────────────────────┤
│ 📁 No categories found!             │
│ Click here to add categories in     │
│ WordPress or click "+ Add New       │
│ Category" button above.             │
├─────────────────────────────────────┤
│ Selected: No categories selected    │
└─────────────────────────────────────┘
```

## Debug Messages

The template now shows helpful messages:

### ⚠️ Category Manager not loaded!
**Meaning**: The PHP class isn't loaded
**Fix**: Deactivate and reactivate the plugin

### ℹ️ No WordPress categories found!
**Meaning**: WordPress has no categories
**Fix**: Go to Posts → Categories and add some

### 📁 No categories found!
**Meaning**: Same as above, shown in the selector box
**Fix**: Click the link or add categories in WordPress

## Manual Verification

### Check Files Exist:
```
Zlaark_custom-page/
├── includes/
│   └── class-category-manager.php ✓
├── admin/
│   ├── class-category-ajax.php ✓
│   ├── js/
│   │   └── category-manager.js ✓
│   └── css/
│       └── category-manager.css ✓
└── templates/
    └── admin/
        └── partials/
            └── category-selector.php ✓
```

### Check WordPress Categories:
```sql
-- Run in phpMyAdmin or database tool
SELECT * FROM wp_terms 
WHERE term_id IN (
    SELECT term_id FROM wp_term_taxonomy 
    WHERE taxonomy = 'category'
);
```

### Check Browser Console:
1. Press **F12** to open Developer Tools
2. Go to **Console** tab
3. Look for errors like:
   - `404 Not Found: category-manager.js`
   - `404 Not Found: category-manager.css`
   - `Uncaught ReferenceError: CategoryManager is not defined`

## Still Not Working?

### Try This:
1. **Check PHP Version**: Must be 7.4 or higher
2. **Check WordPress Version**: Must be 5.0 or higher
3. **Check File Permissions**: Files should be readable (644)
4. **Check Plugin Active**: Must be activated
5. **Check User Role**: Must be Administrator

### Get Debug Info:
Run the test script at:
```
yoursite.com/wp-content/plugins/Zlaark_custom-page/test-categories.php
```

This will show:
- ✅ Category Manager status
- ✅ WordPress categories count
- ✅ File existence
- ✅ Create test category

### Check Network Tab:
1. Press **F12** → **Network** tab
2. Reload the page
3. Look for:
   - `category-manager.css` - Should be 200 OK
   - `category-manager.js` - Should be 200 OK
   - If 404, files aren't being loaded

## Expected Behavior

### When Working Correctly:
1. **Product Grid Section** → Add Product → See "Product Categories"
2. **Hero Banner Section** → See "Categories" section
3. **Content Block Section** → See "Categories" section
4. **Testimonials Section** → See "Categories" section

### Category Selector Features:
- ✅ Shows all WordPress categories
- ✅ Checkboxes for multiple selection
- ✅ "+ Add New Category" button
- ✅ Shows post counts (e.g., "Jewelry (5)")
- ✅ Shows selected categories at bottom
- ✅ Saves selections with the product/section

## Quick Fix Checklist

- [ ] WordPress categories exist (Posts → Categories)
- [ ] Plugin is activated
- [ ] Deactivated and reactivated plugin
- [ ] Cleared browser cache (Ctrl+Shift+Delete)
- [ ] Hard refreshed page (Ctrl+F5)
- [ ] Ran test script (test-categories.php)
- [ ] Checked browser console for errors (F12)
- [ ] Verified files exist in plugin folder
- [ ] User is Administrator
- [ ] PHP version is 7.4+

## Contact Support

If still not working after all steps:
1. Run test script and take screenshot
2. Open browser console (F12) and take screenshot
3. Check WordPress categories page and take screenshot
4. Provide WordPress version and PHP version

## Success Indicators

You'll know it's working when you see:
1. ✅ Gray box with "Select Categories:" label
2. ✅ List of checkboxes with category names
3. ✅ "+ Add New Category" button
4. ✅ "Selected: ..." text at bottom
5. ✅ Can check/uncheck categories
6. ✅ Selected categories show in blue box at bottom
