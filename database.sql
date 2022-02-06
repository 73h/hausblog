CREATE TABLE `tbl_articles`
(
    `pk_article` int(11)       NOT NULL AUTO_INCREMENT,
    `title`      varchar(1000) NOT NULL,
    `content`    mediumtext    NOT NULL,
    `created`    datetime      NOT NULL,
    `url`        varchar(1000) NOT NULL,
    `published`  tinyint(1)    NOT NULL DEFAULT 0,
    PRIMARY KEY (`pk_article`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8;

CREATE TABLE `tbl_images`
(
    `pk_image` int(11)      NOT NULL AUTO_INCREMENT,
    `name`     varchar(255) NOT NULL,
    `uploaded` datetime     NOT NULL,
    `title`    varchar(255) NOT NULL,
    `image`    blob         NOT NULL,
    `type`     varchar(10)  NOT NULL,
    `width`    int(11)      NOT NULL,
    `height`   int(11)      NOT NULL,
    PRIMARY KEY (`pk_image`)
) ENGINE = MyISAM
  AUTO_INCREMENT = 3
  DEFAULT CHARSET = utf8;

CREATE TABLE `tbl_articles_images`
(
    `pk_article_image` int(11) NOT NULL AUTO_INCREMENT,
    `fk_article`       int(11) NOT NULL,
    `fk_image`         int(11) NOT NULL,
    PRIMARY KEY (`pk_article_image`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8;

CREATE TABLE `tbl_logins`
(
    `pk_login` int(11)      NOT NULL AUTO_INCREMENT,
    `fk_user`  int(11)      NOT NULL,
    `created`  datetime     NOT NULL,
    `code`     varchar(256) NOT NULL,
    `used`     tinyint(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (`pk_login`)
) ENGINE = MyISAM
  AUTO_INCREMENT = 38
  DEFAULT CHARSET = utf8;

CREATE TABLE `tbl_users`
(
    `pk_user`           int(11)     NOT NULL AUTO_INCREMENT,
    `user`              varchar(20) NOT NULL,
    `email`             varchar(100) DEFAULT NULL,
    `telegram_id`       int(11)      DEFAULT NULL,
    `telegram_username` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`pk_user`)
) ENGINE = MyISAM
  AUTO_INCREMENT = 3
  DEFAULT CHARSET = utf8;
