<?php

namespace src\app;

use DateTime;
use DateTimeZone;

class Arcticles
{

    public static function getArticlesCount(): int
    {
        $sql = "select count(*) as count from tbl_articles where published = 1";
        $articles_count = Database::select($sql);
        return $articles_count[0]['count'];
    }

    public static function getArticles(int $offset, int $row_count): array
    {
        $sql = <<<EOD
            select pk_article, title, content, created, url
            from tbl_articles
            where published = 1
            order by created desc
            limit ?, ?
        EOD;
        $articles = Database::select($sql, 'ii', [$offset, $row_count]);
        foreach ($articles as &$article) {
            $article['photos'] = Arcticles::getArticlePhotos($article['pk_article']);
            $date = new DateTime($article['created'], new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone('Europe/Berlin'));
            $article['created'] = $date->format('d.m.Y, H:i'); //date('d.m.Y, H:i', strtotime($article['created']));
        }
        return $articles;
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
