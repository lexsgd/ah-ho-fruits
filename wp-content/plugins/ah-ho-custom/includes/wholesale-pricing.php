<?php
/**
 * Wholesale Pricing for B2B Salesperson Orders
 *
 * Adds wholesale price field to products and automatically applies
 * wholesale pricing when salespersons create orders.
 *
 * @package AhHoCustom
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================================
 * SECTION 1: PRODUCT ADMIN UI - Add wholesale price field to product edit
 * ============================================================================
 */

/**
 * Add wholesale price field to simple product pricing tab
 */
function ah_ho_add_wholesale_price_field() {
    global $post;

    echo '<div class="options_group ah-ho-wholesale-pricing">';

    woocommerce_wp_text_input(array(
        'id'          => '_wholesale_price',
        'label'       => __('Wholesale Price', 'ah-ho-custom') . ' (' . get_woocommerce_currency_symbol() . ')',
        'placeholder' => __('Enter wholesale price for B2B orders', 'ah-ho-custom'),
        'desc_tip'    => true,
        'description' => __('This price will be used when salespersons create orders. Leave blank to use regular price.', 'ah-ho-custom'),
        'type'        => 'text',
        'data_type'   => 'price',
        'class'       => 'short wc_input_price',
    ));

    // Show comparison with regular price
    $regular_price = get_post_meta($post->ID, '_regular_price', true);
    $wholesale_price = get_post_meta($post->ID, '_wholesale_price', true);

    if ($regular_price && $wholesale_price) {
        $discount_pct = round((1 - ($wholesale_price / $regular_price)) * 100, 1);
        echo '<p class="form-field _wholesale_discount_display">';
        echo '<label></label>';
        echo '<span class="description" style="color: #007cba;">';
        echo sprintf(
            __('Wholesale discount: %s%% off retail price', 'ah-ho-custom'),
            $discount_pct
        );
        echo '</span>';
        echo '</p>';
    }

    echo '</div>';
}
add_action('woocommerce_product_options_pricing', 'ah_ho_add_wholesale_price_field');

/**
 * Save wholesale price field for simple products
 */
function ah_ho_save_wholesale_price_field($post_id) {
    if (isset($_POST['_wholesale_price'])) {
        $wholesale_price = $_POST['_wholesale_price'];

        // Sanitize as price
        if ($wholesale_price !== '') {
            $wholesale_price = wc_format_decimal($wholesale_price);
        }

        update_post_meta($post_id, '_wholesale_price', $wholesale_price);
    }
}
add_action('woocommerce_process_product_meta', 'ah_ho_save_wholesale_price_field');

/**
 * Add wholesale price field to variable product variations
 */
function ah_ho_add_wholesale_price_variation_field($loop, $variation_data, $variation) {
    woocommerce_wp_text_input(array(
        'id'            => "_wholesale_price_variation_{$loop}",
        'name'          => "_wholesale_price_variation[{$loop}]",
        'value'         => get_post_meta($variation->ID, '_wholesale_price', true),
        'label'         => __('Wholesale Price', 'ah-ho-custom') . ' (' . get_woocommerce_currency_symbol() . ')',
        'placeholder'   => __('Wholesale price', 'ah-ho-custom'),
        'desc_tip'      => true,
        'description'   => __('B2B price for salesperson orders', 'ah-ho-custom'),
        'type'          => 'text',
        'data_type'     => 'price',
        'class'         => 'short wc_input_price',
        'wrapper_class' => 'form-row form-row-first',
    ));
}
add_action('woocommerce_variation_options_pricing', 'ah_ho_add_wholesale_price_variation_field', 10, 3);

/**
 * Save wholesale price for variations
 */
function ah_ho_save_wholesale_price_variation($variation_id, $loop) {
    if (isset($_POST['_wholesale_price_variation'][$loop])) {
        $wholesale_price = $_POST['_wholesale_price_variation'][$loop];

        if ($wholesale_price !== '') {
            $wholesale_price = wc_format_decimal($wholesale_price);
        }

        update_post_meta($variation_id, '_wholesale_price', $wholesale_price);
    }
}
add_action('woocommerce_save_product_variation', 'ah_ho_save_wholesale_price_variation', 10, 2);


/**
 * ============================================================================
 * SECTION 2: PRODUCT LIST COLUMN - Show wholesale price in admin product list
 * ============================================================================
 */

/**
 * Add wholesale price column to product list
 */
