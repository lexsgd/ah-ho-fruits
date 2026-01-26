<?php
/**
 * Plugin Name: Ah Ho Typography Fix
 * Description: Fixes site-wide text readability (overrides Avada yellow theme)
 * Version: 1.0.0
 * Author: Ah Ho Fruit Trading Co
 */

defined( 'ABSPATH' ) || exit;

add_action('wp_head', function() {
    ?>
    <style id="ah-ho-typography-fix">
    /* ========================================
       AH HO FRUITS - GLOBAL TYPOGRAPHY FIX
       Overrides Avada Vegan theme yellow text
       ======================================== */

    /* Global text color fixes - Dark gray for readability */
    body,
    p,
    li,
    td,
    span,
    div,
    .fusion-text,
    .fusion-content-boxes .fusion-content-box-hover p {
        color: #2c3e50 !important;
        line-height: 1.8 !important;
    }

    /* Headings - Black for strong contrast */
    h1, h2, h3, h4, h5, h6,
    .fusion-title h1,
    .fusion-title h2,
    .fusion-title h3,
    .fusion-title h4,
    .fusion-title h5,
    .fusion-title h6 {
        color: #1a1a1a !important;
        line-height: 1.3 !important;
        font-weight: 700 !important;
    }

    /* ========================================
       EXCEPTIONS FOR DARK BACKGROUNDS
       Keep white/light text on dark sections
       ======================================== */

    /* Hero section with dark background - keep white text */
    .fusion-fullwidth.fusion-builder-row-1 h1,
    .fusion-fullwidth.fusion-builder-row-1 h2,
    .fusion-fullwidth.fusion-builder-row-1 h3,
    .fusion-fullwidth.fusion-builder-row-1 h4,
    .fusion-fullwidth.fusion-builder-row-1 p,
    .fusion-fullwidth.fusion-builder-row-1 span,
    .fusion-fullwidth.fusion-builder-row-1 div,
    .fusion-fullwidth[data-bg-url] h1,
    .fusion-fullwidth[data-bg-url] h2,
    .fusion-fullwidth[data-bg-url] h3,
    .fusion-fullwidth[data-bg-url] h4,
    .fusion-fullwidth[data-bg-url] p,
    .fusion-fullwidth[data-bg-url] span,
    .fusion-fullwidth[data-bg-url] div {
        color: #ffffff !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
    }

    /* Category cards with image overlays - keep readable on dark images */
    .fusion-image-wrapper h2,
    .fusion-image-wrapper h3,
    .fusion-image-wrapper h4 {
        color: #ffffff !important;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5) !important;
    }

    /* Dark background sections - keep white text */
    [class*="fusion-builder-row"][style*="background-color: rgb(51, 51, 51)"] *,
    [class*="fusion-builder-row"][style*="background-color: #333"] *,
    .fusion-fullwidth.dark-background *,
    section[style*="background-color: rgb(51, 51, 51)"] *,
    section[style*="background-color: #333"] * {
        color: #ffffff !important;
    }

    /* Heading sizes */
    h1 { font-size: 36px !important; }
    h2 { font-size: 28px !important; }
    h3 { font-size: 22px !important; }
    h4 { font-size: 18px !important; }
    h5 { font-size: 16px !important; }
    h6 { font-size: 14px !important; }

    /* Links - Blue for accessibility */
    a {
        color: #2563eb !important;
    }

    a:hover {
        color: #1d4ed8 !important;
    }

    /* Product links should be dark */
    .woocommerce-loop-product__link,
    .product-title a,
    .woocommerce-loop-product__title {
        color: #1a1a1a !important;
    }

    /* Buttons - keep original colors but ensure text is readable */
    .fusion-button,
    button,
    input[type="submit"],
    .woocommerce-Button,
    .button,
    .added_to_cart {
        color: #ffffff !important; /* White text on buttons */
    }

    /* Primary CTA buttons - keep yellow but with dark text for contrast */
    .fusion-button.button-default {
        background-color: #f9c325 !important;
        color: #1a1a1a !important; /* Dark text on yellow button */
    }

    /* Price colors - Green for fresh/organic feel */
    .price,
    .woocommerce-Price-amount,
    .amount,
    ins .woocommerce-Price-amount {
        color: #2e7d32 !important; /* Green for prices */
        font-weight: 600 !important;
    }

    /* Sale prices - Red for urgency */
    del .woocommerce-Price-amount,
    del .amount {
        color: #d32f2f !important; /* Red for strikethrough prices */
    }

    /* Product titles */
    .product-title,
    .woocommerce-loop-product__title {
        color: #1a1a1a !important;
        font-weight: 600 !important;
    }

    /* Navigation menu */
    .fusion-main-menu > ul > li > a,
    .fusion-secondary-menu > ul > li > a {
        color: #1a1a1a !important;
    }

    .fusion-main-menu > ul > li > a:hover {
        color: #2e7d32 !important; /* Green on hover */
    }

    /* Footer text */
    #footer,
    #footer p,
    #footer li,
    #footer a {
        color: #2c3e50 !important;
    }

    /* Widget titles */
    .fusion-widget-area .widget-title,
    .fusion-footer-widget-area .widget-title {
        color: #1a1a1a !important;
    }

    /* Form labels and inputs */
    label,
    .woocommerce-form label {
        color: #2c3e50 !important;
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="password"],
    textarea,
    select {
        color: #2c3e50 !important;
        border: 1px solid #ddd !important;
    }

    /* WooCommerce specific */
    .woocommerce-info,
    .woocommerce-message {
        color: #2c3e50 !important;
    }

    /* Breadcrumbs */
    .fusion-breadcrumbs {
        color: #666 !important;
    }

    .fusion-breadcrumbs a {
        color: #2563eb !important;
    }

    /* Cart icon count */
    .fusion-widget-cart-number {
        background: #2e7d32 !important;
        color: #ffffff !important;
    }

    /* Rating stars - keep orange/gold */
    .star-rating span,
    .star-rating::before {
        color: #ff9800 !important;
    }

    /* Stock status */
    .stock {
        color: #2e7d32 !important; /* Green for in stock */
    }

    .out-of-stock {
        color: #d32f2f !important; /* Red for out of stock */
    }

    /* Tabs */
    .woocommerce-tabs .tabs li a {
        color: #2c3e50 !important;
    }

    .woocommerce-tabs .tabs li.active a {
        color: #2e7d32 !important;
    }

    /* Product meta */
    .product_meta {
        color: #666 !important;
    }

    /* Ensure good contrast ratio - WCAG AA compliance */
    body {
        background-color: #ffffff !important;
    }

    /* Special override for Avada content boxes */
    .fusion-content-boxes .content-box-heading h2,
    .fusion-content-boxes .content-box-heading h3,
    .fusion-content-boxes .content-box-heading h4 {
        color: #1a1a1a !important;
    }

    /* Icon boxes text */
    .fusion-icon-box-content h2,
    .fusion-icon-box-content h3 {
        color: #1a1a1a !important;
    }

    /* Blog/news post text */
    .fusion-post-content p,
    .post-content p {
        color: #2c3e50 !important;
    }

    /* Testimonials */
    .fusion-testimonials .review {
        color: #2c3e50 !important;
    }

    /* Alert boxes */
    .fusion-alert {
        color: #2c3e50 !important;
    }

    /* Mobile menu */
    @media (max-width: 1024px) {
        .fusion-mobile-menu-text-align-left .fusion-mobile-nav-item a {
            color: #2c3e50 !important;
        }
    }

    /* Ensure checkboxes and radio buttons are visible */
    input[type="checkbox"],
    input[type="radio"] {
        opacity: 1 !important;
    }

    /* Newsletter signup */
    .fusion-newsletter input[type="email"] {
        color: #2c3e50 !important;
    }

    /* Search box */
    .fusion-search input {
        color: #2c3e50 !important;
    }

    /* Make sure no yellow text remains */
    [style*="color: #f9c325"],
    [style*="color: rgb(249, 195, 37)"] {
        color: #2c3e50 !important;
    }
    </style>
    <?php
}, 999); // Very high priority to override theme styles
