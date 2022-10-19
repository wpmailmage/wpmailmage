<?php

namespace EmailWP\Common\Automation;

use EmailWP\Common\Action\ActionManager;
use EmailWP\Common\Event\EventManager;
use EmailWP\Common\Model\AutomationModel;
use EmailWP\Container;
use EmailWP\EventHandler;

class AutomationManager
{
    /**
     * @var ActionManager
     */
    protected $action_manager;

    /**
     * @var EventHandler $event_handler
     */
    protected $event_handler;

    /**
     * @var EventManager
     */
    protected $event_manager;

    /**
     * @param EventHandler $event_handler
     * @param EventManager $event_manager
     * @param ActionManager $action_manager
     */
    public function __construct($event_handler, $event_manager, $action_manager)
    {
        $this->event_handler = $event_handler;
        $this->event_manager = $event_manager;
        $this->action_manager = $action_manager;
    }

    /**
     * Get list of active automations
     * 
     * @return AutomationModel[]
     */
    public function get_automations()
    {
        $result = array();
        $query  = new \WP_Query(array(
            'post_type'      => MAIL_MAGE_POST_TYPE,
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));

        foreach ($query->posts as $post) {
            $result[] = $this->get_automation_model($post);
        }
        return $result;
    }

    /**
     * Get automation
     * 
     * @return AutomationModel
     */
    public function get_automation_model($id)
    {
        if ($id instanceof AutomationModel) {
            return $id;
        }

        if (MAIL_MAGE_POST_TYPE !== get_post_type($id)) {
            return false;
        }

        return new AutomationModel($id);
    }

    /**
     * @param integer|AutomationModel $id
     * @return Automation
     */
    public function get_automation($id)
    {
        $automation_model = $this->get_automation_model($id);

        $event = $this->get_automation_event($automation_model);
        $action = $this->get_automation_action($automation_model);

        if (is_wp_error($event)) {
            return $event;
        }

        if (is_wp_error($action)) {
            return $action;
        }

        $automation = new Automation($automation_model, $event, $action);
        return $automation;
    }

    public function get_automation_event($id)
    {
        $automation_model = $this->get_automation_model($id);
        $event_id = $automation_model->get_event();
        $event_settings = $automation_model->get_event_settings();

        return $this->event_manager->load_event($event_id, $event_settings);
    }

    public function get_automation_action($id)
    {
        $automation_model = $this->get_automation_model($id);
        $action_id = $automation_model->get_action();
        $action_settings = $automation_model->get_action_settings();

        return $this->action_manager->load_action($action_id, $action_settings);
    }

    /**
     * Get enabled automations
     *
     * @return AutomationModel[]
     */
    public function get_enabled_automations()
    {
        $automations = $this->get_automations();
        $output = [];
        foreach ($automations as $automation) {

            if ($automation->is_disabled()) {
                continue;
            }

            $output[] = $automation;
        }

        return $output;
    }

    public function get_enabled_automation_ids()
    {
        return array_reduce($this->get_enabled_automations(), function ($carry, $item) {
            /**
             * @var AutomationModel $item
             */
            $carry[] = $item->get_id();
            return $carry;
        }, []);
    }

    /**
     * Install Automation
     *
     * @return void
     */
    public function install($is_queued = true)
    {
        $automations = $this->get_enabled_automations();
        foreach ($automations as $automation_model) {

            $automation = $this->get_automation($automation_model);
            if (is_wp_error($automation)) {
                // TODO: Log Error
                continue;
            }

            if ($is_queued) {
                $automation->enable_queue();
            }

            $automation->install();
        }
    }

    /**
     * Run automation
     *
     * @param int|array $row Row from queue table
     * @return void
     */
    public function run($queue_id)
    {
        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        $properties = Container::getInstance()->get('properties');

        if (is_array($queue_id)) {
            $row = $queue_id;
        } else {
            $queue_id = intval($queue_id);
            $row = $wpdb->get_row("SELECT * FROM `" . $properties->table_automation_queue . "` WHERE (status='S' OR status='E' OR status='R') AND id = " . $queue_id . " LIMIT 1", ARRAY_A);
            if (empty($row)) {
                return false;
            }
        }

        $wpdb->update($properties->table_automation_queue, ['status' => 'R', 'attempts' => intval($row['attempts']) + 1, 'modified' => current_time('mysql')], ['id' => $row['id']]);

        $automation = $this->get_automation($row['automation_id']);
        if (!is_wp_error($automation)) {

            // TODO: move to event data class
            // Pass queue to event_data
            $data = maybe_unserialize($row['action_data']);

            $data['queue_id'] = $row['id'];
            $data['automation_id'] = $row['automation_id'];

            $result = $automation->run($data);
        } else {
            $result = $automation;
        }

        if (false === $result) {

            // Hard fail dont run again
            $wpdb->update($properties->table_automation_queue, ['status' => 'F', 'status_message' => $automation->get_log_message(), 'ran' => current_time('mysql')], ['id' => $row['id']]);
        } elseif (is_wp_error($result)) {

            // Error happend so try
            if ($row['attempts'] + 1 >= 5) {
                $wpdb->update($properties->table_automation_queue, ['status' => 'F', 'status_message' => $result->get_error_message(), 'ran' => current_time('mysql')], ['id' => $row['id']]);
            } else {
                $wpdb->update($properties->table_automation_queue, ['status' => 'E', 'status_message' => $result->get_error_message(), 'ran' => current_time('mysql')], ['id' => $row['id']]);
            }
        } elseif ($result) {

            // Success
            $message = $automation->get_log_message();
            $wpdb->update($properties->table_automation_queue, ['status' => 'Y', 'status_message' => $message, 'ran' => current_time('mysql')], ['id' => $row['id']]);
        }

        return true;
    }

    public function delete($id)
    {
        $automation_model = $this->get_automation_model($id);
        return $automation_model->delete();
    }
}
