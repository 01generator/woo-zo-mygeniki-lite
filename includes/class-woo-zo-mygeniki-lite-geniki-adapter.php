<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Communicate with the Geniki Taxydromiki JobServicesV2 API.
 */
class Woo_Zo_Mygeniki_Lite_Geniki_Adapter
{
    protected $options;
    protected $pdf_manager;

    /**
     * Store the plugin services required by the carrier adapter.
     */
    public function __construct($options, $pdf_manager)
    {
        $this->options = $options;
        $this->pdf_manager = $pdf_manager;
    }

    /**
     * Return the bundled service definition for the selected environment.
     */
    protected function get_wsdl_path()
    {
        $suffix = 'development' === $this->options->get('environment', 'production') ? '-test' : '';

        return __DIR__ . '/wsdl/JobServicesV2' . $suffix . '.wsdl';
    }

    /**
     * Return the HTTP PDF endpoint matching the configured environment.
     */
    protected function get_pdf_endpoint($bulk)
    {
        $host = 'development' === $this->options->get('environment', 'production')
            ? 'https://testvoucher.taxydromiki.gr'
            : 'https://voucher.taxydromiki.gr';

        return $host . '/JobServicesV2.asmx/' . ($bulk ? 'GetVouchersPdf' : 'GetVoucherPdf');
    }

    /**
     * Validate the runtime and credentials before making a carrier request.
     */
    protected function get_configuration_error()
    {
        if (!extension_loaded('soap')) {
            return __('The PHP SOAP extension is required to communicate with Geniki Taxydromiki.', 'woo-zo-mygeniki-lite');
        }

        foreach (array('api_username', 'api_password', 'api_key') as $key) {
            if ('' === trim((string) $this->options->get($key))) {
                return __('Geniki Taxydromiki credentials are not configured yet.', 'woo-zo-mygeniki-lite');
            }
        }

        if (!is_readable($this->get_wsdl_path())) {
            return __('The Geniki Taxydromiki service definition is missing.', 'woo-zo-mygeniki-lite');
        }

        return '';
    }

    /**
     * Create a SOAP client from the bundled WSDL.
     */
    protected function create_client()
    {
        return new SoapClient(
            $this->get_wsdl_path(),
            array(
                'cache_wsdl'         => WSDL_CACHE_NONE,
                'connection_timeout' => 30,
                'exceptions'         => true,
                'trace'              => defined('WP_DEBUG') && WP_DEBUG,
            )
        );
    }

