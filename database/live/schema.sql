-- =====================================================================
-- SWON Web — core MVP schema (MySQL 8 / MariaDB 10.4)
-- Subset of the mobile product: accounts, roles, profiles, jobs,
-- applications. KYC / attendance / payments / ratings are out of scope.
--
-- Safe to run more than once: tables use IF NOT EXISTS.
-- This file never touches the original WorkBridge database.
-- =====================================================================


CREATE TABLE IF NOT EXISTS `account` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id`     VARCHAR(32)     NOT NULL,
  `phone`         VARCHAR(15)     NOT NULL,
  `role`          ENUM('worker','employer','admin') NULL DEFAULT NULL,
  `status`        ENUM('PENDING','ACTIVE','SUSPENDED','CLOSED') NOT NULL DEFAULT 'PENDING',
  `onboarded_at`  DATETIME        NULL DEFAULT NULL,
  `last_login_at` DATETIME        NULL DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_account_public_id` (`public_id`),
  UNIQUE KEY `uq_account_phone` (`phone`),
  KEY `idx_account_role` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `otp_challenge` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone`          VARCHAR(15)     NOT NULL,
  `code_hash`      CHAR(64)        NOT NULL,
  `attempts`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at`     DATETIME        NOT NULL,
  `consumed_at`    DATETIME        NULL DEFAULT NULL,
  `cooldown_until` DATETIME        NOT NULL,
  `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_otp_phone` (`phone`, `expires_at`),
  KEY `idx_otp_created` (`phone`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `area` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id`  VARCHAR(32)     NOT NULL,
  `name`       VARCHAR(120)    NOT NULL,
  `city`       VARCHAR(120)    NOT NULL,
  `active`     TINYINT(1)      NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_area_public_id` (`public_id`),
  KEY `idx_area_city` (`city`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_category` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id`  VARCHAR(32)     NOT NULL,
  `name`       VARCHAR(120)    NOT NULL,
  `slug`       VARCHAR(120)    NOT NULL,
  `icon`       VARCHAR(32)     NOT NULL DEFAULT 'briefcase',
  `active`     TINYINT(1)      NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_public_id` (`public_id`),
  UNIQUE KEY `uq_category_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `worker_profile` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_id`   BIGINT UNSIGNED NOT NULL,
  `full_name`    VARCHAR(160)    NOT NULL,
  `area_id`      BIGINT UNSIGNED NULL DEFAULT NULL,
  `skills`       VARCHAR(255)    NOT NULL DEFAULT '',
  `availability` ENUM('FULL_TIME','PART_TIME','WEEKENDS','FLEXIBLE') NOT NULL DEFAULT 'FLEXIBLE',
  `about`        VARCHAR(600)    NOT NULL DEFAULT '',
  `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_worker_account` (`account_id`),
  KEY `idx_worker_area` (`area_id`),
  CONSTRAINT `fk_worker_account` FOREIGN KEY (`account_id`) REFERENCES `account` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_worker_area` FOREIGN KEY (`area_id`) REFERENCES `area` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employer_profile` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_id`   BIGINT UNSIGNED NOT NULL,
  `org_name`     VARCHAR(160)    NOT NULL,
  `contact_name` VARCHAR(160)    NOT NULL DEFAULT '',
  `area_id`      BIGINT UNSIGNED NULL DEFAULT NULL,
  `about`        VARCHAR(600)    NOT NULL DEFAULT '',
  `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employer_account` (`account_id`),
  KEY `idx_employer_area` (`area_id`),
  CONSTRAINT `fk_employer_account` FOREIGN KEY (`account_id`) REFERENCES `account` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_employer_area` FOREIGN KEY (`area_id`) REFERENCES `area` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id`        VARCHAR(32)     NOT NULL,
  `employer_id`      BIGINT UNSIGNED NOT NULL,
  `category_id`      BIGINT UNSIGNED NOT NULL,
  `area_id`          BIGINT UNSIGNED NOT NULL,
  `title`            VARCHAR(180)    NOT NULL,
  `description`      TEXT            NOT NULL,
  `address`          VARCHAR(255)    NOT NULL DEFAULT '',
  `pay_amount_paise` BIGINT UNSIGNED NOT NULL,
  `pay_type`         ENUM('DAY','HOUR','TOTAL') NOT NULL DEFAULT 'DAY',
  `headcount`        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `starts_on`        DATE            NOT NULL,
  `ends_on`          DATE            NOT NULL,
  `start_time`       TIME            NOT NULL DEFAULT '09:00:00',
  `end_time`         TIME            NOT NULL DEFAULT '18:00:00',
  `status`           ENUM('DRAFT','OPEN','FILLED','COMPLETED','CANCELLED','EXPIRED') NOT NULL DEFAULT 'DRAFT',
  `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_job_public_id` (`public_id`),
  KEY `idx_job_status_dates` (`status`, `starts_on`),
  KEY `idx_job_employer` (`employer_id`, `status`),
  KEY `idx_job_category` (`category_id`),
  KEY `idx_job_area` (`area_id`),
  CONSTRAINT `fk_job_employer` FOREIGN KEY (`employer_id`) REFERENCES `account` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_job_category` FOREIGN KEY (`category_id`) REFERENCES `job_category` (`id`),
  CONSTRAINT `fk_job_area` FOREIGN KEY (`area_id`) REFERENCES `area` (`id`),
  CONSTRAINT `chk_job_pay` CHECK (`pay_amount_paise` > 0),
  CONSTRAINT `chk_job_headcount` CHECK (`headcount` > 0),
  CONSTRAINT `chk_job_dates` CHECK (`ends_on` >= `starts_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `application` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id`  VARCHAR(32)     NOT NULL,
  `job_id`     BIGINT UNSIGNED NOT NULL,
  `worker_id`  BIGINT UNSIGNED NOT NULL,
  `status`     ENUM('APPLIED','SHORTLISTED','WAITLISTED','HIRED','REJECTED','WITHDRAWN','RELEASED')
               NOT NULL DEFAULT 'APPLIED',
  `note`       VARCHAR(500)    NOT NULL DEFAULT '',
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_application_public_id` (`public_id`),
  UNIQUE KEY `uq_application_job_worker` (`job_id`, `worker_id`),
  KEY `idx_application_worker` (`worker_id`, `status`),
  KEY `idx_application_job` (`job_id`, `status`),
  CONSTRAINT `fk_application_job` FOREIGN KEY (`job_id`) REFERENCES `job` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_application_worker` FOREIGN KEY (`worker_id`) REFERENCES `account` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
