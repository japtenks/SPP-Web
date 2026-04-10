-- =============================================================
-- realmd_patch.sql
-- Run against: the website-owned realmd database
--   USE your realmd DB;  (for example classicrealmd, tbcrealmd, vmangosrealmd)
--
-- Exception: sections marked [website-owned realmd ONLY] should be run
-- against the realmd DB your website uses for shared website tables.
--
-- Run order matters. Execute top to bottom in a single session.
-- IDEMPOTENT where noted; one-shot ALTERs will error if re-run
-- against a DB where they already applied (expected behaviour).
-- =============================================================


-- -------------------------------------------------------------
-- [57] website_settings table
-- [website-owned realmd ONLY] — IDEMPOTENT.
-- Stores runtime overrides that should survive config-file changes.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `website_settings` (
  `setting_key`   VARCHAR(191) NOT NULL,
  `setting_value` LONGTEXT      DEFAULT NULL,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by`    VARCHAR(64)   DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- [58] Sync GM accounts → website_accounts + promote admins
-- -------------------------------------------------------------
-- g_id mapping:
--   1 = Guest / unregistered
--   2 = Registered user
--   3 = Admin     (gmlevel 3)
--   4 = Superadmin (gmlevel >= 4)

-- Create website_accounts rows for any account without one.
INSERT IGNORE INTO `website_accounts` (`account_id`, `display_name`, `g_id`)
SELECT `id`, `username`, 2
FROM `account`
WHERE `id` NOT IN (SELECT `account_id` FROM `website_accounts`);

-- Promote gmlevel 3 → website admin (g_id = 3).
UPDATE `website_accounts`
SET `g_id` = 3
WHERE `account_id` IN (SELECT `id` FROM `account` WHERE `gmlevel` = 3)
  AND `g_id` < 3;

-- Promote gmlevel >= 4 → website superadmin (g_id = 4).
UPDATE `website_accounts`
SET `g_id` = 4
WHERE `account_id` IN (SELECT `id` FROM `account` WHERE `gmlevel` >= 4)
  AND `g_id` < 4;


-- -------------------------------------------------------------
-- [59a] Add background preference columns to website_accounts
-- Safe to re-run on MySQL versions that do not support
-- ALTER TABLE ... ADD COLUMN IF NOT EXISTS.
-- -------------------------------------------------------------
SET @has_background_mode := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'website_accounts'
    AND COLUMN_NAME = 'background_mode'
);
SET @sql := IF(
  @has_background_mode = 0,
  'ALTER TABLE `website_accounts` ADD COLUMN `background_mode` VARCHAR(20) NOT NULL DEFAULT ''daily'' AFTER `theme`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_background_image := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'website_accounts'
    AND COLUMN_NAME = 'background_image'
);
SET @sql := IF(
  @has_background_image = 0,
  'ALTER TABLE `website_accounts` ADD COLUMN `background_image` VARCHAR(60) DEFAULT NULL AFTER `background_mode`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- -------------------------------------------------------------
-- [59b] website_identities table
-- [website-owned realmd ONLY] — run tools/backfill_identities.php after.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `website_identities` (
  `identity_id`        INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `identity_type`      ENUM('account','character','bot_character')
                                         NOT NULL DEFAULT 'account',
  `owner_account_id`   INT UNSIGNED     NULL DEFAULT NULL,
  `realm_id`           TINYINT UNSIGNED NOT NULL,
  `character_guid`     INT UNSIGNED     NULL DEFAULT NULL,
  `display_name`       VARCHAR(64)      NOT NULL DEFAULT '',
  -- Stable dedup key: 'account:{realm_id}:{account_id}' or 'char:{realm_id}:{char_guid}'
  `identity_key`       VARCHAR(80)      NOT NULL DEFAULT '',
  `forum_scope_type`   ENUM('all','realm','expansion','guild_recruitment','event_feed')
                                         NULL DEFAULT NULL,
  `forum_scope_value`  VARCHAR(32)      NULL DEFAULT NULL,
  `guild_id`           INT UNSIGNED     NULL DEFAULT NULL,
  `is_bot`             TINYINT(1)       NOT NULL DEFAULT 0,
  `is_active`          TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at`         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`identity_id`),
  UNIQUE KEY `uq_identity_key` (`identity_key`),
  KEY `idx_account`    (`realm_id`, `owner_account_id`),
  KEY `idx_char`       (`realm_id`, `character_guid`),
  KEY `idx_type_bot`   (`identity_type`, `is_bot`, `is_active`),
  KEY `idx_display`    (`display_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- [60a] website_account_profiles table + backfill
--
-- Prerequisites: migrations 59a must have run (background_mode /
-- background_image exist on website_accounts).
-- character_realm_id is included here directly; migration 67
-- only needs to ALTER website_accounts.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `website_account_profiles` (
  `account_id`          INT(11) UNSIGNED     NOT NULL,
  `character_id`        INT(11) UNSIGNED     DEFAULT NULL,
  `character_name`      VARCHAR(12)          DEFAULT NULL,
  `character_realm_id`  INT(11) UNSIGNED     DEFAULT NULL,
  `display_name`        VARCHAR(12)          DEFAULT NULL,
  `avatar`              VARCHAR(60)          DEFAULT NULL,
  `signature`           TEXT                 DEFAULT NULL,
  `hideemail`           TINYINT(1)           NOT NULL DEFAULT 1,
  `hideprofile`         TINYINT(1)           DEFAULT 0,
  `hidelocation`        TINYINT(1)           NOT NULL DEFAULT 1,
  `theme`               SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
  `background_mode`     VARCHAR(20)          NOT NULL DEFAULT 'daily',
  `background_image`    VARCHAR(60)          DEFAULT NULL,
  `secretq1`            VARCHAR(300)         NOT NULL DEFAULT '0',
  `secretq2`            VARCHAR(300)         NOT NULL DEFAULT '0',
  `secreta1`            VARCHAR(300)         NOT NULL DEFAULT '0',
  `secreta2`            VARCHAR(300)         NOT NULL DEFAULT '0',
  `created_at`          TIMESTAMP            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Backfill from website_accounts for accounts that already have a row there.
-- character_realm_id is NULL here because website_accounts doesn't have that
-- column until migration [67] runs. The profiles table column will be populated
-- by the application once [67] has been applied.
INSERT INTO `website_account_profiles` (
  `account_id`, `character_id`, `character_name`, `character_realm_id`,
  `display_name`, `avatar`, `signature`, `hideemail`, `hideprofile`,
  `hidelocation`, `theme`, `background_mode`, `background_image`,
  `secretq1`, `secreta1`
)
SELECT
  wa.`account_id`,
  wa.`character_id`,
  wa.`character_name`,
  NULL,               -- character_realm_id: not on website_accounts until [67]
  wa.`display_name`,
  wa.`avatar`,
  wa.`signature`,
  wa.`hideemail`,
  wa.`hideprofile`,
  wa.`hidelocation`,
  wa.`theme`,
  COALESCE(NULLIF(wa.`background_mode`, ''), 'daily'),
  wa.`background_image`,
  wa.`secretq1`,
  wa.`secreta1`
FROM `website_accounts` wa
LEFT JOIN `website_account_profiles` wap ON wap.`account_id` = wa.`account_id`
WHERE wap.`account_id` IS NULL;

-- Catch any accounts that exist only in `account` (no website_accounts row yet).
INSERT IGNORE INTO `website_account_profiles` (`account_id`)
SELECT a.`id`
FROM `account` a
LEFT JOIN `website_account_profiles` wap ON wap.`account_id` = a.`id`
WHERE wap.`account_id` IS NULL;


-- -------------------------------------------------------------
-- [60b] Forum identity columns
-- Run against every realm DB.
-- After running: php tools/backfill_post_identities.php
-- One-shot — will error if columns already exist (expected).
-- -------------------------------------------------------------

-- f_posts: link each post to a website_identities row.
ALTER TABLE `f_posts`
  ADD COLUMN `poster_identity_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'FK → classicrealmd.website_identities.identity_id'
    AFTER `poster_character_id`,
  ADD KEY `idx_poster_identity` (`poster_identity_id`);

-- f_topics: link the opening post author to an identity row.
ALTER TABLE `f_topics`
  ADD COLUMN `topic_poster_identity_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'FK → classicrealmd.website_identities.identity_id'
    AFTER `topic_poster_id`,
  ADD KEY `idx_topic_poster_identity` (`topic_poster_identity_id`);

-- f_forums: optional scope restriction per forum.
--   scope_type values:
--     'all'              → anyone can post (default)
--     'realm'            → scope_value = realm ID ('1', '2', '3')
--     'expansion'        → scope_value = 'classic' | 'tbc' | 'wotlk'
--     'guild_recruitment'→ reserved for guild recruitment phase
--     'event_feed'       → reserved for bot-only event posts
ALTER TABLE `f_forums`
  ADD COLUMN `scope_type`  ENUM('all','realm','expansion','guild_recruitment','event_feed')
                           NOT NULL DEFAULT 'all'
                           COMMENT 'Who may post'
                           AFTER `forum_id`,
  ADD COLUMN `scope_value` VARCHAR(32) NULL DEFAULT NULL
                           COMMENT 'Qualifier for scope_type (realm ID or expansion slug)'
                           AFTER `scope_type`;


-- -------------------------------------------------------------
-- [61] PM identity columns
-- Run against every realm DB.
-- After running: php tools/backfill_pm_identities.php
-- One-shot.
-- -------------------------------------------------------------
ALTER TABLE `website_pms`
  ADD COLUMN `sender_identity_id`    INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'FK → classicrealmd.website_identities.identity_id'
    AFTER `sender_id`,
  ADD COLUMN `recipient_identity_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'FK → classicrealmd.website_identities.identity_id'
    AFTER `sender_identity_id`,
  ADD KEY `idx_pm_sender_identity`    (`sender_identity_id`),
  ADD KEY `idx_pm_recipient_identity` (`recipient_identity_id`);


-- -------------------------------------------------------------
-- [62] Guild recruitment columns on f_topics
-- Run against every realm DB. One-shot.
-- -------------------------------------------------------------
ALTER TABLE `f_topics`
  ADD COLUMN `guild_id`              INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Guild this recruitment thread belongs to'
    AFTER `topic_poster_identity_id`,
  ADD COLUMN `managed_by_account_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Account ID of the guild leader who owns this thread'
    AFTER `guild_id`,
  ADD COLUMN `recruitment_status`    ENUM('active','closed') NULL DEFAULT NULL
    COMMENT 'NULL = not a recruitment thread'
    AFTER `managed_by_account_id`,
  ADD COLUMN `last_bumped_at`        INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Unix timestamp of last bump in this recruitment thread'
    AFTER `recruitment_status`,
  ADD KEY `idx_guild_recruitment` (`guild_id`, `recruitment_status`);


-- -------------------------------------------------------------
-- [63] Bot events
-- PART 1: website_bot_events table — [website-owned realmd ONLY]
-- PART 2: content_source column — run against every realm DB.
-- -------------------------------------------------------------

-- PART 1 [website-owned realmd ONLY]
CREATE TABLE IF NOT EXISTS `website_bot_events` (
  `event_id`        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `event_type`      ENUM(
                      'level_up',
                      'guild_created',
                      'profession_milestone',
                      'raid_clear',
                      'quest_complete',
                      'guild_roster_update',  -- added in [64]
                      'achievement_badge'     -- added in [65]
                    ) NOT NULL,
  `realm_id`        TINYINT UNSIGNED NOT NULL,
  `account_id`      INT UNSIGNED    NULL DEFAULT NULL,
  `character_guid`  INT UNSIGNED    NULL DEFAULT NULL,
  `guild_id`        INT UNSIGNED    NULL DEFAULT NULL,
  -- JSON payload: char name, level, profession name, etc.
  `payload_json`    TEXT            NOT NULL,
  -- Format: 'level_up:realm1:char123:level60'
  `dedupe_key`      VARCHAR(120)    NOT NULL DEFAULT '',
  `target_forum_id` INT UNSIGNED    NULL DEFAULT NULL,
  `occurred_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at`    DATETIME        NULL DEFAULT NULL,
  `status`          ENUM('pending','processing','posted','skipped','failed')
                                    NOT NULL DEFAULT 'pending',
  `error_message`   VARCHAR(255)    NULL DEFAULT NULL,
  PRIMARY KEY (`event_id`),
  UNIQUE KEY `uq_dedupe`      (`dedupe_key`),
  KEY        `idx_status`     (`status`, `occurred_at`),
  KEY        `idx_realm_type` (`realm_id`, `event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PART 2 — every realm DB
ALTER TABLE `f_posts`
  ADD COLUMN `content_source` ENUM('player','player_assisted','system_event','bot_generated')
                              NOT NULL DEFAULT 'player'
                              AFTER `poster_identity_id`;

ALTER TABLE `f_topics`
  ADD COLUMN `content_source` ENUM('player','player_assisted','system_event','bot_generated')
                              NOT NULL DEFAULT 'player'
                              AFTER `topic_poster_identity_id`;


-- -------------------------------------------------------------
-- [66] website_identity_profiles table
-- [website-owned realmd ONLY] — IDEMPOTENT.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `website_identity_profiles` (
  `identity_id` INT(11) UNSIGNED NOT NULL,
  `signature`   TEXT             DEFAULT NULL,
  `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`identity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- -------------------------------------------------------------
-- [67] character_realm_id on website_accounts
-- NOTE: website_account_profiles already has this column as of
-- migration 60a above. Only website_accounts needs the ALTER.
-- One-shot.
-- -------------------------------------------------------------
ALTER TABLE `website_accounts`
  ADD COLUMN `character_realm_id` INT(11) UNSIGNED DEFAULT NULL AFTER `character_name`;


-- -------------------------------------------------------------
-- [70] Login throttle table — IDEMPOTENT.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `website_login_throttle` (
  `lookup_key`          CHAR(64)     NOT NULL,
  `normalized_username` VARCHAR(255) NOT NULL,
  `client_ip_hash`      CHAR(64)     NOT NULL,
  `first_failure_at`    INT UNSIGNED NOT NULL DEFAULT 0,
  `last_failure_at`     INT UNSIGNED NOT NULL DEFAULT 0,
  `failure_count`       INT UNSIGNED NOT NULL DEFAULT 0,
  `lock_until`          INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lookup_key`),
  KEY `idx_login_throttle_user_ip`   (`normalized_username`, `client_ip_hash`),
  KEY `idx_login_throttle_lock_until` (`lock_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- [71] show_hidden_forums column on website_accounts
-- One-shot.
-- -------------------------------------------------------------
ALTER TABLE `website_accounts`
  ADD COLUMN `show_hidden_forums` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1
  AFTER `background_image`;


-- -------------------------------------------------------------
-- [72] Normalise background_mode → NOT NULL DEFAULT 'daily'
-- Safe to re-run (UPDATE matches 0 rows if data is already clean).
-- -------------------------------------------------------------

-- website_accounts
UPDATE `website_accounts`
SET `background_mode` = 'daily'
WHERE `background_mode` IS NULL
   OR `background_mode` = ''
   OR `background_mode` = 'as_is';

ALTER TABLE `website_accounts`
  MODIFY COLUMN `background_mode` VARCHAR(20) NOT NULL DEFAULT 'daily';

-- website_account_profiles (already NOT NULL DEFAULT 'daily' from migration 60a,
-- but safe to run as a consistency pass).
UPDATE `website_account_profiles`
SET `background_mode` = 'daily'
WHERE `background_mode` IS NULL
   OR `background_mode` = ''
   OR `background_mode` = 'as_is';

ALTER TABLE `website_account_profiles`
  MODIFY COLUMN `background_mode` VARCHAR(20) NOT NULL DEFAULT 'daily';
