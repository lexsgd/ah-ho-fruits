# Salesperson Feature - Complete Explanation (Simple Language)

## What Is This Feature?

Imagine you have 3 salespeople working for your fruit business:
- Sarah
- John
- Mike

Each salesperson can create orders for customers in WordPress/WooCommerce. When an order is completed, **they automatically earn a commission** (a percentage of the sale).

This plugin **tracks everything automatically**:
- Who created which order
- How much commission they earned
- Whether the commission has been paid

---

## The Big Picture (What Happens)

### Step 1: Admin Sets Up the System

**One-Time Setup:**
1. Admin goes to **WooCommerce > Salesperson Settings**
2. Sets commission rate (e.g., 10%)
3. Decides: "Should commissions be approved automatically, or manually by me?"
4. Adds email addresses to get notifications

**Create Salesperson Users:**
1. Admin goes to **Users > Add New**
2. Creates user "Sarah" with role **"Salesperson"**
3. (Optional) Set Sarah's custom commission rate to 12% instead of default 10%

### Step 2: Salesperson Creates Orders

**Sarah logs into WordPress:**
- She sees a LIMITED admin panel (she can't see John's or Mike's orders)
- She goes to **WooCommerce > Orders > Add Order**
- Creates an order for customer "ABC Restaurant" - $500 total
- Sets order status to **Completed**

**What happens automatically behind the scenes:**
1. ✅ WordPress remembers "Sarah created this order"
2. ✅ Calculates commission: $500 × 12% = $60
3. ✅ Status: "Approved" (if auto-approve is on)
4. ✅ Sends email to admin: "Sarah earned $60 commission on order #123"

### Step 3: Sarah Checks Her Earnings

**Sarah can view her dashboard:**
- Goes to **My Commission** (in admin sidebar)
- Sees: "This month: $450 total commission"
- Sees list of all her orders with commission amounts
- Can export a CSV file for her records

### Step 4: Admin Pays Commissions

**At the end of the month:**
1. Admin goes to **Commissions** dashboard
2. Filters: "Status = Approved, Month = January"
3. Sees Sarah earned $450, John earned $380, Mike earned $220
4. Exports CSV file with all details
5. Makes bank transfers to each salesperson
6. Goes to each order, checks "Mark commission as paid"

Done! The system keeps a permanent record.

---

## How Does Security Work? (Why Sarah Can't See John's Orders)

### The Problem:
Sarah should only see orders SHE created. She shouldn't be able to:
- View John's orders
- Edit Mike's orders
- See other salespeople's commission

### The Solution: 4 Layers of Protection

Think of it like 4 locked doors:

**Layer 1: The Order List Filter**
- When Sarah views **WooCommerce > Orders**, WordPress secretly filters the list
- It ONLY shows orders where `assigned_salesperson_id = Sarah's ID`
- John's orders don't even appear in the list

**Layer 2: SQL Database Filter (Backup)**
- Even if someone tries to hack Layer 1, this blocks them at the database level
- The SQL query itself is modified to only return Sarah's orders

**Layer 3: Direct URL Prevention**
- If someone types the URL: `wp-admin/post.php?post=123&action=edit`
- WordPress checks: "Does order #123 belong to this user?"
- If no → Shows error: "You don't have permission"

**Layer 4: API Protection**
- If someone tries to access via WooCommerce REST API
- Same check: "Does this order belong to this user?"
- If no → Returns error 403 (Forbidden)

**Result:** Even if Sarah is a hacker, she physically CANNOT access other people's orders.

---

## Commission Workflow Explained (Step-by-Step)

### Auto-Approve Mode (Default) - Simpler

```
1. Order Created
   └─> WordPress remembers: "Sarah created this"

2. Order Status = Completed
   └─> Calculate: $500 × 12% = $60
   └─> Status: "Approved" ✅
   └─> Send email: "Sarah earned $60"

3. End of Month
   └─> Admin pays Sarah
   └─> Mark as "Paid" ✅
```

### Manual Approval Mode - More Control

```
1. Order Created
   └─> WordPress remembers: "Sarah created this"

2. Order Status = Completed
   └─> Calculate: $500 × 12% = $60
   └─> Status: "Pending" ⏳

3. Admin Reviews
   └─> Admin checks the order
   └─> Clicks "Approve commission" ✅
   └─> Status: "Approved"
   └─> Send email: "Sarah earned $60"

4. End of Month
   └─> Admin pays Sarah
   └─> Mark as "Paid" ✅
```

---

## Technical Details (How It Actually Works)

### What Gets Stored in the Database

For every order, WordPress stores these "hidden fields" (called meta data):

| Field Name | Example Value | What It Means |
|------------|---------------|---------------|
| `_assigned_salesperson_id` | 42 | Sarah's user ID |
| `_commission_rate` | 12 | Sarah's rate at the time (locked) |
| `_commission_amount` | 60 | Sarah earns $60 |
| `_commission_status` | approved | Ready to pay |
| `_commission_paid_date` | 2026-02-01 | When admin paid her |

**Why lock the rate?**
- Imagine on Jan 15, Sarah's rate is 12%
- Order created on Jan 15 → Locked at 12%
- On Jan 20, admin changes Sarah's rate to 15%
- The Jan 15 order STILL uses 12% (fair for both sides)

### Where Is Everything Stored?

**Plugin Files:**
```
/wp-content/plugins/ah-ho-custom/
├── ah-ho-custom.php                    (Main plugin file)
├── includes/
│   ├── salesperson-roles.php           (Creates the Salesperson role)
│   ├── salesperson-attribution.php     (Assigns orders, calculates commission)
│   ├── salesperson-query-filters.php   (4-layer security system)
│   ├── salesperson-settings.php        (Admin settings page)
│   └── salesperson-dashboard.php       (Dashboards for admin & salesperson)
├── assets/
│   └── dashboard.css                   (Styling)
└── docs/
    ├── SALESPERSON-SETUP.md            (Setup guide)
    ├── COMMISSION-WORKFLOW.md          (Workflow details)
    └── SALESPERSON-FEATURE-EXPLAINED.md (This document!)
```

**Database Storage:**
- Uses **WordPress post meta** (no custom tables needed)
- Uses **WordPress options** for settings
- Very efficient for 50-100 orders per month

---

## What Each File Does (In Simple Terms)

### 1. salesperson-roles.php
**What it does:** Creates the "Salesperson" role you see in Users > Add New

**How it works:**
- When plugin activates, runs: `add_role('ah_ho_salesperson', ...)`
- Gives permissions:
  - ✅ Can view orders (their own only)
  - ✅ Can create orders
  - ✅ Can view products
  - ❌ CANNOT view other salespersons' orders
  - ❌ CANNOT delete orders

**Also adds:**
- Custom field on user profile: "Commission Rate (%)"
- If admin enables custom rates, each salesperson can have different %

---

### 2. salesperson-attribution.php
**What it does:** The "brain" that tracks orders and calculates commission

**Key Functions:**

**Function 1: Auto-assign when order created**
```php
// When Sarah creates an order:
1. WordPress checks: "Is current user a salesperson?"
2. If yes → Save: "_assigned_salesperson_id = Sarah's ID"
3. Add order note: "Order assigned to salesperson: Sarah"
```

**Function 2: Calculate commission when order completed**
```php
// When order status = Completed:
1. Get Sarah's commission rate (12%)
2. Calculate: $500 × 12% = $60
3. Store: "_commission_amount = 60"
4. Store: "_commission_rate = 12" (locked forever)
5. Check approval mode:
   - Auto → Status = "approved", send email
   - Manual → Status = "pending", wait for admin
```

**Function 3: Handle refunds**
```php
// When order is refunded:
1. Set commission to $0
2. Change status to "refunded"
3. Add note: "Commission refunded: $60 set to $0"
```

**Function 4: Add meta box to order edit page**
- Shows "Salesperson Assignment" box
- Admin can manually assign/reassign salesperson
- Shows commission details
- Checkboxes: "Approve commission", "Mark as paid"

---

### 3. salesperson-query-filters.php
**What it does:** The 4-layer security system (prevents cross-access)

**Layer 1 Code (Simplified):**
```php
// When Sarah views the Orders page:
function filter_orders($query) {
    $user = get_current_user();

    if ($user is a salesperson) {
        $query->add_filter("only show orders where _assigned_salesperson_id = {$user->ID}");
    }
}
```

**Layer 2 Code (SQL Backup):**
```php
// Modifies the actual SQL query:
SELECT * FROM orders
WHERE order_id IN (
    SELECT order_id FROM order_meta
    WHERE meta_key = '_assigned_salesperson_id'
    AND meta_value = 42  -- Sarah's ID
)
```

**Layer 3 Code (Direct URL Prevention):**
```php
// When someone visits: /wp-admin/post.php?post=123
function prevent_unauthorized_access() {
    $order = get_order(123);
    $assigned_salesperson = $order->get_meta('_assigned_salesperson_id');

    if ($assigned_salesperson != current_user_id()) {
        show_error("Access Denied");
    }
}
```

**Layer 4 Code (REST API Protection):**
```php
// When API request: GET /wp-json/wc/v3/orders/123
function check_api_permission($order_id) {
    if (current_user is salesperson) {
        $assigned = get_meta($order_id, '_assigned_salesperson_id');
        if ($assigned != current_user_id()) {
            return error_403_forbidden;
        }
    }
}
```

---

### 4. salesperson-settings.php
**What it does:** Creates the settings page at WooCommerce > Salesperson Settings

**Settings Available:**

**Commission Rate Configuration:**
- Default Commission Rate: 10% (number input)
- Enable Custom Rates: Yes/No (checkbox)

**Approval Workflow:**
- Auto-Approve (radio button)
- Manual Approval (radio button)

**Email Notifications:**
- Recipient Emails: admin@example.com, finance@example.com (comma-separated)
- Notify when commission approved: Yes/No
- Send monthly summary to salespersons: Yes/No
- Send monthly summary to admin: Yes/No

**Quick Stats (on settings page):**
- Total Salespersons: 3
- Orders with Commission: 47
- Total Commission: $4,250
- Pending Approvals: 5

---

### 5. salesperson-dashboard.php
**What it does:** Creates 2 dashboards (admin & salesperson views)

**Admin Dashboard (Commissions menu):**

**Summary Cards:**
```
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│ Total Commission│  │ Pending Approval│  │    Approved     │  │      Paid       │
│     $4,250      │  │      $850       │  │     $1,200      │  │     $2,200      │
└─────────────────┘  └─────────────────┘  └─────────────────┘  └─────────────────┘
```

**Commission Table:**
| Order # | Date | Salesperson | Total | Rate | Commission | Status |
|---------|------|-------------|-------|------|------------|--------|
| #123 | Jan 15 | Sarah | $500 | 12% | $60 | Approved |
| #124 | Jan 16 | John | $300 | 10% | $30 | Paid |

**Filters:**
- By Salesperson: [Dropdown: All, Sarah, John, Mike]
- By Status: [Dropdown: All, Pending, Approved, Paid]
- By Month: [Dropdown: January 2026, February 2026, ...]

**Actions:**
- [Export to CSV] button → Downloads Excel-compatible file

---

**Salesperson Dashboard (My Commission menu):**

**Sarah sees:**

**Summary Cards (Her stats only):**
```
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│   This Month    │  │     Orders      │  │    Approved     │  │      Paid       │
│      $450       │  │       12        │  │      $300       │  │      $150       │
└─────────────────┘  └─────────────────┘  └─────────────────┘  └─────────────────┘
```

**Recent Orders (Her orders only):**
| Order # | Date | Customer | Total | Commission | Status |
|---------|------|----------|-------|------------|--------|
| #123 | Jan 15 | ABC Restaurant | $500 | $60 | Approved |
| #125 | Jan 18 | XYZ Cafe | $350 | $42 | Paid |

**Actions:**
- [Export My Statement] → CSV file for Sarah's records

---

## Edge Cases & Special Situations

### What happens if...

**1. Customer requests a full refund?**
- Commission amount → $0
- Status → "refunded"
- Order note: "Commission refunded: $60 set to $0"
- If admin already paid Sarah → System flags: "⚠️ Manual clawback needed"

**2. Order is cancelled before completion?**
- If commission status = "pending" or "approved" → Status changes to "cancelled"
- If already paid → Flagged for manual clawback

**3. Admin changes Sarah's commission rate mid-month?**
- Old orders keep their original rate (locked at creation time)
- New orders use the new rate
- Example:
  - Jan 1-14: Sarah's rate = 12%, Order #100 created Jan 10 → Uses 12%
  - Jan 15: Admin changes to 15%
  - Order #100 still = 12% (fair!)
  - Jan 16: New order #101 → Uses 15%

**4. Sarah is deleted from WordPress?**
- Order history preserved
- Commission data intact
- Reports show "[Deleted User]" instead of name
- Best practice: Deactivate user instead of deleting

**5. Two salespersons work on same order?**
- Current version: Only supports 1 salesperson per order
- Workaround: Admin manually splits commission
- Future enhancement: Multiple salesperson support

---

## Monthly Payment Process (Recommended)

### Day 1 of New Month:

**Step 1: Generate Report**
1. Admin goes to **Commissions**
2. Filter: Status = "Approved", Month = "January 2026"
3. Click **Export to CSV**
4. Open in Excel/Google Sheets

**Step 2: Review Report**
```
Sarah: $450 (12 orders)
John: $380 (10 orders)
Mike: $220 (8 orders)
TOTAL: $1,050
```

**Step 3: Process Payments**
- Bank transfer to Sarah: $450
- Bank transfer to John: $380
- Bank transfer to Mike: $220

**Step 4: Mark as Paid in WordPress**
1. Go to each order in the CSV
2. Open order in WordPress
3. Find "Salesperson Assignment" box
4. Check ☑ "Mark commission as paid"
5. Click "Update"

**Result:**
- Status changes from "Approved" → "Paid"
- Date recorded: 2026-02-01
- Permanent audit trail created

---

## Benefits of This System

### For Admin:
✅ **No manual calculations** - WordPress does the math
✅ **Automatic tracking** - Never forget a commission
✅ **Audit trail** - Every change is logged
✅ **Easy reporting** - Export to Excel anytime
✅ **Security built-in** - Salespeople can't see each other's data
✅ **Email notifications** - Stay informed without checking manually

### For Salespersons:
✅ **Transparency** - See your earnings anytime
✅ **Self-service** - Check your dashboard 24/7
✅ **Export statements** - Download your own records
✅ **No mistakes** - System calculates automatically
✅ **Fair** - Your rate is locked when order is created

### For Business:
✅ **Scalable** - Works for 3 or 30 salespersons
✅ **Efficient** - No custom database tables (uses WordPress built-in)
✅ **Maintainable** - Clear code structure
✅ **Documented** - Full documentation included
✅ **Flexible** - Configure everything via UI

---

## Performance & Scalability

### Current Capacity:
- **Orders per month:** 50-100 (optimal)
- **Salespersons:** 3-20 (optimal)
- **Dashboard load time:** <3 seconds
- **Export time:** <10 seconds

### When to Upgrade:
If you reach:
- **>1000 orders/month** → Migrate to custom database table
- **>50 salespersons** → Consider dedicated reporting system
- **Dashboard >5 seconds** → Add database indexes

The current system uses WordPress's built-in post meta system, which is very efficient for small-to-medium scale.

---

## Troubleshooting Guide

### Problem: "Salesperson role doesn't appear"
**Solution:**
1. Go to **Tools > Salesperson Role**
2. Click "Create Salesperson Role Now"
3. Done! Role is now available

### Problem: "Commission not calculated"
**Check:**
1. Is order status = "Completed"?
2. Is salesperson assigned to order?
3. Check order notes for error messages

**Fix:**
1. Re-save the order
2. Or change status to "Processing", then back to "Completed"

### Problem: "Wrong commission amount"
**Possible Causes:**
1. Custom rate was changed after order creation
2. Manual calculation error

**Check:**
1. View order → Salesperson Assignment box
2. See "Commission Rate: 12%" (the locked rate)
3. Verify: Order Total × Rate = Commission Amount

### Problem: "Salesperson can see other orders"
**This should NEVER happen!**

**Check:**
1. User role is exactly "Salesperson" (not Shop Manager)
2. Clear WordPress cache
3. Check WordPress error log for security filter errors

**Contact developer if this happens** - it's a critical security issue.

---

## Future Enhancements (Planned)

Possible features for v2.0:

1. **Chart.js Visualizations**
   - Line chart: Commission trend over time
   - Bar chart: Top performers
   - Pie chart: Commission breakdown by salesperson

2. **Automated Email Summaries**
   - Automatic email to each salesperson on 1st of month
   - Shows their monthly earnings
   - Includes PDF statement attachment

3. **Partial Refund Handling**
   - Currently: Full refund only
   - Future: Proportional commission reduction
   - Example: $100 refund on $500 order → Reduce commission by 20%

4. **Tiered Commission Rates**
   - Example:
     - 0-$10K sales: 10%
     - $10K-$50K sales: 12%
     - >$50K sales: 15%

5. **Multiple Salesperson Support**
   - Split commission between 2+ salespeople
   - Define percentages (e.g., 60% Sarah, 40% John)

6. **WooCommerce Analytics Integration**
   - Show commission in WooCommerce reports
   - Compare revenue vs commission costs

7. **Mobile App**
   - Salesperson checks earnings on phone
   - Push notifications when commission approved

---

## Support & Documentation

### Documentation Files:
1. **SALESPERSON-SETUP.md** - Initial setup guide
2. **COMMISSION-WORKFLOW.md** - Detailed workflow explanations
3. **SALESPERSON-FEATURE-EXPLAINED.md** - This document (deep technical explanation)

### WordPress Error Log:
If something breaks, check:
```
/wp-content/debug.log
```

Enable debugging in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Contact Information:
For technical support, contact the plugin developer with:
- WordPress version
- WooCommerce version
- Error message from debug.log
- Steps to reproduce the issue

---

## Conclusion

This salesperson commission system is designed to be:
- **Simple** - Easy to understand and use
- **Secure** - Multi-layer protection prevents data breaches
- **Automatic** - Minimal manual work required
- **Transparent** - Everyone can see their own data
- **Scalable** - Grows with your business

The system handles the boring administrative work (calculations, tracking, security) so you can focus on growing your business.

---

**Plugin Version:** 1.2.2
**Last Updated:** January 2026
**Author:** Ah Ho Fruits Development Team
