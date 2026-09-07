# Import/Export Testing & Troubleshooting Guide

## Quick Test Checklist

### 1. Verify Files Are Loaded
Open your browser's Developer Console (F12) and go to the Page Builder > All Pages screen.

You should see these console messages:
```
CPB Import/Export: Script loaded
CPB Import/Export: cpbAdmin available? true
```

If you don't see these messages:
- The JavaScript file isn't loading
- Check browser console for 404 errors
- Verify file exists at: `wp-content/plugins/custom-page-builder/admin/assets/js/import-export.js`

### 2. Test Import Button
1. Go to **Page Builder > All Pages**
2. Click the **Import Page** button (next to "Add New")
3. Check console for: `CPB Import/Export: Import button clicked`
4. A modal should appear with file upload form

If the modal doesn't appear:
- Check if the modal element exists in the page HTML (inspect element)
- Look for CSS conflicts
- Check console for JavaScript errors

### 3. Test Export Button
1. Go to **Page Builder > All Pages**
2. Find any page in the list
3. Click the **Export** link next to the page
4. Check console for: `CPB Import/Export: Export button clicked`
5. A JSON file should download automatically

If export doesn't work:
- Check console for error messages
- Verify the page ID is being passed correctly
- Check Network tab for AJAX request/response

## Common Issues & Solutions

### Issue: "cpbAdmin is not defined"

**Cause**: The JavaScript localization isn't working

**Solution**:
1. Check that `wp_localize_script` is called in the plugin
2. Verify the script handle matches: `cpb-admin-script`
3. Clear WordPress cache
4. Hard refresh browser (Ctrl+Shift+R)

### Issue: Import button doesn't show modal

**Cause**: CSS or JavaScript conflict

**Solution**:
1. Check browser console for errors
2. Verify jQuery is loaded
3. Try disabling other plugins temporarily
4. Check if modal HTML exists in page source

### Issue: Export downloads empty file

**Cause**: AJAX handler not returning data correctly

**Solution**:
1. Check WordPress error logs
2. Verify page ID is valid
3. Check database for page data
4. Enable WordPress debug mode

### Issue: Import fails with "Invalid nonce"

**Cause**: Security token mismatch

**Solution**:
1. Refresh the page
2. Clear browser cache
3. Check if user is logged in
4. Verify nonce is being passed in AJAX request

## Manual Testing Steps

### Test Export Functionality

1. **Create a test page**:
   - Go to Page Builder > Add New
   - Add title: "Test Export Page"
   - Add a few sections with content
   - Save the page

2. **Export the page**:
   - Go to Page Builder > All Pages
   - Find "Test Export Page"
   - Click "Export"
   - Verify JSON file downloads

3. **Verify export content**:
   - Open the downloaded JSON file in a text editor
   - Check it contains:
     - `version` field
     - `export_date` field
     - `page` object with title, slug, sections

### Test Import Functionality

1. **Prepare for import**:
   - Use the JSON file from export test above
   - Or create a test JSON file (see format below)

2. **Import the page**:
   - Go to Page Builder > All Pages
   - Click "Import Page" button
   - Select your JSON file
   - Click "Import"
   - Wait for success message

3. **Verify import**:
   - Page list should refresh
   - New page should appear in the list
   - Open the page to verify content

### Test Update Existing

1. **Export a page**
2. **Modify the JSON file**:
   - Change the title
   - Modify some content
3. **Import with "Update existing" checked**
4. **Verify the page was updated** (not duplicated)

## Sample Test JSON File

```json
{
  "version": "1.0.0",
  "export_date": "2024-01-01 12:00:00",
  "page": {
    "title": "Test Import Page",
    "slug": "test-import-page",
    "status": "draft",
    "language": "en",
    "sections": [
      {
        "type": "hero",
        "title": "Welcome",
        "content": "This is a test page",
        "order": 0
      }
    ],
    "created_at": "2024-01-01 10:00:00",
    "updated_at": "2024-01-01 11:00:00"
  }
}
```

## Debugging Commands

### Check if files exist:
```bash
# In WordPress root directory
ls -la wp-content/plugins/*/admin/assets/js/import-export.js
ls -la wp-content/plugins/*/admin/assets/css/import-export.css
ls -la wp-content/plugins/*/admin/class-import-export.php
```

### Check WordPress error log:
```bash
tail -f wp-content/debug.log
```

### Enable WordPress debugging:
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Browser Console Commands

### Check if jQuery is loaded:
```javascript
typeof jQuery
// Should return: "function"
```

### Check if cpbAdmin is defined:
```javascript
console.log(cpbAdmin);
// Should show object with ajaxUrl and nonce
```

### Manually trigger import modal:
```javascript
jQuery('#cpb-import-modal').css('display', 'flex');
```

### Check if modal exists:
```javascript
jQuery('#cpb-import-modal').length
// Should return: 1
```

### Check if export buttons exist:
```javascript
jQuery('.cpb-export-page').length
// Should return: number of pages in list
```

## Network Tab Debugging

### For Export:
1. Open Network tab in DevTools
2. Click Export button
3. Look for request to `admin-ajax.php`
4. Check request payload:
   - action: `cpb_export_page`
   - nonce: (should be present)
   - page_id: (should be a number)
5. Check response:
   - Should have `success: true`
   - Should have `data` object with export data

### For Import:
1. Open Network tab in DevTools
2. Submit import form
3. Look for request to `admin-ajax.php`
4. Check request payload:
   - action: `cpb_import_page`
   - nonce: (should be present)
   - import_file: (file data)
5. Check response:
   - Should have `success: true`
   - Should have `message` with result

## Still Not Working?

If you've tried everything above and it's still not working:

1. **Check plugin activation**:
   - Deactivate and reactivate the plugin
   - Check for activation errors

2. **Check file permissions**:
   - Ensure web server can read the JS/CSS files
   - Check file ownership and permissions

3. **Check for conflicts**:
   - Disable all other plugins
   - Switch to a default WordPress theme
   - Test if import/export works

4. **Check WordPress version**:
   - Ensure WordPress is up to date
   - Plugin requires WordPress 5.0+

5. **Check PHP version**:
   - Plugin requires PHP 7.4+
   - Check `phpinfo()` or ask your host

6. **Review server logs**:
   - Check Apache/Nginx error logs
   - Check PHP error logs
   - Look for any fatal errors

## Success Indicators

When everything is working correctly, you should see:

✅ Import button appears on All Pages screen
✅ Clicking Import shows modal with file upload
✅ Export link appears next to each page
✅ Clicking Export downloads JSON file
✅ Importing a file shows success message
✅ Imported pages appear in the list
✅ Console shows debug messages (if enabled)
✅ No JavaScript errors in console
✅ No PHP errors in logs

## Contact Support

If you're still experiencing issues after following this guide:

1. Collect the following information:
   - WordPress version
   - PHP version
   - Browser and version
   - Console error messages
   - Network tab screenshots
   - WordPress error log entries

2. Check the main plugin documentation
3. Review the IMPORT_EXPORT_GUIDE.md file
4. Contact plugin support with the collected information
