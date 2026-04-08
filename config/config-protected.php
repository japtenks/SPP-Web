<?php

require_once __DIR__ . '/bootstrap.php';

if (!function_exists('spp_detect_git_runtime')) {
    function spp_detect_git_runtime(string $repoRoot): array
    {
        $runtime = [
            'branch' => 'unknown',
            'commit' => 'unknown',
            'date' => 'unknown',
        ];

        if (!is_dir($repoRoot . DIRECTORY_SEPARATOR . '.git')) {
            return $runtime;
        }

        $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
        $execDisabled = in_array('exec', $disabled, true);
        if ($execDisabled) {
            return $runtime;
        }

        $branchOutput = [];
        $commitOutput = [];
        $dateOutput = [];
        $branchCode = 1;
        $commitCode = 1;
        $dateCode = 1;
        $repoArg = escapeshellarg($repoRoot);

        exec("git -C {$repoArg} rev-parse --abbrev-ref HEAD 2>/dev/null", $branchOutput, $branchCode);
        exec("git -C {$repoArg} rev-parse --short=12 HEAD 2>/dev/null", $commitOutput, $commitCode);
        exec("git -C {$repoArg} log -1 --date=short --format=%cd 2>/dev/null", $dateOutput, $dateCode);

        if ($branchCode === 0 && !empty($branchOutput[0])) {
            $runtime['branch'] = trim((string)$branchOutput[0]);
        }
        if ($commitCode === 0 && !empty($commitOutput[0])) {
            $runtime['commit'] = trim((string)$commitOutput[0]);
        }
        if ($dateCode === 0 && !empty($dateOutput[0])) {
            $runtime['date'] = trim((string)$dateOutput[0]);
        }

        return $runtime;
    }
}

$exampleConfig = spp_load_config_array(__DIR__ . '/config-protected.example.php');
$localConfig = spp_load_config_array(__DIR__ . '/config-protected.local.php');
$config = spp_merge_config_arrays($exampleConfig, $localConfig);

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$scriptDir = str_replace('\\', '/', dirname($scriptName));
$defaultSiteHref = preg_replace('#/+#', '/', $scriptDir . '/');
if ($defaultSiteHref === '' || $defaultSiteHref === '.' || $defaultSiteHref === './') {
    $defaultSiteHref = '/';
}
if ($defaultSiteHref !== '' && $defaultSiteHref[0] !== '/') {
    $defaultSiteHref = '/' . ltrim($defaultSiteHref, '/');
}

$defaultHost = (string)($_SERVER['HTTP_HOST'] ?? '127.0.0.1');
$requestScheme = 'http';
if ((!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || (string)($_SERVER['SERVER_PORT'] ?? '') === '443') {
    $requestScheme = 'https';
}

$appTimezone = (string)spp_env('SPP_APP_TIMEZONE', (string)($config['appTimezone'] ?? 'America/Chicago'));
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set($appTimezone);
}

$siteHref = (string)spp_env('SPP_SITE_HREF', (string)($config['temp']['site_href'] ?? $defaultSiteHref));
if ($siteHref === '') {
    $siteHref = $defaultSiteHref;
}
if ($siteHref !== '/' && substr($siteHref, -1) !== '/') {
    $siteHref .= '/';
}
if ($siteHref[0] !== '/') {
    $siteHref = '/' . ltrim($siteHref, '/');
}

$serverHost = (string)spp_env('SPP_SITE_DOMAIN', (string)($config['temp']['site_domain'] ?? $defaultHost));
if ($serverHost === '') {
    $serverHost = $defaultHost;
}

$emailHref = (string)spp_env('SPP_EMAIL_HREF', (string)($config['temp']['email_href'] ?? $serverHost));
if ($emailHref === '') {
    $emailHref = $serverHost;
}

$baseHref = (string)spp_env('SPP_BASE_HREF', (string)($config['temp']['base_href'] ?? ($requestScheme . '://' . $serverHost . $siteHref)));
if ($baseHref === '') {
    $baseHref = $requestScheme . '://' . $serverHost . $siteHref;
}

$tempRuntime = [
    'site_href' => $siteHref,
    'site_domain' => $serverHost,
    'email_href' => $emailHref,
    'base_href' => $baseHref,
];

$db = [
    'host' => (string)spp_env('SPP_DB_HOST', (string)($config['db']['host'] ?? '127.0.0.1')),
    'port' => (int)spp_env('SPP_DB_PORT', $config['db']['port'] ?? 3310),
    'user' => (string)spp_env('SPP_DB_USER', (string)($config['db']['user'] ?? 'root')),
    'pass' => (string)spp_env('SPP_DB_PASS', (string)($config['db']['pass'] ?? '123456')),
];

$clientConnectionHost = (string)spp_env('SPP_CLIENT_CONNECTION_HOST', (string)($config['clientConnectionHost'] ?? $db['host']));

