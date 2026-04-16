-- Adminer 4.7.8 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `ads`;
CREATE TABLE `ads` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` varchar(20) NOT NULL,
  `client_property_id` varchar(20) NOT NULL,
  `keyword_request_id` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `block_position` varchar(255) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `link` text DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `displayed_link` text DEFAULT NULL,
  `tracking_link` text DEFAULT NULL,
  `snippet` text DEFAULT NULL,
  `snippet_highlighted_word` text DEFAULT NULL,
  `sitelinks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sitelinks`)),
  `favicon` text DEFAULT NULL,
  `advertiser_info_token` text DEFAULT NULL,
  `date` varchar(255) DEFAULT NULL,
  `json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `ai_overview`;
CREATE TABLE `ai_overview` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` varchar(20) NOT NULL,
  `client_property_id` varchar(20) NOT NULL,
  `lms_domain` varchar(255) DEFAULT NULL,
  `keyword_request_id` varchar(255) DEFAULT NULL,
  `cluster_request_id` varchar(255) DEFAULT NULL,
  `keyword_planner_id` varchar(20) NOT NULL,
  `history_log_id` varchar(255) DEFAULT NULL,
  `text_blocks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`text_blocks`)),
  `json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`json`)),
  `markdown` text DEFAULT NULL,
  `priority_sync` enum('1','0') DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `client_landing_page_url`;
CREATE TABLE `client_landing_page_url` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` int(11) NOT NULL,
  `client_properties_id` int(11) DEFAULT NULL,
  `lms_url` varchar(255) NOT NULL,
  `url` text NOT NULL,
  `ad_group_id` varchar(255) NOT NULL,
  `ad_id` varchar(255) NOT NULL,
  `impression` varchar(255) DEFAULT NULL,
  `position` float DEFAULT NULL,
  `click` varchar(255) DEFAULT NULL,
  `ctr` float DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `client_properties`;
CREATE TABLE `client_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` int(11) NOT NULL,
  `type` enum('lms','website','landing') NOT NULL,
  `domain` tinytext NOT NULL,
  `frequency` varchar(255) NOT NULL,
  `keyword_mentioned_array` text DEFAULT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `manager_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `cluster_request`;
CREATE TABLE `cluster_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` varchar(255) NOT NULL,
  `client_property_id` varchar(255) NOT NULL,
  `lms_domain` varchar(255) DEFAULT NULL,
  `keyword` text NOT NULL,
  `date_from` varchar(255) DEFAULT NULL,
  `date_to` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `domainmanagement`;
CREATE TABLE `domainmanagement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` tinytext DEFAULT NULL,
  `phone` tinytext DEFAULT NULL,
  `email` tinytext DEFAULT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `manager_id` varchar(255) DEFAULT NULL,
  `scheduled_slug` text DEFAULT NULL,
  `visited_slug` text DEFAULT NULL,
  `missed_slug` text DEFAULT NULL,
  `interested_slug` text NOT NULL,
  `industry` tinytext DEFAULT NULL,
  `city` tinytext DEFAULT NULL,
  `zip` tinytext DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `generated_prompts`;
CREATE TABLE `generated_prompts` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` int(255) NOT NULL,
  `client_property_id` int(255) NOT NULL,
  `keyword_request_id` int(255) NOT NULL,
  `keyword_ids` text NOT NULL,
  `prompt` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `generated_prompts_response`;
CREATE TABLE `generated_prompts_response` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` int(255) NOT NULL,
  `client_property_id` int(255) NOT NULL,
  `keyword_request_id` int(255) NOT NULL,
  `generated_prompt_id` int(255) NOT NULL,
  `source` varchar(255) NOT NULL,
  `prompt_json` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `googledata`;
CREATE TABLE `googledata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gauthcode` text DEFAULT NULL,
  `gaccesstoken` text DEFAULT NULL,
  `grefreshtoken` text DEFAULT NULL,
  `gauthjson` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` varchar(200) DEFAULT NULL,
  `updated_at` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `history_log`;
CREATE TABLE `history_log` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` varchar(255) NOT NULL,
  `client_property_id` varchar(255) NOT NULL,
  `keyword_request_id` varchar(255) NOT NULL,
  `keyword_planner_id` varchar(255) NOT NULL,
  `aio_status` int(2) NOT NULL DEFAULT 0,
  `search_status` int(2) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `keyword_planner`;
CREATE TABLE `keyword_planner` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` varchar(255) NOT NULL,
  `client_property_id` varchar(255) NOT NULL,
  `lms_domain` varchar(255) DEFAULT NULL,
  `keyword_request_id` varchar(255) NOT NULL,
  `cluster_request_id` varchar(255) DEFAULT NULL,
  `parent_keyword` varchar(25) DEFAULT NULL,
  `keyword_p` text NOT NULL,
  `monthlysearch_p` varchar(255) DEFAULT NULL,
  `competition_p` varchar(255) DEFAULT NULL,
  `low_bid_p` varchar(255) DEFAULT NULL,
  `high_bid_p` varchar(255) DEFAULT NULL,
  `monthlysearchvolume_p` text DEFAULT NULL,
  `clicks_p` varchar(255) DEFAULT NULL,
  `ctr_p` varchar(255) DEFAULT NULL,
  `impressions_p` varchar(255) DEFAULT NULL,
  `position_p` varchar(255) DEFAULT NULL,
  `ai_status` enum('1','0') NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `keyword_prompt`;
