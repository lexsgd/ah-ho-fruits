# Ah Ho Fruits Custom Plugin

Custom WooCommerce functionality for Ah Ho Fruits delivery workflow.

## Features

### Custom Order Statuses

Adds 6 custom order statuses to WooCommerce for managing the complete delivery workflow:

| Status | Meaning | Next Action | Color |
|--------|---------|-------------|-------|
| **Processing** | Order received, not packed | Pack order | Purple (default) |
| **Ready for Delivery** | Packed, awaiting driver | Assign to driver | Blue |
| **Out for Delivery** | With delivery driver | Mark delivered | Orange |
| **Delivered - Paid** | Complete (B2C / Cash) | None - closed | Green |
| **Delivered - Awaiting Payment** | B2B credit terms | Send invoice, follow up | Yellow |
| **Payment Received** | B2B paid (reconciled) | None - closed | Dark Green |

## Installation

1. Upload the `ah-ho-custom` folder to `/wp-content/plugins/`
2. Activate the plugin through WordPress admin → Plugins
3. **Requires WooCommerce** to be installed and active

## Usage

### Changing Order Status

**Method 1: Single Order**
1. Go to **WooCommerce → Orders**
2. Click on an order
3. Find the **Order Actions** box on the right
4. Change the status dropdown to your desired status
5. Click **Update**

**Method 2: Bulk Actions**
1. Go to **WooCommerce → Orders**
2. Select multiple orders using checkboxes
3. Choose status from **Bulk Actions** dropdown
4. Click **Apply**

### Workflow Example

**B2C (Cash/Card Payment):**
```
Pending Payment → Processing → Ready for Delivery → Out for Delivery → Delivered - Paid
```

**B2B (Credit Terms):**
```
Processing → Ready for Delivery → Out for Delivery → Delivered - Awaiting Payment → Payment Received
```

## Features Included

### 1. Order Status Labels
All custom statuses appear in:
- Order edit page dropdown
- Orders list page
- Bulk actions dropdown
- WooCommerce reports

### 2. Stock Management
- Stock is automatically reduced when order moves to "Out for Delivery"
- Prevents double-reduction with meta flag

### 3. Order Notes
- Automatic order notes added when status changes
- Includes next action guidance

### 4. Color Coding
Custom colors for easy visual identification:
- 🔵 Blue: Ready for Delivery
- 🟠 Orange: Out for Delivery
- 🟢 Green: Delivered - Paid
- 🟡 Yellow: Delivered - Awaiting Payment
- 🟢 Dark Green: Payment Received

### 5. Tooltips
Hover over status dropdown to see:
- What the status means
- What action to take next

### 6. Reports
Custom statuses included in WooCommerce reports for accurate sales tracking.

## Technical Details

### Status Slugs

| Display Name | Slug | Post Status |
|--------------|------|-------------|
| Ready for Delivery | ready-delivery | wc-ready-delivery |
| Out for Delivery | out-delivery | wc-out-delivery |
| Delivered - Paid | delivered-paid | wc-delivered-paid |
| Delivered - Awaiting Payment | delivered-awaiting | wc-delivered-awaiting |
| Payment Received | payment-received | wc-payment-received |

### Hooks Used

- `init` - Register post statuses
- `wc_order_statuses` - Add to WooCommerce status list
- `bulk_actions-edit-shop_order` - Add bulk actions
- `woocommerce_reports_order_statuses` - Include in reports
- `woocommerce_order_is_paid_statuses` - Mark as paid
- `woocommerce_order_status_changed` - Trigger actions on status change

## Customization

### Change Status Colors

Edit `/includes/custom-order-statuses.php` and modify the CSS in the `ah_ho_custom_order_status_styles()` function.

### Add Email Notifications

To send emails when status changes, create email templates in:
```
/wp-content/themes/your-theme/woocommerce/emails/
```

### Modify Status Labels

Change the `_x()` function strings in the `ah_ho_register_custom_order_statuses()` function.

## Requirements

- WordPress 6.0+
- WooCommerce 8.0+
- PHP 7.4+

## Support

For issues or questions, contact the development team.

## Changelog

### Version 1.0.0 - 2026-01-23
- Initial release
- Added 5 custom order statuses
- Added stock reduction on "Out for Delivery"
- Added automatic order notes
- Added color coding and tooltips
- Added bulk actions support
- Added reports integration
