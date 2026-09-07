# Image Display Fixes - Complete Guide

## 🎯 Two Different Issues

### Issue 1: Custom Page Builder Images (API/Frontend)
**Problem:** Images don't show on published custom pages (React frontend)  
**Cause:** API returns attachment IDs, not URLs  
**Fix:** Already applied in `class-secure-image-handler.php`

### Issue 2: WooCommerce Product Images (Admin)
**Problem:** Product images show checkered pattern in admin  
**Cause:** Secure Images plugin or no image set  
**Fix:** Use `FIX_IMAGE_DISPLAY_NOW.php`

---

## ⚡ Quick Fixes

### For Custom Page Builder (Frontend/API)

**Already Fixed!** The code has been updated. Just:

1. Run `APPLY_IMAGE_FIX.php` to apply changes
2. Update your React frontend:
```javascript
// Use this:
<img src={section.config.image_url} />

// Or this:
<img src={section.config.image_data.url} />
```

### For WooCommerce Products (Admin)

1. **Deactivate Secure Images plugin**
2. **Refresh page** (Ctrl+Shift+R)
3. **Set product image** (click "Set product image" button)
4. **Save product**

Or run: `FIX_IMAGE_DISPLAY_NOW.php`

---

## 📁 Essential Files

### Custom Page Builder
- **APPLY_IMAGE_FIX.php** - Apply the API fix
- **START_HERE.md** - Quick start guide
- **FRONTEND_IMAGE_USAGE.md** - React integration guide

### WooCommerce Products
- **FIX_IMAGE_DISPLAY_NOW.php** - Fix product images
- **MAKE_IMAGES_VISIBLE.md** - Step-by-step guide

### Core Files (Don't Delete)
- **class-secure-image-handler.php** - Contains the fix
- **INSTALLATION.md** - Plugin installation
- **CLEANED_AND_READY.md** - Plugin status

---

## 🔧 How to Use

### Step 1: Identify Your Issue

**Custom Page Builder Issue:**
- Images don't show on published pages
- React frontend can't display images
- API returns attachment IDs only

**WooCommerce Issue:**
- Product image box shows checkered pattern
- Images don't show in admin
- Can't see uploaded images

### Step 2: Apply the Right Fix

**For Custom Page Builder:**
```
1. Copy APPLY_IMAGE_FIX.php to WordPress root
2. Open: http://yoursite.com/APPLY_IMAGE_FIX.php
3. Follow instructions
4. Update React frontend code
5. Delete the file
```

**For WooCommerce:**
```
1. Copy FIX_IMAGE_DISPLAY_NOW.php to WordPress root
2. Open: http://yoursite.com/FIX_IMAGE_DISPLAY_NOW.php
3. Follow instructions
4. Deactivate Secure Images plugin
5. Set product image
6. Delete the file
```

---

## 📚 Documentation

### Custom Page Builder
- **START_HERE.md** - Start here for Custom Page Builder issues
- **FRONTEND_IMAGE_USAGE.md** - Complete React integration guide with examples

### WooCommerce
- **MAKE_IMAGES_VISIBLE.md** - Simple guide for WooCommerce product images

### General
- **INSTALLATION.md** - Plugin installation instructions
- **CLEANED_AND_READY.md** - Plugin cleanup status

---

## ✅ What Was Fixed

### Custom Page Builder Fix

**Modified:** `integrations/class-secure-image-handler.php`

**Added:**
- `image_url` - Direct URL for easy access
- `image_urls` - All size variants (thumbnail, medium, large, full)
- `image_data` - Complete metadata (alt, title, etc.)

**Result:** API now returns actual URLs, not just IDs

### WooCommerce Fix

**Script:** `FIX_IMAGE_DISPLAY_NOW.php`

**Does:**
- Removes image protection
- Regenerates thumbnails
- Clears caches
- Checks file existence
- Provides step-by-step instructions

**Result:** Product images visible in admin

---

## 🆘 Troubleshooting

### Custom Page Builder

**Images still not showing?**
1. Check API response: `/wp-json/custom-pages/v1/pages/1`
2. Look for `image_url` field
3. Verify frontend is using `section.config.image_url`
4. Clear browser cache

### WooCommerce

**Images still not showing?**
1. Deactivate Secure Images plugin
2. Hard refresh (Ctrl+Shift+R)
3. Re-upload the image
4. Check browser console (F12)

---

## 🔒 Security

### Delete After Use

These diagnostic files should be deleted after use:
- ❌ APPLY_IMAGE_FIX.php
- ❌ FIX_IMAGE_DISPLAY_NOW.php

### Keep These

Documentation files are safe to keep:
- ✅ All .md files
- ✅ Core plugin files

---

## 📊 Summary

| Issue | Fix File | Documentation |
|-------|----------|---------------|
| Custom Page Builder | APPLY_IMAGE_FIX.php | START_HERE.md |
| WooCommerce Products | FIX_IMAGE_DISPLAY_NOW.php | MAKE_IMAGES_VISIBLE.md |
| React Integration | - | FRONTEND_IMAGE_USAGE.md |

---

**Quick Reference:**
- Custom Page Builder → `APPLY_IMAGE_FIX.php` + `START_HERE.md`
- WooCommerce Products → `FIX_IMAGE_DISPLAY_NOW.php` + `MAKE_IMAGES_VISIBLE.md`
- React Frontend → `FRONTEND_IMAGE_USAGE.md`

---

**Last Updated:** October 29, 2025  
**Status:** All fixes ready to use
