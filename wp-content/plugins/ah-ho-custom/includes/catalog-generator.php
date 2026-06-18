<?php
/**
 * WhatsApp Catalog Generator
 *
 * Generates a WhatsApp-compatible product catalog text that can be
 * easily shared with B2B customers.
 *
 * @modified 2026-02-08 - exclude parent "Fruits" category to prevent duplicates
 * @modified 2026-06-18 - manual drag-and-drop sorting (categories + products),
 *                        catalog-only order stored in options (does not affect shop)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* -------------------------------------------------------------------------
 * Sort order storage + helpers (catalog-only; independent of shop ordering)
 * ---------------------------------------------------------------------- */

// Option keys
if (!defined('AH_HO_CAT_ORDER_OPT'))  define('AH_HO_CAT_ORDER_OPT', 'ahho_catalog_cat_order');     // array of category slugs
if (!defined('AH_HO_PROD_ORDER_OPT')) define('AH_HO_PROD_ORDER_OPT', 'ahho_catalog_product_order'); // ordered array of product IDs

// Categories excluded from the catalog (parents / non-B2B)
function ah_ho_catalog_excluded_slugs() {
    return array('fruits', 'uncategorized', 'omakase-fruit-boxes');
}

// Default category display order (used until an admin saves a custom order)
function ah_ho_catalog_default_category_order() {
    return array('apples', 'citrus', 'pears', 'berries', 'grapes', 'melons', 'stone-fruits', 'kiwi', 'tropical', 'others');
}

// Category emoji pairs for headings
function ah_ho_catalog_category_emojis() {
    return array(
        'apples'   => array('🍏', '🍎'),
        'berries'  => array('🍓', '🫐'),
        'citrus'   => array('🍊', '🍋'),
        'grapes'   => array('🍇', '🍇'),
        'kiwi'     => array('🥝', '🥝'),
        'melons'   => array('🍈', '🍉'),
        'others'   => array('🥕', '🌴'),
        'pears'    => array('🍐', '🍐'),
        'stone'    => array('🍑', '🍑'),
        'tropical' => array('🥭', '🍌'),
    );
}

function ah_ho_get_catalog_category_order() {
    $saved = get_option(AH_HO_CAT_ORDER_OPT);
    return (is_array($saved) && !empty($saved)) ? array_values(array_filter(array_map('strval', $saved))) : ah_ho_catalog_default_category_order();
}

function ah_ho_get_catalog_product_order() {
    $saved = get_option(AH_HO_PROD_ORDER_OPT);
    return is_array($saved) ? array_map('intval', $saved) : array();
}

/**
 * Sort an array of category term objects by the saved (or default) order.
 * Unlisted categories fall to the end, alphabetically.
 */
function ah_ho_sort_categories_for_catalog($categories) {
    $order = ah_ho_get_catalog_category_order();
    usort($categories, function ($a, $b) use ($order) {
        $pa = array_search($a->slug, $order, true);
        $pb = array_search($b->slug, $order, true);
        if ($pa === false) $pa = 999;
        if ($pb === false) $pb = 999;
        if ($pa === $pb) return strcasecmp($a->name, $b->name);
        return $pa - $pb;
    });
    return $categories;
}

/**
 * Sort an array of WC_Product objects by the saved catalog order.
 * Products without a saved position fall to the end, alphabetically (the old default).
 */
function ah_ho_sort_products_for_catalog($products) {
    $order = array_flip(ah_ho_get_catalog_product_order()); // id => position
    usort($products, function ($a, $b) use ($order) {
        $ia = isset($order[$a->get_id()]) ? $order[$a->get_id()] : PHP_INT_MAX;
        $ib = isset($order[$b->get_id()]) ? $order[$b->get_id()] : PHP_INT_MAX;
        if ($ia === $ib) return strcasecmp($a->get_name(), $b->get_name());
        return ($ia < $ib) ? -1 : 1;
    });
    return $products;
}

/* -------------------------------------------------------------------------
 * Admin menu + page
 * ---------------------------------------------------------------------- */

