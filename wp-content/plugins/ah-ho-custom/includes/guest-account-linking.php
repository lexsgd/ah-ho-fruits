<?php
/**
 * Guest Account Linking - never leave a paying customer without a login
 *
 * Guest checkout is deliberately left on (Kelvin does not want more friction at
 * the till), and the Blocks checkout's "create an account" tickbox is optional
 * and starts unticked. The result: WooCommerce records the order against the
 * email, so the shopper shows up under Customers, but no wp_users row is ever
 * created. When she later tries to log in or reset her password the site tells
 * her the email is unknown - password reset only looks at wp_users.
 *
 * As of Aug 2026 that had happened to 32 of 46 guest emails, including repeat
 * buyers with 4-6 orders each.
 *
 * This file closes the gap server-side, after the order exists:
 *   - order email already belongs to a user  -> attach the order to that user
 *   - no user yet                            -> create a `customer` account and
 *                                               let WooCommerce email the
 *                                               set-password link
 *
 * Doing it after the fact (rather than pre-ticking the box) is deliberate: the
 * tickbox default lives in the Blocks front-end state, not in PHP - the
 * `woocommerce_create_account_default_checked` filter only reaches the *classic*
 * checkout template, which this store does not use. Hooking the order instead
 * covers Blocks, classic and the Store API alike.
 *
 * @package AhHoCustom
 * @since 1.7.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Marks an order whose account we have already reconciled, so a retried or
 * replayed checkout request cannot create a second account.
 */
const AH_HO_GUEST_LINK_META = '_ah_ho_guest_account_linked';

/**
 * Blocks / Store API checkout.
 */
add_action('woocommerce_store_api_checkout_order_processed', 'ah_ho_link_guest_order_to_account', 20, 1);

/**
 * Classic (shortcode) checkout - not currently the live checkout, but the
 * shortcode still works and express gateways can fall back to it.
 */
add_action('woocommerce_checkout_order_processed', 'ah_ho_link_guest_order_from_classic', 20, 3);

function ah_ho_link_guest_order_from_classic($order_id, $posted_data, $order = null) {
    if (!$order instanceof WC_Order) {
        $order = wc_get_order($order_id);
    }
    ah_ho_link_guest_order_to_account($order);
}

/**
 * Attach a guest order to a customer account, creating one if needed.
 *
 * @param WC_Order $order The order just placed.
 * @return void
 */
function ah_ho_link_guest_order_to_account($order) {
    if (!$order instanceof WC_Order) {
        return;
    }

    // Already a registered customer's order - nothing to do.
    if ($order->get_customer_id() > 0) {
        return;
    }

    // Idempotency: never run twice for the same order.
    if ($order->get_meta(AH_HO_GUEST_LINK_META)) {
        return;
    }

    $email = $order->get_billing_email();
    if (!$email || !is_email($email)) {
        return;
    }

    /**
     * Allow the auto-account behaviour to be skipped for a given order.
     *
     * @param bool     $enabled Whether to link/create an account.
     * @param WC_Order $order   The order being processed.
     */
    if (!apply_filters('ah_ho_auto_create_guest_account', true, $order)) {
        return;
    }

    $existing = get_user_by('email', $email);

    if ($existing) {
        $customer_id = (int) $existing->ID;
        $created     = false;
    } else {
        $customer_id = ah_ho_create_customer_for_order($order, $email);
        if (!$customer_id) {
            return; // Failure already logged; leave the order as a guest order.
        }
        $created = true;
    }

    $order->set_customer_id($customer_id);
    $order->update_meta_data(AH_HO_GUEST_LINK_META, $created ? 'created' : 'matched');
    $order->save();

    // Prefill My Account with what they just typed at checkout.
    ah_ho_sync_order_address_to_user($order, $customer_id);

    $order->add_order_note(
        $created
            ? sprintf(
                /* translators: %s: customer email */
                __('Customer account created automatically for %s and linked to this order. A set-password email was sent.', 'ah-ho-custom'),
                $email
            )
            : sprintf(
                /* translators: %s: customer email */
                __('Order linked to the existing customer account for %s.', 'ah-ho-custom'),
                $email
            )
    );
}

/**
 * Create a `customer` account for a guest order.
 *
 * Username and password are left blank on purpose: the store has
 * woocommerce_registration_generate_username and _generate_password both set to
 * "yes", so WooCommerce generates them and fires the "New account" email, which
 * carries the set-password link. That link is how the shopper gets in - we never
 * invent a password for her.
 *
 * @param WC_Order $order The order being processed.
 * @param string   $email Validated billing email.
 * @return int Customer ID, or 0 on failure.
 */
function ah_ho_create_customer_for_order($order, $email) {
    $customer_id = wc_create_new_customer(
        $email,
        '',
        '',
        array(
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
            'source'     => 'ah-ho-guest-checkout',
        )
    );

    if (is_wp_error($customer_id)) {
        $order->add_order_note(
            sprintf(
                /* translators: 1: customer email, 2: error message */
                __('Could not auto-create a customer account for %1$s: %2$s', 'ah-ho-custom'),
                $email,
                $customer_id->get_error_message()
            )
        );
        return 0;
    }

    return (int) $customer_id;
}

/**
 * Copy the order's billing and shipping address onto the user profile.
 *
 * Without this the customer logs in for the first time to an empty address book
 * and has to retype everything she just entered at checkout.
 *
 * @param WC_Order $order       The order being processed.
 * @param int      $customer_id The account to populate.
 * @return void
 */
function ah_ho_sync_order_address_to_user($order, $customer_id) {
    $customer = new WC_Customer($customer_id);

    foreach (array('first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'email', 'phone') as $field) {
        $getter = "get_billing_{$field}";
        $setter = "set_billing_{$field}";
        if (method_exists($order, $getter) && method_exists($customer, $setter)) {
            $value = $order->{$getter}();
            if ('' !== $value && null !== $value) {
                $customer->{$setter}($value);
            }
        }
    }

    foreach (array('first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country') as $field) {
        $getter = "get_shipping_{$field}";
        $setter = "set_shipping_{$field}";
        if (method_exists($order, $getter) && method_exists($customer, $setter)) {
            $value = $order->{$getter}();
            if ('' !== $value && null !== $value) {
                $customer->{$setter}($value);
            }
        }
    }

    $customer->save();
}
