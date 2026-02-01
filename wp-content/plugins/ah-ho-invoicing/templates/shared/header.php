<?php
/**
 * Shared Header Template
 *
 * Used across all PDF documents (Invoice, Packing Slip, Delivery Order)
 */

if (!defined('ABSPATH')) exit;
?>

<table style="width: 100%; margin-bottom: 20px; border-bottom: 3px solid #000;">
    <tr>
        <td style="width: 100%; text-align: center; padding-bottom: 15px;">
            <?php if (file_exists(str_replace(AH_HO_INVOICING_PLUGIN_URL, AH_HO_INVOICING_PLUGIN_DIR, $company_logo))): ?>
                <img src="<?php echo esc_attr($company_logo); ?>" alt="<?php echo esc_attr($company_name); ?>" style="max-width: 150px; max-height: 60px; margin-bottom: 10px;" /><br>
            <?php endif; ?>
            <h1 style="margin: 0 0 5px 0; color: #000; font-size: 24px; font-weight: bold; text-transform: uppercase;">
                <?php echo esc_html($company_name); ?>
            </h1>
            <h2 style="margin: 0; color: #000; font-size: 24px; font-weight: bold; text-transform: uppercase;">
                <?php echo esc_html($document_title); ?>
            </h2>
            <?php if (!empty($document_subtitle)): ?>
                <p style="margin: 5px 0 0 0; color: #666; font-size: 12px;">
                    <?php echo esc_html($document_subtitle); ?>
                </p>
            <?php endif; ?>
        </td>
    </tr>
</table>
