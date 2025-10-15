(function (window) {
    // Abort when the Blocks runtime is not available (happens on shortcode checkout).
    if (! window.wc || ! window.wc.wcBlocksRegistry || ! window.wc.wcSettings || ! window.wp || ! window.wp.element) {
        return;
    }

    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { getSetting } = window.wc.wcSettings;
    const { createElement, Fragment, useEffect, useState } = window.wp.element;
    const { __ } = window.wp.i18n;

    const gatewayId = 'example_gateway';
    const legacyGatewayId = 'legacy_example_gateway';

    const settings =
        getSetting(`${gatewayId}_data`, null) ||
        getSetting(`${legacyGatewayId}_data`, {});

    const label = settings.title || __('Example Gateway', 'woocommerce');
    const description = settings.description || '';
    const disclaimer = settings.paymentDisclaimer || '';

    const outcomeLabel = settings.i18n && settings.i18n.outcomeLabel
        ? settings.i18n.outcomeLabel
        : __('Payment outcome', 'woocommerce');
    const outcomeError = settings.i18n && settings.i18n.outcomeError
        ? settings.i18n.outcomeError
        : __('Please choose how the test payment should respond.', 'woocommerce');

    const outcomeOptions = Array.isArray(settings.outcomeOptions) && settings.outcomeOptions.length
        ? settings.outcomeOptions
        : [
              { value: 'success', label: __('Succeed payment', 'woocommerce') },
              { value: 'failure', label: __('Fail payment', 'woocommerce') },
          ];

    const PaymentFields = (props) => {
        const { eventRegistration, emitResponse } = props;
        const responseTypes = emitResponse && emitResponse.responseTypes ? emitResponse.responseTypes : { ERROR: 'ERROR', SUCCESS: 'SUCCESS' };
        const [selectedOutcome, setSelectedOutcome] = useState(settings.defaultOutcome || outcomeOptions[0].value);

        useEffect(() => {
            const registerPaymentEvent =
                eventRegistration && typeof eventRegistration.onPaymentSetup === 'function'
                    ? eventRegistration.onPaymentSetup
                    : eventRegistration && eventRegistration.onPaymentProcessing;

            if (typeof registerPaymentEvent !== 'function') {
                return () => {};
            }

            const unsubscribe = registerPaymentEvent(() => {
                if (! selectedOutcome) {
                    if (emitResponse && typeof emitResponse.error === 'function') {
                        emitResponse.error({ message: outcomeError });
                    }

                    return {
                        type: responseTypes.ERROR,
                        message: outcomeError,
                    };
                }

                // Pass the selected option to PHP so the template gateway can react to it.
                const paymentMethodData = {
                    [`${gatewayId}_test_outcome`]: selectedOutcome,
                };

                // Continue storing the legacy meta key until the PHP gateway is updated.
                paymentMethodData[`${legacyGatewayId}_test_outcome`] = selectedOutcome;

                return {
                    type: responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData,
                    },
                };
            });

            return () => {
                if (typeof unsubscribe === 'function') {
                    unsubscribe();
                }
            };
        }, [selectedOutcome, eventRegistration, emitResponse]);

        return createElement(
            Fragment,
            {},
            disclaimer
                ? createElement(
                      'p',
                      { className: `${gatewayId}-payment-disclaimer` },
                      createElement('strong', null, disclaimer)
                  )
                : null,
            createElement(
                'p',
                { className: 'wc-block-components-field' },
                createElement(
                    'label',
                    { htmlFor: `${gatewayId}-test-outcome` },
                    outcomeLabel,
                    createElement('abbr', { className: 'required', title: __('required', 'woocommerce') }, '*')
                ),
                createElement(
                    'select',
                    {
                        id: `${gatewayId}-test-outcome`,
                        value: selectedOutcome,
                        onChange: (event) => setSelectedOutcome(event.target.value),
                    },
                    outcomeOptions.map((option) =>
                        createElement('option', { key: option.value, value: option.value }, option.label)
                    )
                )
            )
        );
    };

    const PaymentMethodContent = (props) =>
        createElement(
            Fragment,
            {},
            description ? createElement('p', { className: `wc-block-${gatewayId}-description` }, description) : null,
            createElement(PaymentFields, props)
        );

    registerPaymentMethod({
        name: gatewayId,
        label,
        ariaLabel: label,
        content: createElement(PaymentMethodContent),
        edit: createElement(PaymentMethodContent),
        canMakePayment: () => true,
        supports: {
            features: settings.supports || ['products'],
        },
    });
})(window);
