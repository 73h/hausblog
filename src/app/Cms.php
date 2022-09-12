<?php

namespace src\app;

class Cms extends App
{

    public function login(string $code = null)
    {
        $message = '';
        if ($code !== null) {
            Auth::logInWithCode($code);
            if (!Auth::isEditor()) $message = 'Der Code ist ungültig oder abgelaufen.';
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
            ], header_image_height: CMS_HEADER_IMAGE_HEIGHT
        );
    }

    private function insertArticlePhotos(array $photos, int $pk_article)
    {
        $position = 1;
        foreach ($photos as $photo) {
            Articles::insertArticlePhoto($pk_article, $photo['pk_photo'], $position);
            $position++;
        }
    }

    public function cms_article_delete(int $pk_article)
    {
        Articles::deleteArticlePhotos($pk_article);
        Articles::deleteArticle($pk_article);
        header('Location: /cms/articles');
        exit;
    }

    public function cms_article_share(int $pk_article)
    {
        //$article = Articles::getArticle($pk_article);
        $url = URL . '/articles/' . $pk_article;
        foreach (Telegram::getAllFollower() as $user) {
            $message = 'Hallo ' . $user['user'] . ", Jessi und Heiko haben einen neuen Beitrag veröffentlicht.\r\n\r\nDein Hausblog-Bot \u{1F916}\u{1F9E1}\r\n\r\n" . $url;
            Telegram::sendMessage($user['telegram_id'], $message, web_page_preview: true);
        }
        header('Location: /cms/articles');
        exit;
    }

    public function cms_article(?int $pk_article)
    {
        $message = '';
        if ($pk_article !== null) $article = Articles::getArticle($pk_article, Auth::isEditor() ? 0 : 1);
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
                    $this->insertArticlePhotos($article['photos'], $pk_article);
                } else {
                    Articles::updateArticle(
                        $pk_article,
                        $article['title'],
                        $article['content'],
                        $article['created'],
                        $article['published']
                    );
                    Articles::deleteArticlePhotos($pk_article);
                    $this->insertArticlePhotos($article['photos'], $pk_article);
                }
                header('Location: /cms/articles');
                exit;
            } else {
                $message = 'Der Titel und Inhalt dürfen nicht leer sein.';
            }
        }
        $photos = Photos::getPhotos();
        $emoticons = Articles::getEmoticons();
        $this->render('cms_article', 'Eintrag bearbeiten.', [
            'article' => $article,
            'photos' => $photos,
            'message' => $message,
            'emoticons' => $emoticons
        ], header_image_height: CMS_HEADER_IMAGE_HEIGHT);
    }

    public function cms_photo(int $pk_photo)
    {
        $message = '';
        $photo = Photos::getPhotoData($pk_photo);
        if ($photo == null) {
            http_response_code(404);
            exit;
        }
        if (isset($_POST) && count($_POST) > 0) {
            $photo['title'] = $_POST['title'];
            if ($photo['title'] != '') {
                Photos::setPhotoTitle(
                    $pk_photo,
                    $photo['title']
                );
                header('Location: /cms/photos');
                exit;
            } else {
                $message = 'Der Titel darf nicht leer sein.';
            }
        }
        $this->render('cms_photo', 'Foto bearbeiten.', [
            'photo' => $photo,
            'message' => $message
        ], header_image_height: CMS_HEADER_IMAGE_HEIGHT);
    }

    public function cms_articles()
    {
        $articles = Articles::getArticles(0, 1000000, 0);
        $this->render('cms_articles', 'Redaktion.', [
            'articles' => $articles,
            'cmsnav' => 1
        ], header_image_height: CMS_HEADER_IMAGE_HEIGHT);
    }

    public function cms_photos()
    {
        $photos = Photos::getPhotos();
        $this->render('cms_photos', 'Redaktion.', [
            'photos' => $photos,
            'cmsnav' => 2
        ], header_image_height: CMS_HEADER_IMAGE_HEIGHT);
    }

    public function cms_photo_delete(int $pk_photo)
    {
        Photos::deletePhotoFromArticles($pk_photo);
        Photos::deletePhoto($pk_photo);
        header('Location: /cms/photos');
        exit;
    }

}