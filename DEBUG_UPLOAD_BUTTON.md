# 🔍 Debug Upload Button - Step by Step

## ✅ How to Check if Upload Button is Working

Follow these steps to debug and verify the upload button functionality.

---

## Step 1: Hard Refresh the Page

**IMPORTANT:** You MUST do a hard refresh to load the new code!

### Windows/Linux:
- Press **Ctrl + Shift + R**
- Or **Ctrl + F5**

### Mac:
- Press **Cmd + Shift + R**
- Or **Cmd + Option + R**

---

## Step 2: Open Browser Console

1. Press **F12** on your keyboard
2. Click the **"Console"** tab
3. Keep this open while testing

---

## Step 3: Go to Add New Page

1. Go to **WordPress Admin**
2. Click **Page Builder** in the left menu
3. Click **Add New**

---

## Step 4: Check Console Messages

You should see these messages in the console:

```
Initializing media uploader...
wp.media available: true
Media uploader initialized successfully
```

### ✅ If you see these messages:
- Good! The script is loading correctly
- Continue to Step 5

### ❌ If you DON'T see these messages:
- The script isn't loading
- Try hard refresh again (Ctrl+Shift+R)
- Check if you're on the correct page

### ❌ If you see "wp.media available: false":
- WordPress media library isn't loaded
- This is the problem!
- See "Fix wp.media Not Available" section below

---

## Step 5: Add a Section

1. Click **"+ Content Block"** button
2. Look for **"Section Image:"** field
3. You should see:
   - **[📁 Upload Image]** button
   - **"Or enter image URL:"** label
   - Text input field

---

## Step 6: Click Upload Button

1. Click the **"📁 Upload Image"** button
2. Watch the console

### ✅ Expected Console Messages:
```
Upload button clicked!
Target ID: section-image-input-0
Target input found: true
Media frame opened
```

### ✅ Expected Result:
- WordPress Media Library popup opens
- You can select/upload images

### ❌ If Nothing Happens:
- Check console for errors
- See troubleshooting section below

---

## Step 7: Select an Image

1. In the media library, click **"Upload Files"** tab
2. Upload an image OR select existing
3. Click **"Use This Image"** button

### ✅ Expected Console Messages:
```
Image selected: https://yoursite.com/wp-content/uploads/...
Image URL set and preview shown
```

### ✅ Expected Result:
- Media library closes
- URL appears in text field
- Image preview shows below

---

## 🐛 Troubleshooting

### Problem: "wp.media not available!" alert

**This means WordPress Media Library isn't loaded.**

**Solution 1: Check Page Hook**
The media library only loads on plugin pages. Make sure you're on:
- `admin.php?page=custom-page-builder-new`
- NOT on a different admin page

**Solution 2: Check Enqueue Code**
Open `custom-page-builder.php` and search for:
```php
wp_enqueue_media();
```

Make sure this line exists in the `admin_enqueue_scripts` hook.

**Solution 3: Force Load**
Add this to the top of the page (temporary test):
```php
<?php wp_enqueue_media(); ?>
```

---

### Problem: Console shows "Upload button clicked!" but nothing happens

**Check:**
1. Is `wp.media available: true`?
2. Are there any JavaScript errors after clicking?
3. Is the button inside a form that's submitting?

**Solution:**
The button should have `type="button"` to prevent form submission:
```html
<button type="button" class="button upload-image-btn" ...>
```

---

### Problem: No console messages at all

**This means JavaScript isn't running.**

**Check:**
1. Is jQuery loaded? Type `jQuery` in console - should not say "undefined"
2. Are there JavaScript errors? Look for red text in console
3. Is the script tag closed properly?

**Solution:**
1. Hard refresh (Ctrl+Shift+R)
2. Clear browser cache
3. Try different browser

---

### Problem: "Target input found: false"

**This means the input field isn't being found.**

**Check:**
1. Does the button have `data-target` attribute?
2. Does the input have matching `id`?

**Example:**
```html
<button data-target="section-image-input-0">Upload</button>
<input id="section-image-input-0" ...>
```

