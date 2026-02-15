<?php
/**
 * Order Fulfillment - Partial Delivery Tracking & Item Returns
 *
 * Provides a tabbed meta box on order edit pages for:
 * 1. Recording partial deliveries against line items
 * 2. Processing item returns via WooCommerce refund system
 *
 * @since 1.6.0
 * @version 1.6.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ========================================
 * HELPER FUNCTIONS
 * ========================================
 */

/**
 * Get total delivered quantity per line item from delivery records
 *
 * @param WC_Order $order
 * @return array [item_id => total_delivered_qty]
 */
function ah_ho_get_delivered_qty_per_item($order) {
    $deliveries = $order->get_meta('_partial_deliveries', true);
    $delivered = array();

    if (!is_array($deliveries) || empty($deliveries)) {
        return $delivered;
    }

    foreach ($deliveries as $delivery) {
        if (!isset($delivery['items']) || !is_array($delivery['items'])) {
            continue;
        }
        foreach ($delivery['items'] as $item) {
            $item_id = intval($item['item_id']);
            if (!isset($delivered[$item_id])) {
                $delivered[$item_id] = 0;
            }
            $delivered[$item_id] += intval($item['qty']);
        }
    }

    return $delivered;
}

/**
 * Get total returned quantity per line item from WC refunds
 *
 * @param WC_Order $order
 * @return array [item_id => total_returned_qty]
 */
function ah_ho_get_returned_qty_per_item($order) {
    $returned = array();

    foreach ($order->get_refunds() as $refund) {
        if ($refund->get_meta('_return_type', true) !== 'item_return') {
            continue;
        }
        foreach ($refund->get_items() as $refund_item) {
            // WC refund quantities are negative
            $original_item_id = $refund_item->get_meta('_refunded_item_id', true);
            if (!$original_item_id) {
                continue;
            }
            $original_item_id = intval($original_item_id);
            if (!isset($returned[$original_item_id])) {
                $returned[$original_item_id] = 0;
            }
            $returned[$original_item_id] += abs($refund_item->get_quantity());
        }
    }

    return $returned;
}

/**
 * Calculate delivery status based on ordered vs delivered quantities
 *
 * @param WC_Order $order
 * @return string 'not_started' | 'partial' | 'complete'
 */
function ah_ho_calculate_delivery_status($order) {
    $delivered_map = ah_ho_get_delivered_qty_per_item($order);
    $returned_map = ah_ho_get_returned_qty_per_item($order);

    $total_ordered = 0;
    $total_delivered = 0;

    foreach ($order->get_items() as $item_id => $item) {
        $ordered = $item->get_quantity();
        $returned = isset($returned_map[$item_id]) ? $returned_map[$item_id] : 0;
        $effective_ordered = max(0, $ordered - $returned);
        $total_ordered += $effective_ordered;
        $total_delivered += isset($delivered_map[$item_id]) ? min($delivered_map[$item_id], $effective_ordered) : 0;
    }

    if ($total_ordered === 0) {
        return 'complete';
    }
    if ($total_delivered === 0) {
        return 'not_started';
    }
    if ($total_delivered >= $total_ordered) {
        return 'complete';
    }
    return 'partial';
}

/**
 * ========================================
 * META BOX REGISTRATION
 * ========================================
 */

add_action('add_meta_boxes', 'ah_ho_add_fulfillment_meta_box');

function ah_ho_add_fulfillment_meta_box() {
    // Legacy post-type screen
    add_meta_box(
        'ah_ho_order_fulfillment',
        __('Deliveries & Returns', 'ah-ho-custom'),
        'ah_ho_render_fulfillment_meta_box',
        'shop_order',
        'normal',
        'high'
    );

    // HPOS screen
    add_meta_box(
        'ah_ho_order_fulfillment',
        __('Deliveries & Returns', 'ah-ho-custom'),
        'ah_ho_render_fulfillment_meta_box',
        'woocommerce_page_wc-orders',
        'normal',
        'high'
    );
}

/**
 * Render the main fulfillment meta box with tabs
 *
 * @param WP_Post|WC_Order $post_or_order
 */
function ah_ho_render_fulfillment_meta_box($post_or_order) {
    // Handle both WP_Post (legacy) and WC_Order (HPOS)
    if ($post_or_order instanceof WP_Post) {
        $order_id = $post_or_order->ID;
    } else {
        $order_id = $post_or_order->get_id();
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        echo '<p>' . __('Unable to load order data.', 'ah-ho-custom') . '</p>';
        return;
    }

    $delivery_status = $order->get_meta('_delivery_status', true) ?: 'not_started';
    $has_returns = $order->get_meta('_has_returns', true);
    ?>
    <input type="hidden" id="ah-ho-fulfillment-order-id" value="<?php echo esc_attr($order_id); ?>">

    <div class="ah-ho-fulfillment-tabs">
        <button type="button" class="ah-ho-tab-btn active" data-tab="deliveries">
            <?php _e('Deliveries', 'ah-ho-custom'); ?>
            <?php ah_ho_render_delivery_status_badge($delivery_status); ?>
        </button>
        <button type="button" class="ah-ho-tab-btn" data-tab="returns">
            <?php _e('Returns', 'ah-ho-custom'); ?>
            <?php if ($has_returns) : ?>
                <span class="ah-ho-badge ah-ho-badge-red">&bull;</span>
            <?php endif; ?>
        </button>
    </div>

    <div id="ah-ho-tab-deliveries" class="ah-ho-tab-content ah-ho-tab-active">
        <?php ah_ho_render_deliveries_tab($order); ?>
    </div>

    <div id="ah-ho-tab-returns" class="ah-ho-tab-content">
        <?php ah_ho_render_returns_tab($order); ?>
    </div>
    <?php
}

