<?php
/**
 * Ah Ho Fruit Theme Functions
 *
 * @package Ah_Ho_Fruits
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Define theme constants
 */
define('AH_HO_THEME_VERSION', '1.0.0');
define('AH_HO_THEME_DIR', get_template_directory());
define('AH_HO_THEME_URI', get_template_directory_uri());

/**
 * Theme Setup
 */
function ah_ho_theme_setup() {
    // Add default posts and comments RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails
    add_theme_support('post-thumbnails');

    // Custom image sizes for products
    add_image_size('product-thumbnail', 300, 300, true);
    add_image_size('product-large', 600, 600, true);
    add_image_size('hero-banner', 1920, 600, true);

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'ah-ho-fruits'),
        'footer'  => __('Footer Menu', 'ah-ho-fruits'),
        'mobile'  => __('Mobile Menu', 'ah-ho-fruits'),
    ));

    // Switch default core markup to output valid HTML5
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));

    // Add support for custom logo
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    // Add support for responsive embeds
    add_theme_support('responsive-embeds');

    // Add support for wide alignment
    add_theme_support('align-wide');

    // WooCommerce support
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}
add_action('after_setup_theme', 'ah_ho_theme_setup');

/**
 * Enqueue scripts and styles
 */
function ah_ho_enqueue_scripts() {
    // Google Fonts
    wp_enqueue_style(
        'ah-ho-google-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap',
        array(),
        null
    );

    // Main stylesheet
    wp_enqueue_style(
        'ah-ho-style',
        get_stylesheet_uri(),
        array(),
        AH_HO_THEME_VERSION
    );

    // Custom CSS
    wp_enqueue_style(
        'ah-ho-custom',
        AH_HO_THEME_URI . '/assets/css/custom.css',
        array('ah-ho-style'),
        AH_HO_THEME_VERSION
    );

    // Main JavaScript
    wp_enqueue_script(
        'ah-ho-main',
        AH_HO_THEME_URI . '/assets/js/main.js',
        array('jquery'),
        AH_HO_THEME_VERSION,
        true
    );

    // Localize script for AJAX
    wp_localize_script('ah-ho-main', 'ahHoAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ah_ho_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'ah_ho_enqueue_scripts');

/**
 * Register widget areas
 */
function ah_ho_widgets_init() {
    register_sidebar(array(
        'name'          => __('Shop Sidebar', 'ah-ho-fruits'),
        'id'            => 'shop-sidebar',
        'description'   => __('Widgets displayed on shop pages.', 'ah-ho-fruits'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));

    register_sidebar(array(
        'name'          => __('Footer Column 1', 'ah-ho-fruits'),
        'id'            => 'footer-1',
        'description'   => __('First footer widget area.', 'ah-ho-fruits'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));

    register_sidebar(array(
        'name'          => __('Footer Column 2', 'ah-ho-fruits'),
        'id'            => 'footer-2',
        'description'   => __('Second footer widget area.', 'ah-ho-fruits'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));

    register_sidebar(array(
        'name'          => __('Footer Column 3', 'ah-ho-fruits'),
        'id'            => 'footer-3',
        'description'   => __('Third footer widget area.', 'ah-ho-fruits'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'ah_ho_widgets_init');

/**
 * WooCommerce customizations
 */

// Change number of products per row
add_filter('loop_shop_columns', function() {
    return 4; // 4 products per row
});

// Change number of products displayed per page
add_filter('loop_shop_per_page', function() {
    return 12;
});

// Remove default WooCommerce styles
add_filter('woocommerce_enqueue_styles', function($styles) {
    // Optionally remove default styles
    // unset($styles['woocommerce-general']);
    return $styles;
});

// Add custom wrapper for WooCommerce content
function ah_ho_woocommerce_wrapper_start() {
    echo '<div class="ah-ho-woo-wrapper container">';
}
add_action('woocommerce_before_main_content', 'ah_ho_woocommerce_wrapper_start', 10);

function ah_ho_woocommerce_wrapper_end() {
    echo '</div>';
}
add_action('woocommerce_after_main_content', 'ah_ho_woocommerce_wrapper_end', 10);

// Remove default WooCommerce sidebar
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

/**
 * Custom WooCommerce mini cart
 */
function ah_ho_mini_cart() {
    if (function_exists('WC')) {
        ?>
        <div class="ah-ho-mini-cart">
            <a href="<?php echo wc_get_cart_url(); ?>" class="cart-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
            </a>
        </div>
        <?php
    }
}

/**
 * AJAX update mini cart count
 */
function ah_ho_update_cart_count() {
    echo WC()->cart->get_cart_contents_count();
    wp_die();
}
add_action('wp_ajax_ah_ho_update_cart_count', 'ah_ho_update_cart_count');
add_action('wp_ajax_nopriv_ah_ho_update_cart_count', 'ah_ho_update_cart_count');

/**
 * Singapore-specific configurations
 */

// Set default currency to SGD
add_filter('woocommerce_currency', function() {
    return 'SGD';
});

// Singapore GST (9%)
function ah_ho_setup_singapore_tax() {
    // This would typically be set up in WooCommerce settings
    // Tax settings: WooCommerce > Settings > Tax
}

/**
 * Custom product badges
 */
function ah_ho_product_badges() {
    global $product;

    $badges = array();

    if ($product->is_on_sale()) {
        $badges[] = '<span class="badge badge-sale">Sale</span>';
    }

    if ($product->is_featured()) {
        $badges[] = '<span class="badge badge-featured">Featured</span>';
    }

    // Check if product is new (within last 30 days)
    $created = strtotime($product->get_date_created());
    if ($created > strtotime('-30 days')) {
        $badges[] = '<span class="badge badge-new">New</span>';
    }

    if (!empty($badges)) {
        echo '<div class="product-badges">' . implode('', $badges) . '</div>';
    }
}
add_action('woocommerce_before_shop_loop_item_title', 'ah_ho_product_badges', 10);

/**
 * Include additional theme files
 */
require_once AH_HO_THEME_DIR . '/inc/customizer.php';
require_once AH_HO_THEME_DIR . '/inc/woocommerce.php';
