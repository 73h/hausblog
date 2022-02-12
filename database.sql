create table tbl_articles
(
    pk_article int auto_increment
        primary key,
    title      varchar(1000)        not null,
    content    mediumtext           not null,
    created    datetime             not null,
    url        varchar(1000)        not null,
    published  tinyint(1) default 0 not null
);

create table tbl_articles_photos
(
    pk_article_photo int not null
        primary key,
    fk_article       int not null,
    fk_photo         int not null
);

create table tbl_logins
(
    pk_login int auto_increment
        primary key,
    fk_user  int                  not null,
    created  datetime             not null,
    code     varchar(256)         not null,
    used     tinyint(1) default 0 not null
);

create table tbl_photos
(
    pk_photo       int auto_increment
        primary key,
    name           varchar(255) not null,
    uploaded       datetime     not null,
    title          varchar(255) not null,
    thumbnail      mediumblob   not null,
    thumbnail_type varchar(10)  not null,
    photo          mediumblob   not null,
    photo_type     varchar(10)  not null
);

create table tbl_users
(
    pk_user           int auto_increment
        primary key,
    user              varchar(20)  not null,
    email             varchar(100) null,
    telegram_id       int          null,
    telegram_username varchar(255) null
);

