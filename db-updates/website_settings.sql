-- =============================================================
-- website_settings.sql
-- Run against the website-owned realmd database.
--
-- This table stores site runtime overrides that should survive
-- config-file changes and Apache restarts.
-- =============================================================

CREATE TABLE IF NOT EXISTS `website_settings` (
  `setting_key`   VARCHAR(191) NOT NULL,
  `setting_value` LONGTEXT      DEFAULT NULL,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by`    VARCHAR(64)   DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
