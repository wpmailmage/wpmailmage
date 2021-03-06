<?php

namespace EmailWP\Common\Automation;

use EmailWP\Common\Action\Action;
use EmailWP\Common\Event\AbstractEvent;
use EmailWP\Common\Event\EventManager;
use EmailWP\Common\Model\AutomationModel;
use EmailWP\Common\Placeholder\PlaceholderManager;
use EmailWP\Common\Properties\Properties;
use EmailWP\Container;

class Automation
{
    /**
     * @var AutomationModel
     */
    private $_model;

    /**
     * @var Action
     */
    private $_action;

    /**
     * @var AbstractEvent
     */
    private $_event;

    /**
     * Should automations be queue?
     *
     * @var boolean
     */
    private $_queue = false;

    /**
     * Time in seconds to delay
     * 
     * @var integer
     */
    private $_delay = 0;

    /**
     * @param AbstractEvent $event
     * @param Action $action
     */
    public function __construct($model, $event, $action)
    {
        $this->_model = $model;
        $this->_action = $action;
        $this->_event = $event;
    }

    public function enable_queue($queue = true)
    {
        $this->_queue = $queue;
    }

    public function is_queued()
    {
        return $this->_queue;
    }

    public function get_id()
    {
        return $this->_model->get_id();
    }

    public function delay($time_in_seconds = 0)
    {
        $this->_delay = max($time_in_seconds, 0);
    }

    public function get_delay()
    {
        return $this->_delay;
    }

    public function get_log_message()
    {
        return $this->_action->get_log_message();
    }

    public function run($event_data = [])
    {
        /**
         * @var PlaceholderManager $placeholder_manager
         */
        $placeholder_manager = Container::getInstance()->get('placeholder_manager');
        $placeholder_manager->reset();

        /**
         * @var EventManager $event_manager
         */
        $event_manager = Container::getInstance()->get('event_manager');
        $event_data = $event_manager->load_event_data($event_data);

        // Optinal Check to see if event is still valid
        // TODO: some method to say no retries when verified fails
        $result = $this->_event->verified($event_data);
        if ($result === false) {

            // fatal error
            $this->_action->set_log_message($this->_event->get_log_message());
        }

        if ($result && !is_wp_error($result)) {
            $result = $this->_action->run($event_data);
        }

        if (false === $result || is_wp_error($result)) {

            $placeholder_manager->cancel();
        }

        return $result;
    }

    public function queue($event_data = [], $scheduled_time = null)
    {
        /**
         * @var Properties $properties
         */
        $properties = Container::getInstance()->get('properties');

        if (is_null($scheduled_time)) {
            $scheduled_time = $this->_model->get_schedule(current_time('timestamp') + $this->_model->get_delay());
        }

        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;
        $wpdb->insert($properties->table_automation_queue, [
            'automation_id' => $this->get_id(),
            'action_name' => $this->_action->get_id(),
            'action_data' => serialize($event_data),
            'scheduled' => date('Y-m-d H:i:s', $scheduled_time),
            'created' => current_time('mysql'),
            'modified' => current_time('mysql')
        ]);
    }

    /**
     * Triggered when the registered event is fired.
     *
     * @param mixed $event_data
     * 
     * @return void
     */
    private function on_event_triggered($event_data = [])
    {
        if ($this->is_queued()) {
            $this->queue($event_data);
        } else {
            $this->run($event_data);
        }
    }

    /**
     * Install and listen for events
     *
     * @return void
     */
    public function install()
    {
        $this->_event->listen(function ($event_data) {
            $this->on_event_triggered($event_data);
        });
    }
}
