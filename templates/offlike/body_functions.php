<?php
require_once dirname(__DIR__, 2) . '/app/support/terminology.php';

$templategenderimage = array(
    0 => spp_modern_meta_icon_url('gender/unknown.png'),
    1 => spp_modern_meta_icon_url('gender/male.png'),
    2 => spp_modern_meta_icon_url('gender/female.png')
);

function population_view($n) {
    $maxlow = 500;
    $maxmedium = 700;
    $maxhigh = 2000;
    if($n <= $maxlow){
        return '<span class="population-status population-status--low">Low</span>';
    }elseif($n > $maxlow && $n <= $maxmedium){
        return '<span class="population-status population-status--medium">Medium</span>';
    }elseif($n > $maxmedium && $n <= $maxhigh){
        return '<span class="population-status population-status--high">High</span>';
    }
    else
        return '<span class="population-status population-status--full">Full</span>';
}

function spp_normalize_menu_name($menuName) {
    $menuName = preg_replace('/^\d+-/', '', (string)$menuName);

    $legacyMap = array(
        'menuNews' => 'News',
        'menuAccount' => 'Account',
        'menuGameGuide' => 'Game Guide',
        'menuWorkshop' => 'Workshop',
        'menuForums' => 'Forums',
        'menuArmory' => 'Armory',
        'menuSupport' => 'Support',
    );

    return $legacyMap[$menuName] ?? $menuName;
}

function spp_render_menu_link_label($label) {
    global $currtmp;

    $label = (string)$label;
    $escapedLabel = htmlspecialchars($label, ENT_QUOTES);

    if (strcasecmp($label, 'Market Place') !== 0 && strcasecmp($label, 'Marketplace') !== 0) {
        return $escapedLabel;
    }

    $iconSrc = htmlspecialchars(spp_modern_image_url('misc/news-contests.gif'), ENT_QUOTES);

    return '<span class="menu-link-label menu-link-label--marketplace">'
        . '<img class="menu-link-label__icon" src="' . $iconSrc . '" alt="" aria-hidden="true">'
        . '<span class="menu-link-label__text">' . $escapedLabel . '</span>'
        . '<small class="menu-link-label__status">Under Construction</small>'
        . '</span>';
}

function spp_is_disabled_menu_link($label) {
    $label = (string)$label;

    return strcasecmp($label, 'Market Place') === 0 || strcasecmp($label, 'Marketplace') === 0;
}

function build_menu_items($links_arr){
    global $user;
    $r = "\n";
    foreach($links_arr as $menu_item){
        $ignore_item = 0;
        if($menu_item[2]) {


            $do_menu_excl = explode('!',$menu_item[2]);
            if(count($do_menu_excl) == 2) {
                if($user[$do_menu_excl[1]]) {
                    $ignore_item = 1;
                }
            }
            else {
                if(!$user[$do_menu_excl[0]]) {
                    $ignore_item = 1;
                }
            }
        }
        if(!$ignore_item && isset($menu_item[0]))
            $r .='                                                <div><a class="menufiller" href="'.$menu_item[1].'">'.htmlspecialchars((string)$menu_item[0]).'</a></div>'."\n";
    }
    return $r;
}

