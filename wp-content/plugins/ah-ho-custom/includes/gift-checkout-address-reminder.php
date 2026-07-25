<?php
/**
 * ============================================================================
 * GIFT CHECKOUT — DELIVERY ADDRESS REMINDER
 * ============================================================================
 *
 * Problem this solves
 * --------------------
 * On gift orders, the recipient's delivery address sometimes never gets
 * entered on the order — the buyer pays with their own saved address, then
 * separately messages the correct delivery address after paying. Confirmed
 * on order #5192 (2026-07-22): buyer paid with his own address, then sent
 * the recipient's address over WhatsApp 3 minutes later.
 *
 * What this does
 * ---------------
 * Shows a reminder banner above the "Place Order" button, but ONLY when the
 * cart contains at least one item marked as a gift (`is_gift` cart item
 * data, set by ah-ho-product-addons). Non-gift checkouts are unaffected.
 *
 * Uses the same defensive DOM-injection pattern already used on this site
 * for the checkout promo-code hint and the Blocks delivery-date field.
 *
 * @package ah-ho-custom
 * @since 1.8.1
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_footer', 'ah_ho_gift_checkout_address_reminder', 20);

function ah_ho_gift_checkout_address_reminder() {
    // Only on the checkout page, never on the order-received / thank-you page.
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }
    if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
        return;
    }

    // Only show when the cart actually contains a gift item.
    if (!function_exists('WC') || !WC()->cart) {
        return;
    }

    $has_gift = false;
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (isset($cart_item['is_gift']) && $cart_item['is_gift'] === 'yes') {
            $has_gift = true;
            break;
        }
    }
    if (!$has_gift) {
        return;
    }
    ?>
    <style>
        #ah-ho-gift-address-reminder {
            margin: 0 0 16px 0;
            padding: 12px 16px;
            background: #fff8e1;
            border: 1px solid #f0b429;
            border-radius: 8px;
            color: #7a4f01;
            font-size: 14px;
            line-height: 1.5;
        }
        #ah-ho-gift-address-reminder strong { color: #5c3c00; }
    </style>
    <script>
    (function() {
        function insertReminder() {
            if (document.getElementById('ah-ho-gift-address-reminder')) {
                return; // already inserted
            }

            // Prefer to sit directly above the Place Order / payment button.
            var target = document.querySelector(
                '.wc-block-checkout__actions, ' +
                '.wp-block-woocommerce-checkout-actions-block, ' +
                '.wc-block-components-checkout-place-order-button'
            );
            if (target && target.closest) {
                var wrapper = target.closest('.wc-block-checkout__actions, .wp-block-woocommerce-checkout-actions-block') || target;
                target = wrapper;
            }
            // Fallback: top of the checkout form.
            if (!target) {
                target = document.querySelector('.wc-block-checkout, form.checkout');
            }
            if (!target || !target.parentNode) {
                return;
            }

            var reminder = document.createElement('div');
            reminder.id = 'ah-ho-gift-address-reminder';
            reminder.innerHTML = '🎁 <strong>Sending this as a gift?</strong> ' +
                'Please double-check the delivery address before paying.<br>' +
                'Need to change it after payment? WhatsApp us at 8013 8128.';

            target.parentNode.insertBefore(reminder, target);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', insertReminder);
        } else {
            insertReminder();
        }

        // Blocks checkout hydrates late — retry on DOM changes for a short time.
        var observer = new MutationObserver(function() {
            insertReminder();
        });
        observer.observe(document.body, { childList: true, subtree: true });
        setTimeout(function() {
            observer.disconnect();
        }, 10000);
    })();
    </script>
    <?php
}
