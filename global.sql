DROP TABLE IF EXISTS `m001_master_clients`;

CREATE TABLE `m001_master_clients` (
  `client_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `api_domain` varchar(255) DEFAULT NULL,
  `open_api_domain` varchar(255) DEFAULT NULL,
  `master_db_server_type` varchar(255) NOT NULL,
  `master_db_hostname` varchar(255) NOT NULL,
  `master_db_port` varchar(255) NOT NULL,
  `master_db_username` varchar(255) NOT NULL,
  `master_db_password` varchar(255) NOT NULL,
  `master_db_database` varchar(255) NOT NULL,
  `slave_db_server_type` varchar(255) NOT NULL,
  `slave_db_hostname` varchar(255) NOT NULL,
  `slave_db_port` varchar(255) NOT NULL,
  `slave_db_username` varchar(255) NOT NULL,
  `slave_db_password` varchar(255) NOT NULL,
  `slave_db_database` varchar(255) NOT NULL,
  `master_cache_server_type` varchar(255) NOT NULL,
  `master_cache_hostname` varchar(255) NOT NULL,
  `master_cache_port` varchar(255) NOT NULL,
  `master_cache_username` varchar(255) NOT NULL,
  `master_cache_password` varchar(255) NOT NULL,
  `master_cache_database` varchar(255) NOT NULL,
  `slave_cache_server_type` varchar(255) NOT NULL,
  `slave_cache_hostname` varchar(255) NOT NULL,
  `slave_cache_port` varchar(255) NOT NULL,
  `slave_cache_username` varchar(255) NOT NULL,
  `slave_cache_password` varchar(255) NOT NULL,
  `slave_cache_database` varchar(255) NOT NULL,
  `rateLimiterMaxRequests` int DEFAULT NULL,
  `rateLimiterSecondsWindow` int DEFAULT NULL,
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
  PRIMARY KEY (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `m002_master_groups`;

CREATE TABLE `m002_master_groups` (
  `group_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `client_id` int DEFAULT NULL,
  `allowed_ips` text,
  `rateLimiterMaxRequests` int DEFAULT NULL,
  `rateLimiterSecondsWindow` int DEFAULT NULL,
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
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

LOCK TABLES `m001_master_clients` WRITE;
/*!40000 ALTER TABLE `m001_master_clients` DISABLE KEYS */;
INSERT INTO `m001_master_clients` VALUES
(1,'Client 001','localhost','public.localhost','dbTypeClient001','dbHostnameClient001','dbPortClient001','dbUsernameClient001','dbPasswordClient001','dbDatabaseClient001','dbTypeClient001','dbHostnameClient001','dbPortClient001','dbUsernameClient001','dbPasswordClient001','dbDatabaseClient001','cacheType','cacheHostname','cachePort','cacheUsername','cachePassword','cacheDatabase','cacheType','cacheHostname','cachePort','cacheUsername','cachePassword','cacheDatabase',NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-29 16:00:41','Yes','No','No');
/*!40000 ALTER TABLE `m001_master_clients` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `m002_master_groups` WRITE;
/*!40000 ALTER TABLE `m002_master_groups` DISABLE KEYS */;
INSERT INTO `m002_master_groups` VALUES
(1,'Client001UserGroup1',1,'127.0.0.1, 127.0.0.1/32',NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes','No','No'),
(2,'AdminGroup',1,'127.0.0.1, 127.0.0.1/32',NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes','No','No');
/*!40000 ALTER TABLE `m002_master_groups` ENABLE KEYS */;
UNLOCK TABLES;
