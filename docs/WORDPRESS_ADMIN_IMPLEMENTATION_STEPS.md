# WordPress Admin Implementation Steps - Ah Ho Fruit SEO

**Site:** https://ahhofruit.com/wp-admin/
**Target Launch:** January 31, 2026 (6 days remaining)
**Estimated Time:** 4-6 hours total

---

## Before You Start

**Required Access:**
- [ ] WordPress admin login credentials
- [ ] FTP/cPanel access for file uploads
- [ ] Google account (for Search Console & Analytics)

**Recommended:**
- [ ] Close all other browser tabs
- [ ] Set aside uninterrupted time (work through one section at a time)
- [ ] Have this guide open in one window, WordPress admin in another

---

## Phase 1: Critical Fixes (30 minutes)

### Step 1: Upload robots.txt and .htaccess (10 min)

**Files to Upload:**
- `/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/robots.txt`
- `/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/.htaccess`

**Method A: Via cPanel File Manager (Recommended)**

1. Login to cPanel: https://sh00017.vodien.com:2083/
   - Username: `contactl` (based on Vodien hosting info)

2. Click **File Manager**

3. Navigate to `/public_html/` (or site root)

4. **Upload robots.txt:**
   - Click **Upload**
   - Select `/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/robots.txt`
   - If `robots.txt` exists, select it and click **Delete** first
   - Upload the new file

5. **Upload .htaccess:**
   - In File Manager, click **Settings** (top right)
   - Check **Show Hidden Files (dotfiles)**
   - Click **Save**
   - Look for existing `.htaccess` file
   - Right-click > **Edit**
   - BACKUP current content (copy to notepad)
   - Replace with new content from `/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/.htaccess`
   - Save

6. **Verify Upload:**
   - Open terminal
   - Run: `curl https://ahhofruit.com/robots.txt`
   - Should see new content with WooCommerce exclusions

**Method B: Via FTP (Alternative)**

```bash
# Using FileZilla or command line
sftp contactl@sh00017.vodien.com
cd public_html
put /Users/lexnaweiming/Downloads/Ah\ Ho\ Fruits/ah-ho-fruits/robots.txt
put /Users/lexnaweiming/Downloads/Ah\ Ho\ Fruits/ah-ho-fruits/.htaccess
exit
```

---

### Step 2: Fix Catastrophic URL Slugs (10 min)

**Problem Pages:**
1. Gift Hampers page (200+ character URL)
2. Refund Policy page (200+ character URL)
3. Delivery Information page (200+ character URL)

**Fix for EACH page:**

1. Login to WordPress: https://ahhofruit.com/wp-admin/

2. Go to **Pages > All Pages**

3. Find the problematic page (look for extremely long URLs in list)

4. Click **Edit**

5. In the editor, look at the URL under the title:
   ```
   https://ahhofruit.com/gift-hamperspremium-fruit-gift-hampers...
   ```

6. Click **Edit** button next to the URL

7. Delete the entire slug

8. Type a short, clean slug:
   - Gift Hampers: `gift-hampers`
   - Refund Policy: `refund-policy`
   - Delivery Information: `delivery-information`

9. Click **OK**

10. Click **Update** (blue button, top right)

11. Repeat for other 2 pages

**Verify:**
- Visit the page
- Check URL in browser address bar
- Should be: `https://ahhofruit.com/gift-hampers/`

---

### Step 3: Delete Duplicate WooCommerce Pages (5 min)

**Pages to Delete:**
- Cart 2
- Shop 2
- Checkout 2
- My Account 2

**Steps:**

1. **Pages > All Pages**

2. In search box (top right), type: `cart 2`

3. Hover over the page title

4. Click **Trash**

5. Repeat for:
   - `shop 2`
   - `checkout 2`
   - `my-account 2`

6. Click **Trash** link (top of page, next to "All", "Published")

7. Click **Empty Trash**

**Verify:**
- Refresh **All Pages**
- Should only see: Cart, Shop, Checkout, My Account (no "-2" versions)

---

### Step 4: Verify Current Permalink Structure (2 min)

1. **Settings > Permalinks**

