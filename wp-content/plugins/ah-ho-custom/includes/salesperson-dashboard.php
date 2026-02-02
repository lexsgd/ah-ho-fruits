<?php
/**
 * Salesperson Commission Dashboards
 *
 * Provides admin and salesperson dashboard views for commission tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Recalculate commission for existing orders (admin tool)
 *
 * Trigger: Visit wp-admin with ?ah_ho_recalc_commission=1
 */
add_action('admin_init', 'ah_ho_maybe_recalculate_commissions');

function ah_ho_maybe_recalculate_commissions() {
    if (!isset($_GET['ah_ho_recalc_commission']) || $_GET['ah_ho_recalc_commission'] !== '1') {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    // Verify nonce if provided
    if (isset($_GET['_wpnonce']) && !wp_verify_nonce($_GET['_wpnonce'], 'ah_ho_recalc_commission')) {
        return;
    }

    global $wpdb;

    // Find orders with salesperson assigned but no commission calculated
    $orders_table = $wpdb->prefix . 'wc_orders';
    $meta_table = $wpdb->prefix . 'wc_orders_meta';

    $order_ids = $wpdb->get_col("
        SELECT DISTINCT o.id
        FROM {$orders_table} o
        INNER JOIN {$meta_table} sp ON o.id = sp.order_id AND sp.meta_key = '_assigned_salesperson_id'
        LEFT JOIN {$meta_table} ca ON o.id = ca.order_id AND ca.meta_key = '_commission_amount'
        WHERE o.type = 'shop_order'
        AND (ca.meta_value IS NULL OR ca.meta_value = '' OR ca.meta_value = '0')
    ");

    $recalculated = 0;

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;

        $salesperson_id = $order->get_meta('_assigned_salesperson_id', true);
        if (!$salesperson_id) continue;

        // Get commission rate
        $enable_custom_rates = get_option('ah_ho_enable_custom_rates', true);
        $rate = null;

        if ($enable_custom_rates) {
            $rate = get_user_meta($salesperson_id, '_commission_rate', true);
        }

        if (empty($rate)) {
            $rate = get_option('ah_ho_default_commission_rate', 10);
        }

        // Calculate commission
        $order_total = $order->get_total();
        $commission = $order_total * ($rate / 100);

        // Update order meta
        $order->update_meta_data('_commission_rate', $rate);
        $order->update_meta_data('_commission_amount', $commission);

        if (!$order->get_meta('_commission_status', true)) {
            $order->update_meta_data('_commission_status', 'pending');
        }

        $order->save();
        $recalculated++;
    }

    // Show admin notice
    add_action('admin_notices', function() use ($recalculated) {
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            sprintf(__('Commission recalculated for %d orders.', 'ah-ho-custom'), $recalculated)
        );
    });
}

/**
 * Clean up incorrectly assigned orders (admin tool)
 *
 * Removes salesperson assignment from orders that are NOT in "Processing - B2B" status
 * Trigger: Visit wp-admin with ?ah_ho_cleanup_assignments=1
 */
add_action('admin_init', 'ah_ho_maybe_cleanup_assignments');

function ah_ho_maybe_cleanup_assignments() {
    if (!isset($_GET['ah_ho_cleanup_assignments']) || $_GET['ah_ho_cleanup_assignments'] !== '1') {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['_wpnonce']) && !wp_verify_nonce($_GET['_wpnonce'], 'ah_ho_cleanup_assignments')) {
        return;
    }

    global $wpdb;

    // Find orders with salesperson assigned but NOT in processing-b2b status
    $orders_table = $wpdb->prefix . 'wc_orders';
    $meta_table = $wpdb->prefix . 'wc_orders_meta';

    $order_ids = $wpdb->get_col("
        SELECT DISTINCT o.id
        FROM {$orders_table} o
        INNER JOIN {$meta_table} sp ON o.id = sp.order_id AND sp.meta_key = '_assigned_salesperson_id'
        WHERE o.type = 'shop_order'
        AND o.status != 'wc-processing-b2b'
    ");

    $cleaned = 0;

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;

        // Remove salesperson assignment and commission data
        $order->delete_meta_data('_assigned_salesperson_id');
        $order->delete_meta_data('_commission_amount');
        $order->delete_meta_data('_commission_rate');
        $order->delete_meta_data('_commission_status');
        $order->save();

        $cleaned++;
    }

    add_action('admin_notices', function() use ($cleaned) {
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            sprintf(__('Removed salesperson assignment from %d non-B2B orders.', 'ah-ho-custom'), $cleaned)
        );
    });
}

