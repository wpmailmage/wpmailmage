<?php

namespace EmailWP\Common\Action;

use EmailWP\Common\Properties\Properties;
use EmailWP\Common\Util\Logger;
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
        $this->register_field('Show unsubscribe link', 'show_unsubscribe', [
            'type' => 'select',
            'options' => [
                ['value' => 'yes', 'label' => 'Yes'],
                ['value' => 'no', 'label' => 'No'],
            ],
            'default' => 'yes',
            'placeholder' => false
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

    private function get_subscriber_id($email)
    {
        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        /**
         * @var Properties $properties
         */
        $properties = Container::getInstance()->get('properties');

        $subscriber = $wpdb->get_row("SELECT * FROM {$properties->table_subscribers} WHERE LOWER(email)='" . strtolower($email) . "' LIMIT 1", ARRAY_A);
        $subscriber_id = null;
        if ($subscriber) {
            if ($subscriber['status'] == 'U') {
                return false;
            }

            $subscriber_id = $subscriber['id'];
        } else {
            $created = current_time('mysql');
            $wpdb->insert($properties->table_subscribers, [
                'email' => $email,
                'created' => $created,
                'modified' => $created,
                'status' => 'Y',
                'source' => 'automation'
            ]);

            $subscriber_id = $wpdb->insert_id;
        }

        return $subscriber_id;
    }

    private function email_limit_reached($to, $subscriber_id, $automation_id)
    {
        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        /**
         * @var Properties $properties
         */
        $properties = Container::getInstance()->get('properties');

        $minutes_wait = intval(get_site_option('ewp_email_delay', 10));
        if ($minutes_wait == 0) {
            return false;
        }

        $last_sent = $wpdb->get_var("SELECT a.created FROM {$properties->table_automation_queue_activity} as a INNER JOIN wp_ewp_automation_queue as q ON a.queue_id=q.id WHERE a.type='email' AND a.data=" . $subscriber_id . " AND q.automation_id=" . $automation_id . " ORDER BY a.created DESC LIMIT 1");
        if ($last_sent && strtotime($last_sent) >= current_time('timestamp') - (MINUTE_IN_SECONDS * $minutes_wait)) {
            $this->set_log_message('Email already sent to ' . $to . ' within last ' . $minutes_wait . ' minutes.');
            return true;
        }

        return false;
    }

    public function get_unsubscribe_url($email)
    {
        return add_query_arg(['ewp_unsubscribe' => base64_encode($email)], site_url());
    }

    public function run($event_data = [])
    {
        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        /**
         * @var Properties $properties
         */
        $properties = Container::getInstance()->get('properties');

        if (!isset($event_data['_action'])) {
            $recipients = explode(',', $this->replace_placeholders($this->get_to(), $event_data));
            $recipients = array_filter(array_map('trim', $recipients));
            $queue_id = intval($event_data['queue_id']);
            if (count($recipients) > 1 && $queue_id > 0) {

                // TODO: Remove need of duplicate action_data, can get this from parent_id

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
            } elseif (count($recipients) == 1 && $queue_id > 0) {
                $to = trim($recipients[0]);
            } else {
                $this->set_log_message('No Email recipents.');
                return false;
            }
        } else {
            $to = trim($event_data['_action']['to']);
        }

        $subscriber_id = $this->get_subscriber_id($to);
        if (!$subscriber_id) {
            $this->set_log_message('Email' . $to . ' is unsubscribed');
            return false;
        }

        // Escape if automation has already sent email to address.
        if ($this->email_limit_reached($to, $subscriber_id, $event_data['automation_id'])) {
            return false;
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
            if ($this->get_setting('show_unsubscribe') !== 'no') {
                $template->add_unsubscribe_url($this->get_unsubscribe_url($to));
            }
            $message = $template->render();
        } else {
            if ($this->get_setting('show_unsubscribe') !== 'no') {
                $message .= sprintf('<br /><br /><a href="%s">Unsubscribe</a>', $this->get_unsubscribe_url($to));
            }
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

        $wpdb->insert($properties->table_automation_queue_activity, [
            'queue_id' => intval($event_data['queue_id']),
            'type' => 'email',
            'created' => current_time('mysql'),
            'data' => $subscriber_id
        ]);

        $this->set_log_message('Email sent to ' . $to);

        return $result;
    }

    public function capture_error($wp_error)
    {
        $this->set_error($wp_error);
    }
}
