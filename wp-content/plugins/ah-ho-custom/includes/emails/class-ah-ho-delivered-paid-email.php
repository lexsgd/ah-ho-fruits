<?php
/**
 * Delivered - Paid Email
 *
 * Sent to customer when order is delivered and paid
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Email')) {
    return;
}

class AH_HO_Delivered_Paid_Email extends WC_Email {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id             = 'ah_ho_delivered_paid';
        $this->customer_email = true;
        $this->title          = __('Delivered - Paid', 'ah-ho-custom');
        $this->description    = __('Email sent to customer when order is delivered and paid (B2C/Cash).', 'ah-ho-custom');
        $this->template_html  = 'emails/customer-delivered-paid.php';
        $this->template_plain = 'emails/plain/customer-delivered-paid.php';
        $this->template_base  = AH_HO_CUSTOM_PLUGIN_DIR . 'templates/';
        $this->placeholders   = array(
            '{order_date}'   => '',
            '{order_number}' => '',
        );

        // Triggers for this email
        add_action('woocommerce_order_status_delivered-paid_notification', array($this, 'trigger'), 10, 2);

        // Call parent constructor
        parent::__construct();
    }

    /**
     * Get email subject
     */
    public function get_default_subject() {
        return __('Your order #{order_number} has been delivered', 'ah-ho-custom');
    }

    /**
     * Get email heading
     */
    public function get_default_heading() {
        return __('Order delivered successfully!', 'ah-ho-custom');
    }

    /**
     * Trigger email
     */
    public function trigger($order_id, $order = false) {
        $this->setup_locale();

        if ($order_id && !is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }

        if (is_a($order, 'WC_Order')) {
            $this->object                         = $order;
            $this->recipient                      = $this->object->get_billing_email();
            $this->placeholders['{order_date}']   = wc_format_datetime($this->object->get_date_created());
            $this->placeholders['{order_number}'] = $this->object->get_order_number();
        }

        if ($this->is_enabled() && $this->get_recipient()) {
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }

        $this->restore_locale();
    }

    /**
     * Get content HTML
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'email'              => $this,
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Get content plain text
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => true,
                'email'              => $this,
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Default content
     */
    public function get_default_additional_content() {
        return __('Thank you for your order! We hope you enjoy your fresh fruits. We look forward to serving you again soon.', 'ah-ho-custom');
    }
}

return new AH_HO_Delivered_Paid_Email();
