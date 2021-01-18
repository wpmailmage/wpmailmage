<?php

namespace EmailWP\Common\Action;

use EmailWP\Container;

class LogAction extends Action
{
    public function __construct($id = null, $settings = [])
    {
        parent::__construct($id, $settings);

        $this->register_fields();
    }

    public function register_fields()
    {
        $text_variable_msg = '<br /><br /> Insert event data using text variables.';
        $this->register_field('Message', 'message', ['type' => 'textarea', 'tooltip' => 'Content to write to file.' . $text_variable_msg]);
    }

    public function get_label()
    {
        return 'Write to log file';
    }

    public function run($event_data = [])
    {
        $base = WP_CONTENT_DIR;
        $ds = DIRECTORY_SEPARATOR;
        $path = $base . $ds . 'uploads';

        if (!is_dir($path)) {
            mkdir($path);
        }

        $path .= $ds . 'emailwp';
        if (!is_dir($path)) {
            mkdir($path);
        }

        $automation_id = $event_data['automation_id'];
        $path .= $ds . $automation_id;
        if (!is_dir($path)) {
            mkdir($path);
        }

        $queue_id = $event_data['queue_id'];
        $file_path = $path . $ds . $queue_id . '.log';

        $message = nl2br($this->get_setting('message'));
        $message = $this->replace_placeholders($message, $event_data);
        $result = file_put_contents($file_path, "\n---\n" . $message, FILE_APPEND);

        if (false === $result) {
            return new \WP_Error('EWP_LA_1', 'Unable to write to file: ' . $file_path);
        }

        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        $properties = Container::getInstance()->get('properties');
        $wpdb->insert($properties->table_automation_queue_activity, [
            'queue_id' => intval($event_data['queue_id']),
            'type' => 'log',
            'created' => current_time('mysql')
        ]);

        $this->set_log_message('Log written to: ' . $file_path);

        return true;
    }
}
