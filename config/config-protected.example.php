<?php

return [
    'appTimezone' => 'America/Chicago',
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3310,
        'user' => 'root',
        'pass' => '123456',
    ],
    'clientConnectionHost' => '127.0.0.1',
    'serviceDefaults' => [
        'soap' => [
            'port' => 7878,
            'user' => '',
            'pass' => '',
        ],
    ],
    'adminRuntime' => [
        'viewlogs' => [
            'enabled' => 0,
            'gmlog_path' => '',
        ],
    ],
    'genericRuntime' => [
        'expansion' => 0,
        'copyright' => 'All Images and Logos are copyright 2025 Blizzard Entertainment',
        'display_banner_flash' => 0,
        'use_archaeic_dbinfo_format' => 0,
        'use_alternate_mangosdb_port' => 0,
        'use_local_ip_port_test' => 1,
        'account_key_retain_length' => 1209600,
        'cache_expiretime' => 0,
        'use_purepass_table' => 0,
        'onlinelist_on' => 1,
        'req_reg_invite' => 0,
        'site_register' => 1,
        'posts_per_page' => 25,
        'topics_per_page' => 16,
        'users_per_page' => 40,
        'ahitems_per_page' => 100,
        'imageautoresize' => '500x500',
        'avatar_path' => 'uploads/avatars/',
        'default_component' => 'frontpage',
        'max_avatar_file' => 102400,
        'max_avatar_size' => '64x64',
        'site_cookie' => 'sppArmory',
        'site_title' => 'SPP-Web Beta - v0.1',
        'smiles_path' => 'images/smiles/',
    ],
    'armoryRuntime' => [
        'locales' => 0,
    ],
    'realmRuntime' => [
        'default_realm_id' => 1,
        'multirealm' => 0,
    ],
    'forumRuntime' => [
        'news_forum_id' => 1,
        'bugs_forum_id' => 5,
        'ql4_forum_id' => 6,
        'externalforum' => 0,
        'frame_forum' => 0,
        'forum_external_link' => '',
        'externalbugstracker' => 1,
        'frame_bugstracker' => 0,
        'bugstracker_external_link' => 'https://github.com/celguar/spp-classics-cmangos/issues',
        'faqsite_external_use' => 0,
        'faqsite_external_link' => '0',
    ],
    'realmDbMap' => [
        1 => [
            'realmd' => 'classicrealmd',
            'world' => 'classicmangos',
            'chars' => 'classiccharacters',
            'armory' => 'classicarmory',
            'bots' => 'classicplayerbots',
        ],
        2 => [
            'realmd' => 'classicrealmd',
            'world' => 'tbcmangos',
            'chars' => 'tbccharacters',
            'armory' => 'tbcarmory',
            'bots' => 'tbcplayerbots',
        ],
    ],
];

