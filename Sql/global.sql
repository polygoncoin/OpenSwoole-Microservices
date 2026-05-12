DROP TABLE IF EXISTS `global_counter`;
CREATE TABLE `global_counter` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) DEFAULT NULL,
    `groupsTable` VARCHAR(255) NOT NULL,
    `usersTable` VARCHAR(255) NOT NULL,
    `allowed_cidr` TEXT,
    `api_domain` VARCHAR(255) DEFAULT NULL,
    `open_api_domain` VARCHAR(255) DEFAULT NULL,
    `master_db_server_type` VARCHAR(255) NOT NULL,
    `master_db_server_hostname` VARCHAR(255) NOT NULL,
    `master_db_server_port` VARCHAR(255) NOT NULL,
    `master_db_server_username` VARCHAR(255) NOT NULL,
    `master_db_server_password` VARCHAR(255) NOT NULL,
    `master_db_server_db` VARCHAR(255) NOT NULL,
    `master_db_server_query_placeholder` VARCHAR(255) NOT NULL,
    `slave_db_server_type` VARCHAR(255) NOT NULL,
    `slave_db_server_hostname` VARCHAR(255) NOT NULL,
    `slave_db_server_port` VARCHAR(255) NOT NULL,
    `slave_db_server_username` VARCHAR(255) NOT NULL,
    `slave_db_server_password` VARCHAR(255) NOT NULL,
    `slave_db_server_db` VARCHAR(255) NOT NULL,
    `slave_db_server_query_placeholder` VARCHAR(255) NOT NULL,
    `master_cache_server_type` VARCHAR(255) NOT NULL,
    `master_cache_server_hostname` VARCHAR(255) NOT NULL,
    `master_cache_server_port` VARCHAR(255) NOT NULL,
    `master_cache_server_username` VARCHAR(255) NOT NULL,
    `master_cache_server_password` VARCHAR(255) NOT NULL,
    `master_cache_server_db` VARCHAR(255) NOT NULL,
    `master_cache_server_table` VARCHAR(255) NOT NULL,
    `slave_cache_server_type` VARCHAR(255) NOT NULL,
    `slave_cache_server_hostname` VARCHAR(255) NOT NULL,
    `slave_cache_server_port` VARCHAR(255) NOT NULL,
    `slave_cache_server_username` VARCHAR(255) NOT NULL,
    `slave_cache_server_password` VARCHAR(255) NOT NULL,
    `slave_cache_server_db` VARCHAR(255) NOT NULL,
    `slave_cache_server_table` VARCHAR(255) NOT NULL,
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

LOCK TABLES `customer` WRITE;
INSERT INTO `customer` VALUES
(1,'Customer 001','group','user','0.0.0.0/0','api.customer001.localhost','localhost','cDbServerType001','cDbServerHostname001','cDbServerPort001','cDbServerUsername001','cDbServerPassword001','cDbServerDatabase001','cDbServerQueryPlaceholder001','cDbServerType001','cDbServerHostname001','cDbServerPort001','cDbServerUsername001','cDbServerPassword001','cDbServerDatabase001','cDbServerQueryPlaceholder001','gCacheServerType','gCacheServerHostname','gCacheServerPort','gCacheServerUsername','gCacheServerPassword','gCacheServerDB','gCacheServerTable','gCacheServerType','gCacheServerHostname','gCacheServerPort','gCacheServerUsername','gCacheServerPassword','gCacheServerDB','gCacheServerTable',NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-29 16:00:41','Yes', 'No','No');
UNLOCK TABLES;

DROP TABLE IF EXISTS `request`;
CREATE TABLE `request` (
    `request_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_by` ENUM('Admin', 'Customer', 'WebsiteAdmin') NOT NULL,
    `customer_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `request_route` VARCHAR(250),
    `request_method` ENUM('GET', 'POST', 'PUT', 'PATCH', 'DELETE') NOT NULL,
    `request_payload_json` JSON NOT NULL,
    `request_datetime` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `request_ip` VARCHAR(25) NOT NULL,
    PRIMARY KEY (`request_id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `error_log`;
CREATE TABLE `error_log` (
    `error_log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` BIGINT UNSIGNED NOT NULL,
    `request_by` ENUM('Admin', 'Customer', 'WebsiteAdmin') NOT NULL,
    `customer_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `request_route` VARCHAR(250),
    `request_method` ENUM('GET', 'POST', 'PUT', 'PATCH', 'DELETE') NOT NULL,
    `request_config_json` JSON NOT NULL,
    `request_payload_json` JSON NOT NULL,
    `request_session_json` JSON NOT NULL,
    `request_exception_json` JSON NOT NULL,
    `request_datetime` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `request_ip` VARCHAR(25) NOT NULL,
    PRIMARY KEY (`error_log_id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `debug_log`;
CREATE TABLE `debug_log` (
    `debug_log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `debug_mode` VARCHAR(250),
    `request_id` BIGINT UNSIGNED NOT NULL,
    `request_by` ENUM('Admin', 'Customer', 'WebsiteAdmin') NOT NULL,
    `customer_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `request_route` VARCHAR(250),
    `request_method` ENUM('GET', 'POST', 'PUT', 'PATCH', 'DELETE') NOT NULL,
    `request_config_json` JSON NOT NULL,
    `request_payload_json` JSON NOT NULL,
    `request_session_json` JSON NOT NULL,
    `request_exception_json` JSON NOT NULL,
    `request_datetime` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `request_ip` VARCHAR(25) NOT NULL,
    PRIMARY KEY (`debug_log_id`)
) ENGINE = InnoDB;
