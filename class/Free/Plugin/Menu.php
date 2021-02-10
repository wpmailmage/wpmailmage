<?php

namespace EmailWP\Free\Plugin;

use EmailWP\Common\Automation\AutomationManager;
use EmailWP\Container;

class Menu extends \EmailWP\Common\Plugin\Menu
{
    final public function load_assets()
    {
        wp_register_script($this->properties->plugin_domain . '-bundle', plugin_dir_url($this->properties->plugin_file_path) . 'dist/js/core.js', array('wp-polyfill'), $this->properties->plugin_version, 'all');

        $matches = false;
        preg_match('/^https?:\/\/[^\/]+(.*?)$/', admin_url('/tools.php?page=' . $this->properties->plugin_domain), $matches);
        $ajax_base = $matches[1];

        $settings = [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'admin_base' => $ajax_base,
            'ajax_base' => rest_url('/' . $this->properties->rest_namespace . '/' . $this->properties->rest_version),
            'plugin_url' => plugin_dir_url($this->properties->plugin_file_path),
            'version' => $this->properties->plugin_version,
        ];

        wp_localize_script($this->properties->plugin_domain . '-bundle', 'wpApiSettings', $settings);

        wp_enqueue_script($this->properties->plugin_domain . '-bundle');
        wp_add_inline_script($this->properties->plugin_domain . '-bundle', '', 'before');

        wp_enqueue_style($this->properties->plugin_domain . '-bundle-styles', plugin_dir_url($this->properties->plugin_file_path) . 'dist/css/core.bundle.css', array(), $this->properties->plugin_version, 'all');
    }
}
