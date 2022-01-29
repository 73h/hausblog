<?php

namespace src;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class App
{

    private Environment $twig;

    function __construct()
    {
        $loader = new FilesystemLoader(BASE . 'templates');
        $this->twig = new Environment($loader);

        // read and set site
        $site = 'index';
        if (array_key_exists('site', $_GET)) {
            $site = $_GET['site'];
        }
        switch ($site) {
            case 'article':
                $this->article();
                break;
            default:
                $this->index();
        }
    }

    private function index()
    {
        exit($this->twig->render('index.html', ['name' => 'Heiko']));
    }

    private function article()
    {
        if (!array_key_exists('article', $_GET)) {
            $this->index();
        }
        $article = $_GET['article'];
        echo htmlspecialchars($article);
    }

}
