# Phase: Internationalization (French & Arabic)

## The Goal
Translate the TQSEET platform into French and Arabic to serve the Moroccan market natively.

## Architecture & Implementation Plan

### 1. Centralized Translation System
Instead of hardcoding text in PHP files (e.g., `<h1>Shop Now</h1>`), we will use a JSON-based translation dictionary.
- We will create `lang/en.json`, `lang/fr.json`, and `lang/ar.json`.
- A simple PHP helper function (e.g. `__('keyword')`) will be created to read from the active session's language file.

### 2. Language Switcher
- A dropdown menu will be added to the public `navbar.php` and portal sidebars.
- When clicked, it will update `$_SESSION['lang']` to the target language and refresh the page to apply the newly loaded JSON dictionary.

### 3. RTL (Right-to-Left) Support
- Arabic requires the layout to flip horizontally (Right-to-Left).
- Instead of maintaining a completely separate stylesheet, we will dynamically set `<html lang="ar" dir="rtl">`.
- We will update our main CSS (`style.css`, `merchant_portal.css`) to use **CSS Logical Properties** (e.g., swapping `margin-left` for `margin-inline-start`). This automatically flips the layout based on the `dir` attribute.

### 4. Database Strategy (Static vs Dynamic)
- **Phase A (Immediate):** Translate the "Static UI" (Buttons, Menus, Alerts, Table Headers). This gives the user an Arabic/French experience immediately without database migrations.
- **Phase B (Future):** Modify the database schema (e.g., `products` table) to support multiple languages for dynamic user-generated content (e.g. `name_ar`, `name_fr`).
