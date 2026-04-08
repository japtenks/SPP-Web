<?php builddiv_start(1, 'Admin Panel') ?>
<div class="admin-home feature-shell">
  <div class="admin-home__intro feature-hero">
    <p class="admin-home__eyebrow feature-eyebrow"><?php echo htmlspecialchars((string)($intro['eyebrow'] ?? 'Control Center')); ?></p>
    <h2 class="admin-home__title"><?php echo htmlspecialchars((string)($intro['title'] ?? 'MangosWeb Enhanced Admin')); ?></h2>
    <p class="admin-home__body feature-copy"><?php echo htmlspecialchars((string)($intro['body'] ?? '')); ?></p>
    <?php if (!empty($launcher_status)) { ?>
    <div class="admin-home__launcher-status" style="margin-top:16px;padding:12px 16px;border:1px solid rgba(255,191,73,0.35);border-radius:12px;background:rgba(9,18,37,0.7);display:inline-block;">
      <strong><?php echo htmlspecialchars((string)($launcher_status['label'] ?? 'Launcher Runtime')); ?>:</strong>
      <span><?php echo htmlspecialchars('v.' . (string)($launcher_status['version'] ?? 'unknown')); ?></span>
      <span style="margin-left:12px;opacity:0.9;"><?php echo htmlspecialchars((string)($launcher_status['git'] ?? 'unknown@unknown')); ?></span>
    </div>
    <?php } ?>
  </div>

  <div class="admin-home__grid">
    <?php foreach ((array)($sections ?? array()) as $section) { ?>
    <section class="admin-home__card feature-panel">
      <h3><?php echo htmlspecialchars((string)($section['title'] ?? '')); ?></h3>
      <p><?php echo htmlspecialchars((string)($section['description'] ?? '')); ?></p>
      <ul class="admin-home__links">
        <?php foreach ((array)($section['links'] ?? array()) as $link) { ?>
        <li><a href="<?php echo htmlspecialchars((string)($link['href'] ?? '#')); ?>"><?php echo htmlspecialchars((string)($link['label'] ?? '')); ?>
          <small><?php echo htmlspecialchars((string)($link['description'] ?? '')); ?></small>
        </a></li>
        <?php } ?>
      </ul>
    </section>
    <?php } ?>
  </div>
</div>
<?php builddiv_end() ?>
