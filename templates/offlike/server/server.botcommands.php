<?php builddiv_start(1, 'Bot Guide'); ?>

<div class="botcommands-page feature-shell" data-active-command-tab="<?php echo htmlspecialchars($activeCommandTab); ?>">
<div class="botcommands-shell">
  <div class="sref-tabs">
    <button type="button" class="sref-tab-btn<?php echo $activeCommandTab === 'strategies' ? ' active' : ''; ?>" data-tab-target="tab-strategies">Strategy Reference</button>
    <button type="button" class="sref-tab-btn<?php echo $activeCommandTab === 'vanilla' ? ' active' : ''; ?>" data-tab-target="tab-vanilla">Vanilla Raiding</button>
    <button type="button" class="sref-tab-btn<?php echo $activeCommandTab === 'wbuffs' ? ' active' : ''; ?>" data-tab-target="tab-wbuffs">WBuff Builder</button>
    <button type="button" class="sref-tab-btn<?php echo $activeCommandTab === 'macros' ? ' active' : ''; ?>" data-tab-target="tab-macros">Macro Builder</button>
    <button type="button" class="sref-tab-btn<?php echo $activeCommandTab === 'filters' ? ' active' : ''; ?>" data-tab-target="tab-filters">Chat Filters</button>
    <button type="button" class="sref-tab-btn<?php echo $activeCommandTab === 'bot' ? ' active' : ''; ?>" data-tab-target="tab-bot">Bot Commands</button>
    <button type="button" class="sref-tab-btn<?php echo $activeCommandTab === 'commands' ? ' active' : ''; ?>" data-tab-target="tab-commands">Commands</button>
    <button type="button" class="sref-tab-btn<?php echo $activeCommandTab === 'builder' ? ' active' : ''; ?>" data-tab-target="tab-builder">Custom Builder</button>
  </div>

  <div id="tab-bot" class="sref-panel<?php echo $activeCommandTab === 'bot' ? ' active' : ''; ?>">
    <input type="text" id="botCommandSearch" class="csb-input cff-search" data-filter-input="bot" placeholder="Search bot commands...">
    <div class="cmd-filter-bar">
      <span class="cmd-filter-label">Type</span>
      <select id="botCommandTypeFilter" class="csb-select" data-filter-select="bot">
        <option value="all">All types</option>
        <option value="action">Action</option>
        <option value="strategy">Strategy</option>
        <option value="trigger">Trigger</option>
        <option value="value">Value</option>
        <option value="list">List</option>
        <option value="chatfilter">Chatfilter</option>
        <option value="object">Object</option>
        <option value="template">Template</option>
        <option value="help">Help</option>
      </select>
      <span class="cmd-filter-label">State</span>
      <select id="botCommandStateFilter" class="csb-select" data-filter-select="bot">
        <option value="all">All states</option>
        <option value="co">Combat</option>
        <option value="nc">Non-combat</option>
        <option value="react">Reaction</option>
        <option value="dead">Dead</option>
        <option value="general">General</option>
      </select>
      <span class="cmd-filter-label">Role</span>
      <select id="botCommandRoleFilter" class="csb-select" data-filter-select="bot">
        <option value="all">All roles</option>
        <option value="tank">Tank</option>
        <option value="dps">DPS</option>
        <option value="healer">Healer</option>
        <option value="general">General</option>
      </select>
      <span class="cmd-filter-label">Class</span>
      <select id="botCommandClassFilter" class="csb-select" data-filter-select="bot">
        <option value="all">All classes</option>
        <option value="warrior">Warrior</option>
        <option value="paladin">Paladin</option>
        <option value="hunter">Hunter</option>
        <option value="rogue">Rogue</option>
        <option value="priest">Priest</option>
        <option value="shaman">Shaman</option>
        <option value="mage">Mage</option>
        <option value="warlock">Warlock</option>
        <option value="druid">Druid</option>
        <option value="deathknight">Death Knight</option>
      </select>
      <button type="button" class="csb-btn csb-btn-copy cmd-filter-reset" data-action="reset-bot-filters">Reset Filters</button>
    </div>
    <div class="cff-note">Browse by state first, then role, then class. Search still matches command name, metadata, and help text.</div>
    <div class="cmd-grid" id="botCommandCards">
      <?php foreach ($botCommandCards as $topic): ?>
      <details class="cmd-card collapse-card"
           data-filter-search="<?php echo htmlspecialchars($topic['search_blob']); ?>"
           data-type="<?php echo htmlspecialchars($topic['type_value']); ?>"
           data-state-tags="<?php echo htmlspecialchars(implode(',', $topic['state_tags'] ?? array())); ?>"
           data-role-tags="<?php echo htmlspecialchars(implode(',', $topic['role_tags'] ?? array())); ?>"
           data-class-tags="<?php echo htmlspecialchars(implode(',', $topic['class_tags'] ?? array())); ?>">
        <summary class="collapse-card__summary cmd-summary">
          <span class="collapse-card__copy">
            <span class="collapse-card__title"><?php echo htmlspecialchars($topic['name']); ?></span>
          </span>
          <span class="collapse-card__caret" aria-hidden="true"></span>
        </summary>
        <div class="collapse-card__body cmd-body">
        <div class="cmd-meta">
          <?php if (($topic['category'] ?? '') !== '' && ($topic['category'] ?? '') !== '-'): ?>
          <button type="button" class="cmd-chip is-clickable" data-action="bot-chip-filter" data-filter-kind="type" data-filter-value="<?php echo htmlspecialchars(strtolower((string)$topic['category'])); ?>" title="Filter bot commands by type <?php echo htmlspecialchars((string)$topic['category']); ?>"><?php echo htmlspecialchars($topic['category']); ?></button>
          <?php endif; ?>
          <?php foreach (($topic['state_tags'] ?? array()) as $tag): ?>
          <button type="button" class="cmd-chip is-clickable" data-action="bot-chip-filter" data-filter-kind="state" data-filter-value="<?php echo htmlspecialchars($tag); ?>" title="Filter bot commands by state <?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars($tag === 'react' ? 'State reaction' : 'State ' . $tag); ?></button>
          <?php endforeach; ?>
          <?php foreach (($topic['role_tags'] ?? array()) as $tag): ?>
          <button type="button" class="cmd-chip is-clickable" data-action="bot-chip-filter" data-filter-kind="role" data-filter-value="<?php echo htmlspecialchars($tag); ?>" title="Filter bot commands by role <?php echo htmlspecialchars($tag); ?>">Role <?php echo htmlspecialchars($tag); ?></button>
          <?php endforeach; ?>
          <?php foreach (($topic['class_tags'] ?? array()) as $tag): ?>
          <?php if ($tag !== 'all'): ?>
          <button type="button" class="cmd-chip is-clickable" data-action="bot-chip-filter" data-filter-kind="class" data-filter-value="<?php echo htmlspecialchars($tag); ?>" title="Filter bot commands by class <?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars(ucfirst($tag)); ?></button>
          <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <div class="cmd-help"><?php echo nl2br(htmlspecialchars($topic['help'])); ?></div>
        </div>
      </details>
      <?php endforeach; ?>
      <?php if (empty($botCommandCards)): ?>
      <div class="cmd-card"><div class="cmd-help">No bot commands found.</div></div>
      <?php endif; ?>
    </div>
  </div>

  <div id="tab-commands" class="sref-panel<?php echo $activeCommandTab === 'commands' ? ' active' : ''; ?>">
    <input type="text" id="gmCommandSearch" class="csb-input cff-search" data-filter-input="gm" placeholder="Search GM commands...">
    <div class="cmd-filter-bar">
      <span class="cmd-filter-label">Security</span>
      <select id="gmCommandSecurityFilter" class="csb-select" data-filter-select="gm">
        <option value="all">All levels</option>
        <?php foreach ($gmSecurityValues as $securityValue): ?>
        <option value="<?php echo htmlspecialchars($securityValue); ?>"><?php echo htmlspecialchars($securityValue); ?></option>
        <?php endforeach; ?>
      </select>
      <span class="cmd-filter-label">Prefix</span>
      <select id="gmCommandPrefixFilter" class="csb-select" data-filter-select="gm">
        <option value="all">All prefixes</option>
        <?php foreach ($gmPrefixValues as $prefixValue): ?>
        <option value="<?php echo htmlspecialchars($prefixValue); ?>"><?php echo htmlspecialchars($prefixValue); ?></option>
        <?php endforeach; ?>
      </select>
      <button type="button" class="csb-btn csb-btn-copy cmd-filter-reset" data-action="reset-gm-filters">Reset Filters</button>
    </div>
    <div class="cff-note">Search by command name, security level, or help text. Prefix comes from the first word of the command name.</div>
    <div class="cmd-grid" id="gmCommandCards">
      <?php foreach ($gmCommandCards as $cmd): ?>
      <details class="cmd-card collapse-card"
           data-filter-search="<?php echo htmlspecialchars($cmd['search_blob']); ?>"
           data-security="<?php echo htmlspecialchars((string)($cmd['security'] ?? '')); ?>"
           data-prefix="<?php echo htmlspecialchars($cmd['prefix']); ?>">
        <summary class="collapse-card__summary cmd-summary">
          <span class="collapse-card__copy">
            <span class="collapse-card__title"><?php echo htmlspecialchars($cmd['name']); ?></span>
          </span>
          <span class="collapse-card__caret" aria-hidden="true"></span>
        </summary>
        <div class="collapse-card__body cmd-body">
        <div class="cmd-meta">
          <button type="button" class="cmd-chip is-clickable" data-action="gm-chip-filter" data-filter-kind="security" data-filter-value="<?php echo htmlspecialchars((string)($cmd['security'] ?? '')); ?>" title="Filter GM commands by security <?php echo htmlspecialchars((string)($cmd['security'] ?? '')); ?>">Security <?php echo htmlspecialchars($cmd['security']); ?></button>
          <?php if (($cmd['prefix'] ?? '') !== ''): ?>
          <button type="button" class="cmd-chip is-clickable" data-action="gm-chip-filter" data-filter-kind="prefix" data-filter-value="<?php echo htmlspecialchars($cmd['prefix']); ?>" title="Filter GM commands by prefix <?php echo htmlspecialchars($cmd['prefix']); ?>"><?php echo htmlspecialchars($cmd['prefix']); ?></button>
          <?php endif; ?>
        </div>
        <div class="cmd-help"><?php echo nl2br(htmlspecialchars($cmd['help'])); ?></div>
        </div>
      </details>
      <?php endforeach; ?>
      <?php if (empty($gmCommandCards)): ?>
      <div class="cmd-card"><div class="cmd-help">No GM commands found for this account level.</div></div>
      <?php endif; ?>
    </div>
  </div>

  <div id="tab-filters" class="sref-panel<?php echo $activeCommandTab === 'filters' ? ' active' : ''; ?>">
    <h3>Chat Filters</h3>
    <p>Chat filters are prefix tokens that narrow which bots respond before the command runs. You can chain multiple filters together, then place the command after them.</p>
    <div class="mb-help-grid">
      <div class="mb-help-card">
        <h4>Order</h4>
        <p><code>/w BotName @tank @60 follow</code></p>
      </div>
      <div class="mb-help-card">
        <h4>Chaining</h4>
        <p>Each filter narrows the pool further. The command starts after the last filter token.</p>
      </div>
      <div class="mb-help-card">
        <h4>Source</h4>
        <p>This tab reflects the current filter surface implemented in <code>playerbots/playerbot/ChatFilter.cpp</code>.</p>
      </div>
    </div>
    <input type="text" id="chatFilterSearch" class="csb-input cff-search" data-filter-input="chat" placeholder="Search filter names, descriptions, or examples...">
    <div class="cff-note">Value-based filters like <code>@guild=</code>, <code>@rank=</code>, <code>@co=</code>, <code>@quest=</code>, and <code>@use=</code> expect text after the token.</div>
    <div class="cff-grid" id="chatFilterCards">
      <?php foreach ($chatFilterCards as $family): ?>
      <details class="cff-card collapse-card" data-filter-search="<?php echo htmlspecialchars($family['search_blob']); ?>">
          <summary class="collapse-card__summary">
            <span class="collapse-card__copy">
              <span class="collapse-card__title"><?php echo htmlspecialchars($family['title']); ?></span>
            </span>
            <span class="collapse-card__caret" aria-hidden="true"></span>
          </summary>
        <div class="collapse-card__body">
        <p><?php echo htmlspecialchars($family['description']); ?></p>
        <div class="cff-token-row">
          <?php foreach ($family['tokens'] as $token): ?>
          <code><?php echo htmlspecialchars($token); ?></code>
          <?php endforeach; ?>
        </div>
        <div class="cff-example-list">
          <?php foreach ($family['examples'] as $syntax => $meaning): ?>
          <div class="cff-example-item">
            <code><?php echo htmlspecialchars($syntax); ?></code>
            <span><?php echo htmlspecialchars($meaning); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        </div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>

  <div id="tab-strategies" class="sref-panel<?php echo $activeCommandTab === 'strategies' ? ' active' : ''; ?>">
    <h3>Strategy Reference</h3>
    <p>This page is most useful when it helps you answer three questions quickly: which state am I changing, what role package should the bot run, and what movement or follow behavior do I want around that package.</p>
    <table>
      <thead><tr><th>Key</th><th>Bot State</th><th>When Active</th></tr></thead>
      <tbody>
        <tr><td><code>co</code></td><td>Combat</td><td>While the bot is in a fight.</td></tr>
        <tr><td><code>nc</code></td><td>Non-combat</td><td>Wandering, questing, traveling, idle.</td></tr>
        <tr><td><code>react</code></td><td>Reaction</td><td>Parallel to combat for immediate responses.</td></tr>
        <tr><td><code>dead</code></td><td>Dead</td><td>While the bot is a ghost.</td></tr>
      </tbody>
    </table>
    <table>
      <thead><tr><th>Priority Band</th><th>Examples</th></tr></thead>
      <tbody>
        <tr><td>90 - Emergency</td><td>Critical health alert, emergency heal.</td></tr>
        <tr><td>80 - Critical heal</td><td>Major healing response.</td></tr>
        <tr><td>60-70 - Heal</td><td>Light or medium heals.</td></tr>
        <tr><td>50 - Dispel</td><td>Remove curse, abolish poison.</td></tr>
        <tr><td>40 - Interrupt</td><td>Kick, counterspell.</td></tr>
        <tr><td>30 - Move</td><td>Charge, disengage.</td></tr>
        <tr><td>20 - High</td><td>Major cooldowns.</td></tr>
        <tr><td>10 - Normal</td><td>Standard rotation.</td></tr>
        <tr><td>1 - Idle</td><td>Fallback behavior like melee or wand.</td></tr>
      </tbody>
    </table>
    <p>Strategy changes use <code>+add</code> and <code>-remove</code> syntax, comma-separated: <code>+dps,+dps assist,-threat</code></p>

    <div class="ref-grid">
      <div class="ref-card">
        <h4>Role Starters</h4>
        <ul>
          <li><code>DPS</code>: <code>co +dps,+dps assist,-threat</code></li>
          <li><code>Tank</code>: <code>co +dps,+tank assist,+threat,+boost</code></li>
          <li><code>Healer</code>: <code>co +offheal,+dps assist,+cast time</code></li>
          <li><code>Leveling</code>: pair a combat package with <code>nc +rpg,+quest,+grind,+loot,+wander</code></li>
        </ul>
      </div>
      <div class="ref-card">
        <h4>Movement and Follow</h4>
        <ul>
          <li><code>follow</code>: stay on the leader</li>
          <li><code>stay</code>: hold the current position</li>
          <li><code>guard</code>: defend the master's spot</li>
          <li><code>free</code>: move independently</li>
          <li><code>wander</code>: roam near players, then snap back when too far</li>
        </ul>
      </div>
      <div class="ref-card">
        <h4>Positioning and Safety</h4>
        <ul>
          <li><code>behind</code>, <code>close</code>, <code>ranged</code>, <code>kite</code>, <code>pull back</code></li>
          <li><code>avoid aoe</code> and <code>avoid mobs</code> prevent bad pulls and bad floor effects</li>
          <li><code>flee</code>, <code>preheal</code>, and <code>cast time</code> are the main survival helpers</li>
        </ul>
      </div>
      <div class="ref-card">
        <h4>Persistence Workflow</h4>
        <ul>
          <li>Change one or more states with <code>co</code>, <code>nc</code>, <code>react</code>, or <code>dead</code></li>
          <li>Save the setup with <code>save ai</code></li>
          <li>Reuse later with <code>load ai &lt;preset&gt;</code></li>
          <li>Reset to defaults with <code>reset ai</code></li>
        </ul>
      </div>
    </div>

    <h3>Useful Starter Loads</h3>
    <pre>Solo leveling bot
