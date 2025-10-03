<?php
/**
 * AllComet WooCommerce Gateway class.
 */

defined('ABSPATH') || exit;

/**
 * Core gateway implementation used by WooCommerce checkout.
 */
class WC_Gateway_Allcomet extends WC_Payment_Gateway
{
    /**
     * Merchant ID credential.
     *
     * @var string
     */
    protected string $merchant_id = '';

    /**
     * Secret key credential.
     *
     * @var string
     */
    protected string $secret_key = '';

    public function __construct()
    {
        $this->id = 'allcomet';
        $this->icon = ''; // Add 32x32 icon URL when assets are ready.
        $this->method_title = __('AllComet', 'allcomet-woocommerce');
        $this->method_description = __('Accept credit card payments securely through the AllComet gateway.', 'allcomet-woocommerce');
        $this->has_fields = true;
        $this->supports = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->merchant_id = (string) $this->get_option('merchant_id');
        $this->secret_key = (string) $this->get_option('secret_key');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Define gateway settings displayed in WooCommerce admin.
     */
    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'allcomet-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable AllComet payments', 'allcomet-woocommerce'),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'allcomet-woocommerce'),
                'type'        => 'text',
                'description' => __('Title shown to customers during checkout.', 'allcomet-woocommerce'),
                'default'     => __('Credit Card (AllComet)', 'allcomet-woocommerce'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __('Description', 'allcomet-woocommerce'),
                'type'        => 'textarea',
                'description' => __('Payment method description displayed at checkout.', 'allcomet-woocommerce'),
                'default'     => __('Pay securely using your credit card via AllComet.', 'allcomet-woocommerce'),
                'desc_tip'    => true,
            ],
            'merchant_id' => [
                'title'       => __('Merchant ID', 'allcomet-woocommerce'),
                'type'        => 'text',
                'description' => __('Your AllComet merchant identifier.', 'allcomet-woocommerce'),
                'default'     => '',
            ],
            'secret_key' => [
                'title'       => __('Secret Key', 'allcomet-woocommerce'),
                'type'        => 'password',
                'description' => __('API key used to authenticate requests to AllComet.', 'allcomet-woocommerce'),
                'default'     => '',
            ],
            'test_mode' => [
                'title'       => __('Test mode', 'allcomet-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Enable sandbox (test) mode', 'allcomet-woocommerce'),
                'default'     => 'yes',
                'description' => __('Use sandbox credentials for development and testing.', 'allcomet-woocommerce'),
            ],
        ];
    }

    /**
     * Output payment fields on checkout.
     */
    public function payment_fields(): void
    {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }

        // Additional custom fields would be output here when integrating with the AllComet API.
    }

    /**
     * Validate custom fields.
     */
    public function validate_fields(): bool
    {
        // Placeholder for field-level validation when collecting extra checkout data.
        return true;
    }

    /**
     * Trigger the payment request with AllComet.
     *
     * @param int $order_id
     * @return array<string, string>
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);

        if (! $order) {
            wc_add_notice(__('Unable to initialize the AllComet payment.', 'allcomet-woocommerce'), 'error');

            return [
                'result'   => 'failure',
                'redirect' => '',
            ];
        }

        // TODO: Replace with live request to AllComet API.
        $order->add_order_note(__('AllComet payment processed in sandbox mode (no API call).', 'allcomet-woocommerce'));
        $order->payment_complete();

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}
