<?php

namespace EmailWP\Common\Automation;

use EmailWP\Common\Action\ActionManager;
use EmailWP\Common\Event\EventManager;
use EmailWP\Common\Model\AutomationModel;
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
        return [];
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

        if (EWP_POST_TYPE !== get_post_type($id)) {
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

        $automation = new Automation($automation_model->get_id(), $event, $action);
        $automation->delay($automation_model->get_delay());
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

    public function delete($id)
    {
        $automation_model = $this->get_automation_model($id);
        return $automation_model->delete();
    }
}
