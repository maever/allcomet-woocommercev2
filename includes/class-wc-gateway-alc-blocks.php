<?php
/**
 * WooCommerce Blocks integration for ALC Payment gateway.
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined('ABSPATH') || exit;

/**
 * Registers the ALC Payment method with WooCommerce Blocks checkout.
 */
class WC_Gateway_alc_Blocks extends AbstractPaymentMethodType
{
    /**
     * Payment method identifier matching the core gateway class.
     */
    protected $name = 'alc';

    /**
     * Initialize integration settings.
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
                'title'       => __('Credit Card', 'alc-woocommerce'),
                'description' => __('Pay securely using your credit card.', 'alc-woocommerce'),
                'payment_disclaimer' => WC_Gateway_alc::DEFAULT_PAYMENT_DISCLAIMER,
            ],
            $settings
        );
    }

    /**
     * Determine if the payment method should be available in Blocks checkout.
     */
    public function is_active(): bool
    {
        return 'yes' === $this->get_setting('enabled', 'no');
    }

    /**
     * Register scripts used by the payment method.
     *
     * @return string[]
     */
    public function get_payment_method_script_handles(): array
    {
        $handle = 'alc-gateway-blocks';

        if (! wp_script_is($handle, 'registered')) {
            $script_url = plugins_url('assets/js/alc-gateway-blocks.js', ALC_GATEWAY_PLUGIN_FILE);

            wp_register_script(
                $handle,
                $script_url,
                [
                    'wc-blocks-registry',
                    'wc-settings',
                    'wp-element',
                    'wp-i18n',
                ],
                ALC_GATEWAY_VERSION,
                true
            );

            wp_set_script_translations($handle, 'alc-woocommerce', dirname(plugin_basename(ALC_GATEWAY_PLUGIN_FILE)) . '/languages');
        }

        return [$handle];
    }

    /**
     * Provide data to the payment method script.
     *
     * @return array<string, mixed>
     */
    public function get_payment_method_data(): array
    {
        return [
            'title'       => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'paymentDisclaimer' => $this->get_setting('payment_disclaimer', WC_Gateway_alc::DEFAULT_PAYMENT_DISCLAIMER),
            'supports'    => ['products'],
            'i18n'        => [
                'cardHolder'  => __('Please enter the card holder name.', 'alc-woocommerce'),
                'cardNumber'  => __('Please enter your card number.', 'alc-woocommerce'),
                'expiryMonth' => __('Please enter the card expiry month.', 'alc-woocommerce'),
                'expiryYear'  => __('Please enter the card expiry year.', 'alc-woocommerce'),
                'cvc'         => __('Please enter the card CVC.', 'alc-woocommerce'),
            ],
        ];
    }
}
