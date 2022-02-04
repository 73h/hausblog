<?php

namespace src\app;

use DateTime;
use Dotenv\Dotenv;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;


class App
{

    private Environment $twig;

    function __construct()
    {
        Dotenv::createImmutable(BASE)->load();
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

    public function webhook($data)
    {
        $token = $_ENV['TELEGRAM_TOKEN'];
        if (array_key_exists('photo', $data['message'])) {
            $photo = $data['message']['photo'][count($data['message']['photo']) - 1];
            $url_image_data = 'https://api.telegram.org/bot' . $token . '/getFile?file_id=' . $photo['file_id'];
            $image_data = json_decode(file_get_contents($url_image_data));
            $url_image = 'https://api.telegram.org/file/bot' . $token . '/' . $image_data->result->file_path;
            $type = preg_replace('/^.+\.([a-zA-Z]{2,6})$/', '$1', $image_data->result->file_path);
            $title = uniqid();
            $objDateTime = new DateTime('NOW');
            $image = new Image();
            $image->create(
                name: $title . '.' . $type,
                uploaded: $objDateTime->format('c'),
                title: $title,
                image: file_get_contents($url_image),
                type: $type,
                width: $photo['width'],
                height: $photo['height']
            );
        }
    }

    public function article($article)
    {
        echo htmlspecialchars($article);
    }

}