/**
 * Render delivery status badge
 */
function ah_ho_render_delivery_status_badge($status) {
    $labels = array(
        'not_started' => array('label' => __('Not Started', 'ah-ho-custom'), 'color' => '#666'),
        'partial'     => array('label' => __('Partial', 'ah-ho-custom'),     'color' => '#f56e28'),
        'complete'    => array('label' => __('Complete', 'ah-ho-custom'),    'color' => '#2ea44f'),
    );

    $info = isset($labels[$status]) ? $labels[$status] : $labels['not_started'];
    printf(
        '<span class="ah-ho-badge" style="background:%s;color:#fff;">%s</span>',
        esc_attr($info['color']),
        esc_html($info['label'])
    );
}

/**
 * ========================================
 * DELIVERIES TAB
 * ========================================
 */

function ah_ho_render_deliveries_tab($order) {
    $delivered_map = ah_ho_get_delivered_qty_per_item($order);
    $returned_map = ah_ho_get_returned_qty_per_item($order);
    $deliveries = $order->get_meta('_partial_deliveries', true);
    if (!is_array($deliveries)) {
        $deliveries = array();
    }

    // Summary totals
    $grand_ordered = 0;
    $grand_delivered = 0;
    $grand_balance = 0;

    foreach ($order->get_items() as $item_id => $item) {
        $ordered = $item->get_quantity();
        $returned = isset($returned_map[$item_id]) ? $returned_map[$item_id] : 0;
        $effective = max(0, $ordered - $returned);
        $delivered = isset($delivered_map[$item_id]) ? min($delivered_map[$item_id], $effective) : 0;
        $grand_ordered += $effective;
        $grand_delivered += $delivered;
        $grand_balance += ($effective - $delivered);
    }
    ?>
    <div class="ah-ho-delivery-summary">
        <p style="margin:0 0 8px;">
            <strong><?php _e('Overall:', 'ah-ho-custom'); ?></strong>
            <?php printf(
                __('%d delivered of %d (%d remaining)', 'ah-ho-custom'),
                $grand_delivered, $grand_ordered, $grand_balance
            ); ?>
        </p>

        <table class="widefat striped ah-ho-fulfillment-table">
            <thead>
                <tr>
                    <th><?php _e('Product', 'ah-ho-custom'); ?></th>
                    <th style="width:80px;text-align:center;"><?php _e('Ordered', 'ah-ho-custom'); ?></th>
                    <th style="width:80px;text-align:center;"><?php _e('Returned', 'ah-ho-custom'); ?></th>
                    <th style="width:80px;text-align:center;"><?php _e('Effective', 'ah-ho-custom'); ?></th>
                    <th style="width:80px;text-align:center;"><?php _e('Delivered', 'ah-ho-custom'); ?></th>
                    <th style="width:80px;text-align:center;"><?php _e('Balance', 'ah-ho-custom'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->get_items() as $item_id => $item) :
                    $ordered = $item->get_quantity();
                    $returned = isset($returned_map[$item_id]) ? $returned_map[$item_id] : 0;
                    $effective = max(0, $ordered - $returned);
                    $delivered = isset($delivered_map[$item_id]) ? min($delivered_map[$item_id], $effective) : 0;
                    $balance = $effective - $delivered;

                    if ($balance === 0 && $effective > 0) {
                        $balance_color = '#2ea44f';
                    } elseif ($delivered > 0) {
                        $balance_color = '#f56e28';
                    } else {
                        $balance_color = '#666';
                    }
                ?>
                <tr>
                    <td><?php echo esc_html($item->get_name()); ?></td>
                    <td style="text-align:center;"><?php echo intval($ordered); ?></td>
                    <td style="text-align:center;"><?php echo $returned > 0 ? '<span style="color:#b32d2e;">-' . intval($returned) . '</span>' : '0'; ?></td>
                    <td style="text-align:center;font-weight:600;"><?php echo intval($effective); ?></td>
                    <td style="text-align:center;"><?php echo intval($delivered); ?></td>
                    <td style="text-align:center;font-weight:600;color:<?php echo esc_attr($balance_color); ?>;">
                        <?php echo intval($balance); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($deliveries)) : ?>
    <div class="ah-ho-delivery-history" style="margin-top:16px;">
        <h4 style="margin:0 0 8px;"><?php _e('Delivery History', 'ah-ho-custom'); ?></h4>
        <?php foreach (array_reverse($deliveries) as $delivery) : ?>
        <div class="ah-ho-delivery-entry">
            <div class="ah-ho-delivery-header">
                <strong><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($delivery['date']))); ?></strong>
                <?php if (!empty($delivery['notes'])) : ?>
                    <span class="ah-ho-delivery-notes"> &mdash; <?php echo esc_html($delivery['notes']); ?></span>
                <?php endif; ?>
                <span class="ah-ho-delivery-meta">
                    <?php printf(__('by %s', 'ah-ho-custom'), esc_html($delivery['created_by_name'])); ?>
                </span>
                <?php if (current_user_can('manage_options')) : ?>
                    <button type="button" class="ah-ho-delete-delivery button-link" data-delivery-id="<?php echo esc_attr($delivery['id']); ?>" title="<?php esc_attr_e('Delete', 'ah-ho-custom'); ?>">&times;</button>
                <?php endif; ?>
            </div>
            <div class="ah-ho-delivery-items">
                <?php foreach ($delivery['items'] as $d_item) : ?>
                    <span class="ah-ho-delivery-item-chip">
                        <?php echo esc_html($d_item['product_name']); ?> &times;<?php echo intval($d_item['qty']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php
    // Record delivery form - only show if there's remaining balance
    $has_balance = false;
    foreach ($order->get_items() as $item_id => $item) {
        $ordered = $item->get_quantity();
        $returned = isset($returned_map[$item_id]) ? $returned_map[$item_id] : 0;
        $effective = max(0, $ordered - $returned);
        $delivered = isset($delivered_map[$item_id]) ? min($delivered_map[$item_id], $effective) : 0;
        if (($effective - $delivered) > 0) {
            $has_balance = true;
            break;
        }
    }

    if ($has_balance) :
    ?>
    <div class="ah-ho-record-delivery-form" style="margin-top:16px;padding:12px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;">
        <h4 style="margin:0 0 10px;"><?php _e('Record Delivery', 'ah-ho-custom'); ?></h4>
        <div style="display:flex;gap:12px;margin-bottom:10px;flex-wrap:wrap;">
            <div>
                <label for="ah-ho-delivery-date"><strong><?php _e('Date:', 'ah-ho-custom'); ?></strong></label><br>
                <input type="date" id="ah-ho-delivery-date" value="<?php echo esc_attr(date('Y-m-d')); ?>" style="width:160px;">
            </div>
            <div style="flex:1;min-width:200px;">
                <label for="ah-ho-delivery-notes"><strong><?php _e('Notes:', 'ah-ho-custom'); ?></strong></label><br>
                <input type="text" id="ah-ho-delivery-notes" placeholder="<?php esc_attr_e('e.g. Morning delivery run', 'ah-ho-custom'); ?>" style="width:100%;">
            </div>
        </div>
        <table class="widefat ah-ho-delivery-input-table">
            <thead>
                <tr>
                    <th><?php _e('Product', 'ah-ho-custom'); ?></th>
                    <th style="width:80px;text-align:center;"><?php _e('Balance', 'ah-ho-custom'); ?></th>
                    <th style="width:100px;text-align:center;"><?php _e('Deliver Qty', 'ah-ho-custom'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->get_items() as $item_id => $item) :
                    $ordered = $item->get_quantity();
                    $returned = isset($returned_map[$item_id]) ? $returned_map[$item_id] : 0;
                    $effective = max(0, $ordered - $returned);
                    $delivered = isset($delivered_map[$item_id]) ? min($delivered_map[$item_id], $effective) : 0;
                    $balance = $effective - $delivered;
                    if ($balance <= 0) continue;
                ?>
                <tr>
                    <td><?php echo esc_html($item->get_name()); ?></td>
                    <td style="text-align:center;"><?php echo intval($balance); ?></td>
                    <td style="text-align:center;">
                        <input type="number" class="ah-ho-delivery-item-qty" data-item-id="<?php echo esc_attr($item_id); ?>"
                               min="0" max="<?php echo esc_attr($balance); ?>" value="0" style="width:70px;text-align:center;">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin:10px 0 0;">
            <button type="button" id="ah-ho-record-delivery-btn" class="button button-primary">
                <?php _e('Record Delivery', 'ah-ho-custom'); ?>
            </button>
        </p>
    </div>
    <?php else : ?>
    <p style="margin-top:12px;color:#2ea44f;font-style:italic;">
        <?php _e('All items have been fully delivered.', 'ah-ho-custom'); ?>
    </p>
    <?php endif;
}

