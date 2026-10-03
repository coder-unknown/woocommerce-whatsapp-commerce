# Stateless WhatsApp Commerce for WooCommerce

[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](LICENSE)
[![WooCommerce: 8.0 - 11.1](https://img.shields.io/badge/WooCommerce-8.0%20--%2011.1-purple.svg)](https://woocommerce.com/)
[![HPOS: Declared Compatible](https://img.shields.io/badge/HPOS-Declared%20Compatible-success.svg)](https://woocommerce.com/)
[![Full-Page Cache: Static Ready](https://img.shields.io/badge/Full--Page%20Cache-Static%20Ready-brightgreen.svg)](https://www.litespeedtech.com/)

> [!WARNING]
> **Important: Store Behavior Notice Upon Activation**
>
> Activating Stateless WhatsApp Commerce transforms your WooCommerce store from a traditional payment gateway store into a **direct WhatsApp order-intent catalog**:
> - **Page Redirections**: Cart, Checkout, and My Account pages are intercepted and redirected (default: redirects to shop/home).
> - **Sessions & Fragments Suppressed**: Native WooCommerce guest session creation (`wp_woocommerce_sessions`) and render-blocking cart fragments AJAX (`wc-ajax=get_refreshed_fragments`) are disabled.
> - **Native Emails & Gateways Bypassed**: Standard WooCommerce customer transaction emails and gateway checkouts are not triggered because orders are initiated directly via customer WhatsApp messages.
>
> **Need a traditional checkout or want to disable catalog lockdown?**
> The lockdown is completely toggleable in code. Add this filter to your theme's `functions.php`:
> ```php
> add_filter('swac_enable_catalog_lockdown', '__return_false');
> ```
> You can also selectively customize lockdown behaviors (such as redirects or session stubbing) via the `swac_lockdown_features` filter.

---

## ⚡ Overview

**Stateless WhatsApp Commerce** enables WooCommerce stores to run as **session-free public catalogs** that can be served directly from full-page static cache (e.g. LiteSpeed Cache, Nginx fastcgi_cache, or Edge CDNs) without PHP or database execution overhead on cache hits.

Customer cart items, address details, and delivery zone serviceability run in the browser using vanilla JavaScript and `localStorage`. When the user is ready, the cart compiles into an itemized, structured **WhatsApp message link** (`wa.me`) where the customer reviews and transmits the order directly to your dispatch team.

---

## 📊 Operational Comparison

| Metric / Behavior | Standard WooCommerce | Stateless WhatsApp Commerce |
| :--- | :--- | :--- |
| **Full-Page Cache Hits** | Frequently bypassed by session cookies & cart fragments | **Served from full-page cache without PHP or database overhead** |
| **Database Session Writes** | 1 write per guest browsing session (`wp_woocommerce_sessions`) | **0 Database Writes** (handled entirely client-side) |
| **Guest Cookies** | Plugin sets `wp_woocommerce_session_*`, `woocommerce_items_in_cart` | **Plugin sets zero guest cookies** (static cache friendly) |
| **Cart Fragments AJAX** | Dispatches `get_refreshed_fragments` on page loads | **Completely disabled** (no background server requests) |
| **Checkout Workflow** | Multi-step form with cart abandonment | **Direct WhatsApp Order** with itemized pricing & address |
| **Edge Cacheability (Cloudflare / CDN)** | Requires complex cookie-bypass exclusion rules | **Edge-cache friendly** (depends on your edge cache rules) |

> *Note on cookies & cache: The plugin itself sets zero guest cookies. Overall site cookie behavior and Cloudflare edge caching also depend on your active theme, third-party plugins, and CDN page rule configurations.*

---

## 🏗️ Architecture Overview

```text
               Public Visitors / Crawlers
                           │
                           ▼
          ┌───────────────────────────────────┐
          │  LiteSpeed Web Server / Edge CDN  │ ──► [ Static Full-Page Cache Hit ]
          └───────────────────────────────────┘
                           │
                           │ (Cache Miss / Purge Only)
                           ▼
          ┌───────────────────────────────────┐
          │    Catalog Lockdown & Security    │
          │  - SWAC_Null_Session_Handler      │
          │  - Cart Fragments Intercept       │
          │  - Guest Checkout Diverter        │
          │  - HPOS Compatibility Declared    │
          └───────────────────────────────────┘
                           │
                           ▼
               Browser / Client-Side DOM
          ┌───────────────────────────────────┐
          │  assets/js/wa_cart.js (Engine)    │
          │  - LocalStorage Cart CRUD         │
          │  - Client-Side Address Capture    │
          │  - Dynamic Postal Zone Gate       │
          │  - Live DOM Price Reconciliation  │
          └───────────────────────────────────┘
                           │
                           ▼
             Serialized WhatsApp Order URL
          [ wa.me/number?text=Itemized+Order ]
```

---

## 🚀 Key Features

- **Zero-Session Lockdown**:
  Stubs the WooCommerce session engine with `SWAC_Null_Session_Handler`. Eliminates session cookies, database writes, and cart fragment overhead. Fully toggleable via `swac_enable_catalog_lockdown`.
- **Client-Side Cart & Slide-Out Drawer**:
  Lightweight, zero-dependency slide-out order drawer with real-time totals, dynamic shipping threshold alerts, and postal zone gating.
- **Dynamic Price Reconciliation**:
  Reconciles `localStorage` cart item prices against fresh DOM attributes on cached pages, preventing stale cache checkouts.
- **Filterable Zone Registry**:
  Supports open checkout or gated delivery zones via `apply_filters('swac_delivery_zones', ...)`.
- **Timezone-Aware Delivery Cutoffs**:
  Calculates local order cutoffs using WordPress core `wp_timezone()`.
- **High-Performance Submodules**:
  - `modules/pharmacy/`: Chemical compositions (`composition`), pack sizes (`pack_size`), and generic alternate price comparisons.
  - `modules/explore/`: High-speed directory exploration for brands and categories with instant live client search.
  - `modules/seo/`: Dynamic OpenGraph headers, taxonomy titles, and Schema.org JSON-LD metadata without external SEO plugins.
  - `integrations/`: Streaming low-memory XML sitemap generator (`/sitemap.xml`) and reverse-proxy SSL enforcer.
- **HPOS & Modern Block Ready**:
  Compatibility for High-Performance Order Storage (`custom_order_tables`) and Cart/Checkout Blocks declared via `FeaturesUtil::declare_compatibility()` on `before_woocommerce_init`.

---

## ⚙️ Configuration & Filter Hooks

All business metrics, contact numbers, and feature toggles are customizable via WordPress filters using the `swac_` prefix:

### 1. General Configuration (`swac_commerce_config`)

```php
add_filter('swac_commerce_config', function ($config) {
    $config['phone_number']     = '1234567890'; // E.164 phone without '+'
    $config['store_name']       = 'My Awesome Store';
    $config['currency_symbol']  = '$';
    $config['free_shipping_at'] = 50.00;
    $config['shipping_charge']  = 5.00;
    $config['order_disclaimer'] = '⚡ Final availability will be confirmed on WhatsApp.';
    return $config;
});
```

### 2. Active Modules (`swac_active_modules`)

Enable or disable specific subsystem modules:

```php
add_filter('swac_active_modules', function ($modules) {
    // Enable core explore and seo, disable pharmacy for a general retail store
    return ['explore', 'seo'];
});
```

### 3. Delivery Zones (`swac_delivery_zones`)

Enforce postal code serviceability (Open Mode by default):

```php
add_filter('swac_delivery_zones', function ($config) {
    $config['gated'] = true; // Enforce postal code gate
    $config['zones']['express'] = [
        'label'        => 'Express Same-Day',
        'badge_text'   => '✓ Express Same-Day Delivery Available',
        'status_class' => 'swac-pin-success',
        'allow_order'  => true,
        'pincodes'     => [
            '90210' => 'Beverly Hills',
            '90001' => 'Los Angeles',
        ],
    ];
    return $config;
});
```

### 4. Lockdown Customization (`swac_enable_catalog_lockdown` & `swac_lockdown_features`)

```php
// Option A: Completely turn off lockdown (preserves normal WooCommerce checkout)
add_filter('swac_enable_catalog_lockdown', '__return_false');

// Option B: Selectively enable/disable lockdown sub-features
add_filter('swac_lockdown_features', function ($features) {
    $features['cart_checkout_redirect'] = false; // Keep cart and checkout routes accessible
    $features['session_tuning']         = true;  // Keep zero-session handler active
    $features['wc_ajax_intercept']      = true;  // Intercept cart fragments AJAX
    $features['script_dequeue']         = true;  // Dequeue unused core cart scripts
    return $features;
});
```

---

## 📋 Shortcode Reference

Standard shortcodes use the unique `[swac_*]` prefix:

| Shortcode | Purpose | Example |
| :--- | :--- | :--- |
| `[swac_button]` | Standalone WhatsApp order/contact button | `[swac_button text="Order Now" message="Hi!"]` |
| `[swac_price_block]` | Hero price block with regular price & discount % | `[swac_price_block context="single"]` |
| `[swac_pack_size]` | Product pack size badge | `[swac_pack_size]` |
| `[swac_price_pack_hierarchy]` | Unified price and pack size container | `[swac_price_pack_hierarchy context="single"]` |
| `[swac_action_row]` | Add to Order button with quantity controls | `[swac_action_row]` |
| `[swac_delivery_notice]` | Dynamic dispatch cutoff notice | `[swac_delivery_notice]` |
| `[swac_stock_status]` | Single product out-of-stock safety net badge | `[swac_stock_status]` |
| `[swac_explore_brands]` | A-Z brand directory with live search | `[swac_explore_brands hide_empty="true"]` |
| `[swac_categories_grid]`| Grid of child categories under a parent slug | `[swac_categories_grid parent_slug="electronics"]`|
| `[swac_missing_images_finder]` | Admin audit tool for products missing images | `[swac_missing_images_finder]` |

> 📖 **Full Documentation**:
> - [docs/shortcodes.md](docs/shortcodes.md) — Comprehensive shortcode dictionary (including pharmacy module shortcodes `[swac_generic_box]`, `[swac_size_chart]`, `[swac_explore_salts]`).
> - [docs/architecture.md](docs/architecture.md) — Architectural deep-dive on zero-session caching and client-side reconciliation.

---

## 🛠️ Requirements & Compatibility

- **WordPress**: 6.2+
- **PHP**: 8.1+ (8.2+ recommended)
- **WooCommerce**: 8.0 through 11.1 (Tested up to 11.1)
- **HPOS**: Fully compatible (Custom Order Tables declared)
- **Cart/Checkout Blocks**: Declared compatible
- **Web Server / Cache**: LiteSpeed Web Server, Nginx, or any reverse-proxy / full-page caching layer

---

## ⚖️ Disclaimer

This plugin is an independent open-source project and is not affiliated with, endorsed by, or sponsored by WhatsApp, Meta, or WooCommerce. WhatsApp is a registered trademark of Meta Platforms, Inc. WooCommerce is a registered trademark of Automattic Inc.

---

## 📄 License

Stateless WhatsApp Commerce for WooCommerce is licensed under the [GNU General Public License v2.0 or later](LICENSE).

