<?php

namespace EmailWP\Free;

use EmailWP\Free\Plugin\Menu;

class ServiceProvider extends \EmailWP\ServiceProvider
{
    /**
     * @param EventHandler $event_handler 
     * @return void 
     */
    public function __construct($event_handler)
    {
        parent::__construct($event_handler);

        $this->menu = new Menu($this->properties, $this->view_manager);
    }
}
