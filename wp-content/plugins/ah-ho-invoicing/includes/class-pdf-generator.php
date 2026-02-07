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
    public static function generate_pdf($html, $filename, $cache = true) {
        // Check cache first
        if ($cache) {
            $hash = md5($html);
            $cache_path = AH_HO_INVOICING_CACHE_DIR . "{$filename}_{$hash}.pdf";

            if (file_exists($cache_path)) {
                return $cache_path;
            }
        }

        try {
            // Configure Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true); // Allow loading remote images (logo)
            $options->set('defaultFont', 'Arial');
            $options->set('dpi', 96);
            $options->set('isFontSubsettingEnabled', true);
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
                file_put_contents($cache_path, $output);

                // Cleanup old versions
                self::cleanup_old_versions($filename, $hash);

                // Free memory
                unset($dompdf);
                gc_collect_cycles();

                return $cache_path;
            }

            // Free memory
            unset($dompdf);
            gc_collect_cycles();

            return $output; // Return raw PDF data if not caching
        } catch (Exception $e) {
            error_log('Ah Ho Invoicing - PDF Generation Error: ' . $e->getMessage());
            return false;
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

        // Disable Apache gzip - Content-Length mismatch causes Chrome filename issues
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }
        ini_set('zlib.output_compression', 'Off');

        // Check if it's a file path or raw data
        if (file_exists($pdf_path)) {
            // It's a file path
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($pdf_path));
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($pdf_path);
        } else {
            // It's raw PDF data
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . strlen($pdf_path));
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
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
        if (file_exists($pdf_path)) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($pdf_path));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            readfile($pdf_path);
            exit;
        }
    }
}
