<?php
builddiv_start(1, 'Population Director');

$actionEndpoints = is_array($action_endpoints ?? null) ? $action_endpoints : array();
$overrideEndpoint = (string)($actionEndpoints['override'] ?? 'index.php?n=admin&sub=populationdirector');
$clearOverrideEndpoint = (string)($actionEndpoints['clear_override'] ?? $overrideEndpoint);
$readEndpoint = (string)($actionEndpoints['read'] ?? 'index.php?n=admin&sub=populationdirector');
$refreshEndpoint = (string)($actionEndpoints['refresh'] ?? $readEndpoint);

$currentTarget = is_array($current_target ?? null) ? $current_target : array();
$observedOnline = is_array($observed_online ?? null) ? $observed_online : array();
$activeBand = is_array($active_band ?? null) ? $active_band : array();
$overrideState = is_array($override_state ?? null) ? $override_state : array();
$targetReason = is_array($target_reason ?? null) ? $target_reason : array();
$overrideForm = is_array($override_form ?? null) ? $override_form : array();
$realmOptions = is_array($realm_options ?? null) ? $realm_options : array();
$selectedRealmId = (int)($selected_realm_id ?? 0);
$overrideBadge = trim((string)($overrideState['value'] ?? ''));
if ($overrideBadge === '') {
    $overrideBadge = !empty($overrideState['active']) ? 'Active' : 'Idle';
}

