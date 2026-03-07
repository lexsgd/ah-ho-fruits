# Ah Ho Fruit - Website Knowledge Base

This document covers everything about the Ah Ho Fruit website (ahhofruit.com) — how features work, how to use the admin panel, and the business logic behind each system.

---

## 1. System Overview

**Website:** https://ahhofruit.com
**Platform:** WordPress + WooCommerce
**Theme:** Avada
**Hosting:** Vodien (Singapore)
**Payment:** Stripe (PayNow + Credit/Debit Card)

### Custom Plugins (Built for Ah Ho Fruit)

| Plugin | Purpose |
|--------|---------|
| Ah Ho Fruit Custom (v1.6.3) | Core business logic — order statuses, salesperson system, wholesale pricing, fulfillment, shipping rules |
| Ah Ho Invoicing (v1.4.1) | PDF invoices, packing slips, delivery orders, email attachments |
| Payment Gateway Fees (v1.2.0) | Credit card processing fee (3.5%) |
| Ah Ho Product Add-ons (v1.2.0) | Gift messages & product remarks at checkout |
| Ah Ho Typography Fix (v1.0.0) | CSS fixes for Avada theme readability |
| HeyMag Chat (v1.1.0) | AI-powered customer chat widget |

---

## 2. Product Management

### Product Categories

Products are organized into categories:
- **Omakase Fruit Boxes** — curated surprise fruit boxes (Small $60, Medium $80, Large $100)
- **Fruits** (parent) with subcategories: Apples, Berries, Citrus, Grapes, Kiwi, Melons, Pears, Stone Fruits, Tropical
- **Others** — for non-fruit products

### How to Create a New Product Category

1. Go to **Products > Categories** in WordPress admin
2. Enter the category **Name** (e.g., "Others")
3. Optionally set a **Slug** (URL-friendly name, e.g., "others")
4. Choose a **Parent category** if it's a subcategory (e.g., select "Fruits" for "Apples")
5. Click **Add new category**

### How to Add a Product to a Category

1. Go to **Products > All Products**
2. Click on the product to edit
3. In the right sidebar, check the box next to the desired category
4. Click **Update**

### Shop Page Ordering

Products on the shop page are automatically sorted:
- **Omakase Fruit Boxes** appear first (pinned to top)
- **B2C products** (retail) appear before B2B products
- Within each group, products are sorted by name

### Wholesale Pricing (B2B)

Each product can have a **wholesale price** in addition to its regular retail price.

**To set wholesale price:**
1. Edit the product
2. In the **Product data** section, find the **Wholesale Price** field
3. Enter the B2B price (excluding GST)
4. Click **Update**

**How wholesale pricing works:**
- Wholesale prices are used for salesperson-created orders
- Quick edit and bulk edit support wholesale price changes
- Products without wholesale prices can be handled via fallback settings (use retail, apply default discount, or block)

---

## 3. Order Management

### Order Statuses

Ah Ho Fruit uses custom order statuses for the delivery workflow:

| Status | Meaning | What Happens |
|--------|---------|--------------|
| **Processing** | New online order paid | Default WooCommerce status |
| **Processing - B2B** | Salesperson-created order | Created by salesperson for wholesale customers |
| **Ready for Delivery** | Packed, awaiting driver | Email sent to customer |
| **Out for Delivery** | With delivery driver | Email + Delivery Order PDF sent to customer; stock is reduced |
| **Delivered - Paid** | Delivered, payment received | Email sent; order complete |
| **Delivered - Awaiting Payment** | Delivered but not yet paid | For B2B COD/credit term orders |
| **Payment Received** | Payment collected after delivery | Final status for B2B orders |
| **Completed** | Order fully complete | Invoice PDF sent to customer |

### How to Change Order Status

1. Go to **WooCommerce > Orders**
2. Click on the order
3. In the **Status** dropdown, select the new status
4. Click **Update**

**Bulk status change:** Select multiple orders with checkboxes, use the "Bulk actions" dropdown.

### Delivery Date

**On checkout:**
- Customers can optionally select a preferred delivery date
- **Sundays and Singapore Public Holidays** are greyed out (not selectable)
- **Saturdays are available** for delivery
- Earliest delivery: 3 working days from today (or next day for Express)
- Date range: up to 30 days ahead

**Singapore Public Holidays blocked (2026):**
New Year's Day, Chinese New Year (2 days), Hari Raya Puasa, Good Friday, Labour Day, Vesak Day, Hari Raya Haji, National Day, Deepavali, Christmas Day

**In admin (editing delivery date on an order):**
1. Open the order
2. Find the **Delivery Schedule** section (below shipping address)
3. Click **Edit**
4. Select a new date
5. Click **Save** (saves immediately via AJAX — no need to click the main Update button)
6. The date will update instantly

### Order Fulfillment (Partial Deliveries & Returns)

Orders have a tabbed section with:
- **Deliveries tab** — Record partial shipments (e.g., deliver 5 out of 10 items)
- **Returns tab** — Process item returns and refunds
- **Edit Items tab** — Adjust quantities, add/remove products

