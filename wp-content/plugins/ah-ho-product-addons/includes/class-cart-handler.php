<?php
/**
 * Cart Handler - Add to cart integration and validation
 */

defined( 'ABSPATH' ) || exit;

class AH_Ho_Addons_Cart_Handler {

    public function __construct() {
        // Validate before adding to cart
        add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_addons' ], 10, 3 );

        // Add data to cart item
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_to_cart_data' ], 10, 2 );

        // Display in cart
        add_filter( 'woocommerce_get_item_data', [ $this, 'display_in_cart' ], 10, 2 );

        // Auto-add linked product addon after main product is added to cart
        add_action( 'woocommerce_add_to_cart', [ $this, 'maybe_add_addon_product' ], 10, 6 );
    }

    /**
     * Validate product notes and gift message.
     *
     * Reads `wc_ahho_*` POST keys (primary) with fallback to the legacy
     * unprefixed keys. The `wc_` prefix is required so Stripe Express
     * Checkout (Apple Pay / Google Pay) forwards these fields — its JS
     * filters form inputs to names matching `^(addon-|wc_)`.
     */
    public function validate_addons( $passed, $product_id, $qty ) {
        $posted_notes = $_POST['wc_ahho_product_notes'] ?? $_POST['product_notes'] ?? '';
        $posted_is_gift = $_POST['wc_ahho_is_gift'] ?? $_POST['is_gift'] ?? '';
        $posted_gift_message = $_POST['wc_ahho_gift_message'] ?? $_POST['gift_message'] ?? '';

        // Validate Product Notes
        $notes_enabled = get_post_meta( $product_id, '_enable_product_notes', true ) === 'yes';
        $notes_required = get_post_meta( $product_id, '_product_notes_required', true ) === 'yes';

        if ( $notes_enabled && $notes_required ) {
            if ( empty( trim( $posted_notes ) ) ) {
                wc_add_notice(
                    __( 'Please enter your special requests, preferences, or allergies.', 'ah-ho-fruits' ),
                    'error'
                );
                $passed = false;
            }
        }

        // Validate Gift Message
        if ( $posted_is_gift === 'yes' ) {
            $gift_required = get_post_meta( $product_id, '_gift_message_required', true ) === 'yes';

            if ( $gift_required && empty( trim( $posted_gift_message ) ) ) {
                wc_add_notice(
                    __( 'Please enter a gift message.', 'ah-ho-fruits' ),
                    'error'
                );
                $passed = false;
            }
        }

        return $passed;
    }

    /**
     * Add addon data to cart item.
     *
     * Reads `wc_ahho_*` POST keys (primary) with fallback to the legacy
     * unprefixed keys. See validate_addons() for why the `wc_` prefix matters.
     */
    public function add_to_cart_data( $cart_item_data, $product_id ) {
        $posted_notes = $_POST['wc_ahho_product_notes'] ?? $_POST['product_notes'] ?? '';
        $posted_is_gift = $_POST['wc_ahho_is_gift'] ?? $_POST['is_gift'] ?? '';
        $posted_gift_message = $_POST['wc_ahho_gift_message'] ?? $_POST['gift_message'] ?? '';
        $posted_addon = $_POST['wc_ahho_add_product_addon'] ?? $_POST['add_product_addon'] ?? 0;

        // Add Product Notes
        if ( ! empty( $posted_notes ) ) {
            $notes = sanitize_textarea_field( $posted_notes );
            $notes_char_limit = get_post_meta( $product_id, '_product_notes_char_limit', true ) ?: 300;

            // Truncate if exceeds limit
            if ( mb_strlen( $notes ) > $notes_char_limit ) {
                $notes = mb_substr( $notes, 0, $notes_char_limit );
            }

            $cart_item_data['product_notes'] = $notes;
        }

        // Add Gift Data
        if ( $posted_is_gift === 'yes' ) {
            $cart_item_data['is_gift'] = 'yes';

            if ( ! empty( $posted_gift_message ) ) {
                $message = sanitize_textarea_field( $posted_gift_message );
                $gift_char_limit = get_post_meta( $product_id, '_gift_message_char_limit', true ) ?: 250;

                // Truncate if exceeds limit
                if ( mb_strlen( $message ) > $gift_char_limit ) {
                    $message = mb_substr( $message, 0, $gift_char_limit );
                }

                $cart_item_data['gift_message'] = $message;
            }
        }

        // Track if product addon was requested
        if ( absint( $posted_addon ) > 0 ) {
            $cart_item_data['product_addon_id'] = absint( $posted_addon );
        }

        // Add unique key if any custom data exists (prevent cart merging)
        if ( isset( $cart_item_data['product_notes'] )
            || isset( $cart_item_data['is_gift'] )
            || isset( $cart_item_data['gift_message'] )
            || isset( $cart_item_data['product_addon_id'] )
        ) {
            $cart_item_data['unique_addon_key'] = md5( microtime() . rand() );
        }

        return $cart_item_data;
    }

    /**
     * Auto-add the linked addon product to cart after main product.
     *
     * Uses a static re-entrancy guard to prevent infinite recursion:
     * calling WC()->cart->add_to_cart() from within the woocommerce_add_to_cart
     * hook fires the same hook again for the addon product. Without the guard
     * that second call would try to add yet another addon, and so on.
     */
    public function maybe_add_addon_product( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        if ( empty( $cart_item_data['product_addon_id'] ) ) {
            return;
        }

        // Re-entrancy guard: do not process if we are already inside this method.
        static $is_adding_addon = false;
        if ( $is_adding_addon ) {
            return;
        }

        $addon_product_id = $cart_item_data['product_addon_id'];
        $addon_product = wc_get_product( $addon_product_id );

        if ( ! $addon_product || ! $addon_product->is_in_stock() ) {
            return;
        }

        // Set guard before adding the addon product to cart.
        $is_adding_addon = true;

        WC()->cart->add_to_cart( $addon_product_id, $quantity, 0, [], [
            'addon_for_product' => $product_id,
            'addon_for_key'     => $cart_item_key,
            'unique_addon_key'  => md5( microtime() . rand() ),
        ] );

        // Clear guard after the nested add_to_cart completes.
        $is_adding_addon = false;
    }

    /**
     * Display addon data in cart
     */
    public function display_in_cart( $item_data, $cart_item ) {
        // Display Product Notes
        if ( ! empty( $cart_item['product_notes'] ) ) {
            $item_data[] = [
                'name'  => __( 'Special Requests', 'ah-ho-fruits' ),
                'value' => nl2br( esc_html( $cart_item['product_notes'] ) ),
            ];
        }

        // Display Gift indicator
        if ( isset( $cart_item['is_gift'] ) && $cart_item['is_gift'] === 'yes' ) {
            $item_data[] = [
                'name'  => __( 'Gift', 'ah-ho-fruits' ),
                'value' => '🎁 ' . __( 'Yes', 'ah-ho-fruits' ),
            ];
        }

        // Display Gift Message
        if ( ! empty( $cart_item['gift_message'] ) ) {
            $item_data[] = [
                'name'  => __( 'Gift Message', 'ah-ho-fruits' ),
                'value' => nl2br( esc_html( $cart_item['gift_message'] ) ),
            ];
        }

        // Display addon-for indicator
        if ( ! empty( $cart_item['addon_for_product'] ) ) {
            $parent_product = wc_get_product( $cart_item['addon_for_product'] );
            if ( $parent_product ) {
                $item_data[] = [
                    'name'  => __( 'Add-on for', 'ah-ho-fruits' ),
                    'value' => esc_html( $parent_product->get_name() ),
                ];
            }
        }

        return $item_data;
    }
}
