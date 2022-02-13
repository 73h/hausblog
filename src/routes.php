<?php

use src\app\App;
use Steampixel\Route;

Route::add('/', function () {
    $app = new App();
    $app->index($_GET['page'] ?? null);
}, ['get']);

Route::add('/login', function () {
    $app = new App();
    $app->login($_POST['code'] ?? null);
}, ['get', 'post']);

Route::add('/login/([0-9]{6})', function ($code) {
    $app = new App();
    $app->login($code);
}, ['get']);

Route::add('/logout', function () {
    session_destroy();
    header('Location: ' . URL);
    exit;
}, ['get']);

Route::add('/article/([a-z0-9-]+)', function (string $article) {
    $app = new App();
    $app->article($article);
}, ['get']);

Route::add('/photos/([0-9]+)/(tn|p).[a-z]+', function (int $pk_image, string $type) {
    $thumbnail = $type == 'tn';
    $app = new App();
    $app->photo($pk_image, $thumbnail);
}, ['get']);

Route::add('/webhook', function () {
    $data = json_decode(file_get_contents('php://input'), TRUE);
    $app = new App();
    $app->webhook($data);
}, 'post');

Route::run('/');
