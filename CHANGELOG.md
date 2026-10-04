# Changelog

All notable changes to **Stateless WhatsApp Commerce for WooCommerce** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.3.0] — 2026-10-05

### Added
- **Developer Feature Flag Control Panel** (`features.php`):
  - New root-level `features.php` — one of two files a developer interacts with. Documents and controls everything the plugin does beyond its core WhatsApp checkout purpose.
  - **Zone 1 — Optional WordPress cleanups** (OFF by default, non-destructive):
    - `SWAC_DISABLE_BLOG`: 301-redirects posts, blog index, author, date, and tag archives to the homepage. Data is never deleted.
    - `SWAC_DISABLE_BLOCK_EDITOR`: Extends the existing product-only Gutenberg disable to cover all post types site-wide.
  - **Zone 2 — Lockdown feature group toggles** (ON by default): Surfaces the six user-tunable lockdown groups as readable constants — `SWAC_LOCKDOWN_HEAD_BLOAT`, `SWAC_LOCKDOWN_SECURITY`, `SWAC_LOCKDOWN_HEARTBEAT_COMMENTS`, `SWAC_LOCKDOWN_ADMIN_CLEANUP`, `SWAC_LOCKDOWN_REGISTRATION`, `SWAC_LOCKDOWN_SCRIPT_DEQUEUE`. Architectural locks (cart redirect, AJAX intercept, zero-session engine) are documented as intentionally absent.
  - **Zone 3 — Module activation** (OFF by default): `SWAC_MODULE_PHARMACY`, `SWAC_MODULE_EXPLORE`, `SWAC_MODULE_SEO` replace the old `'modules' => []` array in `core/config.php`.

### Changed
- **`core/config.php` restructured as a human-editable constant file**:
  - All store settings are now top-level PHP constants — `SWAC_PHONE`, `SWAC_STORE_NAME`, `SWAC_CITY_NAME`, `SWAC_FREE_SHIPPING_AT`, `SWAC_SHIPPING_CHARGE`, `SWAC_MAX_CART_ITEMS`, `SWAC_MAX_QTY_PER_ITEM`, `SWAC_ORDER_DISCLAIMER`, and all `SWAC_LOYALTY_*` constants. No PHP functions or WordPress filter knowledge required.
  - Plugin engine code (`SWAC_PATH`, `SWAC_URL`, `SWAC_VERSION`, `swac_get_config()`, `swac_is_module_active()`) separated below a clear `⚠️ ENGINE — DO NOT EDIT BELOW THIS LINE` boundary.
  - `swac_get_config()` now reads from constants, remaining fully compatible with the `swac_commerce_config` filter for programmatic overrides.
- **Prescription note removed from core store config**: `SWAC_PRESCRIPTION_NOTE_ENABLED` and `SWAC_PRESCRIPTION_NOTE` removed from `core/config.php`. These are pharmacy module concerns — a clothing or electronics store should never encounter them. The pharmacy module sets these keys via the `swac_commerce_config` filter when active. Engine defaults remain `false` / `''` for backward compatibility.
- **Frontend enqueue extracted** (`frontend/enqueue.php`): Moved `wp_enqueue_scripts` hook — including stylesheet registration, `wa_cart.js` enqueue, delivery zone pin processing, and `swacCommerce`/`swacZones` localization — out of the root plugin file into a dedicated frontend file.
- **Admin notices extracted** (`backend/admin-tools.php`): WooCommerce dependency check and unconfigured phone number warning moved from the root plugin file to `backend/admin-tools.php`.
- **README updated**: Configuration section rewritten to lead with `core/config.php` and `features.php` as the primary developer interface. Filter-based overrides demoted to an "Advanced" subsection. Warning block updated to reference `features.php` for lockdown tuning.

---

## [1.2.2] — 2026-10-03

### Added
- **Delivery Cutoff Calendar Date Stamping** (`core/delivery-cutoff.php`, `assets/js/wa_cart.js`):
  - Added dynamic `(j M)` calendar date stamping to delivery cutoff notices and WhatsApp order estimates (e.g. `Order now to get it by Monday (5 Oct)` and `Tomorrow (6 Oct)`).
  - Added client-side date calculation helper `getKolkataFormattedDate()` for responsive hydration on static cached pages.
- **WhatsApp Action Button Icon Toggle** (`frontend/components/whatsapp-button.php`):
  - Added `icon="yes|no"` attribute to `[swac_button]` (and `[cdr_whatsapp_button]`) to allow suppressing the WhatsApp SVG icon when using custom leading emojis or text.