2. Check that **Post name** is selected:
   ```
   ⦿ Post name
   https://ahhofruit.com/sample-post/
   ```

3. If not selected, select it and click **Save Changes**

4. Scroll down to **Optional** section:

   **Product base:** Leave EMPTY (removes `/product/` from URLs)

   **Product category base:** Leave EMPTY (removes `/product-category/`)

5. Click **Save Changes**

**Result:**
- Products: `https://ahhofruit.com/hokkaido-crown-melon/`
- Categories: `https://ahhofruit.com/japanese-fruits/`

---

## Phase 2: Install & Configure Rank Math SEO (45 min)

### Step 5: Install Rank Math Plugin (5 min)

1. **Plugins > Add New**

2. Search box: type `Rank Math SEO`

3. Find **Rank Math SEO** by Rank Math (should be first result)
   - Author: Rank Math
   - 1M+ active installations
   - 5-star rating

4. Click **Install Now**

5. Wait for installation (15-30 seconds)

6. Click **Activate**

7. **Setup Wizard will appear** - Click **Start Wizard**

---

### Step 6: Rank Math Setup Wizard (15 min)

**Screen 1: Welcome**
- Click **Start Wizard**

**Screen 2: Your Site**
- **Site Type:** Select **Small Business**
- **Organization or Person?** Select **Organization**
- **Organization Name:** `Ah Ho Fruit`
- **Organization Logo:** Upload logo (if available)
  - Recommended size: 600x60px or 1200x630px
- Click **Save and Continue**

**Screen 3: Search Console**
- Click **Get Authorization Code**
- Sign in with Google account
- Copy authorization code
- Paste in Rank Math
- Click **Continue**
- Select **ahhofruit.com** property
- Click **Save and Continue**

*If Search Console not set up yet, click **Skip this step** - we'll do it later*

**Screen 4: Sitemap**
- ✅ **Include Images in Sitemap:** CHECKED
- ✅ **Include Posts in Sitemap:** CHECKED
- ✅ **Include Pages in Sitemap:** CHECKED
- ✅ **Include Products in Sitemap:** CHECKED
- ✅ **Include Product Categories in Sitemap:** CHECKED
- Click **Save and Continue**

**Screen 5: Optimization**
- ✅ **Noindex Empty Category and Tag Archives:** CHECKED
- ✅ **Nofollow External Links:** UNCHECKED (we want to link to suppliers)
- ✅ **Open External Links in New Tab:** CHECKED
- ✅ **Nofollow Image File Links:** CHECKED
- Click **Save and Continue**

**Screen 6: Compatibility**
- Check if any conflicting plugins detected
- If **Yoast SEO** or **All in One SEO** found, click **Deactivate and Delete**
- Click **Save and Continue**

**Screen 7: Analytics**
- ✅ **Enable Analytics Module:** CHECKED
- Click **Save and Continue**

**Screen 8: Your Site is Ready**
- ✅ **Advanced Mode:** CHECKED (gives you full control)
- ✅ **Setup Wizard Complete**
- Click **Finish**

---

### Step 7: Configure Rank Math Settings (25 min)

**7.1 General Settings (5 min)**

1. **Rank Math > General Settings**

2. **Breadcrumbs Tab:**
   - ✅ **Enable Breadcrumbs:** CHECKED
   - **Separator:** Choose `>` or `/`
   - ✅ **Show Blog Page on Breadcrumbs:** UNCHECKED
   - **Show on Post Types:** ✅ Products, ✅ Pages
   - Click **Save Changes**

3. **Links Tab:**
   - ✅ **Strip Category Base:** CHECKED (removes `/category/`)
   - ✅ **Redirect Attachments:** Checked (to parent post)
   - Click **Save Changes**

4. **Webmaster Tools Tab:**
   - **Google Search Console:** (If not done in wizard)
     - Paste verification code here
   - **Google Analytics:**
     - Paste Measurement ID (format: `G-XXXXXXXXXX`)
   - Click **Save Changes**

**7.2 Titles & Meta (10 min)**

1. **Rank Math > Titles & Meta**

