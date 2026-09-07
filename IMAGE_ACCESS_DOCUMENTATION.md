# WordPress Image Access Documentation

## Overview
When working with WordPress images, you often have an attachment ID (numeric value) and need to get the actual image URL or other image data. This guide covers all methods to access images from their IDs.

## WordPress Core Functions

### 1. wp_get_attachment_url()
Gets the URL of an attachment by its ID.

```php
$attachment_id = 123;
$image_url = wp_get_attachment_url($attachment_id);
// Returns: https://example.com/wp-content/uploads/2024/01/image.jpg
```

**Parameters:**
- `$attachment_id` (int) - The attachment ID
- Returns: `string|false` - The attachment URL or false on failure

### 2. wp_get_attachment_image_url()
Gets the URL of an attachment image in a specific size.

```php
$attachment_id = 123;
$size = 'thumbnail'; // or 'medium', 'large', 'full', or custom size

$image_url = wp_get_attachment_image_url($attachment_id, $size);
// Returns: https://example.com/wp-content/uploads/2024/01/image-150x150.jpg
```

**Parameters:**
- `$attachment_id` (int) - The attachment ID
- `$size` (string|array) - Image size (default: 'thumbnail')
- Returns: `string|false` - The attachment image URL or false on failure

### 3. wp_get_attachment_image()
Gets a complete HTML img tag for an attachment.

```php
$attachment_id = 123;
$size = 'medium';
$attr = array(
    'class' => 'my-image',
    'alt' => 'Custom alt text'
);

$img_tag = wp_get_attachment_image($attachment_id, $size, false, $attr);
// Returns: <img src="..." class="my-image" alt="Custom alt text" />
```

**Parameters:**
- `$attachment_id` (int) - The attachment ID
- `$size` (string|array) - Image size (default: 'thumbnail')
- `$icon` (bool) - Whether to use icon representation (default: false)
- `$attr` (array) - Additional attributes for the img tag

### 4. wp_get_attachment_image_src()
Gets an array with image URL, width, height, and resize status.

```php
$attachment_id = 123;
$size = 'large';

$image_data = wp_get_attachment_image_src($attachment_id, $size);
if ($image_data) {
    list($url, $width, $height, $is_intermediate) = $image_data;
    echo "URL: $url, Width: $width, Height: $height";
}
```

**Returns:** Array with:
- `[0]` - Image URL
- `[1]` - Image width
- `[2]` - Image height  
- `[3]` - Whether image is a resized version (boolean)

### 5. wp_get_attachment_metadata()
Gets complete metadata for an attachment.

```php
$attachment_id = 123;
$metadata = wp_get_attachment_metadata($attachment_id);

// Example output:
array(
    'width' => 1920,
    'height' => 1080,
    'file' => '2024/01/image.jpg',
    'sizes' => array(
        'thumbnail' => array(
            'file' => 'image-150x150.jpg',
            'width' => 150,
            'height' => 150,
            'mime-type' => 'image/jpeg'
        ),
        // ... other sizes
    ),
    'image_meta' => array(
        'aperture' => '0',
        'credit' => '',
        'camera' => '',
        // ... other EXIF data
    )
)
```

## WordPress Image Sizes

### Default Sizes
- `thumbnail` - Usually 150x150px (cropped)
- `medium` - Usually 300x300px (scaled)
- `medium_large` - Usually 768px wide (scaled)
- `large` - Usually 1024x1024px (scaled)
- `full` - Original image size

### Custom Sizes
You can also use custom registered image sizes or specify dimensions:

```php
// Using custom registered size
$url = wp_get_attachment_image_url($id, 'custom-size');

// Using specific dimensions
$url = wp_get_attachment_image_url($id, array(400, 300));
```

## REST API Access

### WordPress REST API
WordPress provides REST API endpoints for media:

```
GET /wp-json/wp/v2/media/{id}
```

Example response:
```json
{
  "id": 123,
  "source_url": "https://example.com/wp-content/uploads/2024/01/image.jpg",
  "media_details": {
    "width": 1920,
    "height": 1080,
    "sizes": {
      "thumbnail": {
        "source_url": "https://example.com/wp-content/uploads/2024/01/image-150x150.jpg",
        "width": 150,
        "height": 150
      }
    }
  }
}
```

### JavaScript/AJAX Access
```javascript
// Using WordPress REST API
fetch('/wp-json/wp/v2/media/123')
  .then(response => response.json())
  .then(data => {
    console.log('Full image:', data.source_url);
    console.log('Thumbnail:', data.media_details.sizes.thumbnail.source_url);
  });

// Using wp.media (if available)
const attachment = wp.media.attachment(123);
attachment.fetch().then(() => {
  console.log('Image URL:', attachment.get('url'));
  console.log('Sizes:', attachment.get('sizes'));
});
```

## Custom Page Builder Context

### In Your API Response
When your Custom Page Builder API returns image IDs, you can process them:

