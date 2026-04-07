<?php
$siteRoot = dirname(__DIR__, 3);
require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/app/server/items-page.php');

$armoryConfig = (array)($GLOBALS['armoryRuntime'] ?? []);
$itemsPageState = spp_item_database_build_page_state($armoryConfig);
$realmId = (int)$itemsPageState['realmId'];
$realmLabel = (string)$itemsPageState['realmLabel'];
$data = $itemsPageState['data'];
$filters = $itemsPageState['filters'];
$rows = $itemsPageState['rows'];
$pageCount = (int)$itemsPageState['pageCount'];
$totalResults = (int)$itemsPageState['totalResults'];
$page = (int)$itemsPageState['page'];
$perPage = (int)$itemsPageState['perPage'];
$offset = (int)$itemsPageState['offset'];
$resultStart = (int)$itemsPageState['resultStart'];
$resultEnd = (int)$itemsPageState['resultEnd'];
$hasFilters = !empty($itemsPageState['hasFilters']);
$isSearchMode = !empty($itemsPageState['isSearchMode']);
$summary = $itemsPageState['summary'];
$counts = $itemsPageState['counts'] ?? [];
$sections = $itemsPageState['sections'] ?? [];
$sortOptions = $itemsPageState['sortOptions'];
$typeOptions = $itemsPageState['typeOptions'];
$qualityOptions = $itemsPageState['qualityOptions'];
$classOptions = $itemsPageState['classOptions'];
$slotOptions = $itemsPageState['slotOptions'];
$setSectionOptions = $itemsPageState['setSectionOptions'] ?? ['misc' => 'Class & Tier Sets', 'world' => 'World Sets', 'pvp' => 'PvP Sets'];
$setClassOptions = $itemsPageState['setClassOptions'] ?? ['Mage'];

$quickLinkItemIcon = spp_item_database_icon_url('inv_sword_04');
$quickLinkNpcIcon = spp_item_database_icon_url('ability_hunter_pet_gorilla');
$quickLinkQuestIcon = spp_item_database_icon_url('inv_misc_questionmark');
$quickLinkFeaturedIcon = spp_item_database_icon_url('inv_misc_gem_pearl_04');
$quickLinkSetIcon = spp_item_database_icon_url('inv_chest_chain_10');

$realmType = spp_item_vault_realm_type($realmId);
$npcIconPools = [
    'PVP' => [
        'achievement_pvp_a_01',
        'achievement_pvp_h_01',
        'ability_warrior_battleshout',
        'ability_rogue_ambush',
        'ability_dualwield',
        'ability_mount_netherdrakepurple',
    ],
    'RPPVP' => [
        'achievement_pvp_a_01',
        'achievement_pvp_h_01',
        'spell_misc_hellifrepvphonorholdfavor',
        'spell_misc_hellifrepvpthrallmarfavor',
        'inv_bannerpvp_02',
        'inv_bannerpvp_01',
    ],
    'FFA_PVP' => [
        'achievement_pvp_a_01',
        'achievement_pvp_h_01',
        'spell_misc_hellifrepvphonorholdfavor',
        'spell_misc_hellifrepvpthrallmarfavor',
        'ability_warrior_battleshout',
        'ability_rogue_ambush',
    ],
    'NORMAL' => [
        'achievement_character_human_male',
        'achievement_character_orc_male',
        'achievement_character_nightelf_female',
        'achievement_character_tauren_male',
        'achievement_character_dwarf_male',
        'achievement_character_troll_male',
    ],
    'RP' => [
        'achievement_character_human_male',
        'achievement_character_bloodelf_female',
        'achievement_character_nightelf_female',
        'achievement_character_undead_female',
        'achievement_character_draenei_female',
        'achievement_character_tauren_male',
    ],
];
$npcIconCandidates = $npcIconPools[$realmType] ?? $npcIconPools['NORMAL'];
$quickLinkNpcIcon = spp_item_database_icon_url($npcIconCandidates[array_rand($npcIconCandidates)]);

