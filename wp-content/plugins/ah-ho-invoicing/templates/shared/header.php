<?php
/**
 * Shared Header Template
 *
 * Used across all PDF documents (Invoice, Packing Slip, Delivery Order)
 */

if (!defined('ABSPATH')) exit;
?>

<table style="width: 100%; margin-bottom: 20px; border-bottom: 2px solid #2c3e50;">
    <tr>
        <td style="width: 40%; vertical-align: top;">
            <?php if (file_exists(str_replace(AH_HO_INVOICING_PLUGIN_URL, AH_HO_INVOICING_PLUGIN_DIR, $company_logo))): ?>
                <img src="<?php echo esc_attr($company_logo); ?>" alt="<?php echo esc_attr($company_name); ?>" style="max-width: 150px; max-height: 80px;" />
            <?php else: ?>
                <h2 style="margin: 0; color: #27ae60; font-size: 24px;"><?php echo esc_html($company_name); ?></h2>
            <?php endif; ?>
        </td>
        <td style="width: 60%; text-align: right; vertical-align: top;">
            <h1 style="margin: 0; color: #2c3e50; font-size: 32px; font-weight: bold;">
                <?php echo esc_html($document_title); ?>
            </h1>
            <?php if (!empty($document_subtitle)): ?>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 12px;">
                    <?php echo esc_html($document_subtitle); ?>
                </p>
            <?php endif; ?>
        </td>
    </tr>
</table>
