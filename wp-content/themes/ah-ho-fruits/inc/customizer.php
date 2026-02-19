<?php
/**
 * Theme Customizer
 *
 * @package Ah_Ho_Fruit
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add customizer settings
 */
function ah_ho_customize_register($wp_customize) {

    // Theme Options Panel
    $wp_customize->add_panel('ah_ho_theme_options', array(
        'title'       => __('Ah Ho Fruit Options', 'ah-ho-fruits'),
        'description' => __('Customize your theme settings', 'ah-ho-fruits'),
        'priority'    => 30,
    ));

    // ==========================================
    // Header Section
    // ==========================================
    $wp_customize->add_section('ah_ho_header_section', array(
        'title'    => __('Header Settings', 'ah-ho-fruits'),
        'panel'    => 'ah_ho_theme_options',
        'priority' => 10,
    ));

    // Sticky Header
    $wp_customize->add_setting('ah_ho_sticky_header', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));

    $wp_customize->add_control('ah_ho_sticky_header', array(
        'label'   => __('Enable Sticky Header', 'ah-ho-fruits'),
        'section' => 'ah_ho_header_section',
        'type'    => 'checkbox',
    ));

    // ==========================================
    // Colors Section
    // ==========================================
    $wp_customize->add_section('ah_ho_colors_section', array(
        'title'    => __('Theme Colors', 'ah-ho-fruits'),
        'panel'    => 'ah_ho_theme_options',
        'priority' => 20,
    ));

    // Primary Color
    $wp_customize->add_setting('ah_ho_primary_color', array(
        'default'           => '#2E7D32',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ah_ho_primary_color', array(
        'label'   => __('Primary Color', 'ah-ho-fruits'),
        'section' => 'ah_ho_colors_section',
    )));

    // Secondary Color
    $wp_customize->add_setting('ah_ho_secondary_color', array(
        'default'           => '#FF6F00',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ah_ho_secondary_color', array(
        'label'   => __('Secondary Color', 'ah-ho-fruits'),
        'section' => 'ah_ho_colors_section',
    )));

    // ==========================================
    // Shop Section
    // ==========================================
    $wp_customize->add_section('ah_ho_shop_section', array(
        'title'    => __('Shop Settings', 'ah-ho-fruits'),
        'panel'    => 'ah_ho_theme_options',
        'priority' => 30,
    ));

    // Products per page
    $wp_customize->add_setting('ah_ho_products_per_page', array(
        'default'           => 12,
        'sanitize_callback' => 'absint',
    ));

    $wp_customize->add_control('ah_ho_products_per_page', array(
        'label'   => __('Products per Page', 'ah-ho-fruits'),
        'section' => 'ah_ho_shop_section',
        'type'    => 'number',
        'input_attrs' => array(
            'min'  => 4,
            'max'  => 48,
            'step' => 4,
        ),
    ));

    // Products per row
    $wp_customize->add_setting('ah_ho_products_per_row', array(
        'default'           => 4,
        'sanitize_callback' => 'absint',
    ));

    $wp_customize->add_control('ah_ho_products_per_row', array(
        'label'   => __('Products per Row', 'ah-ho-fruits'),
        'section' => 'ah_ho_shop_section',
        'type'    => 'select',
        'choices' => array(
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
        ),
    ));

    // ==========================================
    // Footer Section
    // ==========================================
    $wp_customize->add_section('ah_ho_footer_section', array(
        'title'    => __('Footer Settings', 'ah-ho-fruits'),
        'panel'    => 'ah_ho_theme_options',
        'priority' => 40,
    ));

    // Footer Text
    $wp_customize->add_setting('ah_ho_footer_text', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
    ));

    $wp_customize->add_control('ah_ho_footer_text', array(
        'label'   => __('Footer Text', 'ah-ho-fruits'),
        'section' => 'ah_ho_footer_section',
        'type'    => 'textarea',
    ));

    // WhatsApp Number
    $wp_customize->add_setting('ah_ho_whatsapp_number', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('ah_ho_whatsapp_number', array(
        'label'       => __('WhatsApp Number', 'ah-ho-fruits'),
        'description' => __('Enter number with country code (e.g., 6512345678)', 'ah-ho-fruits'),
        'section'     => 'ah_ho_footer_section',
        'type'        => 'text',
    ));
}
add_action('customize_register', 'ah_ho_customize_register');

/**
 * Output customizer CSS
 */
function ah_ho_customizer_css() {
    $primary_color = get_theme_mod('ah_ho_primary_color', '#2E7D32');
    $secondary_color = get_theme_mod('ah_ho_secondary_color', '#FF6F00');

    ?>
    <style type="text/css">
        :root {
            --color-primary: <?php echo esc_attr($primary_color); ?>;
            --color-secondary: <?php echo esc_attr($secondary_color); ?>;
        }
    </style>
    <?php
}
add_action('wp_head', 'ah_ho_customizer_css');
