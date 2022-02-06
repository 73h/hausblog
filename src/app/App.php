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
        echo $this->twig->render('index.html', ['foo' => 'bar']);
    }

    public function login()
    {
        $auth = (object)[
            "logged_in" => false,
            "error_message" => null
        ];
        if (isset($_POST['code'])) {
            $code = $_POST['code'];
            $auth->error_message = 'Error 73';
        }
        echo $this->twig->render('login.html', ['auth' => $auth]);
    }

    public function webhook(array $data)
    {
        $from = $data['message']['from'];
        $telegram = new Telegram($from['id'], $from['username']);
        if (array_key_exists('photo', $data['message'])) {
            $telegram->receiveImage($data['message']['photo']);
        } elseif (array_key_exists('text', $data['message']) && array_key_exists('entities', $data['message'])) {
            $telegram->receiveCommand($data['message']['text']);
        }
    }

    public function article(string $article)
    {
        echo htmlspecialchars($article);
    }

}
