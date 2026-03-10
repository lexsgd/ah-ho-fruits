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
    // Get minimum delivery date (next working day)
    $min_date = ah_ho_get_next_delivery_date(1);
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
    echo __('Sundays &amp; Public Holidays not available.', 'ah-ho-custom');
    echo '</small></p>';

    echo '</div>';
}

/**
 * Singapore Public Holidays (update annually)
 * Includes observed holidays when PH falls on Sunday.
 *
 * @return array Dates in Y-m-d format
 */
function ah_ho_get_sg_public_holidays() {
    return array(
        // 2026
        '2026-01-01', // New Year's Day
        '2026-01-29', // Chinese New Year
        '2026-01-30', // Chinese New Year
        '2026-03-31', // Hari Raya Puasa
        '2026-04-03', // Good Friday
        '2026-05-01', // Labour Day
        '2026-05-26', // Vesak Day
        '2026-07-17', // Hari Raya Haji
        '2026-08-09', // National Day (Sun)
        '2026-08-10', // National Day observed (Mon)
        '2026-11-08', // Deepavali (Sun)
        '2026-11-09', // Deepavali observed (Mon)
        '2026-12-25', // Christmas Day
        // 2027 — update when official dates released
        '2027-01-01', // New Year's Day
        '2027-02-17', // Chinese New Year
        '2027-02-18', // Chinese New Year
        '2027-12-25', // Christmas Day
    );
}

/**
 * Get next available delivery date (skip Sundays and Public Holidays)
 *
 * @param int $business_days Number of working days from today
 * @return string Date in Y-m-d format
 */
