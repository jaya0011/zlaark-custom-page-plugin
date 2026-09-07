# Quick Reference - Product Category Selectors

## 🚀 Quick Start

1. **Clear cache**: `Ctrl + F5`
2. **Go to**: Custom Pages → Add New Page
3. **Add section**: Product Grid
4. **Click**: "Add Product"
5. **Look for**: Yellow → Green → Product Form

---

## 👀 What You Should See

### ✅ Success (Everything Working)

```
┌─────────────────────────────────────────┐
│ ⏳ Loading product form...              │ ← Yellow box (appears first)
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ ✅ Product form loaded successfully!    │ ← Green box (appears next)
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ Product #1                    [Remove]  │
├─────────────────────────────────────────┤
│ Product Image: [Upload]                 │
│ Product Title: [________]               │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ 🛍️ WooCommerce Categories (BLUE)   │ │ ← Blue box
│ │ ☐ Electronics                       │ │
│ │ ☐ Clothing                          │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ 🏷️ WooCommerce Tags (GREEN)        │ │ ← Green box
│ │ ☐ New                               │ │
│ │ ☐ Sale                              │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ Price: [________]                       │
│ Description: [________]                 │
└─────────────────────────────────────────┘
```

### ❌ Error (Something Failed)

```
┌─────────────────────────────────────────┐
│ ⏳ Loading product form...              │ ← Yellow box
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ ❌ AJAX Request Failed                  │ ← Red box
│ Status: error                           │
│ Error: [error message]                  │
│ Using fallback template.                │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ Product #1 (Basic Form)                 │ ← Fallback form
│ ⚠️ Category selectors not available    │
└─────────────────────────────────────────┘
```

---

## 🐛 Debug Panel (Bottom-Right Corner)

```
┌─────────────────────────────────┐
│ 🐛 Debug Console      [Close]  │
├─────────────────────────────────┤
│ ✅ CPB_Admin found              │
│ ✅ AJAX URL set                 │
│ 🌐 AJAX Request sent            │
│ ✅ Response: SUCCESS            │
│ ℹ️ HTML: 5234 chars            │
│ ✅ Has categories: true         │
│ ✅ Has tags: true               │
└─────────────────────────────────┘
```

---

## 🔍 Where to Check

| Location | What to Look For | How to Access |
|----------|------------------|---------------|
| **Page** | Colored boxes (Yellow/Green/Red) | Just look at the page |
| **Debug Panel** | Real-time logs | Bottom-right corner |
| **Console** | Detailed logs | Press `F12` → Console tab |
| **Network** | AJAX requests | Press `F12` → Network tab |
| **Debug Log** | PHP errors | `wp-content/debug.log` |

---

## 🎨 Color Guide

| Color | Meaning | Action |
|-------|---------|--------|
| 🟡 **Yellow** | Loading/Warning | Wait or read message |
| 🟢 **Green** | Success | Everything is working |
| 🔴 **Red** | Error | Read error, check console |
| 🔵 **Blue** | Category selector | This is what you want to see |
| 🟢 **Green** | Tag selector | This is what you want to see |

---

## 🔧 Quick Fixes

### Issue: Nothing happens when clicking "Add Product"

**Fix**:
1. Clear cache: `Ctrl + F5`
2. Check console for errors (F12)
3. Look for debug panel

### Issue: Red error box appears

**Fix**:
1. Read the error message
2. Check console for details
3. Check debug.log
4. Report error with screenshots

### Issue: Fallback form (no categories)

**Fix**:
1. AJAX failed - check why
2. Look at debug panel
3. Check Network tab
4. Verify template files exist

### Issue: Debug panel doesn't appear

**Fix**:
1. Hard refresh: `Ctrl + F5`
2. Check if on correct page
3. Check console for JS errors

---

## 📋 Testing Checklist

- [ ] Cleared browser cache (`Ctrl + F5`)
- [ ] On correct page (`/admin.php?page=custom-page-builder-new`)
- [ ] Added Product Grid section
- [ ] Clicked "Add Product" button
- [ ] Saw yellow loading box
- [ ] Saw green success box OR red error box
- [ ] Product form appeared
- [ ] Blue category box visible
- [ ] Green tag box visible
- [ ] Debug panel shows in bottom-right
- [ ] Console shows logs (F12)

---

## 🆘 Report Issues

If it doesn't work, provide:

1. **Screenshot** of page after clicking "Add Product"
2. **Screenshot** of debug panel
3. **Screenshot** of console (F12)
4. **Error message** from red box (if any)

---

## 💡 Pro Tips

- Keep **debug panel open** while testing
- Keep **console open** (F12)
- **Hard refresh** after any code changes
- Check **all three places**: Page, Panel, Console
- **Read error messages** - they tell you exactly what's wrong

---

## ⚡ One-Minute Test

```
1. Ctrl + F5 (clear cache)
2. Custom Pages → Add New Page
3. Add Section → Product Grid → Save
4. Click "Add Product"
5. Look for: Yellow → Green → Blue/Green boxes
6. Done! ✅
```

---

## 🎯 Expected Timeline

```
0s  - Click "Add Product"
0.1s - Yellow box appears
0.3s - AJAX request sent
0.5s - Response received
0.6s - Green box appears
0.7s - Product form renders
0.8s - Blue category box visible
0.9s - Green tag box visible
1.0s - Success! ✅
```

---

**Everything is now visible and debuggable! 🎉**
