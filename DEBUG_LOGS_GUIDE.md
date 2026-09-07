# Debug Logs Guide - WooCommerce Category & Tag Selectors

## 🔍 Debug Logs Added

Comprehensive debug logging has been added to help identify any issues with the WooCommerce category and tag selectors in product cards.

---

## 📊 What You'll See

### When Editing a Product in Product Grid Section:

#### **1. Blue Debug Box (Categories)**
```
┌─────────────────────────────────────────────────────┐
│ 🔍 DEBUG: WooCommerce Categories for Product #1    │
├─────────────────────────────────────────────────────┤
│ Product Index: 0                                    │
│ Field Name: config[products][0][wc_categories]     │
│ Plugin Dir: /path/to/plugin/                       │
│ Template Path: /path/to/woocommerce-category-...   │
│ File Exists: ✅ YES                                 │
│ WooCommerce Active: ✅ YES                          │
│ Selected Categories: []                             │
│ WC Categories Found: 8                              │
└─────────────────────────────────────────────────────┘

⬇️ Category Selector Should Appear Below ⬇️

✅ woocommerce-category-selector.php IS EXECUTING!
✅ WooCommerce Status: ACTIVE
Categories Found: 8
Category Names: Anklets, Bangles, Bracelets, Chains, Earrings

[Category Selector Interface Here]

✅ Category selector included successfully!
```

#### **2. Green Debug Box (Tags)**
```
┌─────────────────────────────────────────────────────┐
│ 🔍 DEBUG: WooCommerce Tags for Product #1          │
├─────────────────────────────────────────────────────┤
│ Product Index: 0                                    │
│ Field Name: config[products][0][wc_tags]           │
│ Plugin Dir: /path/to/plugin/                       │
│ Template Path: /path/to/woocommerce-tag-...        │
│ File Exists: ✅ YES                                 │
│ WooCommerce Active: ✅ YES                          │
│ Selected Tags: []                                   │
│ WC Tags Found: 5                                    │
└─────────────────────────────────────────────────────┘

⬇️ Tag Selector Should Appear Below ⬇️

✅ woocommerce-tag-selector.php IS EXECUTING!
✅ WooCommerce Status: ACTIVE
Tags Found: 5
Tag Names: New Arrival, Best Seller, Sale, Featured, Limited

[Tag Selector Interface Here]

✅ Tag selector included successfully!
```

---

## 🎯 How to Read the Debug Logs

### ✅ Success Indicators

| Message | Meaning |
|---------|---------|
| `File Exists: ✅ YES` | Template file found |
| `WooCommerce Active: ✅ YES` | WooCommerce is installed and active |
| `✅ woocommerce-category-selector.php IS EXECUTING!` | Template is loading |
| `Categories Found: 8` | Found WooCommerce categories |
| `✅ Category selector included successfully!` | No errors during include |

### ⚠️ Warning Indicators

| Message | Meaning | Solution |
|---------|---------|----------|
| `File Exists: ❌ NO` | Template file missing | Check file path |
| `WooCommerce Active: ⚠️ NOT ACTIVE` | WooCommerce not installed | Install/activate WooCommerce |
| `Categories Found: 0` | No categories in WooCommerce | Add categories in WooCommerce |
| `Tags Found: 0` | No tags in WooCommerce | Add tags in WooCommerce |

### ❌ Error Indicators

| Message | Meaning | Solution |
|---------|---------|----------|
| `❌ ERROR: [message]` | PHP error occurred | Check error message details |
| `Selected Categories: Not an array` | Data format issue | Check saved data structure |

---

## 🔧 Troubleshooting Guide

### Issue 1: "File Exists: ❌ NO"

**Problem:** Template file not found

**Check:**
1. File exists at: `templates/admin/partials/woocommerce-category-selector.php`
2. File exists at: `templates/admin/partials/woocommerce-tag-selector.php`
3. `CUSTOM_PAGE_BUILDER_PLUGIN_DIR` constant is defined correctly

**Solution:**
- Verify files are in correct location
- Check file permissions
- Reupload files if missing

---

