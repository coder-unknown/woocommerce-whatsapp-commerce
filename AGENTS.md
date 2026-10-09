# AI Maintenance Operating Manual (`AGENTS.md`)

This operational manual documents architectural mental models, immutable invariants, and maintenance procedures for AI coding agents and developers working on **Stateless WhatsApp Commerce for WooCommerce (`woocommerce-whatsapp-commerce`)**.

---

## 1. The Stateless WhatsApp Catalog Mental Model

`woocommerce-whatsapp-commerce` transforms standard stateful WooCommerce into a **zero-session, high-performance public catalog** coupled with a **client-side WhatsApp commerce engine**.

### 1.1. Core Architectural Tenets
1. **Zero-Session Server Operations**:
   - The WordPress / WooCommerce server maintains **zero user cart state** and **no guest sessions**.
   - `WC_Session` is replaced with `SWAC_Null_Session_Handler`, rendering session database writes (`wp_woocommerce_sessions`) completely inert.
2. **No Customer Accounts or Authentication Bloat**:
   - Guest account creation and customer passwords on public storefront pages are suppressed.
   - Traditional `/cart/`, `/checkout/`, and `/my-account/` routes 302-redirect to the homepage (`home_url('/')`) to prevent phantom cart splitting.
3. **LiteSpeed Web Server & Edge CDN Cache Friendliness**:
   - Public pages can be served as static HTML directly from LiteSpeed Cache (LSCache), Nginx `fastcgi_cache`, Cloudflare, or other Edge CDNs.
   - Because no session cookies (`wp_woocommerce_session_*`) are dispatched to guests, cache hits require **zero PHP execution** and **zero database queries**.
4. **Client-Side Cart & DOM Price Reconciliation**:
   - Cart storage is maintained exclusively in the browser (`localStorage['swac_cart']`).
   - Customer address details live in `localStorage['swac_customer_details']`.
   - Stale cached prices are reconciled against fresh DOM nodes on slide-out drawer open.
   - Orders are compiled into plain-text WhatsApp URLs opened directly by the customer.

---

## 2. Immutable Invariants

Any pull request, refactor, or AI-generated edit **MUST NOT** violate these non-negotiable architectural invariants:

1. **No `session_start` or Native Session Usage**:
   - Never call `session_start()`, never access `$_SESSION`, and never invoke `WC()->session->set()`.
   - Never reintroduce WooCommerce native session initialization on public pages.
2. **No Guest Cookies**:
   - Never issue cookies (`setcookie`, `woocommerce_set_cart_cookies`) to guests or catalog visitors.
   - Any cookie headers sent to public guests destroy LiteSpeed full-page caching.
3. **Preserve Shortcodes & Backward-Compatibility Aliases**:
   - Never break or remove existing shortcodes (`[swac_button]`, `[swac_categories_grid]`, `[swac_price_block]`, etc.).
   - Preserve legacy aliases (`[g1_*]`, `[cdr_*]`) and function shims to prevent breaking existing sites.
4. **4-Way Version Synchronization**:
   - When bumping the plugin version, update all four files in tandem:
     1. [stateless-wa-commerce.php](file:///stateless-wa-commerce.php) (`Version: X.Y.Z`)
     2. [core/config.php](file:///core/config.php) (`define('SWAC_VERSION', 'X.Y.Z');`)
     3. [package.json](file:///package.json) (`"version": "X.Y.Z"`)
     4. [CHANGELOG.md](file:///CHANGELOG.md) (`## [X.Y.Z] — DD-MM-YYYY`)
5. **Indian Date Format Standard**:
   - All dates in `CHANGELOG.md`, documentation, and commit references **MUST** strictly follow the Indian standard date format: **`DD-MM-YYYY`** (e.g., `10-10-2026`).
   - Never use ISO `YYYY-MM-DD` or US `MM/DD/YYYY` in release logs.

---

## 3. Delivery Zones & Postal Code Architecture

The shipping engine supports two modes:
- **Open Mode** (default): Any valid postal code is accepted.
- **Gated Mode**: Only registered tiers are serviceable.

### 3.1. Always-Free Postal Codes
Store owners can designate postal codes that permanently receive free delivery regardless of cart subtotal:
- **Configuration**: `SWAC_ALWAYS_FREE_SHIPPING_POSTAL_CODES` in [core/config.php](file:///core/config.php).
- **PHP Filter**: `swac_always_free_shipping_postal_codes`.
- **PHP Helper**: `swac_is_always_free_shipping_postal_code($code)`.
- **Localization**: Localized to `window.swacZones.always_free_shipping_codes` and `window.swacConfig.always_free_shipping_codes`.
- **Client-Side Engine**:
  - `computeCartTotals(cart, pincode)` waives shipping charges (`shipping = 0`, `isAlwaysFree = true`).
  - `renderLoyaltyUI()` renders `.swac-loyalty-card--free-zone` badge and skips shipping gap nudges.

---

## 4. Testing & Verification

Automated testing is 0-dependency and powered by Node.js native test runner and the PHP static verification runner:

```bash
# Run complete test suite (Unit tests + PHP domain/invariant linting)
npm test
```

### 4.1. Test Suite Composition
- **Unit Tests (`tests/unit/`)**:
  - `cart-math.test.js`: Cart totals, MRP, discounts, savings percentage, loyalty adjustments.
  - `shipping-thresholds.test.js`: Free shipping threshold evaluation, flat shipping fees, always-free postal codes.
  - `delivery-cutoff.test.js`: IST timezone cutoff windows (morning same-day, afternoon next-day, weekend Monday cutoff).
  - `delimiter-safety.test.js`: WhatsApp base URL `?`/`&` parameter concatenation, address line-break injection sanitization, emoji encoding.
- **PHP Verification Runner (`tests/php/runner.js`)**:
  - Validates `stateless-wa-commerce` text domain across all i18n calls.
  - Enforces `session_start`, `setcookie`, and `$_SESSION` bans.
  - Enforces 4-way version synchronization.
  - Validates structural PHP token balance.

Before submitting any changes, execute `npm test` and verify that all test suites pass with 0 failures.
