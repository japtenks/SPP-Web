<?php
builddiv_start(1, 'Honor', 1);
?>

<div class="honor-list-shell list-page-shell feature-shell">
  <section class="honor-list-hero list-page-hero feature-hero">
  <form method="get" class="honor-search-form list-page-search-form" id="honorSearchForm">
    <input type="hidden" name="n" value="server">
    <input type="hidden" name="sub" value="honor">
    <input type="hidden" name="realm" value="<?php echo $realmId; ?>">
    <input type="hidden" name="p" value="1">
    <input type="hidden" name="per_page" value="<?php echo $itemsPerPage; ?>">
    <input type="hidden" name="faction" value="<?php echo htmlspecialchars($factionFilter); ?>">
    <input
      type="text"
      class="list-page-search-input"
      id="commandSearch"
      name="search"
      value="<?php echo htmlspecialchars($search); ?>"
      placeholder="Search name, faction, race, class, rank, HK, DK, honor..."
      autocomplete="off"
    >
  </form>
  <?php render_character_pagination($p, $pnum, $itemsPerPage, $realmId, true, $search, false, $factionFilter, null, $pagination_route_url, $count); ?>
  </section>

<section class="honor-table-card list-page-table-card feature-panel">
<div class="wow-table honor-table">
  <div class="honor-table__header">
    <div class="honor-table__cell sortable" data-sort="name">Name</div>
    <div class="honor-table__cell honor-table__faction-filter" data-label="Faction">
      <select
        name="faction"
        class="honor-table__inline-filter list-page-inline-filter"
        form="honorSearchForm"
        onchange="this.form.submit()"
        onmousedown="event.stopPropagation()"
        onclick="event.stopPropagation()"
      >
        <option value="all"      <?php if ($factionFilter === 'all')      echo 'selected'; ?>>All</option>
        <option value="alliance" <?php if ($factionFilter === 'alliance') echo 'selected'; ?>>Alliance</option>
        <option value="horde"    <?php if ($factionFilter === 'horde')    echo 'selected'; ?>>Horde</option>
      </select>
    </div>
    <div class="honor-table__cell sortable" data-sort="level">Level</div>
    <div class="honor-table__cell sortable" data-sort="hk">HK</div>
    <div class="honor-table__cell sortable" data-sort="dk">DK</div>
    <div class="honor-table__cell sortable" data-sort="rank">Rank</div>
    <div class="honor-table__cell sortable" data-sort="honor">Honor</div>
  </div>

  <?php if ($charactersPage): ?>
    <?php foreach ($charactersPage as $item): ?>
      <div class="honor-table__row">
        <div class="honor-table__cell honor-table__name class-<?php echo htmlspecialchars($item['class_slug']); ?>" data-sort-value="<?php echo htmlspecialchars($item['name']); ?>">
          <a href="<?php echo htmlspecialchars($item['character_url']); ?>">
            <img src="<?php echo htmlspecialchars($item['portrait_url']); ?>" class="list-page-avatar-circle portrait" alt="">
            <?php echo htmlspecialchars($item['name']); ?>
          </a>
        </div>
        <div class="honor-table__cell honor-table__icon honor-table__faction" data-sort-value="<?php echo htmlspecialchars($item['faction_name']); ?>">
          <img src="<?php echo htmlspecialchars($item['faction_icon']); ?>" class="faction-icon" alt="<?php echo htmlspecialchars($item['faction_name']); ?>" title="<?php echo htmlspecialchars($item['faction_name']); ?>">
        </div>
        <div class="honor-table__cell honor-table__level" data-sort-value="<?php echo (int)$item['level']; ?>"><?php echo (int)$item['level']; ?></div>
        <div class="honor-table__cell honor-table__hk" data-sort-value="<?php echo (int)$item['honorable_kills']; ?>"><?php echo (int)$item['honorable_kills']; ?></div>
        <div class="honor-table__cell honor-table__dk" data-sort-value="<?php echo (int)$item['dishonorable_kills']; ?>"><?php echo (int)$item['dishonorable_kills']; ?></div>
        <div class="honor-table__cell honor-table__rank" data-sort-value="<?php echo (int)$item['rank_id']; ?>">
          <span
            class="honor-rank-wrap"
            data-rank-title="<?php echo htmlspecialchars($item['rank_name']); ?>"
            data-rank-copy="<?php echo htmlspecialchars((string)($item['rank_blurb'] ?? '')); ?>"
          >
            <img src="<?php echo $item['rank_icon']; ?>" class="rank-icon" alt="<?php echo htmlspecialchars($item['rank_name']); ?>">
            <span>
              <?php echo htmlspecialchars($item['rank_name']); ?>
              <span class="honor-rank-id">R<?php echo (int)$item['rank_id']; ?></span>
            </span>
          </span>
        </div>
        <div class="honor-table__cell honor-table__honor" data-sort-value="<?php echo (int)$item['honor_points']; ?>"><?php echo (int)round((float)$item['honor_points']); ?></div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="honor-table__row">
      <div class="honor-table__cell list-page-empty-row">No honored characters found.</div>
    </div>
  <?php endif; ?>
</div>
</section>
</div>

<div class="honor-rank-tooltip" id="honorRankTooltip" aria-hidden="true">
  <div class="honor-rank-tooltip__title"></div>
  <div class="honor-rank-tooltip__copy"></div>
</div>

<?php builddiv_end(); ?>
