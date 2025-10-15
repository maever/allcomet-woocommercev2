<?php
/**
 * Example Payment Gateway template.
 */

defined('ABSPATH') || exit;

/**
 * Simple template gateway you can copy and rename.
 */
class WC_Gateway_Example extends WC_Payment_Gateway
{
    /**
     * Bootstraps the gateway.
     */
    public function __construct()
    {
        $this->id                 = WL_GATEWAY_ID; // Replace with your gateway ID.
        $this->method_title       = __('Example Gateway', 'woocommerce');
        $this->method_description = __('Whitelabel template gateway description.', 'woocommerce');
        $this->has_fields         = true;
        $this->supports           = ['products'];

        // Load the gateway settings form fields.
        $this->init_form_fields();

        // Load the saved settings.
        $this->init_settings();

        // Copy this hook when adding custom settings fields.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Define the settings shown in WooCommerce > Settings > Payments.
     * Customize labels and placeholders for the final gateway.
     */
    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable Example Gateway', 'woocommerce'),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Controls the payment method title the customer sees during checkout.', 'woocommerce'),
                'default'     => __('Example Gateway', 'woocommerce'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __('Description', 'woocommerce'),
                'type'        => 'textarea',
                'description' => __('Message shown to customers during checkout. Update with your gateway information.', 'woocommerce'),
                'default'     => __('Pay securely using Example Gateway.', 'woocommerce'),
                'desc_tip'    => true,
            ],
            'api_key' => [
                'title'       => __('API Key', 'woocommerce'),
                'type'        => 'password',
                'description' => __('Store your service credentials securely. Replace with the fields required by your provider.', 'woocommerce'),
            ],
        ];
    }

    /**
     * Present the payment fields at checkout.
     */
    public function payment_fields(): void
    {
        // Add simple placeholder content. Replace with your custom fields or tokenized payment widget.
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }

        echo '<p>' . esc_html__('This is where you collect payment details or embed a hosted payment form.', 'woocommerce') . '</p>';
    }

    /**
     * Process the payment.
     *
     * @param int $order_id
     * @return array<string, string>
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);

        // TODO: Call your payment API here. This template simply marks the order as on-hold for manual capture.
        $order->update_status('on-hold', __('Awaiting manual confirmation from Example Gateway.', 'woocommerce'));

        // Reduce stock and clear the cart if needed.
        wc_reduce_stock_levels($order_id);
        WC()->cart->empty_cart();

        // Return the standard WooCommerce thank-you redirect.
        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}
