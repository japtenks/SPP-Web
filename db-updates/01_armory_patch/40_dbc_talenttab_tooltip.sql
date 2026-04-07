-- armory_patch_reduced.sql
-- Reduced/idempotent armory patch for the vanilla base dump in SPP-core.
-- Generated from armory_patch.sql against SPP-core\SPP_Server\sql\vanilla\armory.sql.
--
-- Changes from the original bundle:
--   - Keeps only the dbc_spellicon delta versus the base armory.sql dump.
--   - Keeps tooltip schema/data only where the base dump is missing columns.
--   - Omits the dbc_spell effect_die_sides patch because the base dump already contains it.
--   - Makes armory_itemset_notes idempotent instead of dropping/recreating it.


SET @ddl := IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dbc_talenttab' AND COLUMN_NAME = 'SpellIconID'), 'SELECT 1', 'ALTER TABLE dbc_talenttab ADD COLUMN SpellIconID BIGINT(20) NOT NULL DEFAULT 0 AFTER tab_number');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `dbc_talenttab` SET `ID`=41, `SpellIconID`=11 WHERE `ID`=41;
UPDATE `dbc_talenttab` SET `ID`=61, `SpellIconID`=56 WHERE `ID`=61;
UPDATE `dbc_talenttab` SET `ID`=81, `SpellIconID`=122 WHERE `ID`=81;
UPDATE `dbc_talenttab` SET `ID`=161, `SpellIconID`=1462 WHERE `ID`=161;
UPDATE `dbc_talenttab` SET `ID`=163, `SpellIconID`=1463 WHERE `ID`=163;
UPDATE `dbc_talenttab` SET `ID`=164, `SpellIconID`=456 WHERE `ID`=164;
UPDATE `dbc_talenttab` SET `ID`=181, `SpellIconID`=1501 WHERE `ID`=181;
UPDATE `dbc_talenttab` SET `ID`=182, `SpellIconID`=498 WHERE `ID`=182;
UPDATE `dbc_talenttab` SET `ID`=183, `SpellIconID`=103 WHERE `ID`=183;
UPDATE `dbc_talenttab` SET `ID`=201, `SpellIconID`=555 WHERE `ID`=201;
UPDATE `dbc_talenttab` SET `ID`=202, `SpellIconID`=79 WHERE `ID`=202;
UPDATE `dbc_talenttab` SET `ID`=203, `SpellIconID`=98 WHERE `ID`=203;
UPDATE `dbc_talenttab` SET `ID`=261, `SpellIconID`=1137 WHERE `ID`=261;
UPDATE `dbc_talenttab` SET `ID`=262, `SpellIconID`=963 WHERE `ID`=262;
UPDATE `dbc_talenttab` SET `ID`=263, `SpellIconID`=312 WHERE `ID`=263;
UPDATE `dbc_talenttab` SET `ID`=281, `SpellIconID`=201 WHERE `ID`=281;
UPDATE `dbc_talenttab` SET `ID`=282, `SpellIconID`=962 WHERE `ID`=282;
UPDATE `dbc_talenttab` SET `ID`=283, `SpellIconID`=62 WHERE `ID`=283;
UPDATE `dbc_talenttab` SET `ID`=301, `SpellIconID`=937 WHERE `ID`=301;
UPDATE `dbc_talenttab` SET `ID`=302, `SpellIconID`=150 WHERE `ID`=302;
UPDATE `dbc_talenttab` SET `ID`=303, `SpellIconID`=692 WHERE `ID`=303;
UPDATE `dbc_talenttab` SET `ID`=361, `SpellIconID`=255 WHERE `ID`=361;
UPDATE `dbc_talenttab` SET `ID`=362, `SpellIconID`=257 WHERE `ID`=362;
UPDATE `dbc_talenttab` SET `ID`=363, `SpellIconID`=126 WHERE `ID`=363;
UPDATE `dbc_talenttab` SET `ID`=381, `SpellIconID`=555 WHERE `ID`=381;
UPDATE `dbc_talenttab` SET `ID`=382, `SpellIconID`=70 WHERE `ID`=382;
UPDATE `dbc_talenttab` SET `ID`=383, `SpellIconID`=291 WHERE `ID`=383;

-- ========================================================
