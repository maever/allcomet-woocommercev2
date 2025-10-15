# Payment Gateway Examples

This directory contains standalone, whitelabel examples that can be copied into a new plugin to kick-start additional WooCommerce payment methods.

## Example Payment Gateway

* Directory: `whitelabel-gateway`
* Entry file: `whitelabel-gateway.php`
* Gateway class: `class-wc-gateway-example.php`

### How to use the template

1. Copy the folder and rename it to match your new gateway.
2. Update plugin headers in `whitelabel-gateway.php` with your company information.
3. Replace the `WL_GATEWAY_ID` constant with a unique gateway identifier.
4. Rename the class `WC_Gateway_Example` and update method content to integrate with your payment provider.
5. Review `process_payment()` and swap the placeholder logic for real API calls.
6. Adjust the form fields and checkout UI to match the data your provider requires.

Each file includes inline comments that call out where to customise the code.