/**
 * ========================================
 * RETURNS TAB
 * ========================================
 */

function ah_ho_render_returns_tab($order) {
    $returned_map = ah_ho_get_returned_qty_per_item($order);
    $refunds = $order->get_refunds();

    // Filter to item returns only
    $item_returns = array();
    foreach ($refunds as $refund) {
        if ($refund->get_meta('_return_type', true) === 'item_return') {
            $item_returns[] = $refund;
        }
    }

    // Show existing returns
    if (!empty($item_returns)) :
    ?>
    <div class="ah-ho-returns-history" style="margin-bottom:16px;">
        <h4 style="margin:0 0 8px;"><?php _e('Return History', 'ah-ho-custom'); ?></h4>
        <?php foreach ($item_returns as $refund) :
            $reason = $refund->get_meta('_return_reason', true) ?: $refund->get_reason();
            $refund_required = $refund->get_meta('_refund_required', true);
        ?>
        <div class="ah-ho-return-entry">
            <div class="ah-ho-return-header">
                <strong><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($refund->get_date_created()))); ?></strong>
                <span class="ah-ho-return-amount"><?php echo wc_price(abs($refund->get_total())); ?></span>
                <?php if ($refund_required) : ?>
                    <span class="ah-ho-badge ah-ho-badge-red"><?php _e('Refund Required', 'ah-ho-custom'); ?></span>
                <?php endif; ?>
            </div>
            <?php if ($reason) : ?>
                <div class="ah-ho-return-reason"><?php _e('Reason:', 'ah-ho-custom'); ?> <?php echo esc_html($reason); ?></div>
            <?php endif; ?>
            <div class="ah-ho-return-items">
                <?php foreach ($refund->get_items() as $refund_item) : ?>
                    <span class="ah-ho-delivery-item-chip ah-ho-return-chip">
                        <?php echo esc_html($refund_item->get_name()); ?> &times;<?php echo intval(abs($refund_item->get_quantity())); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php
    // Record return form - only show if there are returnable items
    $has_returnable = false;
    foreach ($order->get_items() as $item_id => $item) {
        $ordered = $item->get_quantity();
        $returned = isset($returned_map[$item_id]) ? $returned_map[$item_id] : 0;
        if (($ordered - $returned) > 0) {
            $has_returnable = true;
            break;
        }
    }

    if ($has_returnable) :
    ?>
    <div class="ah-ho-record-return-form" style="padding:12px;background:#fff8f8;border:1px solid #e2b8b8;border-radius:4px;">
        <h4 style="margin:0 0 10px;"><?php _e('Process Return', 'ah-ho-custom'); ?></h4>
        <div style="margin-bottom:10px;">
            <label for="ah-ho-return-reason"><strong><?php _e('Reason:', 'ah-ho-custom'); ?></strong></label><br>
            <input type="text" id="ah-ho-return-reason" placeholder="<?php esc_attr_e('e.g. Customer changed mind, damaged goods', 'ah-ho-custom'); ?>" style="width:100%;" required>
        </div>
        <table class="widefat ah-ho-return-input-table">
            <thead>
                <tr>
                    <th><?php _e('Product', 'ah-ho-custom'); ?></th>
                    <th style="width:80px;text-align:center;"><?php _e('Ordered', 'ah-ho-custom'); ?></th>
                    <th style="width:80px;text-align:center;"><?php _e('Returned', 'ah-ho-custom'); ?></th>
                    <th style="width:80px;text-align:center;"><?php _e('Available', 'ah-ho-custom'); ?></th>
                    <th style="width:100px;text-align:center;"><?php _e('Return Qty', 'ah-ho-custom'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->get_items() as $item_id => $item) :
                    $ordered = $item->get_quantity();
                    $returned = isset($returned_map[$item_id]) ? $returned_map[$item_id] : 0;
                    $available = max(0, $ordered - $returned);
                    if ($available <= 0) continue;
                ?>
                <tr>
                    <td><?php echo esc_html($item->get_name()); ?></td>
                    <td style="text-align:center;"><?php echo intval($ordered); ?></td>
                    <td style="text-align:center;"><?php echo intval($returned); ?></td>
                    <td style="text-align:center;font-weight:600;"><?php echo intval($available); ?></td>
                    <td style="text-align:center;">
                        <input type="number" class="ah-ho-return-item-qty" data-item-id="<?php echo esc_attr($item_id); ?>"
                               min="0" max="<?php echo esc_attr($available); ?>" value="0" style="width:70px;text-align:center;">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin:10px 0 0;">
            <button type="button" id="ah-ho-process-return-btn" class="button" style="background:#b32d2e;color:#fff;border-color:#8b1e1e;">
                <?php _e('Process Return', 'ah-ho-custom'); ?>
            </button>
            <span id="ah-ho-return-processing" style="display:none;margin-left:8px;"><?php _e('Processing...', 'ah-ho-custom'); ?></span>
        </p>
    </div>
    <?php else : ?>
    <p style="color:#666;font-style:italic;">
        <?php _e('No items available for return.', 'ah-ho-custom'); ?>
    </p>
    <?php endif;
}