// ------------------ MAIN MENU ------------------
function build_main_menu($asList = false) {
    global $mainnav_links;
    if (empty($mainnav_links)) return;

    foreach ($mainnav_links as $menuname => $menuitems) {
                // Skip unwanted menus
        if (
            stripos($menuname, 'account') !== false || 
            stripos($menuname, 'news')    !== false
        ) continue;

        if (count($menuitems) > 0) {
            $menukey   = spp_normalize_menu_name($menuname);
            $menulabel = $menukey;

            if (!$asList) {
                echo '<div class="menu-block">';
                foreach ($menuitems as $item) {
                    if (isset($item[0])) {
                        $itemLabel = (string)$item[0];
                        $itemLabelHtml = spp_render_menu_link_label($itemLabel);
                        $isDisabled = spp_is_disabled_menu_link($itemLabel);
                        if ($menukey === "community" && $item[0] === "server_rules") {
                            if ($isDisabled) {
                                echo '<div class="menu-item"><span class="menu-link-disabled" aria-disabled="true">'.$itemLabelHtml.'</span></div>';
                            } else {
                                echo '<div class="menu-item"><a href="'.$item[1].'">'.$itemLabelHtml.'</a></div>';
                            }
                        } elseif ($menukey !== "community" || $item[0] !== "server_rules") {
                            if ($isDisabled) {
                                echo '<div class="menu-item"><span class="menu-link-disabled" aria-disabled="true">'.$itemLabelHtml.'</span></div>';
                            } else {
                                echo '<div class="menu-item"><a href="'.$item[1].'">'.$itemLabelHtml.'</a></div>';
                            }
                        }
                    }
                }
                echo '</div>';
            } else {
                echo '<li class="has-sub"><a href="#">'.$menulabel.'</a><ul>';
                foreach ($menuitems as $item) {
                    if (isset($item[0])) {
                        $itemLabel = (string)$item[0];
                        $itemLabelHtml = spp_render_menu_link_label($itemLabel);
                        $isDisabled = spp_is_disabled_menu_link($itemLabel);
                        if ($menukey === "community" && $item[0] === "server_rules") {
                            if ($isDisabled) {
                                echo '<li><span class="menu-link-disabled" aria-disabled="true">'.$itemLabelHtml.'</span></li>';
                            } else {
                                echo '<li><a href="'.$item[1].'">'.$itemLabelHtml.'</a></li>';
                            }
                        } elseif ($menukey !== "community" || $item[0] !== "server_rules") {
                            if ($isDisabled) {
                                echo '<li><span class="menu-link-disabled" aria-disabled="true">'.$itemLabelHtml.'</span></li>';
                            } else {
                                echo '<li><a href="'.$item[1].'">'.$itemLabelHtml.'</a></li>';
                            }
                        }
                    }
                }
                echo '</ul></li>';
            }
        }
    }

}

function build_serverinfo_menu($asList = true) {
    global $servers;
    $publicTerms = spp_terminology_public();

    // Bail out early if no servers
    if (empty($servers)) return;

    $cfg = spp_config_path(array('components', 'server_information'), (object)array());

    if ($asList) {
        echo '<li class="has-sub"><a href="#">🖥️ Server Info</a><ul>';

        if (!empty($servers)) {
            foreach ($servers as $server) {
                echo '<li><div class="serverinfo-tooltip">';

                echo '<div class="info-row"><span class="label">Realm:</span> 
                      <span class="value"><b>'.$server['name'].'</b></span></div>';

                if (!empty($cfg->realm_status)) {
                    $status = $server['realm_status']
                        ? '<span class="status-online">▲ Online</span>'
                        : '<span class="status-offline">▼ Offline</span>';
                    echo '<div class="info-row"><span class="label">Status:</span> 
                          <span class="value">'.$status.'</span></div>';
                }

                if (!empty($cfg->server_ip)) {
                    echo '<div class="info-row"><span class="label">Server IP:</span> 
                          <span class="value">'.$server['server_ip'].'</span></div>';
                }

                if (!empty($cfg->online)) {
                    echo '<div class="info-row"><span class="label">Map:</span>';
                    if (!empty($server['realm_status'])) {
                        echo '<span class="value"><a href="index.php?n=server&sub=playermap" class="maplink">Player Map</a></span>';
                    } else {
                        echo '<span class="value"><span class="maplink-offline">Player Map</span></span>';
                    }
                    echo '</div>';
                }

                if (!empty($cfg->population)) {
                    echo '<div class="info-row"><span class="label">Population:</span> 
                          <span class="value status-online">'.$server['population'].'</span></div>';
                }

                if (!empty($cfg->online)) {
                    echo '<div class="info-row"><span class="label">' . htmlspecialchars($publicTerms['humans']) . ' Online:</span> 
                          <span class="value">'.$server['realplayersonline'].'</span></div>';
                }

                if (!empty($cfg->characters)) {
                    echo '<div class="info-row"><span class="label">Characters:</span> 
                          <span class="value">'.$server['characters'].'</span></div>';
                }

                if (!empty($cfg->accounts)) {
                    $acctInfo = $server['accounts'];
                    if (!empty($cfg->active_accounts)) {
                        $acctInfo .= ' <span class="small">('.$server['active_accounts'].' active)</span>';
                    }
                    echo '<div class="info-row"><span class="label">Accounts:</span> 
                          <span class="value">'.$acctInfo.'</span></div>';
                }

                echo '</div></li>';
            }
        } else {
            echo '<li><div class="serverinfo-tooltip"><div class="info-row"><span class="label">Server Info:</span> 
                  <span class="value"><i>No data</i></span></div></div></li>';
        }

        echo '</ul></li>';
    }
}

