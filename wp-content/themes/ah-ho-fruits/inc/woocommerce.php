<?php
/**
 * WooCommerce specific functions
 *
 * @package Ah_Ho_Fruits
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if WooCommerce is active
 */
if (!function_exists('ah_ho_is_woocommerce_active')) {
    function ah_ho_is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
}

// Only run if WooCommerce is active
if (!ah_ho_is_woocommerce_active()) {
    return;
}

/**
 * Products per page from customizer
 */
add_filter('loop_shop_per_page', function($cols) {
    return get_theme_mod('ah_ho_products_per_page', 12);
});

/**
 * Products per row from customizer
 */
add_filter('loop_shop_columns', function($cols) {
    return get_theme_mod('ah_ho_products_per_row', 4);
});

/**
 * Related products settings
 */
add_filter('woocommerce_output_related_products_args', function($args) {
    $args['posts_per_page'] = 4;
    $args['columns'] = 4;
    return $args;
});

/**
 * Change add to cart text
 */
add_filter('woocommerce_product_single_add_to_cart_text', function() {
    return __('Add to Basket', 'ah-ho-fruits');
});

add_filter('woocommerce_product_add_to_cart_text', function() {
    return __('Add to Basket', 'ah-ho-fruits');
});

/**
 * Add weight/quantity info to product loop
 */
function ah_ho_product_weight_info() {
    global $product;

    if ($product->has_weight()) {
        echo '<div class="product-weight">' . wc_format_weight($product->get_weight()) . '</div>';
    }
}
add_action('woocommerce_after_shop_loop_item_title', 'ah_ho_product_weight_info', 8);

/**
 * Custom breadcrumb settings
 */
add_filter('woocommerce_breadcrumb_defaults', function($defaults) {
    return array(
        'delimiter'   => ' <span class="breadcrumb-separator">/</span> ',
        'wrap_before' => '<nav class="woocommerce-breadcrumb" aria-label="Breadcrumb">',
        'wrap_after'  => '</nav>',
        'before'      => '<span class="breadcrumb-item">',
        'after'       => '</span>',
        'home'        => __('Home', 'ah-ho-fruits'),
    );
});

/**
 * Customize sale badge
 */
add_filter('woocommerce_sale_flash', function($html, $post, $product) {
    $regular_price = (float) $product->get_regular_price();
    $sale_price = (float) $product->get_sale_price();

    if ($regular_price > 0 && $sale_price > 0) {
        $percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
        return '<span class="onsale">-' . $percentage . '%</span>';
    }

    return $html;
}, 10, 3);

/**
 * Add Singapore-specific shipping notice
 */
function ah_ho_shipping_notice() {
    if (is_cart() || is_checkout()) {
        $min_free_shipping = 50; // SGD
        $cart_total = WC()->cart->get_subtotal();

        if ($cart_total < $min_free_shipping) {
            $remaining = $min_free_shipping - $cart_total;
            echo '<div class="shipping-notice">';
            printf(
                __('Add <strong>%s</strong> more to qualify for FREE delivery in Singapore!', 'ah-ho-fruits'),
                wc_price($remaining)
            );
            echo '</div>';
        } else {
            echo '<div class="shipping-notice free-shipping">';
            esc_html_e('You qualify for FREE delivery in Singapore!', 'ah-ho-fruits');
            echo '</div>';
        }
    }
}
add_action('woocommerce_before_cart_totals', 'ah_ho_shipping_notice');
add_action('woocommerce_checkout_before_order_review_heading', 'ah_ho_shipping_notice');

/**
 * Add freshness guarantee notice on product pages
 */
function ah_ho_freshness_guarantee() {
    if (is_product()) {
        ?>
        <div class="freshness-guarantee">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span><?php esc_html_e('Freshness Guaranteed', 'ah-ho-fruits'); ?></span>
        </div>
        <?php
    }
}
add_action('woocommerce_single_product_summary', 'ah_ho_freshness_guarantee', 25);

/**
 * Customize checkout fields for Singapore
 */
add_filter('woocommerce_checkout_fields', function($fields) {
    // Make postal code required and prioritize it
    $fields['billing']['billing_postcode']['priority'] = 45;
    $fields['billing']['billing_postcode']['required'] = true;
    $fields['billing']['billing_postcode']['label'] = __('Postal Code', 'ah-ho-fruits');

    // Set default country to Singapore
    $fields['billing']['billing_country']['default'] = 'SG';
    $fields['shipping']['shipping_country']['default'] = 'SG';

    return $fields;
});

/**
 * Add delivery time slot selection (placeholder for future enhancement)
 */
function ah_ho_delivery_time_slots() {
    // This can be expanded to allow customers to choose delivery time slots
    echo '<div class="delivery-info">';
    echo '<h4>' . esc_html__('Delivery Information', 'ah-ho-fruits') . '</h4>';
    echo '<p>' . esc_html__('Same-day delivery available for orders placed before 2 PM.', 'ah-ho-fruits') . '</p>';
    echo '</div>';
}
add_action('woocommerce_checkout_before_order_review', 'ah_ho_delivery_time_slots');

/**
 * WhatsApp floating button
 */
function ah_ho_whatsapp_button() {
    $whatsapp_number = get_theme_mod('ah_ho_whatsapp_number', '');

    if (!empty($whatsapp_number)) {
        $message = urlencode(__('Hi! I\'m interested in ordering fresh fruits.', 'ah-ho-fruits'));
        ?>
        <a href="https://wa.me/<?php echo esc_attr($whatsapp_number); ?>?text=<?php echo $message; ?>"
           class="whatsapp-float"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="<?php esc_attr_e('Chat on WhatsApp', 'ah-ho-fruits'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="white">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
        </a>
        <?php
    }
}
add_action('wp_footer', 'ah_ho_whatsapp_button');
