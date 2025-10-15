<?php
/**
 * Plugin Name:       Example Payment Gateway (Whitelabel Template)
 * Description:       Minimal template to create a custom WooCommerce payment gateway.
 * Author:            Your Company Name
 * Version:           0.1.0
 * Requires Plugins:  woocommerce
 */

defined('ABSPATH') || exit;

// Use a unique prefix to avoid collisions with other gateways.
define('WL_GATEWAY_ID', 'example_gateway');

define('WL_GATEWAY_PLUGIN_FILE', __FILE__);

define('WL_GATEWAY_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Load the gateway when WooCommerce finishes loading.
 */
function wl_gateway_plugins_loaded(): void
{
    if (! class_exists('WC_Payment_Gateway')) {
        // WooCommerce is required for payment gateways to work.
        return;
    }

    // Include the gateway class file. Keep the class in a separate file for clarity.
    require_once WL_GATEWAY_PLUGIN_PATH . 'class-wc-gateway-example.php';
}
add_action('plugins_loaded', 'wl_gateway_plugins_loaded');

/**
 * Register the gateway with WooCommerce.
 *
 * @param string[] $gateways
 * @return string[]
 */
function wl_gateway_register(array $gateways): array
{
    // Tell WooCommerce about our gateway class so it shows up in the checkout settings.
    $gateways[] = 'WC_Gateway_Example';

    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'wl_gateway_register');
