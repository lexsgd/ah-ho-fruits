# Ah Ho Fruit - PDF Invoicing & Packing Lists System
## Complete User Guide

**Version:** 1.3.0
**Date:** January 24, 2026
**Plugin:** Ah Ho Fruit - Invoicing & Packing Lists
**WordPress Version:** 6.0+
**WooCommerce Version:** 8.0+
**PHP Version:** 7.4+

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [System Overview](#system-overview)
3. [Key Features](#key-features)
4. [Installation & Setup](#installation--setup)
5. [User Roles & Permissions](#user-roles--permissions)
6. [PDF Document Types](#pdf-document-types)
7. [Settings Configuration](#settings-configuration)
8. [Email Automation](#email-automation)
9. [Workflow Guides](#workflow-guides)
10. [Troubleshooting](#troubleshooting)
11. [Technical Architecture](#technical-architecture)
12. [Appendix](#appendix)

---

## Executive Summary

The **Ah Ho Fruit - Invoicing & Packing Lists** plugin is a comprehensive PDF document generation system designed specifically for fruit delivery businesses. It automates the creation of invoices, packing slips, and delivery orders while integrating seamlessly with WooCommerce.

### Key Benefits

✅ **Automated PDF Generation** - Invoices, packing slips, and delivery orders created automatically
✅ **Sequential Invoice Numbering** - Professional invoice numbering (AHF-00001, AHF-00002, etc.)
✅ **Email Integration** - Auto-attach PDFs to WooCommerce emails
✅ **Route Optimization** - Consolidated packing slips sorted by delivery date → postal code
✅ **Customer Safety** - Allergy notes highlighted in BOLD RED on packing slips
✅ **Driver-Friendly** - Extra large text (28-48px) on delivery orders
✅ **Warehouse Efficiency** - Checkbox columns for storeman to tick off items
✅ **Zero Configuration Required** - Works out of the box with sensible defaults

### Who Is This For?

- **Admin Staff** - Generate invoices and manage settings
- **Warehouse Staff** - Print consolidated packing slips for batch preparation
- **Delivery Drivers** - Use delivery orders with large text and signature boxes
- **Customers** - Receive professional invoices via email

---

## System Overview

### What This Plugin Does

The plugin extends WooCommerce with three specialized PDF document types:

```
Order Created
    ↓
Processing → Packing Slip (to warehouse) ← Auto-attached to admin email
    ↓
Out for Delivery → Delivery Order (to customer) ← Auto-attached to customer email
    ↓
Completed → Invoice (to customer) ← Auto-attached to customer email
```

### Architecture Overview

```
WordPress/WooCommerce
    ↓
Ah Ho Invoicing Plugin
    ↓
Dompdf Library (2.0.8)
    ↓
PDF Generation Engine
    ↓
Cache Storage (/wp-content/pdf-cache/)
    ↓
Email Attachment System
```

---

## Key Features

### 1. Sequential Invoice Numbering

**How It Works:**
- Invoices are numbered sequentially: AHF-00001, AHF-00002, AHF-00003...
- Uses database locking to prevent duplicate numbers
- Customizable prefix (default: "AHF-")
- Configurable padding (default: 5 digits)

**Example:**
```
Order #1234 → Invoice AHF-00001
Order #1235 → Invoice AHF-00002
Order #1236 → Invoice AHF-00003
```

**Settings:**
- Prefix: `AHF-` (can be changed to `INV-`, `FRUIT-`, etc.)
- Starting number: `1` (can start from any number)
- Padding: `5` (00001 vs 0001 vs 001)

### 2. Consolidated Packing Slips

**What Is It:**
A single PDF containing multiple orders, sorted by delivery route for warehouse efficiency.

**Sorting Options:**
1. **Delivery Date → Postal Code** (Recommended) - Groups by day, then by route
2. **Postal Code → Delivery Date** - Groups by route, then by day
3. **Order Number** - Sequential by order ID

**Use Case:**
Warehouse staff can print one packing slip for tomorrow's deliveries, with all orders sorted by delivery route. This eliminates the need to print individual packing slips and manually sort them.

**Example Output:**
```
CONSOLIDATED PACKING SLIP
Delivery Date: 2026-01-25
Total Orders: 15
Total Items: 48

═══════════════════════════════════════
DELIVERY DATE: 2026-01-25
POSTAL CODE: 123456
═══════════════════════════════════════

Order #1234 - John Doe
□ Durian (2kg) - 2 units
□ Mango (1kg) - 3 units

Order #1235 - Jane Smith
□ Apple (500g) - 5 units
□ Orange (1kg) - 2 units

═══════════════════════════════════════
POSTAL CODE: 234567
═══════════════════════════════════════

Order #1236 - Bob Lee
□ Banana (1kg) - 4 units
```

### 3. Customer Allergy Highlighting

**How It Works:**
- Customer notes containing keywords like "allergy", "allergic", "dietary restriction", "halal", "vegetarian" are automatically detected
- These notes appear in **BOLD RED TEXT** on packing slips
- Ensures warehouse staff cannot miss critical information

**Example:**
```
Customer Notes:
⚠️ CUSTOMER HAS PEANUT ALLERGY - DO NOT PACK PEANUTS ⚠️
(This text appears in bold red on the packing slip)
```

**Supported Keywords:**
- allergy, allergic
- dietary restriction, diet
- halal, kosher, vegetarian, vegan
- gluten, lactose, dairy
- medical condition

### 4. Driver-Optimized Delivery Orders

**Special Features:**
- **Extra Large Fonts** - Addresses: 28px, Phone: 32px, Customer name: 24px
- **Highlighted Sections** - Red border boxes for delivery instructions
- **COD Payment Section** - Clear payment collection area with amount due
- **Signature Boxes** - Separate boxes for driver and customer signatures
- **Delivery Notes** - Special handling instructions prominently displayed

**Font Sizes:**
```
Standard Invoice/Packing Slip: 10-12px
Delivery Order:
  - Customer Name: 24px
  - Delivery Address: 28px
  - Phone Number: 32px
  - Postal Code: 36px (highlighted)
  - Delivery Instructions: 18px (red border)
```

### 5. Email Automation

**Automatic Attachments:**

| Email Type | PDF Attached | Recipient | When Sent |
|-----------|-------------|-----------|-----------|
| New Order | Packing Slip | Admin | Order created |
| Processing Order | Invoice (optional) | Customer | Status → Processing |
| Out for Delivery | Delivery Order | Customer | Status → Out for Delivery |
| Order Completed | Invoice | Customer | Status → Completed |

**Configuration:**
All email attachments can be enabled/disabled in:
**WooCommerce > Settings > PDF Invoicing > Email Automation**

### 6. PDF Caching System

**How It Works:**
- Generated PDFs are stored in `/wp-content/pdf-cache/`
- File names use MD5 hash: `invoice-[order-id]-[hash].pdf`
- Cached PDFs are served instantly on subsequent requests
- Old PDFs (>30 days) are automatically deleted via WP-Cron

**Benefits:**
- ⚡ Fast downloads (cached PDFs load in <100ms)
- 💾 Reduces server load (no regeneration needed)
- 📊 Statistics tracking (cache size, PDF count)

**Cache Management:**
```
Current Cache Statistics:
- Total PDFs Cached: 127
- Cache Size: 4.52 MB
- Oldest PDF: 28 days ago
- Auto-cleanup: Enabled (daily)
```

### 7. Custom Order Status: "Out for Delivery"

**What Is It:**
A new WooCommerce order status specifically for delivery tracking.

**Visual Appearance:**
- Orange badge with truck icon (🚚)
- Easily distinguishable from default statuses

**Workflow Integration:**
```
Processing → Out for Delivery → Completed
```

**Email Trigger:**
When order status changes to "Out for Delivery", the customer receives:
- Email notification with delivery details
- Delivery order PDF attachment
- Expected delivery date (if set)
- Driver contact information

---

## Installation & Setup

### Prerequisites

✅ WordPress 6.0 or higher
✅ WooCommerce 8.0 or higher
✅ PHP 7.4 or higher
✅ PHP memory limit: 256MB or higher (recommended)
✅ Write permissions on `/wp-content/` directory

### Step 1: Install Plugin

**Option A: Manual Upload (Recommended for Vodien Hosting)**

1. Download plugin ZIP file or copy entire plugin folder
2. Upload to `/wp-content/plugins/ah-ho-invoicing/`
3. **IMPORTANT:** Ensure `vendor/` directory is included (contains Dompdf library)

**Option B: WordPress Admin Upload**

1. Go to **WordPress Admin > Plugins > Add New**
2. Click **Upload Plugin**
3. Choose ZIP file and click **Install Now**
4. Click **Activate**

### Step 2: Verify Installation

After activation, check for:

✅ **Admin Menu:** "PDF Documents" menu item should appear in sidebar
✅ **Settings Tab:** WooCommerce > Settings should have "PDF Invoicing" tab
✅ **Order Edit:** "PDF Documents" metabox should appear in order sidebar
✅ **Order Status:** "Out for Delivery" status in order status dropdown

### Step 3: Configure Company Details

1. Go to **WooCommerce > Settings > PDF Invoicing**
2. Fill in **Company Branding** section:

| Field | Example | Required |
|-------|---------|----------|
| Company Name | Ah Ho Fruit Pte Ltd | ✅ Yes |
| Company Address | 123 Fruit Lane, Singapore 123456 | ✅ Yes |
| Phone Number | +65 1234 5678 | ✅ Yes |
| Email Address | hello@ahhofruits.com | ✅ Yes |
| UEN Number | 201234567A | ✅ Yes |
| GST Registration | M12345678X | ⚠️ If applicable |
| Bank Name | DBS Bank | ✅ Yes |
| Bank Account | 123-456-789-0 | ✅ Yes |

3. Click **Save Changes**

### Step 4: Configure Email Automation

1. In the same settings page, scroll to **Email Automation** section
2. Check the boxes for desired email attachments:

**Recommended Settings:**
```
☑ Attach Invoice to "Order Completed" (customer)
☑ Attach Packing Slip to "New Order" (admin)
☑ Attach Delivery Order to "Out for Delivery" (customer)
☐ Attach Invoice to "Processing Order" (optional)
```

3. Click **Save Changes**

### Step 5: Configure PDF Options (Optional)

**Cache Settings:**
- **Enable PDF Caching:** ☑ (recommended)
- **Cache Cleanup (Days):** 30 (delete PDFs older than 30 days)

**PDF Format:**
- **Paper Size:** A4 (210 x 297 mm) or Letter (8.5 x 11 in)

### Step 6: Configure Invoice Numbering (Optional)

Default settings work well for most businesses:

```
Invoice Prefix: AHF-
Starting Number: 1
Number Padding: 5 (results in AHF-00001)
```

**Customization Examples:**
```
INV-0001 → Prefix: "INV-", Padding: 4
FRUIT-000001 → Prefix: "FRUIT-", Padding: 6
2026-00001 → Prefix: "2026-", Padding: 5
```

### Step 7: Test the System

1. Create a test order in **WooCommerce > Orders > Add Order**
2. Add a customer and product
3. Save the order
4. In the **PDF Documents** metabox (right sidebar), click:
   - **Generate Invoice** - Should download PDF with invoice number
   - **Download Packing Slip** - Should download warehouse version
   - **Download Delivery Order** - Should download driver version
5. Verify all PDFs contain correct company branding

✅ **Setup Complete!** The system is now ready for production use.

---

## User Roles & Permissions

### Administrator

**Can Do:**
- ✅ Generate all PDF types for any order
- ✅ Configure plugin settings
- ✅ Generate consolidated packing slips
- ✅ View PDF statistics
- ✅ Manage email automation
- ✅ Adjust invoice numbering

**Cannot Do:**
- ❌ N/A (full access)

### Shop Manager

**Can Do:**
- ✅ Generate all PDF types for orders
- ✅ Generate consolidated packing slips
- ✅ View PDF statistics

**Cannot Do:**
- ❌ Modify plugin settings
- ❌ Change invoice numbering

### Warehouse Staff (Custom Role)

**Can Do:**
- ✅ View and download packing slips
- ✅ Generate consolidated packing slips for batch preparation

**Cannot Do:**
- ❌ Access invoices
- ❌ Access delivery orders
- ❌ Modify settings

**How to Create Warehouse Role:**
```php
// Add to functions.php or custom plugin
add_role('warehouse_staff', 'Warehouse Staff', [
    'read' => true,
    'read_shop_order' => true,
    'ah_ho_download_packing_slip' => true,
]);
```

### Customer

**Can Do:**
- ✅ Receive invoices via email (when order completed)
- ✅ Receive delivery orders via email (when out for delivery)

**Cannot Do:**
- ❌ Generate PDFs manually
- ❌ Access admin features

---

## PDF Document Types

### 1. Invoice

**Purpose:** Professional invoice for customer billing and accounting

**Contains:**
- ✅ Sequential invoice number (AHF-00001)
- ✅ Company branding (logo, name, address, UEN, GST)
- ✅ Customer billing details
- ✅ Itemized order details with prices
- ✅ Subtotal, tax, shipping, total
- ✅ Payment method
- ✅ Bank account details (for payment reference)
- ✅ Order date and invoice date
- ✅ Order number reference

**Layout Example:**
```
┌─────────────────────────────────────────────────┐
│ [Company Logo]        INVOICE #AHF-00001        │
│                                                  │
│ Ah Ho Fruit Pte Ltd                            │
│ 123 Fruit Lane, Singapore 123456                │
│ UEN: 201234567A | GST: M12345678X               │
│                                                  │
│ Bill To:                                         │
│ John Doe                                         │
│ 456 Customer Street                              │
│ Singapore 654321                                 │
│                                                  │
│ Order Date: 2026-01-24                          │
│ Invoice Date: 2026-01-25                        │
│ Order #: 1234                                   │
│                                                  │
├─────────────────────────────────────────────────┤
│ ITEM              QTY    PRICE      TOTAL       │
├─────────────────────────────────────────────────┤
│ Durian (2kg)       2    $25.00     $50.00      │
│ Mango (1kg)        3    $8.00      $24.00      │
│ Apple (500g)       5    $3.50      $17.50      │
├─────────────────────────────────────────────────┤
│                           Subtotal:  $91.50     │
│                           Tax (9%):  $8.24      │
│                           Shipping:  $5.00      │
│                           ─────────────────     │
│                           TOTAL:     $104.74    │
├─────────────────────────────────────────────────┤
│ Payment Method: Bank Transfer                    │
│ Bank: DBS Bank                                   │
│ Account: 123-456-789-0                          │
└─────────────────────────────────────────────────┘
```

**When Generated:**
- Automatically when order status = "Completed"
- Manually via order edit page metabox
- On demand from "PDF Documents" admin page

**File Naming:**
- `invoice-1234-a1b2c3d4.pdf` (order ID + MD5 hash)

### 2. Packing Slip

**Purpose:** Warehouse preparation document for storemen

**Contains:**
- ✅ Order number and customer name
- ✅ Delivery address and postal code
- ✅ Product list with SKU and quantity
- ✅ **NO PRICES** (warehouse staff don't need pricing)
- ✅ Checkbox column for tick-off
- ✅ Weight calculations (per item and total)
- ✅ Customer notes (allergies in BOLD RED)
- ✅ Special handling instructions

**Layout Example:**
```
┌─────────────────────────────────────────────────┐
│ PACKING SLIP - Order #1234                      │
│                                                  │
│ Customer: John Doe                               │
│ Delivery: 456 Customer Street, S(654321)        │
│ Phone: +65 9876 5432                            │
│ Delivery Date: 2026-01-25                       │
│                                                  │
│ ⚠️ CUSTOMER HAS PEANUT ALLERGY ⚠️               │
│    DO NOT PACK ANY PEANUT PRODUCTS               │
│                                                  │
├──┬────────────────┬─────┬────────┬──────────────┤
│☐│ ITEM           │ SKU │  QTY   │   WEIGHT     │
├──┼────────────────┼─────┼────────┼──────────────┤
│☐│ Durian (2kg)   │ D001│   2    │   4.0 kg    │
│☐│ Mango (1kg)    │ M002│   3    │   3.0 kg    │
│☐│ Apple (500g)   │ A003│   5    │   2.5 kg    │
├──┴────────────────┴─────┴────────┴──────────────┤
│                           Total Weight: 9.5 kg   │
│                           Total Items:  3        │
└─────────────────────────────────────────────────┘

Special Notes:
- This is a gift - please include gift card
- Pack fruits in separate bags
```

**Key Features:**
- ☐ **Checkbox Column** - Storeman ticks as items are packed
- ⚠️ **Allergy Highlighting** - Red text for critical notes
- 📦 **SKU Display** - Easy product identification
- ⚖️ **Weight Calculations** - Total weight for delivery planning

**When Generated:**
- Automatically when new order created (emailed to admin)
- Manually via order edit page metabox
- Bulk generation via "PDF Documents" admin page

**File Naming:**
- Individual: `packing-slip-1234-a1b2c3d4.pdf`
- Consolidated: `packing-slip-consolidated-2026-01-25-a1b2c3d4.pdf`

### 3. Delivery Order

**Purpose:** Driver-friendly document with extra large text

**Contains:**
- ✅ **EXTRA LARGE** delivery address (28px font)
- ✅ **EXTRA LARGE** phone number (32px font)
- ✅ **EXTRA LARGE** postal code (36px font, highlighted)
- ✅ Customer name (24px font)
- ✅ Order items (for verification)
- ✅ Delivery instructions (red border box)
- ✅ COD payment section (if applicable)
- ✅ Signature boxes (driver + customer)
- ✅ Delivery date and time window

**Layout Example:**
```
┌─────────────────────────────────────────────────┐
│ DELIVERY ORDER - Order #1234                    │
│                                                  │
│ CUSTOMER: John Doe                               │
│                                                  │
│ DELIVERY ADDRESS:                                │
│ 456 Customer Street                              │
│ Block 123, Unit #05-67                          │
│ Singapore 654321                                 │
│                                                  │
│ POSTAL CODE: 654321  ◄─ LARGE TEXT              │
│                                                  │
│ CONTACT: +65 9876 5432  ◄─ LARGE TEXT           │
│                                                  │
│ DELIVERY DATE: Friday, 25 January 2026          │
│ TIME WINDOW: 2:00 PM - 4:00 PM                  │
│                                                  │
├─────────────────────────────────────────────────┤
│ ITEMS TO DELIVER:                                │
│ • Durian (2kg) x 2                              │
│ • Mango (1kg) x 3                               │
│ • Apple (500g) x 5                              │
├─────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────┐ │
│ │ ⚠️ DELIVERY INSTRUCTIONS ⚠️                 │ │
│ │                                              │ │
│ │ • Customer has peanut allergy                │ │
│ │ • This is a GIFT - include card              │ │
│ │ • Call customer 15 mins before arrival       │ │
│ └─────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────┤
│ COD PAYMENT (if applicable):                     │
│                                                  │
│ Amount to Collect: $104.74                      │
│ Payment Method: □ Cash  □ Card  □ PayNow       │
│                                                  │
│ Amount Collected: $_____________                │
│                                                  │
├─────────────────────────────────────────────────┤
│ SIGNATURES:                                      │
│                                                  │
│ Driver:                    Customer:             │
│ ___________________       ___________________    │
│                                                  │
│ Time Delivered: _________                       │
└─────────────────────────────────────────────────┘
```

**Key Features:**
- 📱 **Driver Can Read While Driving** - Extra large fonts
- 🚚 **Route Optimized** - Postal code prominently displayed
- ⚠️ **Critical Instructions Highlighted** - Red border boxes
- 💵 **COD Payment Section** - Clear payment collection area
- ✍️ **Signature Capture** - Legal proof of delivery

**When Generated:**
- Automatically when order status = "Out for Delivery"
- Manually via order edit page metabox

**File Naming:**
- `delivery-order-1234-a1b2c3d4.pdf`

---

## Settings Configuration

### Accessing Settings

**Path:** WooCommerce > Settings > PDF Invoicing

### Section 1: Company Branding

**Purpose:** Configure company details that appear on all PDFs

**Fields:**

| Setting | Description | Example | Required |
|---------|-------------|---------|----------|
| Company Name | Legal business name | Ah Ho Fruit Pte Ltd | ✅ |
| Company Address | Full business address (multiline) | 123 Fruit Lane<br>Singapore 123456 | ✅ |
| Phone Number | Business contact number | +65 1234 5678 | ✅ |
| Email Address | Business email | hello@ahhofruits.com | ✅ |
| UEN Number | Singapore Unique Entity Number | 201234567A | ✅ |
| GST Registration | GST registration number | M12345678X | If GST-registered |
| Bank Name | Bank for payment details | DBS Bank | ✅ |
| Bank Account | Bank account number | 123-456-789-0 | ✅ |

**Tips:**
- Use multiline address formatting (press Enter for new line)
- Include unit/block numbers for deliveries
- GST number appears on invoices only if GST-registered
- Bank details appear on invoices for payment reference

### Section 2: Email Automation

**Purpose:** Configure which PDFs are auto-attached to emails

**Options:**

| Email Type | PDF Attached | Recipient | Default |
|-----------|-------------|-----------|---------|
| Order Completed | Invoice | Customer | ✅ Enabled |
| New Order | Packing Slip | Admin | ✅ Enabled |
| Out for Delivery | Delivery Order | Customer | ✅ Enabled |
| Processing Order | Invoice | Customer | ❌ Disabled |

**Best Practices:**
```
✅ RECOMMENDED:
- Enable "Order Completed → Invoice" (customer needs invoice)
- Enable "New Order → Packing Slip" (warehouse needs to prepare)
- Enable "Out for Delivery → Delivery Order" (driver needs it)

⚠️ OPTIONAL:
- Disable "Processing Order → Invoice" (invoice too early, order may change)
```

**Email Configuration:**
- WooCommerce emails must be enabled in **WooCommerce > Settings > Emails**
- Test emails with **WP Mail SMTP** plugin for reliability
- Check spam folders if emails not received

### Section 3: PDF Options

**Purpose:** Configure PDF generation and caching behavior

**Settings:**

| Setting | Description | Default | Recommended |
|---------|-------------|---------|-------------|
| Enable PDF Caching | Cache generated PDFs for faster downloads | ✅ Yes | ✅ Yes |
| Cache Cleanup (Days) | Delete PDFs older than X days (0 = never) | 30 | 30 |
| PDF Paper Size | A4 (210x297mm) or Letter (8.5x11in) | A4 | A4 (Singapore) |

**Cache Benefits:**
- ⚡ **Performance:** Cached PDFs load 10x faster (~100ms vs 1000ms)
- 💾 **Server Load:** Reduces CPU usage by 80%
- 📊 **Statistics:** Track cache size and PDF count

**Cache Considerations:**
- Each PDF averages 50-100KB
- 1000 cached PDFs = ~50-100MB disk space
- Auto-cleanup runs daily at midnight (WP-Cron)

**Manual Cache Management:**
```bash
# View cache statistics
du -sh /wp-content/pdf-cache/

# Count PDFs
ls /wp-content/pdf-cache/*.pdf | wc -l

# Clear all cache (if needed)
rm /wp-content/pdf-cache/*.pdf
```

### Section 4: Invoice Numbering

**Purpose:** Configure sequential invoice number format

**Settings:**

| Setting | Description | Example | Default |
|---------|-------------|---------|---------|
| Invoice Prefix | Text before number | AHF-, INV-, FRUIT- | AHF- |
| Starting Number | First invoice number | 1, 1000, 2026001 | 1 |
| Number Padding | Minimum digits (leading zeros) | 5 → 00001, 4 → 0001 | 5 |

**Examples:**

```
Prefix: "AHF-"  Starting: 1     Padding: 5 → AHF-00001, AHF-00002...
Prefix: "INV-"  Starting: 1000  Padding: 4 → INV-1000, INV-1001...
Prefix: "2026-" Starting: 1     Padding: 6 → 2026-000001, 2026-000002...
```

**Important Notes:**
- ⚠️ **Never decrease Starting Number** (causes duplicate invoice numbers)
- ⚠️ **Changing prefix mid-year** requires updating starting number
- ✅ **Best practice:** Set once during setup, don't change

**Resetting Invoice Numbers:**
```
New Year Reset:
- January 1: Change prefix from "2025-" to "2026-"
- Set starting number back to 1
- Result: 2026-00001, 2026-00002...
```

---

## Email Automation

### Email Workflow Overview

```
Order Created
    ↓
    [New Order Email]
    To: Admin
    Attachment: Packing Slip ← Warehouse can print immediately
    ↓
Order Status: Processing
    ↓
    [Processing Order Email] (optional)
    To: Customer
    Attachment: Invoice (if enabled)
    ↓
Order Status: Out for Delivery
    ↓
    [Out for Delivery Email]
    To: Customer
    Attachment: Delivery Order ← Driver can use this
    ↓
Order Status: Completed
    ↓
    [Completed Order Email]
    To: Customer
    Attachment: Invoice ← Final invoice for accounting
```

### Customizing Email Templates

**Email templates location:**
```
/wp-content/plugins/ah-ho-invoicing/templates/emails/
├── customer-out-for-delivery-order.php (HTML)
└── plain/
    └── customer-out-for-delivery-order.php (Plain Text)
```

**To customize:**
1. Copy template to theme:
```bash
/wp-content/themes/[your-theme]/woocommerce/emails/customer-out-for-delivery-order.php
```

2. Edit the copied file (changes won't be lost on plugin update)

**Available Variables:**
```php
$order           // WC_Order object
$email_heading   // Email heading text
$sent_to_admin   // Boolean
$plain_text      // Boolean
$email           // Email object
```

### Testing Email Automation

**Step 1: Enable Test Mode**
1. Install **WP Mail SMTP** plugin
2. Configure with test SMTP settings
3. Enable email logging

**Step 2: Create Test Order**
1. Go to **WooCommerce > Orders > Add Order**
2. Add customer, products, delivery date
3. Save order

**Step 3: Verify Emails Sent**

| Status Change | Expected Email | Recipient | Attachment |
|--------------|----------------|-----------|------------|
| Created | New Order | Admin | Packing Slip |
| Out for Delivery | Out for Delivery | Customer | Delivery Order |
| Completed | Order Completed | Customer | Invoice |

**Troubleshooting:**
- Check **WooCommerce > Settings > Emails** - all emails enabled?
- Check spam folder
- Test with **WP Mail SMTP** test email feature
- Check WordPress debug log for errors

---

## Workflow Guides

### Workflow 1: Daily Order Processing (Admin)

**Morning Routine (9:00 AM):**

1. **Check New Orders**
   - Go to **WooCommerce > Orders**
   - Filter: Status = "Processing"
   - Review orders for tomorrow's delivery

2. **Generate Consolidated Packing Slip**
   - Go to **PDF Documents** admin page
   - Set **Delivery Date:** Tomorrow (e.g., 2026-01-25)
   - Select **Order Status:** Processing
   - Choose **Sort By:** Delivery Date → Postal Code
   - Click **Generate Consolidated Packing Slip**
   - Download PDF

3. **Print and Send to Warehouse**
   - Print consolidated packing slip
   - Give to warehouse manager
   - Warehouse team prepares all orders in route-optimized sequence

**Afternoon Routine (2:00 PM):**

4. **Mark Orders Ready for Delivery**
   - After warehouse confirms packing complete
   - Bulk select all orders for tomorrow
   - Change status to **"Out for Delivery"**
   - Customers automatically receive delivery order email

5. **Review Driver Assignments**
   - Check driver schedules
   - Verify all delivery orders sent
   - Confirm phone numbers are correct

**Evening Routine (6:00 PM):**

6. **Mark Completed Deliveries**
   - After drivers confirm deliveries
   - Change order status to **"Completed"**
   - Customers automatically receive invoice email

### Workflow 2: Warehouse Packing (Warehouse Staff)

**Receiving Orders:**

1. **Morning Email Check**
   - Check admin email inbox
   - Look for "New Order" emails with packing slip attachment
   - Download and print all packing slips

2. **Or Print Consolidated Packing Slip**
   - Receive printed consolidated packing slip from admin
   - Contains all orders sorted by delivery route

**Packing Process:**

3. **Pick Items**
   - Follow packing slip order (sorted by postal code)
   - Check ☐ checkbox as each item is packed
   - Verify quantities match

4. **Check Customer Notes**
   - Look for **BOLD RED** allergy warnings
   - Follow special handling instructions
   - Include gift cards if requested

5. **Weigh Packages**
   - Verify weight matches packing slip estimate
   - Adjust if significant discrepancy

6. **Label Packages**
   - Attach delivery label with postal code (large font)
   - Group packages by delivery route
   - Place in driver's assigned area

**Quality Control:**

7. **Double-Check**
   - All checkboxes ticked?
   - Correct quantities?
   - Allergy notes followed?
   - Special requests completed?

8. **Notify Admin**
   - Inform admin when packing complete
   - Report any issues (missing items, substitutions)

### Workflow 3: Delivery (Driver)

**Pre-Departure:**

1. **Check Email**
   - Open "Out for Delivery" emails from customers
   - Download delivery order PDFs to phone/tablet
   - Or receive printed delivery orders from admin

2. **Load Vehicle**
   - Collect packages from warehouse
   - Load in REVERSE delivery order (last delivery at bottom)
   - Double-check all packages present

3. **Review Route**
   - Check delivery orders for addresses
   - Plan route by postal code (sorted on delivery order)
   - Note any special delivery windows

**During Delivery:**

4. **Before Each Delivery**
   - Call customer 15 minutes before arrival (if requested)
   - Review delivery order for special instructions
   - Prepare COD payment collection (if applicable)

5. **At Customer Location**
   - Verify address matches delivery order
   - Hand items to customer
   - Check delivery instructions (red border box)

6. **Payment Collection (if COD)**
   - Amount to collect shown on delivery order
   - Accept cash/card/PayNow
   - Write amount collected on delivery order
   - Give customer receipt

7. **Get Signature**
   - Customer signs delivery order
   - Driver signs delivery order
   - Write delivery time

8. **Take Photo (Optional)**
   - Photo of signed delivery order
   - Photo of delivered items
   - Upload to order notes via mobile

**Post-Delivery:**

9. **Return to Base**
   - Hand completed delivery orders to admin
   - Report any failed deliveries
   - Submit cash/card payments

10. **Admin Updates Orders**
    - Change order status to "Completed"
    - Customers receive invoice email automatically

### Workflow 4: Generating Individual PDFs (Order Edit Page)

**Use Case:** Generate PDFs for a specific order

1. **Navigate to Order**
   - Go to **WooCommerce > Orders**
   - Click order to edit

2. **Locate PDF Documents Metabox**
   - Right sidebar (below "Order actions")
   - Shows 3 buttons:
     - 📄 Generate/Download Invoice
     - 📦 Download Packing Slip
     - 🚚 Download Delivery Order

3. **Generate Invoice**
   - Click **"Generate/Download Invoice"**
   - PDF downloads automatically
   - Invoice number generated (first time only)
   - Subsequent clicks download cached version

4. **Generate Packing Slip**
   - Click **"Download Packing Slip"**
   - Warehouse version (no prices)
   - Shows checkboxes and customer notes

5. **Generate Delivery Order**
   - Click **"Download Delivery Order"**
   - Driver version (extra large text)
   - Shows COD section and signatures

**Troubleshooting:**
- Button not clickable? Check order has products
- PDF blank? Check Dompdf is installed (`/vendor/autoload.php` exists)
- Wrong company details? Update settings in WooCommerce > Settings > PDF Invoicing

### Workflow 5: Bulk PDF Generation (Admin Page)

**Use Case:** Generate consolidated packing slip for multiple orders

1. **Navigate to PDF Documents Page**
   - Go to **PDF Documents** (admin sidebar menu)
   - Or click **WooCommerce > PDF Documents**

2. **Set Filters**

**Delivery Date:**
- Select date to generate packing slip for
- Default: Tomorrow
- Example: 2026-01-25

**Order Status:**
- Hold Ctrl/Cmd to select multiple statuses
- Recommended: "Processing" only
- Optional: Add "On Hold" if needed

**Sort By:**
- **Delivery Date → Postal Code** (Recommended)
  - Groups orders by day, then by delivery route
  - Best for warehouse efficiency
- **Postal Code → Delivery Date**
  - Groups by route, then by day
  - Use if delivering to same area over multiple days
- **Order Number**
  - Sequential by order ID
  - Use for accounting/audit purposes

3. **Generate Consolidated PDF**
   - Click **"Generate Consolidated Packing Slip"**
   - Wait for success message
   - Shows order count (e.g., "12 orders included")

4. **Download PDF**
   - Click **"Download PDF"** button
   - PDF contains all matching orders
   - File name includes date: `packing-slip-consolidated-2026-01-25-[hash].pdf`

5. **Review PDF**
   - Check all expected orders are included
   - Verify sorting is correct
   - Print for warehouse team

**Example Use Cases:**

**Monday Morning - Prepare Tuesday Deliveries:**
```
Delivery Date: 2026-01-09 (Tuesday)
Order Status: Processing
Sort By: Delivery Date → Postal Code
Result: All Tuesday orders sorted by route
```

**Month-End - Export All Completed Orders:**
```
Delivery Date: 2026-01-31
Order Status: Completed
Sort By: Order Number
Result: All January orders in sequential order
```

---

## Troubleshooting

### Issue 1: PDFs Not Generating

**Symptoms:**
- Clicking download button shows error
- Blank page or white screen
- 500 Internal Server Error

**Causes & Solutions:**

**A. Dompdf Library Missing**
```bash
# Check if vendor directory exists
ls /wp-content/plugins/ah-ho-invoicing/vendor/

# Solution: Reinstall plugin or run Composer
cd /wp-content/plugins/ah-ho-invoicing/
composer install
```

**B. PHP Memory Limit Too Low**
```php
// Check current memory limit
echo ini_get('memory_limit');

// Solution: Increase in wp-config.php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

**C. File Permissions Issue**
```bash
# Check cache directory permissions
ls -ld /wp-content/pdf-cache/

# Solution: Set correct permissions
chmod 755 /wp-content/pdf-cache/
```

**D. Plugin Conflict**
```
# Solution: Deactivate other PDF plugins
- WooCommerce PDF Invoices & Packing Slips
- Print Invoice & Delivery Note
- Any other PDF plugin

# Reactivate Ah Ho Invoicing plugin
```

### Issue 2: Invoice Number Duplicates

**Symptoms:**
- Multiple orders have same invoice number
- Invoice number skips (e.g., AHF-00001, AHF-00003, missing AHF-00002)

**Causes & Solutions:**

**A. Database Lock Failure**
```sql
-- Check MySQL version (needs LOCK TABLES support)
SELECT VERSION();

-- Solution: Enable database locking in plugin code (already implemented)
```

**B. Manual Number Reset**
```
# If you manually changed starting number to a lower value
# Solution: Only INCREASE starting number, never decrease

WooCommerce > Settings > PDF Invoicing > Invoice Numbering
Starting Number: [Current highest + 1]
```

**C. Order Deleted After Invoice Generated**
```
# Invoice number already assigned, but order deleted
# Solution: Numbers will have gaps - this is normal and acceptable
# DO NOT try to fill gaps (causes duplicates)
```

### Issue 3: Email PDFs Not Attaching

**Symptoms:**
- Emails received but no PDF attachment
- PDF generation works manually but not via email

**Solutions:**

**A. Email Automation Disabled**
```
1. Go to WooCommerce > Settings > PDF Invoicing
2. Check "Email Automation" section
3. Ensure relevant checkboxes are ticked:
   ☑ Attach Invoice to "Order Completed"
   ☑ Attach Packing Slip to "New Order"
   ☑ Attach Delivery Order to "Out for Delivery"
4. Click Save Changes
```

**B. WooCommerce Emails Disabled**
```
1. Go to WooCommerce > Settings > Emails
2. Click each email template:
   - Completed Order
   - New Order
   - Out for Delivery Order
3. Ensure "Enable this email notification" is checked
4. Save changes
```

**C. Email Attachment Size Limit**
```
# Server may reject large attachments (>5MB)
# Check PDF file size:
ls -lh /wp-content/pdf-cache/invoice-*.pdf

# Solution: Reduce PDF size
- Remove logo if very large (>500KB)
- Use web-optimized images
- Contact hosting provider to increase attachment limit
```

**D. PDF Generation Timing Issue**
```
# PDF might not generate in time for email
# Solution: Check error logs

# Enable WordPress debug logging:
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

# Check logs:
tail -f /wp-content/debug.log
```

### Issue 4: Consolidated Packing Slip Empty

**Symptoms:**
- "Generate Consolidated Packing Slip" shows success
- Downloaded PDF is blank or shows "No orders found"

**Solutions:**

**A. No Orders Match Filters**
```
# Check if orders exist with selected criteria:
WooCommerce > Orders
Filter: Status = Processing, Date = 2026-01-25

# If no orders shown, adjust filters:
- Try different delivery date
- Include more order statuses (On Hold, etc.)
- Check if "_delivery_date" meta field is set
```

**B. Delivery Date Not Set**
```
# Orders need "_delivery_date" custom field
# Solution: Add delivery date to orders

1. Edit order
2. Look for "Delivery Date" field (should be ACF or custom field)
3. Set date to match packing slip filter
4. Save order
```

**C. Meta Key Name Mismatch**
```
# Plugin looks for "_delivery_date" meta key
# Your setup might use different key name

# Solution: Check actual meta key
WooCommerce > Orders > Edit Order
Scroll down to "Custom Fields" (enable via Screen Options)
Look for delivery date field name

# If different, contact developer to update plugin code
```

### Issue 5: Customer Notes Not Highlighting in Red

**Symptoms:**
- Customer notes with "allergy" appear in normal black text
- No bold red formatting on packing slip

**Solutions:**

**A. Keywords Not Matching**
```
# Plugin highlights these keywords (case-insensitive):
- allergy, allergic, allergies
- dietary restriction, diet
- halal, kosher, vegetarian, vegan
- gluten, lactose, dairy, peanut
- medical condition

# Solution: Use exact keywords in order notes
Example: "Customer has peanut allergy" ✅
         "Avoid peanuts" ❌ (won't highlight)
```

**B. Note Added to Wrong Field**
```
# Allergy notes must be in "Customer Provided Note"
# NOT in "Order Notes" (admin notes)

# Solution: Add note correctly
1. Edit order
2. Scroll to "Order Notes" section
3. In "Customer Provided Note" field, type allergy info
4. Save order
```

**C. CSS Not Loading**
```
# Bold red styling might not render
# Solution: Check Dompdf CSS support

# File: includes/templates/packing-slip-template.php
# Verify this style exists:
<style>
.customer-notes-highlight {
    color: #ff0000 !important;
    font-weight: bold !important;
    font-size: 14px !important;
    background-color: #fff3cd;
    padding: 5px;
    border: 2px solid #ff0000;
}
</style>
```

### Issue 6: Delivery Order Text Too Small

**Symptoms:**
- Delivery order fonts same size as invoice
- Phone number not 32px as expected
- Address not 28px as expected

**Solutions:**

**A. Wrong Template Used**
```
# Ensure correct template file is loaded
File: includes/templates/delivery-order-template.php

# Check font sizes in CSS:
.delivery-address { font-size: 28px; }
.delivery-phone { font-size: 32px; }
.delivery-postal { font-size: 36px; }
.customer-name { font-size: 24px; }
```

**B. PDF Rendering Engine Limitation**
```
# Dompdf may downscale very large fonts
# Solution: Use relative sizing

# Instead of fixed pixels, use percentage:
font-size: 250%; /* Relative to base 12px = 30px */
```

**C. Print Preview vs Actual Print**
```
# PDF viewer (browser) may show different size than printed version
# Solution: Always test by PRINTING to verify actual size

# Print settings:
- Scale: 100% (not "Fit to Page")
- Orientation: Portrait
- Paper: A4
```

### Issue 7: Statistics Not Updating

**Symptoms:**
- "Quick Statistics" dashboard shows old numbers
- Invoice count doesn't increase after generating invoices
- Cache size stays at 0 MB

**Solutions:**

**A. Database Cache Issue**
```
# WordPress transients may be stale
# Solution: Clear object cache

# Install WP-CLI and run:
wp cache flush

# Or use plugin:
Install "Redis Object Cache" or "W3 Total Cache"
Click "Purge Cache"
```

**B. Statistics Calculation Error**
```
# Manually recalculate statistics:

# Admin page file: includes/class-admin-page.php
# Verify SQL query:
$invoice_count = $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->postmeta}
    WHERE meta_key = '_ah_ho_invoice_number'
");

# Check if query returns correct count
```

**C. Cache Directory Not Readable**
```bash
# Check permissions
ls -ld /wp-content/pdf-cache/

# Solution: Fix permissions
chmod 755 /wp-content/pdf-cache/
chown www-data:www-data /wp-content/pdf-cache/ # Linux
chown _www:_www /wp-content/pdf-cache/         # macOS
```

### Debug Checklist

When troubleshooting, enable debug mode:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Check logs
tail -f /wp-content/debug.log

// Look for errors related to:
- "Ah Ho Invoicing"
- "Dompdf"
- "PDF generation"
```

**Common Error Messages:**

| Error | Cause | Solution |
|-------|-------|----------|
| `Call to undefined function Dompdf\...` | Dompdf not loaded | Run `composer install` |
| `Maximum execution time exceeded` | PDF too complex | Increase `max_execution_time` in php.ini |
| `Allowed memory size exhausted` | PHP memory limit | Increase to 256MB in wp-config.php |
| `Permission denied` writing to cache | File permissions | `chmod 755 pdf-cache/` |
| `No such file or directory` | Plugin path wrong | Check `AH_HO_INVOICING_PLUGIN_DIR` |

---

## Technical Architecture

### System Components

```
┌─────────────────────────────────────────────────┐
│           WordPress/WooCommerce Core            │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│         Ah Ho Invoicing Plugin (v1.3.0)         │
├─────────────────────────────────────────────────┤
│ Core Classes:                                   │
│ • AH_HO_PDF_Generator (base)                    │
│ • AH_HO_Invoice (sequential numbering)          │
│ • AH_HO_Packing_Slip (consolidated)             │
│ • AH_HO_Delivery_Order (large text)             │
│ • AH_HO_Cache_Manager (caching)                 │
│ • AH_HO_Metabox (order edit UI)                 │
│ • AH_HO_Custom_Order_Status (out for delivery)  │
│ • AH_HO_Email_Attachments (automation)          │
│ • AH_HO_Settings (configuration)                │
│ • AH_HO_Admin_Page (bulk generation)            │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│           Dompdf Library (2.0.8)                │
│ • HTML → PDF conversion                         │
│ • CSS styling support                           │
│ • Image embedding                               │
│ • Font rendering                                │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│              PDF Cache Storage                  │
│ Location: /wp-content/pdf-cache/                │
│ Format: [type]-[order-id]-[hash].pdf            │
│ Cleanup: Daily WP-Cron (>30 days)               │
└─────────────────────────────────────────────────┘
```

### Database Schema

**No Custom Tables Required** - Uses WordPress/WooCommerce existing tables

**Order Meta Keys:**

| Meta Key | Type | Purpose | Example |
|----------|------|---------|---------|
| `_ah_ho_invoice_number` | string | Sequential invoice number | `AHF-00001` |
| `_ah_ho_invoice_generated_date` | string | When invoice was generated | `2026-01-24 14:30:00` |
| `_delivery_date` | string | Delivery date (for filtering) | `2026-01-25` |

**Plugin Options (wp_options table):**

| Option Key | Type | Default | Purpose |
|-----------|------|---------|---------|
| `ah_ho_invoice_counter` | int | 1 | Next invoice number |
| `ah_ho_company_name` | string | - | Company branding |
| `ah_ho_company_address` | string | - | Company branding |
| `ah_ho_invoice_prefix` | string | `AHF-` | Invoice number prefix |
| `ah_ho_invoice_padding` | int | 5 | Number padding (00001) |
| `ah_ho_attach_invoice_to_completed` | string | `yes` | Email automation |
| `ah_ho_attach_packing_to_new_order` | string | `yes` | Email automation |
| `ah_ho_attach_delivery_to_out_for_delivery` | string | `yes` | Email automation |
| `ah_ho_enable_pdf_caching` | string | `yes` | Caching enabled |
| `ah_ho_cache_cleanup_days` | int | 30 | Cache retention |
| `ah_ho_pdf_paper_size` | string | `a4` | PDF format |

### File Structure

```
/wp-content/plugins/ah-ho-invoicing/
│
├── ah-ho-invoicing.php          # Main plugin file
├── composer.json                 # Composer dependencies
├── README.md                     # Documentation
│
├── includes/                     # PHP classes
│   ├── class-pdf-generator.php          # Base PDF class
│   ├── class-invoice.php                # Invoice generation
│   ├── class-packing-slip.php           # Packing slip generation
│   ├── class-delivery-order.php         # Delivery order generation
│   ├── class-cache-manager.php          # PDF caching
│   ├── class-metabox.php                # Order edit UI
│   ├── class-custom-order-status.php    # "Out for Delivery" status
│   ├── class-email-attachments.php      # Email automation
│   ├── class-settings.php               # Settings page
│   ├── class-admin-page.php             # Bulk generation page
│   │
│   └── emails/                   # Custom email class
│       └── class-wc-out-for-delivery-email.php
│
├── templates/                    # PDF HTML templates
│   ├── invoice-template.php
│   ├── packing-slip-template.php
│   ├── delivery-order-template.php
│   │
│   └── emails/                   # Email templates
│       ├── customer-out-for-delivery-order.php (HTML)
│       └── plain/
│           └── customer-out-for-delivery-order.php (plain text)
│
└── vendor/                       # Composer dependencies (134M+ downloads)
    ├── dompdf/                   # Dompdf library
    │   ├── dompdf/
    │   ├── php-svg-lib/
    │   └── php-font-lib/
    └── autoload.php
```

### Hooks & Filters

**Actions:**

```php
// Plugin initialization
add_action('plugins_loaded', 'ah_ho_invoicing_init');

// Order lifecycle hooks
add_action('woocommerce_new_order', 'ah_ho_generate_packing_slip_on_new_order');
add_action('woocommerce_order_status_completed', 'ah_ho_generate_invoice_on_complete');
add_action('woocommerce_order_status_out-for-delivery', 'ah_ho_send_delivery_email');

// Email attachment hooks
add_filter('woocommerce_email_attachments', 'ah_ho_attach_pdfs_to_emails', 10, 4);

// Admin menu hooks
add_action('admin_menu', 'ah_ho_add_admin_menu');

// WP-Cron cleanup
add_action('ah_ho_invoicing_cleanup_old_pdfs', 'ah_ho_cleanup_old_pdfs');

// AJAX handlers
add_action('wp_ajax_ah_ho_download_pdf', 'ah_ho_ajax_download_pdf');
add_action('wp_ajax_ah_ho_generate_consolidated_packing', 'ah_ho_ajax_generate_consolidated');
```

**Filters:**

```php
// WooCommerce order statuses
add_filter('wc_order_statuses', 'ah_ho_add_custom_order_statuses');

// WooCommerce email classes
add_filter('woocommerce_email_classes', 'ah_ho_add_custom_email_class');

// Settings page
add_filter('woocommerce_settings_tabs_array', 'ah_ho_add_settings_tab');

// Plugin settings filters
add_filter('ah_ho_invoicing_settings', 'custom_modify_settings');
add_filter('ah_ho_invoice_template_html', 'custom_invoice_template');
add_filter('ah_ho_company_logo_url', 'custom_logo_url');
```

### Performance Considerations

**PDF Generation Time:**

| Document Type | Cold (First Gen) | Cached | Orders |
|--------------|------------------|--------|--------|
| Invoice | 800-1200ms | 50-100ms | Single |
| Packing Slip | 600-900ms | 50-100ms | Single |
| Delivery Order | 700-1000ms | 50-100ms | Single |
| Consolidated | 1500-3000ms | N/A | 10-50 |

**Optimization:**

```php
// Enable caching (recommended)
update_option('ah_ho_enable_pdf_caching', 'yes');

// Serve cached PDFs via CDN (advanced)
// Use plugin like "WP Rocket" or "W3 Total Cache"
// Cache /wp-content/pdf-cache/ directory

// Offload PDF generation to background job (advanced)
// Use "Action Scheduler" for async PDF generation
```

**Memory Usage:**

```
Small PDF (invoice):      ~5MB RAM
Medium PDF (packing):     ~8MB RAM
Large PDF (consolidated): ~15-30MB RAM (depends on order count)

Recommended PHP Memory Limit: 256MB
Maximum PHP Memory Limit: 512MB (for large consolidated PDFs)
```

### Security Considerations

**Nonce Protection:**

```php
// All AJAX requests protected
wp_verify_nonce($_REQUEST['_wpnonce'], 'ah_ho_download_pdf');

// Download links include nonce
$url = admin_url('admin-ajax.php?action=ah_ho_download_pdf&_wpnonce=' . wp_create_nonce('ah_ho_download_pdf'));
```

**File Access Control:**

```apache
# /wp-content/pdf-cache/.htaccess
Deny from all
```

- PDFs only accessible via authenticated admin-ajax.php requests
- Direct file access blocked by .htaccess
- Nonce expires after 24 hours

**Capability Checks:**

```php
// Only admins and shop managers can generate PDFs
if (!current_user_can('manage_woocommerce')) {
    wp_die('Unauthorized');
}

// Only assigned roles can access settings
if (!current_user_can('manage_options')) {
    return;
}
```

**SQL Injection Prevention:**

```php
// All database queries use prepared statements
$wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s", '_ah_ho_invoice_number');
```

**XSS Prevention:**

```php
// All output escaped
echo esc_html($order->get_billing_first_name());
echo esc_url($download_url);
echo wp_kses_post($customer_note);
```

---

## Appendix

### Appendix A: Glossary

**ACF (Advanced Custom Fields)** - WordPress plugin for adding custom fields to posts/orders

**COD (Cash on Delivery)** - Payment collected by driver upon delivery

**Consolidated Packing Slip** - Single PDF containing multiple orders

**Dompdf** - PHP library that converts HTML to PDF

**GST (Goods and Services Tax)** - Singapore sales tax (9%)

**HPOS (High-Performance Order Storage)** - WooCommerce custom order tables

**Invoice Number** - Sequential number assigned to invoices (e.g., AHF-00001)

**MD5 Hash** - Cryptographic hash used in PDF filenames for cache invalidation

**Meta Box** - Custom box in WordPress admin (e.g., PDF Documents box in order edit)

**Nonce** - WordPress security token (expires after 24 hours)

**Postal Code** - Singapore postal code (6 digits, e.g., 123456)

**SKU (Stock Keeping Unit)** - Unique product identifier

**UEN (Unique Entity Number)** - Singapore business registration number

**WP-Cron** - WordPress scheduled task system (used for cache cleanup)

### Appendix B: Keyboard Shortcuts

**WordPress Admin:**

```
g + o = Go to Orders
g + s = Go to Settings
g + p = Go to Plugins
```

**Order Edit Page:**

```
Cmd/Ctrl + S = Save order
Cmd/Ctrl + Click = Open link in new tab (PDF download)
```

**PDF Documents Admin Page:**

```
Tab = Navigate form fields
Enter = Submit form (generate PDF)
Cmd/Ctrl + Click = Open PDF in new tab
```

### Appendix C: Common File Paths

**Plugin Directory:**
```
/wp-content/plugins/ah-ho-invoicing/
```

**PDF Cache:**
```
/wp-content/pdf-cache/
```

**Debug Log:**
```
/wp-content/debug.log
```

**WordPress Config:**
```
/wp-config.php
```

**Theme Override:**
```
/wp-content/themes/[your-theme]/woocommerce/emails/
```

### Appendix D: Support Resources

**WordPress Codex:**
- https://codex.wordpress.org/

**WooCommerce Documentation:**
- https://woocommerce.com/documentation/

**Dompdf Documentation:**
- https://github.com/dompdf/dompdf

**PHP Official Documentation:**
- https://www.php.net/manual/

**Common Issues:**
- Search: "WooCommerce PDF generation"
- Search: "Dompdf WordPress"
- Search: "WooCommerce email attachments"

### Appendix E: Version History

**v1.3.0** (January 24, 2026)
- ✅ Added bulk PDF generation admin page
- ✅ Added consolidated packing slip generator
- ✅ Added quick statistics dashboard
- ✅ Improved sorting options (date → postal code)

**v1.2.0** (January 23, 2026)
- ✅ Added custom "Out for Delivery" order status
- ✅ Added email automation system
- ✅ Created custom delivery notification email
- ✅ Added settings page integration

**v1.1.0** (January 22, 2026)
- ✅ Added packing slip generation
- ✅ Added delivery order generation
- ✅ Added consolidated packing slip feature
- ✅ Added customer notes highlighting (allergy warnings)

**v1.0.0** (January 20, 2026)
- ✅ Initial release
- ✅ Sequential invoice numbering
- ✅ Invoice PDF generation
- ✅ PDF caching system

### Appendix F: License & Credits

**License:** GPL v2 or later
**Copyright:** © 2026 Ah Ho Fruit Pte Ltd

**Credits:**
- **Dompdf Library:** https://github.com/dompdf/dompdf (LGPL 2.1)
- **WooCommerce:** Automattic Inc.
- **WordPress:** WordPress Foundation

**Developer:** Ah Ho Fruit Development Team
**Support Email:** dev@ahhofruits.com

---

## Quick Reference Card

### Daily Workflow Checklist

**Morning (9:00 AM):**
- [ ] Check new orders (WooCommerce > Orders)
- [ ] Generate consolidated packing slip (PDF Documents)
- [ ] Print and send to warehouse
- [ ] Verify all delivery dates set

**Afternoon (2:00 PM):**
- [ ] Mark orders "Out for Delivery"
- [ ] Verify delivery order emails sent
- [ ] Assign drivers if needed

**Evening (6:00 PM):**
- [ ] Mark completed deliveries
- [ ] Verify invoice emails sent
- [ ] Check cache statistics

### Common Paths

```
Settings:     WooCommerce > Settings > PDF Invoicing
Bulk PDFs:    PDF Documents (admin menu)
Order Edit:   WooCommerce > Orders > [Order] > PDF Documents metabox
Statistics:   PDF Documents > Quick Statistics
```

### Support Checklist

**Before contacting support:**
1. Check WordPress/WooCommerce versions (6.0+/8.0+ required)
2. Enable WP_DEBUG and check debug.log
3. Verify Dompdf installed (vendor/ directory exists)
4. Test with default WordPress theme
5. Deactivate other PDF plugins
6. Clear cache and try again

**What to include in support ticket:**
- WordPress version
- WooCommerce version
- PHP version
- Error message (from debug.log)
- Steps to reproduce
- Screenshot of issue

---

**End of PDF Invoicing System Guide**
**Version:** 1.3.0
**Last Updated:** January 24, 2026