// ------------------ ACCOUNT MENU ------------------
function spp_account_menu_class_slug($classId) {
    $map = array(
        1 => 'warrior',
        2 => 'paladin',
        3 => 'hunter',
        4 => 'rogue',
        5 => 'priest',
        6 => 'deathknight',
        7 => 'shaman',
        8 => 'mage',
        9 => 'warlock',
        11 => 'druid',
    );

    return $map[(int)$classId] ?? 'unknown';
}

function build_account_menu($asList = true) {
    global $user, $auth;
    $accountCharacters = $GLOBALS['account_characters'] ?? array();

    if (isset($auth) && method_exists($auth, 'load_characters_for_user')) {
        if (empty($accountCharacters)) {
            $auth->load_characters_for_user();
            $accountCharacters = $GLOBALS['account_characters'] ?? array();
        }
    }

    $accountLabel = 'Account';
    if (!empty($user['id']) && (int)$user['id'] > 0) {
        if (!empty($user['username'])) {
            $accountLabel = (string)$user['username'];
        } elseif (!empty($user['name'])) {
            $accountLabel = (string)$user['name'];
        }
    }
    $currentCharacterName = (string)($_GET['character'] ?? '');
    $selectedCharacterGuid = (int)($_COOKIE['cur_selected_character'] ?? ($user['character_id'] ?? 0));
    $currentRealmId = (int)($_GET['realm'] ?? 0);
    if ($currentRealmId <= 0 && function_exists('spp_selected_realm_id')) {
        $currentRealmId = (int)spp_selected_realm_id();
    }
    if ($currentRealmId <= 0) {
        $currentRealmId = (int)($_COOKIE['cur_selected_realmd'] ?? $_COOKIE['cur_selected_realm'] ?? ($user['character_realm_id'] ?? 0));
    }

    if ($asList) {
        echo '<li class="has-sub account">';
        echo '<a href="#">';
        echo '<span class="account-name">' . htmlspecialchars($accountLabel) . ' &#9662;</span>';
        echo '</a>';
        echo '<ul class="account-menu">';
    }

    if ($user['id'] <= 0) {
        echo '<li><a href="index.php?n=account&sub=login">Login</a></li>';
        echo '<li><a href="index.php?n=account&sub=register">Register</a></li>';
    } else {
        if (!empty($user['g_use_pm'])) {
            $userpm_num = $auth->check_pm();
            $label = ($userpm_num > 0) ? "Messages ($userpm_num)" : 'Messages';
            echo '<li><a href="' . spp_route_url('account', 'pms') . '">' . $label . '</a></li>';
            echo '<li><a href="index.php?n=account&sub=userlist">Userlist</a></li>';
        }

        if ((!empty($user['g_is_admin']) && (int)$user['g_is_admin'] === 1)
            || (!empty($user['g_is_supadmin']) && (int)$user['g_is_supadmin'] === 1)) {
            echo '<li><a href="index.php?n=admin">Admin Panel</a></li>';
        }

        if (!empty($accountCharacters) && is_array($accountCharacters)) {
            $grouped = array();
            foreach ($accountCharacters as $char) {
                $characterRealmId = (int)($char['realm_id'] ?? 0);
                if ($characterRealmId <= 0) {
                    $characterRealmId = $currentRealmId > 0 ? $currentRealmId : 1;
                }

                $realmGroupName = trim((string)($char['realm_name'] ?? ''));
                if ($realmGroupName === '') {
                    $realmGroupName = spp_realm_display_name($characterRealmId);
                }

                $char['realm_id'] = $characterRealmId;
                $char['realm_name'] = $realmGroupName;
                if (!isset($grouped[$realmGroupName])) {
                    $grouped[$realmGroupName] = array();
                }
                $grouped[$realmGroupName][] = $char;
            }

            foreach ($grouped as $realmName => $chars) {
                echo '<li class="menu-realm-label"><strong>' . htmlspecialchars($realmName) . ' (' . count($chars) . ')</strong></li>';
                foreach ($chars as $character) {
                    $characterRealmId = (int)($character['realm_id'] ?? 0);
                    $characterGuid = (int)($character['guid'] ?? 0);
                    $characterName = (string)($character['name'] ?? '');
                    $isActive = false;

                    if ($currentCharacterName !== '') {
                        $isActive = ($currentRealmId === $characterRealmId && strcasecmp($currentCharacterName, $characterName) === 0);
                    } elseif ($selectedCharacterGuid > 0) {
                        $isActive = ($currentRealmId === $characterRealmId && $selectedCharacterGuid === $characterGuid);
                    }

                    $classSlug = spp_account_menu_class_slug((int)($character['class'] ?? 0));
                    $itemClass = $isActive ? 'char-item active-char class-' . $classSlug : 'char-item class-' . $classSlug;
                    $armoryHref = 'index.php?n=server&sub=character&realm=' . $characterRealmId
                        . '&character=' . rawurlencode($characterName);

                    echo '<li class="' . $itemClass . '"><a href="' . htmlspecialchars($armoryHref) . '">'
                        . '<span class="char-name">' . htmlspecialchars($characterName) . '</span>'
                        . ' <span class="level">(Lvl ' . (int)($character['level'] ?? 0) . ')</span>'
                        . '</a></li>';
                }
            }
            echo '<li class="menu-spacer"></li>';
        }

        echo '<li><form method="post" action="index.php?n=account&sub=login" class="account-logout-form">'
            . '<input type="hidden" name="action" value="logout"/>'
            . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars((string)spp_csrf_token('account_logout'), ENT_QUOTES) . '"/>'
            . '<input type="hidden" name="returnto" value="' . htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? 'index.php'), ENT_QUOTES) . '"/>'
            . '<button type="submit" class="account-logout-button">Logout</button>'
            . '</form></li>';
    }

    if ($asList) echo '</ul></li>';
}


