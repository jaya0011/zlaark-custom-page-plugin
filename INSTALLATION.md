# Custom Page Builder - Installation Guide

## 📦 Clean Plugin Structure

Your plugin has been cleaned and is now production-ready with only essential files.

## 🚀 Installation Methods

### Method 1: Copy to WordPress Plugins Directory

1. **Locate your WordPress installation:**
   - XAMPP: `C:\xampp\htdocs\[your-site]\`
   - WAMP: `C:\wamp\www\[your-site]\`
   - Laragon: `C:\laragon\www\[your-site]\`

2. **Copy the entire plugin folder:**
   ```
   Copy: Zlaark_custom-page
   To: [WordPress]\wp-content\plugins\custom-page-builder\
   ```

3. **Activate in WordPress:**
   - Go to WordPress Admin → Plugins
   - Find "Custom Page Builder"
   - Click "Activate"

### Method 2: ZIP Upload

1. **Create a ZIP file:**
   - Right-click on `Zlaark_custom-page` folder
   - Select "Send to" → "Compressed (zipped) folder"
   - Rename to `custom-page-builder.zip`

2. **Upload to WordPress:**
   - WordPress Admin → Plugins → Add New
   - Click "Upload Plugin"
   - Choose `custom-page-builder.zip`
   - Click "Install Now"
   - Click "Activate Plugin"

## ✅ Plugin Structure

```
custom-page-builder/
├── custom-page-builder.php    # Main plugin file
├── index.php                   # Security file
├── composer.json               # Dependencies
├── README.md                   # Documentation
├── admin/                      # Admin interface
├── api/                        # REST API
├── includes/                   # Core classes
├── integrations/               # Integrations
├── models/                     # Data models
├── templates/                  # Template files
├── tests/                      # PHPUnit tests
├── logs/                       # Error logs
├── uploads/                    # Upload directory
└── vendor/                     # Composer dependencies
```

## 🎯 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## 🔧 After Activation

The plugin will:
- ✅ Create necessary database tables
- ✅ Add "Page Builder" menu to WordPress admin
- ✅ Initialize all components
- ✅ Be ready to use

## 📖 Usage

After activation:
1. Go to WordPress Admin → Page Builder
2. Create your first custom page
3. Add sections (Hero Banner, Product Grid, Testimonials, etc.)
4. Publish and view on frontend

## 🆘 Troubleshooting

### Plugin doesn't appear in WordPress
- Ensure you copied to `wp-content/plugins/` directory
- Check folder name is `custom-page-builder`
- Verify `custom-page-builder.php` exists in the folder

### "Invalid header" error
- Make sure you're activating from WordPress plugins directory
- Not from Desktop or other location
- WordPress must be able to read the plugin file

### Activation errors
- Check PHP version (must be 7.4+)
- Check WordPress version (must be 5.0+)
- Check error logs in `wp-content/debug.log`

## 📞 Support

For issues or questions, check:
- Plugin README.md
- WordPress error logs
- PHP error logs

---

**Plugin Version:** 1.0.0  
**Last Updated:** October 28, 2025
