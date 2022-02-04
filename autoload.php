<?php

spl_autoload_register(function ($class_name) {
    $class_file = str_replace('\\', '/', $class_name) . '.php';
    if (is_file(BASE . $class_file)) {
        include $class_file;
    }
});
