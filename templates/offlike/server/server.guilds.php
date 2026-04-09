<?php
builddiv_start(1, 'Guilds', 1);
$guildsRealmCapabilities = $realmCapabilities ?? $realm_capabilities ?? array();
$guildCharacterLinksEnabled = !empty($guildsRealmCapabilities['supports_character_detail']);
?>

<div class="guild-list-shell list-page-shell feature-shell">
  <section class="guild-list-hero list-page-hero feature-hero">
  <form method="get" class="guild-search-form list-page-search-form" id="guildsSearchForm">
    <input type="hidden" name="n" value="server">
    <input type="hidden" name="sub" value="guilds">
    <input type="hidden" name="realm" value="<?php echo $realmId; ?>">
    <input type="hidden" name="p" value="1">
    <input type="hidden" name="per_page" value="<?php echo $items_per_page; ?>">
    <input type="hidden" name="faction" value="<?php echo htmlspecialchars($factionFilter); ?>">
    <input
      type="text"
      class="list-page-search-input"
      id="commandSearch"
      name="search"
      value="<?php echo htmlspecialchars($search); ?>"
      placeholder="Search guild name, leader, MOTD, faction, or class..."
      autocomplete="off"
    >
  </form>
  <?php render_character_pagination($p, $pnum, $items_per_page, $realmId, true, $search, false, $factionFilter, null, $pagination_route_url, $count); ?>
  <div class="guild-toolbar list-page-toolbar">
    <div class="guild-toolbar-controls list-page-toolbar-controls">
      <?php if ($isGm): ?>
      <form method="get" class="guild-toolbar-controls">
        <input type="hidden" name="n" value="server">
        <input type="hidden" name="sub" value="guilds">
        <input type="hidden" name="realm" value="<?php echo $realmId; ?>">
        <input type="hidden" name="p" value="1">
        <input type="hidden" name="per_page" value="<?php echo $items_per_page; ?>">
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>">
        <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sortDir); ?>">
        <input type="hidden" name="faction" value="<?php echo htmlspecialchars($factionFilter); ?>">
        <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
        <label class="inline-toggle">
          <input type="checkbox" name="show_gm" value="1" onchange="this.form.submit()" <?php echo $showGmGuilds ? 'checked' : ''; ?>>
          <span>GM Mode</span>
        </label>
        <?php if ($showGmGuilds): ?>
        <button type="submit" form="motd-form" class="feature-button is-primary">Save All MOTDs</button>
        <?php endif; ?>
      </form>
      <?php endif; ?>
    </div>
  </div>
  </section>

<?php if ($showGmGuilds): ?>
<form method="post" action="" id="motd-form">
  <input type="hidden" name="guilds_form_action" value="save_gm_motds">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($guildsCsrfToken, ENT_QUOTES); ?>">
  <input type="hidden" name="realm" value="<?php echo (int)$realmId; ?>">
</form>
<?php endif; ?>

<?php if ($guildMotdFeedback !== ''): ?>
  <div class="list-page-alert is-success">
    <?php echo htmlspecialchars($guildMotdFeedback); ?>
  </div>
<?php endif; ?>
<?php if ($guildMotdError !== ''): ?>
  <div class="list-page-alert is-error">
    <?php echo htmlspecialchars($guildMotdError); ?>
  </div>
<?php endif; ?>

