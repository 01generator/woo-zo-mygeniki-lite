<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Synchronize key shipment values into WooCommerce order meta.
 */
class Woo_Zo_Mygeniki_Lite_Order_Meta
{
    /**
     * Save the main carrier reference on the order.
     */
    public function set_tracking_code($order_id, $tracking_code)
    {
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return false;
        }

        $order->update_meta_data('_woo_zo_mygeniki_reference', sanitize_text_field($tracking_code));
        $order->save_meta_data();

        return true;
    }

    /**
     * Remove the stored carrier reference from the order.
     */
    public function clear_tracking_code($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return false;
        }

        $order->delete_meta_data('_woo_zo_mygeniki_reference');
        $order->save_meta_data();

        return true;
    }

    /**
     * Save the last known tracking status and history summary on the order.
     */
    public function set_tracking_summary($order_id, $status, $history)
    {
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return false;
        }

        $order->update_meta_data('_woo_zo_mygeniki_tracking_status', sanitize_text_field($status));
        $order->update_meta_data('_woo_zo_mygeniki_tracking_history', sanitize_text_field($history));
        $order->save_meta_data();

        return true;
    }

    /**
     * Remove the last known tracking status and history from the order.
     */
    public function clear_tracking_summary($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return false;
        }

        $order->delete_meta_data('_woo_zo_mygeniki_tracking_status');
        $order->delete_meta_data('_woo_zo_mygeniki_tracking_history');
        $order->save_meta_data();

        return true;
    }
}
