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
                $telegram->receiveImage($data['message']['photo']);
            } elseif (array_key_exists('text', $data['message']) && array_key_exists('entities', $data['message'])) {
                $telegram->receiveCommand($data['message']['text']);
            }
        }
    }

    public function article(string $article)
    {
        console($article);
    }

    public function image(int $pk_image, int $height)
    {
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            header('Last-Modified: ' . $_SERVER['HTTP_IF_MODIFIED_SINCE'], true, 304);
            exit;
        }
        $image = Images::getImage($pk_image);
        if ($image != null) {
            $width = $height * $image['width'] / $image['height'];
            $im = imagecreatefromstring($image['image']);
            $new = imagecreatetruecolor($width, $height) or exit("bad url");
            $x = imagesx($im);
            $y = imagesy($im);
            imagecopyresampled($new, $im, 0, 0, 0, 0, $width, $height, $x, $y) or exit("bad url");
            imagedestroy($im);
            header("Cache-Control: private, max-age=31536000, pre-check=31536000");
            header("Pragma: private");
            header("Expires: " . date(DATE_RFC822, strtotime("1 year")));
            header("Content-type: image/jpeg");
            imagejpeg($new, null, 80) or exit("bad url");
        }
    }

}