/**
 * ========================================
 * AJAX HANDLERS
 * ========================================
 */

/**
 * Record a partial delivery
 */
add_action('wp_ajax_ah_ho_record_delivery', 'ah_ho_ajax_record_delivery');

function ah_ho_ajax_record_delivery() {
    if (!wp_verify_nonce($_POST['nonce'], 'ah_ho_fulfillment_nonce')) {
        wp_send_json_error(__('Invalid security token.', 'ah-ho-custom'));
    }
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(__('Permission denied.', 'ah-ho-custom'));
    }

    $order_id = absint($_POST['order_id']);
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(__('Order not found.', 'ah-ho-custom'));
    }

    $date = sanitize_text_field($_POST['date']);
    $notes = sanitize_text_field($_POST['notes']);
    $items_raw = isset($_POST['items']) ? json_decode(stripslashes($_POST['items']), true) : array();

    if (empty($items_raw) || !is_array($items_raw)) {
        wp_send_json_error(__('No items specified.', 'ah-ho-custom'));
    }

    // Validate date
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        wp_send_json_error(__('Invalid date format.', 'ah-ho-custom'));
    }

    // Get current delivery and return data for validation
    $delivered_map = ah_ho_get_delivered_qty_per_item($order);
    $returned_map = ah_ho_get_returned_qty_per_item($order);

    $validated_items = array();
    $summary_parts = array();

    foreach ($items_raw as $raw_item) {
        $item_id = absint($raw_item['item_id']);
        $qty = absint($raw_item['qty']);
        if ($qty <= 0) continue;

        $order_item = $order->get_item($item_id);
        if (!$order_item) continue;

        $ordered = $order_item->get_quantity();
        $returned = isset($returned_map[$item_id]) ? $returned_map[$item_id] : 0;
        $effective = max(0, $ordered - $returned);
        $already_delivered = isset($delivered_map[$item_id]) ? $delivered_map[$item_id] : 0;
        $balance = $effective - $already_delivered;

        if ($qty > $balance) {
            wp_send_json_error(sprintf(
                __('Cannot deliver %d of "%s". Only %d remaining.', 'ah-ho-custom'),
                $qty, $order_item->get_name(), $balance
            ));
        }

        $validated_items[] = array(
            'item_id'      => $item_id,
            'product_name' => $order_item->get_name(),
            'qty'          => $qty,
        );
        $summary_parts[] = $order_item->get_name() . ' x' . $qty;
    }

    if (empty($validated_items)) {
        wp_send_json_error(__('No valid items to deliver.', 'ah-ho-custom'));
    }

    // Build delivery record
    $delivery = array(
        'id'              => 'del_' . wp_generate_password(8, false, false),
        'date'            => $date,
        'notes'           => $notes,
        'items'           => $validated_items,
        'created_by'      => get_current_user_id(),
        'created_by_name' => wp_get_current_user()->display_name,
        'created_at'      => current_time('mysql'),
    );

    // Append to existing deliveries
    $deliveries = $order->get_meta('_partial_deliveries', true);
    if (!is_array($deliveries)) {
        $deliveries = array();
    }
    $deliveries[] = $delivery;
    $order->update_meta_data('_partial_deliveries', $deliveries);

    // Update delivery status
    $status = ah_ho_calculate_delivery_status($order);
    $order->update_meta_data('_delivery_status', $status);

    // Add order note
    $order->add_order_note(sprintf(
        __('Partial delivery recorded (%s): %s by %s', 'ah-ho-custom'),
        date_i18n(get_option('date_format'), strtotime($date)),
        implode(', ', $summary_parts),
        wp_get_current_user()->display_name
    ));

    $order->save();

    // Return refreshed HTML
    ob_start();
    ah_ho_render_deliveries_tab($order);
    $deliveries_html = ob_get_clean();

    wp_send_json_success(array(
        'html'   => $deliveries_html,
        'status' => $status,
    ));
}