/**
 * Add admin menu pages
 */
add_action('admin_menu', 'ah_ho_add_commission_dashboard_pages');

function ah_ho_add_commission_dashboard_pages() {
    // Admin Commission Dashboard (for administrators)
    add_menu_page(
        __('Salesperson Commissions', 'ah-ho-custom'),
        __('Commissions', 'ah-ho-custom'),
        'manage_options',
        'ah-ho-salesperson-commissions',
        'ah_ho_render_admin_commission_dashboard',
        'dashicons-money-alt',
        56
    );

    // Personal Commission Dashboard (for salespersons)
    $user = wp_get_current_user();
    if (in_array('ah_ho_salesperson', $user->roles)) {
        add_menu_page(
            __('My Commission', 'ah-ho-custom'),
            __('My Commission', 'ah-ho-custom'),
            'view_salesperson_commission',
            'ah-ho-my-commission',
            'ah_ho_render_salesperson_dashboard',
            'dashicons-chart-line',
            57
        );
    }
}

/**
 * Render Admin Commission Dashboard
 */
function ah_ho_render_admin_commission_dashboard() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Get filter parameters
    $filter_salesperson = isset($_GET['filter_salesperson']) ? absint($_GET['filter_salesperson']) : 0;
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
    $filter_month = isset($_GET['filter_month']) ? sanitize_text_field($_GET['filter_month']) : date('Y-m');

    // Get commission data
    $commission_data = ah_ho_get_commission_data($filter_salesperson, $filter_status, $filter_month);

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('Salesperson Commissions', 'ah-ho-custom'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=ah-ho-salesperson-settings'); ?>" class="page-title-action">
            <?php _e('Settings', 'ah-ho-custom'); ?>
        </a>
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ah-ho-salesperson-commissions&ah_ho_recalc_commission=1'), 'ah_ho_recalc_commission'); ?>" class="page-title-action">
            <?php _e('Recalculate All', 'ah-ho-custom'); ?>
        </a>
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ah-ho-salesperson-commissions&ah_ho_cleanup_assignments=1'), 'ah_ho_cleanup_assignments'); ?>" class="page-title-action" style="color: #b32d2e;">
            <?php _e('Cleanup Non-B2B', 'ah-ho-custom'); ?>
        </a>
        <hr class="wp-header-end">

        <!-- Summary Cards -->
        <div class="ah-ho-summary-cards">
            <div class="ah-ho-card">
                <h3><?php _e('Total Commission', 'ah-ho-custom'); ?></h3>
                <div class="value">$<?php echo number_format($commission_data['total'], 2); ?></div>
            </div>
            <div class="ah-ho-card">
                <h3><?php _e('Pending Approval', 'ah-ho-custom'); ?></h3>
                <div class="value">$<?php echo number_format($commission_data['pending'], 2); ?></div>
            </div>
            <div class="ah-ho-card">
                <h3><?php _e('Approved', 'ah-ho-custom'); ?></h3>
                <div class="value">$<?php echo number_format($commission_data['approved'], 2); ?></div>
            </div>
            <div class="ah-ho-card">
                <h3><?php _e('Paid', 'ah-ho-custom'); ?></h3>
                <div class="value">$<?php echo number_format($commission_data['paid'], 2); ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="tablenav top">
            <form method="get">
                <input type="hidden" name="page" value="ah-ho-salesperson-commissions">
                <select name="filter_salesperson">
                    <option value=""><?php _e('All Salespersons', 'ah-ho-custom'); ?></option>
                    <?php
                    $salespersons = get_users(array('role' => 'ah_ho_salesperson'));
                    foreach ($salespersons as $sp) {
                        printf(
                            '<option value="%d" %s>%s</option>',
                            $sp->ID,
                            selected($filter_salesperson, $sp->ID, false),
                            esc_html($sp->display_name)
                        );
                    }
                    ?>
                </select>
                <select name="filter_status">
                    <option value=""><?php _e('All Statuses', 'ah-ho-custom'); ?></option>
                    <option value="pending" <?php selected($filter_status, 'pending'); ?>><?php _e('Pending', 'ah-ho-custom'); ?></option>
                    <option value="approved" <?php selected($filter_status, 'approved'); ?>><?php _e('Approved', 'ah-ho-custom'); ?></option>
                    <option value="paid" <?php selected($filter_status, 'paid'); ?>><?php _e('Paid', 'ah-ho-custom'); ?></option>
                </select>
                <input type="month" name="filter_month" value="<?php echo esc_attr($filter_month); ?>">
                <input type="submit" class="button" value="<?php _e('Filter', 'ah-ho-custom'); ?>">
            </form>
        </div>

        <!-- Commission Table -->
        <?php ah_ho_render_commission_table($filter_salesperson, $filter_status, $filter_month); ?>

        <!-- Export Button -->
        <p>
            <a href="<?php echo ah_ho_get_export_url($filter_salesperson, $filter_month); ?>" class="button button-primary">
                <?php _e('Export to CSV', 'ah-ho-custom'); ?>
            </a>
        </p>
    </div>

    <style>
        .ah-ho-summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .ah-ho-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        .ah-ho-card h3 {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #646970;
            font-weight: 600;
            text-transform: uppercase;
        }
        .ah-ho-card .value {
            font-size: 32px;
            font-weight: 600;
            color: #1d2327;
        }
        .commission-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .commission-status.pending {
            background: #fcf8e3;
            color: #856404;
        }
        .commission-status.approved {
            background: #d4edda;
            color: #155724;
        }
        .commission-status.paid {
            background: #d1ecf1;
            color: #0c5460;
        }
    </style>
    <?php
}

