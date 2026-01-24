<?php
/**
 * Delivery Order Generator
 *
 * Generates delivery orders for delivery drivers.
 * Large address, contact info, signature line.
 *
 * @package AhHoInvoicing
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AH_HO_Delivery_Order {

    /**
     * Generate delivery order for a single order
     *
     * @param int $order_id WooCommerce order ID
     * @return string Path to generated PDF file
     */
    public static function generate($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        // Generate HTML from template
        ob_start();
        include AH_HO_INVOICING_PLUGIN_DIR . 'templates/delivery-order/delivery-order.php';
        $html = ob_get_clean();

        // Generate PDF with caching
        $filename = "delivery-order_{$order_id}";
        $pdf_path = AH_HO_PDF_Generator::generate_pdf($html, $filename, true);

        return $pdf_path;
    }

    /**
     * Check if order has delivery order
     *
     * @param int $order_id Order ID
     * @return bool
     */
    public static function has_delivery_order($order_id) {
        $cache_dir = AH_HO_INVOICING_CACHE_DIR;
        $pattern = "{$cache_dir}delivery-order_{$order_id}_*.pdf";
        $files = glob($pattern);
        return !empty($files);
    }

    /**
     * Delete delivery order for an order
     *
     * @param int $order_id Order ID
     * @return bool
     */
    public static function delete($order_id) {
        $cache_dir = AH_HO_INVOICING_CACHE_DIR;
        $pattern = "{$cache_dir}delivery-order_{$order_id}_*.pdf";
        $files = glob($pattern);

        foreach ($files as $file) {
            unlink($file);
        }

        return true;
    }

    /**
     * Extract delivery instructions from order
     *
     * Checks:
     * - Order notes (customer notes)
     * - Custom delivery_instructions meta field
     * - Shipping address 2 (often contains unit/floor info)
     *
     * @param WC_Order $order WooCommerce order
     * @return string HTML formatted delivery instructions
     */
    public static function get_delivery_instructions($order) {
        $instructions = array();

        // Customer notes (may contain gate codes, delivery preferences)
        $customer_note = $order->get_customer_note();
        if (!empty($customer_note)) {
            $instructions[] = array(
                'label' => 'Customer Notes',
                'value' => $customer_note,
            );
        }

        // Custom delivery instructions meta field (if set)
        $delivery_meta = get_post_meta($order->get_id(), '_delivery_instructions', true);
        if (!empty($delivery_meta)) {
            $instructions[] = array(
                'label' => 'Delivery Instructions',
                'value' => $delivery_meta,
            );
        }

        // Shipping address 2 (unit/floor info)
        $address_2 = $order->get_shipping_address_2();
        if (!empty($address_2)) {
            $instructions[] = array(
                'label' => 'Unit/Floor',
                'value' => $address_2,
            );
        }

        // Delivery time slot (if set)
        $delivery_time = get_post_meta($order->get_id(), '_delivery_time_slot', true);
        if (!empty($delivery_time)) {
            $instructions[] = array(
                'label' => 'Preferred Time',
                'value' => $delivery_time,
            );
        }

        return $instructions;
    }

    /**
     * Get order summary for delivery driver
     *
     * Returns quick reference info:
     * - Number of items
     * - Total weight
     * - Payment method
     * - Whether signature required
     *
     * @param WC_Order $order WooCommerce order
     * @return array
     */
    public static function get_delivery_summary($order) {
        $summary = array();

        // Count items
        $item_count = 0;
        foreach ($order->get_items() as $item) {
            $item_count += $item->get_quantity();
        }
        $summary['item_count'] = $item_count;

        // Total weight
        $total_weight = 0;
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $weight = $product->get_weight();
                $quantity = $item->get_quantity();
                if ($weight) {
                    $total_weight += ($weight * $quantity);
                }
            }
        }
        $summary['total_weight'] = $total_weight;

        // Payment method
        $summary['payment_method'] = $order->get_payment_method_title();

        // Signature required (if payment is COD or if flagged)
        $summary['signature_required'] = ($order->get_payment_method() === 'cod') || get_post_meta($order->get_id(), '_signature_required', true);

        // Amount to collect (if COD)
        if ($order->get_payment_method() === 'cod') {
            $summary['amount_to_collect'] = $order->get_total();
        } else {
            $summary['amount_to_collect'] = 0;
        }

        return $summary;
    }
}