2. **Local SEO Tab:**
   - **Business Type:** Select `LocalBusiness` > `FoodEstablishment`
   - **Business Name:** `Ah Ho Fruit`
   - **Address:**
     ```
     Ah Ho Fruit
     [Your actual address]
     Singapore [Postal Code]
     ```
   - **Phone:** `[Your business phone]`
   - **Price Range:** `$$` (or appropriate)
   - Click **Save Changes**

3. **Homepage Tab:**
   - **Homepage Title Format:**
     ```
     Premium Fresh Fruit Delivery Singapore | Ah Ho Fruit
     ```
   - **Homepage Description Format:**
     ```
     Fresh premium fruits delivered daily across Singapore. Japanese Amaou strawberries, Hokkaido melons, Korean grapes & seasonal fruit hampers. Same-day delivery available.
     ```
   - **Facebook Image:** Upload 1200x630px image
   - **Twitter Image:** Same as Facebook
   - Click **Save Changes**

4. **Posts Tab:**
   - **Show in Search Results:** ✅ **Index**
   - Click **Save Changes**

5. **Pages Tab:**
   - **Show in Search Results:** ✅ **Index**
   - Click **Save Changes**

6. **WooCommerce Tab:**

   **Products:**
   - **Show in Search Results:** ✅ **Index**
   - **Follow Links:** ✅ **Follow**
   - **Title Format:** `%title% | Ah Ho Fruit`
   - **Description Format:** `%excerpt%`
   - **Schema Type:** ✅ **Product**
   - **Global Product Brand:** `Ah Ho Fruit`
   - Click **Save Changes**

   **Product Categories:**
   - **Show in Search Results:** ✅ **Index**
   - **Follow Links:** ✅ **Follow**
   - Click **Save Changes**

   **Product Tags:**
   - **Show in Search Results:** ⬜ **No Index** (unless you have 100+ products)
   - **Follow Links:** ✅ **Follow**
   - Click **Save Changes**

   **Shop Page:**
   - **Show in Search Results:** ✅ **Index**
   - Click **Save Changes**

   **Cart Page:**
   - **Robots Meta:** ⬜ **No Index**
   - Click **Save Changes**

   **Checkout Page:**
   - **Robots Meta:** ⬜ **No Index**
   - Click **Save Changes**

   **My Account Page:**
   - **Robots Meta:** ⬜ **No Index**
   - Click **Save Changes**

**7.3 Sitemap Settings (5 min)**

1. **Rank Math > Sitemap Settings**

2. **General Tab:**
   - ✅ **XML Sitemap:** ENABLED
   - ✅ **Include Images:** CHECKED
   - **Links Per Sitemap:** `200` (default)
   - Click **Save Changes**

3. **Exclude Posts Tab:**
   - Add any pages you don't want indexed:
     - `sample-page`
     - `vegan-store-home-alt`
     - `cart-2`, `shop-2` (if not deleted)
   - Click **Save Changes**

4. **Verify Sitemap:**
   - Visit: https://ahhofruit.com/sitemap_index.xml
   - Should see product sitemap, page sitemap, category sitemap

**7.4 Schema Settings (5 min)**

1. **Rank Math > General Settings > Schema**

2. **Organization Schema:**
   - **Name:** `Ah Ho Fruit`
   - **Logo:** Upload 112x112px minimum
   - **Contact Info:**
     - **Phone:** `+65 XXXX XXXX`
     - **Contact Type:** `Customer Service`
   - Click **Save Changes**

3. **Social Profiles:**
   - **Facebook:** `https://facebook.com/ahhofruits` (if exists)
   - **Instagram:** `https://instagram.com/ahhofruits` (if exists)
   - **Twitter:** `https://twitter.com/ahhofruits` (if exists)
   - Click **Save Changes**

---

## Phase 3: Optimize Homepage (30 min)

### Step 8: Edit Homepage SEO (15 min)

1. **Pages > All Pages**

2. Find your homepage (usually titled "Home" or might be using a custom front page)

3. If using **static homepage:**
   - **Settings > Reading**
   - Check what page is set as homepage
   - Edit THAT page

4. Click **Edit** on homepage

5. Scroll down to **Rank Math SEO** meta box (below editor)

