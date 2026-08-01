-- ----------- Tables Product level --------------
DROP TABLE IF EXISTS `global_counter`;
CREATE TABLE `global_counter` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;
-- ----------- Tables Product level --------------

-- ----------- Tables for logging --------------
DROP TABLE IF EXISTS `request`;
CREATE TABLE `request` (
    `request_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `customer_user_group_id` INT NOT NULL,
    `customer_user_id` INT NOT NULL,
    `request_route` VARCHAR(250),
    `request_method` ENUM('GET', 'POST', 'PUT', 'PATCH', 'DELETE') NOT NULL,
    `request_payload_json` JSON NOT NULL,
    `request_datetime` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `request_ip` VARCHAR(25) NOT NULL,
    PRIMARY KEY (`request_id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `error_log`;
CREATE TABLE `error_log` (
    `error_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` BIGINT UNSIGNED NOT NULL,
    `customer_id` INT NOT NULL,
    `customer_user_group_id` INT NOT NULL,
    `customer_user_id` INT NOT NULL,
    `request_route` VARCHAR(250),
    `request_method` ENUM('GET', 'POST', 'PUT', 'PATCH', 'DELETE') NOT NULL,
    `request_config_json` JSON NOT NULL,
    `request_payload_json` JSON NOT NULL,
    `request_session_json` JSON NOT NULL,
    `request_exception_json` JSON NOT NULL,
    `request_datetime` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `request_ip` VARCHAR(25) NOT NULL,
    PRIMARY KEY (`error_id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `debug_log`;
CREATE TABLE `debug_log` (
    `debug_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` BIGINT UNSIGNED NOT NULL,
    `debug_mode` VARCHAR(250),
    `customer_id` INT NOT NULL,
    `customer_user_group_id` INT NOT NULL,
    `customer_user_id` INT NOT NULL,
    `request_route` VARCHAR(250),
    `request_method` ENUM('GET', 'POST', 'PUT', 'PATCH', 'DELETE') NOT NULL,
    `request_config_json` JSON NOT NULL,
    `request_payload_json` JSON NOT NULL,
    `request_session_json` JSON NOT NULL,
    `request_debug_json` JSON NOT NULL,
    `request_datetime` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `request_ip` VARCHAR(25) NOT NULL,
    PRIMARY KEY (`debug_id`)
) ENGINE = InnoDB;
-- ----------- Tables for logging --------------

-- ----------- Tables Super Admin level --------------
DROP TABLE IF EXISTS `super_admin`;
CREATE TABLE `super_admin` (
    `super_admin_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `super_admin_allowed_cidr` VARCHAR(250) DEFAULT NULL,
    `super_admin_rate_limit_max_request` INT DEFAULT NULL,
    `super_admin_rate_limit_max_request_window` INT DEFAULT NULL,
    `super_admin_username` VARCHAR(100) NOT NULL,
    `super_admin_password_hash` VARCHAR(150) NOT NULL,
    `super_admin_user_token` VARCHAR(100) NULL DEFAULT NULL,
    `super_admin_user_token_ts` DATETIME NULL DEFAULT NULL,
    `super_admin_general_information` VARCHAR(150) NULL DEFAULT NULL,
    `super_admin_created_by` INT DEFAULT NULL,
    `super_admin_created_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `super_admin_approved_by` INT DEFAULT NULL,
    `super_admin_approved_on` TIMESTAMP NULL DEFAULT NULL,
    `super_admin_updated_by` INT DEFAULT NULL,
    `super_admin_updated_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `super_admin_is_editable` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `super_admin_is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `super_admin_is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `super_admin_is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`super_admin_id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `super_admin_contact`;
CREATE TABLE `super_admin_contact` (
    `super_admin_contact_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `super_admin_id` INT NOT NULL,
    `super_admin_contact_name` VARCHAR(100) NOT NULL,
    `super_admin_contact_person` VARCHAR(100) NOT NULL,
    `super_admin_contact_firm` VARCHAR(100) NOT NULL,
    `super_admin_contact_department` VARCHAR(100) NOT NULL,
    `super_admin_contact_email_address` VARCHAR(100) NOT NULL,
    `super_admin_contact_phone` VARCHAR(100) NOT NULL,
    `super_admin_contact_fax` VARCHAR(100) NOT NULL,
    `super_admin_contact_mailing_address` VARCHAR(100) NOT NULL,
    `super_admin_contact_city` VARCHAR(100) NOT NULL,
    `super_admin_contact_state` VARCHAR(100) NOT NULL,
    `super_admin_contact_zip` VARCHAR(100) NOT NULL,
    `super_admin_contact_country` VARCHAR(100) NOT NULL,
    `super_admin_contact_general_information` VARCHAR(150) NULL DEFAULT NULL,
    `super_admin_contact_created_by` INT DEFAULT NULL,
    `super_admin_contact_created_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `super_admin_contact_approved_by` INT DEFAULT NULL,
    `super_admin_contact_approved_on` TIMESTAMP NULL DEFAULT NULL,
    `super_admin_contact_updated_by` INT DEFAULT NULL,
    `super_admin_contact_updated_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `super_admin_contact_is_editable` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `super_admin_contact_is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `super_admin_contact_is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `super_admin_contact_is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`super_admin_contact_id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `super_admin_group`;
CREATE TABLE `super_admin_group` (
    `super_admin_group_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `super_admin_group_allowed_cidr` VARCHAR(250) DEFAULT NULL,
    `super_admin_group_rate_limit_max_request` INT DEFAULT NULL,
    `super_admin_group_rate_limit_max_request_window` INT DEFAULT NULL,
    `super_admin_group_name` VARCHAR(100) NOT NULL,
    `super_admin_group_general_information` VARCHAR(250) DEFAULT NULL,
    `super_admin_group_created_by` INT DEFAULT NULL,
    `super_admin_group_created_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `super_admin_group_approved_by` INT DEFAULT NULL,
    `super_admin_group_approved_on` TIMESTAMP NULL DEFAULT NULL,
    `super_admin_group_updated_by` INT DEFAULT NULL,
    `super_admin_group_updated_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `super_admin_group_is_editable` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `super_admin_group_is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `super_admin_group_is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `super_admin_group_is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`super_admin_group_id`)
) ENGINE = InnoDB;
-- ----------- Tables Super Admin level --------------

-- ----------- Tables Customer Level --------------
DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
    `customer_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_name` VARCHAR(255) DEFAULT NULL,
    `customer_user_group_table` VARCHAR(255) NOT NULL,
    `customer_user_table` VARCHAR(255) NOT NULL,
    `customer_allowed_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_rate_limit_max_request` INT DEFAULT NULL,
    `customer_rate_limit_max_request_window` INT DEFAULT NULL,
    `customer_private_token_domain` VARCHAR(255) DEFAULT NULL,
    `customer_private_session_domain` VARCHAR(255) DEFAULT NULL,
    `customer_public_domain` VARCHAR(255) DEFAULT NULL,
    `customer_enabled_cidr_check` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_concurrent_login` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_cron_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_custom_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_dropbox_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_download_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_explain_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_import_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_import_sample_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_input_representation_in_query_string` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_output_representation_in_query_string` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_payload_in_response` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_private_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_public_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_rate_limiting` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_rate_limiting_for_customer` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_rate_limiting_for_customer_user_group` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_rate_limiting_for_ip` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_rate_limiting_for_route` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_rate_limiting_for_user` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_rate_limiting_for_user_per_ip` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_rate_limiting_for_user_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_response_caching` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_routes_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_thirdparty_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_upload_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_query_cache_for_public_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_enabled_query_cache_for_private_request` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_cron_request_restricted_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_custom_request_restricted_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_dropbox_request_restricted_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_explain_request_restricted_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_export_request_restricted_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_import_sample_request_restricted_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_import_request_restricted_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_routes_request_restricted_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_thirdparty_request_restricted_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_upload_request_restricted_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_rate_limit_ip_max_request` INT DEFAULT NULL, -- ; Max request allowed per IP
    `customer_rate_limit_ip_max_request_window` INT DEFAULT NULL, -- ; Window for Max request allowed per IP
    `customer_rate_limit_max_user_per_ip` INT DEFAULT NULL, -- ; Max User allowed per IP
    `customer_rate_limit_max_user_per_ip_window` INT DEFAULT NULL, -- ; Window for Max User allowed per IP
    `customer_rate_limit_user_max_request` INT DEFAULT NULL, -- ; Max request allowed for user
    `customer_rate_limit_user_max_request_window` INT DEFAULT NULL, -- ; Window for Max request allowed for user
    `customer_rate_limit_max_user_login_request` INT DEFAULT NULL, -- ; Max User Login request
    `customer_rate_limit_max_user_login_request_window` INT DEFAULT NULL, -- ; Window for Max User Login request
    `customer_master_db_server_type` VARCHAR(255) NOT NULL,
    `customer_master_db_server_hostname` VARCHAR(255) NOT NULL,
    `customer_master_db_server_port` VARCHAR(255) NOT NULL,
    `customer_master_db_server_username` VARCHAR(255) NOT NULL,
    `customer_master_db_server_password` VARCHAR(255) NOT NULL,
    `customer_master_db_server_db` VARCHAR(255) NOT NULL,
    `customer_master_db_server_query_placeholder` VARCHAR(255) NOT NULL,
    `customer_slave_db_server_type` VARCHAR(255) NOT NULL,
    `customer_slave_db_server_hostname` VARCHAR(255) NOT NULL,
    `customer_slave_db_server_port` VARCHAR(255) NOT NULL,
    `customer_slave_db_server_username` VARCHAR(255) NOT NULL,
    `customer_slave_db_server_password` VARCHAR(255) NOT NULL,
    `customer_slave_db_server_db` VARCHAR(255) NOT NULL,
    `customer_slave_db_server_query_placeholder` VARCHAR(255) NOT NULL,
    `customer_cache_server_type` VARCHAR(255) NOT NULL,
    `customer_cache_server_hostname` VARCHAR(255) NOT NULL,
    `customer_cache_server_port` VARCHAR(255) NOT NULL,
    `customer_cache_server_username` VARCHAR(255) NOT NULL,
    `customer_cache_server_password` VARCHAR(255) NOT NULL,
    `customer_cache_server_db` VARCHAR(255) NOT NULL,
    `customer_cache_server_table` VARCHAR(255) NOT NULL,
    `customer_session_server_type` VARCHAR(255) DEFAULT NULL,
    `customer_session_server_hostname` VARCHAR(255) DEFAULT NULL,
    `customer_session_server_port` VARCHAR(255) DEFAULT NULL,
    `customer_session_server_username` VARCHAR(255) DEFAULT NULL,
    `customer_session_server_password` VARCHAR(255) DEFAULT NULL,
    `customer_session_server_db` VARCHAR(255) DEFAULT NULL,
    `customer_session_server_table` VARCHAR(255) DEFAULT NULL,
    `customer_query_cache_server_type` VARCHAR(255) DEFAULT NULL,
    `customer_query_cache_server_hostname` VARCHAR(255) DEFAULT NULL,
    `customer_query_cache_server_port` VARCHAR(255) DEFAULT NULL,
    `customer_query_cache_server_username` VARCHAR(255) DEFAULT NULL,
    `customer_query_cache_server_password` VARCHAR(255) DEFAULT NULL,
    `customer_query_cache_server_db` VARCHAR(255) DEFAULT NULL,
    `customer_query_cache_server_collection` VARCHAR(255) DEFAULT NULL, -- ; For MongoDb
    `customer_comments` VARCHAR(255) DEFAULT NULL,
    `customer_created_by` INT DEFAULT NULL,
    `customer_created_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `customer_approved_by` INT DEFAULT NULL,
    `customer_approved_on` TIMESTAMP NULL DEFAULT NULL,
    `customer_updated_by` INT DEFAULT NULL,
    `customer_updated_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `customer_is_editable` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`customer_id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `customer_contact`;
CREATE TABLE `customer_contact` (
    `customer_contact_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `customer_contact_name` VARCHAR(100) NOT NULL,
    `customer_contact_person` VARCHAR(100) NOT NULL,
    `customer_contact_firm` VARCHAR(100) NOT NULL,
    `customer_contact_department` VARCHAR(100) NOT NULL,
    `customer_contact_email_address` VARCHAR(100) NOT NULL,
    `customer_contact_phone` VARCHAR(100) NOT NULL,
    `customer_contact_fax` VARCHAR(100) NOT NULL,
    `customer_contact_mailing_address` VARCHAR(100) NOT NULL,
    `customer_contact_city` VARCHAR(100) NOT NULL,
    `customer_contact_state` VARCHAR(100) NOT NULL,
    `customer_contact_zip` VARCHAR(100) NOT NULL,
    `customer_contact_country` VARCHAR(100) NOT NULL,
    `customer_contact_general_information` VARCHAR(150) NULL DEFAULT NULL,
    `customer_contact_created_by` INT DEFAULT NULL,
    `customer_contact_created_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `customer_contact_approved_by` INT DEFAULT NULL,
    `customer_contact_approved_on` TIMESTAMP NULL DEFAULT NULL,
    `customer_contact_updated_by` INT DEFAULT NULL,
    `customer_contact_updated_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `customer_contact_is_editable` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_contact_is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_contact_is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_contact_is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`customer_contact_id`)
) ENGINE = InnoDB;
-- ----------- Tables Customer Level --------------

LOCK TABLES `customer` WRITE;
INSERT INTO `customer` VALUES
(1,'Customer 001','customer_user_group','customer_user',NULL,NULL,NULL,'api.customer001.localhost','web.customer001.localhost','customer001.localhost','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes','Yes',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,600,300,600,300,600,300,600,300,'cDbServerType001','cDbServerHostname001','cDbServerPort001','cDbServerUsername001','cDbServerPassword001','cDbServerDatabase001','cDbServerQueryPlaceholder001','cDbServerType001','cDbServerHostname001','cDbServerPort001','cDbServerUsername001','cDbServerPassword001','cDbServerDatabase001','cDbServerQueryPlaceholder001','cCacheServerType001','cCacheServerHostname001','cCacheServerPort001','cCacheServerUsername001','cCacheServerPassword001','cCacheServerDatabase001','cCacheServerTable001','fileSessionMode',NULL,NULL,NULL,NULL,NULL,NULL,'queryCacheServerType','queryCacheServerHostname','queryCacheServerPort','queryCacheServerUsername','queryCacheServerPassword','queryCacheServerDatabase','queryCacheServerTable','',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-29 16:00:41','Yes','Yes','No','No');
UNLOCK TABLES;
