<?php
/**
 * WhatsApp Catalog Generator
 *
 * Generates a WhatsApp-compatible product catalog text that can be
 * easily shared with B2B customers.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register admin menu for catalog generator
 */
add_action('admin_menu', 'ah_ho_register_catalog_menu');

function ah_ho_register_catalog_menu() {
    add_menu_page(
        __('Catalog Generator', 'ah-ho-custom'),
        __('Catalog', 'ah-ho-custom'),
        'manage_woocommerce', // Admin access only
        'ah-ho-catalog',
        'ah_ho_render_catalog_page',
        'dashicons-share',
        58
    );
}

/**
 * Render the catalog generator admin page
 */
function ah_ho_render_catalog_page() {
    $catalog_text = ah_ho_generate_catalog_text();
    ?>
    <div class="wrap">
        <h1><?php _e('WhatsApp Catalog Generator', 'ah-ho-custom'); ?></h1>
        <p><?php _e('Copy this text and paste into WhatsApp to share with customers.', 'ah-ho-custom'); ?></p>

        <div style="margin-bottom: 15px;">
            <button type="button" id="ah-ho-copy-catalog" class="button button-primary button-large">
                <span class="dashicons dashicons-clipboard" style="vertical-align: middle; margin-right: 5px;"></span>
                <?php _e('Copy to Clipboard', 'ah-ho-custom'); ?>
            </button>
            <button type="button" id="ah-ho-refresh-catalog" class="button" style="margin-left: 10px;">
                <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>
                <?php _e('Refresh Catalog', 'ah-ho-custom'); ?>
            </button>
            <span id="ah-ho-copy-status" style="margin-left: 15px; color: #2ea44f; display: none;">
                <span class="dashicons dashicons-yes" style="vertical-align: middle;"></span>
                <?php _e('Copied!', 'ah-ho-custom'); ?>
            </span>
        </div>

        <textarea
            id="ah-ho-catalog-text"
            rows="30"
            style="width: 100%; max-width: 800px; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.5; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;"
            readonly
        ><?php echo esc_textarea($catalog_text); ?></textarea>

        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #856404; max-width: 800px;">
            <h4 style="margin-top: 0; color: #856404;"><?php _e('Tips for WhatsApp Sharing', 'ah-ho-custom'); ?></h4>
            <ul style="margin-bottom: 0;">
                <li><?php _e('Prices shown are <strong>wholesale prices</strong> (exclusive of GST)', 'ah-ho-custom'); ?></li>
                <li><?php _e('Only products with wholesale price set are included', 'ah-ho-custom'); ?></li>
                <li><?php _e('Only in-stock products are shown', 'ah-ho-custom'); ?></li>
                <li><?php _e('Catalog updates automatically when prices or availability change', 'ah-ho-custom'); ?></li>
                <li><?php _e('Bold text (*text*) will appear bold in WhatsApp', 'ah-ho-custom'); ?></li>
            </ul>
        </div>

        <?php $stock_text = ah_ho_generate_stock_catalog_text(); ?>

        <h2 style="margin-top: 40px;"><?php _e('B2B Stock List', 'ah-ho-custom'); ?></h2>

        <div style="margin-bottom: 15px; padding: 12px; background: #d1ecf1; border-left: 4px solid #0c5460; max-width: 800px;">
            <span class="dashicons dashicons-info" style="color: #0c5460; vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('This section is for internal reference only. Stock quantities are not shareable.', 'ah-ho-custom'); ?>
        </div>

        <pre
            id="ah-ho-stock-list"
            style="width: 100%; max-width: 800px; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.5; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; white-space: pre-wrap; word-wrap: break-word; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; overflow-x: auto;"
            oncopy="return false;"
            oncontextmenu="return false;"
        ><?php echo esc_html($stock_text); ?></pre>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Copy to clipboard
            $('#ah-ho-copy-catalog').on('click', function() {
                var textArea = document.getElementById('ah-ho-catalog-text');
                textArea.select();
                textArea.setSelectionRange(0, 99999); // For mobile

                try {
                    document.execCommand('copy');
                    $('#ah-ho-copy-status').fadeIn().delay(2000).fadeOut();
                } catch (err) {
                    // Fallback for modern browsers
                    navigator.clipboard.writeText(textArea.value).then(function() {
                        $('#ah-ho-copy-status').fadeIn().delay(2000).fadeOut();
                    });
                }
            });

            // Refresh catalog (reload page)
            $('#ah-ho-refresh-catalog').on('click', function() {
                location.reload();
            });

            // Prevent keyboard copy on stock list
            $('#ah-ho-stock-list').on('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 'a' || e.key === 'c')) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
    <?php
}

/**
 * Generate catalog text in WhatsApp-friendly format
 */
function ah_ho_generate_catalog_text() {
    // Get all product categories
    $categories = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ));

    if (is_wp_error($categories) || empty($categories)) {
        return __('No products available.', 'ah-ho-custom');
    }

    // Category emoji mapping (customize as needed)
    $category_emojis = array(
        'fruits'     => '🍎',
        'vegetables' => '🥬',
        'citrus'     => '🍊',
        'berries'    => '🍓',
        'tropical'   => '🥭',
        'apples'     => '🍏',
        'bananas'    => '🍌',
        'grapes'     => '🍇',
        'melons'     => '🍈',
        'stone'      => '🍑',
        'imported'   => '✈️',
        'local'      => '🇸🇬',
    );

    $output = "*AH HO FRUITS - WHOLESALE PRICE LIST*\n";
    $output .= "_Prices exclusive of GST_\n";
    $output .= "_B2B wholesale prices only_\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━\n\n";

    foreach ($categories as $category) {
        // Query in-stock products for this category that have wholesale price set
        $products = wc_get_products(array(
            'status'       => 'publish',
            'stock_status' => 'instock',
            'category'     => array($category->slug),
            'limit'        => -1,
            'orderby'      => 'title',
            'order'        => 'ASC',
            'meta_query'   => array(
                array(
                    'key'     => '_wholesale_price',
                    'value'   => '',
                    'compare' => '!=',
                ),
                array(
                    'key'     => '_wholesale_price',
                    'compare' => 'EXISTS',
                ),
            ),
        ));

        // Skip categories with no products with wholesale price
        if (empty($products)) {
            continue;
        }

        // Get emoji for category (check slug against mapping)
        $emoji = '';
        foreach ($category_emojis as $key => $icon) {
            if (stripos($category->slug, $key) !== false || stripos($category->name, $key) !== false) {
                $emoji = $icon . ' ';
                break;
            }
        }

        // Category header
        $output .= sprintf("*%s%s*\n", $emoji, strtoupper($category->name));

        foreach ($products as $product) {
            // Use wholesale price for B2B catalog
            $wholesale_price = $product->get_meta('_wholesale_price');
            $name = $product->get_name();

            // Format price (wholesale price should always exist due to meta_query)
            if ($wholesale_price) {
                $output .= sprintf("%s @ $%.2f\n", $name, floatval($wholesale_price));
            } else {
                $output .= sprintf("%s @ POA\n", $name); // Price on Application
            }
        }

        $output .= "\n";
    }

    // Footer
    $output .= "━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "_Last updated: " . current_time('d M Y, g:i A') . "_\n";
    $output .= "_Contact us to place your order!_";

    return $output;
}

