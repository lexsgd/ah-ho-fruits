# Ah Ho Fruits - Storeman & Inventory Guide

**Managing stock the fast way!**

*Simple Guide for Shop Owners and Storemen*

---

## What Does the Storeman System Do?

The Storeman system gives warehouse staff a **restricted account** to manage inventory without touching prices, descriptions, or other sensitive settings.

```
+-----------------------------------------------------------------------+
|                    STOREMAN SYSTEM OVERVIEW                            |
+-----------------------------------------------------------------------+
|                                                                       |
|   WHAT STOREMAN CAN DO:               WHAT STOREMAN CANNOT DO:       |
|   -----------------------              -------------------------      |
|   + Update stock quantities            x Change product prices        |
|   + View all products                  x Edit product descriptions    |
|   + Use Quick Stock Update page        x Change product visibility    |
|   + Create and edit orders             x Delete orders                |
|   + View customers                     x Access WordPress settings    |
|   + View commission reports            x Install/remove plugins       |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

# PART 1: Setting Up a Storeman Account

## How to Create a Storeman Account

### Step-by-Step Guide

**Step 1:** Go to Users > Add New

**Step 2:** Fill in the details:

```
+-----------------------------------------------------------------------+
|  ADD NEW USER                                                         |
+-----------------------------------------------------------------------+
|                                                                       |
|  Username:        [ahmad_storeman                    ]                |
|  Email:           [ahmad@company.com                 ]                |
|  First Name:      [Ahmad                             ]                |
|  Last Name:       [Lee                               ]                |
|  Password:        [************] [Generate]                           |
|                                                                       |
|  Role:            [Storeman]  <-- IMPORTANT! Select this role         |
|                                                                       |
|  [Add New User]                                                       |
|                                                                       |
+-----------------------------------------------------------------------+
```

**That's it!** The storeman can now log in and manage stock.

## What the Storeman Sees When Editing a Product

When a storeman opens a product to edit, they only see the **Inventory** tab. All other tabs and pricing fields are hidden:

```
+-----------------------------------------------------------------------+
|  EDIT PRODUCT: Fuji Apple                                             |
+-----------------------------------------------------------------------+
|                                                                       |
|  ADMIN sees:                 STOREMAN sees:                           |
|  -----------                 ---------------                          |
|  [General]                   (hidden)                                 |
|  [Inventory]  <--            [Inventory]  <-- Only this tab!          |
|  [Shipping]                  (hidden)                                 |
|  [Linked Products]           (hidden)                                 |
|  [Attributes]                (hidden)                                 |
|  [Advanced]                  (hidden)                                 |
|                                                                       |
|  Price fields:               (hidden + server-protected)              |
|  Description:                (hidden)                                 |
|  Visibility:                 (hidden)                                 |
|                                                                       |
+-----------------------------------------------------------------------+
```

**Security:** Even if someone tries to submit price changes via browser tools, the server will **reject the changes** and restore the original prices. Prices are protected at the database level.

---

# PART 2: Quick Stock Update Page

This is the **main feature** for storemen. It lets you update stock for ALL products from one page, with no need to open each product individually.

## How to Access

Go to: **Products > Quick Stock Update**

```
+-----------------------------------------------------------------------+
|  WordPress Admin Sidebar                                              |
+-----------------------------------------------------------------------+
|                                                                       |
|  Products                                                             |
|    |-- All Products                                                   |
|    |-- Add new product                                                |
|    |-- Categories                                                     |
|    |-- Tags                                                           |
|    |-- Attributes                                                     |
|    |-- Quick Stock Update  <-- Click here!                            |
|    |-- Reviews                                                        |
|                                                                       |
+-----------------------------------------------------------------------+
```

## Page Layout

The Quick Stock Update page has **4 sections**:

```
+-----------------------------------------------------------------------+
|  QUICK STOCK UPDATE                                                   |
+-----------------------------------------------------------------------+
|                                                                       |
|  1. SUMMARY CARDS (click to filter)                                   |
|  +----------------+ +----------------+ +----------+ +--------------+  |
|  | All Products   | | In Stock       | | Low Stock| | Out of Stock |  |
|  |     127        | |     98         | |    12    | |     17       |  |
|  +----------------+ +----------------+ +----------+ +--------------+  |
|                                                                       |
|  2. FILTERS                                                           |
|  [Select a category v]  [Filter by product name or SKU...    ]       |
|                                                    Showing 45 of 127  |
|                                                                       |
|  3. STOCK TABLE                                                       |
|  +------+------------------+----------+-------+------------------+   |
|  | Img  | Product Name   ^ | Category | Stock | New Stock        |   |
|  +------+------------------+----------+-------+------------------+   |
|  | [pic]| Fuji Apple       | Apples   |  25   | [-] [25 ] [+]   |   |
|  | [pic]| Red Dragonfruit  | Tropical |   3   | [-] [ 3 ] [+]   |   |
|  | [pic]| Solo Papaya      | Tropical |   0   | [-] [ 0 ] [+]   |   |
|  +------+------------------+----------+-------+------------------+   |
|                                                                       |
|  4. STICKY FOOTER                                                     |
|  +---------------------------------------------------------------+   |
|  | [Update All Stock (3)]  3 unsaved changes  Reset all changes  |   |
|  +---------------------------------------------------------------+   |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

