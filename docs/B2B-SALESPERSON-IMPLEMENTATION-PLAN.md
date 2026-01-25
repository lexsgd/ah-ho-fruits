# B2B Salesperson Role Plugin - Implementation Plan

## Executive Summary

**Feasibility Assessment: MODERATELY EASY ✅**

Creating a B2B salesperson role with commission tracking is highly feasible. The existing `ah-ho-custom` plugin provides an excellent foundation with:
- ✅ Modular architecture ready for extension
- ✅ Proven WooCommerce integration patterns
- ✅ Post meta data storage (no custom tables needed)
- ✅ Email notification system (extensible for commission alerts)
- ✅ WooCommerce HPOS compatibility

**Estimated Development Time:** 18-23 hours for core functionality + admin settings UI

---

## Recommended Approach

### Option 1: Extend Existing ah-ho-custom Plugin ⭐ RECOMMENDED

**Why:**
- Leverages existing infrastructure and patterns
- Single plugin to maintain
- Already activated and tested in production
- Consistent codebase architecture

**Implementation:** Add 5 new include files to existing plugin structure

### Option 2: Separate Plugin (NOT RECOMMENDED)

**Why NOT:**
- Duplicate infrastructure
- Plugin dependency management complexity
- Additional activation/maintenance overhead

---

## Approval Workflow Explained

**What is "Approval Workflow"?**

This refers to the process of when a commission becomes "ready to pay" to the salesperson:

### Option A: Auto-Approval (Recommended for simplicity)
```
Order Created → Order Completed → Commission Status = "Approved"
```
- Commission is automatically marked as "approved" when order status becomes "completed"
- Admin can then review approved commissions and mark them as "paid" when payment is processed
- **Workflow:** `pending` → `approved` (auto) → `paid` (manual by admin)

### Option B: Manual Approval (Recommended for control)
```
Order Created → Order Completed → Commission Status = "Pending Approval"
Admin Reviews → Approves → Commission Status = "Approved" → Admin Pays → Status = "Paid"
```
- Commission remains in "pending" status even after order completion
- Admin must manually review and approve each commission before it can be paid
- **Workflow:** `pending` → `approved` (manual by admin) → `paid` (manual by admin)

**Recommended:** Start with **Option A (Auto-Approval)** - simpler workflow, less admin overhead. The admin settings UI will allow toggling between auto/manual approval.

---

## Architecture Design

### 1. Role & Permissions System

**File:** `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/includes/salesperson-roles.php`

**Core Capabilities:**
```php
'ah_ho_salesperson' => [
    'read' => true,
    'read_shop_order' => true,           // View orders
    'edit_shop_order' => true,           // Edit assigned orders
    'publish_shop_orders' => true,       // Create new orders
    'edit_others_shop_orders' => false,  // 🔒 SECURITY BOUNDARY
    'view_salesperson_commission' => true,
]
```

**Critical Security Constraint:**
- `edit_others_shop_orders` = `false` prevents cross-salesperson access
- Enforced at 4 layers (see Query Filtering section)

---

### 2. Order Attribution System

**File:** `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/includes/salesperson-attribution.php`

**Data Model (Post Meta):**

| Meta Key | Type | Purpose |
|----------|------|---------|
| `_assigned_salesperson_id` | int | User ID of salesperson |
| `_commission_rate` | float | % or flat amount |
| `_commission_amount` | float | Calculated commission |
| `_commission_status` | string | pending/approved/paid/cancelled/refunded |
| `_commission_paid_date` | string | Y-m-d timestamp |

**Plugin Options (WordPress Options API):**

| Option Key | Type | Purpose | Default |
|-----------|------|---------|---------|
| `ah_ho_default_commission_rate` | float | Default commission % | 10.0 |
| `ah_ho_commission_approval_mode` | string | auto/manual | 'auto' |
| `ah_ho_commission_notification_emails` | string | Comma-separated emails | admin email |
| `ah_ho_enable_custom_rates` | bool | Allow per-salesperson rates | true |

**Order Lifecycle Hooks:**
```php
// Auto-assign on manual order creation
add_action('woocommerce_new_order', 'ah_ho_assign_salesperson_to_new_order', 10, 2);

// Calculate commission on completion
add_action('woocommerce_order_status_completed', 'ah_ho_calculate_order_commission', 10, 1);

// Handle refunds/cancellations
add_action('woocommerce_order_status_refunded', 'ah_ho_handle_commission_refund', 10, 1);
add_action('woocommerce_order_status_cancelled', 'ah_ho_handle_commission_cancellation', 10, 1);
```

