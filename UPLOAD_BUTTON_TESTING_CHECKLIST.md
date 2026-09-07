# ✅ Upload Button Testing Checklist

## 🔍 Verify Everything Works

Use this checklist to make sure the upload button and text field are both working properly.

---

## ✅ Pre-Test Checklist

Before testing, make sure:

- [ ] You've refreshed your browser (Ctrl+Shift+R or Cmd+Shift+R)
- [ ] You're logged into WordPress as an administrator
- [ ] You're on the Custom Page Builder page
- [ ] JavaScript is enabled in your browser
- [ ] No JavaScript errors in console (Press F12 → Console tab)

---

## 🧪 Test 1: Upload Button Functionality

### Steps:
1. Go to **WordPress Admin** → **Page Builder** → **Add New**
2. Click **"+ Content Block"** button
3. Look for **"Section Image:"** field
4. You should see:
   - ✅ **"📁 Upload Image"** button
   - ✅ **"Or enter image URL:"** label
   - ✅ Text input field below

### Test the Upload Button:
5. Click the **"📁 Upload Image"** button
6. **Expected:** WordPress Media Library opens in a popup
7. Click **"Upload Files"** tab
8. Drag and drop an image OR click **"Select Files"**
9. Choose an image from your computer
10. Click **"Use Image"** button
11. **Expected Results:**
    - ✅ Media Library closes
    - ✅ Image URL appears in the text field
    - ✅ Image preview appears below the text field
    - ✅ Preview shows your image (max 200px wide)

### ✅ Pass Criteria:
- [ ] Upload button opens media library
- [ ] Can select/upload image
- [ ] URL fills in text field automatically
- [ ] Preview appears
- [ ] Preview shows correct image

---

## 🧪 Test 2: Manual URL Entry

### Steps:
1. In the same section, clear the text field (if filled)
2. Type or paste an image URL:
   ```
   https://via.placeholder.com/400x300
   ```
3. Press **Tab** or click outside the field
4. **Expected Results:**
    - ✅ Image preview appears below
    - ✅ Preview shows the placeholder image
    - ✅ No errors in console

### Test with Different URLs:
5. Try another URL:
   ```
   https://picsum.photos/400/300
   ```
6. Press Tab
7. **Expected:** Preview updates with new image

### ✅ Pass Criteria:
- [ ] Can type URL manually
- [ ] Preview appears after Tab/blur
- [ ] Preview updates when URL changes
- [ ] Works with different image URLs

---

## 🧪 Test 3: Both Methods Together

### Steps:
1. Upload an image using the button
2. **Expected:** URL fills, preview shows
3. Edit the URL manually (change part of it)
4. Press Tab
5. **Expected:** Preview updates to new URL
6. Click upload button again
7. Select a different image
8. **Expected:** URL and preview update to new image

### ✅ Pass Criteria:
- [ ] Can switch between upload and manual entry
- [ ] Each method updates the field correctly
- [ ] Preview always matches the current URL
- [ ] No conflicts between methods

---

## 🧪 Test 4: Multiple Sections

### Steps:
1. Click **"+ Content Block"** again to add another section
2. Each section should have its own:
   - ✅ Upload button
   - ✅ Text field
   - ✅ Preview area
3. Upload different images in each section
4. **Expected:** Each section maintains its own image

### ✅ Pass Criteria:
- [ ] Multiple sections work independently
- [ ] Each has its own upload button
- [ ] Images don't mix between sections
- [ ] All previews show correctly

---

## 🧪 Test 5: Hero Slider (Multiple Slides)

### Steps:
1. Click **"+ Hero Slider (Multiple Slides)"**
2. First slide should have image upload
3. Click **"+ Add Another Slide"**
4. Second slide should also have image upload
5. Upload different images for each slide
6. **Expected:** Each slide has its own image

### ✅ Pass Criteria:
- [ ] Each slide has upload button
- [ ] Each slide has text field
- [ ] Can upload different images per slide
- [ ] Previews show correctly for each slide

---

## 🧪 Test 6: Save and Reload

### Steps:
1. Upload images in 2-3 sections
2. Fill in titles and content
3. Click **"Create Page"** at the bottom
4. **Expected:** "Page saved successfully" message
5. Refresh the page or go back to edit
6. **Expected Results:**
    - ✅ All images are still there
    - ✅ URLs are in text fields
    - ✅ Previews show correctly
    - ✅ Can edit/replace images

