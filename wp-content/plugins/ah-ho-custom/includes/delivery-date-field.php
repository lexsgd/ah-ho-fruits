<?php
/**
 * Delivery Date Field
 *
 * Adds delivery date selection to checkout and order admin.
 * Works with the Delivery Date Helper in ah-ho-invoicing plugin.
 *
 * Supports BOTH:
 * - Classic WooCommerce checkout (shortcode)
 * - WooCommerce Blocks checkout (modern block-based)
 *
 * @package AhHoCustom
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================================
 * SECTION 1A: CLASSIC CHECKOUT - Customer Delivery Date Selection
 * ============================================================================
 */

/**
 * Add delivery date field to classic checkout
 */
add_action('woocommerce_before_order_notes', 'ah_ho_add_delivery_date_checkout_field');

function ah_ho_add_delivery_date_checkout_field($checkout) {
    // Get minimum delivery date (3 business days)
    $min_date = ah_ho_get_next_delivery_date(3);
    $max_date = date('Y-m-d', strtotime('+30 days'));

    echo '<div id="ah-ho-delivery-date-field" class="ah-ho-delivery-section">';
    echo '<h3>' . __('Delivery Date', 'ah-ho-custom') . '</h3>';

    woocommerce_form_field('delivery_date', array(
        'type'        => 'date',
        'class'       => array('form-row-wide'),
        'label'       => __('Preferred Delivery Date (optional)', 'ah-ho-custom'),
        'placeholder' => __('Select delivery date', 'ah-ho-custom'),
        'required'    => false,
        'custom_attributes' => array(
            'min' => $min_date,
            'max' => $max_date,
        ),
    ), $checkout->get_value('delivery_date'));

    echo '<p class="form-row form-row-wide"><small>';
    echo __('Weekends not available. Earliest delivery: 3 business days.', 'ah-ho-custom');
    echo '</small></p>';

    echo '</div>';
}

/**
 * Get next available delivery date (business days only, skip weekends)
 *
 * @param int $business_days Number of business days from today
 * @return string Date in Y-m-d format
 */
function ah_ho_get_next_delivery_date($business_days = 3) {
    $date = new DateTime(current_time('Y-m-d'));
    $added_days = 0;

    while ($added_days < $business_days) {
        $date->modify('+1 day');
        $day_of_week = (int) $date->format('N'); // 1 = Monday, 7 = Sunday

        // Skip weekends (6 = Saturday, 7 = Sunday)
        if ($day_of_week < 6) {
            $added_days++;
        }
    }

    // If landed on weekend, move to Monday
    while ((int) $date->format('N') >= 6) {
        $date->modify('+1 day');
    }

    return $date->format('Y-m-d');
}

/**
 * Validate delivery date field (classic checkout) - now optional
 */
add_action('woocommerce_checkout_process', 'ah_ho_validate_delivery_date_field');

function ah_ho_validate_delivery_date_field() {
    // Skip if using blocks checkout (handled by JS)
    if (ah_ho_is_blocks_checkout()) {
        return;
    }

    // Delivery date is now optional, only validate if provided
    if (!empty($_POST['delivery_date'])) {
        $delivery_date = sanitize_text_field($_POST['delivery_date']);

        // Check if date is a weekend
        $day_of_week = date('N', strtotime($delivery_date));
        if ($day_of_week >= 6) {
            wc_add_notice(__('Weekend delivery is not available. Please select a weekday.', 'ah-ho-custom'), 'error');
        }
    }
}

/**
 * Check if using blocks checkout
 */
function ah_ho_is_blocks_checkout() {
    if (class_exists('Automattic\WooCommerce\Blocks\Package')) {
        return \Automattic\WooCommerce\Blocks\Package::feature()->is_feature_plugin_build();
    }
    return false;
}


/**
 * ============================================================================
 * SECTION 1B: WOOCOMMERCE BLOCKS CHECKOUT - Delivery Date Integration
 * ============================================================================
 * Uses JavaScript injection approach for maximum compatibility
 */

/**
 * Inject delivery date fields into Blocks checkout via JavaScript
 */
add_action('wp_footer', 'ah_ho_blocks_checkout_inline_script');