All changes are tracked with an **audit trail** (who, when, what changed, reason).

**Rules:**
- Cannot reduce quantity below what's already been delivered
- Cannot remove items that have deliveries or returns
- A reason is required for all edits

### Order Editing

To edit items on an existing order:
1. Open the order
2. Click the **Edit Items** tab
3. Change quantities, add new products, or remove items
4. Enter a **reason** for the change
5. Click **Save Changes**

All edits are logged with the user, timestamp, and reason.

---

## 4. Invoicing & PDF Documents

### PDF Types

| Document | When Generated | Sent To | Attached To Email |
|----------|---------------|---------|-------------------|
| **Invoice** | Order completed | Customer | "Order Completed" email |
| **Packing Slip** | New order placed | Admin | "New Order" admin email |
| **Delivery Order** | Out for Delivery | Customer | "Out for Delivery" email |

### Invoice Contents

The invoice PDF includes:
- Company header (logo, name, address, UEN, GST reg)
- Bill To / Deliver To addresses
- Invoice number, date, delivery date
- Item table (qty, description, unit price, amount)
- Special highlights: gift items, special requests
- Subtotal, shipping, discounts, fees, GST (9%), total
- Payment status (PAID / PAYMENT REQUIRED)
- Bank transfer details and return policy

### How to Configure Invoice Settings

1. Go to the plugin settings page in WordPress admin
2. Enter: Company name, address, phone, email, UEN, GST registration
3. Upload company logo
4. Configure bank account details
5. Toggle which PDFs attach to which emails

### PDF Caching

- PDFs are cached in `/wp-content/pdf-cache/` for performance
- Cache auto-clears when an order is updated
- Daily cleanup removes PDFs older than 30 days
- Admins can manually clear all cached PDFs from the settings page

---

## 5. Payment & Fees

### Payment Methods

Two active payment methods on checkout:

| Method | Fee | Default |
|--------|-----|---------|
| **PayNow** | No fee | Yes (selected by default) |
| **Credit / Debit Card** | 3.5% processing fee | No |

### How the Credit Card Fee Works

- When a customer selects **Credit / Debit Card** at checkout, a **3.5% fee** is automatically added
- The fee is calculated on: **subtotal + shipping**
- The fee label shows as: "Credit Card Fee(3.5%)"
- When switching back to PayNow, the fee disappears
- The fee is always applied, even in edge cases (safety net mechanism ensures it)

**Example:** Order $100 + $10 shipping = $110 base. Fee = $110 x 3.5% = $3.85. Total = $113.85.

### How to Configure Payment Fees

1. Go to **WooCommerce > Gateway Fees**
2. Find the payment method (e.g., "Credit / Debit Card")
3. Check **Enable Fee**
4. Set the fee label, type (percentage/fixed/both), and amount
5. Click **Save Changes**

### Shipping Rules

- **Free shipping** on orders $60 and above
- Below $60: standard shipping rates apply
- **Express Same Day Delivery**: $20.00 (always available)
- **Self Pickup at Warehouse (S128416)**: Free (always available)

---

## 6. Team Roles & Permissions

### User Roles

| Role | Can Do | Cannot Do |
|------|--------|-----------|
| **Admin** | Everything | — |
| **Salesperson** | View/create/edit assigned orders, view customers, create B2B orders | See other salespeople's orders, edit products, change prices |
| **Storeman** | Update product stock quantities, view products | Change prices, edit descriptions, publish/unpublish products, access orders |

### Salesperson Features

- **Order Attribution:** Orders are automatically assigned to the salesperson who created them
- **Commission Tracking:** Dual commission model — percentage of order total + per-carton rate
- **Dashboard:** Personal view showing monthly earnings, pending/approved/paid commissions
- **Security:** 4-layer access control ensures salespeople only see their own orders (query filter, list table, HPOS SQL, direct access prevention)

### Storeman Features

- **Quick Stock Update:** Bulk inventory page at **Products > Quick Stock Update**
  - Filter by status (In Stock / Low Stock / Out of Stock) or category
  - Search by product name
  - Use +/- buttons to adjust quantities
  - Click **Save All Changes** to bulk update
- **Read-only pricing:** Storeman cannot see or change product prices

### How to Create a Salesperson Account

1. Go to **Users > Add New**
2. Fill in username, email, password
3. Set **Role** to "Salesperson"
4. Click **Add New User**

### Salesperson Commission Settings

Configure at **WooCommerce > Salesperson Settings:**
- Default commission rate (percentage)
- Per-carton commission rate
- Approval workflow: Auto-approve or manual approval
- Email notifications for commission events
- Payment terms for B2B orders

---

## 7. WhatsApp Catalog Generator

### What It Does

Generates a WhatsApp-formatted product price list for B2B customers. Access at **Catalog** in the WordPress admin menu.

### Conditions for Products to Appear in the Catalog

A product appears in the WhatsApp catalog if ALL of these are true:
1. **Product status = Published**
2. **Stock status = In Stock**
3. **Wholesale price is set (> $0)**