co: +dps,-threat,+custom::say
nc: +rpg,+quest,+grind,+loot,+wander,+custom::say

Group DPS bot
co: +dps,+dps assist,-threat,+boost
nc: +follow,+loot,+delayed roll,+food

Group tank bot
co: +dps,+tank assist,+threat,+boost
nc: +follow,+loot,+delayed roll,+food

Group healer bot
co: +offheal,+dps assist,+cast time
nc: +follow,+loot,+delayed roll,+food,+conserve mana

BG farmer
co: +dps,+dps assist,+threat,+boost,+pvp,+duel
nc: +bg,+wander,+rpg
react: +pvp</pre>

    <h3>Commands You Actually Reuse</h3>
    <table>
      <thead><tr><th>Command</th><th>Effect</th></tr></thead>
      <tbody>
        <tr><td><code>.bot co &lt;strategies&gt;</code></td><td>Change combat strategies.</td></tr>
        <tr><td><code>.bot nc &lt;strategies&gt;</code></td><td>Change non-combat strategies.</td></tr>
        <tr><td><code>.bot react &lt;strategies&gt;</code></td><td>Change reaction strategies.</td></tr>
        <tr><td><code>.bot dead &lt;strategies&gt;</code></td><td>Change dead strategies.</td></tr>
        <tr><td><code>.bot save ai</code></td><td>Persist current strategies.</td></tr>
        <tr><td><code>.bot save ai &lt;preset&gt;</code></td><td>Save to a named preset.</td></tr>
        <tr><td><code>.bot load ai &lt;preset&gt;</code></td><td>Load a named preset.</td></tr>
        <tr><td><code>.bot list ai</code></td><td>List saved presets.</td></tr>
        <tr><td><code>.bot reset ai</code></td><td>Reset to default class/spec strategies.</td></tr>
      </tbody>
    </table>
    <p>Strategy syntax: <code>+strategy</code> add, <code>-strategy</code> remove, <code>~strategy</code> toggle, and comma-separate multiple entries.</p>
  </div>

  <div id="tab-vanilla" class="sref-panel<?php echo $activeCommandTab === 'vanilla' ? ' active' : ''; ?>">
    <h3>Vanilla Raiding</h3>
    <p><strong>Credit:</strong> This tab is adapted from Ile's <em>SPP Raiding - Vanilla</em> guide, SPP AI Playerbot Player, Dev and Raid Progression Leader.</p>
    <div class="vanilla-stack">
      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">Before You Raid</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>Raiding with bots is much more micro-heavy than raiding with people. Even easier raids can feel hard if your control habits are still rough.</p>
          <ul>
            <li>Expect to spend real attention on positioning, recoveries, and command timing instead of only your own rotation.</li>
            <li>Do not judge your setup by one wipe. Many raid issues are workflow issues, not raw bot power issues.</li>
            <li>If you are still learning, practice in forgiving dungeons before using 40-man raids as your classroom.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">Build A Real Roster</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>Do not PUG with bots. A mishmash roster with random specs, weak gear, and unclear roles makes raid debugging miserable.</p>
          <ul>
            <li>Build a stable core and gear it with intent instead of swapping random bodies in and out.</li>
            <li>Keep role identity clear so you know who your MT, off-tanks, focus healers, and priority DPS are.</li>
            <li>Use guild ranks, notes, MOTD, and guild info as your lightweight roster tracker.</li>
            <li><code>/g @rank=Veteran join</code> is a practical example of using guild metadata to assemble a core group fast.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">RTSC Is The Raid Game</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>SPP raiding is often closer to an RTS than a normal MMO raid. RTSC is the tool that lets you actually play that layer.</p>
          <p><strong>Credit:</strong> RTSC was added by Mostlikely, and it is one of the most important bot-control tools on the page.</p>
          <ul>
            <li><code>rtsc save &lt;name&gt;</code> stores a useful spot for later.</li>
            <li><code>rtsc go &lt;name&gt;</code> sends bots to the saved spot.</li>
            <li><code>rtsc unsave &lt;name&gt;</code> deletes a location you no longer need.</li>
            <li>Saved RTSC locations persist per bot, which is why encounter-specific macros are worth the setup.</li>
            <li><a href="https://www.youtube.com/watch?v=_hdX6ssVDi8" target="_blank" rel="noopener noreferrer">RTSC Control Demo on YouTube</a> is the walkthrough referenced in the guide.</li>
          </ul>
          <p>RTSC becomes especially important on bosses like Baron Geddon, Firemaw, Chromaggus, Twin Emperors, C'Thun, Heigan, Four Horsemen, Sapphiron, and Kel'Thuzad.</p>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">Focus Healing</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>Focus healing is for stabilizing a tank or another priority target, not for turning the entire healing roster into tunnel vision bots.</p>
          <ul>
            <li><code>focus heal +Name</code> assigns a healer-capable bot to focus that player or bot.</li>
            <li><code>focus heal none</code> removes the assignment.</li>
            <li>A good working range is roughly 0 focus healers in 5-mans and around 1-4 in raids, depending on fight size and pressure.</li>
            <li>Too many focus healers can lower raid stability because they stop naturally covering the rest of the group.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">Threat Management</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>Threat is one of the first raid problems that gets amplified with bots, especially early on when tanks are still gearing and the raid is not yet cleanly paced.</p>
          <ul>
            <li><code>co +wait for attack</code> and <code>wait for attack X</code> buy tank time before DPS starts.</li>
            <li><code>co +threat</code> helps DPS avoid climbing over the tank, though it will not save a fully inactive tank.</li>
            <li>Be careful forcing <code>+threat</code> onto healers, because delayed healing after a threat reset can be worse than healer aggro.</li>
          </ul>
          <pre>Main pull delay
