<?php
builddiv_start(1, 'Downloads', 0);
?>
<div class="downloads-shell feature-shell">
  <section class="feature-hero">
    <p class="feature-eyebrow">Hosted Files</p>
    <h2 class="feature-title">Downloads and client extras in one place.</h2>
    <p class="feature-copy">This page is for hosted client files. The main target is addon packs from the <a class="feature-link" href="https://github.com/celguar/spp-classics-cmangos/tree/master/Addons" target="_blank" rel="noopener noreferrer">spp-classics-cmangos</a> repo, but you can also drop in launchers, patches, PDFs, or other curated files.</p>
    <div class="feature-actions">
      <a class="feature-button" href="<?php echo htmlspecialchars($downloadsRealmlistHref); ?>">Download realmlist.wtf</a>
    </div>
    <p class="feature-note">Suggested Linux host folders: <span class="feature-code">/var/www/html/downloads/addons</span> and <span class="feature-code">/var/www/html/downloads/tools</span>.</p>
    <p class="feature-note">Suggested Windows host folders: <span class="feature-code">/website/downloads/addons</span> and <span class="feature-code">/website/downloads/tools</span>.</p>
  </section>

  <div class="downloads-grid">
    <?php foreach ($downloadsSections as $section): ?>
      <section class="feature-panel">
        <h3 class="feature-panel-title"><?php echo htmlspecialchars($section['title']); ?></h3>
        <p><?php echo htmlspecialchars($section['description']); ?></p>

        <?php if (!empty($section['show_realmlist_action'])): ?>
          <div class="feature-actions">
            <a class="feature-button" href="<?php echo htmlspecialchars($downloadsRealmlistHref); ?>">Download realmlist.wtf</a>
          </div>
        <?php endif; ?>

        <?php if (!empty($section['files'])): ?>
          <div class="downloads-file-list">
            <?php foreach ($section['files'] as $file): ?>
              <div class="download-item">
                <a class="feature-link" href="<?php echo htmlspecialchars($file['href']); ?>"><?php echo htmlspecialchars($file['name']); ?></a>
                <span class="download-badge feature-badge"><?php echo htmlspecialchars($file['ext']); ?></span>
                <span class="download-meta"><?php echo htmlspecialchars($file['size']); ?><?php if (!empty($file['modified'])) echo ' | ' . htmlspecialchars($file['modified']); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="feature-empty">No files are hosted in <span class="feature-code"><?php echo htmlspecialchars($section['web']); ?></span> yet.</p>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>
  </div>
</div>
<?php builddiv_end(); ?>
