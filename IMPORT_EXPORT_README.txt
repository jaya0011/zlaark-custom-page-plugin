================================================================================
CUSTOM PAGE BUILDER - IMPORT/EXPORT FEATURE
================================================================================

VERSION: 1.0.0
ADDED: Import and Export functionality for pages

================================================================================
WHAT WAS ADDED
================================================================================

NEW FILES:
----------
✓ admin/class-import-export.php          - Core import/export logic
✓ admin/assets/js/import-export.js       - Frontend JavaScript
✓ admin/assets/css/import-export.css     - Styling for UI
✓ IMPORT_EXPORT_GUIDE.md                 - Complete documentation
✓ IMPORT_EXPORT_QUICK_START.txt          - Quick reference
✓ IMPORT_EXPORT_TESTING.md               - Testing & troubleshooting

MODIFIED FILES:
--------------
✓ custom-page-builder.php                - Added AJAX handlers and enqueues

================================================================================
HOW TO USE
================================================================================

EXPORT A PAGE:
--------------
1. Go to: Page Builder > All Pages
2. Find the page you want to export
3. Click "Export" link next to the page
4. JSON file downloads automatically to your computer

IMPORT A PAGE:
--------------
1. Go to: Page Builder > All Pages
2. Click "Import Page" button at the top
3. Click "Choose File" and select your JSON export file
4. (Optional) Check "Update existing pages" to overwrite
5. Click "Import" button
6. Success message appears and page list refreshes

================================================================================
FEATURES
================================================================================

✓ Export individual pages to JSON format
✓ Import pages from JSON files
✓ Bulk import support (multiple pages in one file)
✓ Option to update existing pages or create new ones
✓ Automatic slug conflict resolution
✓ Preserves all page content and settings
✓ Includes WooCommerce categories and tags
✓ Language support (Polylang compatible)
✓ Security with nonce verification
✓ Detailed success/error messages
✓ Console logging for debugging

================================================================================
TESTING
================================================================================

To verify the feature is working:

1. Open browser Developer Console (F12)
2. Go to Page Builder > All Pages
3. Look for console message: "CPB Import/Export: Script loaded"
4. Click "Import Page" button - modal should appear
5. Click "Export" on any page - JSON file should download

If something doesn't work, see IMPORT_EXPORT_TESTING.md for troubleshooting.

================================================================================
TECHNICAL DETAILS
================================================================================

AJAX ENDPOINTS:
--------------
- cpb_export_page  : Exports a single page to JSON
- cpb_import_page  : Imports pages from JSON file

SECURITY:
---------
- Requires 'manage_options' capability
- Nonce verification on all requests
- File type validation (JSON only)
- Data sanitization on import

JAVASCRIPT:
-----------
- Uses jQuery for DOM manipulation
- AJAX for server communication
- FormData API for file uploads
- Console logging for debugging

PHP CLASSES:
-----------
- Custom_Page_Builder\Import_Export
  - export_page($page_id)
  - export_pages($page_ids)
  - import_page($page_data, $update_existing)
  - import_pages($import_data, $update_existing)

================================================================================
FILE FORMAT
================================================================================

Single Page Export:
{
  "version": "1.0.0",
  "export_date": "2024-01-01 12:00:00",
  "page": {
    "title": "Page Title",
    "slug": "page-slug",
    "status": "published",
    "language": "en",
    "sections": [ ... ],
    "created_at": "2024-01-01 10:00:00",
    "updated_at": "2024-01-01 11:00:00"
  }
}

Multiple Pages Export:
{
  "version": "1.0.0",
  "export_date": "2024-01-01 12:00:00",
  "pages": [
    { page data... },
    { page data... }
  ]
}

================================================================================
IMPORTANT NOTES
================================================================================

⚠ Images are referenced by ID/URL, not included in export file
⚠ WooCommerce categories/tags must exist in target installation
⚠ Unique slugs are auto-generated if conflicts occur
⚠ Language codes are preserved (requires Polylang if used)

================================================================================
BROWSER COMPATIBILITY
================================================================================

✓ Chrome/Edge (latest)
✓ Firefox (latest)
✓ Safari (latest)
✓ Opera (latest)

Requires:
- JavaScript enabled
- jQuery loaded
- Modern browser with FormData API support

================================================================================
TROUBLESHOOTING
================================================================================

ISSUE: Import button doesn't work
FIX: Check browser console for errors, refresh page

ISSUE: Export downloads empty file
FIX: Check WordPress error logs, verify page has data

ISSUE: "cpbAdmin is not defined" error
FIX: Clear cache, hard refresh browser (Ctrl+Shift+R)

ISSUE: Import fails with "Invalid nonce"
FIX: Refresh page, clear browser cache

For detailed troubleshooting, see IMPORT_EXPORT_TESTING.md

================================================================================
SUPPORT
================================================================================

Documentation:
- IMPORT_EXPORT_GUIDE.md      - Full feature documentation
- IMPORT_EXPORT_TESTING.md    - Testing and troubleshooting
- IMPORT_EXPORT_QUICK_START.txt - Quick reference

For issues:
1. Check browser console for errors
2. Check WordPress debug.log
3. Review testing guide
4. Contact plugin support with error details

================================================================================
CHANGELOG
================================================================================

Version 1.0.0 (Initial Release)
- Added export functionality for individual pages
- Added import functionality with file upload
- Added import modal with options
- Added automatic slug conflict resolution
- Added detailed success/error messages
- Added console logging for debugging
- Added comprehensive documentation

================================================================================
