<?php
/**
 * Content Fixes
 *
 * Fixes hardcoded text in Avada builder layouts and custom code blocks
 * that cannot be easily edited via the REST API.
 *
 * @since 1.6.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Start output buffering to fix hardcoded text in Avada layouts.
 */
function ah_ho_content_fixes_start_buffer() {
    ob_start( 'ah_ho_content_fixes_replace' );
}
add_action( 'template_redirect', 'ah_ho_content_fixes_start_buffer', 1 );

/**
 * Replace outdated text in the final HTML output.
 *
 * @param string $html The full page HTML.
 * @return string Modified HTML.
 */
function ah_ho_content_fixes_replace( $html ) {
    $replacements = array(
        // Footer newsletter: 10% -> 5%
        'Sign up for 10% off your first order' => 'Sign up for 5% off your first order',
        // Omakase category page: next morning -> next day
        'delivered the next morning'           => 'delivered the next day',
    );

    return str_replace(
        array_keys( $replacements ),
        array_values( $replacements ),
        $html
    );
}
