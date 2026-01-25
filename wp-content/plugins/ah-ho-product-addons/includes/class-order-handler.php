<?php
/**
 * Order Handler - Save addon data to order meta
 */

defined( 'ABSPATH' ) || exit;

class AH_Ho_Addons_Order_Handler {

    public function __construct() {
        // Save to order line item meta
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'save_to_order' ], 10, 4 );

        // Display enhanced view in order admin
        add_action( 'woocommerce_after_order_itemmeta', [ $this, 'display_in_admin' ], 10, 3 );
    }

    /**
     * Save addon data to order line item
     */
    public function save_to_order( $item, $cart_item_key, $values, $order ) {
        // Save Product Notes
        if ( ! empty( $values['product_notes'] ) ) {
            $item->add_meta_data(
                __( 'Special Requests', 'ah-ho-fruits' ),
                $values['product_notes'],
                true
            );
        }

        // Save Gift indicator
        if ( isset( $values['is_gift'] ) && $values['is_gift'] === 'yes' ) {
            $item->add_meta_data(
                __( 'Gift', 'ah-ho-fruits' ),
                __( 'Yes', 'ah-ho-fruits' ),
                true
            );
        }

        // Save Gift Message
        if ( ! empty( $values['gift_message'] ) ) {
            $item->add_meta_data(
                __( 'Gift Message', 'ah-ho-fruits' ),
                $values['gift_message'],
                true
            );
        }
    }

    /**
     * Display enhanced indicators in admin order view
     */
    public function display_in_admin( $item_id, $item, $product ) {
        // Get addon data
        $product_notes = wc_get_order_item_meta( $item_id, __( 'Special Requests', 'ah-ho-fruits' ), true );
        $is_gift = wc_get_order_item_meta( $item_id, __( 'Gift', 'ah-ho-fruits' ), true );
        $gift_message = wc_get_order_item_meta( $item_id, __( 'Gift Message', 'ah-ho-fruits' ), true );

        // Display Product Notes
        if ( $product_notes ) {
            echo '<div class="ah-ho-admin-addon" style="margin-top: 10px; padding: 10px; background: #e8f5e9; border-left: 3px solid #2E7D32; font-size: 12px;">';
            echo '<strong>📝 ' . __( 'Special Requests:', 'ah-ho-fruits' ) . '</strong>';
            echo '<div style="margin-top: 5px; font-style: italic; white-space: pre-wrap;">';
            echo esc_html( $product_notes );
            echo '</div>';
            echo '</div>';
        }

        // Display Gift info
        if ( $is_gift === __( 'Yes', 'ah-ho-fruits' ) ) {
            echo '<div class="ah-ho-admin-addon" style="margin-top: 10px; padding: 10px; background: #fff3cd; border-left: 3px solid #ff6f00; font-size: 12px;">';
            echo '<strong>🎁 ' . __( 'GIFT ITEM', 'ah-ho-fruits' ) . '</strong>';

            if ( $gift_message ) {
                echo '<div style="margin-top: 5px; font-style: italic; white-space: pre-wrap;">';
                echo __( 'Message:', 'ah-ho-fruits' ) . ' "' . esc_html( $gift_message ) . '"';
                echo '</div>';
            }

            echo '<div style="margin-top: 5px; color: #856404; font-size: 11px;">';
            echo '⚠️ ' . __( 'Remember to print gift card for delivery', 'ah-ho-fruits' );
            echo '</div>';
            echo '</div>';
        }
    }
}