try {
    $armoryPdo = spp_get_pdo('armory', $realmId);

    $itemIconStmt = $armoryPdo->query('SELECT `name` FROM `dbc_itemdisplayinfo` WHERE `name` <> \'\' ORDER BY RAND() LIMIT 8');
    $itemIconRows = $itemIconStmt->fetchAll(PDO::FETCH_COLUMN);
    $itemIconRows = array_values(array_unique(array_filter(array_map('strval', $itemIconRows))));
    if (!empty($itemIconRows[0])) {
        $quickLinkFeaturedIcon = spp_item_database_icon_url($itemIconRows[0]);
    }
    if (!empty($itemIconRows[1])) {
        $quickLinkItemIcon = spp_item_database_icon_url($itemIconRows[1]);
    }

    $npcIconPlaceholders = implode(',', array_fill(0, count($npcIconCandidates), '?'));
    $npcIconStmt = $armoryPdo->prepare('SELECT `name` FROM `dbc_spellicon` WHERE LOWER(`name`) IN (' . $npcIconPlaceholders . ') ORDER BY RAND() LIMIT 1');
    $npcIconStmt->execute(array_map('strtolower', $npcIconCandidates));
    $npcIconName = (string)$npcIconStmt->fetchColumn();
    if ($npcIconName !== '') {
        $quickLinkNpcIcon = spp_item_database_icon_url($npcIconName);
    }
} catch (Throwable $e) {
    // Leave the fallback icons in place if the icon tables are unavailable.
}

$featuredVaultLinks = [
    [
        'label' => 'Raid Epics',
        'copy' => 'Browse the top-end epic drops in your realm index.',
        'url' => spp_item_database_url($realmId, $filters, ['type' => 'items', 'quality' => '4', 'item_class' => '', 'slot' => '', 'search' => '', 'p' => 1]),
        'active' => $filters['type'] === 'items' && $filters['quality'] === '4' && $filters['class'] === '' && $filters['slot'] === '' && $filters['search'] === '',
    ],
    [
        'label' => 'Weapons Locker',
        'copy' => 'Jump straight into weapons across the live world database.',
        'url' => spp_item_database_url($realmId, $filters, ['type' => 'items', 'item_class' => '2', 'quality' => '', 'slot' => '', 'search' => '', 'p' => 1]),
        'active' => $filters['type'] === 'items' && $filters['class'] === '2' && $filters['search'] === '',
    ],
    [
        'label' => 'Armor Archive',
        'copy' => 'Scan armor pieces, trinkets, shields, and set-ready gear.',
        'url' => spp_item_database_url($realmId, $filters, ['type' => 'items', 'item_class' => '4', 'quality' => '', 'slot' => '', 'search' => '', 'p' => 1]),
        'active' => $filters['type'] === 'items' && $filters['class'] === '4' && $filters['search'] === '',
    ],
    [
        'label' => 'Quest Rewards',
        'copy' => 'Surface questable items with a quick quality and level pass.',
        'url' => spp_item_database_url($realmId, $filters, ['type' => 'items', 'item_class' => '12', 'quality' => '', 'slot' => '', 'search' => '', 'p' => 1]),
        'active' => $filters['type'] === 'items' && $filters['class'] === '12' && $filters['search'] === '',
    ],
];

