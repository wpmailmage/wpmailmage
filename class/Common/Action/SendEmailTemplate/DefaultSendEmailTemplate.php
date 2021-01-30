<?php

namespace EmailWP\Common\Action\SendEmailTemplate;

use EmailWP\Common\Properties\Properties;
use EmailWP\Common\UI\ViewManager;
use EmailWP\Common\Util\Logger;
use EmailWP\Container;

class DefaultSendEmailTemplate
{
    protected $_id = 'default';
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

    public function get_header()
    {
        /**
         * @var ViewManager $view_manager
         */
        $view_manager = Container::getInstance()->get('view_manager');

        ob_start();
        $view_manager->view(sprintf('emails/templates/%s/header', $this->_id), [
            'heading' => $this->_subject
        ]);
        return ob_get_clean();
    }

    public function get_footer()
    {
        /**
         * @var ViewManager $view_manager
         */
        $view_manager = Container::getInstance()->get('view_manager');

        ob_start();
        $view_manager->view(sprintf('emails/templates/%s/footer', $this->_id));
        return ob_get_clean();
    }

    public function before_email($html)
    {
        if ($this->_unsubscribe) {
            return $html . sprintf(' — <a href="%s" style="color: #000000;font-weight: normal;text-decoration: underline;">Unsubscribe</a>', $this->_unsubscribe);
        }

        return $html;
    }

    public function inline_styles($html)
    {
        /**
         * @var Properties $properties
         */
        $properties = Container::getInstance()->get('properties');

        // get styles
        ob_start();
        include $properties->view_dir . sprintf('/emails/templates/%s/style.css', $this->_id);
        $css = ob_get_clean();

        $emogrifier_class = 'Pelago\\Emogrifier';

        if ($this->supports_emogrifier() && class_exists($emogrifier_class)) {
            try {
                $emogrifier = new $emogrifier_class($html, $css);

                $html    = $emogrifier->emogrify();
                $html_prune = \Pelago\Emogrifier\HtmlProcessor\HtmlPruner::fromHtml($html);
                $html_prune->removeElementsWithDisplayNone();
                $html    = $html_prune->render();
            } catch (\Exception $e) {
                Logger::write(__METHOD__ . ' : ' . $e->getMessage());
            }
        } else {
            $html = '<style type="text/css">' . $css . '</style>' . $html;
        }

        return $html;
    }

    public function render()
    {
        $html = wpautop(wptexturize($this->_message)); // WPCS: XSS ok.
        $html = $this->inline_styles($html);
        $html = $this->get_header() . $html;

        add_filter('ewp_email_footer_text', [$this, 'before_email']);
        $html .= $this->get_footer();
        remove_filter('ewp_email_footer_text', [$this, 'before_email']);

        return $html;
    }

    protected function supports_emogrifier()
    {
        return class_exists('DOMDocument');
    }
}
