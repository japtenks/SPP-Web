<?php
$siteRoot = dirname(__DIR__, 3);

require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/core/dbsimple/Generic.php');
require_once($siteRoot . '/config/armory/mysql.php');
require_once($siteRoot . '/config/armory/defines.php');
// statisticshandler.php omitted — character-profile stats functions not needed for talent calc

if (!defined('Armory')) {
    define('Armory', 1);
}
if (!defined('REQUESTED_ACTION')) {
    define('REQUESTED_ACTION', 'talentscalc');
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die('Realm DB map not loaded');
}

if (!function_exists('server_talents_resolve_armory_realm_name')) {
    function server_talents_resolve_armory_realm_name($realmId, array $realmMap, array $legacyRealms = array()): string
    {
        $realmId = (int)$realmId;

        if (function_exists('spp_get_armory_realm_name')) {
            $resolved = spp_get_armory_realm_name($realmId);
            if (is_string($resolved) && $resolved !== '') {
                return $resolved;
            }
        }

        foreach ($legacyRealms as $realmName => $realmInfo) {
            if ((int)($realmInfo[0] ?? 0) === $realmId) {
                return (string)$realmName;
            }
        }

        return 'Realm ' . $realmId;
    }
}

$requestedRealm = $_GET['realm'] ?? null;
$realmId = null;
if (is_string($requestedRealm) && $requestedRealm !== '' && !ctype_digit($requestedRealm)) {
    foreach ($realmMap as $mappedRealmId => $mappedRealmInfo) {
        $mappedArmoryRealm = server_talents_resolve_armory_realm_name((int)$mappedRealmId, $realmMap, $realms ?? array());
        if ($mappedArmoryRealm !== '' && strcasecmp($requestedRealm, $mappedArmoryRealm) === 0) {
            $realmId = (int)$mappedRealmId;
            break;
        }
    }
    if ($realmId === null) {
        foreach ($realms as $realmName => $realmInfo) {
            if (strcasecmp($requestedRealm, $realmName) === 0) {
                $realmId = (int)$realmInfo[0];
                break;
            }
        }
    }
}
if ($realmId === null) {
    $realmId = spp_resolve_realm_id($realmMap);
}

$realmConfig = $realmMap[$realmId] ?? null;
if (!is_array($realmConfig)) {
    die('Unable to resolve realm for talent calculator.');
}

$armoryRealmName = server_talents_resolve_armory_realm_name($realmId, $realmMap, $realms ?? array());

if (!function_exists('server_talents_init_db')) {
    function server_talents_init_db($connectionInfo)
    {
        $legacyDb = dbsimple_Generic::connect(
            'mysql://' . $connectionInfo[1] . ':' . $connectionInfo[2] . '@' . $connectionInfo[0] . '/' . $connectionInfo[3]
        );
        if (!is_object($legacyDb)) {
            return null;
        }
        $legacyDb->setErrorHandler('databaseErrorHandler');
        $legacyDb->query('SET NAMES UTF8;');
        return $legacyDb;
    }
}

if (!function_exists('server_talents_build_url')) {
    function server_talents_build_url(int $realmId, int $classId, string $characterName = '', bool $isProfileMode = false, bool $isEmbedMode = false): string
    {
        $params = array(
            'n' => 'server',
            'sub' => 'talents',
            'realm' => (string)$realmId,
            'class' => (string)$classId,
        );

        if ($characterName !== '') {
            $params['character'] = $characterName;
        }

        if ($isProfileMode) {
            $params['mode'] = 'profile';
        }

        if ($isEmbedMode) {
            $params['embed'] = '1';
        }

        return 'index.php?' . http_build_query($params);
    }
}

$runtimeDb = $db ?? ($GLOBALS['db'] ?? array());
$hostport = $runtimeDb['host'] . ':' . $runtimeDb['port'];
$DB = server_talents_init_db(array($hostport, $runtimeDb['user'], $runtimeDb['pass'], $realmConfig['realmd']));
$WSDB = server_talents_init_db(array($hostport, $runtimeDb['user'], $runtimeDb['pass'], $realmConfig['world']));
$CHDB = spp_get_pdo('chars', $realmId);
$ARDB = server_talents_init_db(array($hostport, $runtimeDb['user'], $runtimeDb['pass'], $realmConfig['armory']));
if (!empty($realmConfig['bots'])) {
    $PBDB = server_talents_init_db(array($hostport, $runtimeDb['user'], $runtimeDb['pass'], $realmConfig['bots']));
}

