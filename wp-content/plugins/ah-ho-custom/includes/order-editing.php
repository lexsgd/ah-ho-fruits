<?php
/**
 * Order Editing with Audit Trail
 *
 * Adds an "Edit Items" tab to the Deliveries & Returns metabox.
 * Allows changing quantities, adding/removing items, with full
 * change history tracking.
 *
 * @package AhHoCustom
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ========================================
 * EDIT ITEMS TAB RENDERER
 * ========================================
 */

function ah_ho_render_edits_tab($order) {
    $edit_history = $order->get_meta('_order_edit_history', true);
    if (!is_array($edit_history)) {
        $edit_history = array();
    }

    $delivered_map = ah_ho_get_delivered_qty_per_item($order);
    $returned_map  = ah_ho_get_returned_qty_per_item($order);
    ?>
    <div class="ah-ho-edit-items-form" style="margin-bottom:16px;">
        <table class="widefat ah-ho-edit-items-table">
            <thead>
                <tr>
                    <th><?php _e('Product', 'ah-ho-custom'); ?></th>
                    <th style="width:70px;text-align:center;"><?php _e('Current', 'ah-ho-custom'); ?></th>
                    <th style="width:80px;text-align:center;"><?php _e('New Qty', 'ah-ho-custom'); ?></th>
                    <th style="width:80px;text-align:right;"><?php _e('Unit Price', 'ah-ho-custom'); ?></th>
                    <th style="width:50px;text-align:center;"><?php _e('', 'ah-ho-custom'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->get_items() as $item_id => $item) :
                    $qty = $item->get_quantity();
                    $item_total = (float) $item->get_total();
                    $unit_price = ($qty > 0) ? ($item_total / $qty) : 0;
                    $delivered = isset($delivered_map[$item_id]) ? $delivered_map[$item_id] : 0;
                    $returned  = isset($returned_map[$item_id]) ? $returned_map[$item_id] : 0;
                    $min_qty   = max(0, $delivered); // Can't go below delivered qty
                ?>
                <tr data-item-id="<?php echo esc_attr($item_id); ?>">
                    <td>
                        <?php echo esc_html($item->get_name()); ?>
                        <?php if ($delivered > 0) : ?>
                            <br><small style="color:#888;"><?php printf(__('%d delivered', 'ah-ho-custom'), $delivered); ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;color:#666;"><?php echo intval($qty); ?></td>
                    <td style="text-align:center;">
                        <input type="number" class="ah-ho-edit-qty" data-item-id="<?php echo esc_attr($item_id); ?>"
                               data-original-qty="<?php echo esc_attr($qty); ?>"
                               min="<?php echo esc_attr($min_qty); ?>" value="<?php echo esc_attr($qty); ?>"
                               style="width:60px;text-align:center;">
                    </td>
                    <td style="text-align:right;">$<?php echo esc_html(number_format($unit_price, 2)); ?></td>
                    <td style="text-align:center;">
                        <?php if ($delivered == 0 && $returned == 0) : ?>
                        <button type="button" class="ah-ho-remove-item button-link" data-item-id="<?php echo esc_attr($item_id); ?>"
                                style="color:#b32d2e;font-size:16px;font-weight:bold;cursor:pointer;" title="<?php esc_attr_e('Remove item', 'ah-ho-custom'); ?>">&times;</button>
                        <?php else : ?>
                        <span style="color:#ccc;" title="<?php esc_attr_e('Cannot remove: has deliveries or returns', 'ah-ho-custom'); ?>">&times;</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Add Product -->
        <div style="margin-top:12px;padding:10px;background:#f0f6fc;border:1px solid #c3d9ed;border-radius:4px;">
            <label><strong><?php _e('Add Product:', 'ah-ho-custom'); ?></strong></label>
            <div style="display:flex;gap:8px;margin-top:6px;align-items:flex-end;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                    <select id="ah-ho-edit-add-product" class="wc-product-search" style="width:100%;"
                            data-placeholder="<?php esc_attr_e('Search for a product...', 'ah-ho-custom'); ?>"
                            data-action="woocommerce_json_search_products_and_variations"
                            data-exclude_type="variable">
                    </select>
                </div>
                <div>
                    <label><?php _e('Qty:', 'ah-ho-custom'); ?></label>
                    <input type="number" id="ah-ho-edit-add-qty" min="1" value="1" style="width:60px;text-align:center;">
                </div>
                <button type="button" id="ah-ho-edit-add-btn" class="button">
                    <?php _e('+ Add', 'ah-ho-custom'); ?>
                </button>
            </div>
        </div>

        <!-- Reason + Save -->
        <div style="margin-top:12px;">
            <label for="ah-ho-edit-reason"><strong><?php _e('Reason for edit:', 'ah-ho-custom'); ?></strong></label>
            <input type="text" id="ah-ho-edit-reason" placeholder="<?php esc_attr_e('e.g. Customer called to change order', 'ah-ho-custom'); ?>" style="width:100%;margin-top:4px;">
        </div>
        <p style="margin:10px 0 0;">
            <button type="button" id="ah-ho-save-edits-btn" class="button button-primary">
                <?php _e('Save Changes', 'ah-ho-custom'); ?>
            </button>
            <span id="ah-ho-edit-saving" style="display:none;margin-left:8px;"><?php _e('Saving...', 'ah-ho-custom'); ?></span>
        </p>
    </div>

    <?php if (!empty($edit_history)) : ?>
    <div class="ah-ho-edit-history" style="margin-top:16px;">
        <h4 style="margin:0 0 8px;"><?php _e('Edit History', 'ah-ho-custom'); ?></h4>
        <?php foreach (array_reverse($edit_history) as $edit) : ?>
        <div class="ah-ho-edit-entry" style="padding:8px 10px;margin-bottom:6px;background:#f0f6fc;border:1px solid #c3d9ed;border-radius:4px;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <strong><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($edit['date']))); ?></strong>
                <?php if (!empty($edit['reason'])) : ?>
                    <span style="color:#555;font-size:12px;"> &mdash; <?php echo esc_html($edit['reason']); ?></span>
                <?php endif; ?>
                <span style="color:#888;font-size:12px;margin-left:auto;">
                    <?php printf(__('by %s', 'ah-ho-custom'), esc_html($edit['user_name'])); ?>
                </span>
            </div>
            <div style="margin-top:4px;display:flex;gap:6px;flex-wrap:wrap;">
                <?php foreach ($edit['changes'] as $change) : ?>
                    <?php if ($change['type'] === 'qty_change') : ?>
                    <span class="ah-ho-delivery-item-chip" style="background:#fff3cd;color:#856404;">
                        <?php echo esc_html($change['item_name']); ?>: <?php echo intval($change['old_qty']); ?> &rarr; <?php echo intval($change['new_qty']); ?>
                    </span>
                    <?php elseif ($change['type'] === 'item_added') : ?>
                    <span class="ah-ho-delivery-item-chip" style="background:#d4edda;color:#155724;">
                        + <?php echo esc_html($change['item_name']); ?> &times;<?php echo intval($change['qty']); ?>
                    </span>
                    <?php elseif ($change['type'] === 'item_removed') : ?>
                    <span class="ah-ho-delivery-item-chip" style="background:#fde8e8;color:#b32d2e;">
                        &minus; <?php echo esc_html($change['item_name']); ?> &times;<?php echo intval($change['old_qty']); ?>
                    </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:4px;font-size:12px;color:#666;">
                <?php printf(
                    __('Total: %s &rarr; %s', 'ah-ho-custom'),
                    wc_price($edit['old_total']),
                    wc_price($edit['new_total'])
                ); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif;
}


