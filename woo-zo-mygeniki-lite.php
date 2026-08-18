<?php
/**
 * Plugin Name:       MyGeniki Lite
 * Plugin URI:        https://github.com/01generator/woo-zo-mygeniki-lite
 * Description:       Create, print, and manually track Geniki Taxydromiki vouchers from WooCommerce orders.
 * Version:           0.1.4
 * Author:            01generator
 * Author URI:        https://01generator.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Text Domain:       woo-zo-mygeniki-lite
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('Woo_Zo_Mygeniki_Lite_VERSION', '0.1.4');
define('Woo_Zo_Mygeniki_Lite_SLUG', 'woo-zo-mygeniki-lite');
define('Woo_Zo_Mygeniki_Lite_FILE', __FILE__);
define('Woo_Zo_Mygeniki_Lite_PATH', plugin_dir_path(__FILE__));
define('Woo_Zo_Mygeniki_Lite_URL', plugin_dir_url(__FILE__));
define('Woo_Zo_Mygeniki_Lite_PRO_URL_EN', 'https://01generator.com/wordpress-plugins/woocommerce-plugins/greek-woocommerce-plugins/woo-mygeniki-taxydromiki');
define('Woo_Zo_Mygeniki_Lite_PRO_URL_EL', 'https://01generator.com/el/wordpress-plugins/woocommerce-plugins/ellinika-woocommerce-plugins/woo-mygeniki-taxydromiki');

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage.
 */
add_action('before_woocommerce_init', function () {
    if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            Woo_Zo_Mygeniki_Lite_FILE,
            true
        );
    }
});

/**
 * Return the localized public product URL for MyGeniki Pro.
 *
 * @return string
 */
function woo_zo_mygeniki_lite_get_pro_url()
{
    $locale = function_exists('determine_locale') ? determine_locale() : get_locale();

    return strpos($locale, 'el') === 0
        ? Woo_Zo_Mygeniki_Lite_PRO_URL_EL
        : Woo_Zo_Mygeniki_Lite_PRO_URL_EN;
}

require_once Woo_Zo_Mygeniki_Lite_PATH . 'includes/class-woo-zo-mygeniki-lite-activator.php';
require_once Woo_Zo_Mygeniki_Lite_PATH . 'includes/class-woo-zo-mygeniki-lite-deactivator.php';
require_once Woo_Zo_Mygeniki_Lite_PATH . 'includes/class-woo-zo-mygeniki-lite.php';

register_activation_hook(__FILE__, array('Woo_Zo_Mygeniki_Lite_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Woo_Zo_Mygeniki_Lite_Deactivator', 'deactivate'));

/**
 * Bootstrap the MyGeniki Lite plugin instance.
 *
 * @return void
 */
function run_Woo_Zo_Mygeniki_Lite()
{
    $plugin = new Woo_Zo_Mygeniki_Lite();
    $plugin->run();
}

run_Woo_Zo_Mygeniki_Lite();
