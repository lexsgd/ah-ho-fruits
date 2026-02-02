<?php
/**
 * Delivery Date Field
 *
 * Adds delivery date selection to checkout and order admin.
 * Works with the Delivery Date Helper in ah-ho-invoicing plugin.
 *
 * @package AhHoCustom
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================================
 * SECTION 1: CHECKOUT - Customer Delivery Date Selection
 * ============================================================================
 */

/**
 * Add delivery date field to checkout
 */
add_action('woocommerce_before_order_notes', 'ah_ho_add_delivery_date_checkout_field');

function ah_ho_add_delivery_date_checkout_field($checkout) {
    // Get minimum delivery date (tomorrow or next business day)
    $min_date = ah_ho_get_next_delivery_date();
    $max_date = date('Y-m-d', strtotime('+30 days'));

    echo '<div id="ah-ho-delivery-date-field" class="ah-ho-delivery-section">';
    echo '<h3>' . __('Delivery Date', 'ah-ho-custom') . '</h3>';

    woocommerce_form_field('delivery_date', array(
        'type'        => 'date',
        'class'       => array('form-row-wide'),
        'label'       => __('Preferred Delivery Date', 'ah-ho-custom'),
        'placeholder' => __('Select delivery date', 'ah-ho-custom'),
        'required'    => true,
        'custom_attributes' => array(
            'min' => $min_date,
            'max' => $max_date,
        ),
    ), $checkout->get_value('delivery_date'));

    // Add delivery time slot if needed
    woocommerce_form_field('delivery_time_slot', array(
        'type'        => 'select',
        'class'       => array('form-row-wide'),
        'label'       => __('Delivery Time Slot', 'ah-ho-custom'),
        'required'    => false,
        'options'     => array(
            ''          => __('Any time', 'ah-ho-custom'),
            'morning'   => __('Morning (6am - 12pm)', 'ah-ho-custom'),
            'afternoon' => __('Afternoon (12pm - 6pm)', 'ah-ho-custom'),
        ),
    ), $checkout->get_value('delivery_time_slot'));

    echo '<p class="form-row form-row-wide"><small>';
    echo __('Note: Orders placed before 2pm may be delivered same day (subject to availability). Weekend deliveries available.', 'ah-ho-custom');
    echo '</small></p>';

    echo '</div>';
}

/**
 * Get next available delivery date
 */
function ah_ho_get_next_delivery_date() {
    $now = current_time('timestamp');
    $cutoff_hour = 14; // 2pm cutoff

    // If before cutoff, same day delivery possible
    if (date('G', $now) < $cutoff_hour) {
        return date('Y-m-d', $now);
    }

    // After cutoff, next day
    return date('Y-m-d', strtotime('+1 day', $now));
}

/**
 * Validate delivery date field
 */
add_action('woocommerce_checkout_process', 'ah_ho_validate_delivery_date_field');

function ah_ho_validate_delivery_date_field() {
    if (empty($_POST['delivery_date'])) {
        wc_add_notice(__('Please select a delivery date.', 'ah-ho-custom'), 'error');
        return;
    }

    $delivery_date = sanitize_text_field($_POST['delivery_date']);
    $min_date = ah_ho_get_next_delivery_date();

    // Check if date is not in the past
    if ($delivery_date < $min_date) {
        wc_add_notice(__('Please select a valid delivery date (today or later).', 'ah-ho-custom'), 'error');
    }
}

/**
 * Save delivery date to order meta
 */
add_action('woocommerce_checkout_update_order_meta', 'ah_ho_save_delivery_date_field');

function ah_ho_save_delivery_date_field($order_id) {
    if (!empty($_POST['delivery_date'])) {
        $order = wc_get_order($order_id);
        $delivery_date = sanitize_text_field($_POST['delivery_date']);

        // Save using the standard meta key that Delivery Date Helper expects
        $order->update_meta_data('_delivery_date', $delivery_date);

        // Also save time slot if selected
        if (!empty($_POST['delivery_time_slot'])) {
            $order->update_meta_data('_delivery_time_slot', sanitize_text_field($_POST['delivery_time_slot']));
        }

        $order->save();
    }
}

/**
 * Display delivery date on order received page
 */
add_action('woocommerce_order_details_after_order_table', 'ah_ho_display_delivery_date_on_order');

