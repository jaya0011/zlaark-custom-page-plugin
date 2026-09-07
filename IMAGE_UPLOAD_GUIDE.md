# Image Upload Guide - Custom Page Builder

## ✅ Image Upload Feature Added!

The Custom Page Builder now has **full image upload functionality** for all section types. Users can easily upload images from their computer through the WordPress media library.

---

## 🎯 How to Upload Images

### Step 1: Add or Edit a Page Section

1. Go to **Custom Pages** → **Add New Page** or edit an existing page
2. Click **"Add Section"** button
3. Select a section type (Content Block, Hero Banner, Category Showcase, or Product Grid)

### Step 2: Upload an Image

1. Look for the **"Select Image"** button in the section configuration
2. Click the button to open the WordPress Media Library
3. **Upload a new image** from your computer OR **select an existing image**
4. Click **"Use Image"** to insert it

### Step 3: Preview and Save

1. The image preview will appear immediately
2. Click **"Save Section"** to save your changes
3. Click **"Save Page"** to publish

---

## 📸 Section Types with Image Upload

### 1. Content Block
- **Main content image** with position options (top, bottom, left, right)
- Image alt text for accessibility
- Automatic image preview

### 2. Hero Banner
- **Background image** for the hero section
- Overlay options
- Responsive image handling

### 3. Category Showcase
- **Multiple category images** (one per category)
- Add/remove categories dynamically
- Each category can have its own image

### 4. Product Grid
- **Multiple product images** (one per product)
- Add/remove products dynamically
- Each product can have its own image
- Support for product badges and sale prices

---

## 🔧 Image Upload Features

### WordPress Media Library Integration
- Full access to WordPress media library
- Upload new images directly
- Select from existing images
- Image type validation (only images allowed)

### Image Preview
- Instant preview after selection
- Thumbnail display (max 200px width)
- Remove and replace images easily

### Secure Image Integration
- Automatic integration with Zlaark Secure Images plugin (if active)
- Images can be protected with encryption
- Fallback URLs for compatibility

### Image Management
- **Select Image** button - Opens media library
- **Remove Image** button - Clears the selected image
- **Image Preview** - Shows the selected image
- **Secure Indicator** - Shows if image is protected (when secure plugin is active)

---

## 🎨 Adding Multiple Images

### For Category Showcase:
1. Click **"Add Category"** button
2. Fill in category details
3. Click **"Select Image"** for that category
4. Upload or select an image
5. Repeat for each category

### For Product Grid:
1. Click **"Add Product"** button
2. Fill in product details
3. Click **"Select Image"** for that product
4. Upload or select an image
5. Repeat for each product

---

## ⚠️ Important Notes

### Image Requirements
- **Supported formats:** JPG, PNG, GIF, WebP
- **Recommended size:** Max 2048x2048px
- **File size:** Under 1MB for best performance
- **Alt text:** Always add alt text for accessibility

### Browser Compatibility
- Works in all modern browsers
- Requires JavaScript enabled
- WordPress media library must be functional

### Secure Images Plugin
- If **Zlaark Secure Images** plugin is active, images will be automatically protected
- Protected images show a lock icon indicator
- Images remain visible in admin area
- Frontend protection is applied automatically

---

## 🐛 Troubleshooting

### Image Upload Button Not Working?

**Check:**
1. JavaScript is enabled in your browser
2. WordPress media library is accessible
3. You have permission to upload media
4. Browser console for errors (F12 → Console tab)

**Solution:**
- Hard refresh the page (Ctrl+Shift+R)
- Clear browser cache
- Try a different browser

### Image Not Showing After Upload?

**Check:**
1. Image preview appears after selection
2. You clicked "Save Section" button
3. You clicked "Save Page" button
4. File permissions on uploads folder (755)

**Solution:**
- Re-upload the image
- Check WordPress uploads folder permissions
- Verify image file exists in Media Library

### Secure Images Plugin Issues?

**If images disappear after enabling encryption:**
1. The plugin may be filtering admin images
2. Check `SIMPLE_FIX_GUIDE.md` in Zlaark_secure-img folder
3. Temporarily deactivate the plugin to set images
4. Reactivate after images are set

---

## 💡 Best Practices

### Image Optimization
1. **Compress images** before uploading (use TinyPNG, ImageOptim, etc.)
2. **Use appropriate dimensions** for your layout
3. **Add descriptive alt text** for SEO and accessibility
4. **Use WebP format** for better compression (if supported)

### Accessibility
1. Always add **alt text** describing the image
2. Use **descriptive filenames** (e.g., "red-shoes-product.jpg")
3. Avoid text in images when possible
4. Ensure sufficient **color contrast** for overlays

### Performance
1. Don't upload images larger than needed
2. Use WordPress image sizes (thumbnail, medium, large)
3. Enable lazy loading for better page speed
4. Consider using a CDN for image delivery

---

## 🎓 Technical Details

### JavaScript Functions
- `initMediaUploader()` - Initializes WordPress media library
- `processImageUpload()` - Processes uploaded images through secure handler
- Image upload works with multiple button classes for flexibility

### Supported Button Classes
- `.cpb-upload-image`
- `.upload-image-btn`
- `#upload-content-image`
- Any button with `id^="upload-"`

### Image Storage
- Images are stored as **attachment IDs** or **URLs** depending on field type
- Secure images store attachment IDs for processing
- Direct image fields store URLs for immediate display

---

## ✅ Quick Reference

| Action | Button | Result |
|--------|--------|--------|
| Upload Image | "Select Image" | Opens media library |
| Remove Image | "Remove" | Clears selected image |
| Preview Image | Automatic | Shows after selection |
| Save Section | "Save Section" | Saves section with image |
| Add Category | "Add Category" | Adds new category with image field |
| Add Product | "Add Product" | Adds new product with image field |

---

## 📞 Need Help?

If you encounter any issues:

1. Check this guide first
2. Review `START_HERE.md` for general plugin info
3. Check `IMAGE_FIXES_README.md` for image-specific issues
4. Check browser console for JavaScript errors (F12)
5. Verify WordPress and plugin versions are up to date

---

**Last Updated:** October 30, 2025  
**Plugin Version:** Custom Page Builder 1.0.0  
**Feature:** Image Upload Functionality ✅ COMPLETE
