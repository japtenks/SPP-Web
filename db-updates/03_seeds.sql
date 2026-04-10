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
-- Managed reset scope:
--   Resets the website-managed forum layout (forum IDs 1-12)
--   and recreates the default categories/forums/topics.
--   This is intended so fresh installs and re-seeds start from
--   a predictable base while leaving custom extra forums alone.
-- =============================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

SET @reset_time     := 1775494800; -- Apr 06 2026 12:00:00 America/Chicago
SET @topic_author   := 'SPP-Web Team';
SET @topic_author_id := 0;
SET @topic_author_ip := '::1';

SET @news_subject := 'SPP-Web Beta v0.2 Release';
SET @news_message := '[b]SPP-Web Beta v0.2 is now available.[/b]<br />\r\n<br />\r\nThis release is the first full beta pass of the rebuilt SPP website. The focus for v0.2 is making the site feel like a real realm hub instead of a loose collection of pages: cleaner presentation, more usable account and armory coverage, better admin/runtime handling, and a seeded forum structure that can act as the built-in handbook for the project.<br />\r\n<br />\r\n[b]Beta v0.2 highlights[/b]<br />\r\n- Reworked player-facing pages across the main site sections so the overall experience feels more consistent.<br />\r\n- Expanded realm tools, downloads, and utility coverage for everyday server use.<br />\r\n- Improved armory coverage across characters, guilds, honor, items, talents, and supporting views.<br />\r\n- Expanded account-side features such as profile handling, messaging, and preference support.<br />\r\n- Improved admin/runtime workflows around maintenance, moderation, and multi-realm use.<br />\r\n- Added seeded forum structure and chapter-style help topics so new installs start with usable built-in documentation instead of an empty forum shell.<br />\r\n<br />\r\n[b]What is new for setup and operators[/b]<br />\r\nThis beta leans harder into being maintainable. The install flow is better documented, the website-owned realmd patch and seed flow are cleaner, the handbook/forum seed is more intentional, and the site is easier to reason about on mixed local SPP environments. Multi-realm use, cache-clearing after updates, identity backfill, and patch order are all now treated as first-class setup concerns instead of tribal knowledge.<br />\r\n<br />\r\n[b]Important release notes[/b]<br />\r\n- This project is intended for local or LAN use by default.<br />\r\n- If you expose it outside your local network, securing the environment is your responsibility.<br />\r\n- Some advanced features still depend on SOAP, optional core support, or expansion-specific schema support.<br />\r\n- After a fresh install or major update, run the identity backfill and clear website cache if the site still appears stale.<br />\r\n<br />\r\n[b]Fresh install summary[/b]<br />\r\nApply the armory/world patch set to each supported armory install, apply the website-owned realmd patch, run the seeds if you want the default forum and handbook layout, bring the site up, run the identity backfill, and then clear cache if pages do not immediately reflect the update.<br />\r\n<br />\r\nPlease report website issues, polish requests, or missing content in the Website Issues section.';
SET @welcome_subject := 'Welcome to the Realm Hub';
SET @welcome_message := 'Welcome to the realm hub.<br />\r\n<br />\r\nThis website is meant to be the shared front door for the server. Instead of acting only as a page to glance at occasionally, it is intended to pull together announcements, realm activity, armory views, account tools, downloads, and discussion into one place that players and admins can keep returning to.<br />\r\n<br />\r\n[b]How to use the hub[/b]<br />\r\nIf you are new here, start with the latest News post so you know what build or release you are looking at. After that, the next stop depends on what you need: General forums for questions and realm discussion, Guild Recruitment if you are building a guild or looking for one, Help / FAQ if you want chapter-style guidance, and the account pages if you need profile or account-facing tools.<br />\r\n<br />\r\n[b]What players should expect[/b]<br />\r\nThe site should help you keep track of what is happening on your realm, inspect characters and guilds, browse realm data, and understand where to go for support or updates. If something looks incomplete, it may be a setup, cache, or realm-mapping issue rather than the feature being absent outright.<br />\r\n<br />\r\nHave fun, be respectful, and help make the realm feel alive.';
SET @legacy_welcome_subject := 'Welcome to the Server';
SET @legacy_welcome_classic_subject := 'Welcome to Classic discussion';
SET @legacy_welcome_tbc_subject := 'Welcome to Burning Crusade discussion';
SET @rules_subject := 'Server Rules and Conduct';
SET @rules_message := '[b]Community expectations[/b]<br />\r\nThis site is part of the realm environment, not a separate social space with different standards. Public posting, guild recruiting, support requests, and admin-facing forum use should all stay readable, respectful, and useful to the people sharing the same server.<br />\r\n<br />\r\n[b]Respect the community[/b]<br />\r\nNo harassment, hate speech, or repeated personal attacks. Keep public posts readable and constructive so people can actually use the forums as a reference and discussion space instead of a cleanup queue.<br />\r\n<br />\r\n[b]Play fair[/b]<br />\r\nDo not cheat, dupe, or abuse exploits for advantage. If you discover a serious bug or exploit, report it instead of turning it into a gameplay strategy. The site is meant to support the realm, not normalize abuse of it.<br />\r\n<br />\r\n[b]Use the right forum for the job[/b]<br />\r\nNews is for official announcements. General is for realm discussion and questions. Guild Recruitment is for active recruiting threads. Help / FAQ is for reference, not general chatter. Using the right place matters because some site features and automation depend on consistent forum structure.<br />\r\n<br />\r\n[b]Guild recruitment expectations[/b]<br />\r\nKeep one active thread per guild per realm. Update your existing thread instead of reposting duplicates. Use the pinned format example so players and tools can read your post consistently.<br />\r\n<br />\r\nStaff may edit, close, or remove posts that do not follow these rules, especially when a thread breaks structure that the site depends on.';
SET @guild_format_subject := 'Guild Recruitment Format Example';
SET @guild_format_message := 'This chapter exists so guild leaders do not have to guess what a useful recruitment post looks like. A good recruitment thread is easy for players to scan, easy for staff to moderate, and structured enough that site-side tooling can understand what realm and guild the post belongs to.<br />\r\n<br />\r\n[b]What a good recruitment thread should communicate[/b]<br />\r\nA reader should be able to tell who you are, what kind of guild you are running, what you are looking for, when your activity happens, and how to contact you without reading a wall of text. If those answers are missing, players tend to skip the thread and staff have less confidence that it is being maintained.<br />\r\n<br />\r\n[b]Recommended title[/b]<br />\r\n- &lt;Your Guild&gt; is Recruiting!<br />\r\n- &lt;Your Guild&gt; is Recruiting (Raiding / Progression)<br />\r\n<br />\r\n[b]Recommended body structure[/b]<br />\r\n- [b]&lt;Your Guild&gt;[/b] is recruiting new members.<br />\r\n- Focus: raiding, dungeons, leveling, PvP, crafting, or social play.<br />\r\n- Needs: tanks, healers, ranged DPS, melee DPS, or all roles.<br />\r\n- Activity window: list your normal play times.<br />\r\n- Loot, leadership, or behavior expectations: include anything applicants should know before joining.<br />\r\n- Contact: whisper [b]&lt;Leader Name&gt;[/b] in-game or list your preferred contact method.<br />\r\n<br />\r\n[b]Example[/b]<br />\r\n- [b]&lt;Ashen Vanguard&gt;[/b] is recruiting for a steady progression roster.<br />\r\n- Focus: organized dungeon groups now, building toward weekly raid nights.<br />\r\n- Needs: one tank, two healers, and reliable ranged DPS.<br />\r\n- Activity window: most guild activity starts after 7:00 PM server time.<br />\r\n- Contact: whisper [b]&lt;Captain Elra&gt;[/b] in-game.<br />\r\n<br />\r\nKeep one active thread per guild. When your needs change, update the existing thread instead of reposting a duplicate so your guild history and discussion stay in one place.';
SET @legacy_guild_classic_subject := 'SPP-Classic Guild Recruitment Rules & Template';
SET @legacy_guild_tbc_subject := 'SPP-Tbc Guild Recruitment Rules & Template';
SET @help_welcome_subject := 'Chapter 1: Start Here';
SET @help_welcome_message := '[b]What this handbook is for[/b]<br />\r\nThis forum is meant to act like a built-in handbook for the website. Instead of keeping all of the useful instructions only in the repo, these chapters are here so players and admins can understand what the site is for, how it is expected to behave, and where to look first when something seems off.<br />\r\n<br />\r\n[b]How to read it[/b]<br />\r\nIf you are a player, begin with How to Connect, then Interactive Pages / Calculators, then whichever chapter matches what you are trying to do. If you are an admin or host, jump early to Website Admin Quick Start, Install / Update Workflow, Windows Multi-Realm Setup, and Troubleshooting. Those chapters explain the environment assumptions that usually matter most on SPP-style installs.<br />\r\n<br />\r\n[b]What the site covers[/b]<br />\r\nThe website is meant to be the shared front end for news, realm status, armory views, account tools, downloads, and forum-backed community workflows. Some sections are player-facing, some are administrative, and some sit in the middle. That means a page looking incomplete can be caused by permissions, realm mapping, runtime settings, cache, or missing DB patches rather than just a bad template.<br />\r\n<br />\r\n[b]Before you open a support thread[/b]<br />\r\nRead the chapter that matches the feature you are trying to use. A lot of broken-looking behavior on older local stacks turns out to be wrong expansion settings, a missing patch step, missing SOAP support, or stale cached output after an update.';
SET @help_connect_subject := 'Chapter 2: How to Connect';
SET @help_connect_message := '[b]What this chapter covers[/b]<br />\r\nThis chapter is about understanding how the game client, the website, and the realm setup relate to each other. A login problem is not always a website problem. Sometimes it is client build mismatch, realmlist mismatch, or simply trying to log into a realm that is offline or configured differently than expected.<br />\r\n<br />\r\n[b]Normal player connection flow[/b]<br />\r\nCreate your website account first if your setup uses the website as the account hub. Then make sure you have the correct client build for the realm you want. Set your realmlist to the address shown on the connect page or in your server instructions. If the site exposes more than one realm, pay attention to which expansion and realm you are actually targeting before assuming the wrong server is broken.<br />\r\n<br />\r\n[b]If login fails[/b]<br />\r\nRecheck account name and password spelling first. Then confirm the game client expansion matches the realm. After that, verify the realmlist entry and the server address. On multi-realm installs, a very common mistake is thinking you are logging into one realm while your client or site selection is actually pointed at another.<br />\r\n<br />\r\n[b]What admins should check[/b]<br />\r\nExpansion, default realm ID, and DB names must match the active install. On multi-realm setups, each realm needs its own correct world, armory, character, and realmd mapping. If the site is using the wrong realm, start with the website-owned realmd runtime settings and realm definitions before chasing template issues.';
SET @help_features_subject := 'Chapter 3: Interactive Pages / Calculators';
SET @help_features_message := '[b]What this chapter covers[/b]<br />\r\nThis chapter is for the pages that behave more like tools than static reading. They let players compare builds, plan buffs, search command references, and inspect live realm-facing data without leaving the site.<br />\r\n<br />\r\n[b]Pages worth bookmarking[/b]<br />\r\n- [url=http://127.0.0.1/index.php?n=server&sub=talents]Talent Calculator[/url]: build talents for the selected realm or open a character talent profile view from the armory.<br />\r\n- [url=http://127.0.0.1/index.php?n=server&sub=wbuffbuilder]World Buff Builder[/url]: search world buffs, class presets, and buff package ideas before you log in.<br />\r\n- [url=http://127.0.0.1/index.php?n=server&sub=botcommands]Bot Guide[/url]: browse command groups, starter macros, and filterable bot command notes.<br />\r\n- [url=http://127.0.0.1/index.php?n=server&sub=playermap]Player Map[/url]: open the interactive atlas for realm-scoped player positioning when that data feed is enabled.<br />\r\n<br />\r\n[b]How to use these pages well[/b]<br />\r\nStart by confirming the selected realm, because most of these tools are realm-aware. Treat them as planning and reference surfaces first. They are best for checking options before you take action in-game, not for proving that a server-side action already succeeded.<br />\r\n<br />\r\n[b]If a tool looks empty[/b]<br />\r\nCheck the active realm, then confirm the related database or armory data exists for that realm. Interactive pages often look broken when the site is pointed at the wrong realm or when the supporting data set is missing rather than when the page template itself is wrong.';
SET @help_bot_subject := 'Chapter 4: Workshop / Live Data Pages';
SET @help_bot_message := '[b]What this chapter covers[/b]<br />\r\nWorkshop pages are the public utility pages that expose live or semi-live realm data. They are the quickest way to see what the server looks like right now without logging in and manually checking everything yourself.<br />\r\n<br />\r\n[b]Main workshop pages[/b]<br />\r\n- [url=http://127.0.0.1/index.php?n=server&sub=realmstatus]Realm Status[/url]: view server reachability, population signals, uptime, and progression snapshots by realm.<br />\r\n- [url=http://127.0.0.1/index.php?n=server&sub=statistic]Statistics[/url]: review broader realm activity and summary metrics.<br />\r\n- [url=http://127.0.0.1/index.php?n=server&sub=ah]Auction House[/url]: browse current listings and market activity for the active realm.<br />\r\n- [url=http://127.0.0.1/index.php?n=server&sub=downloads]Downloads[/url]: pick up client-side files, addons, or setup packages the server exposes.<br />\r\n- [url=http://127.0.0.1/index.php?n=server&sub=items]Armory Database[/url] and [url=http://127.0.0.1/index.php?n=server&sub=marketplace]Market Place[/url]: browse item and market-style reference data tied to the site database layer.<br />\r\n<br />\r\n[b]How to read live data safely[/b]<br />\r\nMost workshop pages trade perfect immediacy for speed and readability. Cache, background refresh timing, and realm availability can all affect what you see. If a number matters for admin work, verify the selected realm and refresh once before assuming the first value is final.<br />\r\n<br />\r\n[b]When values seem wrong[/b]<br />\r\nStart with realm selection, then cache, then realm mapping. On mixed installs, a page that looks stale is often still reading the wrong realm or older cached output.';
SET @help_guild_subject := 'Chapter 5: Guild Recruitment Guide';
SET @help_guild_message := '[b]What this chapter covers[/b]<br />\r\nGuild recruitment works best on this site when threads are consistent, maintained, and clearly tied to the right realm. This chapter explains the expectations behind that, while the pinned format example gives you the structure to copy from.<br />\r\n<br />\r\n[b]Before you post[/b]<br />\r\nUse the Guild Recruitment forum for your realm, not General. Keep one active thread per guild and update that thread instead of starting a fresh copy every time your needs change. That helps players track the life of the guild and helps staff or tooling avoid duplicate clutter.<br />\r\n<br />\r\n[b]What readers actually need from you[/b]<br />\r\nMost players want to know your focus, schedule, roles needed, and who to contact. If loot rules, leadership style, faction restrictions, or progression expectations matter, say so up front. The more clearly you explain what kind of guild you are running, the less time everyone wastes on mismatched applicants.<br />\r\n<br />\r\n[b]How this relates to the site[/b]<br />\r\nGuild threads are not just social posts. They can matter for moderation flow and guild-oriented automation. That is why keeping the thread in the right realm forum and using a predictable structure is worth the effort.';
SET @help_account_subject := 'Chapter 6: Account Features';
SET @help_account_message := '[b]What the account area is for[/b]<br />\r\nThe account area is where the site stops being only a public reference and starts becoming personalized. Profile details, avatar/signature handling, messaging, preferences, and account-linked views all depend on this area working correctly. On a healthy install, it helps bridge the gap between public realm data and the user actually logged into the website.<br />\r\n<br />\r\n[b]What you can expect to manage here[/b]<br />\r\nYou should be able to manage profile details, avatar, signature, and preferences, review characters tied to your account across supported realms, and use account pages for password or profile maintenance where the setup supports it. Some setups also rely on the site more heavily for account-related flows than others, so the exact experience depends on the environment.<br />\r\n<br />\r\n[b]Why account-linked pages sometimes look incomplete[/b]<br />\r\nSome forum actions depend on having a valid realm character loaded. Some moderation or admin controls only appear for eligible accounts. On fresh installs, the identity backfill is especially important because it connects account, character, and forum identity records in a way the newer site features expect. If those pages look incomplete right after setup, run the backfill before deciding the feature failed.';
SET @help_admin_subject := 'Chapter 7: Website Admin Quick Start';
SET @help_admin_message := '[b]What this chapter is for[/b]<br />\r\nThis is the first chapter an admin should read after the site is installed. Its job is to explain the assumptions the admin side makes so you do not treat every missing control as a code bug when it is often a setup, permission, or runtime issue instead.<br />\r\n<br />\r\n[b]Admin starting conditions[/b]<br />\r\nUse an admin-eligible account on the website. Confirm the website-owned realmd patch has been applied before expecting admin and forum features to work. Run the identity backfill after setup so account-linked features and moderation tools can resolve identities correctly. Enable SOAP if you want SOAP-backed admin actions. If you do not want permanent GM access tied to your normal play identity, keep your admin role on a separate account.<br />\r\n<br />\r\n[b]How to work safely[/b]<br />\r\nReview realm settings before changing anything realm-scoped. Re-test as a guest or non-staff account when you change permissions or visibility rules. Prefer forum tools, seeded references, and runtime settings over ad hoc DB edits where possible, because the site is increasingly expecting its own managed structures to remain coherent.<br />\r\n<br />\r\n[b]When something looks broken[/b]<br />\r\nCheck realm connectivity first. Then recheck expansion, default realm ID, and DB names. Then make sure the page is not only showing stale cache content. Finally, confirm your account actually has the permissions you expect. That sequence solves a surprising number of admin-side problems without touching code.';
SET @help_install_subject := 'Chapter 8: Install / Update Workflow';
SET @help_install_message := '[b]How to think about install order[/b]<br />\r\nThis site works best when you treat installation as a sequence instead of a file copy. The website code, the armory-side schema support, the website-owned realmd tables, and the identity/runtime data each solve a different part of the system. If one of those layers is skipped, the site can load while still appearing partially broken.<br />\r\n<br />\r\n[b]Base install sequence[/b]<br />\r\nStop the launcher or web service before replacing website files. Put the website files in place and keep a backup of local config first. Review `config/config-protected.local.php` if you are not using the default Classic-first layout. Apply the armory/world patch files to every supported armory install in the documented order. Apply `02_realmd_patch.sql` to the website-owned realmd database. Apply `03_seeds.sql` if you want the starter forum and demo content. Apply `04_populationdirector.sql` only if you want those population features. Then bring the site up and run the identity backfill.<br />\r\n<br />\r\n[b]What updates usually require[/b]<br />\r\nWhen updating, back up local config first and re-read the README for new patch steps. After code updates or reseeds, clear website cache files if the site still looks unchanged. On older SPP-style MySQL setups, keep SSL disabled for client-side SQL tooling when required, because otherwise even basic verification can fail before you get a useful error back.';
SET @help_multirealm_subject := 'Chapter 9: Windows Multi-Realm Setup';
SET @help_multirealm_message := '[b]How multi-realm actually works on this site[/b]<br />\r\nMulti-realm on this website is driven by the runtime realm definitions, not by magic auto-detection. Each configured realm can point at its own `*.world`, `*.armory`, `*.characters`, and `*realmd` databases. The website-owned realmd database acts as the shared authority DB for forum and runtime tables, while the per-realm mappings determine where realm-specific data is read from.<br />\r\n<br />\r\n[b]What this means for Windows SPP-style installs[/b]<br />\r\nIf you are running more than one realm, each realm ID needs to match the intended realmlist entry, and each mapping needs the right DB names. A realm can still appear on the site even if that world is offline, as long as the website still has a valid realm definition for it. That is useful, but it also means visible on the site does not always mean fully operational in the game server right now.<br />\r\n<br />\r\n[b]If only one realm shows up[/b]<br />\r\nCheck configured realm IDs, DB names for each mapping, the selected default realm, and the website-owned realmd runtime tables. Then clear cache. Those are the usual reasons a second realm appears missing even though the files and databases exist.<br />\r\n<br />\r\n[b]Mixed setups[/b]<br />\r\nThe default managed seed is intended for the shared website lanes such as Classic and TBC. If you want a separate vMaNGOS forum lane, add and maintain that lane manually from forum admin instead of expecting the default seed to create it.';
SET @help_troubleshoot_subject := 'Chapter 10: Troubleshooting / Common Fixes';
SET @help_troubleshoot_message := '[b]How to troubleshoot without wasting time[/b]<br />\r\nThe fastest way to troubleshoot this site is to work from the outside in. Start with the selected realm and basic runtime assumptions, then move to permissions and optional services, then move to cache, and only after that start assuming you have a code or schema bug. That order matches the kinds of issues this environment produces most often.<br />\r\n<br />\r\n[b]First checks[/b]<br />\r\nIf the site loads but data looks wrong, check expansion, default realm ID, and DB names first. If admin actions do not work, check SOAP and account permissions. If account-linked pages look incomplete, run the identity backfill. If forum or admin tables are missing, verify the realmd patch was applied to the correct website-owned realmd database. If MySQL client connections fail on this older stack, retry with SSL disabled.<br />\r\n<br />\r\n[b]Cache and Windows-specific issues[/b]<br />\r\nIf pages still show old content after updates or reseeds, clear website cache files and reload. On Windows installs, confirm Apache is pointing at the right `DocumentRoot`, add the default SPP PHP and MySQL binaries to your `PATH` if you rely on CLI tools, and stop the launcher or web service before replacing website files so you do not end up with a half-updated copy on disk.<br />\r\n<br />\r\n[b]When to escalate further[/b]<br />\r\nIf you have already checked runtime mapping, cache, patches, backfill, and permissions, then it is reasonable to start looking at logs, schema drift, or code-level regressions. Until then, the simpler explanation is usually the correct one on an SPP-style local stack.';
SET @staff_intro_subject := 'Staff Guide: Start Here';
SET @staff_intro_message := '[b]What this guide is for[/b]<br />\r\nThis hidden forum is the handbook for the current admin surface. The dashboard now has four main headings: Operations, Site Maintenance, Character Tools, and Bot Controls. The seeded staff topics should follow that same shape so a staff member can move from the card they clicked to the matching chapter without guessing.<br />\r\n<br />\r\n[b]How to use the guide[/b]<br />\r\nRead the chapter that matches the card you are working from, then use the final tool index topic for direct page links and quick reminders about what each page owns. Treat these chapters as a route map first and a policy guide second. They should help staff find the right page before they start improvising with direct SQL or old launcher-era habits.<br />\r\n<br />\r\n[b]Safe operating habits[/b]<br />\r\nConfirm the target realm before any realm-scoped change. Prefer reviewed website workflows over raw edits when a native page already exists. Re-test from a non-staff view after visibility or runtime changes. Clear cache after reseeds or major content resets when the site still looks stale.';
SET @staff_operations_subject := 'Staff Chapter 1: Operations';
SET @staff_operations_message := '[b]Primary page[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=operations]Operations Catalog[/url]<br />\r\n<br />\r\n[b]What this page controls[/b]<br />\r\nOperations is the reviewed control plane for launcher-style maintenance and destructive data work. It groups queued jobs by family, shows risk labels, renders previews, and leaves an audit trail instead of hiding heavy work behind silent one-click actions.<br />\r\n<br />\r\n[b]How to use it[/b]<br />\r\nStart here when the work is destructive, cleanup-oriented, or should be reviewed before execution. Read the operation summary, confirm the selected realm or cross-realm target, inspect the preview text, satisfy the confirmation phrase, and then queue the job. If a related page is listed as the source of truth, use that native page for the actual repair and treat Operations as the place that points you there.<br />\r\n<br />\r\n[b]What to watch for[/b]<br />\r\nA visible operation does not always mean it is executable in this checkout. Pay attention to the v1 status, delivery type, and any note about helper scripts or link-out ownership before assuming the workflow is ready.';
SET @staff_maintenance_subject := 'Staff Chapter 2: Site Maintenance';
SET @staff_maintenance_message := '[b]Main pages[/b]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=members]Members[/url]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=forum]Forum Admin[/url]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=news]News Editor[/url]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=realms]Realm Management[/url]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=identities]Identity and Data Health[/url]<br />\r\n<br />\r\n[b]What this section controls[/b]<br />\r\nThese pages own the day-to-day website surface: member accounts, forum structure, official news posts, `realmlist` records, and identity or pointer repair. This is the chapter for maintenance that changes how the website itself behaves or what data it believes is valid.<br />\r\n<br />\r\n[b]How to use it[/b]<br />\r\nChoose the page that already owns the data you are changing. Use Members for account-facing actions, Forum Admin for structure and moderation, News for front-page communication, Realms for actual realm records, and Identity/Data Health for backfills and pointer repair. If a page exists here, prefer it over manual SQL so the site keeps one clear source of truth.<br />\r\n<br />\r\n[b]Common mistakes to avoid[/b]<br />\r\nDo not use forum cleanup to remove seeded handbook structure unless you intend to replace it. Do not treat realm naming or address changes like a general text edit when Operations or Realm Management already owns the change path. After identity or forum repair work, verify results from a normal user view.';
SET @staff_chartools_subject := 'Staff Chapter 3: Character Tools';
SET @staff_chartools_message := '[b]Main pages[/b]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=chartools]Character Tools[/url]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=backup]Character Backup Export[/url]<br />\r\n<br />\r\n[b]What this section controls[/b]<br />\r\nCharacter Tools covers staff-side actions that affect live characters directly: rename, race or faction change, item/service delivery, and migration-prep export work. These are not cosmetic website edits; they change game-facing character state or build SQL meant for restoration or transfer work.<br />\r\n<br />\r\n[b]How to use it[/b]<br />\r\nConfirm the target realm and character first. Use Character Tools for focused service actions on existing characters. Use Character Backup Export when you need a copy-account style export for migration, restoration, or review before a larger move. Record what you changed when the action affects a real player account so later troubleshooting has context.<br />\r\n<br />\r\n[b]Safety notes[/b]<br />\r\nCharacter actions are easy to aim at the wrong realm on mixed installs. Double-check realm, character name, and the exact service before you submit anything. For bigger migration work, prefer exports and reviewed steps over ad hoc direct edits.';
SET @staff_botcontrols_subject := 'Staff Chapter 4: Bot Controls';
SET @staff_botcontrols_message := '[b]Main pages[/b]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=playerbots]Playerbots Control[/url]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=bots]Bot Maintenance[/url]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=botevents]Bot Events Pipeline[/url]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=botrotation]Bot Rotation Health[/url]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=populationdirector]Population Director[/url]<br />\r\n<br />\r\n[b]What this section controls[/b]<br />\r\nThese pages cover the automation-facing parts of the beta site: playerbot management, maintenance previews, forum-ready event processing, rotation freshness checks, and population guidance. They are the most likely admin pages to depend on helper scripts, background processing, PHP CLI, or realm support beyond simple page rendering.<br />\r\n<br />\r\n[b]How to use it[/b]<br />\r\nStart with the page that matches the question you are answering. Use Playerbots Control for the main bot admin surface, Bot Maintenance for reset or preservation previews, Bot Events for queueing event output, Bot Rotation Health for uptime and freshness signals, and Population Director before temporary override decisions. Review realm scope and helper requirements before treating a button or command hint as production-ready.<br />\r\n<br />\r\n[b]Troubleshooting order[/b]<br />\r\nCheck realm selection, account permissions, CLI or helper availability, and background run state before assuming the page logic is wrong. Bot-oriented pages often fail because the environment is only partially prepared.';
SET @staff_index_subject := 'Staff Chapter 5: Admin Tool Links and Subsections';
SET @staff_index_message := '[b]Direct tool index[/b]<br />\r\nThis topic is the quick-reference list for the admin dashboard cards and their linked pages.<br />\r\n<br />\r\n[b]Operations[/b]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=operations]Operations Catalog[/url]: reviewed queue for destructive, cleanup, and launcher-parity workflows with preview and audit history.<br />\r\n<br />\r\n[b]Site Maintenance[/b]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=members]Members[/url]: account actions, profile-linked edits, transfers, deletes, and member security controls.<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=forum]Forum Admin[/url]: categories, forums, moderation cleanup, and managed content structure.<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=news]News Editor[/url]: official homepage announcements and archive-facing editorial content.<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=realms]Realm Management[/url]: real `realmlist` records and realm-facing website definitions that should stay aligned with runtime mapping.<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=identities]Identity and Data Health[/url]: backfills, ownership vs speaking identity checks, stale pointer repair, and reset-scope review.<br />\r\n<br />\r\n[b]Character Tools[/b]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=chartools]Character Tools[/url]: rename, race or faction change, and send item packs or service actions to characters.<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=backup]Character Backup Export[/url]: create copy-account SQL exports for migration, restore prep, or manual review.<br />\r\n<br />\r\n[b]Bot Controls[/b]<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=playerbots]Playerbots Control[/url]: main playerbot admin surface and routing point for bot-facing tools.<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=bots]Bot Maintenance[/url]: inspect reset scope, preserved data, and helper-command guidance for maintenance paths.<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=botevents]Bot Events Pipeline[/url]: scan, queue, and process generated forum-ready event activity when PHP CLI is available.<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=botrotation]Bot Rotation Health[/url]: check freshness, uptime, and stalled-cycle signals before blaming gameplay behavior on the bots themselves.<br />\r\n- [url=http://127.0.0.1/index.php?n=admin&sub=populationdirector]Population Director[/url]: read live target recommendations and only then decide whether a temporary override is actually needed.<br />\r\n<br />\r\n[b]How to use this reference[/b]<br />\r\nIf you know the card but not the exact page, start here. If you know the page but not the policy around it, jump back to the matching chapter above.';

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
-- Reset website-managed forum layout.
-- Destroys website-managed forum/category content for forum IDs 1-12.
-- ---------------------------------------------------------------
DELETE p
FROM `f_posts` p
INNER JOIN `f_topics` t ON t.`topic_id` = p.`topic_id`
WHERE t.`forum_id` BETWEEN 1 AND 12;

