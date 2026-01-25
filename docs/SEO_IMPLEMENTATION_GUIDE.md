# Ah Ho Fruit - SEO Implementation Guide

**Version:** 1.0
**Last Updated:** 25 January 2026
**Site:** https://ahhofruit.com
**Target Launch:** 31 January 2026

---

## Table of Contents

1. [Day 1: Foundation Setup](#day-1-foundation-setup)
2. [Day 2: Plugin Configuration](#day-2-plugin-configuration)
3. [Day 3-4: Homepage & Category Optimization](#day-3-4-homepage--category-optimization)
4. [Day 5-9: Product Page Optimization](#day-5-9-product-page-optimization)
5. [Day 10-11: Performance Optimization](#day-10-11-performance-optimization)
6. [Day 12-13: Technical SEO & Analytics](#day-12-13-technical-seo--analytics)
7. [Day 14: Pre-Launch Audit](#day-14-pre-launch-audit)
8. [Launch Day: Go Live](#launch-day-go-live)

---

## Day 1: Foundation Setup

### Task 1.1: Install Rank Math SEO Plugin (30 minutes)

**Step-by-Step:**

1. **Access WordPress Admin:**
   ```
   URL: https://ahhofruit.com/wp-admin/
   Login with admin credentials
   ```

2. **Navigate to Plugins:**
   ```
   WordPress Admin > Plugins > Add New
   ```

3. **Search and Install:**
   ```
   Search box: "Rank Math"
   Find: "Rank Math SEO" by Rank Math
   Click: "Install Now"
   Wait for installation to complete
   Click: "Activate"
   ```

4. **Launch Setup Wizard:**
   ```
   You'll be redirected to Rank Math Setup Wizard
   Click: "Start Wizard"
   ```

5. **Connect Your Rank Math Account (Optional):**
   ```
   Option 1: "Connect with Google" (recommended)
   Option 2: "Skip" (can do later)
   Click: "Next"
   ```

6. **Setup Mode:**
   ```
   Select: "Advanced" mode
   Why: Gives full control over all settings
   Click: "Next"
   ```

7. **Import Settings:**
   ```
   Select: "Start from Scratch"
   (No previous SEO plugin data to import)
   Click: "Next"
   ```

8. **Site Type:**
   ```
   Select: "Online Shop / eCommerce"
   Click: "Next"
   ```

9. **Logo & Social:**
   ```
   Upload Logo: [Upload Ah Ho Fruit logo]
   Default Social Share Image: [Upload default share image 1200x630px]
   Click: "Next"
   ```

10. **Social Profiles:**
    ```
    Facebook: https://facebook.com/ahhofruits (if applicable)
    Instagram: https://instagram.com/ahhofruits (if applicable)
    Twitter: (leave blank if not used)
    LinkedIn: (leave blank if not used)
    Click: "Next"
    ```

11. **Connect Google Services:**
    ```
    ☑ Connect Google Search Console
    ☑ Connect Google Analytics

    Click: "Connect with Google"
    Authorize Rank Math to access your Google account
    Select the correct Search Console property
    Select the correct Analytics property
    Click: "Next"
    ```

12. **Optimization Settings:**
    ```
    ☑ Enable Link Counter (recommended)
    ☑ Redirect Attachments (recommended)
    ☑ Strip Category Base (recommended)
    ☑ Open External Links in New Tab (optional)
    ☑ Nofollow External Links (optional - be cautious)

    Click: "Next"
    ```

13. **Sitemap Settings:**
    ```
    ☑ Include Posts in Sitemap
    ☑ Include Pages in Sitemap
    ☑ Include Products in Sitemap
    ☑ Include Product Categories in Sitemap
    ☐ Include Product Tags (only if >100 products)

    Click: "Next"
    ```

14. **Advanced Configuration:**
    ```
    WooCommerce:
    ☑ Remove Base (/product/)
    ☑ Remove Category Base (/product-category/)
    ☑ Remove Generator Tag

    Click: "Next"
    ```

15. **SEO Tweaks:**
    ```
    ☑ Auto-update Sitemap
    ☑ Add Alt Attributes to Images (recommended)
    ☑ Open External Links in New Tab/Window (optional)

    Click: "Next"
    ```

16. **Role Manager:**
    ```
    Leave default settings (only Administrators can access Rank Math)
    Click: "Next"
    ```

17. **404 Monitor & Redirections:**
    ```
    ☑ Enable 404 Monitor
    ☑ Enable Redirections

    Click: "Next"
    ```

18. **Setup Complete:**
    ```
    Click: "Return to Dashboard"
    ```

**Verification:**
```
✓ Rank Math icon appears in admin sidebar
✓ SEO score widget appears on dashboard
✓ Edit any page and see Rank Math meta box at bottom
```

---

### Task 1.2: Configure Permalink Structure (15 minutes)

**Step-by-Step:**

1. **Access Permalinks:**
   ```
   WordPress Admin > Settings > Permalinks
   ```

2. **Select Permalink Structure:**
   ```
   Common Settings:
   ○ Plain (DO NOT use)
   ○ Day and name
   ○ Month and name
   ○ Numeric
   ● Custom Structure: /%postname%/

   Click the radio button for "Custom Structure"
   Enter: /%postname%/
   ```

3. **Configure WooCommerce Product URLs:**
   ```
   Optional section: "Product permalinks"

   Product base: (LEAVE BLANK to remove /product/)
   Product category base: (LEAVE BLANK to remove /product-category/)
   Product tag base: product-tag
   ```

4. **Save Changes:**
   ```
   Scroll to bottom
   Click: "Save Changes"

   You'll see: "Permalink structure updated."
   ```

5. **Verify URL Structure:**
   ```
   Go to: Products > All Products
   Click: "View" on any product

   Check URL format:
   ✓ Good: https://ahhofruit.com/omakase-box/
   ✗ Bad:  https://ahhofruit.com/product/omakase-box/
   ```

**Important Notes:**
- Changing permalinks AFTER launch can break existing links
- Do this before any external links are created
- Test all product URLs after saving

---

### Task 1.3: Create robots.txt File (20 minutes)

**Step-by-Step:**

1. **Create robots.txt File:**
   ```
   Location on server:
   /Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/robots.txt
   ```

2. **Add Content to robots.txt:**

   Create new file with this content:

   ```
   # Ah Ho Fruit - robots.txt
   # Updated: 25 January 2026

   User-agent: *
   Allow: /wp-content/uploads/
   Disallow: /wp-admin/
   Disallow: /readme.html
   Disallow: /cart/
   Disallow: /checkout/
   Disallow: /my-account/
   Disallow: /*?add-to-cart=*
   Disallow: /*?orderby=*
   Disallow: /*?filter*
   Disallow: /cgi-bin/

   # Sitemap
   Sitemap: https://ahhofruit.com/sitemap_index.xml
   ```

3. **Upload via FTP/cPanel:**

   **Option A: FTP Upload**
   ```
   Connect to: sh00017.vodien.com
   Navigate to: public_html/
   Upload: robots.txt
   Set permissions: 644
   ```

   **Option B: cPanel File Manager**
   ```
   Login: https://sh00017.vodien.com:2083/
   Username: contactl
   File Manager > public_html/
   Upload > Select robots.txt
   ```

   **Option C: WordPress Plugin (Rank Math)**
   ```
   Rank Math > General Settings > Edit robots.txt
   Paste content above
   Save Changes
   ```

4. **Verify robots.txt is Live:**
   ```
   Open browser: https://ahhofruit.com/robots.txt

   Expected: See your robots.txt content
   Error 404: File not uploaded correctly - retry
   ```

5. **Test in Google Search Console:**
   ```
   Google Search Console > Settings > Crawl > robots.txt Tester
   Enter URL: https://ahhofruit.com/robots.txt
   Click: "Test"

   Should show: "Allowed" for most pages
   Should show: "Blocked" for /cart/, /checkout/, /my-account/
   ```

**⚠️ CRITICAL PRE-LAUNCH WARNING:**

```
BEFORE LAUNCH, verify NO blocking rules exist:

✗ WRONG (will de-index entire site):
   User-agent: *
   Disallow: /

✓ CORRECT:
   User-agent: *
   Allow: /wp-content/uploads/
   Disallow: /wp-admin/
```

**Common Mistakes:**
- Leaving staging `Disallow: /` rule active
- Blocking `/wp-content/uploads/` (prevents image indexing)
- Typos in Sitemap URL
- Wrong file permissions (should be 644)

---

### Task 1.4: Enable HTTPS and Force SSL (15 minutes)

**Step-by-Step:**

1. **Verify SSL Certificate is Active:**
   ```
   Open browser: https://ahhofruit.com

   Check for:
   ✓ Padlock icon in address bar
   ✓ "Connection is secure" message
   ✗ Warning or "Not Secure" message (SSL not working)
   ```

2. **Update WordPress Site URLs:**
   ```
   WordPress Admin > Settings > General

   WordPress Address (URL): https://ahhofruit.com
   Site Address (URL): https://ahhofruit.com

   Make sure both start with "https://" not "http://"

   Scroll down
   Click: "Save Changes"

   You'll be logged out - log back in
   ```

3. **Force SSL Redirect in .htaccess:**

   Edit file: `/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/.htaccess`

   Add this code at the TOP of the file (before WordPress rules):

   ```apache
   # Force HTTPS
   <IfModule mod_rewrite.c>
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   </IfModule>
   ```

4. **Upload via FTP/cPanel:**
   ```
   Connect to Vodien FTP
   Navigate to: public_html/
   Download existing .htaccess (backup)
   Upload modified .htaccess
   Set permissions: 644
   ```

5. **Test HTTP to HTTPS Redirect:**
   ```
   Open browser in incognito mode
   Type: http://ahhofruit.com (without 's')
   Press Enter

   Expected: Automatically redirects to https://ahhofruit.com
   ```

6. **Check for Mixed Content Warnings:**
   ```
   Open any page: https://ahhofruit.com
   Right-click > Inspect > Console tab

   Look for warnings like:
   "Mixed Content: The page at 'https://...' was loaded over HTTPS,
   but requested an insecure image 'http://...'"

   If found:
   - Replace http:// with https:// in image URLs
   - Or use plugin: "Really Simple SSL"
   ```

7. **Install Really Simple SSL Plugin (if needed):**
   ```
   Plugins > Add New
   Search: "Really Simple SSL"
   Install and Activate

   It will automatically:
   ☑ Fix mixed content warnings
   ☑ Update internal links to HTTPS
   ☑ Set HTTPS redirects

   Click: "Activate SSL"
   ```

**Verification Checklist:**
```
✓ Browser shows padlock icon
✓ http:// redirects to https://
✓ No mixed content warnings in console
✓ All images loading over HTTPS
✓ Login/checkout pages show "Secure" indicator
```

---

## Day 2: Plugin Configuration

### Task 2.1: Configure Rank Math WooCommerce Settings (45 minutes)

**Step-by-Step:**

1. **Access WooCommerce Settings:**
   ```
   Rank Math > Titles & Meta > WooCommerce
   ```

2. **Product Settings:**
   ```
   Products Tab:

   Show SEO Title: ● Yes
   Show SEO Meta Description: ● Yes

   Robots Meta:
   ☑ Index: Yes
   ☑ Follow: Yes
   ☐ Advanced: (leave default)

   Snippet Preview:
   Title Format: %title% | %sitename%
   Description Format: %excerpt%

   Schema Type: Product (auto-selected)

   Price:
   ☑ Show price in snippet preview

   Global Brand:
   Brand: Ah Ho Fruit
   (This applies to all products unless overridden)
   ```

3. **Product Categories:**
   ```
   Product Categories Tab:

   Robots Meta:
   ☑ Index: Yes
   ☑ Follow: Yes

   Add Meta Description: ● Yes

   Snippet Preview:
   Title Format: %term% | %sitename%
   Description Format: %term_description%
   ```

4. **Product Tags:**
   ```
   Product Tags Tab:

   Robots Meta:
   ☐ Index: No (recommended for <100 products)
   ☑ Follow: Yes

   Reason: Tags often have thin content
   Only index if large product catalog (>100 items)
   ```

5. **Product Archive (Shop Page):**
   ```
   Product Archive Tab:

   Robots Meta:
   ☑ Index: Yes
   ☑ Follow: Yes

   Title Format: Shop | %sitename%
   Description: Browse our full range of premium fresh fruits
   ```

6. **Remove WooCommerce Generator:**
   ```
   Scroll to bottom

   ☑ Remove Generator Tag
   (Hides WooCommerce version for security)
   ```

7. **Save All Settings:**
   ```
   Scroll to bottom
   Click: "Save Changes"
   ```

---

### Task 2.2: Configure Sitemap Settings (30 minutes)

**Step-by-Step:**

1. **Access Sitemap Settings:**
   ```
   Rank Math > Sitemap Settings
   ```

2. **General Settings:**
   ```
   Sitemap Tab:

   ☑ Include Images
   ☑ Include Featured Image
   ☑ Exclude Posts
   ☑ Exclude 301 Redirected URLs

   Links Per Sitemap: 200 (default)
   ```

3. **Posts:**
   ```
   Posts Tab:

   If you have a blog:
   ☑ Include in Sitemap

   If no blog yet:
   ☐ Exclude from Sitemap
   ```

4. **Pages:**
   ```
   Pages Tab:

   ☑ Include in Sitemap

   Exclude These Pages:
   - Cart
   - Checkout
   - My Account
   - Sample Page
   - Privacy Policy (can include if you want)
   ```

5. **Products:**
   ```
   Products Tab:

   ☑ Include in Sitemap

   Priority: 0.8 (high priority)
   Frequency: Daily

   ☐ Exclude out-of-stock products (leave unchecked)
   ```

6. **Product Categories:**
   ```
   Product Categories Tab:

   ☑ Include in Sitemap

   Priority: 0.7
   Frequency: Weekly
   ```

7. **Product Tags:**
   ```
   Product Tags Tab:

   ☐ Include in Sitemap (recommended: exclude)

   Reason: Tags usually have thin content
   ```

8. **Save and Generate Sitemap:**
   ```
   Scroll to bottom
   Click: "Save Changes"

   Sitemap will auto-generate at:
   https://ahhofruit.com/sitemap_index.xml
   ```

9. **Verify Sitemap Works:**
   ```
   Open browser:
   https://ahhofruit.com/sitemap_index.xml

   Expected: XML sitemap with links to:
   - post-sitemap.xml (if blog enabled)
   - page-sitemap.xml
   - product-sitemap.xml
   - product_cat-sitemap.xml

   Click on product-sitemap.xml
   Should list all products
   ```

10. **Test Sitemap Validity:**
    ```
    Go to: https://www.xml-sitemaps.com/validate-xml-sitemap.html
    Enter: https://ahhofruit.com/sitemap_index.xml
    Click: "Validate"

    Expected: "Valid XML Sitemap"
    ```

---

### Task 2.3: Set Up Google Search Console (45 minutes)

**Step-by-Step:**

1. **Access Google Search Console:**
   ```
   URL: https://search.google.com/search-console
   Login with Google account
   ```

2. **Add Property:**
   ```
   Click: "Add Property" or "+ Add a property"

   Two options:
   ○ Domain property (requires DNS verification)
   ● URL prefix property (easier - use this)

   Enter: https://ahhofruit.com
   Click: "Continue"
   ```

3. **Verify Ownership:**

   **Method 1: HTML File Upload (Easiest)**
   ```
   Download verification file: google[xxxxx].html

   Upload to:
   /Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/google[xxxxx].html

   Via FTP: Upload to public_html/
   Or via cPanel: File Manager > Upload

   Test: https://ahhofruit.com/google[xxxxx].html
   Should show: verification code

   Back in Search Console:
   Click: "Verify"
   ```

   **Method 2: HTML Tag (Alternative)**
   ```
   Copy meta tag: <meta name="google-site-verification" content="xxx"/>

   Rank Math > General Settings > Webmaster Tools
   Google Search Console: Paste "xxx" code
   Save Changes

   Back in Search Console:
   Click: "Verify"
   ```

   **Method 3: Google Analytics (If Already Set Up)**
   ```
   Select: "Google Analytics"
   Click: "Verify"
   ```

4. **Success Message:**
   ```
   "Ownership verified"
   Click: "Go to property"
   ```

5. **Submit Sitemap:**
   ```
   Left sidebar > Sitemaps

   Add a new sitemap:
   Enter: sitemap_index.xml
   Click: "Submit"

   Status should change to:
   "Success" or "Couldn't fetch" (may take 24-48 hours)
   ```

6. **Set Up Email Notifications:**
   ```
   Settings (gear icon) > Users and permissions > Add user
   Enter your email
   Permission level: Owner or Full
   Click: "Add"
   ```

7. **Initial Data Collection:**
   ```
   Note: Search Console data takes 24-48 hours to populate

   Check back in 2 days to see:
   - Pages indexed
   - Search performance
   - Coverage issues
   - Mobile usability
   ```

**Bookmark for Daily Monitoring:**
```
https://search.google.com/search-console/performance?resource_id=https://ahhofruit.com
```

---

### Task 2.4: Install Google Analytics (30 minutes)

**Step-by-Step:**

1. **Create Google Analytics Account:**
   ```
   URL: https://analytics.google.com
   Login with Google account

   Click: "Start measuring"
   ```

2. **Set Up Account:**
   ```
   Account name: Ah Ho Fruit
   ☑ Share data (optional - recommended)
   Click: "Next"
   ```

3. **Set Up Property:**
   ```
   Property name: Ah Ho Fruit Website
   Reporting time zone: Singapore (GMT+08:00)
   Currency: Singapore Dollar (SGD)
   Click: "Next"
   ```

4. **Business Information:**
   ```
   Industry category: Food & Drink
   Business size: Small (1-10 employees)

   How do you intend to use Google Analytics:
   ☑ Measure customer engagement
   ☑ Analyze user behavior

   Click: "Create"
   ```

5. **Accept Terms:**
   ```
   Country: Singapore
   ☑ I accept the Terms of Service
   ☑ I accept the Data Processing Terms
   Click: "Accept"
   ```

6. **Set Up Data Stream:**
   ```
   Platform: ● Web

   Website URL: https://ahhofruit.com
   Stream name: Ah Ho Fruit Main Site

   ☑ Enhanced measurement (recommended)

   Click: "Create stream"
   ```

7. **Copy Measurement ID:**
   ```
   You'll see: Measurement ID: G-XXXXXXXXXX

   Copy this ID (click copy icon)
   ```

8. **Install in WordPress via Rank Math:**
   ```
   WordPress Admin > Rank Math > General Settings

   Tab: Analytics

   Google Analytics:
   Paste Measurement ID: G-XXXXXXXXXX

   Tracking Options:
   ☑ Enable Analytics
   ☑ Track logged-in users: No (recommended)
   ☑ Anonymize IP addresses (GDPR compliance)
   ☑ Enable Demographics and Interests Reports

   Events to Track:
   ☑ Outbound Links
   ☑ Downloads
   ☑ Affiliate Links
   ☑ Form Submissions

   eCommerce Tracking:
   ☑ Enable Enhanced Ecommerce (WooCommerce)

   Save Changes
   ```

9. **Verify Tracking is Working:**

   **Option A: Real-Time Reports**
   ```
   Google Analytics > Reports > Realtime

   Open your website in incognito window
   Browse 2-3 pages

   Check Analytics:
   Should show 1 active user (you)
   Should show pages you visited
   ```

   **Option B: Tag Assistant (Chrome Extension)**
   ```
   Install: "Tag Assistant Legacy (by Google)"
   Visit: https://ahhofruit.com
   Click extension icon

   Should show:
   ✓ Google Analytics: 1 tag found
   ✓ No errors or warnings
   ```

10. **Set Up Key Goals/Conversions:**
    ```
    Google Analytics > Admin > Data display > Events

    Mark as conversions:
    - purchase (WooCommerce auto-tracked)
    - begin_checkout
    - add_to_cart
    - view_item

    Toggle "Mark as conversion" for each event
    ```

**Bookmark for Daily Monitoring:**
```
https://analytics.google.com/analytics/web/#/p[YOUR-PROPERTY-ID]/
```

---

## Day 3-4: Homepage & Category Optimization

### Task 3.1: Optimize Homepage (2 hours)

**Step-by-Step:**

1. **Edit Homepage:**
   ```
   WordPress Admin > Pages > All Pages
   Find: "Home" or "Homepage"
   Click: "Edit"
   ```

2. **Scroll to Rank Math SEO Meta Box:**
   ```
   Located below content editor
   ```

3. **Set Focus Keyword:**
   ```
   Focus Keyword: fresh fruit delivery singapore

   Click: "+ Add Focus Keyword"
   Enter keyword
   Press Enter

   Rank Math shows SEO Score (aim for 80+/green)
   ```

4. **Edit SEO Title:**
   ```
   Click "Edit Snippet" button

   SEO Title (60 characters max):
   Fresh Fruits Delivered | Premium Fruit Delivery Singapore

   Character count: 59 ✓

   Preview shows how it appears in Google search
   ```

5. **Edit Meta Description:**
   ```
   Meta Description (160 characters max):
   Order fresh premium fruits delivered to your doorstep. Same-day delivery in Singapore. Corporate gifting, subscriptions & omakase boxes available.

   Character count: 158 ✓
   ```

6. **Permalink/URL Slug:**
   ```
   Should be: https://ahhofruit.com/

   If it's: https://ahhofruit.com/home/
   Change slug to: (blank) or set as front page

   Settings > Reading > Homepage displays: A static page
   Homepage: Select "Home"
   ```

7. **Schema Type:**
   ```
   Rank Math > Schema tab

   Schema Type: WebPage (default is fine)
   Or: Organization (if homepage is about company)
   ```

8. **Add Schema Markup for Organization:**
   ```
   Rank Math > Schema tab > Add Schema

   Schema Type: Organization

   Organization Info:
   Name: Ah Ho Fruit
   Logo: [Upload logo URL]
   Contact Type: Customer Service
   Telephone: +65 XXXX XXXX
   Email: info@ahhofruits.com
   Address: [Full Singapore address]
   ```

9. **Social Media Preview:**
   ```
   Rank Math > Social tab

   Facebook:
   Title: Fresh Fruits Delivered Singapore
   Description: Premium fruit delivery with same-day service
   Image: [Upload 1200x630px image]

   Twitter:
   Title: (same as Facebook)
   Description: (same as Facebook)
   Image: (same as Facebook)
   ```

10. **Check SEO Score:**
    ```
    Rank Math SEO Score widget shows:

    Target: 80+ (Green)
    Good: 60-79 (Orange)
    Needs Work: <60 (Red)

    Fix any red/orange items
    ```

11. **Common Issues and Fixes:**

    **Issue: "Focus keyword not in SEO title"**
    ```
    Fix: Include "fruit delivery singapore" in SEO title
    ```

    **Issue: "Content length too short"**
    ```
    Fix: Add 300-500 words of homepage content
    Include: About company, USPs, featured products, testimonials
    ```

    **Issue: "No internal links"**
    ```
    Fix: Link to top categories from homepage
    Example: "Browse our [Omakase Boxes] or [Corporate Gifts]"
    ```

    **Issue: "No outbound links"**
    ```
    Fix: Link to review sites, certifications, or suppliers (if applicable)
    ```

12. **Update Page:**
    ```
    Click: "Update" button (top right)

    Message: "Page updated."

    Click: "View Page" to see live version
    ```

13. **Verify on Live Site:**
    ```
    Open: https://ahhofruit.com

    Right-click > View Page Source
    Search for: <title>
    Should see: Fresh Fruits Delivered | Premium Fruit Delivery Singapore

    Search for: <meta name="description"
    Should see: Order fresh premium fruits...
    ```

---

### Task 3.2: Optimize Top 5 Product Categories (4 hours)

**Categories to Optimize:**
1. Omakase Boxes
2. Seasonal Fruits
3. Japanese Fruits
4. Corporate Gifts
5. Fruit Hampers

**Step-by-Step (Repeat for Each Category):**

**Example: Omakase Boxes Category**

1. **Edit Category:**
   ```
   WordPress Admin > Products > Categories
   Find: "Omakase Boxes"
   Hover: Click "Edit"
   ```

2. **Basic Category Info:**
   ```
   Name: Omakase Boxes
   Slug: omakase-boxes (auto-generated, don't change after creation)

   Parent Category: None (or select if subcategory)
   ```

3. **Category Description (Above Products):**
   ```
   Description field (short version - 50-100 words):

   Discover our signature omakase fruit boxes, where expert curation meets exceptional quality. Each box features a carefully selected variety of premium seasonal fruits sourced from Japan, Korea, and local farms at peak ripeness. Perfect for corporate gifting or personal enjoyment.

   This appears ABOVE product grid
   ```

4. **Thumbnail/Category Image:**
   ```
   Upload/Select image representing category
   Recommended: 500x500px or larger
   File name: omakase-fruit-boxes-category.jpg
   Alt text: Assorted premium fruits in omakase gift box
   ```

5. **Display Type:**
   ```
   Display type: Default (shows products)
   Or: Products (most common)
   ```

6. **Rank Math SEO Settings:**
   ```
   Scroll down to "Rank Math SEO" meta box
   ```

7. **Focus Keyword:**
   ```
   Focus Keyword: omakase fruit box singapore

   Secondary Keywords (optional):
   - curated fruit box
   - premium fruit gift box
   - japanese fruit box
   ```

8. **SEO Title:**
   ```
   SEO Title (60 characters):
   Premium Omakase Fruit Boxes | Curated Seasonal Selection

   Character count: 59 ✓

   Include primary keyword naturally
   ```

9. **Meta Description:**
   ```
   Meta Description (160 characters):
   Browse our collection of premium omakase fruit boxes. Expertly curated seasonal fruits from Japan, Korea & local farms. Same-day delivery in Singapore.

   Character count: 157 ✓

   Include:
   - Primary keyword
   - Call to action
   - Unique value proposition
   - Location (Singapore)
   ```

10. **Permalink:**
    ```
    Slug: omakase-boxes

    Final URL: https://ahhofruit.com/omakase-boxes/

    ✓ Clean, keyword-focused
    ✓ No special characters
    ✓ Hyphens between words
    ```

11. **Canonical URL:**
    ```
    Rank Math > Advanced tab

    Canonical URL: (leave blank - auto-generated)
    Should be: https://ahhofruit.com/omakase-boxes/
    ```

12. **Robots Meta:**
    ```
    Rank Math > Advanced tab

    Robots Meta:
    ☑ Index
    ☑ Follow
    ☐ noarchive
    ☐ nosnippet

    Always INDEX category pages
    ```

13. **Add Extended Category Description (Below Products):**

    Note: Most themes don't support this natively. Options:

    **Option A: Use Rank Math Content AI Tab**
    ```
    Rank Math > Content AI tab
    Click: "Write with AI"
    Prompt: "Write 200 words about premium omakase fruit boxes"
    Review and edit generated content
    ```

    **Option B: Manual HTML in Description Field**
    ```
    Switch to "Text" editor (not Visual)

    Add at end of description:

    <div class="category-extended-description">
    <h2>What Makes Our Omakase Boxes Special</h2>
    <p>Our omakase fruit boxes embody the Japanese philosophy of "I'll leave it up to you" – where our expert buyers curate the finest seasonal selection for you. Unlike fixed gift boxes, our omakase collection changes weekly based on peak fruit availability and quality.</p>

    <h3>Premium Sourcing</h3>
    <p>We source the finest fruits from renowned regions including Hokkaido melons, Amaou strawberries from Fukuoka, Korean shine muscat grapes, and the best local tropical fruits. Each piece is hand-selected for optimal ripeness and flavor.</p>

    <h3>Perfect For</h3>
    <ul>
    <li>Corporate gifts for valued clients and partners</li>
    <li>Special celebrations and milestones</li>
    <li>Thoughtful get-well gestures</li>
    <li>Personal indulgence in premium quality</li>
    </ul>

    <p>Available in multiple sizes to suit your needs. Same-day delivery available for orders placed before 12pm.</p>
    </div>

    Word count: ~180 words ✓
    ```

    **Option C: Use Category Description Plugin**
    ```
    Install: "WooCommerce Category Description"
    Allows extended descriptions below product grid
    ```

14. **Internal Links:**
    ```
    Add links to related categories and products:

    Related Collections:
    • <a href="/corporate-gifts/">Corporate Gift Hampers</a>
    • <a href="/japanese-fruits/">Premium Japanese Fruits</a>
    • <a href="/seasonal-fruits/">Fresh Seasonal Fruits</a>
    ```

15. **Check SEO Score:**
    ```
    Rank Math SEO Score widget
    Target: 80+ (Green)

    Fix any issues highlighted
    ```

16. **Common Issues for Categories:**

    **Issue: "No focus keyword in content"**
    ```
    Fix: Mention "omakase fruit box" in description 2-3 times
    ```

    **Issue: "Content too thin"**
    ```
    Fix: Add extended description (200-300 words)
    ```

    **Issue: "No internal links"**
    ```
    Fix: Link to related categories and top products
    ```

17. **Update Category:**
    ```
    Scroll to bottom
    Click: "Update"

    Message: "Category updated."
    ```

18. **Verify on Live Site:**
    ```
    Click: "View Category"
    Or visit: https://ahhofruit.com/omakase-boxes/

    Check:
    ✓ SEO title appears in browser tab
    ✓ Description appears on page
    ✓ Products display correctly
    ✓ Breadcrumbs show: Home > Omakase Boxes
    ```

19. **Test Schema Markup:**
    ```
    Google Rich Results Test:
    https://search.google.com/test/rich-results

    Enter: https://ahhofruit.com/omakase-boxes/
    Click: "Test URL"

    Expected: BreadcrumbList schema detected
    ```

**Repeat Steps 1-19 for Remaining Categories:**

2. **Seasonal Fruits**
   - Focus Keyword: seasonal fruits singapore
   - Title: Fresh Seasonal Fruits | Farm-Fresh Daily Delivery
   - Description: Discover the freshest seasonal fruits delivered daily...

3. **Japanese Fruits**
   - Focus Keyword: japanese fruits singapore
   - Title: Premium Japanese Fruits | Imported Direct from Japan
   - Description: Authentic Japanese fruits including Hokkaido melons...

4. **Corporate Gifts**
   - Focus Keyword: corporate fruit gifts singapore
   - Title: Corporate Fruit Gifts | Premium Business Hampers Singapore
   - Description: Impress clients with premium fruit gift hampers...

5. **Fruit Hampers**
   - Focus Keyword: fruit hampers singapore
   - Title: Luxury Fruit Hampers | Premium Gift Baskets Singapore
   - Description: Beautifully packaged fruit hampers for every occasion...

---

## Day 5-9: Product Page Optimization

### Task 4.1: Create Product Optimization Template (1 hour)

**Create Excel Template for Bulk Product Entry:**

File: `/Users/lexnaweiming/Downloads/Ah Ho Fruit/product-seo-template.xlsx`

**Columns:**

| Column | Description | Example |
|--------|-------------|---------|
| Product Name | WooCommerce product name | Premium Omakase Fruit Box |
| SKU | Unique identifier | OMAKASE-PREM-001 |
| Focus Keyword | Primary SEO keyword | omakase fruit box |
| SEO Title | 60 chars max | Premium Omakase Fruit Box - Seasonal Selection |
| Meta Description | 160 chars max | Curated selection of premium seasonal fruits... |
| Short Description | 50-100 words | Experience the finest selection... |
| Long Description | 200+ words | Full product description with benefits |
| Image 1 Filename | Descriptive filename | omakase-box-premium-seasonal.jpg |
| Image 1 Alt Text | Image description | Premium omakase fruit box with strawberries |
| Image 2 Filename | Second image | omakase-box-unboxing.jpg |
| Image 2 Alt Text | Alt text | Unboxing premium fruit omakase box |
| Category | Primary category | Omakase Boxes |
| Tags | Product tags | gift box, premium, seasonal |
| Price | Regular price | 88.00 |
| Sale Price | Sale price (if any) | 78.00 |

---

### Task 4.2: Optimize Individual Products (8 hours for 50 products)

**Example: Premium Omakase Fruit Box**

**Step-by-Step:**

1. **Edit Product:**
   ```
   WordPress Admin > Products > All Products
   Click: "Edit" on product
   ```

2. **Product Name:**
   ```
   Product name: Premium Omakase Fruit Box

   Tips:
   ✓ Descriptive and specific
   ✓ Include key features
   ✓ Keep under 70 characters

   ✗ Avoid: "Box 1" or "OMAKASE-001"
   ```

3. **Permalink/URL Slug:**
   ```
   Below product name

   Slug: premium-omakase-fruit-box

   ✓ Lowercase
   ✓ Hyphens between words
   ✓ Include primary keyword

   Final URL: https://ahhofruit.com/premium-omakase-fruit-box/
   ```

4. **Product Short Description:**
   ```
   Location: Below product name, "Short description" field

   Length: 50-100 words

   Example:
   Experience the finest selection of seasonal fruits handpicked by our expert buyers. Our Premium Omakase Box features 8-10 varieties of premium fruits sourced from Japan, Korea, and local farms at peak ripeness. Perfect for corporate gifts, special occasions, or personal indulgence. Same-day delivery available.

   Word count: 51 ✓

   This appears:
   - On product page (above "Add to Cart")
   - In product archives (category pages)
   ```

5. **Product Long Description:**
   ```
   Location: Main content editor

   Length: 200+ words

   Structure:
   [Opening Hook - 2-3 sentences]
   [Key Benefits - Bullet points]
   [Detailed Description - 150 words]
   [Specifications]
   [What's Included]
   [Delivery Information]
   ```

   **Example Long Description:**

   ```
   Discover the art of Japanese omakase with our Premium Fruit Box – where "I'll leave it up to you" meets exceptional curation. Each week, our expert buyers select 8-10 varieties of premium seasonal fruits at their absolute peak of ripeness and flavor.

   WHY CHOOSE OUR OMAKASE BOX

   • Expert Curation: Hand-selected by fruit specialists with over 20 years of experience
   • Premium Sourcing: Imported from renowned Japanese and Korean farms, plus the finest local tropical fruits
   • Peak Freshness: Only fruits at optimal ripeness make it into your box
   • Beautiful Presentation: Elegantly packaged in our signature gift box
   • Same-Day Delivery: Order before 12pm for delivery today

   WHAT MAKES IT SPECIAL

   Unlike fixed fruit boxes, our omakase selection changes weekly based on seasonal availability and quality. One week might feature Hokkaido crown melons and Amaou strawberries from Fukuoka; the next could showcase Korean shine muscat grapes and Japanese white strawberries. This ensures you always receive the absolute best fruits available.

   Each fruit is hand-selected for:
   - Optimal ripeness and sweetness
   - Perfect texture and juiciness
   - Visual appeal and presentation
   - Seasonal peak availability

   PERFECT FOR

   • Corporate gifting for valued clients and partners
   • Special celebrations (birthdays, anniversaries, congratulations)
   • Thoughtful get-well gestures
   • Appreciation gifts for family and friends
   • Personal luxury and indulgence

   BOX SPECIFICATIONS

   • Size: Large (3.5-4.5kg)
   • Serves: 4-6 people
   • Varieties: 8-10 different premium fruits
   • Packaging: Signature Ah Ho Fruit gift box with ribbon
   • Shelf Life: Best consumed within 3-5 days

   DELIVERY INFORMATION

   • Same-day delivery available for orders before 12pm
   • Island-wide delivery across Singapore
   • Carefully packaged to ensure freshness
   • Includes care instructions and fruit information card

   Order now and experience why our Omakase Box is Singapore's most sought-after premium fruit gift.

   Word count: 328 ✓
   Keywords used:
   - "omakase fruit box" (2 times)
   - "premium" (6 times)
   - "seasonal" (3 times)
   - "Singapore" (2 times)
   Keyword density: 1.2% ✓
   ```

6. **Product Data - General Tab:**
   ```
   Regular price: 88.00
   Sale price: (if on sale) 78.00

   Tax status: Taxable
   Tax class: Standard (or GST rate)
   ```

7. **Product Data - Inventory Tab:**
   ```
   SKU: OMAKASE-PREM-001

   ✓ Must be unique
   ✓ Use consistent format
   ✓ Include category prefix

   Stock Management:
   ☑ Manage stock?
   Stock quantity: 50
   Low stock threshold: 5

   Stock status: In stock
   Sold individually: ☐ (allow multiple)
   ```

8. **Product Data - Shipping Tab:**
   ```
   Weight (kg): 4.0
   Dimensions (L x W x H cm): 35 x 25 x 15
   Shipping class: (default)
   ```

9. **Product Data - Attributes Tab:**
   ```
   Add custom attributes for filters and schema:

   Attribute: Brand
   Value: Ah Ho Fruit
   ☑ Visible on product page

   Attribute: Origin
   Value: Japan, Korea, Singapore
   ☑ Visible on product page

   Attribute: Serves
   Value: 4-6 people
   ☑ Visible on product page

   These appear:
   - On product page as additional information
   - In product schema markup
   - As filters (if enabled)
   ```

10. **Product Categories:**
    ```
    Select primary category:
    ☑ Omakase Boxes

    Additional categories (optional):
    ☑ Corporate Gifts
    ☑ Best Sellers

    Note: Choose ONE primary category for canonical URL
    ```

11. **Product Tags:**
    ```
    Add relevant tags (5-7 max):
    - gift box
    - premium
    - seasonal
    - omakase
    - japanese fruits
    - corporate gifts

    Don't overdo tags - keep focused
    ```

12. **Product Images:**

    **Upload Main Product Image:**
    ```
    Click: "Set product image"

    Image requirements:
    - Size: Minimum 800x800px (1200x1200px ideal)
    - Format: JPEG or PNG (convert to WebP via Imagify)
    - File size: <200KB after compression
    - Filename: premium-omakase-fruit-box-main.jpg

    Upload image

    Alt text: Premium omakase fruit box with assorted seasonal fruits
    Title: Premium Omakase Fruit Box
    Caption: (optional)

    Click: "Set product image"
    ```

    **Add Product Gallery Images:**
    ```
    Click: "Add product gallery images"

    Upload 3-5 images:

    Image 2: Close-up of fruits
    Filename: omakase-box-fruits-closeup.jpg
    Alt text: Close up of premium fruits in omakase box

    Image 3: Unboxing/Packaging
    Filename: omakase-box-packaging-gift.jpg
    Alt text: Gift packaged omakase fruit box with ribbon

    Image 4: Lifestyle shot
    Filename: omakase-box-table-setting.jpg
    Alt text: Omakase fruit box on dining table setting

    Image 5: Size comparison
    Filename: omakase-box-size-comparison.jpg
    Alt text: Premium omakase box size comparison with hand

    Click: "Add to gallery"
    ```

13. **Scroll to Rank Math SEO Meta Box:**

14. **Set Focus Keyword:**
    ```
    Focus Keyword: premium omakase fruit box

    Secondary keywords:
    - omakase fruit box singapore
    - curated fruit gift box

    Rank Math analyzes content and shows SEO score
    ```

15. **Edit SEO Title:**
    ```
    Click: "Edit Snippet"

    SEO Title (60 chars max):
    Premium Omakase Fruit Box - Seasonal Selection | Ah Ho Fruit

    Character count: 62 (slightly over, shorten if needed)

    Optimized:
    Premium Omakase Fruit Box - Seasonal Selection

    Character count: 50 ✓
    ```

16. **Edit Meta Description:**
    ```
    Meta Description (160 chars max):
    Curated selection of 8-10 premium seasonal fruits from Japan, Korea & local farms. Perfect for gifts or personal enjoyment. Same-day delivery available.

    Character count: 157 ✓

    Includes:
    ✓ Primary keyword (implied in "curated")
    ✓ Key features (8-10 fruits, origins)
    ✓ Benefit (perfect for gifts)
    ✓ Call to action (same-day delivery)
    ```

17. **Schema Settings:**
    ```
    Rank Math > Schema tab

    Schema Type: Product (auto-selected)

    Product Info:
    Brand: Ah Ho Fruit
    SKU: OMAKASE-PREM-001 (auto-filled from Inventory tab)
    Price: $88.00 (auto-filled from General tab)
    Currency: SGD
    Availability: In Stock (auto-filled)

    ☑ Include reviews in schema (if reviews enabled)

    Optional (if applicable):
    GTIN: (barcode - leave blank if not applicable)
    MPN: (manufacturer part number - leave blank)
    ```

18. **Social Media Preview:**
    ```
    Rank Math > Social tab

    Facebook/Open Graph:
    Title: Premium Omakase Fruit Box - Seasonal Selection
    Description: Curated selection of premium seasonal fruits...
    Image: [Select main product image]

    Twitter Card:
    Title: (same as Facebook)
    Description: (same as Facebook)
    Image: (same as Facebook)
    ```

19. **Advanced Settings:**
    ```
    Rank Math > Advanced tab

    Robots Meta:
    ☑ Index
    ☑ Follow
    ☐ noarchive
    ☐ nosnippet

    Canonical URL: (leave blank - auto-generated)
    Should be: https://ahhofruit.com/premium-omakase-fruit-box/

    Breadcrumb Title: (leave blank - uses product name)
    ```

20. **Check SEO Score:**
    ```
    Rank Math SEO Score widget

    Target: 80+ (Green) - Excellent
    60-79 (Orange) - Good
    <60 (Red) - Needs improvement

    Common issues and fixes:

    ❌ "Focus keyword not found in SEO title"
    Fix: Include "omakase fruit box" in title

    ❌ "Focus keyword not found in content"
    Fix: Use keyword 2-3 times in description (aim for 1-1.5% density)

    ❌ "Content length too short"
    Fix: Add more details (target 200+ words)

    ❌ "No internal links"
    Fix: Link to related products or categories
    Example: "Also available: [Corporate Omakase Box] or browse all [Omakase Boxes]"

    ❌ "No external links"
    Fix: Link to fruit origin info, certifications, or reviews (if applicable)

    ❌ "No images with focus keyword in alt text"
    Fix: Include "omakase fruit box" in at least one image alt text
    ```

21. **Add Internal Links in Description:**
    ```
    Edit long description, add links:

    Example additions:
    "Looking for a smaller option? Try our <a href="/mini-omakase-box/">Mini Omakase Box</a> perfect for 2-3 people."

    "Browse our complete collection of <a href="/omakase-boxes/">Omakase Fruit Boxes</a> or explore <a href="/corporate-gifts/">Corporate Gift Options</a>."

    "Pair with our <a href="/japanese-strawberries/">Premium Japanese Strawberries</a> for the ultimate fruit experience."

    Limit: 2-4 internal links per product
    ```

22. **Save Draft / Publish:**
    ```
    Click: "Save Draft" (if not ready)
    Or: "Publish" (if ready to go live)

    Message: "Product published."
    ```

23. **Preview Product Page:**
    ```
    Click: "View Product"

    Check:
    ✓ All images load correctly
    ✓ Product title displays properly
    ✓ Short description appears above "Add to Cart"
    ✓ Long description formatted correctly
    ✓ Price displays
    ✓ "Add to Cart" button works
    ✓ Breadcrumbs show: Home > Omakase Boxes > Product Name
    ✓ Related products appear (if configured)
    ```

24. **Verify Schema Markup:**
    ```
    Google Rich Results Test:
    https://search.google.com/test/rich-results

    Enter product URL
    Click: "Test URL"

    Expected results:
    ✓ Product schema detected
    ✓ Price shown correctly
    ✓ Availability shown
    ✓ SKU included
    ✓ Brand: Ah Ho Fruit
    ✓ Image URLs present
    ✓ BreadcrumbList schema

    Should show: "Page is eligible for rich results"
    ```

25. **Test Page Speed:**
    ```
    Google PageSpeed Insights:
    https://pagespeed.web.dev/

    Enter product URL
    Analyze

    Target scores:
    Mobile: 70+ (acceptable), 90+ (excellent)
    Desktop: 90+ (good), 95+ (excellent)

    If scores low:
    - Optimize images (compress, use WebP)
    - Enable caching
    - Minimize CSS/JS
    ```

---

### Task 4.3: Bulk Optimize Products (Remaining 7 hours)

**Priority Order:**

**Day 5: Top 10 Best Sellers (2 hours)**
- Omakase boxes (3 variations)
- Japanese strawberries
- Korean melons
- Corporate gift hampers
- Seasonal fruit boxes

**Day 6: Next 15 Popular Products (3 hours)**
- Premium Japanese fruits
- Tropical fruits
- Fruit hampers
- Gift boxes

**Day 7-8: Next 25 Products (4 hours)**
- Standard category items
- New arrivals
- Seasonal specials

**Day 9: Final Review and Polish (2 hours)**
- Check all products have:
  - SEO titles
  - Meta descriptions
  - Optimized images
  - SKUs
  - Categories assigned
  - Prices set
  - Stock status configured

**Time-Saving Tips:**

1. **Use Product Duplicates:**
   ```
   Products > All Products
   Hover over similar product
   Click: "Duplicate"

   Then edit:
   - Product name
   - URL slug
   - Description (change details)
   - Images
   - SKU
   - Price

   SEO settings mostly remain similar
   ```

2. **Bulk Edit Basic Fields:**
   ```
   Products > All Products
   Select multiple products (checkboxes)
   Bulk Actions: Edit
   Apply

   Can bulk edit:
   - Status (Draft/Published)
   - Categories
   - Stock status
   - Visibility

   Cannot bulk edit SEO fields (must do individually)
   ```

3. **Use Template from Excel:**
   ```
   Fill out Excel template first
   Reference while creating products
   Copy/paste titles and descriptions
   Ensures consistency
   ```

4. **Focus on Quality over Quantity:**
   ```
   Day 5-6: Top 25 products - spend time getting perfect
   Day 7-8: Next 25 products - use templates, go faster
   Day 9: Review top 50, ensure minimum SEO requirements met

   Remaining products: Add basic SEO (title, description, images)
   Optimize fully over time based on popularity
   ```

---

## Day 10-11: Performance Optimization

### Task 5.1: Install and Configure Caching Plugin (2 hours)

**Recommended: LiteSpeed Cache (if on LiteSpeed hosting)**

**Step-by-Step:**

1. **Check Your Hosting Type:**
   ```
   WordPress Admin > Dashboard
   Look for: "Server Info" or contact Vodien support

   Ask: "Is my hosting using LiteSpeed, Apache, or Nginx?"

   If LiteSpeed: Install LiteSpeed Cache (best performance)
   If Apache/Nginx: Install WP Rocket or WP Super Cache
   ```

**Option A: LiteSpeed Cache (FREE)**

2. **Install LiteSpeed Cache:**
   ```
   Plugins > Add New
   Search: "LiteSpeed Cache"
   Find: "LiteSpeed Cache" by LiteSpeed Technologies
   Click: "Install Now"
   Activate
   ```

3. **Run Setup Wizard:**
   ```
   LiteSpeed Cache > General > Setup Wizard
   Click: "Enable LiteSpeed Cache"

   Or manually:
   LiteSpeed Cache > Dashboard
   Click: "Enable LiteSpeed Cache" toggle
   ```

4. **General Settings:**
   ```
   LiteSpeed Cache > General

   ☑ Enable LiteSpeed Cache: ON
   ☑ Auto Upgrade: ON (recommended)
   Guest Mode: ON
   Guest Optimization: ON
   ```

5. **Cache Settings:**
   ```
   LiteSpeed Cache > Cache > Cache

   Enable Cache: ☑ ON
   Cache Logged-in Users: ☐ OFF (recommended for WooCommerce)
   Cache Commenters: ☐ OFF
   Cache REST API: ☐ OFF
   Cache Login Page: ☐ OFF
   Cache Mobile: ☑ ON
   Cache Mobile Separate: ☐ OFF (unless different mobile design)

   TTL:
   Default Public Cache (seconds): 604800 (7 days)
   Default Private Cache (seconds): 1800 (30 minutes)
   Default Front Page (seconds): 604800 (7 days)
   ```

6. **WooCommerce-Specific Exclusions:**
   ```
   LiteSpeed Cache > Cache > Excludes

   Do Not Cache URIs (one per line):
   /cart/
   /checkout/
   /my-account/
   /addons/
   /add-to-cart/

   Do Not Cache Query Strings:
   add-to-cart
   remove_item
   edd_action
   nocache

   Do Not Cache Categories:
   (leave blank unless specific needs)

   Do Not Cache Tags:
   (leave blank)

   Do Not Cache Cookies:
   woocommerce_items_in_cart
   woocommerce_cart_hash
   wp_woocommerce_session_
   wordpress_logged_in
   comment_author
   ```

7. **Object Cache (Advanced - Optional):**
   ```
   LiteSpeed Cache > Cache > Object

   ☑ Object Cache: ON (if server supports Redis/Memcached)

   Method: (auto-detected)

   Note: Check with Vodien if Redis/Memcached available
   Significantly improves database query performance
   ```

8. **Browser Cache:**
   ```
   LiteSpeed Cache > Cache > Browser

   ☑ Browser Cache: ON
   TTL: 31557600 (1 year for static assets)
   ```

9. **CDN Setup (Optional):**
   ```
   LiteSpeed Cache > CDN > Settings

   If using Cloudflare or other CDN:
   ☑ Enable CDN
   CDN URL: https://cdn.ahhofruits.com (if applicable)

   Or use QUIC.cloud CDN (free with LiteSpeed Cache):
   Click: "Generate Domain Key"
   ☑ QUIC.cloud CDN
   ```

10. **Image Optimization:**
    ```
    LiteSpeed Cache > Image Optimization

    ☑ Auto Request Cron: ON
    ☑ Optimize Original Images: ON
    ☑ Remove Original Backups: OFF (keep backups)
    ☑ Optimize Losslessly: ON (or OFF for more compression)

    WebP:
    ☑ WebP Replacement: ON
    ☑ WebP For Extra Srcset: ON

    Lazy Load:
    ☑ Lazy Load Images: ON
    ☑ Lazy Load Iframes: ON

    Excludes:
    nolazy
    skip-lazy
    ```

11. **Page Optimization:**
    ```
    LiteSpeed Cache > Page Optimization > CSS

    ☑ CSS Minify: ON
    ☑ CSS Combine: ON (test - may cause styling issues)
    ☑ Generate Critical CSS: ON
    ☑ Load CSS Asynchronously: ON

    LiteSpeed Cache > Page Optimization > JS

    ☑ JS Minify: ON
    ☑ JS Combine: OFF (often causes conflicts in WooCommerce)
    ☑ Load JS Deferred: ON

    If JS Combine causes issues (broken cart, checkout):
    ☐ Turn OFF JS Combine
    ☐ Clear cache
    ☐ Test again
    ```

12. **Test After Configuration:**
    ```
    CRITICAL: Test these pages after enabling caching:

    ✓ Homepage loads correctly
    ✓ Product pages show correct prices
    ✓ Add to cart works
    ✓ Cart updates quantities
    ✓ Checkout process completes
    ✓ User login/logout works
    ✓ "My Account" shows user-specific data

    If ANY issues:
    1. LiteSpeed Cache > Purge > Purge All
    2. Disable problematic settings
    3. Test again
    ```

13. **Benchmark Performance:**
    ```
    Before caching:
    1. Clear all caches
    2. Test: https://pagespeed.web.dev/
    3. Note scores

    After caching:
    1. Load homepage 2-3 times (to populate cache)
    2. Test: https://pagespeed.web.dev/
    3. Compare scores

    Expected improvement:
    Load time: 30-50% faster
    PageSpeed score: +10-20 points
    ```

**Option B: WP Rocket (PAID - $59/year)**

*(If not on LiteSpeed hosting)*

2. **Purchase and Download:**
   ```
   Visit: https://wp-rocket.me/
   Purchase: $59/year (1 site)
   Download ZIP file
   ```

3. **Install via Upload:**
   ```
   Plugins > Add New > Upload Plugin
   Choose ZIP file
   Install and Activate
   ```

4. **Initial Setup Wizard:**
   ```
   WP Rocket dashboard appears
   Click: "I want to optimize my site"

   Automatically enables:
   ☑ Page caching
   ☑ Cache preloading
   ☑ GZIP compression
   ☑ Browser caching
   ```

5. **Cache Settings:**
   ```
   WP Rocket > Cache

   Enable caching for:
   ☑ Mobile devices
   ☐ Separate cache for mobile (unless different design)
   ☑ Logged-in users: OFF (WooCommerce)

   Cache Lifespan: 10 hours (default)
   ```

6. **WooCommerce Settings:**
   ```
   WP Rocket automatically detects WooCommerce

   Auto-excludes from caching:
   - /cart/
   - /checkout/
   - /my-account/
   - Cart/checkout cookies

   No manual configuration needed
   ```

7. **File Optimization:**
   ```
   WP Rocket > File Optimization

   CSS:
   ☑ Minify CSS files
   ☑ Combine CSS files
   ☑ Optimize CSS delivery

   JavaScript:
   ☑ Minify JavaScript files
   ☐ Combine JavaScript (OFF - causes WooCommerce issues)
   ☑ Load JavaScript deferred
   ☑ Delay JavaScript execution: 0 seconds
   ```

8. **Media:**
   ```
   WP Rocket > Media

   LazyLoad:
   ☑ Enable for images
   ☑ Enable for iframes and videos
   ☑ Replace YouTube iframe with preview image

   WebP Compatibility:
   ☑ Enable WebP caching (works with Imagify)
   ```

9. **Preload:**
   ```
   WP Rocket > Preload

   ☑ Activate Preload
   ☑ Activate Sitemap-based cache preloading

   Sitemap URLs:
   https://ahhofruit.com/sitemap_index.xml

   Preload Fonts:
   (add font URLs if using custom fonts)
   ```

10. **Advanced Rules:**
    ```
    WP Rocket > Advanced Rules

    Never Cache URLs:
    /cart/
    /checkout/
    /my-account/
    /(.*)add-to-cart=(.*)

    Never Cache Cookies:
    woocommerce_items_in_cart
    woocommerce_cart_hash

    Never Cache User Agent:
    (leave blank unless specific needs)
    ```

11. **Database Optimization:**
    ```
    WP Rocket > Database

    ☑ Post Cleanup: Revisions, Drafts, Deleted posts
    ☑ Comments Cleanup: Spam, Trash
    ☑ Transients Cleanup: Expired and all transients
    ☑ Database Optimization: Optimize tables

    Automatic Cleanup:
    ☑ Schedule Automatic Cleanup: Daily

    Click: "Save Changes and Optimize"
    ```

12. **CDN (Optional):**
    ```
    WP Rocket > CDN

    If using Cloudflare or other CDN:
    ☑ Enable CDN
    CDN CNAME: cdn.ahhofruits.com

    Or leave blank if not using CDN
    ```

13. **Test WP Rocket:**
    ```
    1. Clear all caches: WP Rocket > Dashboard > Clear cache
    2. Visit homepage as logged-out user
    3. Check page source: Look for "<!-- Cached by WP Rocket -->"
    4. Test all critical WooCommerce functions
    5. Run PageSpeed test
    ```

---

### Task 5.2: Image Optimization (2 hours)

**Install Imagify (FREE tier or paid)**

1. **Create Imagify Account:**
   ```
   Visit: https://app.imagify.io/register/
   Sign up with email
   Free plan: 20MB/month (about 100-200 images)
   Paid: $9.99/month for unlimited
   ```

2. **Install Imagify Plugin:**
   ```
   Plugins > Add New
   Search: "Imagify"
   Install and Activate
   ```

3. **Connect API Key:**
   ```
   Settings > Imagify

   Enter API Key: (from Imagify account)
   Click: "Save Changes"

   Status: "Your API key is valid"
   ```

4. **Optimization Settings:**
   ```
   Settings > Imagify > Optimization

   Optimization Level:
   ○ Normal (best quality, less compression)
   ● Aggressive (recommended - best balance)
   ○ Ultra (maximum compression, may reduce quality)

   ☑ Keep EXIF data: OFF (removes metadata, smaller files)
   ```

5. **Resize Larger Images:**
   ```
   ☑ Resize larger images
   Max width: 1600px (sufficient for product images)

   This prevents uploading unnecessarily large images
   ```

6. **WebP Format:**
   ```
   ☑ Create WebP versions of images

   WebP delivery:
   Display WebP images on the site:
   ● Using <picture> tags (recommended for compatibility)
   ○ Via .htaccess rewrite rules

   Fallback: JPEG for older browsers
   ```

7. **Backup:**
   ```
   ☑ Backup original images: ON (recommended)

   Allows restoration if needed
   ```

8. **File Types to Optimize:**
   ```
   ☑ Images
   ☑ PDFs (if using PDF uploads)
   ```

9. **Lazy Load:**
   ```
   ☑ Display images only when they enter the viewport

   Exclude the first X images: 3
   (Don't lazy load above-the-fold images)

   Lazy Load in iframes: ☐ OFF (may conflict with videos)
   ```

10. **Bulk Optimize Existing Images:**
    ```
    Media > Bulk Optimization

    Shows:
    - Unoptimized images: 250
    - Estimated compression: 45%
    - Space savings: ~15 MB

    Click: "Imagin'em all"

    Progress bar shows:
    "Optimizing... 50/250 (20%)"

    Wait for completion (may take 30-60 minutes for large libraries)

    Note: Free tier limited to 20MB/month
    If exceeded, pause and continue next month
    Or upgrade to paid plan
    ```

11. **Check Optimization Results:**
    ```
    Media > Library

    Each image shows:
    "Optimized by Imagify: -42% (Original: 450 KB, Optimized: 261 KB)"

    Overall savings visible in Imagify dashboard
    ```

12. **Auto-Optimize New Uploads:**
    ```
    Settings > Imagify > Settings

    ☑ Auto-Optimize images on upload: ON

    All future uploads automatically optimized
    ```

13. **Verify WebP Working:**
    ```
    Upload a test image
    Go to Media Library
    Click image

    Check attachment details:
    ✓ Original: image.jpg (500 KB)
    ✓ Optimized: image.jpg (280 KB) -44%
    ✓ WebP: image.webp (220 KB) -21% from optimized

    View page source with that image:
    Should see:
    <picture>
      <source srcset="image.webp" type="image/webp">
      <img src="image.jpg" alt="...">
    </picture>
    ```

---

### Task 5.3: Mobile Optimization Check (1 hour)

**Step-by-Step:**

1. **Google Mobile-Friendly Test:**
   ```
   URL: https://search.google.com/test/mobile-friendly

   Enter: https://ahhofruit.com
   Click: "Test URL"

   Wait for results (30-60 seconds)

   Expected: "Page is mobile friendly"

   If issues found:
   - Text too small to read
   - Clickable elements too close together
   - Content wider than screen
   - etc.

   Fix each issue listed
   ```

2. **Test on Real Devices:**

   **iPhone (Safari):**
   ```
   Open: https://ahhofruit.com

   Check:
   ✓ All text readable (min 16px)
   ✓ Buttons tappable (min 48x48px)
   ✓ No horizontal scrolling
   ✓ Images load and scale properly
   ✓ Add to cart button accessible
   ✓ Checkout process works smoothly
   ```

   **Android (Chrome):**
   ```
   Same checks as iPhone

   Additional:
   ✓ Back button works correctly
   ✓ Form inputs don't zoom in awkwardly
   ```

3. **Chrome DevTools Mobile Emulation:**
   ```
   Chrome browser > F12 (DevTools)
   Click: Toggle device toolbar (phone icon)
   Or: Ctrl+Shift+M

   Select device:
   - iPhone 12 Pro (390 x 844)
   - iPhone SE (375 x 667)
   - Samsung Galaxy S20 (360 x 800)
   - iPad (768 x 1024)

   Test:
   ✓ Homepage
   ✓ Category pages
   ✓ Product pages
   ✓ Cart
   ✓ Checkout

   Rotate to landscape mode
   Check everything still works
   ```

4. **Common Mobile Issues and Fixes:**

   **Issue: Text too small**
   ```
   Fix:
   Appearance > Customize > Additional CSS

   body {
     font-size: 16px !important;
     line-height: 1.6;
   }

   h1 { font-size: 28px !important; }
   h2 { font-size: 24px !important; }
   h3 { font-size: 20px !important; }
   ```

   **Issue: Buttons too small**
   ```
   Fix:
   .woocommerce a.button,
   .woocommerce button.button,
   .woocommerce input.button {
     min-height: 48px !important;
     min-width: 48px !important;
     padding: 12px 24px !important;
     font-size: 16px !important;
   }
   ```

   **Issue: Horizontal scrolling**
   ```
   Fix:
   body {
     overflow-x: hidden !important;
     max-width: 100vw !important;
   }

   img {
     max-width: 100% !important;
     height: auto !important;
   }

   table {
     display: block !important;
     overflow-x: auto !important;
   }
   ```

   **Issue: Pop-ups blocking content**
   ```
   Fix:
   - Ensure pop-ups/newsletters have easy close button
   - Don't show immediately on mobile
   - Use exit-intent only (not on entry)
   ```

5. **Mobile Page Speed:**
   ```
   PageSpeed Insights: https://pagespeed.web.dev/

   Enter: https://ahhofruit.com

   Check MOBILE score:
   Target: 70+ (acceptable), 90+ (excellent)

   If < 70, optimize:
   ✓ Compress images further
   ✓ Enable caching (already done)
   ✓ Minimize CSS/JS
   ✓ Reduce third-party scripts
   ✓ Use lazy loading (already done)
   ```

6. **Core Web Vitals (Mobile):**
   ```
   Check in PageSpeed Insights:

   LCP (Largest Contentful Paint): <2.5s ✓
   INP (Interaction to Next Paint): <200ms ✓
   CLS (Cumulative Layout Shift): <0.1 ✓

   If failing:
   LCP: Optimize hero image, enable preload
   INP: Reduce JavaScript, optimize third-party scripts
   CLS: Set image dimensions, reserve ad space
   ```

---

## Day 12-13: Technical SEO & Analytics

### Task 6.1: Final Google Search Console Setup (1 hour)

**Step-by-Step:**

1. **Verify Sitemap Submission:**
   ```
   Google Search Console > Sitemaps

   Status should show:
   "Success" or "Fetched"

   Check:
   ✓ sitemap_index.xml submitted
   ✓ No errors
   ✓ Pages discovered

   Note: May take 24-48 hours to fully process
   ```

2. **Request Indexing for Key Pages:**
   ```
   Search Console > URL Inspection

   Enter homepage URL: https://ahhofruit.com
   Click: "Test live URL"
   Wait for results
   Click: "Request indexing"

   Repeat for:
   - Top 5 category pages
   - Top 10 product pages

   Note: Limited to ~10 requests per day
   Priority: Homepage > categories > best-selling products
   ```

3. **Set Up Email Notifications:**
   ```
   Search Console > Settings (gear icon)

   Email notifications:
   ☑ Enable email notifications

   Users and permissions:
   Add: your-email@domain.com
   Permission: Owner

   You'll receive alerts for:
   - Critical indexing issues
   - Security issues
   - Manual actions (penalties)
   - New Search Console messages
   ```

4. **Set Preferred Domain (if needed):**
   ```
   Search Console properties should include:
   - https://ahhofruit.com (main property)
   - http://ahhofruit.com (should 301 redirect)

   If both indexed:
   Use .htaccess 301 redirect (already done in Day 1)
   ```

5. **Check Coverage Report:**
   ```
   Search Console > Index > Coverage

   Valid pages: (number will grow over time)
   Excluded: (check reasons)
   Error: (fix immediately)

   Common exclusions (acceptable):
   - Duplicate without user-selected canonical
   - Noindexed in 'robots' meta tag
   - Blocked by robots.txt

   Expected exclusions:
   - /cart/ (noindexed)
   - /checkout/ (noindexed)
   - /my-account/ (noindexed)
   ```

6. **Monitor Mobile Usability:**
   ```
   Search Console > Experience > Mobile Usability

   Check for errors:
   - Text too small to read
   - Clickable elements too close
   - Content wider than screen
   - Viewport not set

   Fix any errors found
   Re-test with Mobile-Friendly Test tool
   ```

---

### Task 6.2: Set Up Google Analytics 4 Events (2 hours)

**Step-by-Step:**

1. **Verify GA4 Installation:**
   ```
   Visit: https://ahhofruit.com

   Check tracking:
   Chrome > F12 > Network tab
   Filter: collect

   Should see requests to:
   https://www.google-analytics.com/g/collect?...

   Or use GA Debugger Chrome extension
   ```

2. **Enable Enhanced Ecommerce:**
   ```
   WordPress Admin > Rank Math > General Settings > Analytics

   ☑ Enable Enhanced Ecommerce

   This auto-tracks WooCommerce events:
   - view_item
   - add_to_cart
   - begin_checkout
   - purchase
   - refund
   ```

3. **Configure WooCommerce Integration:**
   ```
   WooCommerce > Settings > Integration

   Look for: Google Analytics

   If available:
   Tracking ID: G-XXXXXXXXXX (your GA4 ID)
   ☑ Enable Enhanced Ecommerce
   ☑ Track events (add to cart, remove from cart, etc.)

   If not available:
   Rank Math already handles this via Enhanced Ecommerce setting
   ```

4. **Mark Conversions in GA4:**
   ```
   Google Analytics > Admin > Data display > Events

   Find these events:
   - purchase (should already be there)
   - begin_checkout
   - add_to_cart
   - contact (if using contact form)

   For each:
   Toggle: "Mark as conversion" ON

   These become conversion metrics in reports
   ```

5. **Set Up Custom Events (Optional):**

   **Track WhatsApp Clicks:**
   ```
   Rank Math > General Settings > Analytics > Custom Events

   Add event:
   Event name: whatsapp_click
   Trigger: Click on links containing "wa.me" or "whatsapp"
   Category: Engagement
   Action: WhatsApp
   Label: Contact via WhatsApp
   ```

   **Track Phone Clicks:**
   ```
   Event name: phone_click
   Trigger: Click on tel: links
   Category: Engagement
   Action: Phone
   Label: Call business
   ```

6. **Create Custom Segments:**
   ```
   Google Analytics > Explore > Blank

   Create segments for:

   Segment 1: "First-time visitors who purchased"
   Conditions:
   - User type: New users
   - Event: purchase (count > 0)

   Segment 2: "Returning customers"
   Conditions:
   - User type: Returning users
   - Purchase count > 1

   Segment 3: "High-value customers"
   Conditions:
   - Purchase revenue > $200

   Use for targeted remarketing later
   ```

7. **Set Up Goals/Conversions:**
   ```
   Already done by marking events as conversions

   Verify in:
   Google Analytics > Reports > Monetization > Ecommerce purchases

   Should track:
   - Total revenue
   - Transactions
   - Average order value
   - Product performance
   ```

8. **Test Ecommerce Tracking:**
   ```
   1. Open incognito window
   2. Visit: https://ahhofruit.com
   3. Browse products
   4. Add item to cart
   5. Go to checkout (don't complete)
   6. Close window

   Check GA4 Real-time:
   Google Analytics > Reports > Realtime

   Should show:
   ✓ 1 user active
   ✓ Events: page_view, view_item, add_to_cart, begin_checkout

   Events may take 24-48 hours to appear in standard reports
   But Real-time should show immediately
   ```

9. **Create Ecommerce Dashboard:**
   ```
   Google Analytics > Reports > Ecommerce purchases

   Customize report:
   Click: Customize report

   Add cards:
   - Total revenue (this month)
   - Transactions (this month)
   - Average order value
   - Top products by revenue
   - Top products by quantity
   - Revenue by traffic source

   Save as: "Ah Ho Fruit Dashboard"

   Bookmark for daily monitoring
   ```

10. **Set Up Alerts:**
    ```
    Google Analytics > Admin > Custom definitions > Custom alerts

    Alert 1: "Sudden traffic drop"
    Conditions: Daily users < 50 (set threshold based on normal traffic)
    Send email: Yes

    Alert 2: "Zero ecommerce revenue"
    Conditions: Ecommerce revenue = 0 for 1 day
    Send email: Yes
    (Helps catch tracking issues)

    Alert 3: "Spike in errors"
    Conditions: Page errors > 10 in 1 hour
    Send email: Yes
    ```

---

### Task 6.3: Install Additional Tracking (Optional - 1 hour)

**Facebook Pixel (for future Facebook Ads):**

1. **Create Facebook Pixel:**
   ```
   Facebook Business Manager > Events Manager
   Click: "Add" > "Facebook Pixel"
   Name: Ah Ho Fruit Website
   Enter website URL
   Click: "Create"

   Copy Pixel ID: XXXXXXXXXXXXXXX
   ```

2. **Install via Plugin:**
   ```
   Install: "PixelYourSite" plugin (FREE)

   Plugins > Add New > Search "PixelYourSite"
   Install and Activate

   PixelYourSite > Facebook Settings
   Enter Pixel ID
   ☑ Enable Facebook Pixel
   ☑ Track key events (PageView, AddToCart, Purchase)

   Save
   ```

3. **Test Pixel:**
   ```
   Install: Facebook Pixel Helper (Chrome extension)
   Visit your site
   Click extension icon

   Should show:
   ✓ Pixel found: XXXXXXXXXXXXXXX
   ✓ Events detected: PageView

   Add product to cart:
   ✓ AddToCart event fires
   ```

**Microsoft Clarity (FREE Heatmaps & Session Recordings):**

1. **Create Clarity Account:**
   ```
   Visit: https://clarity.microsoft.com/
   Sign up with Microsoft account
   Click: "Add new project"

   Project name: Ah Ho Fruit
   Website URL: https://ahhofruit.com

   Copy Tracking Code
   ```

2. **Install via Plugin:**
   ```
   Install: "Insert Headers and Footers" plugin

   Settings > Insert Headers and Footers

   Paste Clarity tracking code in:
   "Scripts in Header" section

   Save
   ```

3. **Verify Clarity Working:**
   ```
   Visit your site in incognito mode
   Browse 2-3 pages

   Back in Clarity dashboard:
   Check "Live" tab
   Should show 1 active user (you)

   After 24 hours:
   - Heatmaps show click patterns
   - Recordings show user sessions
   - Insights show rage clicks, dead clicks
   ```

---

## Day 14: Pre-Launch Audit

### Task 7.1: Run Complete SEO Audit (3 hours)

**Step-by-Step:**

1. **Install Screaming Frog SEO Spider (FREE - 500 URLs):**
   ```
   Download: https://www.screamingfrog.co.uk/seo-spider/
   Install on your computer
   Open application
   ```

2. **Crawl Your Website:**
   ```
   Enter: https://ahhofruit.com
   Click: "Start"

   Wait for crawl to complete (5-10 minutes for small site)

   Crawl shows:
   - Total URLs: ~250
   - HTML pages: ~180
   - Images: ~350
   - CSS: ~15
   - JavaScript: ~20
   ```

3. **Check for Errors:**

   **Tab: Internal > HTML**
   ```
   Status Code filter: Client Error (4XX)

   Check for 404 errors:
   Expected: 0-2 (only if intentional)

   If found:
   - Fix broken links
   - Or setup 301 redirects
   ```

4. **Duplicate Content Check:**

   **Tab: Content > Duplicate**
   ```
   Check:
   - Duplicate Titles (should be 0)
   - Duplicate Descriptions (should be 0)
   - Duplicate H1s (should be 0)

   If found:
   List shows URLs with duplicates
   Fix: Make each unique
   ```

5. **Missing Elements:**

   **Tab: Page Titles > Missing**
   ```
   Shows pages without title tags
   Expected: 0

   If found: Add title to each page
   ```

   **Tab: Meta Description > Missing**
   ```
   Shows pages without meta descriptions
   Expected: 0-5 (some utility pages acceptable)

   If found: Add meta descriptions
   ```

   **Tab: Images > Missing Alt Text**
   ```
   Shows images without alt text
   Expected: <10%

   If >10%:
   Export list
   Add alt text to each image via Media Library
   ```

6. **Check Redirects:**

   **Tab: Response Codes > Redirection (3XX)**
   ```
   Check for redirect chains:

   ✗ Bad: Page A → Page B → Page C (2 hops)
   ✓ Good: Page A → Page C (1 hop)

   If redirect chains found:
   Update redirects to point directly to final destination
   ```

7. **Canonical Check:**

   **Tab: Link > Canonical**
   ```
   Select: "Missing Canonical"

   Expected: All important pages have canonical tags

   If missing:
   Rank Math should auto-add
   If not, check Rank Math settings
   ```

8. **Schema Markup Audit:**

   **Tab: Structured Data > Schema.org**
   ```
   Check:
   ✓ Products have Product schema
   ✓ Categories have BreadcrumbList
   ✓ Homepage has Organization schema

   Errors shown in red
   Warnings shown in orange

   Fix any errors
   ```

9. **Mobile Issues:**

   **Tab: Rendering > Viewport**
   ```
   Check: "Missing Viewport Meta Tag"

   Expected: 0 pages

   If found:
   Add to theme header.php:
   <meta name="viewport" content="width=device-width, initial-scale=1">
   ```

10. **Export Audit Report:**
    ```
    Screaming Frog > Reports > Crawl Overview

    Export: PDF or Excel

    Review summary:
    - Total URLs crawled
    - Status code summary
    - Duplicate content
    - Missing elements

    Fix all critical issues before launch
    ```

---

### Task 7.2: Manual Pre-Launch Checklist (2 hours)

**Technical SEO:**

```
☐ 1. SSL certificate active (https://)
☐ 2. HTTP redirects to HTTPS
☐ 3. Permalink structure optimized (/%postname%/)
☐ 4. robots.txt allows indexing (NO Disallow: / rule)
☐ 5. XML sitemap generated (sitemap_index.xml)
☐ 6. Sitemap submitted to Google Search Console
☐ 7. Google Analytics installed and tracking
☐ 8. No broken links (404 errors)
☐ 9. Canonical URLs set correctly
☐ 10. Breadcrumbs enabled and visible
☐ 11. Schema markup implemented (Product, BreadcrumbList)
☐ 12. Mobile-friendly test passed
☐ 13. Page speed >70 mobile, >80 desktop
☐ 14. Core Web Vitals passing (LCP <2.5s, INP <200ms, CLS <0.1)
☐ 15. All redirects working (no redirect chains)
```

**On-Page SEO:**

```
☐ 16. Homepage has custom title (60 chars) and description (160 chars)
☐ 17. All category pages (minimum 5) have unique titles/descriptions
☐ 18. All products (top 50) have:
    ☐ Unique title tags
    ☐ Unique meta descriptions
    ☐ 200+ word descriptions
    ☐ Optimized images with alt text
    ☐ SKU filled in
    ☐ Prices set
    ☐ Stock status configured
☐ 19. Internal linking structure reviewed
☐ 20. No duplicate titles across site
☐ 21. No duplicate descriptions across site
☐ 22. Focus keywords set for main pages
☐ 23. H1 tags unique on every page
```

**WooCommerce-Specific:**

```
☐ 24. Product schema includes: name, price, availability, SKU, brand
☐ 25. Out-of-stock handling configured
☐ 26. Product categories indexed (noindex for tags if <100 products)
☐ 27. Cart/checkout/account pages excluded from indexing
☐ 28. Cart/checkout excluded from caching
☐ 29. Enhanced Ecommerce tracking enabled in GA4
☐ 30. Test purchase tracked in GA4
```

**Performance:**

```
☐ 31. Caching plugin installed and configured
☐ 32. Cart/checkout excluded from cache
☐ 33. Image optimization plugin installed
☐ 34. All images compressed (<200KB each)
☐ 35. WebP format enabled
☐ 36. Lazy loading enabled (except above-fold)
☐ 37. CSS minified
☐ 38. JavaScript minified (but NOT combined if WooCommerce breaks)
☐ 39. GZIP compression enabled
☐ 40. Browser caching enabled
```

**Critical Functionality Tests:**

```
☐ 41. Homepage loads correctly
☐ 42. Category pages display products
☐ 43. Product pages show images, price, description
☐ 44. Add to cart works
☐ 45. Cart updates quantities correctly
☐ 46. Checkout process completes successfully
☐ 47. Payment gateway works (Stripe test mode)
☐ 48. Order confirmation email sent
☐ 49. User registration works
☐ 50. Password reset works
☐ 51. "My Account" page shows correct data
☐ 52. Search function returns relevant results
☐ 53. Filters work on category pages (if applicable)
☐ 54. Mobile: All functions work on phone
☐ 55. Mobile: All functions work on tablet
```

**Security & Legal:**

```
☐ 56. SSL certificate valid (not expired)
☐ 57. Privacy Policy page exists and linked
☐ 58. Terms of Service page exists and linked
☐ 59. Refund Policy page exists and linked
☐ 60. Contact page exists with form/email
☐ 61. Cookie notice displayed (GDPR compliance)
☐ 62. Newsletter signup has privacy consent checkbox
☐ 63. Admin password strong (15+ characters)
☐ 64. WordPress/plugins/theme updated to latest versions
☐ 65. Security plugin installed (Wordfence or similar)
```

**Analytics & Tracking:**

```
☐ 66. Google Search Console verified
☐ 67. Google Analytics tracking verified
☐ 68. Ecommerce events firing correctly
☐ 69. Conversions marked in GA4
☐ 70. Email notifications enabled in Search Console
☐ 71. Real-time tracking shows live data
☐ 72. Excluded internal traffic from analytics (your office IP)
```

**Final Visual Checks:**

```
☐ 73. Logo displays correctly on all pages
☐ 74. Favicon appears in browser tab
☐ 75. Social sharing images set (1200x630px)
☐ 76. Footer contains: copyright, links, contact info
☐ 77. Header menu links work
☐ 78. No placeholder text (lorem ipsum) anywhere
☐ 79. No "test" or "dummy" products visible
☐ 80. All images high quality (not blurry/pixelated)
☐ 81. No spelling errors on homepage
☐ 82. Company information accurate (address, phone, email)
☐ 83. Operating hours displayed (if applicable)
☐ 84. Delivery information clear (zones, fees, times)
☐ 85. Payment methods listed
```

**Print this checklist and check off each item manually.**

---

## Launch Day: Go Live

### Task 8.1: Final Pre-Launch Verification (1 hour)

**Step-by-Step:**

1. **Triple-Check robots.txt:**
   ```
   Visit: https://ahhofruit.com/robots.txt

   ✓ VERIFY: NO "Disallow: /" rule exists

   Correct content:
   User-agent: *
   Allow: /wp-content/uploads/
   Disallow: /wp-admin/

   ✗ WRONG (will de-index site):
   User-agent: *
   Disallow: /
   ```

2. **Verify Indexing Allowed in WordPress:**
   ```
   Settings > Reading

   Search engine visibility:
   ☐ Discourage search engines from indexing this site

   ✓ MUST BE UNCHECKED

   If checked: Uncheck and save
   ```

3. **Force HTTPS One More Time:**
   ```
   Settings > General

   WordPress Address (URL): https://ahhofruit.com
   Site Address (URL): https://ahhofruit.com

   ✓ Both must start with "https://"

   Save Changes
   ```

4. **Clear ALL Caches:**
   ```
   If using LiteSpeed Cache:
   LiteSpeed Cache > Purge > Purge All

   If using WP Rocket:
   WP Rocket > Clear cache

   Also clear:
   - Browser cache (Ctrl+Shift+Delete)
   - Cloudflare cache (if using CDN)
   - Server cache (if applicable)
   ```

5. **Test in Incognito Mode:**
   ```
   Open incognito/private browsing window

   Visit: https://ahhofruit.com

   Check:
   ✓ Site loads correctly
   ✓ Images display
   ✓ No broken links on homepage
   ✓ Add to cart works
   ✓ Checkout accessible

   Test on:
   - Chrome incognito
   - Firefox private window
   - Safari private browsing
   - Mobile browser
   ```

6. **Verify Analytics Tracking:**
   ```
   Google Analytics > Reports > Realtime

   With incognito window open on your site:
   ✓ Shows 1 active user
   ✓ Events firing (page_view, etc.)

   Close incognito window
   Active users drops to 0

   ✓ Tracking confirmed working
   ```

---

### Task 8.2: Submit to Google (15 minutes)

**Step-by-Step:**

1. **Submit Homepage for Indexing:**
   ```
   Google Search Console > URL Inspection

   Enter: https://ahhofruit.com
   Click: "Test live URL"
   Wait for results

   If shows: "URL is on Google"
   ✓ Already indexed (great!)

   If shows: "URL is not on Google"
   Click: "Request indexing"
   Message: "Indexing requested"

   Note: Takes 24-48 hours to actually index
   ```

2. **Verify Sitemap Accessible:**
   ```
   Google Search Console > Sitemaps

   Check status:
   ✓ Success - sitemap fetched
   ✓ Pages discovered: 180

   If status: "Couldn't fetch"
   Re-submit: Enter "sitemap_index.xml" and click Submit
   ```

3. **Request Indexing for Key Pages:**
   ```
   URL Inspection > Enter URL > Request indexing

   Priority pages:
   1. Homepage: https://ahhofruit.com/
   2. Shop page: https://ahhofruit.com/shop/
   3. Top category: https://ahhofruit.com/omakase-boxes/
   4. Best-selling product: https://ahhofruit.com/premium-omakase-box/
   5. About page: https://ahhofruit.com/about/

   Limit: 10 requests per day
   Don't exceed this
   ```

4. **Monitor Coverage Report:**
   ```
   Search Console > Index > Coverage

   Check:
   Valid: (will increase over next 7-14 days)
   Excluded: (acceptable for cart, checkout, etc.)
   Error: 0 (fix immediately if any appear)
   ```

---

### Task 8.3: Post-Launch Monitoring (Ongoing)

**Daily Tasks (Week 1):**

```
Day 1:
☐ Check Google Analytics: Any traffic?
☐ Check Search Console: Any errors?
☐ Test checkout process: Still working?
☐ Check PageSpeed: Scores maintained?

Day 2:
☐ Check for 404 errors in Search Console
☐ Verify Analytics tracking still working
☐ Check cart abandonment rate (if any orders)

Day 3:
☐ Review Coverage report: Pages being indexed?
☐ Check mobile usability: Any new issues?
☐ Test site on different devices

Day 4:
☐ Review top landing pages in Analytics
☐ Check bounce rate: High (>70%)? Investigate.
☐ Verify Ecommerce tracking if any sales

Day 5:
☐ Check Core Web Vitals in Search Console
☐ Review search queries in Performance report
☐ Monitor for any security alerts

Day 6:
☐ Check page indexing progress
☐ Review conversion funnel in Analytics
☐ Test site speed on mobile

Day 7:
☐ Weekly review: Traffic trends?
☐ Any SEO issues to address?
☐ Plan next week's optimizations
```

**Weekly Tasks (Month 1-3):**

```
Week 1:
☐ Full site functionality test
☐ Review Analytics goals/conversions
☐ Check all product pages indexed

Week 2:
☐ Monitor keyword rankings (if tracking)
☐ Review top-performing products
☐ Optimize low-performing pages

Week 3:
☐ Check for new 404 errors
☐ Review and respond to Google Search Console messages
☐ Update product descriptions based on data

Week 4:
☐ Monthly performance review
☐ Identify SEO opportunities
☐ Plan content additions (blog posts, FAQs)
```

**Monthly Tasks:**

```
☐ Run full Screaming Frog crawl
☐ Check for duplicate content
☐ Review and update meta descriptions
☐ Analyze top exit pages (improve those pages)
☐ Review mobile usability
☐ Check for broken links
☐ Update product images if needed
☐ Refresh homepage content
☐ Add new products/categories
☐ Review and respond to customer feedback
```

---

## Troubleshooting Common Issues

### Issue 1: Site Not Showing in Google After 1 Week

**Diagnosis:**
```
1. Check robots.txt: https://ahhofruit.com/robots.txt
   ✓ Should allow indexing

2. Check WordPress settings: Settings > Reading
   ✓ "Discourage search engines" should be UNCHECKED

3. Check Search Console > Coverage
   ✓ Look for errors blocking indexing

4. Check Rank Math settings
   ✓ Titles & Meta > Global Meta > Robots Meta
   ✓ Should be: Index, Follow
```

**Fix:**
```
If robots.txt blocking: Remove Disallow: / rule
If WordPress blocking: Uncheck "Discourage search engines"
If Rank Math noindexing: Change to Index, Follow
If sitemap errors: Re-submit sitemap

After fixing:
Search Console > Request indexing for key pages
Wait 48-72 hours
```

---

### Issue 2: PageSpeed Score Dropped After Launch

**Diagnosis:**
```
Run: https://pagespeed.web.dev/
Check what changed:
- New images added (not optimized)?
- Plugins added?
- Third-party scripts?
- Cache disabled?
```

**Fix:**
```
1. Re-optimize new images with Imagify
2. Disable unnecessary plugins
3. Verify cache still enabled
4. Check CDN still active
5. Minify CSS/JS again
6. Clear all caches and re-test
```

---

### Issue 3: Products Not Showing in Google Shopping Results

**Note:** Requires Google Merchant Center (not covered in this guide).

**Basic Requirements:**
```
✓ Product schema markup (already done via Rank Math)
✓ Product feed (requires plugin: Google Listings & Ads)
✓ Google Merchant Center account
✓ Products approved in Merchant Center

If products have schema but not in Shopping:
- This is expected - organic product results require:
  - High authority domain
  - Significant reviews
  - Time (6-12 months)
```

---

### Issue 4: Analytics Showing Zero Ecommerce Revenue

**Diagnosis:**
```
1. Test purchase:
   - Add product to cart
   - Complete checkout
   - Use Stripe test mode

2. Check GA4 Real-time:
   - Should show purchase event
   - With revenue amount

3. If not showing:
   - Enhanced Ecommerce not enabled
   - WooCommerce integration broken
   - Tracking code missing
```

**Fix:**
```
Rank Math > General Settings > Analytics
☑ Enable Enhanced Ecommerce: ON
Save Changes

Complete test purchase again
Check Real-time report

If still not working:
Contact Rank Math support or install:
Plugin: "Enhanced E-commerce for Woocommerce store"
```

---

### Issue 5: Rank Math SEO Score Stuck at Low Score

**Common Reasons:**

```
❌ Focus keyword not in title
Fix: Include keyword in SEO title

❌ Focus keyword not in content
Fix: Use keyword 2-3 times in description (1-1.5% density)

❌ Content too short
Fix: Add 200+ words of description

❌ No internal links
Fix: Link to 2-3 related products/categories

❌ No images with keyword in alt text
Fix: Add keyword to at least one image alt text

❌ No outbound links
Fix: Link to supplier, certification, or review site (if applicable)

Target: 80+ score (green)
Acceptable: 60-79 (orange) - won't hurt rankings
Problematic: <60 (red) - fix before publishing
```

---

## Conclusion

**You've Completed:**

✅ Day 1: Foundation setup (Rank Math, permalinks, robots.txt, HTTPS)
✅ Day 2: Plugin configuration (WooCommerce SEO, sitemaps, Search Console, Analytics)
✅ Day 3-4: Homepage & category optimization (titles, descriptions, content)
✅ Day 5-9: Product page optimization (50+ products with full SEO)
✅ Day 10-11: Performance optimization (caching, image optimization, mobile)
✅ Day 12-13: Technical SEO & analytics (tracking, events, schema)
✅ Day 14: Pre-launch audit (checklist, testing, verification)
✅ Launch Day: Go live (submit to Google, monitor)

**Your site is now SEO-ready for launch on January 31, 2026.**

**Expected Timeline for SEO Results:**

```
Week 1-2: Pages start getting indexed
Week 3-4: First appearance in search results (branded queries)
Month 2-3: Rankings improve for product names
Month 4-6: Rankings improve for category keywords
Month 7-12: Rankings improve for competitive terms
```

**Next Steps Post-Launch:**

1. Monitor Google Search Console weekly
2. Respond to any indexing errors immediately
3. Add new products with optimized SEO
4. Start blog/content marketing (buying guides, fruit care tips)
5. Build backlinks (supplier partnerships, local directories)
6. Encourage customer reviews (builds trust + SEO)
7. Run monthly SEO audits with Screaming Frog

**Good luck with your launch! 🚀**
