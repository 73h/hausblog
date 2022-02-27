<?php

namespace src\app;

use Twig\Environment;
use Twig\Extra\Markdown\DefaultMarkdown;
use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;


class App
{

    private Environment $twig;

    function __construct()
    {
        $loader = new FilesystemLoader(BASE . 'templates');
        $this->twig = new Environment($loader);
        $this->twig->addExtension(new MarkdownExtension());
        $this->twig->addRuntimeLoader(new class implements RuntimeLoaderInterface {
            public function load($class)
            {
                if (MarkdownRuntime::class === $class) {
                    return new MarkdownRuntime(new DefaultMarkdown());
                }
            }
        });
    }

    protected function render(
        string $site,
        string $subtitle,
        array  $parameters = [],
        int    $header_image_height = 24,
        string $header_image = HEADER_IMAGE,
        string $description = 'Der Weg in unser eigenes Haus.'
    )
    {
        $basic_parameters = [
            'login_state' => Auth::isLoggedIn(),
            'title' => 'Hausblog - Jessi & Heiko',
            'subtitle' => $subtitle,
            'description' => $description,
            'base_url' => URL,
            'url' => URL . $_SERVER['REQUEST_URI'],
            'header_image_height' => $header_image_height,
            'header_image' => $header_image,
            'version' => '?v11'
        ];
        echo $this->twig->render($site . '.html', array_merge($basic_parameters, $parameters));
    }

    public function index(?int $page)
    {
        $offset = ($page != null ? $page : 0) * ROW_COUNT;
        $articles_count = Articles::getArticlesCount(Auth::isLoggedIn() ? 0 : 1);
        $articles = Articles::getArticles($offset, ROW_COUNT, Auth::isLoggedIn() ? 0 : 1);
        $this->render('index', 'Der Weg in unser eigenes Haus.', [
            'articles' => $articles,
            'articles_count' => $articles_count,
            'page' => $page,
            'from' => $offset + 1,
            'to' => (($offset + ROW_COUNT) > $articles_count ? $articles_count : ($offset + ROW_COUNT)),
            'ROW_COUNT' => ROW_COUNT
        ]);
    }

    public function article(int $pk_article)
    {
        $article = Articles::getArticle($pk_article, Auth::isLoggedIn() ? 0 : 1);
        if ($article == null) {
            http_response_code(404);
            exit;
        }
        $header_image = HEADER_IMAGE;
        if (count($article['photos']) > 0) {
            $photo = $article['photos'][0];
            $header_image = '/photos/' . $photo['pk_photo'] . '/' . $photo['id'] . '.' . $photo['photo_type'];
        }
        $this->render('article', $article['title'] . '.', [
            'article' => $article
        ], header_image: $header_image, description: $article['title']);
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

    public function photo(int $pk_photo, string $id, bool $thumbnail)
    {
        console([$pk_photo, $id, $thumbnail]);
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            header('Last-Modified: ' . $_SERVER['HTTP_IF_MODIFIED_SINCE'], true, 304);
            exit;
        }
        $photo = Photos::getPhoto($pk_photo, $id, $thumbnail);
        if ($photo == null) {
            http_response_code(404);
            exit;
        }
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
