<?php

namespace src\app;

use DateTime;
use DateTimeInterface;
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

    public static function getEmoticons(): array
    {
        return [
            'slightly-smiling-face',
            'winking-face',
            'astonished-face',
            'dizzy-face',
            'flushed-face',
            'grinning-face-with-big-eyes',
            'grinning-face-with-sweat',
            'slightly-frowning-face',
            'smiling-face-with-heart-eyes',
            'smiling-face-with-hearts',
            'smiling-face-with-sunglasses',
            'star-struck',
            'zany-face',
            'house'
        ];
    }

    private static function replaceEmoticons(string $content): string
    {
        $short_emoticons = [
            ':)' => 'slightly-smiling-face',
            ':-)' => 'slightly-smiling-face',
            ';)' => 'winking-face',
            ';-)' => 'winking-face',
            ':D' => 'grinning-face-with-big-eyes',
            ':-D' => 'grinning-face-with-big-eyes'
        ];
        foreach ($short_emoticons as $short => $emoticon) {
            $content = str_replace($short, $emoticon, $content);
        }
        $emoticons = Articles::getEmoticons();
        foreach ($emoticons as $emoticon) {
            $content = str_replace($emoticon, '<img src="/assets/icons/icons8-' . $emoticon . '-48.png" class="emoticon" alt="' . $emoticon . '">', $content);
        }
        return $content;
    }

    private static function createEmbeddedVideos(string $content): string
    {
        preg_match_all('/YT\((.+)\)/', $content, $videos, PREG_SET_ORDER);
        foreach ($videos as $video) {
            $content = str_replace($video[0], '<div class="embedded-videos"><iframe src="https://www.youtube.com/embed/' . $video[1] . '" title="YouTube video player" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>', $content);
        }
        return $content;
    }

    public static function getArticles(int $offset, int $row_count, int $published = 1): array
    {
        $sql = <<<EOD
            select
                pk_article,
                title,
                content,
                created,
                published,
                (
                    select count(*) from tbl_comments
                    where fk_article = pk_article
                    and ( published >= ? or creator_hash = ? )
                ) as comments
            from tbl_articles
            where published >= ?
            order by created desc
            limit ?, ?;
        EOD;
        $articles = Database::select($sql, 'isiii', [$published, IPHASH, $published, $offset, $row_count]);
        foreach ($articles as &$article) {
            $article['photos'] = Articles::getArticlePhotos($article['pk_article']);
            $article['created_iso'] = Articles::convertUtcToCet($article['created'])->format(DateTimeInterface::ATOM);
            $article['created'] = Articles::convertUtcToCet($article['created'])->format('d.m.Y, H:i');
            $rendered_content = $article['content'];
            $rendered_content = Articles::replaceEmoticons($rendered_content);
            $rendered_content = Articles::createEmbeddedVideos($rendered_content);
            $article['rendered_content'] = $rendered_content;
        }
        return $articles;
    }

    public static function getArticle(int $pk_article, int $published = 1): ?array
    {
        $sql = <<<EOD
            select pk_article, title, content, created, published
            from tbl_articles
            where pk_article = ?
            and published >= ?;
        EOD;
        $articles = Database::select($sql, 'ii', [$pk_article, $published]);
        if (count($articles) == 1) {
            $article = $articles[0];
            $article['photos'] = Articles::getArticlePhotos($article['pk_article']);
            $article['created_cms'] = Articles::convertUtcToCet($article['created'])->format('Y-m-d\TH:i');
            $article['created'] = Articles::convertUtcToCet($article['created'])->format('d.m.Y, H:i');
            $rendered_content = $article['content'];
            $rendered_content = Articles::replaceEmoticons($rendered_content);
            $rendered_content = Articles::createEmbeddedVideos($rendered_content);
            $article['rendered_content'] = $rendered_content;
            return $article;
        }
        return null;
    }

    public static function getArticlePhotos(int $pk_article): array
    {
        $sql = <<<EOD
            select pk_photo, title, thumbnail_type, photo_type, position, id
            from tbl_photos
            join tbl_articles_photos on fk_photo = pk_photo
            where fk_article = ?
            order by position, uploaded desc;
        EOD;
        return Database::select($sql, 'i', [$pk_article]);
    }

    public static function getArticleComments(int $pk_article, int $published = 1): array
    {
        $sql = <<<EOD
            select creator, comment, creator_hash, created, published from tbl_comments
            where fk_article = ?
            and ( published >= ? or creator_hash = ? )
            order by created desc;
        EOD;
        $comments = Database::select($sql, 'iis', [$pk_article, $published, IPHASH]);
        foreach ($comments as &$comment) {
            $comment['created'] = Articles::convertUtcToCet($comment['created'])->format('d.m.Y, H:i');
            $comment['comment'] = Articles::replaceEmoticons($comment['comment']);
        }
        return $comments;
    }

    public static function deleteArticlePhotos(int $pk_article)
    {
        $sql = "delete from tbl_articles_photos where fk_article = ?;";
        Database::update_or_delete($sql, 'i', [$pk_article]);
    }

    public static function insertArticlePhoto(int $pk_article, int $pk_photo, $position)
    {
        $sql = "insert into tbl_articles_photos (fk_article, fk_photo, position) values (?, ?, ?);";
        Database::insert($sql, 'iii', [$pk_article, $pk_photo, $position]);
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
        int    $published): int
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
