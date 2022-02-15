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

    private function render(string $site, string $subtitle, array $parameters = [])
    {
        $basic_parameters = [
            'login_state' => Auth::isLoggedIn(),
            'title' => 'Hausblog - Jessi & Heiko',
            'subtitle' => $subtitle,
            'description' => 'Der Weg in unser eigenes Haus.',
            'base_url' => URL,
            'url' => URL . $_SERVER['REQUEST_URI'],
            'version' => '?v3'
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
            'to' => (ROW_COUNT * ($offset + 1) > $articles_count ? $articles_count : ROW_COUNT * ($offset + 1))
        ]);
    }

    public function login(string $code = null)
    {
        $message = '';
        if ($code !== null) {
            Auth::logInWithCode($code);
            if (!Auth::isLoggedIn()) $message = 'Das Code ist ungültig oder abgelaufen.';
            else {
                $_SESSION['auth']['pk_user'] = Auth::$pk_user;
                header('Location: /cms/articles');
                exit;
            }
        }
        $this->render('login', 'Login.',
            [
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

    public function cms_article_delete(int $pk_article)
    {
        Articles::deleteArticlePhotos($pk_article);
        Articles::deleteArticle($pk_article);
        header('Location: /cms/articles');
        exit;
    }

    public function cms_article(?int $pk_article)
    {
        $message = '';
        if ($pk_article !== null) $article = Articles::getArticle($pk_article);
        else {
            $article = [
                'created' => now_cet()->format('Y-m-d\TH:i'),
                'title' => '',
                'content' => '',
                'published' => 0,
                'photos' => []
            ];
        }
        if (isset($_POST) && count($_POST) > 0) {
            $photos = [];
            foreach (explode(';', $_POST['photos']) as $pk_photo) {
                array_push($photos, ['pk_photo' => intval($pk_photo)]);
            }
            $article['created'] = $_POST['created'];
            $article['title'] = $_POST['title'];
            $article['content'] = $_POST['content'];
            $article['published'] = isset($_POST['published']) ? 1 : 0;
            $article['photos'] = $photos;
            if ($article['title'] != '' && $article['content'] != '') {
                if ($pk_article == null) {
                    $pk_article = Articles::insertArticle(
                        $article['title'],
                        $article['content'],
                        $article['created'],
                        $article['published']
                    );
                    foreach ($article['photos'] as $photo) {
                        Articles::insertArticlePhoto($pk_article, $photo['pk_photo']);
                    }
                    header('Location: /cms/articles/' . $pk_article);
                    exit;
                } else {
                    Articles::updateArticle(
                        $pk_article,
                        $article['title'],
                        $article['content'],
                        $article['created'],
                        $article['published']
                    );
                    Articles::deleteArticlePhotos($pk_article);
                    foreach ($article['photos'] as $photo) {
                        console($pk_article);
                        console($photo['pk_photo']);
                        Articles::insertArticlePhoto($pk_article, $photo['pk_photo']);
                    }
                }
            } else {
                $message = 'Der Titel und Inhalt dürfen nicht leer sein.';
            }
        }
        $photos = Photos::getPhotos();
        $this->render('cms_article', 'Eintrag bearbeiten.', [
            'article' => $article,
            'photos' => $photos,
            'message' => $message
        ]);
    }

    public function cms_photo_delete(int $pk_photo)
    {
        Photos::deletePhotoFromArticles($pk_photo);
        Photos::deletePhoto($pk_photo);
        header('Location: /cms/photos');
        exit;
    }

    public function cms_photo(int $pk_photo)
    {
        $message = '';
        $photo = Photos::getPhotoData($pk_photo);
        if (isset($_POST) && count($_POST) > 0) {
            $photo['title'] = $_POST['title'];
            if ($photo['title'] != '') {
                Photos::setPhotoTitle(
                    $pk_photo,
                    $photo['title']
                );
            } else {
                $message = 'Der Titel darf nicht leer sein.';
            }
        }
        $this->render('cms_photo', 'Foto bearbeiten.', [
            'photo' => $photo,
            'message' => $message
        ]);
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

    public function cms_articles()
    {
        $articles = Articles::getArticles(0, 1000000, 0);
        $this->render('cms_articles', 'Redaktion.', ['articles' => $articles, 'cmsnav' => 1]);
    }

    public function cms_photos()
    {
        $photos = Photos::getPhotos();
        $this->render('cms_photos', 'Redaktion.', ['photos' => $photos, 'cmsnav' => 2]);
    }

}