6. **Focus Keyword Tab:**
   - **Focus Keyword:** `fresh fruit delivery singapore`
   - **Additional Keywords:** (click + Add Keyword)
     - `premium fruit singapore`
     - `japanese fruit delivery`
     - `fruit hampers singapore`

7. **General Tab:**
   - **SEO Title:**
     ```
     Premium Fresh Fruit Delivery Singapore | Ah Ho Fruit
     ```
   - **Description:**
     ```
     Fresh premium fruits delivered daily across Singapore. Japanese Amaou strawberries, Hokkaido melons, Korean grapes & seasonal fruit hampers. Same-day delivery available.
     ```
   - Preview: Check that title is ~55 chars, description ~155 chars

8. **Social Tab:**
   - **Facebook Title:** Same as SEO title
   - **Facebook Description:** Same as description
   - **Facebook Image:** Upload 1200x630px hero image
   - **Twitter Card Type:** `Summary Large Image`
   - **Twitter Title:** Same as SEO title
   - **Twitter Description:** Same as description
   - **Twitter Image:** Same as Facebook image

9. **Schema Tab:**
   - **Schema Type:** `Website`
   - **Article Type:** (leave default)

10. **Advanced Tab:**
    - **Robots Meta:** ✅ Index, ✅ Follow
    - **Canonical URL:** (leave blank - auto-generates)

11. Click **Update**

12. **Verify:**
    - Visit homepage
    - View page source (Ctrl+U / Cmd+U)
    - Search for `<meta name="description"`
    - Should see your new description

---

### Step 9: Optimize Top 5 Important Pages (15 min)

Repeat Step 8 for each of these pages:

**1. About Page**
- **Focus Keyword:** `fresh fruit supplier singapore`
- **Title:** `About Us - Premium Fruit Supplier Since [Year] | Ah Ho Fruit`
- **Description:** `Family-owned fruit supplier specializing in premium Japanese and Korean fruits. [X] years of expertise in sourcing the finest seasonal produce for Singapore.`

**2. Shop/Products Page**
- **Focus Keyword:** `buy fresh fruits online singapore`
- **Title:** `Shop Fresh Fruits Online - Japanese, Korean & Local | Ah Ho Fruit`
- **Description:** `Browse our selection of premium fresh fruits: Japanese strawberries, Hokkaido melons, Korean grapes, seasonal hampers. Same-day delivery across Singapore.`

**3. Gift Hampers Page**
- **Focus Keyword:** `fruit gift hamper singapore`
- **Title:** `Premium Fruit Gift Hampers Singapore | Ah Ho Fruit`
- **Description:** `Elegant fruit gift hampers perfect for corporate gifts, birthdays, celebrations. Beautifully packaged premium fruits with same-day delivery available.`

**4. Delivery Information Page**
- **Focus Keyword:** `fruit delivery singapore`
- **Title:** `Delivery Information - Same Day Fruit Delivery Singapore`
- **Description:** `Same-day fruit delivery across Singapore. Order before 12pm for today's delivery. Free delivery on orders above $100. View our delivery areas and schedule.`

**5. Contact Page**
- **Focus Keyword:** `contact fruit supplier singapore`
- **Title:** `Contact Us - Order Fresh Fruits Today | Ah Ho Fruit`
- **Description:** `Get in touch with Ah Ho Fruit for fresh fruit orders, corporate accounts, or custom hampers. WhatsApp, phone, and email support available daily.`

---

## Phase 4: Google Search Console Setup (20 min)

### Step 10: Set Up Google Search Console (20 min)

**If you connected during Rank Math wizard, skip to Step 10.4**

**10.1 Create Property**

1. Go to https://search.google.com/search-console

2. Click **Add Property**

3. Choose **URL prefix**

4. Enter: `https://ahhofruit.com`

5. Click **Continue**

**10.2 Verify Ownership**

**Method 1: HTML Tag (Easiest)**

1. Copy the verification meta tag:
   ```html
   <meta name="google-site-verification" content="ABC123..." />
   ```

2. In WordPress:
   - **Rank Math > General Settings > Webmaster Tools**
   - Paste the `ABC123...` code (NOT the full meta tag, just the content value)
   - Click **Save Changes**