```php
// In your API callback
if (is_numeric($image_value)) {
    $attachment_id = intval($image_value);
    
    // Get different sizes
    $full_url = wp_get_attachment_image_url($attachment_id, 'full');
    $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
    $medium_url = wp_get_attachment_image_url($attachment_id, 'medium');
    
    // Return multiple sizes in API
    $image_data = array(
        'id' => $attachment_id,
        'full' => $full_url,
        'thumbnail' => $thumbnail_url,
        'medium' => $medium_url,
        'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true)
    );
}
```

### Frontend JavaScript Processing
```javascript
// Process sections from API response
const sections = JSON.parse(pageData.sections);
sections.forEach(section => {
    if (section.image && typeof section.image === 'number') {
        // Image is stored as ID, fetch the URL
        fetchImageUrl(section.image).then(url => {
            section.imageUrl = url;
        });
    }
});

function fetchImageUrl(attachmentId) {
    return fetch(`/wp-json/wp/v2/media/${attachmentId}`)
        .then(response => response.json())
        .then(data => data.source_url);
}
```

## Secure Image Handling

### With Secure Image Plugin
If you're using a secure image plugin, you might need special handling:

```php
// Check if image is protected
$is_protected = get_post_meta($attachment_id, '_wc_secure_images_protected', true);

if ($is_protected && class_exists('Secure_Image_Handler')) {
    $secure_handler = new Secure_Image_Handler();
    $secure_url = $secure_handler->get_secure_image_url($attachment_id, 'full');
} else {
    $secure_url = wp_get_attachment_image_url($attachment_id, 'full');
}
```

## Error Handling

### Check if Attachment Exists
```php
function get_safe_image_url($attachment_id, $size = 'full') {
    // Check if attachment exists
    if (!get_post($attachment_id)) {
        return false;
    }
    
    // Check if it's actually an image
    if (!wp_attachment_is_image($attachment_id)) {
        return false;
    }
    
    return wp_get_attachment_image_url($attachment_id, $size);
}
```

### Fallback Handling
```php
function get_image_with_fallback($image_value, $size = 'full') {
    if (is_numeric($image_value)) {
        // Try to get URL from attachment ID
        $url = wp_get_attachment_image_url(intval($image_value), $size);
        if ($url) {
            return $url;
        }
    }
    
    // If it's already a URL or ID failed, return as-is
    if (filter_var($image_value, FILTER_VALIDATE_URL)) {
        return $image_value;
    }
    
    // Return placeholder or false
    return 'https://via.placeholder.com/300x200?text=Image+Not+Found';
}
```

## Complete Example for Custom Page Builder

### PHP API Processing
```php
function process_section_images($sections) {
    foreach ($sections as &$section) {
        // Process main section image
        if (!empty($section['image'])) {
            $section['image_data'] = process_image_field($section['image']);
        }
        
        // Process nested images (slides, products, etc.)
        if (!empty($section['slides'])) {
            foreach ($section['slides'] as &$slide) {
                if (!empty($slide['image'])) {
                    $slide['image_data'] = process_image_field($slide['image']);
                }
            }
        }
    }
    return $sections;
}

function process_image_field($image_value) {
    if (is_numeric($image_value)) {
        $attachment_id = intval($image_value);
        return array(
            'id' => $attachment_id,
            'full' => wp_get_attachment_image_url($attachment_id, 'full'),
            'large' => wp_get_attachment_image_url($attachment_id, 'large'),
            'medium' => wp_get_attachment_image_url($attachment_id, 'medium'),
            'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'caption' => wp_get_attachment_caption($attachment_id)
        );
    }
    
    // If it's already a URL
    return array(
        'full' => $image_value,
        'alt' => ''
    );
}
```

### Frontend Usage
```javascript
// Use the processed image data
sections.forEach(section => {
    if (section.image_data) {
        const img = document.createElement('img');
        img.src = section.image_data.medium; // Use appropriate size
        img.alt = section.image_data.alt;
        // Add to DOM
    }
});
```

## WordPress Codex References

- [wp_get_attachment_url()](https://developer.wordpress.org/reference/functions/wp_get_attachment_url/)
- [wp_get_attachment_image_url()](https://developer.wordpress.org/reference/functions/wp_get_attachment_image_url/)
- [wp_get_attachment_image()](https://developer.wordpress.org/reference/functions/wp_get_attachment_image/)
- [wp_get_attachment_image_src()](https://developer.wordpress.org/reference/functions/wp_get_attachment_image_src/)
- [wp_get_attachment_metadata()](https://developer.wordpress.org/reference/functions/wp_get_attachment_metadata/)
- [WordPress REST API - Media](https://developer.wordpress.org/rest-api/reference/media/)

This documentation covers all the standard WordPress methods for accessing images from their attachment IDs, along with error handling and practical examples for your Custom Page Builder context.