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

        $posted_holder  = isset($_POST['allcomet_card_holder']) ? wp_unslash($_POST['allcomet_card_holder']) : '';
        $posted_card    = isset($_POST['allcomet_card_number']) ? wp_unslash($_POST['allcomet_card_number']) : '';
        $posted_month   = isset($_POST['allcomet_expiry_month']) ? wp_unslash($_POST['allcomet_expiry_month']) : '';
        $posted_year    = isset($_POST['allcomet_expiry_year']) ? wp_unslash($_POST['allcomet_expiry_year']) : '';
        $posted_cvc     = isset($_POST['allcomet_card_cvc']) ? wp_unslash($_POST['allcomet_card_cvc']) : '';

        echo '<fieldset id="wc-allcomet-cc-form" class="wc-credit-card-form wc-payment-form">';
        // Persist the card holder for both classic checkout and Blocks submissions.
        echo '<p class="form-row form-row-wide">';
        echo '<label for="allcomet_card_holder">' . esc_html__('Card holder name', 'allcomet-woocommerce') . ' <span class="required">*</span></label>';
        echo '<input id="allcomet_card_holder" name="allcomet_card_holder" type="text" autocomplete="cc-name" placeholder="' . esc_attr__('Jane Doe', 'allcomet-woocommerce') . '" value="' . esc_attr($posted_holder) . '" />';
        echo '</p>';

        echo '<p class="form-row form-row-wide">';
        echo '<label for="allcomet_card_number">' . esc_html__('Card number', 'allcomet-woocommerce') . ' <span class="required">*</span></label>';
        echo '<input id="allcomet_card_number" name="allcomet_card_number" type="text" autocomplete="cc-number" placeholder="•••• •••• •••• ••••" value="' . esc_attr($posted_card) . '" />';
        echo '</p>';

        echo '<style>#wc-allcomet-cc-form .allcomet-expiry-group{display:flex;gap:8px}#wc-allcomet-cc-form .allcomet-expiry-group .input-text{flex:1}#wc-allcomet-cc-form .allcomet-cvc-field input{max-width:140px}</style>';

        echo '<p class="form-row form-row-first allcomet-expiry-field">';
        echo '<label for="allcomet_expiry_month">' . esc_html__('Expiry', 'allcomet-woocommerce') . ' <span class="required">*</span></label>';
        echo '<span class="allcomet-expiry-group">';
        echo '<input id="allcomet_expiry_month" class="input-text" name="allcomet_expiry_month" type="text" autocomplete="cc-exp-month" placeholder="MM" value="' . esc_attr($posted_month) . '" />';
        echo '<input id="allcomet_expiry_year" class="input-text" name="allcomet_expiry_year" type="text" autocomplete="cc-exp-year" placeholder="YYYY" value="' . esc_attr($posted_year) . '" />';
        echo '</span>';
        echo '</p>';

        echo '<p class="form-row form-row-last allcomet-cvc-field">';
        echo '<label for="allcomet_card_cvc">' . esc_html__('CVC', 'allcomet-woocommerce') . ' <span class="required">*</span></label>';
        echo '<input id="allcomet_card_cvc" name="allcomet_card_cvc" type="password" autocomplete="cc-csc" placeholder="CVC" value="' . esc_attr($posted_cvc) . '" />';
        echo '</p>';

        echo '<div class="clear"></div>';
        echo '</fieldset>';
    }

    /**
     * Validate custom fields.
     */
    public function validate_fields(): bool
    {
        $this->log_checkout_snapshot('Validation snapshot');

        $required_fields = [
            'allcomet_card_holder'   => __('Please enter the card holder name.', 'allcomet-woocommerce'),
            'allcomet_card_number'   => __('Please enter your card number.', 'allcomet-woocommerce'),
            'allcomet_expiry_month'  => __('Please enter the card expiry month.', 'allcomet-woocommerce'),
            'allcomet_expiry_year'   => __('Please enter the card expiry year.', 'allcomet-woocommerce'),
            'allcomet_card_cvc'      => __('Please enter the card CVC.', 'allcomet-woocommerce'),
        ];

        foreach ($required_fields as $field => $message) {
            $value = $this->get_posted_payment_field($field);

            if ('' === trim($value)) {
                wc_add_notice($message, 'error');

                return false;
            }
        }

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

        $this->log_checkout_snapshot('Checkout initiated');

        $credentials = $this->get_active_credentials();
        $endpoint = 'https://api.thelinemall.com/apiv2/pay';

        $currency_map = [
            'USD' => '1',
            'EUR' => '2',
            'RMB' => '3',
            'GBP' => '4',
            'HKD' => '5',
            'JPY' => '6',
            'AUD' => '7',
            'NOK' => '8',
            'CAD' => '11',
            'DKK' => '12',
            'SEK' => '13',
            'TWD' => '14',
        ];

        $card_number = preg_replace('/\D+/', '', $this->get_posted_payment_field('allcomet_card_number')) ?: '';
        $order_created = $order->get_date_created();
        $bill_number = substr(preg_replace('/\D+/', '', (string) $order->get_id() . ($order_created ? $order_created->getTimestamp() : time())), 0, 30);
        // Combine the order ID with its creation timestamp to keep a digit-only, <=30 char unique bill number per order.
        $billing_state  = sanitize_text_field($order->get_billing_state());
        $shipping_state = sanitize_text_field($order->get_shipping_state());

        $billing_first_name = substr(sanitize_text_field($order->get_billing_first_name()), 0, 60);
        $billing_last_name  = substr(sanitize_text_field($order->get_billing_last_name()), 0, 30);
        $billing_city       = sanitize_text_field($order->get_billing_city()) ?: 'NA'; // AllComet "must include" text placeholder when billing city absent.
        $billing_address    = trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2()) ?: 'NA'; // AllComet "must include" text placeholder when billing address absent.
        $billing_zip        = sanitize_text_field($order->get_billing_postcode()) ?: '000000'; // AllComet "must include" numeric placeholder when billing postcode absent.
        $billing_email      = sanitize_email($order->get_billing_email()) ?: 'NA'; // AllComet "must include" text placeholder when billing email absent.
        $billing_phone      = sanitize_text_field($order->get_billing_phone()) ?: '0000000000'; // AllComet "must include" numeric placeholder when billing phone absent.

        $shipping_first_name = substr(sanitize_text_field($order->get_shipping_first_name()), 0, 60) ?: ($billing_first_name ?: 'NA'); // AllComet "must include" cascade through billing then placeholder.
        $shipping_last_name  = substr(sanitize_text_field($order->get_shipping_last_name()), 0, 30) ?: ($billing_last_name ?: 'NA'); // AllComet "must include" cascade through billing then placeholder.
        $shipping_country    = strtoupper((string) $order->get_shipping_country()) ?: (strtoupper((string) $order->get_billing_country()) ?: 'NA'); // AllComet "must include" cascade through billing then placeholder.
        $shipping_city       = sanitize_text_field($order->get_shipping_city()) ?: $billing_city; // AllComet "must include" cascade through billing then placeholder.
        $shipping_address    = trim($order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2()) ?: $billing_address; // AllComet "must include" cascade through billing then placeholder.
        $shipping_zip        = sanitize_text_field($order->get_shipping_postcode()) ?: $billing_zip; // AllComet "must include" cascade through billing then placeholder.
        $shipping_email      = method_exists($order, 'get_shipping_email') ? sanitize_email((string) $order->get_shipping_email()) : '';
        $shipping_email      = $shipping_email ?: $billing_email; // AllComet "must include" cascade through billing then placeholder.
        $shipping_phone_raw  = method_exists($order, 'get_shipping_phone') ? (string) $order->get_shipping_phone() : '';
        $shipping_phone      = sanitize_text_field($shipping_phone_raw) ?: $billing_phone; // AllComet "must include" cascade through billing then placeholder.

        $request_args = [
            'merNo'             => $credentials['merchant_id'],
            'amount'            => number_format((float) $order->get_total(), 2, '.', ''),
            'billNo'            => $bill_number,
            'currency'          => $currency_map[$order->get_currency()] ?? $order->get_currency(),
            'returnURL'         => $this->get_return_url($order),
            'notifyUrl'         => home_url('/wc-api/allcomet'),
            'tradeUrl'          => home_url('/'),
            'lastName'          => $billing_last_name,
            'firstName'         => $billing_first_name,
            'country'           => strtoupper((string) $order->get_billing_country()),
            'state'             => '' !== $billing_state ? $billing_state : 'NA', // AllComet requires a non-empty state placeholder.
            'city'              => $billing_city,
            'address'           => $billing_address,
            'zipCode'           => $billing_zip,
            'email'             => $billing_email,
            'phone'             => $billing_phone,
            'cardNum'           => $card_number,
            'year'              => substr(preg_replace('/\D+/', '', $this->get_posted_payment_field('allcomet_expiry_year')), -4),
            'month'             => str_pad(preg_replace('/\D+/', '', $this->get_posted_payment_field('allcomet_expiry_month')), 2, '0', STR_PAD_LEFT),
            'cvv2'              => preg_replace('/\D+/', '', $this->get_posted_payment_field('allcomet_card_cvc')),
            'productInfo'       => wp_json_encode($order->get_items() ? wp_list_pluck($order->get_items(), 'name') : ['Order #' . $order->get_id()]),
            'ip'                => WC_Geolocation::get_ip_address(),
            'dataTime'          => gmdate('YmdHis'),
            'shippingFirstName' => $shipping_first_name,
            'shippingLastName'  => $shipping_last_name,
            'shippingCountry'   => $shipping_country,
            'shippingState'     => '' !== $shipping_state ? $shipping_state : ('' !== $billing_state ? $billing_state : 'NA'),
            'shippingCity'      => $shipping_city,
            'shippingAddress'   => $shipping_address,
            'shippingZipCode'   => $shipping_zip,
            'shippingEmail'     => $shipping_email,
            'shippingPhone'     => $shipping_phone,
            'isThreeDPay'       => 'N',
            'language'          => 'EN',
        ];

        $request_args['md5Info'] = $this->build_signature($request_args, $credentials['secret_key']);

        /**
         * Allow automated tests to override the final request payload before dispatch.
         */
        $request_args = apply_filters('wc_allcomet_payment_request_args', $request_args, $order);

        $response = wp_remote_post(
            apply_filters('wc_allcomet_payment_endpoint', $endpoint, $order, $request_args),
            [
                'timeout' => 60,
                'body'    => $request_args,
            ]
        );

        if (is_wp_error($response)) {
            wc_get_logger()->error($response->get_error_message(), ['source' => $this->id]);
            wc_add_notice(__('Payment error: please try again or use a different card.', 'allcomet-woocommerce'), 'error');

            return [
                'result'   => 'failure',
                'redirect' => '',
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $parsed_body = json_decode($body, true);

        if (! is_array($parsed_body)) {
            parse_str($body, $parsed_body);
        }

        $provided_signature = isset($parsed_body['md5Info']) ? (string) $parsed_body['md5Info'] : '';
        $signature_payload  = is_array($parsed_body) ? $parsed_body : [];
        $expected_signature  = $this->build_signature($signature_payload, $credentials['secret_key']);

        if ($provided_signature === '' || $expected_signature !== $provided_signature) {
            // API section 1.6 requires validating the response signature before trusting the payload.
            $safe_log_data = array_intersect_key(
                (array) $parsed_body,
                array_flip(['code', 'message', 'orderNo', 'billNo'])
            );
            wc_get_logger()->error(
                'AllComet response signature verification failed: ' . wp_json_encode($safe_log_data),
                ['source' => $this->id]
            );
            wc_add_notice(__('Payment error: please try again or use a different card.', 'allcomet-woocommerce'), 'error');

            return [
                'result'   => 'failure',
                'redirect' => '',
            ];
        }

        $safe_log_data = array_intersect_key(
            (array) $parsed_body,
            array_flip(['code', 'message', 'orderNo', 'billNo'])
        );
        wc_get_logger()->info('AllComet response: ' . wp_json_encode($safe_log_data), ['source' => $this->id]);

        /**
         * Surface responses to automated tests and extensions.
         */
        do_action('wc_allcomet_payment_response', $parsed_body, $order);

        if (! isset($parsed_body['code']) || 'P0001' !== $parsed_body['code']) {
            $message = isset($parsed_body['message']) ? wp_strip_all_tags((string) $parsed_body['message']) : __('Unable to process the payment with AllComet.', 'allcomet-woocommerce');
            // Provide customers with a consistently formatted failure notice.
            $formatted_notice = sprintf(__('Transaction failed, error: %s', 'allcomet-woocommerce'), $message);
            wc_add_notice($formatted_notice, 'error');

            return [
                'result'   => 'failure',
                'redirect' => '',
            ];
        }

        $transaction_ref = isset($parsed_body['orderNo']) ? sanitize_text_field((string) $parsed_body['orderNo']) : '';
        if ('' !== $transaction_ref) {
            $order->update_meta_data('_allcomet_transaction_ref', $transaction_ref);
        }

        $order->add_order_note(
            sprintf(
                __('AllComet transaction approved. Reference: %s', 'allcomet-woocommerce'),
                $transaction_ref ?: __('not provided', 'allcomet-woocommerce')
            )
        );

        $order->payment_complete($transaction_ref);
        $order->save();

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }

    /**
     * Retrieve a posted payment field from classic or Blocks checkout requests.
     */
    protected function get_posted_payment_field(string $key): string
    {
        if (isset($_POST['payment_method_data']) && is_array($_POST['payment_method_data']) && isset($_POST['payment_method_data'][$key])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return (string) wp_unslash($_POST['payment_method_data'][$key]); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }

        if (isset($_POST[$key])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return (string) wp_unslash($_POST[$key]); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }

        return '';
    }

    /**
     * Build the AllComet signature string for requests, responses, and future webhook payloads.
     */
    protected function build_signature(array $payload, string $secret_key): string
    {
        ksort($payload);

        $segments = [];

        foreach ($payload as $key => $value) {
            if ('md5Info' === $key) {
                continue; // Skip the signature itself as per AllComet docs.
            }

            if (is_array($value)) {
                $value = wp_json_encode($value);
            }

            $segments[] = $key . '=' . $value;
        }

        $signature_base = implode('&', $segments) . '&key=' . $secret_key;

        return md5($signature_base); // Keep lowercase to match the official samples.
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

    /**
     * Write a sanitized snapshot of the checkout payload to the WooCommerce debug log.
     */
    protected function log_checkout_snapshot(string $context): void
    {
        $card_number = preg_replace('/\D+/', '', $this->get_posted_payment_field('allcomet_card_number')) ?: '';
        $expiry_month = sanitize_text_field($this->get_posted_payment_field('allcomet_expiry_month'));
        $expiry_year = sanitize_text_field($this->get_posted_payment_field('allcomet_expiry_year'));

        $prefix = $card_number !== '' ? substr($card_number, 0, 5) : 'n/a';
        $expiry_month = $expiry_month !== '' ? $expiry_month : 'n/a';
        $expiry_year = $expiry_year !== '' ? $expiry_year : 'n/a';

        wc_get_logger()->debug(
            sprintf(
                '[%s] %s. Card prefix: %s, Expiry: %s/%s',
                gmdate('c'),
                $context,
                $prefix,
                $expiry_month,
                $expiry_year
            ),
            ['source' => $this->id]
        );
    }
}