$randomQuickLink = $featuredVaultLinks[array_rand($featuredVaultLinks)];
$quickLinks = [
    array_merge($randomQuickLink, ['icon' => $quickLinkFeaturedIcon]),
    [
        'label' => 'Items',
        'copy' => 'Open the item database and browse gear, recipes, and quest rewards.',
        'url' => spp_item_database_url($realmId, $filters, ['type' => 'items', 'search' => '', 'quality' => '', 'item_class' => '', 'slot' => '', 'min_level' => '', 'max_level' => '', 'p' => 1]),
        'active' => $filters['type'] === 'items' && $filters['search'] === '' && $filters['quality'] === '' && $filters['class'] === '' && $filters['slot'] === '',
        'icon' => $quickLinkItemIcon,
    ],
    [
        'label' => 'Item Sets',
        'copy' => 'Browse class, world, and PvP armor sets from the same database flow.',
        'url' => spp_item_database_url($realmId, $filters, ['type' => 'sets', 'set_section' => 'all', 'set_class' => 'all', 'search' => '', 'quality' => '', 'item_class' => '', 'slot' => '', 'min_level' => '', 'max_level' => '', 'p' => 1]),
        'active' => $filters['type'] === 'sets',
        'icon' => $quickLinkSetIcon,
    ],
    [
        'label' => 'Quests',
        'copy' => 'Search quest titles, rewards, and later map-linked objective pages.',
        'url' => spp_item_database_url($realmId, $filters, ['type' => 'quests', 'search' => 'quest', 'icon' => '', 'quality' => '', 'item_class' => '', 'slot' => '', 'min_level' => '', 'max_level' => '', 'p' => 1]),
        'active' => $filters['type'] === 'quests',
        'icon' => $quickLinkQuestIcon,
    ],
    [
        'label' => 'NPCs',
        'copy' => 'Jump into creature records for bosses, vendors, and quest targets.',
        'url' => spp_item_database_url($realmId, $filters, ['type' => 'npcs', 'search' => 'guard', 'icon' => '', 'quality' => '', 'item_class' => '', 'slot' => '', 'min_level' => '', 'max_level' => '', 'p' => 1]),
        'active' => $filters['type'] === 'npcs',
        'icon' => $quickLinkNpcIcon,
    ],
];

