<?php builddiv_start(1, 'Operations'); ?>
<div class="admin-home feature-shell">
  <div class="admin-home__intro feature-hero">
    <p class="admin-home__eyebrow feature-eyebrow"><?php echo htmlspecialchars((string)($operationsIntro['eyebrow'] ?? 'Operations')); ?></p>
    <h2 class="admin-home__title"><?php echo htmlspecialchars((string)($operationsIntro['title'] ?? 'Operations Catalog')); ?></h2>
    <p class="playerbots-success is-warning">Warning: not all Operations workflows have been fully tested yet. Review previews carefully and use extra caution before running destructive maintenance.</p>
    <p class="admin-home__body feature-copy"><?php echo htmlspecialchars((string)($operationsIntro['body'] ?? '')); ?></p>
    <?php if (!empty($operationsNotice)): ?>
      <p class="playerbots-success<?php echo !empty($queuedOperationJobId) ? '' : ' is-warning'; ?>"><?php echo htmlspecialchars((string)$operationsNotice); ?></p>
    <?php endif; ?>
  </div>

  <div class="admin-home__grid">
    <?php foreach ((array)($operationGroups ?? array()) as $group): ?>
      <?php if (empty($group['items'])) { continue; } ?>
      <section class="admin-home__card feature-panel" id="<?php echo htmlspecialchars(str_replace('_', '-', (string)($group['id'] ?? 'group'))); ?>">
        <h3><?php echo htmlspecialchars((string)($group['label'] ?? 'Operations')); ?></h3>
        <?php if (!empty($group['description'])): ?>
          <p class="realm-admin__muted"><?php echo htmlspecialchars((string)$group['description']); ?></p>
        <?php endif; ?>
        <ul class="admin-home__links">
          <?php foreach ((array)($group['items'] ?? array()) as $operation): ?>
            <?php
              $operationId = (string)($operation['id'] ?? '');
              $isSelected = $operationId !== '' && $operationId === (string)($selectedOperation['id'] ?? '');
              $metaParts = array(
                  strtoupper((string)($operation['risk_level'] ?? 'safe')),
                  (string)($operation['ownership_scope'] ?? 'realm-specific'),
                  (string)($operation['delivery_mode'] ?? 'SQL package'),
                  spp_admin_operations_status_label($operation),
              );
            ?>
            <li>
              <a href="index.php?n=admin&amp;sub=operations&amp;operation=<?php echo urlencode($operationId); ?>"<?php echo $isSelected ? ' aria-current="page"' : ''; ?>>
                <?php echo htmlspecialchars((string)($operation['label'] ?? 'Operation')); ?>
                <small><?php echo htmlspecialchars((string)($operation['summary'] ?? '')); ?></small>
              </a>
              <div class="realm-admin__muted"><?php echo htmlspecialchars(implode(' / ', $metaParts)); ?></div>
              <?php if (!empty($operation['is_link_only'])): ?>
                <div class="realm-admin__muted">Open native tool instead of queueing a job.</div>
              <?php elseif (!empty($operation['is_deferred'])): ?>
                <div class="realm-admin__muted">Planned-later workflow. Visible for taxonomy completeness only.</div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
  </div>

  <?php if (!empty($selectedOperation)): ?>
    <?php
      $selectedScope = (string)($selectedOperation['scope'] ?? 'single_realm');
      $selectedRequiredInputs = (array)($selectedOperation['required_inputs'] ?? array());
      $scopeProfiles = (array)($selectedOperation['scope_profiles'] ?? array());
    ?>
    <section class="admin-home__card feature-panel">
      <h3><?php echo htmlspecialchars((string)($selectedOperation['label'] ?? 'Selected Operation')); ?></h3>
      <p><?php echo htmlspecialchars((string)($selectedOperation['summary'] ?? '')); ?></p>
      <p class="realm-admin__muted">
        Risk: <?php echo htmlspecialchars((string)($selectedOperation['risk_level'] ?? 'safe')); ?> |
        Ownership: <?php echo htmlspecialchars((string)($selectedOperation['ownership_scope'] ?? 'realm-specific')); ?> |
        Delivery: <?php echo htmlspecialchars((string)($selectedOperation['delivery_mode'] ?? 'SQL package')); ?> |
        V1: <?php echo htmlspecialchars(spp_admin_operations_status_label((array)$selectedOperation)); ?>
      </p>

      <?php if (!empty($selectedOperation['is_link_only'])): ?>
        <p><a href="<?php echo htmlspecialchars((string)$selectedOperation['native_href']); ?>">Open native tool</a></p>
      <?php elseif (!empty($selectedOperation['is_deferred'])): ?>
        <div class="playerbots-preview is-gap-top">
          <strong>Deferred</strong>
          <pre><?php echo htmlspecialchars((string)($selectedOperationPreview['preview_text'] ?? '')); ?></pre>
        </div>
      <?php else: ?>
        <form method="post" action="index.php?n=admin&amp;sub=operations&amp;operation=<?php echo urlencode((string)($selectedOperation['id'] ?? '')); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$admin_operations_csrf_token, ENT_QUOTES); ?>">
          <input type="hidden" name="operations_action" value="queue_job">
          <input type="hidden" name="operation_id" value="<?php echo htmlspecialchars((string)($selectedOperation['id'] ?? ''), ENT_QUOTES); ?>">
          <div class="realm-admin__form-grid">
            <?php if ($selectedScope === 'single_realm'): ?>
              <div class="realm-admin__field">
                <label>Target Realm</label>
                <select name="realm_id">
                  <?php foreach ((array)($operationRealmOptions ?? array()) as $realmOption): ?>
                    <option value="<?php echo (int)($realmOption['id'] ?? 0); ?>"><?php echo htmlspecialchars((string)($realmOption['label'] ?? '')); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>
            <?php if ($selectedScope === 'cross_realm'): ?>
              <div class="realm-admin__field">
                <label>Source Realm</label>
                <select name="source_realm_id">
                  <?php foreach ((array)($operationRealmOptions ?? array()) as $realmOption): ?>
                    <option value="<?php echo (int)($realmOption['id'] ?? 0); ?>"><?php echo htmlspecialchars((string)($realmOption['label'] ?? '')); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="realm-admin__field">
                <label>Target Realm</label>
                <select name="target_realm_id">
                  <?php foreach ((array)($operationRealmOptions ?? array()) as $realmOption): ?>
                    <option value="<?php echo (int)($realmOption['id'] ?? 0); ?>"><?php echo htmlspecialchars((string)($realmOption['label'] ?? '')); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>
            <?php if (!empty($scopeProfiles)): ?>
              <div class="realm-admin__field">
                <label>Scope</label>
                <select name="scope_profile">
                  <?php foreach ($scopeProfiles as $scopeProfileId => $scopeProfileLabel): ?>
                    <option value="<?php echo htmlspecialchars((string)$scopeProfileId, ENT_QUOTES); ?>"><?php echo htmlspecialchars((string)$scopeProfileId . ' - ' . (string)$scopeProfileLabel); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>
            <?php if (in_array('value', $selectedRequiredInputs, true)): ?>
              <div class="realm-admin__field">
                <label>Value</label>
                <input type="text" name="value" value="">
              </div>
            <?php endif; ?>
            <?php if ((string)($selectedOperation['risk_level'] ?? '') === 'destructive'): ?>
              <div class="realm-admin__field">
                <label>Confirmation Phrase</label>
                <input type="text" name="confirmation_phrase" placeholder="<?php echo htmlspecialchars((string)($selectedOperationPreview['confirmation_phrase'] ?? '')); ?>">
              </div>
            <?php endif; ?>
          </div>
          <div class="realm-admin__actions">
            <input class="realm-admin__button" type="submit" value="<?php echo htmlspecialchars((string)($selectedOperation['ui_cta'] ?? 'Queue job')); ?>">
          </div>
        </form>
        <div class="playerbots-preview is-gap-top">
          <strong>Generated Preview</strong>
          <pre><?php echo htmlspecialchars((string)($selectedOperationPreview['preview_text'] ?? '')); ?></pre>
          <?php if (!empty($selectedOperationPreview['confirmation_phrase']) && (string)($selectedOperation['risk_level'] ?? '') === 'destructive'): ?>
            <p class="realm-admin__muted">Destructive confirmation phrase: <code><?php echo htmlspecialchars((string)$selectedOperationPreview['confirmation_phrase']); ?></code></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <section class="admin-home__card feature-panel">
    <h3>Recent Jobs</h3>
    <?php if (!empty($operationJobHistory)): ?>
      <table class="realm-admin__table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Operation</th>
            <th>Status</th>
            <th>Risk</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ((array)$operationJobHistory as $job): ?>
            <tr>
              <td><a href="index.php?n=admin&amp;sub=operations&amp;job=<?php echo (int)($job['id'] ?? 0); ?>"><?php echo (int)($job['id'] ?? 0); ?></a></td>
              <td><?php echo htmlspecialchars((string)($job['operation_label'] ?? '')); ?></td>
              <td><?php echo htmlspecialchars((string)($job['status'] ?? 'queued')); ?></td>
              <td><?php echo htmlspecialchars((string)($job['risk_level'] ?? 'safe')); ?></td>
              <td><?php echo htmlspecialchars((string)($job['created_at'] ?? '')); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No queued jobs yet.</p>
    <?php endif; ?>

    <?php if (!empty($operationJobDetail)): ?>
      <div class="playerbots-preview is-gap-top">
        <strong>Job #<?php echo (int)($operationJobDetail['id'] ?? 0); ?></strong>
        <pre><?php echo htmlspecialchars((string)($operationJobDetail['preview_text'] ?? '')); ?></pre>
        <pre><?php echo htmlspecialchars((string)($operationJobDetail['verification_summary'] ?? '')); ?></pre>
      </div>
    <?php endif; ?>
  </section>
</div>
<?php builddiv_end(); ?>
