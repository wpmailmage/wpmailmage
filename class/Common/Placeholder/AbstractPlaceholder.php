<?php

namespace EmailWP\Common\Placeholder;

abstract class AbstractPlaceholder
{
    protected $_cleanup = [];

    abstract public function get_id();

    abstract public function get_variables();

    /**
     * @param string $key
     * @param mixed $data
     * @return void
     */
    public function replace($key, $data, $args = [])
    {
        $vars = $this->get_variables();
        if (isset($vars[$key])) {
            $output = $vars[$key]($data, $args);
        }

        $output = apply_filters('ewp/placeholder/' . $this->get_id(), $output, $key, $data);
        $output = apply_filters('ewp/placeholder/' . $this->get_id() . '.' . $key, $output, $key, $data);
        return $output;
    }

    /**
     * Triggered before any automation is run to allow reseting
     *
     * @return void
     */
    public function reset()
    {
        $this->_cleanup = [];
    }

    /**
     * Triggered when an error occurs during running of automation
     *
     * @return void
     */
    public function cancel()
    {
        if (empty($this->_cleanup)) {
            return;
        }

        foreach ($this->_cleanup as $cleanup) {
            call_user_func($cleanup);
        }
    }

    protected function add_cleanup_method($method)
    {
        $this->_cleanup[] = $method;
    }
}
