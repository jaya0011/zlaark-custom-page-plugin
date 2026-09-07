# 🚀 Custom Page Builder - Start Here

## ✅ NEW: Dual Image Upload Options - BOTH Methods Available!

**You now have TWO ways to add images:**
1. **📁 Upload from computer** using the "Upload Image" button
2. **✍️ Type/paste image URL** directly into the text field

📖 **Dual Options Guide:** [DUAL_IMAGE_UPLOAD_OPTIONS.md](DUAL_IMAGE_UPLOAD_OPTIONS.md) - How to use both methods  
📖 **Full Guide:** [IMAGE_UPLOAD_GUIDE.md](IMAGE_UPLOAD_GUIDE.md) - Complete instructions

---

## Choose Your Issue

### 1️⃣ How to Upload Images (NEW!)
**Need:** Upload images from your computer  
**Solution:** [Go to Image Upload Guide](#image-upload-guide)

### 2️⃣ Custom Page Builder (React Frontend)
**Problem:** Images don't show on published custom pages  
**Solution:** [Go to Custom Page Builder Fix](#custom-page-builder-fix)

### 3️⃣ WooCommerce Products (Admin Panel)
**Problem:** Product images show checkered pattern  
**Solution:** [Go to WooCommerce Fix](#woocommerce-fix)

---

## Image Upload Guide

### ✨ Upload Images from Your PC

**Quick Start:**
1. Go to **Custom Pages** → **Add New Page**
2. Click **"Add Section"** button
3. Select a section type (Content Block, Hero Banner, etc.)
4. Click **"Select Image"** button
5. Upload from your computer OR select existing image
6. Click **"Use Image"**
7. Image preview appears immediately
8. Click **"Save Section"** and **"Save Page"**

**Supported Sections:**
- ✅ Content Block (main image)
- ✅ Hero Banner (background image)
- ✅ Category Showcase (multiple images)
- ✅ Product Grid (multiple images)

**Full Documentation:**
- **[IMAGE_UPLOAD_GUIDE.md](IMAGE_UPLOAD_GUIDE.md)** - Complete guide with screenshots
- **[IMAGE_UPLOAD_FEATURE_COMPLETE.md](IMAGE_UPLOAD_FEATURE_COMPLETE.md)** - Technical details

---

---

## Custom Page Builder Fix

### Problem
- Images don't display on published custom pages
- React frontend can't find image URLs
- API returns attachment IDs only

### Solution (2 Minutes)

**Step 1: Apply the Fix**
```
1. Copy APPLY_IMAGE_FIX.php to WordPress root
2. Open: http://yoursite.com/APPLY_IMAGE_FIX.php
3. Follow instructions
4. Delete file after use
```

**Step 2: Update React Frontend**
```javascript
// Use this in your React components:
<img src={section.config.image_url} alt="Image" />

// Or with metadata:
<img 
  src={section.config.image_data.url}
  alt={section.config.image_data.alt}
/>
```

**Step 3: Test**
- Open your published custom page
- Images should now display
- Check browser console (F12) for errors

### Documentation
- **FRONTEND_IMAGE_USAGE.md** - Complete React integration guide
- **IMAGE_FIXES_README.md** - Detailed documentation

---

## WooCommerce Fix

### Problem
- Product image box shows checkered pattern
- Images don't appear in admin after upload
- Secure Images plugin may be interfering

### Solution (2 Minutes)

**Step 1: Deactivate Secure Images**
```
1. Go to WordPress Admin → Plugins
2. Find "Zlaark Secure Images"
3. Click "Deactivate"
```

**Step 2: Set Product Image**
```
1. Edit your product
2. Click "Set product image" (right side)
3. Upload or select image
4. Click "Update" to save
```

**Step 3: Run Fix Script (if needed)**
```
1. Copy FIX_IMAGE_DISPLAY_NOW.php to WordPress root
2. Open: http://yoursite.com/FIX_IMAGE_DISPLAY_NOW.php
3. Follow instructions
4. Delete file after use
```

### Documentation
- **MAKE_IMAGES_VISIBLE.md** - Step-by-step guide
- **IMAGE_FIXES_README.md** - Complete documentation

---

## Quick Reference

| Issue | Fix File | Time |
|-------|----------|------|
| Custom Page Builder | APPLY_IMAGE_FIX.php | 2 min |
| WooCommerce Products | FIX_IMAGE_DISPLAY_NOW.php | 2 min |

---

## 📁 All Files

### Fix Scripts (Use These)
- **APPLY_IMAGE_FIX.php** - Custom Page Builder fix
- **FIX_IMAGE_DISPLAY_NOW.php** - WooCommerce fix

### Documentation (Read These)
- **IMAGE_FIXES_README.md** - Complete guide
- **FRONTEND_IMAGE_USAGE.md** - React integration
- **MAKE_IMAGES_VISIBLE.md** - WooCommerce guide

### Core Files (Don't Touch)
- **class-secure-image-handler.php** - Contains the fix
- **custom-page-builder.php** - Main plugin file

---

## 🆘 Need Help?

### Custom Page Builder Not Working?
1. Check API: `http://yoursite.com/wp-json/custom-pages/v1/pages/1`
2. Look for `image_url` field in response
3. Verify React code uses `section.config.image_url`
4. Read: **FRONTEND_IMAGE_USAGE.md**

### WooCommerce Not Working?
1. Deactivate Secure Images plugin
2. Hard refresh (Ctrl+Shift+R)
3. Re-upload the image
4. Read: **MAKE_IMAGES_VISIBLE.md**

---

**Quick Start:**
1. Identify your issue (Custom Page Builder or WooCommerce)
2. Run the appropriate fix script
3. Follow the instructions
4. Delete the fix script
5. Done!