add_action('admin_menu', 'ah_ho_register_catalog_menu');

function ah_ho_register_catalog_menu() {
    add_menu_page(
        __('Catalog Generator', 'ah-ho-custom'),
        __('Catalog', 'ah-ho-custom'),
        'manage_woocommerce',
        'ah-ho-catalog',
        'ah_ho_render_catalog_page',
        'dashicons-share',
        58
    );
}

// Load jQuery UI Sortable only on the catalog page
add_action('admin_enqueue_scripts', 'ah_ho_catalog_enqueue');
function ah_ho_catalog_enqueue($hook) {
    if (strpos((string) $hook, 'ah-ho-catalog') === false) {
        return;
    }
    wp_enqueue_script('jquery-ui-sortable');
}

/**
 * Render the catalog generator admin page (catalog text + drag-and-drop sorter)
 */
function ah_ho_render_catalog_page() {
    $catalog_text = ah_ho_generate_catalog_text();
    $stock_text   = ah_ho_generate_stock_catalog_text();
    $nonce        = wp_create_nonce('ah_ho_catalog_order');
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
                <li><?php _e('Use the <strong>Sort Catalog</strong> section below to drag items into the order you want', 'ah-ho-custom'); ?></li>
                <li><?php _e('Bold text (*text*) will appear bold in WhatsApp', 'ah-ho-custom'); ?></li>
            </ul>
        </div>

        <?php ah_ho_render_catalog_sorter($nonce); ?>

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
                textArea.setSelectionRange(0, 99999);
                try {
                    document.execCommand('copy');
                    $('#ah-ho-copy-status').fadeIn().delay(2000).fadeOut();
                } catch (err) {
                    navigator.clipboard.writeText(textArea.value).then(function() {
                        $('#ah-ho-copy-status').fadeIn().delay(2000).fadeOut();
                    });
                }
            });

            $('#ah-ho-refresh-catalog').on('click', function() { location.reload(); });

            $('#ah-ho-stock-list').on('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 'a' || e.key === 'c')) {
                    e.preventDefault();
                    return false;
                }
            });

            /* ---- Drag-and-drop sorter ---- */
            $('#ah-ho-cat-sortable').sortable({ handle: '.ah-ho-cat-handle', axis: 'y', cursor: 'move', opacity: 0.7 });
            $('.ah-ho-prod-sortable').sortable({ axis: 'y', cursor: 'move', opacity: 0.7 });

            $('#ah-ho-save-order').on('click', function() {
                var $btn = $(this).prop('disabled', true);
                var categories = [];
                var products = [];
                $('#ah-ho-cat-sortable > li').each(function() {
                    categories.push($(this).data('slug'));
                    $(this).find('.ah-ho-prod-sortable > li').each(function() {
                        products.push($(this).data('id'));
                    });
                });
                $.post(ajaxurl, {
                    action: 'ah_ho_save_catalog_order',
                    nonce: '<?php echo esc_js($nonce); ?>',
                    categories: categories,
                    products: products
                }).done(function(resp) {
                    $('#ah-ho-order-status').css('color', resp.success ? '#2ea44f' : '#b32d2e')
                        .text(resp.success ? 'Saved! Refresh to see the catalog update.' : ('Error: ' + (resp.data || 'unknown'))).show();
                }).fail(function() {
                    $('#ah-ho-order-status').css('color', '#b32d2e').text('Save failed (network).').show();
                }).always(function() { $btn.prop('disabled', false); });
            });
        });
    </script>
    <?php
}

/**
 * Render the drag-and-drop sorter UI (categories, each containing its B2B products).
 */
