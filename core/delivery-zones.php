<?php
/**
 * 🚚 EXTENSIBLE DELIVERY SERVICE ZONES & POSTAL CODE REGISTRY
 *
 * Configurable geographical serviceability engine for customer address validation.
 * Supports Open Mode (accepts any postal code) and Gated Mode (validates against registered tiers).
 *
 * Consumed by:
 *  - PHP: stateless-wa-commerce.php (localizes to window.swacZones via wp_localize_script)
 *  - JS:  assets/js/wa_cart.js (client-side postal gating & real-time address validation)
 *
 * @package StatelessWaCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('swac_get_delivery_zones')) {
    /**
     * Retrieve delivery zones registry.
     *
     * @return array{
     *     gated: bool,
     *     zones: array<string, array{label: string, badge_text: string, status_class: string, allow_order: bool, pincodes: array<string, string>}>,
     *     messages: array<string, string>
     * }
     */
    function swac_get_delivery_zones(): array
    {
        $defaults = [
            'gated'    => false, // Default: Open Mode. Set true to enforce registered postal codes.
            'zones'    => [
                'standard' => [
                    'label'        => __('Standard Delivery', 'stateless-wa-commerce'),
                    'badge_text'   => __('✓ Delivery Available', 'stateless-wa-commerce'),
                    'status_class' => 'swac-pin-success',
                    'allow_order'  => true,
                    'pincodes'     => [], // Associative array of ['postal_code' => 'Area Name']
                ],
            ],
            'messages' => [
                'available'   => __('✓ Delivery is available to your location', 'stateless-wa-commerce'),
                'unsupported' => __('⚠️ We do not deliver to this location yet.', 'stateless-wa-commerce'),
                'invalid'     => __('Please enter a valid postal code.', 'stateless-wa-commerce'),
            ],
        ];

        /**
         * Filter delivery zones and serviceability tiers.
         *
         * @param array $defaults Default delivery zones schema.
         */
        return (array)apply_filters('swac_delivery_zones', $defaults);
    }
}