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

    public function login(string $code = null)
    {
        $message = '';
        if ($code !== null) {
            Auth::logInWithCode($code);
            if (!Auth::isLoggedIn()) $message = 'Das Code ist ungültig oder abgelaufen.';
            else {
                $_SESSION['auth']['pk_user'] = Auth::$pk_user;
                header('Location: /login');
                exit;
            }
        }
        echo $this->twig->render('login.html',
            [
                'login_state' => Auth::isLoggedIn(),
                'user' => Auth::$user,
                'message' => $message
            ]
        );
    }

    public function webhook(array $data)
    {
        if (array_key_exists('message', $data) && array_key_exists('from', $data['message'])) {
            $from = $data['message']['from'];
            $telegram = new Telegram($from['id'], $from['username']);
            if (array_key_exists('photo', $data['message'])) {
                $telegram->receiveImage($data['message']['photo']);
            } elseif (array_key_exists('text', $data['message']) && array_key_exists('entities', $data['message'])) {
                $telegram->receiveCommand($data['message']['text']);
            }
        }
    }

    public function article(string $article)
    {
        echo htmlspecialchars($article);
    }

}
