<?php
/**
 * Invoice Document Class
 *
 * Generates branded invoices with sequential numbering
 */

if (!defined('ABSPATH')) {
    exit;
}

class AH_HO_Invoice {

    /**
     * Get or generate invoice number for an order
     *
     * Uses database locking to ensure sequential numbering without gaps
     *
     * @param int $order_id WooCommerce order ID
     * @return string Invoice number (e.g., AHF-2026-00001)
     */
    public static function get_invoice_number($order_id) {
        // Check if invoice number already exists
        $invoice_number = get_post_meta($order_id, '_ah_ho_invoice_number', true);

        if ($invoice_number) {
            return $invoice_number;
        }

        // Generate new invoice number
        global $wpdb;

        // Lock table to prevent race conditions
        $wpdb->query("LOCK TABLES {$wpdb->options} WRITE");

        // Get last invoice number
        $last_number = (int) get_option('ah_ho_last_invoice_number', 0);

        // Increment
        $new_number = $last_number + 1;

        // Update option
        update_option('ah_ho_last_invoice_number', $new_number);

        // Unlock table
        $wpdb->query("UNLOCK TABLES");

        // Format: AHF-2026-00001
        $invoice_number = sprintf('AHF-%d-%05d', date('Y'), $new_number);

        // Save to order meta
        update_post_meta($order_id, '_ah_ho_invoice_number', $invoice_number);
        update_post_meta($order_id, '_ah_ho_invoice_date', date('Y-m-d H:i:s'));

        return $invoice_number;
    }

    /**
     * Generate invoice PDF for an order
     *
     * @param int $order_id WooCommerce order ID
     * @return string|false Path to generated PDF or false on failure
     */
    public static function generate($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        // Get invoice number
        $invoice_number = self::get_invoice_number($order_id);

        // Prepare template data
        $data = array(
            'invoice_number' => $invoice_number,
            'order' => $order,
            'order_id' => $order_id,
            'date' => $order->get_date_created()->format('d M Y'),
            'due_date' => $order->get_date_created()->modify('+30 days')->format('d M Y'),
            'company_logo' => AH_HO_INVOICING_PLUGIN_URL . 'assets/images/ah-ho-logo.png',
            'company_name' => get_option('ah_ho_company_name', 'Ah Ho Fruits Pte Ltd'),
            'company_address' => get_option('ah_ho_company_address', '123 Fruit Lane, Singapore 123456'),
            'company_phone' => get_option('ah_ho_company_phone', '+65 1234 5678'),
            'company_email' => get_option('ah_ho_company_email', 'hello@ahhofruits.com'),
            'company_uen' => get_option('ah_ho_company_uen', '201234567A'),
            'company_gst' => get_option('ah_ho_company_gst', 'M12345678X'),
            'bank_name' => get_option('ah_ho_bank_name', 'DBS Bank'),
            'bank_account' => get_option('ah_ho_bank_account', '123-456-789-0'),
        );

        // Render template
        ob_start();
        extract($data);
        include AH_HO_INVOICING_PLUGIN_DIR . 'templates/invoice/invoice.php';
        $html = ob_get_clean();

        // Generate PDF
        $pdf_path = AH_HO_PDF_Generator::generate_pdf($html, "invoice_{$order_id}");

        return $pdf_path;
    }

    /**
     * Get invoice date for an order
     *
     * @param int $order_id WooCommerce order ID
     * @return string|false Invoice date or false if not generated
     */
    public static function get_invoice_date($order_id) {
        return get_post_meta($order_id, '_ah_ho_invoice_date', true);
    }

    /**
     * Check if order has invoice
     *
     * @param int $order_id WooCommerce order ID
     * @return bool
     */
    public static function has_invoice($order_id) {
        $invoice_number = get_post_meta($order_id, '_ah_ho_invoice_number', true);
        return !empty($invoice_number);
    }

    /**
     * Delete invoice for an order
     *
     * @param int $order_id WooCommerce order ID
     */
    public static function delete_invoice($order_id) {
        delete_post_meta($order_id, '_ah_ho_invoice_number');
        delete_post_meta($order_id, '_ah_ho_invoice_date');

        // Delete cached PDF
        $cache_files = glob(AH_HO_INVOICING_CACHE_DIR . "invoice_{$order_id}_*.pdf");
        foreach ($cache_files as $file) {
            unlink($file);
        }
    }
}
