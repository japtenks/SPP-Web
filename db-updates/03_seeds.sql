-- =============================================================
-- seeds.sql
-- Optional seed / reset scripts for demo and fresh installs.
--
-- WARNING: Both sections are DESTRUCTIVE to existing content.
-- Do not run against a live server with real user data unless
-- you explicitly intend to reset that content.
--
-- SECTION 1 — Forum default state
--   Resets the News forum and seeds the default forum layout.
--   Run against the realmd DB that owns the forum tables.
--
-- SECTION 2 — Auction House showcase data
--   Populates each realm's auction table with demo listings.
--   Run against the DB that owns classiccharacters / tbccharacters
--   / wotlkcharacters (typically the same session with cross-DB
--   references, or run per-realm with your tool of choice).
-- =============================================================


-- =============================================================
-- SECTION 1: Forum default state
-- =============================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

SET @reset_time     := 1775494800; -- Apr 06 2026 12:00:00 America/Chicago
SET @topic_author   := 'web Team';
SET @topic_author_id := 0;
SET @topic_author_ip := '::1';

SET @news_subject := 'SPP-Web Beta v0.1 Release';
SET @news_message := 'Major site redesign<br />\r\nThe website has been rebuilt and expanded across the main player-facing sections.<br />\r\n<br />\r\n[Game Guide]<br />\r\n- How to Connect<br />\r\n- Bot Guide with Macro/WBuff/Bot Strats<br />\r\n<br />\r\n[Workshop]<br />\r\n- Realm Status reworked<br />\r\n- Player Map reworked<br />\r\n- Statics page reworked<br />\r\n- New Auction House page<br />\r\n- New Downloads page<br />\r\n<br />\r\n[Forums]<br />\r\n- Forum system brought online and integrated into the site<br />\r\n<br />\r\n[Armory]<br />\r\n- Character list now shows both players and bots<br />\r\n- Character list includes player and bot filtering<br />\r\n- Character view updated with refreshed tab layouts<br />\r\n- Guild list view added<br />\r\n- Guild view modernized<br />\r\n- Honor view modernized<br />\r\n- Item Vault<br />\r\n- Market Place - UNDER CONSTRUCTION, heavy DB call.<br />\r\n<br />\r\n[Account]<br />\r\n- Direct Message on the site<br />\r\n- Account Preference Expansion, Background img behavior.<br />\r\n- Change Password and Character namees<br />\r\n- Fourm Avatar, signature<br />\r\n<br />\r\n[Admin]<br />\r\n- Operations<br />\r\n- Site Maintance<br />\r\n- Character Tools<br />\r\n- Bot Controls/Automation<br />\r\n<br />\r\nPlease report any website issues, polish requests, or missing content in the Website Issues section.';
SET @welcome_subject := 'Welcome to the Server';
SET @welcome_message := 'Welcome to the server and the website hub.<br />\r\n<br />\r\nUse this site to keep up with announcements, browse character and guild activity, manage your account, and jump into the realm forums.<br />\r\n<br />\r\nStart here:<br />\r\n- Read the latest News posts for updates and changes<br />\r\n- Visit the General forum for your realm to ask questions or share progress<br />\r\n- Use the Guild Recruitment forum if you are building a guild or looking for one<br />\r\n- Check the Rules post before posting or recruiting<br />\r\n<br />\r\nHave fun, be respectful, and help make the realm feel alive.';
SET @rules_subject := 'Server Rules and Conduct';
SET @rules_message := '[b]Respect the community[/b]<br />\r\n- No harassment, hate speech, or repeated personal attacks<br />\r\n- Keep public chat and forum posts readable and constructive<br />\r\n<br />\r\n[b]Play fair[/b]<br />\r\n- No cheating, duping, or exploiting bugs for advantage<br />\r\n- Report major bugs or exploits instead of abusing them<br />\r\n<br />\r\n[b]Use the right forum for the job[/b]<br />\r\n- News is for official announcements<br />\r\n- General is for realm discussion and questions<br />\r\n- Guild Recruitment is for active guild recruiting threads only<br />\r\n<br />\r\n[b]Guild recruitment expectations[/b]<br />\r\n- Keep one active recruitment thread per guild per realm<br />\r\n- Update your existing thread instead of reposting duplicates<br />\r\n- Use the pinned format example so members can scan your post quickly<br />\r\n<br />\r\nStaff may edit, close, or remove posts that do not follow these rules.';
SET @guild_format_subject := 'Guild Recruitment Format Example';
SET @guild_format_message := 'Use this format for guild recruitment threads so players and the guild bot system can read them consistently.<br />\r\n<br />\r\n[b]Recommended title[/b]<br />\r\n- &lt;Your Guild&gt; is Recruiting!<br />\r\n- &lt;Your Guild&gt; is Recruiting (Raiding / Progression)<br />\r\n<br />\r\n[b]Recommended post body[/b]<br />\r\n- [b]&lt;Your Guild&gt;[/b] is recruiting new members.<br />\r\n- Focus: raiding, dungeons, leveling, PvP, crafting, or social play.<br />\r\n- Needs: tanks, healers, ranged DPS, melee DPS, or all roles.<br />\r\n- Activity window: list your normal play times.<br />\r\n- Loot or leadership notes: include any expectations before applying.<br />\r\n- Contact: whisper [b]&lt;Leader Name&gt;[/b] in-game.<br />\r\n<br />\r\n[b]Example[/b]<br />\r\n- [b]&lt;Ashen Vanguard&gt;[/b] is recruiting for a steady progression roster.<br />\r\n- Focus: organized dungeon groups now, building toward weekly raid nights.<br />\r\n- Needs: one tank, two healers, and reliable ranged DPS.<br />\r\n- Activity window: most guild activity starts after 7:00 PM server time.<br />\r\n- Contact: whisper [b]&lt;Captain Elra&gt;[/b] in-game.<br />\r\n<br />\r\nKeep one active thread per guild. Update your existing thread when your needs change.';
SET @help_welcome_subject := 'Welcome / Start Here';
SET @help_welcome_message := '[b]Start Here[/b]<br />\r\n- Read the latest News post for active changes.<br />\r\n- Use How to Connect if this is your first login.<br />\r\n- Check the website and account guides before opening a support thread.<br />\r\n<br />\r\n[b]Quick Paths[/b]<br />\r\n- Forums: ask realm questions in General.<br />\r\n- Guilds: use Guild Recruitment for recruiting or searching.<br />\r\n- Support: use Bug Reports for site or gameplay issues that need follow-up.';
SET @help_connect_subject := 'How to Connect';
SET @help_connect_message := '[b]Connection Checklist[/b]<br />\r\n- Create your website account first.<br />\r\n- Download the correct client build for your realm.<br />\r\n- Set your realmlist to the server address shown on the connect page.<br />\r\n<br />\r\n[b]If Login Fails[/b]<br />\r\n- Recheck account name and password spelling.<br />\r\n- Confirm the game client expansion matches the realm.<br />\r\n- Verify the realm is online before retrying.';
SET @help_features_subject := 'Website Features Guide';
SET @help_features_message := '[b]What the Site Covers[/b]<br />\r\n- News and release notes.<br />\r\n- Realm status, player map, statistics, and downloads.<br />\r\n- Armory pages for characters, guilds, honor, items, and talents.<br />\r\n<br />\r\n[b]Forum Basics[/b]<br />\r\n- General is for discussion.<br />\r\n- Guild Recruitment is for active recruiting only.<br />\r\n- Help / FAQ is read-only and meant as a quick reference.';
SET @help_bot_subject := 'Bot Guide / Common Commands';
SET @help_bot_message := '[b]Bot Basics[/b]<br />\r\n- Start with the Bot Guide page for full command coverage.<br />\r\n- Focus first on summon, follow, stay, attack, and basic role setup.<br />\r\n- Keep one or two reliable macro sets instead of trying every command at once.<br />\r\n<br />\r\n[b]Common Pitfalls[/b]<br />\r\n- Wrong target selected before issuing a command.<br />\r\n- Missing permissions or party state for a command.<br />\r\n- Realm scripts or combat state blocking the action.';
SET @help_guild_subject := 'Guild Recruitment Guide';
SET @help_guild_message := '[b]Before You Post[/b]<br />\r\n- Use the Guild Recruitment forum for your realm.<br />\r\n- Keep one active thread per guild.<br />\r\n- Update your existing thread instead of reposting duplicates.<br />\r\n<br />\r\n[b]Recommended Info[/b]<br />\r\n- Focus, schedule, roles needed, and contact name.<br />\r\n- Short leadership or loot expectations if they matter.<br />\r\n- Use the pinned format example in the Guild Recruitment forum.';
SET @help_account_subject := 'Account Features Guide';
SET @help_account_message := '[b]Account Tools[/b]<br />\r\n- Manage profile details, avatar, signature, and preferences.<br />\r\n- Review characters tied to your account across realms.<br />\r\n- Use account pages for password and profile maintenance.<br />\r\n<br />\r\n[b]Good to Know[/b]<br />\r\n- Some forum actions depend on having a valid realm character loaded.<br />\r\n- Staff-only hidden forum controls only appear for eligible accounts.';
SET @staff_panel_subject := 'Admin Panel Overview';
SET @staff_panel_message := '[b]Admin Areas[/b]<br />\r\n- Operations and site maintenance.<br />\r\n- Character tools and account management.<br />\r\n- Bot controls, automation, and forum moderation paths.<br />\r\n<br />\r\n[b]Routine Check[/b]<br />\r\n- Confirm realm connectivity first.<br />\r\n- Verify current config before changing live behavior.<br />\r\n- Leave short notes when a change affects player-facing flows.';
SET @staff_forum_subject := 'Forum / Moderation Workflow';
SET @staff_forum_message := '[b]Forum Workflow[/b]<br />\r\n- Hidden forums are visible only when staff enable hidden-forum viewing.<br />\r\n- Use close, sticky, and hide actions instead of ad hoc workarounds.<br />\r\n- Keep Help / FAQ and Admin / Staff Guide seeded and read-only.<br />\r\n<br />\r\n[b]Moderation Notes[/b]<br />\r\n- Move fast on spam, harassment, and duplicate recruitment threads.<br />\r\n- Prefer editing or closing over deleting when an audit trail matters.';
SET @staff_realm_subject := 'Realm Runtime Settings Guide';
SET @staff_realm_message := '[b]Runtime Focus[/b]<br />\r\n- Review forum, menu, and realm config values together before release.<br />\r\n- Keep realm-specific behavior aligned with the selected realmd mapping.<br />\r\n- Re-test external links after changing config-backed menu entries.<br />\r\n<br />\r\n[b]Safety[/b]<br />\r\n- Prefer reversible config changes.<br />\r\n- Verify hidden or closed forum behavior with a non-staff account.';
SET @staff_ops_subject := 'Guild / Playerbots Operations Guide';
SET @staff_ops_message := '[b]Operational Focus[/b]<br />\r\n- Guild recruitment automation depends on one active thread per guild per realm.<br />\r\n- Playerbot tooling should be checked against the active realm and expansion.<br />\r\n- Watch for forum-side automation that updates recruitment metadata.<br />\r\n<br />\r\n[b]When Troubleshooting[/b]<br />\r\n- Check forum scope, realm mapping, and account permissions first.<br />\r\n- Confirm background processors have run before assuming stale data.';
SET @staff_schema_subject := 'Known Legacy Schema / Migration Notes';
SET @staff_schema_message := '[b]Legacy Notes[/b]<br />\r\n- The forum schema has legacy assumptions around fixed forum IDs and realm scopes.<br />\r\n- Seed scripts refresh selected pinned topics by forum and subject to stay idempotent.<br />\r\n- Hidden staff content should continue using the existing hidden-forum visibility path.<br />\r\n<br />\r\n[b]Migration Reminder[/b]<br />\r\n- Check for column support before depending on newer identity fields.<br />\r\n- Document reserved IDs in this file before adding more seeded forums.';

