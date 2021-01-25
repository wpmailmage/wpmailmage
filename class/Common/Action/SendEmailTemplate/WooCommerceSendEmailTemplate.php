<?php

namespace EmailWP\Common\Action\SendEmailTemplate;

class WooCommerceSendEmailTemplate
{
    protected $_subject = '';
    protected $_message = '';

    public function set_subject($subject)
    {
        $this->_subject = $subject;
    }
    public function set_message($message)
    {
        $this->_message = $message;
    }

    public function render()
    {
        $email = new \WC_Email();
        $message = WC()->mailer()->wrap_message($this->_subject, $this->_message);
        $message = apply_filters('woocommerce_mail_content', $email->style_inline($message));
        return $message;
    }
}
