<?php

namespace EmailWP\Common\Action\SendEmailTemplate;

class WooCommerceSendEmailTemplate
{
    protected $_subject = '';
    protected $_message = '';
    protected $_unsubscribe = null;

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
