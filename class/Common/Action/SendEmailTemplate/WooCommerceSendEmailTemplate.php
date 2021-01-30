<?php

namespace EmailWP\Common\Action\SendEmailTemplate;

class WooCommerceSendEmailTemplate
{
    protected $_subject = '';
    protected $_message = '';
    protected $_unsubscribe = null;

    public function __construct()
    {
        add_filter('ewp_email_button_args', [$this, 'set_email_button_args']);
    }

    public function __destruct()
    {
        remove_filter('ewp_email_button_args', [$this, 'set_email_button_args']);
    }

    public function set_subject($subject)
    {
        $this->_subject = $subject;
    }
    public function set_message($message)
    {
        $this->_message = $message;
    }

    public function add_unsubscribe_url($unsubscribe_url)
    {
        $this->_unsubscribe = $unsubscribe_url;
    }

    public function before_email($html)
    {
        if ($this->_unsubscribe) {
            return $html . sprintf(' — <a href="%s">Unsubscribe</a>', $this->_unsubscribe);
        }

        return $html;
    }

    public function set_email_button_args($args)
    {
        if (!isset($args['color'])) {
            $args['color'] = get_option('woocommerce_email_base_color');
        }

        return $args;
    }

    public function render()
    {

        if (!class_exists('\WC_Email')) {
            include_once WC()->plugin_path() . '/includes/emails/class-wc-email.php';
        }

        $email = new \WC_Email();

        add_filter('woocommerce_email_footer_text', [$this, 'before_email']);
        $message = WC()->mailer()->wrap_message($this->_subject, $this->_message);
        remove_filter('woocommerce_email_footer_text', [$this, 'before_email']);
        $message = apply_filters('woocommerce_mail_content', $email->style_inline($message));

        return $message;
    }
}
