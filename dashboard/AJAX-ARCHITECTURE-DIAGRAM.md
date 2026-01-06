# AJAX Navigation Architecture - Visual Diagram

## 📐 SYSTEM ARCHITECTURE

```
┌─────────────────────────────────────────────────────────────────┐
│                        BROWSER (Client)                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐         ┌──────────────────────────────────┐ │
│  │   SIDEBAR    │         │      MAIN CONTENT AREA           │ │
│  │  (Loads Once)│         │      #main-content               │ │
│  │              │         │                                  │ │
│  │  Dashboard ──┼────────→│  ← Content updates here          │ │
│  │  Students  ──┼────────→│                                  │ │
│  │  Teachers  ──┼────────→│     (AJAX replaces this)         │ │
│  │  Classes   ──┼────────→│                                  │ │
│  │              │         │                                  │ │
│  └──────────────┘         └──────────────────────────────────┘ │
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │         ajax-navigation.js (running in browser)           │  │
│  │  • Intercepts clicks on [data-ajax-link]                  │  │
│  │  • Fetches content via Fetch API                          │  │
│  │  • Updates #main-content only                             │  │
│  │  • Updates URL with pushState                             │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │ Fetch API
                              │ X-AJAX-Navigation: 1
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        SERVER (PHP)                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  auth-check.php                                           │  │
│  │  • Checks authentication                                  │  │
│  │  • If AJAX + Not Auth → Return 401 JSON                   │  │
│  │  • If Normal + Not Auth → Redirect to login               │  │
│  └───────────────────────────────────────────────────────────┘  │
│                              ▼                                   │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  ajax-helper.php                                          │  │
│  │  • isAjaxRequest() → Checks headers                       │  │
│  │  • Determines what to return                              │  │
│  └───────────────────────────────────────────────────────────┘  │
│                              ▼                                   │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  layout-header.php                                        │  │
│  │  if (!isAjaxRequest()) {                                  │  │
│  │    → Output: <!DOCTYPE>, sidebar, header                  │  │
│  │  }                                                         │  │
│  └───────────────────────────────────────────────────────────┘  │
│                              ▼                                   │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  YOUR CONTENT (e.g., students-management.php)            │  │
│  │  → Always outputs content                                 │  │
│  └───────────────────────────────────────────────────────────┘  │
│                              ▼                                   │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  layout-footer.php                                        │  │
│  │  if (!isAjaxRequest()) {                                  │  │
│  │    → Output: </main>, scripts, </body>, </html>           │  │
│  │  }                                                         │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 NAVIGATION FLOW

### FIRST PAGE LOAD (Full HTML)
```
User visits: /dashboard/students-management.php

┌─────────────┐
│   Browser   │  GET /students-management.php
└─────┬───────┘
      │ (No AJAX headers)
      ▼
┌─────────────────────────────┐
│   auth-check.php            │  ✅ User is logged in
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   ajax-helper.php           │  isAjaxRequest() → FALSE
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   layout-header.php         │  Outputs: <!DOCTYPE>, <html>, 
│   (Full HTML Structure)     │          sidebar, header, <div id="main-content">
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   students-management.php   │  Outputs: Student list content
│   (Content)                 │
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   layout-footer.php         │  Outputs: </div>, scripts,
│   (Close HTML)              │          </body>, </html>
└─────────────┬───────────────┘
              │
              ▼
        Full HTML sent to browser
        Sidebar + Content displayed
```

### AJAX NAVIGATION (Content Only)
```
User clicks: "Teachers" link (with data-ajax-link)

┌─────────────┐
│   Browser   │  JavaScript intercepts click
└─────┬───────┘
      │ Fetch API with headers:
      │ X-Requested-With: XMLHttpRequest
      │ X-AJAX-Navigation: 1
      ▼
┌─────────────────────────────┐
│   auth-check.php            │  ✅ User is logged in
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   ajax-helper.php           │  isAjaxRequest() → TRUE
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   layout-header.php         │  Outputs: NOTHING (skipped)
│   (Conditional)             │
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   teachers-management.php   │  Outputs: Teacher list content
│   (Content)                 │           (HTML only, no wrapper)
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   layout-footer.php         │  Outputs: NOTHING (skipped)
│   (Conditional)             │
└─────────────┬───────────────┘
              │
              ▼
        Content-only HTML sent
              │
              ▼
┌─────────────────────────────┐
│   JavaScript receives HTML  │  Injects into #main-content
│   document.querySelector    │  Updates browser history
│   ('#main-content')         │  Scrolls to top
│   .innerHTML = response     │
└─────────────────────────────┘
```

---

## 🔐 AUTH FLOW

### AJAX Request (Not Authenticated)
```
User (not logged in) tries AJAX navigation