function ah_ho_blocks_checkout_inline_script() {
    // Only on checkout page
    if (!is_checkout()) {
        return;
    }

    $max_date = date('Y-m-d', strtotime('+30 days'));
    ?>
    <script type="text/javascript">
    (function() {
        'use strict';

        /**
         * Calculate minimum delivery date based on shipping method
         * - Standard Delivery / Self Pickup: 3 business days (skip weekends)
         * - Express Same Day: Next business day
         */
        function getMinDeliveryDate(shippingMethod) {
            const today = new Date();
            let minDate = new Date(today);
            let daysToAdd = 0;

            // Determine days to add based on shipping method
            if (shippingMethod && shippingMethod.toLowerCase().includes('express')) {
                // Express: next business day
                daysToAdd = 1;
            } else {
                // Standard Delivery / Self Pickup: 3 business days
                daysToAdd = 3;
            }

            // Add business days (skip weekends)
            let addedDays = 0;
            while (addedDays < daysToAdd) {
                minDate.setDate(minDate.getDate() + 1);
                const dayOfWeek = minDate.getDay();
                // Skip Saturday (6) and Sunday (0)
                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    addedDays++;
                }
            }

            // If landed on weekend, move to Monday
            while (minDate.getDay() === 0 || minDate.getDay() === 6) {
                minDate.setDate(minDate.getDate() + 1);
            }

            return minDate.toISOString().split('T')[0];
        }

        /**
         * Check if a date is a weekend
         */
        function isWeekend(dateString) {
            const date = new Date(dateString);
            const day = date.getDay();
            return day === 0 || day === 6; // Sunday = 0, Saturday = 6
        }

        /**
         * Get currently selected shipping method
         */
        function getSelectedShippingMethod() {
            const selectedRadio = document.querySelector('.wc-block-components-shipping-rates-control input[type="radio"]:checked');
            if (selectedRadio) {
                const label = selectedRadio.closest('.wc-block-components-radio-control__option');
                if (label) {
                    return label.textContent || '';
                }
            }
            return '';
        }

        // Wait for checkout to be ready
        function initDeliveryDate() {
            // Check if blocks checkout exists
            const checkoutForm = document.querySelector('.wc-block-checkout');
            if (!checkoutForm) {
                // Not blocks checkout, skip
                return;
            }

            // Check if already added
            if (document.getElementById('ah-ho-delivery-date-blocks-container')) {
                return;
            }

            // Find the shipping options section
            const shippingSection = document.querySelector('.wc-block-components-shipping-rates-control');
            if (!shippingSection) {
                return; // Wait for shipping section to load
            }

            // Get initial shipping method
            const initialShipping = getSelectedShippingMethod();
            const initialMinDate = getMinDeliveryDate(initialShipping);

            // Create delivery date container - matching WooCommerce Blocks style
            const container = document.createElement('div');
            container.id = 'ah-ho-delivery-date-blocks-container';
            container.className = 'wc-block-components-checkout-step';
            container.style.cssText = 'margin-top: 24px;';
            container.innerHTML = `
                <h2 class="wc-block-components-title wc-block-components-checkout-step__title" style="color: #6abd45; font-size: 1.25rem; font-weight: 700; margin-bottom: 16px;">
                    Delivery Date
                </h2>
                <div class="wc-block-components-checkout-step__content">
                    <div style="margin-bottom: 8px;">
                        <label for="ah_ho_delivery_date" style="display: block; margin-bottom: 8px; font-size: 14px; color: #333;">
                            Preferred Delivery Date (optional)
                        </label>
                        <input type="date" id="ah_ho_delivery_date" name="ah_ho_delivery_date"
                               min="${initialMinDate}"
                               max="<?php echo esc_attr($max_date); ?>"
                               style="width: 100%; padding: 12px; font-size: 16px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        <p id="ah_ho_date_note" style="margin: 8px 0 0; font-size: 12px; color: #666;">
                            Earliest available: <span id="ah_ho_earliest_date">${formatDate(initialMinDate)}</span>. Weekends not available.
                        </p>
                    </div>
                </div>
            `;

            // Insert after shipping options section's parent
            const shippingParent = shippingSection.closest('.wc-block-components-checkout-step');
            if (shippingParent) {
                shippingParent.parentNode.insertBefore(container, shippingParent.nextSibling);
            } else {
                shippingSection.parentNode.insertBefore(container, shippingSection.nextSibling);
            }

            const dateInput = document.getElementById('ah_ho_delivery_date');
            const earliestDateSpan = document.getElementById('ah_ho_earliest_date');

            // Validate date selection (no weekends)
            dateInput.addEventListener('change', function() {
                if (this.value && isWeekend(this.value)) {
                    alert('Weekend delivery is not available. Please select a weekday (Monday - Friday).');
                    this.value = '';
                }
                updateHiddenFields();
            });

            // Listen for shipping method changes
            const shippingContainer = document.querySelector('.wc-block-components-shipping-rates-control');
            if (shippingContainer) {
                shippingContainer.addEventListener('change', function(e) {
                    if (e.target.type === 'radio') {
                        const newShipping = getSelectedShippingMethod();
                        const newMinDate = getMinDeliveryDate(newShipping);
                        dateInput.min = newMinDate;
                        earliestDateSpan.textContent = formatDate(newMinDate);

                        // Clear selected date if it's now before the new minimum
                        if (dateInput.value && dateInput.value < newMinDate) {
                            dateInput.value = '';
                        }
                    }
                });
            }

            // Format date for display
            function formatDate(dateString) {
                const date = new Date(dateString);
                const options = { weekday: 'short', day: 'numeric', month: 'short' };
                return date.toLocaleDateString('en-SG', options);
            }

            // Store values for WooCommerce Blocks
            function updateHiddenFields() {
                if (window.wp && window.wp.data) {
                    const { dispatch } = window.wp.data;
                    const store = dispatch('wc/store/checkout');
                    if (store && store.setExtensionData) {
                        store.setExtensionData('ah-ho-delivery', {
                            delivery_date: dateInput.value
                        });
                    }
                }
                // Update hidden field
                const hiddenDate = document.getElementById('delivery_date_hidden');
                if (hiddenDate) {
                    hiddenDate.value = dateInput.value;
                }
            }

            dateInput.addEventListener('change', updateHiddenFields);

            // Create hidden form field as backup
            const hiddenDate = document.createElement('input');
            hiddenDate.type = 'hidden';
            hiddenDate.name = 'delivery_date';
            hiddenDate.id = 'delivery_date_hidden';
            checkoutForm.appendChild(hiddenDate);
        }

        // Run on page load and observe DOM changes
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDeliveryDate);
        } else {
            initDeliveryDate();
        }

        // Also observe for dynamic content loading (React hydration)
        const observer = new MutationObserver(function(mutations) {
            initDeliveryDate();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Cleanup observer after 10 seconds
        setTimeout(function() {
            observer.disconnect();
        }, 10000);
    })();
    </script>
    <?php
}

