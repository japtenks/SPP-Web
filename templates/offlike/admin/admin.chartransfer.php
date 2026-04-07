<?php builddiv_start(0, 'Character Transfer') ?>
<div class="admin-transfer-shell feature-shell">
  <div class="admin-transfer-card feature-hero">
    <p class="admin-transfer-kicker">Character Tools</p>
    <h2 class="admin-transfer-title">Character Transfer Dry-Run</h2>
    <p class="admin-transfer-copy">Plan cross-realm character transfers without touching live data. This release inspects live schema compatibility, checks source and target character readiness, and blocks unsupported Classic/TBC pairs before any transfer execution exists.</p>
    <div class="admin-transfer-msg admin-transfer-msg--spaced <?php echo htmlspecialchars((string)$statusMessageType, ENT_QUOTES, 'UTF-8'); ?>">
      <strong><?php echo htmlspecialchars((string)$statusLabel, ENT_QUOTES, 'UTF-8'); ?></strong><br>
      <?php echo htmlspecialchars((string)$statusSummary, ENT_QUOTES, 'UTF-8'); ?>
    </div>
  </div>

  <div class="admin-transfer-card feature-panel">
    <form action="<?php echo htmlspecialchars((string)$chartransferActionUrl, ENT_QUOTES, 'UTF-8'); ?>" method="post" class="admin-tool-form-wrap">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$adminChartransferCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="chartransfer_action" value="probe">
      <div class="admin-transfer-form">
        <label for="transfer_source_realm">Source Realm</label>
        <select id="transfer_source_realm" name="source_realm">
          <?php foreach ((array)$realmOptions as $realmOption): ?>
            <option value="<?php echo (int)$realmOption['id']; ?>"<?php echo (int)$realmOption['id'] === (int)$selectedSourceRealmId ? ' selected' : ''; ?>>
              <?php
              echo htmlspecialchars((string)$realmOption['name'], ENT_QUOTES, 'UTF-8');
              echo ' - ' . htmlspecialchars((string)($realmOption['expansion_label'] ?? ''), ENT_QUOTES, 'UTF-8');
              if (empty($realmOption['schema_present'])) {
                  echo ' [schema unavailable]';
              }
              ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="transfer_target_realm">Target Realm</label>
        <select id="transfer_target_realm" name="target_realm">
          <?php foreach ((array)$realmOptions as $realmOption): ?>
            <option value="<?php echo (int)$realmOption['id']; ?>"<?php echo (int)$realmOption['id'] === (int)$selectedTargetRealmId ? ' selected' : ''; ?>>
              <?php
              echo htmlspecialchars((string)$realmOption['name'], ENT_QUOTES, 'UTF-8');
              echo ' - ' . htmlspecialchars((string)($realmOption['expansion_label'] ?? ''), ENT_QUOTES, 'UTF-8');
              if (empty($realmOption['schema_present'])) {
                  echo ' [schema unavailable]';
              }
              ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="transfer_character_name">Character Name</label>
        <input type="text" id="transfer_character_name" name="character_name" maxlength="20" value="<?php echo htmlspecialchars((string)$characterName, ENT_QUOTES, 'UTF-8'); ?>">
      </div>

      <div class="admin-transfer-actions">
        <input type="submit" value="Run Dry-Run Compatibility Probe">
      </div>
    </form>
  </div>

  <?php foreach ((array)$messages as $message): ?>
    <div class="admin-transfer-card feature-panel">
      <div class="admin-transfer-msg <?php echo htmlspecialchars((string)($message['type'] ?? 'error'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars((string)($message['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if (!empty($validationMessages)): ?>
    <div class="admin-transfer-card feature-panel">
      <p class="admin-transfer-kicker">Validation</p>
      <?php foreach ((array)$validationMessages as $validationMessage): ?>
        <div class="admin-transfer-msg error admin-transfer-msg--spaced">
          <?php echo htmlspecialchars((string)$validationMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($submitted): ?>
    <div class="admin-transfer-card feature-panel">
      <p class="admin-transfer-kicker">Probe Summary</p>
      <h2 class="admin-transfer-title">Dry-Run Checks</h2>
      <div class="admin-tool-meta">
        Source: <strong><?php echo htmlspecialchars((string)($sourceRealm['name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8'); ?></strong>
        (<?php echo htmlspecialchars((string)($sourceRealm['chars_schema'] ?? 'unknown'), ENT_QUOTES, 'UTF-8'); ?>)
        |
        Target: <strong><?php echo htmlspecialchars((string)($targetRealm['name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8'); ?></strong>
        (<?php echo htmlspecialchars((string)($targetRealm['chars_schema'] ?? 'unknown'), ENT_QUOTES, 'UTF-8'); ?>)
      </div>
      <ul class="admin-transfer-copy">
        <?php foreach ((array)$checkRows as $checkRow): ?>
          <li>
            <strong><?php echo htmlspecialchars((string)($checkRow['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>:</strong>
            <?php echo htmlspecialchars((string)($checkRow['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            <?php if (!empty($checkRow['detail'])): ?>
              - <?php echo htmlspecialchars((string)$checkRow['detail'], ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="admin-transfer-card feature-panel">
      <p class="admin-transfer-kicker">Core Transfer Surface</p>
      <h2 class="admin-transfer-title">Schema Comparison</h2>
      <ul class="admin-transfer-copy">
        <?php foreach ((array)$coreTableRows as $tableRow): ?>
          <li>
            <strong><?php echo htmlspecialchars((string)($tableRow['table'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>:</strong>
            <?php echo htmlspecialchars((string)($tableRow['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            <?php if (!empty($tableRow['source_only_columns'])): ?>
              | source-only columns: <?php echo htmlspecialchars(implode(', ', (array)$tableRow['source_only_columns']), ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
            <?php if (!empty($tableRow['target_only_columns'])): ?>
              | target-only columns: <?php echo htmlspecialchars(implode(', ', (array)$tableRow['target_only_columns']), ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
            <?php if (empty($tableRow['source_present']) || empty($tableRow['target_present'])): ?>
              | missing on <?php echo empty($tableRow['source_present']) && empty($tableRow['target_present']) ? 'both sides' : (empty($tableRow['source_present']) ? 'source' : 'target'); ?>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <?php if (!empty($deferredTableRows)): ?>
      <div class="admin-transfer-card feature-panel">
        <p class="admin-transfer-kicker">Deferred Expansion Tables</p>
        <h2 class="admin-transfer-title">Unsupported Side Tables</h2>
        <p class="admin-transfer-copy">These expansion-specific tables are detected and reported on purpose. They remain outside v1 support and currently block any future real move action for this source/target pair.</p>
        <ul class="admin-transfer-copy">
          <?php foreach ((array)$deferredTableRows as $tableRow): ?>
            <li>
              <strong><?php echo htmlspecialchars((string)($tableRow['table'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>:</strong>
              <?php echo htmlspecialchars((string)($tableRow['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
              <?php if (!empty($tableRow['source_only_columns'])): ?>
                | source-only columns: <?php echo htmlspecialchars(implode(', ', (array)$tableRow['source_only_columns']), ENT_QUOTES, 'UTF-8'); ?>
              <?php endif; ?>
              <?php if (!empty($tableRow['target_only_columns'])): ?>
                | target-only columns: <?php echo htmlspecialchars(implode(', ', (array)$tableRow['target_only_columns']), ENT_QUOTES, 'UTF-8'); ?>
              <?php endif; ?>
              <?php if (empty($tableRow['source_present']) || empty($tableRow['target_present'])): ?>
                | missing on <?php echo empty($tableRow['source_present']) && empty($tableRow['target_present']) ? 'both sides' : (empty($tableRow['source_present']) ? 'source' : 'target'); ?>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php builddiv_end() ?>
