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
 * Auto-assign salesperson when they create a new order
 */
add_action('woocommerce_new_order', 'ah_ho_assign_salesperson_to_new_order', 10, 2);

function ah_ho_assign_salesperson_to_new_order($order_id, $order) {
    $current_user = wp_get_current_user();

    // Only auto-assign if current user is a salesperson
    if (!in_array('ah_ho_salesperson', $current_user->roles)) {
        return;
    }

    // Assign the current salesperson to this order
    update_post_meta($order_id, '_assigned_salesperson_id', $current_user->ID);
    update_post_meta($order_id, '_commission_status', 'pending');

    // Add order note
    $order->add_order_note(
        sprintf(__('Order assigned to salesperson: %s', 'ah-ho-custom'), $current_user->display_name)
    );
}

/**
 * Calculate commission when order is completed
 */
add_action('woocommerce_order_status_completed', 'ah_ho_calculate_order_commission', 10, 1);

function ah_ho_calculate_order_commission($order_id) {
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    $salesperson_id = get_post_meta($order_id, '_assigned_salesperson_id', true);

    // Not a salesperson order
    if (!$salesperson_id) {
        return;
    }

    // Commission already calculated
    if (get_post_meta($order_id, '_commission_amount', true)) {
        return;
    }

    // Get commission rate
    $enable_custom_rates = get_option('ah_ho_enable_custom_rates', true);
    $rate = null;

    if ($enable_custom_rates) {
        // Try to get salesperson-specific rate
        $rate = get_user_meta($salesperson_id, '_commission_rate', true);
    }

    // Fallback to default rate
    if (empty($rate)) {
        $rate = get_option('ah_ho_default_commission_rate', 10);
    }

    // Calculate commission
    $order_total = $order->get_total();
    $commission = $order_total * ($rate / 100);

    // Store commission data
    update_post_meta($order_id, '_commission_rate', $rate);
    update_post_meta($order_id, '_commission_amount', $commission);

    // Determine commission status based on approval mode
    $approval_mode = get_option('ah_ho_commission_approval_mode', 'auto');
    $status = ($approval_mode === 'auto') ? 'approved' : 'pending';

    update_post_meta($order_id, '_commission_status', $status);

    // Add order note
    $order->add_order_note(
        sprintf(
            __('Commission calculated: $%s (%s%%) - Status: %s', 'ah-ho-custom'),
            number_format($commission, 2),
            $rate,
            $status
        )
    );

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
 * Handle full refund - reset commission
 */
add_action('woocommerce_order_status_refunded', 'ah_ho_handle_commission_refund', 10, 1);

function ah_ho_handle_commission_refund($order_id) {
    $commission_amount = get_post_meta($order_id, '_commission_amount', true);

    if (!$commission_amount) {
        return; // No commission to refund
    }

    $order = wc_get_order($order_id);

    // Set commission to 0 and mark as refunded
    update_post_meta($order_id, '_commission_amount', 0);
    update_post_meta($order_id, '_commission_status', 'refunded');

    // Add order note
    $order->add_order_note(
        sprintf(__('Commission refunded: $%s set to $0', 'ah-ho-custom'), number_format($commission_amount, 2))
    );
}

/**
 * Handle order cancellation
 */
add_action('woocommerce_order_status_cancelled', 'ah_ho_handle_commission_cancellation', 10, 1);

function ah_ho_handle_commission_cancellation($order_id) {
    $commission_status = get_post_meta($order_id, '_commission_status', true);

    // Only cancel if commission hasn't been paid yet
    if ($commission_status === 'paid') {
        // Flag for manual review - can't auto-clawback paid commission
        $order = wc_get_order($order_id);
        $order->add_order_note(
            __('⚠️ Order cancelled but commission was already paid. Manual clawback required.', 'ah-ho-custom')
        );
        update_post_meta($order_id, '_commission_needs_clawback', true);
        return;
    }

    // Cancel commission
    update_post_meta($order_id, '_commission_status', 'cancelled');

    $order = wc_get_order($order_id);
    $order->add_order_note(__('Commission cancelled due to order cancellation', 'ah-ho-custom'));
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
 * Render salesperson assignment meta box
 */
function ah_ho_render_salesperson_meta_box($post) {
    $order = wc_get_order($post->ID);
    $assigned_salesperson_id = get_post_meta($post->ID, '_assigned_salesperson_id', true);
    $commission_amount = get_post_meta($post->ID, '_commission_amount', true);
    $commission_rate = get_post_meta($post->ID, '_commission_rate', true);
    $commission_status = get_post_meta($post->ID, '_commission_status', true);

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

    // Save assigned salesperson
    if (isset($_POST['assigned_salesperson'])) {
        $old_salesperson = get_post_meta($post_id, '_assigned_salesperson_id', true);
        $new_salesperson = absint($_POST['assigned_salesperson']);

        if ($old_salesperson != $new_salesperson) {
            if ($new_salesperson) {
                update_post_meta($post_id, '_assigned_salesperson_id', $new_salesperson);
                $salesperson = get_userdata($new_salesperson);
                $order->add_order_note(
                    sprintf(__('Order assigned to salesperson: %s', 'ah-ho-custom'), $salesperson->display_name)
                );
            } else {
                delete_post_meta($post_id, '_assigned_salesperson_id');
                $order->add_order_note(__('Salesperson assignment removed', 'ah-ho-custom'));
            }
        }
    }

    // Mark commission as paid
    if (isset($_POST['mark_commission_paid']) && $_POST['mark_commission_paid'] === '1') {
        update_post_meta($post_id, '_commission_status', 'paid');
        update_post_meta($post_id, '_commission_paid_date', current_time('Y-m-d'));
        $order->add_order_note(__('Commission marked as paid', 'ah-ho-custom'));
    }

    // Approve commission (manual approval mode)
    if (isset($_POST['approve_commission']) && $_POST['approve_commission'] === '1') {
        update_post_meta($post_id, '_commission_status', 'approved');
        $order->add_order_note(__('Commission manually approved', 'ah-ho-custom'));

        // Send notification if enabled
        if (get_option('ah_ho_notify_on_approval', true)) {
            $salesperson_id = get_post_meta($post_id, '_assigned_salesperson_id', true);
            $commission = get_post_meta($post_id, '_commission_amount', true);
            if ($salesperson_id && $commission) {
                ah_ho_send_commission_notification($post_id, $salesperson_id, $commission);
            }
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
 * Display commission data in orders list
 */
add_action('manage_shop_order_posts_custom_column', 'ah_ho_display_commission_column', 10, 2);

function ah_ho_display_commission_column($column, $post_id) {
    if ($column === 'salesperson_commission') {
        $commission = get_post_meta($post_id, '_commission_amount', true);
        $status = get_post_meta($post_id, '_commission_status', true);

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
