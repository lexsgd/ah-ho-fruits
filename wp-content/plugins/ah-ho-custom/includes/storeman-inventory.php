<?php
/**
 * Storeman Quick Stock Update
 *
 * Provides a bulk inventory management page where storeman can view all products
 * in a single table and update stock quantities inline with one "Update All" button.
 *
 * Features: column sorting, stock status filters, sticky header/footer,
 * +/- buttons, client-side category filter, tab navigation, unsaved changes warning.
 *
 * @package AhHoCustom
 * @since 1.7.0
 * @modified 2026-02-08 - major UX improvements for storeman efficiency
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register "Quick Stock Update" submenu under Products
 */
add_action('admin_menu', 'ah_ho_register_stock_update_page');

function ah_ho_register_stock_update_page() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Quick Stock Update',
        'Quick Stock Update',
        'edit_products',
        'ah-ho-quick-stock',
        'ah_ho_render_quick_stock_page'
    );
}

/**
 * AJAX handler for bulk stock updates
 */
add_action('wp_ajax_ah_ho_bulk_update_stock', 'ah_ho_bulk_update_stock_handler');

function ah_ho_bulk_update_stock_handler() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ah_ho_stock_update_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    // Capability check - must be admin or storeman (not salesperson)
    if (!current_user_can('edit_products') || (!current_user_can('manage_options') && !ah_ho_is_current_user_storeman())) {
        wp_send_json_error(array('message' => 'You do not have permission to update stock.'));
    }

    // Parse updates
    $updates_raw = isset($_POST['updates']) ? $_POST['updates'] : '';
    $updates = json_decode(stripslashes($updates_raw), true);

    if (!is_array($updates) || empty($updates)) {
        wp_send_json_error(array('message' => 'No updates provided.'));
    }

    $success_count = 0;
    $errors = array();

    foreach ($updates as $update) {
        $product_id = isset($update['product_id']) ? absint($update['product_id']) : 0;
        $new_qty = isset($update['new_qty']) ? intval($update['new_qty']) : 0;

        if ($new_qty < 0) {
            $new_qty = 0;
        }

        if (!$product_id) {
            $errors[] = 'Invalid product ID.';
            continue;
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            $errors[] = "Product #{$product_id} not found.";
            continue;
        }

        $product->set_stock_quantity($new_qty);
        $product->set_manage_stock(true);
        $product->set_stock_status($new_qty > 0 ? 'instock' : 'outofstock');
        $product->save();
        $success_count++;
    }

    wp_send_json_success(array(
        'message' => "{$success_count} product(s) updated successfully.",
        'updated' => $success_count,
        'errors' => $errors,
    ));
}

/**
 * Render the Quick Stock Update page
 */
