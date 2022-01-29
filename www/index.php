<?php

use Steampixel\Route;

const BASE = __DIR__ . '/../';

/*
error_reporting(E_ALL);
ini_set('ignore_repeated_errors', TRUE);
ini_set('display_errors', FALSE);
ini_set('log_errors', TRUE);
ini_set('error_log', BASE . 'error.log');
*/

require_once BASE . 'vendor/autoload.php';
require BASE . 'autoload.php';

var_dump($_GET);

Route::add('/', function () {
    echo 'index';
});

Route::add('/login', function () {
    echo 'login';
});

Route::run('/');
