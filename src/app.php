<?php

namespace src;

require_once BASE . 'vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class App
{

    private string $site = 'index';

    function __construct()
    {

        if (array_key_exists('site', $_GET)) {
            $this->site = $_GET['site'];
        }

        $loader = new FilesystemLoader(BASE . 'templates');
        $twig = new Environment($loader);

        echo $twig->render('index.html', ['site' => $this->site]);

        var_dump($_GET);

    }

}