**Commission Calculation Logic:**
```php
function ah_ho_calculate_order_commission($order_id) {
    $order = wc_get_order($order_id);
    $salesperson_id = get_post_meta($order_id, '_assigned_salesperson_id', true);

    if (!$salesperson_id) return; // Not a salesperson order

    // Check if custom rates are enabled
    $enable_custom_rates = get_option('ah_ho_enable_custom_rates', true);

    if ($enable_custom_rates) {
        // Try to get salesperson-specific rate
        $rate = get_user_meta($salesperson_id, '_commission_rate', true);
    }

    // Fallback to default rate
    if (empty($rate)) {
        $rate = get_option('ah_ho_default_commission_rate', 10);
    }

    $order_total = $order->get_total();
    $commission = $order_total * ($rate / 100);

    update_post_meta($order_id, '_commission_amount', $commission);
    update_post_meta($order_id, '_commission_rate', $rate);

    // Check approval mode
    $approval_mode = get_option('ah_ho_commission_approval_mode', 'auto');
    $status = ($approval_mode === 'auto') ? 'approved' : 'pending';

    update_post_meta($order_id, '_commission_status', $status);

    // Send notification if auto-approved
    if ($status === 'approved') {
        ah_ho_send_commission_notification($order_id, $salesperson_id, $commission);
    }
}
```

---

### 3. Query Filtering (Multi-Layer Security)

**File:** `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/includes/salesperson-query-filters.php`

**Layer 1: Admin Order List (pre_get_posts)**
```php
add_filter('pre_get_posts', 'ah_ho_filter_salesperson_orders');
function ah_ho_filter_salesperson_orders($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    if (get_query_var('post_type') !== 'shop_order') return;

    $user = wp_get_current_user();
    if (!in_array('ah_ho_salesperson', $user->roles)) return;

    $meta_query = $query->get('meta_query') ?: [];
    $meta_query[] = [
        'key' => '_assigned_salesperson_id',
        'value' => get_current_user_id(),
        'compare' => '='
    ];
    $query->set('meta_query', $meta_query);
}
```

**Layer 2: SQL Fallback (posts_where)**
```php
add_filter('posts_where', 'ah_ho_filter_orders_sql', 10, 2);
function ah_ho_filter_orders_sql($where, $query) {
    global $wpdb;
    $user = wp_get_current_user();

    if (in_array('ah_ho_salesperson', $user->roles) && $query->get('post_type') === 'shop_order') {
        $where .= $wpdb->prepare(
            " AND {$wpdb->posts}.ID IN (
                SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_assigned_salesperson_id'
                AND meta_value = %d
            )",
            get_current_user_id()
        );
    }
    return $where;
}
```

**Layer 3: Direct URL Access Prevention (load-post.php)**
```php
add_action('load-post.php', 'ah_ho_prevent_unauthorized_order_access');
function ah_ho_prevent_unauthorized_order_access() {
    global $post;
    if ($post->post_type !== 'shop_order') return;

    $user = wp_get_current_user();
    if (!in_array('ah_ho_salesperson', $user->roles)) return;

    $assigned_salesperson = get_post_meta($post->ID, '_assigned_salesperson_id', true);
    if ($assigned_salesperson != get_current_user_id()) {
        wp_die('You do not have permission to access this order.');
    }
}
```

**Layer 4: REST API Protection**
```php
add_filter('woocommerce_rest_check_permissions', 'ah_ho_rest_order_permissions', 10, 4);
```

---

### 4. Dashboard & Reporting

**File:** `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/includes/salesperson-dashboard.php`

#### Admin Dashboard Features:
- **URL:** `wp-admin/admin.php?page=ah-ho-salesperson-commissions`
- **Metrics:**
  - Total commission (pending/approved/paid)
  - Commission by salesperson (table)
  - Monthly trend chart (Chart.js)
  - Top performers leaderboard
- **Actions:**
  - Bulk approve commissions (if manual approval mode)
  - Bulk mark as paid
  - Export monthly report (CSV)
  - Adjust commission rates per salesperson (if custom rates enabled)

