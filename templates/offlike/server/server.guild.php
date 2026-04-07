<?php
$siteRoot = dirname(__DIR__, 3);
require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/components/forum/forum.func.php');
require_once($siteRoot . '/components/admin/admin.playerbots.helpers.php');
require_once($siteRoot . '/app/server/guild-page.php');

if (!function_exists('spp_class_icon_url')) {
    function spp_class_icon_url($classId)
    {
        $classId = (int)$classId;
        $icons = [
            1 => 'class-1',
            2 => 'class-2',
            3 => 'class-3',
            4 => 'class-4',
            5 => 'class-5',
            6 => 'class-6',
            7 => 'class-7',
            8 => 'class-8',
            9 => 'class-9',
            11 => 'class-11',
        ];

        if (!isset($icons[$classId])) {
            return spp_armory_icon_url('404.png');
        }

        return spp_resolve_armory_icon_url($icons[$classId]);
    }
}

if (!function_exists('spp_race_icon_url')) {
    function spp_race_icon_url($raceId, $gender)
    {
        $raceId = (int)$raceId;
        $gender = ((int)$gender === 1) ? 'female' : 'male';
        $icons = [
            1 => 'achievement_character_human_' . $gender,
            2 => 'achievement_character_orc_' . $gender,
            3 => 'achievement_character_dwarf_' . $gender,
            4 => 'achievement_character_nightelf_' . $gender,
            5 => 'achievement_character_undead_' . $gender,
            6 => 'achievement_character_tauren_' . $gender,
            7 => 'achievement_character_gnome_' . $gender,
            8 => 'achievement_character_troll_' . $gender,
            10 => 'achievement_character_bloodelf_' . $gender,
            11 => 'achievement_character_draenei_' . $gender,
        ];

        if (!isset($icons[$raceId])) {
            return spp_armory_icon_url('404.png');
        }

        return spp_resolve_armory_icon_url($icons[$raceId]);
    }
}

if (!function_exists('spp_guild_roster_sort_compare')) {
    function spp_guild_roster_sort_compare(array $left, array $right, $sortBy, $sortDir, array $classNames, array $raceNames, array $memberAverageItemLevels) {
        $direction = strtoupper($sortDir) === 'ASC' ? 1 : -1;
        $leftGuid = (int)($left['guid'] ?? 0);
        $rightGuid = (int)($right['guid'] ?? 0);
        $leftAvgItemLevel = (float)($memberAverageItemLevels[$leftGuid] ?? 0);
        $rightAvgItemLevel = (float)($memberAverageItemLevels[$rightGuid] ?? 0);

        switch ($sortBy) {
            case 'status':
                $comparison = ((int)($left['online'] ?? 0) <=> (int)($right['online'] ?? 0));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'name':
                $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                break;
            case 'race':
                $comparison = strcasecmp((string)($raceNames[(int)($left['race'] ?? 0)] ?? 'Unknown'), (string)($raceNames[(int)($right['race'] ?? 0)] ?? 'Unknown'));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'class':
                $comparison = strcasecmp((string)($classNames[(int)($left['class'] ?? 0)] ?? 'Unknown'), (string)($classNames[(int)($right['class'] ?? 0)] ?? 'Unknown'));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'level':
                $comparison = ((int)($left['level'] ?? 0) <=> (int)($right['level'] ?? 0));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'ilvl':
                $comparison = ($leftAvgItemLevel <=> $rightAvgItemLevel);
                if ($comparison === 0) {
                    $comparison = ((int)($left['level'] ?? 0) <=> (int)($right['level'] ?? 0));
                }
                break;
            case 'rank':
                $comparison = ((int)($left['rank'] ?? 0) <=> (int)($right['rank'] ?? 0));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['rank_name'] ?? ''), (string)($right['rank_name'] ?? ''));
                }
                break;
            default:
                $comparison = ((int)($left['rank'] ?? 0) <=> (int)($right['rank'] ?? 0));
                if ($comparison === 0) {
                    $comparison = ((int)($right['level'] ?? 0) <=> (int)($left['level'] ?? 0));
                }
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
        }

        return $comparison * $direction;
    }
}

if (!function_exists('spp_guild_roster_sort_url')) {
    function spp_guild_roster_sort_url($baseUrl, $sortBy, $currentSortBy, $currentSortDir) {
        if ($currentSortBy === $sortBy) {
            $nextSortDir = strtoupper($currentSortDir) === 'ASC' ? 'DESC' : 'ASC';
        } else {
            $nextSortDir = $sortBy === 'status' ? 'DESC' : 'ASC';
        }
        return $baseUrl . '&sort=' . rawurlencode($sortBy) . '&dir=' . rawurlencode($nextSortDir) . '&p=1';
    }
}

