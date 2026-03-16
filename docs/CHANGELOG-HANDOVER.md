# Ah Ho Fruit — Project Changelog & Handover Document

**Client:** Ah Ho Fruit
**Website:** ahhofruit.com
**Platform:** WordPress + WooCommerce (Avada Theme)
**Hosting:** Vodien (Singapore)
**Project Duration:** 21 January 2026 — 10 March 2026
**Total Commits:** 320

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Week 1–2: 21 Jan – 31 Jan 2026](#week-1-2-21-jan--31-jan-2026)
3. [Week 3–4: 1 Feb – 14 Feb 2026](#week-3-4-1-feb--14-feb-2026)
4. [Week 5–6: 15 Feb – 28 Feb 2026](#week-5-6-15-feb--28-feb-2026)
5. [Week 7–8: 1 Mar – 8 Mar 2026](#week-7-8-1-mar--8-mar-2026)
6. [Complete Feature List](#complete-feature-list)
7. [Custom Plugins Delivered](#custom-plugins-delivered)
8. [Technical Infrastructure](#technical-infrastructure)

---

## Project Overview

A full-scope e-commerce customisation project for **Ah Ho Fruit**, a Singapore-based wholesale and retail fruit supplier. The project involved building a complete B2B wholesale ordering system on top of WordPress/WooCommerce, with custom invoicing, delivery workflows, salesperson management, and inventory tools — all tailored for the fresh produce industry.

**Key deliverables:**
- Custom B2B wholesale pricing and ordering system
- PDF Invoice, Delivery Order, and Packing Slip generation
- Salesperson and Storeman role management
- WhatsApp B2B catalog generator
- Quick Stock Update tool for bulk inventory management
- Delivery date scheduling with business rules
- Payment gateway fee processing
- GST handling for B2B orders
- Mobile app access for field staff
- Comprehensive documentation and guides

---

## Week 1–2: 21 Jan – 31 Jan 2026

### Project Setup & Infrastructure (21 Jan)

- **WordPress deployment pipeline created** — Set up automated deployment from GitHub to Vodien hosting using FTP (GitHub Actions). Tested multiple deployment methods (rsync, SFTP) before settling on FTP due to Vodien's hosting restrictions.
- **Avada theme installed** — Licensed premium WordPress theme (ThemeForest) installed and configured as the storefront foundation.
- **Git version control initialised** — Full project repository set up on GitHub for code tracking and automated deployments.

### Custom Order Statuses (23 Jan)

- **Delivery workflow statuses added** — Created custom WooCommerce order statuses to match Ah Ho Fruit's delivery workflow:
  - "Out for Delivery"
  - "Delivered"
  - "Ready for Pickup"
- **Email notifications configured** — Automatic customer email notifications for each status change, so customers are informed when their order is out for delivery or ready for pickup.

### B2B Salesperson System (24 Jan)

- **Salesperson role created** — New WordPress user role specifically for Ah Ho Fruit's sales staff, with restricted access (can only see their own orders and customers).
- **Commission tracking system built** — Automatic commission calculation for salesperson orders, with admin tools to view and manage commissions.
- **Role-based access control** — Salespersons can create orders and manage their customers but cannot access admin settings, other staff's orders, or financial reports.

### PDF Invoicing System (24 Jan)

Built a complete 4-phase PDF document system:

- **Phase 1 — Invoice Generation** — Professional PDF invoices generated automatically for every order, featuring Ah Ho Fruit branding, itemised line items, weights, and totals.
- **Phase 2 — Packing Slip & Delivery Order** — Separate PDF documents for warehouse packing and delivery drivers. Packing slips show item details and weights; delivery orders match traditional commercial format with sign-off fields.
- **Phase 3 — Email Automation** — Invoices automatically attached to WooCommerce order confirmation emails. Configurable settings for which PDF types to auto-send.
- **Phase 4 — Bulk PDF Download** — Admin page to select multiple orders and download all their PDFs as a single ZIP file. Useful for monthly accounting or batch printing.

### SEO Foundation (25 Jan)

- **Comprehensive SEO setup** — Meta tags, Open Graph tags, structured data (Schema.org), XML sitemap generation, and robots.txt configured for search engine visibility.
- **Product SEO templates** — Standardised SEO title and description templates created for all product categories.

### Server Stability & Troubleshooting (25 Jan)

- **Critical site recovery** — Resolved a server 500 error caused by .htaccess conflicts between PHP version requirements and Vodien's server configuration. Required extensive diagnosis across PHP versions, OPcache, and WordPress core files.
- **Deployment pipeline refined** — Established stable FTP deployment path after thorough testing with Vodien's server structure.

### Product Add-ons Plugin (25 Jan)

- **Gift message support** — Customers can add personal gift messages to their orders during checkout. Messages appear on packing slips with highlighted styling for warehouse staff.
- **Customer notes field** — Additional notes field for special instructions (e.g., "extra ripe", "for event on Saturday").
- **B&W printer optimisation** — Add-on indicators styled with dashed borders and clear markers for printing on standard black-and-white printers.

### Legal Compliance (26 Jan)

- **Terms & Conditions page** — Auto-generated legal page with e-commerce terms specific to fresh produce delivery (perishable goods policy, delivery timeframes, refund conditions).
- **Privacy Policy page** — PDPA-compliant privacy policy covering data collection, storage, and customer rights.
- **Footer integration** — Legal pages automatically linked in the website footer menu.

### Typography & Readability (26 Jan)

- **Site-wide readability improvements** — Fixed font sizing, line height, and colour contrast across the Avada theme for better readability on all pages.
- **Hero section text fix** — Corrected white text rendering on dark background hero sections.

### PHP 8 Compatibility Fixes (29 Jan)

- **Weight calculation fix** — Resolved PHP 8.x type errors in WooCommerce checkout where product weight calculations were failing. Applied type-casting fixes across invoice, packing slip, and checkout processes.

---

## Week 3–4: 1 Feb – 14 Feb 2026

### Payment Gateway Fees (31 Jan – 1 Feb)

- **Credit card surcharge system** — Built a plugin to automatically apply payment processing fees (e.g., 3.4% + $0.50 for Stripe credit card payments). Fees are transparently shown to customers at checkout.
- **PayNow default payment** — Configured PayNow (Singapore bank transfer) as the default payment method, encouraging lower-cost payment options.
- **WooCommerce Blocks compatibility** — Ensured payment fee calculations work with both the classic checkout and the newer WooCommerce Blocks checkout.
- **Fee calculation includes shipping** — Processing fees correctly calculated on the full order amount including shipping costs.

### B2B Wholesale Pricing System (1 Feb)

- **Wholesale price field** — Added a separate wholesale price field to every WooCommerce product. When a salesperson creates an order, the wholesale price is automatically applied instead of the retail price.
- **Price detection logic** — System automatically identifies B2B orders based on whether products have wholesale pricing applied, ensuring correct pricing on invoices and reports.
- **HPOS compatibility** — All wholesale pricing features built to work with WooCommerce's High-Performance Order Storage (modern database structure).

### Salesperson Enhancements (1–2 Feb)

- **Order restriction** — Salespersons can only view and edit their own orders. Order counts and status badges in the admin menu reflect only their assigned orders.
- **Customer management** — Salespersons can create new customer accounts and set payment terms (e.g., "COD", "7 Days", "14 Days", "30 Days").
- **Admin menu cleanup** — Removed unnecessary WordPress admin menu items (Media, Dashboard, etc.) for salesperson accounts, showing only relevant sections (Orders, Customers, Products).
- **Login redirect** — Salespersons are automatically redirected to the Orders page after login, not the WordPress dashboard.
- **Product search in orders** — Enabled product search capability when salespersons create orders, so they can quickly find products to add.
- **Salesperson attribution** — Every order tracks which salesperson created it, displayed as a column in the orders list and in order details.
- **Quick "Complete" action** — Added a one-click "Complete" button for B2B orders in the orders list, streamlining the fulfilment workflow.

### Commission System Upgrade (2 Feb)

- **Immediate calculation** — Commissions are now calculated the moment an order is placed, not delayed.
- **Recalculation tool** — Admin tool to recalculate commissions for existing orders (useful for retroactive adjustments).
- **Cleanup tool** — Utility to fix incorrectly assigned orders from the initial setup period.

### Custom Order Status — Processing B2B (2 Feb)

- **"Processing - B2B" status** — New order status specifically for wholesale/B2B orders. Automatically assigned when a salesperson creates an order, making it easy to distinguish B2B orders from retail (B2C) orders in the admin panel.

### Customer Payment Terms (2 Feb)

- **Payment terms field** — Added configurable payment terms to customer profiles (COD, 7 Days, 14 Days, 30 Days, or custom terms).
- **Dynamic AJAX update** — Payment terms can be changed directly from the order page without reloading.
- **Admin settings UI** — Configurable list of payment term options in the plugin settings.

### WhatsApp B2B Catalog Generator (2 Feb)

- **One-click catalog generation** — Admin page that generates a WhatsApp-friendly text catalog of all B2B products with wholesale prices. Staff can copy the text and paste directly into WhatsApp to share with wholesale customers.
- **Smart filtering** — Only shows in-stock products that have a wholesale price set. Excludes B2C-only products.
- **Category grouping** — Products organised by category with clear headings.
- **Auto-refresh** — Catalog updates automatically whenever prices or stock levels change.

### B2B Stock List (5 Feb)

- **Internal stock reference** — Added a separate "B2B Stock List" section below the WhatsApp catalog showing stock quantities alongside prices. This section is for internal reference only — it cannot be copied or shared (copy protection enabled).

### Quick Bulk Actions (2 Feb)

- **Bulk stock and price updates** — Admin tool for quickly updating stock quantities and wholesale prices for multiple products at once, without opening each product individually.

### Delivery Date System (2 Feb)

- **Checkout date picker** — Customers select their preferred delivery date at checkout using a visual calendar (Flatpickr). Weekends (Sundays) are greyed out. Public holidays are blocked.
- **Business rules enforced** — Minimum lead time enforced (next business day). Saturday deliveries allowed. Sunday deliveries blocked.
- **Admin editing** — Staff can view and modify the delivery date from the order admin page.
- **WooCommerce Blocks compatible** — Works with both classic and Blocks checkout pages.

### Delivery Order Redesign (6–7 Feb)

- **Traditional commercial format** — Redesigned the Delivery Order PDF to match the traditional Singapore commercial delivery order format, with proper header layout, company details (UEN, GST registration), and sign-off section.
- **Single-page optimisation** — Tightened margins and layout to ensure most delivery orders fit on a single A4 page.
- **Auto-fill fields** — PO Number and Payment Terms automatically populated from order data.
- **Contact details** — WhatsApp number and business email displayed on the delivery order.
- **Deliver To section** — Separate delivery address section alongside the billing address.

### Dual Commission Model (7 Feb)

- **Per-carton + percentage commissions** — Extended the commission system to support both flat per-carton rates and percentage-based commissions, matching Ah Ho Fruit's real-world commission structure.

### PDF Improvements (7 Feb)

- **Print buttons** — Added print buttons to invoice, delivery order, and packing slip pages for direct printing from the browser.
- **Tighter margins** — Optimised PDF layouts with tighter margins to maximise content space.
- **14 item rows** — Standardised delivery orders with 14 line item rows for consistency.
- **Gift/request highlighting** — Gift messages and customer requests highlighted with coloured boxes on packing slips for warehouse visibility.

### Storeman Role (8 Feb)

- **New "Storeman" role** — Created a dedicated role for warehouse staff with access to inventory management but restricted from financial and order management features.
- **Product inventory access** — Storemen can view and update stock levels but cannot modify prices or access customer data.
- **Customer visibility restriction** — Staff members can only see customers they personally created, preventing data leakage between salespersons.

### Invoice Redesign (8 Feb)

- **Matching layout** — Redesigned the invoice PDF template to match the delivery order's professional layout, ensuring consistent branding across all documents.
- **Order number as invoice number** — Simplified invoice numbering to use the WooCommerce order number directly, eliminating confusion between sequential invoice numbers and order numbers.

### Quick Stock Update Page (8 Feb)

- **Dedicated inventory page** — Full-featured admin page for bulk stock management, allowing warehouse staff (Storemen) to update stock quantities for all products in one screen.
- **Category filtering** — Filter products by category for faster stock-taking.
- **Search functionality** — Quick search to find specific products.
- **Change tracking** — Visual indicators (yellow highlight) showing which rows have been modified before saving.
- **Mobile responsive** — Optimised layout for tablet use during warehouse stock-taking.

### Packing Slip Optimisation (8 Feb)

- **Paper efficiency** — Redesigned both single-order and consolidated packing slip layouts to use less paper.
- **Removed sign-off section** — Streamlined packing slips by removing the sign-off section (kept on delivery orders only).

### HeyMag Chat Plugin (8 Feb)

- **AI chat widget** — Installed the HeyMag Chat widget for customer support automation on the website. The chatbot can answer common questions about products, delivery, and ordering.
- **WooCommerce integration** — Chat widget compatible with WooCommerce's HPOS and Blocks systems.

### B2B Product Visibility (9 Feb)

- **B2B products visible to all** — Made wholesale products visible in the shop and search results for retail customers too, expanding product discoverability while maintaining wholesale-only pricing for B2B orders.

### CJK Font Support (9 Feb)

- **Chinese character support** — Added Noto Sans CJK font to the PDF generation system, allowing Chinese characters in customer names, addresses, and product names to render correctly on invoices, delivery orders, and packing slips.

### Omakase Boxes Promotion (12 Feb)

- **Product pinning** — Pinned "Omakase Boxes" products to the top of the shop page for promotional visibility.

### Salesman Account Setup (13 Feb)

- **Bulk account creation** — Set up 5 salesman accounts with proper roles, capabilities, and access restrictions. Implemented via a one-time setup script with error handling.

### Partial Delivery & Returns System (14 Feb)

- **Partial delivery tracking** — Built a system to track partial deliveries for large B2B orders. Each delivery generates its own PDF delivery order, tracking which items were delivered and which are pending.
- **Item returns handling** — Returns tracking within orders, showing returned items and adjusted totals on invoices.
- **Per-delivery PDFs** — Individual delivery order PDFs generated for each partial delivery, with delivery sequence numbers.
- **Audit trail** — All delivery and return actions logged with timestamps and user attribution.

---

## Week 5–6: 15 Feb – 28 Feb 2026

### FTP Deployment Fix (15 Feb)

- **Server path correction** — Discovered and fixed a critical deployment path issue where the FTP server directory was `ah-ho-fruit` (no "s"), not `ah-ho-fruits`. This had been causing new files to deploy to the wrong location. Extensive diagnosis required probing multiple FTP paths and server configurations.
- **Stale directory cleanup** — Cleaned up the incorrectly-named `ah-ho-fruits/` directory on the server.

### Salesperson Meta Box Upgrade (15–16 Feb)

- **HPOS-compatible salesperson assignment** — Rebuilt the salesperson assignment meta box on the order page to work reliably with WooCommerce's High-Performance Order Storage.
- **AJAX save** — Salesperson assignment now saves via AJAX without requiring a full page reload.
- **Commission on admin assignment** — Commissions are correctly calculated even when an admin manually assigns a salesperson to an existing order.

### Business Rebranding (19 Feb)

- **"Ah Ho Fruits" → "Ah Ho Fruit"** — Comprehensive rebrand across the entire codebase, changing all instances of "Ah Ho Fruits" (with "s") to "Ah Ho Fruit" (without "s") to match the official business name. Applied to:
  - Plugin names and descriptions
  - PDF templates (invoice, delivery order, packing slip)
  - Email templates
  - Admin interface labels
  - WhatsApp catalog headers
  - Documentation

### Shop & Checkout Improvements (23 Feb)

- **Product ordering** — Customised shop page product sorting to display products in a logical, category-aware order.
- **Free shipping threshold** — Added free delivery for orders above $60. Self-pickup option remains available regardless of order amount.
- **Email update** — Updated business contact email across all customer-facing templates.

### PDF Quality & Download Fixes (23 Feb)

- **CJK font fix (final)** — Resolved persistent Chinese character display issues by switching to pre-built font metrics (.ufm) and Medium weight Noto Sans CJK font. Characters that previously showed as "?????" now render correctly.
- **Darker text for printing** — Increased text darkness across all PDFs for better readability when printed on standard office printers.
- **PDF cache system** — Implemented automatic PDF cache directory creation with graceful fallback on write failure, preventing server errors when the cache folder doesn't exist.
- **Download filename fix** — Fixed an issue where Vodien's server proxy was replacing PDF filenames with random UUIDs. Implemented a workaround using temporary static file serving and XHR/Blob downloads to ensure filenames like "Invoice-1234.pdf" are preserved.

### WooCommerce Mobile App Access (23 Feb)

- **Storeman app access** — Enabled the WooCommerce mobile app for Storeman accounts, allowing warehouse staff to check and update stock levels from their phones while doing stock-takes.
- **Salesperson app access** — Enabled the WooCommerce mobile app for Salesperson accounts, allowing field sales staff to create orders and check product availability on the go.
- **Role compatibility** — Added necessary capabilities and role spoofing to pass the WooCommerce mobile app's strict role-checking requirements while maintaining security restrictions.

### Documentation Update (25 Feb)

- **System documentation refresh** — Updated the comprehensive system documentation covering all custom plugins, features, settings, and configurations.
- **Copy audit report** — Added a review of all customer-facing text and copy across the website.

### Order Item Editing (26 Feb)

- **Edit items after order creation** — Built a system allowing admin staff to edit order line items (change quantities, add/remove products) even after the order has been placed. All changes are logged in an audit trail.
- **Returns on invoice** — Invoices now show return details and net totals when items have been returned, providing accurate financial records.

### Order Management Guide (26 Feb – 1 Mar)

- **Handover documentation** — Created a comprehensive Order Management Guide covering:
  - How to create and edit orders
  - How to process partial deliveries
  - How to handle returns
  - Step-by-step instructions with screenshots
  - Written for non-technical staff

---

## Week 7–8: 1 Mar – 10 Mar 2026

### Product Add-on: Omakase Flowers (2 Mar)

- **Optional flower add-on** — Added an optional "Add Flowers" checkbox for Omakase Box products. When selected, a bouquet of flowers is added to the order at an additional charge.
- **Lightbox preview** — Clicking the flower add-on shows a lightbox preview of the flower arrangement.
- **Checkout integration** — Add-on appears naturally in the product page and carries through to checkout and packing slips.

### Promotional Content Update (3 Mar)

- **Discount update** — Changed promotional discount from "10% off" to "5% off" on product pages.
- **Delivery promise update** — Changed "next morning delivery" to "next day delivery" to accurately reflect delivery timeframes.

### Invoice Deliver To Section (4 Mar)

- **Dual address layout** — Added a "Deliver To" section alongside the "Bill To" section on invoices, showing the delivery address and phone number separately from the billing address. Essential for B2B orders where billing and delivery addresses often differ.

### Product Image Generation (4 Mar)

- **Pomelo product image** — Generated and uploaded a professional product image for the White-flesh Pomelo product using AI image generation (Nano Banana Pro). Iterated through multiple versions to achieve the correct pear-shaped pomelo appearance.

### Delivery Date Improvements (7 Mar)

- **Blocks checkout save fix** — Fixed an issue where the delivery date was not saving correctly on the WooCommerce Blocks checkout page.
- **Invoice delivery date** — Added the selected delivery date to the invoice PDF template, so delivery dates are clearly documented on financial records.
- **Admin edit fix** — Fixed the delivery date edit fields not appearing when clicking "Edit" on the order admin page.
- **Saturday delivery** — Updated business rules to allow Saturday deliveries.
- **Public holiday blocking** — Added Singapore public holiday blocking to prevent customers from selecting non-delivery dates.
- **Time slot removal** — Simplified checkout by removing the time slot selection (previously offered AM/PM slots that were not operationally supported).

### Payment Fee Fix (7 Mar)

- **Guest checkout fee fix** — Fixed credit card processing fees not being applied on Blocks checkout for guest (non-logged-in) customer orders.

### Knowledge Base (7 Mar)

- **HeyMag Copilot knowledge base** — Created a comprehensive knowledge base document covering all Ah Ho Fruit products, delivery policies, ordering processes, and FAQs. This knowledge base powers the AI chat widget's responses to customer inquiries.

### B2B GST Implementation (7–8 Mar)

- **GST toggle on orders** — Added a checkbox on the WooCommerce order page: "B2B Order — Add 9% GST to invoice". When checked, the order is treated as a B2B order with GST added.
- **Auto-enable for B2B** — The GST checkbox is automatically checked when a salesperson or storeman creates an order with wholesale-priced items.
- **Manual override** — Any admin or staff member can manually toggle the GST checkbox on or off for any order, providing flexibility for edge cases.
- **GST on admin order page** — GST (9%) amount and Total + GST rows displayed directly on the order admin page below the Items Subtotal, with real-time JavaScript updates when the toggle is changed.
- **GST on invoice PDF** — B2B invoices show the GST amount (9%) and the GST-inclusive total. B2C invoices remain unchanged (prices are GST-inclusive/nett).
- **Business logic** — B2C prices are nett (GST-inclusive). B2B wholesale prices are GST-exclusive, with 9% GST added on the invoice and order total.

### WhatsApp Catalog Emoji Headings (8 Mar)

- **Dual emoji category headings** — Updated the WhatsApp B2B catalog to use dual emojis for each category heading (one on each side), making the catalog more visually appealing:
  - 🍏 APPLES 🍎
  - 🍓 BERRIES 🫐
  - 🍊 CITRUS 🍋
  - 🍇 GRAPES 🍇
  - 🥝 KIWI 🥝
  - 🍈 MELONS 🍉
  - 🥕 OTHERS 🌴
  - 🍐 PEARS 🍐
  - 🍑 STONE 🍑
  - 🥭 TROPICAL 🍌

### Quick Stock Update — Price Editing (8 Mar)

- **Regular Price column** — Added an editable Regular Price column to the Quick Stock Update page (admin-only).
- **Sale Price column** — Added an editable Sale Price column (admin-only).
- **Wholesale Price column** — Added an editable Wholesale Price column (admin-only).
- **Bulk price updates** — Administrators can now update stock levels AND prices for all products on a single page, saving significant time during price adjustments.
- **Role restriction** — Price columns are only visible to administrator accounts. Storeman accounts continue to see only stock quantity fields.

### Quick Stock Update — Role Compatibility Fix (9 Mar)

- **Administrator role detection fix** — Fixed an issue where certain administrator accounts (e.g., "ahhofruit") could not see the price editing columns. The system was checking for the `manage_options` capability, which can fail when an account's role was changed after creation. Now checks the user's actual role assignment directly, ensuring all Administrator accounts can view and edit prices regardless of how the account was originally set up.

### Credit Card Fee & Express Checkout Fix (10 Mar)

- **Default payment switched to Credit Card** — Changed the default payment method from PayNow to Stripe (Credit Card). The 3.5% processing fee now displays immediately on the cart page and checkout page, so customers always see the fee upfront before paying.
- **Express checkout fee fix** — Apple Pay, Google Pay, and Link (express checkout buttons) are powered by Stripe. With Stripe as the default, the processing fee is already included in the total when customers use express checkout. Previously, customers could complete payment via Apple Pay or Google Pay without being charged the 3.5% processing fee.
- **Fee visible on cart page** — The processing fee now appears on the cart page (not just checkout), giving customers full price transparency from the moment they view their cart.
- **PayNow = no fee** — Customers who proactively select PayNow (bank transfer) as their payment method see the processing fee removed, paying $0 in fees. This incentivises lower-cost payment methods.
- **Express checkout disabled on cart page** — Apple Pay, Google Pay, and Link buttons are hidden on the cart page to prevent premature checkout. They remain available on the checkout page where the fee is visible.
- **Express checkout disabled on product pages** — Apple Pay, Google Pay, and Link buttons are also hidden on individual product pages, directing customers through the standard Add to Cart → Checkout flow where fees are transparent.
- **PayNow tip banner** — A friendly banner displayed at the top of the cart and checkout pages: "Credit card payments come with a 3.5% fee, so PayNow is the happiest (and fee-free) way to pay." Encourages customers to choose PayNow for a fee-free experience.
- **PayNow tip banner (Blocks compatible)** — The banner uses JavaScript injection to work with WooCommerce Blocks (React-based) cart and checkout pages, which don't support traditional PHP hooks. The banner retries briefly to handle the asynchronous rendering of Blocks components.
- **Duplicate fee prevention** — Added intelligent duplicate fee detection to prevent double-charging. The system now checks all configured gateway labels and fee amounts before adding a fee, preventing issues when the Stripe plugin's internal gateway IDs differ between session and order (e.g., 'stripe' vs 'stripe_cc').
- **Safety net for all Stripe orders** — Added a fallback hook that checks every Stripe order at creation time. If the processing fee was missed during cart calculation, it is automatically added to the order before payment is finalised.

### Delivery Date Improvements (10 Mar)

- **Next working day delivery** — Changed the minimum delivery lead time from 3 working days to the next available working day (Monday–Saturday). Sundays and Singapore public holidays remain blocked.
- **12pm cutoff rule** — Orders placed before 12pm can select next working day delivery. Orders placed after 12pm must select at least 2 working days ahead. This applies to both the classic checkout (PHP validation) and Blocks checkout (JavaScript date picker).

---

## Complete Feature List

| # | Feature | Description |
|---|---------|-------------|
| 1 | Automated FTP Deployment | GitHub → Vodien FTP auto-deployment on every code push |
| 2 | Avada Theme | Premium WordPress theme licensed and configured |
| 3 | Custom Order Statuses | "Out for Delivery", "Delivered", "Ready for Pickup", "Processing - B2B" |
| 4 | Order Status Emails | Automatic customer notifications on status changes |
| 5 | Salesperson Role | Custom role with restricted access, order filtering, and commission tracking |
| 6 | Storeman Role | Warehouse staff role with inventory-only access |
| 7 | Commission System | Dual model — per-carton flat rate + percentage-based commissions |
| 8 | B2B Wholesale Pricing | Separate wholesale price field with automatic application for B2B orders |
| 9 | PDF Invoice | Professional branded invoice with itemised details, weights, GST |
| 10 | PDF Delivery Order | Traditional commercial format with sign-off, PO, terms |
| 11 | PDF Packing Slip | Warehouse picking document with gift/note highlighting |
| 12 | Consolidated Packing Slip | Multi-order combined packing slip for batch fulfilment |
| 13 | Bulk PDF Download | ZIP download of multiple order PDFs |
| 14 | CJK Font Support | Chinese characters render correctly on all PDFs |
| 15 | WhatsApp Catalog Generator | One-click B2B product catalog for WhatsApp sharing |
| 16 | B2B Stock List | Internal stock reference with copy protection |
| 17 | Quick Stock Update | Bulk inventory management page with category filters |
| 18 | Bulk Price Editing | Admin-only Regular, Sale, and Wholesale price editing |
| 19 | Delivery Date Picker | Checkout calendar with business rules, weekend/holiday blocking |
| 20 | Payment Gateway Fees | 3.5% credit card fee shown on cart + checkout, with express checkout coverage |
| 21 | Credit Card Default | Stripe (Credit Card) as default; PayNow available with $0 fees |
| 22 | Free Shipping ($60+) | Free delivery threshold with self-pickup always available |
| 23 | Product Add-ons | Gift messages, customer notes, and optional flower add-on |
| 24 | Partial Delivery Tracking | Track partial deliveries with per-delivery PDFs |
| 25 | Item Returns Handling | Returns tracking with adjusted invoice totals |
| 26 | Order Item Editing | Post-creation line item editing with audit trail |
| 27 | B2B GST System | Toggle-based 9% GST for wholesale orders |
| 28 | Customer Payment Terms | Configurable payment terms (COD, 7/14/30 Days) |
| 29 | Omakase Product Pinning | Featured products pinned to top of shop page |
| 30 | SEO Foundation | Meta tags, structured data, XML sitemap |
| 31 | Legal Pages | Terms & Conditions + Privacy Policy (auto-generated) |
| 32 | Typography Fixes | Site-wide readability improvements |
| 33 | HeyMag Chat Widget | AI-powered customer support chatbot |
| 34 | Mobile App Access | WooCommerce mobile app for Storeman and Salesperson |
| 35 | Customer Visibility Control | Staff see only their own customers |
| 36 | Salesperson Auto-attribution | Orders automatically linked to creating salesperson |
| 37 | Product Image Generation | AI-generated product images (Pomelo) |
| 38 | Business Rebranding | "Ah Ho Fruits" → "Ah Ho Fruit" across all systems |

---

## Custom Plugins Delivered

| Plugin | Purpose | Key Files |
|--------|---------|-----------|
| **ah-ho-custom** | Core business logic — order statuses, salesperson roles, wholesale pricing, catalog generator, inventory management, delivery dates, commissions, B2B GST | 20 PHP modules |
| **ah-ho-invoicing** | PDF generation — invoices, delivery orders, packing slips, email automation, bulk downloads | 5 templates, Dompdf library |
| **ah-ho-product-addons** | Product add-ons — gift messages, customer notes, flower add-on with lightbox | 3 PHP files |
| **payment-gateway-fees** | Payment processing fee calculation and display | 1 PHP file |
| **ah-ho-legal-pages-setup** | Auto-generated Terms & Privacy Policy | 1 PHP file |
| **ah-ho-typography-fix** | Site-wide readability corrections | 1 PHP file |
| **heymag-chat** | AI customer support chatbot widget | 1 PHP file |

---

## Technical Infrastructure

| Component | Details |
|-----------|---------|
| **CMS** | WordPress 6.x |
| **E-commerce** | WooCommerce 9.x (HPOS enabled) |
| **Theme** | Avada (ThemeForest license) |
| **Hosting** | Vodien (Singapore, shared hosting) |
| **PHP Version** | 8.3 |
| **Database** | MySQL (prefix: `wpgr_`) |
| **Deployment** | GitHub Actions → FTP (SamKirkland/FTP-Deploy-Action) |
| **PDF Engine** | Dompdf 2.x |
| **CJK Font** | Noto Sans CJK Medium |
| **Checkout** | WooCommerce Blocks + Classic fallback |
| **Payment** | Stripe (Credit Card) + PayNow (Bank Transfer) |

---

*Document generated: 10 March 2026*
*Total project scope: 320 commits, 7 custom plugins, 38 features delivered*