-- Detect which realms are present.
SET @has_classic := EXISTS(SELECT 1 FROM `realmlist` WHERE `id` = 1);
SET @has_tbc     := EXISTS(SELECT 1 FROM `realmlist` WHERE `id` = 2);
SET @has_wotlk   := EXISTS(SELECT 1 FROM `realmlist` WHERE `id` = 3);

-- ---------------------------------------------------------------
-- Sync website_accounts from account and promote GMs.
-- ---------------------------------------------------------------
REPLACE INTO `website_accounts` (`account_id`, `display_name`)
SELECT `id`, `username` FROM `account`;

UPDATE `website_accounts`
SET `g_id` = 3
WHERE `account_id` IN (SELECT `id` FROM `account` WHERE `gmlevel` = 3);

UPDATE `website_accounts`
SET `g_id` = 4
WHERE `account_id` IN (SELECT `id` FROM `account` WHERE `gmlevel` >= 4);

-- ---------------------------------------------------------------
-- Categories (idempotent).
-- ---------------------------------------------------------------
INSERT INTO `f_categories` (`cat_name`, `cat_disp_position`)
SELECT 'News', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `f_categories` WHERE LOWER(`cat_name`) = 'news');

INSERT INTO `f_categories` (`cat_name`, `cat_disp_position`)
SELECT 'General', 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `f_categories` WHERE LOWER(`cat_name`) = 'general');

