# Commission Workflow Guide

## Overview

This document explains how commissions are calculated, tracked, and paid in the Ah Ho Fruits salesperson system.

---

## Commission Lifecycle

### 1. Order Creation

**When**: Salesperson creates a new order

**What Happens**:
- Order is automatically assigned to the logged-in salesperson
- Commission status set to `pending`
- Order note added: "Order assigned to salesperson: [Name]"

**Post Meta Created**:
```
_assigned_salesperson_id = [User ID]
_commission_status = 'pending'
```

---

### 2. Order Completion

**When**: Order status changed to "Completed"

**What Happens**:
1. **Calculate Commission Rate**:
   - Check if custom rates enabled
   - Get salesperson's custom rate (if set)
   - Fallback to default rate if not set

2. **Calculate Commission Amount**:
   ```
   Commission = Order Total × (Rate / 100)
   ```

3. **Determine Status**:
   - **Auto-Approve Mode**: Status = `approved`
   - **Manual Mode**: Status remains `pending`

4. **Store Commission Data**:
   ```
   _commission_rate = [percentage]
   _commission_amount = [calculated amount]
   _commission_status = 'approved' or 'pending'
   ```

5. **Add Order Note**:
   ```
   "Commission calculated: $X.XX (Y%) - Status: approved/pending"
   ```

6. **Send Email** (if auto-approved and notifications enabled)

---

### 3. Commission Approval (Manual Mode Only)

**When**: Admin manually approves commission

**How**:
1. Go to order edit page
2. Find **Salesperson Assignment** meta box
3. Check ☑ **Approve commission**
4. Click **Update**

**What Happens**:
- Status changed from `pending` to `approved`
- Order note added: "Commission manually approved"
- Email sent to configured recipients (if enabled)

---

### 4. Commission Payment

**When**: Admin processes payment to salesperson

**How**:
1. Go to order edit page
2. Find **Salesperson Assignment** meta box
3. Check ☑ **Mark commission as paid**
4. Click **Update**

**What Happens**:
```
_commission_status = 'paid'
_commission_paid_date = [current date]
```

**Order Note**:
```
"Commission marked as paid"
```

---

## Commission Calculation Examples

### Example 1: Default Rate (10%)

**Settings**:
- Default rate: 10%
- Custom rates: Disabled
- Approval: Auto

**Order**:
- Total: $500.00
- Salesperson: John

**Calculation**:
```
Commission = $500.00 × (10 / 100) = $50.00
Status = 'approved' (auto-approve mode)
```

**Result**: John earns $50.00 commission

---

### Example 2: Custom Rate (15%)

**Settings**:
- Default rate: 10%
- Custom rates: Enabled
- John's custom rate: 15%
- Approval: Auto

**Order**:
- Total: $1,000.00
- Salesperson: John

**Calculation**:
```
Commission = $1,000.00 × (15 / 100) = $150.00
Status = 'approved' (auto-approve mode)
```

**Result**: John earns $150.00 commission (custom rate applied)

---

### Example 3: Manual Approval

**Settings**:
- Default rate: 10%
- Approval: Manual

**Order**:
- Total: $750.00
- Salesperson: Sarah

**Step 1 - Order Completed**:
```
Commission = $750.00 × (10 / 100) = $75.00
Status = 'pending' (awaiting admin approval)
```

**Step 2 - Admin Approves**:
```
Status changed to 'approved'
Email sent to admin@ahhofruits.com
```

**Step 3 - Admin Pays**:
```
Status = 'paid'
Date = 2026-02-01
```

---

## Edge Cases

### Refund Handling

**Scenario**: Order is refunded after commission paid

**Process**:
1. Order status changes to "Refunded"
2. Commission amount set to $0
3. Status changed to `refunded`
4. Order note: "Commission refunded: $X.XX set to $0"

**Admin Action Required**: Manual clawback if commission already paid

---

### Partial Refund

**Currently**: Not automatically handled

**Future Enhancement**: Proportional commission adjustment

**Workaround**:
1. Manually calculate adjusted commission
2. Edit order note with new amount
3. Adjust next payment

---

### Order Cancellation

**If commission status = `pending` or `approved`**:
- Status changed to `cancelled`
- No clawback needed
- Order note: "Commission cancelled due to order cancellation"

**If commission status = `paid`**:
- Status unchanged
- Flag added: `_commission_needs_clawback = true`
- Order note: "⚠️ Order cancelled but commission was already paid. Manual clawback required."

---

### Rate Change Mid-Month

**Question**: What if salesperson's custom rate changes?

**Answer**: Rate at order creation time is always used

**Example**:
- Jan 1: John's rate = 10%
- Jan 15: Order created → Commission uses 10%
- Jan 20: Admin changes John's rate to 15%
- Jan 15 order still uses 10% (locked at creation)
- Jan 21 orders use new 15% rate

---

### Salesperson Deleted

**What Happens**:
- Order history preserved
- Salesperson ID remains in post meta
- Commission data intact
- Reports show "[Deleted User]" as salesperson name