3. Return to Search Console

4. Click **Verify**

5. Should see "Ownership verified" ✅

**Method 2: HTML File (Alternative)**

1. Download `google1234.html` file

2. Upload to site root via cPanel File Manager:
   - `/public_html/google1234.html`

3. Test: Visit `https://ahhofruit.com/google1234.html`
   - Should show Google verification content

4. Click **Verify** in Search Console

**10.3 Submit Sitemap**

1. In Search Console left menu, click **Sitemaps**

2. Click **Add a new sitemap**

3. Enter: `sitemap_index.xml`

4. Click **Submit**

5. Status should change to "Success" (may take a few minutes)

6. Also submit:
   - `product-sitemap.xml`
   - `page-sitemap.xml`
   - `product_cat-sitemap.xml`

**10.4 Request Indexing for Key Pages**

1. Click **URL Inspection** (top search bar)

2. Enter each URL:
   - `https://ahhofruit.com/`
   - `https://ahhofruit.com/shop/`
   - `https://ahhofruit.com/gift-hampers/`

3. Click **Request Indexing**

4. Repeat for top 5-10 pages

**Result:**
- Pages will be crawled and indexed within 3-7 days
- You'll start seeing search performance data in Search Console

---

## Phase 5: Google Analytics Setup (15 min)

### Step 11: Set Up Google Analytics 4 (15 min)

**11.1 Create GA4 Property**

1. Go to https://analytics.google.com

2. Click **Admin** (bottom left gear icon)

3. Click **Create Property**

4. **Property Details:**
   - **Property name:** `Ah Ho Fruit`
   - **Time zone:** `Singapore (GMT+8)`
   - **Currency:** `Singapore Dollar (SGD)`
   - Click **Next**

5. **Business Details:**
   - **Industry:** `Retail/Food & Drink`
   - **Business size:** `Small (1-10 employees)`
   - Click **Next**

6. **Business Objectives:**
   - ✅ **Get baseline reports**
   - ✅ **Measure customer engagement**
   - ✅ **Measure conversions**
   - Click **Create**

7. Accept Terms of Service

**11.2 Create Data Stream**

1. **Choose platform:** Web

2. **Website URL:** `https://ahhofruit.com`

3. **Stream name:** `Ah Ho Fruit Website`

4. ✅ **Enhanced Measurement:** ENABLED (default)

5. Click **Create Stream**

6. **Copy Measurement ID:**
   ```
   G-XXXXXXXXXX
   ```

**11.3 Install Tracking Code**

**Option A: Via Rank Math (Recommended)**

1. In WordPress: **Rank Math > General Settings > Analytics**

2. Click **Connect Google Account**

3. Sign in with Google account

4. Grant permissions

5. Select:
   - **Account:** Your account
   - **Property:** Ah Ho Fruit
   - **View:** All Web Site Data

6. Click **Save Changes**

**Option B: Manual Installation**

1. Install plugin: **Site Kit by Google**

2. Go to **Site Kit > Settings**

3. Click **Connect Google**

4. Grant permissions

5. Select Analytics property

6. Click **Finish**

**11.4 Enable Enhanced Ecommerce (CRITICAL for WooCommerce)**

1. In Google Analytics, click **Admin**

2. Under **Property**, click **Data Streams**

3. Click your stream name

4. Scroll down to **Enhanced Measurement**

5. Click **Settings** (gear icon)

6. Enable:
   - ✅ **Page views**
   - ✅ **Scrolls**
   - ✅ **Outbound clicks**
   - ✅ **Site search**
   - ✅ **Video engagement**
   - ✅ **File downloads**

7. Click **Save**

8. In WordPress:
   - Install plugin: **WooCommerce Google Analytics Integration**
   - **WooCommerce > Settings > Integration > Google Analytics**
   - Paste **Measurement ID:** `G-XXXXXXXXXX`
   - ✅ **Enable Enhanced Ecommerce:** CHECKED
   - Click **Save Changes**

**11.5 Verify Tracking**

1. Open https://ahhofruit.com in **Incognito/Private window**

2. Browse 2-3 pages

