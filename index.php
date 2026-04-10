<?php

require_once('config/config-helper.php');
require_once('app/bootstrap/request-runtime.php');

// Current Revision
$rev = "beta_v0.2";

$sessionCookieOptions = spp_bootstrap_session_cookie_options();
if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
	session_set_cookie_params($sessionCookieOptions);
} else {
	session_set_cookie_params(
		$sessionCookieOptions['lifetime'],
		$sessionCookieOptions['path'],
		$sessionCookieOptions['domain'],
		$sessionCookieOptions['secure'],
		$sessionCookieOptions['httponly']
	);
}
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
session_start();

// Set error reporting to only a few things.
ini_set('error_reporting', E_ERROR ^ E_NOTICE ^ E_WARNING);
error_reporting( E_ERROR | E_PARSE | E_WARNING ) ;
ini_set('log_errors',TRUE);
ini_set('html_errors',FALSE);
ini_set('error_log', spp_storage_path('logs/error_log.txt'));
ini_set( 'display_errors', '0' ) ;
// Define INCLUDED so that we can check other pages if they are included by this file
define( 'INCLUDED', true ) ;

// Start a variable that shows how fast page loaded.
$time_start = microtime( 1 ) ;
$_SERVER['REQUEST_TIME'] = time() ;

$realmDbMap = $GLOBALS['realmDbMap'] ?? [] ;

// Site functions & classes ...
include 	( 'core/common.php' ) ;
include 	( 'core/security.php' ) ;
include 	( 'core/request.php' ) ;
include 	( 'core/mangos.class.php' ) ;
include 	( 'core/class.auth.php' ) ;
// core/dbsimple/Generic.php still loaded by armory subsystem (armory/index.php loads it independently)
require 	( 'core/class.captcha.php' ) ;
include 	( 'core/cache_class/safeIO.php' ) ;
include 	( 'core/cache_class/gCache.php' ) ;

// Inizialize difrent variables.
global $mangos ;
// Super-Global variables.
$GLOBALS['users_online'] = array() ;
$GLOBALS['guests_online'] = 0 ;
$GLOBALS['messages'] = '' ;
$GLOBALS['redirect'] = '' ;
$GLOBALS['sidebarmessages'] = '' ;
$GLOBALS['context_menu'] = array() ;
$GLOBALS['expansion'] = (int)spp_config_generic( 'expansion', 0 );
if (file_exists("tbc.spp"))
	$GLOBALS['expansion'] = 1;
if (file_exists("wotlk.spp"))
	$GLOBALS['expansion'] = 2;

// Inzizalize Cache class
$cache = new gCache ;
$cache->folder = spp_storage_path('cache/sites') ;
$cache->timeout = (int)spp_config_generic( 'cache_expiretime', 0 ) ;

// Play arround for IIS lake on $_SERVER['REQUEST_URI']
if ( $_SERVER['REQUEST_URI'] == "" )
{
	if ( $_SERVER['QUERY_STRING'] != "" )
	{
		$__SERVER['REQUEST_URI'] = $_SERVER["SCRIPT_NAME"] . "?" . $_SERVER['QUERY_STRING'] ;
	} else
	{
		$__SERVER['REQUEST_URI'] = $_SERVER["SCRIPT_NAME"] ;
	}
} else
{
	$__SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ;
}

$ext = spp_route_component(
	$_REQUEST['n'] ?? null,
	spp_bootstrap_default_component()
);
$sub = spp_route_subpage($_REQUEST['sub'] ?? null);
if (function_exists('spp_realm_runtime_apply_bootstrap')) {
	$realmDbMap = $GLOBALS['allConfiguredRealmDbMap'] ?? $realmDbMap;
	$realmRuntimeState = spp_realm_runtime_apply_bootstrap(
		is_array($realmDbMap) ? $realmDbMap : array(),
		(string)$ext,
		(string)$sub
	);
	$realmDbMap = $GLOBALS['realmDbMap'] ?? $realmDbMap;
} else {
	$realmRuntimeState = array();
}

// Load auth system //
$auth = new AUTH() ;
$user = $auth->user ;
// ================== //




//Determine Current Template
$currtmp = spp_bootstrap_template_root();

// Load Permissions and aviable sites.
include ( 'core/default_components.php' ) ;

// Start of context menu. ( Only make an array for later output )
$GLOBALS['context_menu'][] = array( 'title' => 'Home', 'link' =>
	'index.php' ) ;