INSERT INTO `f_categories` (`cat_name`, `cat_disp_position`)
SELECT 'Guild', 3 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `f_categories` WHERE LOWER(`cat_name`) = 'guild');

INSERT INTO `f_categories` (`cat_name`, `cat_disp_position`)
SELECT 'Help', 4 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `f_categories` WHERE LOWER(`cat_name`) = 'help');

INSERT INTO `f_categories` (`cat_name`, `cat_disp_position`)
SELECT 'Comments', 5 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `f_categories` WHERE LOWER(`cat_name`) = 'comments');

SET @news_cat_id     := (SELECT `cat_id` FROM `f_categories` WHERE LOWER(`cat_name`) = 'news'     LIMIT 1);
SET @general_cat_id  := (SELECT `cat_id` FROM `f_categories` WHERE LOWER(`cat_name`) = 'general'  LIMIT 1);
SET @guild_cat_id    := (SELECT `cat_id` FROM `f_categories` WHERE LOWER(`cat_name`) = 'guild'    LIMIT 1);
SET @help_cat_id     := (SELECT `cat_id` FROM `f_categories` WHERE LOWER(`cat_name`) = 'help'     LIMIT 1);
SET @comments_cat_id := (SELECT `cat_id` FROM `f_categories` WHERE LOWER(`cat_name`) = 'comments' LIMIT 1);

UPDATE `f_categories` SET `cat_disp_position` = 1 WHERE `cat_id` = @news_cat_id;
UPDATE `f_categories` SET `cat_disp_position` = 2 WHERE `cat_id` = @general_cat_id;
UPDATE `f_categories` SET `cat_disp_position` = 3 WHERE `cat_id` = @guild_cat_id;
UPDATE `f_categories` SET `cat_disp_position` = 4 WHERE `cat_id` = @help_cat_id;
UPDATE `f_categories` SET `cat_disp_position` = 5 WHERE `cat_id` = @comments_cat_id;