DELETE FROM `f_topics`
WHERE `forum_id` BETWEEN 1 AND 12;

DELETE FROM `f_forums`
WHERE `forum_id` BETWEEN 1 AND 12;

DELETE FROM `f_categories`
WHERE LOWER(`cat_name`) IN ('news', 'general', 'guild', 'help', 'comments');

-- ---------------------------------------------------------------
-- Categories (recreated from scratch for the managed layout).
-- ---------------------------------------------------------------
INSERT INTO `f_categories` (`cat_name`, `cat_disp_position`) VALUES
('News', 1),
('General', 2),
('Guild', 3),
('Help', 4),
('Comments', 5);

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
-- Seed fresh managed topics/posts after the managed reset above.
-- ---------------------------------------------------------------
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

-- Keep dedicated homepage news in sync with the managed seeded news topic.
SET @has_website_news := EXISTS(
  SELECT 1
  FROM `information_schema`.`tables`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'website_news'
);
SET @sql_news_reset := IF(@has_website_news = 1, 'DELETE FROM `website_news`', 'SELECT 1');
PREPARE stmt_news_reset FROM @sql_news_reset;
EXECUTE stmt_news_reset;
DEALLOCATE PREPARE stmt_news_reset;

SET @sql_news_insert := IF(
  @has_website_news = 1,
  CONCAT(
    'INSERT INTO `website_news` ',
    '(`source_forum_topic_id`, `slug`, `title`, `excerpt`, `body`, `publisher_label`, `publisher_identity_id`, `created_by_account_id`, `is_published`, `published_at`) VALUES (',
    @news_topic_id, ', ',
    QUOTE('spp-web-beta-v0-2-release'), ', ',
    QUOTE(@news_subject), ', ',
    QUOTE(''), ', ',
    QUOTE(@news_message), ', ',
    QUOTE(@topic_author), ', NULL, 0, 1, ', @reset_time,
    ')'
  ),
  'SELECT 1'
);
PREPARE stmt_news_insert FROM @sql_news_insert;
EXECUTE stmt_news_insert;
DEALLOCATE PREPARE stmt_news_insert;

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
SELECT @topic_author, @topic_author_id, @topic_author_ip, 0,
       CONCAT(
         'Welcome to the realm hub.<br />\r\n',
         '<br />\r\n',
         'This website is meant to be the shared front door for the server. Instead of acting only as a page to glance at occasionally, it is intended to pull together announcements, realm activity, armory views, account tools, downloads, and discussion into one place that players and admins can keep returning to.<br />\r\n',
         '<br />\r\n',
         '[b]How to use the hub[/b]<br />\r\n',
         'If you are new here, start with the latest [url=http://127.0.0.1/index.php?n=news]News[/url] post so you know what build or release you are looking at. After that, the next stop depends on what you need: [url=http://127.0.0.1/index.php?n=forum&sub=viewforum&fid=', t.`forum_id`, ']General[/url] forums for questions and realm discussion, [url=http://127.0.0.1/index.php?n=forum&sub=viewforum&fid=', (t.`forum_id` + 3), ']Guild Recruitment[/url] if you are building a guild or looking for one, [url=http://127.0.0.1/index.php?n=forum&sub=viewtopic&tid=', @help_welcome_topic_id, ']Help / FAQ[/url] if you want chapter-style guidance, and [url=http://127.0.0.1/index.php?n=account&sub=manage]account pages[/url] if you need profile or account-facing tools.<br />\r\n',
         '<br />\r\n',
         '[b]What players should expect[/b]<br />\r\n',
         'The site should help you keep track of what is happening on your realm, inspect characters and guilds, browse realm data, and understand where to go for support or updates. If something looks incomplete, it may be a setup, cache, or realm-mapping issue rather than the feature being absent outright.<br />\r\n',
         '<br />\r\n',
         'Have fun, be respectful, and help make the realm feel alive.'
       ),
       @reset_time + 60, NULL, NULL, t.`topic_id`
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
  (@topic_author, @topic_author_id, @help_account_subject,  @reset_time + 310, @reset_time + 310, 0, @topic_author, 1, 0, 1, 1, NULL, 11),
  (@topic_author, @topic_author_id, @help_admin_subject,    @reset_time + 300, @reset_time + 300, 0, @topic_author, 1, 0, 1, 1, NULL, 11),
  (@topic_author, @topic_author_id, @help_install_subject,  @reset_time + 290, @reset_time + 290, 0, @topic_author, 1, 0, 1, 1, NULL, 11),
  (@topic_author, @topic_author_id, @help_multirealm_subject, @reset_time + 280, @reset_time + 280, 0, @topic_author, 1, 0, 1, 1, NULL, 11),
  (@topic_author, @topic_author_id, @help_troubleshoot_subject, @reset_time + 270, @reset_time + 270, 0, @topic_author, 1, 0, 1, 1, NULL, 11);

