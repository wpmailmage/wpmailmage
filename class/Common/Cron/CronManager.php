<?php

namespace EmailWP\Common\Cron;

use EmailWP\Common\Automation\AutomationManager;
use EmailWP\Common\Model\AutomationWoocommerceCart;
use EmailWP\Common\Properties\Properties;

class CronManager
{
    /**
     * @var AutomationManager
     */
    private $automation_manager;

    /**
     * @var Properties
     */
    public $properties;

    public function __construct($automation_manager, $properties)
    {
        $this->automation_manager = $automation_manager;
        $this->properties = $properties;

        add_action('init', [$this, 'register_cron_runner']);
        add_action('ewp_scheduler', [$this, 'runner']);
        add_filter('cron_schedules', [$this, 'register_cron_interval']);
    }

    public function register_cron_interval($schedules)
    {
        $schedules['ewp_spawner'] = [
            'interval' => MINUTE_IN_SECONDS * 5,
            'display' => __('Every 5 minutes.', 'emailwp')
        ];
        return $schedules;
    }

    public function register_cron_runner()
    {
        if (!wp_next_scheduled('ewp_scheduler')) {
            wp_schedule_event(current_time('timestamp'), 'ewp_spawner', 'ewp_scheduler');
        }
    }

    public function runner()
    {
        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        update_option('ewp_last_ran', current_time('timestamp'));
        $max_time = 30;
        $start_time = time();
        $times = [];
        $avg_time = 0;

        $max_emails_per_hour = intval(get_option('ewp_max_hourly_emails', 100));

        // Process WooCommerce Abandoned Carts older than 60 minutes
        do {
            $row_start = time();
            $query = "SELECT * FROM `" . $this->properties->table_automation_woocommerce_carts . "` WHERE `abandoned` IS NULL AND `modified` <= '" . date('Y-m-d H:i:s', current_time('timestamp') - 3600) . "'  LIMIT 1";
            $row = $wpdb->get_row($query, ARRAY_A);
            if (empty($row)) {
                break;
            }

            $cart = new AutomationWoocommerceCart($row);
            $cart->set_abandoned(true);
        } while ($row && (time() - $start_time) < $max_time - $avg_time);

        // Process Automation queue
        $enabled_automation_ids = $this->automation_manager->get_enabled_automation_ids();
        if (empty($enabled_automation_ids)) {
            return;
        }

        $max_time = 30;
        $start_time = time();
        $times = [];
        $avg_time = 0;

        $check_time = current_time('timestamp') - HOUR_IN_SECONDS;

        do {
            $row_start = time();

            $email_sent_check = intval($wpdb->get_var("SELECT COUNT(*) FROM {$this->properties->table_automation_queue_activity} WHERE `type` = 'email' AND `created` >= '" . date('Y-m-d H:i:s', $check_time) . "'"));
            if ($email_sent_check >= $max_emails_per_hour) {
                $limit_where = " AND action_name != 'send_email' ";
            }

            $row = $wpdb->get_row("SELECT * FROM `" . $this->properties->table_automation_queue . "` WHERE ( (status='S' and scheduled <= NOW()) OR ((status='E' OR status='R') AND automation_id IN (" . implode(',', $enabled_automation_ids) . ") and modified <= NOW() and ran < NOW() - INTERVAL 5 MINUTE) ) " . $limit_where . " LIMIT 1", ARRAY_A);
            if (empty($row)) {
                break;
            }

            $this->automation_manager->run($row);

            if (count($times) >= 10) {
                $times = array_slice($times, -9);
            }

            $times[] = time() - $row_start;
            $avg_time = ceil(array_sum($times) / count($times));
        } while ($row && (time() - $start_time) < $max_time - $avg_time);
    }
}