$serviceDefaults = [
    'soap' => [
        'port' => (int)spp_env('SPP_SOAP_PORT', $config['serviceDefaults']['soap']['port'] ?? 7878),
        'user' => (string)spp_env('SPP_SOAP_USER', (string)($config['serviceDefaults']['soap']['user'] ?? 'replace_me')),
        'pass' => (string)spp_env('SPP_SOAP_PASS', (string)($config['serviceDefaults']['soap']['pass'] ?? 'replace_me')),
    ],
];

$templateNames = $config['templates']['template'] ?? $config['templates']['available'] ?? array('offlike');
if (!is_array($templateNames)) {
    $templateNames = array($templateNames);
}
$templateNames = array_values(array_unique(array_filter(array_map('strval', $templateNames), static function ($templateName) {
    return trim((string)$templateName) !== '';
})));
if (empty($templateNames)) {
    $templateNames = array('offlike');
}

$templateName = (string)spp_env(
    'SPP_TEMPLATE',
    (string)($config['templates']['selected'] ?? $config['template'] ?? $templateNames[0])
);
if ($templateName === '') {
    $templateName = $templateNames[0];
}
if (!in_array($templateName, $templateNames, true)) {
    array_unshift($templateNames, $templateName);
    $templateNames = array_values(array_unique($templateNames));
}

$adminRuntime = [
    'viewlogs' => [
        'enabled' => (int)spp_env('SPP_VIEWLOGS_ENABLED', $config['adminRuntime']['viewlogs']['enabled'] ?? 0),
        'gmlog_path' => (string)spp_env('SPP_GMLOG_PATH', (string)($config['adminRuntime']['viewlogs']['gmlog_path'] ?? '')),
    ],
];

$genericRuntime = [
    'expansion' => (int)spp_env('SPP_EXPANSION', $config['genericRuntime']['expansion'] ?? 0),
    'copyright' => (string)($config['genericRuntime']['copyright'] ?? 'All Images and Logos are copyright 2025 Blizzard Entertainment'),
    'display_banner_flash' => (int)spp_env('SPP_DISPLAY_BANNER_FLASH', $config['genericRuntime']['display_banner_flash'] ?? 0),
    'use_archaeic_dbinfo_format' => (int)spp_env('SPP_USE_ARCHAEIC_DBINFO_FORMAT', $config['genericRuntime']['use_archaeic_dbinfo_format'] ?? 0),
    'use_alternate_mangosdb_port' => (int)spp_env('SPP_USE_ALTERNATE_MANGOSDB_PORT', $config['genericRuntime']['use_alternate_mangosdb_port'] ?? 0),
    'use_local_ip_port_test' => (int)spp_env('SPP_USE_LOCAL_IP_PORT_TEST', $config['genericRuntime']['use_local_ip_port_test'] ?? 1),
    'account_key_retain_length' => (int)spp_env('SPP_ACCOUNT_KEY_RETAIN_LENGTH', $config['genericRuntime']['account_key_retain_length'] ?? 1209600),
    'cache_expiretime' => (int)spp_env('SPP_CACHE_EXPIRETIME', $config['genericRuntime']['cache_expiretime'] ?? 0),
    'use_purepass_table' => (int)spp_env('SPP_USE_PUREPASS_TABLE', $config['genericRuntime']['use_purepass_table'] ?? 0),
    'onlinelist_on' => (int)spp_env('SPP_ONLINELIST_ON', $config['genericRuntime']['onlinelist_on'] ?? 1),
    'req_reg_invite' => (int)spp_env('SPP_REQ_REG_INVITE', $config['genericRuntime']['req_reg_invite'] ?? 0),
    'site_register' => (int)spp_env('SPP_SITE_REGISTER', $config['genericRuntime']['site_register'] ?? 1),
    'posts_per_page' => (int)spp_env('SPP_POSTS_PER_PAGE', $config['genericRuntime']['posts_per_page'] ?? 25),
    'topics_per_page' => (int)spp_env('SPP_TOPICS_PER_PAGE', $config['genericRuntime']['topics_per_page'] ?? 16),
    'users_per_page' => (int)spp_env('SPP_USERS_PER_PAGE', $config['genericRuntime']['users_per_page'] ?? 40),
    'ahitems_per_page' => (int)spp_env('SPP_AHITEMS_PER_PAGE', $config['genericRuntime']['ahitems_per_page'] ?? 100),
    'imageautoresize' => (string)($config['genericRuntime']['imageautoresize'] ?? '500x500'),
    'avatar_path' => (string)($config['genericRuntime']['avatar_path'] ?? 'images/avatars/'),
    'default_component' => (string)($config['genericRuntime']['default_component'] ?? 'frontpage'),
    'max_avatar_file' => (int)spp_env('SPP_MAX_AVATAR_FILE', $config['genericRuntime']['max_avatar_file'] ?? 102400),
    'max_avatar_size' => (string)($config['genericRuntime']['max_avatar_size'] ?? '64x64'),
    'site_cookie' => (string)spp_env('SPP_SITE_COOKIE', (string)($config['genericRuntime']['site_cookie'] ?? 'sppArmory')),
    'site_title' => (string)($config['genericRuntime']['site_title'] ?? 'World of Warcraft'),
    'smiles_path' => (string)($config['genericRuntime']['smiles_path'] ?? 'images/smiles/'),
];