if ( $user['id'] <= 0 )
{
	$GLOBALS['context_menu'][] = array( 'title' => 'Register', 'link' =>
		spp_route_url( 'account', 'register' ) ) ;
}
$GLOBALS['context_menu'][] = array( 'title' => 'Forum', 'link' =>
	'index.php?n=forum' ) ;
if ( ( isset( $user['g_is_admin'] ) || isset( $user['g_is_supadmin'] ) ) && ( $user['g_is_admin'] ==
	1 || $user['g_is_supadmin'] == 1 ) )
{
	$allowed_ext[] = 'admin' ;
	$GLOBALS['context_menu'][] = array( 'title' => '------------------', 'link' =>
		'#' ) ;
	$GLOBALS['context_menu'][] = array( 'title' => 'Admin Panel', 'link' =>
		'index.php?n=admin' ) ;
}

// for mod_rewrite query_string fix //
global $_GETVARS ;

$req_vars = parse_url( $__SERVER['REQUEST_URI'] ) ;
if ( isset( $req_vars['query'] ) )
{
	parse_str( $req_vars['query'], $req_arr ) ;
	$_GETVARS = $req_arr ;
}
unset( $req_arr, $req_vars ) ;
// ======================================================= //

// Finds out what realm we are viewing.
$runtimeRealmMap = $GLOBALS['allEnabledRealmDbMap'] ?? $GLOBALS['allRealmDbMap'] ?? $realmDbMap ;
$defaultRealmId = (int)( $realmRuntimeState['default_realm_id'] ?? spp_bootstrap_default_realm_id( $runtimeRealmMap ) ) ;
$selectedRealmId = (int)( $realmRuntimeState['active_realm_id'] ?? $GLOBALS['activeRealmId'] ?? $defaultRealmId ) ;
$user['cur_selected_realmd'] = $selectedRealmId ;
setcookie( "cur_selected_realmd", $selectedRealmId, time() + ( 3600 * 24 ), '/' ) ;
setcookie( "cur_selected_realm", $selectedRealmId, time() + ( 3600 * 24 ), '/' ) ;
$dbinfo_mangos = null ;
$selectedRealmIsValid = ( $selectedRealmId > 0 && isset( $runtimeRealmMap[$selectedRealmId] ) ) ;
$selectedRealmUnavailable = false ;
$_realmPdo = spp_get_pdo('realmd', $defaultRealmId);

if ( !function_exists( 'spp_build_legacy_realm_dbinfo' ) )
{
	function spp_build_legacy_realm_dbinfo( $realmId )
	{
		$realmId = (int)$realmId ;
		$realmMap = $GLOBALS['realmDbMap'] ?? array() ;
		$db = $GLOBALS['db'] ?? array() ;
		if ( $realmId <= 0 || empty( $realmMap[$realmId] ) || !is_array( $db ) )
		{
			return null ;
		}

		$realmInfo = $realmMap[$realmId] ;
		return array(
			'dbhost' => (string)($db['host'] ?? ''),
			'dbport' => (string)($db['port'] ?? ''),
			'dbuser' => (string)($db['user'] ?? ''),
			'dbpass' => (string)($db['pass'] ?? ''),
			'dbname' => (string)($realmInfo['world'] ?? ''),
			'chardbname' => (string)($realmInfo['chars'] ?? ''),
		) ;
	}
}

if ( $selectedRealmIsValid )
{
	$_stmt = $_realmPdo->prepare("SELECT `id` FROM `realmlist` WHERE `id`=? LIMIT 1");
	$_stmt->execute([(int)$selectedRealmId]);
	$realmExists = $_stmt->fetchColumn();
	$dbinfo_mangos = spp_build_legacy_realm_dbinfo( $selectedRealmId ) ;
	$selectedRealmIsValid = !empty( $realmExists ) && !empty( $dbinfo_mangos ) ;

	if ( $selectedRealmIsValid && function_exists( 'spp_get_pdo' ) && isset( $runtimeRealmMap[$selectedRealmId] ) )
	{
		try
		{
			spp_get_pdo( 'realmd', $selectedRealmId ) ;
		}
		catch ( Throwable $e )
		{
			$selectedRealmIsValid = false ;
			$selectedRealmUnavailable = true ;
			error_log( "[realm] Falling back from realm {$selectedRealmId}: " . $e->getMessage() ) ;
		}
	}
}