function ah_ho_render_catalog_sorter($nonce) {
    $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true));
    if (is_wp_error($categories) || empty($categories)) {
        return;
    }
    $excluded = ah_ho_catalog_excluded_slugs();
    $categories = array_values(array_filter($categories, function ($c) use ($excluded) {
        return !in_array($c->slug, $excluded, true);
    }));
    $categories = ah_ho_sort_categories_for_catalog($categories);
    ?>
    <h2 style="margin-top: 40px;"><?php _e('Sort Catalog (drag to reorder)', 'ah-ho-custom'); ?></h2>
    <div style="margin-bottom: 12px; padding: 12px; background: #e7f5ea; border-left: 4px solid #2ea44f; max-width: 800px;">
        <?php _e('Drag the <strong>category bars</strong> to reorder sections, and drag the <strong>products</strong> within each section. Click <em>Save Order</em>, then <em>Refresh Catalog</em> above.', 'ah-ho-custom'); ?>
    </div>
    <p>
        <button type="button" id="ah-ho-save-order" class="button button-primary"><?php _e('Save Order', 'ah-ho-custom'); ?></button>
        <span id="ah-ho-order-status" style="margin-left: 12px; display: none;"></span>
    </p>

    <ul id="ah-ho-cat-sortable" style="list-style: none; margin: 0; padding: 0; max-width: 800px;">
        <?php foreach ($categories as $category) :
            $products = wc_get_products(array(
                'status'     => 'publish',
                'category'   => array($category->slug),
                'limit'      => -1,
                'meta_query' => array(array('key' => '_wholesale_price', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC')),
            ));
            if (empty($products)) {
                continue;
            }
            $products = ah_ho_sort_products_for_catalog($products);
            ?>
            <li data-slug="<?php echo esc_attr($category->slug); ?>" style="margin: 0 0 10px; border: 1px solid #ccd0d4; background: #fff;">
                <div class="ah-ho-cat-handle" style="cursor: move; padding: 10px 14px; background: #f0f0f1; font-weight: 600; border-bottom: 1px solid #ccd0d4;">
                    <span class="dashicons dashicons-menu" style="vertical-align: middle;"></span>
                    <?php echo esc_html(strtoupper($category->name)); ?>
                    <span style="font-weight: 400; color: #777;">(<?php echo count($products); ?>)</span>
                </div>
                <ul class="ah-ho-prod-sortable" style="list-style: none; margin: 0; padding: 8px 14px;">
                    <?php foreach ($products as $product) : ?>
                        <li data-id="<?php echo esc_attr($product->get_id()); ?>" style="cursor: move; padding: 6px 8px; border-bottom: 1px solid #f1f1f1;">
                            <span class="dashicons dashicons-sort" style="vertical-align: middle; color: #999;"></span>
                            <?php echo esc_html($product->get_name()); ?>
                            <span style="color: #777;">@ $<?php echo esc_html(number_format((float) $product->get_meta('_wholesale_price'), 2)); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

/* -------------------------------------------------------------------------
 * AJAX: save catalog order
 * ---------------------------------------------------------------------- */

add_action('wp_ajax_ah_ho_save_catalog_order', 'ah_ho_ajax_save_catalog_order');
function ah_ho_ajax_save_catalog_order() {
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Permission denied');
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ah_ho_catalog_order')) {
        wp_send_json_error('Invalid nonce');
    }
    $categories = isset($_POST['categories']) ? (array) $_POST['categories'] : array();
    $products   = isset($_POST['products']) ? (array) $_POST['products'] : array();

    $categories = array_values(array_filter(array_map('sanitize_title', wp_unslash($categories))));
    $products   = array_values(array_filter(array_map('intval', $products)));

    update_option(AH_HO_CAT_ORDER_OPT, $categories, false);
    update_option(AH_HO_PROD_ORDER_OPT, $products, false);

    wp_send_json_success(array('categories' => count($categories), 'products' => count($products)));
}

/* -------------------------------------------------------------------------
 * Catalog text generation (now uses the saved order)
 * ---------------------------------------------------------------------- */

function ah_ho_generate_catalog_text() {
    $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true));
    if (is_wp_error($categories) || empty($categories)) {
        return __('No products available.', 'ah-ho-custom');
    }

    $category_emojis = ah_ho_catalog_category_emojis();
    $excluded_slugs  = ah_ho_catalog_excluded_slugs();
    $categories      = ah_ho_sort_categories_for_catalog($categories);

    $output  = "*AH HO FRUIT - WHOLESALE PRICE LIST*\n";
    $output .= "_Prices exclusive of GST_\n";
    $output .= "_B2B wholesale prices only_\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━\n\n";

    foreach ($categories as $category) {
        if (in_array($category->slug, $excluded_slugs, true)) {
            continue;
        }
        $products = wc_get_products(array(
            'status'       => 'publish',
            'stock_status' => 'instock',
            'category'     => array($category->slug),
            'limit'        => -1,
            'meta_query'   => array(
                array('key' => '_wholesale_price', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC'),
            ),
        ));
        if (empty($products)) {
            continue;
        }
        $products = ah_ho_sort_products_for_catalog($products);

        $lines = '';
        foreach ($products as $product) {
            $wholesale_price = $product->get_meta('_wholesale_price');
            if (empty($wholesale_price) || floatval($wholesale_price) <= 0) {
                continue;
            }
            $lines .= sprintf("%s @ $%.2f\n", $product->get_name(), floatval($wholesale_price));
        }
        if (empty($lines)) {
            continue;
        }

        $emoji_left = '';
        $emoji_right = '';
        foreach ($category_emojis as $key => $icons) {
            if (stripos($category->slug, $key) !== false || stripos($category->name, $key) !== false) {
                $emoji_left = $icons[0] . ' ';
                $emoji_right = ' ' . $icons[1];
                break;
            }
        }

        $output .= sprintf("*%s%s%s*\n", $emoji_left, strtoupper($category->name), $emoji_right);
        $output .= $lines;
        $output .= "\n";
    }

    $output .= "━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "_Last updated: " . current_time('d M Y, g:i A') . "_\n";
    $output .= "_Contact us to place your order!_";

    return $output;
}

