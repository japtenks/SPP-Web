<?php

require_once dirname(__DIR__, 3) . '/app/server/marketplace-page.php';
require_once dirname(__DIR__, 3) . '/app/support/terminology.php';

$marketplaceState = spp_marketplace_build_page_state($realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null));
$publicTerms = spp_terminology_public();
$realmId = (int)$marketplaceState['realmId'];
$marketplace = is_array($marketplaceState['professionSummaries'] ?? null) ? $marketplaceState['professionSummaries'] : [];
$pageError = (string)$marketplaceState['pageError'];

builddiv_start(1, 'Marketplace', 1);
?>
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars(spp_template_asset_url('css/armory-tooltips.css'), ENT_QUOTES); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars(spp_template_asset_url('css/marketplace.css'), ENT_QUOTES); ?>" />
<script>
window.marketplaceConfig = {
  realmId: <?php echo $realmId; ?>,
  apiUrl: <?php echo json_encode('index.php?n=server&sub=marketplaceapi&realm=' . $realmId); ?>,
  iconBaseUrl: <?php echo json_encode(spp_modern_icon_url('')); ?>
};
document.documentElement.setAttribute('data-marketplace-icon-base-url', <?php echo json_encode(spp_modern_icon_url('')); ?>);
</script>
<script src="<?php echo htmlspecialchars(spp_template_asset_url('js/item-tooltips.js'), ENT_QUOTES); ?>"></script>
<script src="<?php echo htmlspecialchars(spp_template_asset_url('js/marketplace.js'), ENT_QUOTES); ?>"></script>