/**
 * Delete a delivery record
 */
add_action('wp_ajax_ah_ho_delete_delivery', 'ah_ho_ajax_delete_delivery');

function ah_ho_ajax_delete_delivery() {
    if (!wp_verify_nonce($_POST['nonce'], 'ah_ho_fulfillment_nonce')) {
        wp_send_json_error(__('Invalid security token.', 'ah-ho-custom'));
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Only administrators can delete delivery records.', 'ah-ho-custom'));
    }

    $order_id = absint($_POST['order_id']);
    $delivery_id = sanitize_text_field($_POST['delivery_id']);
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(__('Order not found.', 'ah-ho-custom'));
    }

    $deliveries = $order->get_meta('_partial_deliveries', true);
    if (!is_array($deliveries)) {
        wp_send_json_error(__('No delivery records found.', 'ah-ho-custom'));
    }

    // Find and remove the delivery
    $found = false;
    $removed_summary = '';
    foreach ($deliveries as $index => $delivery) {
        if ($delivery['id'] === $delivery_id) {
            $items_desc = array();
            foreach ($delivery['items'] as $d_item) {
                $items_desc[] = $d_item['product_name'] . ' x' . $d_item['qty'];
            }
            $removed_summary = implode(', ', $items_desc) . ' on ' . $delivery['date'];
            unset($deliveries[$index]);
            $found = true;
            break;
        }
    }

    if (!$found) {
        wp_send_json_error(__('Delivery record not found.', 'ah-ho-custom'));
    }

    // Re-index and save
    $deliveries = array_values($deliveries);
    $order->update_meta_data('_partial_deliveries', $deliveries);

    $status = ah_ho_calculate_delivery_status($order);
    $order->update_meta_data('_delivery_status', $status);

    $order->add_order_note(sprintf(
        __('Delivery record deleted: %s (by %s)', 'ah-ho-custom'),
        $removed_summary,
        wp_get_current_user()->display_name
    ));

    $order->save();

    ob_start();
    ah_ho_render_deliveries_tab($order);
    $deliveries_html = ob_get_clean();

    wp_send_json_success(array(
        'html'   => $deliveries_html,
        'status' => $status,
    ));
}

/**
 * Process an item return via WooCommerce refund system
 */
add_action('wp_ajax_ah_ho_process_return', 'ah_ho_ajax_process_return');