-- ---------------------------------------------------------------
-- Forums (upsert via ON DUPLICATE KEY UPDATE).
-- forum_id layout:
--   1         = News (global)
--   2-4       = General  (Classic / TBC / WotLK)
--   5-7       = Guild    (Classic / TBC / WotLK)
--   8-10      = Comments (Classic / TBC / WotLK) — hidden
-- ---------------------------------------------------------------
INSERT INTO `f_forums` (
  `forum_id`, `scope_type`, `scope_value`, `forum_name`, `forum_desc`,
  `num_topics`, `num_posts`, `last_topic_id`, `disp_position`, `cat_id`,
  `quick_reply`, `hidden`, `closed`
) VALUES
  (1,  'all',              NULL, 'News',                 'Official website announcements and release notes.',                                       0, 0, 0, 1, @news_cat_id,     0, 1, 0)
ON DUPLICATE KEY UPDATE
  `scope_type` = VALUES(`scope_type`), `scope_value` = VALUES(`scope_value`),
  `forum_name` = VALUES(`forum_name`), `forum_desc`  = VALUES(`forum_desc`),
  `disp_position` = VALUES(`disp_position`), `cat_id` = VALUES(`cat_id`),
  `quick_reply` = VALUES(`quick_reply`), `hidden` = VALUES(`hidden`), `closed` = VALUES(`closed`);

-- General forums (only insert if the realm exists).
INSERT INTO `f_forums` (
  `forum_id`, `scope_type`, `scope_value`, `forum_name`, `forum_desc`,
  `num_topics`, `num_posts`, `last_topic_id`, `disp_position`, `cat_id`,
  `quick_reply`, `hidden`, `closed`
)
SELECT 2, 'realm', '1', 'Classic',                'General discussion and updates for the Classic realm.',                     0, 0, 0, 1, @general_cat_id, 1, 0, 0 FROM DUAL WHERE @has_classic = 1
UNION ALL
SELECT 3, 'realm', '2', 'The Burning Crusade',    'General discussion and updates for The Burning Crusade realm.',             0, 0, 0, 2, @general_cat_id, 1, 0, 0 FROM DUAL WHERE @has_tbc     = 1
UNION ALL
SELECT 4, 'realm', '3', 'Wrath of the Lich King', 'General discussion and updates for the Wrath of the Lich King realm.',     0, 0, 0, 3, @general_cat_id, 1, 0, 0 FROM DUAL WHERE @has_wotlk   = 1
ON DUPLICATE KEY UPDATE
  `scope_type` = VALUES(`scope_type`), `scope_value` = VALUES(`scope_value`),
  `forum_name` = VALUES(`forum_name`), `forum_desc`  = VALUES(`forum_desc`),
  `disp_position` = VALUES(`disp_position`), `cat_id` = VALUES(`cat_id`),
  `quick_reply` = VALUES(`quick_reply`), `hidden` = VALUES(`hidden`), `closed` = VALUES(`closed`);

-- Guild recruitment forums.
INSERT INTO `f_forums` (
  `forum_id`, `scope_type`, `scope_value`, `forum_name`, `forum_desc`,
  `num_topics`, `num_posts`, `last_topic_id`, `disp_position`, `cat_id`,
  `quick_reply`, `hidden`, `closed`
)
SELECT 5, 'guild_recruitment', '1', 'Classic',                'Guild recruitment and guild-focused posts for the Classic realm.',                 0, 0, 0, 1, @guild_cat_id, 1, 0, 0 FROM DUAL WHERE @has_classic = 1
UNION ALL
SELECT 6, 'guild_recruitment', '2', 'The Burning Crusade',    'Guild recruitment and guild-focused posts for The Burning Crusade realm.',         0, 0, 0, 2, @guild_cat_id, 1, 0, 0 FROM DUAL WHERE @has_tbc     = 1
UNION ALL
SELECT 7, 'guild_recruitment', '3', 'Wrath of the Lich King', 'Guild recruitment and guild-focused posts for the Wrath of the Lich King realm.', 0, 0, 0, 3, @guild_cat_id, 1, 0, 0 FROM DUAL WHERE @has_wotlk   = 1
ON DUPLICATE KEY UPDATE
  `scope_type` = VALUES(`scope_type`), `scope_value` = VALUES(`scope_value`),
  `forum_name` = VALUES(`forum_name`), `forum_desc`  = VALUES(`forum_desc`),
  `disp_position` = VALUES(`disp_position`), `cat_id` = VALUES(`cat_id`),
  `quick_reply` = VALUES(`quick_reply`), `hidden` = VALUES(`hidden`), `closed` = VALUES(`closed`);

-- Hidden comment forums.
INSERT INTO `f_forums` (
  `forum_id`, `scope_type`, `scope_value`, `forum_name`, `forum_desc`,
  `num_topics`, `num_posts`, `last_topic_id`, `disp_position`, `cat_id`,
  `quick_reply`, `hidden`, `closed`
)
SELECT  8, 'realm', '1', 'Classic',                'Hidden comment threads for Classic item, set, and page discussions.',                     0, 0, 0, 1, @comments_cat_id, 0, 1, 0 FROM DUAL WHERE @has_classic = 1
UNION ALL
SELECT  9, 'realm', '2', 'The Burning Crusade',    'Hidden comment threads for The Burning Crusade item, set, and page discussions.',         0, 0, 0, 2, @comments_cat_id, 0, 1, 0 FROM DUAL WHERE @has_tbc     = 1
UNION ALL
SELECT 10, 'realm', '3', 'Wrath of the Lich King', 'Hidden comment threads for the Wrath of the Lich King item, set, and page discussions.', 0, 0, 0, 3, @comments_cat_id, 0, 1, 0 FROM DUAL WHERE @has_wotlk   = 1
ON DUPLICATE KEY UPDATE
  `scope_type` = VALUES(`scope_type`), `scope_value` = VALUES(`scope_value`),
  `forum_name` = VALUES(`forum_name`), `forum_desc`  = VALUES(`forum_desc`),
  `disp_position` = VALUES(`disp_position`), `cat_id` = VALUES(`cat_id`),
  `quick_reply` = VALUES(`quick_reply`), `hidden` = VALUES(`hidden`), `closed` = VALUES(`closed`);