function ah_ho_generate_stock_catalog_text() {
    $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true));
    if (is_wp_error($categories) || empty($categories)) {
        return __('No products available.', 'ah-ho-custom');
    }

    $category_emojis = ah_ho_catalog_category_emojis();
    $excluded_slugs  = ah_ho_catalog_excluded_slugs();
    $categories      = ah_ho_sort_categories_for_catalog($categories);

    $output  = "*AH HO FRUIT - B2B STOCK LIST*\n";
    $output .= "_Internal use only - Do not share_\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━\n\n";

    foreach ($categories as $category) {
        if (in_array($category->slug, $excluded_slugs, true)) {
            continue;
        }
        $products = wc_get_products(array(
            'status'       => 'publish',
            'stock_status' => 'instock',
            'category'     => array($category->slug),
            'limit'        => -1,
        ));
        if (empty($products)) {
            continue;
        }

        $b2b_products = array_filter($products, function ($product) {
            $wholesale_price = $product->get_meta('_wholesale_price');
            return $wholesale_price !== '' && $wholesale_price !== null && $wholesale_price !== false;
        });
        if (empty($b2b_products)) {
            continue;
        }
        $b2b_products = ah_ho_sort_products_for_catalog(array_values($b2b_products));

        $emoji_left = '';
        $emoji_right = '';
        foreach ($category_emojis as $key => $icons) {
            if (stripos($category->slug, $key) !== false || stripos($category->name, $key) !== false) {
                $emoji_left = $icons[0] . ' ';
                $emoji_right = ' ' . $icons[1];
                break;
            }
        }

        $output .= sprintf("*%s%s%s*\n", $emoji_left, strtoupper($category->name), $emoji_right);

        foreach ($b2b_products as $product) {
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

/* -------------------------------------------------------------------------
 * AJAX: refresh catalog text (existing)
 * ---------------------------------------------------------------------- */

add_action('wp_ajax_ah_ho_refresh_catalog', 'ah_ho_ajax_refresh_catalog');
function ah_ho_ajax_refresh_catalog() {
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(__('Permission denied', 'ah-ho-custom'));
    }
    wp_send_json_success(array('catalog' => ah_ho_generate_catalog_text()));
}
