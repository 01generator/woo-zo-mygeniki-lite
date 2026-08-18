<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wrap plugin option access behind a consistent namespaced API.
 */
class Woo_Zo_Mygeniki_Lite_Options
{
    protected $prefix = 'woo_zo_mygeniki_lite_';

    /**
     * Fetch a namespaced plugin option with a default fallback.
     */
    public function get($key, $default = '')
    {
        $value = get_option($this->prefix . $key, $default);

        return ('' === $value || null === $value) ? $default : $value;
    }

    /**
     * Save a namespaced plugin option.
     */
    public function set($key, $value)
    {
        update_option($this->prefix . $key, $value);
    }

    /**
     * Return the settings array used by the current Lite UI.
     */
    public function all()
    {
        return array(
            'environment'    => $this->get('environment', 'production'),
            'api_key'        => $this->get('api_key'),
            'api_username'   => $this->get('api_username'),
            'api_password'   => $this->get('api_password'),
            'print_template' => $this->get('print_template', 'sticker'),
            'token'          => $this->get('token'),
        );
    }

    /**
     * Create the default options on first install without overwriting existing values.
     */
    public function ensure_defaults()
    {
        $defaults = array(
            'environment'    => 'production',
            'api_key'        => '',
            'api_username'   => '',
            'api_password'   => '',
            'print_template' => 'sticker',
            'token'          => wp_generate_password(20, false, false),
            'last_pdf_clear' => '',
        );

        foreach ($defaults as $key => $value) {
            if (false === get_option($this->prefix . $key, false)) {
                update_option($this->prefix . $key, $value);
            }
        }
    }
}
