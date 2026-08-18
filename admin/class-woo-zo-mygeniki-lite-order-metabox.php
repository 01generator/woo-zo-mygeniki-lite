<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the WooCommerce order metabox for the Lite shipment workflow.
 */
class Woo_Zo_Mygeniki_Lite_Order_Metabox
{
    protected $repository;
    protected $show_upgrade_note;

    /**
     * Store the repository used to load and initialize order rows.
     */
    public function __construct($repository, $show_upgrade_note = true)
    {
        $this->repository = $repository;
        $this->show_upgrade_note = (bool) $show_upgrade_note;
    }

    /**
     * Output the complete order metabox markup and saved values.
     */
    public function render($post)
    {
        $order_id = 0;
        if ($post instanceof WP_Post) {
            $order_id = (int) $post->ID;
        } elseif (is_object($post) && method_exists($post, 'get_id')) {
            $order_id = (int) $post->get_id();
        } elseif (is_object($post) && isset($post->ID)) {
            $order_id = (int) $post->ID;
        }

        if ($order_id <= 0) {
            echo '<p>' . esc_html__('Order context could not be resolved.', 'woo-zo-mygeniki-lite') . '</p>';

            return;
        }

        $row = $this->repository->ensure_order_row($order_id);
        $print_icon = plugins_url('../assets/images/print.svg', __FILE__);
        $track_icon = plugins_url('../assets/images/barcode-alt.svg', __FILE__);
        $delete_icon = plugins_url('../assets/images/trash-alt.svg', __FILE__);
        $comment_icon = plugins_url('../assets/images/pencil-alt.svg', __FILE__);
        $info_icon = plugins_url('../assets/images/info-circle.svg', __FILE__);
        ?>
        <div id="woo-zo-mygeniki-lite-metabox" data-plugin="woo-zo-mygeniki-lite" data-order-id="<?php echo esc_attr($order_id); ?>">
            <div class="woo-zo-mgl-summary">
                <div class="woo-zo-mgl-summary-row">
                    <span class="woo-zo-mgl-summary-label"><?php esc_html_e('Reference', 'woo-zo-mygeniki-lite'); ?></span>
                    <span class="woo-zo-mgl-reference-wrap">
                        <?php if (!empty($row['reference'])) : ?>
                            <a class="woo-zo-mgl-reference-link woo-zo-mgl-reference" href="<?php echo esc_url('https://www.taxydromiki.com/track/' . rawurlencode($row['reference'])); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($row['reference']); ?></a>
                        <?php else : ?>
                            <span class="woo-zo-mgl-reference"></span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="woo-zo-mgl-summary-row">
                    <span class="woo-zo-mgl-summary-label"><?php esc_html_e('Tracking Status', 'woo-zo-mygeniki-lite'); ?></span>
                    <span class="woo-zo-mgl-tracking"><?php echo esc_html(trim($row['order_delivery_status'] . ' ' . $row['order_delivery_history'])); ?></span>
                </div>
            </div>

            <div class="woo-zo-mgl-field-stack">
                <label class="woo-zo-mgl-checkline"><input type="checkbox" class="woo-zo-mgl-field" data-field="cod" <?php checked((int) $row['cod'], 1); ?>> <?php esc_html_e('COD', 'woo-zo-mygeniki-lite'); ?></label>

                <div class="woo-zo-mgl-inline-grid">
                    <label class="woo-zo-mgl-field-box">
                        <span class="woo-zo-mgl-field-label"><?php esc_html_e('Parcels', 'woo-zo-mygeniki-lite'); ?></span>
                        <input type="number" min="1" max="150" class="small-text woo-zo-mgl-field" data-field="parcels" value="<?php echo esc_attr((int) $row['parcels']); ?>">
                    </label>
                    <label class="woo-zo-mgl-field-box">
                        <span class="woo-zo-mgl-field-label"><?php esc_html_e('Weight', 'woo-zo-mygeniki-lite'); ?></span>
                        <input type="text" class="small-text woo-zo-mgl-field" data-field="weight" value="<?php echo esc_attr($row['weight']); ?>">
                    </label>
                </div>

                <label class="woo-zo-mgl-checkline"><input type="checkbox" class="woo-zo-mgl-field" data-field="sat" <?php checked((int) $row['sat'], 1); ?>> <?php esc_html_e('Saturday Delivery', 'woo-zo-mygeniki-lite'); ?></label>
                <label class="woo-zo-mgl-checkline"><input type="checkbox" class="woo-zo-mgl-field" data-field="rec" <?php checked((int) $row['rec'], 1); ?>> <?php esc_html_e('Reception Delivery', 'woo-zo-mygeniki-lite'); ?></label>
                <label class="woo-zo-mgl-comment-field" for="woo-zo-mgl-comment">
                    <span class="woo-zo-mgl-field-label woo-zo-mgl-field-label-icon">
                        <img src="<?php echo esc_url($comment_icon); ?>" alt="" aria-hidden="true" class="woo-zo-mgl-button-icon">
                        <?php esc_html_e('Comment', 'woo-zo-mygeniki-lite'); ?>
                    </span>
                    <textarea id="woo-zo-mgl-comment" maxlength="100" class="widefat woo-zo-mgl-field" data-field="comment"><?php echo esc_textarea($row['comment']); ?></textarea>
                </label>
            </div>

            <div class="woo-zo-mgl-actions">
                <button type="button" class="button button-primary woo-zo-mgl-action woo-zo-mgl-action-primary" data-action="create_print">
                    <img src="<?php echo esc_url($print_icon); ?>" alt="" aria-hidden="true" class="woo-zo-mgl-button-icon">
                    <span><?php esc_html_e('Create & Print Voucher', 'woo-zo-mygeniki-lite'); ?></span>
                </button>
                <div class="woo-zo-mgl-actions-secondary">
                    <button type="button" class="button woo-zo-mgl-action woo-zo-mgl-action-track" data-action="track">
                        <img src="<?php echo esc_url($track_icon); ?>" alt="" aria-hidden="true" class="woo-zo-mgl-button-icon">
                        <span><?php esc_html_e('Track Voucher', 'woo-zo-mygeniki-lite'); ?></span>
                    </button>
                    <button type="button" class="button woo-zo-mgl-action woo-zo-mgl-action-cancel" data-action="cancel">
                        <img src="<?php echo esc_url($delete_icon); ?>" alt="" aria-hidden="true" class="woo-zo-mgl-button-icon">
                        <span><?php esc_html_e('Cancel Voucher', 'woo-zo-mygeniki-lite'); ?></span>
                    </button>
                </div>
            </div>
            <?php if ($this->show_upgrade_note) : ?>
                <div class="woo-zo-mgl-upgrade-note">
                    <p class="woo-zo-mgl-upgrade-note-main">
                        <img src="<?php echo esc_url($info_icon); ?>" alt="" aria-hidden="true" class="woo-zo-mgl-button-icon">
                        <span><?php esc_html_e('Upgrade now and send automated email with the voucher number. Track your orders via CRON and mass print multiple vouchers in a few clicks.', 'woo-zo-mygeniki-lite'); ?></span>
                    </p>
                    <p class="woo-zo-mgl-upgrade-note-link">
                        <a href="<?php echo esc_url(woo_zo_mygeniki_lite_get_pro_url()); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('You can find the Pro version here.', 'woo-zo-mygeniki-lite'); ?></a>
                    </p>
                </div>
            <?php endif; ?>
            <div class="woo-zo-mgl-loading" role="status" aria-live="polite" hidden>
                <span class="woo-zo-mgl-loading-circle" aria-hidden="true"></span>
                <span><?php esc_html_e('Processing request...', 'woo-zo-mygeniki-lite'); ?></span>
            </div>
            <div class="woo-zo-mgl-message"></div>
        </div>
        <?php
    }
}
