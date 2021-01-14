<?php

namespace EmailWP\Common\Action;

use EmailWP\Common\ActionInterface;
use EmailWP\Common\Placeholder\PlaceholderManager;
use EmailWP\Common\Util\FormFieldTrait;
use EmailWP\Container;

class Action implements ActionInterface
{

    use FormFieldTrait;

    private $_id;
    private $_settings;
    private $_log_message;
    private $_error = false;

    public function __construct($id = null, $settings = [])
    {
        $this->_id = $id;
        $this->_settings = $settings;
    }

    public function get_id()
    {
        return $this->_id;
    }

    protected function get_settings()
    {
        return $this->_settings;
    }

    protected function get_setting($id)
    {
        return isset($this->_settings[$id]) ? $this->_settings[$id] : false;
    }

    public function set_log_message($message)
    {
        $this->_log_message = $message;
    }

    public function get_log_message()
    {
        return $this->_log_message;
    }

    protected function replace_placeholders($input, $data)
    {
        /**
         * @var PlaceholderManager $placeholder_manager
         */
        $placeholder_manager = Container::getInstance()->get('placeholder_manager');
        return $placeholder_manager->replace_placeholders($input, $data);
    }

    /**
     * Run event
     *
     * @param array $event_data
     * @return true|\WP_Error
     */
    public function run($event_data = [])
    {
        return true;
    }

    protected function set_error($error)
    {
        $this->_error = $error;
    }

    protected function get_error()
    {
        return $this->_error;
    }
}
