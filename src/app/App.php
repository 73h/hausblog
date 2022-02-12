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

    private function render(string $site, string $subtitle, array $parameters = [])
    {
        $basic_parameters = [
            'title' => 'Hausblog - Jessi & Heiko',
            'subtitle' => $subtitle,
            'description' => 'Wir bauen unser Traumhaus - Jessi & Heiko',
            'base_url' => URL,
            'url' => URL . $_SERVER['REQUEST_URI'],
            'version' => '?v1'
        ];
        echo $this->twig->render($site . '.html', array_merge($basic_parameters, $parameters));
    }

    public function index()
    {
        $articles = Arcticles::getArticles(0, 10);
        $this->render('index', 'Wir bauen unser Traumhaus.', ['articles' => $articles]);
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
        $this->render('login', 'Login.',
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
                $telegram->receivePhoto($data['message']['photo']);
            } elseif (array_key_exists('text', $data['message']) && array_key_exists('entities', $data['message'])) {
                $telegram->receiveCommand($data['message']['text']);
            }
        }
    }

    public function article(string $article)
    {
        console($article);
    }

    public function photo(int $pk_photo, bool $thumbnail)
    {
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            header('Last-Modified: ' . $_SERVER['HTTP_IF_MODIFIED_SINCE'], true, 304);
            exit;
        }
        $photo = Photos::getPhoto($pk_photo, $thumbnail);
        header('Cache-Control: private, max-age=31536000, pre-check=31536000');
        header('Pragma: private');
        header('Expires: ' . date(DATE_RFC822, strtotime('1 year')));
        header('Content-type: ' . match ($photo['type']) {
                'gif' => 'image/gif',
                'png' => 'image/png',
                'svg' => 'image/svg+xml',
                default => 'image/jpeg',
            });
        echo $photo['photo'];
    }

}
