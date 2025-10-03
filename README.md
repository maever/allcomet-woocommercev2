# AllComet WooCommerce Gateway (V2)

Starter scaffolding for a WooCommerce payment gateway that connects to the AllComet credit card processing platform. The implementation is intentionally minimal and focuses on providing the standard hooks required by WooCommerce.

## Getting Started

1. Copy the plugin folder into the WordPress wp-content/plugins directory on a site that has WooCommerce activated.
2. Activate AllComet Payment Gateway (V2) from the WordPress Plugins screen.
3. Navigate to WooCommerce → Settings → Payments → AllComet and configure your AllComet credentials.

## Next Steps

- Implement API requests in includes/class-wc-gateway-allcomet.php::process_payment().
- Add webhook or notification handling for asynchronous payment updates.
- Provide sandbox and production credentials via the AllComet merchant dashboard.
- Add icons, customer facing assets, and translations as needed.

Refer to the official WooCommerce payment gateway documentation for advanced configuration details.