$armoryRuntime = [
    'locales' => (int)spp_env('SPP_ARMORY_LOCALES', $config['armoryRuntime']['locales'] ?? 0),
];

$realmRuntime = [
    'default_realm_id' => (int)spp_env('SPP_DEFAULT_REALM_ID', $config['realmRuntime']['default_realm_id'] ?? 1),
    'multirealm' => (int)spp_env('SPP_MULTIREALM', $config['realmRuntime']['multirealm'] ?? 0),
];

$siteBuildRuntime = spp_detect_git_runtime(dirname(__DIR__));

$forumRuntime = [
    'news_forum_id' => (int)spp_env('SPP_NEWS_FORUM_ID', $config['forumRuntime']['news_forum_id'] ?? 1),
    'bugs_forum_id' => (int)spp_env('SPP_BUGS_FORUM_ID', $config['forumRuntime']['bugs_forum_id'] ?? 5),
    'ql4_forum_id' => (int)spp_env('SPP_QL4_FORUM_ID', $config['forumRuntime']['ql4_forum_id'] ?? 6),
    'externalforum' => (int)spp_env('SPP_EXTERNAL_FORUM', $config['forumRuntime']['externalforum'] ?? 0),
    'frame_forum' => (int)spp_env('SPP_FRAME_FORUM', $config['forumRuntime']['frame_forum'] ?? 0),
    'forum_external_link' => (string)($config['forumRuntime']['forum_external_link'] ?? ''),
    'externalbugstracker' => (int)spp_env('SPP_EXTERNAL_BUGSTRACKER', $config['forumRuntime']['externalbugstracker'] ?? 1),
    'frame_bugstracker' => (int)spp_env('SPP_FRAME_BUGSTRACKER', $config['forumRuntime']['frame_bugstracker'] ?? 0),
    'bugstracker_external_link' => (string)($config['forumRuntime']['bugstracker_external_link'] ?? ''),
    'faqsite_external_use' => (int)spp_env('SPP_FAQSITE_EXTERNAL_USE', $config['forumRuntime']['faqsite_external_use'] ?? 0),
    'faqsite_external_link' => (string)($config['forumRuntime']['faqsite_external_link'] ?? '0'),
];

$realmDbMap = $config['realmDbMap'] ?? [
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
];

$configuredDefaultRealmId = (int)($realmRuntime['default_realm_id'] ?? 0);
if ($configuredDefaultRealmId > 0 && isset($realmDbMap[$configuredDefaultRealmId])) {
    $activeRealmId = $configuredDefaultRealmId;
} else {
    $activeRealmId = spp_default_realm_id($realmDbMap);
    $realmRuntime['default_realm_id'] = $activeRealmId;
}

$activeRealm = $realmDbMap[$activeRealmId];

$realmd = [
    'db_type' => 'mysql',
    'db_host' => $db['host'],
    'db_port' => $db['port'],
    'db_username' => $db['user'],
    'db_password' => $db['pass'],
    'db_name' => (string)($activeRealm['realmd'] ?? ''),
    'db_encoding' => 'utf8',
    'req_reg_invite' => (int)($genericRuntime['req_reg_invite'] ?? 0),
];

$worlddb = [
    'db_type' => 'mysql',
    'db_host' => $db['host'],
    'db_port' => $db['port'],
    'db_username' => $db['user'],
    'db_password' => $db['pass'],
    'db_name' => (string)($activeRealm['world'] ?? ''),
    'db_encoding' => 'utf8',
];

$DB = [
    'db_type' => 'mysql',
    'db_host' => $db['host'],
    'db_port' => $db['port'],
    'db_username' => $db['user'],
    'db_password' => $db['pass'],
    'db_name' => (string)($activeRealm['world'] ?? ''),
    'db_encoding' => 'utf8',
];

