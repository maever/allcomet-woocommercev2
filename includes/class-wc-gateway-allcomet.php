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
     * Indicates whether sandbox mode is enabled.
     */
    protected bool $test_mode = true;

    /**
     * Sandbox credentials provided by AllComet.
     */
    protected string $test_merchant_id = '';
    protected string $test_secret_key = '';

    /**
     * Production credentials provided by AllComet.
     */
    protected string $live_merchant_id = '';
    protected string $live_secret_key = '';

    /**
     * Active credentials selected according to the current mode.
     */
    protected string $merchant_id = '';
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

        $this->load_gateway_settings();

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
            'test_mode' => [
                'title'       => __('Test mode', 'allcomet-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Enable sandbox (test) mode', 'allcomet-woocommerce'),
                'default'     => 'yes',
                'description' => __('Use sandbox credentials for development and testing.', 'allcomet-woocommerce'),
            ],
            'sandbox_credentials_title' => [
                'title'       => __('Sandbox credentials', 'allcomet-woocommerce'),
                'type'        => 'title',
                'description' => __('Enter the AllComet sandbox (test) credentials supplied for development.', 'allcomet-woocommerce'),
            ],
            'test_merchant_id' => [
                'title'       => __('Sandbox Merchant ID', 'allcomet-woocommerce'),
                'type'        => 'text',
                'description' => __('Your AllComet sandbox merchant identifier.', 'allcomet-woocommerce'),
                'default'     => '',
            ],
            'test_secret_key' => [
                'title'       => __('Sandbox Secret Key', 'allcomet-woocommerce'),
                'type'        => 'password',
                'description' => __('API key used to authenticate requests against the AllComet sandbox environment.', 'allcomet-woocommerce'),
                'default'     => '',
            ],
            'production_credentials_title' => [
                'title'       => __('Production credentials', 'allcomet-woocommerce'),
                'type'        => 'title',
                'description' => __('Enter the live credentials that will be used when test mode is disabled.', 'allcomet-woocommerce'),
            ],
            'live_merchant_id' => [
                'title'       => __('Production Merchant ID', 'allcomet-woocommerce'),
                'type'        => 'text',
                'description' => __('Your AllComet production merchant identifier.', 'allcomet-woocommerce'),
                'default'     => '',
            ],
            'live_secret_key' => [
                'title'       => __('Production Secret Key', 'allcomet-woocommerce'),
                'type'        => 'password',
                'description' => __('API key used to authenticate requests against the AllComet production environment.', 'allcomet-woocommerce'),
                'default'     => '',
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
     * Persist settings and refresh cached values.
     */
    public function process_admin_options(): bool
    {
        $saved = parent::process_admin_options();

        $this->init_settings();
        $this->load_gateway_settings();

        return $saved;
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

        $credentials = $this->get_active_credentials();
        $mode_label = $credentials['mode'] === 'test'
            ? __('sandbox', 'allcomet-woocommerce')
            : __('production', 'allcomet-woocommerce');

        // TODO: Replace with live request to AllComet API using $credentials.
        $order->add_order_note(
            sprintf(
                /* translators: %s: payment mode (sandbox or production). */
                __('AllComet payment processed in %s mode (no API call).', 'allcomet-woocommerce'),
                $mode_label
            )
        );
        $order->payment_complete();

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }

    /**
     * Load settings from WooCommerce options and cache them on the instance.
     */
    protected function load_gateway_settings(): void
    {
        $this->test_mode = 'yes' === $this->get_option('test_mode', 'yes');
        $this->test_merchant_id = (string) $this->get_option('test_merchant_id');
        $this->test_secret_key = (string) $this->get_option('test_secret_key');
        $this->live_merchant_id = (string) $this->get_option('live_merchant_id');
        $this->live_secret_key = (string) $this->get_option('live_secret_key');

        $this->set_active_credentials();
    }

    /**
     * Determine which credentials should be active and store them on the instance.
     */
    protected function set_active_credentials(): void
    {
        $active = $this->get_active_credentials();
        $this->merchant_id = $active['merchant_id'];
        $this->secret_key = $active['secret_key'];
    }

    /**
     * Return the credentials matching the current mode.
     *
     * @return array{merchant_id:string,secret_key:string,mode:string}
     */
    protected function get_active_credentials(): array
    {
        if ($this->test_mode) {
            return [
                'merchant_id' => $this->test_merchant_id,
                'secret_key'  => $this->test_secret_key,
                'mode'        => 'test',
            ];
        }

        return [
            'merchant_id' => $this->live_merchant_id,
            'secret_key'  => $this->live_secret_key,
            'mode'        => 'live',
        ];
    }
}
