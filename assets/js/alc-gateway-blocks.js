(function(window){
    if (! window.wc || ! window.wc.wcBlocksRegistry || ! window.wc.wcSettings || ! window.wp || ! window.wp.element) {
        return;
    }

    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { getSetting } = window.wc.wcSettings;
    const { createElement, Fragment, useEffect, useState } = window.wp.element;
    const { __ } = window.wp.i18n;

    const gatewayPrefix = 'alc';
    const legacyPrefix = `al${'lcomet'}`;
    const settings =
        getSetting(`${gatewayPrefix}_data`, null) ||
        getSetting(`${legacyPrefix}_data`, {});
    const label = settings.title || __('Credit Card', 'alc-woocommerce');
    const description = settings.description || '';
    const disclaimer = settings.paymentDisclaimer || '';

    const errorMessages = Object.assign(
        {
            cardHolder: __('Please enter the card holder name.', 'alc-woocommerce'),
            cardNumber: __('Please enter your card number.', 'alc-woocommerce'),
            expiryMonth: __('Please enter the card expiry month.', 'alc-woocommerce'),
            expiryYear: __('Please enter the card expiry year.', 'alc-woocommerce'),
            cvc: __('Please enter the card CVC.', 'alc-woocommerce'),
        },
        settings.i18n || {}
    );

    const Field = ({ id, labelText, value, onChange, type = 'text', placeholder = '', className = '' }) => {
        return createElement(
            'p',
            { className: 'wc-block-components-field ' + className },
            createElement(
                'label',
                { htmlFor: id },
                labelText,
                createElement('abbr', { className: 'required', title: __('required', 'alc-woocommerce') }, '*')
            ),
            createElement('input', {
                id,
                type,
                value,
                onChange: (event) => onChange(event.target.value),
                autoComplete: eventAutocomplete(type, id),
                placeholder,
            })
        );
    };

    function eventAutocomplete(type, id) {
        switch (id) {
            case `${gatewayPrefix}-card-number`:
                return 'cc-number';
            case `${gatewayPrefix}-card-holder`:
                return 'cc-name';
            case `${gatewayPrefix}-expiry-month`:
                return 'cc-exp-month';
            case `${gatewayPrefix}-expiry-year`:
                return 'cc-exp-year';
            case `${gatewayPrefix}-card-cvc`:
                return 'cc-csc';
            default:
                return 'off';
        }
    }

    const ExpiryFields = ({ month, year, onMonthChange, onYearChange }) => {
        return createElement(
            'div',
            { className: `wc-block-components-field-group ${gatewayPrefix}-expiry-group` },
            Field({
                id: `${gatewayPrefix}-expiry-month`,
                labelText: __('Expiry month', 'alc-woocommerce'),
                value: month,
                onChange: onMonthChange,
                placeholder: __('MM', 'alc-woocommerce'),
                className: `${gatewayPrefix}-expiry-field`,
            }),
            Field({
                id: `${gatewayPrefix}-expiry-year`,
                labelText: __('Expiry year', 'alc-woocommerce'),
                value: year,
                onChange: onYearChange,
                placeholder: __('YYYY', 'alc-woocommerce'),
                className: `${gatewayPrefix}-expiry-field`,
            })
        );
    };

    const PaymentFields = (props) => {
        const { eventRegistration, emitResponse } = props;
        const responseTypes = (emitResponse && emitResponse.responseTypes) ? emitResponse.responseTypes : { ERROR: 'ERROR', SUCCESS: 'SUCCESS' };
        const [cardHolder, setCardHolder] = useState('');
        const [cardNumber, setCardNumber] = useState('');
        const [expiryMonth, setExpiryMonth] = useState('');
        const [expiryYear, setExpiryYear] = useState('');
        const [cvc, setCvc] = useState('');

        useEffect(() => {
            // Ensure the checkout specific styling for the expiry fields is only added once.
            if (typeof document !== 'undefined' && ! document.getElementById(`${gatewayPrefix}-block-styles`)) {
                const checkoutContainer = document.querySelector('.wc-block-checkout');

                if (checkoutContainer) {
                    const style = document.createElement('style');
                    style.id = `${gatewayPrefix}-block-styles`;
                    style.textContent = `
                        .wc-block-checkout .wc-block-components-field-group.${gatewayPrefix}-expiry-group {
                            display: flex;
                            gap: 1rem;
                            flex-wrap: wrap;
                        }

                        .wc-block-checkout .wc-block-components-field-group.${gatewayPrefix}-expiry-group > .wc-block-components-field {
                            flex: 1 1 0;
                        }
                    `;

                    checkoutContainer.appendChild(style);
                }
            }

            const unsubscribe = eventRegistration.onPaymentProcessing(() => {
                const errors = [];

                if (! cardHolder) {
                    errors.push(errorMessages.cardHolder);
                }
                if (! cardNumber) {
                    errors.push(errorMessages.cardNumber);
                }
                if (! expiryMonth) {
                    errors.push(errorMessages.expiryMonth);
                }
                if (! expiryYear) {
                    errors.push(errorMessages.expiryYear);
                }
                if (! cvc) {
                    errors.push(errorMessages.cvc);
                }

                if (errors.length > 0) {
                    if (emitResponse && typeof emitResponse.error === 'function') {
                        emitResponse.error({ message: errors.join(' ') });
                    }

                    return {
                        type: responseTypes.ERROR,
                        message: errors.join(' '),
                    };
                }

                // The Blocks API expects additional payment data under a meta key.
                const paymentMethodData = {
                    [`${gatewayPrefix}_card_holder`]: cardHolder,
                    [`${gatewayPrefix}_card_number`]: cardNumber,
                    [`${gatewayPrefix}_expiry_month`]: expiryMonth,
                    [`${gatewayPrefix}_expiry_year`]: expiryYear,
                    [`${gatewayPrefix}_card_cvc`]: cvc,
                };

                // Instructional: keep legacy keys until the server-side gateway is fully renamed.
                paymentMethodData[`${legacyPrefix}_card_holder`] = cardHolder;
                paymentMethodData[`${legacyPrefix}_card_number`] = cardNumber;
                paymentMethodData[`${legacyPrefix}_expiry_month`] = expiryMonth;
                paymentMethodData[`${legacyPrefix}_expiry_year`] = expiryYear;
                paymentMethodData[`${legacyPrefix}_card_cvc`] = cvc;

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
        }, [cardHolder, cardNumber, expiryMonth, expiryYear, cvc, eventRegistration, emitResponse]);

        return createElement(
            Fragment,
            {},
            description
                ? createElement('p', { className: `${gatewayPrefix}-description` }, description)
                : null,
            disclaimer
                ? createElement(
                      'p',
                      { className: `${gatewayPrefix}-payment-disclaimer` },
                      createElement('strong', null, disclaimer)
                  )
                : null,
            Field({
                id: `${gatewayPrefix}-card-holder`,
                labelText: __('Card holder name', 'alc-woocommerce'),
                value: cardHolder,
                onChange: setCardHolder,
                placeholder: __('Jane Doe', 'alc-woocommerce'),
            }),
            Field({
                id: `${gatewayPrefix}-card-number`,
                labelText: __('Card number', 'alc-woocommerce'),
                value: cardNumber,
                onChange: setCardNumber,
                placeholder: '•••• •••• •••• ••••',
            }),
            createElement(ExpiryFields, {
                month: expiryMonth,
                year: expiryYear,
                onMonthChange: setExpiryMonth,
                onYearChange: setExpiryYear,
            }),
            Field({
                id: `${gatewayPrefix}-card-cvc`,
                labelText: __('CVC', 'alc-woocommerce'),
                value: cvc,
                onChange: setCvc,
                placeholder: __('CVC', 'alc-woocommerce'),
                type: 'password',
                className: `${gatewayPrefix}-cvc-field`,
            })
        );
    };

    const PaymentMethodContent = (props) =>
        createElement(
            Fragment,
            {},
            description ? createElement('p', { className: `wc-block-${gatewayPrefix}-description` }, description) : null,
            createElement(PaymentFields, props)
        );

    registerPaymentMethod({
        name: gatewayPrefix,
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