## Section 1: Summary Cards

The four cards at the top show a quick overview of your inventory:

```
+-----------------------------------------------------------------------+
|  SUMMARY CARDS                                                        |
+-----------------------------------------------------------------------+
|                                                                       |
|  +------------------+  Each card shows a count and is CLICKABLE:      |
|  |  ALL PRODUCTS    |                                                 |
|  |      127         |  Click "All Products"  --> Show everything      |
|  +------------------+  Click "In Stock"      --> Show stock > 0       |
|                        Click "Low Stock"     --> Show stock 1-5       |
|  Color coding:         Click "Out of Stock"  --> Show stock = 0       |
|  - In Stock:  GREEN                                                   |
|  - Low Stock: YELLOW (1-5 units)                                      |
|  - Out of Stock: RED (0 units)                                        |
|                                                                       |
|  The active card is highlighted in BLUE.                              |
|                                                                       |
+-----------------------------------------------------------------------+
```

**Tip:** Click "Out of Stock" every morning to quickly see what needs restocking!

---

## Section 2: Filters

Two filters work **together** to narrow down the product list:

```
+-----------------------------------------------------------------------+
|  FILTERS                                                              |
+-----------------------------------------------------------------------+
|                                                                       |
|  CATEGORY DROPDOWN:                                                   |
|  [All Categories          v]                                          |
|  [Apples (15)              ]                                          |
|  [Berries (8)              ]                                          |
|  [Citrus (12)              ]                                          |
|  [Tropical (23)            ]                                          |
|  [Stone Fruits (9)         ]                                          |
|                                                                       |
|  SEARCH BOX:                                                          |
|  [Filter by product name or SKU...                                ]   |
|                                                                       |
|  Type "apple" --> Shows all products with "apple" in name or SKU     |
|  Type "CTN"   --> Shows all products with "CTN" in SKU               |
|                                                                       |
|  All filtering is INSTANT (no page reload needed).                    |
|                                                                       |
|  You can COMBINE filters:                                             |
|  Category: "Tropical" + Search: "papaya" + Card: "In Stock"          |
|  = Shows only in-stock tropical papayas                               |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

## Section 3: Stock Table

The main table where you view and edit stock quantities:

### Column Details

```
+-----------------------------------------------------------------------+
|  TABLE COLUMNS                                                        |
+-----------------------------------------------------------------------+
|                                                                       |
|  1. THUMBNAIL (32x32 image)                                           |
|     - Shows product photo                                             |
|     - Hidden on mobile to save space                                  |
|                                                                       |
|  2. PRODUCT NAME (sortable -- click header)                           |
|     - Click the name to open product edit page                        |
|     - SKU shown in gray below the name                                |
|                                                                       |
|  3. CATEGORY (sortable)                                               |
|     - Shows all categories for the product                            |
|     - Hidden on mobile to save space                                  |
|                                                                       |
|  4. CURRENT STOCK (sortable)                                          |
|     - Color coded:                                                    |
|       GREEN  = good (more than 5)                                     |
|       YELLOW = low (1-5 units)                                        |
|       RED    = out of stock (0)                                       |
|                                                                       |
|  5. NEW STOCK (editable)                                              |
|     [-] [  25  ] [+]                                                  |
|      |      |      |                                                  |
|      |      |      +-- Click to add 1                                 |
|      |      +--------- Type a number directly                         |
|      +---------------- Click to subtract 1                            |
|                                                                       |
+-----------------------------------------------------------------------+
```

### Sorting

Click any **column header** to sort:

```
+-----------------------------------------------------------------------+
|  SORTING                                                              |
+-----------------------------------------------------------------------+
|                                                                       |
|  Click "Product Name"  --> Sort A-Z (click again for Z-A)            |
|  Click "Category"      --> Sort A-Z (click again for Z-A)            |
|  Click "Stock"         --> Sort lowest first (click again for highest)|
|                                                                       |
|  Arrow shows direction:  ^ = ascending    v = descending              |
|                                                                       |
|  TIP: Sort by Stock (ascending) to see out-of-stock items first!     |
|                                                                       |
+-----------------------------------------------------------------------+
```

### Editing Stock

When you change a stock value, the row turns **yellow** to show it has unsaved changes:

```
+-----------------------------------------------------------------------+
|  EDITING STOCK                                                        |
+-----------------------------------------------------------------------+
|                                                                       |
|  BEFORE EDIT:                                                         |
|  | Fuji Apple        | Apples   |  25   | [-] [25 ] [+]   |         |
|                                                                       |
|  AFTER EDIT (row turns yellow):                                       |
|  | Fuji Apple        | Apples   |  25   | [-] [30 ] [+]   | YELLOW  |
|                                                                       |
|  THREE WAYS TO EDIT:                                                  |
|  1. Click [-] or [+] buttons to adjust by 1                          |
|  2. Click the number and type a new value                             |
|  3. Use keyboard: Tab/Enter to move between rows                     |
|                                                                       |
|  KEYBOARD SHORTCUTS:                                                  |
|  Enter      --> Move to next product                                  |
|  Arrow Down --> Move to next product                                  |
|  Arrow Up   --> Move to previous product                              |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

