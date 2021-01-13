<?php

namespace EmailWP\Common\Action;

use EmailWP\Container;

class LogAction extends Action
{
    public function get_label()
    {
        return 'Write to log file';
    }

    public function run($event_data = [])
    {
        $file_path = WP_CONTENT_DIR . '/ewp.log';
        $queue_id = $event_data['queue_id'];

        $groups = array_filter(array_keys($event_data), function ($item) {
            return !in_array($item, ['queue_id']);
        });

        $result = file_put_contents($file_path, current_time('mysql') . ' : -id=' . $queue_id . ' -data=' . implode(',', $groups) . "\n", FILE_APPEND);

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
