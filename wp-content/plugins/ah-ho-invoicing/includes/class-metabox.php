<?php
/**
 * Order Edit Page Metabox
 *
 * Adds PDF download buttons to order edit page
 */

if (!defined('ABSPATH')) {
    exit;
}

class AH_HO_Metabox {

    /**
     * Initialize metabox
     */
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('wp_ajax_ah_ho_download_pdf', array(__CLASS__, 'ajax_download_pdf'));
        add_action('wp_ajax_ah_ho_print_pdf', array(__CLASS__, 'ajax_print_pdf'));
    }

    /**
     * Add meta boxes to order edit page
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'ah_ho_invoicing_actions',
            __('📄 PDF Documents', 'ah-ho-invoicing'),
            array(__CLASS__, 'render_metabox'),
            'shop_order',
            'side',
            'high'
        );

        // Support for HPOS (High-Performance Order Storage)
        add_meta_box(
            'ah_ho_invoicing_actions',
            __('📄 PDF Documents', 'ah-ho-invoicing'),
            array(__CLASS__, 'render_metabox'),
            'woocommerce_page_wc-orders',
            'side',
            'high'
        );
    }

    /**
     * Render metabox content
     *
     * @param WP_Post|WC_Order $post_or_order Post object or Order object
     */
    public static function render_metabox($post_or_order) {
        // Handle both legacy and HPOS
        if ($post_or_order instanceof WP_Post) {
            $order_id = $post_or_order->ID;
        } else {
            $order_id = $post_or_order->get_id();
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            echo '<p>' . __('Order not found.', 'ah-ho-invoicing') . '</p>';
            return;
        }

        // Check if invoice exists
        $has_invoice = AH_HO_Invoice::has_invoice($order_id);
        $invoice_number = get_post_meta($order_id, '_ah_ho_invoice_number', true);

        ?>
        <div class="ah-ho-pdf-actions">
            <?php if ($has_invoice): ?>
                <p class="ah-ho-invoice-info">
                    <strong>Invoice #:</strong> <?php echo esc_html($invoice_number); ?><br>
                    <small style="color: #666;">Generated: <?php echo esc_html(AH_HO_Invoice::get_invoice_date($order_id)); ?></small>
                </p>
            <?php endif; ?>

            <div class="ah-ho-btn-row">
                <a href="<?php echo esc_url(admin_url("admin-ajax.php?action=ah_ho_download_pdf&type=invoice&order_id={$order_id}&_wpnonce=" . wp_create_nonce('ah_ho_download_pdf'))); ?>"
                   class="button button-primary ah-ho-btn-download"
                   target="_blank">
                    📄 <?php echo $has_invoice ? __('Download', 'ah-ho-invoicing') : __('Generate', 'ah-ho-invoicing'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url("admin-ajax.php?action=ah_ho_print_pdf&type=invoice&order_id={$order_id}&_wpnonce=" . wp_create_nonce('ah_ho_print_pdf'))); ?>"
                   class="button ah-ho-btn-print"
                   target="_blank">
                    🖨️ <?php _e('Print', 'ah-ho-invoicing'); ?>
                </a>
            </div>

            <div class="ah-ho-btn-row">
                <a href="<?php echo esc_url(admin_url("admin-ajax.php?action=ah_ho_download_pdf&type=packing-slip&order_id={$order_id}&_wpnonce=" . wp_create_nonce('ah_ho_download_pdf'))); ?>"
                   class="button ah-ho-btn-download"
                   target="_blank">
                    📦 <?php _e('Download', 'ah-ho-invoicing'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url("admin-ajax.php?action=ah_ho_print_pdf&type=packing-slip&order_id={$order_id}&_wpnonce=" . wp_create_nonce('ah_ho_print_pdf'))); ?>"
                   class="button ah-ho-btn-print"
                   target="_blank">
                    🖨️ <?php _e('Print', 'ah-ho-invoicing'); ?>
                </a>
            </div>

            <div class="ah-ho-btn-row">
                <a href="<?php echo esc_url(admin_url("admin-ajax.php?action=ah_ho_download_pdf&type=delivery-order&order_id={$order_id}&_wpnonce=" . wp_create_nonce('ah_ho_download_pdf'))); ?>"
                   class="button ah-ho-btn-download"
                   target="_blank">
                    🚚 <?php _e('Download', 'ah-ho-invoicing'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url("admin-ajax.php?action=ah_ho_print_pdf&type=delivery-order&order_id={$order_id}&_wpnonce=" . wp_create_nonce('ah_ho_print_pdf'))); ?>"
                   class="button ah-ho-btn-print"
                   target="_blank">
                    🖨️ <?php _e('Print', 'ah-ho-invoicing'); ?>
                </a>
            </div>

            <?php if ($has_invoice): ?>
            <p style="text-align: center; margin-top: 10px;">
                <a href="#" class="ah-ho-delete-invoice" data-order-id="<?php echo esc_attr($order_id); ?>" style="color: #b32d2e; font-size: 11px;">
                    <?php _e('Delete Invoice', 'ah-ho-invoicing'); ?>
                </a>
            </p>
            <?php endif; ?>
        </div>

        <style>
            .ah-ho-pdf-actions p {
                margin: 10px 0;
            }
            .ah-ho-pdf-actions .button {
                display: block;
            }
            .ah-ho-invoice-info {
                background: #f0f0f1;
                padding: 10px;
                border-left: 3px solid #2271b1;
                margin-bottom: 15px;
            }
            .ah-ho-btn-row {
                display: flex;
                gap: 4px;
                margin-bottom: 8px;
            }
            .ah-ho-btn-row .ah-ho-btn-download {
                flex: 1;
                text-align: center;
            }
            .ah-ho-btn-row .ah-ho-btn-print {
                flex: 0 0 auto;
                text-align: center;
                min-width: 70px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('.ah-ho-delete-invoice').on('click', function(e) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
                    return;
                }

                var orderId = $(this).data('order-id');
                $.post(ajaxurl, {
                    action: 'ah_ho_delete_invoice',
                    order_id: orderId,
                    _wpnonce: '<?php echo wp_create_nonce('ah_ho_delete_invoice'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error deleting invoice: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for PDF downloads
     */
    public static function ajax_download_pdf() {
        // Check permissions
        if (!current_user_can('edit_shop_orders')) {
            wp_die(__('Unauthorized', 'ah-ho-invoicing'));
        }

        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'ah_ho_download_pdf')) {
            wp_die(__('Security check failed', 'ah-ho-invoicing'));
        }

        $type = sanitize_text_field($_GET['type']);
        $order_id = absint($_GET['order_id']);

        if (!$order_id) {
            wp_die(__('Invalid order ID', 'ah-ho-invoicing'));
        }

        // Generate PDF based on type
        switch ($type) {
            case 'invoice':
                $pdf_path = AH_HO_Invoice::generate($order_id);
                $filename = "invoice-{$order_id}.pdf";
                break;

            case 'packing-slip':
                $pdf_path = AH_HO_Packing_Slip::generate($order_id);
                $filename = "packing-slip-{$order_id}.pdf";
                break;

            case 'delivery-order':
                $pdf_path = AH_HO_Delivery_Order::generate($order_id);
                $filename = "delivery-order-{$order_id}.pdf";
                break;

            default:
                wp_die(__('Invalid document type', 'ah-ho-invoicing'));
        }

        if (!$pdf_path) {
            wp_die(__('Error generating PDF', 'ah-ho-invoicing'));
        }

        // Download PDF
        AH_HO_PDF_Generator::download_pdf($pdf_path, $filename);
    }

    /**
     * AJAX handler for PDF print (opens inline in browser for printing)
     */
    public static function ajax_print_pdf() {
        if (!current_user_can('edit_shop_orders')) {
            wp_die(__('Unauthorized', 'ah-ho-invoicing'));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'ah_ho_print_pdf')) {
            wp_die(__('Security check failed', 'ah-ho-invoicing'));
        }

        $type = sanitize_text_field($_GET['type']);
        $order_id = absint($_GET['order_id']);

        if (!$order_id) {
            wp_die(__('Invalid order ID', 'ah-ho-invoicing'));
        }

        switch ($type) {
            case 'invoice':
                $pdf_path = AH_HO_Invoice::generate($order_id);
                $filename = "invoice-{$order_id}.pdf";
                break;

            case 'packing-slip':
                $pdf_path = AH_HO_Packing_Slip::generate($order_id);
                $filename = "packing-slip-{$order_id}.pdf";
                break;

            case 'delivery-order':
                $pdf_path = AH_HO_Delivery_Order::generate($order_id);
                $filename = "delivery-order-{$order_id}.pdf";
                break;

            default:
                wp_die(__('Invalid document type', 'ah-ho-invoicing'));
        }

        if (!$pdf_path) {
            wp_die(__('Error generating PDF', 'ah-ho-invoicing'));
        }

        // Stream PDF inline for printing
        AH_HO_PDF_Generator::stream_pdf($pdf_path, $filename);
    }
}

/**
 * AJAX handler for deleting invoice
 */
add_action('wp_ajax_ah_ho_delete_invoice', function() {
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error('Unauthorized');
    }

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ah_ho_delete_invoice')) {
        wp_send_json_error('Security check failed');
    }

    $order_id = absint($_POST['order_id']);
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }

    AH_HO_Invoice::delete_invoice($order_id);
    wp_send_json_success('Invoice deleted');
});
