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
        return $this->header($this->_subject) . $this->_message . $this->footer($this->_message);
    }

    public function header($email_heading)
    {
        $output = '';
        $output .= $this->replace_wc_placeholders(wc_get_template_html('emails/email-header.php', ['email_heading' => $email_heading]));
        return $output;
    }

    public function footer()
    {
        $output = '';
        $output .= $this->replace_wc_placeholders(wc_get_template_html('emails/email-footer.php'));
        $output .= '<style type="text/css">' . wc_get_template_html('emails/email-styles.php') . '</style>';
        return $output;
    }

    /**
     * Get blog name formatted for emails.
     *
     * @return string
     */
    private function get_blogname()
    {
        return wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    }

    private function replace_wc_placeholders($string)
    {
        $domain = wp_parse_url(home_url(), PHP_URL_HOST);

        return str_replace(
            array(
                '{site_title}',
                '{site_address}',
                '{site_url}',
                '{woocommerce}',
                '{WooCommerce}',
            ),
            array(
                $this->get_blogname(),
                $domain,
                $domain,
                '<a href="https://woocommerce.com">WooCommerce</a>',
                '<a href="https://woocommerce.com">WooCommerce</a>',
            ),
            $string
        );
    }
}