**Best Practice**: Don't delete salesperson users, deactivate instead

---

## Commission Statuses Explained

### Pending

**Meaning**: Order completed, awaiting approval

**When**:
- Manual approval mode
- Order status = Completed
- Admin hasn't approved yet

**Actions Available**:
- Admin can approve
- Admin can edit order
- Salesperson can view

---

### Approved

**Meaning**: Commission approved, ready for payment

**When**:
- Auto-approve mode (automatic)
- Manual mode + admin approved

**Actions Available**:
- Admin can mark as paid
- Admin can export for payment processing

---

### Paid

**Meaning**: Commission paid to salesperson

**When**: Admin marks as paid

**Actions Available**:
- View only (audit trail)
- Include in accounting reports

---

### Cancelled

**Meaning**: Order cancelled, commission voided

**When**: Order status changed to Cancelled

**Actions Available**:
- None (final status)

---

### Refunded

**Meaning**: Order refunded, commission reversed

**When**: Order status changed to Refunded

**Actions Available**:
- Manual clawback if already paid

---

## Dashboard Views

### Admin Commission Dashboard

**Location**: **Commissions** menu

**Summary Cards**:
- Total Commission (all statuses)
- Pending Approval
- Approved (ready to pay)
- Paid

**Table View**:
- Order #, Date
- Salesperson
- Order Total
- Rate, Commission
- Status

**Filters**:
- Salesperson
- Status
- Month

**Actions**:
- Export to CSV
- View individual order

---

### Salesperson Dashboard

**Location**: **My Commission** menu

**Summary Cards**:
- This Month (total)
- Orders (count)
- Approved
- Paid

**Table View**:
- Recent orders only (assigned to this salesperson)
- Same columns as admin view

**Actions**:
- Export personal statement
- View order details

---

## Monthly Payment Process

### Recommended Workflow

**Day 1 of Month**:
1. Go to **Commissions**
2. Filter by:
   - Status: Approved
   - Month: Previous month
3. Click **Export to CSV**
4. Review CSV in Excel/Google Sheets
5. Process payments (bank transfer, check, etc.)

**Day 2-3 of Month**:
1. For each paid order:
   - Open order
   - Check ☑ "Mark commission as paid"
   - Update

**Alternative (Bulk)**:
1. Export CSV with Order IDs
2. Use WP-CLI or custom script to bulk update
3. Verify with dashboard

---

## Reporting

### Monthly Commission Report

**For Accounting**:
1. Export: Status = All, Month = [Target]
2. Group by salesperson in Excel
3. Sum by status (pending/approved/paid)
4. Reconcile with payments

### Salesperson Performance

**Top Performers**:
1. Export: Month = [Target]
2. Sort by Commission Amount (descending)
3. Identify top 3 salespersons

### Commission vs. Revenue

**Calculate**:
```
Commission % of Revenue = (Total Commission / Total Order Value) × 100
```

**Benchmark**: Should match configured rates

---

## Email Notifications

### Approval Notification

**Subject**: `[Ah Ho Fruits] Commission Approved - Order #123`

**Body**:
```
Commission has been approved:

Salesperson: John Doe
Order: #123
Commission Amount: $50.00
Order Total: $500.00

View Order: [Link]
```

**Recipients**: From settings (e.g., admin@ahhofruits.com, finance@ahhofruits.com)

---

## Best Practices

### For Admins

1. **Set Approval Mode Early**: Choose auto/manual before go-live
2. **Regular Exports**: Export monthly for accounting
3. **Timely Payments**: Pay within 7 days of month-end
4. **Monitor Pending**: Check pending approvals daily (manual mode)
5. **Audit Trail**: Never delete commission data

### For Salespersons

1. **Complete Orders Promptly**: Change status to Completed when shipped
2. **Check Dashboard**: Review commission daily
3. **Report Discrepancies**: Contact admin immediately if commission looks wrong
4. **Export Statements**: Download monthly for personal records

---

## Troubleshooting

### Commission Not Calculated

**Check**:
1. Order status = Completed?
2. Salesperson assigned to order?
3. Check order notes for error messages

**Fix**:
1. Re-save order
2. Manually trigger: Change status to Processing, then back to Completed

---

### Wrong Commission Amount

**Possible Causes**:
1. Wrong rate applied
2. Custom rate changed after order
3. Manual calculation error

**Fix**:
1. Check `_commission_rate` in order meta
2. Recalculate: Order Total × (Rate / 100)
3. Contact admin to adjust

---

### Email Not Received

**Check**:
1. Spam folder
2. Email address in settings correct?
3. WordPress email working? (use test plugin)

**Fix**:
1. Update email address
2. Check server email configuration
3. Use SMTP plugin (WP Mail SMTP)

---

## Future Enhancements

Planned features:
- Automated monthly email summaries
- Partial refund handling
- Commission tiers (e.g., 10% up to $10K, 12% above)
- Salesperson leaderboard
- Mobile app access
- Automatic payment via PayPal/bank transfer

---

**Plugin Version**: 1.2.0
**Last Updated**: January 2026