function ah_ho_add_wholesale_price_column($columns) {
    $new_columns = array();

    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;

        // Add wholesale column after price column
        if ($key === 'price') {
            $new_columns['wholesale_price'] = __('Wholesale', 'ah-ho-custom');
        }
    }

    return $new_columns;
}
add_filter('manage_edit-product_columns', 'ah_ho_add_wholesale_price_column', 20);

/**
 * Display wholesale price in product list column
 */
function ah_ho_display_wholesale_price_column($column, $post_id) {
    if ($column === 'wholesale_price') {
        $product = wc_get_product($post_id);

        if (!$product) {
            echo '&mdash;';
            return;
        }

        if ($product->is_type('variable')) {
            // For variable products, get min/max wholesale prices
            $variations = $product->get_children();
            $wholesale_prices = array();

            foreach ($variations as $variation_id) {
                $wholesale = get_post_meta($variation_id, '_wholesale_price', true);
                if ($wholesale !== '') {
                    $wholesale_prices[] = floatval($wholesale);
                }
            }

            if (!empty($wholesale_prices)) {
                $min = min($wholesale_prices);
                $max = max($wholesale_prices);

                if ($min === $max) {
                    echo wc_price($min);
                } else {
                    echo wc_price($min) . ' &ndash; ' . wc_price($max);
                }
            } else {
                echo '<span style="color: #999;">' . __('Not set', 'ah-ho-custom') . '</span>';
            }
        } else {
            // Simple product
            $wholesale_price = get_post_meta($post_id, '_wholesale_price', true);

            if ($wholesale_price !== '' && $wholesale_price !== null) {
                echo wc_price($wholesale_price);

                // Show discount percentage
                $regular_price = $product->get_regular_price();
                if ($regular_price) {
                    $discount = round((1 - ($wholesale_price / $regular_price)) * 100);
                    if ($discount > 0) {
                        echo ' <small style="color: #007cba;">(-' . $discount . '%)</small>';
                    }
                }
            } else {
                echo '<span style="color: #999;">' . __('Not set', 'ah-ho-custom') . '</span>';
            }
        }
    }
}
add_action('manage_product_posts_custom_column', 'ah_ho_display_wholesale_price_column', 10, 2);


/**
 * ============================================================================
 * SECTION 3: ORDER CREATION - Apply wholesale price for salesperson orders
 * ============================================================================
 */

/**
 * Check if current user is a salesperson
 */
function ah_ho_is_salesperson($user_id = null) {
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $user = get_userdata($user_id);

    if (!$user) {
        return false;
    }

    $roles = (array) $user->roles;
    return in_array('ah_ho_salesperson', $roles, true) || in_array('ah_ho_storeman', $roles, true);
}

/**
 * Get wholesale price for a product
 *
 * @param int $product_id Product or variation ID
 * @return float|false Wholesale price or false if not set
 */
function ah_ho_get_wholesale_price($product_id) {
    $wholesale_price = get_post_meta($product_id, '_wholesale_price', true);

    if ($wholesale_price !== '' && $wholesale_price !== null) {
        return floatval($wholesale_price);
    }

    return false;
}

/**
 * Filter product price in admin order creation for salespersons
 *
 * This hooks into the AJAX product search when adding items to manual orders
 *
 * @param int $item_id The order item ID
 * @param WC_Order_Item $item The order item object
 * @param int $order_id The order ID
 */
function ah_ho_filter_admin_order_item_price($item_id, $item, $order_id) {
    // Only apply in admin context
    if (!is_admin()) {
        return;
    }

    // Ensure $item is an order item object, not an ID
    if (!$item instanceof WC_Order_Item_Product) {
        return;
    }

    // Check if current user is a salesperson
    if (!ah_ho_is_salesperson()) {
        return;
    }

    // Get the product
    $product_id = $item->get_product_id();
    $variation_id = $item->get_variation_id();

    // Use variation ID if it's a variation, otherwise product ID
    $price_product_id = $variation_id ? $variation_id : $product_id;

    // Get effective wholesale price (handles fallback behavior)
    $wholesale_price = ah_ho_get_effective_wholesale_price($price_product_id);

    if ($wholesale_price === false) {
        return;
    }

    // Get the original retail price for comparison
    $product = wc_get_product($price_product_id);
    $retail_price = $product ? floatval($product->get_price()) : 0;

    // Only mark as wholesale if price is actually different
    $is_wholesale = ($wholesale_price !== $retail_price);

    // Set the wholesale price
    $item->set_subtotal($wholesale_price * $item->get_quantity());
    $item->set_total($wholesale_price * $item->get_quantity());

    // Add meta to track that wholesale pricing was applied (only if actually wholesale)
    if ($is_wholesale) {
        $item->add_meta_data('_wholesale_price_applied', 'yes', true);
        $item->add_meta_data('_original_retail_price', $retail_price, true);
        $item->add_meta_data('_wholesale_unit_price', $wholesale_price, true);
    }

    $item->save();
}
add_action('woocommerce_new_order_item', 'ah_ho_filter_admin_order_item_price', 10, 3);

