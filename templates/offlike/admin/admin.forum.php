<br>
<?php builddiv_start(0, $lang['forums'] ?? 'Forums') ?>
<div class="forum-admin feature-shell">
<?php
$forumRequest = (array)($request ?? array());
$forumId = (int)($forumRequest['forum_id'] ?? 0);
$topicId = (int)($forumRequest['topic_id'] ?? 0);
$categoryId = (int)($forumRequest['cat_id'] ?? 0);
$forumCsrfToken = (string)($forum_admin_csrf_token ?? spp_csrf_token('admin_forum'));
$forumOrderLabel = (string)($lang['order'] ?? 'Order');
$forumNameLabel = (string)($lang['l_name'] ?? 'Name');
$forumDescriptionLabel = (string)($lang['l_desc'] ?? 'Description');
$forumConfirmLabel = (string)($lang['sure_q'] ?? 'Are you sure?');
$forumNotice = trim((string)($forum_notice ?? ''));
$realmForumTools = (array)($realm_forum_tools ?? array());
?>
<?php if ($forumNotice !== '') { ?>
  <div class="playerbots-success"><?php echo htmlspecialchars($forumNotice); ?></div>
<?php } ?>
<?php if ($view_mode === 'topic') { ?>
    <div class="forum-admin__card feature-panel">
      <h3>Topic Posts</h3>
      <p class="forum-admin__subtext">Reviewing <strong><?php echo htmlspecialchars($this_topic['topic_name']); ?></strong> inside <strong><?php echo htmlspecialchars($this_forum['forum_name']); ?></strong>.</p>
      <div class="forum-admin__actions forum-admin__actions--bottom">
        <a class="forum-admin__pill" href="index.php?n=admin&amp;sub=forum&amp;forum_id=<?php echo $forumId; ?>">Back to Topics</a>
        <a class="forum-admin__pill" href="index.php?n=forum&amp;sub=viewtopic&amp;tid=<?php echo $topicId; ?>" target="_blank">View Topic</a>
        <?php echo spp_admin_forum_action_button(array('forum_id' => $forumId, 'topic_id' => $topicId, 'action' => 'deletetopic'), 'Delete Topic', $forumCsrfToken, 'forum-admin__pill forum-admin__pill--danger', 'Delete entire topic and all its posts?'); ?>
      </div>
      <table class="forum-admin__table">
        <thead><tr><th>Post</th><th>Author</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item) { ?>
          <tr>
            <td><?php echo nl2br(htmlspecialchars($item['excerpt'])); ?><?php if (strlen($item['excerpt']) >= 120) echo '...'; ?></td>
            <td><?php echo htmlspecialchars($item['poster']); ?></td>
            <td><?php echo date('M d, Y H:i', $item['posted']); ?></td>
            <td><?php echo spp_admin_forum_action_button(array('forum_id' => $forumId, 'topic_id' => $topicId, 'post_id' => (int)$item['post_id'], 'action' => 'deletepost'), 'Delete', $forumCsrfToken, 'forum-admin__pill forum-admin__pill--danger', 'Delete this post?'); ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } elseif ($view_mode === 'forum') { ?>
    <div class="forum-admin__card feature-panel">
      <h3>Forum Topics</h3>
      <p class="forum-admin__subtext">Managing topics inside <strong><?php echo htmlspecialchars($this_forum['forum_name']); ?></strong>.</p>
      <div class="forum-admin__actions forum-admin__actions--bottom">
        <a class="forum-admin__pill" href="index.php?n=admin&amp;sub=forum">Back to Categories</a>
      </div>
      <table class="forum-admin__table">
        <thead><tr><th>Topic</th><th>Posted By</th><th>Date</th><th>Replies</th><th>Action</th></tr></thead>
        <tbody>
        <?php if (empty($items)) { ?><tr><td colspan="5"><em>No topics yet.</em></td></tr><?php } ?>
        <?php foreach ($items as $item) { ?>
          <tr>
            <td><a class="forum-admin__title" href="index.php?n=admin&amp;sub=forum&amp;forum_id=<?php echo $forumId; ?>&amp;topic_id=<?php echo (int)$item['topic_id']; ?>"><?php echo htmlspecialchars($item['topic_name']); ?></a></td>
            <td><?php echo htmlspecialchars($item['topic_poster']); ?></td>
            <td><?php echo date('M d, Y', $item['topic_posted']); ?></td>
            <td><?php echo (int)$item['num_replies']; ?></td>
            <td><?php echo spp_admin_forum_action_button(array('forum_id' => $forumId, 'topic_id' => (int)$item['topic_id'], 'action' => 'deletetopic'), 'Delete', $forumCsrfToken, 'forum-admin__pill forum-admin__pill--danger', 'Delete this topic and all its posts?'); ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } elseif ($view_mode === 'category') { ?>
    <div class="forum-admin__card feature-panel">
      <h3>Forums In Section</h3>
      <p class="forum-admin__subtext">Tune ordering, visibility, and topic access from one cleaner view.</p>
      <div class="forum-admin__stack">
        <?php foreach ($items as $item_c => $item) { ?>
          <div class="forum-admin__row">
            <div class="forum-admin__main">
              <p class="forum-admin__title"><a href="index.php?n=admin&amp;sub=forum&amp;forum_id=<?php echo (int)$item['forum_id']; ?>"><?php echo htmlspecialchars($item['forum_name']); ?></a></p>
              <form method="post" action="index.php?n=admin&amp;sub=forum" class="forum-admin__rename">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($forumCsrfToken); ?>">
                <input type="hidden" name="action" value="renameforum">
                <input type="hidden" name="forum_id" value="<?php echo (int)$item['forum_id']; ?>">
                <input type="text" name="forum_name" value="<?php echo htmlspecialchars($item['forum_name']); ?>">
                <input class="forum-admin__button forum-admin__button--compact" type="submit" value="Rename">
              </form>
              <p class="forum-admin__desc"><?php echo htmlspecialchars($item['forum_desc']); ?></p>
              <form method="post" action="index.php?n=admin&amp;sub=forum" class="forum-admin__order">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($forumCsrfToken); ?>">
                <input type="hidden" name="action" value="updforumsorder">
                <input type="hidden" name="cat_id" value="<?php echo (int)$item['cat_id']; ?>">
                <span><?php echo htmlspecialchars($forumOrderLabel); ?></span>
                <input type="text" name="forumorder[<?php echo (int)$item['forum_id']; ?>]" value="<?php echo (int)$item['disp_position']; ?>">
                <input class="forum-admin__button forum-admin__button--compact" type="submit" value="Save Order">
              </form>
              <div class="forum-admin__actions forum-admin__actions--bottom">
                <?php if ($item_c > 0) { echo spp_admin_forum_action_button(array('action' => 'moveup', 'cat_id' => (int)$item['cat_id'], 'forum_id' => (int)$item['forum_id']), 'Move Up', $forumCsrfToken); } ?>
                <?php if ($item_c < count($items) - 1) { echo spp_admin_forum_action_button(array('action' => 'movedown', 'cat_id' => (int)$item['cat_id'], 'forum_id' => (int)$item['forum_id']), 'Move Down', $forumCsrfToken); } ?>
              </div>
            </div>
            <div class="forum-admin__actions">
              <?php if ($item['closed'] == 0) { echo spp_admin_forum_action_button(array('action' => 'close', 'forum_id' => (int)$item['forum_id'], 'cat_id' => (int)$item['cat_id']), 'Close', $forumCsrfToken); } ?>
              <?php if ($item['closed'] == 1) { echo spp_admin_forum_action_button(array('action' => 'open', 'forum_id' => (int)$item['forum_id'], 'cat_id' => (int)$item['cat_id']), 'Open', $forumCsrfToken); } ?>
              <?php if ($item['hidden'] == 0) { echo spp_admin_forum_action_button(array('action' => 'hide', 'forum_id' => (int)$item['forum_id'], 'cat_id' => (int)$item['cat_id']), 'Hide', $forumCsrfToken); } ?>
              <?php if ($item['hidden'] == 1) { echo spp_admin_forum_action_button(array('action' => 'show', 'forum_id' => (int)$item['forum_id'], 'cat_id' => (int)$item['cat_id']), 'Show', $forumCsrfToken); } ?>
              <?php echo spp_admin_forum_action_button(array('action' => 'recount', 'forum_id' => (int)$item['forum_id'], 'cat_id' => (int)$item['cat_id']), 'Recount', $forumCsrfToken); ?>
              <a class="forum-admin__pill" href="index.php?n=admin&amp;sub=forum&amp;forum_id=<?php echo (int)$item['forum_id']; ?>">Topics</a>
              <?php echo spp_admin_forum_action_button(array('action' => 'deleteforum', 'forum_id' => (int)$item['forum_id'], 'cat_id' => (int)$item['cat_id']), 'Delete', $forumCsrfToken, 'forum-admin__pill forum-admin__pill--danger', $forumConfirmLabel); ?>
            </div>
          </div>
        <?php } ?>
      </div>
      <p class="forum-admin__subtext forum-admin__subtext--top-gap">Order is controlled by <strong>disp_position</strong>. Lower numbers appear first. If two forums share the same value, the current view falls back to <strong>forum_name</strong> as a tiebreaker.</p>
    </div>

    <div class="forum-admin__card feature-panel">
      <h3>Create New Forum</h3>
      <form method="post" action="index.php?n=admin&amp;sub=forum" class="forum-admin__grid-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($forumCsrfToken); ?>">
        <input type="hidden" name="action" value="newforum">
        <input type="hidden" name="cat_id" value="<?php echo $categoryId; ?>">
        <div class="forum-admin__field"><label><?php echo htmlspecialchars($forumNameLabel); ?></label><input type="text" name="forum_name"></div>
        <div class="forum-admin__field forum-admin__field--wide"><label><?php echo htmlspecialchars($forumDescriptionLabel); ?></label><input type="text" name="forum_desc"></div>
        <div class="forum-admin__field"><label><?php echo htmlspecialchars($forumOrderLabel); ?></label><input type="text" name="disp_position" value="<?php echo count($items) + 1; ?>"></div>
        <div class="forum-admin__actions forum-admin__actions--full"><input class="forum-admin__button" type="submit" value="Create New Forum"></div>
      </form>
    </div>
  <?php } else { ?>
    <div class="forum-admin__intro feature-hero">
      Forum sections are your top-level buckets. Realm-specific spaces can live as scoped forums inside each section.
    </div>
    <?php if (!empty($realmForumTools['realm_options'])) { ?>
    <div class="forum-admin__card feature-panel">
      <h3>Realm Forum Tools</h3>
      <p class="forum-admin__subtext">Use these easy buttons to create, remove, or reset the standard realm forum set for each world without losing the granular controls below.</p>
      <div class="forum-admin__stack">
        <?php foreach ((array)($realmForumTools['realm_summaries'] ?? array()) as $realmSummary) { ?>
          <div class="forum-admin__row">
            <div class="forum-admin__main">
              <p class="forum-admin__title"><?php echo htmlspecialchars((string)($realmSummary['realm_name'] ?? 'Realm')); ?></p>
              <p class="forum-admin__desc">
                Managed forums found: <?php echo (int)($realmSummary['managed_forum_count'] ?? 0); ?>
                <?php if (!empty($realmSummary['managed_forums'])) { ?>
                  | <?php echo htmlspecialchars(implode(', ', array_map(static function ($item) { return (string)($item['forum_name'] ?? ''); }, (array)$realmSummary['managed_forums']))); ?>
                <?php } ?>
              </p>
            </div>
            <div class="forum-admin__actions">
              <?php echo spp_admin_forum_action_button(array('action' => 'spawnrealmforums', 'realm_id' => (int)($realmSummary['realm_id'] ?? 0)), 'Spawn Forum Set', $forumCsrfToken, 'forum-admin__pill'); ?>
              <?php echo spp_admin_forum_action_button(array('action' => 'resetrealmforums', 'realm_id' => (int)($realmSummary['realm_id'] ?? 0)), 'Reset Forum Set', $forumCsrfToken, 'forum-admin__pill', 'Delete and recreate the managed forum set for this realm?'); ?>
              <?php echo spp_admin_forum_action_button(array('action' => 'removerealmforums', 'realm_id' => (int)($realmSummary['realm_id'] ?? 0)), 'Remove Forum Set', $forumCsrfToken, 'forum-admin__pill forum-admin__pill--danger', 'Delete the managed forum set for this realm?'); ?>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
    <?php } ?>
    <div class="forum-admin__card feature-panel">
      <h3>Forum Sections</h3>
      <div class="forum-admin__stack">
        <?php foreach ($items as $item_c => $item) { ?>
          <div class="forum-admin__row">
            <div class="forum-admin__main">
              <p class="forum-admin__title"><a href="index.php?n=admin&amp;sub=forum&amp;cat_id=<?php echo (int)$item['cat_id']; ?>"><?php echo htmlspecialchars($item['cat_name']); ?></a></p>
              <form method="post" action="index.php?n=admin&amp;sub=forum" class="forum-admin__rename">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($forumCsrfToken); ?>">
                <input type="hidden" name="action" value="renamecat">
                <input type="hidden" name="cat_id" value="<?php echo (int)$item['cat_id']; ?>">
                <input type="text" name="cat_name" value="<?php echo htmlspecialchars($item['cat_name']); ?>">
                <input class="forum-admin__button forum-admin__button--compact" type="submit" value="Rename">
              </form>
              <div class="forum-admin__order">
                <span><?php echo htmlspecialchars($forumOrderLabel); ?></span>
                <input type="text" value="<?php echo (int)$item['cat_disp_position']; ?>" readonly>
                <?php if ($item_c > 0) { echo spp_admin_forum_action_button(array('action' => 'moveup', 'cat_id' => (int)$item['cat_id']), 'Move Up', $forumCsrfToken); } ?>
                <?php if ($item_c < count($items) - 1) { echo spp_admin_forum_action_button(array('action' => 'movedown', 'cat_id' => (int)$item['cat_id']), 'Move Down', $forumCsrfToken); } ?>
              </div>
            </div>
            <div class="forum-admin__actions">
              <a class="forum-admin__pill" href="index.php?n=admin&amp;sub=forum&amp;cat_id=<?php echo (int)$item['cat_id']; ?>">Open Forums</a>
              <?php echo spp_admin_forum_action_button(array('action' => 'deletecat', 'cat_id' => (int)$item['cat_id']), 'Delete', $forumCsrfToken, 'forum-admin__pill forum-admin__pill--danger', $forumConfirmLabel); ?>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
    <div class="forum-admin__card feature-panel">
      <h3>Create New Forum Section</h3>
      <form method="post" action="index.php?n=admin&amp;sub=forum" class="forum-admin__grid-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($forumCsrfToken); ?>">
        <input type="hidden" name="action" value="newcat">
        <div class="forum-admin__field"><label><?php echo htmlspecialchars($forumNameLabel); ?></label><input type="text" name="cat_name"></div>
        <div class="forum-admin__field"><label><?php echo htmlspecialchars($forumOrderLabel); ?></label><input type="text" name="cat_disp_position" value="<?php echo count($items) + 1; ?>"></div>
        <div class="forum-admin__actions forum-admin__actions--full"><input class="forum-admin__button" type="submit" value="Create New Forum Section"></div>
      </form>
    </div>
  <?php } ?>
</div>
<?php builddiv_end() ?>