<section class="guild-table-card list-page-table-card feature-panel">
<div class="wow-table guild-table<?php echo $showGmGuilds ? ' gm-mode' : ''; ?>">
  <div class="guild-table__header">
    <div class="guild-table__cell sortable"><a class="<?php echo $sortBy === 'guild' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($sort_urls['guild']); ?>">Guild<?php echo $sortBy === 'guild' ? ($sortDir === 'ASC' ? ' &#9650;' : ' &#9660;') : ''; ?></a></div>
    <div class="guild-table__cell guild-table__faction-filter">
      <select name="faction" class="guild-table__inline-filter list-page-inline-filter" form="guildsSearchForm" onchange="this.form.submit()">
        <option value="all" <?php if ($factionFilter === 'all') echo 'selected'; ?>>All</option>
        <option value="alliance" <?php if ($factionFilter === 'alliance') echo 'selected'; ?>>Alliance</option>
        <option value="horde" <?php if ($factionFilter === 'horde') echo 'selected'; ?>>Horde</option>
      </select>
    </div>
    <div class="guild-table__cell sortable"><a class="<?php echo $sortBy === 'leader' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($sort_urls['leader']); ?>">Leader<?php echo $sortBy === 'leader' ? ($sortDir === 'ASC' ? ' &#9650;' : ' &#9660;') : ''; ?></a></div>
    <div class="guild-table__cell sortable"><a class="<?php echo $sortBy === 'members' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($sort_urls['members']); ?>">Members<?php echo $sortBy === 'members' ? ($sortDir === 'ASC' ? ' &#9650;' : ' &#9660;') : ''; ?></a></div>
    <?php if (!$showGmGuilds): ?>
    <div class="guild-table__cell sortable"><a class="<?php echo $sortBy === 'avg' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($sort_urls['avg']); ?>">Avg Lvl<?php echo $sortBy === 'avg' ? ($sortDir === 'ASC' ? ' &#9650;' : ' &#9660;') : ''; ?></a></div>
    <div class="guild-table__cell sortable"><a class="<?php echo $sortBy === 'max' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($sort_urls['max']); ?>">Max Lvl<?php echo $sortBy === 'max' ? ($sortDir === 'ASC' ? ' &#9650;' : ' &#9660;') : ''; ?></a></div>
    <div class="guild-table__cell sortable"><a class="<?php echo $sortBy === 'avgilvl' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($sort_urls['avgilvl']); ?>">Avg iLvl<?php echo $sortBy === 'avgilvl' ? ($sortDir === 'ASC' ? ' &#9650;' : ' &#9660;') : ''; ?></a></div>
    <div class="guild-table__cell sortable"><a class="<?php echo $sortBy === 'maxilvl' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($sort_urls['maxilvl']); ?>">Max iLvl<?php echo $sortBy === 'maxilvl' ? ($sortDir === 'ASC' ? ' &#9650;' : ' &#9660;') : ''; ?></a></div>
    <?php else: ?>
    <div class="guild-table__cell">MOTD</div>
    <?php endif; ?>
  </div>

  <?php if ($guildsPage): ?>
    <?php foreach ($guildsPage as $guild): ?>
      <div class="guild-table__row">
        <div class="guild-table__cell guild-table__name">
          <a href="<?php echo htmlspecialchars($guild['guild_url']); ?>">
            <?php echo htmlspecialchars($guild['name']); ?>
          </a>
        </div>
        <div class="guild-table__cell guild-table__faction">
          <img src="<?php echo htmlspecialchars($guild['faction_icon_url']); ?>" alt="<?php echo htmlspecialchars($guild['faction_name']); ?>" title="<?php echo htmlspecialchars($guild['faction_name']); ?>">
        </div>
        <div class="guild-table__cell guild-table__leader class-<?php echo htmlspecialchars($guild['leader_class_slug']); ?>">
          <?php if (!empty($guild['leader_name'])): ?>
            <?php if ($guildCharacterLinksEnabled && !empty($guild['leader_url'])): ?>
              <a href="<?php echo htmlspecialchars($guild['leader_url']); ?>">
                <?php echo htmlspecialchars($guild['leader_name']); ?>
              </a>
            <?php else: ?>
              <span><?php echo htmlspecialchars($guild['leader_name']); ?></span>
            <?php endif; ?>
          <?php else: ?>
            -
          <?php endif; ?>
        </div>
        <div class="guild-table__cell"><?php echo (int)$guild['member_count']; ?></div>
        <?php if (!$showGmGuilds): ?>
        <div class="guild-table__cell"><?php echo (int)round((float)$guild['avg_level']); ?></div>
        <div class="guild-table__cell"><?php echo (int)$guild['max_level']; ?></div>
        <div class="guild-table__cell"><?php echo !empty($guild['avg_item_level']) ? number_format((float)$guild['avg_item_level'], 1) : '-'; ?></div>
        <div class="guild-table__cell"><?php echo !empty($guild['max_item_level']) ? number_format((float)$guild['max_item_level'], 1) : '-'; ?></div>
        <?php else: ?>
        <div class="guild-table__cell guild-table__motd-edit">
          <input type="text" name="motd[<?php echo (int)$guild['guildid']; ?>]"
            form="motd-form"
            value="<?php echo htmlspecialchars($guild['motd'] ?? ''); ?>"
            placeholder="No MOTD set">
        </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="guild-table__row">
      <div class="guild-table__cell list-page-empty-row">No guilds found.</div>
    </div>
  <?php endif; ?>
</div>
  </section>
</div>

<?php builddiv_end(); ?>



