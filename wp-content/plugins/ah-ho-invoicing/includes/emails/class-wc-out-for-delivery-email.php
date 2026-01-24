<?php
/**
 * Customer "Out for Delivery" Email
 *
 * Sends email to customer when order status changes to "Out for Delivery"
 * Includes delivery order PDF attachment
 *
 * @package AhHoInvoicing
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Out_For_Delivery_Email')) :

/**
 * Customer Out for Delivery Email Class
 */
class WC_Out_For_Delivery_Email extends WC_Email {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id             = 'customer_out_for_delivery_order';
        $this->customer_email = true;
        $this->title          = __('Out for Delivery', 'ah-ho-invoicing');
        $this->description    = __('Order out for delivery emails are sent to customers when their order is marked as out for delivery.', 'ah-ho-invoicing');
        $this->template_html  = 'emails/customer-out-for-delivery-order.php';
        $this->template_plain = 'emails/plain/customer-out-for-delivery-order.php';
        $this->placeholders   = array(
            '{order_date}'   => '',
            '{order_number}' => '',
        );

        // Triggers for this email
        add_action('woocommerce_order_status_out-for-delivery_notification', array($this, 'trigger'), 10, 2);

        // Call parent constructor
        parent::__construct();
    }

    /**
     * Get email subject
     *
     * @return string
     */
    public function get_default_subject() {
        return __('Your {site_title} order #{order_number} is out for delivery', 'ah-ho-invoicing');
    }

    /**
     * Get email heading
     *
     * @return string
     */
    public function get_default_heading() {
        return __('Your order is out for delivery', 'ah-ho-invoicing');
    }

    /**
     * Trigger the sending of this email
     *
     * @param int $order_id Order ID
     * @param WC_Order|bool $order Order object
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
     * Get content html
     *
     * @return string
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
            AH_HO_INVOICING_PLUGIN_DIR . 'templates/'
        );
    }

    /**
     * Get content plain
     *
     * @return string
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
            AH_HO_INVOICING_PLUGIN_DIR . 'templates/'
        );
    }

    /**
     * Default content to show below main email content
     *
     * @return string
     */
    public function get_default_additional_content() {
        return __('Your order is on its way! Our delivery driver will contact you shortly if needed.', 'ah-ho-invoicing');
    }

    /**
     * Initialize settings form fields
     */
    public function init_form_fields() {
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf(__('Available placeholders: %s', 'woocommerce'), '<code>' . implode('</code>, <code>', array_keys($this->placeholders)) . '</code>');
        $this->form_fields = array(
            'enabled'    => array(
                'title'   => __('Enable/Disable', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable this email notification', 'woocommerce'),
                'default' => 'yes',
            ),
            'subject'    => array(
                'title'       => __('Subject', 'woocommerce'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ),
            'heading'    => array(
                'title'       => __('Email heading', 'woocommerce'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ),
            'additional_content' => array(
                'title'       => __('Additional content', 'woocommerce'),
                'description' => __('Text to appear below the main email content.', 'woocommerce') . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __('N/A', 'woocommerce'),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'       => __('Email type', 'woocommerce'),
                'type'        => 'select',
                'description' => __('Choose which format of email to send.', 'woocommerce'),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
                'desc_tip'    => true,
            ),
        );
    }
}

endif;

return new WC_Out_For_Delivery_Email();