SET @help_welcome_topic_id      := (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = 11 AND `topic_name` = @help_welcome_subject LIMIT 1);
SET @help_connect_topic_id      := (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = 11 AND `topic_name` = @help_connect_subject LIMIT 1);
SET @help_features_topic_id     := (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = 11 AND `topic_name` = @help_features_subject LIMIT 1);
SET @help_account_topic_id      := (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = 11 AND `topic_name` = @help_account_subject LIMIT 1);
SET @help_admin_topic_id        := (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = 11 AND `topic_name` = @help_admin_subject LIMIT 1);
SET @help_install_topic_id      := (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = 11 AND `topic_name` = @help_install_subject LIMIT 1);
SET @help_multirealm_topic_id   := (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = 11 AND `topic_name` = @help_multirealm_subject LIMIT 1);
SET @help_troubleshoot_topic_id := (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = 11 AND `topic_name` = @help_troubleshoot_subject LIMIT 1);

INSERT INTO `f_posts` (
  `poster`, `poster_id`, `poster_ip`, `poster_character_id`, `message`,
  `posted`, `edited`, `edited_by`, `topic_id`
)
SELECT @topic_author, @topic_author_id, @topic_author_ip, 0,
       CASE t.`topic_name`
         WHEN @help_welcome_subject  THEN CONCAT(
           '[b]What this handbook is for[/b]<br />\r\n',
           'This forum is meant to act like a built-in handbook for the website. Instead of keeping all of the useful instructions only in the repo, these chapters are here so players and admins can understand what the site is for, how it is expected to behave, and where to look first when something seems off.<br />\r\n',
           '<br />\r\n',
           '[b]How to read it[/b]<br />\r\n',
           'If you are a player, begin with [url=http://127.0.0.1/index.php?n=forum&sub=viewtopic&tid=', @help_connect_topic_id, ']How to Connect[/url], then [url=http://127.0.0.1/index.php?n=forum&sub=viewtopic&tid=', @help_features_topic_id, ']Interactive Pages / Calculators[/url], then whichever chapter matches what you are trying to do. If you are an admin or host, jump early to [url=http://127.0.0.1/index.php?n=forum&sub=viewtopic&tid=', @help_admin_topic_id, ']Website Admin Quick Start[/url], [url=http://127.0.0.1/index.php?n=forum&sub=viewtopic&tid=', @help_install_topic_id, ']Install / Update Workflow[/url], [url=http://127.0.0.1/index.php?n=forum&sub=viewtopic&tid=', @help_multirealm_topic_id, ']Windows Multi-Realm Setup[/url], and [url=http://127.0.0.1/index.php?n=forum&sub=viewtopic&tid=', @help_troubleshoot_topic_id, ']Troubleshooting[/url]. Those chapters explain the environment assumptions that usually matter most on SPP-style installs.<br />\r\n',
           '<br />\r\n',
           '[b]What the site covers[/b]<br />\r\n',
           'The website is meant to be the shared front end for news, realm status, armory views, account tools, downloads, and forum-backed community workflows. Some sections are player-facing, some are administrative, and some sit in the middle. That means a page looking incomplete can be caused by permissions, realm mapping, runtime settings, cache, or missing DB patches rather than just a bad template.<br />\r\n',
           '<br />\r\n',
           '[b]Before you open a support thread[/b]<br />\r\n',
           'Read the chapter that matches the feature you are trying to use. A lot of broken-looking behavior on older local stacks turns out to be wrong expansion settings, a missing patch step, missing SOAP support, or stale cached output after an update.'
         )
         WHEN @help_connect_subject  THEN @help_connect_message
         WHEN @help_features_subject THEN @help_features_message
         WHEN @help_bot_subject      THEN @help_bot_message
         WHEN @help_guild_subject    THEN @help_guild_message
         WHEN @help_account_subject  THEN @help_account_message
         WHEN @help_admin_subject    THEN @help_admin_message
         WHEN @help_install_subject  THEN @help_install_message
         WHEN @help_multirealm_subject THEN @help_multirealm_message
         WHEN @help_troubleshoot_subject THEN @help_troubleshoot_message
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
    @help_account_subject,
    @help_admin_subject,
    @help_install_subject,
    @help_multirealm_subject,
    @help_troubleshoot_subject
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
    @help_account_subject,
    @help_admin_subject,
    @help_install_subject,
    @help_multirealm_subject,
    @help_troubleshoot_subject
  );

