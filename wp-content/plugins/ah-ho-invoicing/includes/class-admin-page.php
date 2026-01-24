<?php
/**
 * Admin Bulk PDF Downloads Page
 *
 * Provides admin interface for:
 * - Generating consolidated packing slips by date range
 * - Bulk downloading invoices/delivery orders
 * - Quick statistics and order filtering
 *
 * @package AhHoInvoicing
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AH_HO_Admin_Page {

    /**
     * Initialize admin page
     */
    public static function init() {
        // Add admin menu page
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));

        // Register AJAX handlers
        add_action('wp_ajax_ah_ho_generate_consolidated_packing', array(__CLASS__, 'ajax_generate_consolidated'));
        add_action('wp_ajax_ah_ho_bulk_download_pdfs', array(__CLASS__, 'ajax_bulk_download'));
    }

    /**
     * Add admin menu page
     */
    public static function add_admin_menu() {
        add_menu_page(
            __('PDF Documents', 'ah-ho-invoicing'),
            __('PDF Documents', 'ah-ho-invoicing'),
            'manage_woocommerce',
            'ah-ho-pdf-bulk',
            array(__CLASS__, 'render_admin_page'),
            'dashicons-media-document',
            56 // Position after WooCommerce
        );
    }

    /**
     * Render admin page HTML
     */
    public static function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Bulk PDF Document Generator', 'ah-ho-invoicing'); ?></h1>

            <!-- Consolidated Packing Slip Section -->
            <div class="card">
                <h2><?php _e('Generate Consolidated Packing Slip', 'ah-ho-invoicing'); ?></h2>
                <p class="description">
                    <?php _e('Generate a single packing slip for multiple orders, sorted by delivery date and postal code. Perfect for warehouse batch preparation.', 'ah-ho-invoicing'); ?>
                </p>

                <form method="post" id="ah-ho-consolidated-form">
                    <?php wp_nonce_field('ah_ho_consolidated_packing', 'ah_ho_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="delivery_date"><?php _e('Delivery Date', 'ah-ho-invoicing'); ?></label>
                            </th>
                            <td>
                                <input type="date"
                                       id="delivery_date"
                                       name="delivery_date"
                                       value="<?php echo esc_attr(date('Y-m-d', strtotime('+1 day'))); ?>"
                                       required>
                                <p class="description">
                                    <?php _e('Generate packing slip for all orders scheduled for this delivery date.', 'ah-ho-invoicing'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="order_status"><?php _e('Order Status', 'ah-ho-invoicing'); ?></label>
                            </th>
                            <td>
                                <select id="order_status" name="order_status[]" multiple style="width: 300px; height: 100px;">
                                    <option value="wc-processing" selected><?php _e('Processing', 'ah-ho-invoicing'); ?></option>
                                    <option value="wc-on-hold"><?php _e('On Hold', 'ah-ho-invoicing'); ?></option>
                                    <option value="wc-out-for-delivery"><?php _e('Out for Delivery', 'ah-ho-invoicing'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Hold Ctrl/Cmd to select multiple statuses.', 'ah-ho-invoicing'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="sort_by"><?php _e('Sort Orders By', 'ah-ho-invoicing'); ?></label>
                            </th>
                            <td>
                                <select id="sort_by" name="sort_by">
                                    <option value="date_postal" selected><?php _e('Delivery Date → Postal Code', 'ah-ho-invoicing'); ?></option>
                                    <option value="postal_date"><?php _e('Postal Code → Delivery Date', 'ah-ho-invoicing'); ?></option>
                                    <option value="order_id"><?php _e('Order Number', 'ah-ho-invoicing'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Recommended: Delivery Date → Postal Code for route optimization.', 'ah-ho-invoicing'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            📄 <?php _e('Generate Consolidated Packing Slip', 'ah-ho-invoicing'); ?>
                        </button>
                        <span class="spinner" style="float: none;"></span>
                    </p>
                </form>

                <div id="ah-ho-consolidated-result" style="display: none; margin-top: 20px;">
                    <div class="notice notice-success">
                        <p>
                            <strong><?php _e('Consolidated packing slip generated successfully!', 'ah-ho-invoicing'); ?></strong><br>
                            <a href="#" id="ah-ho-download-link" class="button button-primary" target="_blank">
                                📥 <?php _e('Download PDF', 'ah-ho-invoicing'); ?>
                            </a>
                            <span id="ah-ho-order-count"></span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bulk Download Section -->
            <div class="card" style="margin-top: 20px;">
                <h2><?php _e('Bulk Download PDFs', 'ah-ho-invoicing'); ?></h2>
                <p class="description">
                    <?php _e('Download all invoices, packing slips, or delivery orders for a date range.', 'ah-ho-invoicing'); ?>
                </p>

                <form method="post" id="ah-ho-bulk-download-form">
                    <?php wp_nonce_field('ah_ho_bulk_download', 'ah_ho_bulk_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="bulk_start_date"><?php _e('Date Range', 'ah-ho-invoicing'); ?></label>
                            </th>
                            <td>
                                <input type="date"
                                       id="bulk_start_date"
                                       name="bulk_start_date"
                                       value="<?php echo esc_attr(date('Y-m-d', strtotime('-7 days'))); ?>"
                                       required>
                                <span> <?php _e('to', 'ah-ho-invoicing'); ?> </span>
                                <input type="date"
                                       id="bulk_end_date"
                                       name="bulk_end_date"
                                       value="<?php echo esc_attr(date('Y-m-d')); ?>"
                                       required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="bulk_document_type"><?php _e('Document Type', 'ah-ho-invoicing'); ?></label>
                            </th>
                            <td>
                                <select id="bulk_document_type" name="bulk_document_type">
                                    <option value="invoice"><?php _e('Invoices', 'ah-ho-invoicing'); ?></option>
                                    <option value="packing-slip"><?php _e('Packing Slips', 'ah-ho-invoicing'); ?></option>
                                    <option value="delivery-order"><?php _e('Delivery Orders', 'ah-ho-invoicing'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-secondary">
                            📦 <?php _e('Download All PDFs (ZIP)', 'ah-ho-invoicing'); ?>
                        </button>
                        <span class="spinner" style="float: none;"></span>
                    </p>
                </form>

                <p class="description">
                    <strong><?php _e('Note:', 'ah-ho-invoicing'); ?></strong>
                    <?php _e('PDFs will be generated on-demand and packaged into a ZIP file for download.', 'ah-ho-invoicing'); ?>
                </p>
            </div>

            <!-- Quick Statistics -->
            <div class="card" style="margin-top: 20px;">
                <h2><?php _e('Quick Statistics', 'ah-ho-invoicing'); ?></h2>
                <?php self::render_statistics(); ?>
            </div>
        </div>

        <style>
            .card {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .card h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            #ah-ho-consolidated-result .notice {
                padding: 15px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Consolidated packing slip form
            $('#ah-ho-consolidated-form').on('submit', function(e) {
                e.preventDefault();

                var $form = $(this);
                var $button = $form.find('button[type="submit"]');
                var $spinner = $form.find('.spinner');
                var $result = $('#ah-ho-consolidated-result');

                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                $result.hide();

                $.post(ajaxurl, {
                    action: 'ah_ho_generate_consolidated_packing',
                    delivery_date: $('#delivery_date').val(),
                    order_status: $('#order_status').val(),
                    sort_by: $('#sort_by').val(),
                    _wpnonce: $('#ah_ho_nonce').val()
                }, function(response) {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');

                    if (response.success) {
                        $('#ah-ho-download-link').attr('href', response.data.download_url);
                        $('#ah-ho-order-count').text('(' + response.data.order_count + ' orders included)');
                        $result.show();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });

            // Bulk download form
            $('#ah-ho-bulk-download-form').on('submit', function(e) {
                e.preventDefault();
                alert('Bulk ZIP download feature coming in v1.4. For now, use consolidated packing slip or download individual PDFs from order pages.');
            });
        });
        </script>
        <?php
    }

    /**
     * Render quick statistics
     */
    private static function render_statistics() {
        global $wpdb;

        // Get invoice count
        $invoice_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta}
            WHERE meta_key = '_ah_ho_invoice_number'
        ");

        // Get cached PDF count
        $cache_dir = AH_HO_INVOICING_CACHE_DIR;
        $pdf_count = 0;
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*.pdf');
            $pdf_count = count($files);
        }

        // Get total cache size
        $cache_size = 0;
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $cache_size += filesize($file);
                }
            }
        }
        $cache_size_mb = round($cache_size / 1024 / 1024, 2);

        ?>
        <table class="widefat" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th><?php _e('Metric', 'ah-ho-invoicing'); ?></th>
                    <th><?php _e('Value', 'ah-ho-invoicing'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?php _e('Total Invoices Generated', 'ah-ho-invoicing'); ?></strong></td>
                    <td><?php echo esc_html(number_format($invoice_count)); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Cached PDFs', 'ah-ho-invoicing'); ?></strong></td>
                    <td><?php echo esc_html($pdf_count); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Cache Size', 'ah-ho-invoicing'); ?></strong></td>
                    <td><?php echo esc_html($cache_size_mb); ?> MB</td>
                </tr>
                <tr>
                    <td><strong><?php _e('Next Invoice Number', 'ah-ho-invoicing'); ?></strong></td>
                    <td><?php echo esc_html(get_option('ah_ho_invoice_counter', 1)); ?></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * AJAX handler for generating consolidated packing slip
     */
    public static function ajax_generate_consolidated() {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized');
        }

        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ah_ho_consolidated_packing')) {
            wp_send_json_error('Security check failed');
        }

        $delivery_date = sanitize_text_field($_POST['delivery_date']);
        $order_statuses = isset($_POST['order_status']) ? array_map('sanitize_text_field', $_POST['order_status']) : array('wc-processing');
        $sort_by = sanitize_text_field($_POST['sort_by']);

        // Query orders by delivery date and status
        $args = array(
            'status'      => $order_statuses,
            'limit'       => -1,
            'meta_key'    => '_delivery_date',
            'meta_value'  => $delivery_date,
            'return'      => 'ids',
        );

        $order_ids = wc_get_orders($args);

        if (empty($order_ids)) {
            wp_send_json_error(__('No orders found for this delivery date and status.', 'ah-ho-invoicing'));
        }

        // Generate consolidated packing slip
        $pdf_path = AH_HO_Packing_Slip::generate_consolidated($order_ids, $sort_by);

        if (!$pdf_path) {
            wp_send_json_error(__('Failed to generate consolidated packing slip.', 'ah-ho-invoicing'));
        }

        // Create download URL with nonce
        $download_url = admin_url('admin-ajax.php?action=ah_ho_download_consolidated_pdf&path=' . urlencode(basename($pdf_path)) . '&_wpnonce=' . wp_create_nonce('ah_ho_download_consolidated'));

        wp_send_json_success(array(
            'download_url' => $download_url,
            'order_count'  => count($order_ids),
        ));
    }
}

/**
 * AJAX handler for downloading consolidated PDF
 */
add_action('wp_ajax_ah_ho_download_consolidated_pdf', function() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('Unauthorized');
    }

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'ah_ho_download_consolidated')) {
        wp_die('Security check failed');
    }

    $filename = sanitize_file_name($_GET['path']);
    $pdf_path = AH_HO_INVOICING_CACHE_DIR . $filename;

    if (!file_exists($pdf_path)) {
        wp_die('PDF file not found.');
    }

    // Download PDF
    AH_HO_PDF_Generator::download_pdf($pdf_path, $filename);
});
