DROP TABLE IF EXISTS `master_users`;

CREATE TABLE `master_users` (
  `id` BIGINT UNSIGNED NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `group_id` int NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `token_ts` int UNSIGNED DEFAULT 0,
  `rateLimitMaxRequests` int DEFAULT NULL,
  `rateLimitSecondsWindow` int DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` int DEFAULT NULL,
  `approved_on` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_approved` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_disabled` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_deleted` enum('Yes','No') NOT NULL DEFAULT 'No',
  UNIQUE INDEX users_id (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `address`;

CREATE TABLE `address` (
  `id` BIGINT UNSIGNED NOT NULL,
  `user_id` int NOT NULL DEFAULT 0,
  `address` varchar(255) NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` int DEFAULT NULL,
  `approved_on` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_approved` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_disabled` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_deleted` enum('Yes','No') NOT NULL DEFAULT 'No',
  UNIQUE INDEX address_id (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `category`;

CREATE TABLE `category` (
  `id` BIGINT UNSIGNED NOT NULL,
  `parent_id` int NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` int DEFAULT NULL,
  `approved_on` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_approved` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_disabled` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_deleted` enum('Yes','No') NOT NULL DEFAULT 'No',
  UNIQUE INDEX category_id (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `api_cache`;

CREATE TABLE `api_cache` (
    `key` CHAR(128) NOT NULL,
    `value` BLOB,
    UNIQUE INDEX api_cache_key (`key`)
) ENGINE=InnoDB;

LOCK TABLES `master_users` WRITE;
/*!40000 ALTER TABLE `master_users` DISABLE KEYS */;
INSERT INTO `master_users` VALUES
(4,'test1','test1','test1@test.com','client_1_group_1_user_1','$2y$10$o8hFTjBIXQS.fOED2Ut1ZOCSdDjTnS3lyELI4rWyFEnu4GUyJr3O6','127.0.0.1',2,'',0,NULL,NULL,NULL,0,'2023-02-22 04:12:50',NULL,NULL,0,'2023-04-20 16:53:57','Yes','No','No'),
(5,'admin1','admin1','admin1@test.com','client_1_admin_1','$2y$10$o8hFTjBIXQS.fOED2Ut1ZOCSdDjTnS3lyELI4rWyFEnu4GUyJr3O6','127.0.0.1',3,'',0,NULL,NULL,NULL,0,'2023-02-22 04:12:50',NULL,NULL,0,'2023-04-20 16:53:57','Yes','No','No');
/*!40000 ALTER TABLE `master_users` ENABLE KEYS */;
UNLOCK TABLES;
