<?php
/**
 * Plugin Name: Stateless WhatsApp Commerce for WooCommerce
 * Description: Zero-session catalog lockdown and client-side WhatsApp commerce engine for WooCommerce. Serving dynamic catalogs from full-page static cache.
 * Version: 1.2.2
 * Author: 0xCoderunknown
 * License: GPL-2.0-or-later
 * Text Domain: stateless-wa-commerce
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * WC requires at least: 8.0
 * WC tested up to: 11.1
 *
 * @package StatelessWaCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

// 🛍️ DECLARE HPOS (High-Performance Order Storage) & CART/CHECKOUT BLOCKS COMPATIBILITY
// Must be declared in the root plugin file early via before_woocommerce_init hook
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// 🔧 BOOTSTRAP CONFIGURATION & TECHNICAL PRIMITIVES
require_once plugin_dir_path(__FILE__) . 'core/config.php';

/**
 * 🌐 LOAD PLUGIN TEXTDOMAIN
 */
add_action('init', function () {
    load_plugin_textdomain('stateless-wa-commerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

/**
 * 🛍️ WOOCOMMERCE DEPENDENCY VERIFICATION
 */
add_action('admin_notices', function () {
    if (!class_exists('WooCommerce')) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong><?php esc_html_e('Stateless WhatsApp Commerce', 'stateless-wa-commerce'); ?></strong>
                <?php esc_html_e('requires WooCommerce to be installed and active.', 'stateless-wa-commerce'); ?>
            </p>
        </div>
        <?php
        return;
    }

    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    $cfg = function_exists('swac_get_config') ? swac_get_config() : [];
    if (empty($cfg['phone_number']) && empty($cfg['base_url'])) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e('Stateless WhatsApp Commerce:', 'stateless-wa-commerce'); ?></strong>
                <?php esc_html_e('No WhatsApp phone number is configured. Customer ordering links and buttons are currently disabled to prevent broken links. Please set phone_number via the swac_commerce_config filter.', 'stateless-wa-commerce'); ?>
            </p>
        </div>
        <?php
    }
});

// 📂 CORE SUBSYSTEM
require_once SWAC_PATH . 'core/pricing-engine.php';
require_once SWAC_PATH . 'core/delivery-cutoff.php';
require_once SWAC_PATH . 'core/delivery-zones.php';
require_once SWAC_PATH . 'core/catalog-lockdown.php';
require_once SWAC_PATH . 'core/cache-manager.php';

// 📂 INTEGRATIONS
require_once SWAC_PATH . 'integrations/ssl-security.php';
require_once SWAC_PATH . 'integrations/sitemap-generator.php';

// 📂 FRONTEND COMPONENTS
require_once SWAC_PATH . 'frontend/archive/archive-loop.php';
require_once SWAC_PATH . 'frontend/components/whatsapp-button.php';
require_once SWAC_PATH . 'frontend/components/cart-drawer.php';
require_once SWAC_PATH . 'frontend/single/product-meta.php';
require_once SWAC_PATH . 'frontend/single/notices.php';
require_once SWAC_PATH . 'frontend/single/delivery-notice.php';
require_once SWAC_PATH . 'frontend/single/single-whatsapp.php';

// 📂 BACKEND UTILITIES
if (is_admin()) {
    require_once SWAC_PATH . 'backend/admin-tools.php';
    require_once SWAC_PATH . 'backend/product-editor.php';
    require_once SWAC_PATH . 'backend/taxonomy-ui.php';
}

// 📂 MODULAR SUBSYSTEMS (Feature Flagged & Folder-Safe)

// 1. Pharmacy Module
if (swac_is_module_active('pharmacy') && is_dir(SWAC_PATH . 'modules/pharmacy')) {
    $pharmacy_files = [
        'modules/pharmacy/register-taxonomies.php',
        'modules/pharmacy/pack-metrics.php',
        'modules/pharmacy/generic-upsell.php',
        'modules/pharmacy/explore-salts.php',
        'modules/pharmacy/product-meta.php',
        'modules/pharmacy/size-charts.php',
    ];
    foreach ($pharmacy_files as $file) {
        if (file_exists(SWAC_PATH . $file)) {
            require_once SWAC_PATH . $file;
        }
    }
}

// 2. Directory Exploration Module
if (swac_is_module_active('explore') && is_dir(SWAC_PATH . 'modules/explore')) {
    $explore_files = [
        'modules/explore/brands.php',
        'modules/explore/categories.php',
    ];
    foreach ($explore_files as $file) {
        if (file_exists(SWAC_PATH . $file)) {
            require_once SWAC_PATH . $file;
        }
    }
}

// 3. Native SEO Module
if (swac_is_module_active('seo') && is_dir(SWAC_PATH . 'modules/seo')) {
    $seo_files = [
        'modules/seo/homepage-schema.php',
        'modules/seo/product-seo.php',
        'modules/seo/taxonomy-seo.php',
    ];
    foreach ($seo_files as $file) {
        if (file_exists(SWAC_PATH . $file)) {
            require_once SWAC_PATH . $file;
        }
    }
}

/**
 * 🎨 ENQUEUE FRONTEND ASSETS
 */
add_action('wp_enqueue_scripts', function () {
    // 1. Core Stylesheets
    wp_enqueue_style('swac-frontend-style', SWAC_URL . 'assets/css/frontend.css', [], SWAC_VERSION, 'all');
    wp_enqueue_style('swac-cart-drawer-style', SWAC_URL . 'assets/css/cart-drawer.css', ['swac-frontend-style'], SWAC_VERSION, 'all');

    // 2. Modular Subsystem Stylesheets (Enqueued conditionally if active and present)
    if (swac_is_module_active('pharmacy') && file_exists(SWAC_PATH . 'assets/css/modules/pharmacy.css')) {
        wp_enqueue_style('swac-pharmacy-style', SWAC_URL . 'assets/css/modules/pharmacy.css', ['swac-frontend-style'], SWAC_VERSION, 'all');
    }

    if (swac_is_module_active('explore') && file_exists(SWAC_PATH . 'assets/css/modules/explore.css')) {
        wp_register_style('swac-explore-style', SWAC_URL . 'assets/css/modules/explore.css', ['swac-frontend-style'], SWAC_VERSION, 'all');
    }

    // 3. Explore Search JS (registered for on-demand enqueue by shortcodes)
    wp_register_script('swac-frontend-script', SWAC_URL . 'assets/js/frontend.js', [], SWAC_VERSION, [
        'strategy'  => 'defer',
        'in_footer' => true,
    ]);

    // 4. WhatsApp Cart Engine (wa_cart.js)
    if (!is_admin()) {
        wp_enqueue_script('swac-cart-script', SWAC_URL . 'assets/js/wa_cart.js', [], SWAC_VERSION, [
            'strategy'  => 'defer',
            'in_footer' => true,
        ]);

        $cfg = swac_get_config();
        $zones = swac_get_delivery_zones();
        $same_day_pins = [];
        $outskirts_pins = [];
        if (!empty($zones['zones']) && is_array($zones['zones'])) {
            foreach ($zones['zones'] as $zone_tier) {
                if (!empty($zone_tier['pincodes']) && is_array($zone_tier['pincodes'])) {
                    $pins = array_map('strval', array_keys($zone_tier['pincodes']));
                    if (!empty($zone_tier['allow_order'])) {
                        $same_day_pins = array_merge($same_day_pins, $pins);
                    } else {
                        $outskirts_pins = array_merge($outskirts_pins, $pins);
                    }
                }
            }
        }

        $localized_zones = [
            'gated'          => !empty($zones['gated']),
            'same_day_pins'  => array_values(array_unique($same_day_pins)),
            'outskirts_pins' => array_values(array_unique($outskirts_pins)),
            'messages'       => $zones['messages'] ?? [],
        ];

        $localized_config = [
            'number'                    => $cfg['phone_number'],
            'name'                      => $cfg['store_name'],
            'baseUrl'                   => $cfg['base_url'],
            'cityName'                  => $cfg['city_name'],
            'currencySymbol'           => $cfg['currency_symbol'],
            'free_shipping_at'          => $cfg['free_shipping_at'],
            'shipping_charge'           => $cfg['shipping_charge'],
            'is_admin'                  => is_user_logged_in(),
            'prescription_note_enabled' => (bool)$cfg['prescription_note_enabled'],
            'prescription_note'         => $cfg['prescription_note'],
            'max_cart_items'            => (int)($cfg['max_cart_items'] ?? 10),
            'max_qty_per_item'          => (int)($cfg['max_qty_per_item'] ?? 10),
            'loyalty'                   => $cfg['loyalty'],
            'timezone_offset'           => function_exists('wp_timezone') ? (float)((wp_timezone()->getOffset(new DateTime('now', new DateTimeZone('UTC')))) / 3600) : 0.0,
            'cutoff_messages'           => [
                'weekend'   => __('Order now to get it by Monday', 'stateless-wa-commerce'),
                'morning'   => __('Order before 12 noon for same-day delivery', 'stateless-wa-commerce'),
                'afternoon' => __('Order now to receive it by tomorrow', 'stateless-wa-commerce'),
            ],
        ];

        // Localize runtime configuration and delivery zones
        wp_localize_script('swac-cart-script', 'swacZones', $localized_zones);
        wp_localize_script('swac-cart-script', 'swacCommerce', $localized_config);
    }
});
