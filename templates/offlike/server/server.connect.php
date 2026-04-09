<?php
builddiv_start(1, 'How to Play');
?>
<div class="modern-content">
<?php header_image_gif('game_guide'); ?>
<div class="feature-shell">
  <section class="feature-hero">
    <p class="feature-eyebrow"><?php echo htmlspecialchars($connectRealmName); ?> Setup</p>
    <h2 class="feature-title"><?php echo $isLoggedIn ? 'Choose a client and connect in a few minutes.' : 'Choose a client, create an account, and connect in a few minutes.'; ?></h2>
    <p class="feature-copy">
      The player will need to source their own WoW client.
    </p>
    <ul class="feature-copy-list">
      <li><strong>Vanilla (1.12.1) client</strong>, optionally pair it with <strong>Project Reforged</strong> for HD visuals</li>
      <li><strong>Classic (1.14.2) with HermesProxy</strong></li>
      <li><strong>WoWee</strong>, the open source C++ client</li>
    </ul>
    <p class="feature-copy feature-copy-footer"><?php echo $isLoggedIn ? 'Then use this server\'s <code>realmlist.wtf</code> to connect.' : 'Create your account, then use this server\'s <code>realmlist.wtf</code> to connect.'; ?></p>
    <div class="feature-actions">
      <?php if (!$isLoggedIn) { ?>
      <a class="feature-button is-primary" href="<?php echo htmlspecialchars($createAccountUrl); ?>">Create Account</a>
      <?php } ?>
      <?php if (!empty($downloadRealmlistOptions) && count((array)$downloadRealmlistOptions) > 1): ?>
        <?php foreach ((array)$downloadRealmlistOptions as $realmlistOption): ?>
          <a class="feature-button<?php echo !empty($realmlistOption['is_selected']) ? ' is-primary' : ''; ?>" href="<?php echo htmlspecialchars((string)$realmlistOption['href']); ?>">
            Download <?php echo htmlspecialchars((string)$realmlistOption['realm_name']); ?> realmlist.wtf
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <a class="feature-button" href="<?php echo htmlspecialchars($downloadRealmlistUrl); ?>">Download realmlist.wtf</a>
      <?php endif; ?>
    </div>
  </section>

  <div class="feature-grid">
    <section class="feature-panel">
      <h3 class="feature-panel-title">Install Options</h3>
      <p>Pick whichever setup fits how you want to play.</p>
      <p class="feature-note">You will need to provide your own game client install. The options below cover the supported ways to connect to this server.</p>
      <div class="feature-card-list">
        <a class="feature-card" href="https://github.com/celguar/HermesProxy/releases/tag/3.8.c" target="_blank" rel="noopener noreferrer">
          <div class="feature-card-head">
            <strong>HermesProxy 3.8.c</strong>
            <span class="feature-badge">Launcher Option</span>
          </div>
          <p>Use HermesProxy if you want to play through the <strong>Classic 1.14.2</strong> client path.</p>
          <span class="feature-link">Open HermesProxy 3.8.c release</span>
        </a>
        <a class="feature-card" href="https://projectreforged.github.io/" target="_blank" rel="noopener noreferrer">
          <div class="feature-card-head">
            <strong>Project Reforged</strong>
            <span class="feature-badge">HD Visual Mod</span>
          </div>
          <p>Project Reforged is an <strong>optional HD visual mod</strong> layered on top of a <strong>Vanilla 1.12.1</strong> client.</p>
          <span class="feature-link">Open Project Reforged</span>
        </a>
        <a class="feature-card" href="https://github.com/Kelsidavis/WoWee" target="_blank" rel="noopener noreferrer">
          <div class="feature-card-head">
            <strong>WoWee</strong>
            <span class="feature-badge">Open Source Client</span>
          </div>
          <p>WoWee is an <strong>open source C++ client</strong> option if you want an alternative to the original Blizzard clients.</p>
          <span class="feature-link">Open WoWee on GitHub</span>
        </a>
      </div>
    </section>

    <section class="feature-panel">
      <h3 class="feature-panel-title">Quick Start</h3>
      <div class="feature-step-grid">
        <div class="feature-step">
          <strong>1. Install your version of choice</strong>
          <div>Source your own client, then choose:</div>
          <ul class="feature-step-list">
            <li><strong>Vanilla (1.12.1)</strong></li>
            <li><strong>Classic (1.14.2) with HermesProxy</strong></li>
            <li><strong>WoWee</strong></li>
          </ul>
        </div>
        <?php if (!$isLoggedIn) { ?>
        <div class="feature-step">
          <strong>2. Create your account</strong>
          Use the website registration page here:
          <a class="feature-link" href="<?php echo htmlspecialchars($createAccountUrl); ?>">Create Account</a>
        </div>
        <?php } else { ?>
        <div class="feature-step">
          <strong>2. Account ready</strong>
          You're already signed in on the website, so you can move straight to the client connection steps below.
        </div>
        <?php } ?>
        <div class="feature-step">
          <strong>3. Get this server's realmlist</strong>
          <?php if (!empty($downloadRealmlistOptions) && count((array)$downloadRealmlistOptions) > 1): ?>
            Download the file that matches the realm you want to join:
            <ul class="feature-step-list">
              <?php foreach ((array)$downloadRealmlistOptions as $realmlistOption): ?>
                <li>
                  <a class="feature-link" href="<?php echo htmlspecialchars((string)$realmlistOption['href']); ?>">realmlist.wtf for <?php echo htmlspecialchars((string)$realmlistOption['realm_name']); ?></a>
                  <?php if (!empty($realmlistOption['host'])): ?>
                    <span class="feature-code">set realmlist <?php echo htmlspecialchars((string)$realmlistOption['host']); ?></span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            Download the file directly here:
            <a class="feature-link" href="<?php echo htmlspecialchars($downloadRealmlistUrl); ?>">realmlist.wtf for <?php echo htmlspecialchars($connectRealmName); ?></a>
          <?php endif; ?>
        </div>
        <div class="feature-step">
          <strong>4. Manual fallback if needed</strong>
          <?php if (!empty($downloadRealmlistOptions) && count((array)$downloadRealmlistOptions) > 1): ?>
            If you need to edit the file by hand, use the host shown next to the matching realm download above.
          <?php else: ?>
            If you need to edit it by hand, your file should contain:
            <span class="feature-code">set realmlist <?php echo htmlspecialchars($connectRealmlistHost); ?></span>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </div>
</div>
</div>
<?php builddiv_end(); ?>
