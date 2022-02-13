<?php

namespace src\app;

use DateTime;
use DateTimeZone;

class Articles
{

    public static function getArticlesCount(int $published = 1): int
    {
        $sql = "select count(*) as count from tbl_articles where published >= ?";
        $articles_count = Database::select($sql, 'i', [$published]);
        return $articles_count[0]['count'];
    }

    private static function getLocalDateTime($datetime): DateTime
    {
        $date = new DateTime($datetime, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone('Europe/Berlin'));
        return $date;
    }

    public static function getArticles(int $offset, int $row_count, int $published = 1): array
    {
        $sql = <<<EOD
            select pk_article, title, content, created
            from tbl_articles
            where published >= ?
            order by created desc
            limit ?, ?
        EOD;
        $articles = Database::select($sql, 'iii', [$published, $offset, $row_count]);
        foreach ($articles as &$article) {
            $article['photos'] = Articles::getArticlePhotos($article['pk_article']);
            $article['created'] = Articles::getLocalDateTime($article['created'])->format('d.m.Y, H:i');
        }
        return $articles;
    }

    public static function getArticle(int $pk_article): ?array
    {
        $sql = <<<EOD
            select pk_article, title, content, created, published
            from tbl_articles
            where pk_article = ?
        EOD;
        $articles = Database::select($sql, 'i', [$pk_article]);
        if (count($articles) == 1) {
            $article = $articles[0];
            $article['photos'] = Articles::getArticlePhotos($article['pk_article']);
            $article['created'] = Articles::getLocalDateTime($article['created'])->format('Y-m-d\TH:i');
            return $article;
        }
        return null;
    }

    public static function getArticlePhotos(int $pk_article): array
    {
        $sql = <<<EOD
            select pk_photo, title, thumbnail_type, photo_type
            from tbl_photos
            join tbl_articles_photos on fk_photo = pk_photo
            where fk_article = ?
            order by uploaded desc
        EOD;
        return Database::select($sql, 'i', [$pk_article]);
    }

}