if (!function_exists('spp_guild_apply_flavor_sql_fallback')) {
    function spp_guild_apply_flavor_sql_fallback(PDO $charsPdo, int $guildId, array $profile, string &$errorMessage = ''): bool
    {
        $guildId = (int)$guildId;
        if ($guildId <= 0) {
            $errorMessage = 'Missing guild id for guild flavor fallback.';
            return false;
        }

        $stmt = $charsPdo->prepare("SELECT guid FROM guild_member WHERE guildid = ? ORDER BY guid ASC");
        $stmt->execute(array($guildId));
        $memberGuids = array_values(array_unique(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: array()))));
        if (empty($memberGuids)) {
            $errorMessage = 'Guild has no members.';
            return false;
        }

        $strategyValues = array_fill_keys(spp_admin_playerbots_strategy_keys(), '');
        foreach ($strategyValues as $strategyKey => $unused) {
            $strategyValues[$strategyKey] = spp_admin_playerbots_normalize_strategy_value((string)($profile[$strategyKey] ?? ''));
        }

        $guidPlaceholders = implode(',', array_fill(0, count($memberGuids), '?'));
        $strategyKeys = spp_admin_playerbots_strategy_keys();
        $keyPlaceholders = implode(',', array_fill(0, count($strategyKeys), '?'));

        try {
            $charsPdo->beginTransaction();

            $deleteStmt = $charsPdo->prepare("
                DELETE FROM ai_playerbot_db_store
                WHERE guid IN ($guidPlaceholders)
                  AND preset = 'default'
                  AND `key` IN ($keyPlaceholders)
            ");
            $deleteStmt->execute(array_merge($memberGuids, $strategyKeys));

            $insertStmt = $charsPdo->prepare("
                INSERT INTO ai_playerbot_db_store (guid, `key`, value, preset)
                VALUES (?, ?, ?, 'default')
            ");

            foreach ($memberGuids as $memberGuid) {
                foreach ($strategyValues as $strategyKey => $strategyValue) {
                    if ($strategyValue === '') {
                        continue;
                    }
                    $insertStmt->execute(array((int)$memberGuid, (string)$strategyKey, (string)$strategyValue));
                }
            }

            $charsPdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($charsPdo->inTransaction()) {
                $charsPdo->rollBack();
            }
            $errorMessage = $e->getMessage();
            return false;
        }
    }
}

if (!function_exists('spp_mangos_soap_quote_argument')) {
    function spp_mangos_soap_quote_argument($value)
    {
        return '"' . addcslashes((string)$value, "\\\"") . '"';
    }
}

if (!function_exists('spp_mangos_soap_format_trailing_argument')) {
    function spp_mangos_soap_format_trailing_argument($value)
    {
        return spp_mangos_soap_quote_argument((string)$value);
    }
}

if (!function_exists('spp_guild_orders_parse_note_row')) {
    function spp_guild_orders_parse_note_row(string $note): array
    {
        $trimmed = trim($note);
        if ($trimmed === '') {
            return array(
                'status_key' => 'share-fallback',
                'status_label' => 'Share fallback',
                'parsed_type' => 'Share',
                'target' => 'auto',
                'amount' => 'auto',
                'normalized' => 'Share: auto',
                'error' => '',
                'is_valid' => true,
                'is_blank' => true,
            );
        }

        $parsed = spp_admin_playerbots_validate_order_note($trimmed);
        if (empty($parsed['valid'])) {
            return array(
                'status_key' => 'invalid',
                'status_label' => 'Invalid',
                'parsed_type' => 'Invalid',
                'target' => '—',
                'amount' => '—',
                'normalized' => '',
                'error' => (string)($parsed['error'] ?? 'Invalid order note.'),
                'is_valid' => false,
                'is_blank' => false,
            );
        }

        $type = (string)($parsed['type'] ?? '');
        if ($type === 'skip order') {
            return array(
                'status_key' => 'skip',
                'status_label' => 'Skip',
                'parsed_type' => 'Skip',
                'target' => '—',
                'amount' => '—',
                'normalized' => 'skip order',
                'error' => '',
                'is_valid' => true,
                'is_blank' => false,
            );
        }

        $amount = $parsed['amount'] ?? null;
        return array(
            'status_key' => 'manual',
            'status_label' => 'Manual',
            'parsed_type' => ucfirst($type !== '' ? $type : 'Manual'),
            'target' => (string)($parsed['target'] ?? ''),
            'amount' => $amount !== null && $amount !== '' ? (string)$amount : '—',
            'normalized' => (string)($parsed['normalized'] ?? ''),
            'error' => '',
            'is_valid' => true,
            'is_blank' => false,
        );
    }
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die("Realm DB map not loaded");
}

$realmId = spp_resolve_realm_id($realmMap);
if (!isset($realmMap[$realmId])) {
    die("Invalid realm ID");
}

$realmDB = $realmMap[$realmId]['chars'];
$realmWorldDB = $realmMap[$realmId]['world'];
$armoryRealm = spp_get_armory_realm_name($realmId) ?? '';
$currtmp = '/armory';
$charsPdo  = spp_get_pdo('chars', $realmId);
$realmdPdo = spp_get_pdo('realmd', $realmId);

$guildId = isset($_GET['guildid']) ? (int)$_GET['guildid'] : 0;
if ($guildId < 1) {
    echo "<div style='padding:24px;color:#f5c46b;'>No guild selected.</div>";
    return;
}

$classNames = [
  1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
  6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'
];
$raceNames = [
  1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead',
  6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei'
];
$allianceRaces = [1, 3, 4, 7, 11, 22, 25, 29];

$stmt = $charsPdo->prepare("SELECT guildid, name, leaderguid, motd FROM {$realmDB}.guild WHERE guildid=?");
$stmt->execute([(int)$guildId]);
$guild = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$guild) {
    echo "<div style='padding:24px;color:#f5c46b;'>Guild not found.</div>";
    return;
}

$guildPageState = spp_guild_load_page_state(array(
    'realmMap' => $realmMap,
    'realmId' => $realmId,
    'guildId' => $guildId,
    'guild' => $guild,
    'realmDB' => $realmDB,
    'realmWorldDB' => $realmWorldDB,
    'charsPdo' => $charsPdo,
    'realmdPdo' => $realmdPdo,
    'armoryRealm' => $armoryRealm,
    'classNames' => $classNames,
    'raceNames' => $raceNames,
    'allianceRaces' => $allianceRaces,
    'user' => $user,
));
extract($guildPageState, EXTR_SKIP);

