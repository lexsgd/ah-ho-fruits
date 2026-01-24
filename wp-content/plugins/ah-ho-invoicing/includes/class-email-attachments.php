<?php
/**
 * Email Attachments Handler
 *
 * Auto-attaches PDFs to WooCommerce emails:
 * - Invoice → Customer "Order Completed" email
 * - Packing Slip → Admin "New Order" email
 * - Delivery Order → Customer "Out for Delivery" email
 *
 * @package AhHoInvoicing
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AH_HO_Email_Attachments {

    /**
     * Initialize email attachments
     */
    public static function init() {
        // Attach PDFs to WooCommerce emails
        add_filter('woocommerce_email_attachments', array(__CLASS__, 'attach_pdfs_to_emails'), 10, 4);

        // Clean up temporary PDF files after email sent
        add_action('woocommerce_email_after_order_table', array(__CLASS__, 'cleanup_temp_pdfs'), 99, 4);
    }

    /**
     * Attach PDFs to WooCommerce emails based on email type
     *
     * @param array $attachments Existing email attachments
     * @param string $email_id Email type ID
     * @param WC_Order $order WooCommerce order object
     * @param object $email Email object
     * @return array Modified attachments array
     */
    public static function attach_pdfs_to_emails($attachments, $email_id, $order, $email = null) {
        // Ensure we have a valid order
        if (!is_object($order)) {
            return $attachments;
        }

        $order_id = $order->get_id();

        // Check plugin settings for each email type
        $attach_invoice = get_option('ah_ho_attach_invoice_to_completed', 'yes');
        $attach_packing = get_option('ah_ho_attach_packing_to_new_order', 'yes');
        $attach_delivery = get_option('ah_ho_attach_delivery_to_out_for_delivery', 'yes');

        // Attach invoice to "Order Completed" customer email
        if ($email_id === 'customer_completed_order' && $attach_invoice === 'yes') {
            $invoice_path = AH_HO_Invoice::generate($order_id);
            if ($invoice_path && file_exists($invoice_path)) {
                $attachments[] = $invoice_path;
                error_log("Ah Ho Invoicing: Attached invoice to Order Completed email for Order #{$order_id}");
            }
        }

        // Attach packing slip to "New Order" admin email
        if ($email_id === 'new_order' && $attach_packing === 'yes') {
            $packing_path = AH_HO_Packing_Slip::generate($order_id);
            if ($packing_path && file_exists($packing_path)) {
                $attachments[] = $packing_path;
                error_log("Ah Ho Invoicing: Attached packing slip to New Order admin email for Order #{$order_id}");
            }
        }

        // Attach delivery order to "Out for Delivery" customer email
        if ($email_id === 'customer_out_for_delivery_order' && $attach_delivery === 'yes') {
            $delivery_path = AH_HO_Delivery_Order::generate($order_id);
            if ($delivery_path && file_exists($delivery_path)) {
                $attachments[] = $delivery_path;
                error_log("Ah Ho Invoicing: Attached delivery order to Out for Delivery email for Order #{$order_id}");
            }
        }

        // Optional: Attach invoice to admin "Processing Order" email
        if ($email_id === 'customer_processing_order') {
            $attach_invoice_processing = get_option('ah_ho_attach_invoice_to_processing', 'no');
            if ($attach_invoice_processing === 'yes') {
                $invoice_path = AH_HO_Invoice::generate($order_id);
                if ($invoice_path && file_exists($invoice_path)) {
                    $attachments[] = $invoice_path;
                }
            }
        }

        return $attachments;
    }

    /**
     * Cleanup temporary PDF files after email is sent
     *
     * Note: PDFs are cached, so this only removes truly temporary files
     * Cached PDFs remain for manual downloads via admin
     *
     * @param WC_Order $order WooCommerce order object
     * @param bool $sent_to_admin Whether email is sent to admin
     * @param bool $plain_text Whether email is plain text
     * @param object $email Email object
     */
    public static function cleanup_temp_pdfs($order, $sent_to_admin, $plain_text, $email) {
        // Currently, PDFs are cached, so no cleanup needed
        // This hook is reserved for future temporary PDF cleanup logic
        // Example: Delete PDFs older than 1 hour that are not cached
    }

    /**
     * Get list of email types that support PDF attachments
     *
     * @return array Array of email IDs with attachment info
     */
    public static function get_supported_emails() {
        return array(
            'customer_completed_order' => array(
                'label' => __('Customer Completed Order', 'ah-ho-invoicing'),
                'default_attachment' => 'invoice',
                'setting_key' => 'ah_ho_attach_invoice_to_completed',
            ),
            'new_order' => array(
                'label' => __('Admin New Order', 'ah-ho-invoicing'),
                'default_attachment' => 'packing-slip',
                'setting_key' => 'ah_ho_attach_packing_to_new_order',
            ),
            'customer_out_for_delivery_order' => array(
                'label' => __('Customer Out for Delivery', 'ah-ho-invoicing'),
                'default_attachment' => 'delivery-order',
                'setting_key' => 'ah_ho_attach_delivery_to_out_for_delivery',
            ),
            'customer_processing_order' => array(
                'label' => __('Customer Processing Order', 'ah-ho-invoicing'),
                'default_attachment' => 'invoice',
                'setting_key' => 'ah_ho_attach_invoice_to_processing',
            ),
        );
    }
}
