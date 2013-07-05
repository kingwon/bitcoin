<?php

/**
* Config
*/
class Config
{
    private static $_config;

    public static function get($key)
    {
        if (!self::$_config) {
            $root = APP_ROOT.'config/';
            self::$_config = array_merge(
                include $root.'common.php',
                include $root.ENV.'.php'
            );
        }
        return isset(self::$_config[$key]) ? self::$_config[$key] : null;
    }
}
