# Custom Page Builder REST API Documentation

## Overview

The Custom Page Builder plugin provides REST API endpoints for accessing custom pages and their sections. The API is designed for consumption by React frontends and other external applications.

## Base URL

```
/wp-json/custom-pages/v1/
```

## Authentication

- Public endpoints (published pages): No authentication required
- Draft/archived pages: Requires WordPress authentication with `edit_pages` capability

## Endpoints

### 1. Get Pages Collection

**Endpoint:** `GET /wp-json/custom-pages/v1/pages`

**Description:** Retrieve a list of custom pages with optional filtering and pagination.

**Parameters:**
- `status` (string, optional): Filter by page status. Values: `draft`, `published`, `archived`. Default: `published`
- `search` (string, optional): Search in page titles and slugs
- `page` (integer, optional): Page number for pagination. Default: `1`
- `per_page` (integer, optional): Number of items per page. Default: `10`, Max: `100`
- `orderby` (string, optional): Sort by field. Values: `created_at`, `updated_at`, `published_at`, `title`. Default: `updated_at`
- `order` (string, optional): Sort order. Values: `ASC`, `DESC`. Default: `DESC`

**Example Request:**
```
GET /wp-json/custom-pages/v1/pages?status=published&per_page=5&search=hero
```

**Example Response:**
```json
[
  {
    "id": 1,
    "title": "Homepage Hero",
    "slug": "homepage-hero",
    "status": "published",
    "created_at": "2023-10-01T10:00:00+00:00",
    "updated_at": "2023-10-01T15:30:00+00:00",
    "published_at": "2023-10-01T15:30:00+00:00",
    "author": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com"
    },
    "meta_data": {},
    "sections": [],
    "url": "https://example.com/custom-page/homepage-hero"
  }
]
```

**Response Headers:**
- `X-WP-Total`: Total number of pages
- `X-WP-TotalPages`: Total number of pages for pagination

### 2. Get Single Page by ID

**Endpoint:** `GET /wp-json/custom-pages/v1/pages/{id}`

**Description:** Retrieve a single page with all its sections and secure image URLs.

**Parameters:**
- `id` (integer, required): Page ID

**Example Request:**
```
GET /wp-json/custom-pages/v1/pages/1
```

**Example Response:**
```json
{
  "id": 1,
  "title": "Homepage Hero",
  "slug": "homepage-hero",
  "status": "published",
  "created_at": "2023-10-01T10:00:00+00:00",
  "updated_at": "2023-10-01T15:30:00+00:00",
  "published_at": "2023-10-01T15:30:00+00:00",
  "author": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com"
  },
  "meta_data": {},
  "sections": [
    {
      "id": 1,
      "section_type": "hero_banner",
      "section_order": 1,
      "config": {
        "title": "Welcome to Our Site",
        "subtitle": "Amazing products await",
        "background_image": 123,
        "background_image_urls": {
          "thumbnail": {
            "secure_url": "https://example.com/wp-content/plugins/secure-images/secure-image.php?token=abc123&size=thumbnail",
            "fallback_url": "https://example.com/wp-content/uploads/2023/10/hero-bg-150x150.jpg",
            "url": "https://example.com/wp-content/plugins/secure-images/secure-image.php?token=abc123&size=thumbnail"
          },
          "medium": {
            "secure_url": "https://example.com/wp-content/plugins/secure-images/secure-image.php?token=def456&size=medium",
            "fallback_url": "https://example.com/wp-content/uploads/2023/10/hero-bg-300x200.jpg",
            "url": "https://example.com/wp-content/plugins/secure-images/secure-image.php?token=def456&size=medium"
          },
          "large": {
            "secure_url": "https://example.com/wp-content/plugins/secure-images/secure-image.php?token=ghi789&size=large",
            "fallback_url": "https://example.com/wp-content/uploads/2023/10/hero-bg-1024x683.jpg",
            "url": "https://example.com/wp-content/plugins/secure-images/secure-image.php?token=ghi789&size=large"
          },
          "full": {
            "secure_url": "https://example.com/wp-content/plugins/secure-images/secure-image.php?token=jkl012&size=full",
            "fallback_url": "https://example.com/wp-content/uploads/2023/10/hero-bg.jpg",
            "url": "https://example.com/wp-content/plugins/secure-images/secure-image.php?token=jkl012&size=full"
          }
        }
      }
    }
  ],
  "url": "https://example.com/custom-page/homepage-hero"
}
```

**Response Headers:**
- `Cache-Control`: `public, max-age=300`
- `Last-Modified`: Last modification date

### 3. Get Single Page by Slug

**Endpoint:** `GET /wp-json/custom-pages/v1/pages/slug/{slug}`

**Description:** Retrieve a single page by its slug with all sections and secure image URLs.

**Parameters:**
- `slug` (string, required): Page slug

**Example Request:**
```
GET /wp-json/custom-pages/v1/pages/slug/homepage-hero
```

**Response:** Same as single page by ID endpoint.

## Error Responses

All endpoints return consistent error responses:

```json
{
  "code": "page_not_found",
  "message": "Page not found.",
  "data": {
    "status": 404
  }
}
```

**Common Error Codes:**
- `page_not_found` (404): Page does not exist or is not accessible
- `get_pages_error` (500): Server error while fetching pages
- `get_page_error` (500): Server error while fetching single page
- `rest_invalid_param` (400): Invalid parameter value

## CORS Support

The API includes CORS headers for React frontend compatibility:
- `Access-Control-Allow-Origin`: Configured origins (including localhost for development)
- `Access-Control-Allow-Methods`: GET, POST, OPTIONS
- `Access-Control-Allow-Headers`: Content-Type, Authorization, X-WP-Nonce
- `Access-Control-Allow-Credentials`: true

## Image Handling

When the Zlaark Secure Images plugin is active:
- Images are automatically protected and served through secure URLs
- Each image field includes `_urls` suffix with secure and fallback URLs for all sizes
- Secure URLs include time-limited tokens for access control
- Fallback URLs are provided when secure image plugin is inactive

## Rate Limiting

No rate limiting is currently implemented, but it's recommended to implement caching on the frontend to reduce API calls.

## Caching

- Individual page responses include caching headers (`Cache-Control`, `Last-Modified`)
- Recommended cache duration: 5 minutes (300 seconds)
- Cache should be invalidated when pages are updated

## Examples for React Frontend

### Fetch Pages List
```javascript
const fetchPages = async () => {
  const response = await fetch('/wp-json/custom-pages/v1/pages?status=published&per_page=10');
  const pages = await response.json();
  return pages;
};
```

### Fetch Single Page
```javascript
const fetchPage = async (slug) => {
  const response = await fetch(`/wp-json/custom-pages/v1/pages/slug/${slug}`);
  if (!response.ok) {
    throw new Error('Page not found');
  }
  const page = await response.json();
  return page;
};
```

### Handle Images
```javascript
const getImageUrl = (section, imageField, size = 'large') => {
  const imageUrls = section.config[`${imageField}_urls`];
  if (imageUrls && imageUrls[size]) {
    return imageUrls[size].url; // Uses secure URL if available, fallback otherwise
  }
  return null;
};
```