/ra @dps wait for attack 10

Tank setup
/ra @tank co +tank,+tank assist,+threat

Threat-aware melee
/ra @melee co +dps,+dps assist,+threat</pre>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">Formation And Range</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>Formation controls whether raid-wide CC, beams, spins, fears, and splash effects become recoverable mistakes or instant wipes.</p>
          <ul>
            <li><code>/ra formation arrow</code> is a fast way to spread bots, but it is clunky for travel and can cause ninja pulls if left on carelessly.</li>
            <li>Try the built-in shapes: <code>near</code>, <code>chaos</code>, <code>circle</code>, <code>arrow</code>, <code>melee</code>, <code>queue</code>, and <code>line</code>.</li>
            <li><code>formation near</code> with <code>range followraid 1</code> is a strong RTSC starting point.</li>
            <li>The three range settings worth learning are <code>range follow</code>, <code>range followraid</code>, and <code>range attack</code>.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">World Buffs And Consumables</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>Class-specific world buffs and consumables are a practical part of raid preparation.</p>
          <ul>
            <li>Configure your packages through <code>AiPlayerbot.WorldBuff</code>.</li>
            <li><code>/ra nc +wbuff</code> becomes the fast raid-wide application once those configs exist.</li>
            <li>Newer playerbot builds also include <code>wbuff travel</code>, so world-buff prep can be handled as a travel workflow instead of only as an instant local apply.</li>
            <li>ZG, MC, and BWL are much more approachable without heavy crutches; AQ40 and Naxx benefit much more from serious preparation.</li>
          </ul>
          <p><a href="/index.php?n=server&sub=botcommands&tab=wbuffs">Open the WBuff Builder tab</a> to load class starters and generate copy-ready <code>AiPlayerbot.WorldBuff</code> lines.</p>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">Spell Mechanics And Relative Difficulty</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>This section explains what the guide means by the difficulty tags shown on raid mechanics. The ratings are about how much bot micro and encounter-specific control a mechanic demands, not just how dangerous the spell looks to a human player.</p>
          <p>The spell IDs are useful if you want to experiment with <code>AiPlayerbot.ImmuneSpellIds</code> in <code>aiplayerbot.conf</code>, but the guide's recommendation is to be careful with that. Adding too many immunities can flatten the challenge and remove the payoff from finally solving a hard fight with your own setup.</p>
          <p><strong>Author note:</strong> “I can assure you it will feel amazing after you’ve killed Four Horsemen for the first time with your own handcrafted strategies!”</p>
          <p>Boss difficulty is judged in the gear context of the raid's intended phase. A fight rated <code>[2]</code> in progression gear may feel trivial in full BIS, but the guide rates it for when players would normally be learning it.</p>
          <ul>
            <li><code>[1]</code> = very easy to counter. Little to no microing required.</li>
            <li><code>[2]</code> = moderate microing. Undergeared raids may feel the mechanic more sharply.</li>
            <li><code>[3]</code> = frequent microing. You will spend meaningful attention controlling bots instead of only playing your character.</li>
            <li><code>[4]</code> = very micro intense. Usually needs a proper premade setup and solid knowledge of bot strategies.</li>
            <li><code>[5]</code> = optimal play, heavy preparation, and major micromanagement during the fight.</li>
            <li><code>[NA]</code> = not realistically counterable in practice because of bugs, missing bot logic, or other technical limitations.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">Zul'Gurub</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>Zul'Gurub is a strong early raid classroom for synchronized kills, spreading, and a first taste of bot-specific mechanic handling before the harder 40-man tiers.</p>
          <p><strong>High Priest Thekal [2]</strong></p>
          <ul>
            <li>Phase 1 is the main check: Thekal and both adds need to die within about five seconds of each other or they reset the attempt by resurrecting.</li>
            <li>This is one of the cleaner early fights for learning RTI assignment and synchronized burst windows with bots.</li>
          </ul>
          <p><strong>Hakkar [3]</strong></p>
          <ul>
            <li><code>24322 - Blood Siphon</code>: the goal is to have <code>Poisonous Blood</code> available on the raid to blunt the siphon cycle.</li>
            <li><code>24321 - Poisonous Blood</code>: control Sons of Hakkar around the room so you can use them when needed instead of letting the fight get messy.</li>
            <li><code>24328 - Corrupted Blood</code>: this is a spacing check first and a healing check second.</li>
            <li><code>5246 - Intimidating Shout</code>: the guide's practical answer is brute-force warrior control, for example <code>/ra @warrior cast Intimidating Shout</code>.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">AQ20</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>AQ20 sits in a useful middle spot: still approachable, but already teaching tank swaps, movement routing, and targeted strategy swaps that matter later in AQ40 and Naxx.</p>
          <p><strong>Kurinnaxx [2]</strong></p>
          <ul>
            <li><code>25646 - Mortal Wound</code>: this is a straightforward early tank-swap lesson.</li>
          </ul>
          <p><strong>General Rajaxx [2]</strong></p>
          <ul>
            <li>The pre-pull wave set is usually manageable if the tank is geared and the raid is stable.</li>
            <li><code>25599 - Thundercrash</code>: be ready for the aggro reset with quick taunts and strong heal recovery.</li>
          </ul>
          <p><strong>Moam [2]</strong></p>
          <ul>
            <li><code>26639 - Drain Mana</code>: hunters and warlocks can help by draining mana back.</li>
            <li><code>28450 - Arcane Explosion</code>: if Moam caps mana, the whole raid pays for it fast.</li>
          </ul>
          <p><strong>Buru the Gorger [2]</strong></p>
          <ul>
            <li>The encounter is mainly about movement routing: kite Buru into eggs and use <code>do follow</code> style control to keep bots moving with you cleanly.</li>
          </ul>
          <p><strong>Ayamiss [1]</strong></p>
          <ul>
            <li>Keep melee on the ground mobs with RTI support while ranged burn the boss. It is one of the simpler fights in the guide.</li>
          </ul>
          <p><strong>Ossirian [3]</strong></p>
          <ul>
            <li><code>25176 - Strength of Ossirian</code>: the crystal game is the whole fight. Move, click the right crystal, then adapt caster strategies to the current weakness.</li>
            <li>The guide specifically calls out mage school swapping here as a good place to use class-specific command control.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">Onyxia's Lair</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>Onyxia is one of the earliest real raid checks in the guide. The fight is rated for progression gear, not full BIS, so it is treated as an early positioning and breath-control lesson instead of a farm boss.</p>
          <p><strong>Onyxia [2]</strong></p>
          <ul>
            <li><code>23364 - Tail Lash</code>: dragons punish sloppy rear positioning immediately, so keep the raid disciplined around her body.</li>
            <li><code>17086 - Breath</code>: this is the real danger. A bad angle can sweep the raid and end the pull instantly, so adaptive positioning matters more than raw throughput.</li>
            <li>The guide treats Onyxia as one of the first fights where safe-spot awareness and responsive movement start mattering for bot raids, even though the encounter is not especially hard for geared human groups.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">Molten Core</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>Molten Core is still the easiest 40-man raid in the guide, but it is where most players learn whether their roster and movement habits are actually raid-ready.</p>
          <p><strong>Magmadar [3]</strong></p>
          <ul>
            <li><code>19428 - Lava Bomb</code>: spread ranged and make sure melee are not sitting in fire.</li>
            <li><code>19408 - Panic</code>: Fear Ward or Tremor Totem smooths the fight out immediately.</li>
            <li><code>19451 - Frenzy</code>: Tranquilizing Shot is the clean answer.</li>
          </ul>
          <p><strong>Shazzrah [1]</strong></p>
          <ul>
            <li><code>19712 - Arcane Explosion</code>: keep everyone except the main tank out of the radius.</li>
            <li><code>28391 - Blink</code>: basic aggro reset, so expect quick tank recovery.</li>
          </ul>
          <p><strong>Baron Geddon [3]</strong></p>
          <ul>
            <li><code>20475 - Living Bomb</code>: the classic first RTSC check in MC. One bad bomb in the pack can wipe the raid instantly.</li>
            <li><code>19695 - Inferno</code>: either use RTSC or a disciplined <code>/ra do follow</code> reset.</li>
          </ul>
          <p><strong>Golemagg [2]</strong></p>
          <ul>
            <li>Use three RTIs and three tanks: one for Golemagg and one for each Core Rager.</li>
            <li><code>13880 - Magma Splash</code>: stacking fire damage and armor reduction. Tank swaps are optional but useful if your MT is under pressure.</li>
          </ul>
          <p><strong>Majordomo Executus [3]</strong></p>
          <ul>
            <li>This is more of a raid-assignment check than a single-boss mechanics check.</li>
            <li>Give warriors different RTIs, CC the healers, kill the healers first, and burn the elites last.</li>
          </ul>
          <p><strong>Ragnaros [3]</strong></p>
          <ul>
            <li><code>20566 - Wrath of Ragnaros</code>: huge knockback in melee. Position for it and be ready for tank recovery.</li>
            <li><code>21158 - Lava Burst</code>: raid-wide spacing matters before the pull, not after the first burst lands.</li>
            <li><strong>Submerge [2]</strong>: Sons of Flame are an off-tank pickup problem. Pull them away from casters because of their mana burn.</li>
            <li>This is one of the earliest fights where a pre-pull RTSC layout really starts paying for itself.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">Blackwing Lair</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p><strong>Vaelastrasz [2]</strong></p>
          <ul>
            <li><code>18173 / 23620 - Burning Adrenaline</code>: treat it like a bigger Baron Geddon bomb and move people decisively.</li>
          </ul>
          <p><strong>Broodlord Lashlayer [2]</strong></p>
          <ul>
            <li><code>24573 - Mortal Strike</code>: this is mostly a tank-and-healer check.</li>
            <li><code>18670 - Knock Away</code>: easier if your tank is already positioned correctly.</li>
          </ul>
          <p><strong>Firemaw [3]</strong>, <strong>Ebonroc [2]</strong>, <strong>Flamegor [2]</strong></p>
          <ul>
            <li><code>23339 - Wing Buffet</code>: all three drakes punish weak positioning and threat recovery.</li>
            <li><strong>Firemaw:</strong> <code>9574, 16536, 9658, 10452, 16168, 22433, 22713, 25651, 25668, 23341 - Flame Buffet</code> is a stacking DoT that falls off with proper LOS positioning. This is one of the clearest RTSC line-of-sight checks in the guide.</li>
            <li><strong>Ebonroc:</strong> <code>Shadow of Ebonroc</code> can be played around by stopping attacks while it is up.</li>
            <li><strong>Flamegor:</strong> <code>Frenzy</code> means Tranquilizing Shot still matters here too.</li>
          </ul>
          <p><strong>Chromaggus [3]</strong></p>
          <ul>
            <li><code>23310 - Time Lapse</code> is the hardest breath. DPS and healers need to be out of LOS when breaths go out.</li>
            <li><code>23316 - Ignite Flesh</code>, <code>23187 - Frost Burn</code>, and <code>23313 - Corrosive Acid</code> are easier individually but still punish sloppy breath handling.</li>
            <li><code>23170 - Brood Affliction: Bronze</code> means Hourglass Sand support matters.</li>
            <li>The guide's layout is basically three RTSC zones: a ranged spot, a LOS-and-cleanse spot, and a melee-and-main-tank spot.</li>
          </ul>
          <p><strong>Nefarian [4]</strong></p>
          <ul>
            <li><code>22539 - Shadow Flame</code>: Onyxia Scale Cloaks and facing discipline remain mandatory.</li>
            <li><code>22686 - Bellowing Roar</code>: Berserker Rage, Fear Ward, and Tremor Totem smooth the fight out.</li>
            <li><strong>Class calls:</strong> some are mild, some are brutal. Warrior call is the most dangerous because it hurts both tank durability and threat.</li>
            <li><strong>Bone Constructs [2]:</strong> when Nefarian drops under 20%, the room turns into an AOE cleanup check.</li>
            <li><strong>Suppression Room:</strong> rogue disarm support may still be unreliable, so treat it as a known caveat.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">AQ40 Trash</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>The guide calls out AQ40 trash as a real progression wall, especially later in the instance. A lot of these packs are more dangerous than the bosses around them if the pull plan and bot control are sloppy.</p>
          <p><strong>Note from author:</strong> Welcome to the endgame.</p>
          <p><strong>Anubisath Sentinel [3]</strong></p>
          <ul>
            <li>Use a mage with <code>cast Detect Magic</code> to reveal the random ability package before you commit the pull.</li>
            <li><code>13022 - Fire and Arcane Reflect</code>: swap mages away from fire and arcane damage.</li>
            <li><code>19595 - Shadow and Frost Reflect</code>: swap mages toward fire and keep warlocks and shadow priests from killing themselves into the reflect.</li>
            <li><code>24573 - Mortal Strike</code>: manageable with focus healing.</li>
            <li><code>26046 - Mana Burn</code>: drag the mob away from casters.</li>
            <li><code>26546 - Shadow Storm</code>: stack the raid near the target instead of leaving ranged spread out.</li>
          </ul>
          <p><strong>Obsidian Eradicator [3]</strong>, <strong>Obsidian Brainwasher [2]</strong></p>
          <ul>
            <li><strong>Eradicator:</strong> <code>26639 - Drain Mana</code> into <code>26458 - Shock Blast</code> is a mana-control problem first and a DPS problem second.</li>
            <li><strong>Brainwasher:</strong> <code>26079 - Cause Insanity</code> means you need fast CC on MC targets, and its mana-burn pressure rewards quick focus fire.</li>
          </ul>
          <p><strong>Vekniss Guardian [4]</strong>, <strong>Vekniss Warrior [1]</strong></p>
          <ul>
            <li><strong>Guardian:</strong> <code>26025 - Impale</code> is a huge positioning and kill-priority problem.</li>
            <li><strong>Warrior:</strong> the death borers are mostly an AOE cleanup tax.</li>
          </ul>
          <p><strong>Bug Tunnel [2]</strong></p>
          <ul>
            <li>The guide recommends strong AOE and even forcing mages into <code>co -ranged</code> so they stay planted and pump damage.</li>
            <li>Have a <code>do follow</code> reset ready in case bots fall behind while you move the pack route forward.</li>
          </ul>
          <p><strong>Vekniss Hive Crawler [1]</strong>, <strong>Vekniss Wasp [1]</strong>, <strong>Vekniss Stinger [2]</strong></p>
          <ul>
            <li><strong>Hive Crawler:</strong> poison bolts and sunder armor are manageable if cleanse and healing are awake.</li>
            <li><strong>Wasp/Stinger:</strong> the catalyst plus charge combo is the real danger, so burn stingers fast.</li>
          </ul>
          <p><strong>Qiraji Lasher [2]</strong></p>
          <ul>
            <li><code>26038 - Whirlwind</code> and <code>26027 - Knockback</code> are both trash-pull killers if the raid is positioned near other packs.</li>
          </ul>
          <p><strong>Anubisath Defender [3]</strong></p>
          <ul>
            <li><strong>Tip:</strong> Whisper a mage <code>cast Detect Magic</code> while targeting Anubisath Defender to reveal its randomly chosen abilities.</li>
            <li>This is another detect-magic pack where reflect handling matters just as much as raw tanking.</li>
            <li><code>26558 - Meteor</code>: stack the raid so the damage splits properly.</li>
            <li><code>26556 - Plague</code>: if a bot gets it, this can turn into heavy RTSC micro fast.</li>
            <li><code>25698 - Explode</code>: either finish the mob instantly or force the raid out before the cast ends.</li>
          </ul>
          <p><strong>Qiraji Champion Packs [NA]</strong></p>
          <ul>
            <li><strong>Qiraji Champion:</strong> fear and knock-away make walling and spacing important.</li>
            <li><strong>Qiraji Slayer [4]:</strong> whirlwind plus long silence can wipe a raid in seconds if it is not burned immediately.</li>
            <li><strong>Qiraji Mindslayer [NA]:</strong> the guide specifically calls out its bugged mana-burn behavior as one of the nastier technical limitations in the instance.</li>
          </ul>
          <p><strong>Warder And Nullifier Packs [3]</strong></p>
          <ul>
            <li><strong>Anubisath Warder:</strong> keep it away from the raid, especially if <code>Fire Nova</code> overlaps with other pressure.</li>
            <li><strong>Obsidian Nullifier:</strong> <code>26639 - Drain Mana</code> into <code>26552 - Nullify</code> can create instant wipe conditions if the raid is clumped and unprepared.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">AQ40</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p><strong>The Prophet Skeram [3]</strong></p>
          <ul>
            <li><code>28401 - Blink</code> at 75%, 50%, and 25% creates a tank and melee RTSC problem, not just a threat problem.</li>
            <li><code>785 - True Fulfillment</code> means you need an answer for MC targets.</li>
          </ul>
          <p><strong>The Bug Trio [3]</strong></p>
          <ul>
            <li><strong>Yauj:</strong> <code>26580 - Fear</code> is an AOE fear plus aggro reset. Counter it with warrior taunts and proper RTI management.</li>
            <li><strong>Yauj:</strong> <code>25807 - Great Heal</code> rewards creating space between the bugs with RTSC.</li>
            <li><strong>Yauj:</strong> <code>25808 - Dispel</code> is another reason to keep the bosses spaced instead of stacked in LOS.</li>
            <li><strong>Vem:</strong> <code>26561 - Berserker Charge</code> punishes bad RTSC LOS and can flatten anyone caught in the path.</li>
            <li><strong>Vem:</strong> <code>18670 - Knock Away</code> is much easier if the tank is set against a wall.</li>
            <li><strong>Lord Kri:</strong> <code>25812 - Toxic Volley</code> is the nature-resistance pressure point and a big reason to kill Kri first.</li>
            <li><strong>Lord Kri:</strong> <code>26590 - Summon Poison Cloud</code> is easy only if Kri dies in a safe spot.</li>
          </ul>
          <p><strong>Battleguard Sartura [4]</strong></p>
          <ul>
            <li><code>26038 - Whirlwind</code> turns the whole fight into RTSC choreography.</li>
            <li>The guide's shape is explicit: melee in the center, ranged on the inner rim, healers on the outer rim.</li>
          </ul>
          <p><strong>Fankriss [2]</strong>, <strong>Viscidus [4]</strong>, <strong>Princess Huhuran [4]</strong></p>
          <ul>
            <li><strong>Fankriss:</strong> <code>25646 - Mortal Wound</code> is a normal tank-swap check.</li>
            <li><strong>Fankriss:</strong> <code>518 - Summon Worm</code> means Spawn of Fankriss needs immediate RTI focus.</li>
            <li><strong>Viscidus:</strong> <code>25991 - Poison Bolt Volley</code> is constant raid poison pressure and pushes cleanse classes hard.</li>
            <li><strong>Viscidus:</strong> <code>25989 - Toxin</code> is manageable if people are not parked in bad ground.</li>
            <li><strong>Huhuran:</strong> <code>26052 - Poison Bolt</code> on the nearest 15 targets is the big nature-resistance and healer-depth gate.</li>
            <li><strong>Huhuran:</strong> <code>26051 - Frenzy</code> still needs Tranquilizing Shot.</li>
            <li><strong>Huhuran:</strong> <code>26050 - Acid Spit</code> means tank swaps matter.</li>
            <li><strong>Huhuran:</strong> <code>26180 - Wyvern Sting</code> is usually manageable, but it can still cause deaths if cleanses and healing get awkward.</li>
            <li><strong>Huhuran:</strong> <code>26053 - Noxious Poison</code> punishes bad spread and bad silence positioning.</li>
            <li><strong>Huhuran:</strong> <code>26068 - Berserk</code> at 30% is the cue for cooldowns and a fast finish.</li>
          </ul>
          <p><strong>Twin Emperors [5]</strong></p>
          <ul>
            <li><code>7393 - Heal Brother</code>: if the twins get too close, the attempt is over almost instantly.</li>
            <li><code>800 - Twin Teleport</code>: the guide treats this as a full pre-planned macro encounter with warrior tanks, warlock tanks, and bug-control assignments.</li>
            <li><strong>Vek'lor:</strong> <code>26006 - Shadow Bolt</code> is why the guide wants two shadow-resistant warlock tanks.</li>
            <li><strong>Vek'lor:</strong> <code>568 - Arcane Burst</code> can be used to help warrior tanks advance if they survive the opening pressure.</li>
            <li><strong>Vek'lor:</strong> <code>26607 - Blizzard</code> is manageable if positioning stays disciplined.</li>
            <li><strong>Vek'lor:</strong> <code>804 - Explode Bug</code> is a huge AOE punishment if the bug control falls apart.</li>
            <li><strong>Vek'nilash:</strong> <code>26613 - Unbalancing Strike</code> is a tank-defense problem supported by focus heals, Demo Shout, and Disarm.</li>
            <li><strong>Vek'nilash:</strong> <code>26007 - Uppercut</code> is mostly about not getting launched into Blizzard or bug explosions.</li>
            <li><strong>Vek'nilash:</strong> <code>802 - Mutate Bug</code> is why the guide recommends a dedicated bug-hunter group with specific RTIs.</li>
          </ul>
          <p><strong>Ouro [3]</strong></p>
          <ul>
            <li><code>26102 - Sand Blast</code> and underground <code>Quake</code> turn the fight into dynamic RTSC movement.</li>
            <li>At 20%, berserk overlaps with the normal control problem, so the fight accelerates hard.</li>
          </ul>
          <p><strong>C'Thun [5]</strong></p>
          <ul>
            <li><code>26134 - Eye Beam</code>: pre-planned spread is mandatory or the room chain-detonates.</li>
            <li><code>26029 - Dark Glare</code>: surprisingly manageable with a few prepared RTSC macros, but only if you are watching for it.</li>
            <li><strong>Phase 2 stomach:</strong> this is one of the most micro-heavy jobs in the whole guide. Kill flesh tentacles fast or the room gets overrun.</li>
            <li><strong>Giant Eye Tentacle [4]:</strong> treat it like phase 1 all over again and burn it immediately.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">Naxxramas</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p><strong>Naxxramas - Trash</strong></p>
          <ul>
            <li>TBD</li>
          </ul>
          <p><strong>Spider Wing</strong></p>
          <p><strong>Anub'rekhan [3]</strong></p>
          <ul>
            <li><code>28783 - Impale</code>: random target launch plus fall damage. Stable positioning and decent healing usually cover it.</li>
            <li><code>28785 - Locust Swarm</code>: the real fight. Kite around the room edge with RTSC, speed tools, and clean pathing.</li>
            <li>Each Locust cycle also spawns a Crypt Guard, so the movement plan has to account for pickup and space.</li>
          </ul>
          <p><strong>Faerlina [1]</strong></p>
          <ul>
            <li><code>28794 - Rain of Fire</code>: basic AOE placement check.</li>
            <li><code>19953 - Enrage</code>: after one minute she ramps up, but this is still one of the easier encounters in the wing.</li>
          </ul>
          <p><strong>Maexxna [2]</strong></p>
          <ul>
            <li><code>28776 - Necrotic Poison</code>: easy if cleanse classes are awake.</li>
            <li><code>29484 - Web Spray</code>: the whole raid is stunned for 8 seconds, so tank durability matters more than clever micro here.</li>
            <li><code>28747 - Enrage</code> at 30% is the burn window. If it overlaps badly with Web Spray, the tank can disappear fast.</li>
          </ul>
          <p><strong>Plague Wing</strong></p>
          <p><strong>Noth the Plaguebringer [1]</strong></p>
          <ul>
            <li><code>29211 - Blink</code>: straightforward threat reset as long as a tank is ready.</li>
            <li><code>29213 - Curse of the Plaguebringer</code>: decurse quickly and avoid letting it spread.</li>
            <li>Balcony teleports are more about keeping the add phases organized than about a single dangerous cast.</li>
          </ul>
          <p><strong>Heigan the Unclean [4]</strong></p>
          <ul>
            <li>The gauntlet before the boss is easier than the BWL suppression run, but it still sets the tone.</li>
            <li><code>14033 - Mana Burn</code>: manageable with normal positioning.</li>
            <li><code>29371 - Eruption</code>: this is the real fight. The guide treats it as a pure RTSC timing and execution check.</li>
          </ul>
          <p><strong>Loatheb [3]</strong></p>
          <ul>
            <li><code>29185, 29201, 29196, 29198 - Corrupted Mind</code>: healer rhythm is completely different here, so pre-pull setup matters more than normal instincts.</li>
            <li><code>29865 - Poison Aura</code>: Greater Nature Protection Potions help smooth it out.</li>
            <li><code>29204 - Inevitable Doom</code>: once the Doom cadence speeds up, the fight becomes a hard race.</li>
          </ul>
          <p><strong>Abomination Wing</strong></p>
          <p><strong>Patchwerk [2]</strong></p>
          <ul>
            <li><code>28308 - Hateful Strike</code>: mostly about proper tanks and focus healing. The guide notes CMaNGOS behavior is not perfectly blizzlike here because Hateful can still hit the MT.</li>
            <li><code>19953 - Enrage</code> at 5% is the signal to finish with cooldowns.</li>
          </ul>
          <p><strong>Grobbulus [3]</strong></p>
          <ul>
            <li><code>28240 - Poison Cloud</code>: kite the boss around the room and respect the route.</li>
            <li><code>28169 - Mutating Injection</code>: dynamic RTSC problem. Where the target runs determines whether the room stays playable.</li>
            <li><code>28157 - Slime Spray</code>: keep Grobbulus facing only the main tank.</li>
            <li><code>28137 - Slime Stream</code>: do not let the kite get too wide or too fast.</li>
          </ul>
          <p><strong>Gluth [NA]</strong></p>
          <ul>
            <li><strong>Note from author:</strong> Gluth is currently broken. <code>Decimate</code> does not decrease Zombie Chow HP, which makes the intended add cycle practically impossible.</li>
            <li><code>29685 - Terrifying Roar</code>: fear handling is standard if your wards and totems are ready.</li>
            <li><code>25646 - Mortal Wound</code>: another tank-swap check.</li>
            <li><code>28404 - Zombie Chow Search</code>: if the chow reaches Gluth, the boss heals.</li>
            <li><code>28375 - Decimate</code>: this is the bugged mechanic the guide flags as the reason the fight is effectively NA.</li>
          </ul>
          <p><strong>Thaddius [4]</strong></p>
          <ul>
            <li><strong>Note from author:</strong> Stalagg and Feugen can despawn permanently after a wipe, which breaks phase 1 and may require GM activation for Thaddius.</li>
            <li><strong>Stalagg and Feugen:</strong> kill timing and side assignments matter. The guide recommends four tanks total, with melee on Feugen's side and ranged on Stalagg's.</li>
            <li><code>28089 - Polarity Shift</code>: one of the heaviest micro checks in the raid. Mixed charges standing together can erase the raid instantly.</li>
          </ul>
          <p><strong>Death Knight Wing</strong></p>
          <p><strong>Instructor Razuvious [2]</strong></p>
          <ul>
            <li><code>26613 - Unbalancing Strike</code>: either mind-control understudies or treat spare warriors as meat shields.</li>
            <li><code>29107 - Disrupting Shout</code>: keep mana users safe with spacing and LOS discipline.</li>
          </ul>
          <p><strong>Gothik the Harvester [4]</strong></p>
          <ul>
            <li>The whole fight is about balancing your split between live and undead sides for 4 minutes and 30 seconds.</li>
            <li><code>15245 - Shadow Bolt Volley</code> from Unrelenting Riders is the scariest add cast and should be interrupted or the mob killed immediately.</li>
          </ul>
          <p><strong>Four Horsemen [5]</strong></p>
          <ul>
            <li><strong>Note from author:</strong> the guide is blunt here. For many players this is the wall, and it may not be worth brute-forcing without a highly refined setup.</li>
            <li><code>28834, 28832, 28833, 28835 - Marks</code>: the core mechanic. The room has to be split into corners and rotated cleanly.</li>
            <li><code>28884 - Meteor</code>: simple if Thane's group is stacked correctly.</li>
            <li><code>28882 - Righteous Fire</code>: Mograine tanks need to be sturdy.</li>
            <li><code>10320 - Holy Wrath</code>: Zeliek punishes sloppy spread the same way C'Thun punishes bad beam spacing.</li>
            <li><code>28863 - Void Zone</code>: the guide calls this one of the least forgiving interactions in the whole encounter because the movement correction can break the rest of the setup.</li>
          </ul>
          <p><strong>Frostwyrm Lair</strong></p>
          <p><strong>Sapphiron [4]</strong></p>
          <ul>
            <li><code>28531 - Frost Aura</code>: constant raid damage that makes frost resistance, healer depth, and potion use matter.</li>
            <li><code>28542 - Life Drain</code>: decurse quickly; Shadow Protection can help.</li>
            <li><code>28561 - Summon Blizzard</code>: dynamic positioning issue, though the guide notes brute resistance prep can matter more than elegant movement here.</li>
            <li><code>31800 - Icebolt</code>: spread correctly in the air phase so splash damage does not ruin the safe blocks.</li>
            <li><code>28524 - Frost Breath</code>: the defining wipe mechanic. Everyone has to collapse behind an ice block in time.</li>
          </ul>
          <p><strong>Kel'Thuzad [4]</strong></p>
          <ul>
            <li><strong>Note from author:</strong> phase 1 add volume can cause serious lag with a full bot raid.</li>
            <li><strong>Phase 1:</strong> melee should clear Unstoppable Abominations while ranged handle Soldiers of the Frozen Wastes and Soul Weavers.</li>
            <li><strong>Phase 2:</strong> <code>28478 - Frostbolt</code> should be interrupted, <code>28479 - Frostbolt Volley</code> is potion and resistance pressure, <code>28410 - Chains of Kel'Thuzad</code> needs CC, <code>27810 - Shadow Fissure</code> needs fast RTSC movement, <code>27808 - Frost Blast</code> demands fast healing, and <code>27819 - Detonate Mana</code> rewards proper ranged spread.</li>
            <li><strong>Phase 3:</strong> the phase 2 problems stay active while five Guardians of Icecrown spawn and need off-tank pickup, with up to three shackled by priests.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card collapse-card">
        <summary class="collapse-card__summary"><span class="collapse-card__copy"><span class="collapse-card__title">Known Vanilla Caveats</span></span><span class="collapse-card__caret vanilla-toggle" aria-hidden="true"></span></summary>
        <div class="vanilla-body collapse-card__body">
          <p>These notes matter because they tell you when a wipe source may be a core limitation rather than a player mistake.</p>
          <ul>
            <li><strong>Suppression Devices in BWL:</strong> rogue disarm support may not be reliable.</li>
            <li><strong>Gluth:</strong> <code>Decimate</code> may fail to reduce Zombie Chow health, breaking the intended add cycle.</li>
            <li><strong>Thaddius:</strong> Stalagg and Feugen can despawn after a wipe, and activation may need GM help depending on core state.</li>
            <li><strong>Kel'Thuzad phase 1:</strong> a full bot raid can cause major performance pressure from sheer add count.</li>
          </ul>
        </div>
      </details>

    </div>
  </div>

  <div id="tab-builder" class="sref-panel<?php echo $activeCommandTab === 'builder' ? ' active' : ''; ?>">
    <h3>Custom Strategies</h3>
    <p><code>custom::&lt;name&gt;</code> is a database-driven trigger to action pipeline you define yourself. Each line maps one trigger to one or more actions, and the first matching line fires.</p>
    <p><strong>Syntax:</strong> <code>trigger&gt;action1!priority,action2!priority</code>. Use <code>say::text_name</code> to speak a DB text name and <code>emote::emote_name</code> to perform an emote.</p>
    <p><strong>In-game editing:</strong> whisper the bot <code>cs &lt;name&gt; &lt;idx&gt; &lt;action_line&gt;</code> to set a line, <code>cs &lt;name&gt; &lt;idx&gt;</code> to delete, and <code>cs &lt;name&gt; ?</code> to list.</p>
    <hr class="csb-sep">

    <div class="csb-section">
      <div class="csb-row">
        <span class="csb-label">Name</span>
        <input id="csb-name" class="csb-input" type="text" placeholder="e.g. pvpcall" value="mysay" data-action="csb-update-output">
        <span class="csb-hint">Activated as <code id="csb-activation-preview">+custom::mysay</code></span>
      </div>
      <div class="csb-row">
        <span class="csb-label">Scope</span>
        <div class="csb-scope-row">
          <label class="csb-scope-label">
            <input class="csb-radio" type="radio" name="csb-owner" value="0" checked data-action="csb-toggle-guid"> Global (all bots)
          </label>
          <label class="csb-scope-label">
            <input class="csb-radio" type="radio" name="csb-owner" value="guid" data-action="csb-toggle-guid"> Specific bot (GUID)
          </label>
          <input id="csb-guid" class="csb-input csb-priority csb-guid is-hidden" type="text" placeholder="GUID" data-action="csb-update-output">
        </div>
      </div>
    </div>

    <div id="csb-lines"></div>
    <button type="button" class="csb-btn csb-btn-add csb-add-line" data-action="csb-add-line">+ Add Line</button>

    <hr class="csb-sep">
    <h3>Output</h3>
    <div class="csb-output-label">Activation string</div>
    <div class="csb-copy-row">
      <div class="csb-output is-activation" id="csb-out-activation"></div>
      <button type="button" class="csb-btn csb-btn-copy" data-copy-target="csb-out-activation">Copy</button>
    </div>
    <div class="csb-output-label">SQL INSERT (paste into MariaDB)</div>
    <div class="csb-copy-row">
      <div class="csb-output is-inline-fill" id="csb-out-sql"></div>
      <button type="button" class="csb-btn csb-btn-copy" data-copy-target="csb-out-sql">Copy</button>
    </div>
    <div class="csb-output-label">In-game cs commands (whisper bot)</div>
    <div class="csb-copy-row">
      <div class="csb-output is-inline-fill" id="csb-out-cs"></div>
      <button type="button" class="csb-btn csb-btn-copy" data-copy-target="csb-out-cs">Copy</button>
    </div>
  </div>

  <div id="tab-wbuffs" class="sref-panel<?php echo $activeCommandTab === 'wbuffs' ? ' active' : ''; ?>">
    <div
      id="wbuilder-app"
      class="modern-content wb-page"
      data-wb-classes="<?php echo htmlspecialchars(json_encode($worldBuffClasses ?? array()), ENT_QUOTES); ?>"
      data-wb-spells="<?php echo htmlspecialchars(json_encode($worldBuffSpellCatalog ?? array()), ENT_QUOTES); ?>"
      data-wb-default-preset="warrior_2"
    >
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
  </div>

  <div id="tab-macros" class="sref-panel<?php echo $activeCommandTab === 'macros' ? ' active' : ''; ?>">
      <h3>Macro Builder</h3>
      <p>Build ready-to-use bot control macros. Add any filters you want, choose how the command should be sent, pick a preset, and copy the finished macro straight into the game.</p>

      <div class="mb-help-grid">
        <div class="mb-help-card">
          <h4>Macro Shape</h4>
          <p><code>/w BotName @tank @60 follow</code> or <code>/ra @warrior co +dps,+threat</code></p>
        </div>
        <div class="mb-help-card">
          <h4>Multiple Filters</h4>
          <p>Stack filters in order to target exactly the bots you want before the command executes.</p>
        </div>
        <div class="mb-help-card">
        <h4>Preset Focus</h4>
        <p>Use presets for common movement, utility, and strategy commands, or switch to the custom preset for raw command text.</p>
      </div>
    </div>

      <div class="mb-card">
        <h3 class="sref-section-title">Send</h3>
        <div class="mb-row">
          <span class="csb-label">Send</span>
          <div class="mb-row-fields">
            <select id="mb-delivery" class="csb-select" data-action="mb-update-delivery">
              <option value="whisper">/w whisper</option>
              <option value="party">/p party</option>
              <option value="raid">/ra raid</option>
              <option value="guild">/g guild</option>
              <option value="say">/s say</option>
            </select>
          </div>
        </div>
        <div class="mb-row" id="mb-target-row">
          <span class="csb-label">Bot</span>
          <div class="mb-row-fields">
            <input id="mb-target" class="csb-input" type="text" placeholder="BotName" data-action="mb-update-output">
          </div>
        </div>
      </div>

      <div class="mb-card">
          <h3 class="sref-section-title">Filters</h3>
          <p>Add filters in the order you want them to appear before the command text.</p>
          <div class="mb-quick-groups">
            <div class="mb-quick-group">
              <span class="mb-quick-label">Roles</span>
            <div class="mb-quick-buttons">
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@tank">tank</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@dps">dps</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@heal">heal</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@melee">melee</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@ranged">ranged</button>
            </div>
          </div>
          <div class="mb-quick-group">
            <span class="mb-quick-label">Class</span>
            <div class="mb-quick-buttons">
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@warrior">warrior</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@paladin">paladin</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@hunter">hunter</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@rogue">rogue</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@priest">priest</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@shaman">shaman</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@mage">mage</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@warlock">warlock</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@druid">druid</button>
              <button type="button" class="mb-quick-btn" data-action="mb-add-quick-filter" data-filter-token="@deathknight">death knight</button>
            </div>
          </div>
        </div>
          <div id="mb-filters" class="mb-filter-list"></div>
          <button type="button" class="csb-btn csb-btn-add" data-action="mb-add-filter">+ Add Filter</button>
      </div>

      <div class="mb-card">
        <h3 class="sref-section-title">Builder</h3>
        <div id="mb-layers"></div>
        <button type="button" class="csb-btn csb-btn-add" data-action="mb-add-layer">+ Add Layer</button>
        <div class="mb-status" id="mb-status">Choose how to send the macro, add any filters you want, and pick a command preset.</div>
      </div>

      <div class="mb-card">
        <h3 class="sref-section-title">Output</h3>
        <div class="csb-output-label">Final macro</div>
      <div class="csb-copy-row">
        <div class="csb-output is-inline-fill" id="mb-out-macro"></div>
        <button type="button" class="csb-btn csb-btn-copy" data-copy-target="mb-out-macro">Copy</button>
      </div>
      <div class="csb-output-label">Command preview</div>
      <div class="csb-copy-row">
        <div class="csb-output is-inline-fill" id="mb-out-command"></div>
        <button type="button" class="csb-btn csb-btn-copy" data-copy-target="mb-out-command">Copy</button>
      </div>
    </div>
  </div>
