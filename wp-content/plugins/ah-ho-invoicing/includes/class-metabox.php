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
        add_action('wp_ajax_ah_ho_prepare_pdf', array(__CLASS__, 'ajax_prepare_pdf'));
    }

    /**
     * Add meta boxes to order edit page
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'ah_ho_invoicing_actions',
            __('PDF Documents', 'ah-ho-invoicing'),
            array(__CLASS__, 'render_metabox'),
            'shop_order',
            'side',
            'high'
        );

        // Support for HPOS (High-Performance Order Storage)
        add_meta_box(
            'ah_ho_invoicing_actions',
            __('PDF Documents', 'ah-ho-invoicing'),
            array(__CLASS__, 'render_metabox'),
            'woocommerce_page_wc-orders',
            'side',
            'high'
        );
    }

    /**
     * Get the temp directory for PDF downloads
     */
    private static function get_temp_dir() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/ah-ho-invoicing/temp/';
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        // Add .htaccess to force downloads and block directory listing
        $htaccess = $temp_dir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess,
                "Options -Indexes\n" .
                "<FilesMatch \"\\.pdf$\">\n" .
                "    ForceType application/octet-stream\n" .
                "    Header set Content-Disposition attachment\n" .
                "</FilesMatch>\n"
            );
        }
        // Add index.php for security
        $index = $temp_dir . 'index.php';
        if (!file_exists($index)) {
            file_put_contents($index, '<?php // Silence is golden');
        }
        return $temp_dir;
    }

    /**
     * Get the temp URL for PDF downloads
     */
    private static function get_temp_url() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/ah-ho-invoicing/temp/';
    }

    /**
     * Clean up old temp PDF files (older than 1 hour)
     */
    private static function cleanup_temp_files() {
        $temp_dir = self::get_temp_dir();
        $files = glob($temp_dir . '*.pdf');
        if ($files) {
            $cutoff = time() - 3600; // 1 hour
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff) {
                    @unlink($file);
                }
            }
        }
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

        $prepare_nonce  = wp_create_nonce('ah_ho_prepare_pdf');
        $print_nonce    = wp_create_nonce('ah_ho_print_pdf');
        $ajax_url       = admin_url('admin-ajax.php');
        ?>
        <div class="ah-ho-pdf-actions">
            <div class="ah-ho-btn-row">
                <button type="button" onclick="ahHoDownloadPdf('invoice', <?php echo $order_id; ?>, this)"
                   class="button button-primary ah-ho-btn-download">
                    Invoice
                </button>
                <a href="<?php echo esc_url($ajax_url . "?action=ah_ho_print_pdf&type=invoice&order_id={$order_id}&_wpnonce={$print_nonce}"); ?>"
                   class="button ah-ho-btn-print"
                   target="_blank">
                    Print
                </a>
            </div>

            <div class="ah-ho-btn-row">
                <button type="button" onclick="ahHoDownloadPdf('packing-slip', <?php echo $order_id; ?>, this)"
                   class="button ah-ho-btn-download">
                    Packing Slip
                </button>
                <a href="<?php echo esc_url($ajax_url . "?action=ah_ho_print_pdf&type=packing-slip&order_id={$order_id}&_wpnonce={$print_nonce}"); ?>"
                   class="button ah-ho-btn-print"
                   target="_blank">
                    Print
                </a>
            </div>

            <div class="ah-ho-btn-row">
                <button type="button" onclick="ahHoDownloadPdf('delivery-order', <?php echo $order_id; ?>, this)"
                   class="button ah-ho-btn-download">
                    Delivery Order
                </button>
                <a href="<?php echo esc_url($ajax_url . "?action=ah_ho_print_pdf&type=delivery-order&order_id={$order_id}&_wpnonce={$print_nonce}"); ?>"
                   class="button ah-ho-btn-print"
                   target="_blank">
                    Print
                </a>
            </div>
        </div>

        <style>
            .ah-ho-pdf-actions p {
                margin: 10px 0;
            }
            .ah-ho-pdf-actions .button {
                display: block;
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
        /* PDF Download v8 — static temp file approach */
        function ahHoDownloadPdf(type, orderId, btn) {
            var url = '<?php echo esc_js($ajax_url); ?>' +
                '?action=ah_ho_prepare_pdf&type=' + type +
                '&order_id=' + orderId +
                '&_wpnonce=<?php echo esc_js($prepare_nonce); ?>';
            var originalText = btn.textContent;

            btn.textContent = 'Generating...';
            btn.disabled = true;

            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            /* Use text, not json — admin-ajax may prepend PHP notices
               that break JSON.parse. We parse manually below. */
            xhr.responseType = 'text';

            xhr.onload = function() {
                btn.textContent = originalText;
                btn.disabled = false;

                var raw = xhr.responseText;
                var r = null;

                /* Try to extract JSON even if PHP notices are prepended */
                try {
                    var jsonStart = raw.indexOf('{');
                    if (jsonStart >= 0) {
                        r = JSON.parse(raw.substring(jsonStart));
                    }
                } catch (e) {
                    console.error('PDF prepare parse error:', e, 'Raw:', raw);
                }

                if (r && r.success && r.data && r.data.url) {
                    /* Download via hidden iframe — doesn't navigate away
                       from the order page. The .htaccess in the temp dir
                       forces Content-Type: application/octet-stream so
                       the browser downloads instead of displaying. */
                    var iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = r.data.url;
                    document.body.appendChild(iframe);
                    setTimeout(function() {
                        try { document.body.removeChild(iframe); } catch(e) {}
                    }, 60000);
                } else {
                    var msg = 'Unknown error';
                    if (r && r.data && r.data.error) {
                        msg = r.data.error;
                    } else if (raw === '0') {
                        msg = 'Session expired — please reload the page';
                    }
                    alert('PDF generation failed: ' + msg + '\n\nDebug: HTTP ' + xhr.status + ', response length ' + raw.length);
                    console.error('PDF prepare failed. Status:', xhr.status, 'Raw:', raw);
                }
            };

            xhr.onerror = function() {
                btn.textContent = originalText;
                btn.disabled = false;
                alert('Network error generating PDF. Please try again.');
            };

            xhr.send();
        }
        </script>
        <?php
    }

    /**
     * AJAX handler: Generate PDF, save as temp static file, return URL.
     * This allows the browser to download a static file (bypassing proxy).
     */
    public static function ajax_prepare_pdf() {
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('error' => 'Unauthorized'), 403);
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'ah_ho_prepare_pdf')) {
            wp_send_json_error(array('error' => 'Security check failed'), 403);
        }

        $type = sanitize_text_field($_GET['type']);
        $order_id = absint($_GET['order_id']);

        if (!$order_id) {
            wp_send_json_error(array('error' => 'Invalid order ID'), 400);
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

            case 'partial-delivery-order':
                $delivery_id = isset($_GET['delivery_id']) ? sanitize_text_field($_GET['delivery_id']) : '';
                if (empty($delivery_id)) {
                    wp_send_json_error(array('error' => 'Missing delivery ID'), 400);
                }
                $pdf_path = AH_HO_Delivery_Order::generate_partial($order_id, $delivery_id);
                $filename = "delivery-order-{$order_id}-{$delivery_id}.pdf";
                break;

            default:
                wp_send_json_error(array('error' => 'Invalid document type'), 400);
        }

        if (!$pdf_path) {
            error_log("Ah Ho PDF prepare: generate() returned false for type={$type} order={$order_id}");
            wp_send_json_error(array('error' => 'Error generating PDF'), 500);
        }

        // Clean up old temp files
        self::cleanup_temp_files();

        // Copy PDF to temp dir with correct filename
        $temp_dir = self::get_temp_dir();
        $temp_url = self::get_temp_url();

        // Add a short random token to prevent caching/guessing
        $token = substr(md5(wp_salt() . $order_id . time()), 0, 8);
        $temp_filename = pathinfo($filename, PATHINFO_FILENAME) . '-' . $token . '.pdf';
        $temp_path = $temp_dir . $temp_filename;

        if (file_exists($pdf_path)) {
            $copied = copy($pdf_path, $temp_path);
        } else {
            // $pdf_path might be raw PDF data
            $copied = (file_put_contents($temp_path, $pdf_path) !== false);
        }

        if (!$copied) {
            error_log("Ah Ho PDF prepare: copy failed. src={$pdf_path} dest={$temp_path} src_exists=" . (file_exists($pdf_path) ? 'yes' : 'no'));
            wp_send_json_error(array('error' => 'Failed to prepare download'), 500);
        }

        wp_send_json_success(array(
            'url' => $temp_url . $temp_filename,
            'filename' => $filename,
        ));
    }

    /**
     * AJAX handler for PDF downloads (legacy, kept for backwards compat)
     */
    public static function ajax_download_pdf() {
        if (!current_user_can('edit_shop_orders')) {
            wp_die(__('Unauthorized', 'ah-ho-invoicing'));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'ah_ho_download_pdf')) {
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

            case 'partial-delivery-order':
                $delivery_id = isset($_GET['delivery_id']) ? sanitize_text_field($_GET['delivery_id']) : '';
                if (empty($delivery_id)) {
                    wp_die(__('Missing delivery ID', 'ah-ho-invoicing'));
                }
                $pdf_path = AH_HO_Delivery_Order::generate_partial($order_id, $delivery_id);
                $filename = "delivery-order-{$order_id}-{$delivery_id}.pdf";
                break;

            default:
                wp_die(__('Invalid document type', 'ah-ho-invoicing'));
        }

        if (!$pdf_path) {
            wp_die(__('Error generating PDF', 'ah-ho-invoicing'));
        }

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

            case 'partial-delivery-order':
                $delivery_id = isset($_GET['delivery_id']) ? sanitize_text_field($_GET['delivery_id']) : '';
                if (empty($delivery_id)) {
                    wp_die(__('Missing delivery ID', 'ah-ho-invoicing'));
                }
                $pdf_path = AH_HO_Delivery_Order::generate_partial($order_id, $delivery_id);
                $filename = "delivery-order-{$order_id}-{$delivery_id}.pdf";
                break;

            default:
                wp_die(__('Invalid document type', 'ah-ho-invoicing'));
        }

        if (!$pdf_path) {
            wp_die(__('Error generating PDF', 'ah-ho-invoicing'));
        }

        AH_HO_PDF_Generator::stream_pdf($pdf_path, $filename);
    }
}
