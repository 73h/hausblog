<?php

const BASE = __DIR__ . '/../';

error_reporting(E_ALL); // Error/Exception engine, always use E_ALL
ini_set('ignore_repeated_errors', TRUE); // always use TRUE
ini_set('display_errors', FALSE); // Error/Exception display, use FALSE only in production environment or real server. Use TRUE in development environment
ini_set('log_errors', TRUE); // Error/Exception file logging engine.
ini_set('error_log', BASE . '../../logs/error.log'); // Logging file path

require_once BASE . 'vendor/autoload.php';
require BASE . 'autoload.php';

use src\App;

new App();