-- Sticky hidden staff guide topics.
INSERT INTO `f_topics` (
  `topic_poster`, `topic_poster_id`, `topic_name`, `topic_posted`, `last_post`,
  `last_post_id`, `last_poster`, `num_views`, `num_replies`, `closed`,
  `sticky`, `redirect_url`, `forum_id`
) VALUES
  (@topic_author, @topic_author_id, @staff_operations_subject,  @reset_time + 460, @reset_time + 460, 0, @topic_author, 1, 0, 1, 1, NULL, 12),
  (@topic_author, @topic_author_id, @staff_maintenance_subject, @reset_time + 450, @reset_time + 450, 0, @topic_author, 1, 0, 1, 1, NULL, 12),
  (@topic_author, @topic_author_id, @staff_chartools_subject,   @reset_time + 440, @reset_time + 440, 0, @topic_author, 1, 0, 1, 1, NULL, 12),
  (@topic_author, @topic_author_id, @staff_botcontrols_subject, @reset_time + 430, @reset_time + 430, 0, @topic_author, 1, 0, 1, 1, NULL, 12);

INSERT INTO `f_posts` (
  `poster`, `poster_id`, `poster_ip`, `poster_character_id`, `message`,
  `posted`, `edited`, `edited_by`, `topic_id`
)
SELECT @topic_author, @topic_author_id, @topic_author_ip, 0, p.`message`,
       t.`topic_posted` + p.`reply_offset`, NULL, NULL, t.`topic_id`
