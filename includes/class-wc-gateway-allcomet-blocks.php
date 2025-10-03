<?php
/**
 * WooCommerce Blocks integration for AllComet gateway.
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined('ABSPATH') || exit;

/**
 * Registers the AllComet payment method with WooCommerce Blocks checkout.
 */
class WC_Gateway_Allcomet_Blocks extends AbstractPaymentMethodType
{
    /**
     * Payment method identifier matching the core gateway class.
     */
    protected $name = 'allcomet';

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
                'title'       => __('Credit Card (AllComet)', 'allcomet-woocommerce'),
                'description' => __('Pay securely using your credit card via AllComet.', 'allcomet-woocommerce'),
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
        $handle = 'allcomet-gateway-blocks';

        if (! wp_script_is($handle, 'registered')) {
            $script_url = plugins_url('assets/js/allcomet-gateway-blocks.js', ALLCOMET_GATEWAY_PLUGIN_FILE);

            wp_register_script(
                $handle,
                $script_url,
                [
                    'wc-blocks-registry',
                    'wc-settings',
                    'wp-element',
                    'wp-i18n',
                ],
                ALLCOMET_GATEWAY_VERSION,
                true
            );

            wp_set_script_translations($handle, 'allcomet-woocommerce', dirname(plugin_basename(ALLCOMET_GATEWAY_PLUGIN_FILE)) . '/languages');
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
            'supports'    => ['products'],
            'i18n'        => [
                'cardNumber'  => __('Please enter your card number.', 'allcomet-woocommerce'),
                'expiryMonth' => __('Please enter the card expiry month.', 'allcomet-woocommerce'),
                'expiryYear'  => __('Please enter the card expiry year.', 'allcomet-woocommerce'),
                'cvc'         => __('Please enter the card CVC.', 'allcomet-woocommerce'),
            ],
        ];
    }
}
