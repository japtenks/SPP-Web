<?php builddiv_start(1, 'Personality Feed', 1); ?>

<div class="modern-content" style="display:grid;gap:18px;">
  <div style="padding:14px 16px;border:1px solid rgba(214,188,124,0.18);background:rgba(10,16,24,0.72);color:#d7c9a2;">
    This page reads the live characters DB directly for personality-phase verification.
    It does not require the armory middleman for these signals.
    <div style="margin-top:8px;color:#b9ad88;">
      Server clock base: <strong style="color:#f0e6c8;"><?php echo htmlspecialchars($serverNowLabel, ENT_QUOTES); ?></strong>
      in <strong style="color:#f0e6c8;"><?php echo htmlspecialchars($serverTimezoneLabel, ENT_QUOTES); ?></strong>.
    </div>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(214,188,124,0.12);color:#b9ad88;">
      From the site root, snapshot polling:
      <code style="color:#f0e6c8;">php tools/snapshot_personality_history.php --realm=<?php echo (int)$realmId; ?></code>
      <br>
      Lighter sample:
      <code style="color:#f0e6c8;">php tools/snapshot_personality_history.php --realm=<?php echo (int)$realmId; ?> --limit=200</code>
    </div>
  </div>

  <?php if ($databaseError !== ''): ?>
    <div style="padding:14px 16px;border:1px solid rgba(180,70,70,0.45);background:rgba(55,15,15,0.55);color:#ffd3d3;">
      Database error: <?php echo htmlspecialchars($databaseError, ENT_QUOTES); ?>
    </div>
  <?php else: ?>
    <section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;">
      <?php foreach ($phaseCards as $card): ?>
        <article style="padding:16px;border:1px solid rgba(214,188,124,0.18);background:linear-gradient(180deg, rgba(17,24,34,0.94), rgba(9,13,20,0.94));">
          <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
            <h3 style="margin:0;color:#f0e6c8;font-size:1rem;"><?php echo htmlspecialchars($card['title'], ENT_QUOTES); ?></h3>
            <span style="padding:4px 8px;border-radius:999px;font-size:0.75rem;font-weight:700;background:<?php echo $card['badge']['class'] === 'ok' ? 'rgba(68,138,88,0.25)' : 'rgba(161,108,38,0.25)'; ?>;color:<?php echo $card['badge']['class'] === 'ok' ? '#9de3aa' : '#f2c36f'; ?>;">
              <?php echo htmlspecialchars($card['badge']['label'], ENT_QUOTES); ?>
            </span>
          </div>
          <p style="margin:10px 0 0;color:#c7bb98;line-height:1.5;"><?php echo htmlspecialchars($card['detail'], ENT_QUOTES); ?></p>
        </article>
      <?php endforeach; ?>
    </section>

    <section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;">
      <article style="padding:16px;border:1px solid rgba(214,188,124,0.16);background:rgba(10,16,24,0.78);">
        <div style="color:#9a8d68;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em;">Personality Rows</div>
        <div style="margin-top:8px;font-size:1.9rem;color:#f0e6c8;"><?php echo (int)($summary['personality_rows'] ?? 0); ?></div>
        <div style="margin-top:6px;color:#b9ad88;">Linked characters: <?php echo (int)($summary['linked_characters'] ?? 0); ?></div>
      </article>
      <article style="padding:16px;border:1px solid rgba(214,188,124,0.16);background:rgba(10,16,24,0.78);">
        <div style="color:#9a8d68;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em;">Online Snapshot</div>
        <div style="margin-top:8px;font-size:1.9rem;color:#f0e6c8;"><?php echo (int)($summary['online_now'] ?? 0); ?></div>
        <div style="margin-top:6px;color:#b9ad88;">All chars online: <?php echo (int)($summary['total_online_characters'] ?? 0); ?></div>
      </article>
      <article style="padding:16px;border:1px solid rgba(214,188,124,0.16);background:rgba(10,16,24,0.78);">
        <div style="color:#9a8d68;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em;">Schedule Evidence</div>
        <div style="margin-top:8px;font-size:1.9rem;color:#f0e6c8;"><?php echo (int)($summary['login_written'] ?? 0); ?></div>
        <div style="margin-top:6px;color:#b9ad88;">Logout writes: <?php echo (int)($summary['logout_written'] ?? 0); ?></div>
      </article>
      <article style="padding:16px;border:1px solid rgba(214,188,124,0.16);background:rgba(10,16,24,0.78);">
        <div style="color:#9a8d68;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em;">Leaders / Tourists</div>
        <div style="margin-top:8px;font-size:1.9rem;color:#f0e6c8;"><?php echo (int)($summary['leaders'] ?? 0); ?></div>
        <div style="margin-top:6px;color:#b9ad88;">Tourists: <?php echo (int)($summary['tourists'] ?? 0); ?> | PvP lean: <?php echo (int)($summary['pvp_lean_rows'] ?? 0); ?></div>
      </article>
      <article style="padding:16px;border:1px solid rgba(214,188,124,0.16);background:rgba(10,16,24,0.78);">
        <div style="color:#9a8d68;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em;">History Snapshots</div>
        <div style="margin-top:8px;font-size:1.9rem;color:#f0e6c8;"><?php echo (int)($historySummary['total_snapshots'] ?? 0); ?></div>
        <?php if (!empty($historySummary['available'])): ?>
          <div style="margin-top:6px;color:#b9ad88;">
            Latest batch rows: <?php echo (int)($historySummary['latest_batch_rows'] ?? 0); ?>
          </div>
          <div style="margin-top:4px;color:#8f8463;font-size:0.82rem;">
            <?php echo htmlspecialchars((string)($historySummary['latest_capture'] ?? '-'), ENT_QUOTES); ?>
          </div>
        <?php else: ?>
          <div style="margin-top:6px;color:#b9ad88;">Apply `05_personality_history.sql` to enable drift polling.</div>
        <?php endif; ?>
      </article>
    </section>

    <section style="display:grid;grid-template-columns:1.2fr 0.8fr;gap:18px;">
      <article style="padding:16px;border:1px solid rgba(214,188,124,0.16);background:rgba(10,16,24,0.78);overflow:auto;">
        <h3 style="margin:0 0 12px;color:#f0e6c8;">Archetype / Affiliation Mix</h3>
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Archetype</th>
              <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Affiliation</th>
              <th style="text-align:right;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Rows</th>
              <th style="text-align:right;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Online</th>
              <th style="text-align:right;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Leaders</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($archetypeRows as $row): ?>
              <tr>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#f0e6c8;"><?php echo htmlspecialchars($row['archetype'], ENT_QUOTES); ?></td>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#c7bb98;"><?php echo htmlspecialchars($row['affiliation'], ENT_QUOTES); ?></td>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right;color:#c7bb98;"><?php echo (int)$row['row_count']; ?></td>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right;color:#c7bb98;"><?php echo (int)$row['online_now']; ?></td>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right;color:#c7bb98;"><?php echo (int)$row['leaders']; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </article>

      <article style="padding:16px;border:1px solid rgba(214,188,124,0.16);background:rgba(10,16,24,0.78);overflow:auto;">
        <h3 style="margin:0 0 12px;color:#f0e6c8;">Timezone Distribution</h3>
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Offset</th>
              <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Region Guess</th>
              <th style="text-align:right;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Rows</th>
              <th style="text-align:right;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Avg Session</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($timezoneRows as $row): ?>
              <tr>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#f0e6c8;"><?php echo (int)$row['tz_offset_minutes']; ?> min</td>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#c7bb98;"><?php echo htmlspecialchars(spp_personality_timezone_region_label($row['tz_offset_minutes']), ENT_QUOTES); ?></td>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right;color:#c7bb98;"><?php echo (int)$row['row_count']; ?></td>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right;color:#c7bb98;"><?php echo htmlspecialchars(number_format((float)$row['avg_session_minutes'], 1), ENT_QUOTES); ?>m</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </article>
    </section>

    <section style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
      <article style="padding:16px;border:1px solid rgba(214,188,124,0.16);background:rgba(10,16,24,0.78);overflow:auto;">
        <h3 style="margin:0 0 12px;color:#f0e6c8;">Average Weight Profile</h3>
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Archetype</th>
              <th style="text-align:right;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Quest</th>
              <th style="text-align:right;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Grind</th>
              <th style="text-align:right;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">PvP</th>
              <th style="text-align:right;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Dung</th>
              <th style="text-align:right;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Farm</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($weightRows as $row): ?>
              <tr>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#f0e6c8;"><?php echo htmlspecialchars($row['archetype'], ENT_QUOTES); ?></td>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right;color:#c7bb98;"><?php echo htmlspecialchars(number_format((float)$row['avg_w_quest'], 1), ENT_QUOTES); ?></td>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right;color:#c7bb98;"><?php echo htmlspecialchars(number_format((float)$row['avg_w_grind'], 1), ENT_QUOTES); ?></td>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right;color:#c7bb98;"><?php echo htmlspecialchars(number_format((float)$row['avg_w_pvp'], 1), ENT_QUOTES); ?></td>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right;color:#c7bb98;"><?php echo htmlspecialchars(number_format((float)$row['avg_w_dungeon'], 1), ENT_QUOTES); ?></td>
                <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right;color:#c7bb98;"><?php echo htmlspecialchars(number_format((float)$row['avg_w_farm'], 1), ENT_QUOTES); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </article>

      <article style="padding:16px;border:1px solid rgba(214,188,124,0.16);background:rgba(10,16,24,0.78);overflow:auto;">
        <h3 style="margin:0 0 12px;color:#f0e6c8;">Guild Culture Table</h3>
        <div style="margin-bottom:10px;color:#c7bb98;">
          Rows: <?php echo (int)($summary['culture_rows'] ?? 0); ?> |
          Recent events: <?php echo (int)($summary['culture_recent_rows'] ?? 0); ?>
        </div>
        <?php if (empty($guildCultureRows)): ?>
          <div style="color:#b9ad88;">No live culture rows yet.</div>
        <?php else: ?>
          <table style="width:100%;border-collapse:collapse;">
            <thead>
              <tr>
                <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Guild</th>
                <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Dominant</th>
                <th style="text-align:right;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;">Cohesion</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($guildCultureRows as $row): ?>
                <tr>
                  <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#f0e6c8;"><?php echo (int)$row['guildid']; ?></td>
                  <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#c7bb98;"><?php echo htmlspecialchars($row['archetype_dominant'], ENT_QUOTES); ?></td>
                  <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right;color:#c7bb98;"><?php echo (int)$row['cohesion']; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </article>
    </section>

    <section style="padding:16px;border:1px solid rgba(214,188,124,0.16);background:rgba(10,16,24,0.78);overflow:auto;">
      <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:12px;">
        <h3 style="margin:0;color:#f0e6c8;">Recent Personality Samples</h3>
        <div style="color:#b9ad88;">Showing up to <?php echo (int)$sampleLimit; ?> rows after search/sort.</div>
      </div>
      <form method="get" action="index.php" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px;">
        <input type="hidden" name="n" value="server">
        <input type="hidden" name="sub" value="personality">
        <input type="hidden" name="realm" value="<?php echo (int)$realmId; ?>">
        <input type="hidden" name="limit" value="<?php echo (int)$sampleLimit; ?>">
        <input
          type="text"
          name="sample_search"
          value="<?php echo htmlspecialchars($sampleSearch, ENT_QUOTES); ?>"
          placeholder="Search bot, archetype, region, status..."
          style="min-width:320px;padding:8px 10px;border:1px solid rgba(214,188,124,0.18);background:rgba(8,12,18,0.92);color:#f0e6c8;"
        >
        <label style="display:flex;align-items:center;gap:8px;color:#b9ad88;">
          Sort
          <select
            name="sample_sort"
            style="padding:8px 10px;border:1px solid rgba(214,188,124,0.18);background:rgba(8,12,18,0.92);color:#f0e6c8;"
          >
            <option value="last_login"<?php echo $sampleSortBy === 'last_login' ? ' selected' : ''; ?>>Last Login</option>
            <option value="last_logout"<?php echo $sampleSortBy === 'last_logout' ? ' selected' : ''; ?>>Last Logout</option>
            <option value="local_now"<?php echo $sampleSortBy === 'local_now' ? ' selected' : ''; ?>>Actual Local Now</option>
            <option value="plan_status"<?php echo $sampleSortBy === 'plan_status' ? ' selected' : ''; ?>>Plan vs Actual</option>
            <option value="window"<?php echo $sampleSortBy === 'window' ? ' selected' : ''; ?>>Planned Window</option>
            <option value="type"<?php echo $sampleSortBy === 'type' ? ' selected' : ''; ?>>Type</option>
            <option value="bot"<?php echo $sampleSortBy === 'bot' ? ' selected' : ''; ?>>Bot</option>
            <option value="group_time"<?php echo $sampleSortBy === 'group_time' ? ' selected' : ''; ?>>Group Time</option>
          </select>
        </label>
        <label style="display:flex;align-items:center;gap:8px;color:#b9ad88;">
          Direction
          <select
            name="sample_dir"
            style="padding:8px 10px;border:1px solid rgba(214,188,124,0.18);background:rgba(8,12,18,0.92);color:#f0e6c8;"
          >
            <option value="DESC"<?php echo $sampleSortDir === 'DESC' ? ' selected' : ''; ?>>Descending</option>
            <option value="ASC"<?php echo $sampleSortDir === 'ASC' ? ' selected' : ''; ?>>Ascending</option>
          </select>
        </label>
        <button type="submit" style="padding:8px 12px;border:1px solid rgba(214,188,124,0.24);background:rgba(37,51,28,0.78);color:#f0e6c8;cursor:pointer;">Apply</button>
        <?php if ($sampleSearch !== ''): ?>
          <a href="index.php?n=server&amp;sub=personality&amp;realm=<?php echo (int)$realmId; ?>&amp;limit=<?php echo (int)$sampleLimit; ?>&amp;sample_sort=<?php echo htmlspecialchars($sampleSortBy, ENT_QUOTES); ?>&amp;sample_dir=<?php echo htmlspecialchars($sampleSortDir, ENT_QUOTES); ?>" style="color:#d4bb7d;">Clear search</a>
        <?php endif; ?>
      </form>
      <?php
        $sortUrl = static function (string $sortKey) use ($realmId, $sampleLimit, $sampleSearch, $sampleSortBy, $sampleSortDir): string {
            $nextDir = ($sampleSortBy === $sortKey && $sampleSortDir === 'ASC') ? 'DESC' : 'ASC';
            return 'index.php?n=server&sub=personality&realm=' . (int)$realmId
                . '&limit=' . (int)$sampleLimit
                . '&sample_search=' . rawurlencode($sampleSearch)
                . '&sample_sort=' . rawurlencode($sortKey)
                . '&sample_dir=' . rawurlencode($nextDir);
        };
      ?>
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;"><a href="<?php echo htmlspecialchars($sortUrl('bot'), ENT_QUOTES); ?>" style="color:inherit;text-decoration:none;">Bot</a></th>
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;"><a href="<?php echo htmlspecialchars($sortUrl('type'), ENT_QUOTES); ?>" style="color:inherit;text-decoration:none;">Type</a></th>
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;"><a href="<?php echo htmlspecialchars($sortUrl('window'), ENT_QUOTES); ?>" style="color:inherit;text-decoration:none;">Planned Window</a></th>
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;"><a href="<?php echo htmlspecialchars($sortUrl('local_now'), ENT_QUOTES); ?>" style="color:inherit;text-decoration:none;">Actual Local Now</a></th>
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;"><a href="<?php echo htmlspecialchars($sortUrl('plan_status'), ENT_QUOTES); ?>" style="color:inherit;text-decoration:none;">Plan vs Actual</a></th>
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;"><a href="<?php echo htmlspecialchars($sortUrl('last_login'), ENT_QUOTES); ?>" style="color:inherit;text-decoration:none;">Last Login</a></th>
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;"><a href="<?php echo htmlspecialchars($sortUrl('last_logout'), ENT_QUOTES); ?>" style="color:inherit;text-decoration:none;">Last Logout</a></th>
            <th style="text-align:right;padding:8px 10px;border-bottom:1px solid rgba(214,188,124,0.18);color:#9a8d68;"><a href="<?php echo htmlspecialchars($sortUrl('group_time'), ENT_QUOTES); ?>" style="color:inherit;text-decoration:none;">Group Time</a></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sampleRows as $row): ?>
            <tr>
              <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#f0e6c8;">
                <?php echo htmlspecialchars((string)($row['name'] ?? ('GUID ' . (int)$row['guid'])), ENT_QUOTES); ?>
                <div style="color:#8f8463;font-size:0.82rem;">GUID <?php echo (int)$row['guid']; ?> | lvl <?php echo (int)($row['level'] ?? 0); ?> | online <?php echo (int)($row['online'] ?? 0); ?></div>
              </td>
              <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#c7bb98;">
                <?php echo htmlspecialchars($row['archetype'], ENT_QUOTES); ?> / <?php echo htmlspecialchars($row['affiliation'], ENT_QUOTES); ?>
                <div style="color:#8f8463;font-size:0.82rem;">
                  leader <?php echo (int)$row['is_leader']; ?> | tourist <?php echo (int)$row['is_tourist']; ?> | pvp lean <?php echo (int)$row['pvp_lean']; ?>
                </div>
              </td>
              <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#c7bb98;">
                <?php echo htmlspecialchars(spp_personality_format_minutes_of_day($row['play_window_start']), ENT_QUOTES); ?>
                -
                <?php echo htmlspecialchars(spp_personality_format_minutes_of_day($row['play_window_end']), ENT_QUOTES); ?>
                <div style="color:#8f8463;font-size:0.82rem;">
                  <?php echo (int)$row['session_minutes']; ?>m | tz <?php echo (int)$row['tz_offset_minutes']; ?>
                </div>
              </td>
              <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#c7bb98;">
                <?php echo htmlspecialchars($row['computed_local_label'], ENT_QUOTES); ?>
                <div style="color:#8f8463;font-size:0.82rem;"><?php echo htmlspecialchars($row['computed_region_label'], ENT_QUOTES); ?></div>
              </td>
              <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);">
                <span style="display:inline-block;padding:4px 8px;border-radius:999px;font-size:0.78rem;background:
                  <?php
                    echo $row['computed_status_class'] === 'ok'
                        ? 'rgba(68,138,88,0.25)'
                        : ($row['computed_status_class'] === 'warn' ? 'rgba(161,108,38,0.25)' : 'rgba(90,90,90,0.2)');
                  ?>;
                  color:
                  <?php
                    echo $row['computed_status_class'] === 'ok'
                        ? '#9de3aa'
                        : ($row['computed_status_class'] === 'warn' ? '#f2c36f' : '#b5b5b5');
                  ?>;">
                  <?php echo htmlspecialchars($row['computed_status_label'], ENT_QUOTES); ?>
                </span>
              </td>
              <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#c7bb98;"><?php echo htmlspecialchars(spp_personality_format_timestamp($row['last_login_at']), ENT_QUOTES); ?></td>
              <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);color:#c7bb98;"><?php echo htmlspecialchars(spp_personality_format_timestamp($row['last_logout_at']), ENT_QUOTES); ?></td>
              <td style="padding:8px 10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right;color:#c7bb98;"><?php echo htmlspecialchars(spp_personality_format_seconds($row['group_time_seconds']), ENT_QUOTES); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  <?php endif; ?>
</div>

<?php builddiv_end(); ?>