</div>

<datalist id="csb-say-list">
  <option value="critical health"><option value="low health"><option value="low mana">
  <option value="aoe"><option value="taunt"><option value="attacking"><option value="fleeing">
  <option value="fleeing_far"><option value="following"><option value="staying"><option value="guarding">
  <option value="grinding"><option value="loot"><option value="hello"><option value="goodbye">
  <option value="join_group"><option value="join_raid"><option value="no ammo"><option value="low ammo">
  <option value="reply"><option value="suggest_trade"><option value="suggest_something">
  <option value="broadcast_levelup_generic"><option value="broadcast_killed_player">
  <option value="broadcast_killed_elite"><option value="broadcast_killed_worldboss">
  <option value="broadcast_quest_turned_in"><option value="broadcast_looting_item_epic">
  <option value="broadcast_looting_item_legendary"><option value="broadcast_looting_item_rare">
  <option value="quest_accept"><option value="quest_remove"><option value="quest_status_completed">
  <option value="quest_error_bag_full"><option value="use_command"><option value="equip_command">
  <option value="error_far"><option value="wait_travel_close"><option value="wait_travel_far">
</datalist>

<datalist id="csb-emote-list">
  <option value="helpme"><option value="healme"><option value="flee"><option value="charge">
  <option value="danger"><option value="oom"><option value="openfire"><option value="wait">
  <option value="follow"><option value="train"><option value="joke"><option value="silly">
  <option value="hug"><option value="kneel"><option value="kiss"><option value="point">
  <option value="roar"><option value="rude"><option value="chicken"><option value="flirt">
  <option value="introduce"><option value="anecdote"><option value="dance"><option value="bow">
  <option value="cheer"><option value="cry"><option value="laugh"><option value="wave">
  <option value="salute"><option value="flex"><option value="no"><option value="yes">
  <option value="beg"><option value="applaud"><option value="sleep"><option value="shy"><option value="talk">
</datalist>

<template id="botcommands-client-state"><?php echo htmlspecialchars($botcommandsClientStateJson ?? '', ENT_NOQUOTES); ?></template>

<?php builddiv_end(); ?>
