<?php
/**
 * Packing Slip Generator
 *
 * Generates packing slips for storeman/warehouse staff.
 * No prices, focus on SKU/quantity.
 *
 * @package AhHoInvoicing
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AH_HO_Packing_Slip {

    /**
     * Generate packing slip for a single order
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
        include AH_HO_INVOICING_PLUGIN_DIR . 'templates/packing-slip/packing-slip.php';
        $html = ob_get_clean();

        // Generate PDF with caching
        $filename = "packing-slip_{$order_id}";
        $pdf_path = AH_HO_PDF_Generator::generate_pdf($html, $filename, true);

        return $pdf_path;
    }

    /**
     * Generate consolidated packing slip for multiple orders
     *
     * CRITICAL FEATURE: Sort by delivery date FIRST, then by postal code
     *
     * @param array $order_ids Array of WooCommerce order IDs
     * @param string $sort_by Sort order: 'date_postal' (default), 'postal_only', 'date_only'
     * @return string Path to generated PDF file
     */
    public static function generate_consolidated($order_ids, $sort_by = 'date_postal') {
        if (empty($order_ids)) {
            return false;
        }

        // Load and sort orders
        $orders_data = array();
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }

            // Extract delivery date from order meta (auto-detect meta key)
            $delivery_date = AH_HO_Delivery_Date_Helper::get_delivery_date($order, 'Y-m-d');
            if (empty($delivery_date)) {
                // Fallback to order date if no delivery date set
                $delivery_date = $order->get_date_created()->format('Y-m-d');
            }

            // Extract postal code from shipping address
            $shipping_postcode = $order->get_shipping_postcode();
            if (empty($shipping_postcode)) {
                // Fallback to billing postcode
                $shipping_postcode = $order->get_billing_postcode();
            }

            $orders_data[] = array(
                'order' => $order,
                'delivery_date' => $delivery_date,
                'postal_code' => $shipping_postcode,
            );
        }

        // Sort orders based on sort_by parameter
        usort($orders_data, function($a, $b) use ($sort_by) {
            switch ($sort_by) {
                case 'date_postal':
                    // Sort by date first, then postal code
                    if ($a['delivery_date'] === $b['delivery_date']) {
                        return strcmp($a['postal_code'], $b['postal_code']);
                    }
                    return strcmp($a['delivery_date'], $b['delivery_date']);

                case 'postal_only':
                    return strcmp($a['postal_code'], $b['postal_code']);

                case 'date_only':
                    return strcmp($a['delivery_date'], $b['delivery_date']);

                default:
                    // Default: date first, then postal
                    if ($a['delivery_date'] === $b['delivery_date']) {
                        return strcmp($a['postal_code'], $b['postal_code']);
                    }
                    return strcmp($a['delivery_date'], $b['delivery_date']);
            }
        });

        // Generate HTML from template
        ob_start();
        include AH_HO_INVOICING_PLUGIN_DIR . 'templates/packing-slip/consolidated.php';
        $html = ob_get_clean();

        // Generate PDF with caching
        $date = date('Ymd');
        $hash = md5(implode('-', $order_ids));
        $filename = "consolidated-packing-slip_{$date}_{$hash}";
        $pdf_path = AH_HO_PDF_Generator::generate_pdf($html, $filename, true);

        return $pdf_path;
    }

    /**
     * Check if order has packing slip
     *
     * @param int $order_id Order ID
     * @return bool
     */
    public static function has_packing_slip($order_id) {
        $cache_dir = AH_HO_INVOICING_CACHE_DIR;
        $pattern = "{$cache_dir}packing-slip_{$order_id}_*.pdf";
        $files = glob($pattern);
        return !empty($files);
    }

    /**
     * Delete packing slip for an order
     *
     * @param int $order_id Order ID
     * @return bool
     */
    public static function delete($order_id) {
        $cache_dir = AH_HO_INVOICING_CACHE_DIR;
        $pattern = "{$cache_dir}packing-slip_{$order_id}_*.pdf";
        $files = glob($pattern);

        foreach ($files as $file) {
            unlink($file);
        }

        return true;
    }

    /**
     * Extract and highlight customer notes
     *
     * Returns HTML with bold red highlighting for:
     * - Allergies (keywords: allergy, allergic, allergen)
     * - Dietary restrictions (keywords: vegan, vegetarian, halal, gluten-free, dairy-free)
     * - Gift messages (keywords: gift, present, birthday, message)
     *
     * @param WC_Order $order WooCommerce order
     * @return string HTML formatted customer notes
     */
    public static function format_customer_notes($order) {
        $notes = $order->get_customer_note();
        if (empty($notes)) {
            return '';
        }

        // Keywords that trigger bold red highlighting
        $critical_keywords = array(
            'allerg',       // Catches: allergy, allergic, allergen
            'anaphyla',     // Catches: anaphylaxis
            'epipen',
            'shellfish',
            'peanut',
            'tree nut',
            'dairy',
            'egg',
            'soy',
            'wheat',
            'fish',
            'sesame',
            'mustard',
            'celery',
            'gluten',
            'lactose',
        );

        $gift_keywords = array(
            'gift',
            'present',
            'birthday',
            'anniversary',
            'congratulations',
            'message card',
        );

        // Check if note contains critical keywords
        $has_critical = false;
        $lower_notes = strtolower($notes);
        foreach (array_merge($critical_keywords, $gift_keywords) as $keyword) {
            if (strpos($lower_notes, $keyword) !== false) {
                $has_critical = true;
                break;
            }
        }

        // Format output
        if ($has_critical) {
            return '<div style="background-color: #fff3cd; border: 2px solid #dc3545; padding: 10px; margin: 10px 0;">' .
                   '<strong style="color: #dc3545; font-size: 16px;">⚠️ IMPORTANT CUSTOMER NOTES:</strong><br>' .
                   '<span style="color: #dc3545; font-size: 14px; font-weight: bold;">' . nl2br(esc_html($notes)) . '</span>' .
                   '</div>';
        } else {
            return '<div style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; margin: 10px 0;">' .
                   '<strong>Customer Notes:</strong><br>' .
                   '<span style="font-size: 13px;">' . nl2br(esc_html($notes)) . '</span>' .
                   '</div>';
        }
    }

    /**
     * Get order weight (total of all products)
     *
     * @param WC_Order $order WooCommerce order
     * @return float Total weight in kg
     */
    public static function get_order_weight($order) {
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

        return $total_weight;
    }
}
