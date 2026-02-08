<?php
/**
 * Storeman Quick Stock Update
 *
 * Provides a bulk inventory management page where storeman can view all products
 * in a single table and update stock quantities inline with one "Update All" button.
 *
 * @package AhHoCustom
 * @since 1.7.0
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

    // Capability check
    if (!current_user_can('edit_products')) {
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
    // Get selected category filter
    $selected_cat = isset($_GET['product_cat']) ? absint($_GET['product_cat']) : 0;

    // Query products
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );

    if ($selected_cat) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $selected_cat,
            ),
        );
    }

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
            $manages_stock = $product->get_manage_stock();

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

            $products_data[] = array(
                'id'            => get_the_ID(),
                'name'          => $product->get_name(),
                'sku'           => $product->get_sku(),
                'stock'         => $stock_qty,
                'manages_stock' => $manages_stock,
                'categories'    => $cat_names,
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
    <div class="wrap">
        <h1>Quick Stock Update</h1>

        <style>
            .ah-ho-summary-cards {
                display: flex;
                gap: 12px;
                margin: 16px 0;
                flex-wrap: wrap;
            }
            .ah-ho-summary-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-left: 4px solid #2271b1;
                padding: 12px 20px;
                min-width: 140px;
                border-radius: 2px;
            }
            .ah-ho-summary-card.in-stock { border-left-color: #00a32a; }
            .ah-ho-summary-card.out-of-stock { border-left-color: #d63638; }
            .ah-ho-summary-card.low-stock { border-left-color: #dba617; }
            .ah-ho-summary-card .card-number {
                font-size: 28px;
                font-weight: 600;
                line-height: 1.2;
            }
            .ah-ho-summary-card .card-label {
                color: #50575e;
                font-size: 13px;
            }
            .ah-ho-filters {
                display: flex;
                gap: 12px;
                align-items: center;
                margin: 16px 0;
                flex-wrap: wrap;
            }
            .ah-ho-filters label {
                font-weight: 600;
            }
            .ah-ho-filters input[type="search"] {
                min-width: 250px;
            }
            .ah-ho-stock-table {
                border-collapse: collapse;
                width: 100%;
                background: #fff;
                border: 1px solid #c3c4c7;
            }
            .ah-ho-stock-table th,
            .ah-ho-stock-table td {
                padding: 8px 12px;
                text-align: left;
                border-bottom: 1px solid #f0f0f1;
            }
            .ah-ho-stock-table th {
                background: #f6f7f7;
                font-weight: 600;
                border-bottom: 1px solid #c3c4c7;
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
            .ah-ho-stock-table input[type="number"] {
                width: 80px;
                padding: 4px 8px;
                text-align: center;
            }
            .ah-ho-stock-table .stock-zero {
                color: #d63638;
                font-weight: 600;
            }
            .ah-ho-stock-table .stock-low {
                color: #dba617;
                font-weight: 600;
            }
            .ah-ho-actions {
                margin: 16px 0;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .ah-ho-actions .button-primary {
                font-size: 14px;
                padding: 6px 24px;
                height: auto;
            }
            #ah-ho-update-status {
                font-weight: 600;
            }
            #ah-ho-update-status.success { color: #00a32a; }
            #ah-ho-update-status.error { color: #d63638; }
            .ah-ho-no-stock-mgmt {
                color: #999;
                font-style: italic;
            }
        </style>

        <!-- Summary Cards -->
        <div class="ah-ho-summary-cards">
            <div class="ah-ho-summary-card in-stock">
                <div class="card-number"><?php echo esc_html($in_stock); ?></div>
                <div class="card-label">In Stock</div>
            </div>
            <div class="ah-ho-summary-card out-of-stock">
                <div class="card-number"><?php echo esc_html($out_of_stock); ?></div>
                <div class="card-label">Out of Stock</div>
            </div>
            <div class="ah-ho-summary-card low-stock">
                <div class="card-number"><?php echo esc_html($low_stock); ?></div>
                <div class="card-label">Low Stock (&le;<?php echo $low_stock_threshold; ?>)</div>
            </div>
            <div class="ah-ho-summary-card">
                <div class="card-number"><?php echo esc_html($total); ?></div>
                <div class="card-label">Total Products</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="ah-ho-filters">
            <label for="ah-ho-cat-filter">Category:</label>
            <select id="ah-ho-cat-filter" onchange="ahHoFilterByCategory(this.value)">
                <option value="">All Categories</option>
                <?php
                if (!is_wp_error($categories)) {
                    foreach ($categories as $cat) {
                        $selected = ($selected_cat == $cat->term_id) ? ' selected' : '';
                        echo '<option value="' . esc_attr($cat->term_id) . '"' . $selected . '>'
                            . esc_html($cat->name) . ' (' . esc_html($cat->count) . ')'
                            . '</option>';
                    }
                }
                ?>
            </select>

            <label for="ah-ho-search">Search:</label>
            <input type="search" id="ah-ho-search" placeholder="Filter by product name or SKU..." oninput="ahHoSearchProducts(this.value)">

            <span id="ah-ho-visible-count" style="color: #50575e;"></span>
        </div>

        <!-- Products Table -->
        <form id="ah-ho-stock-form">
            <table class="ah-ho-stock-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th style="width: 100px; text-align: center;">Current Stock</th>
                        <th style="width: 120px; text-align: center;">New Stock</th>
                    </tr>
                </thead>
                <tbody id="ah-ho-stock-tbody">
                    <?php if (empty($products_data)) : ?>
                        <tr><td colspan="6" style="text-align: center; padding: 24px;">No products found.</td></tr>
                    <?php else : ?>
                        <?php $row_num = 1; foreach ($products_data as $p) : ?>
                            <tr data-product-id="<?php echo esc_attr($p['id']); ?>"
                                data-name="<?php echo esc_attr(strtolower($p['name'])); ?>"
                                data-sku="<?php echo esc_attr(strtolower($p['sku'])); ?>"
                                data-categories="<?php echo esc_attr(strtolower($p['categories'])); ?>">
                                <td><?php echo $row_num++; ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($p['id']); ?>" target="_blank">
                                        <?php echo esc_html($p['name']); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($p['sku'] ?: '-'); ?></td>
                                <td><?php echo esc_html($p['categories'] ?: '-'); ?></td>
                                <td style="text-align: center;">
                                    <?php
                                    $stock_class = '';
                                    if ($p['stock'] === 0) {
                                        $stock_class = 'stock-zero';
                                    } elseif ($p['stock'] <= $low_stock_threshold) {
                                        $stock_class = 'stock-low';
                                    }
                                    ?>
                                    <span class="<?php echo $stock_class; ?>"><?php echo esc_html($p['stock']); ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <input type="number"
                                           name="stock_<?php echo esc_attr($p['id']); ?>"
                                           value="<?php echo esc_attr($p['stock']); ?>"
                                           data-original="<?php echo esc_attr($p['stock']); ?>"
                                           min="0"
                                           step="1">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($products_data)) : ?>
                <div class="ah-ho-actions">
                    <button type="button" id="ah-ho-update-btn" class="button button-primary" onclick="ahHoUpdateAllStock()">
                        Update All Stock
                    </button>
                    <span id="ah-ho-update-status"></span>
                </div>
            <?php endif; ?>
        </form>

        <script>
        (function() {
            var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = '<?php echo $nonce; ?>';

            // Track changes - highlight row yellow when input changes
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
                var btn = document.getElementById('ah-ho-update-btn');
                if (btn) {
                    btn.textContent = changed > 0
                        ? 'Update All Stock (' + changed + ' changed)'
                        : 'Update All Stock';
                }
            }

            // Category filter via URL reload
            window.ahHoFilterByCategory = function(catId) {
                var url = new URL(window.location.href);
                if (catId) {
                    url.searchParams.set('product_cat', catId);
                } else {
                    url.searchParams.delete('product_cat');
                }
                window.location.href = url.toString();
            };

            // Client-side search filter
            window.ahHoSearchProducts = function(query) {
                query = query.toLowerCase().trim();
                var rows = document.querySelectorAll('#ah-ho-stock-tbody tr[data-product-id]');
                var visible = 0;
                rows.forEach(function(row) {
                    var name = row.getAttribute('data-name') || '';
                    var sku = row.getAttribute('data-sku') || '';
                    var cats = row.getAttribute('data-categories') || '';
                    if (!query || name.indexOf(query) !== -1 || sku.indexOf(query) !== -1 || cats.indexOf(query) !== -1) {
                        row.style.display = '';
                        visible++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                var countEl = document.getElementById('ah-ho-visible-count');
                if (countEl) {
                    countEl.textContent = query ? 'Showing ' + visible + ' of ' + rows.length : '';
                }
            };

            // Bulk update via AJAX
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

                        // Update original values and flash green
                        inputs.forEach(function(input) {
                            var row = input.closest('tr');
                            if (row.classList.contains('ah-ho-changed')) {
                                input.setAttribute('data-original', input.value);
                                // Update the "Current Stock" column
                                var currentStockCell = row.querySelector('td:nth-child(5) span');
                                if (currentStockCell) {
                                    var val = parseInt(input.value) || 0;
                                    currentStockCell.textContent = val;
                                    currentStockCell.className = val === 0 ? 'stock-zero' : (val <= 5 ? 'stock-low' : '');
                                }
                                row.classList.remove('ah-ho-changed');
                                row.classList.add('ah-ho-saved');
                                setTimeout(function() { row.classList.remove('ah-ho-saved'); }, 2000);
                            }
                        });
                        updateChangedCount();
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
        })();
        </script>
    </div>
    <?php
}
