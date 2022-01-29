<?php

$uri = explode("?", $_SERVER["REQUEST_URI"])[0];
if (in_array("QUERY_STRING", $_SERVER)) {
    $get_vars = explode("&", $_SERVER["QUERY_STRING"]);
    foreach ($get_vars as $get_var) {
        $get_var_pair = explode("=", $get_var);
        $_GET[$get_var_pair[0]] = ($get_var_pair[1] ?? "");
    }
}
if (preg_match('/^\/?[a-z\-]+\/?$/', $uri)) {
    $_GET['site'] = preg_replace('/^\/?([a-z\-]+)\/?$/', '$1', $uri);
    chdir('www/');
    require 'index.php';
} else if (preg_match('/^\/article\/?[a-z\-]+\/?$/', $uri)) {
    $_GET['site'] = 'article';
    $_GET['article'] = preg_replace('/^\/?([a-z\-]+)\/?$/', '$1', $uri);
    chdir('www/');
    require 'index.php';
} else {
    return false;
}
