<?php

use src\app\App;
use src\app\Auth;
use src\app\Cms;
use Steampixel\Route;

function redirectWhenNotAuth()
{
    if (!Auth::isEditor()) {
        header('Location: ' . URL);
        exit;
    }
}

Route::add('/', function () {
    $app = new App();
    $app->index($_GET['page'] ?? null);
}, ['get']);

Route::add('/articles/([0-9]+)', function (int $pk_article) {
    $app = new App();
    $app->article($pk_article);
}, ['get', 'post']);

Route::add('/photos/([0-9]+)/(tn/|)([0-9a-z]+)\.[a-z]+', function (int $pk_image, string $type, string $id) {
    $thumbnail = $type == 'tn/';
    $app = new App();
    $app->photo($pk_image, $id, $thumbnail);
}, ['get']);

Route::add('/webhook', function () {
    $data = json_decode(file_get_contents('php://input'), TRUE);
    $app = new App();
    $app->webhook($data);
}, 'post');

Route::add('/login', function () {
    $cms = new Cms();
    $cms->login($_POST['code'] ?? null);
}, ['get', 'post']);

Route::add('/login/([0-9]{6})', function ($code) {
    $app = new Cms();
    $app->login($code);
}, ['get']);

Route::add('/logout', function () {
    session_destroy();
    header('Location: ' . URL);
    exit;
}, ['get']);

Route::add('/cms', function () {
    header('Location: /cms/articles');
    exit;
}, ['get']);

Route::add('/cms/articles', function () {
    redirectWhenNotAuth();
    $app = new Cms();
    $app->cms_articles();
}, ['get']);

Route::add('/cms/articles/([0-9]+)', function (int $pk_article) {
    redirectWhenNotAuth();
    $app = new Cms();
    $app->cms_article($pk_article);
}, ['get', 'post']);

Route::add('/cms/articles/([0-9]+)/delete', function (int $pk_article) {
    redirectWhenNotAuth();
    $app = new Cms();
    $app->cms_article_delete($pk_article);
}, ['get']);

Route::add('/cms/articles/new', function () {
    redirectWhenNotAuth();
    $app = new Cms();
    $app->cms_article(null);
}, ['get', 'post']);

Route::add('/cms/photos', function () {
    redirectWhenNotAuth();
    $app = new Cms();
    $app->cms_photos();
}, ['get']);

Route::add('/cms/photos/([0-9]+)', function (int $pk_photo) {
    redirectWhenNotAuth();
    $app = new Cms();
    $app->cms_photo($pk_photo);
}, ['get', 'post']);

Route::add('/cms/photos/([0-9]+)/delete', function (int $pk_photo) {
    redirectWhenNotAuth();
    $app = new Cms();
    $app->cms_photo_delete($pk_photo);
}, ['get']);

Route::add('/robots.txt', function () {
    header('Content-Type:text/plain');
    echo "User-Agent: *\r\nDisallow: /login\r\nSitemap: " . URL . "/sitemap.xml";
}, ['get']);

Route::add('/sitemap.xml', function () {
    $app = new App();
    $app->sitemap();
}, ['get']);

Route::run('/');
