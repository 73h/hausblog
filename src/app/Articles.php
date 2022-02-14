<?php

namespace src\app;

use DateTime;
use DateTimeZone;

class Articles
{

    public static function getArticlesCount(int $published = 1): int
    {
        $sql = "select count(*) as count from tbl_articles where published >= ?;";
        $articles_count = Database::select($sql, 'i', [$published]);
        return $articles_count[0]['count'];
    }

    private static function convertUtcToCet(string $datetime): DateTime
    {
        $date = new DateTime($datetime, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone('Europe/Berlin'));
        return $date;
    }

    private static function convertCetToUtc(string $datetime): DateTime
    {
        $date = new DateTime($datetime, new DateTimeZone('Europe/Berlin'));
        $date->setTimezone(new DateTimeZone('UTC'));
        return $date;
    }

    public static function getArticles(int $offset, int $row_count, int $published = 1): array
    {
        $sql = <<<EOD
            select pk_article, title, content, created, published
            from tbl_articles
            where published >= ?
            order by created desc
            limit ?, ?;
        EOD;
        $articles = Database::select($sql, 'iii', [$published, $offset, $row_count]);
        foreach ($articles as &$article) {
            $article['photos'] = Articles::getArticlePhotos($article['pk_article']);
            $article['created'] = Articles::convertUtcToCet($article['created'])->format('d.m.Y, H:i');
        }
        return $articles;
    }

    public static function getArticle(int $pk_article): ?array
    {
        $sql = <<<EOD
            select pk_article, title, content, created, published
            from tbl_articles
            where pk_article = ?;
        EOD;
        $articles = Database::select($sql, 'i', [$pk_article]);
        if (count($articles) == 1) {
            $article = $articles[0];
            $article['photos'] = Articles::getArticlePhotos($article['pk_article']);
            $article['created'] = Articles::convertUtcToCet($article['created'])->format('Y-m-d\TH:i');
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
            order by uploaded desc;
        EOD;
        return Database::select($sql, 'i', [$pk_article]);
    }

    public static function deleteArticlePhotos(int $pk_article)
    {
        $sql = "delete from tbl_articles_photos where fk_article = ?;";
        Database::update_or_delete($sql, 'i', [$pk_article]);
    }

    public static function insertArticlePhoto(int $pk_article, int $pk_photo)
    {
        $sql = "insert into tbl_articles_photos (fk_article, fk_photo) values (?, ?);";
        Database::insert($sql, 'ii', [$pk_article, $pk_photo]);
    }

    public static function updateArticle(
        int    $pk_article,
        string $title,
        string $content,
        string $created,
        int    $published)
    {
        $sql = <<<EOD
            update tbl_articles
            set title = ?,
                content = ?,
                created = ?,
                published = ?
            where pk_article = ?;
        EOD;
        Database::update_or_delete($sql, 'sssii', [
            $title,
            $content,
            Articles::convertCetToUtc($created)->format('c'),
            $published,
            $pk_article
        ]);
    }

    public static function insertArticle(
        string $title,
        string $content,
        string $created,
        int    $published)
    {
        $sql = <<<EOD
            insert into tbl_articles
                (title, content, created, published)
                values (?, ?, ?, ?);
        EOD;
        return Database::insert($sql, 'sssi', [
            $title,
            $content,
            Articles::convertCetToUtc($created)->format('c'),
            $published
        ]);
    }

    public static function deleteArticle(int $pk_article)
    {
        $sql = "delete from tbl_articles where pk_article = ?;";
        Database::update_or_delete($sql, 'i', [$pk_article]);
    }

}
