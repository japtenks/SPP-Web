<?php
$GLOBALS['builddiv_header_actions'] = '<a href="index.php?n=account&sub=userlist" class="btn secondary">User List</a>';
builddiv_start(1, 'Account');
?>
<?php if($user['id']>0 && $profile){ ?>
<div class="modern-content member-profile feature-shell">
    <div class="member-hero feature-hero">
        <div class="member-identity">
            <div class="member-avatar">
                <?php if(!empty($profile['avatar'])) { ?>
                    <img src="uploads/avatars/<?php echo htmlspecialchars($profile['avatar']); ?>" alt="<?php echo htmlspecialchars($profile['username']); ?>">
                <?php } elseif(!empty($profile['avatar_fallback_url'])) { ?>
                    <img src="<?php echo htmlspecialchars($profile['avatar_fallback_url']); ?>" alt="<?php echo htmlspecialchars($profile['username']); ?>">
                <?php } else { ?>
                    <div class="member-avatar-placeholder"><?php echo strtoupper(substr($profile['username'], 0, 1)); ?></div>
                <?php } ?>
            </div>
            <div class="member-copy">
                <div class="member-kicker feature-eyebrow">Member Profile</div>
                <h2><?php echo htmlspecialchars($profile['username']); ?></h2>
                <div class="member-subline">
                    <span><?php echo htmlspecialchars($profile['expansion_label']); ?></span>
                </div>
            </div>
        </div>
        <div class="member-actions">
            <?php if(!empty($profile['is_own_profile'])): ?>
            <a class="feature-button is-primary" href="index.php?n=account&sub=manage">Edit Profile</a>
            <?php else: ?>
            <a class="feature-button is-primary" href="index.php?n=account&sub=pms&action=add&to=<?php echo urlencode($profile['username']); ?>">Personal Message</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="member-grid">
        <section class="member-panel feature-panel">
            <div class="member-panel-label feature-eyebrow">Account Snapshot</div>
            <div class="member-stat-list">
                <div class="member-stat-item">
                    <span class="member-stat-label">Registered</span>
                    <span class="member-stat-value"><?php echo htmlspecialchars($profile['joindate'] ?? '-'); ?></span>
                </div>
                <div class="member-stat-item">
                    <span class="member-stat-label">Forum Posts</span>
                    <span class="member-stat-value"><?php echo (int)($profile['forum_posts'] ?? 0); ?></span>
                </div>
                <div class="member-stat-item">
                    <span class="member-stat-label">Total Played</span>
                    <span class="member-stat-value"><?php echo htmlspecialchars($profile['total_played_label'] ?? '0m'); ?></span>
                </div>
                <div class="member-stat-item">
                    <span class="member-stat-label">Total Characters</span>
                    <span class="member-stat-value"><?php echo (int)($profile['character_count'] ?? 0); ?></span>
                </div>
                <div class="member-stat-item">
                    <span class="member-stat-label">Game Access</span>
                    <span class="member-stat-value"><?php echo htmlspecialchars($profile['expansion_label']); ?></span>
                </div>
            </div>
        </section>

        <section class="member-panel feature-panel">
            <div class="member-panel-label feature-eyebrow">Selected Forum Character</div>
            <?php if(!empty($profile['selected_forum_character'])): ?>
                <div class="member-character-card">
                    <div class="member-character-name"><?php echo htmlspecialchars($profile['selected_forum_character']['name']); ?></div>
                    <?php if(!empty($profile['selected_forum_character']['level'])): ?>
                        <div class="member-character-meta">Level <?php echo (int)$profile['selected_forum_character']['level']; ?></div>
                    <?php endif; ?>
                    <?php if(!empty($profile['selected_forum_character']['guild'])): ?>
                        <div class="member-character-meta">&lt;<?php echo htmlspecialchars($profile['selected_forum_character']['guild']); ?>&gt;</div>
                    <?php endif; ?>
                    <?php if(!empty($profile['selected_forum_character']['realm'])): ?>
                        <div class="member-character-meta"><?php echo htmlspecialchars($profile['selected_forum_character']['realm']); ?></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="member-empty-copy feature-copy">No forum character selected yet.</div>
            <?php endif; ?>
        </section>
    </div>

    <?php if(!empty($profile['is_human_account'])): ?>
    <section class="member-panel feature-panel">
        <div class="member-panel-label feature-eyebrow">Chars</div>
        <div class="member-chars-groups">
            <?php foreach(($profile['grouped_characters'] ?? array()) as $realmLabel => $realmChars): ?>
                <div class="member-chars-group">
                    <div class="member-chars-group-title"><?php echo htmlspecialchars($realmLabel); ?></div>
                    <?php if(!empty($realmChars)): ?>
                        <div class="member-chars-list">
                            <?php foreach($realmChars as $realmChar): ?>
                                <div class="member-chars-item">
                                    <div class="member-chars-item-main"><?php echo htmlspecialchars($realmChar['name']); ?></div>
                                    <div class="member-chars-item-meta">
                                        Level <?php echo (int)$realmChar['level']; ?>
                                        <?php if(!empty($realmChar['guild'])): ?>
                                            &middot; &lt;<?php echo htmlspecialchars($realmChar['guild']); ?>&gt;
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="member-empty-copy feature-copy">No characters yet.</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div>
<?php } ?>
<?php builddiv_end() ?>