function ah_ho_display_delivery_date_on_order($order) {
    $delivery_date = $order->get_meta('_delivery_date');
    $time_slot = $order->get_meta('_delivery_time_slot');

    if ($delivery_date) {
        echo '<h2>' . __('Delivery Information', 'ah-ho-custom') . '</h2>';
        echo '<table class="woocommerce-table shop_table delivery-info">';
        echo '<tr><th>' . __('Delivery Date:', 'ah-ho-custom') . '</th>';
        echo '<td><strong>' . esc_html(date('l, d F Y', strtotime($delivery_date))) . '</strong></td></tr>';

        if ($time_slot) {
            $slots = array(
                'morning'   => __('Morning (6am - 12pm)', 'ah-ho-custom'),
                'afternoon' => __('Afternoon (12pm - 6pm)', 'ah-ho-custom'),
            );
            echo '<tr><th>' . __('Time Slot:', 'ah-ho-custom') . '</th>';
            echo '<td>' . esc_html($slots[$time_slot] ?? $time_slot) . '</td></tr>';
        }
        echo '</table>';
    }
}

/**
 * Add delivery date to order emails
 */
add_action('woocommerce_email_after_order_table', 'ah_ho_add_delivery_date_to_emails', 10, 4);

function ah_ho_add_delivery_date_to_emails($order, $sent_to_admin, $plain_text, $email) {
    $delivery_date = $order->get_meta('_delivery_date');
    $time_slot = $order->get_meta('_delivery_time_slot');

    if (!$delivery_date) {
        return;
    }

    $slots = array(
        'morning'   => __('Morning (6am - 12pm)', 'ah-ho-custom'),
        'afternoon' => __('Afternoon (12pm - 6pm)', 'ah-ho-custom'),
    );

    if ($plain_text) {
        echo "\n" . __('Delivery Date:', 'ah-ho-custom') . ' ' . date('l, d F Y', strtotime($delivery_date));
        if ($time_slot) {
            echo "\n" . __('Time Slot:', 'ah-ho-custom') . ' ' . ($slots[$time_slot] ?? $time_slot);
        }
        echo "\n";
    } else {
        echo '<h2>' . __('Delivery Information', 'ah-ho-custom') . '</h2>';
        echo '<p><strong>' . __('Delivery Date:', 'ah-ho-custom') . '</strong> ';
        echo esc_html(date('l, d F Y', strtotime($delivery_date))) . '</p>';
        if ($time_slot) {
            echo '<p><strong>' . __('Time Slot:', 'ah-ho-custom') . '</strong> ';
            echo esc_html($slots[$time_slot] ?? $time_slot) . '</p>';
        }
    }
}


/**
 * ============================================================================
 * SECTION 2: ADMIN ORDER PAGE - Delivery Date Field
 * ============================================================================
 */

/**
 * Add delivery date field to order edit page
 */
add_action('woocommerce_admin_order_data_after_shipping_address', 'ah_ho_add_delivery_date_admin_field');

function ah_ho_add_delivery_date_admin_field($order) {
    $delivery_date = $order->get_meta('_delivery_date');
    $time_slot = $order->get_meta('_delivery_time_slot');
    ?>
    <div class="address" style="margin-top: 20px;">
        <h3 style="margin-bottom: 10px;">
            <?php _e('Delivery Schedule', 'ah-ho-custom'); ?>
            <a href="#" class="edit_address" style="font-size: 12px;"><?php _e('Edit', 'ah-ho-custom'); ?></a>
        </h3>

        <div class="delivery-info-view">
            <?php if ($delivery_date) : ?>
                <p>
                    <strong><?php _e('Delivery Date:', 'ah-ho-custom'); ?></strong><br>
                    <?php echo esc_html(date('l, d F Y', strtotime($delivery_date))); ?>
                </p>
                <?php if ($time_slot) :
                    $slots = array(
                        'morning'   => __('Morning (6am - 12pm)', 'ah-ho-custom'),
                        'afternoon' => __('Afternoon (12pm - 6pm)', 'ah-ho-custom'),
                    );
                    ?>
                    <p>
                        <strong><?php _e('Time Slot:', 'ah-ho-custom'); ?></strong><br>
                        <?php echo esc_html($slots[$time_slot] ?? $time_slot); ?>
                    </p>
                <?php endif; ?>
            <?php else : ?>
                <p style="color: #b32d2e; font-style: italic;">
                    <?php _e('No delivery date set', 'ah-ho-custom'); ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="edit_address" style="display: none;">
            <p class="form-field form-field-wide">
                <label for="_delivery_date"><?php _e('Delivery Date:', 'ah-ho-custom'); ?></label>
                <input type="date" id="_delivery_date" name="_delivery_date"
                       value="<?php echo esc_attr($delivery_date); ?>"
                       style="width: 100%;">
            </p>
            <p class="form-field form-field-wide">
                <label for="_delivery_time_slot"><?php _e('Time Slot:', 'ah-ho-custom'); ?></label>
                <select id="_delivery_time_slot" name="_delivery_time_slot" style="width: 100%;">
                    <option value="" <?php selected($time_slot, ''); ?>><?php _e('Any time', 'ah-ho-custom'); ?></option>
                    <option value="morning" <?php selected($time_slot, 'morning'); ?>><?php _e('Morning (6am - 12pm)', 'ah-ho-custom'); ?></option>
                    <option value="afternoon" <?php selected($time_slot, 'afternoon'); ?>><?php _e('Afternoon (12pm - 6pm)', 'ah-ho-custom'); ?></option>
                </select>
            </p>
        </div>
    </div>
    <?php
}