- **Backward Compatibility Aliases**:
  - Registered legacy aliases `[g1_delivery_notice]`, `[g1_composition_tag]`, `[cdr_whatsapp_button]`, `[cdr_generic_box]`, and `[g1_generic_box]`.
  - Added function aliases `g1_get_delivery_cutoff_message()`, `cdr_whatsapp_button_shortcode()`, and `cdr_generic_box_html()`.

### Fixed
- **Composition & Generic Badge Clean Markup** (`modules/pharmacy/product-meta.php`, `modules/pharmacy/generic-upsell.php`):
  - Removed emoji prefix (`🧪`) from pharmaceutical active salt links and generic comparison cards for cleaner typography and badge styling.
- **Loyalty Gift Emoji Spacing & Italic Alignment** (`assets/js/wa_cart.js`, `assets/css/cart-drawer.css`):
  - Wrapped loyalty gift emoji (`🎁`) inside `.cdr-loyalty-gift-icon` with `font-style: normal` to prevent italic font slanting overlap.
  - Normalized spacing after the loyalty gift emoji in generated WhatsApp order summary messages.

## [1.2.1] — 2026-09-30

### Added
- **Canonical WhatsApp URL Builder** (`core/pricing-engine.php`):
  - Added `swac_build_whatsapp_url(string $message, ?string $base_url)` helper to safely format WhatsApp direct URLs with proper query delimiters (`?` vs `&`).
- **Targeted WhatsApp Delivery Estimate** (`assets/js/wa_cart.js`):
  - Added `getDeliveryEstimate()` returning concise day labels (`Tomorrow`, `Today (Same-day)`, or `Monday`) for WhatsApp messages without bloating URLs or messages.

### Fixed
- **WhatsApp Button Query Delimiter** (`frontend/components/whatsapp-button.php`):
  - Ensured `[swac_button]` cleanly resolves query parameters via `swac_build_whatsapp_url()` when base URLs contain existing parameters (`?phone=...`).

### Changed
- **WhatsApp Order Message Formatting & URL Shortening** (`assets/js/wa_cart.js`):
  - Streamlined delivery notice in WhatsApp to bold `*Estimated Delivery:* Tomorrow` (or `Today (Same-day)` / `Monday`), keeping detailed cutoff marketing banners exclusively on site.
  - Streamlined delivery address heading to bold `*Delivery Details:*` without emoji or city name suffixes.
  - Updated totals labels to `Discount` / `Discount:` and `To Pay` / `To Pay:` across drawer totals and the serialized WhatsApp message.

## [1.2.0] — 2026-09-29

### Added
- **WooCommerce HPOS & Blocks Compatibility**: Formally declared feature compatibility for High-Performance Order Storage (`custom_order_tables`) and Cart/Checkout Blocks on `before_woocommerce_init`.
- **Fault-Tolerant Module Loading**: Added directory and file existence guards to module loading and style enqueuing, preventing fatal errors if optional modules are removed from the filesystem.
- **Unconfigured Phone Number Safeguards**: Added an admin warning notice when no WhatsApp phone number is configured, gracefully suppressed broken `[swac_button]` links, and disabled cart checkout with explanatory notices.

### Changed
- **Opt-in Modular Extensions**: Optional subsystems (`pharmacy`, `explore`, `seo`) are now disabled by default (`'modules' => []`) for a lean core install, with documented activation filters (`swac_active_modules`).
- **Text Domain & Core Namespacing**: Migrated text domain to `stateless-wa-commerce` and transitioned core PHP constants, functions, filters, and asset handles from `wa_` to the canonical `swac_` namespace (`SWAC_PATH`, `swac_get_config()`, `swac-cart-script`, etc.).
- **Frontend & CSS Prefix Standardization**: Unified all DOM IDs, CSS classes, JavaScript selectors, animations, and template attributes from legacy/private prefixes (`cdr-`, `g1-`, `wa-`) to the canonical `swac-` namespace across all stylesheets, scripts, and PHP views.

### Removed
- **Legacy Compatibility Layer**: Stripped legacy backward-compatibility shims, constant fallbacks, deprecated shortcode aliases (`[cdr_*]`, `[g1_*]`), and obsolete migration logic to streamline code for fresh WordPress installations.

## [1.1.0] — 2024-09-26

