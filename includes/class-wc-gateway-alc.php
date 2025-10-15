<?php
/**
 * ALC Payment WooCommerce Gateway class.
 */

defined('ABSPATH') || exit;

/**
 * Core gateway implementation used by WooCommerce checkout.
 */
class WC_Gateway_alc extends WC_Payment_Gateway
{
    /**
     * Default disclaimer highlighted for shoppers.
     */
    public const DEFAULT_PAYMENT_DISCLAIMER = 'Please remember to authorize your credit card for foreign payments !!! This option accepts Discover and JCB Cards. Please note that our processor is based in Asia, so the charge will be made internationally, and your bank could charge you extra for this. Please pay attention to the payment processor in your invoice as these may vary. If you have any questions, please reach out to us directly.';

    /**
     * Indicates whether sandbox mode is enabled.
     */
    protected bool $test_mode = true;

    /**
     * Sandbox credentials provided by ALC Payment.
     */
    protected string $test_merchant_id = '';
    protected string $test_secret_key = '';

    /**
     * Production credentials provided by ALC Payment.
     */
    protected string $live_merchant_id = '';
    protected string $live_secret_key = '';

    /**
     * Active credentials selected according to the current mode.
     */
    protected string $merchant_id = '';
    protected string $secret_key = '';

    /**
     * Flag indicating whether 3D Secure redirects should be requested.
     */
    protected bool $enable_three_d = false;

    /**
     * Checkout disclaimer displayed to shoppers.
     */
    protected string $payment_disclaimer = '';