if ( !$selectedRealmIsValid )
{
	if ( $selectedRealmId > 0 && $selectedRealmId !== $defaultRealmId )
	{
		$selectedRealmUnavailable = true ;
	}
	$user['cur_selected_realmd'] = $defaultRealmId ;
	setcookie( "cur_selected_realmd", $user['cur_selected_realmd'], time() + ( 3600 * 24 ), '/' ) ;
	setcookie( "cur_selected_realm", $user['cur_selected_realmd'], time() + ( 3600 * 24 ), '/' ) ;
	$dbinfo_mangos = spp_build_legacy_realm_dbinfo( $user['cur_selected_realmd'] ) ;
}

if ( $selectedRealmUnavailable )
{
	output_message( 'alert', 'Realm <b>#' . (int)$selectedRealmId . '</b> is not installed yet.' ) ;
}

// Make an array from `dbinfo` column for the selected realm..
//$dbinfo_mangos = explode( ';', $mangos_info ) ;
if ( ( int )spp_config_generic( 'use_archaeic_dbinfo_format', 0 ) )
{
	//alternate config - for users upgrading from Modded MaNGOS Web
	//DBinfo column:  host;port;username;password;WorldDBname;CharDBname
	$mangos = array(
		'db_type' => 'mysql',
		'db_host' => $dbinfo_mangos['dbhost'], //ip of db world
		'db_port' => $dbinfo_mangos['dbport'], //port
		'db_username' => $dbinfo_mangos['dbuser'], //world user
		'db_password' => $dbinfo_mangos['dbpass'], //world password
		'db_name' => $dbinfo_mangos['dbname'], //world db name
		'db_char' => $dbinfo_mangos['chardbname'], //character db name
		'db_encoding' => 'utf8', // don't change
		) ;
} else
{
	//normal config, as outlined in how-to
	//DBinfo column:  username;password;port;host;WorldDBname;CharDBname
	$mangos = array( 'db_type' => 'mysql', 'db_host' => $dbinfo_mangos['dbhost'],
		//ip of db world
		'db_port' => $dbinfo_mangos['dbport'], //port
		'db_username' => $dbinfo_mangos['dbuser'], //world user
		'db_password' => $dbinfo_mangos['dbpass'], //world password
		'db_name' => $dbinfo_mangos['dbname'], //world db name
		'db_char' => $dbinfo_mangos['chardbname'], //character db name
		'db_encoding' => 'utf8', // don't change
		) ;
}
unset( $dbinfo_mangos ) ; // Free up memory.

if ( ( int )spp_config_generic( 'use_alternate_mangosdb_port', 0 ) )
{
	$mangos['db_port'] = ( int )spp_config_generic( 'use_alternate_mangosdb_port', 0 ) ;
}

// Output error message and die if user has not changed info in realmd.realmlist.`dbinfo` .
if ( $mangos['db_host'] == '127.0.0.1' && $mangos['db_port'] == '3306' && $mangos['db_username'] ==
	'username' && $mangos['db_password'] == 'password' && $mangos['db_name'] ==
	'DBName' )
{
	echo "Setup error: the WORLD database connection in realmd.realmlist.dbinfo is still using placeholder values.<br />Update dbinfo to the correct format: username;password;3306;127.0.0.1;DBName" ;
	die ;
}

// Load authenticated account characters without reusing the page-level $characters name.
$ownedCharacterCache = $GLOBALS['account_characters'] ?? array();
if (!empty($ownedCharacterCache) && is_array($ownedCharacterCache)) {
    foreach ($ownedCharacterCache as $character) {
        if ($character['guid'] == ($_COOKIE['cur_selected_character'] ?? 0)) {
            break;
        }
    }
}

if ( empty( $_GET['p'] ) or $_GET['p'] < 1 )
	$p = 1 ;
else
	$p = $_GET['p'] ;

if (
    isset($_GET['searchType'], $_GET['charPage']) &&
    $_GET['searchType'] === 'profile' &&
    $_GET['charPage'] === 'talentcalc'
) {
    $legacyRealm = trim($_GET['realm'] ?? '');
    $realmId = 1;
    $realmMap = $GLOBALS['realmDbMap'] ?? null;

    if (ctype_digit($legacyRealm)) {
        $realmId = (int)$legacyRealm;
    } elseif (is_array($realmMap)) {
        $normalizedLegacyRealm = strtolower(preg_replace('/[^a-z0-9]+/', '', $legacyRealm));

        foreach ($realmMap as $candidateRealmId => $realmInfo) {
            $resolvedRealmName = function_exists('spp_get_armory_realm_name')
                ? (spp_get_armory_realm_name((int)$candidateRealmId) ?? '')
                : '';
            $label = strtolower(preg_replace('/[^a-z0-9]+/', '', $resolvedRealmName));
            if ($label !== '' && ($normalizedLegacyRealm === $label || $normalizedLegacyRealm === 'spp' . $label)) {
                $realmId = (int)$candidateRealmId;
                break;
            }
        }
    }

    $redirectUrl = 'index.php?n=server&sub=talents&realm=' . $realmId;
    if (!empty($_GET['character'])) {
        $redirectUrl .= '&character=' . rawurlencode($_GET['character']);
    }
    if (!empty($_GET['class'])) {
        $redirectUrl .= '&class=' . rawurlencode($_GET['class']);
    }

    header('Location: ' . $redirectUrl);
    exit;
}

