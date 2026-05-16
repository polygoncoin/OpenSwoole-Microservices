DROP TABLE IF EXISTS `group`;
CREATE TABLE `group` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `customer_id` INT DEFAULT NULL,
    `allowed_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0',
    `rateLimitMaxRequest` INT DEFAULT NULL,
    `rateLimitMaxRequestWindow` INT DEFAULT NULL,
    `comments` VARCHAR(255) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `approved_by` INT DEFAULT NULL,
    `approved_on` DATETIME NULL DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,
    `updated_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `group_id` INT NOT NULL,
    `firstname` VARCHAR(255) NOT NULL,
    `lastname` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `username` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `allowed_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0',
    `token` VARCHAR(255) DEFAULT NULL,
    `token_ts` INT UNSIGNED DEFAULT 0,
    `rateLimitMaxRequest` INT DEFAULT NULL,
    `rateLimitMaxRequestWindow` INT DEFAULT NULL,
    `comments` VARCHAR(255) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `approved_by` INT DEFAULT NULL,
    `approved_on` DATETIME NULL DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,
    `updated_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `address`;
CREATE TABLE `address` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `user_id` INT NOT NULL DEFAULT 0,
    `address` VARCHAR(255) NOT NULL,
    `created_by` INT DEFAULT NULL,
    `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `approved_by` INT DEFAULT NULL,
    `approved_on` DATETIME NULL DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,
    `updated_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `category`;
CREATE TABLE `category` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` INT NOT NULL DEFAULT 0,
    `name` VARCHAR(255) NOT NULL,
    `created_by` INT DEFAULT NULL,
    `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `approved_by` INT DEFAULT NULL,
    `approved_on` DATETIME NULL DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,
    `updated_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `api_cache`;
CREATE TABLE `api_cache` (
    `key` CHAR(128) NOT NULL,
    `value` BLOB,
    UNIQUE INDEX api_cache_key (`key`)
) ENGINE = InnoDB;

LOCK TABLES `group` WRITE;
INSERT INTO `group` VALUES
(2,'Customer001UserGroup1',1,'0.0.0.0/0',NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes', 'No','No'),
(3,'AdminGroup',1,'0.0.0.0/0',NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes', 'No','No');
UNLOCK TABLES;

LOCK TABLES `user` WRITE;
INSERT INTO `user` VALUES
(4,1,2,'test1','test1','test1@test.com','customer_1_group_1_user_1','$2y$10$o8hFTjBIXQS.fOED2Ut1ZOCSdDjTnS3lyELI4rWyFEnu4GUyJr3O6','0.0.0.0/0','',0,NULL,NULL,NULL,0,'2023-02-22 04:12:50',NULL,NULL,0,'2023-04-20 16:53:57','Yes', 'No','No'),
(5,1,3,'admin1','admin1','admin1@test.com','customer_1_admin_1','$2y$10$o8hFTjBIXQS.fOED2Ut1ZOCSdDjTnS3lyELI4rWyFEnu4GUyJr3O6','0.0.0.0/0','',0,NULL,NULL,NULL,0,'2023-02-22 04:12:50',NULL,NULL,0,'2023-04-20 16:53:57','Yes', 'No','No');
UNLOCK TABLES;
