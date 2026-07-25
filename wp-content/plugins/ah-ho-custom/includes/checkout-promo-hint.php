<?php
/**
 * ============================================================================
 * CHECKOUT PROMO-CODE HINT
 * ============================================================================
 *
 * Problem this solves
 * -------------------
 * The Google Pay / Link "express" buttons sit at the TOP of the checkout page
 * and their wallet pop-ups have NO coupon field. Shoppers were clicking them
 * before scrolling down to the "Add coupons" box, paying full price, then
 * reporting "I can't use the discount code" (confirmed 2026-07-16).
 *
 * What this does
 * --------------
 * Surfaces a prominent promo prompt ABOVE the express buttons that points
 * shoppers to the "Add coupons" box first. Express checkout stays enabled
 * (it lifts conversion) — we just make the discount path more visible.
 *
 * Uses the same defensive DOM-injection pattern already used on this site for
 * the PayNow tip banner and the Blocks delivery-date field.
 *
 * @package ah-ho-custom
 * @since 1.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_footer', 'ah_ho_checkout_promo_hint', 20);

function ah_ho_checkout_promo_hint() {
    // Only on the checkout page, never on the order-received / thank-you page.
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }
    if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
        return;
    }
    ?>
    <style>
        #ah-ho-promo-hint {
            margin: 0 0 16px 0;
            padding: 12px 16px;
            background: #f3faef;
            border: 1px solid #6abd45;
            border-radius: 8px;
            color: #33691e;
            font-size: 14px;
            line-height: 1.5;
        }
        #ah-ho-promo-hint strong { color: #2e7d1e; }
    </style>
    <script>
    (function() {
        function insertHint() {
            if (document.getElementById('ah-ho-promo-hint')) {
                return; // already inserted
            }

            // Prefer to sit directly above the express-payment area.
            var target = document.querySelector(
                '.wp-block-woocommerce-checkout-express-payment-block, ' +
                '.wc-block-checkout__express-payment, ' +
                '.wc-block-components-express-payment'
            );
            // Fallback: top of the checkout form.
            if (!target) {
                target = document.querySelector('.wc-block-checkout, form.checkout');
            }
            if (!target || !target.parentNode) {
                return;
            }

            var hint = document.createElement('div');
            hint.id = 'ah-ho-promo-hint';
            hint.innerHTML = '🏷️ <strong>Have a promo code?</strong> ' +
                'Add it in the “Add coupons” box before you pay — ' +
                'discount codes can’t be applied through the Google Pay / Link quick-pay buttons.';

            target.parentNode.insertBefore(hint, target);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', insertHint);
        } else {
            insertHint();
        }

        // Blocks checkout hydrates late — retry on DOM changes for a short time.
        var observer = new MutationObserver(function() {
            insertHint();
        });
        observer.observe(document.body, { childList: true, subtree: true });
        setTimeout(function() {
            observer.disconnect();
        }, 10000);
    })();
    </script>
    <?php
}
