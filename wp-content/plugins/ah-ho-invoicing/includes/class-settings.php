<?php
/**
 * Admin Settings Page
 *
 * Provides WooCommerce settings tab for:
 * - Company branding (logo, UEN, GST, bank details)
 * - Email attachment toggles
 * - PDF customization options
 *
 * @package AhHoInvoicing
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AH_HO_Settings {

    /**
     * Initialize settings
     */
    public static function init() {
        // Add settings tab to WooCommerce
        add_filter('woocommerce_settings_tabs_array', array(__CLASS__, 'add_settings_tab'), 50);

        // Register settings fields
        add_action('woocommerce_settings_tabs_ah_ho_invoicing', array(__CLASS__, 'settings_tab'));

        // Save settings
        add_action('woocommerce_update_options_ah_ho_invoicing', array(__CLASS__, 'update_settings'));
    }

    /**
     * Add settings tab to WooCommerce Settings
     *
     * @param array $settings_tabs Existing settings tabs
     * @return array Modified settings tabs
     */
    public static function add_settings_tab($settings_tabs) {
        $settings_tabs['ah_ho_invoicing'] = __('PDF Invoicing', 'ah-ho-invoicing');
        return $settings_tabs;
    }

    /**
     * Render settings tab content
     */
    public static function settings_tab() {
        woocommerce_admin_fields(self::get_settings());
    }

    /**
     * Save settings
     */
    public static function update_settings() {
        woocommerce_update_options(self::get_settings());
    }

    /**
     * Get all settings fields
     *
     * @return array Settings fields configuration
     */
    public static function get_settings() {
        $settings = array(
            // Section: Company Branding
            array(
                'title' => __('Company Branding', 'ah-ho-invoicing'),
                'type'  => 'title',
                'desc'  => __('These details appear on all PDF documents (invoices, packing slips, delivery orders).', 'ah-ho-invoicing'),
                'id'    => 'ah_ho_company_branding',
            ),
            array(
                'title'   => __('Company Name', 'ah-ho-invoicing'),
                'desc'    => __('Legal company name', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_company_name',
                'type'    => 'text',
                'default' => 'Ah Ho Fruits Pte Ltd',
            ),
            array(
                'title'   => __('Company Address', 'ah-ho-invoicing'),
                'desc'    => __('Full business address', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_company_address',
                'type'    => 'textarea',
                'css'     => 'width: 400px; height: 75px;',
                'default' => '123 Fruit Lane, Singapore 123456',
            ),
            array(
                'title'   => __('Phone Number', 'ah-ho-invoicing'),
                'desc'    => __('Business phone number', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_company_phone',
                'type'    => 'text',
                'default' => '+65 1234 5678',
            ),
            array(
                'title'   => __('Email Address', 'ah-ho-invoicing'),
                'desc'    => __('Business email address', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_company_email',
                'type'    => 'email',
                'default' => 'hello@ahhofruits.com',
            ),
            array(
                'title'   => __('UEN Number', 'ah-ho-invoicing'),
                'desc'    => __('Unique Entity Number (Singapore)', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_company_uen',
                'type'    => 'text',
                'default' => '201234567A',
            ),
            array(
                'title'   => __('GST Registration Number', 'ah-ho-invoicing'),
                'desc'    => __('GST registration number (if applicable)', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_company_gst',
                'type'    => 'text',
                'default' => 'M12345678X',
            ),
            array(
                'title'   => __('Bank Name', 'ah-ho-invoicing'),
                'desc'    => __('Bank name for payment details', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_bank_name',
                'type'    => 'text',
                'default' => 'DBS Bank',
            ),
            array(
                'title'   => __('Bank Account Number', 'ah-ho-invoicing'),
                'desc'    => __('Bank account number', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_bank_account',
                'type'    => 'text',
                'default' => '123-456-789-0',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'ah_ho_company_branding',
            ),

            // Section: Email Automation
            array(
                'title' => __('Email Automation', 'ah-ho-invoicing'),
                'type'  => 'title',
                'desc'  => __('Automatically attach PDFs to WooCommerce emails. Emails are configured under WooCommerce > Settings > Emails.', 'ah-ho-invoicing'),
                'id'    => 'ah_ho_email_automation',
            ),
            array(
                'title'   => __('Attach Invoice to "Order Completed"', 'ah-ho-invoicing'),
                'desc'    => __('Attach invoice PDF to customer "Order Completed" email', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_attach_invoice_to_completed',
                'type'    => 'checkbox',
                'default' => 'yes',
            ),
            array(
                'title'   => __('Attach Packing Slip to "New Order"', 'ah-ho-invoicing'),
                'desc'    => __('Attach packing slip PDF to admin "New Order" email', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_attach_packing_to_new_order',
                'type'    => 'checkbox',
                'default' => 'yes',
            ),
            array(
                'title'   => __('Attach Delivery Order to "Out for Delivery"', 'ah-ho-invoicing'),
                'desc'    => __('Attach delivery order PDF to customer "Out for Delivery" email', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_attach_delivery_to_out_for_delivery',
                'type'    => 'checkbox',
                'default' => 'yes',
            ),
            array(
                'title'   => __('Attach Invoice to "Processing Order"', 'ah-ho-invoicing'),
                'desc'    => __('Attach invoice PDF to customer "Processing Order" email (optional)', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_attach_invoice_to_processing',
                'type'    => 'checkbox',
                'default' => 'no',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'ah_ho_email_automation',
            ),

            // Section: PDF Options
            array(
                'title' => __('PDF Options', 'ah-ho-invoicing'),
                'type'  => 'title',
                'desc'  => __('Advanced PDF generation and caching settings.', 'ah-ho-invoicing'),
                'id'    => 'ah_ho_pdf_options',
            ),
            array(
                'title'   => __('Enable PDF Caching', 'ah-ho-invoicing'),
                'desc'    => __('Cache generated PDFs for faster downloads (recommended)', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_enable_pdf_caching',
                'type'    => 'checkbox',
                'default' => 'yes',
            ),
            array(
                'title'   => __('Cache Cleanup (Days)', 'ah-ho-invoicing'),
                'desc'    => __('Delete cached PDFs older than X days (0 = never delete)', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_cache_cleanup_days',
                'type'    => 'number',
                'default' => '30',
                'custom_attributes' => array(
                    'min'  => 0,
                    'step' => 1,
                ),
            ),
            array(
                'title'   => __('PDF Paper Size', 'ah-ho-invoicing'),
                'desc'    => __('Paper size for PDF documents', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_pdf_paper_size',
                'type'    => 'select',
                'options' => array(
                    'a4'     => __('A4 (210 x 297 mm)', 'ah-ho-invoicing'),
                    'letter' => __('Letter (8.5 x 11 in)', 'ah-ho-invoicing'),
                ),
                'default' => 'a4',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'ah_ho_pdf_options',
            ),

            // Section: Invoice Numbering
            array(
                'title' => __('Invoice Numbering', 'ah-ho-invoicing'),
                'type'  => 'title',
                'desc'  => __('Sequential invoice numbering configuration.', 'ah-ho-invoicing'),
                'id'    => 'ah_ho_invoice_numbering',
            ),
            array(
                'title'   => __('Invoice Prefix', 'ah-ho-invoicing'),
                'desc'    => __('Prefix for invoice numbers (e.g., "AHF-" → AHF-00001)', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_invoice_prefix',
                'type'    => 'text',
                'default' => 'AHF-',
            ),
            array(
                'title'   => __('Starting Number', 'ah-ho-invoicing'),
                'desc'    => __('Next invoice number (current: ' . get_option('ah_ho_invoice_counter', 1) . ')', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_invoice_counter',
                'type'    => 'number',
                'default' => '1',
                'custom_attributes' => array(
                    'min'  => 1,
                    'step' => 1,
                ),
            ),
            array(
                'title'   => __('Number Padding', 'ah-ho-invoicing'),
                'desc'    => __('Minimum digits (5 = 00001, 4 = 0001)', 'ah-ho-invoicing'),
                'id'      => 'ah_ho_invoice_padding',
                'type'    => 'number',
                'default' => '5',
                'custom_attributes' => array(
                    'min'  => 1,
                    'max'  => 10,
                    'step' => 1,
                ),
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'ah_ho_invoice_numbering',
            ),
        );

        return apply_filters('ah_ho_invoicing_settings', $settings);
    }
}