/**
 * Generate B2B stock catalog text with stock quantities
 */
function ah_ho_generate_stock_catalog_text() {
    $categories = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ));

    if (is_wp_error($categories) || empty($categories)) {
        return __('No products available.', 'ah-ho-custom');
    }

    $category_emojis = array(
        'fruits'     => '🍎',
        'vegetables' => '🥬',
        'citrus'     => '🍊',
        'berries'    => '🍓',
        'tropical'   => '🥭',
        'apples'     => '🍏',
        'bananas'    => '🍌',
        'grapes'     => '🍇',
        'melons'     => '🍈',
        'stone'      => '🍑',
        'imported'   => '✈️',
        'local'      => '🇸🇬',
    );

    $output = "*AH HO FRUITS - B2B STOCK LIST*\n";
    $output .= "_Internal use only - Do not share_\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━\n\n";

    foreach ($categories as $category) {
        $products = wc_get_products(array(
            'status'       => 'publish',
            'stock_status' => 'instock',
            'category'     => array($category->slug),
            'limit'        => -1,
            'orderby'      => 'title',
            'order'        => 'ASC',
        ));

        if (empty($products)) {
            continue;
        }

        // Filter to only hidden products (B2B only, not visible in shop)
        $hidden_products = array_filter($products, function($product) {
            return $product->get_catalog_visibility() === 'hidden';
        });

        if (empty($hidden_products)) {
            continue;
        }

        $emoji = '';
        foreach ($category_emojis as $key => $icon) {
            if (stripos($category->slug, $key) !== false || stripos($category->name, $key) !== false) {
                $emoji = $icon . ' ';
                break;
            }
        }

        $output .= sprintf("*%s%s*\n", $emoji, strtoupper($category->name));

        foreach ($hidden_products as $product) {
            $wholesale_price = $product->get_meta('_wholesale_price');
            $name = $product->get_name();
            $stock_qty = $product->get_stock_quantity();
            $stock_display = ($stock_qty !== null && $stock_qty !== '') ? $stock_qty : 'N/A';

            if ($wholesale_price) {
                $output .= sprintf("%s @ \$%.2f x %s\n", $name, floatval($wholesale_price), $stock_display);
            } else {
                $output .= sprintf("%s @ POA x %s\n", $name, $stock_display);
            }
        }

        $output .= "\n";
    }

    $output .= "━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "_Last updated: " . current_time('d M Y, g:i A') . "_";

    return $output;
}

/**
 * AJAX endpoint for refreshing catalog (optional)
 */
add_action('wp_ajax_ah_ho_refresh_catalog', 'ah_ho_ajax_refresh_catalog');

function ah_ho_ajax_refresh_catalog() {
    // Check capabilities
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(__('Permission denied', 'ah-ho-custom'));
    }

    wp_send_json_success(array(
        'catalog' => ah_ho_generate_catalog_text()
    ));
}
