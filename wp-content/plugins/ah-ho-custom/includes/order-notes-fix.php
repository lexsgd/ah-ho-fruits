<?php
/**
 * Order Notes Fix - Ensure special requests are always captured at checkout
 *
 * Fixes Issue #4783: Special requests field not appearing in order
 *
 * @package AhHoCustom
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add order notes field to checkout page (fallback if product page field was missed)
 *
 * This ensures customers can still add special requests even if:
 * - The product page field wasn't displayed
 * - The product doesn't have the addon enabled
 * - The customer forgets to fill it out on the product page
 */
add_action('woocommerce_before_order_notes', 'ah_ho_add_checkout_notes_field', 5);

function ah_ho_add_checkout_notes_field() {
    woocommerce_form_field(
        'ah_ho_checkout_special_notes',
        array(
            'type'        => 'textarea',
            'label'       => __('Special Requests / Preferences / Allergies', 'ah-ho-fruits'),
            'placeholder' => __('E.g., "No avocados", "Extra salt", "Allergic to nuts"', 'ah-ho-fruits'),
            'required'    => false,
            'class'       => array('form-row-wide'),
            'custom_attributes' => array(
                'maxlength' => '500',
                'rows'      => '3',
            ),
        ),
        WC()->checkout->get_value('ah_ho_checkout_special_notes')
    );
}

/**
 * Save checkout special notes to order meta when processing checkout
 */
add_action('woocommerce_checkout_create_order', 'ah_ho_save_checkout_notes_to_order', 10, 2);

function ah_ho_save_checkout_notes_to_order($order, $data) {
    $special_notes = isset($_POST['post_data']) ? 
        array_filter(explode('&', $_POST['post_data']), function($pair) {
            return strpos($pair, 'ah_ho_checkout_special_notes=') === 0;
        }) : array();
    
    if (!empty($special_notes)) {
        $param = array_shift($special_notes);
        list($key, $value) = explode('=', $param);
        $notes = urldecode($value);
        
        if (!empty(trim($notes))) {
            // Add to order notes
            $order->add_order_note(
                sprintf(
                    __('Special Requests from Checkout: %s', 'ah-ho-fruits'),
                    sanitize_textarea_field($notes)
                ),
                0
            );
            
            // Also save to order meta for easy retrieval
            $order->update_meta_data('_ah_ho_special_requests', sanitize_textarea_field($notes));
        }
    }
}

/**
 * Display special requests in order admin if they exist
 */
add_action('woocommerce_admin_order_data_after_shipping_address', 'ah_ho_display_special_requests_admin', 10, 1);

function ah_ho_display_special_requests_admin($order) {
    $special_requests = $order->get_meta('_ah_ho_special_requests');
    
    if (!empty($special_requests)) {
        echo '<div style="margin-top: 15px; padding: 12px; background: #e8f5e9; border: 2px solid #2E7D32; border-radius: 3px;">';
        echo '<strong style="color: #1B5E20; font-size: 14px;">📝 Special Requests:</strong>';
        echo '<div style="margin-top: 8px; font-style: italic; white-space: pre-wrap; color: #333;">';
        echo esc_html($special_requests);
        echo '</div>';
        echo '</div>';
    }
}
