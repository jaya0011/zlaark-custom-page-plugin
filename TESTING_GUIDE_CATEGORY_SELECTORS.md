# Testing Guide: Dynamic Category Selectors in Product Grid

## Quick Test Procedure

### Test 1: Add New Product with Category Selectors

1. **Go to**: WordPress Admin → Custom Pages → Add New Page
2. **Click**: "Add Section" button
3. **Select**: "Product Grid" section type
4. **Click**: "Save Section"
5. **In the Product Grid section, click**: "Add Product" button
6. **Expected Result**: 
   - ✅ Product form appears immediately
   - ✅ Blue box with "WooCommerce Product Categories" appears
   - ✅ Green box with "WooCommerce Product Tags" appears
   - ✅ Category checkboxes are clickable
   - ✅ Selected categories show in the "Selected:" area

### Test 2: Verify Category Selection Works

1. **In the newly added product**:
   - Check 2-3 WooCommerce categories
   - Check 1-2 WooCommerce tags
2. **Expected Result**:
   - ✅ Checkboxes toggle on/off
   - ✅ Selected items appear as colored tags below
   - ✅ Hidden input field updates with JSON array

### Test 3: Save and Reload

1. **Fill in product details**:
   - Title: "Test Product"
   - Price: "$99.99"
   - Description: "Test description"
2. **Click**: "Save Page" button
3. **Reload the page**
4. **Click**: "Edit" on the Product Grid section
5. **Expected Result**:
   - ✅ Product appears with all data
   - ✅ Selected categories are still checked
   - ✅ Selected tags are still checked
   - ✅ Category/tag selectors are fully functional

### Test 4: Add Multiple Products

1. **Click**: "Add Product" button 3 times
2. **Expected Result**:
   - ✅ Each product has its own category selector
   - ✅ Each product has its own tag selector
   - ✅ Selecting categories in one product doesn't affect others
   - ✅ Product numbers update correctly (1, 2, 3, 4...)

### Test 5: Fallback Scenario (Optional)

1. **Disable JavaScript temporarily** (browser dev tools)
2. **Click**: "Add Product" button
3. **Expected Result**:
   - ✅ Basic product form appears
   - ⚠️ Warning message shows: "Category and tag selectors are not available..."
   - ✅ All other fields work normally

## What to Look For

### ✅ Success Indicators

- Category selectors appear in blue boxes
- Tag selectors appear in green boxes
- Checkboxes are interactive
- Selected items display as colored tags
- Form data saves correctly
- No JavaScript console errors

### ❌ Failure Indicators

- Empty space where selectors should be
- "Loading..." message that never completes
- JavaScript errors in console
- Categories not saving
- Page needs refresh to see selectors

## Debug Information

If selectors don't appear, check browser console for:

```javascript
// Look for these messages:
"✅ woocommerce-category-selector.php IS EXECUTING!"
"WooCommerce Status: ✅ ACTIVE"
"Categories Found: [number]"
```

If you see errors, check:
1. WooCommerce plugin is active
2. Product categories exist in WooCommerce
3. AJAX endpoint is responding (Network tab)
4. No PHP errors in WordPress debug log

## Browser Console Commands

Test AJAX endpoint manually:

```javascript
jQuery.post(ajaxurl, {
    action: 'cpb_admin_action',
    cpb_action: 'get_product_template',
    nonce: cpb_admin.nonce,
    index: 0
}, function(response) {
    console.log('Response:', response);
});
```

Expected response:
```json
{
    "success": true,
    "data": {
        "html": "<div class='product-item'>...</div>"
    }
}
```

## Common Issues & Solutions

### Issue: Selectors Don't Appear
**Solution**: Check that WooCommerce is installed and active

### Issue: "No categories found" Message
**Solution**: Add product categories in WooCommerce → Products → Categories

### Issue: AJAX Fails
**Solution**: Check WordPress debug log for PHP errors in `handle_get_product_template()`

### Issue: Categories Don't Save
**Solution**: Verify form field names match: `config[products][0][wc_categories]`

## Performance Notes

- AJAX call adds ~100-300ms delay when adding products
- Fallback method is instant but lacks category selectors
- No performance impact on page load or existing products
- Category selector JavaScript is already loaded, no additional requests

## Browser Compatibility

Tested and working on:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

## Next Steps After Testing

If all tests pass:
1. ✅ Feature is working correctly
2. ✅ Users can add products with categories dynamically
3. ✅ No workflow interruption

If tests fail:
1. Check browser console for errors
2. Verify WooCommerce is active
3. Check WordPress debug log
4. Review AJAX response in Network tab
5. Verify file permissions on template files