    public function __construct()
    {
        $this->id = 'alc';
        $this->icon = ''; // Add 32x32 icon URL when assets are ready.
        $this->method_title = __('Credit Card', 'alc-woocommerce');
        $this->method_description = __('Accept credit card payments securely through this gateway.', 'alc-woocommerce');
        $this->has_fields = true;
        $this->supports = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->payment_disclaimer = $this->get_option('payment_disclaimer', self::DEFAULT_PAYMENT_DISCLAIMER);

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
                'title'   => __('Enable/Disable', 'alc-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable credit card payments', 'alc-woocommerce'),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'alc-woocommerce'),
                'type'        => 'text',
                'description' => __('Title shown to customers during checkout.', 'alc-woocommerce'),
                'default'     => __('Credit Card', 'alc-woocommerce'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __('Description', 'alc-woocommerce'),
                'type'        => 'textarea',
                'description' => __('Payment method description displayed at checkout.', 'alc-woocommerce'),
                'default'     => __('Pay securely using your credit card.', 'alc-woocommerce'),
                'desc_tip'    => true,
            ],
            'payment_disclaimer' => [
                'title'       => __('Payment disclaimer', 'alc-woocommerce'),
                'type'        => 'textarea',
                'description' => __('Important notice shown to customers underneath the payment method.', 'alc-woocommerce'),
                'default'     => self::DEFAULT_PAYMENT_DISCLAIMER,
                'desc_tip'    => true,
            ],
            'test_mode' => [
                'title'       => __('Test mode', 'alc-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Enable sandbox (test) mode', 'alc-woocommerce'),
                'default'     => 'yes',
                'description' => __('Use sandbox credentials for development and testing.', 'alc-woocommerce'),
            ],
            'enable_three_d' => [
                'title'       => __('3D Secure', 'alc-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Forward shoppers to 3D Secure when required.', 'alc-woocommerce'),
                'default'     => 'no',
                'description' => __('When enabled the gateway will request 3D Secure authentication and redirect customers to the auth3DUrl provided by the payment processor.', 'alc-woocommerce'),
                'desc_tip'    => true,
            ],
            'sandbox_credentials_title' => [
                'title'       => __('Sandbox credentials', 'alc-woocommerce'),
                'type'        => 'title',
                'description' => __('Enter the ALC Payment sandbox (test) credentials supplied for development.', 'alc-woocommerce'),
            ],
            'test_merchant_id' => [
                'title'       => __('Sandbox Merchant ID', 'alc-woocommerce'),
                'type'        => 'text',
                'description' => __('Your ALC Payment sandbox merchant identifier.', 'alc-woocommerce'),
                'default'     => '',
            ],
            'test_secret_key' => [
                'title'       => __('Sandbox Secret Key', 'alc-woocommerce'),
                'type'        => 'password',
                'description' => __('API key used to authenticate requests against the ALC Payment sandbox environment.', 'alc-woocommerce'),
                'default'     => '',
            ],
            'production_credentials_title' => [
                'title'       => __('Production credentials', 'alc-woocommerce'),
                'type'        => 'title',
                'description' => __('Enter the live credentials that will be used when test mode is disabled.', 'alc-woocommerce'),
            ],
            'live_merchant_id' => [
                'title'       => __('Production Merchant ID', 'alc-woocommerce'),
                'type'        => 'text',
                'description' => __('Your ALC Payment production merchant identifier.', 'alc-woocommerce'),
                'default'     => '',
            ],
            'live_secret_key' => [
                'title'       => __('Production Secret Key', 'alc-woocommerce'),
                'type'        => 'password',
                'description' => __('API key used to authenticate requests against the ALC Payment production environment.', 'alc-woocommerce'),
                'default'     => '',
            ],
        ];
    }

    /**
     * Output payment fields on checkout.
     */
    public function payment_fields(): void
    {
        parent::payment_fields();

        if ($this->payment_disclaimer) {
            // Instructional: keep the disclaimer editable while ensuring it is emphasised for customers.
            echo '<p class="alc-payment-disclaimer"><strong>' . wp_kses_post($this->payment_disclaimer) . '</strong></p>';
        }

        $posted_holder  = isset($_POST['alc_card_holder']) ? wp_unslash($_POST['alc_card_holder']) : '';
        $posted_card    = isset($_POST['alc_card_number']) ? wp_unslash($_POST['alc_card_number']) : '';
        $posted_month   = isset($_POST['alc_expiry_month']) ? wp_unslash($_POST['alc_expiry_month']) : '';
        $posted_year    = isset($_POST['alc_expiry_year']) ? wp_unslash($_POST['alc_expiry_year']) : '';
        $posted_cvc     = isset($_POST['alc_card_cvc']) ? wp_unslash($_POST['alc_card_cvc']) : '';

        echo '<fieldset id="wc-alc-cc-form" class="wc-credit-card-form wc-payment-form">';
        // Persist the card holder for both classic checkout and Blocks submissions.
        echo '<p class="form-row form-row-wide">';
        echo '<label for="alc_card_holder">' . esc_html__('Card holder name', 'alc-woocommerce') . ' <span class="required">*</span></label>';
        echo '<input id="alc_card_holder" name="alc_card_holder" type="text" autocomplete="cc-name" placeholder="' . esc_attr__('Jane Doe', 'alc-woocommerce') . '" value="' . esc_attr($posted_holder) . '" />';
        echo '</p>';

        echo '<p class="form-row form-row-wide">';
        echo '<label for="alc_card_number">' . esc_html__('Card number', 'alc-woocommerce') . ' <span class="required">*</span></label>';
        echo '<input id="alc_card_number" name="alc_card_number" type="text" autocomplete="cc-number" placeholder="•••• •••• •••• ••••" value="' . esc_attr($posted_card) . '" />';
        echo '</p>';

        echo '<style>#wc-alc-cc-form .alc-expiry-group{display:flex;gap:8px}#wc-alc-cc-form .alc-expiry-group .input-text{flex:1}#wc-alc-cc-form .alc-cvc-field input{max-width:140px}</style>';

        echo '<p class="form-row form-row-first alc-expiry-field">';
        echo '<label for="alc_expiry_month">' . esc_html__('Expiry', 'alc-woocommerce') . ' <span class="required">*</span></label>';
        echo '<span class="alc-expiry-group">';
        echo '<input id="alc_expiry_month" class="input-text" name="alc_expiry_month" type="text" autocomplete="cc-exp-month" placeholder="MM" value="' . esc_attr($posted_month) . '" />';
        echo '<input id="alc_expiry_year" class="input-text" name="alc_expiry_year" type="text" autocomplete="cc-exp-year" placeholder="YYYY" value="' . esc_attr($posted_year) . '" />';
        echo '</span>';
        echo '</p>';

        echo '<p class="form-row form-row-last alc-cvc-field">';
        echo '<label for="alc_card_cvc">' . esc_html__('CVC', 'alc-woocommerce') . ' <span class="required">*</span></label>';
        echo '<input id="alc_card_cvc" name="alc_card_cvc" type="password" autocomplete="cc-csc" placeholder="CVC" value="' . esc_attr($posted_cvc) . '" />';
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
            'alc_card_holder'   => __('Please enter the card holder name.', 'alc-woocommerce'),
            'alc_card_number'   => __('Please enter your card number.', 'alc-woocommerce'),
            'alc_expiry_month'  => __('Please enter the card expiry month.', 'alc-woocommerce'),
            'alc_expiry_year'   => __('Please enter the card expiry year.', 'alc-woocommerce'),
            'alc_card_cvc'      => __('Please enter the card CVC.', 'alc-woocommerce'),
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
     * Trigger the payment request with ALC Payment.
     *
     * @param int $order_id
     * @return array<string, string>
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);

        if (! $order) {
            wc_add_notice(__('Unable to initialize the credit card payment.', 'alc-woocommerce'), 'error');

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

        $card_number = preg_replace('/\D+/', '', $this->get_posted_payment_field('alc_card_number')) ?: '';
        $order_created = $order->get_date_created();
        $bill_number = substr(preg_replace('/\D+/', '', (string) $order->get_id() . ($order_created ? $order_created->getTimestamp() : time())), 0, 30);
        // Combine the order ID with its creation timestamp to keep a digit-only, <=30 char unique bill number per order.
        $billing_state  = sanitize_text_field($order->get_billing_state());
        $shipping_state = sanitize_text_field($order->get_shipping_state());

        $billing_first_name = substr(sanitize_text_field($order->get_billing_first_name()), 0, 60);
        $billing_last_name  = substr(sanitize_text_field($order->get_billing_last_name()), 0, 30);
        $billing_city       = sanitize_text_field($order->get_billing_city()) ?: 'NA'; // ALC Payment "must include" text placeholder when billing city absent.
        $billing_address    = trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2()) ?: 'NA'; // ALC Payment "must include" text placeholder when billing address absent.
        $billing_zip        = sanitize_text_field($order->get_billing_postcode()) ?: '000000'; // ALC Payment "must include" numeric placeholder when billing postcode absent.
        $billing_email      = sanitize_email($order->get_billing_email()) ?: 'NA'; // ALC Payment "must include" text placeholder when billing email absent.
        $billing_phone      = sanitize_text_field($order->get_billing_phone()) ?: '0000000000'; // ALC Payment "must include" numeric placeholder when billing phone absent.

        $shipping_first_name = substr(sanitize_text_field($order->get_shipping_first_name()), 0, 60) ?: ($billing_first_name ?: 'NA'); // ALC Payment "must include" cascade through billing then placeholder.
        $shipping_last_name  = substr(sanitize_text_field($order->get_shipping_last_name()), 0, 30) ?: ($billing_last_name ?: 'NA'); // ALC Payment "must include" cascade through billing then placeholder.
        $shipping_country    = strtoupper((string) $order->get_shipping_country()) ?: (strtoupper((string) $order->get_billing_country()) ?: 'NA'); // ALC Payment "must include" cascade through billing then placeholder.
        $shipping_city       = sanitize_text_field($order->get_shipping_city()) ?: $billing_city; // ALC Payment "must include" cascade through billing then placeholder.
        $shipping_address    = trim($order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2()) ?: $billing_address; // ALC Payment "must include" cascade through billing then placeholder.
        $shipping_zip        = sanitize_text_field($order->get_shipping_postcode()) ?: $billing_zip; // ALC Payment "must include" cascade through billing then placeholder.
        $shipping_email      = method_exists($order, 'get_shipping_email') ? sanitize_email((string) $order->get_shipping_email()) : '';
        $shipping_email      = $shipping_email ?: $billing_email; // ALC Payment "must include" cascade through billing then placeholder.
        $shipping_phone_raw  = method_exists($order, 'get_shipping_phone') ? (string) $order->get_shipping_phone() : '';
        $shipping_phone      = sanitize_text_field($shipping_phone_raw) ?: $billing_phone; // ALC Payment "must include" cascade through billing then placeholder.

        $request_args = [
            'merNo'             => $credentials['merchant_id'],
            'amount'            => number_format((float) $order->get_total(), 2, '.', ''),
            'billNo'            => $bill_number,
            'currency'          => $currency_map[$order->get_currency()] ?? $order->get_currency(),
            'returnURL'         => $this->get_return_url($order),
            'notifyUrl'         => home_url('/wc-api/alc-notify'),
            'tradeUrl'          => home_url('/'),
            'lastName'          => $billing_last_name,
            'firstName'         => $billing_first_name,
            'country'           => strtoupper((string) $order->get_billing_country()),
            'state'             => '' !== $billing_state ? $billing_state : 'NA', // ALC Payment requires a non-empty state placeholder.
            'city'              => $billing_city,
            'address'           => $billing_address,
            'zipCode'           => $billing_zip,
            'email'             => $billing_email,
            'phone'             => $billing_phone,
            'cardNum'           => $card_number,
            'year'              => substr(preg_replace('/\D+/', '', $this->get_posted_payment_field('alc_expiry_year')), -4),
            'month'             => str_pad(preg_replace('/\D+/', '', $this->get_posted_payment_field('alc_expiry_month')), 2, '0', STR_PAD_LEFT),
            'cvv2'              => preg_replace('/\D+/', '', $this->get_posted_payment_field('alc_card_cvc')),
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
            'isThreeDPay'       => $this->enable_three_d ? 'Y' : 'N',
            'language'          => 'EN',
        ];

        $request_args['md5Info'] = $this->build_signature($request_args, $credentials['secret_key']);

        /**
         * Allow automated tests to override the final request payload before dispatch.
         */
        $request_args = apply_filters('wc_alc_payment_request_args', $request_args, $order);

        $response = wp_remote_post(
            apply_filters('wc_alc_payment_endpoint', $endpoint, $order, $request_args),
            [
                'timeout' => 60,
                'body'    => $request_args,
            ]
        );

        if (is_wp_error($response)) {
            wc_get_logger()->error($response->get_error_message(), ['source' => $this->id]);
            wc_add_notice(__('Payment error: please try again or use a different card.', 'alc-woocommerce'), 'error');

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
                'ALC Payment response signature verification failed: ' . wp_json_encode($safe_log_data),
                ['source' => $this->id]
            );
            wc_add_notice(__('Payment error: please try again or use a different card.', 'alc-woocommerce'), 'error');

            return [
                'result'   => 'failure',
                'redirect' => '',
            ];
        }

        $safe_log_data = array_intersect_key(
            (array) $parsed_body,
            array_flip(['code', 'message', 'orderNo', 'billNo'])
        );
        wc_get_logger()->info('ALC Payment response: ' . wp_json_encode($safe_log_data), ['source' => $this->id]);

        /**
         * Surface responses to automated tests and extensions.
         */
        do_action('wc_alc_payment_response', $parsed_body, $order);

        $transaction_ref = isset($parsed_body['orderNo']) ? sanitize_text_field((string) $parsed_body['orderNo']) : '';
        if ('' !== $transaction_ref) {
            $order->update_meta_data('_alc_transaction_ref', $transaction_ref);
        }

        $response_code = isset($parsed_body['code']) ? (string) $parsed_body['code'] : '';

        if ('Q0001' === $response_code) {
            $auth_url = isset($parsed_body['auth3DUrl']) ? esc_url_raw((string) $parsed_body['auth3DUrl']) : '';

            if ($this->enable_three_d && '' !== $auth_url) {
                // Documented 3DS hand-off: ALC Payment expects the shopper to complete authentication at the supplied URL.
                $order->update_status('pending', __('Awaiting 3D Secure authentication.', 'alc-woocommerce'));
                $order->add_order_note(__('Customer redirected to 3D Secure authentication.', 'alc-woocommerce'));
                $order->save();

                return [
                    'result'   => 'success',
                    'redirect' => $auth_url,
                ];
            }
        }

        if ('P0001' !== $response_code) {
            $message = isset($parsed_body['message']) ? wp_strip_all_tags((string) $parsed_body['message']) : __('Unable to process the credit card payment.', 'alc-woocommerce');
            // Provide customers with a consistently formatted failure notice.
            $formatted_notice = sprintf(__('Transaction failed, error: %s', 'alc-woocommerce'), $message);
            wc_add_notice($formatted_notice, 'error');

            return [
                'result'   => 'failure',
                'redirect' => '',
            ];
        }

        $order->add_order_note(
            sprintf(
                __('Transaction approved. Reference: %s', 'alc-woocommerce'),
                $transaction_ref ?: __('not provided', 'alc-woocommerce')
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
     * Build the ALC Payment signature string for requests, responses, and future webhook payloads.
     */
    protected function build_signature(array $payload, string $secret_key): string
    {
        ksort($payload);

        $segments = [];

        foreach ($payload as $key => $value) {
            if ('md5Info' === $key) {
                continue; // Skip the signature itself as per ALC Payment docs.
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
        $this->enable_three_d = 'yes' === $this->get_option('enable_three_d', 'no');

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
        $card_number = preg_replace('/\D+/', '', $this->get_posted_payment_field('alc_card_number')) ?: '';
        $expiry_month = sanitize_text_field($this->get_posted_payment_field('alc_expiry_month'));
        $expiry_year = sanitize_text_field($this->get_posted_payment_field('alc_expiry_year'));

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
