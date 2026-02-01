<?php
/**
 * Shared Footer Template
 *
 * Used across all PDF documents (Invoice, Packing Slip, Delivery Order)
 */

if (!defined('ABSPATH')) exit;
?>

<div style="margin-top: 30px; padding: 20px 30px; border-top: 2px solid #ddd;">
    <table style="width: 100%; font-size: 11px; color: #7f8c8d;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <strong><?php echo esc_html($company_name); ?></strong><br>
                <?php echo nl2br(esc_html($company_address)); ?><br>
                Tel: <?php echo esc_html($company_phone); ?><br>
                Email: <?php echo esc_html($company_email); ?>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top;">
                <strong>Company Registration</strong><br>
                UEN: <?php echo esc_html($company_uen); ?><br>
                GST Reg No: <?php echo esc_html($company_gst); ?><br>
                <br>
                <?php if (!empty($show_bank_details) && $show_bank_details): ?>
                    <strong>Bank Details</strong><br>
                    Bank: <?php echo esc_html($bank_name); ?><br>
                    Account: <?php echo esc_html($bank_account); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center; padding-top: 15px; border-top: 1px solid #ecf0f1; margin-top: 10px;">
                <em style="font-size: 10px; color: #95a5a6;">
                    Thank you for your business! | Fresh fruits delivered with care
                </em>
            </td>
        </tr>
    </table>
</div>
