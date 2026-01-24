# Salesperson Setup Guide

## Overview

The Ah Ho Fruits Custom plugin now includes a complete B2B salesperson management system with commission tracking, multi-layer security, and customizable approval workflows.

---

## Initial Setup

### Step 1: Configure Commission Settings

1. Go to **WooCommerce > Salesperson Settings**
2. Configure the following:

#### Commission Rate Configuration
- **Default Commission Rate**: Set the default percentage (e.g., 10%)
- **Enable Custom Rates**: Check this to allow per-salesperson rates

#### Approval Workflow
- **Auto-Approve** (Recommended): Commissions approved when order completes
- **Manual Approval**: Admin must approve each commission manually

#### Email Notifications
- **Notification Recipients**: Enter comma-separated email addresses
- **Notification Events**:
  - ☑ Notify when commission is approved
  - ☑ Send monthly summary to salespersons
  - ☐ Send monthly summary to admin

3. Click **Save Settings**

---

### Step 2: Create Salesperson Users

1. Go to **Users > Add New**
2. Fill in user details:
   - Username: (e.g., `john_sales`)
   - Email: salesperson's email address
   - First Name & Last Name
   - **Role**: Select **Salesperson**
3. Click **Add New User**

#### Set Custom Commission Rate (Optional)

If **Enable Custom Rates** is checked in settings:

1. Go to **Users > All Users**
2. Click **Edit** on the salesperson
3. Scroll to **Commission Settings**
4. Enter **Custom Commission Rate (%)** (e.g., 12)
5. Leave blank to use default rate
6. Click **Update Profile**

---

### Step 3: Test the System

1. **Login as Salesperson**
   - Use the salesperson credentials
   - Notice limited admin menu (only relevant sections visible)

2. **Create a Test Order**
   - Go to **WooCommerce > Orders > Add Order**
   - Select a customer
   - Add products
   - Set order status to **Processing**
   - Click **Create**

3. **Complete the Order**
   - Open the order
   - Change status to **Completed**
   - Check order notes for commission calculation

4. **View Commission Dashboard**
   - Go to **My Commission** (in admin menu)
   - See personal commission summary

---

## Salesperson Capabilities

Salespersons can:
- ✅ Create new orders for customers
- ✅ View and edit **only their own orders**
- ✅ View commission dashboard
- ✅ Export personal commission statements
- ✅ View products (read-only)
- ✅ Access customer list

Salespersons cannot:
- ❌ View other salespersons' orders
- ❌ Delete orders
- ❌ Modify commission amounts
- ❌ Access plugin settings
- ❌ Edit products

---

## Security Features

The system implements **4 layers of protection** to prevent cross-salesperson data access:

1. **Layer 1**: Query filtering on order list
2. **Layer 2**: SQL-level filtering (backup)
3. **Layer 3**: Direct URL access prevention
4. **Layer 4**: REST API protection

**Result**: Salespersons can ONLY see orders assigned to them.

---

## Admin Features

### Commission Dashboard

**Location**: **Commissions** (admin menu)

**Features**:
- Total commission overview
- Filter by salesperson, status, month
- Commission breakdown (pending/approved/paid)
- Export to CSV

### Order Management

When editing an order, admins see **Salesperson Assignment** meta box:

- Assign/reassign salesperson
- View commission details (rate, amount, status)
- Approve commission (if manual mode)
- Mark commission as paid

---

## Approval Workflows

### Auto-Approval Workflow (Recommended)

```
Order Created → Order Completed → Status: Approved → Admin Marks Paid → Status: Paid
```

**Best for**: Simple, low-volume operations

### Manual Approval Workflow

```
Order Created → Order Completed → Status: Pending → Admin Approves → Status: Approved → Admin Marks Paid → Status: Paid
```

**Best for**: High-volume operations requiring oversight

---

## Commission Statuses

| Status | Meaning |
|--------|---------|
| **Pending** | Order completed, awaiting approval (manual mode) |
| **Approved** | Commission approved, ready for payment |
| **Paid** | Commission paid to salesperson |
| **Cancelled** | Order cancelled before payment |
| **Refunded** | Order refunded, commission reversed |

---

## Email Notifications

### Approval Notification

**Sent when**: Commission is approved (auto or manual)

**Recipients**: Configured in settings

**Contains**:
- Salesperson name
- Order number
- Commission amount
- Order total
- Link to order

### Monthly Summary (Future Enhancement)

**Sent**: 1st day of month
**Recipients**: Salespersons and/or admins
**Contains**: Monthly commission breakdown

---

## CSV Export

### Admin Export

1. Go to **Commissions**
2. Apply filters (salesperson, month, status)
3. Click **Export to CSV**

**Includes**:
- Order number, date
- Salesperson name
- Order total
- Commission rate & amount
- Status

### Salesperson Export

1. Go to **My Commission**
2. Select month
3. Click **Export My Statement**

**Includes**: Personal orders and commissions only

---

## Troubleshooting

### Salesperson can't see orders

**Check**:
1. Order is assigned to that salesperson (check order meta box)
2. User role is exactly `ah_ho_salesperson`
3. Clear WordPress object cache if using caching plugin

### Commission not calculated

**Check**:
1. Order status is **Completed**
2. Salesperson is assigned to the order
3. Check order notes for calculation log
4. Verify commission rate is set (default or custom)

### Email notifications not sending

**Check**:
1. Settings > Notification Recipients has valid emails
2. "Notify when commission is approved" is checked
3. Test WordPress email (use WP Mail Test plugin)
4. Check server spam filters

---

## Best Practices

1. **Set Default Rate First**: Configure default commission rate before creating salespersons
2. **Use Custom Rates Sparingly**: Only for senior salespersons or special arrangements
3. **Regular Exports**: Export monthly statements for accounting
4. **Monitor Approval Queue**: Check pending commissions daily (manual mode)
5. **Communicate Changes**: Notify salespersons when approval mode changes

---

## Next Steps

- See **COMMISSION-WORKFLOW.md** for detailed commission lifecycle
- Configure WooCommerce order statuses to match your workflow
- Train salespersons on order creation process
- Set up monthly export automation (future enhancement)

---

## Support

For technical issues:
1. Check WordPress error log
2. Enable WP_DEBUG to see detailed errors
3. Verify WooCommerce compatibility (v8.0+)
4. Check PHP version (7.4+)

---

**Plugin Version**: 1.2.0
**Last Updated**: January 2026