function write_subheader($subheader){
	global $currtmp;
    echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" class="legacy-subheader-table">
<tbody><tr>
    <td width="24"><img src="'.$currtmp.'/images/subheader-left-sword.gif" height="20" width="24" alt=""/></td>
    <td class="legacy-subheader-table__fill" width="100%"><b class="legacy-subheader-table__label">'.$subheader.':</b></td>
    <td width="10"><img src="'.$currtmp.'/images/subheader-right.gif" height="20" width="10" alt=""/></td>
</tr>
</tbody></table>';
}
function write_metalborder_header(){
	global $currtmp;
    echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" class="legacy-metalborder-table">
<tbody>
<tr>
    <td width="12"><img src="'.$currtmp.'/images/metalborder-top-left.gif" height="12" width="12" alt=""/></td>
    <td class="legacy-metalborder-table__top-fill"></td>
    <td width="12"><img src="'.$currtmp.'/images/metalborder-top-right.gif" height="12" width="12" alt=""/></td>
</tr>
<tr>
    <td class="legacy-metalborder-table__side-fill legacy-metalborder-table__side-fill--left"></td>
    <td>
';
}

function write_metalborder_footer(){
	global $currtmp;
    echo '        </td>
        <td class="legacy-metalborder-table__side-fill legacy-metalborder-table__side-fill--right"></td>
    </tr>
    <tr>
        <td><img src="'.$currtmp.'/images/metalborder-bot-left.gif" height="11" width="12" alt=""/></td>
        <td class="legacy-metalborder-table__bottom-fill"></td>
        <td><img src="'.$currtmp.'/images/metalborder-bot-right.gif" height="11" width="12" alt=""/></td>
    </tr>
    </tbody>
</table>
';
}