3. In Google Analytics, go to **Reports > Realtime**

4. Should see **1 active user**

5. Click on user to see:
   - Page views
   - Events (page_view, scroll, etc.)

**Result:** ✅ Analytics installed and tracking

---

## Phase 6: Product Category Setup (30 min)

### Step 12: Create & Optimize Product Categories (30 min)

**Categories to Create:**

1. Omakase Boxes
2. Japanese Fruits
3. Korean Fruits
4. Seasonal Fruits
5. Corporate Gifts
6. Fruit Hampers

**For EACH category:**

**12.1 Create Category**

1. **Products > Categories**

2. **Add New Category:**
   - **Name:** `Omakase Boxes`
   - **Slug:** `omakase-boxes` (auto-generates, verify it's clean)
   - **Parent:** None (or choose parent for subcategories)
   - **Description:** (see templates below)
   - Click **Add New Category**

**12.2 Optimize Category SEO**

1. After creating, find category in list

2. Click **Edit**

3. Scroll to **Rank Math SEO** section

4. **Focus Keyword:** (see templates below)

5. **SEO Title:** (60 chars max)

6. **Meta Description:** (160 chars max)

7. **Schema Type:** `CollectionPage`

8. Click **Update**

---

### Category Templates (Copy-Paste Ready)

**1. Omakase Boxes**
```
Focus Keyword: omakase fruit box singapore

SEO Title: Premium Omakase Fruit Boxes Singapore | Ah Ho Fruit

Meta Description: Curated omakase fruit boxes with 8-10 premium seasonal fruits. Hand-selected by experts, beautifully packaged. Same-day delivery available. From $88.

Category Description:
Experience the art of omakase with our expertly curated fruit boxes. Each box features 8-10 premium seasonal fruits hand-selected at peak ripeness from Japan, Korea, and local farms. Perfect for gifts or personal enjoyment.
```

**2. Japanese Fruits**
```
Focus Keyword: japanese fruits singapore

SEO Title: Premium Japanese Fruits Singapore - Amaou, Shine Muscat | Ah Ho

Meta Description: Authentic Japanese fruits: Hokkaido melons, Amaou strawberries, shine muscat grapes. Directly imported from renowned farms. Order online today.

Category Description:
Indulge in premium Japanese fruits sourced directly from renowned farms in Hokkaido, Fukuoka, and Yamanashi. Our selection includes crown melons, Amaou strawberries, shine muscat grapes, and seasonal specialties.
```

**3. Korean Fruits**
```
Focus Keyword: korean fruits singapore

SEO Title: Korean Fruits Singapore - Shine Muscat, Hallabong | Ah Ho Fruit

Meta Description: Fresh Korean fruits: shine muscat grapes, Hallabong tangerines, Korean pears. Premium quality from Korea's top farms. Same-day delivery.

Category Description:
Discover the finest Korean fruits from Jeju Island and premium farms. Our selection features shine muscat grapes, Hallabong tangerines, Korean pears, and seasonal fruits known for exceptional sweetness and quality.
```

**4. Seasonal Fruits**
```
Focus Keyword: seasonal fruits singapore

SEO Title: Seasonal Fresh Fruits Singapore - Premium Quality | Ah Ho Fruit

Meta Description: Fresh seasonal fruits at peak ripeness. Daily rotating selection of premium local and imported fruits. Order today for same-day delivery across Singapore.

Category Description:
Enjoy the best of each season with our rotating selection of premium fruits. We source peak-season produce from local farms and trusted international suppliers, ensuring optimal freshness and flavor year-round.
```

**5. Corporate Gifts**
```
Focus Keyword: corporate fruit gifts singapore

SEO Title: Corporate Fruit Gifts Singapore - Premium Hampers | Ah Ho Fruit

Meta Description: Premium fruit gifts for corporate occasions. Elegantly packaged hampers with custom branding options. Bulk orders, nationwide delivery. Perfect for client appreciation.

Category Description:
Make a lasting impression with premium fruit gifts for your clients and employees. Our corporate collection features elegantly packaged fruit hampers with optional custom branding, perfect for festive seasons, appreciation gifts, and business events.
```

**6. Fruit Hampers**
```
Focus Keyword: fruit hamper singapore

SEO Title: Premium Fruit Hampers Singapore - Gift Baskets | Ah Ho Fruit

Meta Description: Beautiful fruit hampers for all occasions. Premium packaging, ribbon finish, personalized cards. Same-day delivery. Perfect for birthdays, celebrations, get well.

Category Description:
Surprise your loved ones with beautifully curated fruit hampers. Each hamper is carefully arranged with premium seasonal fruits, elegantly packaged with ribbon, and includes a personalized gift card. Perfect for birthdays, celebrations, get well wishes, and special occasions.
```

---

## Phase 7: Install Performance Plugins (20 min)

### Step 13: Install Caching Plugin (10 min)

**Check Server First:**

```bash
# SSH or ask hosting support
php -v
# If shows "LiteSpeed", use LiteSpeed Cache
# If shows "Apache" or "Nginx", use WP Rocket or W3 Total Cache
```

**Option A: LiteSpeed Cache (If on LiteSpeed server - FREE)**

1. **Plugins > Add New**

2. Search: `LiteSpeed Cache`

3. Install and Activate

4. **LiteSpeed Cache > Settings**

5. **Cache Tab:**
   - ✅ **Enable Cache:** ON
   - ✅ **Cache Mobile:** ON
   - ✅ **Cache Logged-in Users:** OFF

6. **Exclude Tab:**
   - **Do Not Cache URIs:**
     ```
     /cart/
     /checkout/
     /my-account/
     /addons/
     ```
   - **Do Not Cache Query Strings:**
     ```
     add-to-cart
     remove_item
     ```

7. Click **Save Changes**

8. **Test:**
   - Visit homepage
   - Refresh (Ctrl+R)
   - View page source
   - Should see `<!-- Page generated by LiteSpeed Cache -->`

**Option B: WP Rocket (Premium - $59/year) or W3 Total Cache (FREE)**

*If not on LiteSpeed, use W3 Total Cache (free but more complex setup)*

1. Install **W3 Total Cache**

2. **Performance > General Settings**

3. **Page Cache:** ✅ Enable, Method: `Disk: Enhanced`

4. **Minify:** ✅ Enable

5. **Browser Cache:** ✅ Enable

6. **Object Cache:** ✅ Enable (if available)

7. **CDN:** (skip for now)

8. Click **Save**

---

### Step 14: Install Image Optimization (10 min)

1. **Plugins > Add New**

2. Search: `Imagify`

3. Install and Activate

4. **Sign up for free account:**
   - 25MB/month free (about 200-300 images)
   - Enter email
   - Verify email
   - Copy API key

5. **Settings > Imagify**

6. Paste API key

7. **Optimization Level:** `Aggressive` (best compression)

8. **Resize Larger Images:** ✅ Enable
   - **Max Width:** `1920px`

9. **Format Conversion:** ✅ **Convert to WebP**

10. **Backup Original Images:** ✅ Enable (recommended)

11. Click **Save Changes**

12. **Bulk Optimize Existing Images:**
    - **Media > Bulk Optimization**
    - Click **Imagify All**
    - Wait for completion

---

## Phase 8: Final Verification (15 min)

### Step 15: Run SEO Tests (15 min)

**Test 1: Google Mobile-Friendly Test**

1. Visit: https://search.google.com/test/mobile-friendly

2. Enter: `https://ahhofruit.com`

3. Click **Test URL**

4. Should see: ✅ **Page is mobile-friendly**

5. If issues found, note them for fixing

**Test 2: Google PageSpeed Insights**

1. Visit: https://pagespeed.web.dev/

2. Enter: `https://ahhofruit.com`

3. Check both:
   - Mobile score (should be >60)
   - Desktop score (should be >80)

4. **Core Web Vitals:**
   - LCP (Largest Contentful Paint): <2.5s ✅
   - INP (Interaction to Next Paint): <200ms ✅
   - CLS (Cumulative Layout Shift): <0.1 ✅

5. If failing, check:
   - Images optimized?
   - Caching enabled?
   - Lazy loading enabled?

**Test 3: Structured Data Test**

1. Visit: https://validator.schema.org/

2. Enter: `https://ahhofruit.com`

3. Should see:
   - ✅ Organization schema
   - ✅ Website schema
   - ✅ BreadcrumbList schema

4. No errors should appear

**Test 4: Meta Tags Test**

```bash
# Check meta description
curl -s https://ahhofruit.com | grep -A2 "meta name=\"description\""

# Should show your custom description, not empty
```

**Test 5: Sitemap Test**

1. Visit: `https://ahhofruit.com/sitemap_index.xml`

2. Should see:
   - page-sitemap.xml
   - product-sitemap.xml
   - product_cat-sitemap.xml
   - post-sitemap.xml (if blog posts exist)

3. Click each sitemap, verify URLs are correct

---

## Completion Checklist

### Critical (Must Do Before Launch - Jan 31)

- [ ] robots.txt uploaded and verified
- [ ] .htaccess uploaded (HTTPS enforced)
- [ ] Fixed 3 catastrophic URL slugs
- [ ] Deleted duplicate WooCommerce pages
- [ ] Rank Math SEO installed and configured
- [ ] Homepage meta description added
- [ ] Google Search Console verified and sitemap submitted
- [ ] Google Analytics tracking installed and verified
- [ ] Top 5 pages optimized (About, Shop, Gift Hampers, Delivery, Contact)
- [ ] 6 product categories created and optimized

### Important (First Week After Launch)

- [ ] Caching plugin installed and configured
- [ ] Image optimization plugin installed
- [ ] Mobile-friendly test passed
- [ ] PageSpeed score >60 mobile, >80 desktop
- [ ] Structured data validated with no errors

### Ongoing (Post-Launch)

- [ ] Add products with SEO-optimized titles/descriptions
- [ ] Monitor Search Console for indexing errors
- [ ] Check Analytics weekly for traffic trends
- [ ] Update meta descriptions based on CTR data
- [ ] Add blog content for long-tail keywords

---

## Time Breakdown

| Phase | Tasks | Time |
|-------|-------|------|
| 1. Critical Fixes | Upload files, fix URLs, delete duplicates | 30 min |
| 2. Rank Math Setup | Install, configure wizard, settings | 45 min |
| 3. Homepage Optimization | SEO meta tags, schema | 30 min |
| 4. Google Search Console | Verify, submit sitemap | 20 min |
| 5. Google Analytics | Setup GA4, enhanced ecommerce | 15 min |
| 6. Product Categories | Create 6 categories, optimize each | 30 min |
| 7. Performance Plugins | Caching, image optimization | 20 min |
| 8. Final Verification | Run tests, verify setup | 15 min |
| **TOTAL** | | **3 hours 25 minutes** |

---

## Need Help?

**Stuck on a step?**
- Take screenshot of error
- Note which step you're on
- Check SEO_CRITICAL_ISSUES_FOUND.md for troubleshooting

**FTP Access Issues?**
- Verify credentials with hosting provider
- Check if FTP is enabled (Vodien sometimes requires activation)
- Try cPanel File Manager as alternative

**WordPress Admin Access?**
- Reset password via `/wp-admin/` "Lost your password?" link
- Check email for reset link

**Plugin Conflicts?**
- Deactivate all plugins except WooCommerce
- Activate Rank Math alone
- Test if issue persists
- Reactivate other plugins one by one

---

## What You'll Have When Complete

✅ **Technical SEO:**
- Clean URL structure
- Optimized robots.txt
- HTTPS enforced
- XML sitemaps submitted
- Google Search Console connected
- Google Analytics tracking

✅ **On-Page SEO:**
- Homepage fully optimized
- 5 key pages optimized
- 6 product categories optimized
- Schema markup implemented
- Breadcrumbs enabled

✅ **Performance:**
- Caching enabled
- Images optimized and converted to WebP
- Mobile-friendly
- Passing Core Web Vitals

✅ **Ready for Launch:**
- Site indexed by Google
- Tracking conversions
- Monitoring search performance
- Foundation for adding products

**Next Step:** Start adding products using templates from `PRODUCT_SEO_TEMPLATES.md`
