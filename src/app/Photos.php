<?php

namespace src\app;

class Photos
{

    function insertPhoto(
        string $uploaded,
        string $title,
        string $thumbnail,
        string $thumbnail_type,
        string $photo,
        string $photo_type)
    {
        $sql = <<<EOD
            insert into tbl_photos
                (uploaded, title, thumbnail, thumbnail_type, photo, photo_type, id)
                values (?, ?, ?, ?, ?, ?, left(md5(rand()),16));
        EOD;
        $parameters = [
            $uploaded,
            $title,
            $thumbnail,
            $thumbnail_type,
            $photo,
            $photo_type
        ];
        Database::insert($sql, 'ssssss', $parameters);
    }

    public static function getPhoto(int $pk_photo, string $id, bool $thumbnail): ?array
    {
        if ($thumbnail) {
            $sql = <<<EOD
                select thumbnail as photo, thumbnail_type as type from tbl_photos
                where pk_photo = ? and id = ?;
            EOD;
        } else {
            $sql = <<<EOD
                select photo, photo_type as type from tbl_photos
                where pk_photo = ? and id = ?;
            EOD;
        }
        $photos = Database::select($sql, 'is', [$pk_photo, $id]);
        if (count($photos) == 1) return $photos[0];
        return null;
    }

    public static function getPhotoData(int $pk_photo): ?array
    {
        $sql = 'select pk_photo, uploaded, title, thumbnail_type, photo_type, id from tbl_photos where pk_photo = ?;';
        $photos = Database::select($sql, 'i', [$pk_photo]);
        if (count($photos) == 1) return $photos[0];
        return null;
    }

    public static function getPhotos(): array
    {
        $sql = "select pk_photo, uploaded, title, thumbnail_type, photo_type, id from tbl_photos order by uploaded desc;";
        return Database::select($sql);
    }

    public static function deletePhotoFromArticles(int $pk_photo)
    {
        $sql = "delete from tbl_articles_photos where fk_photo = ?;";
        Database::update_or_delete($sql, 'i', [$pk_photo]);
    }

    public static function deletePhoto(int $pk_photo)
    {
        $sql = "delete from tbl_photos where pk_photo = ?;";
        Database::update_or_delete($sql, 'i', [$pk_photo]);
    }

    public static function setPhotoTitle(int $pk_photo, string $title)
    {
        $sql = "update tbl_photos set title = ? where pk_photo = ?;";
        Database::update_or_delete($sql, 'si', [$title, $pk_photo]);
    }

}