-- Help forums (reserved forum IDs 11-12).
INSERT INTO `f_forums` (
  `forum_id`, `scope_type`, `scope_value`, `forum_name`, `forum_desc`,
  `num_topics`, `num_posts`, `last_topic_id`, `disp_position`, `cat_id`,
  `quick_reply`, `hidden`, `closed`
) VALUES
  (11, 'all', NULL, 'Help / FAQ',          'Start here for connection help, website basics, account tools, and common player questions.', 0, 0, 0, 1, @help_cat_id, 0, 0, 1),
  (12, 'all', NULL, 'Admin / Staff Guide', 'Hidden internal operating notes for admins, moderators, and staff workflows.',                 0, 0, 0, 2, @help_cat_id, 0, 1, 1)
ON DUPLICATE KEY UPDATE
  `scope_type` = VALUES(`scope_type`), `scope_value` = VALUES(`scope_value`),
  `forum_name` = VALUES(`forum_name`), `forum_desc`  = VALUES(`forum_desc`),
  `disp_position` = VALUES(`disp_position`), `cat_id` = VALUES(`cat_id`),
  `quick_reply` = VALUES(`quick_reply`), `hidden` = VALUES(`hidden`), `closed` = VALUES(`closed`);

-- Hide/close forums for realms that don't exist.
UPDATE `f_forums` SET `hidden` = 1, `closed` = 1 WHERE `forum_id` =  2 AND @has_classic = 0;
UPDATE `f_forums` SET `hidden` = 1, `closed` = 1 WHERE `forum_id` =  3 AND @has_tbc     = 0;
UPDATE `f_forums` SET `hidden` = 1, `closed` = 1 WHERE `forum_id` =  4 AND @has_wotlk   = 0;
UPDATE `f_forums` SET `hidden` = 1, `closed` = 1 WHERE `forum_id` =  5 AND @has_classic = 0;
UPDATE `f_forums` SET `hidden` = 1, `closed` = 1 WHERE `forum_id` =  6 AND @has_tbc     = 0;
UPDATE `f_forums` SET `hidden` = 1, `closed` = 1 WHERE `forum_id` =  7 AND @has_wotlk   = 0;
UPDATE `f_forums` SET `hidden` = 1, `closed` = 1 WHERE `forum_id` =  8 AND @has_classic = 0;
UPDATE `f_forums` SET `hidden` = 1, `closed` = 1 WHERE `forum_id` =  9 AND @has_tbc     = 0;
UPDATE `f_forums` SET `hidden` = 1, `closed` = 1 WHERE `forum_id` = 10 AND @has_wotlk   = 0;

-- ---------------------------------------------------------------
-- Reset News forum: wipe existing topics/posts, insert fresh ones.
-- ---------------------------------------------------------------
DELETE p
FROM `f_posts` p
INNER JOIN `f_topics` t ON t.`topic_id` = p.`topic_id`
WHERE t.`forum_id` = 1;

DELETE FROM `f_topics` WHERE `forum_id` = 1;

-- Remove existing seeded sticky topics before recreating them.
DELETE p
FROM `f_posts` p
INNER JOIN `f_topics` t ON t.`topic_id` = p.`topic_id`
WHERE (t.`forum_id` IN (1, 2, 3, 4) AND t.`topic_name` IN (@welcome_subject, @rules_subject))
   OR (t.`forum_id` IN (5, 6, 7) AND t.`topic_name` = @guild_format_subject);

DELETE FROM `f_topics`
WHERE (`forum_id` IN (1, 2, 3, 4) AND `topic_name` IN (@welcome_subject, @rules_subject))
   OR (`forum_id` IN (5, 6, 7) AND `topic_name` = @guild_format_subject);

DELETE p
FROM `f_posts` p
INNER JOIN `f_topics` t ON t.`topic_id` = p.`topic_id`
WHERE (t.`forum_id` = 11 AND t.`topic_name` IN (
        @help_welcome_subject,
        @help_connect_subject,
        @help_features_subject,
        @help_bot_subject,
        @help_guild_subject,
        @help_account_subject
      ))
   OR (t.`forum_id` = 12 AND t.`topic_name` IN (
        @staff_panel_subject,
        @staff_forum_subject,
        @staff_realm_subject,
        @staff_ops_subject,
        @staff_schema_subject
      ));

DELETE FROM `f_topics`
WHERE (`forum_id` = 11 AND `topic_name` IN (
        @help_welcome_subject,
        @help_connect_subject,
        @help_features_subject,
        @help_bot_subject,
        @help_guild_subject,
        @help_account_subject
      ))
   OR (`forum_id` = 12 AND `topic_name` IN (
        @staff_panel_subject,
        @staff_forum_subject,
        @staff_realm_subject,
        @staff_ops_subject,
        @staff_schema_subject
      ));