function ah_ho_get_next_delivery_date($business_days = 3) {
    $date = new DateTime(current_time('Y-m-d'));
    $holidays = ah_ho_get_sg_public_holidays();
    $added_days = 0;

    while ($added_days < $business_days) {
        $date->modify('+1 day');
        $day_of_week = (int) $date->format('N'); // 1=Mon, 7=Sun

        // Skip Sundays and Public Holidays
        if ($day_of_week === 7 || in_array($date->format('Y-m-d'), $holidays)) {
            continue;
        }
        $added_days++;
    }

    // If landed on Sunday or PH, move forward
    while ((int) $date->format('N') === 7 || in_array($date->format('Y-m-d'), $holidays)) {
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

        // Check if date is a Sunday
        $day_of_week = date('N', strtotime($delivery_date));
        if ($day_of_week == 7) {
            wc_add_notice(__('Sunday delivery is not available. Please select another day.', 'ah-ho-custom'), 'error');
        }

        // Check if date is a Public Holiday
        $holidays = ah_ho_get_sg_public_holidays();
        if (in_array($delivery_date, $holidays)) {
            wc_add_notice(__('Delivery is not available on Public Holidays. Please select another day.', 'ah-ho-custom'), 'error');
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
 * Enqueue Flatpickr for checkout page
 */
add_action('wp_enqueue_scripts', 'ah_ho_enqueue_flatpickr');

function ah_ho_enqueue_flatpickr() {
    if (!is_checkout()) {
        return;
    }

    // Flatpickr CSS
    wp_enqueue_style(
        'flatpickr',
        'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
        array(),
        '4.6.13'
    );

    // Flatpickr JS
    wp_enqueue_script(
        'flatpickr',
        'https://cdn.jsdelivr.net/npm/flatpickr',
        array(),
        '4.6.13',
        true
    );
}

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

        let flatpickrInstance = null;

        // Singapore Public Holidays (update annually)
        const sgPublicHolidays = <?php echo json_encode(ah_ho_get_sg_public_holidays()); ?>;

        /**
         * Check if a date is a Public Holiday
         */
        function isPublicHoliday(date) {
            const dateStr = date.getFullYear() + '-' +
                String(date.getMonth() + 1).padStart(2, '0') + '-' +
                String(date.getDate()).padStart(2, '0');
            return sgPublicHolidays.includes(dateStr);
        }

        /**
         * Calculate minimum delivery date - next working day (skip Sundays + PH)
         */
        function getMinDeliveryDate(shippingMethod) {
            const today = new Date();
            let minDate = new Date(today);
            let daysToAdd = 1;

            // Add working days (skip Sundays and Public Holidays)
            let addedDays = 0;
            while (addedDays < daysToAdd) {
                minDate.setDate(minDate.getDate() + 1);
                if (minDate.getDay() !== 0 && !isPublicHoliday(minDate)) {
                    addedDays++;
                }
            }

            // If landed on Sunday or PH, move forward
            while (minDate.getDay() === 0 || isPublicHoliday(minDate)) {
                minDate.setDate(minDate.getDate() + 1);
            }

            return minDate;
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

        /**
         * Format date for display
         */
        function formatDateDisplay(date) {
            const options = { weekday: 'short', day: 'numeric', month: 'short' };
            return date.toLocaleDateString('en-SG', options);
        }

        // Wait for checkout to be ready
        function initDeliveryDate() {
            // Check if blocks checkout exists
            const checkoutForm = document.querySelector('.wc-block-checkout');
            if (!checkoutForm) {
                return;
            }

            // Check if already added
            if (document.getElementById('ah-ho-delivery-date-blocks-container')) {
                return;
            }

            // Wait for Flatpickr to load
            if (typeof flatpickr === 'undefined') {
                setTimeout(initDeliveryDate, 100);
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
                        <input type="text" id="ah_ho_delivery_date" name="ah_ho_delivery_date"
                               placeholder="Select a date..."
                               readonly
                               style="width: 100%; padding: 12px; font-size: 16px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; background: #fff; cursor: pointer;">
                        <p id="ah_ho_date_note" style="margin: 8px 0 0; font-size: 12px; color: #666;">
                            Earliest available: <span id="ah_ho_earliest_date">${formatDateDisplay(initialMinDate)}</span>. Sundays &amp; Public Holidays not available.
                        </p>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #999;">
                            Choose any working day (Mon-Sat).
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

            // Initialize Flatpickr with weekends disabled
            flatpickrInstance = flatpickr(dateInput, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'D, d M Y', // Display format: "Mon, 05 Feb 2026"
                minDate: initialMinDate,
                maxDate: '<?php echo esc_attr($max_date); ?>',
                disable: [
                    function(date) {
                        // Disable Sundays and Public Holidays
                        return (date.getDay() === 0 || isPublicHoliday(date));
                    }
                ],
                locale: {
                    firstDayOfWeek: 1 // Start week on Monday
                },
                onChange: function(selectedDates, dateStr) {
                    updateDeliveryDate(dateStr);
                }
            });

            // Listen for shipping method changes
            const shippingContainer = document.querySelector('.wc-block-components-shipping-rates-control');
            if (shippingContainer) {
                shippingContainer.addEventListener('change', function(e) {
                    if (e.target.type === 'radio') {
                        const newShipping = getSelectedShippingMethod();
                        const newMinDate = getMinDeliveryDate(newShipping);

                        // Update Flatpickr minDate
                        if (flatpickrInstance) {
                            flatpickrInstance.set('minDate', newMinDate);

                            // Clear selected date if it's now before the new minimum
                            const currentDate = flatpickrInstance.selectedDates[0];
                            if (currentDate && currentDate < newMinDate) {
                                flatpickrInstance.clear();
                            }
                        }

                        earliestDateSpan.textContent = formatDateDisplay(newMinDate);
                    }
                });
            }

            // Track selected delivery date for fetch interceptor
            let selectedDeliveryDate = '';

            // Store values for WooCommerce Blocks via Store API extension
            function updateDeliveryDate(dateStr) {
                selectedDeliveryDate = dateStr;

                // Primary: Use WooCommerce Blocks setExtensionData
                try {
                    if (window.wp && window.wp.data) {
                        const store = window.wp.data.dispatch('wc/store/checkout');
                        if (store && store.setExtensionData) {
                            store.setExtensionData('ah-ho-delivery', {
                                delivery_date: dateStr
                            });
                        }
                    }
                } catch(e) {
                    // Silently fall through to fetch interceptor
                }
            }

            // Backup: Intercept fetch to inject delivery_date into Store API checkout request
            const originalFetch = window.fetch;
            window.fetch = function(url, options) {
                if (selectedDeliveryDate && options && options.method === 'POST' &&
                    typeof url === 'string' && url.includes('/wc/store/') && url.includes('checkout')) {
                    try {
                        const body = JSON.parse(options.body);
                        if (!body.extensions) body.extensions = {};
                        if (!body.extensions['ah-ho-delivery']) body.extensions['ah-ho-delivery'] = {};
                        body.extensions['ah-ho-delivery'].delivery_date = selectedDeliveryDate;
                        options.body = JSON.stringify(body);
                    } catch(e) {
                        // Not JSON or parse error, skip
                    }
                }
                return originalFetch.call(this, url, options);
            };
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
 * ============================================================================
 * SECTION 1C: WOOCOMMERCE BLOCKS - Store API Integration
 * ============================================================================
 * Registers the 'ah-ho-delivery' extension namespace with the Store API
 * so that Blocks checkout includes delivery_date in the checkout request.
 * Without this registration, setExtensionData() data is silently stripped.
 */

/**
 * Register delivery date extension with WooCommerce Store API
 */
add_action('woocommerce_blocks_loaded', 'ah_ho_register_delivery_date_store_api');

function ah_ho_register_delivery_date_store_api() {
    if (!function_exists('woocommerce_store_api_register_endpoint_data')) {
        return;
    }

    woocommerce_store_api_register_endpoint_data(
        array(
            'endpoint'        => 'checkout',
            'namespace'       => 'ah-ho-delivery',
            'data_callback'   => function() {
                return array(
                    'delivery_date' => '',
                );
            },
            'schema_callback' => function() {
                return array(
                    'delivery_date' => array(
                        'description' => 'Preferred delivery date',
                        'type'        => 'string',
                        'context'     => array('view', 'edit'),
                        'readonly'    => false,
                    ),
                );
            },
            'schema_type'     => ARRAY_A,
        )
    );
}

/**
 * Save delivery date from WooCommerce Store API (Blocks checkout)
 */
add_action('woocommerce_store_api_checkout_update_order_from_request', 'ah_ho_save_delivery_date_from_store_api', 10, 2);

function ah_ho_save_delivery_date_from_store_api($order, $request) {
    // Skip if already has delivery date (saved by classic checkout hook)
    if ($order->get_meta('_delivery_date')) {
        return;
    }

    $extensions = $request->get_param('extensions');

    if (!empty($extensions['ah-ho-delivery']['delivery_date'])) {
        $delivery_date = sanitize_text_field($extensions['ah-ho-delivery']['delivery_date']);
        $order->update_meta_data('_delivery_date', $delivery_date);
        $order->save();
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
    $order_id = $order->get_id();
    $nonce = wp_create_nonce('ah_ho_delivery_nonce');
    ?>
    <div class="ah-ho-delivery-schedule" style="margin-top: 20px; padding: 12px; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 4px;">
        <h3 style="margin: 0 0 10px 0;">
            <?php _e('Delivery Schedule', 'ah-ho-custom'); ?>
            <a href="#" id="ah-ho-delivery-edit-toggle" style="font-size: 12px; margin-left: 8px;"><?php _e('Edit', 'ah-ho-custom'); ?></a>
        </h3>

        <div id="ah-ho-delivery-view">
            <?php if ($delivery_date) : ?>
                <p style="margin: 4px 0;">
                    <strong><?php _e('Delivery Date:', 'ah-ho-custom'); ?></strong><br>
                    <span id="ah-ho-delivery-display"><?php echo esc_html(date('l, d F Y', strtotime($delivery_date))); ?></span>
                </p>
            <?php else : ?>
                <p style="color: #b32d2e; font-style: italic; margin: 4px 0;" id="ah-ho-no-date-msg">
                    <?php _e('No delivery date set', 'ah-ho-custom'); ?>
                </p>
            <?php endif; ?>
        </div>

        <div id="ah-ho-delivery-edit" style="display: none;">
            <p class="form-field form-field-wide">
                <label for="_delivery_date"><?php _e('Delivery Date:', 'ah-ho-custom'); ?></label>
                <input type="date" id="_delivery_date" name="_delivery_date"
                       value="<?php echo esc_attr($delivery_date); ?>"
                       style="width: 100%;">
            </p>
            <p>
                <button type="button" id="ah-ho-delivery-save-btn" class="button button-primary" style="margin-right: 8px;">
                    <?php _e('Save', 'ah-ho-custom'); ?>
                </button>
                <button type="button" id="ah-ho-delivery-cancel-btn" class="button">
                    <?php _e('Cancel', 'ah-ho-custom'); ?>
                </button>
                <span id="ah-ho-delivery-status" style="margin-left: 10px; font-size: 12px;"></span>
            </p>
        </div>
    </div>
    <script>
    jQuery(function($) {
        // Toggle edit mode
        $('#ah-ho-delivery-edit-toggle').on('click', function(e) {
            e.preventDefault();
            $('#ah-ho-delivery-view').hide();
            $('#ah-ho-delivery-edit').show();
            $(this).hide();
        });

        // Cancel — go back to view mode
        $('#ah-ho-delivery-cancel-btn').on('click', function() {
            $('#ah-ho-delivery-edit').hide();
            $('#ah-ho-delivery-view').show();
            $('#ah-ho-delivery-edit-toggle').show();
            $('#ah-ho-delivery-status').text('');
        });

        // Save via AJAX
        $('#ah-ho-delivery-save-btn').on('click', function() {
            var btn = $(this);
            var dateVal = $('#_delivery_date').val();
            var statusEl = $('#ah-ho-delivery-status');

            btn.prop('disabled', true);
            statusEl.text('Saving...').css('color', '#666');

            $.post(ajaxurl, {
                action: 'ah_ho_save_delivery_date',
                nonce: '<?php echo esc_js($nonce); ?>',
                order_id: <?php echo (int) $order_id; ?>,
                delivery_date: dateVal
            }, function(response) {
                btn.prop('disabled', false);
                if (response.success) {
                    statusEl.text('Saved!').css('color', '#46b450');

                    // Update the view
                    if (response.data.display) {
                        var viewHtml = '<p style="margin: 4px 0;"><strong>Delivery Date:</strong><br><span id="ah-ho-delivery-display">' + response.data.display + '</span></p>';
                        $('#ah-ho-delivery-view').html(viewHtml);
                    } else {
                        $('#ah-ho-delivery-view').html('<p style="color: #b32d2e; font-style: italic; margin: 4px 0;">No delivery date set</p>');
                    }

                    // Switch back to view after short delay
                    setTimeout(function() {
                        $('#ah-ho-delivery-edit').hide();
                        $('#ah-ho-delivery-view').show();
                        $('#ah-ho-delivery-edit-toggle').show();
                        statusEl.text('');
                    }, 800);
                } else {
                    statusEl.text('Error: ' + (response.data || 'Save failed')).css('color', '#dc3232');
                }
            }).fail(function() {
                btn.prop('disabled', false);
                statusEl.text('Error: Request failed').css('color', '#dc3232');
            });
        });
    });
    </script>
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

    $order->save();
}

/**
 * AJAX handler for saving delivery date from admin order page
 */
add_action('wp_ajax_ah_ho_save_delivery_date', 'ah_ho_ajax_save_delivery_date');

function ah_ho_ajax_save_delivery_date() {
    check_ajax_referer('ah_ho_delivery_nonce', 'nonce');

    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error('Permission denied');
    }

    $order_id = absint($_POST['order_id'] ?? 0);
    $delivery_date = sanitize_text_field($_POST['delivery_date'] ?? '');

    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('Order not found');
    }

    $order->update_meta_data('_delivery_date', $delivery_date);
    $order->save();

    $display = $delivery_date ? date('l, d F Y', strtotime($delivery_date)) : '';

    wp_send_json_success([
        'message' => 'Delivery date saved',
        'display' => $display,
    ]);
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