function ah_ho_render_quick_stock_page() {
    // Block salesperson direct URL access - only admin and storeman allowed
    if (!current_user_can('manage_options') && !ah_ho_is_current_user_storeman()) {
        wp_die(__('You do not have permission to access this page.', 'ah-ho-custom'), 403);
    }

    // Always load ALL products (filtering is client-side)
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );

    $products_query = new WP_Query($args);
    $products_data = array();
    $total = 0;
    $in_stock = 0;
    $out_of_stock = 0;
    $low_stock = 0;
    $low_stock_threshold = 5;

    if ($products_query->have_posts()) {
        while ($products_query->have_posts()) {
            $products_query->the_post();
            $product = wc_get_product(get_the_ID());
            if (!$product) continue;

            $stock_qty = $product->get_stock_quantity();
            $stock_qty = $stock_qty !== null ? (int) $stock_qty : 0;

            $total++;
            if ($stock_qty <= 0) {
                $out_of_stock++;
            } else {
                $in_stock++;
                if ($stock_qty <= $low_stock_threshold) {
                    $low_stock++;
                }
            }

            // Get category names
            $cat_terms = get_the_terms(get_the_ID(), 'product_cat');
            $cat_names = '';
            if ($cat_terms && !is_wp_error($cat_terms)) {
                $cat_names = implode(', ', wp_list_pluck($cat_terms, 'name'));
            }

            // Get thumbnail URL
            $thumb_id = $product->get_image_id();
            $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : '';

            $products_data[] = array(
                'id'         => get_the_ID(),
                'name'       => $product->get_name(),
                'sku'        => $product->get_sku(),
                'stock'      => $stock_qty,
                'categories' => $cat_names,
                'thumb'      => $thumb_url,
            );
        }
    }
    wp_reset_postdata();

    // Get all product categories for filter dropdown
    $categories = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'orderby'    => 'name',
    ));

    $nonce = wp_create_nonce('ah_ho_stock_update_nonce');
    ?>
    <div class="wrap" id="ah-ho-stock-wrap">
        <h1 style="margin-bottom: 4px;">Quick Stock Update</h1>

        <style>
            /* ===== Summary Cards ===== */
            .ah-ho-summary-cards {
                display: flex;
                gap: 10px;
                margin: 12px 0;
                flex-wrap: wrap;
            }
            .ah-ho-summary-card {
                background: #fff;
                border: 2px solid #c3c4c7;
                border-left: 4px solid #2271b1;
                padding: 10px 16px;
                min-width: 120px;
                border-radius: 2px;
                cursor: pointer;
                transition: all 0.15s;
                user-select: none;
            }
            .ah-ho-summary-card:hover {
                border-color: #2271b1;
                box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            }
            .ah-ho-summary-card.active {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                background: #f0f6fc;
            }
            .ah-ho-summary-card.in-stock { border-left-color: #00a32a; }
            .ah-ho-summary-card.in-stock.active { border-color: #00a32a; box-shadow: 0 0 0 1px #00a32a; }
            .ah-ho-summary-card.out-of-stock { border-left-color: #d63638; }
            .ah-ho-summary-card.out-of-stock.active { border-color: #d63638; box-shadow: 0 0 0 1px #d63638; }
            .ah-ho-summary-card.low-stock { border-left-color: #dba617; }
            .ah-ho-summary-card.low-stock.active { border-color: #dba617; box-shadow: 0 0 0 1px #dba617; }
            .ah-ho-summary-card .card-number {
                font-size: 26px;
                font-weight: 600;
                line-height: 1.2;
            }
            .ah-ho-summary-card .card-label {
                color: #50575e;
                font-size: 12px;
            }

            /* ===== Filters Row ===== */
            .ah-ho-filters {
                display: flex;
                gap: 10px;
                align-items: center;
                margin: 12px 0;
                flex-wrap: wrap;
            }
            .ah-ho-filters label {
                font-weight: 600;
                font-size: 13px;
            }
            .ah-ho-filters select,
            .ah-ho-filters input[type="search"] {
                height: 32px;
                font-size: 13px;
            }
            .ah-ho-filters input[type="search"] {
                min-width: 220px;
            }
            #ah-ho-visible-count {
                color: #50575e;
                font-size: 13px;
            }

            /* ===== Stock Table ===== */
            .ah-ho-table-wrap {
                max-height: calc(100vh - 320px);
                overflow-y: auto;
                border: 1px solid #c3c4c7;
                background: #fff;
            }
            .ah-ho-stock-table {
                border-collapse: collapse;
                width: 100%;
                background: #fff;
                border: none;
            }
            .ah-ho-stock-table thead {
                position: sticky;
                top: 0;
                z-index: 10;
            }
            .ah-ho-stock-table th {
                background: #23282d;
                color: #fff;
                padding: 8px 10px;
                text-align: left;
                font-weight: 600;
                font-size: 12px;
                border-bottom: 2px solid #000;
                white-space: nowrap;
                cursor: pointer;
                user-select: none;
            }
            .ah-ho-stock-table th:hover {
                background: #32373c;
            }
            .ah-ho-stock-table th .sort-arrow {
                margin-left: 4px;
                font-size: 10px;
                opacity: 0.4;
            }
            .ah-ho-stock-table th.sort-active .sort-arrow {
                opacity: 1;
            }
            .ah-ho-stock-table th.no-sort {
                cursor: default;
            }
            .ah-ho-stock-table th.no-sort:hover {
                background: #23282d;
            }
            .ah-ho-stock-table td {
                padding: 6px 10px;
                text-align: left;
                border-bottom: 1px solid #f0f0f1;
                font-size: 13px;
                vertical-align: middle;
            }
            .ah-ho-stock-table tr:hover {
                background: #f6f7f7;
            }
            .ah-ho-stock-table tr.ah-ho-changed {
                background: #fff8e5 !important;
            }
            .ah-ho-stock-table tr.ah-ho-saved {
                background: #edfaef !important;
                transition: background 0.5s ease;
            }

            /* Stock colors */
            .stock-zero { color: #d63638; font-weight: 700; }
            .stock-low { color: #dba617; font-weight: 700; }
            .stock-ok { color: #00a32a; font-weight: 600; }

            /* ===== +/- Input Group ===== */
            .stock-input-group {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0;
            }
            .stock-input-group button {
                width: 28px;
                height: 28px;
                border: 1px solid #8c8f94;
                background: #f0f0f1;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #50575e;
                padding: 0;
                line-height: 1;
            }
            .stock-input-group button:hover {
                background: #dcdcde;
                color: #1d2327;
            }
            .stock-input-group button.btn-minus {
                border-radius: 3px 0 0 3px;
                border-right: none;
            }
            .stock-input-group button.btn-plus {
                border-radius: 0 3px 3px 0;
                border-left: none;
            }
            .stock-input-group input[type="number"] {
                width: 60px;
                height: 28px;
                padding: 2px 4px;
                text-align: center;
                font-size: 13px;
                font-weight: 600;
                border: 1px solid #8c8f94;
                border-radius: 0;
                -moz-appearance: textfield;
            }
            .stock-input-group input[type="number"]::-webkit-outer-spin-button,
            .stock-input-group input[type="number"]::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            /* ===== Thumbnail ===== */
            .product-thumb {
                width: 32px;
                height: 32px;
                object-fit: cover;
                border-radius: 3px;
                border: 1px solid #ddd;
                vertical-align: middle;
            }
            .product-thumb-placeholder {
                width: 32px;
                height: 32px;
                background: #f0f0f1;
                border-radius: 3px;
                border: 1px solid #ddd;
                display: inline-block;
                vertical-align: middle;
            }

            /* ===== Sticky Footer Bar ===== */
            .ah-ho-sticky-footer {
                position: sticky;
                bottom: 0;
                background: #fff;
                border-top: 2px solid #c3c4c7;
                padding: 10px 16px;
                display: flex;
                align-items: center;
                gap: 12px;
                z-index: 20;
                box-shadow: 0 -2px 6px rgba(0,0,0,0.08);
            }
            .ah-ho-sticky-footer .button-primary {
                font-size: 14px;
                padding: 6px 24px;
                height: auto;
            }
            #ah-ho-update-status { font-weight: 600; }
            #ah-ho-update-status.success { color: #00a32a; }
            #ah-ho-update-status.error { color: #d63638; }
            #ah-ho-changed-count {
                color: #b32d2e;
                font-weight: 600;
                font-size: 13px;
            }

            /* ===== Misc ===== */
            .ah-ho-reset-btn {
                color: #b32d2e;
                cursor: pointer;
                text-decoration: underline;
                font-size: 13px;
                background: none;
                border: none;
                padding: 0;
            }
            .ah-ho-reset-btn:hover {
                color: #d63638;
            }

            /* ===== MOBILE RESPONSIVE ===== */
            @media screen and (max-width: 782px) {
                /* Summary cards: 2x2 grid, tighter */
                .ah-ho-summary-cards {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 6px;
                    margin: 8px 0;
                }
                .ah-ho-summary-card {
                    padding: 8px 10px;
                    min-width: 0;
                }
                .ah-ho-summary-card .card-number {
                    font-size: 22px;
                }
                .ah-ho-summary-card .card-label {
                    font-size: 11px;
                }

                /* Filters: stack vertically */
                .ah-ho-filters {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 6px;
                    margin: 8px 0;
                }
                .ah-ho-filters label {
                    display: none;
                }
                .ah-ho-filters select,
                .ah-ho-filters input[type="search"] {
                    width: 100%;
                    min-width: 0;
                    height: 36px;
                    font-size: 14px;
                }
                #ah-ho-visible-count {
                    text-align: center;
                    font-size: 12px;
                }

                /* Table: hide thumbnail and category columns */
                .ah-ho-stock-table th:nth-child(1),
                .ah-ho-stock-table td:nth-child(1),
                .ah-ho-stock-table th:nth-child(3),
                .ah-ho-stock-table td:nth-child(3) {
                    display: none;
                }

                /* Tighter table cells */
                .ah-ho-stock-table th {
                    padding: 6px 4px;
                    font-size: 11px;
                }
                .ah-ho-stock-table td {
                    padding: 5px 4px;
                    font-size: 12px;
                }

                /* Hide SKU on mobile */
                .ah-ho-stock-table td:nth-child(2) small {
                    display: none;
                }

                /* Stock column: narrower */
                .ah-ho-stock-table th:nth-child(4),
                .ah-ho-stock-table td:nth-child(4) {
                    width: 40px;
                    font-size: 13px;
                }

                /* +/- input group: compact */
                .stock-input-group button {
                    width: 32px;
                    height: 32px;
                    font-size: 18px;
                }
                .stock-input-group input[type="number"] {
                    width: 48px;
                    height: 32px;
                    font-size: 14px;
                }

                /* Table wrapper: taller on mobile */
                .ah-ho-table-wrap {
                    max-height: calc(100vh - 280px);
                }

                /* Sticky footer: compact */
                .ah-ho-sticky-footer {
                    padding: 8px 10px;
                    gap: 8px;
                    flex-wrap: wrap;
                }
                .ah-ho-sticky-footer .button-primary {
                    font-size: 13px;
                    padding: 6px 16px;
                    width: 100%;
                    text-align: center;
                }
                #ah-ho-changed-count {
                    font-size: 12px;
                    width: 100%;
                    text-align: center;
                }
                .ah-ho-reset-btn {
                    font-size: 12px;
                    width: 100%;
                    text-align: center;
                }

                /* Page title */
                #ah-ho-stock-wrap > h1 {
                    font-size: 18px;
                }
            }
        </style>

        <!-- Summary Cards (clickable filters) -->
        <div class="ah-ho-summary-cards">
            <div class="ah-ho-summary-card active" data-filter="all" onclick="ahHoFilterByStatus('all')">
                <div class="card-number"><?php echo esc_html($total); ?></div>
                <div class="card-label">All Products</div>
            </div>
            <div class="ah-ho-summary-card in-stock" data-filter="in" onclick="ahHoFilterByStatus('in')">
                <div class="card-number"><?php echo esc_html($in_stock); ?></div>
                <div class="card-label">In Stock</div>
            </div>
            <div class="ah-ho-summary-card low-stock" data-filter="low" onclick="ahHoFilterByStatus('low')">
                <div class="card-number"><?php echo esc_html($low_stock); ?></div>
                <div class="card-label">Low Stock (&le;<?php echo $low_stock_threshold; ?>)</div>
            </div>
            <div class="ah-ho-summary-card out-of-stock" data-filter="out" onclick="ahHoFilterByStatus('out')">
                <div class="card-number"><?php echo esc_html($out_of_stock); ?></div>
                <div class="card-label">Out of Stock</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="ah-ho-filters">
            <label for="ah-ho-cat-filter">Category:</label>
            <select id="ah-ho-cat-filter" onchange="ahHoApplyFilters()">
                <option value="">All Categories</option>
                <?php
                if (!is_wp_error($categories)) {
                    foreach ($categories as $cat) {
                        echo '<option value="' . esc_attr(strtolower($cat->name)) . '">'
                            . esc_html($cat->name) . ' (' . esc_html($cat->count) . ')'
                            . '</option>';
                    }
                }
                ?>
            </select>

            <label for="ah-ho-search">Search:</label>
            <input type="search" id="ah-ho-search" placeholder="Filter by product name or SKU..." oninput="ahHoApplyFilters()">

            <span id="ah-ho-visible-count"></span>
        </div>

        <!-- Products Table -->
        <div class="ah-ho-table-wrap">
            <table class="ah-ho-stock-table" id="ah-ho-stock-table">
                <thead>
                    <tr>
                        <th style="width: 36px;" class="no-sort"></th>
                        <th data-sort="name" class="sort-active" style="width: auto;">
                            Product <span class="sort-arrow">&#9650;</span>
                        </th>
                        <th data-sort="category" style="width: 120px;">
                            Category <span class="sort-arrow">&#9650;</span>
                        </th>
                        <th data-sort="stock" style="width: 80px; text-align: center;">
                            Stock <span class="sort-arrow">&#9650;</span>
                        </th>
                        <th style="width: 140px; text-align: center;" class="no-sort">New Stock</th>
                    </tr>
                </thead>
                <tbody id="ah-ho-stock-tbody">
                    <?php if (empty($products_data)) : ?>
                        <tr><td colspan="5" style="text-align: center; padding: 24px;">No products found.</td></tr>
                    <?php else : ?>
                        <?php $tab_index = 1; foreach ($products_data as $p) : ?>
                            <tr data-product-id="<?php echo esc_attr($p['id']); ?>"
                                data-name="<?php echo esc_attr(strtolower($p['name'])); ?>"
                                data-sku="<?php echo esc_attr(strtolower($p['sku'])); ?>"
                                data-categories="<?php echo esc_attr(strtolower($p['categories'])); ?>"
                                data-stock="<?php echo esc_attr($p['stock']); ?>">
                                <td>
                                    <?php if ($p['thumb']): ?>
                                        <img src="<?php echo esc_url($p['thumb']); ?>" alt="" class="product-thumb">
                                    <?php else: ?>
                                        <span class="product-thumb-placeholder"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($p['id']); ?>" target="_blank" style="text-decoration: none;">
                                        <?php echo esc_html($p['name']); ?>
                                    </a>
                                    <?php if ($p['sku']): ?>
                                        <br><small style="color: #999;"><?php echo esc_html($p['sku']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 12px;"><?php echo esc_html($p['categories'] ?: '-'); ?></td>
                                <td style="text-align: center;">
                                    <?php
                                    $stock_class = 'stock-ok';
                                    if ($p['stock'] <= 0) {
                                        $stock_class = 'stock-zero';
                                    } elseif ($p['stock'] <= $low_stock_threshold) {
                                        $stock_class = 'stock-low';
                                    }
                                    ?>
                                    <span class="<?php echo $stock_class; ?>"><?php echo esc_html($p['stock']); ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <div class="stock-input-group">
                                        <button type="button" class="btn-minus" onclick="ahHoAdjustStock(this, -1)" tabindex="-1">&minus;</button>
                                        <input type="number"
                                               name="stock_<?php echo esc_attr($p['id']); ?>"
                                               value="<?php echo esc_attr($p['stock']); ?>"
                                               data-original="<?php echo esc_attr($p['stock']); ?>"
                                               min="0"
                                               step="1"
                                               tabindex="<?php echo $tab_index++; ?>">
                                        <button type="button" class="btn-plus" onclick="ahHoAdjustStock(this, 1)" tabindex="-1">&plus;</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($products_data)) : ?>
            <!-- Sticky Footer Action Bar -->
            <div class="ah-ho-sticky-footer">
                <button type="button" id="ah-ho-update-btn" class="button button-primary" onclick="ahHoUpdateAllStock()">
                    Update All Stock
                </button>
                <span id="ah-ho-changed-count"></span>
                <button type="button" class="ah-ho-reset-btn" id="ah-ho-reset-btn" onclick="ahHoResetAll()" style="display:none;">
                    Reset all changes
                </button>
                <span id="ah-ho-update-status"></span>
            </div>
        <?php endif; ?>

        <script>
        (function() {
            var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = '<?php echo $nonce; ?>';
            var LOW_THRESHOLD = <?php echo $low_stock_threshold; ?>;
            var activeStatus = 'all';
            var currentSort = { col: 'name', dir: 'asc' };
            var hasUnsavedChanges = false;

            // ===== UNSAVED CHANGES WARNING =====
            window.addEventListener('beforeunload', function(e) {
                if (hasUnsavedChanges) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // ===== CHANGE TRACKING =====
            var inputs = document.querySelectorAll('#ah-ho-stock-tbody input[type="number"]');
            inputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    var row = this.closest('tr');
                    if (this.value !== this.getAttribute('data-original')) {
                        row.classList.add('ah-ho-changed');
                        row.classList.remove('ah-ho-saved');
                    } else {
                        row.classList.remove('ah-ho-changed');
                    }
                    updateChangedCount();
                });
            });

            function updateChangedCount() {
                var changed = document.querySelectorAll('#ah-ho-stock-tbody tr.ah-ho-changed').length;
                hasUnsavedChanges = changed > 0;
                var btn = document.getElementById('ah-ho-update-btn');
                var countEl = document.getElementById('ah-ho-changed-count');
                var resetBtn = document.getElementById('ah-ho-reset-btn');
                if (btn) {
                    btn.textContent = changed > 0 ? 'Update All Stock (' + changed + ')' : 'Update All Stock';
                }
                if (countEl) {
                    countEl.textContent = changed > 0 ? changed + ' unsaved change' + (changed > 1 ? 's' : '') : '';
                }
                if (resetBtn) {
                    resetBtn.style.display = changed > 0 ? 'inline' : 'none';
                }
            }

            // ===== +/- BUTTONS =====
            window.ahHoAdjustStock = function(btn, delta) {
                var group = btn.closest('.stock-input-group');
                var input = group.querySelector('input[type="number"]');
                var val = parseInt(input.value) || 0;
                val = Math.max(0, val + delta);
                input.value = val;
                input.dispatchEvent(new Event('input'));
            };

            // ===== RESET ALL CHANGES =====
            window.ahHoResetAll = function() {
                var inputs = document.querySelectorAll('#ah-ho-stock-tbody input[type="number"]');
                inputs.forEach(function(input) {
                    input.value = input.getAttribute('data-original');
                    input.closest('tr').classList.remove('ah-ho-changed');
                });
                updateChangedCount();
            };

            // ===== COMPOSABLE FILTERS =====
            window.ahHoFilterByStatus = function(status) {
                activeStatus = status;
                // Update card active state
                document.querySelectorAll('.ah-ho-summary-card').forEach(function(card) {
                    card.classList.toggle('active', card.getAttribute('data-filter') === status);
                });
                ahHoApplyFilters();
            };

            window.ahHoApplyFilters = function() {
                var searchQuery = (document.getElementById('ah-ho-search').value || '').toLowerCase().trim();
                var catFilter = (document.getElementById('ah-ho-cat-filter').value || '').toLowerCase();
                var rows = document.querySelectorAll('#ah-ho-stock-tbody tr[data-product-id]');
                var visible = 0;

                rows.forEach(function(row) {
                    var name = row.getAttribute('data-name') || '';
                    var sku = row.getAttribute('data-sku') || '';
                    var cats = row.getAttribute('data-categories') || '';
                    var stock = parseInt(row.getAttribute('data-stock')) || 0;

                    // Use the live input value for stock status filtering
                    var input = row.querySelector('input[type="number"]');
                    if (input) {
                        stock = parseInt(input.getAttribute('data-original')) || 0;
                    }

                    var passSearch = !searchQuery || name.indexOf(searchQuery) !== -1 || sku.indexOf(searchQuery) !== -1;
                    var passCat = !catFilter || cats.indexOf(catFilter) !== -1;
                    var passStatus = true;

                    if (activeStatus === 'out') {
                        passStatus = stock <= 0;
                    } else if (activeStatus === 'low') {
                        passStatus = stock > 0 && stock <= LOW_THRESHOLD;
                    } else if (activeStatus === 'in') {
                        passStatus = stock > 0;
                    }

                    if (passSearch && passCat && passStatus) {
                        row.style.display = '';
                        visible++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                var countEl = document.getElementById('ah-ho-visible-count');
                if (countEl) {
                    var total = rows.length;
                    countEl.textContent = (visible < total) ? 'Showing ' + visible + ' of ' + total : '';
                }

                updateRowNumbers();
            };

            // ===== ROW NUMBERS =====
            function updateRowNumbers() {
                // Row numbers not used in new design (replaced by thumbnail)
            }

            // ===== COLUMN SORTING =====
            document.querySelectorAll('.ah-ho-stock-table th[data-sort]').forEach(function(th) {
                th.addEventListener('click', function() {
                    var col = this.getAttribute('data-sort');
                    var dir = 'asc';
                    if (currentSort.col === col && currentSort.dir === 'asc') {
                        dir = 'desc';
                    }
                    currentSort = { col: col, dir: dir };

                    // Update header styling
                    document.querySelectorAll('.ah-ho-stock-table th').forEach(function(h) {
                        h.classList.remove('sort-active');
                        var arrow = h.querySelector('.sort-arrow');
                        if (arrow) arrow.innerHTML = '&#9650;';
                    });
                    this.classList.add('sort-active');
                    var arrow = this.querySelector('.sort-arrow');
                    if (arrow) {
                        arrow.innerHTML = dir === 'asc' ? '&#9650;' : '&#9660;';
                    }

                    sortTable(col, dir);
                });
            });

            function sortTable(col, dir) {
                var tbody = document.getElementById('ah-ho-stock-tbody');
                var rows = Array.from(tbody.querySelectorAll('tr[data-product-id]'));

                rows.sort(function(a, b) {
                    var valA, valB;
                    if (col === 'stock') {
                        valA = parseInt(a.getAttribute('data-stock')) || 0;
                        valB = parseInt(b.getAttribute('data-stock')) || 0;
                        return dir === 'asc' ? valA - valB : valB - valA;
                    } else if (col === 'name') {
                        valA = a.getAttribute('data-name') || '';
                        valB = b.getAttribute('data-name') || '';
                    } else if (col === 'category') {
                        valA = a.getAttribute('data-categories') || '';
                        valB = b.getAttribute('data-categories') || '';
                    } else {
                        return 0;
                    }
                    var cmp = valA.localeCompare(valB);
                    return dir === 'asc' ? cmp : -cmp;
                });

                rows.forEach(function(row) {
                    tbody.appendChild(row);
                });
            }

            // ===== TAB NAVIGATION =====
            document.getElementById('ah-ho-stock-tbody').addEventListener('keydown', function(e) {
                if (e.target.tagName !== 'INPUT') return;

                var allInputs = Array.from(document.querySelectorAll('#ah-ho-stock-tbody tr[data-product-id]:not([style*="display: none"]) input[type="number"]'));
                var currentIndex = allInputs.indexOf(e.target);

                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentIndex < allInputs.length - 1) {
                        allInputs[currentIndex + 1].focus();
                        allInputs[currentIndex + 1].select();
                    }
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (currentIndex < allInputs.length - 1) {
                        allInputs[currentIndex + 1].focus();
                        allInputs[currentIndex + 1].select();
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (currentIndex > 0) {
                        allInputs[currentIndex - 1].focus();
                        allInputs[currentIndex - 1].select();
                    }
                }
            });

            // ===== BULK UPDATE VIA AJAX =====
            window.ahHoUpdateAllStock = function() {
                var inputs = document.querySelectorAll('#ah-ho-stock-tbody input[type="number"]');
                var updates = [];

                inputs.forEach(function(input) {
                    var original = input.getAttribute('data-original');
                    if (input.value !== original) {
                        var row = input.closest('tr');
                        updates.push({
                            product_id: parseInt(row.getAttribute('data-product-id')),
                            new_qty: parseInt(input.value) || 0
                        });
                    }
                });

                if (updates.length === 0) {
                    var statusEl = document.getElementById('ah-ho-update-status');
                    statusEl.textContent = 'No changes to save.';
                    statusEl.className = '';
                    setTimeout(function() { statusEl.textContent = ''; }, 3000);
                    return;
                }

                // Confirmation
                if (!confirm('Update stock for ' + updates.length + ' product(s)?')) {
                    return;
                }

                var btn = document.getElementById('ah-ho-update-btn');
                btn.disabled = true;
                btn.textContent = 'Updating...';

                var statusEl = document.getElementById('ah-ho-update-status');
                statusEl.textContent = '';
                statusEl.className = '';

                var formData = new FormData();
                formData.append('action', 'ah_ho_bulk_update_stock');
                formData.append('nonce', nonce);
                formData.append('updates', JSON.stringify(updates));

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function(response) { return response.json(); })
                .then(function(result) {
                    btn.disabled = false;

                    if (result.success) {
                        statusEl.textContent = result.data.message;
                        statusEl.className = 'success';

                        // Update original values, current stock display, and flash green
                        inputs.forEach(function(input) {
                            var row = input.closest('tr');
                            if (row.classList.contains('ah-ho-changed')) {
                                var val = parseInt(input.value) || 0;
                                input.setAttribute('data-original', val);
                                row.setAttribute('data-stock', val);

                                // Update current stock column
                                var stockCell = row.querySelector('td:nth-child(4) span');
                                if (stockCell) {
                                    stockCell.textContent = val;
                                    stockCell.className = val <= 0 ? 'stock-zero' : (val <= LOW_THRESHOLD ? 'stock-low' : 'stock-ok');
                                }

                                row.classList.remove('ah-ho-changed');
                                row.classList.add('ah-ho-saved');
                                setTimeout(function() { row.classList.remove('ah-ho-saved'); }, 2000);
                            }
                        });

                        updateChangedCount();
                        updateSummaryCards();
                    } else {
                        statusEl.textContent = result.data ? result.data.message : 'Update failed.';
                        statusEl.className = 'error';
                    }
                })
                .catch(function(err) {
                    btn.disabled = false;
                    statusEl.textContent = 'Network error. Please try again.';
                    statusEl.className = 'error';
                });

                btn.textContent = 'Update All Stock';
            };

            // ===== UPDATE SUMMARY CARDS AFTER SAVE =====
            function updateSummaryCards() {
                var rows = document.querySelectorAll('#ah-ho-stock-tbody tr[data-product-id]');
                var total = 0, inStock = 0, outStock = 0, lowStock = 0;
                rows.forEach(function(row) {
                    var stock = parseInt(row.getAttribute('data-stock')) || 0;
                    total++;
                    if (stock <= 0) {
                        outStock++;
                    } else {
                        inStock++;
                        if (stock <= LOW_THRESHOLD) {
                            lowStock++;
                        }
                    }
                });

                var cards = document.querySelectorAll('.ah-ho-summary-card');
                cards.forEach(function(card) {
                    var filter = card.getAttribute('data-filter');
                    var num = card.querySelector('.card-number');
                    if (filter === 'all') num.textContent = total;
                    else if (filter === 'in') num.textContent = inStock;
                    else if (filter === 'low') num.textContent = lowStock;
                    else if (filter === 'out') num.textContent = outStock;
                });
            }

        })();
        </script>
    </div>
    <?php
}
