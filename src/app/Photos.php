<?php

namespace src\app;

class Photos
{

    function insertPhoto(
        string $name,
        string $uploaded,
        string $title,
        string $thumbnail,
        string $thumbnail_type,
        string $photo,
        string $photo_type)
    {
        $sql = <<<EOD
            insert into tbl_photos
                (name, uploaded, title, thumbnail, thumbnail_type, photo, photo_type)
                values(?, ?, ?, ?, ?, ?, ?);
        EOD;
        $parameters = [
            $name,
            $uploaded,
            $title,
            $thumbnail,
            $thumbnail_type,
            $photo,
            $photo_type
        ];
        Database::insert($sql, 'sssssss', $parameters);
    }

    public static function getPhoto(int $pk_photo, bool $thumbnail): ?array
    {
        if ($thumbnail) $sql = 'select thumbnail as photo, thumbnail_type as type from tbl_photos where pk_photo = ?';
        else $sql = 'select photo, photo_type as type from tbl_photos where pk_photo = ?';
        $photos = Database::select($sql, 'i', [$pk_photo]);
        if (count($photos) == 1) return $photos[0];
        return null;
    }

    public static function getPhotos(): array
    {
        $sql = "select pk_photo, name, uploaded, title, thumbnail_type from tbl_photos order by uploaded desc";
        return Database::select($sql);
    }

}