/**
 * Alternative approach: Filter price during admin AJAX item add
 * This ensures wholesale price is shown immediately when adding items
 */
function ah_ho_ajax_add_order_item_meta($item_id, $item, $order) {
    // Only apply in admin context
    if (!is_admin()) {
        return;
    }

    // Check if doing the add items AJAX
    if (!wp_doing_ajax()) {
        return;
    }

    // Verify this is the add order items action
    $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
    if ($action !== 'woocommerce_add_order_item') {
        return;
    }

    // Check if current user is a salesperson
    if (!ah_ho_is_salesperson()) {
        return;
    }

    // Get the product
    $product_id = $item->get_product_id();
    $variation_id = $item->get_variation_id();
    $price_product_id = $variation_id ? $variation_id : $product_id;

    // Get effective wholesale price (handles fallback behavior)
    $wholesale_price = ah_ho_get_effective_wholesale_price($price_product_id);

    // If blocked (no wholesale price and fallback=block), we could show an error
    // For now, we just skip applying wholesale if blocked
    if ($wholesale_price === false) {
        return;
    }

    // Get the original retail price for comparison
    $product = wc_get_product($price_product_id);
    $retail_price = $product ? floatval($product->get_price()) : 0;

    // Only mark as wholesale if price is actually different
    $is_wholesale = ($wholesale_price !== $retail_price);

    $quantity = $item->get_quantity();

    // Update line item totals
    $item->set_subtotal($wholesale_price * $quantity);
    $item->set_total($wholesale_price * $quantity);

    // Track wholesale pricing (only if actually wholesale)
    if ($is_wholesale) {
        $item->add_meta_data('_wholesale_price_applied', 'yes', true);
        $item->add_meta_data('_wholesale_unit_price', $wholesale_price, true);
        $item->add_meta_data('_original_retail_price', $retail_price, true);
    }

    $item->save();

    // Trigger order recalculation
    $order->calculate_totals(true);
}
add_action('woocommerce_ajax_add_order_item_meta', 'ah_ho_ajax_add_order_item_meta', 10, 3);

/**
 * Display wholesale price info in order item meta (admin view)
 */
function ah_ho_display_wholesale_meta_in_order($item_id, $item, $product) {
    // Only in admin
    if (!is_admin()) {
        return;
    }

    $wholesale_applied = $item->get_meta('_wholesale_price_applied');

    if ($wholesale_applied === 'yes') {
        $wholesale_unit = $item->get_meta('_wholesale_unit_price');
        echo '<div class="wc-order-item-wholesale" style="color: #007cba; font-size: 12px; margin-top: 5px;">';
        echo '<strong>' . __('B2B Wholesale Price:', 'ah-ho-custom') . '</strong> ';
        echo wc_price($wholesale_unit) . ' ' . __('per unit', 'ah-ho-custom');
        echo '</div>';
    }
}
add_action('woocommerce_after_order_itemmeta', 'ah_ho_display_wholesale_meta_in_order', 10, 3);


/**
 * ============================================================================
 * SECTION 4: BULK EDIT - Allow bulk setting of wholesale prices
 * ============================================================================
 */

/**
 * Add wholesale price to quick edit
 */
function ah_ho_quick_edit_wholesale_price() {
    ?>
    <br class="clear" />
    <label class="alignleft">
        <span class="title"><?php esc_html_e('Wholesale', 'ah-ho-custom'); ?></span>
        <span class="input-text-wrap">
            <input type="text" name="_wholesale_price" class="text wc_input_price" placeholder="<?php esc_attr_e('Wholesale price', 'ah-ho-custom'); ?>" value="">
        </span>
    </label>
    <?php
}
add_action('woocommerce_product_quick_edit_end', 'ah_ho_quick_edit_wholesale_price');

