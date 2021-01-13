<?php

namespace EmailWP\Free;

use EmailWP\Common\Cron\CronManager;
use EmailWP\Common\Rest\RestManager;
use EmailWP\Free\Automation\AutomationManager;
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

        $this->automation_manager = new AutomationManager($event_handler, $this->event_manager, $this->action_manager);
        $this->cron_manager = new CronManager($this->automation_manager, $this->properties);
        $this->rest_manager = new RestManager($this->http, $this->event_manager, $this->action_manager, $this->automation_manager, $this->properties, $this->analytics_manager, $this->placeholder_manager);
        $this->menu = new Menu($this->properties, $this->view_manager);
    }
}
