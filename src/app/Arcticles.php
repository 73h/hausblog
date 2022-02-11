<?php

namespace src\app;

class Arcticles
{

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
            $article['images'] = Arcticles::getArticleImages($article['pk_article']);

        }
        return $articles;
    }

    public static function getArticleImages(int $pk_article): array
    {
        $sql = <<<EOD
            select pk_image, title, type
            from tbl_images
            join tbl_articles_images on fk_image = pk_image
            where fk_article = ?
            order by uploaded desc
        EOD;
        return Database::select($sql, 'i', [$pk_article]);
    }

}
