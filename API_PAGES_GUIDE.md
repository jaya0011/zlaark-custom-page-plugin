# Custom Page Builder API Guide

## How It Works

When you create pages in the Custom Page Builder, they automatically become accessible via REST API using their slug.

### API URL Pattern
```
https://api.dhawada.com/wp-json/custom-page-builder/v1/pages/[SLUG]
```

### Examples
- Page with slug "home" → `https://api.dhawada.com/wp-json/custom-page-builder/v1/pages/home`
- Page with slug "about" → `https://api.dhawada.com/wp-json/custom-page-builder/v1/pages/about`
- Page with slug "contact-us" → `https://api.dhawada.com/wp-json/custom-page-builder/v1/pages/contact-us`

## Creating API-Accessible Pages

### Step 1: Create a New Page
1. Go to **WordPress Admin → Page Builder → Add New**
2. Fill in the required fields:
   - **Page Title**: Display name (e.g., "Home Page")
   - **Slug**: URL-friendly identifier (e.g., "home")
   - **Status**: Set to "Published" for API access

### Step 2: Add Content Sections
1. Click the section buttons to add content:
   - **+ Hero Slider** - For image carousels
   - **+ Content Block** - For text and mixed content
   - **+ Testimonials** - For customer reviews
   - **+ Product Grid** - For product showcases
   - **+ Custom Section** - For flexible content

### Step 3: Save and Get API URL
1. Click **"Create Page"** or **"Update Page"**
2. You'll see a success message with the API URL
3. Click the API URL to test it

## API Response Format

### Successful Response (200)
```json
{
  "success": true,
  "page": {
    "id": 1,
    "title": "Home Page",
    "slug": "home",
    "status": "published",
    "sections": "[{\"type\":\"content\",\"title\":\"Welcome\",\"content\":\"Welcome to our site\"}]",
    "created_at": "2024-01-01 12:00:00",
    "updated_at": "2024-01-01 12:00:00"
  }
}
```

### Page Not Found (404)
```json
{
  "success": false,
  "message": "Page not found",
  "slug_requested": "nonexistent-page"
}
```

## Available API Endpoints

### 1. Get All Published Pages
```
GET /wp-json/custom-page-builder/v1/pages/
```
Returns a list of all published pages.

### 2. Get Specific Page by Slug
```
GET /wp-json/custom-page-builder/v1/pages/{slug}
```
Returns a specific page by its slug.

### 3. Get Specific Page by ID
```
GET /wp-json/custom-page-builder/v1/pages/{id}
```
Returns a specific page by its numeric ID.

## Slug Rules

### Valid Slug Characters
- Letters (a-z, A-Z)
- Numbers (0-9)
- Hyphens (-)
- Underscores (_)

### Slug Examples
- ✅ `home`
- ✅ `about-us`
- ✅ `contact_page`
- ✅ `product-123`
- ❌ `home page` (spaces not allowed)
- ❌ `about@us` (special characters not allowed)

### Automatic Slug Generation
- If you leave the slug empty, it's generated from the title
- If a slug already exists, a number is appended (e.g., `home-2`)

## Testing Your API

### Method 1: Use the Test Script
Visit: `https://api.dhawada.com/wp-content/plugins/custom-page-builder/test-api-pages.php`

This will show:
- All pages in your database
- Their API URLs
- Test each endpoint
- Detailed response data

### Method 2: Direct Browser Test
1. Create a page with slug "home"
2. Set status to "Published"
3. Visit: `https://api.dhawada.com/wp-json/custom-page-builder/v1/pages/home`
4. You should see JSON data

### Method 3: Using curl
```bash
curl https://api.dhawada.com/wp-json/custom-page-builder/v1/pages/home
```

## Troubleshooting

### Issue: "Page not found" (404)
**Causes:**
- Page doesn't exist in database
- Page status is not "published"
- Slug doesn't match exactly

**Solutions:**
1. Check if page exists in Page Builder admin
2. Verify status is set to "Published"
3. Check slug spelling (case-sensitive)

### Issue: Empty or missing sections
**Causes:**
- No sections were added to the page
- Sections weren't saved properly
- Database corruption

**Solutions:**
1. Edit the page and add sections
2. Save the page again
3. Check WordPress error logs

### Issue: API returns error
**Causes:**
- WordPress REST API disabled
- Plugin not activated
- Database table missing

**Solutions:**
1. Check if other WP REST API endpoints work
2. Reactivate the plugin
3. Check WordPress error logs

## Security Notes

- Only **published** pages are accessible via API
- **Draft** and **archived** pages are not exposed
- No authentication required for reading published pages
- All data is properly sanitized and escaped

## Integration Examples

### JavaScript/React
```javascript
fetch('https://api.dhawada.com/wp-json/custom-page-builder/v1/pages/home')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Page data:', data.page);
      // Process sections
      const sections = JSON.parse(data.page.sections);
      sections.forEach(section => {
        console.log('Section:', section.type, section.title);
      });
    }
  });
```

### PHP
```php
$response = wp_remote_get('https://api.dhawada.com/wp-json/custom-page-builder/v1/pages/home');
$data = json_decode(wp_remote_retrieve_body($response), true);

if ($data['success']) {
    $page = $data['page'];
    $sections = json_decode($page['sections'], true);
    // Process page data
}
```

## Best Practices

1. **Use descriptive slugs** - Make them meaningful and SEO-friendly
2. **Keep slugs short** - Easier to remember and type
3. **Use consistent naming** - Follow a pattern across your site
4. **Test after creation** - Always verify the API URL works
5. **Monitor error logs** - Check for any API-related issues

Your pages are now accessible via API! Each page you create with a unique slug will have its own API endpoint.