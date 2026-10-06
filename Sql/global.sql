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
DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
    `sessionId` VARCHAR(250) NOT NULL,
    `customerId` INT UNSIGNED,
    `sessionData` TEXT NOT NULL,
    `lastAccessed` INT UNSIGNED,
    UNIQUE KEY (`sessionId`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `super_admin`;
CREATE TABLE `super_admin` (
    `super_admin_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `super_admin_cidr` VARCHAR(250) DEFAULT NULL,
    `super_admin_rate_limit_count` INT DEFAULT NULL,
    `super_admin_rate_limit_count_window` INT DEFAULT NULL,
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
    `super_admin_group_cidr` VARCHAR(250) DEFAULT NULL,
    `super_admin_group_rate_limit_count` INT DEFAULT NULL,
    `super_admin_group_rate_limit_count_window` INT DEFAULT NULL,
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
    `customer_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_comments` VARCHAR(255) DEFAULT NULL,
    `customer_public_domain` VARCHAR(255) DEFAULT NULL,
    `customer_private_session_domain` VARCHAR(255) DEFAULT NULL,
    `customer_private_token_domain` VARCHAR(255) DEFAULT NULL,
    `customer_cron_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_custom_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_dropbox_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_download_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_explain_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_import_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_routes_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_thirdparty_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_upload_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_limiting_count` INT DEFAULT NULL,
    `customer_limiting_count_window` INT DEFAULT NULL,
    `customer_limiting_per_ip_count` INT DEFAULT NULL,
    `customer_limiting_per_ip_count_window` INT DEFAULT NULL,
    `customer_limiting_login_per_user_count` INT DEFAULT NULL,
    `customer_limiting_login_request_per_user_count_window` INT DEFAULT NULL,
    `customer_limiting_login_successfull_per_user_count` INT DEFAULT NULL,
    `customer_limiting_login_successfull_per_user_count_window` INT DEFAULT NULL,
    `customer_limiting_logged_in_user_count` INT DEFAULT NULL,
    `customer_limiting_logged_in_user_count_window` INT DEFAULT NULL,
    `customer_limiting_logged_in_user_per_ip_count` INT DEFAULT NULL,
    `customer_limiting_logged_in_user_per_ip_count_window` INT DEFAULT NULL,
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
INSERT INTO `customer` SET
    `customer_id` = 1,
    `customer_name` = 'Customer 001',
    `customer_user_group_table` = 'customer_user_group',
    `customer_user_table` = 'customer_user',
    `customer_cidr` = NULL,
    `customer_comments` = NULL,

-- Customer level domain settings
    `customer_public_domain` = 'customer001.localhost',
    `customer_private_session_domain` = 'web.customer001.localhost',
    `customer_private_token_domain` = 'api.customer001.localhost',

-- CIDR columns at customer level
    `customer_cron_cidr` =  NULL,
    `customer_custom_cidr` =  NULL,
    `customer_dropbox_cidr` =  NULL,
    `customer_download_cidr` =  NULL,
    `customer_explain_cidr` =  NULL,
    `customer_import_cidr` =  NULL,
    `customer_routes_cidr` =  NULL,
    `customer_thirdparty_cidr` =  NULL,
    `customer_upload_cidr` =  NULL,

-- Rate limiting columns at customer level
    `customer_limiting_count` =  600,
    `customer_limiting_count_window` =  300,
    `customer_limiting_per_ip_count` =  600,
    `customer_limiting_per_ip_count_window` =  300,
    `customer_limiting_login_per_user_count` =  600, --
    `customer_limiting_login_request_per_user_count_window` =  300, --
    `customer_limiting_login_successfull_per_user_count` =  600,
    `customer_limiting_login_successfull_per_user_count_window` =  300, --
    `customer_limiting_logged_in_user_count` =  600,
    `customer_limiting_logged_in_user_count_window` =  300,
    `customer_limiting_logged_in_user_per_ip_count` =  600,
    `customer_limiting_logged_in_user_per_ip_count_window` =  300,

-- Customer level other settings
    `customer_created_by` = NULL,
    `customer_created_on` = '2023-04-29 16:00:41',
    `customer_approved_by` =  NULL,
    `customer_approved_on` =  NULL,
    `customer_updated_by` =  NULL,
    `customer_updated_on` = '2023-04-29 16:00:41',
    `customer_is_editable` = 'Yes',
    `customer_is_approved` = 'Yes',
    `customer_is_disabled` = 'No',
    `customer_is_deleted` = 'No';

UNLOCK TABLES;
