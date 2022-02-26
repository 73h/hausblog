<?php

session_start();

use Dotenv\Dotenv;
use src\app\Auth;

Dotenv::createImmutable(BASE)->load();
define("URL", (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);
const ROW_COUNT = 10;
const CMS_HEADER_IMAGE_HEIGHT = 10;

function now(): DateTime
{
    return new DateTime('NOW', new DateTimeZone('UTC'));
}

function now_cet(): DateTime
{
    $date = new DateTime('NOW', new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Europe/Berlin'));
    return $date;
}

function console(mixed $mixed)
{
    error_log(json_encode($mixed));
}

if (isset($_SESSION['auth'])) {
    Auth::loadUser($_SESSION['auth']['pk_user']);
}
