---
description: Update the Ah Ho Fruit system documentation after making code changes. Use this after any commit that adds, modifies, or removes features, plugins, database fields, settings, or infrastructure. Keeps the single source of truth current.
---

# Update Ah Ho Fruit System Documentation

Analyze recent code changes and update `/Users/lexnaweiming/ah-ho-fruits/docs/AH-HO-FRUITS-SYSTEM-DOCUMENTATION.md` to reflect the current state of the project.

## Documentation File

**Path:** `/Users/lexnaweiming/ah-ho-fruits/docs/AH-HO-FRUITS-SYSTEM-DOCUMENTATION.md`

## Document Structure (preserve this exact structure)

```
# Ah Ho Fruit - Complete System Documentation

> Last updated: YYYY-MM-DD
> WordPress on Vodien | Domain: ahhofruit.com (currently fruits.heymag.app)

## 1. System Overview
   - Tech Stack table
   - Custom Plugin Inventory table (name, version, purpose)
   - File Structure tree

## 2. Custom Plugins
   ### 2.1 Ah Ho Custom Plugin (vX.Y.Z)
      - Feature A through K (one section per major feature)
      - Each feature: file path, purpose, key behaviors, tables where relevant
   ### 2.2 Ah Ho Invoicing Plugin (vX.Y.Z)
   ### 2.3 Ah Ho Product Addons (vX.Y.Z)
   ### 2.4 Payment Gateway Fees (vX.Y.Z)
   ### 2.5 Typography Fix (vX.Y.Z)
   ### 2.6 Legal Pages Setup (vX.Y.Z)

## 3. Custom Child Theme
   - Files table (file, lines, purpose)
   - Singapore-specific features

## 4. Infrastructure & Deployment
   - Hosting, deployment, critical config files, verification commands

## 5. Database Reference
   - Order Meta Keys table
   - Order Item Meta Keys table
   - Product Meta Keys table
   - User Meta Keys table
   - WordPress Options table

## 6. Changelog
   - Organized by Phase (Phase 1: Foundation, Phase 2: B2B, etc.)
   - Bullet points per feature within each phase
```

## Workflow

### Step 1: Read the current documentation

Read the full contents of `/Users/lexnaweiming/ah-ho-fruits/docs/AH-HO-FRUITS-SYSTEM-DOCUMENTATION.md`.

### Step 2: Identify what changed

Determine what changed by running:

```bash
cd /Users/lexnaweiming/ah-ho-fruits && git log --oneline -10
```

Then read the files that were modified in recent commits to understand the changes:

```bash
cd /Users/lexnaweiming/ah-ho-fruits && git diff HEAD~1 --stat
```

If more context is needed, read the changed files directly.

### Step 3: Determine which sections need updates

Map each change to the documentation sections it affects:

| Change Type | Sections to Update |
|-------------|-------------------|
| New feature in ah-ho-custom | 2.1 (add/update Feature section) + 6 (Changelog) |
| New feature in ah-ho-invoicing | 2.2 + 6 |
| New feature in ah-ho-product-addons | 2.3 + 6 |
| New plugin added | 1 (Plugin Inventory + File Structure) + 2 (new subsection) + 6 |
| Plugin version bump | 1 (Plugin Inventory) + relevant 2.x section header |
| New order/product/user meta key | 5 (Database Reference) |
| New WordPress option | 5 (WordPress Options table) |
| New order item meta key | 5 (Order Item Meta Keys) |
| Theme changes | 3 (Child Theme) + 6 |
| Infrastructure changes | 4 (Infrastructure) + 6 |
| Bug fix (no new feature) | 6 (Changelog) only |
| Settings page change | Relevant 2.x section + 5 (if new option) |

### Step 4: Apply updates using Edit tool

Use the Edit tool to make targeted changes to the documentation. Do NOT rewrite the entire file.

**Rules:**
- Update the `Last updated` date in the header to today's date
- Add new features to the correct plugin section (2.x)
- Add new meta keys to the correct Database Reference table (Section 5)
- Add new wp_options to the WordPress Options table (Section 5)
- Append to the latest Phase in the Changelog (Section 6), or create a new Phase if appropriate
- Keep the same formatting style: tables, `code`, **bold**, bullet points
- Keep feature descriptions concise (2-5 lines each)
- Always include file paths when referencing code

### Step 5: Update the "Last updated" date

```
> Last updated: YYYY-MM-DD
```

### Step 6: Verify the update

Read the updated sections to confirm formatting is correct and no content was accidentally removed.

## Formatting Rules

### Plugin Feature Format
```markdown
#### Feature X: [Name]
**File:** `includes/[filename].php`

[2-3 sentence description of what it does]

Key behaviors:
- [Behavior 1]
- [Behavior 2]
```

### Database Reference Table Format
```markdown
| Key | Type | Source |
|-----|------|--------|
| `_meta_key_name` | type | plugin-name (feature) |
```

### Changelog Entry Format
```markdown
### Phase N: [Name] (Month Year)
- [Feature/fix description]
- [Feature/fix description]
```

### New Plugin Section Format
```markdown
### 2.N [Plugin Name] (vX.Y.Z)

**Path:** `/wp-content/plugins/[plugin-dir]/`

[1-2 sentence summary]

[Feature details with file paths, tables where relevant]
```

## Important Notes

- NEVER delete existing content unless a feature was actually removed from the codebase
- ALWAYS use the Edit tool for targeted changes, not Write for the full file
- Keep the document concise — this is a reference guide, not a tutorial
- If a plugin version changed, update BOTH the section header AND the Plugin Inventory table in Section 1
- When adding to the Changelog, append to the most recent Phase or create a new one
- All file paths should be relative to the plugin directory (e.g., `includes/filename.php`)
- Meta key names should always be in backtick code formatting
