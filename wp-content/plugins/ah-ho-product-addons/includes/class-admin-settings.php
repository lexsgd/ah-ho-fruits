<?php
/**
 * Admin Settings - Product-level configuration for Gift Message and Product Notes
 */

defined( 'ABSPATH' ) || exit;

class AH_Ho_Addons_Admin_Settings {

    public function __construct() {
        // Add fields to product data panel
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_product_fields' ] );

        // Save product meta
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_fields' ] );
    }

    /**
     * Add settings fields in General tab of product data
     */
    public function add_product_fields() {
        global $post;

        echo '<div class="options_group show_if_simple show_if_variable">';
        echo '<h4 style="padding: 10px 12px; border-bottom: 1px solid #eee; margin: 0;">'
            . __( 'Product Add-ons', 'ah-ho-fruits' )
            . '</h4>';

        // ===== PRODUCT NOTES SECTION =====
        echo '<div style="padding: 10px 12px; background: #f9f9f9; margin: 10px 0;">';
        echo '<h5 style="margin: 0 0 10px 0; color: #2E7D32;">📝 '
            . __( 'Product Notes/Remarks', 'ah-ho-fruits' )
            . '</h5>';

        // Enable product notes
        woocommerce_wp_checkbox([
            'id'          => '_enable_product_notes',
            'label'       => __( 'Enable Product Notes', 'ah-ho-fruits' ),
            'description' => __( 'Allow customers to add preferences/allergies/special requests', 'ah-ho-fruits' ),
            'desc_tip'    => true,
        ]);

        // Notes label
        woocommerce_wp_text_input([
            'id'          => '_product_notes_label',
            'label'       => __( 'Field Label', 'ah-ho-fruits' ),
            'placeholder' => __( 'Special Requests', 'ah-ho-fruits' ),
            'description' => __( 'Custom label for the notes field', 'ah-ho-fruits' ),
            'desc_tip'    => true,
        ]);

        // Notes placeholder
        woocommerce_wp_text_input([
            'id'          => '_product_notes_placeholder',
            'label'       => __( 'Placeholder Text', 'ah-ho-fruits' ),
            'placeholder' => __( 'E.g., "More strawberries" or "No bananas - allergic"', 'ah-ho-fruits' ),
            'description' => __( 'Hint text shown in the notes field', 'ah-ho-fruits' ),
            'desc_tip'    => true,
        ]);

        // Notes character limit
        woocommerce_wp_text_input([
            'id'          => '_product_notes_char_limit',
            'label'       => __( 'Character Limit', 'ah-ho-fruits' ),
            'placeholder' => '300',
            'description' => __( 'Maximum characters (default: 300)', 'ah-ho-fruits' ),
            'type'        => 'number',
            'custom_attributes' => [ 'min' => '50', 'max' => '1000' ],
            'desc_tip'    => true,
        ]);

        // Notes required
        woocommerce_wp_checkbox([
            'id'          => '_product_notes_required',
            'label'       => __( 'Make Required', 'ah-ho-fruits' ),
            'description' => __( 'Customer must fill in notes to add to cart', 'ah-ho-fruits' ),
            'desc_tip'    => true,
        ]);

        echo '</div>';

        // ===== GIFT MESSAGE SECTION =====
        echo '<div style="padding: 10px 12px; background: #fff3cd; margin: 10px 0;">';
        echo '<h5 style="margin: 0 0 10px 0; color: #ff6f00;">🎁 '
            . __( 'Gift Message', 'ah-ho-fruits' )
            . '</h5>';

        // Enable gift message
        woocommerce_wp_checkbox([
            'id'          => '_enable_gift_message',
            'label'       => __( 'Enable Gift Message', 'ah-ho-fruits' ),
            'description' => __( 'Allow customers to mark this as a gift with a message', 'ah-ho-fruits' ),
            'desc_tip'    => true,
        ]);

        // Gift placeholder
        woocommerce_wp_text_input([
            'id'          => '_gift_message_placeholder',
            'label'       => __( 'Placeholder Text', 'ah-ho-fruits' ),
            'placeholder' => __( 'Enter your heartfelt message here...', 'ah-ho-fruits' ),
            'description' => __( 'Hint text for gift message field', 'ah-ho-fruits' ),
            'desc_tip'    => true,
        ]);

        // Gift character limit
        woocommerce_wp_text_input([
            'id'          => '_gift_message_char_limit',
            'label'       => __( 'Character Limit', 'ah-ho-fruits' ),
            'placeholder' => '250',
            'description' => __( 'Maximum characters (default: 250)', 'ah-ho-fruits' ),
            'type'        => 'number',
            'custom_attributes' => [ 'min' => '50', 'max' => '500' ],
            'desc_tip'    => true,
        ]);

        // Gift required
        woocommerce_wp_checkbox([
            'id'          => '_gift_message_required',
            'label'       => __( 'Require Message', 'ah-ho-fruits' ),
            'description' => __( 'Make message required when "This is a gift" is checked', 'ah-ho-fruits' ),
            'desc_tip'    => true,
        ]);

        echo '</div>';
        echo '</div>';
    }

    /**
     * Save product meta data
     */
    public function save_product_fields( $post_id ) {
        // Save Product Notes settings
        $enable_notes = isset( $_POST['_enable_product_notes'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_enable_product_notes', $enable_notes );

        $notes_label = isset( $_POST['_product_notes_label'] )
            ? sanitize_text_field( $_POST['_product_notes_label'] )
            : '';
        update_post_meta( $post_id, '_product_notes_label', $notes_label );

        $notes_placeholder = isset( $_POST['_product_notes_placeholder'] )
            ? sanitize_text_field( $_POST['_product_notes_placeholder'] )
            : '';
        update_post_meta( $post_id, '_product_notes_placeholder', $notes_placeholder );

        $notes_char_limit = isset( $_POST['_product_notes_char_limit'] )
            ? absint( $_POST['_product_notes_char_limit'] )
            : 300;
        update_post_meta( $post_id, '_product_notes_char_limit', $notes_char_limit );

        $notes_required = isset( $_POST['_product_notes_required'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_product_notes_required', $notes_required );

        // Save Gift Message settings
        $enable_gift = isset( $_POST['_enable_gift_message'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_enable_gift_message', $enable_gift );

        $gift_placeholder = isset( $_POST['_gift_message_placeholder'] )
            ? sanitize_text_field( $_POST['_gift_message_placeholder'] )
            : '';
        update_post_meta( $post_id, '_gift_message_placeholder', $gift_placeholder );

        $gift_char_limit = isset( $_POST['_gift_message_char_limit'] )
            ? absint( $_POST['_gift_message_char_limit'] )
            : 250;
        update_post_meta( $post_id, '_gift_message_char_limit', $gift_char_limit );

        $gift_required = isset( $_POST['_gift_message_required'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_gift_message_required', $gift_required );
    }
}