#### Salesperson Dashboard Features:
- **URL:** `wp-admin/admin.php?page=my-commission`
- **Metrics:**
  - Personal commission summary
  - Orders this month (count + total value)
  - Commission breakdown by status
  - Recent orders table
- **Actions:**
  - View commission history
  - Export personal statement (CSV)

**Chart.js Implementation:**
```php
// Enqueue Chart.js
add_action('admin_enqueue_scripts', 'ah_ho_enqueue_dashboard_scripts');
function ah_ho_enqueue_dashboard_scripts($hook) {
    if ($hook !== 'toplevel_page_ah-ho-salesperson-commissions') return;

    wp_enqueue_script(
        'ah-ho-chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        [],
        '4.4.0',
        true
    );
    wp_enqueue_script('ah-ho-dashboard', AH_HO_CUSTOM_PLUGIN_URL . 'assets/dashboard.js', ['ah-ho-chartjs'], AH_HO_CUSTOM_VERSION, true);
}
```

**CSV Export:**
```php
function ah_ho_export_commission_csv($salesperson_id = null, $month = null) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="commission-' . date('Y-m') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Date', 'Customer', 'Total', 'Commission', 'Status']);

    // Query orders and output rows
    fclose($output);
    exit;
}
```

---

### 5. Admin Settings UI ⭐ NEW

**File:** `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/includes/salesperson-settings.php`

**URL:** `wp-admin/admin.php?page=ah-ho-salesperson-settings`

#### Settings Page UI Wireframe:

```
┌─────────────────────────────────────────────────────────────┐
│ Salesperson Commission Settings                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ Commission Rate Configuration                                │
│ ┌──────────────────────────────────────────────────────┐   │
│ │ Default Commission Rate (%)                           │   │
│ │ [  10.00  ] %                                         │   │
│ │                                                        │   │
│ │ ☑ Enable Custom Rates Per Salesperson                │   │
│ │   When enabled, admins can set individual commission │   │
│ │   rates in each salesperson's user profile.          │   │
│ └──────────────────────────────────────────────────────┘   │
│                                                              │
│ Commission Approval Workflow                                 │
│ ┌──────────────────────────────────────────────────────┐   │
│ │ ○ Auto-Approve (Recommended)                          │   │
│ │   Commissions automatically approved when order is    │   │
│ │   completed. Admin marks as "paid" when processed.    │   │
│ │                                                        │   │
│ │ ○ Manual Approval                                     │   │
│ │   Admin must manually approve each commission before  │   │
│ │   it can be marked as paid.                           │   │
│ └──────────────────────────────────────────────────────┘   │
│                                                              │
│ Email Notifications                                          │
│ ┌──────────────────────────────────────────────────────┐   │
│ │ Notification Recipients (comma-separated)             │   │
│ │ [admin@ahhofruits.com, accounts@ahhofruits.com    ]  │   │
│ │                                                        │   │
│ │ ☑ Notify when commission is approved                 │   │
│ │ ☑ Send monthly summary to salespersons               │   │
│ │ ☐ Send monthly summary to admin                      │   │
│ └──────────────────────────────────────────────────────┘   │
│                                                              │
│ [ Save Settings ]                                            │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

#### Settings Implementation Code:

```php
<?php
/**
 * Salesperson Settings Page
 */

// Add settings page to admin menu
add_action('admin_menu', 'ah_ho_add_settings_page');
function ah_ho_add_settings_page() {
    add_submenu_page(
        'ah-ho-salesperson-commissions',  // Parent slug
        'Commission Settings',             // Page title
        'Settings',                        // Menu title
        'manage_options',                  // Capability
        'ah-ho-salesperson-settings',      // Menu slug
        'ah_ho_render_settings_page'       // Callback
    );
}