function write_form_tool(){
	global $currtmp;
    $template_href = $currtmp . "/";
?>
        <div id="form_tool">
            <ul id="bbcode_tool">
                <li id="bbcode_b"><a href="#"><img src="<?php echo $template_href;?>images/button-bold.gif" alt="Bold" title="Bold"></a></li>
                <li id="bbcode_i"><a href="#"><img src="<?php echo $template_href;?>images/button-italic.gif" alt="Italic" title="Italic"></a></li>
                <li id="bbcode_u"><a href="#"><img src="<?php echo $template_href;?>images/button-underline.gif" alt="Underline" title="Underline"></a></li>
                <li id="bbcode_url"><a href="#"><img src="<?php echo $template_href;?>images/button-url.gif" alt="Link" title="Link"></a></li>
                <li id="bbcode_img"><a href="#"><img src="<?php echo $template_href;?>images/button-img.gif" alt="Image" title="Image"></a></li>
                <li id="bbcode_blockquote"><a href="#"><img src="<?php echo $template_href;?>images/button-quote.gif" alt="Quote" title="Quote"></a></li>
            </ul>
            <ul id="text_tool">
                <li id="text_size"><a href="#"><img src="<?php echo $template_href;?>images/button-size.gif" alt="Text Size" title="Text Size"></a>
                    <ul>
                        <li id="text_size-hugesize"><a href="#">Huge</a></li>
                        <li id="text_size-largesize"><a href="#">Large</a></li>
                        <li id="text_size-mediumsize"><a href="#">Medium</a></li>
                    </ul>
                </li>
                <li id="text_color"><a href="#"><img src="<?php echo $template_href;?>images/button-color.gif" alt="Text Color" title="Text Color"></a>
                    <ul>
                        <li id="text_color-red"><a href="#">Red</a></li>
                        <li id="text_color-green"><a href="#">Green</a></li>
                        <li id="text_color-blue"><a href="#">Blue</a></li>
                        <li id="text_color-custom"><a href="#">Custom</a></li>
                    </ul>
                </li>
                <li id="text_align"><a href="#"><img src="<?php echo $template_href;?>images/button-list.gif" alt="Text Alignment" title="Text Alignment"></a>
                    <ul>
                        <li id="text_align-left"><a href="#">Left</a></li>
                        <li id="text_align-right"><a href="#">Right</a></li>
                        <li id="text_align-center"><a href="#">Center</a></li>
                        <li id="text_align-justify"><a href="#">Justify</a></li>
                    </ul>
                </li>
                <li id="text_smile"><a href="#"><img src="<?php echo $template_href;?>images/button-emote.gif" alt="Smileys" title="Smileys"></a>
                    <ul>
<?php
$smiles = load_smiles();
$smilepath = (string)spp_config_generic('smiles_path', 'images/smiles/');
foreach($smiles as $smile):
    $smilename = ucfirst(str_replace('.gif','',str_replace('.png','',$smile)));
?>
                        <li id="text_smile-<?php echo $smilepath.$smile;?>"><a href="#" title="<?php echo $smilename;?>"><img src="<?php echo $smilepath.$smile;?>" alt="<?php echo $smilename;?>"></a></li>
<?php
endforeach;
?>
                    </ul>
                </li>
            </ul>
        </div>
<?php
}

function random_screenshot(){
  $fa = array();
  if ($handle = opendir('images/screenshots/thumbs/')) {
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && $file != "Thumbs.db" && $file != "index.html") {
            $fa[] = $file;
        }
    }
    closedir($handle);
  }
  $fnum = count($fa);
  $fpos = rand(0, $fnum-1);
  return $fa[$fpos];
}
function build_pathway(){
    global $pathway_info;
    global $title_str,$pathway_str;
    $path_info2 = array($pathway_info);
    $path_c = count($path_info2);
    $pathway_info[$path_c-1]['link'] = '';
    $pathway_str = '';
    if(empty($_REQUEST['n']) || !is_array($pathway_info))$pathway_str .= ' <b><u>Home</u></b>';
    else $pathway_str .= '<a href="./">Home</a>';
    if(is_array($pathway_info)){
        foreach($pathway_info as $newpath){
            if(isset($newpath['title'])){
                if(empty($newpath['link'])) $pathway_str .= ' &raquo; '.$newpath['title'].'';
                else $pathway_str .= ' &raquo; <a href="'.$newpath['link'].'">'.$newpath['title'].'</a>';
                $title_str .= ' &raquo; '.$newpath['title'];
            }
        }
    }
    $pathway_str .= '';
}
// !!!!!!!!!!!!!!!! //
build_pathway();

