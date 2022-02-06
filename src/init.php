<?php

session_start();
session_destroy();
session_start();

use Dotenv\Dotenv;
use src\app\Auth;

Dotenv::createImmutable(BASE)->load();

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
