(function(window){
    if (! window.wc || ! window.wc.wcBlocksRegistry || ! window.wc.wcSettings || ! window.wp || ! window.wp.element) {
        return;
    }

    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { getSetting } = window.wc.wcSettings;
    const { createElement, Fragment, useEffect, useState } = window.wp.element;
    const { __ } = window.wp.i18n;

    const settings = getSetting('allcomet_data', {});
    const label = settings.title || __('AllComet', 'allcomet-woocommerce');
    const description = settings.description || '';

    const errorMessages = Object.assign(
        {
            cardNumber: __('Please enter your card number.', 'allcomet-woocommerce'),
            expiryMonth: __('Please enter the card expiry month.', 'allcomet-woocommerce'),
            expiryYear: __('Please enter the card expiry year.', 'allcomet-woocommerce'),
            cvc: __('Please enter the card CVC.', 'allcomet-woocommerce'),
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
                createElement('abbr', { className: 'required', title: __('required', 'allcomet-woocommerce') }, '*')
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
            case 'allcomet-card-number':
                return 'cc-number';
            case 'allcomet-expiry-month':
                return 'cc-exp-month';
            case 'allcomet-expiry-year':
                return 'cc-exp-year';
            case 'allcomet-card-cvc':
                return 'cc-csc';
            default:
                return 'off';
        }
    }

    const ExpiryFields = ({ month, year, onMonthChange, onYearChange }) => {
        return createElement(
            'div',
            { className: 'wc-block-components-field-group allcomet-expiry-group' },
            Field({
                id: 'allcomet-expiry-month',
                labelText: __('Expiry month', 'allcomet-woocommerce'),
                value: month,
                onChange: onMonthChange,
                placeholder: __('MM', 'allcomet-woocommerce'),
                className: 'allcomet-expiry-field',
            }),
            Field({
                id: 'allcomet-expiry-year',
                labelText: __('Expiry year', 'allcomet-woocommerce'),
                value: year,
                onChange: onYearChange,
                placeholder: __('YYYY', 'allcomet-woocommerce'),
                className: 'allcomet-expiry-field',
            })
        );
    };

    const PaymentFields = (props) => {
        const { eventRegistration, emitResponse } = props;
        const responseTypes = (emitResponse && emitResponse.responseTypes) ? emitResponse.responseTypes : { ERROR: 'ERROR', SUCCESS: 'SUCCESS' };
        const [cardNumber, setCardNumber] = useState('');
        const [expiryMonth, setExpiryMonth] = useState('');
        const [expiryYear, setExpiryYear] = useState('');
        const [cvc, setCvc] = useState('');

        useEffect(() => {
            const unsubscribe = eventRegistration.onPaymentProcessing(() => {
                const errors = [];

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

                return {
                    type: responseTypes.SUCCESS,
                    paymentMethodData: {
                        allcomet_card_number: cardNumber,
                        allcomet_expiry_month: expiryMonth,
                        allcomet_expiry_year: expiryYear,
                        allcomet_card_cvc: cvc,
                    },
                };
            });

            return () => {
                if (typeof unsubscribe === 'function') {
                    unsubscribe();
                }
            };
        }, [cardNumber, expiryMonth, expiryYear, cvc, eventRegistration, emitResponse]);

        return createElement(
            Fragment,
            {},
            Field({
                id: 'allcomet-card-number',
                labelText: __('Card number', 'allcomet-woocommerce'),
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
                id: 'allcomet-card-cvc',
                labelText: __('CVC', 'allcomet-woocommerce'),
                value: cvc,
                onChange: setCvc,
                placeholder: __('CVC', 'allcomet-woocommerce'),
                type: 'password',
                className: 'allcomet-cvc-field',
            })
        );
    };

    const render = (props) => {
        return createElement(
            Fragment,
            {},
            description ? createElement('p', { className: 'wc-block-allcomet-description' }, description) : null,
            createElement(PaymentFields, props)
        );
    };

    registerPaymentMethod({
        name: 'allcomet',
        label,
        ariaLabel: label,
        content: render,
        edit: render,
        canMakePayment: () => true,
        supports: {
            features: settings.supports || ['products'],
        },
    });
})(window);
