<?php

session_start();

use Dotenv\Dotenv;
use src\app\Auth;

Dotenv::createImmutable(BASE)->load();
define("URL", (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);

function now(): string
{
    return (new DateTime('NOW'))->format('c');
}

function console(mixed $mixed)
{
    error_log(json_encode($mixed));
}

if (isset($_SESSION['auth'])) {
    Auth::loadUser($_SESSION['auth']['pk_user']);
}

function sanitize_output($buffer): array|string|null
{
    $search = array(
        '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
        '/[^\S ]+\</s',     // strip whitespaces before tags, except space
        '/(\s)+/s',         // shorten multiple whitespace sequences
        '/<!--(.|\s)*?-->/' // Remove HTML comments
    );
    $replace = array(
        '>',
        '<',
        '\\1',
        ''
    );
    $buffer = preg_replace($search, $replace, $buffer);
    return $buffer;
}

ob_start("sanitize_output");