    /**
     * Authenticate and return a short-lived Geniki API key.
     */
    protected function authenticate()
    {
        $configuration_error = $this->get_configuration_error();
        if ('' !== $configuration_error) {
            return array('success' => false, 'message' => $configuration_error);
        }

        try {
            $client = $this->create_client();
            $response = $client->Authenticate(
                array(
                    'sUsrName'      => (string) $this->options->get('api_username'),
                    'sUsrPwd'       => (string) $this->options->get('api_password'),
                    'applicationKey'=> (string) $this->options->get('api_key'),
                )
            );
            $key = is_object($response)
                && isset($response->AuthenticateResult)
                && is_object($response->AuthenticateResult)
                && isset($response->AuthenticateResult->Key)
                ? trim((string) $response->AuthenticateResult->Key)
                : '';
            $result_code = is_object($response)
                && isset($response->AuthenticateResult)
                && is_object($response->AuthenticateResult)
                && isset($response->AuthenticateResult->Result)
                ? (int) $response->AuthenticateResult->Result
                : 0;

            if (0 !== $result_code || '' === $key) {
                return array(
                    'success' => false,
                    'message' => sprintf(
                        __('Geniki Taxydromiki rejected the configured credentials (result code %d).', 'woo-zo-mygeniki-lite'),
                        $result_code
                    ),
                );
            }

            return array('success' => true, 'key' => $key, 'client' => $client);
        } catch (Throwable $error) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Geniki Taxydromiki authentication failed: %s', 'woo-zo-mygeniki-lite'),
                    sanitize_text_field($error->getMessage())
                ),
            );
        }
    }

    /**
     * Cap plain-text values before sending them to the carrier.
     */
    protected function truncate_text($value, $length)
    {
        $value = trim(wp_strip_all_tags((string) $value));

        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    /**
     * Normalize a voucher reference received from storage or the API.
     */
    protected function normalize_reference($reference)
    {
        $reference = preg_replace('/[^A-Za-z0-9-]/', '', (string) $reference);

        return $this->truncate_text($reference, 50);
    }

    /**
     * Convert Geniki's SOAP sub-voucher collection to a flat array.
     */
    protected function extract_sub_vouchers($result)
    {
        if (!is_object($result) || empty($result->SubVouchers) || !isset($result->SubVouchers->Record)) {
            return array();
        }

        $records = is_array($result->SubVouchers->Record)
            ? $result->SubVouchers->Record
            : array($result->SubVouchers->Record);
        $vouchers = array();

        foreach ($records as $record) {
            $reference = is_object($record) && isset($record->VoucherNo)
                ? $this->normalize_reference($record->VoucherNo)
                : '';
            if ('' !== $reference) {
                $vouchers[] = $reference;
            }
        }

        return array_values(array_unique($vouchers));
    }

    /**
     * Build the documented Geniki voucher record from a WooCommerce order.
     */
    protected function build_voucher_record($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return array('success' => false, 'message' => __('The order could not be loaded.', 'woo-zo-mygeniki-lite'));
        }

        $repository = new Woo_Zo_Mygeniki_Lite_Repository();
        $row = $repository->ensure_order_row($order_id);
        $first_name = $order->get_shipping_first_name() ?: $order->get_billing_first_name();
        $last_name = $order->get_shipping_last_name() ?: $order->get_billing_last_name();
        $address_1 = $order->get_shipping_address_1() ?: $order->get_billing_address_1();
        $address_2 = $order->get_shipping_address_2() ?: $order->get_billing_address_2();
        $city = $order->get_shipping_city() ?: $order->get_billing_city();
        $postcode = $order->get_shipping_postcode() ?: $order->get_billing_postcode();
        $country_code = $order->get_shipping_country() ?: $order->get_billing_country();
        $phone = method_exists($order, 'get_shipping_phone') ? $order->get_shipping_phone() : '';
        $phone = $phone ?: $order->get_billing_phone();
        $name = trim($last_name . ' ' . $first_name);
        $address = trim($address_1 . ' ' . $address_2);

        if ('' === $name || '' === $address || '' === $city || '' === $postcode || '' === trim((string) $phone)) {
            return array(
                'success' => false,
                'message' => __('The order is missing recipient name, address, city, postcode, or telephone.', 'woo-zo-mygeniki-lite'),
            );
        }

        $country_name = $country_code;
        if (function_exists('WC') && WC()->countries) {
            $countries = WC()->countries->get_countries();
            $country_name = $countries[$country_code] ?? $country_code;
        }

        $services = array();
        if (!empty($row['cod'])) {
            $services[] = 'ΑΜ';
        }
        if (!empty($row['rec'])) {
            $services[] = 'ΑΡ';
            $services[] = 'ΒΡ';
        }
        if (!empty($row['sat'])) {
            $services[] = '5Σ';
        }
        if ('CY' === strtoupper((string) $country_code)) {
            $services[] = 'ΦΡ';
        }

        $comment = (string) $row['comment'];
        if ((int) $this->options->get('add_order_id', 0)) {
            $comment = '#' . $order_id . ' ' . $comment;
        }

        return array(
            'success' => true,
            'order'   => $order,
            'record'  => array(
                'OrderId'                => $this->truncate_text($order->get_order_number(), 50),
                'Name'                   => $this->truncate_text($name, 100),
                'Address'                => $this->truncate_text($address, 150),
                'Email'                  => sanitize_email($order->get_billing_email()),
                'Country'                => $this->truncate_text($country_name, 100),
                'CountryIso'             => $this->truncate_text(strtoupper((string) $country_code), 2),
                'City'                   => $this->truncate_text($city, 100),
                'Telephone'              => $this->truncate_text($phone, 50),
                'Zip'                    => $this->truncate_text($postcode, 20),
                'Destination'            => $this->truncate_text(strtoupper((string) $country_code), 2),
                'Courier'                => '',
                'Pieces'                 => min(99, max(1, (int) $row['parcels'])),
                'Weight'                 => number_format(max(0.1, (float) $row['weight']), 3, '.', ''),
                'Comments'               => $this->truncate_text($comment, 100),
                'Services'               => implode(',', array_unique($services)),
                'CodAmount'              => !empty($row['cod']) ? number_format(max(0, (float) $order->get_total()), 2, '.', '') : '0.00',
                'InsAmount'              => '0.00',
                'VoucherNo'              => '',
                'SubCode'                => '',
                'BelongsTo'              => '',
                'DeliverTo'              => '',
                'ReceivedDate'            => gmdate('c'),
                'ContentsDescription'     => '',
                'SendAndReturnRecipient'  => '',
                'ExtraInfo'               => '',
            ),
        );
    }

    /**
     * Create a Geniki voucher and preserve its cancellation JobId.
     */
    public function create_shipment($order_id)
    {
        $payload = $this->build_voucher_record($order_id);
        if (empty($payload['success'])) {
            return $payload;
        }

        $auth = $this->authenticate();
        if (empty($auth['success'])) {
            return $auth;
        }

        try {
            $response = $auth['client']->CreateJob(
                array(
                    'sAuthKey' => $auth['key'],
                    'oVoucher' => $payload['record'],
                    'eType'    => 'Voucher',
                )
            );
            $result = is_object($response) && isset($response->CreateJobResult) ? $response->CreateJobResult : null;
            $reference = is_object($result) && isset($result->Voucher)
                ? $this->normalize_reference($result->Voucher)
                : '';
            $job_id = is_object($result) && isset($result->JobId) ? absint($result->JobId) : 0;
            $result_code = is_object($result) && isset($result->Result) ? (int) $result->Result : 0;

            if (0 !== $result_code) {
                return array(
                    'success' => false,
                    'message' => sprintf(
                        __('Geniki Taxydromiki could not create the voucher (result code %d).', 'woo-zo-mygeniki-lite'),
                        $result_code
                    ),
                );
            }
            if ('' === $reference) {
                return array('success' => false, 'message' => __('Geniki Taxydromiki did not return a voucher number.', 'woo-zo-mygeniki-lite'));
            }

            $repository = new Woo_Zo_Mygeniki_Lite_Repository();
            if ($job_id > 0 && !$repository->save_job_id($order_id, $job_id)) {
                return array('success' => false, 'message' => __('The Geniki cancellation JobId could not be saved.', 'woo-zo-mygeniki-lite'));
            }

            return array(
                'success'   => true,
                'reference' => $reference,
                'vouchers'  => $this->extract_sub_vouchers($result),
                'job_id'    => $job_id,
                'message'   => __('Voucher created successfully.', 'woo-zo-mygeniki-lite'),
            );
        } catch (Throwable $error) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Geniki Taxydromiki could not create the voucher: %s', 'woo-zo-mygeniki-lite'),
                    sanitize_text_field($error->getMessage())
                ),
            );
        }
    }

    /**
     * Download one carrier PDF containing all supplied voucher references.
     */
    public function print_voucher($reference)
    {
        $references = is_array($reference) ? $reference : array($reference);
        $references = array_values(array_unique(array_filter(array_map(array($this, 'normalize_reference'), $references))));
        if (empty($references)) {
            return array('success' => false, 'message' => __('There is no voucher to print.', 'woo-zo-mygeniki-lite'));
        }

        $auth = $this->authenticate();
        if (empty($auth['success'])) {
            return $auth;
        }

        $bulk = count($references) > 1;
        $query = 'authKey=' . rawurlencode($auth['key']);
        if ($bulk) {
            foreach ($references as $voucher) {
                $query .= '&voucherNumbers=' . rawurlencode($voucher);
            }
        } else {
            $query .= '&voucherNo=' . rawurlencode($references[0]);
        }
        $formats = array(
            'sticker' => 'Sticker',
            'flyer' => 'Flyer',
            'sticker_f6' => 'StickerF6',
        );
        $configured_format = strtolower((string) $this->options->get('print_template', 'sticker'));
        $format = $formats[$configured_format] ?? $formats['sticker'];
        $url = $this->get_pdf_endpoint($bulk) . '?' . $query . '&format=' . rawurlencode($format) . '&extraInfoFormat=None';
        $response = wp_remote_get($url, array('timeout' => 60, 'redirection' => 2));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        if ((int) wp_remote_retrieve_response_code($response) < 200 || (int) wp_remote_retrieve_response_code($response) >= 300) {
            return array('success' => false, 'message' => __('Geniki Taxydromiki rejected the voucher PDF request.', 'woo-zo-mygeniki-lite'));
        }

        $binary = wp_remote_retrieve_body($response);
        if (0 !== strpos($binary, '%PDF')) {
            return array('success' => false, 'message' => __('Geniki Taxydromiki returned invalid PDF data.', 'woo-zo-mygeniki-lite'));
        }

        $saved = $this->pdf_manager->save_pdf(
            'mygeniki-lite-' . ($bulk ? 'bulk' : $references[0]) . '-' . gmdate('YmdHis') . '.pdf',
            $binary
        );
        if (!$saved) {
            return array('success' => false, 'message' => __('The voucher PDF could not be saved.', 'woo-zo-mygeniki-lite'));
        }

        $saved['success'] = true;

        return $saved;
    }

    /**
     * Cancel a voucher using the JobId returned when it was created.
     */
    public function cancel_shipment($reference)
    {
        $reference = $this->normalize_reference($reference);
        if ('' === $reference) {
            return array('success' => false, 'message' => __('There is no voucher to cancel.', 'woo-zo-mygeniki-lite'));
        }

        $repository = new Woo_Zo_Mygeniki_Lite_Repository();
        $row = $repository->get_order_row_by_reference($reference);
        $job_id = is_array($row) ? absint($row['job_id'] ?? 0) : 0;
        if ($job_id <= 0) {
            return array('success' => false, 'message' => __('The Geniki JobId required for cancellation is missing.', 'woo-zo-mygeniki-lite'));
        }

        $auth = $this->authenticate();
        if (empty($auth['success'])) {
            return $auth;
        }

        try {
            $response = $auth['client']->CancelJob(
                array(
                    'sAuthKey' => $auth['key'],
                    'nJobId'   => $job_id,
                    'bCancel'  => true,
                )
            );
            $result = is_object($response) && isset($response->CancelJobResult)
                ? (int) $response->CancelJobResult
                : -1;
            if (0 !== $result) {
                return array(
                    'success' => false,
                    'message' => sprintf(__('Geniki Taxydromiki cancellation failed with result code %d.', 'woo-zo-mygeniki-lite'), $result),
                );
            }

            return array('success' => true, 'message' => __('Voucher canceled successfully.', 'woo-zo-mygeniki-lite'));
        } catch (Throwable $error) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Geniki Taxydromiki could not cancel the voucher: %s', 'woo-zo-mygeniki-lite'),
                    sanitize_text_field($error->getMessage())
                ),
            );
        }
    }

    /**
     * Retrieve and normalize the full tracking history for a voucher.
     */
    public function track_shipment($reference)
    {
        $reference = $this->normalize_reference($reference);
        if ('' === $reference) {
            return array('success' => false, 'message' => __('There is no voucher to track.', 'woo-zo-mygeniki-lite'));
        }

        $auth = $this->authenticate();
        if (empty($auth['success'])) {
            return $auth;
        }

        try {
            $response = $auth['client']->TrackAndTrace(
                array(
                    'authKey'   => $auth['key'],
                    'voucherNo' => $reference,
                    'language'  => 0 === strpos(function_exists('determine_locale') ? determine_locale() : get_locale(), 'el') ? 'el' : 'en',
                )
            );
            $result = is_object($response) && isset($response->TrackAndTraceResult)
                ? $response->TrackAndTraceResult
                : null;
            if (!is_object($result)) {
                return array('success' => false, 'message' => __('Geniki Taxydromiki did not return tracking data for this voucher.', 'woo-zo-mygeniki-lite'));
            }
            $result_code = isset($result->Result) ? (int) $result->Result : 0;
            if (0 !== $result_code) {
                return array(
                    'success' => false,
                    'message' => sprintf(
                        __('Geniki Taxydromiki could not track this voucher (result code %d).', 'woo-zo-mygeniki-lite'),
                        $result_code
                    ),
                );
            }

            $checkpoints = array();
            if (isset($result->Checkpoints->Checkpoint)) {
                $checkpoints = is_array($result->Checkpoints->Checkpoint)
                    ? $result->Checkpoints->Checkpoint
                    : array($result->Checkpoints->Checkpoint);
            }
            usort($checkpoints, function ($left, $right) {
                $left_date = is_object($left) && isset($left->StatusDate) ? strtotime((string) $left->StatusDate) : 0;
                $right_date = is_object($right) && isset($right->StatusDate) ? strtotime((string) $right->StatusDate) : 0;

                return $right_date <=> $left_date;
            });

            $history_items = array();
            foreach (array_slice($checkpoints, 0, 20) as $checkpoint) {
                if (!is_object($checkpoint)) {
                    continue;
                }
                $date = !empty($checkpoint->StatusDate) ? wp_date('Y-m-d H:i', strtotime((string) $checkpoint->StatusDate)) : '';
                $history_items[] = implode(' - ', array_filter(array(
                    $date,
                    sanitize_text_field((string) ($checkpoint->Shop ?? '')),
                    sanitize_text_field((string) ($checkpoint->Status ?? '')),
                )));
            }

            $latest = !empty($checkpoints) && is_object($checkpoints[0]) ? $checkpoints[0] : null;
            $status = isset($result->Status) ? sanitize_text_field((string) $result->Status) : '';
            if ('' === $status && $latest && isset($latest->Status)) {
                $status = sanitize_text_field((string) $latest->Status);
            }
            if ('' === $status) {
                return array('success' => false, 'message' => __('Geniki Taxydromiki did not return a tracking status for this voucher.', 'woo-zo-mygeniki-lite'));
            }

            $delivery_date = isset($result->DeliveryDate) ? trim((string) $result->DeliveryDate) : '';
            $consignee = isset($result->Consignee) ? sanitize_text_field((string) $result->Consignee) : '';
            $normalized_status = function_exists('mb_strtoupper') ? mb_strtoupper($status, 'UTF-8') : strtoupper($status);
            $greek_delivered = function_exists('mb_strpos') && (
                false !== mb_strpos($normalized_status, 'ΠΑΡΑΔΟΘ', 0, 'UTF-8')
                || false !== mb_strpos($normalized_status, 'ΕΠΙΔΟΘ', 0, 'UTF-8')
            );
            $delivered = '' !== $delivery_date || '' !== $consignee || false !== strpos($normalized_status, 'DELIVERED') || $greek_delivered;
            $history = implode(' | ', array_filter($history_items));
            if ($delivered) {
                $history = implode(' | ', array_filter(array($history, trim($delivery_date . ' ' . $consignee))));
            }

            return array(
                'success'   => true,
                'delivered' => $delivered,
                'status'    => $status,
                'history'   => $history,
            );
        } catch (Throwable $error) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Geniki Taxydromiki tracking failed: %s', 'woo-zo-mygeniki-lite'),
                    sanitize_text_field($error->getMessage())
                ),
            );
        }
    }

}
