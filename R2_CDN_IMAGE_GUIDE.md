# R2 CDN Signed URL Integration Guide

## Overview
The Custom Page Builder now supports Cloudflare R2 CDN with signed URLs for secure image delivery. This approach eliminates the need for authentication while maintaining security through time-limited signed URLs.

## Image Fields

### Main Product/Section Image
Use the `image_url` field for the primary image:

```json
{
  "type": "products",
  "products": [
    {
      "title": "Product Name",
      "image_url": "https://your-r2-bucket.r2.dev/images/product.jpg?X-Amz-Algorithm=...",
      "description": "Product description"
    }
  ]
}
```

### Gallery Images
Use the `gallery_urls` array for multiple images:

```json
{
  "gallery_urls": [
    "https://your-r2-bucket.r2.dev/images/gallery1.jpg?X-Amz-Signature=...",
    "https://your-r2-bucket.r2.dev/images/gallery2.jpg?X-Amz-Signature=...",
    "https://your-r2-bucket.r2.dev/images/gallery3.jpg?X-Amz-Signature=..."
  ]
}
```

## Backward Compatibility
The plugin still supports the legacy `image` field for:
- WordPress attachment IDs
- Direct image URLs
- Secure image handler integration

When both `image_url` and `image` are present, `image_url` takes priority.

## API Response Format

### Page Data with R2 CDN
```json
{
  "success": true,
  "page": {
    "id": 1,
    "title": "Home",
    "sections": [
      {
        "type": "hero",
        "image_url": "https://r2.dev/hero.jpg?X-Amz-...",
        "gallery_urls": [
          "https://r2.dev/img1.jpg?X-Amz-...",
          "https://r2.dev/img2.jpg?X-Amz-..."
        ]
      }
    ],
    "cdn_info": {
      "uses_signed_urls": true,
      "url_refresh_notice": "R2 CDN signed URLs are self-contained and secure. Re-fetch page data if images fail to load (URLs may have expired).",
      "image_fields": {
        "main_image": "image_url",
        "gallery": "gallery_urls",
        "legacy_support": "image field still supported"
      }
    }
  }
}
```

## Handling URL Expiration

### Detection
Signed URLs have built-in expiration. When images fail to load:
1. Re-fetch the page data from the API
2. The new response will contain fresh signed URLs
3. Update your frontend state with the new URLs

### Example Frontend Code (React)
```javascript
const [pageData, setPageData] = useState(null);
const [lastFetch, setLastFetch] = useState(Date.now());

// Fetch page data
const fetchPage = async (slug, lang) => {
  const response = await fetch(
    `/wp-json/custom-page-builder/v1/pages/${slug}?lang=${lang}`
  );
  const data = await response.json();
  setPageData(data.page);
  setLastFetch(Date.now());
};

// Handle image load error
const handleImageError = (e) => {
  const timeSinceLastFetch = Date.now() - lastFetch;
  
  // If last fetch was more than 5 minutes ago, refresh
  if (timeSinceLastFetch > 5 * 60 * 1000) {
    console.log('Signed URL may have expired, refreshing page data...');
    fetchPage(currentSlug, currentLang);
  }
};

// Render
<img 
  src={section.image_url} 
  onError={handleImageError}
  alt={section.title}
/>
```

### Example Frontend Code (JavaScript)
```javascript
// Automatic refresh on image error
document.querySelectorAll('img[data-r2-cdn]').forEach(img => {
  img.addEventListener('error', async function() {
    const slug = this.dataset.slug;
    const lang = this.dataset.lang;
    
    console.log('R2 signed URL expired, refreshing...');
    
    const response = await fetch(
      `/wp-json/custom-page-builder/v1/pages/${slug}?lang=${lang}`
    );
    const data = await response.json();
    
    // Find the corresponding section and update image
    const sectionId = this.dataset.sectionId;
    const newSection = data.page.sections.find(s => s.id === sectionId);
    if (newSection && newSection.image_url) {
      this.src = newSection.image_url;
    }
  });
});
```

## Saving Pages with R2 Images

### Via WordPress Admin
When creating/editing a page, you can now paste R2 signed URLs directly into image fields. The plugin will:
1. Detect R2 CDN URLs automatically
2. Store them in the `image_url` and `gallery_urls` fields
3. Maintain backward compatibility with the `image` field

### Via REST API
```bash
# Create/Update page with R2 CDN images
curl -X POST \
  'https://yoursite.com/wp-json/custom-page-builder/v1/pages' \
  -H 'Content-Type: application/json' \
  -d '{
    "title": "Product Page",
    "slug": "products",
    "language": "en",
    "sections": [
      {
        "type": "products",
        "products": [
          {
            "title": "Product 1",
            "image_url": "https://r2.dev/product1.jpg?X-Amz-...",
            "gallery_urls": [
              "https://r2.dev/gallery1.jpg?X-Amz-...",
              "https://r2.dev/gallery2.jpg?X-Amz-..."
            ]
          }
        ]
      }
    ]
  }'
```

## Security Features

### Self-Contained Security
- R2 signed URLs contain the signature in the URL itself
- No additional authentication needed
- URLs are time-limited for security
- Cannot be tampered with without invalidating the signature

### No Backend Authentication
Unlike the legacy secure image handler, R2 signed URLs:
- Don't require WordPress authentication
- Work from any origin (good for CDN/caching)
- Are perfect for public-facing pages
- Automatically expire after the configured time

## Best Practices

1. **URL Expiration Time**: Set R2 signed URL expiration to match your expected page cache duration (e.g., 1-24 hours)

2. **Automatic Refresh**: Implement automatic refresh logic in your frontend when images fail to load

3. **Fallback Images**: Consider showing a placeholder while fetching fresh URLs

4. **Caching Strategy**: 
   - Cache page data for a shorter duration than URL expiration
   - Re-fetch before URLs expire
   - Use `updated_at` timestamp to detect stale data

5. **Error Handling**: Always handle image load errors gracefully

## Migration from Legacy Images

The plugin automatically supports both formats:

```json
// Old format (still works)
{
  "image": "123",  // WordPress attachment ID
  "image": "https://site.com/image.jpg"  // Direct URL
}

// New format (preferred for R2)
{
  "image_url": "https://r2.dev/image.jpg?X-Amz-...",  // R2 signed URL
  "gallery_urls": ["https://r2.dev/img1.jpg?X-Amz-..."]
}

// Mixed (both will work)
{
  "image": "https://site.com/fallback.jpg",  // Fallback
  "image_url": "https://r2.dev/image.jpg?X-Amz-..."  // Primary
}
```

## Troubleshooting

### Images Not Loading
1. Check if the signed URL has expired
2. Re-fetch page data from the API
3. Verify R2 bucket CORS settings
4. Check browser console for errors

### URL Format Issues
- Ensure URLs are properly URL-encoded
- Don't strip query parameters (they contain the signature)
- Preserve the exact URL format from R2

### API Issues
- Verify the `image_url` and `gallery_urls` fields are being sent
- Check API response includes `cdn_info` metadata
- Ensure URLs are valid and accessible

## Support

For issues or questions:
1. Check the browser console for errors
2. Verify R2 signed URLs are correctly generated
3. Test URLs directly in browser
4. Check WordPress error logs