function ah_ho_ajax_process_return() {
    if (!wp_verify_nonce($_POST['nonce'], 'ah_ho_fulfillment_nonce')) {
        wp_send_json_error(__('Invalid security token.', 'ah-ho-custom'));
    }
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(__('Permission denied.', 'ah-ho-custom'));
    }

    $order_id = absint($_POST['order_id']);
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(__('Order not found.', 'ah-ho-custom'));
    }

    $reason = sanitize_text_field($_POST['reason']);
    if (empty($reason)) {
        wp_send_json_error(__('Please provide a reason for the return.', 'ah-ho-custom'));
    }

    $items_raw = isset($_POST['items']) ? json_decode(stripslashes($_POST['items']), true) : array();
    if (empty($items_raw) || !is_array($items_raw)) {
        wp_send_json_error(__('No items specified for return.', 'ah-ho-custom'));
    }

    $returned_map = ah_ho_get_returned_qty_per_item($order);

    $refund_line_items = array();
    $refund_amount = 0;
    $summary_parts = array();

    foreach ($items_raw as $raw_item) {
        $item_id = absint($raw_item['item_id']);
        $qty = absint($raw_item['qty']);
        if ($qty <= 0) continue;

        $item = $order->get_item($item_id);
        if (!$item) continue;

        $ordered = $item->get_quantity();
        $already_returned = isset($returned_map[$item_id]) ? $returned_map[$item_id] : 0;
        $available = max(0, $ordered - $already_returned);

        if ($qty > $available) {
            wp_send_json_error(sprintf(
                __('Cannot return %d of "%s". Only %d available.', 'ah-ho-custom'),
                $qty, $item->get_name(), $available
            ));
        }

        // Calculate refund amount for this line item
        $item_total = floatval($item->get_total());
        $item_qty = $item->get_quantity();
        $unit_price = ($item_qty > 0) ? ($item_total / $item_qty) : 0;
        $line_refund = round($unit_price * $qty, 2);

        // Handle taxes
        $refund_taxes = array();
        $taxes = $item->get_taxes();
        if (!empty($taxes['total'])) {
            foreach ($taxes['total'] as $tax_id => $tax_total) {
                $tax_per_unit = ($item_qty > 0) ? (floatval($tax_total) / $item_qty) : 0;
                $tax_refund = round($tax_per_unit * $qty, 2);
                $refund_taxes[$tax_id] = $tax_refund;
                $refund_amount += $tax_refund;
            }
        }

        $refund_line_items[$item_id] = array(
            'qty'          => $qty,
            'refund_total' => $line_refund,
            'refund_tax'   => $refund_taxes,
        );

        $refund_amount += $line_refund;
        $summary_parts[] = $item->get_name() . ' x' . $qty;
    }

    if (empty($refund_line_items)) {
        wp_send_json_error(__('No valid items to return.', 'ah-ho-custom'));
    }

    // Create WooCommerce refund
    $refund = wc_create_refund(array(
        'amount'         => $refund_amount,
        'reason'         => $reason,
        'order_id'       => $order->get_id(),
        'line_items'     => $refund_line_items,
        'refund_payment' => false, // Never auto-refund - handled manually
        'restock_items'  => true,
    ));

    if (is_wp_error($refund)) {
        wp_send_json_error($refund->get_error_message());
    }

    // Add custom meta to the refund
    $refund->update_meta_data('_return_type', 'item_return');
    $refund->update_meta_data('_return_reason', $reason);

    // Determine payment terms and set refund flag
    $customer_id = $order->get_customer_id();
    $payment_terms = $customer_id ? get_user_meta($customer_id, '_payment_terms', true) : '';

    if ($payment_terms === 'cod') {
        $refund->update_meta_data('_refund_required', true);
        $order->add_order_note(sprintf(
            __('Item return processed (%s): %s. COD order - monetary refund of %s required. Process manually.', 'ah-ho-custom'),
            $reason,
            implode(', ', $summary_parts),
            wc_price($refund_amount)
        ));
    } else {
        $refund->update_meta_data('_refund_required', false);
        $credit_label = $payment_terms ? $payment_terms : 'credit';
        $order->add_order_note(sprintf(
            __('Item return processed (%s): %s. %s customer - invoice reduced by %s. New order total: %s.', 'ah-ho-custom'),
            $reason,
            implode(', ', $summary_parts),
            ucfirst(str_replace('_', ' ', $credit_label)),
            wc_price($refund_amount),
            wc_price($order->get_total())
        ));
    }

    $refund->save();

    // Update order-level flags
    $order->update_meta_data('_has_returns', true);

    // Recalculate total returned quantity
    $total_returned = 0;
    foreach ($order->get_refunds() as $r) {
        if ($r->get_meta('_return_type', true) === 'item_return') {
            foreach ($r->get_items() as $ri) {
                $total_returned += abs($ri->get_quantity());
            }
        }
    }
    $order->update_meta_data('_total_returned_quantity', $total_returned);

    // Recalculate delivery status (effective ordered qty changed)
    $status = ah_ho_calculate_delivery_status($order);
    $order->update_meta_data('_delivery_status', $status);

    $order->save();

    // Return refreshed HTML for both tabs
    ob_start();
    ah_ho_render_returns_tab($order);
    $returns_html = ob_get_clean();

    ob_start();
    ah_ho_render_deliveries_tab($order);
    $deliveries_html = ob_get_clean();

    wp_send_json_success(array(
        'returns_html'    => $returns_html,
        'deliveries_html' => $deliveries_html,
        'delivery_status' => $status,
        'new_total'       => $order->get_total(),
    ));
}

