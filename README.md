# SPP-Web

Beta release of the redesigned and modernized website for SPP-based installs.

Current packaged release: `Beta v0.2`

Supported targets:

- Windows SPP release: [celguar/spp-classics-cmangos](https://github.com/celguar/spp-classics-cmangos)
- Proxmox shell-port build: [japtenks/spp-cmangos-prox](https://github.com/japtenks/spp-cmangos-prox)

## **Local Network Use Notice**

This project is intended for local/LAN use.

If you plan to host it outside your local network, it is your responsibility to review and secure your setup properly. The default environment is based on HTTP and older Apache-style local hosting assumptions, so it should not be treated as hardened for public internet exposure out of the box.

The project does include security-focused cleanup and modernization work compared to the older site code, including:

- centralized CSRF protection on mutating actions
- safer form field filtering and request guards
- improved forum posting validation and repeat-post protection
- more secure attachment and avatar file handling
- PDO/prepared-statement migration across major database access paths
- SRP-based account password flow updates in supported account paths


## Installation

You can install this site by cloning the repository or by downloading the release ZIP.

1. Stop the launcher before replacing the existing website files.
2. Back up the current site folder if needed.
   Example: rename `Server\website` to `website-bkup`.
3. Extract or clone this repo into the `website` folder.
4. Review your runtime config before first launch.
   Default config values are Classic-first. If your install is TBC or WotLK, create and update `config/config-protected.local.php` to match your setup.

   Start from `config/config-protected.local.php.example`.

   Update these values to match your install:
   - Expansion: `0=Classic`, `1=TBC`, `2=WOTLK`
   - `default_realm_id`
   - Database names for `realmd`, `world`, `chars`, and `armory`
   - Database host/port if your MySQL service is not using the default SPP values
   - `clientConnectionHost` for the address players should use in `realmlist.wtf`
   - SOAP port/credentials if you plan to use SOAP-backed admin features

   `default_realm_id` must match the realm/realmlist entry your site should use.
   `clientConnectionHost` is the value the website uses for the generated `realmlist.wtf` downloads and the on-page `set realmlist ...` instructions shown to players.
   
```
    'genericRuntime' => [
        'expansion' => 1,
    ],
    'realmRuntime' => [
        'default_realm_id' => 1,
    ],
    'clientConnectionHost' => '192.168.1.42',
    'realmDbMap' => [
        1 => [
            'realmd' => 'tbcrealmd',
            'world' => 'tbcmangos',
            'chars' => 'tbccharacters',
            'armory' => 'tbcarmory',
```
   
5. In `mangos.conf`, make sure SOAP is enabled:

```ini
SOAP.Enabled = 1
```

   Optional depending on your setup:

   - `Ra.Enable = 1` if you use RA-backed features or tooling
   - `Console.Enable = 1` if your environment depends on console access or console-driven controls

6. Make sure the database server is running and reachable from the website.
   Current live DB reference for the standard install:
   - Host: `127.0.0.1`
   - Port: `3310`
   - User: `root`
   - Password: `123456`

   It is recommended to update these values from the standard default setup.
7. From the `website` folder, run the SQL updates:
   - Apply the armory/world-side patch files in `db-updates/01_armory_world_patch` in this order:
     - `10_dbc_spellicon_delta.sql`
     - `20_dbc_spellitemenchantment_tooltip.sql`
     - `30_dbc_talent_tooltip.sql`
     - `40_dbc_talenttab_tooltip.sql`
     - `50_armory_itemset_notes.sql`
   - Apply those armory/world-side patch files to every armory/world install you want the website to support
   - Apply `db-updates/02_realmd_patch.sql` to the website-owned `realmd` database
   - `db-updates/03_seeds.sql`
   - `db-updates/04_populationdirector.sql` if you want the population director features
   - `db-updates/05_personality_history.sql` if you want external polling snapshots for personality drift history

After the patches are applied, the website should be available for guest access.

## Windows PATH Guidance

Some website tools and patch workflows work best when the default SPP PHP and MySQL binaries are available on `PATH`.

For a default SPP install, you can use:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\add_spp_tools_to_path.ps1
```

That helper adds the usual SPP tool locations to your user `PATH`:

- `Tools\php7`
- `Database\bin`

Notes:

- `php` on `PATH` is useful for website helper scripts such as identity backfill and bot event processing
- `mysql` on `PATH` is useful if you want to run the SQL patch files from a terminal instead of importing them manually
- Open a new PowerShell window after running the helper if you want the updated `PATH` outside the current session

## Installation On Proxmox

For the Proxmox shell-port workflow:

1. Change into the Proxmox project folder:

```bash
cd spp-cmangos-prox/
```

2. Start the launcher:

```bash
./Launcher.sh
```
3. If an update is required, open the service menu.
4. Choose `3` for `website`.
5. Choose `2` for `update`.

## Accounts And Admin Access

If you do not already have an account, create one through the database or the SPP launcher tools.

For admin tasks:

- Use an admin account if you want website administration features.
- If you do not want permanent GM access on your character, keep that separate from your normal play account.
- If you plan to use SOAP-backed features, create or configure a SOAP-enabled account for that purpose.
- Full SOAP support currently requires a recompile with this change:
  [japtenks/mangos-classic commit `bd5c6c1`](https://github.com/japtenks/mangos-classic/commit/bd5c6c10d2a308c2666a9e9a4f196151c7097abf)
- A pull request has been issued for that work.

## First Launch Checklist

- Confirm `config/config-protected.local.php` matches your expansion, realm ID, and DB names
- Confirm the armory/world patch files and `02_realmd_patch.sql` were applied
- Confirm the site opens and guest pages load
- Run the identity backfill from the admin identities page

## Runtime And Tooling Notes

`admin/botevents` is supported only when PHP CLI is available in the target environment, such as a Windows SPP install with portable PHP on `PATH`.

`admin/bots` and `admin/botrotation` are available as Windows-safe admin surfaces for previewing maintenance scope and rotation health. Legacy helper commands are shown conditionally when their local tool scripts are present.

Useful scripts in `tools/` include:

- `process_bot_events.php` to process bot event data
- `scan_bot_events.php` to scan and inspect bot event inputs
- `snapshot_personality_history.php` to poll `ai_playerbot_personality` into the website-owned history table for drift-over-time analysis

## Windows SPP Notes And Troubleshooting

- If the site loads but shows the wrong realm, missing characters, or empty armory data, check `expansion`, `default_realm_id`, database names, and MySQL port first.
- If guest pages work but admin actions do not, verify SOAP is enabled and the configured SOAP port and credentials are correct.
- If pages render strangely after replacing the site, stop the launcher and clear generated cache/log files before trying again.
- If you replaced `Server\website` while the launcher or bundled web server was still running, restart the stack and re-check the copied files.
- `admin/botevents` requires PHP CLI support in the Windows environment; without it, that admin surface is not available.
- If you are using only a TBC install, you should expect to use a local config override rather than relying on the Classic defaults.

## Windows Multi-Realm Notes

- If your Windows setup includes more than one world, such as Classic and TBC, add each realm to `realmDbMap` in `config/config-protected.local.php`.
- Give each realm the correct `realmd`, `world`, `chars`, and `armory` database names, and make sure each entry uses the correct realm ID from `realmlist`.
- In SPP-Win naming, that usually means mapping each realm entry to its matching `*.world`, `*.armory`, `*.characters`, and `*realmd` databases.
- Set `realmRuntime.multirealm` to `1` if you want the website to expose more than one configured realm.
- Each `realmDbMap` entry is resolved independently, so the website will use the `realmd`, `world`, `chars`, and `armory` names defined for that specific realm ID rather than forcing every realm through one shared world or armory database.
- The site can still list a configured realm even when that world is offline, as long as the realm is present in your runtime config and enabled in the runtime realm settings.
- For shared website tables and runtime settings, apply `02_realmd_patch.sql` to the realmd database you want the website to treat as its website-owned authority DB.
- If only one realm appears, check the configured realm IDs, the runtime enabled realm list, and whether the matching `realmlist` rows exist in the website-owned `realmd` database.

## Showcase
<!-- SHOWCASE_GALLERY_START -->
<p align="center">

<img width="31%" alt="SPP Web showcase 01" src="./showcase/processed/showcase-01.png">

<img width="31%" alt="SPP Web showcase 02" src="./showcase/processed/showcase-02.png">

<img width="31%" alt="SPP Web showcase 03" src="./showcase/processed/showcase-03.png">

<img width="31%" alt="SPP Web showcase 04" src="./showcase/processed/showcase-04.png">

<img width="31%" alt="SPP Web showcase 05" src="./showcase/processed/showcase-05.png">

<img width="31%" alt="SPP Web showcase 06" src="./showcase/processed/showcase-06.png">

</p>

<details>
<summary>Open full screenshot gallery (35 images)</summary>

<p><img width="900" alt="SPP Web showcase 01" src="./showcase/processed/showcase-01.png"></p>

<p><img width="900" alt="SPP Web showcase 02" src="./showcase/processed/showcase-02.png"></p>

<p><img width="900" alt="SPP Web showcase 03" src="./showcase/processed/showcase-03.png"></p>

<p><img width="900" alt="SPP Web showcase 04" src="./showcase/processed/showcase-04.png"></p>

<p><img width="900" alt="SPP Web showcase 05" src="./showcase/processed/showcase-05.png"></p>

<p><img width="900" alt="SPP Web showcase 06" src="./showcase/processed/showcase-06.png"></p>

<p><img width="900" alt="SPP Web showcase 07" src="./showcase/processed/showcase-07.png"></p>

<p><img width="900" alt="SPP Web showcase 08" src="./showcase/processed/showcase-08.png"></p>

<p><img width="900" alt="SPP Web showcase 09" src="./showcase/processed/showcase-09.png"></p>

<p><img width="900" alt="SPP Web showcase 10" src="./showcase/processed/showcase-10.png"></p>

<p><img width="900" alt="SPP Web showcase 11" src="./showcase/processed/showcase-11.png"></p>

<p><img width="900" alt="SPP Web showcase 12" src="./showcase/processed/showcase-12.png"></p>

<p><img width="900" alt="SPP Web showcase 13" src="./showcase/processed/showcase-13.png"></p>

<p><img width="900" alt="SPP Web showcase 14" src="./showcase/processed/showcase-14.png"></p>

<p><img width="900" alt="SPP Web showcase 15" src="./showcase/processed/showcase-15.png"></p>

<p><img width="900" alt="SPP Web showcase 16" src="./showcase/processed/showcase-16.png"></p>

<p><img width="900" alt="SPP Web showcase 17" src="./showcase/processed/showcase-17.png"></p>

<p><img width="900" alt="SPP Web showcase 18" src="./showcase/processed/showcase-18.png"></p>

<p><img width="900" alt="SPP Web showcase 19" src="./showcase/processed/showcase-19.png"></p>

<p><img width="900" alt="SPP Web showcase 20" src="./showcase/processed/showcase-20.png"></p>

<p><img width="900" alt="SPP Web showcase 21" src="./showcase/processed/showcase-21.png"></p>

<p><img width="900" alt="SPP Web showcase 22" src="./showcase/processed/showcase-22.png"></p>

<p><img width="900" alt="SPP Web showcase 23" src="./showcase/processed/showcase-23.png"></p>

<p><img width="900" alt="SPP Web showcase 24" src="./showcase/processed/showcase-24.png"></p>

<p><img width="900" alt="SPP Web showcase 25" src="./showcase/processed/showcase-25.png"></p>

<p><img width="900" alt="SPP Web showcase 26" src="./showcase/processed/showcase-26.png"></p>

<p><img width="900" alt="SPP Web showcase 27" src="./showcase/processed/showcase-27.png"></p>

<p><img width="900" alt="SPP Web showcase 28" src="./showcase/processed/showcase-28.png"></p>

<p><img width="900" alt="SPP Web showcase 29" src="./showcase/processed/showcase-29.png"></p>

<p><img width="900" alt="SPP Web showcase 30" src="./showcase/processed/showcase-30.png"></p>

<p><img width="900" alt="SPP Web showcase 31" src="./showcase/processed/showcase-31.png"></p>

<p><img width="900" alt="SPP Web showcase 32" src="./showcase/processed/showcase-32.png"></p>

<p><img width="900" alt="SPP Web showcase 33" src="./showcase/processed/showcase-33.png"></p>

<p><img width="900" alt="SPP Web showcase 34" src="./showcase/processed/showcase-34.png"></p>

<p><img width="900" alt="SPP Web showcase 35" src="./showcase/processed/showcase-35.png"></p>

</details>
<!-- SHOWCASE_GALLERY_END -->
