<?php

namespace EmailWP\Common\Migration;

use EmailWP\Common\Model\AutomationWoocommerceCart;
use EmailWP\Common\Properties\Properties;

class Migrations
{
    private $_migrations = array();
    private $_version_key = 'ewp_db_version';

    /**
     * @var Properties
     */
    private $properties;

    public function __construct($properties)
    {
        $this->properties = $properties;
        $this->_migrations[] = array($this, 'migration_01');
        $this->_migrations[] = array($this, 'migration_02');
        $this->_migrations[] = array($this, 'migration_03');
        $this->_migrations[] = array($this, 'migration_04');
        $this->_migrations[] = array($this, 'migration_05');
        $this->_migrations[] = array($this, 'migration_06');
        $this->_migrations[] = array($this, 'migration_07');
    }

    public function isSetup()
    {
        $version = intval(get_site_option($this->_version_key, 0));
        if ($version < count($this->_migrations)) {
            return false;
        }
        return true;
    }

    public function install()
    {
        $this->migrate(false);
    }

    public function uninstall()
    {
        global $wpdb;
        delete_site_option($this->_version_key);
    }

    public function migrate($migrate_data = true)
    {

        $verion_key = $this->_version_key;
        $version = intval(get_site_option($this->_version_key, 0));

        // $migrating = get_site_option('ewp_is_migrating', 'no');
        // if ('yes' === $migrating) {
        //     return;
        // }

        if ($version < count($this->_migrations)) {

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            for ($i = 0; $i < count($this->_migrations); $i++) {

                $migration_version = $i + 1;
                if ($version < $migration_version) {

                    // update_site_option('ewp_is_migrating', 'yes');

                    set_time_limit(0);

                    // Run migration
                    if (!is_null($this->_migrations[$i])) {
                        call_user_func($this->_migrations[$i], $migrate_data);
                    }

                    // Flag as migrated
                    update_site_option($verion_key, $migration_version);
                    // update_site_option('ewp_is_migrating', 'no');
                }
            }
        }

        // update_site_option('iwp_is_setup', 'yes');
    }

    public function get_charset()
    {
        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;
        $charset_collate = "";

        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        }
        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE $wpdb->collate";
        }
        return $charset_collate;
    }

    public function migration_01($migrate_data = true)
    {
        $charset_collate = $this->get_charset();

        $sql = "CREATE TABLE `" . $this->properties->table_automation_queue . "` (
					  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					  `automation_id` int(11) DEFAULT NULL,
                      `action_name` varchar(255) DEFAULT NULL,
                      `action_data` TEXT DEFAULT NULL,
					  `status` char(1) DEFAULT 'S',
                      `status_message` varchar(255) DEFAULT NULL,
                      `attempts` int(4) DEFAULT 0,
                      `ran` timestamp NULL DEFAULT NULL,
                      `scheduled` timestamp NULL DEFAULT NULL,
                      `created` timestamp NULL DEFAULT NULL,
                      `modified` timestamp NULL DEFAULT NULL,
					  PRIMARY KEY (`id`)
					) $charset_collate; ";
        dbDelta($sql);
    }

    public function migration_02($migrate_data = true)
    {
        $charset_collate = $this->get_charset();

        $sql = "CREATE TABLE `" . $this->properties->table_automation_queue_activity . "` (
					  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					  `queue_id` int(11) DEFAULT NULL,
                      `type` varchar(255) DEFAULT NULL,
                      `data` TEXT DEFAULT NULL,
                      `created` timestamp NULL DEFAULT NULL,
					  PRIMARY KEY (`id`)
					) $charset_collate; ";
        dbDelta($sql);
    }

    public function migration_03($migrate_data = true)
    {
        $charset_collate = $this->get_charset();

        $sql = "CREATE TABLE `" . $this->properties->table_automation_woocommerce_carts . "` (
					  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					  `session_id` varchar(255) DEFAULT NULL,
                      `user_id` int(11) DEFAULT NULL,
                      `data` TEXT DEFAULT NULL,
                      `cart` TEXT DEFAULT NULL,
                      `abandoned` timestamp NULL DEFAULT NULL,
                      `created` timestamp NULL DEFAULT NULL,
                      `modified` timestamp NULL DEFAULT NULL,
					  PRIMARY KEY (`id`)
					) $charset_collate; ";
        dbDelta($sql);
    }

    public function migration_04($migrate_data = true)
    {
        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        $sql = "ALTER TABLE `" . $this->properties->table_automation_queue . "` ADD COLUMN parent_id int(11) DEFAULT 0;";
        $wpdb->query($sql);
    }

    public function migration_05($migrate_data = true)
    {
        $query = new \WP_Query([
            'post_type' => MAIL_MAGE_POST_TYPE,
            'posts_per_page' => -1
        ]);
        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $json = maybe_unserialize($post->post_content, true);
                $delay = isset($json['delay']);
                $unit = isset($json['delay'], $json['delay']['unit']) ? $json['delay']['unit'] : null;
                $interval = isset($json['delay'], $json['delay']['interval']) ? $json['delay']['interval'] : null;
                unset($json['delay']);

                if (!is_null($unit) && intval($interval) > 0 && !is_null($interval)) {
                    $json['schedule'] = [
                        'type' => 'delay',
                        'delay' => [
                            'unit' => $unit,
                            'interval' => $interval
                        ],
                        'schedule' => [
                            'unit' => null,
                            'day' => null,
                            'hour' => null
                        ]
                    ];
                } else {
                    $json['schedule'] = [
                        'type' => 'now',
                        'delay' => [
                            'unit' => null,
                            'interval' => null
                        ],
                        'schedule' => [
                            'unit' => null,
                            'day' => null,
                            'hour' => null
                        ]
                    ];
                }

                remove_filter('content_save_pre', 'wp_filter_post_kses');
                $result = wp_update_post([
                    'ID' => $post->ID,
                    'post_content' => serialize($json)
                ], true);
                add_filter('content_save_pre', 'wp_filter_post_kses');
            }
        }
    }

    public function migration_06($migrate_data = true)
    {
        $charset_collate = $this->get_charset();

        $sql = "CREATE TABLE `" . $this->properties->table_subscribers . "` (
					  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                      `first_name` varchar(255) DEFAULT NULL,
                      `last_name` varchar(255) DEFAULT NULL,
                      `email` varchar(255) DEFAULT NULL,
                      `activation` varchar(255) DEFAULT NULL,
                      `source` varchar(255) DEFAULT NULL,
					  `status` char(1) DEFAULT 'Y',
                      `created` timestamp NULL DEFAULT NULL,
                      `modified` timestamp NULL DEFAULT NULL,
					  PRIMARY KEY (`id`)
					) $charset_collate; ";
        dbDelta($sql);
    }

    public function migration_07($migrate_data = true)
    {
        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        $sql = "ALTER TABLE `" . $this->properties->table_automation_woocommerce_carts . "` ADD COLUMN `total` DECIMAL(8, 2) DEFAULT NULL;";
        $wpdb->query($sql);

        $rows = $wpdb->get_results("SELECT * FROM {$this->properties->table_automation_woocommerce_carts} WHERE abandoned IS NOT NULL", ARRAY_A);
        foreach ($rows as $row) {
            $model = new AutomationWoocommerceCart($row);
            $wpdb->update($this->properties->table_automation_woocommerce_carts, ['total' => $model->get_cart_total()], ['id' => $row['id']]);
        }
    }
}
