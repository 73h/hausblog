<?php

namespace src\app;

class Comments
{

    public static function insertComment(
        int    $pk_article,
        string $creator,
        string $comment,
        string $created,
        int    $published
    ): int
    {
        $sql = <<<EOD
            insert into tbl_comments
                (fk_article, creator, comment, creator_hash, created, published)
                values (?, ?, ?, ?, ?, ?);
        EOD;
        return Database::insert($sql, 'issssi', [
            $pk_article,
            $creator,
            $comment,
            IPHASH,
            $created,
            $published
        ]);
    }

    public static function deleteComment(int $pk_comment): int
    {
        $sql = "delete from tbl_comments where pk_comment = ?;";
        return Database::update_or_delete($sql, 'i', [$pk_comment]);
    }

    public static function publishComment(int $pk_comment): int
    {
        $sql = "update tbl_comments set published = 1 where pk_comment = ?;";
        return Database::update_or_delete($sql, 'i', [$pk_comment]);
    }

}