## Section 4: Sticky Footer (Save Bar)

The save bar stays at the bottom of the screen so you can always see it:

```
+-----------------------------------------------------------------------+
|  STICKY FOOTER                                                        |
+-----------------------------------------------------------------------+
|                                                                       |
|  +---------------------------------------------------------------+   |
|  | [Update All Stock (5)]  5 unsaved changes  Reset all changes  |   |
|  +---------------------------------------------------------------+   |
|                                                                       |
|  BUTTON: "Update All Stock (5)"                                       |
|  - Shows the number of products you've changed                        |
|  - Click to save ALL changes at once                                  |
|  - A confirmation popup will ask: "Update stock for 5 product(s)?"    |
|                                                                       |
|  COUNTER: "5 unsaved changes" (red text)                              |
|  - Reminds you to save before leaving                                 |
|                                                                       |
|  LINK: "Reset all changes"                                            |
|  - Undoes ALL edits and restores original values                      |
|  - Only appears when you have unsaved changes                         |
|                                                                       |
|  AFTER SAVING:                                                        |
|  - Updated rows flash GREEN for 2 seconds                             |
|  - Summary cards update with new counts                               |
|  - Success message: "5 product(s) updated successfully."              |
|                                                                       |
+-----------------------------------------------------------------------+
```

**Warning:** If you try to leave the page with unsaved changes, the browser will ask "Are you sure you want to leave?"

---

# PART 3: Typical Daily Workflow

## Morning Stock Check

