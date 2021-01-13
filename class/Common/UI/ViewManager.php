<?php

namespace EmailWP\Common\UI;

use EmailWP\Common\Properties\Properties;

class ViewManager
{
    /**
     * @var Properties
     */
    private $properties;

    public function __construct($properties)
    {
        $this->properties = $properties;
    }

    public function view($name, $args = array())
    {
        extract($args);

        include $this->properties->view_dir . $name . '.php';
    }

    public function plugin_page()
    {
        $this->view('admin');
    }

    public function tool_box()
    {
        $this->view('tools-card');
    }
}
