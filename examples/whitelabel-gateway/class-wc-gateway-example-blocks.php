<?php
/**
 * WooCommerce Blocks integration for the whitelabel template gateway.
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined('ABSPATH') || exit;

/**
 * Registers the template gateway with WooCommerce Blocks checkout.
 */
class WC_Gateway_Example_Blocks extends AbstractPaymentMethodType
{
    /**
     * Keep the name in sync with the core gateway class identifier.
     */
    protected $name = WL_GATEWAY_ID;

    /**
     * Prepare the settings shared with the JavaScript integration.
     */
    public function initialize()
    {
        $settings = get_option('woocommerce_' . $this->name . '_settings', []);

        if (! is_array($settings)) {
            $settings = [];
        }

        $this->settings = array_merge(
            [
                'enabled'     => 'no',
                'title'       => __('Example Gateway', 'woocommerce'),
                'description' => __('Pay securely using Example Gateway.', 'woocommerce'),
                'payment_disclaimer' => __('Update this message with the disclaimer you want to highlight to shoppers.', 'woocommerce'),
            ],
            $settings
        );
    }

    /**
     * Check whether the payment method should load in the block based checkout.
     */
    public function is_active(): bool
    {
        return 'yes' === $this->get_setting('enabled', 'no');
    }

    /**
     * Register the JavaScript file that implements the checkout block UI.
     *
     * @return string[]
     */
    public function get_payment_method_script_handles(): array
    {
        $handle = 'wl-gateway-blocks';

        if (! wp_script_is($handle, 'registered')) {
            $script_url = plugins_url('assets/js/example-gateway-blocks.js', WL_GATEWAY_PLUGIN_FILE);

            wp_register_script(
                $handle,
                $script_url,
                [
                    'wc-blocks-registry',
                    'wc-settings',
                    'wp-element',
                    'wp-i18n',
                ],
                WL_GATEWAY_VERSION,
                true
            );

            // Leave this helper in place so you remember to internationalize the checkout strings.
            wp_set_script_translations($handle, 'woocommerce');
        }

        return [$handle];
    }

    /**
     * Share data with the JavaScript integration.
     *
     * @return array<string, mixed>
     */
    public function get_payment_method_data(): array
    {
        return [
            'title'             => $this->get_setting('title'),
            'description'       => $this->get_setting('description'),
            'paymentDisclaimer' => $this->get_setting('payment_disclaimer'),
            'supports'          => ['products'],
            // Expose a simple dropdown so integrators can test different gateway responses.
            'defaultOutcome'    => 'success',
            'outcomeOptions'    => [
                [
                    'value' => 'success',
                    'label' => __('Succeed payment', 'woocommerce'),
                ],
                [
                    'value' => 'failure',
                    'label' => __('Fail payment', 'woocommerce'),
                ],
            ],
            'i18n'              => [
                'outcomeLabel' => __('Payment outcome', 'woocommerce'),
                'outcomeError' => __('Please choose how the test payment should respond.', 'woocommerce'),
            ],
        ];
    }
}
