<?php
/**
 * PDF Generator Class
 *
 * Handles PDF generation using Dompdf library with caching support
 */

if (!defined('ABSPATH')) {
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

class AH_HO_PDF_Generator {

    /**
     * Initialize PDF generator
     */
    public static function init() {
        // Increase memory limit for PDF generation
        add_filter('init', array(__CLASS__, 'set_memory_limit'));
    }

    /**
     * Set memory limit for PDF generation requests
     */
    public static function set_memory_limit() {
        if (isset($_GET['download_pdf']) || isset($_GET['action']) && $_GET['action'] === 'ah_ho_download_pdf') {
            ini_set('memory_limit', '256M');
            set_time_limit(120); // 2 minutes max
        }
    }

    /**
     * Generate PDF from HTML
     *
     * @param string $html HTML content to convert
     * @param string $filename Base filename (without extension)
     * @param bool $cache Whether to cache the PDF
     * @return string|false Path to generated PDF or false on failure
     */
    // Bump this version to invalidate all cached PDFs (e.g. after font fixes)
    const CACHE_VERSION = '7';

    // Dompdf hashed font base name for Medium weight Noto Sans SC
    // Generated locally via FontLib from the instanced variable font at wght=500
    // Uses Medium (500) weight because the variable font defaults to Thin (100)
    const CJK_FONT_BASE = 'noto_sans_sc_medium_6414f22937fa743cf56f4eb5b0fa1909';

    public static function generate_pdf($html, $filename, $cache = true) {
        // Auto-create cache directory if missing (may not exist after migration/deploy)
        if ($cache && !is_dir(AH_HO_INVOICING_CACHE_DIR)) {
            @mkdir(AH_HO_INVOICING_CACHE_DIR, 0755, true);
            // Security: deny direct access
            $htaccess = AH_HO_INVOICING_CACHE_DIR . '.htaccess';
            if (!file_exists($htaccess)) {
                @file_put_contents($htaccess, "deny from all\n");
            }
        }

        // Check cache first
        if ($cache) {
            $hash = md5($html . self::CACHE_VERSION);
            $cache_path = AH_HO_INVOICING_CACHE_DIR . "{$filename}_{$hash}.pdf";

            if (file_exists($cache_path)) {
                return $cache_path;
            }
        }

        try {
            // Ensure CJK font is installed before generating PDF
            self::install_cjk_font();

            // Configure Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true); // Allow loading remote images (logo)
            $options->set('defaultFont', 'Noto Sans SC');
            $options->set('dpi', 96);
            $options->set('isFontSubsettingEnabled', false); // Disabled: CJK subsetting breaks PDF generation on this host
            $options->set('debugPng', false);
            $options->set('debugKeepTemp', false);
            $options->set('debugCss', false);
            $options->set('debugLayout', false);

            // Initialize Dompdf
            $dompdf = new Dompdf($options);

            // Load HTML
            $dompdf->loadHtml($html);

            // Set paper size (A4 portrait)
            $dompdf->setPaper('A4', 'portrait');

            // Render PDF
            $dompdf->render();

            // Get output
            $output = $dompdf->output();

            // Save to cache if enabled
            if ($cache) {
                $written = @file_put_contents($cache_path, $output);

                if ($written !== false) {
                    // Cache write succeeded — return file path
                    self::cleanup_old_versions($filename, $hash);
                    unset($dompdf);
                    gc_collect_cycles();
                    return $cache_path;
                }

                // Cache write failed — fall through to return raw PDF data
                error_log('Ah Ho Invoicing - Cache write failed: ' . $cache_path);
            }

            // Free memory
            unset($dompdf);
            gc_collect_cycles();

            return $output; // Return raw PDF data as fallback
        } catch (Exception $e) {
            error_log('Ah Ho Invoicing - PDF Generation Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Install CJK (Chinese) font into Dompdf's font directory
     *
     * Copies pre-built .ttf and .ufm files from assets/fonts/ into Dompdf's
     * font directory, then updates the font registry. This avoids relying on
     * FontMetrics::registerFont() which fails on shared hosting due to memory
     * and time limits when processing the 17MB CJK font.
     *
     * IMPORTANT: Dompdf uses hashed filenames for registered fonts, e.g.
     * noto_sans_sc_normal_{hash}.ttf/.ufm — NOT the original filename.
     * The .ufm was pre-generated locally and committed to the repo.
     *
     * @modified 2026-02-23 - Use pre-built .ufm instead of server-side generation
     */
    private static function install_cjk_font() {
        $assets_dir = AH_HO_INVOICING_PLUGIN_DIR . 'assets/fonts/';
        $dompdf_font_dir = AH_HO_INVOICING_PLUGIN_DIR . 'vendor/dompdf/dompdf/lib/fonts/';
        $font_base = self::CJK_FONT_BASE;

        $source_ttf = $assets_dir . 'NotoSansSC-Medium.ttf';
        $source_ufm = $assets_dir . $font_base . '.ufm';

        $dest_ttf = $dompdf_font_dir . $font_base . '.ttf';
        $dest_ufm = $dompdf_font_dir . $font_base . '.ufm';

        // Skip if source font files don't exist
        if (!file_exists($source_ttf) || !file_exists($source_ufm)) {
            error_log('Ah Ho Invoicing - CJK font source files missing');
            return;
        }

        // Copy TTF with hashed filename if not already present
        if (!file_exists($dest_ttf)) {
            @copy($source_ttf, $dest_ttf);
        }

        // Copy pre-built UFM if not already present
        if (!file_exists($dest_ufm)) {
            @copy($source_ufm, $dest_ufm);
        }

        // Ensure the font is registered in Dompdf's JSON registry
        self::ensure_font_registry($dompdf_font_dir, $font_base);
    }

    /**
     * Ensure Noto Sans SC is in the installed-fonts.json registry
     * with all style variants pointing to the hashed font base name.
     *
     * @param string $dompdf_font_dir Path to Dompdf's fonts directory
     * @param string $font_base Hashed font base name (without extension)
     */
    private static function ensure_font_registry($dompdf_font_dir, $font_base) {
        $registry_file = $dompdf_font_dir . 'installed-fonts.json';

        $registry = array();
        if (file_exists($registry_file)) {
            $registry = json_decode(file_get_contents($registry_file), true);
            if (!is_array($registry)) {
                $registry = array();
            }
        }

        // Check if registry already has correct entries
        $needs_update = false;
        if (!isset($registry['noto sans sc'])) {
            $needs_update = true;
        } elseif ($registry['noto sans sc']['normal'] !== $font_base ||
                  $registry['noto sans sc']['bold'] !== $font_base) {
            $needs_update = true;
        }

        if ($needs_update) {
            // Load base registry if empty
            $dist_file = $dompdf_font_dir . 'installed-fonts.dist.json';
            if (empty($registry) && file_exists($dist_file)) {
                $registry = json_decode(file_get_contents($dist_file), true);
                if (!is_array($registry)) {
                    $registry = array();
                }
            }

            // Register all style variants pointing to the hashed font file
            // (we only have one weight, so all variants use the same file)
            $registry['noto sans sc'] = array(
                'normal'      => $font_base,
                'bold'        => $font_base,
                'italic'      => $font_base,
                'bold_italic' => $font_base,
            );

            @file_put_contents($registry_file, json_encode($registry, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Cleanup old versions of cached PDFs
     *
     * @param string $filename Base filename
     * @param string $current_hash Current content hash
     */
    private static function cleanup_old_versions($filename, $current_hash) {
        $files = glob(AH_HO_INVOICING_CACHE_DIR . "{$filename}_*.pdf");
        foreach ($files as $file) {
            // Delete if doesn't match current hash
            if (strpos($file, $current_hash) === false) {
                unlink($file);
            }
        }
    }

    /**
     * Download PDF to browser
     *
     * @param string $pdf_path Path to PDF file or raw PDF data
     * @param string $filename Filename to send to browser
     */
    public static function download_pdf($pdf_path, $filename) {
        // Clear all output buffers to prevent header interference
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Aggressively disable ALL compression — shared hosting gzip
        // causes Content-Length mismatch which makes Chrome use URL as filename
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }
        ini_set('zlib.output_compression', 'Off');

        // Remove any previously set headers that might interfere
        @header_remove('Content-Encoding');
        @header_remove('Transfer-Encoding');
        @header_remove('Content-Disposition');

        // Sanitize filename for Content-Disposition
        $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Use octet-stream to force download (prevents Chrome PDF viewer from intercepting)
        // This ensures the browser downloads the file instead of trying to display it
        $content_type = 'application/octet-stream';

        // Check if it's a file path or raw data
        if (file_exists($pdf_path)) {
            $size = filesize($pdf_path);

            // Headers — order matters for shared hosting compatibility
            header('Content-Type: ' . $content_type);
            header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
            header('Content-Length: ' . $size);
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: none');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Flush headers before body
            if (function_exists('flush')) {
                flush();
            }

            readfile($pdf_path);
        } else {
            $size = strlen($pdf_path);

            header('Content-Type: ' . $content_type);
            header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
            header('Content-Length: ' . $size);
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: none');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');

            if (function_exists('flush')) {
                flush();
            }

            echo $pdf_path;
        }
        exit;
    }

    /**
     * Stream PDF to browser (inline, not download)
     *
     * @param string $pdf_path Path to PDF file
     * @param string $filename Filename
     */
    public static function stream_pdf($pdf_path, $filename) {
        // Clear all output buffers to prevent header interference
        while (ob_get_level()) {
            ob_end_clean();
        }

        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }
        ini_set('zlib.output_compression', 'Off');

        @header_remove('Content-Encoding');
        @header_remove('Transfer-Encoding');
        @header_remove('Content-Disposition');

        $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Check if it's a file path or raw PDF data
        if (file_exists($pdf_path)) {
            $size = filesize($pdf_path);

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $safe_filename . '"');
            header('Content-Length: ' . $size);
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: none');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');

            if (function_exists('flush')) {
                flush();
            }

            readfile($pdf_path);
        } else {
            // Raw PDF data (when caching is disabled, e.g. partial delivery orders)
            $size = strlen($pdf_path);

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $safe_filename . '"');
            header('Content-Length: ' . $size);
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: none');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');

            if (function_exists('flush')) {
                flush();
            }

            echo $pdf_path;
        }
        exit;
    }
}
