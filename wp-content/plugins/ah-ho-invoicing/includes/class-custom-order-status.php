<?php
/**
 * Custom Order Status: Out for Delivery
 *
 * Registers a custom WooCommerce order status for tracking deliveries
 *
 * @package AhHoInvoicing
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AH_HO_Custom_Order_Status {

    /**
     * Initialize custom order status
     */
    public static function init() {
        // Register custom order status
        add_action('init', array(__CLASS__, 'register_order_status'));

        // Add status to order status list
        add_filter('wc_order_statuses', array(__CLASS__, 'add_to_order_statuses'));

        // Add status to bulk actions
        add_filter('bulk_actions-edit-shop_order', array(__CLASS__, 'add_bulk_actions'));
        add_filter('bulk_actions-woocommerce_page_wc-orders', array(__CLASS__, 'add_bulk_actions'));

        // Add status to reports
        add_filter('woocommerce_reports_order_statuses', array(__CLASS__, 'add_to_reports'));

        // Add custom status colors/icon to admin
        add_action('admin_head', array(__CLASS__, 'add_admin_styles'));
    }

    /**
     * Register custom order status with WordPress
     */
    public static function register_order_status() {
        register_post_status('wc-out-for-delivery', array(
            'label'                     => __('Out for Delivery', 'ah-ho-invoicing'),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop('Out for Delivery <span class="count">(%s)</span>', 'Out for Delivery <span class="count">(%s)</span>', 'ah-ho-invoicing'),
        ));
    }

    /**
     * Add custom status to WooCommerce order statuses list
     *
     * @param array $order_statuses Existing order statuses
     * @return array Modified order statuses
     */
    public static function add_to_order_statuses($order_statuses) {
        $new_order_statuses = array();

        // Add custom status after 'Processing'
        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;

            if ('wc-processing' === $key) {
                $new_order_statuses['wc-out-for-delivery'] = __('Out for Delivery', 'ah-ho-invoicing');
            }
        }

        return $new_order_statuses;
    }

    /**
     * Add custom status to bulk actions
     *
     * @param array $bulk_actions Existing bulk actions
     * @return array Modified bulk actions
     */
    public static function add_bulk_actions($bulk_actions) {
        $bulk_actions['mark_out-for-delivery'] = __('Change status to Out for Delivery', 'ah-ho-invoicing');
        return $bulk_actions;
    }

    /**
     * Add custom status to order reports
     *
     * @param array $statuses Order statuses included in reports
     * @return array Modified statuses
     */
    public static function add_to_reports($statuses) {
        $statuses[] = 'out-for-delivery';
        return $statuses;
    }

    /**
     * Add custom CSS for order status in admin
     */
    public static function add_admin_styles() {
        ?>
        <style>
            .order-status.status-out-for-delivery {
                background: #ff9800;
                color: #fff;
            }
            mark.order-status.status-out-for-delivery {
                background: #ff9800;
                color: #fff;
                font-weight: bold;
            }
            mark.order-status.status-out-for-delivery::after {
                content: '\f343'; /* Dashicons truck icon */
                font-family: 'Dashicons';
                speak: never;
                margin-left: 5px;
            }
        </style>
        <?php
    }
}
