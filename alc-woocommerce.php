<?php
/**
 * Plugin Name:       ALC Payment Gateway (V2)
 * Plugin URI:        https://alc-payment.example/
 * Description:       Accept credit card payments through ALC Payment in WooCommerce stores.
 * Version:           0.1.0
 * Author:            ALC Payment
 * Author URI:        https://alc-payment.example/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       alc-woocommerce
 * Domain Path:       /languages
 */

defined('ABSPATH') || exit;

define('ALC_GATEWAY_VERSION', '0.1.0');
define('ALC_GATEWAY_PLUGIN_FILE', __FILE__);
define('ALC_GATEWAY_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Check for WooCommerce dependency on activation.
 */
function alc_gateway_activation_check(): void
{
    if (! class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(ALC_GATEWAY_PLUGIN_FILE));
        wp_die(
            esc_html__('WooCommerce must be active to use the ALC Payment gateway.', 'alc-woocommerce'),
            esc_html__('Plugin dependency check failed', 'alc-woocommerce'),
            ['back_link' => true]
        );
    }
}
register_activation_hook(ALC_GATEWAY_PLUGIN_FILE, 'alc_gateway_activation_check');

/**
 * Load translations.
 */
function alc_gateway_load_textdomain(): void
{
    load_plugin_textdomain('alc-woocommerce', false, dirname(plugin_basename(ALC_GATEWAY_PLUGIN_FILE)) . '/languages');
}
add_action('init', 'alc_gateway_load_textdomain');

/**
 * Load gateway files when WooCommerce is ready.
 */
function alc_gateway_plugins_loaded(): void
{
    if (! class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once ALC_GATEWAY_PLUGIN_PATH . 'includes/class-wc-gateway-alc.php';
}
add_action('plugins_loaded', 'alc_gateway_plugins_loaded');

/**
 * Register gateway with WooCommerce.
 *
 * @param string[] $gateways
 * @return string[]
 */
function alc_gateway_register(array $gateways): array
{
    $gateways[] = 'WC_Gateway_alc';

    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'alc_gateway_register');

/**
 * Register the gateway integration for WooCommerce Blocks checkout.
 */
function alc_gateway_register_blocks_support(): void
{
    if (! class_exists('\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once ALC_GATEWAY_PLUGIN_PATH . 'includes/class-wc-gateway-alc-blocks.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        static function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry): void {
            $payment_method_registry->register(new WC_Gateway_alc_Blocks());
        }
    );
}
add_action('woocommerce_blocks_loaded', 'alc_gateway_register_blocks_support');

/**
 * Add custom action links on the plugins screen.
 *
 * @param string[] $links
 * @return string[]
 */
function alc_gateway_plugin_action_links(array $links): array
{
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=alc')) . '">' .
        esc_html__('Settings', 'alc-woocommerce') . '</a>';

    array_unshift($links, $settings_link);

    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(ALC_GATEWAY_PLUGIN_FILE), 'alc_gateway_plugin_action_links');

/**
 * Handle the ALC Payment gateway notifications.
 */
function alc_gateway_handle_notify(): void
{
    $raw_body = file_get_contents('php://input');
    if ($raw_body === false) {
        $raw_body = '';
    }

    $payload = null;

    if ($raw_body !== '') {
        $decoded_json = json_decode($raw_body, true);
        if (is_array($decoded_json)) {
            $payload = $decoded_json;
        } else {
            parse_str($raw_body, $parsed_form);
            if (! empty($parsed_form)) {
                $payload = $parsed_form;
            }
        }
    }

    if (! is_array($payload) && ! empty($_REQUEST)) {
        // Form posts from ALC Payment should be unslashed before validation.
        $payload = wp_unslash($_REQUEST); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    }

    if (! is_array($payload)) {
        $payload = [];
    }

    $settings  = get_option('woocommerce_alc_settings', []);
    $settings  = is_array($settings) ? $settings : [];
    $test_mode = isset($settings['test_mode']) ? $settings['test_mode'] : 'yes';
    $secret_key = 'yes' === $test_mode
        ? (string) ($settings['test_secret_key'] ?? '')
        : (string) ($settings['live_secret_key'] ?? '');

    if ('' === $secret_key) {
        wc_get_logger()->error('ALC Payment notify secret key missing for signature verification.', ['source' => 'alc']);
        wp_send_json_error(['message' => __('Signature verification failed.', 'alc-woocommerce')], 400);

        return;
    }

    $provided_signature = isset($payload['md5Info']) ? strtoupper((string) $payload['md5Info']) : '';
    $signature_payload  = $payload;
    unset($signature_payload['md5Info']);
    ksort($signature_payload);
    array_walk(
        $signature_payload,
        static function (&$value): void {
            if (is_array($value)) {
                $value = wp_json_encode($value);
            }
        }
    );
    // API section 1.6 expects signatures calculated over the URL-encoded key/value pairs.
    $signature_query    = http_build_query($signature_payload, '', '&');
    $signature_base     = $signature_query . '&key=' . $secret_key;
    $expected_signature = strtoupper(md5($signature_base));

    if ($provided_signature === '' || $expected_signature !== $provided_signature) {
        // API section 1.6 requires validating the notification signature before acknowledging receipt.
        $sanitized_snapshot = wc_clean($payload);
        wc_get_logger()->error(
            'ALC Payment notify signature verification failed: ' . wp_json_encode($sanitized_snapshot),
            ['source' => 'alc']
        );
        wp_send_json_error(['message' => __('Signature verification failed.', 'alc-woocommerce')], 400);

        return;
    }

    $sanitized_snapshot = wc_clean($payload);
    wc_get_logger()->info(
        'ALC Payment notify payload verified: ' . wp_json_encode($sanitized_snapshot),
        ['source' => 'alc']
    );

    wp_send_json_success();
}
add_action( 'woocommerce_api_alc-notify', 'alc_gateway_handle_notify' );