/**
 * ========================================
 * AJAX HANDLER: Save Order Edits
 * ========================================
 */

add_action('wp_ajax_ah_ho_save_order_edits', 'ah_ho_ajax_save_order_edits');

function ah_ho_ajax_save_order_edits() {
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
        wp_send_json_error(__('Please provide a reason for the edit.', 'ah-ho-custom'));
    }

    $qty_changes   = isset($_POST['qty_changes'])   ? json_decode(stripslashes($_POST['qty_changes']), true) : array();
    $new_items     = isset($_POST['new_items'])      ? json_decode(stripslashes($_POST['new_items']), true) : array();
    $removed_items = isset($_POST['removed_items'])  ? json_decode(stripslashes($_POST['removed_items']), true) : array();

    if (empty($qty_changes) && empty($new_items) && empty($removed_items)) {
        wp_send_json_error(__('No changes to save.', 'ah-ho-custom'));
    }

    // Get delivery tracking maps for validation
    $delivered_map = ah_ho_get_delivered_qty_per_item($order);
    $returned_map  = ah_ho_get_returned_qty_per_item($order);

    // Snapshot current state
    $old_total = (float) $order->get_total();
    $changes = array();

    // 1. Process quantity changes
    if (is_array($qty_changes)) {
        foreach ($qty_changes as $change) {
            $item_id = absint($change['item_id']);
            $new_qty = absint($change['qty']);
            $item = $order->get_item($item_id);
            if (!$item) continue;

            $old_qty = $item->get_quantity();
            if ($new_qty == $old_qty) continue;

            // Validate: can't go below delivered quantity
            $delivered = isset($delivered_map[$item_id]) ? $delivered_map[$item_id] : 0;
            if ($new_qty < $delivered) {
                wp_send_json_error(sprintf(
                    __('Cannot set "%s" to %d — already %d delivered.', 'ah-ho-custom'),
                    $item->get_name(), $new_qty, $delivered
                ));
            }

            // Calculate new totals
            $item_total = (float) $item->get_total();
            $unit_price = ($old_qty > 0) ? ($item_total / $old_qty) : 0;

            $item->set_quantity($new_qty);
            $item->set_subtotal(round($unit_price * $new_qty, 2));
            $item->set_total(round($unit_price * $new_qty, 2));
            $item->save();

            $changes[] = array(
                'type'      => 'qty_change',
                'item_name' => $item->get_name(),
                'old_qty'   => $old_qty,
                'new_qty'   => $new_qty,
            );
        }
    }

    // 2. Remove items
    if (is_array($removed_items)) {
        foreach ($removed_items as $item_id) {
            $item_id = absint($item_id);
            $item = $order->get_item($item_id);
            if (!$item) continue;

            // Validate: can't remove items with deliveries or returns
            $delivered = isset($delivered_map[$item_id]) ? $delivered_map[$item_id] : 0;
            $returned  = isset($returned_map[$item_id]) ? $returned_map[$item_id] : 0;
            if ($delivered > 0 || $returned > 0) {
                wp_send_json_error(sprintf(
                    __('Cannot remove "%s" — it has deliveries or returns recorded.', 'ah-ho-custom'),
                    $item->get_name()
                ));
            }

            $changes[] = array(
                'type'      => 'item_removed',
                'item_name' => $item->get_name(),
                'old_qty'   => $item->get_quantity(),
            );

            $order->remove_item($item_id);
        }
    }

    // 3. Add new items
    if (is_array($new_items)) {
        foreach ($new_items as $new_item) {
            $product_id = absint($new_item['product_id']);
            $qty = absint($new_item['qty']);
            if ($qty <= 0) continue;

            $product = wc_get_product($product_id);
            if (!$product) {
                wp_send_json_error(sprintf(__('Product #%d not found.', 'ah-ho-custom'), $product_id));
            }

            $item_id = $order->add_product($product, $qty);

            $changes[] = array(
                'type'       => 'item_added',
                'item_name'  => $product->get_name(),
                'qty'        => $qty,
                'unit_price' => (float) $product->get_price(),
            );
        }
    }

    if (empty($changes)) {
        wp_send_json_error(__('No actual changes detected.', 'ah-ho-custom'));
    }

    // Recalculate order totals
    $order->calculate_totals();
    $new_total = (float) $order->get_total();

    // Record in edit history
    $edit_history = $order->get_meta('_order_edit_history', true);
    if (!is_array($edit_history)) {
        $edit_history = array();
    }

    $current_user = wp_get_current_user();
    $edit_history[] = array(
        'id'        => 'edit_' . substr(md5(uniqid()), 0, 8),
        'date'      => current_time('Y-m-d H:i:s'),
        'user_id'   => $current_user->ID,
        'user_name' => $current_user->display_name,
        'reason'    => $reason,
        'changes'   => $changes,
        'old_total' => number_format($old_total, 2, '.', ''),
        'new_total' => number_format($new_total, 2, '.', ''),
    );

    $order->update_meta_data('_order_edit_history', $edit_history);
    $order->update_meta_data('_has_edits', true);

    // Recalculate delivery status (effective qty may have changed)
    $status = ah_ho_calculate_delivery_status($order);
    $order->update_meta_data('_delivery_status', $status);

    $order->save();

    // Add order note
    $note_parts = array();
    foreach ($changes as $c) {
        if ($c['type'] === 'qty_change') {
            $note_parts[] = $c['item_name'] . ': ' . $c['old_qty'] . ' → ' . $c['new_qty'];
        } elseif ($c['type'] === 'item_added') {
            $note_parts[] = '+ ' . $c['item_name'] . ' x' . $c['qty'];
        } elseif ($c['type'] === 'item_removed') {
            $note_parts[] = '- ' . $c['item_name'] . ' x' . $c['old_qty'];
        }
    }
    $order->add_order_note(sprintf(
        __('Order edited (%s): %s. Total: %s → %s.', 'ah-ho-custom'),
        $reason,
        implode(', ', $note_parts),
        wc_price($old_total),
        wc_price($new_total)
    ));

    // Return refreshed HTML for all tabs
    ob_start();
    ah_ho_render_edits_tab($order);
    $edits_html = ob_get_clean();

    ob_start();
    ah_ho_render_deliveries_tab($order);
    $deliveries_html = ob_get_clean();

    wp_send_json_success(array(
        'edits_html'      => $edits_html,
        'deliveries_html' => $deliveries_html,
        'delivery_status' => $status,
        'new_total'       => $order->get_total(),
    ));
}


