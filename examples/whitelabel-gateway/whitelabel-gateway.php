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

// Keeping a version constant makes it easier to bust browser caches for scripts and styles.
define('WL_GATEWAY_VERSION', '0.1.0');

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

/**
 * Register the gateway integration with WooCommerce Blocks checkout.
 */
function wl_gateway_register_blocks_support(): void
{
    if (! class_exists('\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType')) {
        // Exit early when the store is still using shortcode checkout only.
        return;
    }

    require_once WL_GATEWAY_PLUGIN_PATH . 'class-wc-gateway-example-blocks.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        static function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry): void {
            // Register the payment method type so it appears in the block based checkout.
            $payment_method_registry->register(new WC_Gateway_Example_Blocks());
        }
    );
}
add_action('woocommerce_blocks_loaded', 'wl_gateway_register_blocks_support');
