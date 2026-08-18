<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-woo-zo-mygeniki-lite-loader.php';
require_once __DIR__ . '/class-woo-zo-mygeniki-lite-i18n.php';
require_once __DIR__ . '/class-woo-zo-mygeniki-lite-options.php';
require_once __DIR__ . '/class-woo-zo-mygeniki-lite-repository.php';
require_once __DIR__ . '/class-woo-zo-mygeniki-lite-order-meta.php';
require_once __DIR__ . '/class-woo-zo-mygeniki-lite-pdf-manager.php';
require_once __DIR__ . '/class-woo-zo-mygeniki-lite-notices.php';
require_once __DIR__ . '/class-woo-zo-mygeniki-lite-geniki-adapter.php';
require_once dirname(__DIR__) . '/admin/class-woo-zo-mygeniki-lite-settings.php';
require_once dirname(__DIR__) . '/admin/class-woo-zo-mygeniki-lite-order-metabox.php';
require_once dirname(__DIR__) . '/admin/class-woo-zo-mygeniki-lite-ajax.php';
require_once dirname(__DIR__) . '/admin/class-woo-zo-mygeniki-lite-admin.php';

/**
 * Bootstrap and wire together the Lite plugin services.
 */
class Woo_Zo_Mygeniki_Lite
{
    protected $loader;

    /**
     * Initialize the hook loader and register the plugin modules.
     */
    public function __construct()
    {
        $this->loader = new Woo_Zo_Mygeniki_Lite_Loader();
        $this->set_locale();
        $this->define_admin_hooks();
    }

    /**
     * Register translation loading for the plugin text domain.
     */
    protected function set_locale()
    {
        $plugin_i18n = new Woo_Zo_Mygeniki_Lite_I18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Build the admin service graph and register all Lite admin hooks.
     */
    protected function define_admin_hooks()
    {
        $options = new Woo_Zo_Mygeniki_Lite_Options();
        $repository = new Woo_Zo_Mygeniki_Lite_Repository();
        $order_meta = new Woo_Zo_Mygeniki_Lite_Order_Meta();
        $pdf_manager = new Woo_Zo_Mygeniki_Lite_Pdf_Manager();
        $notices = new Woo_Zo_Mygeniki_Lite_Notices();
        $adapter = new Woo_Zo_Mygeniki_Lite_Geniki_Adapter($options, $pdf_manager);

        $admin = new Woo_Zo_Mygeniki_Lite_Admin(
            Woo_Zo_Mygeniki_Lite_SLUG,
            Woo_Zo_Mygeniki_Lite_VERSION,
            $options,
            $repository,
            $order_meta,
            $pdf_manager,
            $notices,
            $adapter
        );

        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_assets');
        $this->loader->add_action('admin_menu', $admin, 'register_menu');
        $this->loader->add_action('add_meta_boxes', $admin, 'register_metabox');
        $this->loader->add_action('admin_notices', $admin, 'render_notices');
        $this->loader->add_filter('plugin_action_links_' . plugin_basename(Woo_Zo_Mygeniki_Lite_FILE), $admin, 'add_action_links');

        $this->loader->add_action('wp_ajax_woo_zo_mygeniki_lite_save_options', $admin, 'ajax_save_options');
        $this->loader->add_action('wp_ajax_woo_zo_mygeniki_lite_create_print', $admin, 'ajax_create_print');
        $this->loader->add_action('wp_ajax_woo_zo_mygeniki_lite_cancel', $admin, 'ajax_cancel');
        $this->loader->add_action('wp_ajax_woo_zo_mygeniki_lite_track', $admin, 'ajax_track');
        $this->loader->add_action('wp_ajax_woo_zo_mygeniki_lite_clear_pdfs', $admin, 'ajax_clear_pdfs');
    }

    /**
     * Register the collected hooks with WordPress.
     */
    public function run()
    {
        $this->loader->run();
    }
}
