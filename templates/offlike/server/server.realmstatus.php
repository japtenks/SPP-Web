<?php builddiv_start(1, 'Realm Status', 1); ?>
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
    </aside>

    <div class="realm-list">
    <?php foreach ($realmstatusItems as $realmItem): ?>
    <div class="realm-card <?php echo (int)$realmItem['res_color'] === 1 ? 'online' : 'offline'; ?><?php echo !empty($realmItem['is_offline_realm']) ? ' is-collapsed' : ''; ?>"<?php if (!empty($realmItem['is_offline_realm'])): ?> data-realm-collapse="card"<?php endif; ?>>
      <div class="realm-card__header">
        <img src="<?php echo htmlspecialchars($realmItem['img']); ?>" alt="<?php echo htmlspecialchars($realmItem['status_label']); ?>" class="realm-card__icon"/>
        <span class="realm-card__name"><?php echo htmlspecialchars($realmItem['name']); ?></span>
        <span class="realm-card__header-meta">
          <?php if (!empty($realmItem['is_offline_realm'])): ?>
            <button type="button" class="realm-card__collapse-toggle" data-realm-collapse="toggle" aria-expanded="false">Show Details</button>
          <?php endif; ?>
          <span class="realm-card__build">(Build: <?php echo htmlspecialchars($realmItem['build']); ?>)</span>
        </span>
      </div>

      <div class="realm-card__body">
        <div><strong>Uptime:</strong> <?php echo htmlspecialchars($realmItem['uptime_label']); ?></div>
        <div><strong>Stable Avg Uptime (<?php echo htmlspecialchars($realmItem['stats_window_label']); ?>):</strong> <?php echo htmlspecialchars($realmItem['stable_avg_up_label']); ?></div>
        <div><strong>Median Uptime (<?php echo htmlspecialchars($realmItem['stats_window_label']); ?>):</strong> <?php echo htmlspecialchars($realmItem['median_up_label']); ?></div>
        <div><strong>Short Restarts (<?php echo htmlspecialchars($realmItem['stats_window_label']); ?>):</strong> <?php echo (int)$realmItem['short_restarts']; ?><?php echo !empty($realmItem['stable_runs']) ? ' (' . (int)$realmItem['stable_runs'] . ' stable runs kept)' : ''; ?></div>
        <div><strong>Restarts Today:</strong> <?php echo (int)$realmItem['restarts']; ?></div>
        <div><strong>Type:</strong> <?php echo htmlspecialchars($realmItem['type']); ?></div>
        <div><strong>Population:</strong> <?php if (!empty($realmItem['has_char_data'])): ?><?php echo htmlspecialchars($realmItem['population_label']); ?> (<?php echo population_view((int)$realmItem['pop']); ?>)<?php else: ?>-<?php endif; ?></div>
        <div><strong>Online:</strong> <?php echo htmlspecialchars($realmItem['online_label']); ?></div>
        <div><strong>Players Online:</strong> <?php echo htmlspecialchars($realmItem['player_count_label']); ?></div>

          <?php if (!empty($realmItem['balance'])): ?>
            <div class="faction-labels">
              <span class="alliance">Alliance (<?php echo (int)$realmItem['alli']; ?>)</span>
              <span class="horde">Horde (<?php echo (int)$realmItem['horde']; ?>)</span>
            </div>
            <div class="faction-bar">
              <div class="alliance" style="width:<?php echo (int)$realmItem['balance']['alliance_width']; ?>%"></div>
              <div class="horde" style="width:<?php echo (int)$realmItem['balance']['horde_width']; ?>%"></div>
            </div>
            <div class="faction-balance <?php echo htmlspecialchars($realmItem['balance']['balance_class']); ?>">
              <?php echo htmlspecialchars($realmItem['balance']['balance_text']); ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="realm-card__meta"><strong>Avg Online / Avg Total / Max Level:</strong> <?php echo htmlspecialchars($realmItem['avg_online_total_max_level_label']); ?></div>

        <div class="realm-card__progression">
          <strong>Progression:</strong>
          <?php foreach ($realmItem['progression_badges'] as $badge): ?>
            <span class="<?php echo htmlspecialchars($badge['class']); ?>"><?php echo htmlspecialchars($badge['label']); ?></span>
          <?php endforeach; ?>
          <span class="realm-card__avg-ilvl"><strong>Avg iLvl Online / Total:</strong> <?php echo htmlspecialchars($realmItem['avg_ilvl_online_total_label']); ?></span>
        </div>

        <?php if ($realmstatusDebug): ?>
        <div class="realm-card__progression realm-card__debug" style="margin-top:10px;">
          <strong>Debug:</strong>
          <div style="margin-top:6px;color:#cdb88a;font-size:.92rem;line-height:1.55;">
            <?php echo htmlspecialchars($realmItem['debug_summary']); ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
    </div>
  </div>
<?php builddiv_end(); ?>
