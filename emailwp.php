<?php

/**
 * Plugin Name: Mail Mage
 * Plugin URI: https://www.wpmailmage.com
 * Description: Mail Mage allows you to ...
 * Author: Mail Mage <hello@wpmailmage.com>
 * Version: 0.0.5 
 * Author URI: https://www.wpmailmage.com
 * Network: True
 */

$ewp_base_path = dirname(__FILE__);

if (!defined('EWP_VERSION')) {
    define('EWP_VERSION', '0.0.5');
}

if (!defined('EWP_MINIMUM_PHP_VERSION')) {
    define('EWP_MINIMUM_PHP_VERSION', '5.4');
}

if (!defined('EWP_POST_TYPE')) {
    define('EWP_POST_TYPE', 'ewp-automation');
}

if (version_compare(PHP_VERSION, EWP_MINIMUM_PHP_VERSION, '>=')) {
    require_once $ewp_base_path . '/class/autoload.php';

    $updater = new \EmailWP\Github\Updater\Updater(__FILE__);
    $updater->set_username('wpmailmage');
    $updater->set_repository('wpmailmage');
    $updater->initialize();

    require_once $ewp_base_path . '/setup-ewp.php';
}
