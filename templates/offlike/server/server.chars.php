<?php
require_once dirname(__DIR__, 3) . '/app/support/terminology.php';
$publicTerms = spp_terminology_public();
$characterRows = $characterRows ?? $character_rows ?? $characters ?? array();
$charsRealmCapabilities = $realmCapabilities ?? $realm_capabilities ?? array();
$characterLinksEnabled = !empty($charsRealmCapabilities['supports_character_detail']);
builddiv_start(1, 'Characters', 1);
?>

<div class="character-list-shell list-page-shell feature-shell">
  <section class="character-list-hero list-page-hero feature-hero">
    <form method="get" class="character-search-form list-page-search-form" id="charsSearchForm">
      <input type="hidden" name="n" value="server">
      <input type="hidden" name="sub" value="chars">
      <input type="hidden" name="realm" value="<?php echo $realmId; ?>">
      <input type="hidden" name="p" value="1">
      <input type="hidden" name="per_page" value="<?php echo $items_per_page; ?>">

      <input
        type="text"
        class="list-page-search-input"
        id="commandSearch"
        name="search"
        value="<?php echo htmlspecialchars($search); ?>"
        placeholder="Search: -g guild -z zone -c class -r race -f faction -l level"
        autocomplete="off"
      >
    </form>
    <?php render_character_pagination($p, $pnum, $items_per_page, $realmId, $includeBots, $search, $onlineOnly, $factionFilter, $filteredBotCount, 'index.php?n=server&sub=chars', $count); ?>
    <div class="character-table-toolbar">
      <div class="character-toolbar-controls">
        <div class="character-filter-actions">
        <label class="inline-toggle" form="charsSearchForm">
          <input
            type="hidden"
            name="show_bots"
            value="0"
            form="charsSearchForm"
          >
          <input
            type="checkbox"
            name="show_bots"
            value="1"
            form="charsSearchForm"
            onchange="this.form.submit()"
            <?php if ($includeBots) echo 'checked'; ?>
          >
          <span><?php echo htmlspecialchars($publicTerms['include_characters']); ?></span>
        </label>
        <label class="inline-toggle" form="charsSearchForm">
          <input
            type="hidden"
            name="online"
            value="0"
            form="charsSearchForm"
          >
          <input
            type="checkbox"
            name="online"
            value="1"
            form="charsSearchForm"
            onchange="this.form.submit()"
            <?php if ($onlineOnly) echo 'checked'; ?>
          >
          <span>Online</span>
        </label>
        </div>
      </div>
    </div>
  </section>

<section class="character-table-card list-page-table-card feature-panel">
<div class="wow-table character-table">
  <div class="character-table__header">
    <div class="character-table__cell sortable character-table__name-header" data-sort="name">Name</div>
    <div class="character-table__cell sortable" data-sort="guild">Guild</div>
    <div class="character-table__cell sortable" data-sort="race">Race</div>
    <div class="character-table__cell sortable" data-sort="class">Class</div>
    <div class="character-table__cell character-table__faction-filter" data-label="Faction">
      <select
            name="faction"
            class="character-table__inline-filter list-page-inline-filter"
        form="charsSearchForm"
        onchange="this.form.submit()"
        onmousedown="event.stopPropagation()"
        onclick="event.stopPropagation()"
      >
        <option value="all" <?php if ($factionFilter === 'all') echo 'selected'; ?>>All</option>
        <option value="alliance" <?php if ($factionFilter === 'alliance') echo 'selected'; ?>>Alliance</option>
        <option value="horde" <?php if ($factionFilter === 'horde') echo 'selected'; ?>>Horde</option>
      </select>
    </div>
    <div class="character-table__cell sortable" data-sort="level">Level</div>
    <div class="character-table__cell sortable character-table__location-filter" data-sort="location" data-label="Location">
      <span class="character-table__location-label">Location</span>
    </div>
  </div>

<?php if (!empty($characterRows)): ?>
  <?php foreach ($characterRows as $item): ?>
    <?php
      $portrait = get_character_portrait_path(
        $item['guid'],
        $item['gender'],
        $item['race'],
        $item['class']
      );
    ?>
    <div class="character-table__row">
      <div class="character-table__cell character-table__name class-<?php echo strtolower($classNames[$item['class']] ?? 'unknown'); ?>">
        <?php if ($characterLinksEnabled): ?>
          <a href="index.php?n=server&amp;sub=character&amp;realm=<?php echo (int)$realmId; ?>&amp;character=<?php echo urlencode($item['name']); ?>">
            <img src="<?php echo $portrait; ?>" class="list-page-avatar-circle portrait" alt="">
            <?php echo htmlspecialchars($item['name']); ?>
          </a>
        <?php else: ?>
          <span>
            <img src="<?php echo $portrait; ?>" class="list-page-avatar-circle portrait" alt="">
            <?php echo htmlspecialchars($item['name']); ?>
          </span>
        <?php endif; ?>
      </div>
      <div class="character-table__cell"><?php if (!empty($item['guild_id']) && !empty($item['guild_name'])): ?><a href="index.php?n=server&sub=guild&guildid=<?php echo (int)$item['guild_id']; ?>&realm=<?php echo $realmId; ?>"><?php echo htmlspecialchars($item['guild_name']); ?></a><?php else: ?>-<?php endif; ?></div>
      <div class="character-table__cell">
        <img src="<?php echo htmlspecialchars(spp_server_chars_race_icon_url($item['race'], $item['gender'])); ?>"
             class="list-page-avatar-circle"
             title="<?php echo $raceNames[$item['race']] ?? 'Unknown'; ?>">
      </div>
      <div class="character-table__cell">
        <img src="<?php echo htmlspecialchars(spp_server_chars_class_icon_url($item['class'])); ?>"
             class="list-page-avatar-circle"
             title="<?php echo $classNames[$item['class']] ?? 'Unknown'; ?>">
      </div>
      <div class="character-table__cell character-table__faction">
        <img src="<?php echo htmlspecialchars(spp_server_chars_faction_icon_url((string)$item['faction_name'])); ?>"
             class="list-page-avatar-circle"
             title="<?php echo $item['faction_name']; ?>">
      </div>
      <div class="character-table__cell character-table__level"><?php echo (int)$item['level']; ?></div>
      <div class="character-table__cell character-table__location"><?php echo htmlspecialchars($item['location_name']); ?></div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="character-table__row">
    <div class="character-table__cell character-empty-row list-page-empty-row">No characters found.</div>
  </div>
<?php endif; ?>
</div>
</section>
</div>
<?php builddiv_end(); ?>