### ✅ Pass Criteria:
- [ ] Images save correctly
- [ ] URLs persist after save
- [ ] Previews show on reload
- [ ] Can edit saved images

---

## 🧪 Test 7: Error Handling

### Test Invalid URL:
1. Type an invalid URL: `not-a-url`
2. Press Tab
3. **Expected:** No preview or error message

### Test Empty Field:
1. Clear the text field
2. Press Tab
3. **Expected:** Preview disappears

### Test Media Library Cancel:
1. Click upload button
2. Click X or Cancel in media library
3. **Expected:** Nothing changes, no errors

### ✅ Pass Criteria:
- [ ] Invalid URLs don't break the page
- [ ] Empty field clears preview
- [ ] Canceling upload doesn't cause errors
- [ ] No JavaScript errors in console

---

## 🧪 Test 8: Browser Compatibility

Test in different browsers:

### Chrome/Edge:
- [ ] Upload button works
- [ ] Text field works
- [ ] Preview shows

### Firefox:
- [ ] Upload button works
- [ ] Text field works
- [ ] Preview shows

### Safari (if available):
- [ ] Upload button works
- [ ] Text field works
- [ ] Preview shows

---

## 🐛 Troubleshooting Guide

### Issue: Upload button doesn't open media library

**Check:**
1. Press F12 → Console tab
2. Look for errors mentioning "wp.media"
3. **Solution:** Hard refresh (Ctrl+Shift+R)

**If still not working:**
1. Check if you're on the correct page (Page Builder → Add New)
2. Make sure you're logged in as admin
3. Try a different browser

### Issue: Preview doesn't show

**Check:**
1. Is the URL valid? (starts with http:// or https://)
2. Is the image accessible? (try opening URL in new tab)
3. Press F12 → Console for errors

**Solution:**
- Make sure URL is complete
- Check image file exists
- Try a different image URL

### Issue: Text field doesn't fill after upload

**Check:**
1. Did you click "Use Image" in media library?
2. Check console for JavaScript errors
3. **Solution:** Refresh page and try again

### Issue: Multiple sections interfere with each other

**Check:**
1. Each section should have unique IDs
2. Check console for errors
3. **Solution:** This shouldn't happen - report if it does

---

## ✅ Final Verification

After all tests, verify:

- [ ] ✅ Upload button opens media library
- [ ] ✅ Can select/upload images
- [ ] ✅ URL fills automatically after upload
- [ ] ✅ Can type URL manually
- [ ] ✅ Preview shows for both methods
- [ ] ✅ Multiple sections work independently
- [ ] ✅ Images save and persist
- [ ] ✅ Can edit/replace images
- [ ] ✅ No JavaScript errors
- [ ] ✅ Works in multiple browsers

---

## 📊 Test Results Template

```
Date: _______________
Tester: _______________
Browser: _______________

Test 1 (Upload Button): ☐ Pass ☐ Fail
Test 2 (Manual URL): ☐ Pass ☐ Fail
Test 3 (Both Methods): ☐ Pass ☐ Fail
Test 4 (Multiple Sections): ☐ Pass ☐ Fail
Test 5 (Hero Slider): ☐ Pass ☐ Fail
Test 6 (Save/Reload): ☐ Pass ☐ Fail
Test 7 (Error Handling): ☐ Pass ☐ Fail
Test 8 (Browser Compat): ☐ Pass ☐ Fail

Overall: ☐ All Tests Pass ☐ Some Issues Found

Notes:
_________________________________
_________________________________
_________________________________
```

---

## 🎯 Success Criteria

**The feature is working correctly if:**

✅ Upload button opens WordPress Media Library  
✅ Can upload images from computer  
✅ URL automatically fills in text field  
✅ Can manually type/paste URLs  
✅ Preview appears for both methods  
✅ Multiple sections work independently  
✅ Images save and persist  
✅ No JavaScript errors  
✅ Works in major browsers  

**If all criteria are met:** 🎉 **FEATURE IS WORKING!**

---

**Last Updated:** October 30, 2025  
**Status:** Ready for Testing  
**Expected Result:** All tests should pass
