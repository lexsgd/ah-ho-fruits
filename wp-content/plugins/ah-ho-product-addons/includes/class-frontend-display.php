<?php
/**
 * Frontend Display - Product page UI for notes and gift message
 */

defined( 'ABSPATH' ) || exit;

class AH_Ho_Addons_Frontend_Display {

    public function __construct() {
        // Display before Add to Cart button
        add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'display_addon_fields' ], 9 );
    }

    /**
     * Display product notes and gift message fields
     */
    public function display_addon_fields() {
        global $product;

        $product_id = $product->get_id();

        // Check if any addon is enabled
        $notes_enabled = get_post_meta( $product_id, '_enable_product_notes', true ) === 'yes';
        $gift_enabled = get_post_meta( $product_id, '_enable_gift_message', true ) === 'yes';
        $product_addon_enabled = get_post_meta( $product_id, '_enable_product_addon', true ) === 'yes';

        if ( ! $notes_enabled && ! $gift_enabled && ! $product_addon_enabled ) {
            return; // No addons enabled for this product
        }

        // Preserve values if form was submitted with errors
        $product_notes = isset( $_POST['product_notes'] ) ? sanitize_textarea_field( $_POST['product_notes'] ) : '';
        $is_gift = isset( $_POST['is_gift'] ) ? 'checked' : '';
        $gift_message = isset( $_POST['gift_message'] ) ? sanitize_textarea_field( $_POST['gift_message'] ) : '';

        echo '<div class="ah-ho-addons-wrapper">';

        // ===== PRODUCT NOTES SECTION =====
        if ( $notes_enabled ) {
            $notes_label = get_post_meta( $product_id, '_product_notes_label', true )
                ?: __( 'Special Requests', 'ah-ho-fruits' );
            $notes_placeholder = get_post_meta( $product_id, '_product_notes_placeholder', true )
                ?: __( 'E.g., "More strawberries please" or "No bananas - allergic"', 'ah-ho-fruits' );
            $notes_char_limit = get_post_meta( $product_id, '_product_notes_char_limit', true ) ?: 300;
            $notes_required = get_post_meta( $product_id, '_product_notes_required', true ) === 'yes';

            ?>
            <div class="ah-ho-notes-section">
                <label for="ah_ho_product_notes">
                    <?php echo esc_html( $notes_label ); ?>
                    <small style="color: #666; font-weight: normal;">
                        <?php _e( '(Preferences / Allergies)', 'ah-ho-fruits' ); ?>
                    </small>
                    <?php if ( $notes_required ) : ?>
                        <span class="required">*</span>
                    <?php endif; ?>
                </label>
                <textarea
                    name="product_notes"
                    id="ah_ho_product_notes"
                    rows="3"
                    maxlength="<?php echo esc_attr( $notes_char_limit ); ?>"
                    placeholder="<?php echo esc_attr( $notes_placeholder ); ?>"
                    <?php echo $notes_required ? 'required' : ''; ?>
                    class="ah-ho-textarea"
                ><?php echo esc_textarea( $product_notes ); ?></textarea>
                <small class="ah-ho-char-counter" data-counter="notes">
                    <?php printf( __( '0 / %d characters', 'ah-ho-fruits' ), $notes_char_limit ); ?>
                </small>
            </div>
            <?php
        }

        // ===== GIFT MESSAGE SECTION =====
        if ( $gift_enabled ) {
            $gift_placeholder = get_post_meta( $product_id, '_gift_message_placeholder', true )
                ?: __( 'Enter your heartfelt message here...', 'ah-ho-fruits' );
            $gift_char_limit = get_post_meta( $product_id, '_gift_message_char_limit', true ) ?: 250;
            $gift_required = get_post_meta( $product_id, '_gift_message_required', true ) === 'yes';

            ?>
            <div class="ah-ho-gift-section">
                <div class="ah-ho-gift-checkbox-row">
                    <label class="ah-ho-gift-checkbox-label">
                        <input
                            type="checkbox"
                            name="is_gift"
                            id="ah_ho_is_gift"
                            value="yes"
                            <?php echo $is_gift; ?>
                        >
                        <span>🎁 <?php _e( 'This is a gift', 'ah-ho-fruits' ); ?></span>
                    </label>
                </div>

                <div class="ah-ho-gift-message-row" style="display: none;">
                    <label for="ah_ho_gift_message">
                        <?php _e( 'Gift Message', 'ah-ho-fruits' ); ?>
                        <?php if ( $gift_required ) : ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                    </label>
                    <textarea
                        name="gift_message"
                        id="ah_ho_gift_message"
                        rows="3"
                        maxlength="<?php echo esc_attr( $gift_char_limit ); ?>"
                        placeholder="<?php echo esc_attr( $gift_placeholder ); ?>"
                        <?php echo $gift_required ? 'data-required="true"' : ''; ?>
                        class="ah-ho-textarea"
                    ><?php echo esc_textarea( $gift_message ); ?></textarea>
                    <small class="ah-ho-char-counter" data-counter="gift">
                        <?php printf( __( '0 / %d characters', 'ah-ho-fruits' ), $gift_char_limit ); ?>
                    </small>
                </div>
            </div>
            <?php
        }

        // ===== PRODUCT ADD-ON SECTION =====
        if ( $product_addon_enabled ) {
            $addon_product_id = absint( get_post_meta( $product_id, '_addon_product_id', true ) );
            $addon_label = get_post_meta( $product_id, '_addon_product_label', true );
            $addon_checked = isset( $_POST['add_product_addon'] ) ? 'checked' : '';

            if ( $addon_product_id ) {
                $addon_product = wc_get_product( $addon_product_id );

                if ( $addon_product && $addon_product->is_in_stock() ) {
                    $addon_name = $addon_product->get_name();
                    $addon_price = $addon_product->get_price();
                    $addon_price_html = $addon_product->get_price_html();
                    $addon_image_id = $addon_product->get_image_id();
                    $addon_image_url = $addon_image_id
                        ? wp_get_attachment_image_url( $addon_image_id, 'thumbnail' )
                        : wc_placeholder_img_src( 'thumbnail' );
                    $addon_image_full = $addon_image_id
                        ? wp_get_attachment_image_url( $addon_image_id, 'large' )
                        : $addon_image_url;

                    if ( ! $addon_label ) {
                        $addon_label = sprintf( __( 'Add %s', 'ah-ho-fruits' ), $addon_name );
                    }

                    ?>
                    <div class="ah-ho-product-addon-section">
                        <div class="ah-ho-addon-checkbox-row">
                            <label class="ah-ho-addon-checkbox-label">
                                <input
                                    type="checkbox"
                                    name="add_product_addon"
                                    id="ah_ho_product_addon"
                                    value="<?php echo esc_attr( $addon_product_id ); ?>"
                                    <?php echo $addon_checked; ?>
                                >
                                <span class="ah-ho-addon-content">
                                    <img
                                        src="<?php echo esc_url( $addon_image_url ); ?>"
                                        alt="<?php echo esc_attr( $addon_name ); ?>"
                                        class="ah-ho-addon-image"
                                        data-full-src="<?php echo esc_url( $addon_image_full ); ?>"
                                        title="<?php esc_attr_e( 'Click to enlarge', 'ah-ho-fruits' ); ?>"
                                    >
                                    <span class="ah-ho-addon-details">
                                        <span class="ah-ho-addon-name"><?php echo esc_html( $addon_label ); ?></span>
                                        <span class="ah-ho-addon-price">+ <?php echo wp_kses_post( $addon_price_html ); ?></span>
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>
                    <?php
                }
            }
        }

        // Lightbox overlay for addon image preview
        echo '<div class="ah-ho-addon-lightbox" style="display:none;">';
        echo '<div class="ah-ho-addon-lightbox-backdrop"></div>';
        echo '<div class="ah-ho-addon-lightbox-content">';
        echo '<img src="" alt="" class="ah-ho-addon-lightbox-img">';
        echo '<button type="button" class="ah-ho-addon-lightbox-close" aria-label="' . esc_attr__( 'Close preview', 'ah-ho-fruits' ) . '">&times;</button>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
    }
}
