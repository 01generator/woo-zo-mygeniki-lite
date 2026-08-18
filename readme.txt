=== MyGeniki Lite for WooCommerce ===
Contributors: 01generator
Tags: woocommerce, geniki taxydromiki, shipping, voucher, tracking
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create, print, cancel, and manually track Geniki Taxydromiki vouchers from WooCommerce orders.

== Description ==

MyGeniki Lite provides the essential single-order Geniki Taxydromiki workflow for WooCommerce.

* Create and print a voucher from the WooCommerce order screen.
* Use the Geniki Sticker, Flyer, or Sticker F6 print format.
* Preserve main and child references for multi-parcel shipments.
* Save the main tracking number to WooCommerce order metadata.
* Request the latest tracking history manually.
* Cancel a voucher through the Geniki API.
* Pre-fill COD, weight, parcel count, and the customer order note.
* Clear locally generated PDF files from the settings page.
* Support classic WooCommerce orders and HPOS.

MyGeniki Pro adds mass printing, shipping-method filters and order-list columns, automatic tracking through CRON, status automation, customer emails, close-day processing, scheduled PDF cleanup, and private updates.

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/` or install its ZIP package.
2. Activate MyGeniki Lite from the WordPress Plugins screen.
3. Open WooCommerce > MyGeniki Lite.
4. Enter the Geniki Taxydromiki application key, API username, and API password.
5. Select Production or Development and the preferred Sticker, Flyer, or Sticker F6 print format.

The PHP SOAP extension must be enabled on the server.

== Frequently Asked Questions ==

= Does Lite support HPOS? =

Yes. The order tools and order metadata use WooCommerce APIs compatible with classic storage and High-Performance Order Storage.

= Does Lite support mass printing or automatic tracking? =

No. These workflow features are available in MyGeniki Pro.

= Does Lite close pending Geniki jobs? =

No. Close-day processing is reserved for MyGeniki Pro.

== Changelog ==

= 0.1.4 =
* Added the final English and Greek MyGeniki Pro product links to the order and settings upgrade notices.

= 0.1.3 =
* Clear the voucher reference, tracking status, and tracking history from the plugin database, WooCommerce order meta, and order interface after successful cancellation.

= 0.1.2 =
* Added the complete Greek translation catalog for settings, order tools, carrier responses, and help text.
* Added WordPress.org compatibility metadata and an upgrade notice.

= 0.1.1 =
* Added an accessible loading indicator and duplicate-action protection for order AJAX operations.

= 0.1.0 =
* Initial MyGeniki Lite implementation for WooCommerce.
* Added production and test JobServicesV2 integration.
* Added voucher creation, Sticker/Flyer/Sticker F6 PDF printing, multi-parcel references, cancellation, and manual tracking.
* Added classic-order and HPOS-compatible order tools and metadata.

== Upgrade Notice ==

= 0.1.4 =
Adds the language-specific MyGeniki Pro product links.

= 0.1.3 =
Ensures successful voucher cancellation immediately clears all stored and displayed shipment tracking data.

= 0.1.2 =
Adds the complete Greek interface and improves WordPress.org release compatibility metadata.