/**
 * Save delivery date to order meta (classic checkout)
 */
add_action('woocommerce_checkout_update_order_meta', 'ah_ho_save_delivery_date_field');

function ah_ho_save_delivery_date_field($order_id) {
    if (!empty($_POST['delivery_date'])) {
        $order = wc_get_order($order_id);
        $delivery_date = sanitize_text_field($_POST['delivery_date']);

        // Save using the standard meta key that Delivery Date Helper expects
        $order->update_meta_data('_delivery_date', $delivery_date);
        $order->save();
    }
}

/**
 * Save delivery date from Blocks checkout (multiple fallback methods)
 */
add_action('woocommerce_checkout_order_created', 'ah_ho_save_delivery_date_blocks_fallback');

function ah_ho_save_delivery_date_blocks_fallback($order) {
    // Skip if already has delivery date
    if ($order->get_meta('_delivery_date')) {
        return;
    }

    // Try to get from POST data (hidden fields)
    if (!empty($_POST['delivery_date'])) {
        $order->update_meta_data('_delivery_date', sanitize_text_field($_POST['delivery_date']));
        $order->save();
        return;
    }

    // Try to get from request body (Blocks checkout JSON)
    $request_body = file_get_contents('php://input');
    if ($request_body) {
        $data = json_decode($request_body, true);
        if ($data) {
            // Check extensions
            if (!empty($data['extensions']['ah-ho-delivery']['delivery_date'])) {
                $order->update_meta_data('_delivery_date', sanitize_text_field($data['extensions']['ah-ho-delivery']['delivery_date']));
                $order->save();
                return;
            }

            // Check additional_fields (newer WooCommerce Blocks format)
            if (!empty($data['additional_fields']['ah_ho/delivery_date'])) {
                $order->update_meta_data('_delivery_date', sanitize_text_field($data['additional_fields']['ah_ho/delivery_date']));
                $order->save();
            }
        }
    }
}

/**
 * Display delivery date on order received page
 */
add_action('woocommerce_order_details_after_order_table', 'ah_ho_display_delivery_date_on_order');

function ah_ho_display_delivery_date_on_order($order) {
    $delivery_date = $order->get_meta('_delivery_date');

    if ($delivery_date) {
        echo '<h2>' . __('Delivery Information', 'ah-ho-custom') . '</h2>';
        echo '<table class="woocommerce-table shop_table delivery-info">';
        echo '<tr><th>' . __('Preferred Delivery Date:', 'ah-ho-custom') . '</th>';
        echo '<td><strong>' . esc_html(date('l, d F Y', strtotime($delivery_date))) . '</strong></td></tr>';
        echo '</table>';
    }
}

/**
 * Add delivery date to order emails
 */
add_action('woocommerce_email_after_order_table', 'ah_ho_add_delivery_date_to_emails', 10, 4);

function ah_ho_add_delivery_date_to_emails($order, $sent_to_admin, $plain_text, $email) {
    $delivery_date = $order->get_meta('_delivery_date');

    if (!$delivery_date) {
        return;
    }

    if ($plain_text) {
        echo "\n" . __('Preferred Delivery Date:', 'ah-ho-custom') . ' ' . date('l, d F Y', strtotime($delivery_date));
        echo "\n";
    } else {
        echo '<h2>' . __('Delivery Information', 'ah-ho-custom') . '</h2>';
        echo '<p><strong>' . __('Preferred Delivery Date:', 'ah-ho-custom') . '</strong> ';
        echo esc_html(date('l, d F Y', strtotime($delivery_date))) . '</p>';
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
