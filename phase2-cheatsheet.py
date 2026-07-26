#!/usr/bin/env python3
"""Generate the Phase-2 connector product-mapping cheat-sheet (read-only)."""
import json, openpyxl
from collections import defaultdict

BT = chr(96)  # backtick, avoid shell quoting issues
ITEMS = json.load(open("qbo-backups/2026-06-17T02-16-05Z/Item.json"))
WC = json.load(open("wc-backups/2026-06-17T02-15-42Z/full-store/products.json"))
ws = openpyxl.load_workbook("/Users/lexnaweiming/Test/QBO-WC-Product-Mapping-FINAL-2026-06-16.xlsx")["Product Mapping"]

byname = {str(i.get("Name", "")).strip(): i for i in ITEMS}
bycode = defaultdict(list)
norow = []
for r in range(2, ws.max_row + 1):
    sku = ws.cell(r, 1).value
    name = ws.cell(r, 2).value
    code = str(ws.cell(r, 4).value).strip()
    if not sku:
        norow.append((name, code)); continue
    bycode[code].append((sku, name))

shared = {c: v for c, v in bycode.items() if len(v) > 1}
n_prod = sum(len(v) for v in shared.values())

L = []
L.append("# Phase 2 — Connector Product-Mapping Cheat-Sheet (Ah Ho WC<->QBO)\n")
L.append("In the connector's product-mapping screen, point EACH WooCommerce product below to the ONE shared QuickBooks item. These could not take a single SKU in Phase 1.\n")
L.append("## A) %d shared QuickBooks items  (%d WooCommerce products)\n" % (len(shared), n_prod))
for code, v in sorted(shared.items(), key=lambda x: -len(x[1])):
    it = byname.get(code, {})
    desc = it.get("Description") or it.get("Name") or "?"
    L.append("### QBO %s%s%s  -  %s   (Id %s)" % (BT, code, BT, desc, it.get("Id", "?")))
    for sku, name in v:
        L.append("- %s%s%s   %s" % (BT, sku, BT, name))
    L.append("")
L.append("## B) Rows with NO WooCommerce SKU (handle manually in the UI)\n")
for name, code in norow:
    L.append("- QBO %s%s%s  <-  %s  (no SKU on the website product)" % (BT, code, BT, name or "(blank row)"))

open("/Users/lexnaweiming/Test/QBO-WC-Phase2-Mapping-Cheatsheet.md", "w").write("\n".join(L))
print("shared groups:", len(shared), "| products in them:", n_prod, "| no-SKU rows:", len(norow))
print("Cheat-sheet -> /Users/lexnaweiming/Test/QBO-WC-Phase2-Mapping-Cheatsheet.md")