```
+-----------------------------------------------------------------------+
|  MORNING STOCK CHECK WORKFLOW                                         |
+-----------------------------------------------------------------------+
|                                                                       |
|  1. Log in to WordPress admin                                         |
|                  |                                                    |
|                  v                                                    |
|  2. Go to Products > Quick Stock Update                               |
|                  |                                                    |
|                  v                                                    |
|  3. Click "Out of Stock" summary card                                 |
|     --> See all products that need restocking                         |
|                  |                                                    |
|                  v                                                    |
|  4. Check deliveries received and update quantities                   |
|     --> Use +/- buttons or type new values                            |
|                  |                                                    |
|                  v                                                    |
|  5. Click "Update All Stock"                                          |
|     --> Confirm the update                                            |
|     --> All changes saved!                                            |
|                  |                                                    |
|                  v                                                    |
|  6. Click "Low Stock" card to check what's running low                |
|     --> Plan your next order accordingly                              |
|                                                                       |
+-----------------------------------------------------------------------+
```

## Receiving a Delivery

```
+-----------------------------------------------------------------------+
|  RECEIVING DELIVERY WORKFLOW                                          |
+-----------------------------------------------------------------------+
|                                                                       |
|  1. Go to Quick Stock Update                                          |
|                  |                                                    |
|                  v                                                    |
|  2. Use CATEGORY FILTER to select the delivery category               |
|     Example: Select "Tropical" for a tropical fruit delivery          |
|                  |                                                    |
|                  v                                                    |
|  3. For each product received:                                        |
|     - Find it in the list (use search if needed)                      |
|     - Enter the NEW total quantity                                    |
|       (current stock + received quantity)                             |
|                  |                                                    |
|                  v                                                    |
|  4. Click "Update All Stock" when done                                |
|                                                                       |
|  EXAMPLE:                                                             |
|  Papaya currently shows 3 in stock.                                   |
|  You received 20 more.                                                |
|  Type 23 in the "New Stock" field (3 + 20 = 23).                     |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

# PART 4: Mobile Usage

The Quick Stock Update page works on **phones and tablets**. On smaller screens:

```
+-----------------------------------------------------------------------+
|  MOBILE LAYOUT                                                        |
+-----------------------------------------------------------------------+
|                                                                       |
|  CHANGES ON MOBILE:                                                   |
|  - Summary cards arrange in a 2x2 grid                                |
|  - Thumbnail and Category columns are hidden                          |
|  - SKU is hidden (just product name shown)                            |
|  - +/- buttons are larger (easier to tap)                             |
|  - Search box goes full width                                         |
|  - Update button goes full width                                      |
|                                                                       |
|  MOBILE VIEW:                                                         |
|  +-------------------------------------------+                       |
|  | +-------+ +-------+                       |                       |
|  | |  All  | |In Stock|                       |                       |
|  | |  127  | |   98   |                       |                       |
|  | +-------+ +-------+                       |                       |
|  | +-------+ +-------+                       |                       |
|  | |  Low  | |  Out  |                       |                       |
|  | |   12  | |   17  |                       |                       |
|  | +-------+ +-------+                       |                       |
|  |                                            |                       |
|  | [All Categories               v]          |                       |
|  | [Search...                     ]          |                       |
|  |                                            |                       |
|  | Product Name    | Stock | New Stock       |                       |
|  | ----------------+-------+---------------- |                       |
|  | Fuji Apple      |  25   | [-] [25] [+]   |                       |
|  | Red Dragonfruit |   3   | [-] [ 3] [+]   |                       |
|  | Solo Papaya     |   0   | [-] [ 0] [+]   |                       |
|  |                                            |                       |
|  | [    Update All Stock (3)              ]  |                       |
|  +-------------------------------------------+                       |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

# PART 5: Troubleshooting

## Common Issues and Fixes

