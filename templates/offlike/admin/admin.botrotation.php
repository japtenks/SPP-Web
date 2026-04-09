<?php
if (!function_exists('rotFormatSeconds')) {
    function rotFormatSeconds($seconds)
    {
        if ($seconds === null || $seconds === '' || !is_numeric($seconds) || $seconds <= 0) {
            return 'N/A';
        }
        $seconds = (int)round((float)$seconds);
        if ($seconds >= 86400) {
            return round($seconds / 86400, 1) . 'd';
        }
        if ($seconds >= 3600) {
            return round($seconds / 3600, 1) . 'h';
        }
        if ($seconds >= 60) {
            return round($seconds / 60, 1) . 'm';
        }
        return $seconds . 's';
    }
}
if (!function_exists('rotFormatSnapshotTime')) {
    function rotFormatSnapshotTime($timestamp)
    {
        $timestamp = trim((string)$timestamp);
        if ($timestamp === '') {
            return 'N/A';
        }

        try {
            $utc = new DateTimeZone('UTC');
            $local = new DateTimeZone((string)date_default_timezone_get());
            $dt = new DateTime($timestamp, $utc);
            $dt->setTimezone($local);
            return $dt->format('M j H:i');
        } catch (Exception $e) {
            return $timestamp;
        }
    }
}

$rotationData = is_array($rotationData ?? null) ? $rotationData : array();
$rotationConfig = is_array($rotationConfig ?? null) ? $rotationConfig : array();
$latestHistory = is_array($latestHistory ?? null) ? $latestHistory : array();
$uptimeSummary = is_array($uptimeSummary ?? null) ? $uptimeSummary : array();
$cleanHistory = is_array($cleanHistory ?? null) ? $cleanHistory : array();
$rotationCommandAvailability = is_array($rotationCommandAvailability ?? null) ? $rotationCommandAvailability : array();
$rotationCommands = is_array($rotationCommands ?? null) ? $rotationCommands : array();

$highestLevelUrl = !empty($topBotData['name']) ? 'index.php?n=server&sub=character&realm=' . (int)$realmId . '&character=' . rawurlencode((string)$topBotData['name']) : '';
$longestOnlineUrl = !empty($longestOnlineBot['bot_name']) ? 'index.php?n=server&sub=character&realm=' . (int)$realmId . '&character=' . rawurlencode((string)$longestOnlineBot['bot_name']) : '';
$longestOfflineUrl = !empty($longestOfflineBot['bot_name']) ? 'index.php?n=server&sub=character&realm=' . (int)$realmId . '&character=' . rawurlencode((string)$longestOfflineBot['bot_name']) : '';
?>
<?php builddiv_start(1, 'Bot Rotation Health', 1); ?>
<?php builddiv_end(); ?>

