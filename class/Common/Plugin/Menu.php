<?php

namespace EmailWP\Common\Plugin;

use EmailWP\Common\Properties\Properties;
use EmailWP\Common\UI\ViewManager;

class Menu
{
    /**
     * @var Properties
     */
    protected $properties;

    /**
     * @var ViewManager
     */
    protected $view_manager;

    /**
     * @param Properties $properties
     * @param ViewManager $view_manager
     */
    public function __construct($properties, $view_manager)
    {
        $this->properties = $properties;
        $this->view_manager = $view_manager;

        add_action('admin_menu', array($this, 'register_tools_menu'));
    }

    public function register_tools_menu()
    {
        $title = __('Mail Mage', $this->properties->plugin_domain);

        $hook_suffix = add_management_page($title, $title, 'manage_options', $this->properties->plugin_domain, array(
            $this->view_manager,
            'plugin_page'
        ));

        add_action('load-' . $hook_suffix, array($this, 'load_assets'));
    }

    public function load_assets()
    {
    }
}
