<?php

use src\app\App;
use Steampixel\Route;

Route::add('/', function () {
    $app = new App();
    $app->index();
}, ['get']);

Route::add('/login', function () {
    $app = new App();
    $app->login($_POST['code'] ?? null);
}, ['get', 'post']);

Route::add('/login/([0-9]{6})', function ($code) {
    console($_SERVER["REMOTE_ADDR"]);
    console(gethostbyaddr($_SERVER["REMOTE_ADDR"]));
    $app = new App();
    $app->login($code);
}, ['get']);

Route::add('/article/([a-z0-9-]+)', function (string $article) {
    $app = new App();
    $app->article($article);
}, ['get']);

Route::add('/webhook', function () {
    $data = json_decode(file_get_contents('php://input'), TRUE);
    $app = new App();
    $app->webhook($data);
}, 'post');

Route::run('/');