<div class="rot-shell feature-shell" data-is-windows-host="<?php echo !empty($isWindowsHost) ? '1' : '0'; ?>">
  <div class="rot-panel feature-hero" id="rot-overview-panel">
    <div class="rot-title">Rotation Overview</div>
    <div class="rot-context">Selected realm: <strong><?php echo htmlspecialchars((string)$realmName, ENT_QUOTES, 'UTF-8'); ?></strong> (ID <?php echo (int)$realmId; ?>)</div>
    <?php if (!empty($rotationError)): ?>
      <div class="rot-error">Query error: <?php echo htmlspecialchars((string)$rotationError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php elseif (empty($rotationData) || (int)($rotationData['total_bots'] ?? 0) === 0): ?>
      <div class="rot-error">No bot rotation data was found for this realm. Confirm random bot accounts and rotation tables are present.</div>
    <?php else: ?>
      <div class="rot-stats">
        <div class="rot-stat highlight"><div class="val"><?php echo htmlspecialchars((string)($rotationData['pct_online_rotating'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?>%</div><div class="lbl">Live Rotation</div></div>
        <div class="rot-stat good"><div class="val"><?php echo (int)($rotationData['rotating_active'] ?? 0); ?></div><div class="lbl">Online + Progressing</div></div>
        <div class="rot-stat muted"><div class="val"><?php echo (int)($rotationData['online_idle'] ?? 0); ?></div><div class="lbl">Online Idle</div></div>
        <div class="rot-stat info"><div class="val"><?php echo (int)($rotationData['cycled_off_progressed'] ?? 0); ?></div><div class="lbl">Cycled Off</div></div>
        <div class="rot-stat muted"><div class="val"><?php echo (int)($rotationData['never_progressed'] ?? 0); ?></div><div class="lbl">Never Progressed</div></div>
        <div class="rot-stat info"><div class="val"><?php echo (int)($rotationData['total_online'] ?? 0); ?> / <?php echo (int)($rotationData['total_bots'] ?? 0); ?></div><div class="lbl">Online / Total</div></div>
        <div class="rot-stat good"><div class="val"><?php echo htmlspecialchars((string)($rotationData['avg_level_rotating'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></div><div class="lbl">Avg Level</div></div>
        <a class="rot-stat rot-stat-link good" href="<?php echo htmlspecialchars($highestLevelUrl !== '' ? $highestLevelUrl : '#', ENT_QUOTES, 'UTF-8'); ?>">
          <div class="val"><?php echo htmlspecialchars((string)($rotationData['highest_level'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="lbl">Highest Level</div>
          <div class="meta"><?php echo !empty($topBotData['name']) ? htmlspecialchars((string)$topBotData['name'], ENT_QUOTES, 'UTF-8') : 'No top bot found'; ?></div>
        </a>
        <div class="rot-stat info"><div class="val"><?php echo htmlspecialchars((string)$totalServerUptime, ENT_QUOTES, 'UTF-8'); ?></div><div class="lbl">Total Uptime</div></div>
        <div class="rot-stat info"><div class="val"><?php echo $uptimeSummary['stable_avg_uptime_hours'] !== null ? htmlspecialchars((string)$uptimeSummary['stable_avg_uptime_hours'], ENT_QUOTES, 'UTF-8') . 'h' : 'N/A'; ?></div><div class="lbl">Stable Avg Uptime</div></div>
        <div class="rot-stat info"><div class="val"><?php echo rotFormatSeconds($uptimeSummary['median_uptime_sec'] ?? null); ?></div><div class="lbl">Median Uptime</div></div>
        <div class="rot-stat <?php echo (int)($uptimeSummary['short_restarts'] ?? 0) > 0 ? 'warn' : 'good'; ?>"><div class="val"><?php echo (int)($uptimeSummary['short_restarts'] ?? 0); ?></div><div class="lbl">Short Restarts (7d)</div></div>
      </div>
    <?php endif; ?>
  </div>

  <div class="rot-panel feature-panel rot-toolbox">
    <div class="rot-toolbox-title">Config And Commands</div>
    <div class="rot-toolbox-note">This page stays read-first in `spp-web`. Use it to inspect health and copy safe/manual commands, while reviewed destructive work stays in the appropriate maintenance surface.</div>
    <div class="rot-stats">
      <div class="rot-stat info"><div class="val"><?php echo rotFormatSeconds($rotationConfig['avg_in_world_sec'] ?? ($latestHistory['cfg_avg_in_world_sec'] ?? null)); ?></div><div class="lbl">Avg Time In World</div></div>
      <div class="rot-stat info"><div class="val"><?php echo rotFormatSeconds($rotationConfig['avg_offline_sec'] ?? ($latestHistory['cfg_avg_offline_sec'] ?? null)); ?></div><div class="lbl">Avg Time Offline</div></div>
      <div class="rot-stat info"><div class="val"><?php echo htmlspecialchars((string)($rotationConfig['expected_online_pct'] ?? ($latestHistory['cfg_expected_online_pct'] ?? 'N/A')), ENT_QUOTES, 'UTF-8'); ?><?php echo isset($rotationConfig['expected_online_pct']) || isset($latestHistory['cfg_expected_online_pct']) ? '%' : ''; ?></div><div class="lbl">Expected Online Share</div></div>
      <div class="rot-stat info"><div class="val"><?php echo rotFormatSeconds($cleanHistory['avg_online_sec'] ?? null); ?></div><div class="lbl">Clean Avg Online</div></div>
      <div class="rot-stat info"><div class="val"><?php echo rotFormatSeconds($cleanHistory['avg_offline_sec'] ?? null); ?></div><div class="lbl">Clean Avg Offline</div></div>
      <a class="rot-stat rot-stat-link info" href="<?php echo htmlspecialchars($longestOnlineUrl !== '' ? $longestOnlineUrl : '#', ENT_QUOTES, 'UTF-8'); ?>"><div class="val"><?php echo rotFormatSeconds($liveOnlineMax ?? null); ?></div><div class="lbl">Longest Online Now</div><div class="meta"><?php echo !empty($longestOnlineBot['bot_name']) ? htmlspecialchars((string)$longestOnlineBot['bot_name'], ENT_QUOTES, 'UTF-8') : 'No active bot'; ?></div></a>
      <a class="rot-stat rot-stat-link info" href="<?php echo htmlspecialchars($longestOfflineUrl !== '' ? $longestOfflineUrl : '#', ENT_QUOTES, 'UTF-8'); ?>"><div class="val"><?php echo rotFormatSeconds($rotationData['current_max_offline_sec'] ?? null); ?></div><div class="lbl">Longest Offline Now</div><div class="meta"><?php echo !empty($longestOfflineBot['bot_name']) ? htmlspecialchars((string)$longestOfflineBot['bot_name'], ENT_QUOTES, 'UTF-8') : 'No offline bot'; ?></div></a>
      <div class="rot-stat info"><div class="val"><?php echo $restartsToday !== null ? (int)$restartsToday : 'N/A'; ?></div><div class="lbl">Restarts Today</div></div>
    </div>

    <div class="rot-command" id="rot-reset-dry"><?php echo htmlspecialchars((string)($rotationCommands['rotation_reset_dry_run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="rot-command" id="rot-reset-run"><?php echo htmlspecialchars((string)($rotationCommands['rotation_reset_run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="rot-actions">
      <button class="btn secondary" type="button" data-rotation-copy-target="rot-reset-dry">Copy Rotation Dry Run</button>
      <button class="btn secondary" type="button" data-rotation-copy-target="rot-reset-run">Copy Rotation Run</button>
    </div>
    <?php if (empty($rotationCommandAvailability['rotation_reset'])): ?>
      <div class="rot-help rot-help--warning">The `tools/reset_bot_rotation_realm.php` helper is not bundled in this checkout. The command above shows the expected script path if you add the helper locally.</div>
    <?php endif; ?>

    <?php if (!empty($rotationCommandAvailability['linux_logging'])): ?>
      <div class="rot-command" id="rot-pause-logging"><?php echo htmlspecialchars((string)($rotationCommands['pause_logging'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="rot-command" id="rot-resume-logging"><?php echo htmlspecialchars((string)($rotationCommands['resume_logging'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="rot-actions">
        <button class="btn secondary" type="button" data-rotation-copy-target="rot-pause-logging">Copy Pause Logging</button>
        <button class="btn secondary" type="button" data-rotation-copy-target="rot-resume-logging">Copy Resume Logging</button>
      </div>
    <?php else: ?>
      <div class="rot-help">Linux cron and `systemctl` rotation-log controls are hidden on Windows hosts. Use PowerShell-safe manual PHP commands or your platform’s normal scheduling tools instead.</div>
    <?php endif; ?>
  </div>

  <div class="rot-panel feature-panel">
    <div class="rot-title">Recent History</div>
    <?php if (!empty($historyRows)): ?>
      <table class="rot-table">
        <thead>
          <tr>
            <th>Snapshot</th>
            <th>Uptime</th>
            <th>Live Rotation</th>
            <th>Ever Rotated</th>
            <th>Online</th>
            <th>Rotating</th>
            <th>Avg Level</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ((array)$historyRows as $historyRow): ?>
            <tr>
              <td><?php echo htmlspecialchars(rotFormatSnapshotTime($historyRow['snapshot_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars(rotFormatSeconds($historyRow['server_uptime_sec'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars((string)($historyRow['pct_online_rotating'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?>%</td>
              <td><?php echo htmlspecialchars((string)($historyRow['pct_ever_rotated'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?>%</td>
              <td><?php echo (int)($historyRow['total_online'] ?? 0); ?></td>
              <td><?php echo (int)($historyRow['rotating_active'] ?? 0); ?></td>
              <td><?php echo htmlspecialchars((string)($historyRow['avg_level_rotating'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="rot-help">No bot rotation history rows were available for this realm yet.</div>
    <?php endif; ?>
  </div>
</div>