function load_banners($type){
    $realmMap = $GLOBALS['realmDbMap'] ?? [];
    $realmId  = is_array($realmMap) && !empty($realmMap) ? spp_resolve_realm_id($realmMap) : 1;
    $pdo = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE type=? ORDER BY num_click DESC");
    $stmt->execute([(int)$type]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function paginate($num_pages, $cur_page, $link_to){
  $pages = array();
  $link_to_all = false;
  if ($cur_page == -1)
  {
    $cur_page = 1;
    $link_to_all = true;
  }
  if ($num_pages <= 1)
    $pages = array('1');
  else
  {
    $tens = floor($num_pages/10);
    for ($i=1;$i<=$tens;$i++)
    {
      $tp = $i*10;
      $pages[$tp] = "<a href='$link_to&p=$tp'>$tp</a>";
    }
    if ($cur_page > 3)
    {
      $pages[1] = "<a href='$link_to&p=1'>1</a>";
    }
    for ($current = $cur_page - 2, $stop = $cur_page + 3; $current < $stop; ++$current)
    {
      if ($current < 1 || $current > $num_pages){
        continue;
      }elseif ($current != $cur_page || $link_to_all){
        $pages[$current] = "<a href='$link_to&p=$current'>$current</a>";
      }else{
        $pages[$current] = '[ '.$current.' ]';
      }
    }
    if ($cur_page <= ($num_pages-3))
    {
      $pages[$num_pages] = "<a href='$link_to&p=$num_pages'>$num_pages</a>";
    }
  }
  $pages = array_unique($pages);
  ksort($pages);
  $pp = implode(' ', $pages);
  return str_replace('//','/',$pp);
}

function builddiv_start($type = 0, $title = "No title set", $realm = 0, $forumnav = false, $forumId = 0, $forumClosed = false)
{
    echo '<div class="modern-wrapper">';
    echo '<div class="modern-header">';
    echo '<div class="modern-header-bar">';
    echo '<div class="modern-header-title">' . htmlspecialchars($title) . '</div>';

    $headerControls = array();

    // === Optional Realm Selector ===
    if ($realm == 1) {
        $realmMap = $GLOBALS['allEnabledRealmDbMap'] ?? $GLOBALS['realmDbMap'] ?? array();
        $realmId = is_array($realmMap) && !empty($realmMap) ? spp_resolve_realm_id($realmMap) : (int)($_GET['realm'] ?? 1);
        $availableRealms = array();

        if (is_array($realmMap) && !empty($realmMap)) {
            foreach ($realmMap as $candidateRealmId => $realmInfo) {
                $candidateRealmId = (int)$candidateRealmId;
                if ($candidateRealmId <= 0) {
                    continue;
                }

                if (function_exists('spp_realm_capabilities')) {
                    $candidateCapabilities = spp_realm_capabilities($realmMap, $candidateRealmId);
                    $hasVisibleService = !empty($candidateCapabilities['services']['chars']['available'])
                        || !empty($candidateCapabilities['services']['world']['available']);
                    if (!$hasVisibleService) {
                        continue;
                    }
                }

                $availableRealms[$candidateRealmId] = array(
                    'id' => $candidateRealmId,
                    'name' => spp_realm_display_name($candidateRealmId, $realmMap),
                );
            }
        }

        $hideRealmPicker = (($GLOBALS['n_main'] ?? ($_GET['n'] ?? '')) === 'server')
            && (($_GET['sub'] ?? '') === 'realmstatus');

        if (count($availableRealms) > 1 && !$hideRealmPicker) {
            $realmFormAction = $_SERVER['PHP_SELF'] ?? 'index.php';
            if (!is_string($realmFormAction) || $realmFormAction === '') {
                $realmFormAction = 'index.php';
            }

            $realmControl = '<form method="get" action="' . htmlspecialchars($realmFormAction) . '" class="realm-select-form">';

            // preserve query params like n=statistic
            foreach ($_GET as $key => $value) {
                if ($key !== 'realm') {
                    $realmControl .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '" />';
                }
            }

            $realmControl .= '<label for="realm" class="realm-select-label">Select Realm:</label>';
            $realmControl .= '<select id="realm" name="realm" onchange="this.form.submit()" class="realm-select-input">';
            foreach ($availableRealms as $realmOption) {
                $realmControl .= '<option value="' . (int)$realmOption['id'] . '"' . ($realmId === (int)$realmOption['id'] ? ' selected' : '') . '>'
                    . htmlspecialchars($realmOption['name']) . '</option>';
            }
            $realmControl .= '</select>';
            $realmControl .= '</form>';
            $headerControls[] = $realmControl;
        }
    }

    if ($forumnav === true) {
        $forumActions = '<div class="header-action-group forum-actions">';

        $sub = $_GET['sub'] ?? '';
        $fid = (int)($_GET['fid'] ?? 0);
        $tid = (int)($_GET['tid'] ?? 0);

        if ($fid == 0 && $tid > 0) {
            $topic = get_topic_byid($tid);
            if (!empty($topic['forum_id'])) {
                $fid = (int)$topic['forum_id'];
            }
        }

        if ($sub === 'viewtopic' && !$forumClosed) {
            $forumActions .= '<a href="index.php?n=forum&sub=post&action=newpost&t=' . $tid . '&fid=' . $fid . '" class="btn primary">Reply</a>';
        } elseif ($sub === 'post') {
            $postAction = (string)($_GET['action'] ?? '');
            $label = str_starts_with($postAction, 'newpost') || str_starts_with($postAction, 'donewpost')
                ? 'Submit Reply'
                : 'Submit Topic';
            $forumActions .= '<button type="submit" form="forum-post-form" class="btn primary">' . $label . '</button>';
        }

        $forumActions .= '<a href="index.php?n=forum" class="btn secondary">Back to Forums</a>';
        $forumActions .= '</div>';
        $headerControls[] = $forumActions;
    }

    if (($_GET['n'] ?? '') === 'admin' && ($_GET['sub'] ?? '') !== '') {
        $headerControls[] = '<div class="header-action-group forum-actions"><a href="index.php?n=admin" class="btn secondary">Back to Admin Panel</a></div>';
    }

    $customHeaderActions = $GLOBALS['builddiv_header_actions'] ?? '';
    if (is_string($customHeaderActions) && $customHeaderActions !== '') {
        $headerControls[] = '<div class="header-action-group builddiv-actions">' . $customHeaderActions . '</div>';
        unset($GLOBALS['builddiv_header_actions']);
    }

    if (!empty($headerControls)) {
        echo '<div class="modern-header-controls">' . implode('', $headerControls) . '</div>';
    }

    echo '</div>';
    echo '</div>';
    echo '<div class="modern-content">';
}

function builddiv_end() {
  echo '</div></div>';
}

function get_realm_info()
{
    $realmId = (int)($_GET['realm'] ?? 1);
    $resolveRealmName = static function (int $id, string $fallback = ''): string {
        if (function_exists('spp_get_armory_realm_name')) {
            $resolved = spp_get_armory_realm_name($id);
            if (is_string($resolved) && $resolved !== '') {
                return $resolved;
            }
        }
        return $fallback;
    };

    switch ($realmId) {
        case 1:
            return [
                'id'   => 1,
                'db'   => 'classiccharacters',
                'world'=> 'classicmangos',
                'bots' => 'classicplayerbots',
                'logs' => 'classiclogs',
                'name' => $resolveRealmName(1),
                'exp'  => 0
            ];
        case 2:
            return [
                'id'   => 2,
                'db'   => 'tbccharacters',
                'world'=> 'tbcmangos',
                'bots' => 'tbcplayerbots',
                'logs' => 'tbclogs',
                'name' => $resolveRealmName(2),
                'exp'  => 1
            ];
        case 3:
            return [
                'id'   => 3,
                'db'   => 'wotlkcharacters',
                'world'=> 'wotlkmangos',
                'bots' => 'wotlkplayerbots',
                'logs' => 'wotlklogs',
                'name' => $resolveRealmName(3),
                'exp'  => 2
            ];
        default:
            return [
                'id'   => 1,
                'db'   => 'classiccharacters',
                'world'=> 'classicmangos',
                'bots' => 'classicplayerbots',
                'logs' => 'classiclogs',
                'name' => $resolveRealmName(1),
                'exp'  => 0
            ];
    }
}


?>

<?php

function compact_paginate($current, $total, $base) {
      $html = '';
      $range = 1;
      $show_first = $current > $range + 2;
      $show_last  = $current < $total - ($range + 1);

      if ($current > 1)
        $html .= '<a href="'.$base.'&p='.($current-1).'">&lt; Prev</a> ';

      if ($show_first)
        $html .= '<a href="'.$base.'&p=1">1</a> ... ';

      for ($i = max(1, $current-$range); $i <= min($total, $current+$range); $i++) {
        $active = $i == $current ? 'class="active"' : '';
        $html .= '<a '.$active.' href="'.$base.'&p='.$i.'">'.$i.'</a> ';
      }
      if ($show_last)
        $html .= '... <a href="'.$base.'&p='.$total.'">'.$total.'</a> ';

      if ($current < $total)
        $html .= '<a href="'.$base.'&p='.($current+1).'">Next &gt;</a>';


      return $html;
    }
	
function render_page_size_form($items_per_page, $extra_params = [], $show_bots = true, $show_per_page = true) {
    $publicTerms = spp_terminology_public();
    // detect current page params
    $n   = isset($_GET['n'])   ? htmlspecialchars($_GET['n'])   : '';
    $sub = isset($_GET['sub']) ? htmlspecialchars($_GET['sub']) : '';

    // persistent GET vars
    $persist = ['char', 'lvl', 'minlvl', 'maxlvl', 'class', 'race', 'p'];
    $persist = array_unique(array_merge($persist, $extra_params));

    echo '<form method="get" class="page-size-form">';
    if ($n)   echo '<input type="hidden" name="n" value="' . $n . '">';
    if ($sub) echo '<input type="hidden" name="sub" value="' . $sub . '">';

    // preserve other query parameters
    foreach ($persist as $param) {
        if (isset($_GET[$param]) && $param !== 'per_page') {
            echo '<input type="hidden" name="' . htmlspecialchars($param) . 
                 '" value="' . htmlspecialchars($_GET[$param]) . '">';
        }
    }

    // per-page selector
    if ($show_per_page) {
        echo '<label for="per_page">Show:</label>';
        echo '<select id="per_page" name="per_page" onchange="this.form.submit()">';
        foreach ([10, 25, 50, 100] as $opt) {
            $sel = ($items_per_page == $opt) ? 'selected' : '';
            echo "<option value=\"$opt\" $sel>$opt</option>";
        }
        echo '</select> <span>per page</span>';
    }

    // show bots checkbox
    if ($show_bots) {
        $checked = !empty($_GET['show_bots']) ? 'checked' : '';
        echo '<label class="list-page-show-bots-toggle">';
        echo '<input type="hidden" name="show_bots" value="0">';
        echo '<input type="checkbox" name="show_bots" value="1" onchange="this.form.submit()" ' . $checked . '>';
        echo ' ' . htmlspecialchars($publicTerms['include_characters']) . '</label>';
    }

    echo '</form>';
}

?>

<?php
function render_character_pagination($p, $pnum, $items_per_page, $realmId, $includeBots, $search = '', $onlineOnly = false, $factionFilter = 'all', $botCount = null, $urlBase = 'index.php?n=server&sub=chars', $matchedCount = null) {
    $publicTerms = spp_terminology_public();
    $urlstring = $urlBase
      . '&realm=' . $realmId
      . '&per_page=' . $items_per_page
      . '&show_bots=' . ($includeBots ? '1' : '0')
      . '&online=' . ($onlineOnly ? '1' : '0')
      . '&faction=' . urlencode($factionFilter)
      . ($search !== '' ? '&search=' . urlencode($search) : '');
    ?>
    <div class="pagination-controls">
      <div class="page-links">
        <?php echo compact_paginate($p, $pnum, htmlspecialchars($urlstring)); ?>
      </div>
      <div class="page-size-form">
        <form method="get" class="page-size-form js-char-controls-form">
          <input type="hidden" name="n" value="server">
          <input type="hidden" name="sub" value="chars">
          <input type="hidden" name="realm" value="<?php echo $realmId; ?>">
          <input type="hidden" name="p" value="1">
          <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
          <input type="hidden" name="online" value="<?php echo $onlineOnly ? '1' : '0'; ?>">
          <input type="hidden" name="faction" value="<?php echo htmlspecialchars($factionFilter); ?>">

          <label for="per_page">Show:</label>
          <select id="per_page" name="per_page" onchange="this.form.submit()">
            <?php foreach ([10,25,50,100] as $opt): ?>
              <option value="<?php echo $opt; ?>" <?php if ($items_per_page == $opt) echo 'selected'; ?>>
                <?php echo $opt; ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span>per page</span>

          <?php if ($matchedCount !== null): ?>
            <span class="pagination-meta"><?php echo number_format((int)$matchedCount); ?> matched</span>
          <?php endif; ?>
          <?php if ($botCount !== null): ?>
            <span class="pagination-meta"><?php echo htmlspecialchars($publicTerms['characters']); ?>: <?php echo (int)$botCount; ?></span>
          <?php endif; ?>
        </form>
      </div>
    </div>
    <?php
}
?>
