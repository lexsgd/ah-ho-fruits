# Ah Ho Fruit - Order Management Guide

**Edit orders, track deliveries, and handle returns like a pro!**

*Simple Guide for Shop Owners and Staff*

---

## What Does Order Management Do?

This system gives you **3 powerful tools** inside every order to handle real-world B2B situations:

```
+-----------------------------------------------------------------------+
|                    ORDER MANAGEMENT FEATURES                           |
+-----------------------------------------------------------------------+
|                                                                       |
|   1. EDIT ITEMS                                                |
|      Change quantities, add products, remove items                    |
|      with full audit trail of every change                            |
|                                                                       |
|   2. PARTIAL DELIVERIES                                        |
|      Deliver part of an order now, rest later                         |
|      Print a Delivery Order (DO) for each batch                       |
|                                                                       |
|   3. RETURNS & INVOICE ADJUSTMENTS                             |
|      Record damaged/returned items                                    |
|      Invoice automatically shows deductions                           |
|                                                                       |
+-----------------------------------------------------------------------+
```

## Where to Find It

All three features live inside the **"Deliveries & Returns"** metabox on every order edit page:

```
+-----------------------------------------------------------------------+
|  Deliveries & Returns                                                 |
+-----------------------------------------------------------------------+
|                                                                       |
|  [Deliveries] [Not Started]    [Returns]    [Edit Items]              |
|   ^^^^^^^^^^                                 ^^^^^^^^^^               |
|   Tab 1: Track               Tab 2: Handle   Tab 3: Change           |
|   what's been                damaged goods    order contents           |
|   delivered                  and refunds      after placement          |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

# PART 1: Edit Items (Order Editing)

## When to Use

```
+-----------------------------------------------------------------------+
|  COMMON SCENARIOS                                                     |
+-----------------------------------------------------------------------+
|                                                                       |
|  "Customer calls to add 10 more boxes of oranges"                     |
|  --> Edit Items tab --> Change qty --> Save                           |
|                                                                       |
|  "Customer wants to cancel the pears from their order"                |
|  --> Edit Items tab --> Remove item --> Save                          |
|                                                                       |
|  "Customer ordered 50 boxes but now only wants 30"                    |
|  --> Edit Items tab --> Change qty from 50 to 30 --> Save             |
|                                                                       |
|  IMPORTANT: Every edit is logged with WHO did it, WHEN,               |
|  and WHY (the reason you type in).                                    |
|                                                                       |
+-----------------------------------------------------------------------+
```

## How to Edit an Order

### Step 1: Open the Order

Go to **WooCommerce > Orders** and click the order you want to edit.

### Step 2: Click "Edit Items" Tab

Scroll down to the **Deliveries & Returns** metabox and click the **Edit Items** tab:

```
+-----------------------------------------------------------------------+
|  Deliveries & Returns                                                 |
+-----------------------------------------------------------------------+
|                                                                       |
|  [Deliveries]    [Returns]    [Edit Items]  <-- Click this            |
|                                                                       |
|  +------+------------------------------+-----+-------+-----+         |
|  |      | Product                      | Cur | New   |     |         |
|  +------+------------------------------+-----+-------+-----+         |
|  |      | 72" CN. FUJI APPLE           |  5  | [ 5 ] |  x  |         |
|  |      | 40" CHINA ORANGE             |  3  | [ 3 ] |  x  |         |
|  +------+------------------------------+-----+-------+-----+         |
|                                                                       |
|  Add Product:                                                         |
|  [Search for a product...               ] Qty: [1] [+ Add]           |
|                                                                       |
|  Reason for edit: [e.g. Customer called to change order_____]         |
|                                                                       |
|  [Save Changes]                                                       |
|                                                                       |
+-----------------------------------------------------------------------+
```

### Step 3: Make Your Changes

**To change a quantity:**
- Find the item in the table
- Change the number in the **"New Qty"** column
- The row turns **yellow** to show it's been changed

**To add a new product:**
- Click the search box under "Add Product"
- Type the product name (e.g. "pear")
- Select the product from the dropdown
- Set the quantity
- Click **"+ Add"**
- The new item appears in the table with a **green "(new)"** label

**To remove an item:**
- Click the **x** button next to the item
- The row turns **red** to show it's marked for removal
- Click **x** again to undo the removal

```
+-----------------------------------------------------------------------+
|  WHAT THE COLORS MEAN                                                 |
+-----------------------------------------------------------------------+
|                                                                       |
|  YELLOW row  = Quantity has been changed (not saved yet)              |
|  GREEN row   = New product added (not saved yet)                      |
|  RED row     = Item marked for removal (not saved yet)                |
|                                                                       |
|  Nothing is saved until you click "Save Changes"!                     |
|                                                                       |
+-----------------------------------------------------------------------+
```

### Step 4: Enter a Reason and Save

**Step 4a:** Type a reason in the "Reason for edit" field. This is **required**.

Examples:
- "Customer called to add 10 more boxes"
- "Wrong product ordered, replacing with correct one"
- "Customer reduced order size"

**Step 4b:** Click **"Save Changes"**

A confirmation dialog will appear showing what will change:

```
+-----------------------------------------------------------------------+
|  CONFIRMATION DIALOG                                                  |
+-----------------------------------------------------------------------+
|                                                                       |
|  Save these changes?                                                  |
|                                                                       |
|  1 qty change(s), 1 item(s) added                                    |
|  Reason: Customer called to change order                              |
|                                                                       |
|  [OK]  [Cancel]                                                       |
|                                                                       |
+-----------------------------------------------------------------------+
```

After saving, the page reloads with updated quantities, totals, and the edit is logged.

## Edit History

Every edit is recorded and shown at the bottom of the Edit Items tab:

```
+-----------------------------------------------------------------------+
|  Edit History                                                         |
+-----------------------------------------------------------------------+
|                                                                       |
|  Feb 26, 2026 8:55 am -- Customer added pears to order               |
|  [+ CN FRAGRANT PEAR x2]                                             |
|  Total: $270.00 --> $340.00                          by admin         |
|                                                                       |
|  Feb 26, 2026 8:53 am -- Customer reduced apple order                |
|  [72" CN. FUJI APPLE: 5 --> 3]                                       |
|  Total: $380.00 --> $270.00                          by admin         |
|                                                                       |
+-----------------------------------------------------------------------+

Color-coded badges:
  YELLOW = Quantity changed
  GREEN  = Item added
  RED    = Item removed
```

## Important Rules

```
+-----------------------------------------------------------------------+
|  RULES & RESTRICTIONS                                                 |
+-----------------------------------------------------------------------+
|                                                                       |
|  1. REASON IS REQUIRED                                                |
|     You must type a reason for every edit. This protects               |
|     against accidental changes.                                        |
|                                                                       |
|  2. CAN'T GO BELOW DELIVERED QUANTITY                                 |
|     If 3 boxes of apples have been delivered, you can't               |
|     change the qty to less than 3.                                     |
|                                                                       |
|  3. CAN'T REMOVE DELIVERED OR RETURNED ITEMS                         |
|     The X button is grayed out for items that have                    |
|     delivery or return records. Use "Returns" tab instead.             |
|                                                                       |
|  4. INVOICE UPDATES AUTOMATICALLY                                     |
|     After editing, the invoice PDF will reflect the new                |
|     quantities and totals. No extra action needed.                     |
|                                                                       |
|  5. ORDER NOTES LOGGED AUTOMATICALLY                                  |
|     Every edit creates a note in the Order Notes sidebar               |
|     with full details of what changed.                                 |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

# PART 2: Partial Deliveries

## When to Use

```
+-----------------------------------------------------------------------+
|  COMMON SCENARIOS                                                     |
+-----------------------------------------------------------------------+
|                                                                       |
|  Customer orders 60 boxes, but truck only fits 40:                    |
|  --> Record delivery of 40 today, remaining 20 tomorrow               |
|                                                                       |
|  Customer wants apples ASAP but oranges can wait:                     |
|  --> Deliver apples today, oranges next week                          |
|                                                                       |
|  Multiple delivery runs per day:                                      |
|  --> Record "Morning batch" and "Afternoon batch" separately          |
|                                                                       |
+-----------------------------------------------------------------------+
```

## How It Works

```
+-----------------------------------------------------------------------+
|  PARTIAL DELIVERY LIFECYCLE                                           |
+-----------------------------------------------------------------------+
|                                                                       |
|  Order #4287: 5x Apple, 3x Orange, 2x Pear                          |
|                                                                       |
|  Day 1: Deliver 3x Apple, 2x Orange                                  |
|          Status: PARTIAL (5 of 10 delivered)                          |
|          Print DO --> Shows only 3x Apple, 2x Orange                  |
|                                                                       |
|  Day 3: Deliver 2x Apple, 1x Orange, 2x Pear                        |
|          Status: COMPLETE (10 of 10 delivered)                        |
|          Print DO --> Shows only 2x Apple, 1x Orange, 2x Pear        |
|                                                                       |
+-----------------------------------------------------------------------+
```

## How to Record a Delivery

### Step 1: Open the Order and Click "Deliveries" Tab

```
+-----------------------------------------------------------------------+
|  Deliveries & Returns                                                 |
+-----------------------------------------------------------------------+
|                                                                       |
|  [Deliveries] [Not Started]    [Returns]    [Edit Items]              |
|   ^^^^^^^^^^                                                          |
|                                                                       |
|  Overall: 0 delivered of 10 (10 remaining)                            |
|                                                                       |
|  +----------+---------+----------+-----------+-----------+---------+  |
|  | Product  | Ordered | Returned | Effective | Delivered | Balance |  |
|  +----------+---------+----------+-----------+-----------+---------+  |
|  | Apple    |    5    |    0     |     5     |     0     |    5    |  |
|  | Orange   |    3    |    0     |     3     |     0     |    3    |  |
|  | Pear     |    2    |    0     |     2     |     0     |    2    |  |
|  +----------+---------+----------+-----------+-----------+---------+  |
|                                                                       |
+-----------------------------------------------------------------------+
```

### Step 2: Fill in the Delivery Form

```
+-----------------------------------------------------------------------+
|  Record Delivery                                                      |
+-----------------------------------------------------------------------+
|                                                                       |
|  Date: [2026-02-26]   Notes: [Morning batch__________________]        |
|                                                                       |
|  +----------+---------+------------+                                  |
|  | Product  | Balance | Deliver Qty|                                  |
|  +----------+---------+------------+                                  |
|  | Apple    |    5    |   [ 3 ]    |  <-- Enter how many              |
|  | Orange   |    3    |   [ 2 ]    |      to deliver                  |
|  | Pear     |    2    |   [ 0 ]    |      this batch                  |
|  +----------+---------+------------+                                  |
|                                                                       |
|  [Record Delivery]                                                    |
|                                                                       |
+-----------------------------------------------------------------------+
```

### Step 3: Click "Record Delivery"

The page updates instantly:
- Status badge changes to **"Partial"** (orange) or **"Complete"** (green)
- Delivery history shows your record
- Balance column updates

## Delivery History & Print DO

After recording deliveries, you'll see a history with **Print DO** links:

```
+-----------------------------------------------------------------------+
|  Delivery History                                                     |
+-----------------------------------------------------------------------+
|                                                                       |
|  Feb 26, 2026 -- Morning batch              by admin           [x]   |
|  [Apple x3] [Orange x2]                          Print DO -->        |
|                                                                       |
|  Feb 28, 2026 -- Afternoon run               by admin          [x]   |
|  [Apple x2] [Orange x1] [Pear x2]                Print DO -->        |
|                                                                       |
+-----------------------------------------------------------------------+
```

### Print DO (Delivery Order PDF)

Click **"Print DO"** next to any delivery record to generate a PDF **just for that batch**:

```
+-----------------------------------------------------------------------+
|  DELIVERY ORDER (1 of 2)                                              |
+-----------------------------------------------------------------------+
|                                                                       |
|  AH HO FRUIT TRADING CO.                                             |
|  Invoice No: 4287         Date: 26/2/2026                             |
|                                                                       |
|  Deliver To: [Customer Name & Address]                                |
|                                                                       |
|  Remarks: Morning batch                                               |
|                                                                       |
|  +------+----------------------------------+                          |
|  | Qty  | Description                      |                          |
|  +------+----------------------------------+                          |
|  |  3   | 72" CN. FUJI APPLE *BAOXIANG*    |  <-- Only this           |
|  |  2   | 40" CHINA ORANGE                 |      batch's items!       |
|  +------+----------------------------------+                          |
|                                                                       |
|  Delivered by: ________    Customer Signature: ________               |
|                                                                       |
+-----------------------------------------------------------------------+
```

**Key points:**
- The PDF shows **only the items in that delivery batch**, not the full order
- Title says **"Delivery Order (1 of 2)"** or **(2 of 2)** etc.
- Date is the **delivery date**, not the order date
- Delivery notes appear under Remarks
- Signature lines for driver and customer

## Status Badges

```
+-----------------------------------------------------------------------+
|  DELIVERY STATUS BADGES                                               |
+-----------------------------------------------------------------------+
|                                                                       |
|  [Not Started]  GRAY    = No deliveries recorded yet                  |
|  [Partial]      ORANGE  = Some items delivered, some remaining        |
|  [Complete]     GREEN   = All items fully delivered                    |
|                                                                       |
|  The badge appears on the Deliveries tab and updates automatically    |
|  when you record or delete deliveries.                                |
|                                                                       |
|  NOTE: Returns reduce the "effective" quantity. If you ordered 5,     |
|  returned 1, you only need to deliver 4 for "Complete" status.        |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

# PART 3: Returns & Invoice Adjustments

## When to Use

```
+-----------------------------------------------------------------------+
|  COMMON SCENARIOS                                                     |
+-----------------------------------------------------------------------+
|                                                                       |
|  "Customer received 2 boxes of damaged apples"                        |
|  --> Returns tab --> Enter qty 2 + reason --> Process Return           |
|                                                                       |
|  "Customer rejects 1 box — wrong product delivered"                   |
|  --> Returns tab --> Enter qty 1 + reason --> Process Return           |
|                                                                       |
|  After processing, the INVOICE automatically deducts the return       |
|  so the customer sees the correct amount to pay!                      |
|                                                                       |
+-----------------------------------------------------------------------+
```

## How to Process a Return

### Step 1: Go to "Returns" Tab

```
+-----------------------------------------------------------------------+
|  Deliveries & Returns                                                 |
+-----------------------------------------------------------------------+
|                                                                       |
|  [Deliveries]    [Returns]  <-- Click this    [Edit Items]            |
|                                                                       |
+-----------------------------------------------------------------------+
```

### Step 2: Fill in the Return Form

```
+-----------------------------------------------------------------------+
|  Process Return                                                       |
+-----------------------------------------------------------------------+
|                                                                       |
|  Reason: [2 boxes damaged during delivery____________]  <-- Required  |
|                                                                       |
|  +----------+---------+----------+-----------+------------+           |
|  | Product  | Ordered | Returned | Available | Return Qty |           |
|  +----------+---------+----------+-----------+------------+           |
|  | Apple    |    5    |    0     |     5     |   [ 2 ]    |           |
|  | Orange   |    3    |    0     |     3     |   [ 0 ]    |           |
|  | Pear     |    2    |    0     |     2     |   [ 0 ]    |           |
|  +----------+---------+----------+-----------+------------+           |
|                                                                       |
|  [Process Return]  <-- RED button                                     |
|                                                                       |
+-----------------------------------------------------------------------+
```

### Step 3: Confirm and Process

A confirmation dialog appears:

```
+-----------------------------------------------------------------------+
|  CONFIRMATION                                                         |
+-----------------------------------------------------------------------+
|                                                                       |
|  This will process a return and adjust the order total.               |
|  This action cannot be easily undone. Continue?                       |
|                                                                       |
|  [OK]  [Cancel]                                                       |
|                                                                       |
+-----------------------------------------------------------------------+
```

After confirming:
- A **WooCommerce refund** is created automatically
- Stock is **restocked** automatically
- Order total is reduced
- An order note is added with full details

## What Happens After a Return

```
+-----------------------------------------------------------------------+
|  AFTER PROCESSING A RETURN                                            |
+-----------------------------------------------------------------------+
|                                                                       |
|  ORDER PAGE:                                                          |
|  - Refund line appears: -$110.00                                      |
|  - Net Payment shows: $270.00 (was $380.00)                          |
|  - Order note: "Item return processed (2 boxes damaged):              |
|    72" CN. FUJI APPLE x2. Invoice reduced by $110.00."               |
|                                                                       |
|  DELIVERIES TAB:                                                      |
|  - "Returned" column shows -2 in red                                  |
|  - "Effective" qty drops from 5 to 3                                  |
|  - Balance recalculated based on effective qty                        |
|                                                                       |
|  RETURNS TAB:                                                         |
|  - Return history entry with date, amount, reason, items              |
|  - Red dot badge on the Returns tab                                   |
|                                                                       |
+-----------------------------------------------------------------------+
```

## How Returns Appear on the Invoice

This is the most important part for B2B customers. The invoice now shows returns clearly:

```
+-----------------------------------------------------------------------+
|  INVOICE #4287                                                        |
+-----------------------------------------------------------------------+
|                                                                       |
|  Qty | Description                           | Unit Price | Amount   |
|  ----+---------------------------------------+------------+--------  |
|   5  | 72" CN. FUJI APPLE *BAOXIANG*         |   $55.00   | $275.00  |
|   3  | 40" CHINA ORANGE                      |   $35.00   | $105.00  |
|      |                                       |            |          |
|   RETURN: 2x 72" CN. FUJI APPLE *BAOXIANG*  |            |          |
|   Reason: 2 boxes damaged during delivery    |            | -$110.00 |
|      |  <-- RED TEXT                         |            |          |
|  ----+---------------------------------------+------------+--------  |
|                                                                       |
|                                    Subtotal:   $380.00               |
|                                    Less: Returns:  -$110.00  (RED)   |
|                                    TOTAL:      $270.00               |
|                                                                       |
|  PAYMENT REQUIRED: $270.00 - Due by 28 Mar 2026                      |
|                                                                       |
+-----------------------------------------------------------------------+
```

**What the customer sees:**
1. **Original items** with original quantities and prices
2. **Return line item** in RED showing what was returned and why
3. **"Less: Returns"** deduction in the totals section
4. **Correct NET total** as the amount to pay
5. **PAYMENT REQUIRED** shows the correct net amount

**Special cases:**
- If the order is **fully refunded**, the invoice shows **"FULLY CREDITED — No payment required"**
- For **COD orders**, the return is flagged as "Refund Required" (cash needs to be returned manually)
- For **credit term orders**, the return reduces the invoice automatically

## COD vs Credit Term Returns

```
+-----------------------------------------------------------------------+
|  HOW RETURNS WORK BY PAYMENT TYPE                                     |
+-----------------------------------------------------------------------+
|                                                                       |
|  COD (Cash on Delivery):                                              |
|  - Flagged as "Refund Required"                                       |
|  - Order note: "COD order - monetary refund of $110 required"         |
|  - Admin must manually return cash to customer                        |
|                                                                       |
|  Credit Terms (Net 7 / Net 14 / Net 30):                             |
|  - Invoice total automatically reduced                                |
|  - Order note: "Credit customer - invoice reduced by $110"            |
|  - No manual action needed — next payment is lower                    |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

# PART 4: Putting It All Together

## Typical B2B Order Lifecycle

```
+-----------------------------------------------------------------------+
|  COMPLETE ORDER LIFECYCLE EXAMPLE                                     |
+-----------------------------------------------------------------------+
|                                                                       |
|  1. CUSTOMER PLACES ORDER                                             |
|     Order #4287: 5x Apple ($275), 3x Orange ($105), 2x Pear ($70)   |
|     Total: $450                                                       |
|              |                                                        |
|              v                                                        |
|  2. CUSTOMER CALLS TO CHANGE                                         |
|     "Add 1 more box of oranges" --> Edit Items tab                    |
|     Orange: 3 --> 4 (+$35)                                            |
|     New Total: $485                                                   |
|              |                                                        |
|              v                                                        |
|  3. FIRST DELIVERY (morning)                                          |
|     Deliver: 3x Apple, 2x Orange --> Record in Deliveries tab        |
|     Print DO (1 of 2) for driver                                      |
|     Status: Partial                                                   |
|              |                                                        |
|              v                                                        |
|  4. CUSTOMER REPORTS DAMAGE                                           |
|     "1 box of apples arrived damaged" --> Returns tab                 |
|     Process return: 1x Apple (-$55)                                   |
|     New Total: $430                                                   |
|     Effective Apple qty: 5 --> 4                                      |
|              |                                                        |
|              v                                                        |
|  5. SECOND DELIVERY (next day)                                        |
|     Deliver: 1x Apple, 2x Orange, 2x Pear                            |
|     Print DO (2 of 2) for driver                                      |
|     Status: Complete (all effective items delivered)                   |
|              |                                                        |
|              v                                                        |
|  6. SEND INVOICE                                                      |
|     Invoice shows:                                                    |
|       5x Apple = $275                                                 |
|       4x Orange = $140 (edited from 3)                                |
|       2x Pear = $70                                                   |
|       RETURN: 1x Apple = -$55 (red)                                   |
|       Less: Returns = -$55                                            |
|       TOTAL: $430                                                     |
|       PAYMENT REQUIRED: $430                                          |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

# Troubleshooting

## Common Issues

```
+-----------------------------------------------------------------------+
|  PROBLEM                          |  SOLUTION                         |
+-----------------------------------+-----------------------------------+
|  Can't reduce qty below a number  |  Items already delivered. You     |
|                                   |  can't go below delivered qty.    |
|                                   |  Use Returns tab instead.         |
+-----------------------------------+-----------------------------------+
|  Can't remove an item (X grayed)  |  Item has deliveries or returns.  |
|                                   |  Use Returns tab to process a     |
|                                   |  return for the full quantity.    |
+-----------------------------------+-----------------------------------+
|  Invoice still shows old total    |  The PDF is cached. After making  |
|                                   |  edits/returns, click the Print   |
|                                   |  button again to regenerate.      |
+-----------------------------------+-----------------------------------+
|  "No changes to save" error       |  You haven't actually changed     |
|                                   |  any quantities. Check the New    |
|                                   |  Qty column matches Current.      |
+-----------------------------------+-----------------------------------+
|  "Please provide a reason" error  |  The reason field is required.    |
|                                   |  Type why you're making the edit. |
+-----------------------------------+-----------------------------------+
|  Print DO shows "0" or blank      |  Try clicking Print DO again.     |
|                                   |  The PDF is generated fresh each  |
|                                   |  time (not cached).               |
+-----------------------------------+-----------------------------------+
```

---

# Technical Reference

## File Locations

```
Plugin Root: wp-content/plugins/ah-ho-custom/

Order Management Files:
  includes/order-fulfillment.php     -- Deliveries & Returns metabox, tabs, AJAX
  includes/order-editing.php         -- Edit Items tab, AJAX handler, audit trail

Invoice Plugin: wp-content/plugins/ah-ho-invoicing/

Invoice Files:
  includes/class-delivery-order.php  -- DO generation + partial delivery PDFs
  includes/class-metabox.php         -- PDF download/print AJAX handlers
  includes/class-pdf-generator.php   -- Dompdf PDF engine
  templates/invoice/invoice.php      -- Invoice template (shows returns)
  templates/delivery-order/delivery-order.php -- DO template (supports partial mode)
```

## Order Meta Fields

| Meta Key | Type | Purpose |
|----------|------|---------|
| `_partial_deliveries` | JSON array | All delivery records with items, dates, notes |
| `_delivery_status` | string | `not_started` / `partial` / `complete` |
| `_has_returns` | bool | Flag if order has any returns |
| `_total_returned_quantity` | int | Running count of returned items |
| `_has_edits` | bool | Flag if order has been edited |
| `_order_edit_history` | JSON array | Full edit audit trail |

## Delivery Record Structure

```json
{
  "id": "del_abc12345",
  "date": "2026-02-26",
  "notes": "Morning batch",
  "items": [
    { "item_id": 1, "product_name": "Fuji Apple", "qty": 3 },
    { "item_id": 2, "product_name": "China Orange", "qty": 2 }
  ],
  "created_by": 1,
  "created_by_name": "admin",
  "created_at": "2026-02-26 08:30:00"
}
```

## Edit History Record Structure

```json
{
  "id": "edit_def67890",
  "date": "2026-02-26 08:53:00",
  "user_id": 1,
  "user_name": "admin",
  "reason": "Customer reduced apple order",
  "changes": [
    { "type": "qty_change", "item_name": "Fuji Apple", "old_qty": 5, "new_qty": 3 },
    { "type": "item_added", "item_name": "Pears", "qty": 10, "unit_price": 45.00 },
    { "type": "item_removed", "item_name": "Oranges", "old_qty": 3 }
  ],
  "old_total": "380.00",
  "new_total": "270.00"
}
```

## Key Functions

| Function | File | Purpose |
|----------|------|---------|
| `ah_ho_render_edits_tab()` | order-editing.php | Render Edit Items tab UI |
| `ah_ho_ajax_save_order_edits()` | order-editing.php | AJAX handler for saving edits |
| `ah_ho_render_deliveries_tab()` | order-fulfillment.php | Render Deliveries tab |
| `ah_ho_render_returns_tab()` | order-fulfillment.php | Render Returns tab |
| `ah_ho_ajax_record_delivery()` | order-fulfillment.php | AJAX: record a partial delivery |
| `ah_ho_ajax_delete_delivery()` | order-fulfillment.php | AJAX: delete a delivery record |
| `ah_ho_ajax_process_return()` | order-fulfillment.php | AJAX: process a return/refund |
| `ah_ho_get_delivered_qty_per_item()` | order-fulfillment.php | Get total delivered per item |
| `ah_ho_get_returned_qty_per_item()` | order-fulfillment.php | Get total returned per item |
| `ah_ho_calculate_delivery_status()` | order-fulfillment.php | Calculate delivery status |
| `AH_HO_Delivery_Order::generate()` | class-delivery-order.php | Generate full order DO PDF |
| `AH_HO_Delivery_Order::generate_partial()` | class-delivery-order.php | Generate per-batch DO PDF |

## AJAX Endpoints

```
Save Order Edits:
  URL:    /wp-admin/admin-ajax.php
  Action: ah_ho_save_order_edits
  Method: POST
  Params: nonce, order_id, reason, qty_changes (JSON), new_items (JSON), removed_items (JSON)

Record Delivery:
  URL:    /wp-admin/admin-ajax.php
  Action: ah_ho_record_delivery
  Method: POST
  Params: nonce, order_id, date, notes, items (JSON)

Delete Delivery:
  URL:    /wp-admin/admin-ajax.php
  Action: ah_ho_delete_delivery
  Method: POST
  Params: nonce, order_id, delivery_id

Process Return:
  URL:    /wp-admin/admin-ajax.php
  Action: ah_ho_process_return
  Method: POST
  Params: nonce, order_id, reason, items (JSON)

Print Partial Delivery Order:
  URL:    /wp-admin/admin-ajax.php
  Action: ah_ho_print_pdf
  Method: GET
  Params: _wpnonce, type=partial-delivery-order, order_id, delivery_id
```

## WordPress Hooks Used

| Hook | Type | Purpose |
|------|------|---------|
| `add_meta_boxes` | Action | Register Deliveries & Returns metabox |
| `admin_footer` | Action | Inject CSS & JS for order edit pages |
| `wp_ajax_ah_ho_save_order_edits` | Action | Handle order edit AJAX |
| `wp_ajax_ah_ho_record_delivery` | Action | Handle delivery recording AJAX |
| `wp_ajax_ah_ho_delete_delivery` | Action | Handle delivery deletion AJAX |
| `wp_ajax_ah_ho_process_return` | Action | Handle return processing AJAX |
| `wp_ajax_ah_ho_print_pdf` | Action | Handle PDF print/stream |
| `wp_ajax_ah_ho_prepare_pdf` | Action | Handle PDF download |

---

# Quick Reference Card

```
+-----------------------------------------------------------------------+
|  ORDER MANAGEMENT QUICK REFERENCE                                     |
+-----------------------------------------------------------------------+
|                                                                       |
|  EDIT ORDER:    Edit Items tab > Change qty/add/remove > Save         |
|  ADD PRODUCT:   Edit Items tab > Search > Set qty > + Add > Save      |
|  REMOVE ITEM:   Edit Items tab > Click X > Save                       |
|                                                                       |
|  RECORD DELIVERY: Deliveries tab > Set date + qty > Record Delivery   |
|  PRINT BATCH DO:  Deliveries tab > History > "Print DO" link          |
|  DELETE DELIVERY:  Deliveries tab > History > X button (admin only)    |
|                                                                       |
|  PROCESS RETURN: Returns tab > Reason + qty > Process Return          |
|                                                                       |
|  VIEW INVOICE:   PDF Documents metabox > Print (opens in new tab)     |
|  DOWNLOAD INVOICE: PDF Documents metabox > Invoice button             |
|                                                                       |
|  BADGES:  [Not Started] = gray  |  [Partial] = orange                |
|           [Complete] = green    |  Red dot = has returns              |
|           Blue dot = has edits                                        |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

*Last Updated: February 2026*
