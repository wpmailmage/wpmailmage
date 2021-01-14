<?php

namespace EmailWP\Common\Event;

use EmailWP\Common\Util\FormFieldTrait;
use EmailWP\Container;

abstract class AbstractEvent
{
    use FormFieldTrait;

    /**
     * @var callable
     */
    private $_callback;
    private $_log_message;

    public function listen($callback)
    {
        $this->_callback = $callback;

        $this->install_listener();
    }

    abstract protected function install_listener();

    /**
     * Verification check before action is ran.
     *
     * @param array $event_data
     * 
     * @return \WP_Error|true
     */
    abstract public function verified($event_data = []);

    protected final function triggered($data = [])
    {
        /**
         * @var EventManager $event_manager
         */
        $event_manager = Container::getInstance()->get('event_manager');
        $event_data = $event_manager->generate_event_data($data);

        call_user_func_array($this->_callback, [$event_data]);
    }

    protected function set_log_message($message)
    {
        $this->_log_message = $message;
    }

    public function get_log_message()
    {
        return $this->_log_message;
    }
}
