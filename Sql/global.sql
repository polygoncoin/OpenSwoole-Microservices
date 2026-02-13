DROP TABLE IF EXISTS `global_counter`;

CREATE TABLE `global_counter` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `clients`;

CREATE TABLE `clients` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) DEFAULT NULL,
  `allowed_cidrs` text,
  `api_domain` varchar(255) DEFAULT NULL,
  `open_api_domain` varchar(255) DEFAULT NULL,
  `master_db_server_type` varchar(255) NOT NULL,
  `master_db_hostname` varchar(255) NOT NULL,
  `master_db_port` varchar(255) NOT NULL,
  `master_db_username` varchar(255) NOT NULL,
  `master_db_password` varchar(255) NOT NULL,
  `master_db_database` varchar(255) NOT NULL,
  `master_query_placeholder` varchar(255) NOT NULL,
  `slave_db_server_type` varchar(255) NOT NULL,
  `slave_db_hostname` varchar(255) NOT NULL,
  `slave_db_port` varchar(255) NOT NULL,
  `slave_db_username` varchar(255) NOT NULL,
  `slave_db_password` varchar(255) NOT NULL,
  `slave_db_database` varchar(255) NOT NULL,
  `slave_query_placeholder` varchar(255) NOT NULL,
  `usersTable` varchar(255) NOT NULL,
  `master_cache_server_type` varchar(255) NOT NULL,
  `master_cache_hostname` varchar(255) NOT NULL,
  `master_cache_port` varchar(255) NOT NULL,
  `master_cache_username` varchar(255) NOT NULL,
  `master_cache_password` varchar(255) NOT NULL,
  `master_cache_database` varchar(255) NOT NULL,
  `master_cache_table` varchar(255) NOT NULL,
  `slave_cache_server_type` varchar(255) NOT NULL,
  `slave_cache_hostname` varchar(255) NOT NULL,
  `slave_cache_port` varchar(255) NOT NULL,
  `slave_cache_username` varchar(255) NOT NULL,
  `slave_cache_password` varchar(255) NOT NULL,
  `slave_cache_database` varchar(255) NOT NULL,
  `slave_cache_table` varchar(255) NOT NULL,
  `rateLimitMaxRequests` int DEFAULT NULL,
  `rateLimitMaxRequestsWindow` int DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` int DEFAULT NULL,
  `approved_on` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_approved` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_disabled` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_deleted` enum('Yes','No') NOT NULL DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `groups`;

CREATE TABLE `groups` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `client_id` int DEFAULT NULL,
  `allowed_cidrs` text,
  `rateLimitMaxRequests` int DEFAULT NULL,
  `rateLimitMaxRequestsWindow` int DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` int DEFAULT NULL,
  `approved_on` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_approved` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_disabled` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_deleted` enum('Yes','No') NOT NULL DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES
(1,'Client 001','0.0.0.0/0','localhost', 'public.localhost','cDbServerType001','cDbServerHostname001','cDbServerPort001','cDbServerUsername001','cDbServerPassword001','cDbServerDatabase001','cDbServerQueryPlaceholder001','cDbServerType001','cDbServerHostname001','cDbServerPort001','cDbServerUsername001','cDbServerPassword001','cDbServerDatabase001','cDbServerQueryPlaceholder001','clientUsersTable','gCacheServerType','gCacheServerHostname','gCacheServerPort','gCacheServerUsername','gCacheServerPassword','gCacheServerDatabase','gCacheServerTable','gCacheServerType','gCacheServerHostname','gCacheServerPort','gCacheServerUsername','gCacheServerPassword','gCacheServerDatabase','gCacheServerTable',NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-29 16:00:41','Yes','No','No');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
INSERT INTO `groups` VALUES
(2,'Client001UserGroup1',1,'0.0.0.0/0',NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes','No','No'),
(3,'AdminGroup',1,'0.0.0.0/0',NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes','No','No');
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;
