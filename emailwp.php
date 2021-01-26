<?php

/**
 * Plugin Name: Mail Mage
 * Plugin URI: https://www.wpmailmage.com
 * Description: Mail Mage allows you to automate your WordPress marketing workflows helping to convert, retain and recover customers in WordPress, WooCommerce and other popular plugins.
 * Author: Mail Mage <hello@wpmailmage.com>
 * Version: 0.0.14 
 * Network: True
 * WC tested up to: 4.9
 */

$ewp_base_path = dirname(__FILE__);

if (!defined('EWP_VERSION')) {
    define('EWP_VERSION', '0.0.14');
}

if (!defined('EWP_MINIMUM_PHP_VERSION')) {
    define('EWP_MINIMUM_PHP_VERSION', '5.4');
}

if (!defined('EWP_POST_TYPE')) {
    define('EWP_POST_TYPE', 'ewp-automation');
}

if (version_compare(PHP_VERSION, EWP_MINIMUM_PHP_VERSION, '>=')) {
    require_once $ewp_base_path . '/class/autoload.php';
    require_once $ewp_base_path . '/setup-ewp.php';
}