builddiv_start(1, 'SPP Armory Item Vault', 1);
?>
<div class="item-vault">
  <section class="item-vault__hero">
    <div class="item-vault__hero-copy">
      <p class="item-vault__eyebrow">SPP Armory Database</p>
      <h1 class="item-vault__title">Item Vault</h1>
      <p class="item-vault__lead">Browse live realm item data in a faster database layout, hover any entry for the full tooltip, and drill into who owns that item on <?php echo htmlspecialchars($realmLabel); ?>.</p>
    </div>
    <div class="item-vault__hero-metrics">
      <div class="item-vault__metric">
        <span class="item-vault__metric-label">Results</span>
        <strong><?php echo number_format($totalResults); ?></strong>
      </div>
      <div class="item-vault__metric">
        <span class="item-vault__metric-label">Epics On Page</span>
        <strong><?php echo number_format((int)($summary['epic'] ?? 0)); ?></strong>
      </div>
      <div class="item-vault__metric">
        <span class="item-vault__metric-label">Weapons On Page</span>
        <strong><?php echo number_format((int)($summary['weapon'] ?? 0)); ?></strong>
      </div>
      <div class="item-vault__metric">
        <span class="item-vault__metric-label">Armor On Page</span>
        <strong><?php echo number_format((int)($summary['armor'] ?? 0)); ?></strong>
      </div>
    </div>
  </section>

  <section class="item-vault__quicklinks">
    <?php foreach ($quickLinks as $quickLink): ?>
      <a class="item-vault__quicklink<?php echo $quickLink['active'] ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($quickLink['url']); ?>">
        <?php if (!empty($quickLink['icon'])): ?><img class="item-vault__quicklink-icon" src="<?php echo htmlspecialchars($quickLink['icon']); ?>" alt=""><?php endif; ?>
        <span class="item-vault__quicklink-title"><?php echo htmlspecialchars($quickLink['label']); ?></span>
        <span class="item-vault__quicklink-copy"><?php echo htmlspecialchars($quickLink['copy']); ?></span>
      </a>
    <?php endforeach; ?>
  </section>

  <section class="item-vault__filters">
    <form method="get" class="item-vault__form">
      <input type="hidden" name="n" value="server">
      <input type="hidden" name="sub" value="items">
      <input type="hidden" name="realm" value="<?php echo (int)$realmId; ?>">
      <input type="hidden" name="p" value="1">
      <?php if (!empty($filters['icon'])): ?><input type="hidden" name="icon" value="<?php echo htmlspecialchars($filters['icon']); ?>"><?php endif; ?>

      <div class="item-vault__form-row item-vault__form-row--primary">
        <label class="item-vault__field item-vault__field--search">
          <span>Search the vault</span>
          <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Thunderfury, Hand of Ragnaros, Felstriker..." autocomplete="off">
        </label>

        <label class="item-vault__field item-vault__field--type">
          <span>Record type</span>
          <select name="type">
            <?php foreach ($typeOptions as $value => $label): ?>
              <option value="<?php echo htmlspecialchars($value); ?>"<?php echo $filters['type'] === (string)$value ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="item-vault__field item-vault__field--per-page">
          <span>Per page</span>
          <select name="per_page">
            <?php foreach ([12, 24, 48, 96] as $option): ?>
              <option value="<?php echo $option; ?>"<?php echo $perPage === $option ? ' selected' : ''; ?>><?php echo $option; ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <div class="item-vault__actions">
          <button type="submit" class="item-vault__button">Search Vault</button>
          <a class="item-vault__reset" href="<?php echo htmlspecialchars(spp_item_database_url($realmId, $filters, ['type' => 'all', 'search' => '', 'quality' => '', 'item_class' => '', 'slot' => '', 'min_level' => '', 'max_level' => '', 'sort' => 'featured', 'dir' => 'DESC', 'p' => 1])); ?>">Reset Filters</a>
        </div>
      </div>

      <?php if ($filters['type'] === 'items'): ?>
        <div class="item-vault__form-row item-vault__form-row--secondary">
          <label class="item-vault__field">
            <span>Quality</span>
            <select name="quality">
              <?php foreach ($qualityOptions as $value => $label): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"<?php echo $filters['quality'] === (string)$value ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="item-vault__field">
            <span>Item family</span>
            <select name="item_class">
              <?php foreach ($classOptions as $value => $label): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"<?php echo $filters['class'] === (string)$value ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="item-vault__field">
            <span>Slot</span>
            <select name="slot">
              <?php foreach ($slotOptions as $value => $label): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"<?php echo $filters['slot'] === (string)$value ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="item-vault__field">
            <span>Min item level</span>
            <input type="number" name="min_level" min="0" max="500" value="<?php echo htmlspecialchars($filters['min_level']); ?>" placeholder="0">
          </label>

          <label class="item-vault__field">
            <span>Max item level</span>
            <input type="number" name="max_level" min="0" max="500" value="<?php echo htmlspecialchars($filters['max_level']); ?>" placeholder="500">
          </label>

          <label class="item-vault__field">
            <span>Sort</span>
            <select name="sort">
              <?php foreach ($sortOptions as $value => $label): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"<?php echo $filters['sort'] === (string)$value ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="item-vault__field">
            <span>Direction</span>
            <select name="dir">
              <option value="DESC"<?php echo $filters['dir'] === 'DESC' ? ' selected' : ''; ?>>Descending</option>
              <option value="ASC"<?php echo $filters['dir'] === 'ASC' ? ' selected' : ''; ?>>Ascending</option>
            </select>
          </label>
        </div>
      <?php elseif ($filters['type'] === 'sets'): ?>
        <div class="item-vault__form-row item-vault__form-row--secondary">
          <label class="item-vault__field">
            <span>Set section</span>
            <select name="set_section">
              <?php foreach ($setSectionOptions as $value => $label): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"<?php echo ($filters['set_section'] ?? 'all') === (string)$value ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="item-vault__field">
              <span>Class</span>
              <select name="set_class">
                <?php foreach ($setClassOptions as $className): ?>
                  <option value="<?php echo htmlspecialchars($className); ?>"<?php echo ($filters['set_class'] ?? 'all') === (string)$className ? ' selected' : ''; ?>><?php echo $className === 'all' ? 'All Classes' : htmlspecialchars($className); ?></option>
                <?php endforeach; ?>
              </select>
            </label>
        </div>
      <?php endif; ?>
    </form>
  </section>

  <?php if ($filters['search'] !== ''): ?>
    <section class="item-vault__typebar">
      <?php foreach ($typeOptions as $typeKey => $typeLabel): ?>
        <?php
          $typeCount = $typeKey === 'all' ? array_sum($counts) : (int)($counts[$typeKey] ?? 0);
          $typeUrl = spp_item_database_url($realmId, $filters, ['type' => $typeKey, 'p' => 1]);
        ?>
        <a class="item-vault__typechip<?php echo $typeKey === 'all' ? ' item-vault__typechip--all' : ''; ?><?php echo $filters['type'] === $typeKey ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($typeUrl); ?>">
          <span><?php echo htmlspecialchars($typeLabel); ?></span>
          <strong><?php echo number_format($typeCount); ?></strong>
        </a>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <?php if ($data['error'] !== ''): ?>
    <div class="item-vault__state item-vault__state--error"><?php echo htmlspecialchars($data['error']); ?></div>
  <?php else: ?>
    <section class="item-vault__results-head">
      <div>
        <p class="item-vault__results-title"><?php echo $isSearchMode ? 'Search Results' : ($hasFilters ? 'Filtered Vault Results' : 'Featured Realm Gear'); ?></p>
        <p class="item-vault__results-copy">
          <?php if ($filters['type'] === 'sets'): ?>
            Showing <?php echo number_format($resultStart); ?>-<?php echo number_format($resultEnd); ?> of <?php echo number_format($totalResults); ?> set entries<?php if (($filters['set_class'] ?? 'all') !== 'all'): ?> for <?php echo htmlspecialchars((string)$filters['set_class']); ?><?php endif; ?><?php if (($filters['set_section'] ?? 'all') !== 'all'): ?> in <?php echo htmlspecialchars($setSectionOptions[$filters['set_section'] ?? 'all'] ?? 'Item Sets'); ?><?php endif; ?>.
          <?php elseif ($filters['type'] === 'all' && $filters['search'] === ''): ?>
            Search across items, quests, NPCs, spells, and talents, then use the live category bar to narrow the database like a classic Thottbot or Wowhead flow.
          <?php elseif ($totalResults > 0): ?>
            <?php if ($filters['type'] === 'all'): ?>
              Search menu built from <?php echo number_format($totalResults); ?> matching records across the Armory database.
            <?php else: ?>
              Showing <?php echo number_format($resultStart); ?>-<?php echo number_format($resultEnd); ?> of <?php echo number_format($totalResults); ?> entries from <?php echo htmlspecialchars($realmLabel); ?>.
            <?php endif; ?>
          <?php else: ?>
            No entries matched this vault query on <?php echo htmlspecialchars($realmLabel); ?>.
          <?php endif; ?>
        </p>
      </div>
      <?php if ($hasFilters): ?>
        <div class="item-vault__active-filters">
          <?php if ($filters['search'] !== ''): ?><span class="item-vault__pill">Name: <?php echo htmlspecialchars($filters['search']); ?></span><?php endif; ?>
          <?php if (!empty($filters['icon'])): ?><span class="item-vault__pill">Icon: <?php echo htmlspecialchars($filters['icon']); ?></span><?php endif; ?>
          <?php if ($filters['type'] !== 'all'): ?><span class="item-vault__pill">Type: <?php echo htmlspecialchars($typeOptions[$filters['type']] ?? $filters['type']); ?></span><?php endif; ?>
          <?php if ($filters['type'] === 'sets' && ($filters['set_section'] ?? 'all') !== 'all'): ?><span class="item-vault__pill">Section: <?php echo htmlspecialchars($setSectionOptions[$filters['set_section'] ?? 'all'] ?? ($filters['set_section'] ?? 'all')); ?></span><?php endif; ?>
          <?php if ($filters['type'] === 'sets' && ($filters['set_class'] ?? 'all') !== 'all'): ?><span class="item-vault__pill">Class: <?php echo htmlspecialchars((string)($filters['set_class'] ?? 'all')); ?></span><?php endif; ?>
          <?php if ($filters['quality'] !== ''): ?><span class="item-vault__pill">Quality: <?php echo htmlspecialchars($qualityOptions[$filters['quality']] ?? $filters['quality']); ?></span><?php endif; ?>
          <?php if ($filters['class'] !== ''): ?><span class="item-vault__pill">Family: <?php echo htmlspecialchars($classOptions[$filters['class']] ?? $filters['class']); ?></span><?php endif; ?>
          <?php if ($filters['slot'] !== ''): ?><span class="item-vault__pill">Slot: <?php echo htmlspecialchars($slotOptions[$filters['slot']] ?? $filters['slot']); ?></span><?php endif; ?>
          <?php if ($filters['min_level'] !== ''): ?><span class="item-vault__pill">Min ilvl: <?php echo (int)$filters['min_level']; ?></span><?php endif; ?>
          <?php if ($filters['max_level'] !== ''): ?><span class="item-vault__pill">Max ilvl: <?php echo (int)$filters['max_level']; ?></span><?php endif; ?>
          <?php if (($filters['sort'] ?? 'featured') !== 'featured'): ?><span class="item-vault__pill">Sort: <?php echo htmlspecialchars($sortOptions[$filters['sort']] ?? $filters['sort']); ?></span><?php endif; ?>
          <?php if (($filters['dir'] ?? 'DESC') !== 'DESC'): ?><span class="item-vault__pill">Direction: <?php echo htmlspecialchars($filters['dir']); ?></span><?php endif; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php if ($filters['type'] === 'all' && !empty($sections)): ?>
      <section class="item-vault__sections">
        <?php foreach ($typeOptions as $typeKey => $typeLabel): ?>
          <?php if ($typeKey === 'all' || empty($sections[$typeKey])) continue; ?>
          <div class="item-vault__section">
            <div class="item-vault__section-head">
              <h2><?php echo htmlspecialchars($typeLabel); ?></h2>
              <a href="<?php echo htmlspecialchars(spp_item_database_url($realmId, $filters, ['type' => $typeKey, 'p' => 1])); ?>">Open <?php echo htmlspecialchars($typeLabel); ?></a>
            </div>
            <div class="item-vault__mini-grid">
              <?php foreach ($sections[$typeKey] as $sectionRow): ?>
                <article class="item-vault__mini-card">
                  <img src="<?php echo htmlspecialchars($sectionRow['icon']); ?>" alt="">
                  <div>
                    <h3<?php echo !empty($sectionRow['quality_color']) ? ' style="color:' . htmlspecialchars($sectionRow['quality_color']) . ';"' : ''; ?>><?php echo htmlspecialchars($sectionRow['name']); ?></h3>
                    <p><?php echo htmlspecialchars($sectionRow['submeta']); ?></p>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </section>
    <?php elseif ($rows): ?>
      <section class="item-vault__table-shell list-page-table-card feature-panel">
        <div class="wow-table item-vault__table item-vault__table--<?php echo htmlspecialchars($filters['type']); ?>">
          <div class="item-vault__table-header">
            <div class="item-vault__table-cell item-vault__table-cell--icon">Icon</div>
            <div class="item-vault__table-cell item-vault__table-cell--main"><?php echo $filters['type'] === 'items' ? spp_item_vault_sort_link($realmId, $filters, 'name', 'Entry') : 'Entry'; ?></div>
            <div class="item-vault__table-cell item-vault__table-cell--type"><?php echo $filters['type'] === 'items' ? spp_item_vault_sort_link($realmId, $filters, 'quality', 'Type') : 'Type'; ?></div>
            <div class="item-vault__table-cell item-vault__table-cell--meta"><?php echo $filters['type'] === 'items' ? spp_item_vault_sort_link($realmId, $filters, 'level', 'Details') : 'Details'; ?></div>
            <?php if ($filters['type'] !== 'sets'): ?>
              <div class="item-vault__table-cell item-vault__table-cell--note">Notes</div>
            <?php endif; ?>
            <div class="item-vault__table-cell item-vault__table-cell--action">View</div>
          </div>
          <?php foreach ($rows as $row): ?>
            <?php
              $entityType = (string)($row['entity_type'] ?? 'items');
              $itemUrl = 'index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$row['id'];
              $detailUrl = (string)($row['detail_url'] ?? '');
              if ($filters['type'] !== '') {
                  $itemUrl .= '&type=' . urlencode($filters['type']);
              }
              if ($filters['search'] !== '') {
                  $itemUrl .= '&search=' . urlencode($filters['search']);
              }
              if (!empty($filters['icon'])) {
                  $itemUrl .= '&icon=' . urlencode($filters['icon']);
              }
              if ($filters['quality'] !== '') {
                  $itemUrl .= '&quality=' . urlencode($filters['quality']);
              }
              if ($filters['class'] !== '') {
                  $itemUrl .= '&item_class=' . urlencode($filters['class']);
              }
              if ($filters['slot'] !== '') {
                  $itemUrl .= '&slot=' . urlencode($filters['slot']);
              }
              if ($filters['min_level'] !== '') {
                  $itemUrl .= '&min_level=' . urlencode($filters['min_level']);
              }
              if ($filters['max_level'] !== '') {
                  $itemUrl .= '&max_level=' . urlencode($filters['max_level']);
              }
              $itemUrl .= '&p=' . (int)$page . '&per_page=' . (int)$perPage . '&sort=' . urlencode($filters['sort']) . '&dir=' . urlencode($filters['dir']);
            ?>
            <div class="item-vault__table-row">
              <div class="item-vault__table-cell item-vault__table-cell--icon">
                <?php if ($entityType === 'items'): ?>
                  <a class="item-vault__table-icon" href="<?php echo htmlspecialchars($itemUrl); ?>" data-item-tooltip-id="<?php echo (int)$row['id']; ?>" data-item-tooltip-realm="<?php echo (int)$realmId; ?>">
                    <img src="<?php echo htmlspecialchars($row['icon']); ?>" alt="">
                  </a>
                <?php elseif ($entityType === 'sets' && $detailUrl !== ''): ?>
                  <a class="item-vault__table-icon" href="<?php echo htmlspecialchars($detailUrl); ?>"<?php if (!empty($row['tooltip_item_id'])): ?> data-item-tooltip-id="<?php echo (int)$row['tooltip_item_id']; ?>" data-item-tooltip-realm="<?php echo (int)$realmId; ?>"<?php endif; ?>>
                    <img src="<?php echo htmlspecialchars($row['icon']); ?>" alt="">
                  </a>
                <?php else: ?>
                  <span class="item-vault__table-icon item-vault__table-icon--static"><img src="<?php echo htmlspecialchars($row['icon']); ?>" alt=""></span>
                <?php endif; ?>
              </div>
              <div class="item-vault__table-cell item-vault__table-cell--main">
                <?php if ($entityType === 'items'): ?>
                  <a class="item-vault__table-name" href="<?php echo htmlspecialchars($itemUrl); ?>" style="color: <?php echo htmlspecialchars($row['quality_color']); ?>;" data-item-tooltip-id="<?php echo (int)$row['id']; ?>" data-item-tooltip-realm="<?php echo (int)$realmId; ?>"><?php echo htmlspecialchars($row['name']); ?></a>
                <?php elseif ($entityType === 'sets' && $detailUrl !== ''): ?>
                  <a class="item-vault__table-name sets-set-link" href="<?php echo htmlspecialchars($detailUrl); ?>"<?php if (!empty($row['tooltip_item_id'])): ?> data-item-tooltip-id="<?php echo (int)$row['tooltip_item_id']; ?>" data-item-tooltip-realm="<?php echo (int)$realmId; ?>"<?php endif; ?>><?php echo htmlspecialchars($row['name']); ?></a>
                <?php else: ?>
                  <span class="item-vault__table-name"><?php echo htmlspecialchars($row['name']); ?></span>
                <?php endif; ?>
                <?php if ($entityType === 'items'): ?>
                  <div class="item-vault__table-sub"><?php echo htmlspecialchars($row['slot_name']); ?> | <?php echo htmlspecialchars($row['class_name']); ?> | <?php echo htmlspecialchars($row['source']); ?></div>
                <?php else: ?>
                  <div class="item-vault__table-sub"><?php echo htmlspecialchars($row['submeta'] ?? ''); ?></div>
                <?php endif; ?>
              </div>
              <div class="item-vault__table-cell item-vault__table-cell--type">
                <span class="item-vault__tag"><?php echo htmlspecialchars($row['meta'] ?? $row['quality_label']); ?></span>
              </div>
              <div class="item-vault__table-cell item-vault__table-cell--meta">
                <?php if ($entityType === 'items'): ?>
                  <div class="item-vault__table-statline">
                    <span>ilvl <?php echo (int)$row['level']; ?></span>
                    <?php if ($row['required_level'] > 0): ?><span>req <?php echo (int)$row['required_level']; ?></span><?php endif; ?>
                    <span><?php echo htmlspecialchars($row['quality_label']); ?></span>
                  </div>
                <?php elseif ($entityType === 'sets'): ?>
                  <div class="item-vault__table-sub"><?php echo htmlspecialchars((string)($row['detail_summary'] ?? '')); ?></div>
                <?php else: ?>
                  <div class="item-vault__table-sub"><?php echo htmlspecialchars($row['submeta'] ?? ''); ?></div>
                <?php endif; ?>
              </div>
              <?php if ($entityType !== 'sets'): ?>
                <div class="item-vault__table-cell item-vault__table-cell--note">
                  <?php if ($row['description'] !== ''): ?>
                    <span class="item-vault__table-note">"<?php echo htmlspecialchars($row['description']); ?>"</span>
                  <?php else: ?>
                    <span class="item-vault__table-note item-vault__table-note--muted">No extra notes</span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <div class="item-vault__table-cell item-vault__table-cell--action">
                <?php if ($entityType === 'items'): ?>
                  <a class="item-vault__view" href="<?php echo htmlspecialchars($itemUrl); ?>">Open</a>
                <?php elseif ($entityType === 'sets' && $detailUrl !== ''): ?>
                  <a class="item-vault__view" href="<?php echo htmlspecialchars($detailUrl); ?>">Open</a>
                <?php else: ?>
                  <span class="item-vault__table-note item-vault__table-note--muted">Soon</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <?php if ($pageCount > 1 && $filters['type'] !== 'all'): ?>
        <div class="pagination-controls">
          <div class="page-links">
            <?php
              $baseUrl = spp_item_database_url($realmId, $filters, ['p' => '']);
              echo compact_paginate($page, $pageCount, $baseUrl);
            ?>
          </div>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="item-vault__state">
        <?php if ($filters['type'] === 'all' && $filters['search'] === ''): ?>
          <strong>Start with a name, zone, spell, or quest title.</strong>
          <span>The category menu above will populate as soon as the search term narrows the database.</span>
        <?php else: ?>
          <strong>No vault entries matched this search.</strong>
          <span>Try a shorter item name, loosen the item level range, or jump into one of the quick browse links above.</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php builddiv_end(); ?>