FROM `f_topics` t
JOIN (
  SELECT @staff_operations_subject AS `topic_name`, 0 AS `reply_offset`, @staff_operations_message AS `message`
  UNION ALL
  SELECT @staff_operations_subject, 1,
    '[b]Operations Catalog[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=operations]http://127.0.0.1/index.php?n=admin&sub=operations[/url]<br />\r\n<br />\r\nThis page is the reviewed queue for destructive or cleanup work. Use it when the task needs previews, confirmation phrases, risk labels, or a job history instead of an immediate hidden action.<br />\r\n<br />\r\n[b]How to use it[/b]<br />\r\nPick the correct operation family, confirm realm scope, read the preview, and only then queue the job. If the operation says a native admin page owns the repair, follow that link instead of forcing the change from here.'
  UNION ALL
  SELECT @staff_maintenance_subject, 0, @staff_maintenance_message
  UNION ALL
  SELECT @staff_maintenance_subject, 1,
    '[b]Members[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=members]http://127.0.0.1/index.php?n=admin&sub=members[/url]<br />\r\n<br />\r\nUse this page for account-facing admin work: profile-linked edits, transfers, deletes, bot profile work, and member security controls. If the change belongs to a website account or member record, start here first.'
  UNION ALL
  SELECT @staff_maintenance_subject, 2,
    '[b]Forum Admin[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=forum]http://127.0.0.1/index.php?n=admin&sub=forum[/url]<br />\r\n<br />\r\nThis page owns categories, forums, moderation cleanup, and content structure. Use it to keep the forum layout healthy, but avoid deleting seeded handbook lanes unless you are intentionally replacing them.'
  UNION ALL
  SELECT @staff_maintenance_subject, 3,
    '[b]News Editor[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=news]http://127.0.0.1/index.php?n=admin&sub=news[/url]<br />\r\n<br />\r\nThis page publishes official homepage news and archive-facing announcements. Use it for release notes, maintenance notices, and front-page communication rather than burying those updates in forum posts.'
  UNION ALL
  SELECT @staff_maintenance_subject, 4,
    '[b]Realm Management[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=realms]http://127.0.0.1/index.php?n=admin&sub=realms[/url]<br />\r\n<br />\r\nThis page manages the real `realmlist` records the site reads from. Use it for realm definitions and visibility checks, but route reviewed rename or address maintenance through the supported workflow when Operations owns that path.'
  UNION ALL
  SELECT @staff_maintenance_subject, 5,
    '[b]Identity and Data Health[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=identities]http://127.0.0.1/index.php?n=admin&sub=identities[/url]<br />\r\n<br />\r\nUse this page for identity backfills, pointer repair, ownership-versus-speaking identity checks, and reset-scope review. If account-linked pages or forum identities look broken after setup, this is one of the first places to verify.'
  UNION ALL
  SELECT @staff_chartools_subject, 0, @staff_chartools_message
  UNION ALL
  SELECT @staff_chartools_subject, 1,
    '[b]Character Tools[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=chartools]http://127.0.0.1/index.php?n=admin&sub=chartools[/url]<br />\r\n<br />\r\nThis page handles direct character services such as rename, race change, faction change, and item pack delivery. Always confirm the target realm and character before submitting a live service action.'
  UNION ALL
  SELECT @staff_chartools_subject, 2,
    '[b]Character Backup Export[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=backup]http://127.0.0.1/index.php?n=admin&sub=backup[/url]<br />\r\n<br />\r\nUse this page when you need copy-account SQL exports for migration, restore prep, or manual review. Prefer an export-first workflow for larger moves instead of making broad direct edits against live character data.'
  UNION ALL
  SELECT @staff_botcontrols_subject, 0, @staff_botcontrols_message
  UNION ALL
  SELECT @staff_botcontrols_subject, 1,
    '[b]Playerbots Control[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=playerbots]http://127.0.0.1/index.php?n=admin&sub=playerbots[/url]<br />\r\n<br />\r\nThis is the main playerbot admin surface. Use it for approved bot-facing tools and as the jump point into the rest of the bot-control workflow.'
  UNION ALL
  SELECT @staff_botcontrols_subject, 2,
    '[b]Bot Maintenance[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=bots]http://127.0.0.1/index.php?n=admin&sub=bots[/url]<br />\r\n<br />\r\nUse this page to preview reset scope, inspect preserved data, and read helper-command guidance. It is for maintenance review, not blind deletion.'
  UNION ALL
  SELECT @staff_botcontrols_subject, 3,
    '[b]Bot Events Pipeline[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=botevents]http://127.0.0.1/index.php?n=admin&sub=botevents[/url]<br />\r\n<br />\r\nThis page scans, queues, and processes forum-ready bot event activity when PHP CLI support is available. Check environment readiness before assuming an empty queue means nothing happened.'
  UNION ALL
  SELECT @staff_botcontrols_subject, 4,
    '[b]Bot Rotation Health[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=botrotation]http://127.0.0.1/index.php?n=admin&sub=botrotation[/url]<br />\r\n<br />\r\nUse this page to inspect freshness, uptime, and stalled-cycle signals. It helps separate actual bot-runtime issues from normal gameplay variance or stale assumptions.'
  UNION ALL
  SELECT @staff_botcontrols_subject, 5,
    '[b]Population Director[/b]<br />\r\n[url=http://127.0.0.1/index.php?n=admin&sub=populationdirector]http://127.0.0.1/index.php?n=admin&sub=populationdirector[/url]<br />\r\n<br />\r\nThis page shows live target observations and recommendations before you use temporary override controls. Read the recommendation first so overrides stay intentional and short-lived.'
) p ON p.`topic_name` = t.`topic_name`
WHERE t.`forum_id` = 12
  AND t.`topic_name` IN (
    @staff_operations_subject,
    @staff_maintenance_subject,
    @staff_chartools_subject,
    @staff_botcontrols_subject
  )
  AND t.`last_post_id` = 0;

UPDATE `f_topics` t
JOIN (
  SELECT `topic_id`, MAX(`post_id`) AS post_id, COUNT(*) AS post_count, MAX(`posted`) AS last_posted
  FROM `f_posts`
  GROUP BY `topic_id`
) p ON p.`topic_id` = t.`topic_id`
SET t.`last_post` = p.`last_posted`,
    t.`last_post_id` = p.`post_id`,
    t.`last_poster` = @topic_author,
    t.`num_replies` = GREATEST(p.`post_count` - 1, 0)
WHERE t.`forum_id` = 12
  AND t.`topic_name` IN (
    @staff_operations_subject,
    @staff_maintenance_subject,
    @staff_chartools_subject,
    @staff_botcontrols_subject
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