```
+-----------------------------------------------------------------------+
|  PROBLEM: "Permission Denied" when accessing Quick Stock Update       |
+-----------------------------------------------------------------------+
|                                                                       |
|  CAUSE: User does not have the Storeman or Admin role.                |
|                                                                       |
|  FIX:                                                                 |
|  1. Go to Users > All Users                                          |
|  2. Find the user and click "Edit"                                    |
|  3. Change their Role to "Storeman"                                   |
|  4. Click "Update User"                                               |
|                                                                       |
+-----------------------------------------------------------------------+

+-----------------------------------------------------------------------+
|  PROBLEM: Stock changes not saving (error message)                    |
+-----------------------------------------------------------------------+
|                                                                       |
|  CAUSE: Session may have expired (security token timeout).            |
|                                                                       |
|  FIX:                                                                 |
|  1. Refresh the page (F5)                                             |
|  2. Make your changes again                                           |
|  3. Click "Update All Stock"                                          |
|                                                                       |
|  If still not working: Log out and log back in.                       |
|                                                                       |
+-----------------------------------------------------------------------+

+-----------------------------------------------------------------------+
|  PROBLEM: Product not appearing in the list                           |
+-----------------------------------------------------------------------+
|                                                                       |
|  CAUSE: Product may be in Draft/Private status or filter may be on.   |
|                                                                       |
|  FIX:                                                                 |
|  1. Click "All Products" summary card to remove filters               |
|  2. Clear the search box                                              |
|  3. Set category to "All Categories"                                  |
|  4. If still missing: Product may be Draft -- ask admin to publish    |
|                                                                       |
|  NOTE: Only PUBLISHED products appear in Quick Stock Update.          |
|                                                                       |
+-----------------------------------------------------------------------+

+-----------------------------------------------------------------------+
|  PROBLEM: "Are you sure you want to leave?" popup                     |
+-----------------------------------------------------------------------+
|                                                                       |
|  CAUSE: You have unsaved stock changes.                               |
|                                                                       |
|  FIX:                                                                 |
|  Option 1: Click "Stay" and then "Update All Stock" to save          |
|  Option 2: Click "Leave" if you want to discard changes              |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

# PART 6: Stock Color Guide

Quick reference for the color coding used throughout the system:

```
+-----------------------------------------------------------------------+
|  STOCK COLOR REFERENCE                                                |
+-----------------------------------------------------------------------+
|                                                                       |
|  COLOR       MEANING              WHEN                                |
|  ----------  -------------------  ----------------------------        |
|  GREEN       Good stock level     More than 5 units                   |
|  YELLOW      Low stock warning    Between 1 and 5 units               |
|  RED         Out of stock         0 units                             |
|                                                                       |
|  ROW COLORS:                                                          |
|  ----------  -------------------  ----------------------------        |
|  YELLOW ROW  Unsaved change       You edited but haven't saved        |
|  GREEN ROW   Just saved           Flashes green for 2 seconds         |
|  WHITE ROW   No changes           Normal state                        |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

# PART 7: Security Overview

## What Protects the System

```
+-----------------------------------------------------------------------+
|  SECURITY LAYERS                                                      |
+-----------------------------------------------------------------------+
|                                                                       |
|  LAYER 1: ROLE-BASED ACCESS                                          |
|  Only Admin and Storeman roles can access Quick Stock Update.         |
|  Other roles (customers, subscribers) cannot see the page.            |
|                                                                       |
|  LAYER 2: HIDDEN FIELDS (UI)                                         |
|  When a storeman edits a product, pricing and description fields      |
|  are hidden with CSS. They cannot see or interact with them.          |
|                                                                       |
|  LAYER 3: SERVER-SIDE PROTECTION                                      |
|  Even if someone bypasses the hidden fields using browser tools,      |
|  the server RESTORES original prices after every save.                |
|  Prices CANNOT be changed by a storeman. Period.                      |
|                                                                       |
|  LAYER 4: NONCE VERIFICATION                                         |
|  Every stock update request includes a security token.                |
|  Requests without a valid token are rejected (prevents CSRF).         |
|                                                                       |
|  LAYER 5: INPUT VALIDATION                                            |
|  Product IDs and quantities are validated server-side.                |
|  Negative quantities are automatically set to 0.                      |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

# PART 8: Technical Reference (For Developers)

## File Locations

```
Plugin Root: wp-content/plugins/ah-ho-custom/

