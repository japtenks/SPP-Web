-- =============================================================
-- 04_populationdirector.sql
-- Run against the website-owned realmd database.
--
-- Population Director runtime tables:
-- - active band state
-- - realm-level target / pressure overrides
-- - recommendation snapshots
-- - audit history
-- =============================================================

CREATE TABLE IF NOT EXISTS `website_populationdirector_bands` (
  `band_key`          VARCHAR(64)   NOT NULL,
  `band_label`        VARCHAR(128)  NOT NULL,
  `start_hour`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `end_hour`          TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `focus_terms`       VARCHAR(255)  NOT NULL DEFAULT '',
  `baseline_target`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `baseline_pressure` DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `persona_weight`    DECIMAL(5,2)  NOT NULL DEFAULT 0.60,
  `continuity_weight` DECIMAL(5,2)  NOT NULL DEFAULT 0.25,
  `pressure_weight`   DECIMAL(5,2)  NOT NULL DEFAULT 0.15,
  `is_active`         TINYINT(1)    NOT NULL DEFAULT 0,
  `notes`             TEXT          DEFAULT NULL,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`band_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `website_populationdirector_state` (
  `state_id`          TINYINT UNSIGNED NOT NULL,
  `active_band_key`   VARCHAR(64)     NOT NULL DEFAULT '',
  `active_band_source` VARCHAR(32)     NOT NULL DEFAULT 'derived',
  `active_since`      DATETIME        DEFAULT NULL,
  `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by`        VARCHAR(64)     DEFAULT NULL,
  PRIMARY KEY (`state_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `website_populationdirector_realm_overrides` (
  `realm_id`          INT UNSIGNED    NOT NULL,
  `band_key`          VARCHAR(64)     NOT NULL DEFAULT '',
  `target_override`   DECIMAL(10,2)   DEFAULT NULL,
  `pressure_override` DECIMAL(5,2)    DEFAULT NULL,
  `expires_at`        DATETIME        DEFAULT NULL,
  `note`              VARCHAR(255)    DEFAULT NULL,
  `updated_by`        VARCHAR(64)     DEFAULT NULL,
  `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`realm_id`, `band_key`),
  KEY `idx_populationdirector_override_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `website_populationdirector_recommendation_snapshots` (
  `snapshot_id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `realm_id`           INT UNSIGNED    NOT NULL,
  `band_key`           VARCHAR(64)     NOT NULL DEFAULT '',
  `snapshot_label`     VARCHAR(128)    NOT NULL DEFAULT '',
  `candidate_count`    INT UNSIGNED    NOT NULL DEFAULT 0,
  `target_count`       DECIMAL(10,2)   DEFAULT NULL,
  `pressure_value`     DECIMAL(5,2)    DEFAULT NULL,
  `context_json`       LONGTEXT        DEFAULT NULL,
  `recommendations_json` LONGTEXT      DEFAULT NULL,
  `created_by`         VARCHAR(64)     DEFAULT NULL,
  `created_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`snapshot_id`),
  KEY `idx_populationdirector_snapshot_realm` (`realm_id`, `band_key`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `website_populationdirector_audit_history` (
  `audit_id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `realm_id`      INT UNSIGNED    NOT NULL DEFAULT 0,
  `band_key`      VARCHAR(64)     NOT NULL DEFAULT '',
  `event_type`    VARCHAR(64)     NOT NULL,
  `summary`       VARCHAR(255)    NOT NULL DEFAULT '',
  `detail_json`   LONGTEXT        DEFAULT NULL,
  `created_by`    VARCHAR(64)     DEFAULT NULL,
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_id`),
  KEY `idx_populationdirector_audit_realm` (`realm_id`, `band_key`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `website_populationdirector_state` (`state_id`, `active_band_key`, `active_band_source`, `active_since`, `updated_by`)
VALUES (1, '', 'derived', NULL, 'system');

INSERT IGNORE INTO `website_populationdirector_bands` (
  `band_key`, `band_label`, `start_hour`, `end_hour`, `focus_terms`,
  `baseline_target`, `baseline_pressure`, `persona_weight`, `continuity_weight`, `pressure_weight`, `is_active`, `notes`
) VALUES
  ('overnight', 'Overnight', 0, 5, 'stability,guard,watch,sustain,quiet', 4, 0.30, 0.58, 0.27, 0.15, 0, 'Low-traffic control window that prefers steady, watchful personas.'),
  ('morning', 'Morning', 6, 11, 'quest,craft,gather,assist,travel', 6, 0.42, 0.60, 0.25, 0.15, 0, 'Player pickup hours with a bias toward helpful utility profiles.'),
  ('afternoon', 'Afternoon', 12, 16, 'group,dungeon,assist,adapt,utility', 8, 0.55, 0.62, 0.23, 0.15, 0, 'Balanced traffic band for flexible support and group-ready bots.'),
  ('prime', 'Prime Time', 17, 21, 'raid,dungeon,tactics,team,role', 12, 0.82, 0.66, 0.22, 0.12, 0, 'Highest activity band with a stronger preference for role-aligned personas.'),
  ('late', 'Late Night', 22, 23, 'cleanup,patrol,social,steady,fallback', 7, 0.48, 0.57, 0.28, 0.15, 0, 'Wind-down band that keeps the realm covered without chasing churn.');