### Issue 2: "WooCommerce Active: ⚠️ NOT ACTIVE"

**Problem:** WooCommerce plugin not active

**Solution:**
1. Go to WordPress Admin → Plugins
2. Find "WooCommerce"
3. Click "Activate"
4. Refresh the page builder

---

### Issue 3: "Categories Found: 0" or "Tags Found: 0"

**Problem:** No WooCommerce categories/tags exist

**Solution:**

**For Categories:**
1. Go to WordPress Admin → Products → Categories
2. Add some product categories
3. Refresh the page builder

**For Tags:**
1. Go to WordPress Admin → Products → Tags
2. Add some product tags
3. Refresh the page builder

---

### Issue 4: Selector Template Executes But Nothing Shows

**Problem:** Template loads but selector doesn't render

**Check Debug Logs For:**
- `✅ woocommerce-category-selector.php IS EXECUTING!` - Should appear
- `Categories Found: X` - Should show number > 0
- Any error messages after "⬇️ Category Selector Should Appear Below ⬇️"

**Possible Causes:**
1. JavaScript error (check browser console)
2. CSS conflict hiding the selector
3. PHP error in selector template

**Solution:**
1. Open browser console (F12)
2. Look for JavaScript errors
3. Check if selector HTML exists in page source
4. Check for CSS `display: none` on selector elements

---

### Issue 5: "❌ ERROR: [message]"

**Problem:** PHP error during template include

**Action:**
1. Read the error message carefully
2. Check PHP error log
3. Look for syntax errors in template files
4. Check for missing functions or classes

---

## 📋 Debug Checklist

When reporting issues, provide this information:

### Product Card Debug Info:
- [ ] Product Index number
- [ ] Field Name shown
- [ ] Plugin Dir path
- [ ] Template Path shown
- [ ] File Exists status (YES/NO)
- [ ] WooCommerce Active status (YES/NO)
- [ ] Selected Categories/Tags value
- [ ] Categories/Tags Found count

### Template Execution:
- [ ] Does "IS EXECUTING!" message appear?
- [ ] What is WooCommerce Status?
- [ ] How many categories/tags found?
- [ ] Any category/tag names shown?
- [ ] Does "included successfully!" message appear?
- [ ] Any error messages?

### Browser Console:
- [ ] Any JavaScript errors?
- [ ] Any network errors?
- [ ] Any console warnings?

---

## 🎨 Debug Log Colors

| Color | Purpose | Element |
|-------|---------|---------|
| **Blue (#2196f3)** | Categories debug info | Category-related logs |
| **Green (#10b981)** | Tags debug info | Tag-related logs |
| **Green (#4caf50)** | Success messages | File executing, WC active |
| **Orange (#ff9800)** | Warning messages | WC not active |
| **Red (#f44336)** | Error messages | Errors during execution |

---

## 🚀 Next Steps

### If Everything Shows Green ✅:
- The selectors are working!
- You should see the category and tag interfaces
- You can select categories and tags for products

### If You See Warnings ⚠️:
- Follow the troubleshooting guide above
- Fix the specific issue indicated
- Refresh and check again

### If You See Errors ❌:
- Copy the complete error message
- Check the troubleshooting guide
- Provide debug info when asking for help

---

## 📸 What to Screenshot

If you need help, take screenshots of:

1. **The complete blue debug box** (categories)
2. **The complete green debug box** (tags)
3. **Any error messages** in red
4. **Browser console** (F12 → Console tab)
5. **The area where selector should appear**

---

## 🔄 Removing Debug Logs

Once everything is working, the debug logs can be removed to clean up the interface. The logs are only for troubleshooting and will be removed in the final version.

---

## 📞 Support Information

**Debug logs show:**
- ✅ What files are being loaded
- ✅ What data is available
- ✅ What WooCommerce returns
- ✅ Where errors occur
- ✅ What's being rendered

**This helps identify:**
- File path issues
- WooCommerce activation issues
- Missing categories/tags
- Template loading errors
- Data format problems

---

**Status: Debug Mode Active**
**Purpose: Identify and fix any rendering issues**
**Next: Test and report findings**