$runtimeConfig = [
    'generic' => [
        'expansion' => (int)$genericRuntime['expansion'],
        'copyright' => (string)$genericRuntime['copyright'],
        'display_banner_flash' => (int)$genericRuntime['display_banner_flash'],
        'use_archaeic_dbinfo_format' => (int)$genericRuntime['use_archaeic_dbinfo_format'],
        'use_alternate_mangosdb_port' => (int)$genericRuntime['use_alternate_mangosdb_port'],
        'use_local_ip_port_test' => (int)$genericRuntime['use_local_ip_port_test'],
        'account_key_retain_length' => (int)$genericRuntime['account_key_retain_length'],
        'cache_expiretime' => (int)$genericRuntime['cache_expiretime'],
        'use_purepass_table' => (int)$genericRuntime['use_purepass_table'],
        'onlinelist_on' => (int)$genericRuntime['onlinelist_on'],
        'req_reg_invite' => (int)$genericRuntime['req_reg_invite'],
        'site_register' => (int)$genericRuntime['site_register'],
        'posts_per_page' => (int)$genericRuntime['posts_per_page'],
        'topics_per_page' => (int)$genericRuntime['topics_per_page'],
        'users_per_page' => (int)$genericRuntime['users_per_page'],
        'ahitems_per_page' => (int)$genericRuntime['ahitems_per_page'],
        'imageautoresize' => (string)$genericRuntime['imageautoresize'],
        'avatar_path' => (string)$genericRuntime['avatar_path'],
        'default_component' => (string)$genericRuntime['default_component'],
        'max_avatar_file' => (int)$genericRuntime['max_avatar_file'],
        'max_avatar_size' => (string)$genericRuntime['max_avatar_size'],
        'site_cookie' => (string)$genericRuntime['site_cookie'],
        'site_title' => (string)$genericRuntime['site_title'],
        'smiles_path' => (string)$genericRuntime['smiles_path'],
    ],
    'generic_values' => [
        'realm_info' => [
            'default_realm_id' => (int)$realmRuntime['default_realm_id'],
            'multirealm' => (int)$realmRuntime['multirealm'],
        ],
        'forum' => [
            'news_forum_id' => (int)$forumRuntime['news_forum_id'],
            'bugs_forum_id' => (int)$forumRuntime['bugs_forum_id'],
            'ql4_forum_id' => (int)$forumRuntime['ql4_forum_id'],
            'externalforum' => (int)$forumRuntime['externalforum'],
            'frame_forum' => (int)$forumRuntime['frame_forum'],
            'forum_external_link' => (string)$forumRuntime['forum_external_link'],
            'externalbugstracker' => (int)$forumRuntime['externalbugstracker'],
            'frame_bugstracker' => (int)$forumRuntime['frame_bugstracker'],
            'bugstracker_external_link' => (string)$forumRuntime['bugstracker_external_link'],
            'faqsite_external_use' => (int)$forumRuntime['faqsite_external_use'],
            'faqsite_external_link' => (string)$forumRuntime['faqsite_external_link'],
        ],
    ],
    'armory' => [
        'locales' => (int)$armoryRuntime['locales'],
    ],
    'temp' => [
        'site_href' => (string)$tempRuntime['site_href'],
        'site_domain' => (string)$tempRuntime['site_domain'],
        'email_href' => (string)$tempRuntime['email_href'],
        'base_href' => (string)$tempRuntime['base_href'],
    ],
    'templates' => [
        'selected' => $templateName,
        'template' => $templateNames,
    ],
];

$bootstrapState = [
    'request_scheme' => $requestScheme,
    'site_href' => (string)$tempRuntime['site_href'],
    'site_domain' => (string)$tempRuntime['site_domain'],
    'base_href' => (string)$tempRuntime['base_href'],
    'template_name' => $templateName,
    'template_root' => 'templates/' . $templateName,
    'default_realm_id' => (int)$realmRuntime['default_realm_id'],
    'multirealm' => (int)$realmRuntime['multirealm'],
    'site_cookie' => (string)$genericRuntime['site_cookie'],
];

$GLOBALS['db'] = $db;
$GLOBALS['serviceDefaults'] = $serviceDefaults;
$GLOBALS['realmDbMap'] = $realmDbMap;
$GLOBALS['activeRealmId'] = $activeRealmId;
$GLOBALS['activeRealm'] = $activeRealm;
$GLOBALS['realmd'] = $realmd;
$GLOBALS['worlddb'] = $worlddb;
$GLOBALS['DB'] = $DB;
$GLOBALS['appTimezone'] = $appTimezone;
$GLOBALS['tempRuntime'] = $tempRuntime;
$GLOBALS['adminRuntime'] = $adminRuntime;
$GLOBALS['genericRuntime'] = $genericRuntime;
$GLOBALS['armoryRuntime'] = $armoryRuntime;
$GLOBALS['realmRuntime'] = $realmRuntime;
$GLOBALS['siteBuildRuntime'] = $siteBuildRuntime;
$GLOBALS['forumRuntime'] = $forumRuntime;
$GLOBALS['runtimeConfig'] = $runtimeConfig;
$GLOBALS['spp_bootstrap_state'] = $bootstrapState;