┌─────────────┐
│   Browser   │  Fetch /students-management.php
└─────┬───────┘  Headers: X-AJAX-Navigation: 1
      ▼
┌─────────────────────────────┐
│   auth-check.php            │  
│   if (!isAuthenticated) {   │
│     if (isAjaxRequest()) {  │  ← Detects AJAX!
│       return 401 JSON       │
│     }                        │
│   }                          │
└─────────────┬───────────────┘
              │
              ▼
        HTTP 401 Response
        { "success": false,
          "message": "Authentication required" }
              │
              ▼
┌─────────────────────────────┐
│   ajax-navigation.js        │  Detects 401 status
│   if (response.status===401)│  Redirects to login
│   window.location.href=...  │
└─────────────────────────────┘
```

### Normal Request (Not Authenticated)
```
User (not logged in) visits URL directly

┌─────────────┐
│   Browser   │  GET /students-management.php
└─────┬───────┘  (No AJAX headers)
      ▼
┌─────────────────────────────┐
│   auth-check.php            │  
│   if (!isAuthenticated) {   │
│     if (isAjaxRequest()) {  │  ← NOT AJAX
│       // skipped             │
│     } else {                 │
│       header('Location:..') │  ← Redirect!
│     }                        │
│   }                          │
└─────────────┬───────────────┘
              │
              ▼
        HTTP 302 Redirect
        Location: /login-form.php?return_url=...
```

**Key Point:** Auth check ALWAYS runs. AJAX requests can't bypass security!

---

## 📦 RESPONSE COMPARISON

### Full Page Request → Full HTML Response
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Students - Northland Schools</title>
    <!-- CSS -->
</head>
<body>
    <!-- SIDEBAR (entire sidebar HTML) -->
    <aside class="sidebar">...</aside>
    
    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- HEADER -->
        <header>...</header>
        
        <!-- CONTENT -->
        <div id="main-content">
            <div class="p-6">
                <h1>Students Management</h1>
                <!-- Students list -->
            </div>
        </div>
    </main>
    
    <!-- SCRIPTS -->
    <script src="ajax-navigation.js"></script>
</body>
</html>
```

### AJAX Request → Content Only
```html
<div class="p-6">
    <h1>Students Management</h1>
    <!-- Students list -->
    <table>
        <tr>...</tr>
    </table>
</div>
```

**Size Difference:**  
- Full: ~500KB  
- AJAX: ~50KB  
- **90% reduction!**

---

## 🎯 KEY CONCEPTS

### 1. Conditional Output
```php
// layout-header.php
if (!isAjaxRequest()):
    // Output sidebar + header
endif;

// Page content ALWAYS outputs

// layout-footer.php
if (!isAjaxRequest()):
    // Close HTML tags
endif;
```

### 2. Link Interception
```javascript
// ajax-navigation.js
document.addEventListener('click', (e) => {
    const link = e.target.closest('a[data-ajax-link]');
    if (link) {
        e.preventDefault();
        loadContent(link.href); // AJAX load
    }
});
```

### 3. Browser History
```javascript
// Push new state
history.pushState({ url: newUrl }, title, newUrl);

// Handle back/forward
window.addEventListener('popstate', (e) => {
    loadContent(e.state.url);
});
```

---

## ✅ WHY THIS WORKS

1. **Sidebar loads once** → Remains in DOM, never replaced
2. **Only #main-content updates** → AJAX response replaces only this div
3. **URLs work** → pushState updates URL without reload
4. **Back/forward work** → popstate event loads previous content
5. **Auth is secure** → Server-side check on EVERY request
6. **Graceful fallback** → On error, falls back to full page load
7. **No frameworks needed** → Pure JavaScript + PHP

---

## 🎨 VISUAL: What Updates vs. What Stays

```
┌─────────────────────────────────────────────┐
│  BROWSER WINDOW                             │
├─────────────────────────────────────────────┤
│                                             │
│  ┌─────────────┐  ┌───────────────────────┐│
│  │             │  │                       ││
│  │             │  │                       ││
│  │   SIDEBAR   │  │     MAIN CONTENT      ││
│  │             │  │                       ││
│  │   STAYS!    │  │     UPDATES!          ││
│  │   ✓✓✓✓✓✓    │  │     🔄🔄🔄🔄🔄         ││
│  │             │  │                       ││
│  │  Dashboard  │  │  [Content div]        ││
│  │  Students   │  │   Updates here        ││
│  │  Teachers   │  │   without reload      ││
│  │  Classes    │  │                       ││
│  │             │  │                       ││
│  │  [Logout]   │  │                       ││
│  └─────────────┘  └───────────────────────┘│
│                                             │
└─────────────────────────────────────────────┘
```

---

**That's the architecture!** 🎉

Simple, clean, and effective. No complexity, just smart conditional output.
