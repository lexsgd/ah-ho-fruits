<?php
/**
 * Salesperson Role & Permissions
 *
 * Registers the B2B salesperson role with restricted order access
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register salesperson role on plugin activation
 */
function ah_ho_register_salesperson_role() {
    // Check if role already exists
    if (get_role('ah_ho_salesperson')) {
        return;
    }

    // Create salesperson role based on Shop Manager but with restrictions
    add_role(
        'ah_ho_salesperson',
        __('Salesperson', 'ah-ho-custom'),
        array(
            // Basic WordPress capabilities
            'read'                          => true,
            'upload_files'                  => true,

            // WooCommerce order capabilities
            'read_shop_order'               => true,
            'read_shop_orders'              => true,
            'edit_shop_order'               => true,
            'edit_shop_orders'              => true,
            'publish_shop_orders'           => true,
            'create_shop_orders'            => true,

            // 🔒 SECURITY BOUNDARY - Prevent cross-salesperson access
            'edit_others_shop_orders'       => false,
            'read_others_shop_orders'       => false,
            'delete_shop_orders'            => false,
            'delete_others_shop_orders'     => false,

            // Product read-only access
            'read_product'                  => true,
            'read_products'                 => true,

            // Customer management (needed for creating orders)
            'list_users'                    => true,
            'read_shop_customer'            => true,

            // Custom capability for commission viewing
            'view_salesperson_commission'   => true,
        )
    );
}

/**
 * Update salesperson role capabilities
 * Run this on plugin updates to refresh capabilities
 * ALSO creates the role if it doesn't exist (fallback for activation issues)
 */
function ah_ho_update_salesperson_role() {
    $role = get_role('ah_ho_salesperson');

    if (!$role) {
        // Role doesn't exist, create it immediately
        ah_ho_register_salesperson_role();

        // Log for debugging
        error_log('Ah Ho Custom: Salesperson role created via plugins_loaded hook');
        return;
    }

    // Update capabilities to match current requirements
    $capabilities = array(
        'read'                          => true,
        'upload_files'                  => true,
        'read_shop_order'               => true,
        'read_shop_orders'              => true,
        'edit_shop_order'               => true,
        'edit_shop_orders'              => true,
        'publish_shop_orders'           => true,
        'create_shop_orders'            => true,
        'edit_others_shop_orders'       => false,
        'read_others_shop_orders'       => false,
        'delete_shop_orders'            => false,
        'delete_others_shop_orders'     => false,
        'read_product'                  => true,
        'read_products'                 => true,
        'list_users'                    => true,
        'read_shop_customer'            => true,
        'view_salesperson_commission'   => true,
    );

    foreach ($capabilities as $cap => $grant) {
        if ($grant) {
            $role->add_cap($cap);
        } else {
            $role->remove_cap($cap);
        }
    }
}

/**
 * Remove salesperson role on plugin deactivation
 */
function ah_ho_remove_salesperson_role() {
    // Don't remove role if users still have it
    $users = get_users(array('role' => 'ah_ho_salesperson'));
    if (count($users) > 0) {
        return; // Keep role if users exist
    }

    remove_role('ah_ho_salesperson');
}

/**
 * Initialize role on plugin activation
 */
add_action('ah_ho_custom_activate', 'ah_ho_register_salesperson_role');

/**
 * Update role capabilities on plugin updates
 */
add_action('plugins_loaded', 'ah_ho_update_salesperson_role', 5);

/**
 * Clean up role on deactivation (only if no users)
 */
add_action('ah_ho_custom_deactivate', 'ah_ho_remove_salesperson_role');

/**
 * Add commission rate field to user profile when creating/editing salesperson
 */
add_action('show_user_profile', 'ah_ho_add_commission_rate_field');
add_action('edit_user_profile', 'ah_ho_add_commission_rate_field');

