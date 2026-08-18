# MyGeniki Lite for WooCommerce

MyGeniki Lite connects WooCommerce orders to the Geniki Taxydromiki JobServicesV2 API. Store managers can create, print, cancel, and manually track vouchers without leaving the WooCommerce order screen.

## Features

- Create Geniki Taxydromiki vouchers from classic and HPOS order screens.
- Print voucher PDFs using the Geniki `Sticker`, `Flyer`, or `StickerF6` format.
- Preserve main and child references for multi-parcel shipments.
- Save the main tracking number to WooCommerce order metadata.
- Request and store the latest Geniki tracking history manually.
- Cancel vouchers using the carrier JobId.
- Pre-fill COD, product weight, parcel count, and customer notes.
- Keep generated PDFs in isolated plugin storage with a manual cleanup action.
- Provide translation-ready strings under the `woo-zo-mygeniki-lite` text domain.

## Requirements

- WordPress 6.5 or newer.
- WooCommerce.
- PHP 7.4 or newer with the SOAP extension.
- Geniki Taxydromiki application key, API username, and API password.

## Lite And Pro

Lite focuses on individual-order shipment work. MyGeniki Pro adds mass printing, order-list tools, scheduled tracking, status automation, customer emails, close-day processing, automatic PDF cleanup, and secure private updates.

MyGeniki Pro is available from the 01generator store:

- [English product page](https://01generator.com/wordpress-plugins/woocommerce-plugins/greek-woocommerce-plugins/woo-mygeniki-taxydromiki)
- [Greek product page](https://01generator.com/el/wordpress-plugins/woocommerce-plugins/ellinika-woocommerce-plugins/woo-mygeniki-taxydromiki)

## Development

Lite has no Composer runtime dependencies. Reviewed production and test WSDL files are bundled under `includes/wsdl`.

## License

GPL-2.0-or-later.
