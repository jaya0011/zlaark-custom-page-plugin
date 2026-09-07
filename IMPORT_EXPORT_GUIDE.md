# Import/Export Guide for Custom Page Builder

## Overview
The Custom Page Builder now includes import and export functionality, allowing you to easily backup, share, and migrate pages between WordPress installations.

## Features

### Export Pages
- Export individual pages to JSON format
- Includes all page data: title, slug, status, sections, and content
- Preserves all section types and configurations
- Maintains WooCommerce category and tag associations

### Import Pages
- Import pages from JSON files
- Option to update existing pages or create new ones
- Supports both single page and bulk imports
- Validates data before importing

## How to Use

### Exporting a Page

1. Go to **Page Builder > All Pages** in your WordPress admin
2. Find the page you want to export
3. Click the **Export** link next to the page
4. A JSON file will automatically download to your computer
5. The filename will be in the format: `page-slug-export.json`

### Importing a Page

1. Go to **Page Builder > All Pages** in your WordPress admin
2. Click the **Import Page** button at the top
3. Click **Choose File** and select your JSON export file
4. (Optional) Check **Update existing pages** if you want to overwrite pages with matching slugs
5. Click **Import**
6. You'll see a success message with details about the import
7. The page list will automatically refresh

### Import Options

**Update existing pages**: When checked, if a page with the same slug already exists, it will be updated with the imported data. When unchecked, a new page will be created with a unique slug (e.g., `page-slug-1`, `page-slug-2`).

## File Format

Export files are in JSON format and contain:

```json
{
  "version": "1.0.0",
  "export_date": "2024-01-01 12:00:00",
  "page": {
    "title": "Page Title",
    "slug": "page-slug",
    "status": "published",
    "language": "en",
    "sections": [
      // Section data...
    ],
    "created_at": "2024-01-01 10:00:00",
    "updated_at": "2024-01-01 11:00:00"
  }
}
```

For bulk exports (multiple pages):

```json
{
  "version": "1.0.0",
  "export_date": "2024-01-01 12:00:00",
  "pages": [
    {
      "title": "Page 1",
      // Page data...
    },
    {
      "title": "Page 2",
      // Page data...
    }
  ]
}
```

## Use Cases

### Backup
- Export your pages regularly as backups
- Store JSON files in version control
- Quick recovery if something goes wrong

### Migration
- Move pages between development, staging, and production
- Transfer pages to different WordPress installations
- Share page templates with team members

### Templates
- Create reusable page templates
- Build a library of common page layouts
- Share designs with clients or colleagues

## Important Notes

1. **Images**: Image references are preserved, but the actual image files are not included in the export. You'll need to ensure images exist in the target installation.

2. **WooCommerce Data**: Category and tag IDs are preserved. Ensure the same categories/tags exist in the target installation, or they'll need to be recreated.

3. **Unique Slugs**: If importing without the "update existing" option, the system will automatically generate unique slugs to avoid conflicts.

4. **Language Support**: If using Polylang, language codes are preserved in the export/import.

## Troubleshooting

### Import fails with "Invalid JSON file"
- Ensure the file is a valid JSON export from Custom Page Builder
- Check that the file wasn't corrupted during transfer
- Try opening the file in a text editor to verify it's valid JSON

### Page imports but looks different
- Check that all required images exist in the media library
- Verify WooCommerce categories and tags are set up correctly
- Ensure the theme and plugins are the same on both installations

### "Page not found" error on export
- The page may have been deleted
- Refresh the page list and try again

## Technical Details

### AJAX Endpoints
- `cpb_export_page`: Exports a single page
- `cpb_import_page`: Imports pages from JSON file

### Security
- All operations require `manage_options` capability
- Nonce verification on all AJAX requests
- File type validation (JSON only)
- Data sanitization on import

### Filters and Hooks
The import/export system can be extended using WordPress filters:

```php
// Modify export data before download
add_filter('cpb_export_data', function($data, $page_id) {
    // Your modifications
    return $data;
}, 10, 2);

// Modify import data before saving
add_filter('cpb_import_data', function($data) {
    // Your modifications
    return $data;
}, 10, 1);
```

## Support

For issues or questions about import/export functionality, please check:
1. WordPress error logs
2. Browser console for JavaScript errors
3. The main README.md for general plugin support
