<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render and process the Lite plugin settings page.
 */
class Woo_Zo_Mygeniki_Lite_Settings
{
    protected $options;
    protected $pdf_manager;

    /**
     * Store the option and PDF management services used by the settings screen.
     */
    public function __construct($options, $pdf_manager)
    {
        $this->options = $options;
        $this->pdf_manager = $pdf_manager;
    }

    /**
     * Process submitted settings and render the full settings page UI.
     */
    public function render_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        if (isset($_POST['woo_zo_mygeniki_lite_save_settings']) && check_admin_referer('woo_zo_mygeniki_lite_save_settings')) {
            $environment = sanitize_key(wp_unslash($_POST['environment'] ?? 'production'));
            $this->options->set('environment', in_array($environment, array('production', 'development'), true) ? $environment : 'production');
            $this->options->set('api_key', sanitize_text_field(wp_unslash($_POST['api_key'] ?? '')));
            $this->options->set('api_username', sanitize_text_field(wp_unslash($_POST['api_username'] ?? '')));
            $this->options->set('api_password', sanitize_text_field(wp_unslash($_POST['api_password'] ?? '')));
            $print_template = sanitize_key(wp_unslash($_POST['print_template'] ?? 'sticker'));
            $this->options->set('print_template', in_array($print_template, array('sticker', 'flyer', 'sticker_f6'), true) ? $print_template : 'sticker');
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'woo-zo-mygeniki-lite') . '</p></div>';
        }

        $settings = $this->options->all();
        $logo_url = Woo_Zo_Mygeniki_Lite_URL . 'assets/images/logo-256x256.png';
        $save_icon = Woo_Zo_Mygeniki_Lite_URL . 'assets/images/save.svg';
        $info_icon = Woo_Zo_Mygeniki_Lite_URL . 'assets/images/info-circle.svg';
        ?>
        <div class="wrap woocommerce">
            <div class="woo-zo-mgl-header">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('MyGeniki Lite', 'woo-zo-mygeniki-lite'); ?>" class="woo-zo-mgl-logo">
                <div class="woo-zo-mgl-header-copy">
                    <h1><?php esc_html_e('MyGeniki Lite', 'woo-zo-mygeniki-lite'); ?></h1>
                    <p><?php esc_html_e('Single-order shipment workflow for WooCommerce with a clean upgrade path to the full Pro feature set.', 'woo-zo-mygeniki-lite'); ?></p>
                </div>
            </div>
            <form method="post">
                <?php wp_nonce_field('woo_zo_mygeniki_lite_save_settings'); ?>
                <div class="woo-zo-mgl-settings-panels">
                    <div class="woo-zo-mgl-panel">
                        <div class="woo-zo-mgl-panel-header">
                            <h2><?php esc_html_e('Credentials', 'woo-zo-mygeniki-lite'); ?></h2>
                            <p><?php esc_html_e('Store the Geniki Taxydromiki SOAP credentials required to create, print, and track vouchers.', 'woo-zo-mygeniki-lite'); ?></p>
                        </div>
                        <table class="form-table">
                            <tr><th><label for="environment"><?php esc_html_e('Environment', 'woo-zo-mygeniki-lite'); ?></label></th><td><select name="environment" id="environment"><option value="production" <?php selected($settings['environment'], 'production'); ?>><?php esc_html_e('Production', 'woo-zo-mygeniki-lite'); ?></option><option value="development" <?php selected($settings['environment'], 'development'); ?>><?php esc_html_e('Development / Test', 'woo-zo-mygeniki-lite'); ?></option></select><p class="description"><?php esc_html_e('Geniki provides separate credentials for the test and production environments. Update the credentials when switching environments.', 'woo-zo-mygeniki-lite'); ?></p></td></tr>
                            <tr><th><label for="api_key"><?php esc_html_e('Application API Key', 'woo-zo-mygeniki-lite'); ?></label></th><td><input class="regular-text" type="text" name="api_key" id="api_key" value="<?php echo esc_attr($settings['api_key']); ?>"><p class="description"><?php esc_html_e('Use the web-service application key supplied by Geniki Taxydromiki.', 'woo-zo-mygeniki-lite'); ?></p></td></tr>
                            <tr><th><label for="api_username"><?php esc_html_e('API Username', 'woo-zo-mygeniki-lite'); ?></label></th><td><input class="regular-text" type="text" name="api_username" id="api_username" value="<?php echo esc_attr($settings['api_username']); ?>"></td></tr>
                            <tr><th><label for="api_password"><?php esc_html_e('API Password', 'woo-zo-mygeniki-lite'); ?></label></th><td><input class="regular-text" type="password" name="api_password" id="api_password" value="<?php echo esc_attr($settings['api_password']); ?>" autocomplete="new-password"></td></tr>
                        </table>
                    </div>

                    <div class="woo-zo-mgl-panel">
                        <div class="woo-zo-mgl-panel-header">
                            <h2><?php esc_html_e('Voucher Settings', 'woo-zo-mygeniki-lite'); ?></h2>
                            <p><?php esc_html_e('Configure the single-order voucher tools and the Geniki print template used when generating labels.', 'woo-zo-mygeniki-lite'); ?></p>
                        </div>
                        <table class="form-table">
                            <tr><th><label for="print_template"><?php esc_html_e('Print Format', 'woo-zo-mygeniki-lite'); ?></label></th><td><select name="print_template" id="print_template"><option value="sticker" <?php selected($settings['print_template'], 'sticker'); ?>><?php esc_html_e('Sticker', 'woo-zo-mygeniki-lite'); ?></option><option value="flyer" <?php selected($settings['print_template'], 'flyer'); ?>><?php esc_html_e('Flyer', 'woo-zo-mygeniki-lite'); ?></option><option value="sticker_f6" <?php selected($settings['print_template'], 'sticker_f6'); ?>><?php esc_html_e('Sticker F6', 'woo-zo-mygeniki-lite'); ?></option></select></td></tr>
                        </table>
                    </div>

                    <div class="woo-zo-mgl-panel">
                        <div class="woo-zo-mgl-panel-header">
                            <h2><?php esc_html_e('PDF Settings', 'woo-zo-mygeniki-lite'); ?></h2>
                            <p><?php esc_html_e('Manage the locally generated voucher PDFs stored in the WordPress uploads folder.', 'woo-zo-mygeniki-lite'); ?></p>
                        </div>
                        <table class="form-table">
                            <tr><th><?php esc_html_e('Generated PDFs', 'woo-zo-mygeniki-lite'); ?></th><td><p><?php echo esc_html(sprintf(__('Stored files: %d', 'woo-zo-mygeniki-lite'), $this->pdf_manager->count_files())); ?></p><p><button type="button" class="button" id="woo-zo-mgl-clear-pdfs"><?php esc_html_e('Clear Generated PDFs', 'woo-zo-mygeniki-lite'); ?></button></p></td></tr>
                        </table>
                    </div>

                    <div class="woo-zo-mgl-panel woo-zo-mgl-panel-accent">
                        <div class="woo-zo-mgl-panel-header">
                            <h2><?php esc_html_e('Lite vs Pro', 'woo-zo-mygeniki-lite'); ?></h2>
                            <p><?php esc_html_e('The Lite version already covers the single-order workflow. Use this table to see exactly what expands in Pro.', 'woo-zo-mygeniki-lite'); ?></p>
                        </div>
                        <div class="woo-zo-mgl-panel-body">
                            <table class="woo-zo-mgl-pro-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Feature / Function', 'woo-zo-mygeniki-lite'); ?></th>
                                        <th><?php esc_html_e('Lite', 'woo-zo-mygeniki-lite'); ?></th>
                                        <th><?php esc_html_e('Pro', 'woo-zo-mygeniki-lite'); ?></th>
                                        <th><?php esc_html_e('Description', 'woo-zo-mygeniki-lite'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php esc_html_e('Create & Print Voucher', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Create a shipment from the order screen and open the generated PDF label in a new tab for immediate printing.', 'woo-zo-mygeniki-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Remote Voucher Cancellation', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Cancel a voucher remotely using the JobId returned by Geniki Taxydromiki.', 'woo-zo-mygeniki-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Manual Tracking', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Request and store the latest carrier tracking message manually from the order page.', 'woo-zo-mygeniki-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Order Page Shipment Options', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Edit COD, parcels, weight, comment and other supported carrier options inline on each order.', 'woo-zo-mygeniki-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Close Day', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('No', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Close all pending voucher jobs from the Pro orders workflow.', 'woo-zo-mygeniki-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Print Template Choice', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Select Sticker, Flyer, or Sticker F6 output from the plugin settings.', 'woo-zo-mygeniki-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Manual PDF Cleanup', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Delete all locally stored generated PDFs from the settings page whenever you want.', 'woo-zo-mygeniki-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Mass Printing', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('No', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Print multiple selected orders in batches and generate one final merged PDF file.', 'woo-zo-mygeniki-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('CRON Tracking', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('No', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Run scheduled tracking updates through a tokenized endpoint and optional server cron job.', 'woo-zo-mygeniki-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Automatic Status Changes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('No', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Update WooCommerce order statuses automatically when shipments are created, delivered, or marked late.', 'woo-zo-mygeniki-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Customer Emails', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('No', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Send in-transit and thank-you emails using WooCommerce email classes and automation rules.', 'woo-zo-mygeniki-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Automatic PDF Cleanup', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Manual only', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Automatic + Manual', 'woo-zo-mygeniki-lite'); ?></td>
                                        <td><?php esc_html_e('Delete old generated PDF files automatically after the number of days you configure.', 'woo-zo-mygeniki-lite'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="woo-zo-mgl-upgrade-note woo-zo-mgl-upgrade-note-settings">
                                <p class="woo-zo-mgl-upgrade-note-main">
                                    <img src="<?php echo esc_url($info_icon); ?>" alt="" aria-hidden="true" class="woo-zo-mgl-button-icon">
                                    <span><?php esc_html_e('Upgrade now and send automated email with the voucher number. Track your orders via CRON and mass print multiple vouchers in a few clicks.', 'woo-zo-mygeniki-lite'); ?></span>
                                </p>
                                <p class="woo-zo-mgl-upgrade-note-link">
                                    <a href="<?php echo esc_url(woo_zo_mygeniki_lite_get_pro_url()); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('You can find the Pro version here.', 'woo-zo-mygeniki-lite'); ?></a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <p><button type="submit" class="button button-primary woo-zo-mgl-settings-submit" name="woo_zo_mygeniki_lite_save_settings" value="1"><img src="<?php echo esc_url($save_icon); ?>" alt="" aria-hidden="true" class="woo-zo-mgl-button-icon"> <span><?php esc_html_e('Save Settings', 'woo-zo-mygeniki-lite'); ?></span></button></p>
            </form>
            <div id="woo-zo-mgl-settings-message"></div>
        </div>
        <?php
    }
}