/**
 * Save quick edit wholesale price
 */
function ah_ho_save_quick_edit_wholesale_price($product) {
    if (isset($_REQUEST['_wholesale_price'])) {
        $wholesale_price = sanitize_text_field($_REQUEST['_wholesale_price']);

        if ($wholesale_price !== '') {
            $wholesale_price = wc_format_decimal($wholesale_price);
        }

        $product->update_meta_data('_wholesale_price', $wholesale_price);
    }
}
add_action('woocommerce_product_quick_edit_save', 'ah_ho_save_quick_edit_wholesale_price');

/**
 * Add wholesale price to bulk edit
 */
function ah_ho_bulk_edit_wholesale_price() {
    ?>
    <label class="alignleft">
        <span class="title"><?php esc_html_e('Wholesale', 'ah-ho-custom'); ?></span>
        <span class="input-text-wrap">
            <select class="change_wholesale_price change_to" name="change_wholesale_price">
                <option value=""><?php esc_html_e('&mdash; No change &mdash;', 'ah-ho-custom'); ?></option>
                <option value="1"><?php esc_html_e('Change to:', 'ah-ho-custom'); ?></option>
                <option value="2"><?php esc_html_e('Increase by (fixed amount):', 'ah-ho-custom'); ?></option>
                <option value="3"><?php esc_html_e('Decrease by (fixed amount):', 'ah-ho-custom'); ?></option>
                <option value="4"><?php esc_html_e('Set to X% of regular price:', 'ah-ho-custom'); ?></option>
            </select>
            <input type="text" name="_wholesale_price_bulk" class="text wc_input_price" placeholder="<?php esc_attr_e('Enter value', 'ah-ho-custom'); ?>" value="">
        </span>
    </label>
    <?php
}
add_action('woocommerce_product_bulk_edit_end', 'ah_ho_bulk_edit_wholesale_price');

/**
 * Save bulk edit wholesale price
 */
function ah_ho_save_bulk_edit_wholesale_price($product) {
    if (!isset($_REQUEST['change_wholesale_price']) || $_REQUEST['change_wholesale_price'] === '') {
        return;
    }

    $change_type = absint($_REQUEST['change_wholesale_price']);
    $value = isset($_REQUEST['_wholesale_price_bulk']) ? wc_format_decimal($_REQUEST['_wholesale_price_bulk']) : '';

    if ($value === '') {
        return;
    }

    $current_wholesale = floatval($product->get_meta('_wholesale_price'));
    $regular_price = floatval($product->get_regular_price());
    $new_wholesale = $current_wholesale;

    switch ($change_type) {
        case 1: // Change to
            $new_wholesale = $value;
            break;
        case 2: // Increase by
            $new_wholesale = $current_wholesale + $value;
            break;
        case 3: // Decrease by
            $new_wholesale = max(0, $current_wholesale - $value);
            break;
        case 4: // X% of regular price
            if ($regular_price > 0) {
                $new_wholesale = $regular_price * ($value / 100);
            }
            break;
    }

    $product->update_meta_data('_wholesale_price', wc_format_decimal($new_wholesale));
}
add_action('woocommerce_product_bulk_edit_save', 'ah_ho_save_bulk_edit_wholesale_price');


/**
 * Note: Wholesale settings are integrated into salesperson-settings.php
 * See WooCommerce > Salesperson Settings > Wholesale Pricing section
 */


/**
 * ============================================================================
 * SECTION 6: HELPER FUNCTIONS
 * ============================================================================
 */

/**
 * Get effective wholesale price for a product
 * Takes into account fallback settings
 *
 * @param int $product_id Product ID
 * @return float|false Effective wholesale price or false if blocked
 */
function ah_ho_get_effective_wholesale_price($product_id) {
    $wholesale_price = ah_ho_get_wholesale_price($product_id);

    if ($wholesale_price !== false) {
        return $wholesale_price;
    }

    // Get fallback behavior
    $fallback = get_option('ah_ho_wholesale_fallback', 'retail');
    $product = wc_get_product($product_id);

    if (!$product) {
        return false;
    }

    $retail_price = floatval($product->get_price());

    switch ($fallback) {
        case 'retail':
            return $retail_price;

        case 'discount':
            $discount = floatval(get_option('ah_ho_default_wholesale_discount', 0));
            if ($discount > 0) {
                return $retail_price * (1 - ($discount / 100));
            }
            return $retail_price;

        case 'block':
            return false;
    }

    return $retail_price;
}