/**
 * ========================================
 * ADMIN CSS & JS for Edit Items tab
 * ========================================
 */

add_action('admin_footer', 'ah_ho_edit_items_admin_scripts');

function ah_ho_edit_items_admin_scripts() {
    $screen = get_current_screen();
    if (!$screen) return;

    $is_order_edit = false;
    if ($screen->id === 'woocommerce_page_wc-orders' && isset($_GET['action']) && $_GET['action'] === 'edit') {
        $is_order_edit = true;
    }
    if ($screen->id === 'shop_order' && isset($_GET['action']) && $_GET['action'] === 'edit') {
        $is_order_edit = true;
    }
    if ($screen->id === 'shop_order' && get_post_type() === 'shop_order') {
        $is_order_edit = true;
    }

    if (!$is_order_edit) return;
    ?>
    <style>
        .ah-ho-edit-items-table th,
        .ah-ho-edit-items-table td {
            padding: 6px 8px;
            font-size: 13px;
        }
        .ah-ho-edit-items-table .ah-ho-edit-qty {
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 3px;
        }
        .ah-ho-edit-items-table .ah-ho-edit-qty:focus {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
            outline: none;
        }
        .ah-ho-edit-items-table tr.ah-ho-row-changed {
            background: #fff8e5 !important;
        }
        .ah-ho-edit-items-table tr.ah-ho-row-removed {
            background: #fde8e8 !important;
            opacity: 0.6;
        }
        .ah-ho-edit-items-table tr.ah-ho-row-removed input {
            pointer-events: none;
        }
        .ah-ho-new-item-row {
            background: #d4edda !important;
        }
    </style>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var $metabox = $('#ah_ho_order_fulfillment');
        if (!$metabox.length) return;

        var orderId = $('#ah-ho-fulfillment-order-id').val();
        var nonce = $metabox.data('nonce') || '<?php echo wp_create_nonce('ah_ho_fulfillment_nonce'); ?>';
        var removedItems = [];
        var newItems = [];

        // Initialize WC product search on the Edit tab
        function initProductSearch() {
            var $select = $metabox.find('#ah-ho-edit-add-product');
            if ($select.length && !$select.hasClass('select2-hidden-accessible')) {
                $(document.body).trigger('wc-enhanced-select-init');
            }
        }

        // Init on tab switch to Edit Items
        $metabox.on('click', '.ah-ho-tab-btn[data-tab="edits"]', function() {
            setTimeout(initProductSearch, 100);
        });

        // Also init if Edit tab is already visible
        setTimeout(initProductSearch, 500);

        // Highlight changed quantities
        $metabox.on('input', '.ah-ho-edit-qty', function() {
            var $row = $(this).closest('tr');
            var original = parseInt($(this).data('original-qty'));
            var current = parseInt($(this).val()) || 0;
            $row.toggleClass('ah-ho-row-changed', current !== original);
        });

        // Remove item
        $metabox.on('click', '.ah-ho-remove-item', function(e) {
            e.preventDefault();
            var itemId = $(this).data('item-id');
            var $row = $(this).closest('tr');

            if ($row.hasClass('ah-ho-row-removed')) {
                // Undo removal
                $row.removeClass('ah-ho-row-removed');
                removedItems = removedItems.filter(function(id) { return id !== itemId; });
            } else {
                if (!confirm('<?php echo esc_js(__('Mark this item for removal? Click Save Changes to confirm.', 'ah-ho-custom')); ?>')) {
                    return;
                }
                $row.addClass('ah-ho-row-removed');
                removedItems.push(itemId);
            }
        });

        // Add product
        $metabox.on('click', '#ah-ho-edit-add-btn', function(e) {
            e.preventDefault();
            var $select = $metabox.find('#ah-ho-edit-add-product');
            var productId = $select.val();
            var productName = $select.find('option:selected').text();
            var qty = parseInt($metabox.find('#ah-ho-edit-add-qty').val()) || 1;

            if (!productId) {
                alert('<?php echo esc_js(__('Please search and select a product.', 'ah-ho-custom')); ?>');
                return;
            }

            // Check if already in new items list
            var exists = false;
            newItems.forEach(function(item) {
                if (item.product_id == productId) {
                    item.qty += qty;
                    exists = true;
                }
            });

            if (!exists) {
                newItems.push({ product_id: productId, name: productName, qty: qty });
            }

            // Add visual row to table
            var $existingNewRow = $metabox.find('.ah-ho-new-item-row[data-product-id="' + productId + '"]');
            if ($existingNewRow.length) {
                var newQty = 0;
                newItems.forEach(function(item) { if (item.product_id == productId) newQty = item.qty; });
                $existingNewRow.find('.ah-ho-new-item-qty').text(newQty);
            } else {
                var row = '<tr class="ah-ho-new-item-row" data-product-id="' + productId + '">' +
                    '<td>' + $('<span>').text(productName).html() + ' <small style="color:#155724;">(new)</small></td>' +
                    '<td style="text-align:center;color:#666;">&mdash;</td>' +
                    '<td style="text-align:center;"><span class="ah-ho-new-item-qty">' + qty + '</span></td>' +
                    '<td style="text-align:right;">&mdash;</td>' +
                    '<td style="text-align:center;"><button type="button" class="ah-ho-remove-new-item button-link" data-product-id="' + productId + '" style="color:#b32d2e;font-size:16px;font-weight:bold;cursor:pointer;">&times;</button></td>' +
                    '</tr>';
                $metabox.find('.ah-ho-edit-items-table tbody').append(row);
            }

            // Reset search
            $select.val(null).trigger('change');
            $metabox.find('#ah-ho-edit-add-qty').val(1);
        });

        // Remove newly added item
        $metabox.on('click', '.ah-ho-remove-new-item', function(e) {
            e.preventDefault();
            var productId = $(this).data('product-id');
            newItems = newItems.filter(function(item) { return item.product_id != productId; });
            $(this).closest('tr').remove();
        });

        // Save Changes
        $metabox.on('click', '#ah-ho-save-edits-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var reason = $metabox.find('#ah-ho-edit-reason').val().trim();

            if (!reason) {
                alert('<?php echo esc_js(__('Please provide a reason for the edit.', 'ah-ho-custom')); ?>');
                $metabox.find('#ah-ho-edit-reason').focus();
                return;
            }

            // Collect quantity changes
            var qtyChanges = [];
            $metabox.find('.ah-ho-edit-qty').each(function() {
                var itemId = $(this).data('item-id');
                var $row = $(this).closest('tr');
                if ($row.hasClass('ah-ho-row-removed')) return; // skip removed items
                var original = parseInt($(this).data('original-qty'));
                var current = parseInt($(this).val()) || 0;
                if (current !== original) {
                    qtyChanges.push({ item_id: itemId, qty: current });
                }
            });

            if (qtyChanges.length === 0 && newItems.length === 0 && removedItems.length === 0) {
                alert('<?php echo esc_js(__('No changes to save.', 'ah-ho-custom')); ?>');
                return;
            }

            var summary = [];
            if (qtyChanges.length > 0) summary.push(qtyChanges.length + ' qty change(s)');
            if (newItems.length > 0) summary.push(newItems.length + ' item(s) added');
            if (removedItems.length > 0) summary.push(removedItems.length + ' item(s) removed');

            if (!confirm('<?php echo esc_js(__('Save these changes?', 'ah-ho-custom')); ?>\n\n' + summary.join(', ') + '\n<?php echo esc_js(__('Reason:', 'ah-ho-custom')); ?> ' + reason)) {
                return;
            }

            $btn.prop('disabled', true);
            $metabox.find('#ah-ho-edit-saving').show();

            $.post(ajaxurl, {
                action: 'ah_ho_save_order_edits',
                nonce: nonce,
                order_id: orderId,
                reason: reason,
                qty_changes: JSON.stringify(qtyChanges),
                new_items: JSON.stringify(newItems),
                removed_items: JSON.stringify(removedItems)
            }, function(response) {
                if (response.success) {
                    // Refresh tabs
                    $metabox.find('#ah-ho-tab-edits').html(response.data.edits_html);
                    $metabox.find('#ah-ho-tab-deliveries').html(response.data.deliveries_html);

                    // Reset state
                    removedItems = [];
                    newItems = [];

                    // Update badge
                    if (typeof ah_ho_update_status_badge === 'function') {
                        ah_ho_update_status_badge(response.data.delivery_status);
                    }

                    // Re-init product search
                    setTimeout(initProductSearch, 200);

                    // Reload page to update order items/totals display
                    location.reload();
                } else {
                    alert(response.data || '<?php echo esc_js(__('An error occurred.', 'ah-ho-custom')); ?>');
                }
                $btn.prop('disabled', false);
                $metabox.find('#ah-ho-edit-saving').hide();
            }).fail(function() {
                alert('<?php echo esc_js(__('Request failed. Please try again.', 'ah-ho-custom')); ?>');
                $btn.prop('disabled', false);
                $metabox.find('#ah-ho-edit-saving').hide();
            });
        });
    });
    </script>
    <?php
}
