<?php
/**
 * UNINSTALL: Stateless WhatsApp Commerce for WooCommerce
 *
 * Runs when the plugin is deleted via the WordPress admin.
 * Removes all plugin-created options and transients from the database.
 *
 * @package StatelessWaCommerce
 */

// Safety check - WordPress must trigger this, not direct access.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 1. Remove the explore module cache version counter.
delete_option('swac_explore_cache_version');

// 2. Remove any swac_* transients left by the explore/pharmacy modules.
//    Transients expire naturally, but this gives a clean slate on uninstall.
global $wpdb;

$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_swac_%'
        OR option_name LIKE '_transient_timeout_swac_%'
",
);

// 3. Remove the cron lockdown flag.
delete_option('swac_cron_cleaned');

