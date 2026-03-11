DROP TABLE IF EXISTS `global_counter`;
CREATE TABLE `global_counter` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `client`;
CREATE TABLE `client` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) DEFAULT NULL,
    `allowed_cidr` TEXT,
    `api_domain` VARCHAR(255) DEFAULT NULL,
    `open_api_domain` VARCHAR(255) DEFAULT NULL,
    `master_db_server_type` VARCHAR(255) NOT NULL,
    `master_db_hostname` VARCHAR(255) NOT NULL,
    `master_db_port` VARCHAR(255) NOT NULL,
    `master_db_username` VARCHAR(255) NOT NULL,
    `master_db_password` VARCHAR(255) NOT NULL,
    `master_db_database` VARCHAR(255) NOT NULL,
    `master_query_placeholder` VARCHAR(255) NOT NULL,
    `slave_db_server_type` VARCHAR(255) NOT NULL,
    `slave_db_hostname` VARCHAR(255) NOT NULL,
    `slave_db_port` VARCHAR(255) NOT NULL,
    `slave_db_username` VARCHAR(255) NOT NULL,
    `slave_db_password` VARCHAR(255) NOT NULL,
    `slave_db_database` VARCHAR(255) NOT NULL,
    `slave_query_placeholder` VARCHAR(255) NOT NULL,
    `usersTable` VARCHAR(255) NOT NULL,
    `master_cache_server_type` VARCHAR(255) NOT NULL,
    `master_cache_hostname` VARCHAR(255) NOT NULL,
    `master_cache_port` VARCHAR(255) NOT NULL,
    `master_cache_username` VARCHAR(255) NOT NULL,
    `master_cache_password` VARCHAR(255) NOT NULL,
    `master_cache_database` VARCHAR(255) NOT NULL,
    `master_cache_table` VARCHAR(255) NOT NULL,
    `slave_cache_server_type` VARCHAR(255) NOT NULL,
    `slave_cache_hostname` VARCHAR(255) NOT NULL,
    `slave_cache_port` VARCHAR(255) NOT NULL,
    `slave_cache_username` VARCHAR(255) NOT NULL,
    `slave_cache_password` VARCHAR(255) NOT NULL,
    `slave_cache_database` VARCHAR(255) NOT NULL,
    `slave_cache_table` VARCHAR(255) NOT NULL,
    `rateLimitMaxRequest` INT DEFAULT NULL,
    `rateLimitMaxRequestWindow` INT DEFAULT NULL,
    `comments` VARCHAR(255) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `approved_by` INT DEFAULT NULL,
    `approved_on` TIMESTAMP NULL DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,
    `updated_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `group`;
CREATE TABLE `group` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `client_id` INT DEFAULT NULL,
    `allowed_cidr` TEXT,
    `rateLimitMaxRequest` INT DEFAULT NULL,
    `rateLimitMaxRequestWindow` INT DEFAULT NULL,
    `comments` VARCHAR(255) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `approved_by` INT DEFAULT NULL,
    `approved_on` TIMESTAMP NULL DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,
    `updated_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

LOCK TABLES `client` WRITE;
INSERT INTO `client` VALUES
(1,'Client 001','0.0.0.0/0','api.client001.localhost','localhost','cDbServerType001','cDbServerHostname001','cDbServerPort001','cDbServerUsername001','cDbServerPassword001','cDbServerDatabase001','cDbServerQueryPlaceholder001','cDbServerType001','cDbServerHostname001','cDbServerPort001','cDbServerUsername001','cDbServerPassword001','cDbServerDatabase001','cDbServerQueryPlaceholder001','clientUsersTable','gCacheServerType','gCacheServerHostname','gCacheServerPort','gCacheServerUsername','gCacheServerPassword','gCacheServerDatabase','gCacheServerTable','gCacheServerType','gCacheServerHostname','gCacheServerPort','gCacheServerUsername','gCacheServerPassword','gCacheServerDatabase','gCacheServerTable',NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-29 16:00:41','Yes', 'No','No');
UNLOCK TABLES;

LOCK TABLES `group` WRITE;
INSERT INTO `group` VALUES
(2,'Client001UserGroup1',1,'0.0.0.0/0',NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes', 'No','No'),
(3,'AdminGroup',1,'0.0.0.0/0',NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes', 'No','No');
UNLOCK TABLES;
