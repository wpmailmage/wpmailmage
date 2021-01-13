<?php

namespace EmailWP\Common\Action;

use EmailWP\Common\Placeholder\PlaceholderManager;
use EmailWP\EventHandler;

class ActionManager
{
    /**
     * @var EventHandler $event_handler
     */
    protected $event_handler;

    /**
     * @var PlaceholderManager
     */
    public $placeholder_manager;

    private $_actions = [];

    public function __construct($event_handler, $placeholder_manager)
    {
        $this->event_handler = $event_handler;
        $this->placeholder_manager = $placeholder_manager;
        $this->_actions = $this->get_actions();
    }

    public function get_actions()
    {

        $actions = $this->event_handler->run('actions.register', [[]]);

        // Register core events, unable to be overridden
        $actions = array_merge($actions, [
            'send_email' => SendEmailAction::class,
            'log' => LogAction::class
        ]);

        return $actions;
    }

    public function get_action($id)
    {
        if (!isset($this->_actions[$id])) {
            return new \WP_Error('EWP_AM_1', 'Unable to locate action: ' . $id);
        }

        return $this->_actions[$id];
    }

    public function load_action($id, $settings = [])
    {
        $action = $this->get_action($id);
        if (is_wp_error($action)) {
            return $action;
        }

        return new $action($id, $settings);
    }
}
