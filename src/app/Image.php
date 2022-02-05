<?php

namespace src\app;

class Image
{

    private ?int $pk_image = null;
    public ?string $name = '';
    public ?string $uploaded = '';
    public ?string $title = '';
    public ?string $image = '';
    public ?string $type = '';
    public ?int $width = 0;
    public ?int $height = 0;

    function __construct()
    {

    }

    function create(string $name, string $uploaded, string $title, string $image, string $type, int $width, int $height)
    {
        $this->name = $name;
        $this->uploaded = $uploaded;
        $this->title = $title;
        $this->image = $image;
        $this->type = $type;
        $this->width = $width;
        $this->height = $height;
        $sql = <<<EOD
            insert into `tbl_images`
                (`name`, `uploaded`, `title`, `image`, `type`, `width`, `height`)
                values(?, ?, ?, ?, ?, ?, ?);
        EOD;
        $parameters = [
            $this->name,
            $this->uploaded,
            $this->title,
            $this->image,
            $this->type,
            $this->width,
            $this->height
        ];
        Database::insert($sql, 'sssssii', $parameters);
        Database::insert($sql, 'sssssii', $parameters);
        Database::insert($sql, 'sssssii', $parameters);
    }

}