/**
 * Check if wholesale pricing is active for an order
 *
 * @param WC_Order $order Order object
 * @return bool
 */
function ah_ho_order_has_wholesale_pricing($order) {
    foreach ($order->get_items() as $item) {
        if ($item->get_meta('_wholesale_price_applied') === 'yes') {
            return true;
        }
    }
    return false;
}

/**
 * Calculate total wholesale savings for an order
 *
 * @param WC_Order $order Order object
 * @return float Total savings amount
 */
function ah_ho_calculate_wholesale_savings($order) {
    $savings = 0;

    foreach ($order->get_items() as $item) {
        if ($item->get_meta('_wholesale_price_applied') === 'yes') {
            $wholesale_unit = floatval($item->get_meta('_wholesale_unit_price'));
            $product = $item->get_product();

            if ($product) {
                $retail_price = floatval($product->get_regular_price());
                $quantity = $item->get_quantity();
                $savings += ($retail_price - $wholesale_unit) * $quantity;
            }
        }
    }

    return $savings;
}


/**
 * ============================================================================
 * SECTION 7: ADMIN NOTICES & INDICATORS
 * ============================================================================
 */

/**
 * Add wholesale indicator to order list
 */
function ah_ho_add_wholesale_order_indicator($column, $post_id) {
    if ($column !== 'order_number') {
        return;
    }

    $order = wc_get_order($post_id);

    if (!$order) {
        return;
    }

    if (ah_ho_order_has_wholesale_pricing($order)) {
        echo ' <span class="ah-ho-wholesale-badge" style="background: #007cba; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-left: 5px;">B2B</span>';
    }
}
add_action('manage_shop_order_posts_custom_column', 'ah_ho_add_wholesale_order_indicator', 20, 2);
add_action('manage_woocommerce_page_wc-orders_custom_column', 'ah_ho_add_wholesale_order_indicator', 20, 2);

/**
 * Show wholesale summary in order details
 */
function ah_ho_show_wholesale_summary_in_order($order) {
    if (!ah_ho_order_has_wholesale_pricing($order)) {
        return;
    }

    $savings = ah_ho_calculate_wholesale_savings($order);
    ?>
    <div class="ah-ho-wholesale-summary" style="background: #e7f5ff; border-left: 4px solid #007cba; padding: 10px 15px; margin: 15px 0;">
        <h4 style="margin: 0 0 5px; color: #007cba;">
            <?php esc_html_e('B2B Wholesale Order', 'ah-ho-custom'); ?>
        </h4>
        <p style="margin: 0; color: #666;">
            <?php
            printf(
                esc_html__('This order uses wholesale pricing. Total savings vs retail: %s', 'ah-ho-custom'),
                wc_price($savings)
            );
            ?>
        </p>
    </div>
    <?php
}
add_action('woocommerce_admin_order_data_after_order_details', 'ah_ho_show_wholesale_summary_in_order');


/**
 * ============================================================================
 * SECTION 8: QUICK BULK ACTIONS (Stock Status & Wholesale)
 * ============================================================================
 * These actions appear in the bulk actions dropdown and execute immediately
 */

/**
 * Add custom bulk actions to product list
 */
add_filter('bulk_actions-edit-product', 'ah_ho_add_product_bulk_actions');

function ah_ho_add_product_bulk_actions($bulk_actions) {
    $bulk_actions['set_out_of_stock'] = __('Set to Out of Stock', 'ah-ho-custom');
    $bulk_actions['set_in_stock'] = __('Set to In Stock', 'ah-ho-custom');
    $bulk_actions['clear_wholesale_price'] = __('Clear Wholesale Price', 'ah-ho-custom');
    $bulk_actions['set_wholesale_modal'] = __('Set Wholesale Price...', 'ah-ho-custom');
    return $bulk_actions;
}

/**
 * Handle custom bulk actions
 */
add_filter('handle_bulk_actions-edit-product', 'ah_ho_handle_product_bulk_actions', 10, 3);