INSERT INTO `f_topics` (
  `topic_poster`, `topic_poster_id`, `topic_name`, `topic_posted`, `last_post`,
  `last_post_id`, `last_poster`, `num_views`, `num_replies`, `closed`,
  `sticky`, `redirect_url`, `forum_id`
) VALUES (
  @topic_author, @topic_author_id, @news_subject, @reset_time, @reset_time,
  0, @topic_author, 1, 0, 0, 1, NULL, 1
);

SET @news_topic_id := LAST_INSERT_ID();

INSERT INTO `f_posts` (
  `poster`, `poster_id`, `poster_ip`, `poster_character_id`, `message`,
  `posted`, `edited`, `edited_by`, `topic_id`
) VALUES (
  @topic_author, @topic_author_id, @topic_author_ip, 0, @news_message,
  @reset_time, NULL, NULL, @news_topic_id
);

SET @news_post_id := LAST_INSERT_ID();

UPDATE `f_topics`
SET `last_post`    = @reset_time,
    `last_post_id` = @news_post_id,
    `last_poster`  = @topic_author,
    `num_replies`  = 0
WHERE `topic_id` = @news_topic_id;

-- Sticky welcome topics in each active General forum.
INSERT INTO `f_topics` (
  `topic_poster`, `topic_poster_id`, `topic_name`, `topic_posted`, `last_post`,
  `last_post_id`, `last_poster`, `num_views`, `num_replies`, `closed`,
  `sticky`, `redirect_url`, `forum_id`
) 
SELECT @topic_author, @topic_author_id, @welcome_subject, @reset_time + 60, @reset_time + 60,
       0, @topic_author, 1, 0, 0, 1, NULL, 2 FROM DUAL WHERE @has_classic = 1
UNION ALL
SELECT @topic_author, @topic_author_id, @welcome_subject, @reset_time + 60, @reset_time + 60,
       0, @topic_author, 1, 0, 0, 1, NULL, 3 FROM DUAL WHERE @has_tbc = 1
UNION ALL
SELECT @topic_author, @topic_author_id, @welcome_subject, @reset_time + 60, @reset_time + 60,
       0, @topic_author, 1, 0, 0, 1, NULL, 4 FROM DUAL WHERE @has_wotlk = 1;

INSERT INTO `f_posts` (
  `poster`, `poster_id`, `poster_ip`, `poster_character_id`, `message`,
  `posted`, `edited`, `edited_by`, `topic_id`
) 
SELECT @topic_author, @topic_author_id, @topic_author_ip, 0, @welcome_message, @reset_time + 60, NULL, NULL, t.`topic_id`
FROM `f_topics` t
WHERE t.`topic_name` = @welcome_subject
  AND t.`forum_id` IN (
    SELECT 2 FROM DUAL WHERE @has_classic = 1
    UNION ALL
    SELECT 3 FROM DUAL WHERE @has_tbc = 1
    UNION ALL
    SELECT 4 FROM DUAL WHERE @has_wotlk = 1
  )
  AND t.`last_post_id` = 0;

UPDATE `f_topics` t
JOIN (
  SELECT `topic_id`, MAX(`post_id`) AS post_id
  FROM `f_posts`
  GROUP BY `topic_id`
) p ON p.`topic_id` = t.`topic_id`
SET t.`last_post` = @reset_time + 60,
    t.`last_post_id` = p.`post_id`,
    t.`last_poster` = @topic_author,
    t.`num_replies` = 0
WHERE t.`topic_name` = @welcome_subject
  AND t.`forum_id` IN (2, 3, 4);

-- Sticky rules topics in each active General forum.
INSERT INTO `f_topics` (
  `topic_poster`, `topic_poster_id`, `topic_name`, `topic_posted`, `last_post`,
  `last_post_id`, `last_poster`, `num_views`, `num_replies`, `closed`,
  `sticky`, `redirect_url`, `forum_id`
) 
SELECT @topic_author, @topic_author_id, @rules_subject, @reset_time + 120, @reset_time + 120,
       0, @topic_author, 1, 0, 1, 1, NULL, 2 FROM DUAL WHERE @has_classic = 1
UNION ALL
SELECT @topic_author, @topic_author_id, @rules_subject, @reset_time + 120, @reset_time + 120,
       0, @topic_author, 1, 0, 1, 1, NULL, 3 FROM DUAL WHERE @has_tbc = 1
UNION ALL
SELECT @topic_author, @topic_author_id, @rules_subject, @reset_time + 120, @reset_time + 120,
       0, @topic_author, 1, 0, 1, 1, NULL, 4 FROM DUAL WHERE @has_wotlk = 1;

INSERT INTO `f_posts` (
  `poster`, `poster_id`, `poster_ip`, `poster_character_id`, `message`,
  `posted`, `edited`, `edited_by`, `topic_id`
) 
SELECT @topic_author, @topic_author_id, @topic_author_ip, 0, @rules_message, @reset_time + 120, NULL, NULL, t.`topic_id`
FROM `f_topics` t
WHERE t.`topic_name` = @rules_subject
  AND t.`forum_id` IN (
    SELECT 2 FROM DUAL WHERE @has_classic = 1
    UNION ALL
    SELECT 3 FROM DUAL WHERE @has_tbc = 1
    UNION ALL
    SELECT 4 FROM DUAL WHERE @has_wotlk = 1
  )
  AND t.`last_post_id` = 0;

