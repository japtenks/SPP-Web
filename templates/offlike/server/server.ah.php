<?php
builddiv_start(1, 'Auction House', 1);
$ahRealmCapabilities = $realmCapabilities ?? $realm_capabilities ?? array();
$ahItemLinksEnabled = !empty($ahRealmCapabilities['supports_item_detail']);
$ahSortState = array(
    'realmId' => $realmId ?? 1,
    'filter' => $filter ?? 'all',
    'search' => $search ?? '',
    'qualityFilter' => $qualityFilter ?? -1,
    'itemClassFilter' => $itemClassFilter ?? -1,
    'minReqLevel' => $minReqLevel ?? null,
    'maxReqLevel' => $maxReqLevel ?? null,
    'sort' => $sort ?? 'time',
    'dir' => $dir ?? 'desc',
    'page' => $page ?? 1,
);
?>

<div class="ah-shell list-page-shell feature-shell">
  <?php if (!empty($pageError)): ?>
    <div class="list-page-empty-state"><?php echo htmlspecialchars((string)$pageError); ?></div>
  <?php else: ?>
  <section class="ah-hero list-page-hero feature-hero">
    <form method="get" class="ah-search-form list-page-search-form" id="ahFilterForm">
      <input type="hidden" name="n" value="server">
      <input type="hidden" name="sub" value="ah">
      <input type="hidden" name="realm" value="<?php echo (int)$realmId; ?>">
      <input type="hidden" name="filter" value="<?php echo htmlspecialchars((string)$filter); ?>">
      <input
        type="text"
        id="commandSearch"
        class="ah-search-input list-page-search-input"
        name="search"
        value="<?php echo htmlspecialchars((string)$search); ?>"
        placeholder="Search auction items..."
      >
    </form>

    <div class="pagination-controls">
      <div class="page-links">
        <?php echo compact_paginate((int)$page, (int)$numofpgs, (string)$baseUrl); ?>
      </div>
      <div class="page-size-form">
        <div class="ah-controls">
          <span class="ah-results-note list-page-results-note"><?php echo number_format((int)$total); ?> matched</span>
          <div class="ah-filter-bar">
            <?php foreach ($ahFilterLinks as $filterLink): ?>
              <a href="<?php echo htmlspecialchars((string)$filterLink['url']); ?>" class="ah-filter <?php echo htmlspecialchars((string)$filterLink['class']); ?><?php echo !empty($filterLink['active']) ? ' is-active' : ''; ?>"><?php echo htmlspecialchars((string)$filterLink['label']); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="ah-table-card list-page-table-card feature-panel">
  <div class="wow-table ah-table">
    <div class="ah-table__header">
      <div class="ah-table__cell">Type</div>
      <div class="ah-table__cell"><?php echo spp_server_ah_sort_link('item', 'Name', $ahSortState); ?></div>
      <div class="ah-table__cell"><?php echo spp_server_ah_sort_link('qty', 'Qty', $ahSortState); ?></div>
      <div class="ah-table__cell">Req Lvl</div>
      <div class="ah-table__cell">Item Lvl</div>
      <div class="ah-table__cell"><?php echo spp_server_ah_sort_link('time', 'Time Left', $ahSortState); ?></div>
      <div class="ah-table__cell"><?php echo spp_server_ah_sort_link('bid', 'Current Bid', $ahSortState); ?></div>
      <div class="ah-table__cell"><?php echo spp_server_ah_sort_link('buyout', 'Buyout', $ahSortState); ?></div>
    </div>
    <div class="ah-table__header ah-header-filters list-page-header-filters">
      <div class="ah-table__cell">
        <span class="ah-header-filter-title list-page-header-filter-title">Quality</span>
        <div class="ah-quality-filter-wrap">
          <select id="ah-quality-filter" class="ah-header-filter-control ah-header-filter-control--quality <?php echo (int)$qualityFilter >= 0 ? 'quality-' . (int)$qualityFilter : 'quality-any'; ?>" name="quality" form="ahFilterForm" aria-label="Filter by item quality">
            <?php foreach ($qualityOptions as $value => $label): ?>
              <option value="<?php echo (int)$value; ?>"<?php echo (int)$qualityFilter === (int)$value ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="ah-table__cell">
        <span class="ah-header-filter-title list-page-header-filter-title">Type</span>
        <select id="ah-type-filter" class="ah-header-filter-control" name="item_class" form="ahFilterForm" aria-label="Filter by type">
          <?php foreach ($itemClassOptions as $value => $label): ?>
            <option value="<?php echo (int)$value; ?>"<?php echo (int)$itemClassFilter === (int)$value ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="ah-table__cell"></div>
      <div class="ah-table__cell">
        <input id="ah-min-level" class="ah-header-filter-input" type="number" name="min_level" min="0" value="<?php echo $minReqLevel !== null ? (int)$minReqLevel : ''; ?>" form="ahFilterForm" placeholder="Min Req" aria-label="Minimum required level">
      </div>
      <div class="ah-table__cell">
        <input id="ah-max-level" class="ah-header-filter-input" type="number" name="max_level" min="0" value="<?php echo $maxReqLevel !== null ? (int)$maxReqLevel : ''; ?>" form="ahFilterForm" placeholder="Max Req" aria-label="Maximum required level">
      </div>
      <div class="ah-table__cell"></div>
      <div class="ah-table__cell"></div>
      <div class="ah-table__cell">
        <button type="submit" class="ah-filter faction-neutral ah-header-apply" form="ahFilterForm">Apply</button>
      </div>
    </div>

    <?php if (empty($ah_entry)): ?>
      <div class="ah-table__row empty">No auctions found for this search.</div>
    <?php else: ?>
      <?php foreach ($ah_entry as $row): ?>
        <div class="ah-table__row">
          <div class="ah-table__cell"><?php echo htmlspecialchars((string)$row['item_class_label']); ?></div>
          <div class="ah-table__cell">
            <?php if ($ahItemLinksEnabled && !empty($row['item_url'])): ?>
              <a
                class="<?php echo htmlspecialchars((string)$row['quality_class']); ?> js-ah-tooltip"
                href="<?php echo htmlspecialchars((string)$row['item_url']); ?>"
                data-item-id="<?php echo (int)$row['tooltip_item_id']; ?>"
                data-realm-id="<?php echo (int)$row['tooltip_realm_id']; ?>"
              >
                <?php echo htmlspecialchars((string)$row['itemname']); ?>
              </a>
            <?php else: ?>
              <span class="<?php echo htmlspecialchars((string)$row['quality_class']); ?>">
                <?php echo htmlspecialchars((string)$row['itemname']); ?>
              </span>
            <?php endif; ?>
          </div>
          <div class="ah-table__cell"><?php echo (int)$row['quantity']; ?></div>
          <div class="ah-table__cell"><?php echo !empty($row['required_level']) ? (int)$row['required_level'] : '-'; ?></div>
          <div class="ah-table__cell"><?php echo !empty($row['item_level']) ? (int)$row['item_level'] : '-'; ?></div>
          <div class="ah-table__cell">
            <?php if (!empty($row['is_expired'])): ?>
              <span class="expired">Expired</span>
            <?php else: ?>
              <?php echo htmlspecialchars((string)$row['time_left_label']); ?>
            <?php endif; ?>
          </div>
          <div class="ah-table__cell price-cell">
            <span class="gold-inline">
              <?php if ((int)$row['current_bid']['gold'] > 0): ?><?php echo (int)$row['current_bid']['gold']; ?><img src="<?php echo htmlspecialchars((string)$iconPath); ?>/gold.GIF" alt="g"><?php endif; ?>
              <?php if ((int)$row['current_bid']['silver'] > 0): ?> <?php echo (int)$row['current_bid']['silver']; ?><img src="<?php echo htmlspecialchars((string)$iconPath); ?>/silver.GIF" alt="s"><?php endif; ?>
              <?php if ((int)$row['current_bid']['copper'] > 0 || ((int)$row['current_bid']['gold'] === 0 && (int)$row['current_bid']['silver'] === 0)): ?> <?php echo (int)$row['current_bid']['copper']; ?><img src="<?php echo htmlspecialchars((string)$iconPath); ?>/copper.GIF" alt="c"><?php endif; ?>
            </span>
          </div>
          <div class="ah-table__cell price-cell">
            <span class="gold-inline">
              <?php if ((int)$row['buyout']['gold'] > 0): ?><?php echo (int)$row['buyout']['gold']; ?><img src="<?php echo htmlspecialchars((string)$iconPath); ?>/gold.GIF" alt="g"><?php endif; ?>
              <?php if ((int)$row['buyout']['silver'] > 0): ?> <?php echo (int)$row['buyout']['silver']; ?><img src="<?php echo htmlspecialchars((string)$iconPath); ?>/silver.GIF" alt="s"><?php endif; ?>
              <?php if ((int)$row['buyout']['copper'] > 0 || ((int)$row['buyout']['gold'] === 0 && (int)$row['buyout']['silver'] === 0)): ?> <?php echo (int)$row['buyout']['copper']; ?><img src="<?php echo htmlspecialchars((string)$iconPath); ?>/copper.GIF" alt="c"><?php endif; ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  </section>
  <?php endif; ?>
</div>

<?php builddiv_end(); ?>
