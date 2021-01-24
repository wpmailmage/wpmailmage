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
        $this->register_field('Cc', 'cc');
        $this->register_field('Bcc', 'bcc');
        $this->register_field('Subject', 'subject', ['tooltip' => 'The Email subject line, some email templates such as WooCommerce also use this as the email heading.' . $text_variable_msg]);
        $this->register_field('Message', 'message', ['type' => 'textarea', 'tooltip' => 'The main body of the email.' . $text_variable_msg]);
    }

    public function get_to()
    {
        return $this->get_setting('to');
    }

    public function get_cc()
    {
        return $this->get_setting('cc');
    }

    public function get_bcc()
    {
        return $this->get_setting('bcc');
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
        if (!isset($event_data['_action'])) {
            $recipients = explode(',', $this->replace_placeholders($this->get_to(), $event_data));
            $recipients = array_filter(array_map('trim', $recipients));
            $queue_id = intval($event_data['queue_id']);
            if (count($recipients) > 1 && $queue_id > 1) {

                // TODO: Remove need of duplicate action_data, can get this from parent_id

                /**
                 * @var \WPDB $wpdb
                 */
                global $wpdb;
                $properties = Container::getInstance()->get('properties');

                $row = $wpdb->get_row("SELECT * FROM {$properties->table_automation_queue} WHERE id='" . $queue_id . "'", ARRAY_A);
                if (!$row) {
                    $this->set_log_message('Unable to find parent queue id: ' . $queue_id . '.');
                    return false;
                }

                // rest and cleanup row data
                $row['status'] = 'S';
                $row['attempts'] = 0;
                $row['parent_id'] = $queue_id;
                $row['scheduled'] = current_time('mysql');
                unset($row['id']);

                foreach ($recipients as $recipient) {

                    $tmp = $row;
                    $action_data = maybe_unserialize($tmp['action_data']);
                    $action_data['_action'] = ['to' => $recipient];
                    $tmp['action_data'] = serialize($action_data);
                    $wpdb->insert($properties->table_automation_queue, $tmp);
                }

                $this->set_log_message(count($recipients) . ' Emails added to queue.');
                return true;
            } elseif (count($recipients) == 1 && $queue_id > 1) {
                $to = $recipients[0];
            } else {
                $this->set_log_message('No Email recipents.');
                return false;
            }
        } else {
            $to = $event_data['_action']['to'];
        }

        $cc = $this->replace_placeholders($this->get_cc(), $event_data);
        $bcc = $this->replace_placeholders($this->get_bcc(), $event_data);
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

        $headers = ["Content-Type: text/html"];

        if (!empty($cc)) {
            $cc = explode(',', $cc);
            foreach ($cc as $email) {
                if (!is_email($email)) {
                    continue;
                }

                $headers[] = "Cc: " . trim($email);
            }
        }

        if (!empty($bcc)) {
            $bcc = explode(',', $bcc);
            foreach ($bcc as $email) {
                if (!is_email($email)) {
                    continue;
                }

                $headers[] = "Bcc: " . trim($email);
            }
        }


        $result = $this->send($to, $subject, $message, $headers);
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
