<?php

namespace src\app;

use src\utils\HTMLPurifierExtension;
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
        $this->twig->addExtension(new HTMLPurifierExtension());
        $this->twig->addRuntimeLoader(new class implements RuntimeLoaderInterface {
            public function load($class): ?MarkdownRuntime
            {
                if (MarkdownRuntime::class === $class) {
                    return new MarkdownRuntime(new DefaultMarkdown());
                }
                return null;
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
            'iphash' => IPHASH,
            'header_image_height' => $header_image_height,
            'header_image' => $header_image,
            'version' => '?v17',
            'telegram_bot' => 'haus_bad_freienwalde' . (isProd() ? '' : '_dev') . '_bot'
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

    private function add_comment(int $pk_article, array $article, array $data): ?array
    {
        $message = [];
        // For Bots
        if (
            $_SESSION['comment_timestamp'] + 3 > time() ||
            (isset($data['field-3']) && strlen($data['field-3']) > 0) ||
            (isset($data['field-1']) && str_contains($data['field-1'], 'CrytoAssox'))
        ) {
            $error_message = 'Comment blocked: ' . json_encode($data);
            error_log($error_message);
            $message['general'] = 'Es ist ein unbekannter Fehler aufgetreten. Versuche es später noch einmal.';
        }
        // For Users
        if (!isset($data['field-1']) || $data['field-1'] == '')
            $message['field-1'] = 'Bitte gib einen Namen ein.';
        if (!isset($data['field-2']) || $data['field-2'] == '')
            $message['field-2'] = 'Bitte gib einen Kommentar ein.';
        if (isset($data['field-1']) && strlen($data['field-1']) > 100)
            $message['field-1'] = 'Dein Name ist zu lang (maximal 100 Zeichen).';
        if (isset($data['field-2']) && strlen($data['field-2']) > 2000)
            $message['field-2'] = 'Dein Kommentar ist zu lang (maximal 2000 Zeichen).';
        if (count($message) == 0) {
            $pk_comment = Comments::insertComment(
                pk_article: $pk_article,
                creator: $data['field-1'],
                comment: $data['field-2'],
                created: now()->format('c'),
                published: Auth::isLoggedIn() ? 1 : 0
            );
            if (!Auth::isLoggedIn())
                Telegram::sendMessageForNewComment($pk_comment, $data['field-1'], $data['field-2'], $article['title']);
            header('Location: ' . URL . $_SERVER['REQUEST_URI'] . '#comments');
            exit;
        }
        return $message;
    }

    public function article(int $pk_article)
    {
        $article = Articles::getArticle($pk_article, Auth::isLoggedIn() ? 0 : 1);
        if ($article == null) {
            http_response_code(404);
            exit;
        }
        $message = [];
        if (isset($_POST) && count($_POST) > 0) $message = $this->add_comment($pk_article, $article, $_POST);
        else $_SESSION['comment_timestamp'] = time();
        $article['comments'] = Articles::getArticleComments($article['pk_article'], Auth::isLoggedIn() ? 0 : 1);
        $header_image = HEADER_IMAGE;
        if (count($article['photos']) > 0) {
            $photo = $article['photos'][0];
            $header_image = '/photos/' . $photo['pk_photo'] . '/' . $photo['id'] . '.' . $photo['photo_type'];
        }
        $this->render('article', $article['title'] . '.', [
            'article' => $article,
            'message' => $message
        ], header_image: $header_image, description: $article['title']);
    }

    public function webhook(array $data)
    {
        //console($data);
        if (array_key_exists('message', $data) && array_key_exists('from', $data['message'])) {
            $from = $data['message']['from'];
            $telegram = new Telegram($from['id'], $from['username']);
            if (array_key_exists('photo', $data['message'])) {
                $telegram->receivePhoto($data['message']['photo']);
            } elseif (array_key_exists('text', $data['message']) && array_key_exists('entities', $data['message'])) {
                $telegram->receiveCommand($data['message']['text']);
            } elseif (array_key_exists('text', $data['message']) && array_key_exists('reply_to_message', $data['message'])) {
                $telegram->receiveTextReply($data['message']['text'], $data['message']['reply_to_message']['text']);
            }
        }
        if (array_key_exists('callback_query', $data) && array_key_exists('from', $data['callback_query'])) {
            $from = $data['callback_query']['from'];
            $telegram = new Telegram($from['id'], $from['username']);
            if (array_key_exists('data', $data['callback_query'])) {
                $telegram->receiveButton(json_decode($data['callback_query']['data']));
            }
        }
    }

    public function photo(int $pk_photo, string $id, bool $thumbnail)
    {
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

    public function sitemap()
    {
        header("Content-type: text/xml");
        $articles = Articles::getArticles(0, 100000);
        $static_urls = [['loc' => URL, 'lastmod' => $articles[0]['created_iso']]];
        echo $this->twig->render('sitemap.xml', [
            'base_url' => URL,
            'static_urls' => $static_urls,
            'articles' => $articles
        ]);
    }

}
