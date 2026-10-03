<?php
/**
 * ⚙️ CONFIGURATION REGISTRY
 *
 * Centralized, filterable configuration for Stateless WhatsApp Commerce.
 * Provides runtime configuration, technical paths, and module feature flags.
 *
 * @package StatelessWaCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

// 🔧 PATH & VERSION CONSTANTS (swac_ prefix compliant with WordPress.org 4+ character guideline)
if (!defined('SWAC_PATH')) {
    define('SWAC_PATH', plugin_dir_path(dirname(__FILE__)) . '/');
}
if (!defined('SWAC_URL')) {
    define('SWAC_URL', plugin_dir_url(dirname(__FILE__)) . '/');
}
if (!defined('SWAC_VERSION')) {
    define('SWAC_VERSION', '1.2.1');
}


if (!function_exists('swac_get_config')) {
    /**
     * Retrieve complete plugin operational configuration.
     *
     * @return array<string, mixed>
     */
    function swac_get_config(): array
    {
        static $resolved_config = null;

        if ($resolved_config !== null) {
            return $resolved_config;
        }

        $currency_symbol = function_exists('get_woocommerce_currency_symbol')
            ? get_woocommerce_currency_symbol()
            : '$';

        $store_name = get_bloginfo('name') ?: 'Store';

        $defaults = [
            'phone_number'              => '', // E.164 without '+', e.g. '1234567890'
            'store_name'                => $store_name,
            'base_url'                  => '',
            'free_shipping_at'          => 0.0,
            'shipping_charge'           => 0.0,
            'currency_symbol'           => $currency_symbol,
            'city_name'                 => '', // Optional geographical focus (e.g. 'Los Angeles')
            'max_cart_items'            => 10,  // Max distinct items per order (URL length protection)
            'max_qty_per_item'          => 10,  // Max units allowed per item (retail limit)
            'order_disclaimer'          => __('⚡ Final bill & product availability will be confirmed on WhatsApp.', 'stateless-wa-commerce'),
            'prescription_note_enabled' => false,
            'prescription_note'         => '',
            'loyalty'                   => [
                'enabled'    => false,
                'threshold'  => 0,
                'item_label' => '',
                'item_worth' => 0,
                'item_price' => 0,
                'flag'       => 'OFFER ACCEPTED ✅',
            ],
            // Optional modules: 'pharmacy', 'explore', 'seo' (disabled by default)
            // To enable a module, add its name inside the brackets below, e.g.:
            // 'modules'                   => ['pharmacy'],
            'modules'                   => [],
        ];

        /**
         * Filter global commerce configuration.
         *
         * @param array $defaults Default configuration values.
         */
        $config = apply_filters('swac_commerce_config', $defaults);

        if (empty($config['base_url']) && !empty($config['phone_number'])) {
            $config['base_url'] = 'https://api.whatsapp.com/send?phone=' . preg_replace('/[^0-9]/', '', (string)$config['phone_number']);
        }

        $resolved_config = $config;
        return $resolved_config;
    }
}

if (!function_exists('swac_is_module_active')) {
    /**
     * Check if a specific subsystem module is enabled.
     *
     * @param string $module Module key ('pharmacy', 'explore', 'seo').
     * @return bool
     */
    function swac_is_module_active(string $module): bool
    {
        $config = swac_get_config();
        $active_modules = apply_filters('swac_active_modules', $config['modules'] ?? []);

        return in_array($module, (array)$active_modules, true);
    }
}
