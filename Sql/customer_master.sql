-- Required Table
-- ----------- Tables Customer level (Customer Entered Data) --------------
DROP TABLE IF EXISTS `customer_user_group`;
CREATE TABLE `customer_user_group` (
    `customer_user_group_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_user_group_name` VARCHAR(100) NOT NULL,
    `customer_user_group_allowed_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_user_group_rate_limit_max_request` INT DEFAULT NULL,
    `customer_user_group_rate_limit_max_request_window` INT DEFAULT NULL,
    `customer_user_group_general_information` VARCHAR(250) DEFAULT NULL,
    `customer_user_group_created_by` INT DEFAULT NULL,
    `customer_user_group_created_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `customer_user_group_approved_by` INT DEFAULT NULL,
    `customer_user_group_approved_on` TIMESTAMP NULL DEFAULT NULL,
    `customer_user_group_updated_by` INT DEFAULT NULL,
    `customer_user_group_updated_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `customer_user_group_is_editable` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_user_group_is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_user_group_is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_user_group_is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`customer_user_group_id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `customer_user`;
CREATE TABLE `customer_user` (
    `customer_user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_user_group_id` INT UNSIGNED NOT NULL,
    `customer_user_allowed_cidr` VARCHAR(250) DEFAULT NULL,
    `customer_user_rate_limit_max_request` INT DEFAULT NULL,
    `customer_user_rate_limit_max_request_window` INT DEFAULT NULL,
    `customer_user_username` VARCHAR(100) NOT NULL,
    `customer_user_password_hash` VARCHAR(150) NOT NULL,
    `customer_user_contact_name` VARCHAR(100) DEFAULT NULL,
    `customer_user_contact_person` VARCHAR(100) DEFAULT NULL,
    `customer_user_contact_firm` VARCHAR(100) DEFAULT NULL,
    `customer_user_contact_department` VARCHAR(100) DEFAULT NULL,
    `customer_user_contact_email_address` VARCHAR(100) DEFAULT NULL,
    `customer_user_contact_phone` VARCHAR(100) DEFAULT NULL,
    `customer_user_contact_fax` VARCHAR(100) DEFAULT NULL,
    `customer_user_contact_mailing_address` VARCHAR(100) DEFAULT NULL,
    `customer_user_contact_city` VARCHAR(100) DEFAULT NULL,
    `customer_user_contact_state` VARCHAR(100) DEFAULT NULL,
    `customer_user_contact_zip` VARCHAR(100) DEFAULT NULL,
    `customer_user_contact_country` VARCHAR(100) DEFAULT NULL,
    `customer_user_contact_general_information` VARCHAR(150) NULL DEFAULT NULL,
    `customer_user_created_by` INT DEFAULT NULL,
    `customer_user_created_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `customer_user_approved_by` INT DEFAULT NULL,
    `customer_user_approved_on` TIMESTAMP NULL DEFAULT NULL,
    `customer_user_updated_by` INT DEFAULT NULL,
    `customer_user_updated_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `customer_user_is_editable` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_user_is_approved` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_user_is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `customer_user_is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`customer_user_id`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `import_file_detail`;
CREATE TABLE `import_file_detail` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `customer_user_group_id` INT NOT NULL,
    `customer_user_id` INT NOT NULL,
    `uploaded_file_name` VARCHAR(255) NOT NULL,
    `uploaded_file_md5` VARCHAR(255) NOT NULL,
    `uploaded_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `request_ip` VARCHAR(25) NOT NULL,
    `is_disabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    `is_deleted` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;
-- ----------- Tables Customer level (Customer Entered Data) --------------

LOCK TABLES `customer_user_group` WRITE;
INSERT INTO `customer_user_group` VALUES
(2,'Customer001UserGroup1',NULL,NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes','Yes','No','No'),
(3,'AdminGroup',NULL,NULL,NULL,'',NULL,'2023-04-15 08:54:50',NULL,NULL,NULL,'2023-04-21 06:38:22','Yes','Yes','No','No');
UNLOCK TABLES;

LOCK TABLES `customer_user` WRITE;
INSERT INTO `customer_user` VALUES
(4,2,'',NULL,NULL,'customer_1_group_1_user_1','$2y$10$o8hFTjBIXQS.fOED2Ut1ZOCSdDjTnS3lyELI4rWyFEnu4GUyJr3O6','','','','','','','','','','','','','',0,'2023-02-22 04:12:50',NULL,NULL,0,'2023-04-20 16:53:57','Yes','Yes','No','No'),
(5,3,'',NULL,NULL,'customer_1_admin_1','$2y$10$o8hFTjBIXQS.fOED2Ut1ZOCSdDjTnS3lyELI4rWyFEnu4GUyJr3O6','','','','','','','','','','','','','',0,'2023-02-22 04:12:50',NULL,NULL,0,'2023-04-20 16:53:57','Yes','Yes','No','No');
UNLOCK TABLES;

-- Product Tables definition goes below
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