Storeman Files:
  includes/storeman-product-access.php    -- Product edit restrictions
  includes/storeman-inventory.php         -- Quick Stock Update page
  includes/salesperson-roles.php          -- Storeman role & capabilities
```

## Key Functions

| Function | File | Purpose |
|----------|------|---------|
| `ah_ho_is_current_user_storeman()` | storeman-product-access.php | Check if user has storeman role |
| `ah_ho_storeman_product_tabs()` | storeman-product-access.php | Restrict product tabs to Inventory only |
| `ah_ho_storeman_hide_product_fields()` | storeman-product-access.php | CSS hide pricing/description fields |
| `ah_ho_storeman_protect_price_fields()` | storeman-product-access.php | Server-side price protection |
| `ah_ho_register_stock_update_page()` | storeman-inventory.php | Register admin menu page |
| `ah_ho_render_quick_stock_page()` | storeman-inventory.php | Render the Quick Stock Update UI |
| `ah_ho_bulk_update_stock_handler()` | storeman-inventory.php | AJAX handler for bulk stock updates |
| `ah_ho_register_storeman_role()` | salesperson-roles.php | Create storeman role on activation |
| `ah_ho_update_storeman_role()` | salesperson-roles.php | Sync capabilities on plugin load |

## WordPress Hooks Used

| Hook | Type | Purpose |
|------|------|---------|
| `admin_menu` | Action | Register Quick Stock Update submenu |
| `admin_head` | Action | Inject CSS to hide fields for storeman |
| `woocommerce_product_data_tabs` | Filter | Remove tabs except Inventory |
| `woocommerce_process_product_meta` | Action | Protect price fields on save |
| `wp_ajax_ah_ho_bulk_update_stock` | Action | Handle AJAX stock updates |
| `plugins_loaded` | Action | Register/update storeman role |

## AJAX Endpoint

```
URL:    /wp-admin/admin-ajax.php
Action: ah_ho_bulk_update_stock
Method: POST

Request:
  nonce:   (string)  Security token from wp_create_nonce()
  updates: (JSON)    [{"product_id": 123, "new_qty": 50}, ...]

Response (success):
  {"success": true, "data": {"message": "5 product(s) updated.", "updated": 5, "errors": []}}

Response (error):
  {"success": false, "data": {"message": "Security check failed."}}
```

## Storeman Role Capabilities

| Capability | Granted | Purpose |
|-----------|---------|---------|
| `read` | Yes | Basic WordPress access |
| `edit_products` | Yes | Edit product inventory |
| `edit_others_products` | Yes | Edit all products |
| `edit_published_products` | Yes | Edit published products |
| `edit_shop_orders` | Yes | Edit orders |
| `create_shop_orders` | Yes | Create orders |
| `list_users` | Yes | View user list |
| `create_users` | Yes | Create customers |
| `delete_shop_orders` | No | Cannot delete orders |
| `manage_options` | No | Cannot access settings |
| `install_plugins` | No | Cannot manage plugins |

---

# Quick Reference Card

```
+-----------------------------------------------------------------------+
|  STOREMAN QUICK REFERENCE                                             |
+-----------------------------------------------------------------------+
|                                                                       |
|  ACCESS:    Products > Quick Stock Update                             |
|  FILTER:    Category dropdown + Search box + Summary cards            |
|  EDIT:      Click +/- buttons or type numbers directly                |
|  NAVIGATE:  Enter or Arrow keys to move between rows                  |
|  SAVE:      Click "Update All Stock" button at bottom                 |
|  UNDO:      Click "Reset all changes" to discard edits                |
|  SORT:      Click column headers (Name, Category, Stock)              |
|                                                                       |
|  COLORS:    GREEN = good | YELLOW = low (1-5) | RED = out (0)        |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

*Last Updated: February 2026*
*Plugin Version: 1.5.0*