// Register settings
add_action('admin_init', 'ah_ho_register_settings');
function ah_ho_register_settings() {
    register_setting('ah_ho_salesperson_settings', 'ah_ho_default_commission_rate', [
        'type' => 'number',
        'default' => 10.0,
        'sanitize_callback' => 'floatval'
    ]);

    register_setting('ah_ho_salesperson_settings', 'ah_ho_enable_custom_rates', [
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);

    register_setting('ah_ho_salesperson_settings', 'ah_ho_commission_approval_mode', [
        'type' => 'string',
        'default' => 'auto',
        'sanitize_callback' => 'sanitize_text_field'
    ]);

    register_setting('ah_ho_salesperson_settings', 'ah_ho_commission_notification_emails', [
        'type' => 'string',
        'default' => get_option('admin_email'),
        'sanitize_callback' => 'ah_ho_sanitize_email_list'
    ]);

    register_setting('ah_ho_salesperson_settings', 'ah_ho_notify_on_approval', [
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);

    register_setting('ah_ho_salesperson_settings', 'ah_ho_monthly_summary_salesperson', [
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);

    register_setting('ah_ho_salesperson_settings', 'ah_ho_monthly_summary_admin', [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
}

// Sanitize email list
function ah_ho_sanitize_email_list($input) {
    $emails = array_map('trim', explode(',', $input));
    $valid_emails = array_filter($emails, 'is_email');
    return implode(', ', $valid_emails);
}

// Render settings page
function ah_ho_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Show success message if settings saved
    if (isset($_GET['settings-updated'])) {
        add_settings_error(
            'ah_ho_salesperson_messages',
            'ah_ho_salesperson_message',
            'Settings Saved',
            'updated'
        );
    }

    settings_errors('ah_ho_salesperson_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <form action="options.php" method="post">
            <?php settings_fields('ah_ho_salesperson_settings'); ?>

            <!-- Commission Rate Configuration -->
            <h2>Commission Rate Configuration</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Default Commission Rate (%)</th>
                    <td>
                        <input type="number"
                               name="ah_ho_default_commission_rate"
                               value="<?php echo esc_attr(get_option('ah_ho_default_commission_rate', 10)); ?>"
                               step="0.01"
                               min="0"
                               max="100"
                               class="regular-text" />
                        <p class="description">Default commission percentage for all salespersons</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Custom Rates</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="ah_ho_enable_custom_rates"
                                   value="1"
                                   <?php checked(get_option('ah_ho_enable_custom_rates', true)); ?> />
                            Enable Custom Rates Per Salesperson
                        </label>
                        <p class="description">When enabled, admins can set individual commission rates in each salesperson's user profile.</p>
                    </td>
                </tr>
            </table>

            <!-- Commission Approval Workflow -->
            <h2>Commission Approval Workflow</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Approval Mode</th>
                    <td>
                        <label>
                            <input type="radio"
                                   name="ah_ho_commission_approval_mode"
                                   value="auto"
                                   <?php checked(get_option('ah_ho_commission_approval_mode', 'auto'), 'auto'); ?> />
                            <strong>Auto-Approve</strong> (Recommended)
                        </label>
                        <p class="description" style="margin-left: 25px;">
                            Commissions automatically approved when order is completed. Admin marks as "paid" when processed.
                        </p>

                        <label>
                            <input type="radio"
                                   name="ah_ho_commission_approval_mode"
                                   value="manual"
                                   <?php checked(get_option('ah_ho_commission_approval_mode', 'auto'), 'manual'); ?> />
                            <strong>Manual Approval</strong>
                        </label>
                        <p class="description" style="margin-left: 25px;">
                            Admin must manually approve each commission before it can be marked as paid.
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Email Notifications -->
            <h2>Email Notifications</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Notification Recipients</th>
                    <td>
                        <input type="text"
                               name="ah_ho_commission_notification_emails"
                               value="<?php echo esc_attr(get_option('ah_ho_commission_notification_emails', get_option('admin_email'))); ?>"
                               class="regular-text"
                               placeholder="admin@example.com, accounts@example.com" />
                        <p class="description">Comma-separated list of email addresses to receive commission notifications</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Notification Events</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="ah_ho_notify_on_approval"
                                   value="1"
                                   <?php checked(get_option('ah_ho_notify_on_approval', true)); ?> />
                            Notify when commission is approved
                        </label>
                        <br />
                        <label>
                            <input type="checkbox"
                                   name="ah_ho_monthly_summary_salesperson"
                                   value="1"
                                   <?php checked(get_option('ah_ho_monthly_summary_salesperson', true)); ?> />
                            Send monthly summary to salespersons
                        </label>
                        <br />
                        <label>
                            <input type="checkbox"
                                   name="ah_ho_monthly_summary_admin"
                                   value="1"
                                   <?php checked(get_option('ah_ho_monthly_summary_admin', false)); ?> />
                            Send monthly summary to admin
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}
```

#### User Profile Custom Commission Rate Field:

```php
// Add custom commission rate field to user profile (when custom rates enabled)
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
    <h3>Commission Settings</h3>
    <table class="form-table">
        <tr>
            <th><label for="commission_rate">Custom Commission Rate (%)</label></th>
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
                    Leave blank to use default rate (<?php echo $default_rate; ?>%)
                </p>
            </td>
        </tr>
    </table>
    <?php
}

// Save custom commission rate
add_action('personal_options_update', 'ah_ho_save_commission_rate_field');
add_action('edit_user_profile_update', 'ah_ho_save_commission_rate_field');

function ah_ho_save_commission_rate_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    if (isset($_POST['commission_rate'])) {
        $rate = floatval($_POST['commission_rate']);
        update_user_meta($user_id, '_commission_rate', $rate);
    }
}
```

---

## Database Schema

### Post Meta Approach (Phase 1 - RECOMMENDED)

**Advantages:**
- No custom tables (simpler maintenance)
- No database migrations needed
- Built-in WordPress query compatibility
- Proven pattern in existing ah-ho-custom plugin

**When to Migrate to Custom Table:**
- >1000 orders per month
- Report generation takes >5 seconds
- Need complex aggregation queries

### Custom Table Approach (Phase 2 - Optional Future Enhancement)

```sql
CREATE TABLE {$wpdb->prefix}ah_ho_commissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    salesperson_id BIGINT UNSIGNED NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    commission_rate DECIMAL(5,2) NOT NULL,
    commission_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    order_total DECIMAL(10,2) NOT NULL,
    order_date DATETIME NOT NULL,
    commission_paid_date DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_salesperson (salesperson_id),
    INDEX idx_status (commission_status),
    INDEX idx_order (order_id)
);
```

---

## Implementation Sequence

### Phase 1: Core Role & Attribution (5 hours)

**Files to create:**
1. `/includes/salesperson-roles.php` - Role registration
2. `/includes/salesperson-attribution.php` - Order assignment + commission calculation
3. `/includes/salesperson-query-filters.php` - Multi-layer security

**Tasks:**
- [ ] Register `ah_ho_salesperson` role on plugin activation
- [ ] Add meta box to order edit page for manual assignment
- [ ] Auto-assign salesperson on manual order creation
- [ ] Calculate commission on order completion
- [ ] Add commission info to order details page

**Verification:**
```php
// Test role capabilities
$user = get_user_by('id', $salesperson_id);
var_dump($user->has_cap('edit_others_shop_orders')); // Should be false

// Test order visibility
// Login as salesperson A, verify cannot see salesperson B's orders
```

### Phase 2: Security Hardening (3 hours)

**Files to modify:**
1. `/includes/salesperson-query-filters.php` - Add all 4 layers

**Tasks:**
- [ ] Implement Layer 1: `pre_get_posts` filter
- [ ] Implement Layer 2: `posts_where` SQL filter
- [ ] Implement Layer 3: `load-post.php` access check
- [ ] Implement Layer 4: REST API protection

**Verification:**
```bash
# Test direct URL access
curl -b cookies.txt "https://site.com/wp-admin/post.php?post=123&action=edit"
# Should return 403 if order not assigned to logged-in salesperson

# Test REST API
curl -H "Authorization: Bearer TOKEN" "https://site.com/wp-json/wc/v3/orders/123"
# Should return 403 if unauthorized
```

### Phase 3: Settings UI & Dashboard (8 hours) ⭐ UPDATED

**Files to create:**
1. `/includes/salesperson-settings.php` - Admin settings page
2. `/includes/salesperson-dashboard.php` - Admin pages + metrics
3. `/assets/dashboard.js` - Chart.js integration
4. `/assets/dashboard.css` - Styling

**Tasks:**
- [ ] Create settings page with commission rate configuration
- [ ] Add approval workflow toggle (auto/manual)
- [ ] Add email notification settings
- [ ] Add custom commission rate field to user profiles
- [ ] Add admin menu page "Salesperson Commissions"
- [ ] Build metrics dashboard (total, by salesperson, trends)
- [ ] Implement Chart.js monthly trend chart
- [ ] Add salesperson personal dashboard page
- [ ] Build CSV export functionality

**Verification:**
- [ ] Settings page saves and retrieves values correctly
- [ ] Default commission rate applies to new orders
- [ ] Custom rates (when enabled) override default
- [ ] Approval mode affects commission status correctly
- [ ] Email notifications sent to configured recipients
- [ ] Admin can see all salespersons' commissions
- [ ] Salesperson can only see own commission
- [ ] CSV export includes all expected columns
- [ ] Chart renders correctly with sample data

### Phase 4: Edge Cases & Polish (3 hours)

**Files to modify:**
1. `/includes/salesperson-attribution.php` - Add refund/cancellation handling

**Tasks:**
- [ ] Handle full refund (set commission to 0, status to 'refunded')
- [ ] Handle partial refund (adjust commission proportionally)
- [ ] Handle cancellation (only if status = pending/approved)
- [ ] Add email notification for commission approval (extend existing email system)
- [ ] Add bulk actions to order list (assign salesperson)
- [ ] Add commission summary in order list columns

**Edge Cases:**
| Scenario | Expected Behavior |
|----------|-------------------|
| Full refund | Commission = 0, status = 'refunded' |
| Partial refund | Commission reduced proportionally |
| Order cancelled (pending commission) | Status = 'cancelled' |
| Order cancelled (paid commission) | Flag for manual clawback |
| Salesperson deleted | Preserve order history, mark commission as orphaned |
| Commission rate changed mid-month | Use rate at order creation time |
| Custom rates disabled after setup | Fall back to default rate for new orders |

### Phase 5: Testing & Documentation (2 hours)

**Files to create:**
1. `/docs/SALESPERSON-SETUP.md` - Setup guide
2. `/docs/COMMISSION-WORKFLOW.md` - Usage guide

**Tasks:**
- [ ] End-to-end test: Configure settings via UI
- [ ] End-to-end test: Create salesperson user
- [ ] End-to-end test: Set custom commission rate
- [ ] End-to-end test: Create order as salesperson
- [ ] End-to-end test: Verify commission calculation
- [ ] End-to-end test: Test approval workflow (both modes)
- [ ] End-to-end test: Verify email notifications
- [ ] End-to-end test: Export monthly report
- [ ] Write admin setup documentation
- [ ] Write salesperson user guide

---

## Critical Files Reference

### Files to Create (New):
1. `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/includes/salesperson-roles.php`
2. `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/includes/salesperson-attribution.php`
3. `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/includes/salesperson-query-filters.php`
4. `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/includes/salesperson-settings.php` ⭐ NEW
5. `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/includes/salesperson-dashboard.php`
6. `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/assets/dashboard.js`
7. `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/assets/dashboard.css`

### Files to Modify (Existing):
1. `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/ah-ho-custom.php`
   - Add: `require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/salesperson-roles.php';`
   - Add: `require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/salesperson-attribution.php';`
   - Add: `require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/salesperson-query-filters.php';`
   - Add: `require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/salesperson-settings.php';` ⭐ NEW
   - Add: `require_once AH_HO_CUSTOM_PLUGIN_DIR . 'includes/salesperson-dashboard.php';`

### Reference Files (Study These Patterns):
1. `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/includes/custom-order-statuses.php` - Order hooks pattern
2. `/Users/lexnaweiming/Downloads/Ah Ho Fruits/ah-ho-fruits/wp-content/plugins/ah-ho-custom/includes/custom-emails.php` - Email system pattern

---

## Risk Assessment

### Technical Risks:

| Risk | Severity | Mitigation |
|------|----------|------------|
| Cross-salesperson data leak | HIGH | 4-layer query filtering + capability checks |
| Performance with large datasets | MEDIUM | Start with post meta, migrate to custom table if needed |
| Commission calculation errors | MEDIUM | Store calculation parameters in post meta for audit trail |
| Plugin conflicts | LOW | Use unique function prefixes (`ah_ho_`), late hook priority |
| Settings UI not saving | LOW | Use WordPress Settings API with proper sanitization |

### Business Risks:

| Risk | Severity | Mitigation |
|------|----------|------------|
| Disputed commissions | MEDIUM | Store order total + rate + amount in post meta for transparency |
| Manual payout errors | LOW | CSV export with status tracking (pending → approved → paid) |
| Salesperson gaming system | LOW | Admin approval workflow available as option |
| Email notification failures | LOW | Log notification attempts, provide manual retry option |

---

## Performance Considerations

### Current Scale (Assumptions):
- 50-100 orders per month
- 3-5 salespersons
- **Post meta approach is OPTIMAL**

### Scaling Thresholds:
| Metric | Post Meta Limit | Action |
|--------|----------------|--------|
| Orders/month | <1000 | Continue with post meta |
| Report generation time | <5 seconds | Continue with post meta |
| Salespersons | <20 | Continue with post meta |

**When to migrate to custom table:**
- Report generation >5 seconds
- Need complex JOIN queries
- >1000 orders per month

---

## Success Criteria

### Functional Requirements:
- [ ] Admin can configure commission settings via UI
- [ ] Admin can set default commission rate
- [ ] Admin can enable/disable custom rates per salesperson
- [ ] Admin can choose auto-approve or manual approval workflow
- [ ] Admin can configure email notification recipients
- [ ] Salesperson can create orders for customers
- [ ] Salesperson can ONLY view/edit their own orders
- [ ] Commission automatically calculated on order completion
- [ ] Commission uses custom rate (if set) or default rate
- [ ] Admin can view all commissions by salesperson
- [ ] Salesperson can view personal commission summary
- [ ] CSV export for monthly payouts
- [ ] Email notifications sent to configured recipients
- [ ] Handles refunds and cancellations correctly

### Security Requirements:
- [ ] No cross-salesperson data access
- [ ] Multi-layer query filtering
- [ ] Capability-based access control
- [ ] SQL injection prevention (prepared statements)
- [ ] Settings values properly sanitized

### Performance Requirements:
- [ ] Dashboard loads in <3 seconds
- [ ] Settings page loads in <2 seconds
- [ ] Order list filtering adds <500ms overhead
- [ ] CSV export completes in <10 seconds for 1 month

---

## Configuration Examples

### Example 1: Simple Fixed Rate (Default)
**Settings:**
- Default commission rate: 10%
- Enable custom rates: ✓ (checked)
- Approval mode: Auto-approve
- Notification emails: admin@ahhofruits.com

**Result:**
- All salespersons earn 10% commission by default
- Individual rates can be customized in user profiles
- Commissions automatically approved when order completes
- Admin receives email when commission approved

### Example 2: Manual Approval with Multiple Recipients
**Settings:**
- Default commission rate: 8%
- Enable custom rates: ✗ (unchecked)
- Approval mode: Manual approval
- Notification emails: admin@ahhofruits.com, finance@ahhofruits.com

**Result:**
- All salespersons earn fixed 8% commission (no customization)
- Admin must manually approve commissions
- Both admin and finance team receive email notifications

### Example 3: Tiered Rates with Custom Settings
**Settings:**
- Default commission rate: 5%
- Enable custom rates: ✓ (checked)
- Approval mode: Auto-approve

**User Profiles:**
- Salesperson A: 10% (senior salesperson)
- Salesperson B: 8% (mid-level)
- Salesperson C: (blank - uses default 5%)

**Result:**
- Tiered commission structure based on seniority
- Auto-approved for fast processing

---

## Next Steps

### All Decision Points Now Configurable in UI! ✅

The following questions are **NO LONGER NEEDED** - they will be configurable by the admin in the Settings UI:

~~**Question 1:** Commission rate structure~~
~~**Question 2:** Commission approval workflow~~
~~**Question 3:** Email notifications~~

### For Implementation:

Start with **Phase 1** (5 hours):
1. Create 3 core include files
2. Register role on activation
3. Implement order attribution
4. Test with sample orders

Then proceed to **Phase 3** (8 hours) to build the Settings UI, which will make the system fully configurable without code changes.

---

## Conclusion

**Creating a B2B salesperson role with commission tracking and admin-configurable settings is HIGHLY FEASIBLE.**

The existing `ah-ho-custom` plugin provides an excellent foundation. By following this 5-phase implementation plan with the new **Admin Settings UI**, you can build a robust, secure, and fully customizable commission tracking system in approximately **18-23 hours of development time**.

### Key Benefits of Settings UI Approach:

✅ **No hard-coded values** - Everything configurable via WordPress admin
✅ **Flexible commission structures** - Support both fixed and custom rates
✅ **Adaptable workflows** - Switch between auto/manual approval anytime
✅ **Multi-recipient notifications** - Configure multiple email addresses
✅ **User-friendly** - Non-technical admins can adjust settings
✅ **Future-proof** - Easy to add new settings fields as needed

**Estimated cost:** 18-23 hours × hourly rate
**Risk level:** Low (well-understood WordPress/WooCommerce APIs + Settings API)
**Maintenance burden:** Low (extends existing plugin, no custom tables, all settings in UI)
