# Ah Ho Fruit - Remaining Tasks Implementation Plan

**Date**: January 22, 2026
**Project**: Ah Ho Fruit WordPress Site Transformation
**Site URL**: https://fruits.heymag.app/
**Status**: Phase 1 Complete (Footer Menu Links Updated)

---

## Table of Contents

1. [Understanding Token Limit Issues](#understanding-token-limit-issues)
2. [Task 2: Remove Vegan References from Meta Description](#task-2-remove-vegan-references-from-meta-description)
3. [Task 3: Remove Vegan References from Section Headers](#task-3-remove-vegan-references-from-section-headers)
4. [Task 4: Update Footer Logo Text](#task-4-update-footer-logo-text)
5. [Task 5: Create FRUITS10 Coupon Code](#task-5-create-fruits10-coupon-code)
6. [Task 6: Add Sample Fruit Products](#task-6-add-sample-fruit-products)
7. [File Structure Summary](#file-structure-summary)
8. [Execution Timeline](#execution-timeline)

---

## Understanding Token Limit Issues

### Problem
WordPress admin pages return 100k-200k+ characters of HTML/accessibility tree data when using browser automation, exceeding the 30k character tool result limit.

### Solutions

**Option A: Use Screenshots Only**
- Take screenshots and manually identify element positions
- Slower but avoids token limits
- Good for visual tasks (logo updates, section headers)

**Option B: Direct Database/File Access**
- For meta descriptions: Edit WordPress database directly via SQL
- For content: Edit page HTML files directly
- Fastest approach but requires knowing exact field names

**Option C: Targeted JavaScript**
- Use minimal JavaScript that returns only simple strings
- Avoid triggering page reloads that return massive snapshots

**Option D: Manual Instructions**
- Provide step-by-step instructions for manual completion
- Most reliable for complex SEO/plugin interactions

---

## Task 2: Remove Vegan References from Meta Description

**Status**: In Progress
**Priority**: High
**Complexity**: Low
**Time Estimate**: 5 minutes (database) or 10 minutes (manual)

### Current State
- Homepage meta description contains "Vegan Store" references
- Managed by SiteSEO plugin
- Causing token limit issues when accessing via browser

### WordPress Installation Paths

**Remote Server (Vodien)**:
- Installation: `/home2/contactl/public_html/ah-ho-fruits/`
- Database Host: localhost
- Database Name: `contactl_wp123` (needs verification)
- cPanel URL: https://sh00017.vodien.com:2083/
- phpMyAdmin: https://sh00017.vodien.com:2083/cpsess0585210987/frontend/jupiter/sql/index.html

**Local Project**:
- Repository: `/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/`
- SQL Scripts: `/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/sql/` (to be created)

### Recommended Approach: Direct Database Edit

**SQL File Path**:
```
/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/sql/update-meta-description.sql
```

**SQL Query**:
```sql
-- Update homepage meta description
UPDATE wp_postmeta
SET meta_value = 'Fresh fruits delivered daily to your doorstep in Singapore. Premium fruit hampers, seasonal selections, and gift baskets with same-day delivery.'
WHERE meta_key = '_siteseo_titles_desc'
AND post_id = (SELECT ID FROM wp_posts WHERE post_name = 'home' AND post_type = 'page' LIMIT 1);

-- Verify the update
SELECT p.post_title, pm.meta_key, pm.meta_value
FROM wp_postmeta pm
JOIN wp_posts p ON pm.post_id = p.ID
WHERE pm.meta_key = '_siteseo_titles_desc'
AND p.post_name = 'home';
```

### Alternative: Manual Update via WordPress Admin

**Steps**:
1. Navigate to: https://fruits.heymag.app/wp-admin/edit.php?post_type=page
2. Find "Home — Front Page" in the list
3. Click to edit
4. Scroll to "SiteSEO" meta box (below content editor)
5. Click "Title" tab
6. Update "Meta Description" field to: "Fresh fruits delivered daily to your doorstep in Singapore. Premium fruit hampers, seasonal selections, and gift baskets with same-day delivery."
7. Click "Update" button

### Verification
- Check live site: https://fruits.heymag.app/
- View page source (Ctrl+U / Cmd+Option+U)
- Look for: `<meta name="description" content="Fresh fruits delivered...">`

---

## Task 3: Remove Vegan References from Section Headers

**Status**: Pending
**Priority**: High
**Complexity**: Medium
**Time Estimate**: 15-20 minutes

### Current Vegan References

**Section 1**: "buy the finest vegan drinks & beverages"
- **Location**: Mid-page, left column heading
- **New Text**: "Fresh Seasonal Fruits & Gift Hampers"
- **Element**: Heading level 3

**Section 2**: "vegan news & articles"
- **Location**: Lower page, section heading
- **New Text**: "Fruit News & Recipes"
- **Element**: Heading level 3

### Content Storage

**Database Location**:
- **Table**: `wp_posts`
- **Table**: `wp_postmeta`
- **Meta Keys**: `_fusion_builder_content` or `_avada_page_content`

**Find Content Query**:
```sql
-- Locate all vegan references in home page
SELECT post_id, meta_key, LEFT(meta_value, 200) as content_preview
FROM wp_postmeta
WHERE meta_value LIKE '%vegan%'
AND post_id IN (SELECT ID FROM wp_posts WHERE post_name = 'home' AND post_type = 'page');
```

### Recommended Approach: Avada Live Builder

**Editor URLs**:
- Live Builder: https://fruits.heymag.app/?fb-edit=1 (replace 1 with actual home page ID)
- Backend Builder: https://fruits.heymag.app/wp-admin/post.php?post=1&action=edit

**Steps**:
1. Navigate to home page editor
2. Click "Edit with Pagelayer" or "Live Builder" button
3. Locate section with "buy the finest vegan drinks & beverages"
4. Click to edit heading element
5. Change text to: "Fresh Seasonal Fruits & Gift Hampers"
6. Locate section with "vegan news & articles"
7. Click to edit heading element
8. Change text to: "Fruit News & Recipes"
9. Click "Save" or "Publish"
10. View live site to verify changes

### Alternative: Database Direct Edit

⚠️ **Warning**: Avada stores content in serialized format. Direct database editing is complex and risky.

**If needed, SQL approach**:
```sql
-- Backup first!
CREATE TABLE wp_postmeta_backup AS SELECT * FROM wp_postmeta WHERE post_id = <HOME_PAGE_ID>;

-- Then carefully replace text (serialized data requires exact byte counts)
UPDATE wp_postmeta
SET meta_value = REPLACE(meta_value, 'buy the finest vegan drinks & beverages', 'Fresh Seasonal Fruits & Gift Hampers')
WHERE post_id = <HOME_PAGE_ID>
AND meta_value LIKE '%vegan%';
```

---

## Task 4: Update Footer Logo Text

**Status**: Pending
**Priority**: Medium
**Complexity**: Low
**Time Estimate**: 10 minutes

### Current State
- Footer logo shows "Vegan Store Logo" as alt text and link title
- Located in footer newsletter signup section
- Managed by Avada theme options or footer widgets

### File Locations

**Theme Files**:
```
/home2/contactl/public_html/ah-ho-fruits/wp-content/themes/Avada/includes/class-avada-footer.php
/home2/contactl/public_html/ah-ho-fruits/wp-content/themes/Avada/templates/footer-content.php
```

**Database Storage**:
- **Table**: `wp_options`
- **Option Name**: `fusion_options` (Avada theme settings)
- **Specific Setting**: `footer_widgets` or `footer_logo_alt_text`

### Logo Details

**Current**:
- Alt Text: "Vegan Store Logo"
- Link Title: "Vegan Store Logo"
- Link URL: https://fruits.heymag.app/

**New**:
- Alt Text: "Ah Ho Fruit"
- Link Title: "Ah Ho Fruit"
- Link URL: https://fruits.heymag.app/ (unchanged)

### Recommended Approach: WordPress Customizer

**Steps**:
1. Navigate to: https://fruits.heymag.app/wp-admin/customize.php
2. Click "Footer" section
3. OR navigate to: https://fruits.heymag.app/wp-admin/themes.php?page=avada_options
4. Find "Footer Content" or "Footer Logo" settings
5. Update alt text field to: "Ah Ho Fruit"
6. Update link title to: "Ah Ho Fruit"
7. Click "Publish" to save changes

### Alternative: Widget Editor

**Steps**:
1. Navigate to: https://fruits.heymag.app/wp-admin/widgets.php
2. Find "Footer" widget area
3. Locate image/logo widget
4. Edit alt text and title attributes
5. Save widget

### Database Query (if needed)

```sql
-- Find footer logo configuration
SELECT option_name, LEFT(option_value, 500) as value_preview
FROM wp_options
WHERE option_name LIKE '%footer%'
AND option_value LIKE '%Vegan%';

-- Update will depend on serialized format in fusion_options
```

---

## Task 5: Create FRUITS10 Coupon Code

**Status**: Pending
**Priority**: Medium
**Complexity**: Low
**Time Estimate**: 5 minutes

### Coupon Configuration

**Code**: FRUITS10
**Type**: Percentage discount
**Amount**: 10%
**Description**: "Get 10% off your first order! Valid for new customers."

**Settings**:
- Allow free shipping: No
- Expiry date: None (or 1 year from now)
- Minimum spend: None (or $0)
- Maximum spend: None
- Individual use only: No
- Exclude sale items: No
- Products: All products
- Usage limit per coupon: Unlimited
- Usage limit per user: 1 (first order only if possible)

### Recommended Approach: WooCommerce Admin UI

**URL**: https://fruits.heymag.app/wp-admin/post-new.php?post_type=shop_coupon

**Steps**:
1. Navigate to WooCommerce → Coupons
2. Click "Add Coupon" button
3. Enter coupon code: `FRUITS10`
4. Set "Discount type": Percentage discount
5. Set "Coupon amount": `10`
6. Set "Coupon description": "Get 10% off your first order! Valid for new customers."
7. Leave expiry date blank (no expiration)
8. Under "Usage restriction":
   - Leave minimum/maximum spend blank
   - Check "Individual use only": No
9. Under "Usage limits":
   - Leave "Usage limit per coupon" blank (unlimited)
   - Set "Usage limit per user": `1`
10. Click "Publish" button

### Alternative: Database Direct Insert

**SQL File Path**:
```
/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/sql/create-fruits10-coupon.sql
```

**SQL Query**:
```sql
-- Step 1: Insert coupon post
INSERT INTO wp_posts (
    post_author,
    post_date,
    post_date_gmt,
    post_content,
    post_title,
    post_excerpt,
    post_status,
    comment_status,
    ping_status,
    post_name,
    post_modified,
    post_modified_gmt,
    post_type
) VALUES (
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Get 10% off your first order! Valid for new customers.',
    'FRUITS10',
    '',
    'publish',
    'closed',
    'closed',
    'fruits10',
    NOW(),
    UTC_TIMESTAMP(),
    'shop_coupon'
);

-- Step 2: Get the inserted post ID
SET @coupon_id = LAST_INSERT_ID();

-- Step 3: Add coupon meta data
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(@coupon_id, 'discount_type', 'percent'),
(@coupon_id, 'coupon_amount', '10'),
(@coupon_id, 'individual_use', 'no'),
(@coupon_id, 'product_ids', ''),
(@coupon_id, 'exclude_product_ids', ''),
(@coupon_id, 'usage_limit', ''),
(@coupon_id, 'usage_limit_per_user', '1'),
(@coupon_id, 'limit_usage_to_x_items', ''),
(@coupon_id, 'usage_count', '0'),
(@coupon_id, 'expiry_date', ''),
(@coupon_id, 'free_shipping', 'no'),
(@coupon_id, 'exclude_sale_items', 'no'),
(@coupon_id, 'minimum_amount', ''),
(@coupon_id, 'maximum_amount', ''),
(@coupon_id, 'customer_email', '');

-- Verify
SELECT p.post_title as coupon_code, pm.meta_key, pm.meta_value
FROM wp_posts p
JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'shop_coupon'
AND p.post_title = 'FRUITS10'
ORDER BY pm.meta_key;
```

### Verification

**Test the coupon**:
1. Open: https://fruits.heymag.app/cart/
2. Add a test product to cart
3. Enter coupon code: `FRUITS10`
4. Click "Apply coupon"
5. Verify 10% discount appears
6. Check that total is reduced by 10%

---

## Task 6: Add Sample Fruit Products

**Status**: Pending
**Priority**: Low
**Complexity**: High (requires product images)
**Time Estimate**: 30-40 minutes (UI) or 5 minutes (CSV import)

### Sample Products to Add

#### Product 1: Premium Mango Box
- **SKU**: MANGO-PREM-01
- **Name**: Premium Mango Box
- **Category**: Fruits > Tropical
- **Short Description**: "6 premium Alphonso mangoes"
- **Description**: "Experience the king of fruits with our hand-selected premium Alphonso mangoes from India. Each box contains 6 perfectly ripened mangoes, known for their rich, sweet flavor and smooth texture. Perfect for gifting or treating yourself to a tropical delight."
- **Regular Price**: SGD $45.00
- **Stock**: In stock
- **Image**: `/Users/lexnaweiming/Downloads/Ah Ho Fruit/product-images/mango-box.jpg` (to be provided)

#### Product 2: Dragon Fruit Trio
- **SKU**: DRAGON-TRIO-01
- **Name**: Dragon Fruit Trio
- **Category**: Fruits > Exotic
- **Short Description**: "3 red dragon fruits"
- **Description**: "Vibrant red dragon fruits packed with antioxidants and natural sweetness. Each trio contains 3 fresh dragon fruits, perfect for smoothies, salads, or enjoying fresh. Locally sourced for maximum freshness."
- **Regular Price**: SGD $28.00
- **Stock**: In stock
- **Image**: `/Users/lexnaweiming/Downloads/Ah Ho Fruit/product-images/dragon-fruit.jpg` (to be provided)

#### Product 3: Japanese Melon
- **SKU**: MELON-JP-01
- **Name**: Premium Japanese Melon
- **Category**: Fruits > Premium
- **Short Description**: "1 premium Yubari King melon"
- **Description**: "The ultimate luxury fruit - a premium Japanese Yubari King melon, carefully cultivated for perfect sweetness and texture. Each melon is individually selected and comes in an elegant gift box. The perfect gift for special occasions."
- **Regular Price**: SGD $88.00
- **Stock**: In stock
- **Image**: `/Users/lexnaweiming/Downloads/Ah Ho Fruit/product-images/japanese-melon.jpg` (to be provided)

#### Product 4: Berry Mix Box
- **SKU**: BERRY-MIX-01
- **Name**: Berry Mix Box
- **Category**: Fruits > Berries
- **Short Description**: "Strawberries, blueberries, raspberries"
- **Description**: "A delightful assortment of fresh berries including strawberries, blueberries, and raspberries. Perfect for breakfast, desserts, or healthy snacking. All berries are fresh and locally sourced when possible."
- **Regular Price**: SGD $35.00
- **Stock**: In stock
- **Image**: `/Users/lexnaweiming/Downloads/Ah Ho Fruit/product-images/berry-mix.jpg` (to be provided)

#### Product 5: Citrus Collection
- **SKU**: CITRUS-COL-01
- **Name**: Citrus Collection
- **Category**: Fruits > Citrus
- **Short Description**: "Oranges, grapefruits, mandarins"
- **Description**: "Start your day with vitamin C! Our citrus collection includes fresh oranges, grapefruits, and sweet mandarins. Perfect for juicing or eating fresh. Each box contains approximately 2kg of mixed citrus fruits."
- **Regular Price**: SGD $32.00
- **Stock**: In stock
- **Image**: `/Users/lexnaweiming/Downloads/Ah Ho Fruit/product-images/citrus-collection.jpg` (to be provided)

### File Paths

**CSV Import Template**:
```
/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/products/fruit-products-import.csv
```

**Product Images Directory** (local staging):
```
/Users/lexnaweiming/Downloads/Ah Ho Fruit/product-images/
├── mango-box.jpg
├── dragon-fruit.jpg
├── japanese-melon.jpg
├── berry-mix.jpg
└── citrus-collection.jpg
```

**Remote Upload Directory**:
```
/home2/contactl/public_html/ah-ho-fruits/wp-content/uploads/2026/01/
```

### Recommended Approach: CSV Import

**Import URL**: https://fruits.heymag.app/wp-admin/edit.php?post_type=product&page=product_importer

**CSV Template** (`fruit-products-import.csv`):
```csv
Type,SKU,Name,Published,Categories,Short description,Description,Regular price,Stock,Images
simple,MANGO-PREM-01,Premium Mango Box,1,"Fruits > Tropical","6 premium Alphonso mangoes","Experience the king of fruits with our hand-selected premium Alphonso mangoes from India. Each box contains 6 perfectly ripened mangoes, known for their rich, sweet flavor and smooth texture. Perfect for gifting or treating yourself to a tropical delight.",45,instock,https://fruits.heymag.app/wp-content/uploads/2026/01/mango-box.jpg
simple,DRAGON-TRIO-01,Dragon Fruit Trio,1,"Fruits > Exotic","3 red dragon fruits","Vibrant red dragon fruits packed with antioxidants and natural sweetness. Each trio contains 3 fresh dragon fruits, perfect for smoothies, salads, or enjoying fresh. Locally sourced for maximum freshness.",28,instock,https://fruits.heymag.app/wp-content/uploads/2026/01/dragon-fruit.jpg
simple,MELON-JP-01,Premium Japanese Melon,1,"Fruits > Premium","1 premium Yubari King melon","The ultimate luxury fruit - a premium Japanese Yubari King melon, carefully cultivated for perfect sweetness and texture. Each melon is individually selected and comes in an elegant gift box. The perfect gift for special occasions.",88,instock,https://fruits.heymag.app/wp-content/uploads/2026/01/japanese-melon.jpg
simple,BERRY-MIX-01,Berry Mix Box,1,"Fruits > Berries","Strawberries, blueberries, raspberries","A delightful assortment of fresh berries including strawberries, blueberries, and raspberries. Perfect for breakfast, desserts, or healthy snacking. All berries are fresh and locally sourced when possible.",35,instock,https://fruits.heymag.app/wp-content/uploads/2026/01/berry-mix.jpg
simple,CITRUS-COL-01,Citrus Collection,1,"Fruits > Citrus","Oranges, grapefruits, mandarins","Start your day with vitamin C! Our citrus collection includes fresh oranges, grapefruits, and sweet mandarins. Perfect for juicing or eating fresh. Each box contains approximately 2kg of mixed citrus fruits.",32,instock,https://fruits.heymag.app/wp-content/uploads/2026/01/citrus-collection.jpg
```

**Steps**:
1. **User Action Required**: Provide 5 product images
   - Save to: `/Users/lexnaweiming/Downloads/Ah Ho Fruit/product-images/`
   - Filenames: mango-box.jpg, dragon-fruit.jpg, japanese-melon.jpg, berry-mix.jpg, citrus-collection.jpg
   - Recommended size: 800x800px or larger
   - Format: JPG or PNG

2. **Upload images to WordPress**:
   - Navigate to: https://fruits.heymag.app/wp-admin/upload.php
   - Click "Add New" button
   - Upload all 5 product images
   - Note the URLs of uploaded images

3. **Create CSV file** with actual image URLs

4. **Import products**:
   - Navigate to: https://fruits.heymag.app/wp-admin/edit.php?post_type=product&page=product_importer
   - Click "Choose File" and select CSV
   - Click "Continue"
   - Map CSV columns to WooCommerce fields
   - Click "Run the importer"

### Alternative: Manual Product Creation

**URL**: https://fruits.heymag.app/wp-admin/post-new.php?post_type=product

**Steps for each product**:
1. Click "Products" → "Add New"
2. Enter product name
3. Set product type: Simple product
4. Add short description (excerpt)
5. Add full description (main content area)
6. Set regular price in "Product data" → "General" tab
7. Set SKU in "Product data" → "Inventory" tab
8. Set stock status: "In stock"
9. Add product image (featured image)
10. Add product to categories
11. Click "Publish"

### Database Structure

**Tables**:
- `wp_posts` (post_type = 'product')
- `wp_postmeta` (product meta data)
- `wp_term_relationships` (product-category associations)
- `wp_terms` + `wp_term_taxonomy` (product categories)

**Key Meta Fields**:
- `_sku`: Product SKU
- `_regular_price`: Regular price
- `_sale_price`: Sale price (if any)
- `_stock_status`: instock, outofstock, onbackorder
- `_thumbnail_id`: Featured image post ID
- `_product_image_gallery`: Additional images

---

## File Structure Summary

```
Local Project Structure:
└── /Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/
    ├── .github/
    │   └── workflows/
    │       └── deploy.yml (FTP deployment via GitHub Actions)
    ├── wp-content/
    │   ├── themes/Avada/
    │   ├── plugins/
    │   │   ├── woocommerce/
    │   │   └── siteseo/
    │   └── uploads/
    ├── sql/ (to be created)
    │   ├── update-meta-description.sql
    │   ├── create-fruits10-coupon.sql
    │   └── backup-before-changes.sql
    ├── products/ (to be created)
    │   └── fruit-products-import.csv
    └── IMPLEMENTATION_PLAN.md (this file)

Product Images Directory:
└── /Users/lexnaweiming/Downloads/Ah Ho Fruit/product-images/
    ├── mango-box.jpg
    ├── dragon-fruit.jpg
    ├── japanese-melon.jpg
    ├── berry-mix.jpg
    └── citrus-collection.jpg

Remote WordPress (Vodien):
└── /home2/contactl/public_html/ah-ho-fruits/
    ├── wp-config.php (database credentials)
    ├── wp-content/
    │   ├── themes/Avada/
    │   │   ├── includes/class-avada-footer.php
    │   │   └── templates/footer-content.php
    │   ├── plugins/
    │   │   ├── woocommerce/
    │   │   └── siteseo/
    │   └── uploads/
    │       └── 2026/
    │           └── 01/ (product images uploaded here)
    ├── .htaccess
    └── index.php

Database Tables:
└── MySQL Server (contactl_wp123)
    ├── wp_posts (pages, products, coupons)
    ├── wp_postmeta (page meta, product data, coupon settings)
    ├── wp_options (site settings, theme options)
    ├── wp_term_relationships (product categories)
    ├── wp_terms (category names)
    └── wp_term_taxonomy (category hierarchy)
```

---

## Execution Timeline

### Phase 1: Quick Database Edits (10 minutes)
**Tasks**: Meta description + FRUITS10 coupon (if using SQL)

**Steps**:
1. Create `/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/sql/` directory
2. Create SQL files for meta description and coupon
3. Log into phpMyAdmin: https://sh00017.vodien.com:2083/.../sql/
4. Execute SQL queries
5. Verify changes on live site

**Tools Needed**: phpMyAdmin access via cPanel

---

### Phase 2: WooCommerce Coupon (5 minutes)
**Tasks**: FRUITS10 coupon (if using UI instead of SQL)

**Steps**:
1. Navigate to: https://fruits.heymag.app/wp-admin/post-new.php?post_type=shop_coupon
2. Fill in coupon details
3. Publish
4. Test on cart page

**Tools Needed**: WordPress admin access

---

### Phase 3: Content Updates (20-30 minutes)
**Tasks**: Section headers + footer logo

**Steps**:
1. Edit section headers via Avada Live Builder
2. Update footer logo text via Customizer or Widgets
3. Verify changes on live site

**Tools Needed**: WordPress admin + Live Builder access
**Challenge**: May encounter browser token limits (use screenshots)

---

### Phase 4: Product Creation (30-40 minutes + image prep time)
**Tasks**: Add 5 sample fruit products

**Prerequisites**:
- User must provide 5 product images
- Images saved to: `/Users/lexnaweiming/Downloads/Ah Ho Fruit/product-images/`

**Steps**:
1. Upload product images to WordPress media library
2. Create CSV import file with actual image URLs
3. Import products via WooCommerce importer
4. Verify products appear on shop page

**Alternative**: Create products manually (slower but more control)

**Tools Needed**: Product images from user, WordPress admin access

---

## Success Criteria

### Task Completion Checklist

- [ ] **Task 2**: Homepage meta description updated, no "vegan" references
  - Verify: View page source at https://fruits.heymag.app/
  - Check: `<meta name="description" content="Fresh fruits delivered...">`

- [ ] **Task 3**: Section headers updated, no "vegan" references
  - Verify: View https://fruits.heymag.app/ and scroll through page
  - Check: "Fresh Seasonal Fruits & Gift Hampers" visible
  - Check: "Fruit News & Recipes" visible

- [ ] **Task 4**: Footer logo text changed to "Ah Ho Fruit"
  - Verify: Scroll to footer at https://fruits.heymag.app/
  - Check: Hover over logo shows "Ah Ho Fruit" tooltip
  - Check: Screen reader reads "Ah Ho Fruit"

- [ ] **Task 5**: FRUITS10 coupon code created and functional
  - Verify: Add product to cart
  - Check: Enter "FRUITS10" in coupon field
  - Check: 10% discount applies successfully

- [ ] **Task 6**: 5 sample fruit products added to shop
  - Verify: Navigate to https://fruits.heymag.app/shop/
  - Check: All 5 products visible with images
  - Check: Prices display correctly
  - Check: Products can be added to cart

---

## Rollback Plan

### If Something Goes Wrong

**Database Changes**:
```sql
-- Backup current state before any changes
CREATE TABLE wp_posts_backup_20260122 AS SELECT * FROM wp_posts;
CREATE TABLE wp_postmeta_backup_20260122 AS SELECT * FROM wp_postmeta;
CREATE TABLE wp_options_backup_20260122 AS SELECT * FROM wp_options;

-- Restore if needed
DROP TABLE wp_posts;
RENAME TABLE wp_posts_backup_20260122 TO wp_posts;
```

**File Changes**:
- GitHub repository has version history
- Can revert any code changes via: `git revert <commit-hash>`
- FTP deployment can be rolled back by pushing previous commit

**WordPress Backups**:
- cPanel backup available at: https://sh00017.vodien.com:2083/frontend/jupiter/backup/
- Download full backup before major changes
- Restore via cPanel if needed

---

## Contact Information

**Deployment Method**: FTP via GitHub Actions
**GitHub Repo**: `/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/`
**Live Site**: https://fruits.heymag.app/
**Admin**: https://fruits.heymag.app/wp-admin/
**cPanel**: https://sh00017.vodien.com:2083/
**DNS**: Cloudflare (fruits.heymag.app points to Vodien server)

---

## Notes

- WordPress version: 6.9
- Theme: Avada v7.14.2
- WooCommerce: Active (version TBD)
- SiteSEO plugin: Active
- Server: Vodien (sh00017.vodien.com)
- PHP version: Check via cPanel
- MySQL version: Check via phpMyAdmin

**Last Updated**: January 22, 2026
