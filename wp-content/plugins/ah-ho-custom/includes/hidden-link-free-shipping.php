<?php
/**
 * Hidden Link Free Shipping - Apply free shipping to customers from hidden/private links
 *
 * Fixes Issue #4796: Hidden link for regular customers not applying shipping-free status
 *
 * Detects customers who arrived via a "hidden link" (with ?hidden_link=true or utm_source=hidden_link)
 * and automatically applies free shipping, even below the $60 threshold.
 *
 * @package AhHoCustom
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mark customer as arriving from hidden link
 * 
 * Sets a cookie when customer visits a link with ?hidden_link=true or utm_source=hidden_link
 */
add_action('wp_footer', 'ah_ho_check_hidden_link_param', 1);

function ah_ho_check_hidden_link_param() {
    // Check query params for hidden link indicator
    $is_hidden_link = isset($_GET['hidden_link']) && $_GET['hidden_link'] === 'true';
    $is_hidden_utm = isset($_GET['utm_source']) && strpos($_GET['utm_source'], 'hidden') !== false;
    
    if ($is_hidden_link || $is_hidden_utm) {
        // Set cookie for 30 days
        if (!isset($_COOKIE['ah_ho_hidden_link_customer'])) {
            setcookie(
                'ah_ho_hidden_link_customer',
                '1',
                time() + (30 * 24 * 60 * 60), // 30 days
                COOKIEPATH,
                COOKIE_DOMAIN
            );
            $_COOKIE['ah_ho_hidden_link_customer'] = '1';
        }
    }
}

/**
 * Apply free shipping to hidden link customers
 * 
 * This runs before the regular free shipping threshold check,
 * so hidden link customers always get free shipping.
 */
add_filter('woocommerce_package_rates', 'ah_ho_hidden_link_free_shipping', 5, 2);

function ah_ho_hidden_link_free_shipping($rates, $package) {
    if (!WC()->cart) {
        return $rates;
    }

    // Check if customer arrived from hidden link
    $is_hidden_link_customer = isset($_COOKIE['ah_ho_hidden_link_customer']) && $_COOKIE['ah_ho_hidden_link_customer'] === '1';
    
    // Also check current user meta if logged in
    if (!$is_hidden_link_customer && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $user_hidden_link = get_user_meta($current_user->ID, '_ah_ho_hidden_link_customer', true);
        $is_hidden_link_customer = $user_hidden_link === '1';
    }
    
    if ($is_hidden_link_customer) {
        $free_rates = array();
        $other_rates = array();
        
        foreach ($rates as $rate_id => $rate) {
            if ($rate->method_id === 'free_shipping') {
                // Keep and prioritize free shipping
                $free_rates[$rate_id] = $rate;
            } elseif (
                stripos($rate->label, 'express') !== false ||
                $rate->method_id === 'local_pickup'
            ) {
                // Keep express and pickup as alternatives
                $other_rates[$rate_id] = $rate;
            }
            // Remove all paid shipping for hidden link customers
        }
        
        // Free shipping first, then alternatives
        if (!empty($free_rates)) {
            $rates = $free_rates + $other_rates;
        }
    }
    
    return $rates;
}

/**
 * Save hidden link status to customer account on checkout
 * 
 * When a hidden link customer completes checkout, save the status
 * to their account so future orders auto-qualify for free shipping.
 */
add_action('woocommerce_checkout_order_created', 'ah_ho_save_hidden_link_to_customer', 10, 1);

function ah_ho_save_hidden_link_to_customer($order) {
    $is_hidden_link_customer = isset($_COOKIE['ah_ho_hidden_link_customer']) && $_COOKIE['ah_ho_hidden_link_customer'] === '1';
    
    if ($is_hidden_link_customer) {
        $customer_id = $order->get_customer_id();
        
        if ($customer_id > 0) {
            // Save to user meta for future orders
            update_user_meta($customer_id, '_ah_ho_hidden_link_customer', '1');
            
            // Add note to order
            $order->add_order_note(
                __('Customer arrived via hidden link - free shipping applied', 'ah-ho-fruits'),
                0
            );
        }
    }
}

/**
 * Verify free shipping was applied on order review
 * 
 * Log any cases where a hidden link customer didn't get free shipping
 * (helps debug edge cases like shipping to restricted zones)
 */
add_action('woocommerce_order_status_processing', 'ah_ho_verify_hidden_link_shipping', 10, 1);

function ah_ho_verify_hidden_link_shipping($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return;
    }
    
    $customer_id = $order->get_customer_id();
    $is_hidden_link = $customer_id > 0 ? 
        get_user_meta($customer_id, '_ah_ho_hidden_link_customer', true) === '1' : false;
    
    if ($is_hidden_link) {
        $shipping_total = (float) $order->get_shipping_total();
        
        // Log warning if hidden link customer was charged shipping
        if ($shipping_total > 0) {
            error_log(sprintf(
                'Ah Ho Fruits: WARNING - Hidden link customer (ID %d) was charged $%.2f shipping on order #%d. Check shipping zone restrictions.',
                $customer_id,
                $shipping_total,
                $order_id
            ));
            
            // Add admin note
            $order->add_order_note(
                sprintf(
                    __('⚠️ Hidden link customer was charged $%.2f shipping - may be due to restricted zone or method availability', 'ah-ho-fruits'),
                    $shipping_total
                )
            );
        }
    }
}