function ah_ho_handle_product_bulk_actions($redirect_to, $action, $post_ids) {
    $processed = 0;

    switch ($action) {
        case 'set_out_of_stock':
            foreach ($post_ids as $post_id) {
                $product = wc_get_product($post_id);
                if ($product) {
                    $product->set_stock_status('outofstock');
                    $product->save();
                    $processed++;
                }
            }
            $redirect_to = add_query_arg('ah_ho_stock_updated', $processed, $redirect_to);
            $redirect_to = add_query_arg('ah_ho_stock_status', 'outofstock', $redirect_to);
            break;

        case 'set_in_stock':
            foreach ($post_ids as $post_id) {
                $product = wc_get_product($post_id);
                if ($product) {
                    $product->set_stock_status('instock');
                    $product->save();
                    $processed++;
                }
            }
            $redirect_to = add_query_arg('ah_ho_stock_updated', $processed, $redirect_to);
            $redirect_to = add_query_arg('ah_ho_stock_status', 'instock', $redirect_to);
            break;

        case 'clear_wholesale_price':
            foreach ($post_ids as $post_id) {
                delete_post_meta($post_id, '_wholesale_price');
                $processed++;
            }
            $redirect_to = add_query_arg('ah_ho_wholesale_cleared', $processed, $redirect_to);
            break;

        case 'set_wholesale_modal':
            // Store selected product IDs in transient for the modal
            set_transient('ah_ho_bulk_wholesale_ids_' . get_current_user_id(), $post_ids, 300);
            $redirect_to = add_query_arg('ah_ho_show_wholesale_modal', '1', $redirect_to);
            break;
    }

    return $redirect_to;
}

/**
 * Show admin notices for bulk actions
 */
add_action('admin_notices', 'ah_ho_bulk_action_admin_notices');

function ah_ho_bulk_action_admin_notices() {
    // Stock status update notice
    if (!empty($_REQUEST['ah_ho_stock_updated'])) {
        $count = intval($_REQUEST['ah_ho_stock_updated']);
        $status = sanitize_text_field($_REQUEST['ah_ho_stock_status'] ?? '');
        $status_label = $status === 'outofstock' ? __('Out of Stock', 'ah-ho-custom') : __('In Stock', 'ah-ho-custom');

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            sprintf(
                _n(
                    '%d product set to %s.',
                    '%d products set to %s.',
                    $count,
                    'ah-ho-custom'
                ),
                $count,
                '<strong>' . esc_html($status_label) . '</strong>'
            )
        );
    }

    // Wholesale cleared notice
    if (!empty($_REQUEST['ah_ho_wholesale_cleared'])) {
        $count = intval($_REQUEST['ah_ho_wholesale_cleared']);

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            sprintf(
                _n(
                    'Wholesale price cleared from %d product.',
                    'Wholesale price cleared from %d products.',
                    $count,
                    'ah-ho-custom'
                ),
                $count
            )
        );
    }

    // Wholesale set notice
    if (!empty($_REQUEST['ah_ho_wholesale_set'])) {
        $count = intval($_REQUEST['ah_ho_wholesale_set']);
        $price = sanitize_text_field($_REQUEST['ah_ho_wholesale_price'] ?? '');

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            sprintf(
                _n(
                    'Wholesale price set to %s for %d product.',
                    'Wholesale price set to %s for %d products.',
                    $count,
                    'ah-ho-custom'
                ),
                wc_price($price),
                $count
            )
        );
    }
}

/**
 * Render wholesale price modal on products page
 */
add_action('admin_footer-edit.php', 'ah_ho_render_wholesale_modal');

