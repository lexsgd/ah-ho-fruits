<?php
/**
 * Salesperson Order Attribution & Commission Calculation
 *
 * Handles order assignment to salespersons and automatic commission tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ========================================
 * COMMISSION CALCULATION HELPERS
 * ========================================
 */

/**
 * Calculate dual-model commission for an order
 *
 * Returns array with percentage_amount, carton_amount, total,
 * percentage_rate, per_carton_rate, total_quantity
 *
 * @param WC_Order $order
 * @param int      $salesperson_id
 * @return array
 */
function ah_ho_calculate_commission_components($order, $salesperson_id) {
    $enable_custom_rates = get_option('ah_ho_enable_custom_rates', true);

    // Get percentage rate
    $percentage_rate = null;
    if ($enable_custom_rates) {
        $percentage_rate = get_user_meta($salesperson_id, '_commission_rate', true);
    }
    if (empty($percentage_rate) && $percentage_rate !== '0' && $percentage_rate !== 0) {
        $percentage_rate = get_option('ah_ho_default_commission_rate', 10);
    }
    $percentage_rate = floatval($percentage_rate);

    // Get per-carton rate
    $per_carton_rate = null;
    if ($enable_custom_rates) {
        $per_carton_rate = get_user_meta($salesperson_id, '_commission_per_carton_rate', true);
    }
    if (empty($per_carton_rate) && $per_carton_rate !== '0' && $per_carton_rate !== 0) {
        $per_carton_rate = get_option('ah_ho_default_per_carton_rate', 0);
    }
    $per_carton_rate = floatval($per_carton_rate);

    // Calculate percentage commission
    $order_total = $order->get_total();
    $percentage_amount = $order_total * ($percentage_rate / 100);

    // Calculate per-carton commission (sum all line item quantities)
    $total_quantity = 0;
    foreach ($order->get_items() as $item) {
        $total_quantity += $item->get_quantity();
    }
    $carton_amount = $per_carton_rate * $total_quantity;

    // Total commission
    $total = $percentage_amount + $carton_amount;

    return array(
        'percentage_rate'    => $percentage_rate,
        'per_carton_rate'    => $per_carton_rate,
        'percentage_amount'  => round($percentage_amount, 2),
        'carton_amount'      => round($carton_amount, 2),
        'total'              => round($total, 2),
        'total_quantity'     => $total_quantity,
    );
}

/**
 * Store commission component data on an order
 *
 * @param WC_Order $order
 * @param array    $components  Output of ah_ho_calculate_commission_components()
 * @param string   $status      Commission status to set
 */
function ah_ho_store_commission_meta($order, $components, $status = 'pending') {
    $order->update_meta_data('_commission_rate', $components['percentage_rate']);
    $order->update_meta_data('_commission_per_carton_rate', $components['per_carton_rate']);
    $order->update_meta_data('_commission_percentage_amount', $components['percentage_amount']);
    $order->update_meta_data('_commission_carton_amount', $components['carton_amount']);
    $order->update_meta_data('_commission_amount', $components['total']);
    $order->update_meta_data('_commission_total_quantity', $components['total_quantity']);
    $order->update_meta_data('_commission_status', $status);
}

/**
 * ========================================
 * AJAX: Get Customer Payment Terms
 * ========================================
 * Returns payment terms for a customer (used by order page JS)
 */
add_action('wp_ajax_ah_ho_get_customer_payment_terms', 'ah_ho_ajax_get_customer_payment_terms');

