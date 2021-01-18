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
        update_option('ewp_last_ran', current_time('timestamp'));
        $max_time = 30;
        $start_time = time();
        $times = [];
        $avg_time = 0;

        // Process WooCommerce Abandoned Carts older than 60 minutes
        do {
            $row_start = time();
            /**
             * @var \WPDB $wpdb
             */
            global $wpdb;
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

        do {
            $row_start = time();
            /**
             * @var \WPDB $wpdb
             */
            global $wpdb;
            $row = $wpdb->get_row("SELECT * FROM `" . $this->properties->table_automation_queue . "` WHERE (status='S' and scheduled <= NOW()) OR ((status='E' OR status='R') AND automation_id IN (" . implode(',', $enabled_automation_ids) . ") and modified <= NOW() and ran < NOW() - INTERVAL 5 MINUTE) LIMIT 1", ARRAY_A);
            if (empty($row)) {
                break;
            }

            $wpdb->update($this->properties->table_automation_queue, ['status' => 'R', 'attempts' => intval($row['attempts']) + 1, 'modified' => current_time('mysql')], ['id' => $row['id']]);

            $automation = $this->automation_manager->get_automation($row['automation_id']);
            if (!is_wp_error($automation)) {

                // TODO: move to event data class
                // Pass queue to event_data
                $data = maybe_unserialize($row['action_data']);

                $data['queue_id'] = $row['id'];
                $data['automation_id'] = $row['automation_id'];

                $result = $automation->run($data);
            } else {
                $result = $automation;
            }

            if (false === $result) {

                // Hard fail dont run again
                $wpdb->update($this->properties->table_automation_queue, ['status' => 'F', 'status_message' => $automation->get_log_message(), 'ran' => current_time('mysql')], ['id' => $row['id']]);
            } elseif (is_wp_error($result)) {

                // Error happend so try
                if ($row['attempts'] + 1 >= 5) {
                    $wpdb->update($this->properties->table_automation_queue, ['status' => 'F', 'status_message' => $result->get_error_message(), 'ran' => current_time('mysql')], ['id' => $row['id']]);
                } else {
                    $wpdb->update($this->properties->table_automation_queue, ['status' => 'E', 'status_message' => $result->get_error_message(), 'ran' => current_time('mysql')], ['id' => $row['id']]);
                }
            } elseif ($result) {

                // Success
                $message = $automation->get_log_message();
                $wpdb->update($this->properties->table_automation_queue, ['status' => 'Y', 'status_message' => $message, 'ran' => current_time('mysql')], ['id' => $row['id']]);
            }

            if (count($times) >= 10) {
                $times = array_slice($times, -9);
            }

            $times[] = time() - $row_start;
            $avg_time = ceil(array_sum($times) / count($times));
        } while ($row && (time() - $start_time) < $max_time - $avg_time);
    }
}
