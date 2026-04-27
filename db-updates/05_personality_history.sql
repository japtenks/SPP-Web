-- =============================================================
-- 05_personality_history.sql
-- Run against the website-owned realmd database.
--
-- External personality-history polling support:
-- - periodic snapshots of ai_playerbot_personality by realm
-- - website-owned storage for drift-over-time analysis
-- =============================================================

CREATE TABLE IF NOT EXISTS `website_personality_history` (
  `snapshot_id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `snapshot_batch`          VARCHAR(32)     NOT NULL,
  `captured_at`             DATETIME        NOT NULL,
  `captured_unix`           INT UNSIGNED    NOT NULL,
  `realm_id`                INT UNSIGNED    NOT NULL,
  `guid`                    INT UNSIGNED    NOT NULL,
  `character_name`          VARCHAR(32)     DEFAULT NULL,
  `level`                   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `online`                  TINYINT(1)      NOT NULL DEFAULT 0,
  `affiliation`             VARCHAR(16)     NOT NULL DEFAULT 'SOLO',
  `archetype`               VARCHAR(16)     NOT NULL DEFAULT 'CASUAL',
  `w_quest`                 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `w_grind`                 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `w_pvp`                   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `w_dungeon`               SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `w_farm`                  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_leader`               TINYINT(1)      NOT NULL DEFAULT 0,
  `leader_charisma`         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `sociability`             SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `group_time_seconds`      INT UNSIGNED    NOT NULL DEFAULT 0,
  `signatures_pledged`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `tz_offset_minutes`       SMALLINT        NOT NULL DEFAULT 0,
  `play_window_start`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `play_window_end`         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `session_minutes`         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `weekend_bonus`           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `play_day_mask`           TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `last_drift_time`         INT UNSIGNED    NOT NULL DEFAULT 0,
  `last_login_at`           INT UNSIGNED    NOT NULL DEFAULT 0,
  `last_logout_at`          INT UNSIGNED    NOT NULL DEFAULT 0,
  `first_eligible_login_at` INT UNSIGNED    NOT NULL DEFAULT 0,
  `is_tourist`              TINYINT(1)      NOT NULL DEFAULT 0,
  `tourist_session_count`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `tourist_sessions_used`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_dormant`              TINYINT(1)      NOT NULL DEFAULT 0,
  `pvp_lean`                TINYINT(1)      NOT NULL DEFAULT 0,
  `craft_for_guild`         TINYINT(1)      NOT NULL DEFAULT 0,
  `version`                 TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`snapshot_id`),
  KEY `idx_personality_history_batch` (`snapshot_batch`),
  KEY `idx_personality_history_realm_time` (`realm_id`, `captured_at`),
  KEY `idx_personality_history_guid_time` (`realm_id`, `guid`, `captured_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
