-- Table: users
CREATE TABLE `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(255) NOT NULL,
    `avatar_url` TEXT DEFAULT NULL,
    `anime_count` INT(11) DEFAULT 0,
    `anime_episodes` INT(11) DEFAULT 0,
    `manga_count` INT(11) DEFAULT 0,
    `manga_chapters` INT(11) DEFAULT 0,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `image` TEXT DEFAULT NULL,
    `custom_avatar` INT(11) DEFAULT NULL,
    PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: comments
CREATE TABLE `comments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `anime_id` VARCHAR(255) NOT NULL,
    `episode_id` INT(11) NOT NULL,
    `content` TEXT NOT NULL,
    `parent_id` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    `username` VARCHAR(255) DEFAULT NULL,
    `user_avatar` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(`id`),
    KEY `idx_comments_anime_episode`(`anime_id`, `episode_id`),
    KEY `idx_comments_user`(`user_id`),
    KEY `idx_comments_parent`(`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: comment_reactions
CREATE TABLE `comment_reactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `comment_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `type` TINYINT(1) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY(`id`),
    UNIQUE KEY `unique_reaction`(`comment_id`, `user_id`),
    KEY `idx_reactions_comment`(`comment_id`),
    KEY `idx_reactions_user`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: watched_episode
CREATE TABLE `watched_episode` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `anime_id` VARCHAR(255) NOT NULL,
    `anilist_id` INT(11) DEFAULT NULL,
    `episodes_watched` TEXT NOT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    PRIMARY KEY(`id`),
    UNIQUE KEY `user_id`(`user_id`, `anime_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: watchlist
CREATE TABLE `watchlist` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `anime_id` VARCHAR(255) DEFAULT NULL,
    `anilist_id` INT(50) DEFAULT NULL,
    `anime_name` VARCHAR(255) NOT NULL,
    `type` INT(11) NOT NULL COMMENT '1=Watching, 2=On-Hold, 3=Plan to Watch, 4=Dropped, 5=Completed',
    `poster` VARCHAR(255) DEFAULT NULL,
    `sub_count` INT(11) DEFAULT NULL,
    `dub_count` INT(11) DEFAULT NULL,
    `anime_type` VARCHAR(255) DEFAULT NULL,
    `duration` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    PRIMARY KEY(`id`),
    UNIQUE KEY `unique_user_anime`(`user_id`, `anime_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: watch_history
CREATE TABLE `watch_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `anime_id` VARCHAR(255) NOT NULL,
    `anime_name` VARCHAR(255) NOT NULL,
    `poster` VARCHAR(255) DEFAULT NULL,
    `sub_count` INT(11) DEFAULT NULL,
    `dub_count` INT(11) DEFAULT NULL,
    `anilist_id` VARCHAR(50) DEFAULT NULL,
    `episode_number` INT(11) NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `watched_episodes` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
    PRIMARY KEY(`id`),
    UNIQUE INDEX `user_anime_unique` (`user_id`, `anime_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: pageview
CREATE TABLE `pageview` (
    `id` INT(255) NOT NULL AUTO_INCREMENT,
    `pageID` VARCHAR(250) NOT NULL,
    `totalview` BIGINT(20) NOT NULL,
    `like_count` INT(11) NOT NULL,
    `dislike_count` INT(11) NOT NULL,
    `animeID` TEXT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci AUTO_INCREMENT=16141;

-- Foreign keys
ALTER TABLE `comments`
    ADD CONSTRAINT `fk_comments_parent` FOREIGN KEY(`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_comments_user` FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

ALTER TABLE `comment_reactions`
    ADD CONSTRAINT `comment_reactions_ibfk_1` FOREIGN KEY(`comment_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `comment_reactions_ibfk_2` FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

ALTER TABLE `comments`
ADD `is_spoiler` TINYINT(1) DEFAULT 0 AFTER `content`,
ADD INDEX `idx_comments_created_at` (`created_at`);