### Added
- **Cart Ceilings & User Quota Safeguards** (`core/config.php`, `stateless-wa-commerce.php`, `assets/js/wa_cart.js`, `assets/css/cart-drawer.css`):
  - Configurable `max_cart_items` (default: 10 items) and `max_qty_per_item` (default: 10 units) in `wa_commerce_config` to prevent WhatsApp deep-link URL truncation (>2,000 chars) and bulk catalog hoarding.
  - Enforced item & quantity limits in `addToCart()`, `getSelectedQty()`, and the cart drawer, automatically disabling the `+` button at 10 units.
  - Added an accessible floating dark pill toast notification (`.wa-toast`) in `wa_cart.js` and `cart-drawer.css` to inform users with clear messaging instead of silently failing.
- **Decoupled Prescription `[Rx]` Item Tagging & Notice** (`modules/pharmacy/pack-metrics.php`, `frontend/single/single-whatsapp.php`, `frontend/archive/archive-loop.php`, `assets/js/wa_cart.js`):
  - Completely decoupled Rx checks from core templates using the `wa_product_is_rx` filter: general stores (clothing, pet shops, electronics) have zero Rx pollution and default to regular goods.
  - In `modules/pharmacy/`, products only qualify as Rx if explicitly tagged (`rx`, `prescription`, `schedule-h`), never defaulting to Rx for untagged general products.
  - When the pharmacy module is active and Rx products are present, prepends `[Rx]` in the WhatsApp message item list and displays the prescription compliance note. Pure OTC or general goods orders omit the note entirely.

### Fixed
- **WhatsApp URL Emoji Encoding Corruption** (`core/config.php`, `assets/js/wa_cart.js`):
  - Switched default `base_url` construction from `https://wa.me/` to `https://api.whatsapp.com/send?phone=` to eliminate Meta's HTTP 302 redirect Latin-1 transcoding that turned emojis (⚡, 📍, 📋, 🎁) into replacement characters (``).
  - Added dynamic query separator handling (`?` vs `&`) in `wa_cart.js` for flexible base URLs.

## [1.0.0] — 2024-09-26

### Added
- **Stateless Zero-Session Engine**:
  - Turns WooCommerce into a session-free public catalog optimized for full-page static caching (LSCache, Nginx fastcgi_cache, Edge CDNs) without PHP or database execution overhead on cache hits.
  - Custom `WA_Null_Session_Handler` extending `WC_Session` with complete null-safe stubs (`has_session`, `maybe_set_customer_session_cookie`, `forget_session`, `cleanup_sessions`).
  - Suppression of AJAX cart fragments (`wc-ajax=get_refreshed_fragments`), cart cookies, guest customer tracking, and unused WooCommerce assets.
  - Automatic 302 redirection of standard cart and checkout pages directly to the catalog shop or homepage.
- **Client-Side WhatsApp Cart**:
  - LocalStorage-backed cart state machine with instant UI updates and badge counter.
  - Dynamic sliding modal drawer with inline address capture, postal code validation, and batch pricing disclaimer.
  - Real-time DOM price reconciliation preventing outdated price leaks from aggressively cached HTML.
  - Dynamic WhatsApp order message serializer with customizable currency formatting, itemized totals, delivery details, and order notes.
- **Extensible Configuration & Delivery Zones Registry**:
  - Centralized, filterable configuration via `apply_filters('swac_commerce_config', ...)`.
  - Multi-tier postal code serviceability registry via `apply_filters('swac_delivery_zones', ...)`.
  - Default Open Mode allowing nationwide/international checkout, with optional Gated Mode for local dispatch radius.
- **Dynamic Delivery Cutoff Engine**:
  - Timezone-aware dispatch cutoff window calculator using WordPress core `wp_timezone()`.
  - Filterable cutoff schedule and customer notice formatting.
- **Standalone High-Scale Integrations**:
  - Streaming XML sitemap generator (`/sitemap.xml`) via direct low-memory `$wpdb` streaming queries.
  - Edge CDN and reverse-proxy SSL enforcer with HTTP 301 redirection and CSP `upgrade-insecure-requests`.
- **Modular Subsystems**:
  - `modules/pharmacy/`: Chemical compositions (salts), pack sizes, generic badges, generic alternate savings comparison, and packaging unit metrics parser.
  - `modules/explore/`: High-performance A-Z brands and categories exploration directories with real-time client-side live search.
  - `modules/seo/`: Vendor-neutral dynamic document titles, meta descriptions, OpenGraph headers, and Schema.org JSON-LD generation.
- **WooCommerce 8.0 - 11.1 & HPOS Compatibility**:
  - Full compatibility declaration for High-Performance Order Storage (`custom_order_tables`) and Cart/Checkout Blocks.
