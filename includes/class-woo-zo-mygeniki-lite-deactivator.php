<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle lightweight deactivation tasks for the Lite plugin.
 */
class Woo_Zo_Mygeniki_Lite_Deactivator
{
    /**
     * Deactivate without destroying stored data.
     */
    public static function deactivate()
    {
        // No destructive action on deactivate.
    }
}