$guildOrderShareBlock = trim((string)($guildOrderShareBlock ?? ($guildInfo ?? ($guild['info'] ?? ''))));
$guildOrderSharePreview = array('errors' => array(), 'entries' => array());
if ($guildOrderShareBlock !== '') {
    $guildOrderSharePreview = spp_admin_playerbots_validate_share_block($guildOrderShareBlock);
}

$guildOrderMeetingPreview = is_array($meetingPreview ?? null) ? $meetingPreview : array();
$guildOrderRows = array();
$guildOrderStats = array(
    'manual' => 0,
    'share-fallback' => 0,
    'skip' => 0,
    'invalid' => 0,
);

foreach (is_array($members ?? null) ? $members : array() as $member) {
    $officerNote = trim((string)($member['offnote'] ?? ''));
    $parsedOrder = spp_guild_orders_parse_note_row($officerNote);
    $statusKey = (string)($parsedOrder['status_key'] ?? 'invalid');
    if (!isset($guildOrderStats[$statusKey])) {
        $guildOrderStats[$statusKey] = 0;
    }
    $guildOrderStats[$statusKey]++;

    $guildOrderRows[] = array(
        'guid' => (int)($member['guid'] ?? 0),
        'name' => (string)($member['name'] ?? ''),
        'offnote' => $officerNote,
        'parsed_type' => (string)($parsedOrder['parsed_type'] ?? 'Invalid'),
        'target' => (string)($parsedOrder['target'] ?? '—'),
        'amount' => (string)($parsedOrder['amount'] ?? '—'),
        'status_key' => $statusKey,
        'status_label' => (string)($parsedOrder['status_label'] ?? 'Invalid'),
        'normalized' => (string)($parsedOrder['normalized'] ?? ''),
        'error' => (string)($parsedOrder['error'] ?? ''),
        'is_valid' => !empty($parsedOrder['is_valid']),
        'is_blank' => !empty($parsedOrder['is_blank']),
    );
}

$guildOrderSummary = array(
    'manual' => (int)($guildOrderStats['manual'] ?? 0),
    'share_fallback' => (int)($guildOrderStats['share-fallback'] ?? 0),
    'skip' => (int)($guildOrderStats['skip'] ?? 0),
    'invalid' => (int)($guildOrderStats['invalid'] ?? 0),
);

$guildOrderPreviewRows = is_array($guildOrdersState['order_preview'] ?? null) ? $guildOrdersState['order_preview'] : array();
$guildOrderPreviewByGuid = array();
foreach ($guildOrderPreviewRows as $guildOrderPreviewRow) {
    $guildOrderPreviewByGuid[(int)($guildOrderPreviewRow['guid'] ?? 0)] = $guildOrderPreviewRow;
}

$isCompactLeaderRoster = false;

?>
<?php if (!empty($GLOBALS['spp_guild_redirect'])): ?>
<meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($GLOBALS['spp_guild_redirect'], ENT_QUOTES); ?>">
<?php endif; ?>
<div class="guild-page">
<div class="guild-detail">
  <div class="guild-hero">
      <div class="guild-hero-main">
        <img class="guild-crest" src="<?php echo $crest; ?>" alt="<?php echo htmlspecialchars($factionName); ?>">
        <div class="guild-hero-copy">
          <h1 class="guild-title"><?php echo htmlspecialchars($guild['name']); ?></h1>
          <p class="guild-subtitle"><?php echo htmlspecialchars($armoryRealm); ?></p>
          <p class="guild-masterline">Guild Master <strong><?php echo $leader ? htmlspecialchars($leader['name']) : 'Unknown'; ?></strong></p>
          <p class="guild-establishedline">Established <strong><?php echo htmlspecialchars($guildEstablishedLabel); ?></strong></p>
        </div>
      </div>
      <div class="guild-meta">
        <div class="guild-meta-card">
          <span class="guild-meta-label">Faction</span>
        <span class="guild-meta-value"><?php echo htmlspecialchars($factionName); ?></span>
      </div>
      <div class="guild-meta-card">
        <span class="guild-meta-label">Members</span>
        <span class="guild-meta-value"><?php echo $guildMembers; ?></span>
      </div>
      <div class="guild-meta-card">
        <span class="guild-meta-label">Average Level</span>
        <span class="guild-meta-value"><?php echo $avgLevel; ?></span>
      </div>
        <div class="guild-meta-card">
          <span class="guild-meta-label">Average iLvl</span>
          <span class="guild-meta-value"><?php echo $guildAverageItemLevel > 0 ? number_format($guildAverageItemLevel, 1) : '-'; ?></span>
        </div>
    </div>
  </div>

<?php if ($canAccessGuildLeaderTab): ?>
  <nav class="guild-tabs" aria-label="Guild page view">
    <a class="guild-tab<?php echo $guildActiveTab === 'overview' ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($guildOverviewUrl); ?>">
      <span class="guild-tab-copy">
        <span class="guild-tab-label">Guild Overview</span>
        <span class="guild-tab-description">Public roster, MOTD, and guild health at a glance.</span>
      </span>
    </a>
    <a class="guild-tab<?php echo $guildActiveTab === 'leader' ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($guildLeaderUrl); ?>">
      <span class="guild-tab-copy">
        <span class="guild-tab-label">Guild Leader / Admin</span>
        <span class="guild-tab-description">Notes, orders, MOTD editing, roster actions, and bot controls.</span>
      </span>
    </a>
  </nav>
<?php endif; ?>

