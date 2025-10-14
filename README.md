# ALC Payment WooCommerce Gateway (V2)

Starter scaffolding for a WooCommerce payment gateway that connects to the ALC Payment credit card processing platform. The implementation is intentionally minimal and focuses on providing the standard hooks required by WooCommerce.

## Getting Started

1. Copy the plugin folder into the WordPress  directory on a site that has WooCommerce activated.
2. Activate ALC Payment Gateway (V2) from the WordPress Plugins screen.
3. Navigate to WooCommerce → Settings → Payments → ALC Payment and configure the gateway.
4. Enter both sandbox and production credentials on the settings screen so you can toggle modes without losing saved values.

## Next Steps

- Implement API requests in  using the active credentials.
- Add webhook or notification handling for asynchronous payment updates.
- Provide sandbox and production credentials via the ALC Payment merchant dashboard.
- Add icons, customer facing assets, and translations as needed.

Refer to the official WooCommerce payment gateway documentation for advanced configuration details.
