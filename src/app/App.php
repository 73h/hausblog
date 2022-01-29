<?php

namespace src\app;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class App
{

    private Environment $twig;

    function __construct()
    {
        $loader = new FilesystemLoader(BASE . 'templates');
        $this->twig = new Environment($loader);
    }

    public function index()
    {
        exit($this->twig->render('index.html', ['name' => 'Heiko']));
    }

    public function login()
    {
        exit($this->twig->render('login.html', ['logged_in' => false]));
    }

    public function article($article)
    {
        echo htmlspecialchars($article);
    }

}
