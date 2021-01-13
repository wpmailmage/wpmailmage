<?php

namespace EmailWP\Common\Migration;

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
}