function ah_ho_render_wholesale_modal() {
    global $typenow;

    if ($typenow !== 'product') {
        return;
    }

    // Check if we should show the modal
    $show_modal = isset($_GET['ah_ho_show_wholesale_modal']) && $_GET['ah_ho_show_wholesale_modal'] === '1';
    $product_ids = get_transient('ah_ho_bulk_wholesale_ids_' . get_current_user_id());

    if (!$show_modal || empty($product_ids)) {
        return;
    }

    // Clear the transient
    delete_transient('ah_ho_bulk_wholesale_ids_' . get_current_user_id());

    $product_count = count($product_ids);
    $product_ids_json = json_encode($product_ids);
    ?>
    <div id="ah-ho-wholesale-modal" class="ah-ho-modal-overlay" style="display: flex;">
        <div class="ah-ho-modal-content">
            <h2><?php _e('Set Wholesale Price', 'ah-ho-custom'); ?></h2>
            <p><?php printf(_n('Set wholesale price for %d selected product:', 'Set wholesale price for %d selected products:', $product_count, 'ah-ho-custom'), $product_count); ?></p>

            <form id="ah-ho-wholesale-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="ah_ho_bulk_set_wholesale">
                <input type="hidden" name="product_ids" value="<?php echo esc_attr($product_ids_json); ?>">
                <?php wp_nonce_field('ah_ho_bulk_wholesale', 'ah_ho_nonce'); ?>

                <div class="ah-ho-form-row">
                    <label for="ah_ho_wholesale_type"><?php _e('Price Type:', 'ah-ho-custom'); ?></label>
                    <select name="wholesale_type" id="ah_ho_wholesale_type">
                        <option value="fixed"><?php _e('Fixed Price', 'ah-ho-custom'); ?></option>
                        <option value="percent"><?php _e('% of Regular Price', 'ah-ho-custom'); ?></option>
                    </select>
                </div>

                <div class="ah-ho-form-row">
                    <label for="ah_ho_wholesale_value"><?php _e('Value:', 'ah-ho-custom'); ?></label>
                    <input type="number" name="wholesale_value" id="ah_ho_wholesale_value" step="0.01" min="0" required>
                    <span id="ah_ho_value_hint" class="description"><?php echo get_woocommerce_currency_symbol(); ?></span>
                </div>

                <div class="ah-ho-modal-buttons">
                    <button type="button" class="button" onclick="document.getElementById('ah-ho-wholesale-modal').style.display='none';">
                        <?php _e('Cancel', 'ah-ho-custom'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <?php _e('Set Wholesale Price', 'ah-ho-custom'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .ah-ho-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 100000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .ah-ho-modal-content {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
        }
        .ah-ho-modal-content h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .ah-ho-form-row {
            margin-bottom: 15px;
        }
        .ah-ho-form-row label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .ah-ho-form-row select,
        .ah-ho-form-row input[type="number"] {
            width: 100%;
            padding: 8px;
        }
        .ah-ho-modal-buttons {
            margin-top: 20px;
            text-align: right;
        }
        .ah-ho-modal-buttons .button {
            margin-left: 10px;
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            $('#ah_ho_wholesale_type').on('change', function() {
                if ($(this).val() === 'percent') {
                    $('#ah_ho_value_hint').text('%');
                    $('#ah_ho_wholesale_value').attr('max', '100');
                } else {
                    $('#ah_ho_value_hint').text('<?php echo get_woocommerce_currency_symbol(); ?>');
                    $('#ah_ho_wholesale_value').removeAttr('max');
                }
            });

            // Close modal on overlay click
            $('#ah-ho-wholesale-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });

            // Close on escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('#ah-ho-wholesale-modal').hide();
                }
            });
        });
    </script>
    <?php
}

/**
 * Handle bulk wholesale price form submission
 */
add_action('admin_post_ah_ho_bulk_set_wholesale', 'ah_ho_handle_bulk_wholesale_submit');

function ah_ho_handle_bulk_wholesale_submit() {
    // Verify nonce
    if (!isset($_POST['ah_ho_nonce']) || !wp_verify_nonce($_POST['ah_ho_nonce'], 'ah_ho_bulk_wholesale')) {
        wp_die(__('Security check failed', 'ah-ho-custom'));
    }

    // Check permissions
    if (!current_user_can('edit_products')) {
        wp_die(__('Permission denied', 'ah-ho-custom'));
    }

    $product_ids = json_decode(stripslashes($_POST['product_ids']), true);
    $type = sanitize_text_field($_POST['wholesale_type']);
    $value = floatval($_POST['wholesale_value']);

    if (empty($product_ids) || !is_array($product_ids)) {
        wp_redirect(admin_url('edit.php?post_type=product'));
        exit;
    }

    $processed = 0;
    $final_price = 0;

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            continue;
        }

        if ($type === 'percent') {
            // Calculate as percentage of regular price
            $regular_price = floatval($product->get_regular_price());
            if ($regular_price > 0) {
                $final_price = $regular_price * ($value / 100);
            } else {
                continue;
            }
        } else {
            // Fixed price
            $final_price = $value;
        }

        update_post_meta($product_id, '_wholesale_price', wc_format_decimal($final_price));
        $processed++;
    }

    // Redirect back with success message
    $redirect_url = add_query_arg(array(
        'post_type' => 'product',
        'ah_ho_wholesale_set' => $processed,
        'ah_ho_wholesale_price' => $final_price,
    ), admin_url('edit.php'));

    wp_redirect($redirect_url);
    exit;
}
