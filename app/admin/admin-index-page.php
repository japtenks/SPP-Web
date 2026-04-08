<?php

if (!function_exists('spp_admin_load_index_page_state')) {
    function spp_admin_load_index_page_state(): array
    {
        $siteBuildRuntime = $GLOBALS['siteBuildRuntime'] ?? array();
        $siteBuildCommit = (string)($siteBuildRuntime['commit'] ?? 'unknown');
        $siteBuildDate = (string)($siteBuildRuntime['date'] ?? 'unknown');

        $siteBuildStatus = array();
        $isLinux = defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY === 'Linux' : stripos(PHP_OS, 'Linux') !== false;
        if ($isLinux && $siteBuildCommit !== '' && $siteBuildCommit !== 'unknown' && $siteBuildDate !== '' && $siteBuildDate !== 'unknown') {
            $siteBuildStatus = array(
                'label' => 'SPP-Web Build',
                'commit' => $siteBuildCommit,
                'date' => $siteBuildDate,
            );
        }

        return array(
            'intro' => array(
                'eyebrow' => 'Control Center',
                'title' => 'MangosWeb Enhanced Admin',
                'body' => 'Use these tools to manage members, forums, realms, and launcher-parity operations from one admin surface.',
            ),
            'site_build_status' => $siteBuildStatus,
            'sections' => array(
                array(
                    'title' => 'Operations',
                    'description' => 'Queue reviewed jobs, inspect previews, and centralize launcher-style workflows without hiding risk.',
                    'links' => array(
                        array(
                            'label' => 'Operations Catalog',
                            'href' => 'index.php?n=admin&sub=operations',
                            'description' => 'Browse native, SQL-backed, and external-tool operations with risk labels, previews, and audit history.',
                        ),
                    ),
                ),
                array(
                    'title' => 'Site Maintenance',
                    'description' => 'Core admin work for members, realm configuration, forum upkeep, and identity health.',
                    'links' => array(
                        array(
                            'label' => 'Members',
                            'href' => 'index.php?n=admin&sub=members',
                            'description' => 'Account management, bot profile edits, transfers, deletes, and security controls.',
                        ),
                        array(
                            'label' => 'Forum Admin',
                            'href' => 'index.php?n=admin&sub=forum',
                            'description' => 'Categories, forums, moderation cleanup, and content structure.',
                        ),
                        array(
                            'label' => 'News Editor',
                            'href' => 'index.php?n=admin&sub=news',
                            'description' => 'Publish official homepage news with dedicated editorial identities and its own front-end archive.',
                        ),
                        array(
                            'label' => 'Realm Management',
                            'href' => 'index.php?n=admin&sub=realms',
                            'description' => 'Real `realmlist` records only. Rename/address changes now route through Operations for reviewed execution.',
                        ),
                        array(
                            'label' => 'Identity & Data Health',
                            'href' => 'index.php?n=admin&sub=identities',
                            'description' => 'Inspect ownership vs speaking identities, run backfills, repair stale website pointers, and review reset scope in one merged health page.',
                        ),
                    ),
                ),
                array(
                    'title' => 'Character Tools',
                    'description' => 'Admin-side character operations, migration prep, and service actions that affect live characters.',
                    'links' => array(
                        array(
                            'label' => 'Character Tools',
                            'href' => 'index.php?n=admin&sub=chartools',
                            'description' => 'Rename, race or faction change, and send item packs to characters.',
                        ),
                        array(
                            'label' => 'Character Backup Export',
                            'href' => 'index.php?n=admin&sub=backup',
                            'description' => 'Build copy-account SQL exports for migration or restoration workflows.',
                        ),
                        array(
                            'label' => 'Character Transfer',
                            'href' => 'index.php?n=admin&sub=chartransfer',
                            'description' => 'Run schema-aware dry-run validation for Classic/TBC character transfer planning without executing live data moves.',
                        ),
                    ),
                ),
                array(
                    'title' => 'Bot Controls',
                    'description' => 'Automation-facing tools approved for the beta website surface.',
                    'links' => array(
                        array(
                            'label' => 'Playerbots Control',
                            'href' => 'index.php?n=admin&sub=playerbots',
                            'description' => 'Manage playerbot tools and jump into the dedicated playerbots admin surface.',
                        ),
                        array(
                            'label' => 'Bot Events Pipeline',
                            'href' => 'index.php?n=admin&sub=botevents',
                            'description' => 'Scan, queue, and process generated forum-ready bot event activity when PHP CLI is available.',
                        ),
                    ),
                ),
            ),
        );
    }
}

