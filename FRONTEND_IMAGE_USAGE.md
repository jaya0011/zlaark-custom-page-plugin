# Frontend Image Usage Guide

## ✅ Fix Applied

The Custom Page Builder now automatically adds image URLs to the API response, making it easy to display images in your React frontend.

---

## 📦 What's Included in API Response

When you fetch a page from the API, each section with an image will now include:

### 1. Direct URL Field
```json
{
  "config": {
    "image": 123,
    "image_url": "https://yoursite.com/wp-content/uploads/2025/10/hero-image.jpg"
  }
}
```

### 2. Size Variants
```json
{
  "config": {
    "image": 123,
    "image_urls": {
      "thumbnail": {
        "url": "https://yoursite.com/.../hero-image-150x150.jpg",
        "secure_url": "",
        "fallback_url": "https://yoursite.com/.../hero-image-150x150.jpg"
      },
      "medium": {
        "url": "https://yoursite.com/.../hero-image-300x200.jpg",
        "secure_url": "",
        "fallback_url": "https://yoursite.com/.../hero-image-300x200.jpg"
      },
      "large": {
        "url": "https://yoursite.com/.../hero-image-1024x683.jpg",
        "secure_url": "",
        "fallback_url": "https://yoursite.com/.../hero-image-1024x683.jpg"
      },
      "full": {
        "url": "https://yoursite.com/.../hero-image.jpg",
        "secure_url": "",
        "fallback_url": "https://yoursite.com/.../hero-image.jpg"
      }
    }
  }
}
```

### 3. Complete Image Metadata
```json
{
  "config": {
    "image": 123,
    "image_data": {
      "id": 123,
      "url": "https://yoursite.com/.../hero-image.jpg",
      "secure_url": "",
      "fallback_url": "https://yoursite.com/.../hero-image.jpg",
      "alt": "Hero banner image",
      "title": "Hero Image",
      "sizes": {
        "thumbnail": { "url": "...", "secure_url": "", "fallback_url": "..." },
        "medium": { "url": "...", "secure_url": "", "fallback_url": "..." },
        "large": { "url": "...", "secure_url": "", "fallback_url": "..." },
        "full": { "url": "...", "secure_url": "", "fallback_url": "..." }
      }
    }
  }
}
```

---

## 🎯 React Frontend Usage

### Simple Usage (Recommended)

```jsx
// Fetch page data
const response = await fetch('https://yoursite.com/wp-json/custom-pages/v1/pages/1');
const page = await response.json();

// Render sections
{page.sections.map((section, index) => {
  // Access image URL directly
  const imageUrl = section.config.image_url;
  
  return (
    <div key={index} className="section">
      {imageUrl && (
        <img 
          src={imageUrl} 
          alt={section.config.image_data?.alt || 'Section image'} 
        />
      )}
      <h2>{section.config.title}</h2>
      <p>{section.config.content}</p>
    </div>
  );
})}
```

### With Responsive Images

```jsx
// Use different sizes for different screen sizes
const HeroSection = ({ section }) => {
  const imageUrls = section.config.image_urls;
  
  return (
    <div className="hero">
      <picture>
        <source 
          media="(max-width: 768px)" 
          srcSet={imageUrls?.medium?.url} 
        />
        <source 
          media="(max-width: 1024px)" 
          srcSet={imageUrls?.large?.url} 
        />
        <img 
          src={imageUrls?.full?.url} 
          alt={section.config.image_data?.alt || 'Hero image'}
          loading="lazy"
        />
      </picture>
      <h1>{section.config.title}</h1>
    </div>
  );
};
```

### With Image Metadata

```jsx
// Use complete image data
const ImageSection = ({ section }) => {
  const imageData = section.config.image_data;
  
  if (!imageData) return null;
  
  return (
    <figure className="image-section">
      <img 
        src={imageData.url} 
        alt={imageData.alt || imageData.title} 
        title={imageData.title}
        loading="lazy"
      />
      {imageData.title && (
        <figcaption>{imageData.title}</figcaption>
      )}
    </figure>
  );
};
```

### With Fallback

```jsx
// Handle missing images gracefully
const Section = ({ section }) => {
  const imageUrl = section.config.image_url 
    || section.config.image_data?.url 
    || section.config.image_urls?.full?.url;
  
  return (
    <div className="section">
      {imageUrl ? (
        <img src={imageUrl} alt="Section image" />
      ) : (
        <div className="placeholder">No image</div>
      )}
    </div>
  );
};
```

---

## 🔧 TypeScript Types

```typescript
interface ImageUrls {
  thumbnail: ImageVariant;
  medium: ImageVariant;
  large: ImageVariant;
  full: ImageVariant;
}

interface ImageVariant {
  url: string;
  secure_url: string;
  fallback_url: string;
}

interface ImageData {
  id: number;
  url: string;
  secure_url: string;
  fallback_url: string;
  alt: string;
  title: string;
  sizes: ImageUrls;
}

interface SectionConfig {
  title?: string;
  content?: string;
  image?: number;  // Attachment ID
  image_url?: string;  // Direct URL
  image_urls?: ImageUrls;  // All size variants
  image_data?: ImageData;  // Complete metadata
  [key: string]: any;
}

interface Section {
  id: number;
  section_type: string;
  config: SectionConfig;
  display_order: number;
}

interface Page {
  id: number;
  title: string;
  slug: string;
  status: string;
  sections: Section[];
}
```