/**
 * ========================================
 * ADMIN CSS & JS
 * ========================================
 */

add_action('admin_footer', 'ah_ho_fulfillment_admin_scripts');

function ah_ho_fulfillment_admin_scripts() {
    // Only load on order edit pages
    $screen = get_current_screen();
    if (!$screen) return;

    $is_order_edit = false;
    // HPOS order edit
    if ($screen->id === 'woocommerce_page_wc-orders' && isset($_GET['action']) && $_GET['action'] === 'edit') {
        $is_order_edit = true;
    }
    // Legacy order edit
    if ($screen->id === 'shop_order' && isset($_GET['action']) && $_GET['action'] === 'edit') {
        $is_order_edit = true;
    }
    // Legacy new order
    if ($screen->id === 'shop_order' && get_post_type() === 'shop_order') {
        $is_order_edit = true;
    }

    if (!$is_order_edit) return;
    ?>
    <style>
        /* Tabs */
        .ah-ho-fulfillment-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #ddd;
            margin-bottom: 12px;
        }
        .ah-ho-tab-btn {
            padding: 8px 16px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .ah-ho-tab-btn:hover {
            color: #333;
        }
        .ah-ho-tab-btn.active {
            color: #2271b1;
            border-bottom-color: #2271b1;
        }
        .ah-ho-tab-content {
            display: none;
        }
        .ah-ho-tab-content.ah-ho-tab-active {
            display: block;
        }

        /* Badges */
        .ah-ho-badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            line-height: 18px;
        }
        .ah-ho-badge-red {
            background: #b32d2e;
            color: #fff;
        }

        /* Tables */
        .ah-ho-fulfillment-table th,
        .ah-ho-delivery-input-table th,
        .ah-ho-return-input-table th {
            font-size: 12px;
            padding: 6px 8px;
        }
        .ah-ho-fulfillment-table td,
        .ah-ho-delivery-input-table td,
        .ah-ho-return-input-table td {
            padding: 6px 8px;
            font-size: 13px;
        }

        /* Delivery history entries */
        .ah-ho-delivery-entry {
            padding: 8px 10px;
            margin-bottom: 6px;
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
        }
        .ah-ho-delivery-header {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .ah-ho-delivery-meta {
            color: #888;
            font-size: 12px;
        }
        .ah-ho-delivery-notes {
            color: #555;
            font-size: 12px;
        }
        .ah-ho-delete-delivery {
            color: #b32d2e !important;
            font-size: 16px;
            font-weight: bold;
            margin-left: auto;
            text-decoration: none !important;
            cursor: pointer;
            padding: 0 4px;
        }
        .ah-ho-delete-delivery:hover {
            color: #dc3545 !important;
        }
        .ah-ho-delivery-items {
            margin-top: 4px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .ah-ho-delivery-item-chip {
            display: inline-block;
            padding: 2px 8px;
            background: #e8f0fe;
            color: #1a56db;
            border-radius: 3px;
            font-size: 12px;
        }

        /* Return entries */
        .ah-ho-return-entry {
            padding: 8px 10px;
            margin-bottom: 6px;
            background: #fff8f8;
            border: 1px solid #e2b8b8;
            border-radius: 4px;
        }
        .ah-ho-return-header {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .ah-ho-return-amount {
            font-weight: 600;
            color: #b32d2e;
        }
        .ah-ho-return-reason {
            color: #555;
            font-size: 12px;
            margin-top: 2px;
        }
        .ah-ho-return-items {
            margin-top: 4px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .ah-ho-return-chip {
            background: #fde8e8;
            color: #b32d2e;
        }
    </style>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var $metabox = $('#ah_ho_order_fulfillment');
        if (!$metabox.length) return;

        var orderId = $('#ah-ho-fulfillment-order-id').val();
        var nonce = '<?php echo wp_create_nonce('ah_ho_fulfillment_nonce'); ?>';

        // Tab switching
        $metabox.on('click', '.ah-ho-tab-btn', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            $metabox.find('.ah-ho-tab-btn').removeClass('active');
            $(this).addClass('active');
            $metabox.find('.ah-ho-tab-content').removeClass('ah-ho-tab-active');
            $metabox.find('#ah-ho-tab-' + tab).addClass('ah-ho-tab-active');
        });

        // Record Delivery
        $metabox.on('click', '#ah-ho-record-delivery-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);

            var items = [];
            $metabox.find('.ah-ho-delivery-item-qty').each(function() {
                var qty = parseInt($(this).val()) || 0;
                if (qty > 0) {
                    items.push({
                        item_id: $(this).data('item-id'),
                        qty: qty
                    });
                }
            });

            if (items.length === 0) {
                alert('<?php echo esc_js(__('Please enter at least one delivery quantity.', 'ah-ho-custom')); ?>');
                return;
            }

            $btn.prop('disabled', true).text('<?php echo esc_js(__('Recording...', 'ah-ho-custom')); ?>');

            $.post(ajaxurl, {
                action: 'ah_ho_record_delivery',
                nonce: nonce,
                order_id: orderId,
                date: $metabox.find('#ah-ho-delivery-date').val(),
                notes: $metabox.find('#ah-ho-delivery-notes').val(),
                items: JSON.stringify(items)
            }, function(response) {
                if (response.success) {
                    $metabox.find('#ah-ho-tab-deliveries').html(response.data.html);
                    // Update status badge in tab
                    ah_ho_update_status_badge(response.data.status);
                } else {
                    alert(response.data || '<?php echo esc_js(__('An error occurred.', 'ah-ho-custom')); ?>');
                }
                $btn.prop('disabled', false).text('<?php echo esc_js(__('Record Delivery', 'ah-ho-custom')); ?>');
            }).fail(function() {
                alert('<?php echo esc_js(__('Request failed. Please try again.', 'ah-ho-custom')); ?>');
                $btn.prop('disabled', false).text('<?php echo esc_js(__('Record Delivery', 'ah-ho-custom')); ?>');
            });
        });

        // Delete Delivery
        $metabox.on('click', '.ah-ho-delete-delivery', function(e) {
            e.preventDefault();
            if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this delivery record?', 'ah-ho-custom')); ?>')) {
                return;
            }

            var deliveryId = $(this).data('delivery-id');
            var $entry = $(this).closest('.ah-ho-delivery-entry');
            $entry.css('opacity', '0.5');

            $.post(ajaxurl, {
                action: 'ah_ho_delete_delivery',
                nonce: nonce,
                order_id: orderId,
                delivery_id: deliveryId
            }, function(response) {
                if (response.success) {
                    $metabox.find('#ah-ho-tab-deliveries').html(response.data.html);
                    ah_ho_update_status_badge(response.data.status);
                } else {
                    alert(response.data || '<?php echo esc_js(__('An error occurred.', 'ah-ho-custom')); ?>');
                    $entry.css('opacity', '1');
                }
            }).fail(function() {
                alert('<?php echo esc_js(__('Request failed.', 'ah-ho-custom')); ?>');
                $entry.css('opacity', '1');
            });
        });

        // Process Return
        $metabox.on('click', '#ah-ho-process-return-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var reason = $metabox.find('#ah-ho-return-reason').val().trim();

            if (!reason) {
                alert('<?php echo esc_js(__('Please provide a reason for the return.', 'ah-ho-custom')); ?>');
                $metabox.find('#ah-ho-return-reason').focus();
                return;
            }

            var items = [];
            $metabox.find('.ah-ho-return-item-qty').each(function() {
                var qty = parseInt($(this).val()) || 0;
                if (qty > 0) {
                    items.push({
                        item_id: $(this).data('item-id'),
                        qty: qty
                    });
                }
            });

            if (items.length === 0) {
                alert('<?php echo esc_js(__('Please enter at least one return quantity.', 'ah-ho-custom')); ?>');
                return;
            }

            if (!confirm('<?php echo esc_js(__('This will process a return and adjust the order total. This action cannot be easily undone. Continue?', 'ah-ho-custom')); ?>')) {
                return;
            }

            $btn.prop('disabled', true);
            $metabox.find('#ah-ho-return-processing').show();

            $.post(ajaxurl, {
                action: 'ah_ho_process_return',
                nonce: nonce,
                order_id: orderId,
                reason: reason,
                items: JSON.stringify(items)
            }, function(response) {
                if (response.success) {
                    $metabox.find('#ah-ho-tab-returns').html(response.data.returns_html);
                    $metabox.find('#ah-ho-tab-deliveries').html(response.data.deliveries_html);
                    ah_ho_update_status_badge(response.data.delivery_status);

                    // Refresh the page to update order total display
                    if (response.data.new_total) {
                        location.reload();
                    }
                } else {
                    alert(response.data || '<?php echo esc_js(__('An error occurred.', 'ah-ho-custom')); ?>');
                }
                $btn.prop('disabled', false);
                $metabox.find('#ah-ho-return-processing').hide();
            }).fail(function() {
                alert('<?php echo esc_js(__('Request failed. Please try again.', 'ah-ho-custom')); ?>');
                $btn.prop('disabled', false);
                $metabox.find('#ah-ho-return-processing').hide();
            });
        });

        // Helper: update delivery status badge in tab
        function ah_ho_update_status_badge(status) {
            var colors = {
                'not_started': '#666',
                'partial': '#f56e28',
                'complete': '#2ea44f'
            };
            var labels = {
                'not_started': '<?php echo esc_js(__('Not Started', 'ah-ho-custom')); ?>',
                'partial': '<?php echo esc_js(__('Partial', 'ah-ho-custom')); ?>',
                'complete': '<?php echo esc_js(__('Complete', 'ah-ho-custom')); ?>'
            };
            var $badge = $metabox.find('.ah-ho-tab-btn[data-tab="deliveries"] .ah-ho-badge');
            if ($badge.length) {
                $badge.css('background', colors[status] || '#666').text(labels[status] || status);
            }
        }
    });
    </script>
    <?php
}