/**
 * Render Salesperson Personal Dashboard
 */
function ah_ho_render_salesperson_dashboard() {
    $user = wp_get_current_user();

    if (!in_array('ah_ho_salesperson', $user->roles)) {
        return;
    }

    $month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');
    $commission_data = ah_ho_get_commission_data($user->ID, '', $month);

    ?>
    <div class="wrap">
        <h1><?php _e('My Commission', 'ah-ho-custom'); ?></h1>

        <!-- Summary Cards -->
        <div class="ah-ho-summary-cards">
            <div class="ah-ho-card">
                <h3><?php _e('This Month', 'ah-ho-custom'); ?></h3>
                <div class="value">$<?php echo number_format($commission_data['total'], 2); ?></div>
            </div>
            <div class="ah-ho-card">
                <h3><?php _e('Orders', 'ah-ho-custom'); ?></h3>
                <div class="value"><?php echo esc_html($commission_data['order_count']); ?></div>
            </div>
            <div class="ah-ho-card">
                <h3><?php _e('Approved', 'ah-ho-custom'); ?></h3>
                <div class="value">$<?php echo number_format($commission_data['approved'], 2); ?></div>
            </div>
            <div class="ah-ho-card">
                <h3><?php _e('Paid', 'ah-ho-custom'); ?></h3>
                <div class="value">$<?php echo number_format($commission_data['paid'], 2); ?></div>
            </div>
        </div>

        <!-- Month Filter -->
        <div class="tablenav top">
            <form method="get">
                <input type="hidden" name="page" value="ah-ho-my-commission">
                <input type="month" name="month" value="<?php echo esc_attr($month); ?>">
                <input type="submit" class="button" value="<?php _e('Filter', 'ah-ho-custom'); ?>">
            </form>
        </div>

        <!-- Recent Orders -->
        <h2><?php _e('Recent Orders', 'ah-ho-custom'); ?></h2>
        <?php ah_ho_render_commission_table($user->ID, '', $month); ?>

        <!-- Export Button -->
        <p>
            <a href="<?php echo ah_ho_get_export_url($user->ID, $month); ?>" class="button">
                <?php _e('Export My Statement', 'ah-ho-custom'); ?>
            </a>
        </p>
    </div>

    <style>
        .ah-ho-summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .ah-ho-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        .ah-ho-card h3 {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #646970;
            font-weight: 600;
            text-transform: uppercase;
        }
        .ah-ho-card .value {
            font-size: 32px;
            font-weight: 600;
            color: #1d2327;
        }
    </style>
    <?php
}

/**
 * Get commission data for dashboard (HPOS Compatible)
 */