$cardValue = static function ($row, $fallback = '') {
    if (!is_array($row)) {
        $text = trim((string)$row);
        return $text !== '' ? $text : $fallback;
    }

    $text = trim((string)($row['value'] ?? ''));
    if ($text !== '') {
        return $text;
    }

    $text = trim((string)($row['label'] ?? ''));
    return $text !== '' ? $text : $fallback;
};
?>
<div class="populationdirector-shell feature-shell" data-populationdirector-shell="1">
  <?php if (!empty($notice)): ?>
    <div class="populationdirector-alert feature-panel">
      <strong>Planner update</strong>
      <div><?php echo htmlspecialchars((string)$notice); ?><?php if (!empty($saved_snapshot_id)): ?> Snapshot #<?php echo (int)$saved_snapshot_id; ?>.<?php endif; ?></div>
    </div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="populationdirector-alert populationdirector-alert--error feature-panel">
      <strong>Planner state needs attention</strong>
      <ul>
        <?php foreach ((array)$errors as $error): ?>
          <li><?php echo htmlspecialchars((string)$error); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="populationdirector-hero feature-hero">
    <p class="populationdirector-eyebrow feature-eyebrow"><?php echo htmlspecialchars((string)($intro['eyebrow'] ?? 'Read-First Operator Dashboard')); ?></p>
    <h2 class="populationdirector-title"><?php echo htmlspecialchars((string)($intro['title'] ?? 'Population Director')); ?></h2>
    <p class="populationdirector-body feature-copy"><?php echo htmlspecialchars((string)($intro['body'] ?? '')); ?></p>

    <div class="populationdirector-metadata">
      <div class="populationdirector-pill">
        <span>Current target</span>
        <strong><?php echo htmlspecialchars((string)$cardValue($currentTarget, 'Unavailable')); ?></strong>
        <?php if (!empty($currentTarget['detail'])): ?><small><?php echo htmlspecialchars((string)$currentTarget['detail']); ?></small><?php endif; ?>
      </div>
      <div class="populationdirector-pill">
        <span>Observed online</span>
        <strong><?php echo htmlspecialchars((string)$cardValue($observedOnline, 'Unavailable')); ?></strong>
        <?php if (!empty($observedOnline['detail'])): ?><small><?php echo htmlspecialchars((string)$observedOnline['detail']); ?></small><?php endif; ?>
      </div>
      <div class="populationdirector-pill">
        <span>Active band</span>
        <strong><?php echo htmlspecialchars((string)$cardValue($activeBand, 'Unavailable')); ?></strong>
        <?php if (!empty($activeBand['detail'])): ?><small><?php echo htmlspecialchars((string)$activeBand['detail']); ?></small><?php endif; ?>
      </div>
      <div class="populationdirector-pill<?php echo !empty($overrideState['active']) ? ' is-live' : ''; ?>">
        <span>Override state</span>
        <strong><?php echo htmlspecialchars((string)$overrideBadge); ?></strong>
        <?php if (!empty($overrideState['detail'])): ?><small><?php echo htmlspecialchars((string)$overrideState['detail']); ?></small><?php endif; ?>
      </div>
    </div>

    <div class="populationdirector-endpoints feature-panel">
      <div class="populationdirector-endpoints__header">
        <strong>Action wiring</strong>
        <span class="populationdirector-muted">The page reads first. Copy the endpoints if you need to inspect or replay the current control flow.</span>
      </div>
      <ul class="populationdirector-endpoint-list">
        <li>
          <span>Read</span>
          <code id="populationdirector-endpoint-read"><?php echo htmlspecialchars($readEndpoint); ?></code>
          <button class="populationdirector-button populationdirector-button--ghost" type="button" data-populationdirector-copy-endpoint="populationdirector-endpoint-read">Copy</button>
        </li>
        <li>
          <span>Refresh</span>
          <code id="populationdirector-endpoint-refresh"><?php echo htmlspecialchars($refreshEndpoint); ?></code>
          <button class="populationdirector-button populationdirector-button--ghost" type="button" data-populationdirector-copy-endpoint="populationdirector-endpoint-refresh">Copy</button>
        </li>
        <li>
          <span>Override</span>
          <code id="populationdirector-endpoint-override"><?php echo htmlspecialchars($overrideEndpoint); ?></code>
          <button class="populationdirector-button populationdirector-button--ghost" type="button" data-populationdirector-copy-endpoint="populationdirector-endpoint-override">Copy</button>
        </li>
        <li>
          <span>Clear override</span>
          <code id="populationdirector-endpoint-clear"><?php echo htmlspecialchars($clearOverrideEndpoint); ?></code>
          <button class="populationdirector-button populationdirector-button--ghost" type="button" data-populationdirector-copy-endpoint="populationdirector-endpoint-clear">Copy</button>
        </li>
      </ul>
    </div>

    <?php if (!empty($realmOptions)): ?>
      <form class="populationdirector-realm-form" method="get" action="<?php echo htmlspecialchars($readEndpoint); ?>">
        <input type="hidden" name="n" value="admin">
        <input type="hidden" name="sub" value="populationdirector">
        <label class="populationdirector-field">
          <span>Realm</span>
          <select name="realm">
            <?php foreach ($realmOptions as $realmOption): ?>
              <?php $realmOptionId = (int)($realmOption['id'] ?? 0); ?>
              <option value="<?php echo $realmOptionId; ?>"<?php echo $realmOptionId === $selectedRealmId ? ' selected' : ''; ?>>
                <?php echo htmlspecialchars((string)($realmOption['label'] ?? ('Realm ' . $realmOptionId))); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <div class="populationdirector-actions">
          <button class="populationdirector-button populationdirector-button--ghost" type="submit">Switch Realm</button>
          <button class="populationdirector-button populationdirector-button--ghost" type="submit" formaction="<?php echo htmlspecialchars($refreshEndpoint); ?>" formmethod="post" name="populationdirector_action" value="refresh_snapshot">Refresh Snapshot</button>
        </div>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$csrf_token, ENT_QUOTES); ?>">
      </form>
    <?php endif; ?>
  </div>

  <div class="populationdirector-grid populationdirector-grid--summary">
    <section class="populationdirector-card feature-panel">
      <h3>Target Reason</h3>
      <?php if (!empty($targetReason['body'])): ?>
        <p class="populationdirector-note"><?php echo htmlspecialchars((string)$targetReason['body']); ?></p>
      <?php else: ?>
        <p class="populationdirector-empty">No target rationale was supplied yet.</p>
      <?php endif; ?>
    </section>

    <section class="populationdirector-card feature-panel">
      <h3>Current Override</h3>
      <?php if (!empty($overrideState['active']) || trim((string)($overrideState['value'] ?? '')) !== ''): ?>
        <p class="populationdirector-note"><?php echo htmlspecialchars((string)$overrideBadge); ?></p>
        <?php if (!empty($overrideState['mode'])): ?><p class="populationdirector-muted">Mode: <code><?php echo htmlspecialchars((string)$overrideState['mode']); ?></code></p><?php endif; ?>
        <?php if (!empty($overrideState['expires_at'])): ?><p class="populationdirector-muted">Expires: <?php echo htmlspecialchars((string)$overrideState['expires_at']); ?></p><?php endif; ?>
        <?php if (!empty($overrideState['updated_at'])): ?><p class="populationdirector-muted">Updated: <?php echo htmlspecialchars((string)$overrideState['updated_at']); ?></p><?php endif; ?>
      <?php else: ?>
        <p class="populationdirector-empty">No temporary override is active right now.</p>
      <?php endif; ?>
    </section>
  </div>

  <div class="populationdirector-grid populationdirector-grid--content">
    <section class="populationdirector-card feature-panel">
      <h3>Recommendations In</h3>
      <?php if (!empty($recommendations_in)): ?>
        <div class="populationdirector-card-list">
          <?php foreach ((array)$recommendations_in as $recommendation): ?>
            <article class="populationdirector-rec">
              <?php if (!empty($recommendation['title'])): ?><strong><?php echo htmlspecialchars((string)$recommendation['title']); ?></strong><?php endif; ?>
              <?php if (!empty($recommendation['body'])): ?><p><?php echo htmlspecialchars((string)$recommendation['body']); ?></p><?php endif; ?>
              <?php if (!empty($recommendation['meta'])): ?><span><?php echo htmlspecialchars((string)$recommendation['meta']); ?></span><?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="populationdirector-empty">No inbound recommendations were supplied.</p>
      <?php endif; ?>
    </section>

    <section class="populationdirector-card feature-panel">
      <h3>Recommendations Out</h3>
      <?php if (!empty($recommendations_out)): ?>
        <div class="populationdirector-card-list">
          <?php foreach ((array)$recommendations_out as $recommendation): ?>
            <article class="populationdirector-rec">
              <?php if (!empty($recommendation['title'])): ?><strong><?php echo htmlspecialchars((string)$recommendation['title']); ?></strong><?php endif; ?>
              <?php if (!empty($recommendation['body'])): ?><p><?php echo htmlspecialchars((string)$recommendation['body']); ?></p><?php endif; ?>
              <?php if (!empty($recommendation['meta'])): ?><span><?php echo htmlspecialchars((string)$recommendation['meta']); ?></span><?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="populationdirector-empty">No outbound recommendations were supplied.</p>
      <?php endif; ?>
    </section>
  </div>

  <div class="populationdirector-grid populationdirector-grid--content">
    <section class="populationdirector-card feature-panel">
      <h3>Explanation Snippets</h3>
      <?php if (!empty($explanation_snippets)): ?>
        <div class="populationdirector-snippet-grid">
          <?php foreach ((array)$explanation_snippets as $snippet): ?>
            <article class="populationdirector-snippet">
              <?php if (!empty($snippet['title'])): ?><strong><?php echo htmlspecialchars((string)$snippet['title']); ?></strong><?php endif; ?>
              <?php if (!empty($snippet['meta'])): ?><span><?php echo htmlspecialchars((string)$snippet['meta']); ?></span><?php endif; ?>
              <?php if (!empty($snippet['body'])): ?><p><?php echo htmlspecialchars((string)$snippet['body']); ?></p><?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="populationdirector-empty">No explanation snippets were supplied for this snapshot.</p>
      <?php endif; ?>
    </section>

    <section class="populationdirector-card feature-panel">
      <h3>Temporary Override</h3>
      <p class="populationdirector-note">Keep this surface temporary. Use it to steer the live target for a limited time, then clear it once the system returns to the normal band.</p>
      <form class="populationdirector-form" method="post" action="<?php echo htmlspecialchars($overrideEndpoint); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$csrf_token, ENT_QUOTES); ?>">
        <input type="hidden" name="realm_id" value="<?php echo $selectedRealmId; ?>">
        <div class="populationdirector-form-grid">
          <label class="populationdirector-field">
            <span>Override target</span>
            <input type="text" name="override_target" value="<?php echo htmlspecialchars((string)($overrideForm['target'] ?? ''), ENT_QUOTES); ?>" placeholder="Current target or a temporary replacement">
          </label>
          <label class="populationdirector-field">
            <span>Band</span>
            <input type="text" name="override_band" value="<?php echo htmlspecialchars((string)($overrideForm['band'] ?? ''), ENT_QUOTES); ?>" placeholder="Band name or label">
          </label>
          <label class="populationdirector-field">
            <span>Pressure override</span>
            <input type="number" min="-5" max="5" step="0.1" name="pressure_override" value="<?php echo htmlspecialchars((string)($overrideForm['pressure'] ?? ''), ENT_QUOTES); ?>" placeholder="0.0">
          </label>
          <label class="populationdirector-field">
            <span>Duration minutes</span>
            <input type="number" min="5" step="5" name="override_minutes" value="<?php echo (int)($overrideForm['minutes'] ?? 60); ?>" data-populationdirector-minutes>
          </label>
        </div>
        <label class="populationdirector-field populationdirector-field--wide">
          <span>Reason</span>
          <textarea name="override_reason" rows="4" placeholder="Why this temporary override is being applied"><?php echo htmlspecialchars((string)($overrideForm['reason'] ?? ''), ENT_QUOTES); ?></textarea>
        </label>
        <div class="populationdirector-quick-actions">
          <span>Quick duration</span>
          <button class="populationdirector-button populationdirector-button--ghost" type="button" data-populationdirector-set-minutes="15">15m</button>
          <button class="populationdirector-button populationdirector-button--ghost" type="button" data-populationdirector-set-minutes="30">30m</button>
          <button class="populationdirector-button populationdirector-button--ghost" type="button" data-populationdirector-set-minutes="60">60m</button>
          <button class="populationdirector-button populationdirector-button--ghost" type="button" data-populationdirector-set-minutes="240">4h</button>
        </div>
        <div class="populationdirector-actions">
          <button class="populationdirector-button" type="submit" name="populationdirector_action" value="apply_temporary_override">Apply Temporary Override</button>
          <button class="populationdirector-button populationdirector-button--danger" type="submit" name="populationdirector_action" value="clear_temporary_override" formaction="<?php echo htmlspecialchars($clearOverrideEndpoint); ?>">Clear Override</button>
        </div>
      </form>
    </section>
  </div>

  <section class="populationdirector-card feature-panel">
    <h3>History</h3>
    <?php if (!empty($history)): ?>
      <div class="populationdirector-table-wrap">
        <table class="populationdirector-table">
          <thead>
            <tr>
              <th>When</th>
              <th>Action</th>
              <th>Target</th>
              <th>Band</th>
              <th>Reason</th>
              <th>Details</th>
              <th>Actor</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ((array)$history as $row): ?>
              <tr>
                <td><?php echo htmlspecialchars((string)($row['timestamp'] ?? ''), ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars((string)($row['action'] ?? ''), ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars((string)($row['target'] ?? ''), ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars((string)($row['band'] ?? ''), ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars((string)($row['reason'] ?? ''), ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars((string)($row['detail'] ?? ''), ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars((string)($row['actor'] ?? ''), ENT_QUOTES); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="populationdirector-empty">No override history was supplied yet.</p>
    <?php endif; ?>
  </section>
</div>
<?php builddiv_end(); ?>
