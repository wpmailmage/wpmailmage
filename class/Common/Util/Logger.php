<?php

namespace EmailWP\Common\Util;

class Logger
{
    public static function write($message)
    {
        if (!defined('EWP_DEBUG') || false === EWP_DEBUG) {
            return;
        }

        $log_file = self::getLogFile();
        file_put_contents($log_file, date('Y-m-d H:i:s - ') . $message . "\n", FILE_APPEND);
    }

    public static function getLogFile($url = false)
    {
        return ($url ? WP_CONTENT_URL : WP_CONTENT_DIR) . '/ewp.log';
    }
}
