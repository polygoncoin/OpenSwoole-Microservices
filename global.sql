DROP TABLE IF EXISTS `m001_master_group`;

CREATE TABLE `m001_master_group` (
  `group_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `client_id` int DEFAULT NULL,
  `connection_id` int NOT NULL,
  `allowed_ips` text,
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

DROP TABLE IF EXISTS `m002_master_user`;

CREATE TABLE `m002_master_user` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `group_id` int NOT NULL,
  `token` varchar(255) NOT NULL,
  `token_ts` int UNSIGNED DEFAULT 0,
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
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `m003_master_connection`;

CREATE TABLE `m003_master_connection` (
  `connection_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255),
  `write_db_server_type` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `write_db_hostname` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `write_db_port` int NOT NULL,
  `write_db_username` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `write_db_password` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `write_db_database` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `read_db_server_type` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `read_db_hostname` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `read_db_port` int NOT NULL,
  `read_db_username` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `read_db_password` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `read_db_database` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
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
  PRIMARY KEY (`connection_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `m004_master_client`;

CREATE TABLE `m004_master_client` (
  `client_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
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

LOCK TABLES `m001_master_group` WRITE;
/*!40000 ALTER TABLE `m001_master_group` DISABLE KEYS */;
INSERT INTO `m001_master_group` VALUES
(1,'AdminGroup',1,1,'127.0.0.1, 127.0.0.1/32','',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes','No','No'),
(2,'Client001UserGroup1',2,2,'127.0.0.1, 127.0.0.1/32','',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes','No','No'),
(3,'Client002UserGroup1',3,3,'127.0.0.1, 127.0.0.1/32','',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes','No','No');
/*!40000 ALTER TABLE `m001_master_group` ENABLE KEYS */;
UNLOCK TABLES;


LOCK TABLES `m002_master_user` WRITE;
/*!40000 ALTER TABLE `m002_master_user` DISABLE KEYS */;
INSERT INTO `m002_master_user` VALUES
(1,'admin','$2y$10$o8hFTjBIXQS.fOED2Ut1ZOCSdDjTnS3lyELI4rWyFEnu4GUyJr3O6',1,'',0,NULL,0,'2023-02-22 04:12:50',NULL,NULL,0,'2023-04-20 16:53:57','Yes','No','No'),
(2,'client_1_user_1','$2y$10$o8hFTjBIXQS.fOED2Ut1ZOCSdDjTnS3lyELI4rWyFEnu4GUyJr3O6',2,'',0,NULL,0,'2023-02-22 04:12:50',NULL,NULL,0,'2023-04-20 16:53:57','Yes','No','No'),
(3,'client_2_user_1','$2y$10$o8hFTjBIXQS.fOED2Ut1ZOCSdDjTnS3lyELI4rWyFEnu4GUyJr3O6',3,'',0,NULL,0,'2023-02-22 04:12:50',NULL,NULL,0,'2023-04-20 16:53:57','Yes','No','No');
/*!40000 ALTER TABLE `m002_master_user` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `m003_master_connection` WRITE;
/*!40000 ALTER TABLE `m003_master_connection` DISABLE KEYS */;
INSERT INTO `m003_master_connection` VALUES
(1,'admin','defaultDbType','defaultDbHostname','defaultDbPort','defaultDbUsername','defaultDbPassword','defaultDbDatabase','defaultDbType','defaultDbHostname','defaultDbPort','defaultDbUsername','defaultDbPassword','defaultDbDatabase','',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-05-02 14:48:16','Yes','No','No'),
(2,'clientOneConnectionName','defaultDbType','defaultDbHostname','defaultDbPort','defaultDbUsername','defaultDbPassword','dbDatabaseClient001','defaultDbType','defaultDbHostname','defaultDbPort','defaultDbUsername','defaultDbPassword','dbDatabaseClient001','',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-05-02 14:50:41','Yes','No','No'),
(3,'clientTwoConnectionName','defaultDbType','dbHostnameClient002','defaultDbPort','dbUsernameClient002','dbPasswordClient002','dbDatabaseClient002','defaultDbType','dbHostnameClient002','defaultDbPort','dbUsernameClient002','dbPasswordClient002','dbDatabaseClient002','',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-05-02 14:50:41','Yes','No','No');
/*!40000 ALTER TABLE `m003_master_connection` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `m004_master_client` WRITE;
/*!40000 ALTER TABLE `m004_master_client` DISABLE KEYS */;
INSERT INTO `m004_master_client` VALUES
(1,'Admins','',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-29 16:00:41','Yes','No','No'),
(2,'Client 001','',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-29 16:00:41','Yes','No','No'),
(3,'Client 002','',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-29 16:00:41','Yes','No','No');
/*!40000 ALTER TABLE `m004_master_client` ENABLE KEYS */;
UNLOCK TABLES;
