<?php

namespace EmailWP;

use EmailWP\Common\Action\ActionManager;
use EmailWP\Common\Action\SendEmailTemplate\WooCommerceSendEmailTemplate;
use EmailWP\Common\Analytics\AnalyticsManager;
use EmailWP\Common\Automation\AutomationManager;
use EmailWP\Common\Cron\CronManager;
use EmailWP\Common\Event\EventManager;
use EmailWP\Common\Http\Http;
use EmailWP\Common\Placeholder\PlaceholderManager;
use EmailWP\Common\Properties\Properties;
use EmailWP\Common\Rest\RestManager;
use EmailWP\Common\UI\ViewManager;

class ServiceProvider
{
    /**
     * @var ActionManager
     */
    public $action_manager;

    /**
     * @var AnalyticsManager
     */
    public $analytics_manager;

    /**
     * @var AutomationManager
     */
    public $automation_manager;

    /**
     * @var CronManager
     */
    public $cron_manager;

    /**
     * @var EventManager
     */
    public $event_manager;

    /**
     * @var Http;
     */
    public $http;

    /**
     * @var PlaceholderManager
     */
    public $placeholder_manager;

    /**
     * @var Properties
     */
    public $properties;

    /**
     * @var RestManager
     */
    public $rest_manager;

    /**
     * @var ViewManager
     */
    public $view_manager;

    /**
     * @param EventHandler $event_handler 
     * @return void 
     */
    public function __construct($event_handler)
    {
        $this->properties = new Properties();
        $this->http = new Http();
        $this->placeholder_manager = new PlaceholderManager($event_handler);
        $this->event_manager = new EventManager($event_handler, $this->placeholder_manager);
        $this->action_manager = new ActionManager($event_handler, $this->placeholder_manager);
        $this->analytics_manager = new AnalyticsManager($event_handler);
        $this->view_manager = new ViewManager($this->properties);

        add_filter('ewp/send_email/register_template', function ($templates) {
            if (class_exists('WooCommerce')) {
                $templates['woocommerce'] = [
                    'label' => 'WooCommerce',
                    'class' => WooCommerceSendEmailTemplate::class
                ];
            }
            return $templates;
        });
    }
}