if (!defined('REALM_NAME')) {
    define('REALM_NAME', $armoryRealmName);
}
$talentExpansionKey = function_exists('spp_realm_to_expansion_key')
    ? (string)spp_realm_to_expansion_key($realmId)
    : ((int)($GLOBALS['expansion'] ?? 0) === 2 ? 'wotlk' : (((int)($GLOBALS['expansion'] ?? 0) === 1) ? 'tbc' : 'classic'));

$classNameToId = [
    'warrior' => 1,
    'paladin' => 2,
    'hunter' => 3,
    'rogue' => 4,
    'priest' => 5,
    'shaman' => 7,
    'mage' => 8,
    'warlock' => 9,
    'druid' => 11,
];

$selectedCharacter = trim($_GET['character'] ?? '');
$selectedClassParam = trim($_GET['class'] ?? '');
$selectedClassId = 1;
$viewMode = strtolower(trim($_GET['mode'] ?? 'calc'));
$isProfileMode = in_array($viewMode, array('profile', 'build', 'view'), true);
$isEmbedMode = !empty($_GET['embed']);

if ($selectedClassParam !== '') {
    if (ctype_digit($selectedClassParam)) {
        $selectedClassId = (int)$selectedClassParam;
    } else {
        $selectedClassId = $classNameToId[strtolower($selectedClassParam)] ?? 1;
    }
}

$stat = [
    'guid' => 0,
    'name' => $selectedCharacter,
    'class' => $selectedClassId,
    'level' => 0,
];
if ($selectedCharacter !== '') {
    $chStmt = $CHDB->prepare('SELECT `guid`, `name`, `class`, `level` FROM `characters` WHERE `name`=? LIMIT 1');
    $chStmt->execute([$selectedCharacter]);
    $characterRow = $chStmt->fetch(PDO::FETCH_ASSOC);
    if ($characterRow) {
        $stat = array_merge($stat, $characterRow);
        if ($selectedClassParam === '') {
            $selectedClassId = (int)$characterRow['class'];
        }
    }
}

$_GET['class'] = $selectedClassId;
$_GET['realm'] = (string)$realmId;
$GLOBALS['talent_calc_realm_id'] = (int)$realmId;
$GLOBALS['talent_calc_realm_name'] = (string)REALM_NAME;
$GLOBALS['talent_calc_expansion'] = $talentExpansionKey;
$GLOBALS['talent_calc_base_url'] = server_talents_build_url(
    (int)$realmId,
    (int)$selectedClassId,
    $selectedCharacter,
    $isProfileMode,
    $isEmbedMode
);
$GLOBALS['server_talent_calc_mode'] = !$isProfileMode;
$GLOBALS['server_talent_profile_mode'] = $isProfileMode;
$talentBaseParams = $GLOBALS['talent_calc_base_url'];
echo '<script>window.tcBaseUrl = ' . json_encode($talentBaseParams) . ';</script>';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars(spp_template_asset_url('css/talents-calc.css'), ENT_QUOTES); ?>">
<?php if (!$isProfileMode): ?>
<script defer src="<?php echo htmlspecialchars(spp_template_asset_url('js/talents-calc.js'), ENT_QUOTES); ?>"></script>
<?php endif; ?>
<?php
if (!$isEmbedMode) builddiv_start(1, $isProfileMode ? 'Talent Build' : 'Talent Calculator', 1);
echo '<div class="server-talents-shell' . ($isProfileMode ? ' is-profile' : '') . ($isEmbedMode ? ' is-embed' : '') . '">';
include($siteRoot . '/templates/offlike/server/talent-calc.php');
echo '</div>';
if (!$isEmbedMode) builddiv_end();
