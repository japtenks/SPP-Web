<?php builddiv_start(1, 'Realm Status', 1); ?>
  <script>
  window.realmstatusConfig = {
    pollUrl: <?php echo json_encode((string)($realmstatusPollUrl ?? '')); ?>,
    pollIntervalMs: 15000,
    targetRealmIds: <?php echo json_encode((array)($realmstatusTargetRealmIds ?? array())); ?>
  };
  </script>
  <div class="realmstatus-shell feature-shell">
    <section class="realmstatus-hero">
      <?php header_image("realm"); ?>
    </section>

    <div class="realmstatus-content">
    <aside class="realmstatus-legend feature-panel legend">
      <div class="realmstatus-legend-item">
        <span class="realmstatus-legend-dot">&#128994;</span>
        <span class="cleared">Cleared</span>
      </div>
      <div class="realmstatus-legend-item">
        <span class="realmstatus-legend-dot">&#128993;</span>
        <span class="partial">Partial</span>
      </div>
      <div class="realmstatus-legend-item">
        <span class="realmstatus-legend-dot">&#128308;</span>
        <span class="uncleared">Uncleared</span>
      </div>
      <div class="realmstatus-legend-item">
        <span class="realmstatus-legend-dot">Realm DB</span>
        <span class="cleared" data-realmstatus-polled-at>Polling realms: <?php echo htmlspecialchars(implode(', ', array_map('strval', (array)($realmstatusTargetRealmIds ?? array())))); ?><?php if (!empty($realmstatusPolledAtLabel)): ?>, updated <?php echo htmlspecialchars((string)$realmstatusPolledAtLabel); ?><?php endif; ?></span>
      </div>
    </aside>

    <div class="realm-list" data-realmstatus-list>
    <?php echo $realmstatusListHtml ?? ''; ?>
    </div>
    </div>
  </div>
<?php builddiv_end(); ?>