$realmDbMap = $GLOBALS['realmDbMap'] ?? $realmDbMap;
$req_tpl = false ;

// Handle character switch from ?setchar=ID
if (isset($_GET['setchar'])) {
    $charId = (int) $_GET['setchar'];
    $requestedCharRealmId = (int)($_GET['setchar_realm'] ?? $_GET['changerealm_to'] ?? 0);

    if ($charId > 0 && isset($user['id']) && $user['id'] > 0) {
        $char = null;
        $selectedRealmId = $requestedCharRealmId > 0 ? $requestedCharRealmId : (int)($user['cur_selected_realmd'] ?? 0);

        $ownedCharacters = $GLOBALS['account_characters'] ?? array();
        if (!empty($ownedCharacters) && is_array($ownedCharacters)) {
            foreach ($ownedCharacters as $loadedCharacter) {
                if ((int)($loadedCharacter['guid'] ?? 0) === $charId
                    && ($requestedCharRealmId <= 0 || (int)($loadedCharacter['realm_id'] ?? 0) === $requestedCharRealmId)) {
                    $char = array(
                        'guid' => (int)$loadedCharacter['guid'],
                        'name' => $loadedCharacter['name'],
                    );
                    $selectedRealmId = (int)($loadedCharacter['realm_id'] ?? $selectedRealmId);
                    break;
                }
            }
        }

        if (!$char) {
            // Fallback to the current realm DB if the global character cache is unavailable.
            $_charsFbPdo = spp_get_pdo('chars', $selectedRealmId > 0 ? $selectedRealmId : $defaultRealmId);
            $_stmt = $_charsFbPdo->prepare("SELECT guid, name FROM `characters` WHERE guid=? AND account=?");
            $_stmt->execute([(int)$charId, (int)$user['id']]);
            $char = $_stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($char) {
            // Update cookie and DB
            setcookie('cur_selected_character', $char['guid'], time() + 86400, '/');
            setcookie('cur_selected_realm', $selectedRealmId, time() + 86400, '/');
            setcookie('cur_selected_realmd', $selectedRealmId, time() + 86400, '/');
            if (function_exists('spp_website_accounts_has_columns') && spp_website_accounts_has_columns(['character_realm_id'])) {
                $_waStmt2 = spp_get_pdo('realmd', $defaultRealmId)->prepare(
                    "UPDATE website_accounts SET character_id=?, character_name=?, character_realm_id=? WHERE account_id=?"
                );
                $_waStmt2->execute([(int)$char['guid'], $char['name'], (int)$selectedRealmId, (int)$user['id']]);
            } else {
                $_waStmt2 = spp_get_pdo('realmd', $defaultRealmId)->prepare(
                    "UPDATE website_accounts SET character_id=?, character_name=? WHERE account_id=?"
                );
                $_waStmt2->execute([(int)$char['guid'], $char['name'], (int)$user['id']]);
            }
        }
    }

    $redirectTarget = 'index.php';
    if (!empty($_GET['returnto'])) {
        $candidateRedirect = rawurldecode((string)$_GET['returnto']);
        if ($candidateRedirect !== '' && preg_match('#^(?:/|index\.php|\./index\.php|\?.*)#', $candidateRedirect)) {
            $redirectTarget = $candidateRedirect;
        }
    }

    // Always redirect to clean the URL
    header("Location: " . $redirectTarget);
    exit;
}

//initialize modules
//if installing a new module, please delete the cache file
include ( spp_component_path( 'modules/initialize.php' ) ) ;


if ( in_array( $ext, $allowed_ext ) )
{

	// load component

	//set defaults here to be loaded -- these can be changed via the main.php or whatnot
	//this is used especially in the case of the module system
	$routeExecution = spp_route_execution_paths( $ext, $sub ) ;
	$script_file = $routeExecution['script_file'] ;
	$template_file = $routeExecution['template_file'] ;
	spp_register_style_asset( 'shell:xfer', spp_template_asset_url( 'css/xfer.css' ) ) ;
	spp_register_style_asset( 'shell:components', spp_template_asset_url( 'css/components.css' ) ) ;
	spp_register_script_asset( 'shell:shell-nav', spp_template_js_asset_url( 'shell-nav.js' ), array( 'defer' => true ) ) ;
	spp_register_script_asset( 'shell:wz-tooltip', spp_js_asset_url( 'wz_tooltip.js', true ) ) ;
	spp_bootstrap_route_contract( $ext, $sub ) ;

	require ( spp_component_path( $ext . '/main.php' ) ) ;
	$group_privilege = $com_content[$ext][$sub][0] ;
	$expectation = ( substr( $group_privilege, 0, 1 ) == '!' ) ? 0 : 1 ;
	if ( $expectation == 0 )
		$group_privilege = substr( $group_privilege, 1 ) ;
	if ( $group_privilege && $user[$group_privilege] != $expectation )
		exit( '<h2>Forbidden</h2><meta http-equiv=refresh content="3;url=\'./\'">' ) ;
	// ==================== //
	if ( isset( $_REQUEST['n'] ) )
		$pathway_info[] = array( 'title' => (string)$com_content[$ext]['index'][1],
			'link' => $com_content[$ext]['index'][2] ) ;
	// ==================== //
	foreach ( $com_content[( string )$ext] as $sub_name => $sub_conf )
	{
		if ( $sub_conf[4] == 1 )
		{
			if ( $sub_conf[0] )
			{
				if ( $user[$sub_conf[0]] == 1 )
				{
					$GLOBALS['context_menu'][] = array( 'title' => (string)$sub_conf[1], 'link' => ( isset( $sub_conf[2] ) ? $sub_conf[2] :
						'?link?' ) ) ;
				}
			} else
			{
				$GLOBALS['context_menu'][] = array( 'title' => (string)$sub_conf[1], 'link' => $sub_conf[2] ) ;
			}
		}
	}
	if ( $sub )
	{
		if ( $com_content[$ext][$sub] )
		{
			if ( $com_content[$ext][$sub][0] )
			{
				if ( $user[$com_content[$ext][$sub][0]] == 1 )
				{
					$req_tpl = true ;
					@include ( $script_file ) ;
				}
			} else
			{
				$req_tpl = true ;
				@include ( $script_file ) ;

			}
		}
	}
	if ( empty( $_GET['nobody'] ) )
	{
		// DEBUG //
		if ( ( int )spp_config_generic( 'debuginfo', 0 ) )
		{
			output_message( 'debug', 'DEBUG://' ) ;
			output_message( 'debug', '<pre>' . print_r( $_SERVER, true ) . '</pre>' ) ;
		}
		// =======//

		include ( spp_template_path( 'body_functions.php' ) ) ;
		ob_start() ;
		include ( spp_template_path( 'body_header.php' ) ) ;
		ob_end_flush() ;

		if ( $req_tpl )
		{
			if ( file_exists( $template_file ) )
			{
				// Only cache if user is not logged in.
				if ( $user['id'] < 0 && ( int )spp_config_generic( 'cache_expiretime', 0 ) != 0 )
				{

					// Start caching process But we want to exclude some cases.
					$skipPageCache = false ;
					if ( isset( $_REQUEST['n'] ) && $_REQUEST['n'] === 'server' && isset( $_REQUEST['sub'] ) && in_array( $_REQUEST['sub'], array( 'items', 'item' ), true ) )
					{
						$skipPageCache = true ;
					}

					if ( isset( $_REQUEST['n'] ) && $_REQUEST['n'] != 'account' && !$skipPageCache )
					{
						$cache->contentId = md5( 'CONTENT' . $_SERVER['REQUEST_URI'] ) ;
						if ( $cache->Valid() )
						{
							echo $cache->content ;
						} else
						{
							$cache->capture() ;
							include ( $template_file ) ;
							$cache->endcapture() ;
						}
					} else
					{
						include ( $template_file ) ;
					}

				} else
				{
					// Create output buffer
					ob_start() ;
					include ( $template_file ) ;
					ob_end_flush() ;
				}
			}
		}
		$time_end = microtime( 1 ) ;
		$exec_time = $time_end - $time_start ;
		include ( spp_template_path( 'body_footer.php' ) ) ;
	} else
	{
		if ( file_exists( $template_file ) )
		{

			include ( $template_file ) ;

		}
	}
} else
{
	echo '<h2>Forbidden Sushi: Call (901) 867-5309</h2><meta http-equiv=refresh content="3;url=\'./\'">' ;
}

?>