UPDATE `f_topics` t
JOIN (
  SELECT `topic_id`, MAX(`post_id`) AS post_id
  FROM `f_posts`
  GROUP BY `topic_id`
) p ON p.`topic_id` = t.`topic_id`
SET t.`last_post` = @reset_time + 120,
    t.`last_post_id` = p.`post_id`,
    t.`last_poster` = @topic_author,
    t.`num_replies` = 0
WHERE t.`topic_name` = @rules_subject
  AND t.`forum_id` IN (2, 3, 4);

-- Sticky guild recruitment format topic for each guild forum that exists.
INSERT INTO `f_topics` (
  `topic_poster`, `topic_poster_id`, `topic_name`, `topic_posted`, `last_post`,
  `last_post_id`, `last_poster`, `num_views`, `num_replies`, `closed`,
  `sticky`, `redirect_url`, `forum_id`
)
SELECT @topic_author, @topic_author_id, @guild_format_subject, @reset_time + 180, @reset_time + 180,
       0, @topic_author, 1, 0, 1, 1, NULL, 5 FROM DUAL WHERE @has_classic = 1
UNION ALL
SELECT @topic_author, @topic_author_id, @guild_format_subject, @reset_time + 180, @reset_time + 180,
       0, @topic_author, 1, 0, 1, 1, NULL, 6 FROM DUAL WHERE @has_tbc = 1
UNION ALL
SELECT @topic_author, @topic_author_id, @guild_format_subject, @reset_time + 180, @reset_time + 180,
       0, @topic_author, 1, 0, 1, 1, NULL, 7 FROM DUAL WHERE @has_wotlk = 1;

INSERT INTO `f_posts` (
  `poster`, `poster_id`, `poster_ip`, `poster_character_id`, `message`,
  `posted`, `edited`, `edited_by`, `topic_id`
)
SELECT @topic_author, @topic_author_id, @topic_author_ip, 0, @guild_format_message, @reset_time + 180, NULL, NULL, t.`topic_id`
FROM `f_topics` t
WHERE t.`topic_name` = @guild_format_subject
  AND t.`forum_id` IN (
    SELECT 5 FROM DUAL WHERE @has_classic = 1
    UNION ALL
    SELECT 6 FROM DUAL WHERE @has_tbc = 1
    UNION ALL
    SELECT 7 FROM DUAL WHERE @has_wotlk = 1
  )
  AND t.`last_post_id` = 0;

UPDATE `f_topics` t
JOIN (
  SELECT `topic_id`, MAX(`post_id`) AS post_id
  FROM `f_posts`
  GROUP BY `topic_id`
) p ON p.`topic_id` = t.`topic_id`
SET t.`last_post` = @reset_time + 180,
    t.`last_post_id` = p.`post_id`,
    t.`last_poster` = @topic_author,
    t.`num_replies` = 0
WHERE t.`topic_name` = @guild_format_subject
  AND t.`forum_id` IN (5, 6, 7);

-- Reserved seeded help topics:
--   forum 11 subjects = public Help / FAQ
--   forum 12 subjects = hidden Admin / Staff Guide
-- Topics refresh by forum + subject instead of fixed topic_id values to stay safe on existing installs.

-- Sticky public help topics.
INSERT INTO `f_topics` (
  `topic_poster`, `topic_poster_id`, `topic_name`, `topic_posted`, `last_post`,
  `last_post_id`, `last_poster`, `num_views`, `num_replies`, `closed`,
  `sticky`, `redirect_url`, `forum_id`
) VALUES
  (@topic_author, @topic_author_id, @help_welcome_subject,  @reset_time + 360, @reset_time + 360, 0, @topic_author, 1, 0, 1, 1, NULL, 11),
  (@topic_author, @topic_author_id, @help_connect_subject,  @reset_time + 350, @reset_time + 350, 0, @topic_author, 1, 0, 1, 1, NULL, 11),
  (@topic_author, @topic_author_id, @help_features_subject, @reset_time + 340, @reset_time + 340, 0, @topic_author, 1, 0, 1, 1, NULL, 11),
  (@topic_author, @topic_author_id, @help_bot_subject,      @reset_time + 330, @reset_time + 330, 0, @topic_author, 1, 0, 1, 1, NULL, 11),
  (@topic_author, @topic_author_id, @help_guild_subject,    @reset_time + 320, @reset_time + 320, 0, @topic_author, 1, 0, 1, 1, NULL, 11),
  (@topic_author, @topic_author_id, @help_account_subject,  @reset_time + 310, @reset_time + 310, 0, @topic_author, 1, 0, 1, 1, NULL, 11);

