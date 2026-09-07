# Section Disappearing Issue - Fix Applied

## Problems Identified

1. **Sections not opening immediately** - JavaScript timing issues
2. **Data disappearing after Update** - JSON decoding issues with stripslashes

## Root Cause

The main issue was in how we handled the `stripslashes()` function on JSON data. When we applied `stripslashes()` to the sections JSON before decoding, it could corrupt valid JSON if the slashes weren't actually escape slashes but part of the JSON structure.

## Fixes Applied

### 1. Smart JSON Decoding
**File:** `custom-page-builder.php` (lines ~770-790)

**Before:**
```php
$sections = $page && $page->sections ? json_decode(stripslashes($page->sections), true) : array();
```

**After:**
```php
// Handle sections JSON carefully - only stripslashes if needed
$sections = array();
if ($page && $page->sections) {
    // First try to decode without stripslashes
    $decoded = json_decode($page->sections, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $sections = $decoded;
    } else {
        // If that fails, try with stripslashes
        $decoded = json_decode(stripslashes($page->sections), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $sections = $decoded;
        } else {
            // If both fail, log the error and use empty array
            error_log('Custom Page Builder: Failed to decode sections JSON');
            $sections = array();
        }
    }
}
```

### 2. Enhanced Debugging
Added comprehensive logging to track:
- Section count during rendering
- Form data processing
- Database save operations
- JavaScript section index synchronization

### 3. JavaScript Index Synchronization
Added automatic correction of section index when DOM and JavaScript get out of sync:

```javascript
// Update section index based on actual DOM elements
const actualSectionCount = container.querySelectorAll('.section-item').length;
if (actualSectionCount !== sectionIndex) {
    console.log('Section index mismatch. Updating from', sectionIndex, 'to', actualSectionCount);
    sectionIndex = actualSectionCount;
}
```

## How the Fix Works

### JSON Decoding Strategy
1. **First attempt:** Try to decode JSON as-is (most common case)
2. **Second attempt:** If that fails, try with `stripslashes()` (for corrupted data)
3. **Fallback:** If both fail, use empty array and log error

### Data Flow Protection
1. **Form submission:** Sections are properly sanitized and saved
2. **Data retrieval:** JSON is carefully decoded without corruption
3. **Form display:** Individual field values get `stripslashes()` for display
4. **JavaScript sync:** Section count stays accurate

## Testing Steps

### Test 1: Basic Section Creation
1. Go to Add New Page
2. Click "+ Content Block"
3. **Expected:** Section should appear immediately
4. Fill in title and content
5. Click "Update Page"
6. **Expected:** Data should be saved and remain visible

### Test 2: Multiple Sections
1. Add 3 different section types
2. Fill in data for each
3. Save the page
4. **Expected:** All sections and data should persist

### Test 3: Apostrophe Handling
1. Add a section with title: `Men's Jewelry`
2. Save multiple times
3. **Expected:** No accumulating backslashes

### Test 4: Existing Pages
1. Edit an existing page with sections
2. **Expected:** All existing data should load correctly
3. Make changes and save
4. **Expected:** Changes should persist without data loss

## Debug Information

### Check WordPress Error Log
Look for these messages in your WordPress error log:
- `"Custom Page Builder: Rendering X sections"`
- `"Custom Page Builder: Processing X sections from form data"`
- `"Custom Page Builder: Saving X sections to database"`
- `"Custom Page Builder: Failed to decode sections JSON"` (indicates corruption)

### Browser Console Messages
When adding sections, you should see:
- `"Initial section count: X"`
- `"Button clicked"`
- `"cpbAddSection called with type: [type]"`
- `"Section successfully added to DOM"`

## Troubleshooting

### If Sections Still Don't Appear
1. Check browser console for JavaScript errors
2. Verify WordPress error log for JSON decode errors
3. Try refreshing the page (Ctrl+F5)
4. Test with a simple Content Block first

### If Data Still Disappears
1. Check error log for "Failed to decode sections JSON"
2. This indicates database corruption - may need to recreate affected pages
3. Verify form is submitting correctly (check Network tab in browser)

### If Apostrophes Still Accumulate
1. The individual field `stripslashes()` fixes should handle this
2. Check if the issue is in new sections vs existing sections
3. May need to manually clean up corrupted existing data

## Files Modified
- `custom-page-builder.php` - Main plugin file with JSON handling and debugging

## Prevention
- The smart JSON decoding prevents future corruption
- Enhanced logging helps identify issues early
- JavaScript synchronization prevents index mismatches

The fix addresses both the immediate display issues and the underlying data persistence problems while maintaining backward compatibility with existing data.