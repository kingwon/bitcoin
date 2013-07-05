<?php

require_once CORE_ROOT.'function.php';
require_once APP_ROOT.'lib/klein.php';

spl_autoload_register(function ($class_name) {
    $fpath = CORE_ROOT.$class_name.'.php';
    if (file_exists($fpath)) {
        require_once $fpath;
        return;
    }
    if (preg_match('/Controller$/', $class_name)) {
        $fpath = APP_ROOT."controller/$class_name.php";
        if (file_exists($fpath)) {
            require_once $fpath;
        }
    } else {
        require_once APP_ROOT.'lib/idiorm.php';
        $fpath = APP_ROOT."model/$class_name.php";
        if (file_exists($fpath)) {
            require_once $fpath;
        }
    }
});