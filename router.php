<?php

$uri = explode("?", $_SERVER["REQUEST_URI"])[0];
if (in_array("QUERY_STRING", $_SERVER)) {
    $get_vars = explode("&", $_SERVER["QUERY_STRING"]);
    foreach ($get_vars as $get_var) {
        $get_var_pair = explode("=", $get_var);
        $_GET[$get_var_pair[0]] = ($get_var_pair[1] ?? "");
    }
}
if (preg_match('/^\/?assets\/.+/', $uri)) {
    return false;
} else {
    chdir('www/');
    require 'index.php';
}