/**
 * Save delivery date from admin
 */
add_action('woocommerce_process_shop_order_meta', 'ah_ho_save_delivery_date_admin_field');

function ah_ho_save_delivery_date_admin_field($order_id) {
    $order = wc_get_order($order_id);

    if (isset($_POST['_delivery_date'])) {
        $delivery_date = sanitize_text_field($_POST['_delivery_date']);
        $order->update_meta_data('_delivery_date', $delivery_date);
    }

    if (isset($_POST['_delivery_time_slot'])) {
        $time_slot = sanitize_text_field($_POST['_delivery_time_slot']);
        $order->update_meta_data('_delivery_time_slot', $time_slot);
    }

    $order->save();
}


/**
 * ============================================================================
 * SECTION 3: ORDER LIST COLUMN - Show Delivery Date
 * ============================================================================
 */

/**
 * Add delivery date column to orders list
 */
add_filter('manage_edit-shop_order_columns', 'ah_ho_add_delivery_date_column', 20);
add_filter('manage_woocommerce_page_wc-orders_columns', 'ah_ho_add_delivery_date_column', 20);

function ah_ho_add_delivery_date_column($columns) {
    $new_columns = array();

    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;

        // Add delivery date column after order date
        if ($key === 'order_date') {
            $new_columns['delivery_date'] = __('Delivery Date', 'ah-ho-custom');
        }
    }

    return $new_columns;
}

/**
 * Display delivery date in column
 */
add_action('manage_shop_order_posts_custom_column', 'ah_ho_display_delivery_date_column', 20, 2);
add_action('manage_woocommerce_page_wc-orders_custom_column', 'ah_ho_display_delivery_date_column', 20, 2);

function ah_ho_display_delivery_date_column($column, $post_id) {
    if ($column !== 'delivery_date') {
        return;
    }

    $order = wc_get_order($post_id);
    if (!$order) {
        echo '&mdash;';
        return;
    }

    $delivery_date = $order->get_meta('_delivery_date');

    if ($delivery_date) {
        $formatted = date('d M Y', strtotime($delivery_date));
        $day_name = date('D', strtotime($delivery_date));

        // Color code based on date
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        if ($delivery_date === $today) {
            echo '<mark class="order-status status-processing tips" style="background: #f56e28; color: #fff;"><span>TODAY - ' . esc_html($formatted) . '</span></mark>';
        } elseif ($delivery_date === $tomorrow) {
            echo '<mark class="order-status tips" style="background: #dba617; color: #fff;"><span>TOMORROW - ' . esc_html($formatted) . '</span></mark>';
        } elseif ($delivery_date < $today) {
            echo '<mark class="order-status tips" style="background: #b32d2e; color: #fff;"><span>OVERDUE - ' . esc_html($formatted) . '</span></mark>';
        } else {
            echo '<span>' . esc_html($day_name . ', ' . $formatted) . '</span>';
        }
    } else {
        echo '<span style="color: #999;">&mdash;</span>';
    }
}

/**
 * Make delivery date column sortable
 */
add_filter('manage_edit-shop_order_sortable_columns', 'ah_ho_delivery_date_sortable_column');

function ah_ho_delivery_date_sortable_column($columns) {
    $columns['delivery_date'] = 'delivery_date';
    return $columns;
}

/**
 * Handle sorting by delivery date
 */
add_action('pre_get_posts', 'ah_ho_delivery_date_orderby');

function ah_ho_delivery_date_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('orderby') === 'delivery_date') {
        $query->set('meta_key', '_delivery_date');
        $query->set('orderby', 'meta_value');
    }
}


/**
 * ============================================================================
 * SECTION 4: QUICK FILTER - Filter Orders by Delivery Date
 * ============================================================================
 */

/**
 * Add delivery date filter dropdown
 */