<div class="guild-shell<?php echo $isGuildLeaderTabActive ? ' is-leader-tab' : ''; ?>" data-guild-shell>
    <section class="guild-section guild-roster-panel<?php echo $isGuildLeaderTabActive ? ' is-leader-tab' : ''; ?>" data-guild-roster-panel>
      <h2 class="guild-panel-title"><?php echo $isGuildLeaderTabActive ? 'Guild Leader Roster' : 'Guild Roster'; ?></h2>
      <form method="get" class="guild-filter-grid">
        <input type="hidden" name="n" value="server">
        <input type="hidden" name="sub" value="guild">
        <input type="hidden" name="realm" value="<?php echo $realmId; ?>">
        <input type="hidden" name="guildid" value="<?php echo $guildId; ?>">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($guildActiveTab); ?>">
        <input type="hidden" name="p" value="1">
        <input type="hidden" name="per_page" value="<?php echo $itemsPerPage; ?>">

        <input class="guild-input" type="text" name="name" value="<?php echo htmlspecialchars($selectedName); ?>" placeholder="Search member name...">
        <select class="guild-select" name="class">
          <option value="-1">All Classes</option>
          <?php foreach($classNames as $classId => $className): ?>
            <option value="<?php echo $classId; ?>"<?php echo $selectedClass === $classId ? ' selected' : ''; ?>><?php echo htmlspecialchars($className); ?></option>
          <?php endforeach; ?>
        </select>
        <select class="guild-select" name="rank">
          <option value="-1">All Ranks</option>
          <?php foreach($rankOptions as $rankId => $rankName): ?>
            <option value="<?php echo (int)$rankId; ?>"<?php echo $selectedRank === (int)$rankId ? ' selected' : ''; ?>><?php echo htmlspecialchars($rankName); ?></option>
          <?php endforeach; ?>
        </select>
        <label class="guild-check"><input type="checkbox" name="maxonly" value="1"<?php echo $selectedMax ? ' checked' : ''; ?> data-guild-maxonly-toggle="1"> Max Level Only</label>
        </form>

      <div class="guild-roster-toolbar">
        <div class="guild-summary">Showing <?php echo $resultStart; ?>-<?php echo $resultEnd; ?> of <?php echo $totalMembers; ?> members</div>
        <?php if ($guildRosterAllowsNoteEditing): ?>
          <button class="guild-wip-note guild-wip-note--emphasis" type="submit" name="guild_submit_mode" value="all_notes" form="guild-note-bulk-form">Save All Notes &amp; MOTD</button>
        <?php endif; ?>
      </div>
      <?php if ($guildNoteFeedback !== ''): ?><div class="guild-note-banner"><?php echo htmlspecialchars($guildNoteFeedback); ?></div><?php endif; ?>
      <?php if ($guildNoteError !== ''): ?><div class="guild-note-banner is-error"><?php echo htmlspecialchars($guildNoteError); ?></div><?php endif; ?>

      <?php if ($guildRosterAllowsNoteEditing): ?>
        <form method="post" id="guild-note-bulk-form">
          <input type="hidden" name="guild_form_action" value="save_guild_data">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($guildCsrfToken, ENT_QUOTES); ?>">
        </form>
      <?php endif; ?>
      <div class="guild-roster-table-wrap<?php echo $isGuildLeaderTabActive ? ' is-leader-tab' : ''; ?>" data-guild-roster-scroll>
      <table class="guild-roster">
        <thead>
          <tr>
            <th class="guild-status-cell">
              <a class="<?php echo $sortBy === 'status' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'status', $sortBy, $sortDir)); ?>" title="Sort by online status">-<?php echo $sortBy === 'status' ? ($sortDir === 'ASC' ? ' &uarr;' : ' &darr;') : ''; ?></a>
            </th>
            <th><a class="<?php echo $sortBy === 'name' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'name', $sortBy, $sortDir)); ?>">Name<?php echo $sortBy === 'name' ? ($sortDir === 'ASC' ? ' &uarr;' : ' &darr;') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'race' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'race', $sortBy, $sortDir)); ?>">Race<?php echo $sortBy === 'race' ? ($sortDir === 'ASC' ? ' &uarr;' : ' &darr;') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'class' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'class', $sortBy, $sortDir)); ?>">Class<?php echo $sortBy === 'class' ? ($sortDir === 'ASC' ? ' &uarr;' : ' &darr;') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'level' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'level', $sortBy, $sortDir)); ?>">Level<?php echo $sortBy === 'level' ? ($sortDir === 'ASC' ? ' &uarr;' : ' &darr;') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'ilvl' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'ilvl', $sortBy, $sortDir)); ?>">Avg iLvl<?php echo $sortBy === 'ilvl' ? ($sortDir === 'ASC' ? ' &uarr;' : ' &darr;') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'rank' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'rank', $sortBy, $sortDir)); ?>">Guild Rank<?php echo $sortBy === 'rank' ? ($sortDir === 'ASC' ? ' &uarr;' : ' &darr;') : ''; ?></a></th>
            <th class="guild-note-col">Public Note</th>
            <?php if ($guildRosterShowsOfficerNotes): ?>
              <th class="guild-note-col guild-order-col"><?php echo $isGuildLeaderTabActive ? 'Guild Order' : 'Officer Note'; ?></th>
            <?php endif; ?>
            <?php if ($guildRosterAllowsManagement): ?>
              <th class="guild-action-cell">Actions</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (count($membersPage)): ?>
            <?php foreach($membersPage as $member): ?>
              <?php
                $memberClassName = $classNames[(int)$member['class']] ?? 'Unknown';
                $memberClassSlug = strtolower(str_replace(' ', '', $memberClassName));
                $memberRaceName = $raceNames[(int)$member['race']] ?? 'Unknown';
                $memberIsOnline = !empty($member['online']);
                $memberStatusClass = $memberIsOnline ? 'online' : 'offline';
                $memberStatusLabel = $memberIsOnline ? 'Online' : 'Offline';
                $portrait = get_character_portrait_path($member['guid'], $member['gender'], $member['race'], $member['class']);
                $publicNoteValue = trim((string)($member['pnote'] ?? ''));
                $officerNoteValue = trim((string)($member['offnote'] ?? ''));
                $guildOrderPreviewRow = $guildOrderPreviewByGuid[(int)$member['guid']] ?? array();
                $guildOrderParsed = is_array($guildOrderPreviewRow['parsed'] ?? null) ? $guildOrderPreviewRow['parsed'] : array();
                $guildOrderBadgeClass = (string)($guildOrderPreviewRow['status_badge_class'] ?? 'is-share-fallback');
                $guildOrderBadgeLabel = (string)($guildOrderPreviewRow['status_badge_label'] ?? 'Auto via Share');
                $guildOrderTypeLabel = (string)($guildOrderPreviewRow['type_label'] ?? 'Share fallback');
                $guildOrderTarget = trim((string)($guildOrderParsed['target'] ?? ''));
                $guildOrderAmount = $guildOrderParsed['amount'] ?? null;
                $guildOrderValidationError = trim((string)($guildOrderPreviewRow['validation_error'] ?? ''));
                $guildOrderSummaryParts = array();
                if ($guildOrderTypeLabel !== '') {
                    $guildOrderSummaryParts[] = '<strong>' . htmlspecialchars($guildOrderTypeLabel) . '</strong>';
                }
                if ($guildOrderTarget !== '') {
                    $guildOrderSummaryParts[] = htmlspecialchars($guildOrderTarget);
                }
                if ($guildOrderAmount !== null && $guildOrderAmount !== '' && $guildOrderAmount !== false) {
                    $guildOrderSummaryParts[] = 'x' . (int)$guildOrderAmount;
                }
                if (empty($guildOrderSummaryParts) && $officerNoteValue === '') {
                    $guildOrderSummaryParts[] = '<strong>Auto via Share block</strong>';
                }
              ?>
              <tr>
                <td class="guild-status-cell" title="<?php echo $memberStatusLabel; ?>">
                  <span class="guild-status-dot <?php echo $memberStatusClass; ?>" title="<?php echo $memberStatusLabel; ?>"></span>
                </td>
                <td>
                  <div class="guild-member class-<?php echo $memberClassSlug; ?>">
                    <img class="guild-portrait" src="<?php echo $portrait; ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                    <a href="index.php?n=server&sub=character&realm=<?php echo (int)$realmId; ?>&character=<?php echo urlencode($member['name']); ?>"><?php echo htmlspecialchars($member['name']); ?></a>
                  </div>
                </td>
                <td><img class="guild-race-icon" src="<?php echo htmlspecialchars(spp_race_icon_url($member['race'], $member['gender'])); ?>" alt="<?php echo htmlspecialchars($memberRaceName); ?>" title="<?php echo htmlspecialchars($memberRaceName); ?>"></td>
                <td><img class="guild-class-icon" src="<?php echo htmlspecialchars(spp_class_icon_url($member['class'])); ?>" alt="<?php echo htmlspecialchars($memberClassName); ?>" title="<?php echo htmlspecialchars($memberClassName); ?>"></td>
                <td><?php echo (int)$member['level']; ?></td>
                <td><?php echo !empty($memberAverageItemLevels[(int)$member['guid']]) ? number_format((float)$memberAverageItemLevels[(int)$member['guid']], 1) : '-'; ?></td>
                <td class="guild-rank"><?php echo htmlspecialchars(!empty($member['rank_name']) ? $member['rank_name'] : ('Rank ' . (int)$member['rank'])); ?></td>
                <td class="guild-notes-cell guild-note-col">
                  <?php if ($guildRosterAllowsNoteEditing): ?>
                    <input class="guild-note-input" id="pnote-<?php echo (int)$member['guid']; ?>" type="text" name="pnote[<?php echo (int)$member['guid']; ?>]" maxlength="31" value="<?php echo htmlspecialchars($publicNoteValue); ?>" form="guild-note-bulk-form">
                  <?php else: ?>
                    <div class="guild-note-card">
                      <div class="guild-note-value<?php echo $publicNoteValue === '' ? ' is-empty' : ''; ?>"><?php echo $publicNoteValue !== '' ? htmlspecialchars($publicNoteValue) : 'No public note'; ?></div>
                    </div>
                  <?php endif; ?>
                </td>
                <?php if ($guildRosterShowsOfficerNotes): ?>
                  <td class="guild-notes-cell guild-note-col">
                    <?php if ($isGuildLeaderTabActive): ?>
                      <div class="guild-order-preview">
                        <div class="guild-order-preview-row">
                          <span class="guild-orders-badge <?php echo htmlspecialchars($guildOrderBadgeClass); ?>"><?php echo htmlspecialchars($guildOrderBadgeLabel); ?></span>
                        </div>
                        <div class="guild-order-preview-copy">
                          <?php echo !empty($guildOrderSummaryParts) ? implode(' &middot; ', $guildOrderSummaryParts) : '<strong>Auto via Share block</strong>'; ?>
                        </div>
                        <?php if ($guildRosterAllowsNoteEditing): ?>
                          <details class="guild-order-disclosure">
                            <summary>Order note</summary>
                            <div class="guild-order-disclosure-body">
                              <input class="guild-note-input guild-order-input" id="offnote-<?php echo (int)$member['guid']; ?>" type="text" name="offnote[<?php echo (int)$member['guid']; ?>]" maxlength="31" value="<?php echo htmlspecialchars($officerNoteValue); ?>" form="guild-note-bulk-form" placeholder="blank = auto via Share">
                              <?php if ($guildOrderValidationError !== ''): ?>
                                <div class="guild-order-error"><?php echo htmlspecialchars($guildOrderValidationError); ?></div>
                              <?php else: ?>
                                <div class="guild-orders-help">Leave blank to fall back to the Share block.</div>
                              <?php endif; ?>
                            </div>
                          </details>
                        <?php elseif ($guildOrderValidationError !== ''): ?>
                          <div class="guild-order-error"><?php echo htmlspecialchars($guildOrderValidationError); ?></div>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <div class="guild-note-card">
                        <div class="guild-note-value<?php echo $officerNoteValue === '' ? ' is-empty' : ''; ?>"><?php echo $officerNoteValue !== '' ? htmlspecialchars($officerNoteValue) : ($canEditGuildNotes ? 'Managed in Guild Orders' : 'No officer note'); ?></div>
                      </div>
                    <?php endif; ?>
                  </td>
                <?php endif; ?>
                <?php if ($guildRosterAllowsManagement): ?>
                  <td class="guild-action-cell">
                    <?php if ((int)$member['guid'] === (int)$guild['leaderguid']): ?>
                      <span class="guild-action-placeholder">Guild leader</span>
                    <?php else: ?>
                      <form method="post" class="guild-action-form">
                        <input type="hidden" name="guild_roster_action" value="manage_member">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($guildCsrfToken, ENT_QUOTES); ?>">
                        <input type="hidden" name="target_guid" value="<?php echo (int)$member['guid']; ?>">
                        <div class="guild-action-buttons">
                          <?php if ((int)$member['rank'] > 1): ?>
                            <button class="guild-action-btn is-symbol" type="submit" name="guild_roster_action_type" value="rank_up" title="Rank Up" aria-label="Rank Up">&#9650;</button>
                          <?php else: ?>
                            <span class="guild-action-placeholder-icon" aria-hidden="true"></span>
                          <?php endif; ?>
                          <?php if ((int)$member['rank'] < $maxGuildRankId): ?>
                            <button class="guild-action-btn is-symbol" type="submit" name="guild_roster_action_type" value="rank_down" title="Rank Down" aria-label="Rank Down">&#9660;</button>
                          <?php else: ?>
                            <span class="guild-action-placeholder-icon" aria-hidden="true"></span>
                          <?php endif; ?>
                          <button class="guild-action-btn is-danger" type="submit" name="guild_roster_action_type" value="kick" onclick="return confirm('Kick <?php echo htmlspecialchars(addslashes((string)$member['name'])); ?> from the guild?');">Kick</button>
                        </div>
                      </form>
                    <?php endif; ?>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="<?php echo 8 + ($guildRosterShowsOfficerNotes ? 1 : 0) + ($guildRosterAllowsManagement ? 1 : 0); ?>">No roster members matched the current filter.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      </div>

      <?php if ($pageCount > 1): ?>
        <div class="pagination-controls"><div class="page-links"><?php echo compact_paginate($p, $pageCount, $baseUrl); ?></div></div>
      <?php endif; ?>

    </section>

    <div class="guild-side-stack" data-guild-side-stack>
      <section class="guild-section guild-motd-panel">
        <h3 class="guild-side-title">Message Of The Day</h3>
        <?php if ($guildMotdFeedback !== ''): ?>
          <div class="guild-note-banner"><?php echo htmlspecialchars($guildMotdFeedback); ?></div>
        <?php endif; ?>
        <?php if ($guildMotdError !== ''): ?>
          <div class="guild-note-banner is-error"><?php echo htmlspecialchars($guildMotdError); ?></div>
        <?php endif; ?>

        <?php if ($guildLeaderToolsVisible && $canEditGuildMotd): ?>
          <?php if ($guildRosterAllowsNoteEditing): ?>
            <div class="guild-motd-form">
              <div class="guild-meeting-helper">
                <label class="guild-meeting-label" for="guild-meeting-location-bulk">Meeting Location</label>
                <select
                  id="guild-meeting-location-bulk"
                  class="guild-meeting-select"
                  data-meeting-start="<?php echo htmlspecialchars((string)($meetingPreview['normalized_start'] !== '' ? $meetingPreview['normalized_start'] : '15:00')); ?>"
                  data-meeting-end="<?php echo htmlspecialchars((string)($meetingPreview['normalized_end'] !== '' ? $meetingPreview['normalized_end'] : '18:00')); ?>"
                  data-guild-meeting-select="1"
                  data-guild-meeting-target="guild-motd-input-bulk"
                >
                  <option value="">Choose a named travel location...</option>
                  <?php foreach (($meetingLocationOptions ?? array()) as $meetingLocation): ?>
                    <option value="<?php echo htmlspecialchars((string)$meetingLocation); ?>"<?php echo (string)$meetingLocation === (string)($meetingPreview['location'] ?? '') ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$meetingLocation); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <textarea class="guild-motd-input" id="guild-motd-input-bulk" name="guild_motd" maxlength="128" form="guild-note-bulk-form"><?php echo htmlspecialchars((string)($guild['motd'] ?? '')); ?></textarea>
              <div class="guild-motd-actions">
                <span class="guild-motd-status">Saved together with the roster from the Save All Notes button.</span>
              </div>
              <div class="guild-motd-help">
                <div><strong>Guild meetings format:</strong> <code>Meeting:[location] [start time] [end time]</code></div>
                <p class="guild-motd-example">Meeting:Stormwind City 15:00 18:00</p>
                <p class="guild-motd-example">Meeting:Stormwind City 3:00pm 6:00pm</p>
                <div>Bots begin traveling 30 minutes before the start time and stay until the end time.</div>
              </div>
            </div>
          <?php else: ?>
            <form class="guild-motd-form" method="post">
              <input type="hidden" name="guild_form_action" value="save_guild_data">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($guildCsrfToken, ENT_QUOTES); ?>">
              <div class="guild-meeting-helper">
                <label class="guild-meeting-label" for="guild-meeting-location-single">Meeting Location</label>
                <select
                  id="guild-meeting-location-single"
                  class="guild-meeting-select"
                  data-meeting-start="<?php echo htmlspecialchars((string)($meetingPreview['normalized_start'] !== '' ? $meetingPreview['normalized_start'] : '15:00')); ?>"
                  data-meeting-end="<?php echo htmlspecialchars((string)($meetingPreview['normalized_end'] !== '' ? $meetingPreview['normalized_end'] : '18:00')); ?>"
                  data-guild-meeting-select="1"
                  data-guild-meeting-target="guild-motd-input-single"
                >
                  <option value="">Choose a named travel location...</option>
                  <?php foreach (($meetingLocationOptions ?? array()) as $meetingLocation): ?>
                    <option value="<?php echo htmlspecialchars((string)$meetingLocation); ?>"<?php echo (string)$meetingLocation === (string)($meetingPreview['location'] ?? '') ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$meetingLocation); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <textarea class="guild-motd-input" id="guild-motd-input-single" name="guild_motd" maxlength="128"><?php echo htmlspecialchars((string)($guild['motd'] ?? '')); ?></textarea>
              <div class="guild-motd-actions">
                <button class="guild-note-save" type="submit" name="guild_submit_mode" value="motd_only">Save MOTD</button>
                <span class="guild-motd-status">Writes to the live in-game guild MOTD.</span>
              </div>
              <div class="guild-motd-help">
                <div><strong>Guild meetings format:</strong> <code>Meeting:[location] [start time] [end time]</code></div>
                <p class="guild-motd-example">Meeting:Stormwind City 15:00 18:00</p>
                <p class="guild-motd-example">Meeting:Stormwind City 3:00pm 6:00pm</p>
                <div>Bots begin traveling 30 minutes before the start time and stay until the end time.</div>
              </div>
            </form>
          <?php endif; ?>
      <?php else: ?>
        <?php if (trim((string)$motd) !== ''): ?>
          <p class="guild-side-copy guild-motd-current"><?php echo htmlspecialchars((string)$motd); ?></p>
        <?php endif; ?>
      <?php endif; ?>
    </section>

    <?php if ($guildLeaderToolsVisible && ($canViewOfficerNotes || $canEditGuildNotes)): ?>
    <section class="guild-section guild-orders-panel">
      <h3 class="guild-side-title">Guild Orders v1</h3>
      <p class="guild-orders-summary">
        Officer notes are decoded into manual orders, share fallbacks, skips, or invalid entries.
        Blank notes automatically fall back to the Share block.
      </p>

      <div class="guild-orders-stats" aria-label="Guild orders summary">
        <span class="guild-orders-stat">Manual: <?php echo (int)$guildOrderSummary['manual']; ?></span>
        <span class="guild-orders-stat">Share fallback: <?php echo (int)$guildOrderSummary['share_fallback']; ?></span>
        <span class="guild-orders-stat">Skip: <?php echo (int)$guildOrderSummary['skip']; ?></span>
        <span class="guild-orders-stat">Invalid: <?php echo (int)$guildOrderSummary['invalid']; ?></span>
      </div>

      <div class="guild-orders-block">
        <h4>Rules</h4>
        <p class="guild-orders-copy">Accepted formats are intentionally small so the roster can stay readable:</p>
        <ul class="guild-orders-list">
          <li><code>blank</code> - auto / Share fallback</li>
          <li><code>skip order</code> - explicit skip</li>
          <li><code>Craft: &lt;target&gt; [amount]</code></li>
          <li><code>Farm: &lt;target&gt; [amount]</code></li>
          <li><code>Kill: &lt;target&gt;</code></li>
          <li><code>Explore: &lt;target&gt;</code></li>
        </ul>
        <p class="guild-orders-help">
          Normalized examples:
          <code>Craft: Arcane Dust 20</code>,
          <code>Farm: Dreamfoil 12</code>,
          <code>Kill: Scarlet Spellbinder</code>,
          <code>Explore: Western Plaguelands</code>
        </p>
      </div>

      <div class="guild-orders-block">
        <h4>Share Context</h4>
        <?php if (!empty($guildOrderSharePreview['entries'])): ?>
          <p class="guild-orders-copy">Share block is parsed as a read-only fallback source for blank officer notes.</p>
          <div class="guild-orders-table-wrap">
            <table class="guild-orders-table">
              <thead>
                <tr>
                  <th>Filter</th>
                  <th>Items</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($guildOrderSharePreview['entries'] as $shareEntry): ?>
                  <tr>
                    <td><?php echo htmlspecialchars((string)($shareEntry['filter'] ?? '')); ?></td>
                    <td class="guild-orders-raw">
                      <?php
                        $shareItems = array();
                        foreach (($shareEntry['items'] ?? array()) as $shareItem) {
                            $shareItems[] = (string)($shareItem['item_name'] ?? '') . ' x' . (int)($shareItem['amount'] ?? 0);
                        }
                        echo htmlspecialchars(implode(', ', $shareItems));
                      ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php elseif (!empty($guildOrderSharePreview['errors'])): ?>
          <div class="guild-orders-empty">
            <?php echo implode('<br>', array_map('htmlspecialchars', $guildOrderSharePreview['errors'])); ?>
          </div>
        <?php else: ?>
          <div class="guild-orders-copy">
            No Share block is currently configured. Blank officer notes will display as auto via the Share fallback.
          </div>
        <?php endif; ?>
      </div>

      <div class="guild-orders-block">
        <h4>Meeting Context</h4>
        <?php if (!empty($guildOrderMeetingPreview['found']) && !empty($guildOrderMeetingPreview['valid'])): ?>
          <p class="guild-orders-copy">Current meeting directive: <strong><?php echo htmlspecialchars((string)($guildOrderMeetingPreview['display'] ?? '')); ?></strong></p>
          <p class="guild-orders-help">
            Read-only reminder: meeting data is still sourced from the guild MOTD and stays separate from officer notes.
          </p>
        <?php elseif (!empty($guildOrderMeetingPreview['found'])): ?>
          <div class="guild-orders-empty"><?php echo htmlspecialchars((string)($guildOrderMeetingPreview['error'] ?? 'Meeting directive found, but it could not be parsed.')); ?></div>
        <?php else: ?>
          <div class="guild-orders-copy">No meeting directive is currently configured in the MOTD.</div>
        <?php endif; ?>
      </div>

    </section>
    <?php endif; ?>

    <?php if ($guildLeaderToolsVisible && ($isSelectedGuildLeader || $isGm)): ?>
    <section class="guild-section">
      <h3 class="guild-side-title">Bot Strategy Flavor</h3>
        <p class="guild-side-copy">
          Sets the AI strategy profile for all bots in this guild.
          Changes take effect on each bot's next relog.
          Currently: <strong><?php echo htmlspecialchars(ucfirst($currentFlavor)); ?></strong>
        </p>

        <?php if ($flavorFeedback !== ''): ?>
          <div class="guild-flavor-feedback">
            <?php echo $flavorFeedback; ?>
          </div>
        <?php endif; ?>
        <?php if ($flavorError !== ''): ?>
          <div class="guild-flavor-error">
            <?php echo htmlspecialchars($flavorError); ?>
          </div>
        <?php endif; ?>

        <form method="post" class="guild-flavor-form">
          <input type="hidden" name="guild_form_action" value="save_guild_flavor">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($guildCsrfToken, ENT_QUOTES); ?>">
          <select name="guild_flavor" class="guild-flavor-select">
            <?php foreach ($guildFlavorProfiles as $fKey => $fData): ?>
              <option value="<?php echo htmlspecialchars($fKey); ?>"
                <?php echo ($currentFlavor === $fKey ? 'selected' : ''); ?>>
                <?php echo htmlspecialchars($fData['label']); ?> - <?php echo htmlspecialchars($fData['desc']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="guild-flavor-button">
            Apply Flavor
          </button>
        </form>
      </section>
      <?php endif; ?>

      <section class="guild-section guild-insights-panel">
        <h3 class="guild-side-title">Roster Overview</h3>
        <p class="guild-side-copy"><?php echo $guildMembers; ?> members, average level <?php echo $avgLevel; ?>, guild average iLvl <?php echo $guildAverageItemLevel > 0 ? number_format($guildAverageItemLevel, 1) : '-'; ?>, max level <?php echo $maxLevel; ?>.</p>

        <div class="guild-divider">
          <h3 class="guild-side-title">Class Breakdown</h3>
          <div class="guild-breakdown">
            <?php foreach($orderedClassBreakdown as $classId => $classCount): ?>
              <?php
                $breakClassName = $classNames[$classId] ?? ('Class ' . $classId);
                $breakClassSlug = strtolower(str_replace(' ', '', $breakClassName));
                $breakWidth = $maxBreakdown > 0 ? round(($classCount / $maxBreakdown) * 100, 1) : 0;
              ?>
              <div class="guild-breakdown-row class-<?php echo $breakClassSlug; ?>">
                <div class="guild-breakdown-label"><?php echo htmlspecialchars($breakClassName); ?></div>
                <div class="guild-breakdown-bar"><div class="guild-breakdown-fill" style="width: <?php echo $breakWidth; ?>%; background: var(--class-color);"></div></div>
                <div><?php echo (int)$classCount; ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if (!empty($guildClassLevelCards)): ?>
            <div class="guild-level-breakdown">
              <h3 class="guild-side-title">Typical Class Level</h3>
              <p class="guild-side-copy">Median level per class inside this guild.</p>
              <div class="guild-level-columns">
                <?php foreach($orderedClassBreakdown as $classId => $classCount): ?>
                  <?php
                    $levelClassName = $classNames[$classId] ?? ('Class ' . $classId);
                    $levelClassSlug = strtolower(str_replace(' ', '', $levelClassName));
                    $medianLevel = (int)($guildClassLevelCards[$classId] ?? 0);
                    $height = $guildMedianLevelMax > 0 ? max(4, (int)round(($medianLevel / $guildMedianLevelMax) * 100)) : 0;
                  ?>
                  <div class="guild-level-column class-<?php echo $levelClassSlug; ?>">
                    <div class="guild-level-value"><?php echo $medianLevel; ?></div>
                    <div class="guild-level-track">
                      <div class="guild-level-fill" style="height: <?php echo $height; ?>%;"></div>
                    </div>
                    <div class="guild-level-label"><?php echo htmlspecialchars($levelClassName); ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </div>
</div>
</div>
</div>
