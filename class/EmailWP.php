<?php

namespace EmailWP;

use EmailWP\Common\Analytics\AnalyticsManager;
use EmailWP\Common\Automation\AutomationManager;
use EmailWP\Common\Cron\CronManager;
use EmailWP\Common\Rest\RestManager;

class EmailWP
{
    /**
     * @var RestManager
     */
    private $rest_manager;

    /**
     * @var AnalyticsManager
     */
    private $analytics_manager;

    /**
     * @var AutomationManager
     */
    private $automation_manager;

    /**
     * @var CronManager
     */
    private $cron_manager;

    public function __construct($is_pro = false)
    {
        $container = Container::getInstance();
        $container->setupServiceProviders($is_pro);
        // $container->maybeAddInterceptor('EmailWP\Common\Interceptor\AbandonCartInterceptor');

        $this->rest_manager = $container->get('rest_manager');
        $this->analytics_manager = $container->get('analytics_manager');
        $this->automation_manager = $container->get('automation_manager');
        $this->cron_manager = $container->get('cron_manager');
    }

    public function register()
    {
        // TODO: Move to REST
        $properties = Container::getInstance()->get('properties');
        $migration = new \EmailWP\Common\Migration\Migrations($properties);
        if (!$migration->isSetup()) {
            $migration->install();
        }

        if ($this->rest_manager) {
            $this->rest_manager->register();
        }

        if ($this->analytics_manager) {
            $this->analytics_manager->register();
        }

        // Install automations
        if ($this->automation_manager) {
            $this->automation_manager->install();
        }

        if ($this->cron_manager && isset($_GET['dev'])) {

            add_action('init', function () {

                // TEST: run the next automation
                $this->cron_manager->runner();
                die();
            });
        }
    }
}
