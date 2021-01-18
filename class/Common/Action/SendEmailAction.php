<?php

namespace EmailWP\Common\Action;

use EmailWP\Container;

class SendEmailAction extends Action
{
    private $_templates = [];
    public function __construct($id = null, $settings = [])
    {
        parent::__construct($id, $settings);

        $this->_templates = apply_filters('ewp/send_email/register_template', []);

        $this->register_fields();
    }

    public function register_fields()
    {
        $templates = [
            ['value' => '', 'label' => 'Default']
        ];
        foreach ($this->_templates as $template_id => $template_data) {
            $templates[] = ['value' => $template_id, 'label' => $template_data['label']];
        }

        $text_variable_msg = '<br /><br /> Insert event data using text variables.';

        $this->register_field('Template', 'template', [
            'type' => 'select',
            'options' => $templates,
            'placeholder' => false,
            'tooltip' => 'Select which email template to use.'
        ]);
        $this->register_field('To', 'to', ['tooltip' => 'Set the email recipient, seperate multiple emails with a “,”.' . $text_variable_msg]);
        $this->register_field('Subject', 'subject', ['tooltip' => 'The Email subject line, some email templates such as WooCommerce also use this as the email heading.' . $text_variable_msg]);
        $this->register_field('Message', 'message', ['type' => 'textarea', 'tooltip' => 'The main body of the email.' . $text_variable_msg]);
    }

    public function get_to()
    {
        return $this->get_setting('to');
    }

    public function get_subject()
    {
        return $this->get_setting('subject');
    }

    public function get_message()
    {
        return $this->get_setting('message');
    }

    public function get_label()
    {
        return 'Send Email';
    }

    public function email_header($email_heading)
    {
        return '';
    }

    public function email_footer()
    {
        return '';
    }

    protected function send($to, $subject, $message, $headers = '', $attachments = [])
    {
        return wp_mail($to, $subject, $message, $headers, $attachments);
    }

    public function add_tracking_img($queue_id)
    {
        return sprintf('<img src="%s" />',  rest_url('/ewp/v1/automations/queue/' . $queue_id . '.png'));
    }

    public function add_link_tracking($html, $event_data)
    {
        $html = preg_replace_callback('/<a([^>]*)href=("[^"]*"|\'[^\']*\')([^>])*>/', function ($matches) use ($event_data) {
            $url = substr($matches[2], 1, -1);
            $url = add_query_arg(['ewp_ref_session' => $event_data['queue_id']], $url);
            $before = isset($matches[1]) ? $matches[1] : '';
            $after = isset($matches[3]) ? $matches[3] : '';
            return '<a' . $before . 'href="' . $url . '"' . $after . '>';
        }, $html);

        return $html;
    }

    public function run($event_data = [])
    {
        $to = $this->replace_placeholders($this->get_to(), $event_data);
        $subject = $this->replace_placeholders($this->get_subject(), $event_data);
        $message = nl2br($this->get_message());
        $message = $this->replace_placeholders($message, $event_data);
        $message = $this->add_link_tracking($message, $event_data);
        $message .= $this->add_tracking_img($event_data['queue_id']);

        // load template
        $template_id = $this->get_setting('template');
        if (isset($this->_templates[$template_id], $this->_templates[$template_id]['class'])) {
            $template = new $this->_templates[$template_id]['class'];
            $template->set_subject($subject);
            $template->set_message($message);
            $message = $template->render();
        }

        add_action('wp_mail_failed', [$this, 'capture_error']);

        $result = $this->send($to, $subject, $message, "Content-Type: text/html\r\n");
        remove_action('wp_mail_failed', [$this, 'capture_error']);

        if (!$result) {
            return $this->get_error();
        }

        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        $properties = Container::getInstance()->get('properties');
        $wpdb->insert($properties->table_automation_queue_activity, [
            'queue_id' => intval($event_data['queue_id']),
            'type' => 'email',
            'created' => current_time('mysql')
        ]);

        $this->set_log_message('Email sent to ' . $to);

        return $result;
    }

    public function capture_error($wp_error)
    {
        $this->set_error($wp_error);
    }
}
