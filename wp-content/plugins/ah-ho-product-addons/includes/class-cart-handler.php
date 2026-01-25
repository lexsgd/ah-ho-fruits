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
    }

    /**
     * Validate product notes and gift message
     */
    public function validate_addons( $passed, $product_id, $qty ) {
        // Validate Product Notes
        $notes_enabled = get_post_meta( $product_id, '_enable_product_notes', true ) === 'yes';
        $notes_required = get_post_meta( $product_id, '_product_notes_required', true ) === 'yes';

        if ( $notes_enabled && $notes_required ) {
            if ( empty( trim( $_POST['product_notes'] ?? '' ) ) ) {
                wc_add_notice(
                    __( 'Please enter your special requests, preferences, or allergies.', 'ah-ho-fruits' ),
                    'error'
                );
                $passed = false;
            }
        }

        // Validate Gift Message
        if ( isset( $_POST['is_gift'] ) && $_POST['is_gift'] === 'yes' ) {
            $gift_required = get_post_meta( $product_id, '_gift_message_required', true ) === 'yes';

            if ( $gift_required && empty( trim( $_POST['gift_message'] ?? '' ) ) ) {
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
     * Add addon data to cart item
     */
    public function add_to_cart_data( $cart_item_data, $product_id ) {
        // Add Product Notes
        if ( ! empty( $_POST['product_notes'] ) ) {
            $notes = sanitize_textarea_field( $_POST['product_notes'] );
            $notes_char_limit = get_post_meta( $product_id, '_product_notes_char_limit', true ) ?: 300;

            // Truncate if exceeds limit
            if ( mb_strlen( $notes ) > $notes_char_limit ) {
                $notes = mb_substr( $notes, 0, $notes_char_limit );
            }

            $cart_item_data['product_notes'] = $notes;
        }

        // Add Gift Data
        if ( isset( $_POST['is_gift'] ) && $_POST['is_gift'] === 'yes' ) {
            $cart_item_data['is_gift'] = 'yes';

            if ( ! empty( $_POST['gift_message'] ) ) {
                $message = sanitize_textarea_field( $_POST['gift_message'] );
                $gift_char_limit = get_post_meta( $product_id, '_gift_message_char_limit', true ) ?: 250;

                // Truncate if exceeds limit
                if ( mb_strlen( $message ) > $gift_char_limit ) {
                    $message = mb_substr( $message, 0, $gift_char_limit );
                }

                $cart_item_data['gift_message'] = $message;
            }
        }

        // Add unique key if any custom data exists (prevent cart merging)
        if ( isset( $cart_item_data['product_notes'] )
            || isset( $cart_item_data['is_gift'] )
            || isset( $cart_item_data['gift_message'] )
        ) {
            $cart_item_data['unique_addon_key'] = md5( microtime() . rand() );
        }

        return $cart_item_data;
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

        return $item_data;
    }
}