add_action('restrict_manage_posts', 'ah_ho_delivery_date_filter_dropdown');
add_action('woocommerce_order_list_table_restrict_manage_orders', 'ah_ho_delivery_date_filter_dropdown');

function ah_ho_delivery_date_filter_dropdown($post_type = '') {
    global $typenow;

    // Check if we're on orders page
    if ($typenow !== 'shop_order' && $post_type !== 'shop_order') {
        return;
    }

    $selected = isset($_GET['delivery_date_filter']) ? sanitize_text_field($_GET['delivery_date_filter']) : '';
    ?>
    <select name="delivery_date_filter" id="delivery_date_filter">
        <option value=""><?php _e('All delivery dates', 'ah-ho-custom'); ?></option>
        <option value="today" <?php selected($selected, 'today'); ?>><?php _e('Delivery Today', 'ah-ho-custom'); ?></option>
        <option value="tomorrow" <?php selected($selected, 'tomorrow'); ?>><?php _e('Delivery Tomorrow', 'ah-ho-custom'); ?></option>
        <option value="this_week" <?php selected($selected, 'this_week'); ?>><?php _e('This Week', 'ah-ho-custom'); ?></option>
        <option value="overdue" <?php selected($selected, 'overdue'); ?>><?php _e('Overdue (Past Date)', 'ah-ho-custom'); ?></option>
        <option value="no_date" <?php selected($selected, 'no_date'); ?>><?php _e('No Delivery Date', 'ah-ho-custom'); ?></option>
    </select>

    <input type="date" name="delivery_date_exact" id="delivery_date_exact"
           value="<?php echo esc_attr($_GET['delivery_date_exact'] ?? ''); ?>"
           placeholder="<?php _e('Specific date', 'ah-ho-custom'); ?>"
           style="width: 140px;">
    <?php
}

/**
 * Apply delivery date filter
 */
add_filter('request', 'ah_ho_filter_orders_by_delivery_date');

function ah_ho_filter_orders_by_delivery_date($vars) {
    global $typenow;

    if ($typenow !== 'shop_order') {
        return $vars;
    }

    $today = date('Y-m-d');

    // Handle preset filters
    if (!empty($_GET['delivery_date_filter'])) {
        $filter = sanitize_text_field($_GET['delivery_date_filter']);

        switch ($filter) {
            case 'today':
                $vars['meta_query'][] = array(
                    'key'     => '_delivery_date',
                    'value'   => $today,
                    'compare' => '=',
                );
                break;

            case 'tomorrow':
                $vars['meta_query'][] = array(
                    'key'     => '_delivery_date',
                    'value'   => date('Y-m-d', strtotime('+1 day')),
                    'compare' => '=',
                );
                break;

            case 'this_week':
                $week_start = date('Y-m-d', strtotime('monday this week'));
                $week_end = date('Y-m-d', strtotime('sunday this week'));
                $vars['meta_query'][] = array(
                    'key'     => '_delivery_date',
                    'value'   => array($week_start, $week_end),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                );
                break;

            case 'overdue':
                $vars['meta_query'][] = array(
                    'key'     => '_delivery_date',
                    'value'   => $today,
                    'compare' => '<',
                    'type'    => 'DATE',
                );
                break;

            case 'no_date':
                $vars['meta_query'][] = array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_delivery_date',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'     => '_delivery_date',
                        'value'   => '',
                        'compare' => '=',
                    ),
                );
                break;
        }
    }

    // Handle exact date filter
    if (!empty($_GET['delivery_date_exact'])) {
        $exact_date = sanitize_text_field($_GET['delivery_date_exact']);
        $vars['meta_query'][] = array(
            'key'     => '_delivery_date',
            'value'   => $exact_date,
            'compare' => '=',
        );
    }

    return $vars;
}


/**
 * ============================================================================
 * SECTION 5: CHECKOUT STYLING
 * ============================================================================
 */

/**
 * Add checkout styling for delivery date field
 */
add_action('wp_head', 'ah_ho_delivery_date_checkout_styles');

function ah_ho_delivery_date_checkout_styles() {
    if (!is_checkout()) {
        return;
    }
    ?>
    <style>
        #ah-ho-delivery-date-field {
            background: #f8f9fa;
            border: 2px solid #2ea44f;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        #ah-ho-delivery-date-field h3 {
            margin-top: 0;
            color: #2ea44f;
            font-size: 1.2em;
        }
        #ah-ho-delivery-date-field input[type="date"] {
            padding: 10px;
            font-size: 16px;
        }
        #ah-ho-delivery-date-field select {
            padding: 10px;
            font-size: 16px;
        }
    </style>
    <?php
}