function ah_ho_get_commission_data($salesperson_id = 0, $status = '', $month = '') {
    global $wpdb;

    $data = array(
        'total'       => 0,
        'pending'     => 0,
        'approved'    => 0,
        'paid'        => 0,
        'order_count' => 0,
    );

    // Use HPOS tables
    $orders_table = $wpdb->prefix . 'wc_orders';
    $meta_table = $wpdb->prefix . 'wc_orders_meta';

    // Build WHERE conditions
    $where_conditions = array("o.type = 'shop_order'");

    if ($salesperson_id) {
        $where_conditions[] = $wpdb->prepare("sp.meta_value = %d", $salesperson_id);
    }

    if ($status) {
        $where_conditions[] = $wpdb->prepare("cs.meta_value = %s", $status);
    }

    if ($month) {
        $where_conditions[] = $wpdb->prepare("DATE_FORMAT(o.date_created_gmt, '%%Y-%%m') = %s", $month);
    }

    $where = implode(' AND ', $where_conditions);

    // Get totals by status using HPOS tables
    $query = "
        SELECT
            cs.meta_value as status,
            SUM(CAST(ca.meta_value AS DECIMAL(10,2))) as total,
            COUNT(DISTINCT o.id) as count
        FROM {$orders_table} o
        INNER JOIN {$meta_table} sp ON o.id = sp.order_id AND sp.meta_key = '_assigned_salesperson_id'
        INNER JOIN {$meta_table} ca ON o.id = ca.order_id AND ca.meta_key = '_commission_amount'
        INNER JOIN {$meta_table} cs ON o.id = cs.order_id AND cs.meta_key = '_commission_status'
        WHERE {$where}
        GROUP BY cs.meta_value
    ";

    $results = $wpdb->get_results($query);

    foreach ($results as $row) {
        if (isset($data[$row->status])) {
            $data[$row->status] = floatval($row->total);
        }
        $data['total'] += floatval($row->total);
        $data['order_count'] += intval($row->count);
    }

    return $data;
}

/**
 * Render commission table (HPOS Compatible)
 */
