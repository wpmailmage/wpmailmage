<?php

namespace EmailWP\Common\Properties;

use EmailWP\Common\Util\Singleton;

class Properties
{
    use Singleton;

    public $plugin_dir_path;
    public $plugin_url_path;
    public $plugin_folder_name;
    public $plugin_basename;
    public $view_dir;
    public $frontend_url;
    public $view_url;
    public $js_url;
    public $plugin_domain;
    public $plugin_version;
    public $plugin_file_path;
    public $encodings;

    public $rest_version;
    public $rest_namespace;
    public $table_automation_queue;
    public $table_automation_queue_activity;
    public $table_automation_woocommerce_carts;
    public $table_subscribers;
    protected $rest_nonce;

    public function __construct()
    {
        $this->plugin_file_path = realpath(dirname(__DIR__) . '/../../emailwp.php');
        $this->generate_file_paths();

        $this->plugin_domain = 'mail-mage';
        $this->plugin_version = EWP_VERSION;
        $this->is_pro = false;

        $this->rest_namespace = 'ewp';
        $this->rest_version = 'v1';
        // $this->rest_nonce = wp_create_nonce('wp_rest');

        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;
        $this->table_automation_queue = $wpdb->prefix . 'ewp_automation_queue';
        $this->table_automation_queue_activity = $wpdb->prefix . 'ewp_automation_queue_activity';
        $this->table_automation_woocommerce_carts = $wpdb->prefix . 'ewp_automation_woocommerce_carts';
        $this->table_subscribers = $wpdb->prefix . 'ewp_subscribers';
    }

    public function generate_file_paths()
    {
        $this->plugin_dir_path = plugin_dir_path($this->plugin_file_path);
        $this->plugin_url_path = plugin_dir_url($this->plugin_file_path);
        $this->plugin_folder_name = basename($this->plugin_dir_path);
        $this->plugin_basename = plugin_basename($this->plugin_file_path);

        $this->frontend_dir = $this->plugin_dir_path . trailingslashit('frontend');
        $this->view_dir = $this->frontend_dir . trailingslashit('views');

        $this->frontend_url = $this->plugin_url_path . trailingslashit('frontend');
        $this->view_url = $this->frontend_url . trailingslashit('views');
        $this->js_url = $this->frontend_url . trailingslashit('js');
    }

    public function get_rest_nonce()
    {
        return $this->rest_nonce;
    }
}
