<?php
require_once dirname(__DIR__, 3) . '/app/support/terminology.php';
$publicTerms = spp_terminology_public();
$statisticsRealmCapabilities = $realmCapabilities ?? $realm_capabilities ?? array();
builddiv_start(1, 'Statistics', 1);
?>

<div class="modern-content statistics-page">
  <div class="stats-controls">
    <div class="stats-filter-group">
      <?php foreach ($scopeLinks as $scopeKey => $scopeLabel): ?>
        <a class="stats-filter-chip<?php echo $statScope === $scopeKey ? ' is-active' : ''; ?>" href="index.php?n=server&amp;sub=statistic&amp;realm=<?php echo (int)$realmId; ?>&amp;scope=<?php echo htmlspecialchars($scopeKey); ?>">
          <?php echo htmlspecialchars($scopeLabel); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($databaseError !== ''): ?>
    <p style="color:#f66;">Database error: <?php echo htmlspecialchars($databaseError); ?></p>
  <?php endif; ?>

  <?php if ($num_chars == 0): ?>
    <p class="no-chars">0 Characters</p>
  <?php else: ?>
    <div class="stats-faction-columns">
      <div class="stats-faction-col horde">
        <div class="stats-faction-bg" style="background-image:url('<?php echo htmlspecialchars(spp_modern_meta_icon_url('faction/horde.png')); ?>');"></div>
        <div class="stats-faction-text">
          Horde: <strong><?php echo $num_horde; ?></strong> (<?php echo $pc_horde; ?>%)
        </div>
        <?php foreach ($hordeRaces as $id => $data): ?>
          <div class="stats-race-line">
            <img src="<?php echo htmlspecialchars(spp_modern_meta_icon_url('race/' . $id . '-0.jpg')); ?>" alt="">
            <span><?php echo $data['count']; ?> (<?php echo $data['pc']; ?>%)</span>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="stats-faction-col alliance">
        <div class="stats-faction-bg" style="background-image:url('<?php echo htmlspecialchars(spp_modern_meta_icon_url('faction/alliance.png')); ?>');"></div>
        <div class="stats-faction-text">
          Alliance: <strong><?php echo $num_ally; ?></strong> (<?php echo $pc_ally; ?>%)
        </div>
        <?php foreach ($allianceRaces as $id => $data): ?>
          <div class="stats-race-line">
            <img src="<?php echo htmlspecialchars(spp_modern_meta_icon_url('race/' . $id . '-0.jpg')); ?>" alt="">
            <span><?php echo $data['count']; ?> (<?php echo $data['pc']; ?>%)</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($hasDK): ?>
      <div class="stats-neutral-dk">
        <img src="<?php echo $currtmp; ?>/images/stat/12-0.gif" alt="Death Knight">
        <span>Death Knights: <strong><?php echo $rc[12]; ?></strong> (<?php echo $pc_12; ?>%)</span>
      </div>
    <?php endif; ?>

    <?php if (!empty($classCards)): ?>
      <section class="stats-breakdown-shell">
        <div class="stats-breakdown-grid">
          <details class="stats-accordion collapse-card" open>
            <summary class="collapse-card__summary">
              <div class="stats-accordion-summary-copy collapse-card__copy">
                <h3>Typical Class Level</h3>
                <p>Median level by class on this realm.</p>
              </div>
              <span class="stats-accordion-caret collapse-card__caret" aria-hidden="true"></span>
            </summary>
            <div class="stats-accordion-body collapse-card__body">
              <div class="stats-columns">
                <?php foreach ($availableClassOrder as $classId): ?>
                  <?php $class = $classCards[$classId]; ?>
                  <?php $height = $classMedianMax > 0 ? max(4, (int)round(($class['median_level'] / $classMedianMax) * 100)) : 0; ?>
                  <div class="stats-column">
                    <div class="stats-column-value"><?php echo (int)$class['median_level']; ?></div>
                    <div class="stats-column-track">
                      <div
                        class="stats-column-fill"
                        style="height: <?php echo $height; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>; box-shadow: inset 0 1px 0 rgba(255,255,255,0.18), 0 0 14px rgba(<?php echo htmlspecialchars($class['rgb']); ?>, 0.22); <?php echo $class['name'] === 'Priest' ? 'border:1px solid rgba(12,12,18,0.45);' : ''; ?>">
                      </div>
                    </div>
                    <div class="stats-column-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                      <?php echo htmlspecialchars($class['name']); ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </details>

          <details class="stats-accordion collapse-card">
            <summary class="collapse-card__summary">
              <div class="stats-accordion-summary-copy collapse-card__copy">
                <h3>Typical Class Play Time</h3>
                <p>Median play time by class, with average as a quick comparison.</p>
              </div>
              <span class="stats-accordion-caret collapse-card__caret" aria-hidden="true"></span>
            </summary>
            <div class="stats-accordion-body collapse-card__body">
              <div class="stats-bars">
                <?php foreach ($availableClassOrder as $classId): ?>
                  <?php $class = $classCards[$classId]; ?>
                  <?php $width = $classPlaytimeMedianMax > 0 ? max(4, (int)round(($class['median_playtime'] / $classPlaytimeMedianMax) * 100)) : 0; ?>
                  <div class="stats-row">
                    <div class="stats-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                      <?php echo htmlspecialchars($class['name']); ?>
                    </div>
                    <div class="stats-bar-track" title="Avg: <?php echo htmlspecialchars(spp_stat_format_playtime($class['avg_playtime'])); ?>">
                      <div class="stats-bar-fill" style="width: <?php echo $width; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>;"></div>
                    </div>
                    <div class="stats-value"><?php echo htmlspecialchars(spp_stat_format_playtime($class['median_playtime'])); ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </details>

          <details class="stats-accordion collapse-card">
            <summary class="collapse-card__summary">
              <div class="stats-accordion-summary-copy collapse-card__copy">
                <h3>Class Population</h3>
                <p>Character count by class on this realm.</p>
              </div>
              <span class="stats-accordion-caret collapse-card__caret" aria-hidden="true"></span>
            </summary>
            <div class="stats-accordion-body collapse-card__body">
              <div class="stats-bars">
                <?php foreach ($availableClassOrder as $classId): ?>
                  <?php $class = $classCards[$classId]; ?>
                  <?php $width = $classCountMax > 0 ? max(4, (int)round(($class['count'] / $classCountMax) * 100)) : 0; ?>
                  <div class="stats-row">
                    <div class="stats-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                      <?php echo htmlspecialchars($class['name']); ?>
                    </div>
                    <div class="stats-bar-track">
                      <div class="stats-bar-fill" style="width: <?php echo $width; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>;"></div>
                    </div>
                    <div class="stats-value"><?php echo (int)$class['count']; ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </details>

          <?php if (!empty($statisticsRealmCapabilities['supports_item_template'])): ?>
          <details class="stats-accordion collapse-card">
            <summary class="collapse-card__summary">
              <div class="stats-accordion-summary-copy collapse-card__copy">
                <h3>Typical Class Gear</h3>
                <p>Median equipped item level by class, using per-character average equipped gear.</p>
              </div>
              <span class="stats-accordion-caret collapse-card__caret" aria-hidden="true"></span>
            </summary>
            <div class="stats-accordion-body collapse-card__body">
              <div class="stats-bars">
                <?php foreach ($availableClassOrder as $classId): ?>
                  <?php $class = $classCards[$classId]; ?>
                  <?php $width = $classGearMedianMax > 0 ? max(4, (int)round(($class['median_gear'] / $classGearMedianMax) * 100)) : 0; ?>
                  <div class="stats-row">
                    <div class="stats-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                      <?php echo htmlspecialchars($class['name']); ?>
                    </div>
                    <div class="stats-bar-track" title="Avg: <?php echo $class['avg_gear'] > 0 ? htmlspecialchars(number_format((float)$class['avg_gear'], 1)) : '-'; ?>">
                      <div class="stats-bar-fill" style="width: <?php echo $width; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>;"></div>
                    </div>
                    <div class="stats-value"><?php echo $class['median_gear'] > 0 ? htmlspecialchars(number_format((float)$class['median_gear'], 1)) : '-'; ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </details>
          <?php endif; ?>

          <details class="stats-accordion collapse-card">
            <summary class="collapse-card__summary">
              <div class="stats-accordion-summary-copy collapse-card__copy">
                <h3>Online Share by Class</h3>
                <p>How much of each class is online right now.</p>
              </div>
              <span class="stats-accordion-caret collapse-card__caret" aria-hidden="true"></span>
            </summary>
            <div class="stats-accordion-body collapse-card__body">
              <div class="stats-bars">
                <?php foreach ($availableClassOrder as $classId): ?>
                  <?php $class = $classCards[$classId]; ?>
                  <?php $width = $classOnlineShareMax > 0 ? max(4, (int)round(($class['online_share'] / $classOnlineShareMax) * 100)) : 0; ?>
                  <div class="stats-row">
                    <div class="stats-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                      <?php echo htmlspecialchars($class['name']); ?>
                    </div>
                    <div class="stats-bar-track">
                      <div class="stats-bar-fill" style="width: <?php echo $width; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>;"></div>
                    </div>
                    <div class="stats-value"><?php echo htmlspecialchars(number_format((float)$class['online_share'], 1)); ?>%</div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </details>

          <details class="stats-accordion collapse-card">
            <summary class="collapse-card__summary">
              <div class="stats-accordion-summary-copy collapse-card__copy">
                <h3>Guilded Share by Class</h3>
                <p>How much of each class is currently attached to a guild.</p>
              </div>
              <span class="stats-accordion-caret collapse-card__caret" aria-hidden="true"></span>
            </summary>
            <div class="stats-accordion-body collapse-card__body">
              <div class="stats-bars">
                <?php foreach ($availableClassOrder as $classId): ?>
                  <?php $class = $classCards[$classId]; ?>
                  <?php $width = $classGuildedShareMax > 0 ? max(4, (int)round(($class['guilded_share'] / $classGuildedShareMax) * 100)) : 0; ?>
                  <div class="stats-row">
                    <div class="stats-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                      <?php echo htmlspecialchars($class['name']); ?>
                    </div>
                    <div class="stats-bar-track">
                      <div class="stats-bar-fill" style="width: <?php echo $width; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>;"></div>
                    </div>
                    <div class="stats-value"><?php echo htmlspecialchars(number_format((float)$class['guilded_share'], 1)); ?>%</div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </details>

          <details class="stats-accordion collapse-card">
            <summary class="collapse-card__summary">
              <div class="stats-accordion-summary-copy collapse-card__copy">
                <h3>PvP Tendency</h3>
                <p>Median honorable kills by class for a quick PvP-flavor read.</p>
              </div>
              <span class="stats-accordion-caret collapse-card__caret" aria-hidden="true"></span>
            </summary>
            <div class="stats-accordion-body collapse-card__body">
              <div class="stats-bars">
                <?php foreach ($availableClassOrder as $classId): ?>
                  <?php $class = $classCards[$classId]; ?>
                  <?php $width = $classHonorMedianMax > 0 ? max(4, (int)round(($class['median_honorable_kills'] / $classHonorMedianMax) * 100)) : 0; ?>
                  <div class="stats-row">
                    <div class="stats-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                      <?php echo htmlspecialchars($class['name']); ?>
                    </div>
                    <div class="stats-bar-track">
                      <div class="stats-bar-fill" style="width: <?php echo $width; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>;"></div>
                    </div>
                    <div class="stats-value"><?php echo (int)$class['median_honorable_kills']; ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </details>

          <details class="stats-accordion collapse-card">
            <summary class="collapse-card__summary">
              <div class="stats-accordion-summary-copy collapse-card__copy">
                <h3>Realm Play Time Mix</h3>
                <p>Overall time investment buckets and a rough character/human split.</p>
              </div>
              <span class="stats-accordion-caret collapse-card__caret" aria-hidden="true"></span>
            </summary>
            <div class="stats-accordion-body collapse-card__body">
              <div class="stats-mini-grid">
                <div class="stats-mini-card">
                  <h4>Play Time Buckets</h4>
                  <p class="stats-mini-note">A quick feel for how alt-heavy or main-heavy the realm is.</p>
                  <div class="stats-bucket-list">
                    <?php foreach ($playtimeBuckets as $label => $count): ?>
                      <?php $width = $playtimeBucketMax > 0 ? max(4, (int)round(($count / $playtimeBucketMax) * 100)) : 0; ?>
                      <div class="stats-bucket-row">
                        <div class="stats-bucket-label"><?php echo htmlspecialchars($label); ?></div>
                        <div class="stats-bar-track">
                          <div class="stats-bar-fill" style="width: <?php echo $width; ?>%; background: linear-gradient(90deg, #d69c3f, rgba(255,255,255,0.12));"></div>
                        </div>
                        <div class="stats-bucket-value"><?php echo (int)$count; ?></div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>

                <div class="stats-mini-card">
                <h4>Human Characters vs Bot Characters</h4>
                  <p class="stats-mini-note">Character totals grouped by whether the owning account matches the <code>rndbot</code> naming pattern.</p>
                  <div class="stats-split-card">
                    <div class="stats-split-stat">
                      <span>Human Characters</span>
                      <strong><?php echo (int)$totalPlayers; ?><?php echo $accountSplitTotal > 0 ? ' (' . round(($totalPlayers / $accountSplitTotal) * 100, 1) . '%)' : ''; ?></strong>
                    </div>
                    <div class="stats-split-stat">
                      <span>Bot Characters</span>
                      <strong><?php echo (int)$totalBots; ?><?php echo $accountSplitTotal > 0 ? ' (' . round(($totalBots / $accountSplitTotal) * 100, 1) . '%)' : ''; ?></strong>
                    </div>
                    <div class="stats-split-stat">
                      <span>Total Characters</span>
                      <strong><?php echo (int)$accountSplitTotal; ?></strong>
                    </div>
                  </div>
                </div>

                <div class="stats-mini-card">
                  <h4>Quest Completion Overview</h4>
                  <p class="stats-mini-note">Rewarded quest completions split by realm, characters, and humans.</p>
                  <div class="stats-split-card">
                    <div class="stats-split-stat">
                      <span>Realm max / avg / median</span>
                      <strong><?php echo (int)$questOverview['realm']['max']; ?> / <?php echo htmlspecialchars(number_format((float)$questOverview['realm']['avg'], 1)); ?> / <?php echo (int)$questOverview['realm']['median']; ?></strong>
                    </div>
                    <div class="stats-split-stat">
                      <span><?php echo htmlspecialchars($publicTerms['characters']); ?> max / avg / median</span>
                      <strong><?php echo (int)$questOverview['bots']['max']; ?> / <?php echo htmlspecialchars(number_format((float)$questOverview['bots']['avg'], 1)); ?> / <?php echo (int)$questOverview['bots']['median']; ?></strong>
                    </div>
                    <div class="stats-split-stat">
                      <span><?php echo htmlspecialchars($publicTerms['humans']); ?> max / avg / median</span>
                      <strong><?php echo (int)$questOverview['players']['max']; ?> / <?php echo htmlspecialchars(number_format((float)$questOverview['players']['avg'], 1)); ?> / <?php echo (int)$questOverview['players']['median']; ?></strong>
                    </div>
                  </div>
                </div>

                <div class="stats-mini-card">
                  <h4>Play Time Summary</h4>
                  <p class="stats-mini-note">Max, average, and median total play time across the same realm split.</p>
                  <div class="stats-split-card">
                    <div class="stats-split-stat">
                      <span>Realm max / avg / median</span>
                      <strong><?php echo htmlspecialchars(spp_stat_format_playtime($playtimeSummary['realm']['max'])); ?> / <?php echo htmlspecialchars(spp_stat_format_playtime($playtimeSummary['realm']['avg'])); ?> / <?php echo htmlspecialchars(spp_stat_format_playtime($playtimeSummary['realm']['median'])); ?></strong>
                    </div>
                    <div class="stats-split-stat">
                      <span><?php echo htmlspecialchars($publicTerms['characters']); ?> max / avg / median</span>
                      <strong><?php echo htmlspecialchars(spp_stat_format_playtime($playtimeSummary['bots']['max'])); ?> / <?php echo htmlspecialchars(spp_stat_format_playtime($playtimeSummary['bots']['avg'])); ?> / <?php echo htmlspecialchars(spp_stat_format_playtime($playtimeSummary['bots']['median'])); ?></strong>
                    </div>
                    <div class="stats-split-stat">
                      <span><?php echo htmlspecialchars($publicTerms['humans']); ?> max / avg / median</span>
                      <strong><?php echo htmlspecialchars(spp_stat_format_playtime($playtimeSummary['players']['max'])); ?> / <?php echo htmlspecialchars(spp_stat_format_playtime($playtimeSummary['players']['avg'])); ?> / <?php echo htmlspecialchars(spp_stat_format_playtime($playtimeSummary['players']['median'])); ?></strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </details>
        </div>
      </section>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php builddiv_end(); ?>

<?php /* Bot Rotation Health has moved to index.php?n=admin&sub=botrotation */ ?>
