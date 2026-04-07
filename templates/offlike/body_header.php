<?php
if (!defined('Armory')) { define('Armory', 1); }

$backgroundCatalog = function_exists('spp_background_image_catalog')
    ? spp_background_image_catalog()
    : ['19.jpg' => spp_background_image_url('19.jpg')];
$backgroundMode = 'daily';
$backgroundImage = '';
$backgroundSection = function_exists('spp_background_section_key')
    ? spp_background_section_key()
    : 'frontpage';

if (!empty($user['id']) && (int)$user['id'] > 0) {
    $requestedMode = (string)($user['background_mode'] ?? '');
    $backgroundMode = function_exists('spp_normalize_background_mode')
        ? spp_normalize_background_mode($requestedMode, 'daily')
        : 'daily';

    $requestedImage = basename(trim((string)($user['background_image'] ?? '')));
    if (isset($backgroundCatalog[$requestedImage])) {
        $backgroundImage = $requestedImage;
    }
}

$resolvedBackground = function_exists('spp_pick_background_path')
    ? spp_pick_background_path($backgroundMode, $backgroundImage, $backgroundCatalog, $backgroundSection)
    : spp_background_image_url('19.jpg');

$isGuildPage = ((string)($_REQUEST['n'] ?? '') === 'server' && (string)($_REQUEST['sub'] ?? '') === 'guild');
if ($isGuildPage) {
    $guildId = isset($_REQUEST['guildid']) ? (int)$_REQUEST['guildid'] : 0;
    $realmMap = is_array($GLOBALS['realmDbMap'] ?? null) ? $GLOBALS['realmDbMap'] : array();
    $realmId = function_exists('spp_resolve_realm_id')
        ? (int)spp_resolve_realm_id($realmMap)
        : (int)($_REQUEST['realm'] ?? ($user['cur_selected_realmd'] ?? 0));
    $realmCharsDb = (string)($realmMap[$realmId]['chars'] ?? '');

    if ($guildId > 0 && $realmId > 0 && $realmCharsDb !== '' && function_exists('spp_get_pdo')) {
        try {
            $charsPdo = spp_get_pdo('chars', $realmId);
            if ($charsPdo instanceof PDO) {
                $realmCharsDb = str_replace('`', '', $realmCharsDb);
                $stmt = $charsPdo->prepare("
                    SELECT c.race
                    FROM {$realmCharsDb}.guild g
                    INNER JOIN {$realmCharsDb}.characters c ON c.guid = g.leaderguid
                    WHERE g.guildid = ?
                    LIMIT 1
                ");
                $stmt->execute(array($guildId));
                $leaderRace = (int)$stmt->fetchColumn();
                if ($leaderRace > 0) {
                    $isAlliance = in_array($leaderRace, array(1, 3, 4, 7, 11, 22, 25, 29), true);
                    $resolvedBackground = $isAlliance
                        ? spp_modern_guild_image_url('alliance_guild.jpg')
                        : spp_modern_guild_image_url('horde_guild.jpg');
                }
            }
        } catch (Throwable $e) {
        }
    }
}

$mobileMenuLogo = ((int)$expansion === 1)
    ? spp_site_url('components/pomm/img/map_tbc/realm_on.gif')
    : spp_modern_branding_url('Logo-wow-NA.png');

$registeredStyles = spp_render_registered_styles();
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?php echo htmlspecialchars(spp_config_generic('site_title', 'World of Warcraft'), ENT_QUOTES); ?></title>

  <link rel="shortcut icon" href="/favicon.ico"/>
  <link rel="icon" href="/favicon.ico" type="image/x-icon"/>
  <?php if ($registeredStyles !== ''): ?>
  <?php echo $registeredStyles; ?>
  <?php endif; ?>
</head>
<body class="site-shell" style="--site-shell-background-image: url('<?php echo htmlspecialchars($resolvedBackground, ENT_QUOTES); ?>');">
  <div class="nav-container">
    <div class="nav-logo">
      <a href="./">
        <img src="<?php echo htmlspecialchars(spp_modern_branding_url('wow.png'), ENT_QUOTES); ?>" alt="WoW Logo" class="nav-logo-img" />
      </a>
    </div>

    <button type="button" class="mobile-toggle" aria-label="Open navigation" aria-expanded="false">&#9776;</button>

    <div class="mobile-menu">
      <button type="button" class="menu-close" aria-label="Close navigation">&times;</button>
      <img
        src="<?php echo htmlspecialchars($mobileMenuLogo, ENT_QUOTES); ?>"
        alt="WoW Logo"
        class="menu-logo"
      />

      <ul class="mobile-main">
        <?php build_main_menu(true); ?>
        <li class="menu-spacer"><br></li>
      </ul>
    </div>

    <ul class="nav-menu desktop-menu">
      <?php build_main_menu(true); ?>
    </ul>

    <div class="nav-right">
      <ul class="nav-menu account-dropdown">
        <?php build_account_menu(true); ?>
      </ul>
    </div>
  </div>

  <div class="menu-overlay"></div>
  <div id="tooltip" class="tooltip-box"><div id="tooltiptext"></div></div>

  <main class="site-main">
