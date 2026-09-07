# Apostrophe/Slashes Issue Fixed

## Problem Identified
When users entered text with apostrophes (like "Men's Jewelry"), each time they clicked "Update Page", additional backslashes were being added:
- First save: `Men\'s Jewelry`
- Second save: `Men\\\'s Jewelry`  
- Third save: `Men\\\\\'s Jewelry`

This happened because WordPress was adding escape slashes when saving to the database, but when retrieving and displaying the data in forms, the slashes weren't being removed before display.

## Root Cause
The issue was in the form rendering functions where data retrieved from the database was displayed using `esc_attr()` and `esc_textarea()` without first applying `stripslashes()`. This caused:

1. **Data saved to database** → WordPress adds slashes for security
2. **Data retrieved from database** → Still contains slashes  
3. **Data displayed in form** → Slashes visible to user
4. **User clicks Update** → More slashes added on top of existing ones

## Fixes Applied

### 1. Fixed Main Page Data Retrieval
**File:** `custom-page-builder.php` (lines ~770)

**Before:**
```php
$title = $page ? $page->title : '';
$slug = $page ? $page->slug : '';
$sections = $page && $page->sections ? json_decode($page->sections, true) : array();
```

**After:**
```php
// CRITICAL FIX: Strip slashes from database data to prevent accumulating backslashes
$title = $page ? stripslashes($page->title) : '';
$slug = $page ? stripslashes($page->slug) : '';
$sections = $page && $page->sections ? json_decode(stripslashes($page->sections), true) : array();
```

### 2. Fixed Section Form Rendering
**File:** `custom-page-builder.php` - `cpb_render_section_form()` function

Applied `stripslashes()` to all form field values before `esc_attr()` and `esc_textarea()`:

**Hero Slider Fields:**
- Slide titles: `esc_attr(stripslashes($slide['title'] ?? ''))`
- Slide content: `esc_textarea(stripslashes($slide['content'] ?? ''))`
- Button text: `esc_attr(stripslashes($slide['button_text'] ?? ''))`

**Product Fields:**
- Product titles: `esc_attr(stripslashes($product['title'] ?? ''))`
- Product descriptions: `esc_textarea(stripslashes($product['description'] ?? ''))`
- Product badges: `esc_attr(stripslashes($product['badge'] ?? ''))`

**Testimonial Fields:**
- Testimonial content: `esc_textarea(stripslashes($testimonial['content'] ?? ''))`
- Author names: `esc_attr(stripslashes($testimonial['author_name'] ?? ''))`
- Author titles: `esc_attr(stripslashes($testimonial['author_title'] ?? ''))`

**Content Item Fields:**
- Item titles: `esc_attr(stripslashes($item['title'] ?? ''))`
- Item content: `esc_textarea(stripslashes($item['content'] ?? ''))`
- Link text: `esc_attr(stripslashes($item['link_text'] ?? ''))`

**General Section Fields:**
- Section titles: `esc_attr(stripslashes($section['title'] ?? ''))`
- Section content: `esc_textarea(stripslashes($section['content'] ?? ''))`
- Button text: `esc_attr(stripslashes($section['button_text'] ?? ''))`

### 3. Fixed Element Rendering Function
**File:** `custom-page-builder.php` - `cpb_render_element_fields()` function

Applied `stripslashes()` to all element field values:

**Heading Elements:**
- Heading text: `esc_attr(stripslashes($element['content'] ?? ''))`

**Paragraph Elements:**
- Paragraph text: `esc_textarea(stripslashes($element['content'] ?? ''))`

**List Elements:**
- List items: `esc_textarea(stripslashes($element['content'] ?? ''))`

**Image Elements:**
- Alt text: `esc_attr(stripslashes($element['alt_text'] ?? ''))`
- Image caption: `esc_attr(stripslashes($element['content'] ?? ''))`

**Button Elements:**
- Button text: `esc_attr(stripslashes($element['content'] ?? ''))`
- Button link: `esc_attr(stripslashes($element['button_link'] ?? ''))`

## How the Fix Works

### Before Fix:
1. User enters: `Men's Jewelry`
2. WordPress saves: `Men\'s Jewelry` (with slash)
3. Form displays: `Men\'s Jewelry` (slash visible)
4. User clicks Update: `Men\\\'s Jewelry` (more slashes added)

### After Fix:
1. User enters: `Men's Jewelry`
2. WordPress saves: `Men\'s Jewelry` (with slash)
3. Form displays: `Men's Jewelry` (slash removed by `stripslashes()`)
4. User clicks Update: `Men\'s Jewelry` (only one slash, as expected)

## Testing the Fix

### Test Cases:
1. **New Content with Apostrophes:**
   - Enter: `Men's Jewelry`
   - Save and edit multiple times
   - Should remain: `Men's Jewelry`

2. **Existing Content with Multiple Slashes:**
   - Content showing: `Men\\\'s Jewelry`
   - Edit and save once
   - Should become: `Men's Jewelry`

3. **Various Punctuation:**
   - Test: `Don't, can't, won't, it's, we're`
   - Should remain unchanged after multiple saves

4. **Mixed Content:**
   - Test: `John's "favorite" item & Mary's choice`
   - Should handle all punctuation correctly

## Files Modified
- `custom-page-builder.php` - Main plugin file
  - Fixed main page data retrieval (line ~770)
  - Fixed `cpb_render_section_form()` function (lines ~1575-1900)
  - Fixed `cpb_render_element_fields()` function (lines ~1470-1570)

## Backward Compatibility
- **Existing pages with multiple slashes** will be automatically cleaned up when edited and saved
- **New pages** will not accumulate slashes
- **No data loss** - only removes unwanted escape slashes
- **Security maintained** - WordPress still handles proper escaping for database storage

## Prevention
The fix prevents future accumulation by:
1. Always stripping slashes when displaying data in forms
2. Letting WordPress handle proper escaping during save operations
3. Maintaining the security benefits of WordPress's built-in sanitization

The apostrophe/slashes issue is now completely resolved. Users can enter text with apostrophes and other punctuation without worrying about accumulating backslashes on subsequent edits.