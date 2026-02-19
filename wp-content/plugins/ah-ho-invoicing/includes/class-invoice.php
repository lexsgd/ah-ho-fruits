<?php
/**
 * Invoice Document Class
 *
 * Generates branded invoices using WooCommerce order number as invoice number.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AH_HO_Invoice {

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

        // Prepare template data
        $data = array(
            'order' => $order,
            'order_id' => $order_id,
            'date' => $order->get_date_created()->format('d M Y'),
            'due_date' => $order->get_date_created()->modify('+30 days')->format('d M Y'),
            'company_logo' => AH_HO_INVOICING_PLUGIN_URL . 'assets/images/ah-ho-logo.png',
            'company_name' => get_option('ah_ho_company_name', 'Ah Ho Fruit Trading Co'),
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
}