---

## 🎨 Common Patterns

### Hero Banner with Background Image

```jsx
const HeroBanner = ({ section }) => {
  const imageUrl = section.config.background_image_url;
  
  return (
    <div 
      className="hero-banner"
      style={{
        backgroundImage: imageUrl ? `url(${imageUrl})` : 'none',
        backgroundSize: 'cover',
        backgroundPosition: 'center'
      }}
    >
      <h1>{section.config.title}</h1>
      <p>{section.config.subtitle}</p>
    </div>
  );
};
```

### Product Grid with Images

```jsx
const ProductGrid = ({ section }) => {
  const products = section.config.products || [];
  
  return (
    <div className="product-grid">
      {products.map((product, index) => (
        <div key={index} className="product-card">
          {product.image_url && (
            <img 
              src={product.image_url} 
              alt={product.title}
              loading="lazy"
            />
          )}
          <h3>{product.title}</h3>
          <p>{product.price}</p>
        </div>
      ))}
    </div>
  );
};
```

### Testimonial with Author Image

```jsx
const Testimonial = ({ section }) => {
  const authorImage = section.config.author_image_url;
  
  return (
    <div className="testimonial">
      <blockquote>{section.config.content}</blockquote>
      <div className="author">
        {authorImage && (
          <img 
            src={authorImage} 
            alt={section.config.author}
            className="author-avatar"
          />
        )}
        <div>
          <strong>{section.config.author}</strong>
          <span>{section.config.author_title}</span>
        </div>
      </div>
    </div>
  );
};
```

---

## 🚀 Performance Tips

### 1. Lazy Loading

```jsx
<img 
  src={imageUrl} 
  alt="Image" 
  loading="lazy"  // Native lazy loading
/>
```

### 2. Responsive Images

```jsx
<img 
  src={imageUrls.full.url}
  srcSet={`
    ${imageUrls.thumbnail.url} 150w,
    ${imageUrls.medium.url} 300w,
    ${imageUrls.large.url} 1024w,
    ${imageUrls.full.url} 2048w
  `}
  sizes="(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw"
  alt="Responsive image"
/>
```

### 3. Preload Critical Images

```jsx
// In your <head>
<link 
  rel="preload" 
  as="image" 
  href={heroImageUrl}
/>
```

### 4. Use Next.js Image Component

```jsx
import Image from 'next/image';

<Image
  src={section.config.image_url}
  alt={section.config.image_data?.alt || 'Image'}
  width={1200}
  height={800}
  priority={index === 0}  // Preload first image
/>
```

---

## 🔍 Debugging

### Check API Response

```javascript
// Fetch and log the response
fetch('https://yoursite.com/wp-json/custom-pages/v1/pages/1')
  .then(res => res.json())
  .then(data => {
    console.log('Page data:', data);
    console.log('First section config:', data.sections[0]?.config);
    console.log('Image URL:', data.sections[0]?.config.image_url);
  });
```

### Verify Image Fields

```javascript
// Check what image fields are available
const section = page.sections[0];
const imageFields = Object.keys(section.config).filter(key => 
  key.includes('image')
);
console.log('Available image fields:', imageFields);
```

### Test Image Loading

```javascript
// Test if image URL is accessible
const testImage = (url) => {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(true);
    img.onerror = () => reject(false);
    img.src = url;
  });
};

testImage(section.config.image_url)
  .then(() => console.log('Image loads successfully'))
  .catch(() => console.error('Image failed to load'));
```

---

## ⚠️ Common Issues

### Issue: Image URL is undefined

**Cause:** Image field name doesn't match pattern  
**Solution:** Check field name includes "image" (e.g., `hero_image`, `background_image`)

### Issue: Image shows attachment ID instead of URL

**Cause:** Old cached data  
**Solution:** Clear cache and refetch from API

### Issue: CORS errors

**Cause:** API not allowing your frontend domain  
**Solution:** Check CORS headers in REST controller

### Issue: 404 on image URLs

**Cause:** Image file doesn't exist  
**Solution:** Re-upload image or check file permissions

---

## 📚 Additional Resources

- **API Documentation:** See `API_DOCUMENTATION.md`
- **Troubleshooting:** See `IMAGE_DISPLAY_TROUBLESHOOTING.md`
- **Test Script:** Run `test-image-fix.php` to verify setup

---

## ✅ Quick Checklist

Before deploying your frontend:

- [ ] API returns `image_url` field
- [ ] API returns `image_urls` with size variants
- [ ] API returns `image_data` with metadata
- [ ] Frontend can access image URLs
- [ ] Images display correctly
- [ ] Lazy loading is implemented
- [ ] Responsive images are configured
- [ ] Alt text is used for accessibility
- [ ] Error handling for missing images

---

**Last Updated:** October 29, 2025  
**Plugin Version:** Custom Page Builder 1.0.0