INSERT INTO `f_posts` (
  `poster`, `poster_id`, `poster_ip`, `poster_character_id`, `message`,
  `posted`, `edited`, `edited_by`, `topic_id`
)
SELECT @topic_author, @topic_author_id, @topic_author_ip, 0,
       CASE t.`topic_name`
         WHEN @help_welcome_subject  THEN @help_welcome_message
         WHEN @help_connect_subject  THEN @help_connect_message
         WHEN @help_features_subject THEN @help_features_message
         WHEN @help_bot_subject      THEN @help_bot_message
         WHEN @help_guild_subject    THEN @help_guild_message
         WHEN @help_account_subject  THEN @help_account_message
         ELSE ''
       END,
       t.`topic_posted`, NULL, NULL, t.`topic_id`
FROM `f_topics` t
WHERE t.`forum_id` = 11
  AND t.`topic_name` IN (
    @help_welcome_subject,
    @help_connect_subject,
    @help_features_subject,
    @help_bot_subject,
    @help_guild_subject,
    @help_account_subject
  )
  AND t.`last_post_id` = 0;

UPDATE `f_topics` t
JOIN (
  SELECT `topic_id`, MAX(`post_id`) AS post_id
  FROM `f_posts`
  GROUP BY `topic_id`
) p ON p.`topic_id` = t.`topic_id`
SET t.`last_post` = t.`topic_posted`,
    t.`last_post_id` = p.`post_id`,
    t.`last_poster` = @topic_author,
    t.`num_replies` = 0
WHERE t.`forum_id` = 11
  AND t.`topic_name` IN (
    @help_welcome_subject,
    @help_connect_subject,
    @help_features_subject,
    @help_bot_subject,
    @help_guild_subject,
    @help_account_subject
  );

-- Sticky hidden staff guide topics.
INSERT INTO `f_topics` (
  `topic_poster`, `topic_poster_id`, `topic_name`, `topic_posted`, `last_post`,
  `last_post_id`, `last_poster`, `num_views`, `num_replies`, `closed`,
  `sticky`, `redirect_url`, `forum_id`
) VALUES
  (@topic_author, @topic_author_id, @staff_panel_subject,  @reset_time + 460, @reset_time + 460, 0, @topic_author, 1, 0, 1, 1, NULL, 12),
  (@topic_author, @topic_author_id, @staff_forum_subject,  @reset_time + 450, @reset_time + 450, 0, @topic_author, 1, 0, 1, 1, NULL, 12),
  (@topic_author, @topic_author_id, @staff_realm_subject,  @reset_time + 440, @reset_time + 440, 0, @topic_author, 1, 0, 1, 1, NULL, 12),
  (@topic_author, @topic_author_id, @staff_ops_subject,    @reset_time + 430, @reset_time + 430, 0, @topic_author, 1, 0, 1, 1, NULL, 12),
  (@topic_author, @topic_author_id, @staff_schema_subject, @reset_time + 420, @reset_time + 420, 0, @topic_author, 1, 0, 1, 1, NULL, 12);

INSERT INTO `f_posts` (
  `poster`, `poster_id`, `poster_ip`, `poster_character_id`, `message`,
  `posted`, `edited`, `edited_by`, `topic_id`
)
SELECT @topic_author, @topic_author_id, @topic_author_ip, 0,
       CASE t.`topic_name`
         WHEN @staff_panel_subject  THEN @staff_panel_message
         WHEN @staff_forum_subject  THEN @staff_forum_message
         WHEN @staff_realm_subject  THEN @staff_realm_message
         WHEN @staff_ops_subject    THEN @staff_ops_message
         WHEN @staff_schema_subject THEN @staff_schema_message
         ELSE ''
       END,
       t.`topic_posted`, NULL, NULL, t.`topic_id`
FROM `f_topics` t
WHERE t.`forum_id` = 12
  AND t.`topic_name` IN (
    @staff_panel_subject,
    @staff_forum_subject,
    @staff_realm_subject,
    @staff_ops_subject,
    @staff_schema_subject
  )
  AND t.`last_post_id` = 0;

UPDATE `f_topics` t
JOIN (
  SELECT `topic_id`, MAX(`post_id`) AS post_id
  FROM `f_posts`
  GROUP BY `topic_id`
) p ON p.`topic_id` = t.`topic_id`
SET t.`last_post` = t.`topic_posted`,
    t.`last_post_id` = p.`post_id`,
    t.`last_poster` = @topic_author,
    t.`num_replies` = 0
WHERE t.`forum_id` = 12
  AND t.`topic_name` IN (
    @staff_panel_subject,
    @staff_forum_subject,
    @staff_realm_subject,
    @staff_ops_subject,
    @staff_schema_subject
  );

-- Recount forum stats for all seeded forums.
UPDATE `f_forums` f
JOIN (
  SELECT
    t.`forum_id`,
    COUNT(DISTINCT t.`topic_id`) AS topic_count,
    COUNT(p.`post_id`)           AS post_count,
    MAX(t.`topic_id`)            AS latest_topic_id
  FROM `f_topics` t
  LEFT JOIN `f_posts` p ON p.`topic_id` = t.`topic_id`
  WHERE t.`forum_id` BETWEEN 1 AND 12
  GROUP BY t.`forum_id`
) AS stats ON stats.`forum_id` = f.`forum_id`
SET
  f.`num_topics`    = stats.`topic_count`,
  f.`num_posts`     = stats.`post_count`,
  f.`last_topic_id` = stats.`latest_topic_id`
WHERE f.`forum_id` BETWEEN 1 AND 12;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