function ah_ho_ajax_get_customer_payment_terms() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ah_ho_payment_terms_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    // Check permissions
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error('Permission denied');
    }

    $customer_id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;

    if (!$customer_id) {
        wp_send_json_success(array(
            'html' => '<span style="color: #666; font-style: italic;">No customer selected</span>',
            'terms' => '',
        ));
    }

    $payment_terms = get_user_meta($customer_id, '_payment_terms', true);
    $terms_labels = ah_ho_get_payment_terms();

    if ($payment_terms && isset($terms_labels[$payment_terms])) {
        $html = sprintf(
            '<span style="display: inline-block; background: %s; color: #fff; padding: 4px 12px; border-radius: 4px; font-weight: 500;">%s</span>',
            esc_attr($terms_labels[$payment_terms]['color']),
            esc_html($terms_labels[$payment_terms]['label'])
        );
    } else {
        $html = sprintf(
            '<span style="color: #b32d2e; font-style: italic;">Not set</span> <a href="%s" class="button button-small" style="margin-left: 8px;">Set Payment Terms</a>',
            esc_url(get_edit_user_link($customer_id))
        );
    }

    wp_send_json_success(array(
        'html' => $html,
        'terms' => $payment_terms,
    ));
}

/**
 * Enqueue JavaScript for order page payment terms
 */
add_action('admin_footer', 'ah_ho_payment_terms_js');