<div class="marketplace-shell feature-shell">
  <section class="marketplace-search-panel feature-hero">
    <p class="marketplace-search-label">Find The Best Crafter</p>
    <p class="marketplace-search-copy">Marketplace highlights the strongest specialists on this realm first, with profession specialties and online availability up front.</p>
    <div class="marketplace-construction-warning" role="note" aria-label="Marketplace under construction notice">
      <strong>Under Construction</strong>
      <span>Marketplace is still being built, so some listings and features may change as we keep improving it.</span>
    </div>
    <input id="marketplace-craft-search" class="marketplace-search-input list-page-search-input" type="search" placeholder="Search for a craft, like Copper Chain Pants" autocomplete="off">
  </section>

  <?php if ($pageError !== ''): ?>
    <div class="marketplace-error list-page-alert is-error"><?php echo htmlspecialchars($pageError); ?></div>
  <?php elseif (empty($marketplace)): ?>
    <div class="marketplace-empty list-page-empty-row feature-panel">No character profession data was available for this realm.</div>
  <?php else: ?>
    <section id="marketplace-search-results" class="marketplace-search-results" hidden>
      <div class="marketplace-loading feature-panel">Searching marketplace...</div>
    </section>

    <section id="marketplace-profession-grid" class="marketplace-profession-grid">
      <?php foreach ($marketplace as $professionName => $profession): ?>
        <article class="marketplace-profession" data-skill-id="<?php echo (int)$profession['skill_id']; ?>">
          <div class="marketplace-profession-summary">
            <div class="marketplace-profession-head">
              <div class="marketplace-profession-id">
                <img class="marketplace-profession-icon" src="<?php echo htmlspecialchars((string)$profession['icon'], ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars((string)$professionName, ENT_QUOTES); ?>">
                <div>
                  <div class="marketplace-profession-kicker">
                    <?php
                      echo !empty($profession['has_coverage']) ? 'Top crafter coverage available' : ('No ' . (string)$professionName . ' characters recorded on this realm');
                    ?>
                  </div>
                  <h2 class="marketplace-profession-title"><?php echo htmlspecialchars((string)$professionName); ?></h2>
                  <?php if (!empty($profession['description'])): ?>
                    <p class="marketplace-profession-desc"><?php echo htmlspecialchars((string)$profession['description']); ?></p>
                  <?php endif; ?>
                </div>
              </div>
              <div class="marketplace-profession-stats">
                <div class="marketplace-profession-stat">
                  <strong><?php echo (int)$profession['profession_cap']; ?></strong>
                  <span>Realm Cap</span>
                </div>
                <div class="marketplace-profession-stat">
                  <strong><?php echo (int)($profession['total_crafters'] ?? 0); ?></strong>
                  <span>Bot Crafters</span>
                </div>
              </div>
            </div>
          </div>

          <div class="marketplace-profession-body">
            <section class="marketplace-specialist-panel">
              <div class="marketplace-section-head">
                <h3 class="marketplace-section-title">Top Crafters</h3>
                <span class="marketplace-section-copy">Top 3 per faction</span>
              </div>
              <div class="marketplace-featured-board">
                <?php foreach (['Horde', 'Alliance'] as $factionName): ?>
                  <?php $factionCrafters = (array)($profession['faction_top_crafters'][$factionName] ?? []); ?>
                  <section class="marketplace-faction-panel marketplace-faction-panel--<?php echo strtolower($factionName); ?>">
                    <div class="marketplace-faction-head">
                      <h3 class="marketplace-faction-title"><?php echo htmlspecialchars($factionName); ?></h3>
                      <span class="marketplace-faction-copy"><?php echo count($factionCrafters); ?> listed</span>
                    </div>
                    <div class="marketplace-bot-grid">
                      <?php if (!empty($factionCrafters)): ?>
                        <?php foreach ($factionCrafters as $factionCrafter): ?>
                          <article class="marketplace-crafter-card">
                            <div class="marketplace-bot-head">
                              <a class="marketplace-bot-link" href="<?php echo htmlspecialchars((string)$factionCrafter['href'], ENT_QUOTES); ?>">
                                <span class="marketplace-bot-avatars">
                                  <img src="<?php echo htmlspecialchars((string)$factionCrafter['race_icon'], ENT_QUOTES); ?>" alt="">
                                  <img src="<?php echo htmlspecialchars((string)$factionCrafter['class_icon'], ENT_QUOTES); ?>" alt="">
                                </span>
                                <span>
                                  <strong class="marketplace-bot-name"><?php echo htmlspecialchars((string)$factionCrafter['name']); ?></strong>
                                  <span class="marketplace-bot-meta"><?php echo htmlspecialchars((string)$factionCrafter['tier']); ?> crafter</span>
                                </span>
                              </a>
                              <span class="marketplace-bot-rank">Skill <?php echo (int)$factionCrafter['value']; ?>/<?php echo (int)$factionCrafter['max']; ?></span>
                            </div>
                            <div class="marketplace-crafter-meta">
                              <span class="marketplace-online-pill<?php echo !empty($factionCrafter['online']) ? ' is-online' : ' is-offline'; ?>">
                                <span class="marketplace-online-pill__dot" aria-hidden="true"></span>
                                <?php echo htmlspecialchars((string)$factionCrafter['online_label']); ?>
                              </span>
                              <span class="marketplace-crafter-chip marketplace-crafter-chip--cap"><?php echo !empty($factionCrafter['is_capped']) ? 'Capped' : 'Below cap'; ?></span>
                            </div>
                          </article>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <div class="marketplace-bot-empty">No <?php echo strtolower($factionName); ?> <?php echo htmlspecialchars(strtolower((string)$professionName)); ?> characters are recorded on this realm yet.</div>
                      <?php endif; ?>
                    </div>
                  </section>
                <?php endforeach; ?>
              </div>
            </section>

            <details class="marketplace-profession-detail-toggle collapse-card" data-skill-id="<?php echo (int)$profession['skill_id']; ?>">
              <summary class="collapse-card__summary">View detailed recipes and specialties</summary>
              <div class="marketplace-lazy-detail collapse-card__body" data-loaded="0"></div>
            </details>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</div>
<?php builddiv_end(); ?>
