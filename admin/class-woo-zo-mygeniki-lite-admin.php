<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register and coordinate all admin-facing Lite plugin features.
 */
class Woo_Zo_Mygeniki_Lite_Admin
{
    protected $plugin_name;
    protected $version;
    protected $settings;
    protected $metabox;
    protected $ajax;
    protected $notices;

    /**
     * Return the WooCommerce admin screen IDs supported for order editing.
     */
    protected function get_order_screen_ids()
    {
        $screens = array('shop_order');

        if (function_exists('wc_get_page_screen_id')) {
            $screens[] = wc_get_page_screen_id('shop-order');
        }

        return array_values(array_unique(array_filter($screens)));
    }

    /**
     * Return the WooCommerce admin screen IDs supported for order lists.
     */
    protected function get_order_list_screen_ids()
    {
        return array_values(array_unique(array_filter(array(
            'edit-shop_order',
            'woocommerce_page_wc-orders',
            'admin_page_wc-orders',
            function_exists('wc_get_page_screen_id') ? wc_get_page_screen_id('shop-order') : '',
            function_exists('wc_get_page_screen_id') ? wc_get_page_screen_id('shop_order') : '',
        ))));
    }

    /**
     * Build the admin module dependencies.
     */
    public function __construct($plugin_name, $version, $options, $repository, $order_meta, $pdf_manager, $notices, $adapter)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings = new Woo_Zo_Mygeniki_Lite_Settings($options, $pdf_manager);
        $this->metabox = new Woo_Zo_Mygeniki_Lite_Order_Metabox($repository);
        $this->ajax = new Woo_Zo_Mygeniki_Lite_Ajax($repository, $order_meta, $pdf_manager, $adapter, $options);
        $this->notices = $notices;
    }

    /**
     * Load admin assets on plugin pages, order screens and the updates screen.
     */
    public function enqueue_assets($hook)
    {
        if ('update-core.php' !== $hook && false === strpos((string) $hook, 'woocommerce_page_woo-zo-mygeniki-lite')) {
            $screen = get_current_screen();
            $allowed_screens = array_merge($this->get_order_screen_ids(), $this->get_order_list_screen_ids());
            if (!$screen || !in_array($screen->id, $allowed_screens, true)) {
                return;
            }
        }

        wp_enqueue_style($this->plugin_name, Woo_Zo_Mygeniki_Lite_URL . 'assets/css/admin.css', array(), $this->version);
        wp_enqueue_script($this->plugin_name, Woo_Zo_Mygeniki_Lite_URL . 'assets/js/admin.js', array('jquery'), $this->version, true);
        wp_localize_script($this->plugin_name, 'wooZoMygenikiLite', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('woo_zo_mygeniki_lite_nonce'),
            'pluginFile' => plugin_basename(Woo_Zo_Mygeniki_Lite_FILE),
            'logoUrl' => Woo_Zo_Mygeniki_Lite_URL . 'assets/images/logo-128x128.png',
            'i18n' => array(
                'cancelTitle' => __('Cancel voucher', 'woo-zo-mygeniki-lite'),
                'cancelMessage' => __('Are you sure you want to cancel voucher #%s?', 'woo-zo-mygeniki-lite'),
                'cancelMessageEmpty' => __('Are you sure you want to cancel this voucher?', 'woo-zo-mygeniki-lite'),
                'confirmYes' => __('Yes', 'woo-zo-mygeniki-lite'),
                'confirmCancel' => __('Cancel', 'woo-zo-mygeniki-lite'),
                'actionCompleted' => __('Action completed.', 'woo-zo-mygeniki-lite'),
                'requestFailed' => __('Request failed.', 'woo-zo-mygeniki-lite'),
            ),
        ));
    }

    /**
     * Register the WooCommerce submenu page for the Lite settings screen.
     */
    public function register_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('MyGeniki Lite', 'woo-zo-mygeniki-lite'),
            __('MyGeniki Lite', 'woo-zo-mygeniki-lite'),
            'manage_woocommerce',
            'woo-zo-mygeniki-lite',
            array($this->settings, 'render_page')
        );
    }

    /**
     * Register the order-side shipment metabox for WooCommerce orders.
     */
    public function register_metabox()
    {
        foreach ($this->get_order_screen_ids() as $screen_id) {
            add_meta_box(
                'woo-zo-mygeniki-lite',
                __('MyGeniki Lite', 'woo-zo-mygeniki-lite'),
                array($this->metabox, 'render'),
                $screen_id,
                'side',
                'high'
            );
        }
    }

    /**
     * Render queued admin notices.
     */
    public function render_notices()
    {
        $this->notices->render();
    }

    /**
     * Add a quick settings link to the plugins list row.
     */
    public function add_action_links($links)
    {
        array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=woo-zo-mygeniki-lite')) . '">' . esc_html__('Settings', 'woo-zo-mygeniki-lite') . '</a>');

        return $links;
    }

    /**
     * Proxy the order option save AJAX request.
     */
    public function ajax_save_options() { $this->ajax->save_options(); }

    /**
     * Proxy the create-and-print AJAX request.
     */
    public function ajax_create_print() { $this->ajax->create_print(); }

    /**
     * Proxy the cancel shipment AJAX request.
     */
    public function ajax_cancel() { $this->ajax->cancel(); }

    /**
     * Proxy the manual tracking AJAX request.
     */
    public function ajax_track() { $this->ajax->track(); }

    /**
     * Proxy the generated PDF cleanup AJAX request.
     */
    public function ajax_clear_pdfs() { $this->ajax->clear_pdfs(); }
}
