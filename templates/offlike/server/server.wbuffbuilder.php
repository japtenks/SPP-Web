<?php builddiv_start(1, 'World Buff Builder'); ?>

<div
  id="wbuilder-app"
  class="modern-content wb-page"
  data-wb-classes="<?php echo htmlspecialchars(json_encode($worldBuffClasses), ENT_QUOTES); ?>"
  data-wb-spells="<?php echo htmlspecialchars(json_encode($worldBuffSpellCatalog), ENT_QUOTES); ?>"
  data-wb-default-preset="warrior_2"
>
  <a class="wb-back" href="/index.php?n=server&sub=botcommands">&#8592; Back to Bot Guide</a>
  <div class="wb-card">
    <h3>World Buff Builder</h3>
    <p>Build <code>AiPlayerbot.WorldBuff...</code> config lines for class-specific Vanilla raid prep. Starter presets on this page are adapted from Ile's raid guide, and you can tweak every field before copying the final line.</p>
    <div class="wb-tag-row">
      <span class="wb-tag">Config key: <code>AiPlayerbot.WorldBuff</code></span>
      <span class="wb-tag">Travel strategy: <code>wbuff travel</code></span>
      <span class="wb-tag">Fast apply: <code>/ra nc +wbuff</code></span>
    </div>
  </div>

  <div class="wb-grid">
    <div>
      <div class="wb-card">
        <h3>Starter Presets</h3>
        <p>Pick a class, then load one of the available starter presets. If a class has no starter entry yet, you can still build the line manually.</p>
        <div class="wb-row">
          <span class="wb-label">Class</span>
          <div class="wb-field">
            <select id="wb-class-filter" class="wb-select"></select>
          </div>
        </div>
        <div id="wb-preset-list" class="wb-preset-list"></div>
      </div>

      <div class="wb-card">
        <h3>Builder</h3>
        <div class="wb-row">
          <span class="wb-label">Faction</span>
          <div class="wb-field">
            <select id="wb-faction" class="wb-select">
              <option value="0">0 = all bots</option>
              <option value="1">1 = alliance</option>
              <option value="2">2 = horde</option>
            </select>
          </div>
        </div>
        <div class="wb-row">
          <span class="wb-label">Class ID</span>
          <div class="wb-field">
            <select id="wb-class" class="wb-select"></select>
          </div>
          <div class="wb-field">
            <input id="wb-class-label" class="wb-input" type="text" readonly>
          </div>
        </div>
        <div class="wb-row">
          <span class="wb-label">Spec ID</span>
          <div class="wb-field">
            <select id="wb-spec" class="wb-select"></select>
          </div>
          <div class="wb-field">
            <input id="wb-spec-label" class="wb-input" type="text" readonly>
          </div>
        </div>
        <div class="wb-row">
          <span class="wb-label">Levels</span>
          <div class="wb-field wb-field-small">
            <input id="wb-min" class="wb-input" type="number" min="1" max="80" value="60">
          </div>
          <div class="wb-field wb-field-small">
            <input id="wb-max" class="wb-input" type="number" min="1" max="80" value="60">
          </div>
          <div class="wb-field">
            <input id="wb-event" class="wb-input" type="number" min="0" placeholder="Optional event id">
          </div>
        </div>
        <div class="wb-row">
          <span class="wb-label">Preset Name</span>
          <div class="wb-field">
            <input id="wb-name" class="wb-input" type="text" placeholder="Optional note for yourself">
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="wb-card">
        <h3>Buff List</h3>
        <p>Choose buffs from the available list. Each buff can only be selected once, and the spell ID updates automatically from that choice.</p>
        <div id="wb-spells"></div>
        <button type="button" class="wb-btn wb-btn-add" data-wb-action="add-spell">+ Add Buff</button>
      </div>

      <div class="wb-card">
        <h3>Output</h3>
        <div class="wb-note">Config line</div>
        <div class="wb-copy-row">
          <div id="wb-out-line" class="wb-output"></div>
          <button type="button" class="wb-btn wb-btn-copy" data-wb-action="copy-output">Copy</button>
        </div>
      </div>
    </div>
  </div>
</div>
<?php builddiv_end(); ?>
