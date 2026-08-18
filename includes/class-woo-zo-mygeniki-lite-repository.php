<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Encapsulate all reads and writes for the Lite custom shipment table.
 */
class Woo_Zo_Mygeniki_Lite_Repository
{
    protected $table;

    /**
     * Resolve the custom table name from the current WordPress prefix.
     */
    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'Woo_Zo_Mygeniki_Lite';
    }

    /**
     * Build the initial stored row values from the WooCommerce order.
     */
    protected function get_default_row_data($order_id)
    {
        $defaults = array(
            'reference'              => '',
            'vouchers'               => '',
            'job_id'                 => 0,
            'order_delivery_status'  => '',
            'order_delivery_history' => '',
            'weight'                 => 1,
            'parcels'                => 1,
            'cod'                    => 0,
            'comment'                => '',
            'price'                  => 0,
            'sat'                    => 0,
            'rec'                    => 0,
            'extra_flags'            => '',
        );

        if (!function_exists('wc_get_order')) {
            return $defaults;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return $defaults;
        }

        $total_weight = 0.0;
        foreach ($order->get_items() as $item) {
            if (!method_exists($item, 'get_product')) {
                continue;
            }

            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            $item_weight = (float) $product->get_weight();
            if (function_exists('wc_get_weight')) {
                $item_weight = (float) wc_get_weight($item_weight, 'kg');
            }
            $total_weight += max(0.0, $item_weight) * max(1, (int) $item->get_quantity());
        }

        $defaults['weight'] = $total_weight > 0 ? $total_weight : 0.5;
        $defaults['cod'] = 'cod' === $order->get_payment_method() ? 1 : 0;
        $defaults['comment'] = sanitize_textarea_field((string) $order->get_customer_note());

        return $defaults;
    }

    /**
     * Guarantee that an order row exists and return its stored values.
     */
    public function ensure_order_row($order_id)
    {
        $row = $this->get_order_row($order_id);
        if ($row) {
            return $row;
        }

        global $wpdb;
        $defaults = $this->get_default_row_data($order_id);
        $wpdb->insert(
            $this->table,
            array(
                'id_order'               => (int) $order_id,
                'reference'              => $defaults['reference'],
                'vouchers'               => $defaults['vouchers'],
                'job_id'                 => $defaults['job_id'],
                'order_delivery_status'  => $defaults['order_delivery_status'],
                'order_delivery_history' => $defaults['order_delivery_history'],
                'weight'                 => $defaults['weight'],
                'parcels'                => $defaults['parcels'],
                'cod'                    => $defaults['cod'],
                'comment'                => $defaults['comment'],
                'price'                  => $defaults['price'],
                'sat'                    => $defaults['sat'],
                'rec'                    => $defaults['rec'],
                'extra_flags'            => $defaults['extra_flags'],
                'date_created'           => current_time('mysql'),
                'date_updated'           => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%f', '%d', '%d', '%s', '%f', '%d', '%d', '%s', '%s', '%s')
        );

        return $this->get_order_row($order_id);
    }

    /**
     * Fetch the stored shipment row for a WooCommerce order.
     */
    public function get_order_row($order_id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id_order = %d", $order_id), ARRAY_A);
    }

    /**
     * Fetch a stored shipment row by its main voucher reference.
     */
    public function get_order_row_by_reference($reference)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE reference = %s LIMIT 1", sanitize_text_field($reference)),
            ARRAY_A
        );
    }

    /**
     * Persist the Geniki JobId required for remote cancellation.
     */
    public function save_job_id($order_id, $job_id)
    {
        global $wpdb;

        return false !== $wpdb->update(
            $this->table,
            array('job_id' => absint($job_id), 'date_updated' => current_time('mysql')),
            array('id_order' => (int) $order_id),
            array('%d', '%s'),
            array('%d')
        );
    }

    /**
     * Validate and save editable order-level shipment options.
     */
    public function save_order_options($order_id, array $data)
    {
        global $wpdb;

        $allowed = array('weight', 'parcels', 'cod', 'comment', 'sat', 'rec');
        $update = array();

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }

            if (in_array($key, array('cod', 'sat', 'rec'), true)) {
                $update[$key] = empty($value) ? 0 : 1;
            } elseif ('parcels' === $key) {
                $update[$key] = min(150, max(1, (int) $value));
            } elseif ('weight' === $key) {
                $update[$key] = min(999999.999, max(0.001, (float) $value));
            } else {
                $value = sanitize_textarea_field((string) $value);
                $update[$key] = function_exists('mb_substr') ? mb_substr($value, 0, 100) : substr($value, 0, 100);
            }
        }

        if (empty($update)) {
            return false;
        }

        $update['date_updated'] = current_time('mysql');

        return false !== $wpdb->update($this->table, $update, array('id_order' => (int) $order_id));
    }

    /**
     * Save the main reference and any additional voucher references for an order.
     */
    public function save_reference($order_id, $reference, $vouchers = '')
    {
        global $wpdb;

        return false !== $wpdb->update(
            $this->table,
            array(
                'reference'    => sanitize_text_field($reference),
                'vouchers'     => sanitize_text_field($vouchers),
                'date_updated' => current_time('mysql'),
            ),
            array('id_order' => (int) $order_id)
        );
    }

    /**
     * Clear the stored reference values for an order.
     */
    public function clear_reference($order_id)
    {
        global $wpdb;

        return false !== $wpdb->update(
            $this->table,
            array(
                'reference'    => '',
                'vouchers'     => '',
                'job_id'       => 0,
                'order_delivery_status'  => '',
                'order_delivery_history' => '',
                'date_updated' => current_time('mysql'),
            ),
            array('id_order' => (int) $order_id)
        );
    }

    /**
     * Clear stored references for rows matching a carrier voucher number and return affected order IDs.
     */
    public function clear_reference_by_value($reference)
    {
        global $wpdb;

        $reference = sanitize_text_field((string) $reference);
        if ('' === $reference) {
            return array();
        }

        $order_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id_order FROM {$this->table} WHERE reference = %s",
                $reference
            )
        );

        if (!empty($order_ids)) {
            $wpdb->update(
                $this->table,
                array(
                    'reference'    => '',
                    'vouchers'     => '',
                    'job_id'       => 0,
                    'order_delivery_status'  => '',
                    'order_delivery_history' => '',
                    'date_updated' => current_time('mysql'),
                ),
                array('reference' => $reference)
            );
        }

        return array_map('intval', $order_ids);
    }

    /**
     * Save the latest tracking status and history returned by the carrier.
     */
    public function save_tracking($order_id, $status, $history)
    {
        global $wpdb;

        return false !== $wpdb->update(
            $this->table,
            array(
                'order_delivery_status'  => sanitize_text_field($status),
                'order_delivery_history' => sanitize_text_field($history),
                'date_updated'           => current_time('mysql'),
            ),
            array('id_order' => (int) $order_id)
        );
    }
}
