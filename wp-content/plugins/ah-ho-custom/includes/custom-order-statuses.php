<?php
/**
 * Custom WooCommerce Order Statuses
 *
 * Adds delivery workflow statuses for Ah Ho Fruits
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom order statuses
 */
function ah_ho_register_custom_order_statuses() {
    // Ready for Delivery - Packed, awaiting driver
    register_post_status('wc-ready-delivery', array(
        'label'                     => _x('Ready for Delivery', 'Order status', 'ah-ho-custom'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Ready for Delivery <span class="count">(%s)</span>', 'Ready for Delivery <span class="count">(%s)</span>', 'ah-ho-custom')
    ));

    // Out for Delivery - With delivery driver
    register_post_status('wc-out-delivery', array(
        'label'                     => _x('Out for Delivery', 'Order status', 'ah-ho-custom'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Out for Delivery <span class="count">(%s)</span>', 'Out for Delivery <span class="count">(%s)</span>', 'ah-ho-custom')
    ));

    // Delivered - Paid (B2C / Cash)
    register_post_status('wc-delivered-paid', array(
        'label'                     => _x('Delivered - Paid', 'Order status', 'ah-ho-custom'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Delivered - Paid <span class="count">(%s)</span>', 'Delivered - Paid <span class="count">(%s)</span>', 'ah-ho-custom')
    ));

    // Delivered - Awaiting Payment (B2B credit terms)
    register_post_status('wc-delivered-awaiting', array(
        'label'                     => _x('Delivered - Awaiting Payment', 'Order status', 'ah-ho-custom'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Delivered - Awaiting Payment <span class="count">(%s)</span>', 'Delivered - Awaiting Payment <span class="count">(%s)</span>', 'ah-ho-custom')
    ));

    // Payment Received (B2B paid/reconciled)
    register_post_status('wc-payment-received', array(
        'label'                     => _x('Payment Received', 'Order status', 'ah-ho-custom'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Payment Received <span class="count">(%s)</span>', 'Payment Received <span class="count">(%s)</span>', 'ah-ho-custom')
    ));
}
add_action('init', 'ah_ho_register_custom_order_statuses');

/**
 * Add custom statuses to WooCommerce order statuses list
 */
function ah_ho_add_custom_order_statuses($order_statuses) {
    $new_order_statuses = array();

    // Add statuses in the correct workflow order
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;

        // Add custom statuses after 'processing'
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-ready-delivery']      = _x('Ready for Delivery', 'Order status', 'ah-ho-custom');
            $new_order_statuses['wc-out-delivery']        = _x('Out for Delivery', 'Order status', 'ah-ho-custom');
            $new_order_statuses['wc-delivered-paid']      = _x('Delivered - Paid', 'Order status', 'ah-ho-custom');
            $new_order_statuses['wc-delivered-awaiting']  = _x('Delivered - Awaiting Payment', 'Order status', 'ah-ho-custom');
            $new_order_statuses['wc-payment-received']    = _x('Payment Received', 'Order status', 'ah-ho-custom');
        }
    }

    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'ah_ho_add_custom_order_statuses');

/**
 * Add custom statuses to bulk actions dropdown
 */
function ah_ho_add_bulk_actions($bulk_actions) {
    $bulk_actions['mark_ready-delivery']      = __('Change status to Ready for Delivery', 'ah-ho-custom');
    $bulk_actions['mark_out-delivery']        = __('Change status to Out for Delivery', 'ah-ho-custom');
    $bulk_actions['mark_delivered-paid']      = __('Change status to Delivered - Paid', 'ah-ho-custom');
    $bulk_actions['mark_delivered-awaiting']  = __('Change status to Delivered - Awaiting Payment', 'ah-ho-custom');
    $bulk_actions['mark_payment-received']    = __('Change status to Payment Received', 'ah-ho-custom');

    return $bulk_actions;
}
add_filter('bulk_actions-edit-shop_order', 'ah_ho_add_bulk_actions');

/**
 * Add custom statuses to reports
 */
function ah_ho_include_custom_order_status_to_reports($statuses) {
    $statuses[] = 'ready-delivery';
    $statuses[] = 'out-delivery';
    $statuses[] = 'delivered-paid';
    $statuses[] = 'delivered-awaiting';
    $statuses[] = 'payment-received';

    return $statuses;
}
add_filter('woocommerce_reports_order_statuses', 'ah_ho_include_custom_order_status_to_reports');

/**
 * Mark delivered-paid and payment-received as "paid" statuses
 */
function ah_ho_custom_paid_statuses($statuses) {
    $statuses[] = 'delivered-paid';
    $statuses[] = 'payment-received';

    return $statuses;
}
add_filter('woocommerce_order_is_paid_statuses', 'ah_ho_custom_paid_statuses');

/**
 * Add custom order status icons/colors to admin (CSS)
 */
function ah_ho_custom_order_status_styles() {
    ?>
    <style>
        /* Ready for Delivery - Blue */
        .order-status.status-ready-delivery {
            background: #2271b1;
            color: #fff;
        }
        mark.order-status.status-ready-delivery {
            background: #2271b1;
            color: #fff;
        }

        /* Out for Delivery - Orange */
        .order-status.status-out-delivery {
            background: #f56e28;
            color: #fff;
        }
        mark.order-status.status-out-delivery {
            background: #f56e28;
            color: #fff;
        }

        /* Delivered - Paid - Green */
        .order-status.status-delivered-paid {
            background: #2ea44f;
            color: #fff;
        }
        mark.order-status.status-delivered-paid {
            background: #2ea44f;
            color: #fff;
        }

        /* Delivered - Awaiting Payment - Yellow */
        .order-status.status-delivered-awaiting {
            background: #dba617;
            color: #fff;
        }
        mark.order-status.status-delivered-awaiting {
            background: #dba617;
            color: #fff;
        }

        /* Payment Received - Dark Green */
        .order-status.status-payment-received {
            background: #0e8c3e;
            color: #fff;
        }
        mark.order-status.status-payment-received {
            background: #0e8c3e;
            color: #fff;
        }
    </style>
    <?php
}
add_action('admin_head', 'ah_ho_custom_order_status_styles');

/**
 * Add order status descriptions to admin (optional)
 */
function ah_ho_add_custom_status_help_text() {
    $screen = get_current_screen();

    if ('shop_order' !== $screen->post_type && 'edit-shop_order' !== $screen->id) {
        return;
    }

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add tooltips to order status dropdown
            var statusDescriptions = {
                'wc-processing': 'Order received, not packed',
                'wc-ready-delivery': 'Packed, awaiting driver assignment',
                'wc-out-delivery': 'With delivery driver',
                'wc-delivered-paid': 'Complete (B2C / Cash payment)',
                'wc-delivered-awaiting': 'B2B credit terms - invoice sent',
                'wc-payment-received': 'B2B paid and reconciled'
            };

            $('select[name="order_status"]').on('focus', function() {
                $(this).find('option').each(function() {
                    var value = $(this).val();
                    if (statusDescriptions[value]) {
                        var currentText = $(this).text();
                        if (!currentText.includes('—')) {
                            $(this).text(currentText + ' — ' + statusDescriptions[value]);
                        }
                    }
                });
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'ah_ho_add_custom_status_help_text');

/**
 * Auto-reduce stock when order moves to "Out for Delivery"
 */
function ah_ho_reduce_stock_on_out_for_delivery($order_id, $old_status, $new_status, $order) {
    if ('out-delivery' === $new_status && !$order->get_meta('_stock_reduced_out_delivery')) {
        wc_reduce_stock_levels($order_id);
        $order->update_meta_data('_stock_reduced_out_delivery', 'yes');
        $order->save();
    }
}
add_action('woocommerce_order_status_changed', 'ah_ho_reduce_stock_on_out_for_delivery', 10, 4);

/**
 * Add order note when status changes (for tracking)
 */
function ah_ho_add_order_note_on_status_change($order_id, $old_status, $new_status, $order) {
    $custom_statuses = array(
        'ready-delivery'      => 'Order is ready for delivery. Next: Assign to driver',
        'out-delivery'        => 'Order is out for delivery with driver',
        'delivered-paid'      => 'Order delivered and paid (B2C/Cash)',
        'delivered-awaiting'  => 'Order delivered, awaiting payment (B2B credit terms)',
        'payment-received'    => 'Payment received and reconciled (B2B)'
    );

    if (isset($custom_statuses[$new_status])) {
        $order->add_order_note($custom_statuses[$new_status], false, true);
    }
}
add_action('woocommerce_order_status_changed', 'ah_ho_add_order_note_on_status_change', 10, 4);