function ah_ho_render_commission_table($salesperson_id = 0, $status = '', $month = '') {
    global $wpdb;

    // Use HPOS tables
    $orders_table = $wpdb->prefix . 'wc_orders';
    $meta_table = $wpdb->prefix . 'wc_orders_meta';

    // Build WHERE conditions
    $where_conditions = array("o.type = 'shop_order'");

    if ($salesperson_id) {
        $where_conditions[] = $wpdb->prepare("sp.meta_value = %d", $salesperson_id);
    }

    if ($status) {
        $where_conditions[] = $wpdb->prepare("cs.meta_value = %s", $status);
    }

    if ($month) {
        $where_conditions[] = $wpdb->prepare("DATE_FORMAT(o.date_created_gmt, '%%Y-%%m') = %s", $month);
    }

    $where = implode(' AND ', $where_conditions);

    $query = "
        SELECT
            o.id as ID,
            o.date_created_gmt as post_date,
            sp.meta_value as salesperson_id,
            ca.meta_value as commission_amount,
            cs.meta_value as commission_status,
            cr.meta_value as commission_rate
        FROM {$orders_table} o
        INNER JOIN {$meta_table} sp ON o.id = sp.order_id AND sp.meta_key = '_assigned_salesperson_id'
        INNER JOIN {$meta_table} ca ON o.id = ca.order_id AND ca.meta_key = '_commission_amount'
        INNER JOIN {$meta_table} cs ON o.id = cs.order_id AND cs.meta_key = '_commission_status'
        LEFT JOIN {$meta_table} cr ON o.id = cr.order_id AND cr.meta_key = '_commission_rate'
        WHERE {$where}
        ORDER BY o.date_created_gmt DESC
        LIMIT 50
    ";

    $results = $wpdb->get_results($query);

    if (empty($results)) {
        echo '<p>' . __('No commission data found.', 'ah-ho-custom') . '</p>';
        return;
    }

    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Order #', 'ah-ho-custom'); ?></th>
                <th><?php _e('Date', 'ah-ho-custom'); ?></th>
                <th><?php _e('Salesperson', 'ah-ho-custom'); ?></th>
                <th><?php _e('Order Total', 'ah-ho-custom'); ?></th>
                <th><?php _e('Rate', 'ah-ho-custom'); ?></th>
                <th><?php _e('Commission', 'ah-ho-custom'); ?></th>
                <th><?php _e('Status', 'ah-ho-custom'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row) :
                $order = wc_get_order($row->ID);
                if (!$order) continue;
                $salesperson = get_userdata($row->salesperson_id);
            ?>
            <tr>
                <td>
                    <a href="<?php echo esc_url($order->get_edit_order_url()); ?>">
                        #<?php echo $order->get_order_number(); ?>
                    </a>
                </td>
                <td><?php echo date_i18n(get_option('date_format'), strtotime($row->post_date)); ?></td>
                <td><?php echo $salesperson ? esc_html($salesperson->display_name) : '—'; ?></td>
                <td>$<?php echo number_format($order->get_total(), 2); ?></td>
                <td><?php echo esc_html($row->commission_rate); ?>%</td>
                <td><strong>$<?php echo number_format($row->commission_amount, 2); ?></strong></td>
                <td>
                    <span class="commission-status <?php echo esc_attr($row->commission_status); ?>">
                        <?php echo esc_html(ucfirst($row->commission_status)); ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Get CSV export URL
 */
function ah_ho_get_export_url($salesperson_id = 0, $month = '') {
    return add_query_arg(array(
        'action'       => 'ah_ho_export_commission',
        'salesperson'  => $salesperson_id,
        'month'        => $month,
        '_wpnonce'     => wp_create_nonce('ah_ho_export_commission'),
    ), admin_url('admin-ajax.php'));
}

/**
 * Handle CSV export
 */
add_action('wp_ajax_ah_ho_export_commission', 'ah_ho_handle_commission_export');

function ah_ho_handle_commission_export() {
    // Verify nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'ah_ho_export_commission')) {
        wp_die(__('Invalid security token', 'ah-ho-custom'));
    }

    // Check permissions
    if (!current_user_can('view_salesperson_commission') && !current_user_can('manage_options')) {
        wp_die(__('You do not have permission to export commissions', 'ah-ho-custom'));
    }

    $salesperson_id = isset($_GET['salesperson']) ? absint($_GET['salesperson']) : 0;
    $month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');

    // Set headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="commission-' . $month . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output CSV
    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Headers
    fputcsv($output, array(
        __('Order #', 'ah-ho-custom'),
        __('Date', 'ah-ho-custom'),
        __('Salesperson', 'ah-ho-custom'),
        __('Order Total', 'ah-ho-custom'),
        __('Commission Rate', 'ah-ho-custom'),
        __('Commission Amount', 'ah-ho-custom'),
        __('Status', 'ah-ho-custom'),
    ));

    // Get data using HPOS tables
    global $wpdb;
    $orders_table = $wpdb->prefix . 'wc_orders';
    $meta_table = $wpdb->prefix . 'wc_orders_meta';

    $where_conditions = array("o.type = 'shop_order'");

    if ($salesperson_id) {
        $where_conditions[] = $wpdb->prepare("sp.meta_value = %d", $salesperson_id);
    }

    if ($month) {
        $where_conditions[] = $wpdb->prepare("DATE_FORMAT(o.date_created_gmt, '%%Y-%%m') = %s", $month);
    }

    $where = implode(' AND ', $where_conditions);

    $query = "
        SELECT
            o.id as ID,
            o.date_created_gmt as post_date,
            sp.meta_value as salesperson_id,
            ca.meta_value as commission_amount,
            cs.meta_value as commission_status,
            cr.meta_value as commission_rate
        FROM {$orders_table} o
        INNER JOIN {$meta_table} sp ON o.id = sp.order_id AND sp.meta_key = '_assigned_salesperson_id'
        INNER JOIN {$meta_table} ca ON o.id = ca.order_id AND ca.meta_key = '_commission_amount'
        INNER JOIN {$meta_table} cs ON o.id = cs.order_id AND cs.meta_key = '_commission_status'
        LEFT JOIN {$meta_table} cr ON o.id = cr.order_id AND cr.meta_key = '_commission_rate'
        WHERE {$where}
        ORDER BY o.date_created_gmt DESC
    ";

    $results = $wpdb->get_results($query);

    // Output rows
    foreach ($results as $row) {
        $order = wc_get_order($row->ID);
        if (!$order) continue;

        $salesperson = get_userdata($row->salesperson_id);

        fputcsv($output, array(
            $order->get_order_number(),
            date_i18n(get_option('date_format'), strtotime($row->post_date)),
            $salesperson ? $salesperson->display_name : '',
            $order->get_total(),
            $row->commission_rate . '%',
            $row->commission_amount,
            ucfirst($row->commission_status),
        ));
    }

    fclose($output);
    exit;
}
