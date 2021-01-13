<?php

namespace EmailWP\Common\Analytics;

use EmailWP\Common\Model\AutomationQueueModel;
use EmailWP\Container;

abstract class AbstractClickTracker
{
    public function __construct()
    {
        add_action('init', function () {
            $id = isset($_GET['ewp_id']) ? $_GET['ewp_id'] : false;
            $properties = Container::getInstance()->get('properties');

            /**
             * @var \WPDB $wpdb
             */
            global $wpdb;
            $row = $wpdb->get_row("SELECT * FROM `" . $properties->table_automation_queue . "` WHERE id='" . $id . "' LIMIT 1", ARRAY_A);

            if (empty($row)) {
                return;
            }

            $automation_queue_model = new AutomationQueueModel($row);
            $this->on_click($automation_queue_model);
        }, 1);
    }

    /**
     * @param AutomationQueueModel $automation_queue_model
     */
    abstract function on_click($automation_queue_model);
}
