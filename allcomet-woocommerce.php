<?php
/**
 * Plugin Name:       AllComet Payment Gateway (V2)
 * Plugin URI:        https://allcomet.com/
 * Description:       Accept credit card payments through AllComet in WooCommerce stores.
 * Version:           0.1.0
 * Author:            AllComet
 * Author URI:        https://allcomet.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       allcomet-woocommerce
 * Domain Path:       /languages
 */

defined('ABSPATH') || exit;

define('ALLCOMET_GATEWAY_VERSION', '0.1.0');
define('ALLCOMET_GATEWAY_PLUGIN_FILE', __FILE__);
define('ALLCOMET_GATEWAY_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Check for WooCommerce dependency on activation.
 */
function allcomet_gateway_activation_check(): void
{
    if (! class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(ALLCOMET_GATEWAY_PLUGIN_FILE));
        wp_die(
            esc_html__('WooCommerce must be active to use the AllComet payment gateway.', 'allcomet-woocommerce'),
            esc_html__('Plugin dependency check failed', 'allcomet-woocommerce'),
            ['back_link' => true]
        );
    }
}
register_activation_hook(ALLCOMET_GATEWAY_PLUGIN_FILE, 'allcomet_gateway_activation_check');

/**
 * Load translations.
 */
function allcomet_gateway_load_textdomain(): void
{
    load_plugin_textdomain('allcomet-woocommerce', false, dirname(plugin_basename(ALLCOMET_GATEWAY_PLUGIN_FILE)) . '/languages');
}
add_action('init', 'allcomet_gateway_load_textdomain');

/**
 * Load gateway files when WooCommerce is ready.
 */
function allcomet_gateway_plugins_loaded(): void
{
    if (! class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once ALLCOMET_GATEWAY_PLUGIN_PATH . 'includes/class-wc-gateway-allcomet.php';
}
add_action('plugins_loaded', 'allcomet_gateway_plugins_loaded');

/**
 * Register gateway with WooCommerce.
 *
 * @param string[] $gateways
 * @return string[]
 */
function allcomet_gateway_register(array $gateways): array
{
    $gateways[] = 'WC_Gateway_Allcomet';

    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'allcomet_gateway_register');

/**
 * Register the gateway integration for WooCommerce Blocks checkout.
 */
function allcomet_gateway_register_blocks_support(): void
{
    if (! class_exists('\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once ALLCOMET_GATEWAY_PLUGIN_PATH . 'includes/class-wc-gateway-allcomet-blocks.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        static function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry): void {
            $payment_method_registry->register(new WC_Gateway_Allcomet_Blocks());
        }
    );
}
add_action('woocommerce_blocks_loaded', 'allcomet_gateway_register_blocks_support');

/**
 * Add custom action links on the plugins screen.
 *
 * @param string[] $links
 * @return string[]
 */
function allcomet_gateway_plugin_action_links(array $links): array
{
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=allcomet')) . '">' .
        esc_html__('Settings', 'allcomet-woocommerce') . '</a>';

    array_unshift($links, $settings_link);

    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(ALLCOMET_GATEWAY_PLUGIN_FILE), 'allcomet_gateway_plugin_action_links');