function ah_ho_payment_terms_js() {
    global $pagenow;

    // Only on order edit/create pages
    if ($pagenow !== 'admin.php' || !isset($_GET['page']) || $_GET['page'] !== 'wc-orders') {
        return;
    }

    $nonce = wp_create_nonce('ah_ho_payment_terms_nonce');
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Watch for customer selection changes
        // WooCommerce uses Select2 for the customer dropdown
        $(document).on('change', '#customer_user, select.wc-customer-search', function() {
            var customerId = $(this).val();
            updatePaymentTerms(customerId);
        });

        // Also watch for WooCommerce's customer selection via Select2
        $(document).on('select2:select', '#customer_user, .wc-customer-search', function(e) {
            var customerId = e.params.data.id;
            updatePaymentTerms(customerId);
        });

        // Handle customer clear
        $(document).on('select2:unselect select2:clear', '#customer_user, .wc-customer-search', function() {
            updatePaymentTerms(0);
        });

        function updatePaymentTerms(customerId) {
            var $display = $('#ah-ho-payment-terms-display');

            if (!$display.length) {
                return;
            }

            // Show loading
            $display.html('<span style="color: #666;">Loading...</span>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ah_ho_get_customer_payment_terms',
                    customer_id: customerId,
                    nonce: '<?php echo $nonce; ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $display.html(response.data.html);
                    } else {
                        $display.html('<span style="color: #b32d2e;">Error loading payment terms</span>');
                    }
                },
                error: function() {
                    $display.html('<span style="color: #b32d2e;">Error loading payment terms</span>');
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Auto-assign salesperson when they create a new order (HPOS Compatible)
 */
add_action('woocommerce_new_order', 'ah_ho_assign_salesperson_to_new_order', 10, 2);

function ah_ho_assign_salesperson_to_new_order($order_id, $order) {
    $current_user = wp_get_current_user();

    // Only auto-assign if current user is a salesperson or storeman
    $roles = (array) $current_user->roles;
    if (!in_array('ah_ho_salesperson', $roles) && !in_array('ah_ho_storeman', $roles)) {
        return;
    }

    // Ensure we have the order object (HPOS compatible)
    if (!$order instanceof WC_Order) {
        $order = wc_get_order($order_id);
    }

    if (!$order) {
        return;
    }

    // Assign the current salesperson to this order (HPOS compatible - use order meta methods)
    $order->update_meta_data('_assigned_salesperson_id', $current_user->ID);
    $order->update_meta_data('_commission_status', 'pending');

    // Set status to Processing - B2B for salesperson-created orders
    $order->set_status('processing-b2b', sprintf(
        __('B2B order created by salesperson: %s', 'ah-ho-custom'),
        $current_user->display_name
    ));

    // Save all changes including meta data
    $order->save();

    // Calculate commission immediately (as pending) so it shows in dashboard
    ah_ho_calculate_pending_commission($order_id);
}

/**
 * Calculate pending commission when order is created
 * This allows salespersons to see projected earnings before order completion
 */
function ah_ho_calculate_pending_commission($order_id) {
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    $salesperson_id = $order->get_meta('_assigned_salesperson_id', true);

    if (!$salesperson_id) {
        return;
    }

    // Don't recalculate if already calculated
    if ($order->get_meta('_commission_amount', true)) {
        return;
    }

    $components = ah_ho_calculate_commission_components($order, $salesperson_id);
    ah_ho_store_commission_meta($order, $components, 'pending');
    $order->save();
}

/**
 * Recalculate commission when order total changes (items added/removed)
 */
add_action('woocommerce_order_status_changed', 'ah_ho_recalculate_commission_on_change', 5, 4);

function ah_ho_recalculate_commission_on_change($order_id, $old_status, $new_status, $order) {
    $salesperson_id = $order->get_meta('_assigned_salesperson_id', true);

    if (!$salesperson_id) {
        return;
    }

    $commission_status = $order->get_meta('_commission_status', true);

    // Only recalculate if commission is still pending
    if ($commission_status !== 'pending') {
        return;
    }

    $components = ah_ho_calculate_commission_components($order, $salesperson_id);
    ah_ho_store_commission_meta($order, $components, 'pending');
    $order->save();
}

/**
 * Calculate commission when order is completed (HPOS Compatible)
 */
add_action('woocommerce_order_status_completed', 'ah_ho_calculate_order_commission', 10, 1);

function ah_ho_calculate_order_commission($order_id) {
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    // HPOS compatible - use order meta methods
    $salesperson_id = $order->get_meta('_assigned_salesperson_id', true);

    // Not a salesperson order
    if (!$salesperson_id) {
        return;
    }

    // Commission already calculated
    if ($order->get_meta('_commission_amount', true)) {
        return;
    }

    $components = ah_ho_calculate_commission_components($order, $salesperson_id);

    // Determine commission status based on approval mode
    $approval_mode = get_option('ah_ho_commission_approval_mode', 'auto');
    $status = ($approval_mode === 'auto') ? 'approved' : 'pending';

    ah_ho_store_commission_meta($order, $components, $status);

    // Build order note with breakdown
    $note_parts = array();
    if ($components['percentage_rate'] > 0) {
        $note_parts[] = sprintf('$%s (%s%% of order)',
            number_format($components['percentage_amount'], 2),
            $components['percentage_rate']);
    }
    if ($components['per_carton_rate'] > 0) {
        $note_parts[] = sprintf('$%s ($%s x %d cartons)',
            number_format($components['carton_amount'], 2),
            number_format($components['per_carton_rate'], 2),
            $components['total_quantity']);
    }

    $order->add_order_note(
        sprintf(
            __('Commission calculated: $%s [%s] - Status: %s', 'ah-ho-custom'),
            number_format($components['total'], 2),
            implode(' + ', $note_parts),
            $status
        )
    );

    // Save all meta changes
    $order->save();

    // Send notification if auto-approved and notifications enabled
    if ($status === 'approved' && get_option('ah_ho_notify_on_approval', true)) {
        ah_ho_send_commission_notification($order_id, $salesperson_id, $components['total']);
    }
}

/**
 * Send commission notification email
 */
function ah_ho_send_commission_notification($order_id, $salesperson_id, $commission) {
    $order = wc_get_order($order_id);
    $salesperson = get_userdata($salesperson_id);

    if (!$order || !$salesperson) {
        return;
    }

    // Get notification recipients from settings
    $recipients = get_option('ah_ho_commission_notification_emails', get_option('admin_email'));

    // Get commission breakdown from order meta
    $percentage_amount = $order->get_meta('_commission_percentage_amount', true);
    $carton_amount_meta = $order->get_meta('_commission_carton_amount', true);
    $per_carton_rate = $order->get_meta('_commission_per_carton_rate', true);
    $percentage_rate = $order->get_meta('_commission_rate', true);
    $total_quantity = $order->get_meta('_commission_total_quantity', true);

    $breakdown = '';
    if ($percentage_amount && floatval($percentage_amount) > 0) {
        $breakdown .= sprintf("\nPercentage: $%s (%s%% of order)", number_format(floatval($percentage_amount), 2), $percentage_rate);
    }
    if ($carton_amount_meta && floatval($carton_amount_meta) > 0) {
        $breakdown .= sprintf("\nPer-Carton: $%s ($%s x %d cartons)", number_format(floatval($carton_amount_meta), 2), number_format(floatval($per_carton_rate), 2), intval($total_quantity));
    }

    // Prepare email
    $subject = sprintf(
        __('[Ah Ho Fruits] Commission Approved - Order #%s', 'ah-ho-custom'),
        $order->get_order_number()
    );

    $message = sprintf(
        __("Commission has been approved:\n\nSalesperson: %s\nOrder: #%s\nTotal Commission: $%s%s\nOrder Total: $%s\n\nView Order: %s", 'ah-ho-custom'),
        $salesperson->display_name,
        $order->get_order_number(),
        number_format($commission, 2),
        $breakdown,
        $order->get_total(),
        admin_url('post.php?post=' . $order_id . '&action=edit')
    );

    // Send email
    wp_mail($recipients, $subject, $message);
}

/**
 * Handle full refund - reset commission (HPOS Compatible)
 */
add_action('woocommerce_order_status_refunded', 'ah_ho_handle_commission_refund', 10, 1);

function ah_ho_handle_commission_refund($order_id) {
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    $commission_amount = $order->get_meta('_commission_amount', true);

    if (!$commission_amount) {
        return; // No commission to refund
    }

    // Set commission to 0 and mark as refunded (HPOS compatible)
    $order->update_meta_data('_commission_amount', 0);
    $order->update_meta_data('_commission_status', 'refunded');

    // Add order note
    $order->add_order_note(
        sprintf(__('Commission refunded: $%s set to $0', 'ah-ho-custom'), number_format($commission_amount, 2))
    );

    $order->save();
}

/**
 * Handle order cancellation (HPOS Compatible)
 */
add_action('woocommerce_order_status_cancelled', 'ah_ho_handle_commission_cancellation', 10, 1);

function ah_ho_handle_commission_cancellation($order_id) {
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    $commission_status = $order->get_meta('_commission_status', true);

    // Only cancel if commission hasn't been paid yet
    if ($commission_status === 'paid') {
        // Flag for manual review - can't auto-clawback paid commission
        $order->add_order_note(
            __('⚠️ Order cancelled but commission was already paid. Manual clawback required.', 'ah-ho-custom')
        );
        $order->update_meta_data('_commission_needs_clawback', true);
        $order->save();
        return;
    }

    // Cancel commission (HPOS compatible)
    $order->update_meta_data('_commission_status', 'cancelled');
    $order->add_order_note(__('Commission cancelled due to order cancellation', 'ah-ho-custom'));
    $order->save();
}

/**
 * Add meta box to order edit page for manual salesperson assignment
 */
add_action('add_meta_boxes', 'ah_ho_add_salesperson_meta_box');

function ah_ho_add_salesperson_meta_box() {
    add_meta_box(
        'ah_ho_salesperson_assignment',
        __('Salesperson Assignment', 'ah-ho-custom'),
        'ah_ho_render_salesperson_meta_box',
        'shop_order',
        'side',
        'default'
    );
}

/**
 * Render salesperson assignment meta box (HPOS Compatible)
 */
function ah_ho_render_salesperson_meta_box($post) {
    $order = wc_get_order($post->ID);

    if (!$order) {
        echo '<p>' . __('Unable to load order data.', 'ah-ho-custom') . '</p>';
        return;
    }

    // HPOS compatible - use order meta methods
    $assigned_salesperson_id = $order->get_meta('_assigned_salesperson_id', true);
    $commission_amount = $order->get_meta('_commission_amount', true);
    $commission_rate = $order->get_meta('_commission_rate', true);
    $commission_status = $order->get_meta('_commission_status', true);

    // Get all salespersons and storemen
    $salespersons = get_users(array('role__in' => array('ah_ho_salesperson', 'ah_ho_storeman'), 'orderby' => 'display_name'));

    wp_nonce_field('ah_ho_save_salesperson', 'ah_ho_salesperson_nonce');
    ?>
    <div class="ah-ho-salesperson-assignment">
        <p>
            <label for="assigned_salesperson"><?php _e('Assign to Salesperson:', 'ah-ho-custom'); ?></label>
            <select name="assigned_salesperson" id="assigned_salesperson" style="width: 100%;">
                <option value=""><?php _e('-- None --', 'ah-ho-custom'); ?></option>
                <?php foreach ($salespersons as $salesperson) : ?>
                    <option value="<?php echo esc_attr($salesperson->ID); ?>" <?php selected($assigned_salesperson_id, $salesperson->ID); ?>>
                        <?php echo esc_html($salesperson->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <?php if ($commission_amount) :
            $per_carton_rate = $order->get_meta('_commission_per_carton_rate', true);
            $percentage_amount = $order->get_meta('_commission_percentage_amount', true);
            $carton_amount = $order->get_meta('_commission_carton_amount', true);
            $total_quantity = $order->get_meta('_commission_total_quantity', true);
            $has_breakdown = ($percentage_amount || $carton_amount);
        ?>
            <hr>
            <h4><?php _e('Commission Details', 'ah-ho-custom'); ?></h4>

            <?php if ($has_breakdown) : ?>
                <?php if ($percentage_amount && floatval($percentage_amount) > 0) : ?>
                <p>
                    <strong><?php _e('Percentage:', 'ah-ho-custom'); ?></strong>
                    $<?php echo number_format($percentage_amount, 2); ?>
                    (<?php echo esc_html($commission_rate); ?>% of order)
                </p>
                <?php endif; ?>

                <?php if ($carton_amount && floatval($carton_amount) > 0) : ?>
                <p>
                    <strong><?php _e('Per-Carton:', 'ah-ho-custom'); ?></strong>
                    $<?php echo number_format($carton_amount, 2); ?>
                    ($<?php echo number_format($per_carton_rate, 2); ?> x <?php echo intval($total_quantity); ?> cartons)
                </p>
                <?php endif; ?>

                <p>
                    <strong><?php _e('Total Commission:', 'ah-ho-custom'); ?></strong>
                    $<?php echo number_format($commission_amount, 2); ?>
                </p>
            <?php else : ?>
                <p>
                    <strong><?php _e('Rate:', 'ah-ho-custom'); ?></strong> <?php echo esc_html($commission_rate); ?>%<br>
                    <strong><?php _e('Amount:', 'ah-ho-custom'); ?></strong> $<?php echo number_format($commission_amount, 2); ?>
                </p>
            <?php endif; ?>

            <p>
                <strong><?php _e('Status:', 'ah-ho-custom'); ?></strong>
                <span class="commission-status-<?php echo esc_attr($commission_status); ?>">
                    <?php echo esc_html(ucfirst($commission_status)); ?>
                </span>
            </p>

            <?php if ($commission_status === 'approved' && current_user_can('manage_options')) : ?>
                <p>
                    <label>
                        <input type="checkbox" name="mark_commission_paid" value="1">
                        <?php _e('Mark commission as paid', 'ah-ho-custom'); ?>
                    </label>
                </p>
            <?php endif; ?>

            <?php if ($commission_status === 'pending' && current_user_can('manage_options') && get_option('ah_ho_commission_approval_mode', 'auto') === 'manual') : ?>
                <p>
                    <label>
                        <input type="checkbox" name="approve_commission" value="1">
                        <?php _e('Approve commission', 'ah-ho-custom'); ?>
                    </label>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Save salesperson assignment and commission status
 */
add_action('save_post_shop_order', 'ah_ho_save_salesperson_assignment', 10, 2);

function ah_ho_save_salesperson_assignment($post_id, $post) {
    // Verify nonce
    if (!isset($_POST['ah_ho_salesperson_nonce']) || !wp_verify_nonce($_POST['ah_ho_salesperson_nonce'], 'ah_ho_save_salesperson')) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Prevent autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $order = wc_get_order($post_id);
    if (!$order) {
        return;
    }

    // Save assigned salesperson (HPOS compatible)
    if (isset($_POST['assigned_salesperson'])) {
        $old_salesperson = $order->get_meta('_assigned_salesperson_id', true);
        $new_salesperson = absint($_POST['assigned_salesperson']);

        if ($old_salesperson != $new_salesperson) {
            if ($new_salesperson) {
                $order->update_meta_data('_assigned_salesperson_id', $new_salesperson);
                $salesperson = get_userdata($new_salesperson);
                $order->add_order_note(
                    sprintf(__('Order assigned to salesperson: %s', 'ah-ho-custom'), $salesperson->display_name)
                );
            } else {
                $order->delete_meta_data('_assigned_salesperson_id');
                $order->add_order_note(__('Salesperson assignment removed', 'ah-ho-custom'));
            }
            $order->save();
        }
    }

    // Mark commission as paid (HPOS compatible)
    if (isset($_POST['mark_commission_paid']) && $_POST['mark_commission_paid'] === '1') {
        $order->update_meta_data('_commission_status', 'paid');
        $order->update_meta_data('_commission_paid_date', current_time('Y-m-d'));
        $order->add_order_note(__('Commission marked as paid', 'ah-ho-custom'));
        $order->save();
    }

    // Approve commission (manual approval mode) - HPOS compatible
    if (isset($_POST['approve_commission']) && $_POST['approve_commission'] === '1') {
        $order->update_meta_data('_commission_status', 'approved');
        $order->add_order_note(__('Commission manually approved', 'ah-ho-custom'));
        $order->save();

        // Send notification if enabled (HPOS compatible)
        if (get_option('ah_ho_notify_on_approval', true)) {
            $salesperson_id = $order->get_meta('_assigned_salesperson_id', true);
            $commission = $order->get_meta('_commission_amount', true);
            if ($salesperson_id && $commission) {
                ah_ho_send_commission_notification($post_id, $salesperson_id, $commission);
            }
        }
    }
}

/**
 * ========================================
 * HPOS ORDER DETAILS - SALESPERSON DISPLAY
 * ========================================
 * Show assigned salesperson prominently in order details
 */

/**
 * Display salesperson info in HPOS order details section
 * Shows in the main order data area (not just sidebar)
 */
add_action('woocommerce_admin_order_data_after_order_details', 'ah_ho_display_salesperson_in_order_details');

function ah_ho_display_salesperson_in_order_details($order) {
    if (!$order) {
        return;
    }

    $salesperson_id = $order->get_meta('_assigned_salesperson_id', true);
    $salesperson = $salesperson_id ? get_userdata($salesperson_id) : null;

    // Get customer payment terms
    $customer_id = $order->get_customer_id();
    $payment_terms = $customer_id ? get_user_meta($customer_id, '_payment_terms', true) : '';
    $terms_labels = ah_ho_get_payment_terms();
    ?>
    <p class="form-field form-field-wide ah-ho-salesperson-field">
        <label for="ah_ho_salesperson_display">
            <strong><?php _e('Assigned Salesperson:', 'ah-ho-custom'); ?></strong>
        </label>
        <?php if ($salesperson) : ?>
            <span class="ah-ho-salesperson-name" style="display: inline-block; background: #8b5cf6; color: #fff; padding: 4px 12px; border-radius: 4px; font-weight: 500;">
                <?php echo esc_html($salesperson->display_name); ?>
            </span>
            <a href="<?php echo esc_url(get_edit_user_link($salesperson_id)); ?>" class="button button-small" style="margin-left: 8px;">
                <?php _e('View Profile', 'ah-ho-custom'); ?>
            </a>
        <?php else : ?>
            <span style="color: #666; font-style: italic;">
                <?php _e('Not assigned (Web/E-commerce order)', 'ah-ho-custom'); ?>
            </span>
        <?php endif; ?>
    </p>

    <p class="form-field form-field-wide ah-ho-payment-terms-field">
        <label>
            <strong><?php _e('Customer Payment Terms:', 'ah-ho-custom'); ?></strong>
        </label>
        <span id="ah-ho-payment-terms-display">
        <?php if ($payment_terms && isset($terms_labels[$payment_terms])) : ?>
            <span style="display: inline-block; background: <?php echo esc_attr($terms_labels[$payment_terms]['color']); ?>; color: #fff; padding: 4px 12px; border-radius: 4px; font-weight: 500;">
                <?php echo esc_html($terms_labels[$payment_terms]['label']); ?>
            </span>
        <?php elseif ($customer_id) : ?>
            <span style="color: #b32d2e; font-style: italic;">
                <?php _e('Not set', 'ah-ho-custom'); ?>
            </span>
            <a href="<?php echo esc_url(get_edit_user_link($customer_id)); ?>" class="button button-small" style="margin-left: 8px;">
                <?php _e('Set Payment Terms', 'ah-ho-custom'); ?>
            </a>
        <?php else : ?>
            <span style="color: #666; font-style: italic;">
                <?php _e('No customer selected', 'ah-ho-custom'); ?>
            </span>
        <?php endif; ?>
        </span>
    </p>
    <?php
}

/**
 * Add salesperson column to HPOS orders list
 */
add_filter('woocommerce_shop_order_list_table_columns', 'ah_ho_add_salesperson_column_hpos', 20);

function ah_ho_add_salesperson_column_hpos($columns) {
    $new_columns = array();
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        // Add after order_status column
        if ($key === 'order_status') {
            $new_columns['salesperson'] = __('Salesperson', 'ah-ho-custom');
        }
    }
    return $new_columns;
}

/**
 * Display salesperson in HPOS orders list column
 */
add_action('woocommerce_shop_order_list_table_custom_column', 'ah_ho_display_salesperson_column_hpos', 10, 2);

function ah_ho_display_salesperson_column_hpos($column, $order) {
    if ($column === 'salesperson') {
        $salesperson_id = $order->get_meta('_assigned_salesperson_id', true);

        if ($salesperson_id) {
            $salesperson = get_userdata($salesperson_id);
            if ($salesperson) {
                printf(
                    '<span style="background: #8b5cf6; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 12px;">%s</span>',
                    esc_html($salesperson->display_name)
                );
            } else {
                echo '<span style="color: #999;">—</span>';
            }
        } else {
            echo '<span style="color: #999;">—</span>';
        }
    }
}

/**
 * Add commission column to orders list
 */
add_filter('manage_edit-shop_order_columns', 'ah_ho_add_commission_column', 20);

function ah_ho_add_commission_column($columns) {
    // Add commission column after order total
    $new_columns = array();
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        if ($key === 'order_total') {
            $new_columns['salesperson_commission'] = __('Commission', 'ah-ho-custom');
        }
    }
    return $new_columns;
}

/**
 * Display commission data in orders list (HPOS Compatible)
 */
add_action('manage_shop_order_posts_custom_column', 'ah_ho_display_commission_column', 10, 2);

function ah_ho_display_commission_column($column, $post_id) {
    if ($column === 'salesperson_commission') {
        $order = wc_get_order($post_id);

        if (!$order) {
            echo '—';
            return;
        }

        // HPOS compatible - use order meta methods
        $commission = $order->get_meta('_commission_amount', true);
        $status = $order->get_meta('_commission_status', true);

        if ($commission) {
            printf(
                '<strong>$%s</strong><br><small>%s</small>',
                number_format($commission, 2),
                esc_html(ucfirst($status))
            );
        } else {
            echo '—';
        }
    }
}
