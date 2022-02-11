<?php

namespace src\app;

class Images
{

    function insertImage(string $name, string $uploaded, string $title, string $image, string $type, int $width, int $height)
    {
        $sql = <<<EOD
            insert into tbl_images
                (name, uploaded, title, image, type, width, height)
                values(?, ?, ?, ?, ?, ?, ?);
        EOD;
        $parameters = [
            $name,
            $uploaded,
            $title,
            $image,
            $type,
            $width,
            $height
        ];
        Database::insert($sql, 'sssssii', $parameters);
    }

    public static function getImage(int $pk_image): ?array
    {
        $sql = <<<EOD
            select type, image, width, height
            from tbl_images
            where pk_image = ?
        EOD;
        $images = Database::select($sql, 'i', [$pk_image]);
        if (count($images) == 1) return $images[0];
        return null;
    }

}
