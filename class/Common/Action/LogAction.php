<?php

namespace EmailWP\Common\Action;

use EmailWP\Common\Properties\Properties;
use EmailWP\Common\Util\Logger;
use EmailWP\Container;

class LogAction extends Action
{
    public function get_label()
    {
        return 'Log Event';
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

        $queue_id = $event_data['queue_id'];
        $automation_id = $event_data['automation_id'];

        $log_msg = __METHOD__ . ' : -queue_id=' . $queue_id . ' -automation_id=' . $automation_id;

        $action_data = $wpdb->get_var("SELECT action_data FROM {$properties->table_automation_queue} WHERE id=" . intval($queue_id) . " LIMIT 1");
        if ($action_data) {
            $action_data = (array)maybe_unserialize($action_data);
            if (!empty($action_data)) {
                foreach ($action_data as $k => $v) {
                    $log_msg .= ' -' . $k . '=' . $v;
                }
            }
        }

        Logger::write($log_msg);

        $wpdb->insert($properties->table_automation_queue_activity, [
            'queue_id' => intval($event_data['queue_id']),
            'type' => 'log',
            'created' => current_time('mysql')
        ]);

        $this->set_log_message('Log written.');

        return true;
    }
}
