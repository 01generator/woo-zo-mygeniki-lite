<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load translation files for the Lite text domain.
 */
class Woo_Zo_Mygeniki_Lite_I18n
{
    /**
     * Register the plugin text domain path with WordPress.
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'woo-zo-mygeniki-lite',
            false,
            dirname(plugin_basename(Woo_Zo_Mygeniki_Lite_FILE)) . '/languages/'
        );
    }
}
