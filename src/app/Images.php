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

}