**Solution:**
Make sure IDs match exactly!

---

### Problem: Image URL doesn't fill in text field

**Check console for:**
```
Image selected: [URL]
Image URL set and preview shown
```

**If you see these but field is empty:**
- The input selector might be wrong
- Check if input has correct ID

**Solution:**
Inspect the input element (right-click → Inspect) and verify:
- It has an `id` attribute
- The ID matches the button's `data-target`

---

### Problem: Preview doesn't show

**Check:**
1. Is the URL valid?
2. Is the image accessible?
3. Does the preview div exist?

**Solution:**
The preview should be created automatically. If not, check console for errors.

---

## 🔧 Manual Test

If automatic debugging doesn't work, try this manual test:

### Open Console and Type:

```javascript
// Test 1: Check if jQuery is loaded
jQuery

// Test 2: Check if wp.media is loaded
wp.media

// Test 3: Try to open media library manually
var frame = wp.media({
    title: 'Test',
    button: { text: 'Select' },
    multiple: false
});
frame.open();
```

### Expected Results:

**Test 1:** Should show jQuery function, not "undefined"  
**Test 2:** Should show object, not "undefined"  
**Test 3:** Media library should open

### If Test 2 Fails (wp.media is undefined):

**This is the root problem!** WordPress media library isn't loaded.

**Fix:**
1. Make sure you're on the plugin page
2. Check `admin_enqueue_scripts` hook
3. Verify `wp_enqueue_media()` is called

---

## ✅ Success Indicators

You'll know it's working when:

1. ✅ Console shows initialization messages
2. ✅ Console shows "wp.media available: true"
3. ✅ Clicking button shows "Upload button clicked!"
4. ✅ Media library opens
5. ✅ Selecting image fills URL
6. ✅ Preview appears
7. ✅ No errors in console

---

## 📊 Checklist

Use this to verify each step:

- [ ] Hard refreshed page (Ctrl+Shift+R)
- [ ] Opened browser console (F12)
- [ ] Went to Page Builder → Add New
- [ ] Saw initialization messages in console
- [ ] Saw "wp.media available: true"
- [ ] Added a section (+ Content Block)
- [ ] Saw upload button
- [ ] Clicked upload button
- [ ] Saw "Upload button clicked!" in console
- [ ] Media library opened
- [ ] Selected/uploaded image
- [ ] Clicked "Use This Image"
- [ ] URL filled in text field
- [ ] Preview appeared
- [ ] No errors in console

**If all checked:** ✅ **IT'S WORKING!**

---

## 🆘 Still Not Working?

### Last Resort Checks:

1. **WordPress Version:** Make sure you're on WordPress 5.0+
2. **Theme Conflicts:** Try switching to a default theme (Twenty Twenty-Four)
3. **Plugin Conflicts:** Temporarily deactivate other plugins
4. **JavaScript Errors:** Check for ANY red errors in console
5. **Browser:** Try a different browser (Chrome, Firefox, Edge)

### Get More Info:

Run this in console:
```javascript
console.log('jQuery version:', jQuery.fn.jquery);
console.log('wp object:', typeof wp);
console.log('wp.media:', typeof wp.media);
console.log('Upload buttons found:', jQuery('.upload-image-btn').length);
```

Copy the output and check:
- jQuery version should be 1.12+ or 3.0+
- wp object should be "object"
- wp.media should be "function"
- Upload buttons found should be > 0

---

## 📝 Report Template

If you need to report an issue, provide this info:

```
Browser: [Chrome/Firefox/Edge/Safari]
WordPress Version: [5.x/6.x]
Console Messages: [Copy all messages]
Errors: [Copy any red errors]

Checklist Results:
- wp.media available: [true/false]
- Upload button clicked: [yes/no]
- Media library opened: [yes/no]
- URL filled: [yes/no]
- Preview showed: [yes/no]

Additional Notes:
[Any other observations]
```

---

**Last Updated:** October 30, 2025  
**Purpose:** Debug upload button functionality  
**Expected Result:** Upload button should open media library
