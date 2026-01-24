# Ah Ho Fruits - Invoicing & Packing Lists

> **Version:** 1.3.0  
> **Requires:** WordPress 6.0+, WooCommerce 8.0+, PHP 7.4+  
> **License:** GPL v2 or later

## Overview

Custom PDF invoice, packing slip, and delivery order generator for Ah Ho Fruits. Designed specifically for fruit delivery businesses with features like sequential invoice numbering, consolidated packing slips sorted by delivery route, and customer allergy highlighting.

---

## Key Features

### 📄 PDF Document Types

1. **Invoices**
   - Sequential invoice numbering (AHF-00001, AHF-00002, etc.)
   - Company branding (logo, UEN, GST, bank details)
   - Itemized order details with prices
   - Auto-attach to "Order Completed" email

2. **Packing Slips**
   - Warehouse-optimized layout (no prices, focus on SKU/quantity)
   - Customer notes highlighting (allergies/dietary restrictions in BOLD RED)
   - Checkbox column for storeman to tick off items
   - Weight calculations per item and total
   - **Consolidated mode**: Multiple orders sorted by delivery date → postal code

3. **Delivery Orders**
   - Driver-optimized with EXTRA LARGE text (28-48px fonts)
   - Highlighted delivery address and postal code
   - Large customer phone number (32px)
   - Delivery instructions in red border box
   - COD payment collection section
   - Signature boxes for driver and customer

### 🔄 Email Automation

- **Order Completed** → Auto-attach invoice to customer
- **New Order** → Auto-attach packing slip to admin
- **Out for Delivery** → Auto-attach delivery order to customer (custom email)
- **Processing Order** → Optional invoice attachment

### 🎛️ Admin Features

- **Bulk PDF Generation**: Generate consolidated packing slips by delivery date
- **Quick Statistics**: Invoice count, cache size, next invoice number
- **Settings Panel**: WooCommerce > Settings > PDF Invoicing
- **Custom Order Status**: "Out for Delivery" with truck icon

---

## Installation

### 1. Upload Plugin

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone <repository-url> ah-ho-invoicing
# OR upload ZIP via WordPress admin
```

### 2. Install Dependencies

This plugin requires Dompdf for PDF generation:

```bash
cd ah-ho-invoicing/
composer install
```

**IMPORTANT for Vodien hosting:**  
The vendor/ directory is committed to the repository (not .gitignored) because Vodien shared hosting doesn't have Composer installed. Simply upload the entire plugin folder via FTP.

### 3. Activate Plugin

1. Go to **WordPress Admin > Plugins**
2. Find "Ah Ho Fruits - Invoicing & Packing Lists"
3. Click **Activate**

### 4. Configure Settings

1. Go to **WooCommerce > Settings > PDF Invoicing**
2. Update company details (name, address, UEN, GST, bank account)
3. Configure email attachment settings (all enabled by default)
4. Customize invoice numbering (prefix, starting number, padding)

---

## Usage Guide

### For Admin/Office Staff

#### Download Individual PDFs

1. Go to **WooCommerce > Orders**
2. Click on any order to edit
3. In the right sidebar, find the **📄 PDF Documents** metabox
4. Click:
   - **📄 Generate/Download Invoice**
   - **📦 Download Packing Slip**
   - **🚚 Download Delivery Order**

#### Generate Consolidated Packing Slip

Perfect for warehouse batch preparation:

1. Go to **WordPress Admin > PDF Documents**
2. Select **Delivery Date** (e.g., tomorrow's deliveries)
3. Select **Order Status** (usually "Processing")
4. Choose **Sort By**: "Delivery Date → Postal Code" (recommended)
5. Click **Generate Consolidated Packing Slip**
6. Download the generated PDF

**Result:** Single PDF with all orders sorted by route for efficient packing.

---

## Troubleshooting

### PDFs Not Generating

**Symptom:** Clicking download buttons shows error or blank page.

**Solutions:**
1. Check Dompdf is installed: `/vendor/autoload.php` should exist
2. Verify PHP memory limit: ≥256MB
3. Check file permissions: `/wp-content/pdf-cache/` should be writable (755)
4. Enable WP_DEBUG to see error messages

---

## Changelog

### Version 1.3.0
- NEW: Bulk PDF download admin page
- NEW: Consolidated packing slip generator by delivery date
- NEW: Quick statistics dashboard

### Version 1.2.0
- NEW: Custom "Out for Delivery" order status
- NEW: Email automation (auto-attach PDFs)
- NEW: Settings page

### Version 1.1.0
- NEW: Packing slip and delivery order templates
- NEW: Consolidated packing slip (multi-order)
- NEW: Customer notes highlighting

### Version 1.0.0
- Initial release
- Sequential invoice numbering
- Invoice PDF generation

---

## License

GPL v2 or later