function ah_ho_add_commission_rate_field($user) {
    // Only show for salespersons
    if (!in_array('ah_ho_salesperson', $user->roles)) {
        return;
    }

    // Only show if custom rates are enabled
    if (!get_option('ah_ho_enable_custom_rates', true)) {
        return;
    }

    $commission_rate = get_user_meta($user->ID, '_commission_rate', true);
    $default_rate = get_option('ah_ho_default_commission_rate', 10);
    ?>
    <h3><?php _e('Commission Settings', 'ah-ho-custom'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="commission_rate"><?php _e('Custom Commission Rate (%)', 'ah-ho-custom'); ?></label></th>
            <td>
                <input type="number"
                       name="commission_rate"
                       id="commission_rate"
                       value="<?php echo esc_attr($commission_rate); ?>"
                       step="0.01"
                       min="0"
                       max="100"
                       class="regular-text" />
                <p class="description">
                    <?php printf(__('Leave blank to use default rate (%s%%)', 'ah-ho-custom'), $default_rate); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save commission rate field
 */
add_action('personal_options_update', 'ah_ho_save_commission_rate_field');
add_action('edit_user_profile_update', 'ah_ho_save_commission_rate_field');

function ah_ho_save_commission_rate_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    if (isset($_POST['commission_rate'])) {
        $rate = floatval($_POST['commission_rate']);

        // Validate rate is between 0-100
        if ($rate < 0 || $rate > 100) {
            return;
        }

        update_user_meta($user_id, '_commission_rate', $rate);
    }
}

/**
 * Add Tools submenu page for role creation
 */
add_action('admin_menu', 'ah_ho_add_role_tools_page');
function ah_ho_add_role_tools_page() {
    add_submenu_page(
        'tools.php',
        'Create Salesperson Role',
        'Salesperson Role',
        'manage_options',
        'ah-ho-create-salesperson-role',
        'ah_ho_render_role_tools_page'
    );
}

function ah_ho_render_role_tools_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    // Handle form submission
    if (isset($_POST['create_role']) && check_admin_referer('ah_ho_create_role')) {
        // Force remove and recreate
        remove_role('ah_ho_salesperson');
        ah_ho_register_salesperson_role();

        echo '<div class="notice notice-success"><p><strong>Success!</strong> Salesperson role has been created.</p></div>';
    }

    $role = get_role('ah_ho_salesperson');

    ?>
    <div class="wrap">
        <h1>Salesperson Role Management</h1>

        <?php if ($role): ?>
            <div class="notice notice-info">
                <p><strong>Salesperson role exists!</strong> You can now select it when creating users.</p>
            </div>

            <h2>Current Role Capabilities</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Capability</th>
                        <th>Granted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($role->capabilities as $cap => $grant): ?>
                        <tr>
                            <td><?php echo esc_html($cap); ?></td>
                            <td><?php echo $grant ? '✅ Yes' : '❌ No'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p>&nbsp;</p>
            <form method="post">
                <?php wp_nonce_field('ah_ho_create_role'); ?>
                <input type="hidden" name="create_role" value="1">
                <button type="submit" class="button button-secondary">Recreate Role (Force Refresh)</button>
            </form>

        <?php else: ?>
            <div class="notice notice-warning">
                <p><strong>Salesperson role does not exist yet.</strong></p>
            </div>

            <form method="post">
                <?php wp_nonce_field('ah_ho_create_role'); ?>
                <input type="hidden" name="create_role" value="1">
                <p>
                    <button type="submit" class="button button-primary button-hero">Create Salesperson Role Now</button>
                </p>
            </form>
        <?php endif; ?>

        <hr>

        <h2>Next Steps</h2>
        <ol>
            <li>Click the button above to create the Salesperson role</li>
            <li>Go to <a href="<?php echo admin_url('user-new.php'); ?>">Users > Add New</a></li>
            <li>Select "Salesperson" from the Role dropdown</li>
            <li>Create your first salesperson user</li>
        </ol>
    </div>
    <?php
}

