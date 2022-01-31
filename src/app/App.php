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
        $auth = (object)[
            "logged_in" => false,
            "user" => null,
            "error_message" => null
        ];
        $error = null;
        if (isset($_POST['user']) && isset($_POST['password'])) {
            $auth->user = $_POST['user'];
            $password = $_POST['password'];
            $auth->error_message = 'Error 73';
        }
        exit($this->twig->render('login.html', ['auth' => $auth]));
    }

    public function article($article)
    {
        echo htmlspecialchars($article);
    }

}
