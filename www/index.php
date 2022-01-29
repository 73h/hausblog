<?php

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
require_once BASE . 'src/routes.php';