CREATE TABLE `keyword_prompt` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` int(255) NOT NULL,
  `client_property_id` int(255) NOT NULL,
  `keyword_request_id` int(255) NOT NULL,
  `keyword_planner_id` int(255) NOT NULL,
  `prompt` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `keyword_request`;
CREATE TABLE `keyword_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` varchar(255) NOT NULL,
  `client_property_id` varchar(255) NOT NULL,
  `lms_domain` varchar(255) DEFAULT NULL,
  `keyword` text NOT NULL,
  `ai_overview` varchar(5) NOT NULL DEFAULT '1',
  `json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `median-fetch`;
CREATE TABLE `median-fetch` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` varchar(255) NOT NULL,
  `client_property_id` varchar(255) NOT NULL,
  `lms_domain` varchar(255) DEFAULT NULL,
  `keyword_request_id` varchar(255) NOT NULL,
  `median_name` varchar(255) DEFAULT NULL,
  `bucket` int(1) NOT NULL DEFAULT 0,
  `keyword_p` text NOT NULL,
  `monthlysearch_p` varchar(255) DEFAULT NULL,
  `competition_p` varchar(255) DEFAULT NULL,
  `low_bid_p` varchar(255) DEFAULT NULL,
  `high_bid_p` varchar(255) DEFAULT NULL,
  `monthlysearchvolume_p` text DEFAULT NULL,
  `clicks_p` varchar(255) DEFAULT NULL,
  `ctr_p` varchar(255) DEFAULT NULL,
  `impressions_p` varchar(255) DEFAULT NULL,
  `position_p` varchar(255) DEFAULT NULL,
  `date_from` varchar(255) NOT NULL,
  `date_to` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `median_info`;
CREATE TABLE `median_info` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` varchar(255) NOT NULL,
  `client_property_id` varchar(255) NOT NULL,
  `keyword_request_id` varchar(255) NOT NULL,
  `median_name` text NOT NULL,
  `date_from` varchar(255) DEFAULT NULL,
  `date_to` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1,	'2014_10_12_000000_create_users_table',	1),
(2,	'2014_10_12_100000_create_password_reset_tokens_table',	1),
(3,	'2019_08_19_000000_create_failed_jobs_table',	1),
(4,	'2019_12_14_000001_create_personal_access_tokens_table',	1),
(5,	'2023_12_12_072442_create_domain_management_models_table',	1),
(6,	'2014_10_12_100000_create_password_resets_table',	2),
(7,	'2025_12_30_060638_create_jobs_table',	3);

DROP TABLE IF EXISTS `organic_results`;
CREATE TABLE `organic_results` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` varchar(20) NOT NULL,
  `client_property_id` varchar(20) NOT NULL,
  `keyword_request_id` varchar(255) DEFAULT NULL,
  `cluster_request_id` varchar(255) DEFAULT NULL,
  `keyword_planner_id` varchar(20) NOT NULL,
  `history_log_id` varchar(255) DEFAULT NULL,
  `priority` enum('1','0') NOT NULL DEFAULT '0',
  `position` varchar(255) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `link` text DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `displayed_link` text DEFAULT NULL,
  `snippet` text DEFAULT NULL,
  `snippet_highlighted_word` text DEFAULT NULL,
  `sitelinks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sitelinks`)),
  `favicon` text DEFAULT NULL,
  `date` varchar(255) DEFAULT NULL,
  `json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `parent_keyword`;
CREATE TABLE `parent_keyword` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` varchar(25) NOT NULL,
  `client_property_id` varchar(25) NOT NULL,
  `keyword_request_id` varchar(25) NOT NULL,
  `cluster_request_id` varchar(25) NOT NULL,
  `parent_keyword` varchar(255) NOT NULL,
  `clicks` varchar(11) DEFAULT NULL,
  `ctr` varchar(11) DEFAULT NULL,
  `impressions` varchar(11) DEFAULT NULL,
  `position` varchar(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `related_questions`;
CREATE TABLE `related_questions` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` varchar(20) NOT NULL,
  `client_property_id` varchar(20) NOT NULL,
  `lms_domain` varchar(255) DEFAULT NULL,
  `keyword_request_id` varchar(255) DEFAULT NULL,
  `cluster_request_id` varchar(255) DEFAULT NULL,
  `keyword_planner_id` varchar(20) NOT NULL,
  `history_log_id` varchar(255) DEFAULT NULL,
  `priority` enum('1','0') NOT NULL DEFAULT '0',
  `question` text DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `source_title` text DEFAULT NULL,
  `source_link` text DEFAULT NULL,
  `source_source` varchar(255) DEFAULT NULL,
  `source_domain` varchar(255) DEFAULT NULL,
  `source_displayed_link` text DEFAULT NULL,
  `source_favicon` varchar(255) DEFAULT NULL,
  `json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`json`)),
  `date` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `related_searches`;
CREATE TABLE `related_searches` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `domainmanagement_id` int(255) NOT NULL,
  `client_property_id` int(255) NOT NULL,
  `lms_domain` varchar(255) DEFAULT NULL,
  `keyword_request_id` int(255) NOT NULL,
  `cluster_request_id` varchar(255) DEFAULT NULL,
  `keyword_planner_id` varchar(20) NOT NULL,
  `history_log_id` varchar(255) DEFAULT NULL,
  `priority` enum('1','0') NOT NULL DEFAULT '0',
  `query` text DEFAULT NULL,
  `link` text DEFAULT NULL,
  `json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `type` enum('SA','Admin') DEFAULT 'Admin',
  `domainmanagement_id` int(11) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `type`, `domainmanagement_id`, `remember_token`, `created_at`, `updated_at`) VALUES
(1,	'Ichelon',	'himanshu@ichelon.in',	NULL,	'$2y$12$F/aTekt5xJOdX7DOOx1e5e/4vAYGcfr/MZN72fnDfxmvzdUGAm4gi',	'SA',	0,	NULL,	'2023-12-12 03:23:16',	'2024-06-27 03:52:18');

