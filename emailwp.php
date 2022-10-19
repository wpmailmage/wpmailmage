<?php

/**
 * Plugin Name: Mail Mage
 * Plugin URI: https://www.wpmailmage.com
 * Description: Mail Mage allows you to automate your WordPress marketing workflows helping to convert, retain and recover customers in WordPress, WooCommerce and other popular plugins.
 * Author: Mail Mage <hello@wpmailmage.com>
 * Version: 0.0.24 
 * Network: True
 * WC tested up to: 4.9
 */

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('email_wp')) {
    // Dont allow two version of plugin to be loaded
    return;
}

$ewp_base_path = dirname(__FILE__);

if (!defined('MAIL_MAGE_VERSION')) {
    define('MAIL_MAGE_VERSION', '0.0.24');
}

if (!defined('MAIL_MAGE_MINIMUM_PHP_VERSION')) {
    define('MAIL_MAGE_MINIMUM_PHP_VERSION', '5.4');
}

if (!defined('MAIL_MAGE_POST_TYPE')) {
    define('MAIL_MAGE_POST_TYPE', 'ewp-automation');
}

if (version_compare(PHP_VERSION, MAIL_MAGE_MINIMUM_PHP_VERSION, '>=')) {
    require_once $ewp_base_path . '/class/autoload.php';
    require_once $ewp_base_path . '/setup-ewp.php';
}
