# Ah Ho Fruit - Complete System Documentation

> Last updated: 2026-02-08
> WordPress on Vodien | Domain: ahhofruit.com (currently fruits.heymag.app)

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Custom Plugins](#2-custom-plugins)
3. [Custom Child Theme](#3-custom-child-theme)
4. [Infrastructure & Deployment](#4-infrastructure--deployment)
5. [Database Reference](#5-database-reference)
6. [Changelog](#6-changelog)

---

## 1. System Overview

### Tech Stack
| Component | Technology |
|-----------|-----------|
| CMS | WordPress 6.x |
| E-Commerce | WooCommerce 8.x (HPOS enabled) |
| Theme | Avada (parent) + ah-ho-fruits (child) |
| Hosting | Vodien (sh00017.vodien.com) |
| PHP | 8.3 (ea-php83) |
| Database | MySQL (contactl_wp153, prefix: `wpgr_`) |
| Deployment | GitHub Actions → FTP |
| PDF Engine | Dompdf 2.0 (Composer) |
| Domain | fruits.heymag.app → ahhofruit.com |

### Custom Plugin Inventory

| Plugin | Version | Purpose |
|--------|---------|---------|
| **ah-ho-custom** | 1.5.0 | B2B roles, commissions, wholesale, delivery, catalog |
| **ah-ho-invoicing** | 1.4.1 | PDF invoices, packing slips, delivery orders |
| **ah-ho-product-addons** | 1.0.0 | Product notes & gift messages |
| **payment-gateway-fees** | 1.0.0 | Per-gateway processing fees |
| **ah-ho-typography-fix** | 1.0.0 | Avada theme readability fixes |
| **ah-ho-legal-pages-setup** | 1.0.0 | One-time legal pages creator (deactivate after use) |

### File Structure

```
ah-ho-fruits/
├── .github/workflows/deploy.yml     # GitHub Actions FTP deploy
├── .htaccess                         # PHP 8.3 handler + security
├── wp-config.php                     # DB config + site URLs
├── robots.txt                        # SEO rules
├── docs/                             # All documentation
├── wp-content/
│   ├── plugins/
│   │   ├── ah-ho-custom/             # Core B2B plugin
│   │   ├── ah-ho-invoicing/          # PDF documents
│   │   ├── ah-ho-product-addons/     # Product notes/gifts
│   │   ├── payment-gateway-fees/     # Processing fees
│   │   ├── ah-ho-typography-fix.php  # Theme CSS fixes
│   │   └── ah-ho-legal-pages-setup.php # Legal pages (one-time)
│   └── themes/
│       ├── Avada/                    # Parent theme (unmodified)
│       └── ah-ho-fruits/             # Custom child theme
└── vendor/                           # (not tracked)
```

---

## 2. Custom Plugins

---

### 2.1 Ah Ho Custom Plugin (v1.5.0)

**Path:** `/wp-content/plugins/ah-ho-custom/`
**Files:** 11 PHP files across `includes/`

This is the main business logic plugin. It contains 10 major features:

#### Feature A: Custom Order Statuses
**File:** `includes/custom-order-statuses.php`

6 custom order statuses for the full delivery workflow:

| Status | Color | Purpose |
|--------|-------|---------|
| Processing - B2B | Purple `#8b5cf6` | Salesperson-created order |
| Ready for Delivery | Blue `#2271b1` | Packed, awaiting driver |
| Out for Delivery | Orange `#f56e28` | With delivery driver |
| Delivered - Paid | Green `#2ea44f` | Complete (cash/COD) |
| Delivered - Awaiting Payment | Yellow `#dba617` | Credit terms, unpaid |
| Payment Received | Dark Green `#0e8c3e` | Credit terms, paid |

Key behaviors:
- Stock auto-reduces on "Out for Delivery" (with `_stock_reduced_out_delivery` flag to prevent double-reduction)
- "Complete" quick action button added for Processing-B2B orders
- All custom statuses counted in WooCommerce reports
- CSS badges + JavaScript tooltips in admin

#### Feature B: Custom Email Notifications
**Files:** `includes/custom-emails.php`, `includes/emails/class-ah-ho-*.php`

3 customer-facing email templates triggered by status changes:

| Email | Trigger | Subject |
|-------|---------|---------|
| Ready for Delivery | → `ready-delivery` | "Your order is ready for delivery" |
| Out for Delivery | → `out-delivery` | "Your order is out for delivery" |
| Delivered - Paid | → `delivered-paid` | "Your order has been delivered" |

Templates in `templates/emails/`.

#### Feature C: Salesperson & Storeman Roles
**File:** `includes/salesperson-roles.php`

Two custom WordPress roles with restricted admin access:

**Salesperson** (`ah_ho_salesperson`):
- Create/edit orders (own only), read products, create/edit customers
- Cannot: edit other staff orders, edit products, access media, delete orders

**Storeman** (`ah_ho_storeman`):
- Same as salesperson + edit product inventory (stock only, not prices)
- Gets Quick Stock Update page

**Customer management security:**
- Salespersons can only see/assign the "Customer" role
- Cannot edit admin, shop_manager, or other salesperson accounts
- New users created by salesperson are forced to "Customer" role
- User list filtered to show only customers

**Payment Terms** (configurable via Settings):
- Stored on customer profile as `_payment_terms` user meta
- Displayed on order details, invoices, delivery orders
- Configurable from WooCommerce > Salesperson Settings

**Admin UI restrictions:**
- Menus: Only Orders, My Commission, Users (+ Products for storeman)
- Login redirect: Goes to Orders page
- Admin bar: Cleaned of unnecessary items

#### Feature D: Commission System
**Files:** `includes/salesperson-attribution.php`, `includes/salesperson-dashboard.php`

Dual-model commission tracking:
- **Percentage commission:** X% of order total
- **Per-carton commission:** $Y per line item quantity
- **Total = percentage + per-carton**

Commission workflow: Pending → Approved → Paid (or Cancelled/Refunded)

Admin features:
- Commission dashboard (WooCommerce > Commissions) with summary cards, filters, CSV export
- Personal dashboard for salespersons (My Commission)
- Meta box on order edit page for assignment + commission details
- Recalculate and cleanup tools

#### Feature E: Order Access Security (4-Layer)
**File:** `includes/salesperson-query-filters.php`

Prevents salespersons from seeing other staff members' orders:

| Layer | Method | Hook |
|-------|--------|------|
| 1 | WC order query filter | `woocommerce_order_query_args` |
| 2 | Admin list table + SQL filter | `woocommerce_shop_order_list_table_prepare_items_query_args` |
| 3 | Direct URL access block | `admin_init` |
| 4 | REST API protection | `woocommerce_rest_check_permissions` |

Also filters order counts, menu badges, and status view counts.

#### Feature F: Wholesale Pricing
**File:** `includes/wholesale-pricing.php`

Per-product wholesale prices for B2B orders:
- Product admin field: `_wholesale_price` on simple + variable products
- "Wholesale" column on product list showing price + discount %
- Auto-applied when salesperson adds items to orders
- Fallback behavior (configurable): use retail / apply default discount / block product
- Quick edit, bulk edit, and bulk actions (Set Wholesale Price modal)
- Order display: "B2B Wholesale Price" badge + savings summary

#### Feature G: Delivery Date Management
**File:** `includes/delivery-date-field.php`

Complete delivery date selection for checkout and admin:
- **Classic checkout:** Date field with 3 business day minimum, weekend disabled
- **Blocks checkout:** Flatpickr date picker with dynamic minimum based on shipping method
- **Admin order page:** Editable date + time slot (Morning/Afternoon)
- **Order list column:** Color-coded (TODAY=orange, TOMORROW=yellow, OVERDUE=red)
- **Quick filter:** Today, Tomorrow, This Week, Overdue, specific date
- **Email integration:** Date shown in order emails

#### Feature H: WhatsApp Catalog Generator
**File:** `includes/catalog-generator.php`

Admin page (WordPress Admin > Catalog) with two sections:
1. **WhatsApp Catalog** — Shareable text with products organized by category with emojis, wholesale prices, copy-to-clipboard
2. **B2B Stock List** — Internal only, shows hidden products with stock quantities, non-copyable (CSS + JS protection)

#### Feature I: Storeman Inventory Tools
**Files:** `includes/storeman-product-access.php`, `includes/storeman-inventory.php`

- **Product access control:** Storeman can only see inventory tab, prices hidden via CSS + server-side protection
- **Quick Stock Update page** (Products > Quick Stock Update):
  - Clickable summary cards (All Products, In Stock, Low Stock, Out of Stock) that filter the table
  - Category dropdown filter + real-time search by product name or SKU
  - Clickable column sorting (Product name, Stock level — ASC/DESC)
  - Product thumbnails (hidden on mobile)
  - +/- buttons for quick stock adjustment with change tracking (yellow highlight)
  - Unsaved changes warning (browser beforeunload) + confirmation dialog before save
  - Keyboard navigation (Tab/Enter/Arrow keys between stock inputs)
  - Reset all changes button
  - Sticky table header + sticky footer action bar with change count
  - Mobile responsive layout (hides thumbnail/category columns, 2x2 summary cards, larger touch targets)
  - Bulk AJAX update with real-time summary card refresh

#### Feature J: Payment Gateway Settings
**File:** `includes/payment-settings.php`

Sets Stripe PayNow as default payment method for both classic and Blocks checkout. Uses session manipulation + JavaScript polling for Blocks compatibility.

#### Feature K: Admin Settings Page
**File:** `includes/salesperson-settings.php`

Centralized settings at WooCommerce > Salesperson Settings:
- Commission rates (default %, per-carton, custom rates toggle)
- Approval workflow (auto/manual)
- Email notifications (recipients, events)
- Wholesale pricing fallback
- B2B Payment Terms (configurable repeater table)
- Salesperson overview stats

---

### 2.2 Ah Ho Invoicing Plugin (v1.4.1)

**Path:** `/wp-content/plugins/ah-ho-invoicing/`
**Dependency:** Dompdf 2.0 (via Composer in `vendor/`)

#### PDF Document Types

| Document | Template | Prices | Signatures | Use Case |
|----------|----------|--------|------------|----------|
| **Invoice** | `templates/invoice/invoice.php` | Yes | No | Customer billing |
| **Packing Slip** | `templates/packing-slip/packing-slip.php` | No | Packed/Checked By | Warehouse |
| **Delivery Order** | `templates/delivery-order/delivery-order.php` | No | Delivered/Customer | Driver |
| **Consolidated Packing** | `templates/packing-slip/consolidated.php` | No | Packed/Checked By | Multi-order batch (compact, paper-optimized) |

#### Invoice Numbering
- Format: `AHF-YYYY-NNNNN` (sequential, thread-safe with MySQL table locking)
- Stored in `_ah_ho_invoice_number` order meta

#### Key Classes

| Class | File | Purpose |
|-------|------|---------|
| `AH_HO_PDF_Generator` | `class-pdf-generator.php` | Dompdf wrapper with MD5 caching |
| `AH_HO_Invoice` | `class-invoice.php` | Sequential invoice numbering + generation |
| `AH_HO_Packing_Slip` | `class-packing-slip.php` | Single + consolidated packing slips |
| `AH_HO_Delivery_Order` | `class-delivery-order.php` | Delivery order with driver instructions |
| `AH_HO_Cache_Manager` | `class-cache-manager.php` | Auto-invalidation on order changes |
| `AH_HO_Metabox` | `class-metabox.php` | Download/print buttons on order page |
| `AH_HO_Email_Attachments` | `class-email-attachments.php` | Auto-attach PDFs to emails |
| `AH_HO_Settings` | `class-settings.php` | WC Settings > PDF Invoicing tab |
| `AH_HO_Admin_Page` | `class-admin-page.php` | Bulk PDF page (consolidated + ZIP) |
| `AH_HO_Delivery_Date_Helper` | `class-delivery-date-helper.php` | Detects 15+ delivery date plugins |

#### Email Attachments (Auto)

| Email | Attachment |
|-------|-----------|
| Customer Completed Order | Invoice |
| New Order (admin) | Packing Slip |
| Out for Delivery | Delivery Order |

#### PDF Cache
- Location: `/wp-content/pdf-cache/`
- Strategy: MD5 hash of HTML content
- Auto-invalidated on order update/status change
- Daily cron cleans PDFs older than 30 days

#### Settings (WC > Settings > PDF Invoicing)
- Company branding (logo, name, address, UEN, GST, bank)
- Email automation toggles
- PDF caching options
- Invoice numbering (prefix, starting number, padding)

---

### 2.3 Ah Ho Product Addons (v1.0.0)

**Path:** `/wp-content/plugins/ah-ho-product-addons/`

Two per-product features configured in the product edit General tab:

#### Product Notes (Special Requests)
- Green-themed section on product page
- Textarea with configurable label, placeholder, character limit (50-1000), required option
- Stored as order line item meta: `Special Requests`
- Admin display: Green box with diagonal stripe pattern

#### Gift Messages
- Yellow-themed section on product page
- Checkbox "This is a gift" + hidden textarea that slides down
- Configurable placeholder, character limit (50-500), required option
- Stored as: `Gift` = "Yes" + `Gift Message` = text
- Admin display: Yellow box with double border + "PRINT GIFT CARD" reminder

Both features integrate with ah-ho-invoicing PDF templates (green/yellow boxes on packing slips and delivery orders).

---

### 2.4 Payment Gateway Fees (v1.0.0)

**Path:** `/wp-content/plugins/payment-gateway-fees/`

Adds processing fees based on payment method:
- Supports fixed amount, percentage, or combined
- Works with Classic and Blocks checkout
- Stripe-specific support for dynamic payment methods
- Admin UI under WooCommerce menu
- Fees optionally taxable

---

### 2.5 Typography Fix (v1.0.0)

**Path:** `/wp-content/plugins/ah-ho-typography-fix.php`

Single-file plugin that fixes Avada Vegan theme's poor readability:
- Forces dark gray (#2c3e50) body text
- Black (#1a1a1a) headings
- Preserves white text on dark hero backgrounds
- Fixes WooCommerce elements and mobile menu
- Priority 999 to override theme

---

### 2.6 Legal Pages Setup (v1.0.0)

**Path:** `/wp-content/plugins/ah-ho-legal-pages-setup.php`

One-time utility — creates Terms & Conditions and Privacy Policy pages with PDPA-compliant content, configures WooCommerce/WordPress to use them, adds to footer menu. **Deactivate after use.**

---

## 3. Custom Child Theme

**Path:** `/wp-content/themes/ah-ho-fruits/`
**Parent:** Avada (ThemeForest)

### Files

| File | Lines | Purpose |
|------|-------|---------|
| `functions.php` | 282 | Theme setup, WooCommerce, custom image sizes, widgets |
| `inc/woocommerce.php` | 195 | "Add to Basket" text, sale badges, shipping notice, WhatsApp button |
| `inc/customizer.php` | 173 | WordPress Customizer: colors, products per page, WhatsApp number |
| `assets/css/custom.css` | 505 | Header, navigation, badges, WhatsApp button, responsive |
| `assets/js/main.js` | 216 | Mobile menu, sticky header, mini cart AJAX, smooth scroll |
| `header.php` | — | Custom header template |
| `footer.php` | — | Custom footer template |
| `style.css` | — | Child theme declaration |

### Singapore-Specific Features
- SGD currency + GST (9%) support
- Free delivery threshold messaging
- Postal code required at checkout
- WhatsApp floating button (green #25D366)
- Delivery time slot messaging

---

## 4. Infrastructure & Deployment

### Hosting
- **Provider:** Vodien (sh00017.vodien.com)
- **Access:** FTP only (no shell), cPanel at port 2083
- **Server path:** `/home2/contactl/public_html/ah-ho-fruits/`
- **PHP:** 8.3 (ea-php83) via MultiPHP Manager

### Deployment
- **Method:** GitHub Actions → FTP-Deploy-Action (single step)
- **Triggers:** Changes to `wp-content/**`, `.htaccess`, `wp-config.php`, `robots.txt`, `*.php`, `*.html`
- **Duration:** 1-2 min typical, up to 10 min for large deploys
- **Excludes:** `.git/`, `docs/`, `sql/`, deployment scripts

### Critical Configuration Files

**.htaccess** (633 bytes):
- `AddHandler application/x-httpd-ea-php83 .php .php8 .phtml`
- WordPress rewrite rules
- iThemes Security rules (file protection, directory browsing disabled)
- Never copy from other sites — causes 404/500 errors

**wp-config.php:**
- Database: `contactl_wp153`, prefix: `wpgr_`
- `WP_HOME` / `WP_SITEURL`: `https://fruits.heymag.app`

**robots.txt:**
- Allows product images, blocks admin/cart/checkout
- Filters tracking parameters (fbclid, utm_*)
- Sitemap: `https://fruits.heymag.app/sitemap_index.xml`

### Verification Commands
```bash
# GitHub Actions status
gh run list --repo lexsgd/ah-ho-fruits --limit 1

# Site checks
curl -s -o /dev/null -w "%{http_code}" "https://fruits.heymag.app"        # 200
curl -s -o /dev/null -w "%{http_code}" "https://fruits.heymag.app/wp-admin/"  # 302
```

---

## 5. Database Reference

### Order Meta Keys

| Key | Type | Source |
|-----|------|--------|
| `_assigned_salesperson_id` | int | ah-ho-custom (attribution) |
| `_commission_rate` | float | ah-ho-custom (commission) |
| `_commission_per_carton_rate` | float | ah-ho-custom (commission) |
| `_commission_percentage_amount` | float | ah-ho-custom (commission) |
| `_commission_carton_amount` | float | ah-ho-custom (commission) |
| `_commission_amount` | float | ah-ho-custom (commission) |
| `_commission_total_quantity` | int | ah-ho-custom (commission) |
| `_commission_status` | string | ah-ho-custom (commission) |
| `_commission_paid_date` | date | ah-ho-custom (commission) |
| `_commission_needs_clawback` | bool | ah-ho-custom (commission) |
| `_stock_reduced_out_delivery` | bool | ah-ho-custom (order statuses) |
| `_delivery_date` | Y-m-d | ah-ho-custom (delivery date) |
| `_delivery_time_slot` | string | ah-ho-custom (delivery date) |
| `_ah_ho_invoice_number` | string | ah-ho-invoicing |
| `_ah_ho_invoice_date` | datetime | ah-ho-invoicing |
| `_po_number` | string | Manual entry |

### Order Item Meta Keys

| Key | Type | Source |
|-----|------|--------|
| `_wholesale_price_applied` | "yes" | ah-ho-custom (wholesale) |
| `_wholesale_unit_price` | float | ah-ho-custom (wholesale) |
| `_original_retail_price` | float | ah-ho-custom (wholesale) |
| `Special Requests` | text | ah-ho-product-addons |
| `Gift` | "Yes" | ah-ho-product-addons |
| `Gift Message` | text | ah-ho-product-addons |

### Product Meta Keys

| Key | Type | Source |
|-----|------|--------|
| `_wholesale_price` | float | ah-ho-custom (wholesale) |
| `_enable_product_notes` | yes/no | ah-ho-product-addons |
| `_product_notes_label` | string | ah-ho-product-addons |
| `_product_notes_placeholder` | string | ah-ho-product-addons |
| `_product_notes_char_limit` | int | ah-ho-product-addons |
| `_product_notes_required` | yes/no | ah-ho-product-addons |
| `_enable_gift_message` | yes/no | ah-ho-product-addons |
| `_gift_message_placeholder` | string | ah-ho-product-addons |
| `_gift_message_char_limit` | int | ah-ho-product-addons |
| `_gift_message_required` | yes/no | ah-ho-product-addons |

### User Meta Keys

| Key | Type | Source |
|-----|------|--------|
| `_payment_terms` | string | ah-ho-custom (roles) |
| `_commission_rate` | float | ah-ho-custom (roles) |
| `_commission_per_carton_rate` | float | ah-ho-custom (roles) |
| `_created_by_staff_id` | int | ah-ho-custom (roles) |

### WordPress Options (`wp_options`)

| Key | Default | Source |
|-----|---------|--------|
| `ah_ho_default_commission_rate` | 10 | ah-ho-custom |
| `ah_ho_default_per_carton_rate` | 0 | ah-ho-custom |
| `ah_ho_enable_custom_rates` | true | ah-ho-custom |
| `ah_ho_commission_approval_mode` | "auto" | ah-ho-custom |
| `ah_ho_commission_notification_emails` | admin_email | ah-ho-custom |
| `ah_ho_notify_on_approval` | true | ah-ho-custom |
| `ah_ho_monthly_summary_salesperson` | true | ah-ho-custom |
| `ah_ho_monthly_summary_admin` | false | ah-ho-custom |
| `ah_ho_default_wholesale_discount` | 0 | ah-ho-custom |
| `ah_ho_wholesale_fallback` | "retail" | ah-ho-custom |
| `ah_ho_payment_terms` | array | ah-ho-custom |
| `ah_ho_last_invoice_number` | 0 | ah-ho-invoicing |
| `ah_ho_company_name` | — | ah-ho-invoicing |
| `ah_ho_company_address` | — | ah-ho-invoicing |
| `ah_ho_company_phone` | — | ah-ho-invoicing |
| `ah_ho_company_email` | — | ah-ho-invoicing |
| `ah_ho_company_uen` | — | ah-ho-invoicing |
| `ah_ho_company_gst` | — | ah-ho-invoicing |
| `ah_ho_bank_name` | — | ah-ho-invoicing |
| `ah_ho_bank_account` | — | ah-ho-invoicing |
| `ah_ho_company_logo_url` | — | ah-ho-invoicing |

---

## 6. Changelog

### Phase 1: Foundation (Initial → Jan 2026)
- WordPress + Avada theme deployment to Vodien
- FTP deployment pipeline (GitHub Actions)
- Ah Ho Custom plugin: custom order statuses, email notifications
- Child theme with Singapore-specific features

### Phase 2: B2B Salesperson System (Jan 2026)
- Salesperson role with restricted admin access
- Commission tracking (percentage model)
- Order assignment and attribution
- 4-layer order access security (HPOS compatible)
- Customer creation/management by salespersons
- Payment terms per customer (COD, Credit 7/14/30 days)
- Custom statuses: Processing-B2B, complete delivery workflow

### Phase 3: Wholesale Pricing (Jan 2026)
- Per-product wholesale pricing (`_wholesale_price`)
- Auto-applied on salesperson order creation
- Quick edit, bulk edit, bulk actions
- Wholesale column on product list
- Configurable fallback behavior

### Phase 4: PDF Invoicing System (Jan-Feb 2026)
- Invoice (sequential AHF-YYYY-NNNNN numbering)
- Packing slip (no prices, checkbox column, allergy highlighting)
- Delivery order (driver-facing, signatures, COD collection)
- Consolidated packing slip (multi-order, route-optimized sorting)
- Bulk PDF download (ZIP)
- Email auto-attachments
- PDF caching with auto-invalidation
- Company branding settings
- Delivery date helper (15+ plugin compatibility)

### Phase 5: Product Addons (Jan 2026)
- Product Notes/Special Requests per product
- Gift Messages with checkbox toggle
- Character limits, required validation
- Green/yellow admin display boxes
- Integration with packing slips and delivery orders

### Phase 6: Operational Enhancements (Jan-Feb 2026)
- Payment Gateway Fees plugin
- PayNow default payment method (Classic + Blocks)
- WhatsApp catalog generator with category emojis
- B2B stock list (internal, hidden products)
- Storeman role with inventory-only product access
- Quick Stock Update bulk page
- Delivery date checkout field (Classic + Blocks)
- Delivery date admin column with color coding and filters
- Dual commission model (percentage + per-carton)
- Configurable payment terms via admin settings UI
- Quick Stock Update page for rapid inventory management

### Phase 7: UX & Efficiency (Feb 2026)
- Quick Stock Update major UX overhaul: clickable sorting, composable filters (category + stock status + search), +/- stock buttons, change tracking, unsaved changes warning, sticky header/footer, keyboard navigation, product thumbnails
- Quick Stock Update mobile responsive layout (hidden columns, 2x2 cards, enlarged touch targets)
- Consolidated packing slip redesigned for paper efficiency: custom compact header, removed SKU/weight/footer, tighter spacing, date-grouped page breaks

### Phase 8: SEO & Legal (Jan 2026)
- robots.txt with WooCommerce-specific rules
- Legal pages (Terms & Conditions, Privacy Policy — PDPA-compliant)
- Typography fix plugin for Avada theme readability

---

## Notes

- **No WordPress core modifications.** All customizations are in plugins and child theme.
- **HPOS compatible** throughout — uses `$order->get_meta()` / `$order->update_meta_data()`.
- All custom code uses `ah_ho_` prefix for functions and `_ah_ho_` for meta keys to avoid conflicts.
- Test/debug PHP files in root directory (`check-files.php`, `phpinfo.php`, etc.) are deployment diagnostics — safe to delete when stable.