### Categories Excluded

- **"Fruits"** (parent category) — excluded to prevent duplicating subcategory products
- **"Uncategorized"** — not relevant

### How to Use

1. Go to **Catalog** in WordPress admin
2. The catalog text is auto-generated with current prices
3. Click **Copy to Clipboard**
4. Paste into WhatsApp to send to B2B customers

### Format

Each product shows as: `Product Name @ $XX.XX` (wholesale price, excl. GST)

Products are grouped by category with emoji icons. Bold text (`*text*`) renders as bold in WhatsApp.

### B2B Stock List

Below the shareable catalog, there's an internal **B2B Stock List** showing stock quantities. This section is read-only and cannot be copied (for internal reference only).

---

## 8. Customer-Facing Checkout Features

### Product Add-ons

At checkout, customers can:
- Add a **gift message** to their order
- Add **special requests / remarks** for specific products

These appear highlighted on the invoice PDF.

### Order Notes

Customers can add a note to their order during checkout (optional).

### Address Verification

A warning shows at checkout: "Please verify your address is correct. We cannot redirect or redeliver once shipped."

---

## 9. Email Notifications

### Automated Emails

| Trigger | Email Sent To | Attachment |
|---------|--------------|------------|
| New order placed | Admin | Packing Slip PDF |
| Order completed | Customer | Invoice PDF |
| Ready for Delivery | Customer | — |
| Out for Delivery | Customer | Delivery Order PDF |
| Delivered - Paid | Customer | — |

### Customizing Emails

Go to **WooCommerce > Settings > Emails** to customize:
- Enable/disable each email
- Edit subject lines and headings
- Add additional content

---

## 10. Common How-To Guides

### How to Add a New Product

1. Go to **Products > Add new product**
2. Enter product name and description
3. Set the **Regular price**
4. Optionally set a **Wholesale price** (for B2B catalog)
5. Upload a product image
6. Select one or more **Product categories**
7. Set stock quantity in the **Inventory** tab
8. Click **Publish**

### How to Update Stock Quantities (Quick Method)

1. Go to **Products > Quick Stock Update**
2. Use the search bar or category filter to find products
3. Use the +/- buttons to adjust stock
4. Click **Save All Changes**

### How to Process an Order

1. Customer places order online (status: Processing)
2. Pack the order → Change status to **Ready for Delivery**
3. Hand to driver → Change status to **Out for Delivery** (delivery order PDF sent to customer)
4. Delivered → Change status to **Delivered - Paid** or **Completed**

### How to Create a B2B Order (Salesperson)

1. Go to **WooCommerce > Orders > Add Order**
2. Add customer details
3. Add products (wholesale prices auto-applied)
4. Set status to **Processing - B2B**
5. Click **Create**

### How to Generate and Send the WhatsApp Catalog

1. Go to **Catalog** in admin menu
2. Click **Copy to Clipboard**
3. Open WhatsApp
4. Paste into the chat with your B2B customer

### How to Set Up a New Payment Fee

1. Go to **WooCommerce > Gateway Fees**
2. Find the payment method
3. Check **Enable Fee**
4. Choose fee type: Percentage, Fixed, or Both
5. Enter the amount
6. Set a customer-facing label (e.g., "Processing Fee (3.5%)")
7. Click **Save Changes**

### How to View Commission Reports

**For Admin:**
1. Go to **WooCommerce > Commission Dashboard**
2. Filter by salesperson, status, or month
3. Export to CSV if needed

**For Salesperson:**
1. Go to **My Commission** in the admin menu
2. View personal monthly earnings

### How to Handle a Return/Refund

1. Open the order
2. Click the **Returns** tab
3. Select the items being returned and quantities
4. Enter the reason
5. Click **Process Return**
6. The invoice will automatically reflect the return amount

---

## 11. Technical Reference

### Warehouse Address
230A Pandan Loop, Coldroom #3, Singapore 128416

### Contact
- WhatsApp: +65 8013 8128
- Email: enquiry@ahhofruit.com

### Website Pages
- Home: https://ahhofruit.com
- Shop: https://ahhofruit.com/shop
- Omakase Boxes: https://ahhofruit.com/product-category/omakase-fruit-boxes/
- About: https://ahhofruit.com/about/
- Contact: https://ahhofruit.com/contact/

### Free Shipping Threshold
$60 SGD (orders at or above this get free standard delivery)

### GST Rate
9% (included in invoice calculations)

### Public Holidays (2026)
These dates are blocked from delivery date selection:
- 1 Jan — New Year's Day
- 29-30 Jan — Chinese New Year
- 31 Mar — Hari Raya Puasa
- 3 Apr — Good Friday
- 1 May — Labour Day
- 26 May — Vesak Day
- 17 Jul — Hari Raya Haji
- 9-10 Aug — National Day (+ observed)
- 8-9 Nov — Deepavali (+ observed)
- 25 Dec — Christmas Day

---

*Last updated: 7 March 2026*
