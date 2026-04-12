<?php
builddiv_start(1, 'Downloads', 0);
?>
<div class="downloads-shell feature-shell">
  <section class="feature-hero">
    <p class="feature-eyebrow">Hosted Files</p>
    <h2 class="feature-title">Downloads and client extras in one place.</h2>
    <p class="feature-copy">This page is for hosted client files. Addon bundles now come from the <a class="feature-link" href="https://github.com/japtenks/spp-cmangos-prox/releases/tag/assets" target="_blank" rel="noopener noreferrer">spp-cmangos-prox GitHub assets release</a>, while tools, patches, PDFs, and other curated files can still be hosted here.</p>
    <div class="feature-actions">
      <?php if (!empty($downloadsRealmlistOptions) && count((array)$downloadsRealmlistOptions) > 1): ?>
        <?php foreach ((array)$downloadsRealmlistOptions as $realmlistOption): ?>
          <?php if (!empty($realmlistOption['is_download_available'])): ?>
            <a class="feature-button<?php echo !empty($realmlistOption['is_selected']) ? ' is-primary' : ''; ?>" href="<?php echo htmlspecialchars((string)$realmlistOption['href']); ?>">
              Download <?php echo htmlspecialchars((string)$realmlistOption['realm_name']); ?> realmlist.wtf
            </a>
          <?php else: ?>
            <span class="feature-button is-disabled" aria-disabled="true">
              <?php echo htmlspecialchars((string)$realmlistOption['realm_name']); ?> realmlist unavailable
            </span>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <?php if (!empty($downloadsRealmlistDownloadAvailable)): ?>
          <a class="feature-button" href="<?php echo htmlspecialchars($downloadsRealmlistHref); ?>">Download realmlist.wtf</a>
        <?php else: ?>
          <span class="feature-button is-disabled" aria-disabled="true">Realmlist download unavailable</span>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>

  <div class="downloads-grid">
    <?php foreach ($downloadsSections as $section): ?>
      <section class="feature-panel">
        <h3 class="feature-panel-title"><?php echo htmlspecialchars($section['title']); ?></h3>
        <p><?php echo htmlspecialchars($section['description']); ?></p>

        <?php if (!empty($section['show_realmlist_action'])): ?>
          <div class="feature-actions">
            <?php if (!empty($downloadsRealmlistOptions) && count((array)$downloadsRealmlistOptions) > 1): ?>
              <?php foreach ((array)$downloadsRealmlistOptions as $realmlistOption): ?>
                <?php if (!empty($realmlistOption['is_download_available'])): ?>
                  <a class="feature-button<?php echo !empty($realmlistOption['is_selected']) ? ' is-primary' : ''; ?>" href="<?php echo htmlspecialchars((string)$realmlistOption['href']); ?>">
                    Download <?php echo htmlspecialchars((string)$realmlistOption['realm_name']); ?> realmlist.wtf
                  </a>
                <?php else: ?>
                  <span class="feature-button is-disabled" aria-disabled="true">
                    <?php echo htmlspecialchars((string)$realmlistOption['realm_name']); ?> realmlist unavailable
                  </span>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php else: ?>
              <?php if (!empty($downloadsRealmlistDownloadAvailable)): ?>
                <a class="feature-button" href="<?php echo htmlspecialchars($downloadsRealmlistHref); ?>">Download realmlist.wtf</a>
              <?php else: ?>
                <span class="feature-button is-disabled" aria-disabled="true">Realmlist download unavailable</span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($section['files'])): ?>
          <div class="downloads-file-list">
            <?php foreach ($section['files'] as $file): ?>
              <div class="download-item">
                <a class="feature-link" href="<?php echo htmlspecialchars($file['href']); ?>"><?php echo htmlspecialchars($file['name']); ?></a>
                <span class="download-badge feature-badge"><?php echo htmlspecialchars($file['ext']); ?></span>
                <span class="download-meta"><?php echo htmlspecialchars($file['size']); ?><?php if (!empty($file['note'])) echo ' | ' . htmlspecialchars($file['note']); ?><?php if (!empty($file['modified'])) echo ' | ' . htmlspecialchars($file['modified']); ?></span>
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
