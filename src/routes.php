<?php

use src\app\App;
use Steampixel\Route;

Route::add('/', function () {
    $app = new App();
    $app->index();
});

Route::add('/login', function () {
    $app = new App();
    $app->login();
}, ['get', 'post']);

Route::add('/article/([a-z0-9-]+)', function ($article) {
    $app = new App();
    $app->article($article);
});

Route::add('/webhook', function () {
    $data = json_decode(file_get_contents('php://input'), TRUE);
    $app = new App();
    $app->webhook($data);
}, 'post');

Route::run('/');
