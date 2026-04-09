# spp-web

Beta release of the redesigned and modernized website for SPP-based installs.

Supported targets:

- Windows SPP release: [celguar/spp-classics-cmangos](https://github.com/celguar/spp-classics-cmangos)
- Proxmox shell-port build: [japtenks/spp-cmangos-prox](https://github.com/japtenks/spp-cmangos-prox)

## Installation

You can install this site by cloning the repository or by downloading the release ZIP.

1. Stop the launcher before replacing the existing website files.
2. Back up the current site folder if needed.
   Example: rename `Server\website` to `website-bkup`.
3. Extract or clone this repo into the `website` folder.
4. If you are using the default SPP settings, the site should work with little or no config change.
5. If your environment is customized, update the protected local config file to match your setup.
   Check `config/config-protected.local.php` and the example file in the same folder.
6. In `mangos.conf`, update these values near the end of the file:

```ini
Console.Enable = 1
Ra.Enable = 1
SOAP.Enabled = 1
```

7. Make sure the database server is running and reachable from the website.
8. From the `website` folder, run the sql file classicrealmd and seeds:

After the patches are applied, the website should be available for guest access.

## Installation On Proxmox

For the Proxmox shell-port workflow:

1. Change into the Proxmox project folder:

```bash
cd spp-cmangos-prox/
```

2. Update the repo if needed:

```bash
git pull
```

3. Open the service menu.
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

After the site is up, visit [http://127.0.0.1/index.php?n=admin&sub=identities](http://127.0.0.1/index.php?n=admin&sub=identities) and run the identity backfill.

## Runtime And Tooling Notes

`admin/botevents` is supported only when PHP CLI is available in the target environment, such as a Windows SPP install with portable PHP on `PATH`.

`admin/bots` and `admin/botrotation` are available as Windows-safe admin surfaces for previewing maintenance scope and rotation health. Legacy helper commands are shown conditionally when their local tool scripts are present.

Useful scripts in `tools/` include:

- `process_bot_events.php` to process bot event data
- `scan_bot_events.php` to scan and inspect bot event inputs

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
