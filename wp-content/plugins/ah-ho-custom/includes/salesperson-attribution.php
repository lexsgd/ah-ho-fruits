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
 * Auto-assign salesperson when they create a new order (HPOS Compatible)
 */
add_action('woocommerce_new_order', 'ah_ho_assign_salesperson_to_new_order', 10, 2);

function ah_ho_assign_salesperson_to_new_order($order_id, $order) {
    $current_user = wp_get_current_user();

    // Only auto-assign if current user is a salesperson
    if (!in_array('ah_ho_salesperson', $current_user->roles)) {
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

    // Get commission rate
    $enable_custom_rates = get_option('ah_ho_enable_custom_rates', true);
    $rate = null;

    if ($enable_custom_rates) {
        $rate = get_user_meta($salesperson_id, '_commission_rate', true);
    }

    if (empty($rate)) {
        $rate = get_option('ah_ho_default_commission_rate', 10);
    }

    // Calculate commission based on order total
    $order_total = $order->get_total();
    $commission = $order_total * ($rate / 100);

    // Store commission data with 'pending' status
    $order->update_meta_data('_commission_rate', $rate);
    $order->update_meta_data('_commission_amount', $commission);
    $order->update_meta_data('_commission_status', 'pending');

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

    // Get rate
    $rate = $order->get_meta('_commission_rate', true);
    if (!$rate) {
        $rate = get_option('ah_ho_default_commission_rate', 10);
    }

    // Recalculate commission
    $order_total = $order->get_total();
    $commission = $order_total * ($rate / 100);

    $order->update_meta_data('_commission_amount', $commission);
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

    // Get commission rate
    $enable_custom_rates = get_option('ah_ho_enable_custom_rates', true);
    $rate = null;

    if ($enable_custom_rates) {
        // Try to get salesperson-specific rate (user meta is still post-based)
        $rate = get_user_meta($salesperson_id, '_commission_rate', true);
    }

    // Fallback to default rate
    if (empty($rate)) {
        $rate = get_option('ah_ho_default_commission_rate', 10);
    }

    // Calculate commission
    $order_total = $order->get_total();
    $commission = $order_total * ($rate / 100);

    // Determine commission status based on approval mode
    $approval_mode = get_option('ah_ho_commission_approval_mode', 'auto');
    $status = ($approval_mode === 'auto') ? 'approved' : 'pending';

    // Store commission data (HPOS compatible)
    $order->update_meta_data('_commission_rate', $rate);
    $order->update_meta_data('_commission_amount', $commission);
    $order->update_meta_data('_commission_status', $status);

    // Add order note
    $order->add_order_note(
        sprintf(
            __('Commission calculated: $%s (%s%%) - Status: %s', 'ah-ho-custom'),
            number_format($commission, 2),
            $rate,
            $status
        )
    );

    // Save all meta changes
    $order->save();

    // Send notification if auto-approved and notifications enabled
    if ($status === 'approved' && get_option('ah_ho_notify_on_approval', true)) {
        ah_ho_send_commission_notification($order_id, $salesperson_id, $commission);
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

    // Prepare email
    $subject = sprintf(
        __('[Ah Ho Fruits] Commission Approved - Order #%s', 'ah-ho-custom'),
        $order->get_order_number()
    );

    $message = sprintf(
        __("Commission has been approved:\n\nSalesperson: %s\nOrder: #%s\nCommission Amount: $%s\nOrder Total: $%s\n\nView Order: %s", 'ah-ho-custom'),
        $salesperson->display_name,
        $order->get_order_number(),
        number_format($commission, 2),
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

    // Get all salespersons
    $salespersons = get_users(array('role' => 'ah_ho_salesperson'));

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

        <?php if ($commission_amount) : ?>
            <hr>
            <h4><?php _e('Commission Details', 'ah-ho-custom'); ?></h4>
            <p>
                <strong><?php _e('Rate:', 'ah-ho-custom'); ?></strong> <?php echo esc_html($commission_rate); ?>%<br>
                <strong><?php _e('Amount:', 'ah-ho-custom'); ?></strong> $<?php echo number_format($commission_amount, 2); ?><br>
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
    $terms_labels = array(
        'cod' => array('label' => 'COD', 'color' => '#2ea44f'),
        'credit_7' => array('label' => 'Credit 7 Days', 'color' => '#dba617'),
        'credit_14' => array('label' => 'Credit 14 Days', 'color' => '#f56e28'),
        'credit_30' => array('label' => 'Credit 30 Days', 'color' => '#b32d2e'),
    );
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

    <?php if ($customer_id) : ?>
    <p class="form-field form-field-wide ah-ho-payment-terms-field">
        <label>
            <strong><?php _e('Customer Payment Terms:', 'ah-ho-custom'); ?></strong>
        </label>
        <?php if ($payment_terms && isset($terms_labels[$payment_terms])) : ?>
            <span style="display: inline-block; background: <?php echo esc_attr($terms_labels[$payment_terms]['color']); ?>; color: #fff; padding: 4px 12px; border-radius: 4px; font-weight: 500;">
                <?php echo esc_html($terms_labels[$payment_terms]['label']); ?>
            </span>
        <?php else : ?>
            <span style="color: #b32d2e; font-style: italic;">
                <?php _e('Not set - Please update customer profile', 'ah-ho-custom'); ?>
            </span>
            <?php if ($customer_id) : ?>
                <a href="<?php echo esc_url(get_edit_user_link($customer_id)); ?>" class="button button-small" style="margin-left: 8px;">
                    <?php _e('Set Payment Terms', 'ah-ho-custom'); ?>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </p>
    <?php endif; ?>
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
