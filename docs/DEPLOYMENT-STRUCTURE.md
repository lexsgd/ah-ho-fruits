# Ah Ho Fruit - Project Structure & Deployment Documentation

## Project Overview

**Site**: fruits.heymag.app
**Platform**: WordPress on Vodien shared hosting
**Deployment**: GitHub Actions (FTP)
**Repository**: https://github.com/lexsgd/ah-ho-fruits

---

## Directory Structure

### Local Development
```
/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits/
├── .github/
│   └── workflows/
│       └── deploy.yml              # GitHub Actions deployment workflow
├── wp-content/                     # WordPress themes/plugins (tracked in git)
│   ├── themes/
│   │   └── ah-ho-fruits/          # Custom theme
│   └── plugins/
│       └── ah-ho-custom/          # Custom plugin
├── docs/                           # Documentation (not deployed)
├── sql/                            # Database backups (not deployed)
├── .htaccess                       # Apache configuration (TRACKED in git)
├── wp-config.php                   # WordPress config (TRACKED in git)
├── robots.txt                      # SEO robots file (TRACKED in git)
├── test-access.html                # Diagnostic file (TRACKED in git)
├── check-wp-config.php             # Diagnostic file (TRACKED in git)
└── [other diagnostic .php files]  # phpinfo.php, version.php, etc.
```

**IMPORTANT NOTES:**
- WordPress core files (wp-admin/, wp-includes/, index.php, wp-login.php, etc.) are **NOT tracked in git**
- They exist only on the server at `/home2/contactl/public_html/ah-ho-fruits/`
- wp-config.php **IS tracked in git** (contains DB credentials and site URLs)
- .htaccess **IS tracked in git** (contains security rules, caching, PHP handler)

---

## Web Hosting Details

### Vodien Server
- **Host**: sh00017.vodien.com
- **cPanel**: https://sh00017.vodien.com:2083/
- **cPanel User**: contactl
- **FTP Host**: sh00017.vodien.com
- **FTP User**: contactl
- **Server Path**: `/home2/contactl/public_html/ah-ho-fruits/`

### Domain & DNS
- **Primary Domain**: fruits.heymag.app
- **DNS A Record**: 101.100.247.79 (Vodien server IP)
- **Alternate Domain**: ahhofruit.com (future, not yet configured)
- **Subdirectory Path**: contactlens.sg/ah-ho-fruits (NOT used, incorrect configuration)

### Database
- **Database Name**: contactl_wp153
- **Database User**: contactl_wp153
- **Database Password**: sf[dMzM,y7@I
- **Table Prefix**: wpgr_
- **Site URLs in DB**:
  - `home`: https://fruits.heymag.app
  - `siteurl`: https://fruits.heymag.app

### PHP Configuration
- **PHP Version**: PHP 8.3 (configured in cPanel MultiPHP Manager)
- **PHP Handler**: ea-php83 (defined in .htaccess)
- **Previous Handler**: ea-php74 (before migration to PHP 8.3)

---

## Current Deployment Workflow Issues

### Problem #1: Multiple Overlapping FTP Deployments

The current `.github/workflows/deploy.yml` has **3 separate FTP deployment steps** that deploy from the same root directory with different exclude lists. This causes:
- File sync conflicts
- Inconsistent deployment state
- Some files not being uploaded

**Current workflow:**
```yaml
Step 1: Deploy .htaccess via FTP
  - Deploys from ./
  - Excludes: wp-content, wp-config.php, robots.txt, and others
  - PURPOSE: Upload .htaccess and diagnostic files

Step 2: Deploy wp-content via FTP
  - Deploys from ./wp-content/
  - No excludes
  - PURPOSE: Upload theme and plugin files

Step 3: Deploy root files (robots.txt only) via FTP
  - Deploys from ./
  - Excludes: wp-content, and others
  - PURPOSE: Upload wp-config.php and robots.txt
```

### Problem #2: Cleanup Step Deletes Files Before Deployment

```yaml
- name: Cleanup diagnostic files
  run: |
    rm -f phpinfo.php wp-test.php clear-opcache.php test-php.php check-files.php check-wp-urls.php
```

This deletes diagnostic files from the server **BEFORE** the FTP deployment runs. The FTP sync tool then:
1. Sees files were deleted on server
2. Has local copies of the files
3. But may not re-upload them because the sync state is inconsistent

### Problem #3: PHP Files Not Accessible (HTTP 404)

**Symptoms:**
- HTML files: ✅ HTTP 200 (test-access.html loads)
- PHP files: ❌ HTTP 404 (check-wp-config.php, phpinfo.php return 404)
- WordPress core: ❌ HTTP 404 (/, /wp-login.php, /wp-admin/ return 404)

**Possible Causes:**
1. PHP files not deployed to server (FTP sync issue)
2. PHP handler misconfigured in .htaccess
3. Server PHP processing disabled for this directory

---

## Correct Deployment Workflow

### What SHOULD Be Deployed

| File/Directory | Deploy? | Why |
|----------------|---------|-----|
| `.htaccess` | ✅ YES | Contains security rules, caching, PHP handler |
| `wp-config.php` | ✅ YES | Contains DB credentials, site URLs (WP_HOME, WP_SITEURL) |
| `robots.txt` | ✅ YES | SEO robots file |
| `wp-content/` | ✅ YES | Custom theme and plugins |
| `*.php` (diagnostic) | ✅ YES | phpinfo.php, check-wp-config.php, etc. |
| `*.html` (diagnostic) | ✅ YES | test-access.html |
| WordPress core | ❌ NO | Already on server, not tracked in git |
| `docs/` | ❌ NO | Documentation only |
| `sql/` | ❌ NO | Database backups only |
| `.env` | ❌ NO | Local environment config |
| `*.md` | ❌ NO | Documentation files |

### Recommended Single-Step Deployment

Instead of 3 separate FTP steps, use **1 unified step**:

```yaml
- name: Deploy all files via FTP
  uses: SamKirkland/FTP-Deploy-Action@v4.3.5
  with:
    server: ${{ secrets.VODIEN_HOST }}
    username: ${{ secrets.VODIEN_USER }}
    password: ${{ secrets.VODIEN_PASSWORD }}
    local-dir: ./
    server-dir: public_html/ah-ho-fruits/
    dangerous-clean-slate: false
    exclude: |
      **/.git*
      **/.github/**
      **/node_modules/**
      **/docs/**
      **/sql/**
      **/.env
      **/deploy-key*
      **/deploy.sh
      **/*.md
      **/*.yml
      **/*.sh
      **/docker-compose.yml
```

This deploys:
- ✅ .htaccess
- ✅ wp-config.php
- ✅ robots.txt
- ✅ wp-content/ (themes and plugins)
- ✅ All diagnostic .php and .html files
- ❌ Excludes: git files, docs, sql, deployment scripts

---

## Deployment Triggers

GitHub Actions deployment triggers on:

```yaml
on:
  push:
    branches: [main]
    paths:
      - 'wp-content/**'          # Theme/plugin changes
      - '.github/workflows/**'   # Workflow changes
      - 'robots.txt'             # SEO file changes
      - 'wp-config.php'          # Config changes
      - '.htaccess'              # Server config changes
      - '*.html'                 # Diagnostic HTML files
      - '*.php'                  # Diagnostic PHP files
```

**Manual Deployment:**
- Go to: https://github.com/lexsgd/ah-ho-fruits/actions
- Select "Deploy to Vodien" workflow
- Click "Run workflow"

---

## File Ownership & Access

### What You Can Change via Git/FTP:
- ✅ wp-content/ (themes, plugins)
- ✅ .htaccess
- ✅ wp-config.php
- ✅ robots.txt
- ✅ Diagnostic files (*.php, *.html)

### What You CANNOT Change via Git:
- ❌ WordPress core files (wp-admin/, wp-includes/, index.php, etc.)
- ❌ Database content (must use phpMyAdmin or SQL)
- ❌ cPanel settings (PHP version, MultiPHP Manager)

### How to Change WordPress Core:
1. Access cPanel File Manager
2. Navigate to `/home2/contactl/public_html/ah-ho-fruits/`
3. Edit files directly OR upload via cPanel
4. **DO NOT** add WordPress core files to git

---

## Common Issues & Solutions

### Issue: Site returns HTTP 404
**Symptoms**: fruits.heymag.app returns 404, but server is accessible
**Causes**:
1. WordPress core files missing
2. .htaccess misconfigured
3. wp-config.php has wrong URLs
4. PHP handler incorrect

**Debug Steps**:
1. Test HTML file: `curl https://fruits.heymag.app/test-access.html`
   - If 200: Server is working, PHP issue
   - If 404: Server path or DNS issue
2. Test PHP file: `curl https://fruits.heymag.app/phpinfo.php`
   - If 200: PHP working, WordPress issue
   - If 404: PHP not configured or files not deployed
3. Check wp-config.php constants:
   ```php
   define( 'WP_HOME', 'https://fruits.heymag.app' );
   define( 'WP_SITEURL', 'https://fruits.heymag.app' );
   ```
4. Check .htaccess PHP handler:
   ```apache
   AddHandler application/x-httpd-ea-php83 .php .php8 .phtml
   ```

### Issue: GitHub Actions deployment succeeds but files not updated
**Symptoms**: Deployment shows "success" but changes not visible
**Causes**:
1. FTP sync tool cached state
2. Files excluded from deployment
3. Server cache (OPcache, WP Rocket)

**Solutions**:
1. Add `force: true` to FTP-Deploy-Action
2. Clear server OPcache via cPanel
3. Check exclude list in deploy.yml
4. Verify file exists in git: `git ls-files | grep filename`

### Issue: Database URLs wrong (contactlens.sg instead of fruits.heymag.app)
**Symptoms**: Site redirects to contactlens.sg/ah-ho-fruits
**Causes**: Database options table has wrong URLs

**Solution**:
1. Access phpMyAdmin via cPanel
2. Select database: contactl_wp153
3. Run SQL:
   ```sql
   UPDATE wpgr_options
   SET option_value = 'https://fruits.heymag.app'
   WHERE option_name IN ('siteurl', 'home');
   ```
4. Add to wp-config.php (overrides DB):
   ```php
   define( 'WP_HOME', 'https://fruits.heymag.app' );
   define( 'WP_SITEURL', 'https://fruits.heymag.app' );
   ```

---

## Emergency Rollback

### If Deployment Breaks Site:

1. **Restore .htaccess from backup:**
   - cPanel File Manager → `/home2/contactl/public_html/ah-ho-fruits/`
   - Rename `.htaccess_back` to `.htaccess`

2. **Restore wp-config.php:**
   - If no backup exists, create new one via cPanel
   - Get DB credentials from original wp-config.php in git history

3. **Revert git commit:**
   ```bash
   cd "/Users/lexnaweiming/Downloads/Ah Ho Fruit/ah-ho-fruits"
   git revert HEAD
   git push origin main
   ```

4. **Check WordPress database:**
   - cPanel → phpMyAdmin
   - Verify `siteurl` and `home` options are correct

---

## Deployment Checklist

Before every deployment:

- [ ] Verify changes in git: `git status`
- [ ] Check deployment paths in `deploy.yml`
- [ ] Ensure .htaccess PHP handler matches cPanel
- [ ] Verify wp-config.php has correct URLs
- [ ] Test deployment on staging (if available)
- [ ] Commit with descriptive message
- [ ] Push to main branch
- [ ] Monitor GitHub Actions: https://github.com/lexsgd/ah-ho-fruits/actions
- [ ] Verify site loads: https://fruits.heymag.app
- [ ] Test WordPress admin: https://fruits.heymag.app/wp-admin/

After deployment:

- [ ] Check HTTP status: `curl -I https://fruits.heymag.app`
- [ ] Verify HTML loads: `curl https://fruits.heymag.app/test-access.html`
- [ ] Verify PHP loads: `curl https://fruits.heymag.app/phpinfo.php`
- [ ] Clear server cache (if using WP Rocket)
- [ ] Test WordPress login
- [ ] Verify theme and plugins work

---

## Contact & Access

**GitHub Repository**: https://github.com/lexsgd/ah-ho-fruits
**GitHub Actions**: https://github.com/lexsgd/ah-ho-fruits/actions
**cPanel**: https://sh00017.vodien.com:2083/ (user: contactl)
**Live Site**: https://fruits.heymag.app

**Backup Files Location** (on server):
- `/home2/contactl/public_html/ah-ho-fruits/.htaccess_back` (working .htaccess)
- `/home2/contactl/public_html/ah-ho-fruits/.htaccess-OLD` (older backup)
- `/home2/contactl/public_html/ah-ho-fruits/.htaccess_` (another backup)

---

## Next Steps to Fix Current Issue

1. **Fix deployment workflow** - Use single FTP step instead of 3
2. **Remove cleanup step** - Don't delete files before deployment
3. **Verify PHP handler** - Check cPanel MultiPHP Manager for correct handler name
4. **Test deployment** - Deploy and verify files are uploaded
5. **Verify site loads** - Test HTML, PHP, and WordPress access
