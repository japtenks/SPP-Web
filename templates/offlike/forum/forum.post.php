<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

$action = $_GET['action'] ?? '';
$forumPostMode = $forum_post_mode ?? (($action === 'newtopic' || $action === 'donewtopic') ? 'newtopic' : 'reply');
$is_newtopic = ($forumPostMode === 'newtopic');
$pageTitle = $is_newtopic ? 'Create New Topic' : 'Reply to Thread';
$forumId = $this_forum['forum_id'] ?? 0;
$topicId = $this_topic['topic_id'] ?? 0;
$forumUrl = spp_forum_url('viewforum', array('fid' => $forumId));
$indexUrl = spp_forum_url();
$topicUrl = $topicId > 0 ? spp_forum_url('viewtopic', array('tid' => $topicId)) : $forumUrl;
$formAction = $is_newtopic
    ? spp_forum_url('post', array('action' => 'donewtopic', 'f' => $forumId))
    : spp_forum_url('post', array('action' => 'donewpost', 't' => $topicId, 'f' => $forumId));
$contextFormAction = $is_newtopic
    ? spp_forum_url('post', array('action' => 'newtopic', 'fid' => $forumId))
    : spp_forum_url('post', array('action' => 'newpost', 't' => $topicId, 'fid' => $forumId));
$postingContext = $posting_context ?? array();
$postingForumName = trim((string)($postingContext['forum_name'] ?? ($this_forum['forum_name'] ?? 'Unknown Forum')));
$postingRealmName = trim((string)($postingContext['realm_name'] ?? ''));
$postingScopeLabel = trim((string)($postingContext['forum_scope_label'] ?? ''));
$postingCharacterName = trim((string)($postingContext['character_name'] ?? ''));
$postingCharacterLevel = (int)($postingContext['character_level'] ?? 0);
$postingGuildName = trim((string)($postingContext['guild_name'] ?? ''));
$postingCharacterOptions = $posting_character_options ?? array();
$postingPublisherLabel = trim((string)($postingContext['publisher_label'] ?? ''));
$newsPublisherOptions = $news_publisher_options ?? array();
$isNewsComposer = !empty($newsPublisherOptions);
?>

<?php builddiv_start(1, $pageTitle); ?>

<div class="reply-nav">
  <?php if (!$is_newtopic && $topicId > 0): ?>
    <a href="<?php echo $topicUrl; ?>" class="btn secondary">Back to Thread</a>
  <?php endif; ?>
  <a href="<?php echo $forumUrl; ?>" class="btn secondary">Back to Forum</a>
  <a href="<?php echo $indexUrl; ?>" class="btn secondary">Forum Index</a>
</div>

<section class="posting-context">
  <div class="posting-context-head">
    <div>
      <h3><?php echo $is_newtopic ? 'Posting Context' : 'Reply Context'; ?></h3>
      <p><?php echo $is_newtopic ? 'You are creating a new topic in this forum.' : 'Your reply will use this forum and character context.'; ?></p>
    </div>
    <div class="posting-context-badges">
      <?php if ($postingRealmName !== ''): ?>
        <span class="posting-context-badge"><?php echo htmlspecialchars($postingRealmName); ?></span>
      <?php endif; ?>
      <?php if ($postingScopeLabel !== ''): ?>
        <span class="posting-context-badge"><?php echo htmlspecialchars($postingScopeLabel); ?></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="posting-context-grid">
    <div class="posting-context-item">
      <span class="posting-context-label">Forum</span>
      <div class="posting-context-value"><?php echo htmlspecialchars($postingForumName); ?></div>
      <div class="posting-context-subvalue">This is the destination forum for your post.</div>
    </div>
    <div class="posting-context-item">
      <span class="posting-context-label"><?php echo $isNewsComposer ? 'Publishing As' : 'Posting As'; ?></span>
      <?php if ($isNewsComposer): ?>
        <form method="get" action="<?php echo htmlspecialchars($contextFormAction, ENT_QUOTES, 'UTF-8'); ?>" id="forum-character-context-form">
          <?php if (!$is_newtopic && $topicId > 0): ?>
            <input type="hidden" name="t" value="<?php echo (int)$topicId; ?>">
          <?php endif; ?>
          <select name="publisher_identity" class="posting-character-select" onchange="this.form.submit()">
          <?php foreach ($newsPublisherOptions as $publisherKey => $publisherLabel): ?>
            <option value="<?php echo htmlspecialchars((string)$publisherKey, ENT_QUOTES, 'UTF-8'); ?>"<?php if ((string)$publisherKey === (string)($forum_post_form['publisher_identity'] ?? '')) echo ' selected'; ?>>
              <?php echo htmlspecialchars((string)$publisherLabel); ?>
            </option>
          <?php endforeach; ?>
          </select>
        </form>
      <?php elseif (!empty($postingCharacterOptions)): ?>
        <form method="get" action="<?php echo htmlspecialchars($contextFormAction, ENT_QUOTES, 'UTF-8'); ?>" id="forum-character-context-form">
          <?php if (!$is_newtopic && $topicId > 0): ?>
            <input type="hidden" name="t" value="<?php echo (int)$topicId; ?>">
          <?php endif; ?>
          <select name="posting_character_id" class="posting-character-select" onchange="this.form.submit()">
          <?php foreach ($postingCharacterOptions as $postingCharacterOption): ?>
            <option value="<?php echo (int)$postingCharacterOption['guid']; ?>"<?php if ((int)$postingCharacterOption['guid'] === (int)($forum_post_form['posting_character_id'] ?? 0)) echo ' selected'; ?>>
              <?php
                echo htmlspecialchars(
                  $postingCharacterOption['name']
                  . ' (Lvl ' . (int)$postingCharacterOption['level'] . ')'
                  . (!empty($postingCharacterOption['guild']) ? ' - <' . $postingCharacterOption['guild'] . '>' : '')
                );
              ?>
            </option>
          <?php endforeach; ?>
          </select>
        </form>
      <?php else: ?>
        <div class="posting-context-value">No valid character selected</div>
      <?php endif; ?>
      <div class="posting-context-subvalue">
        <?php if ($isNewsComposer && $postingPublisherLabel !== ''): ?>
          This news post will publish as <?php echo htmlspecialchars($postingPublisherLabel); ?>.
        <?php elseif ($postingCharacterName !== ''): ?>
          Currently using <?php echo htmlspecialchars($postingCharacterName); ?>, level <?php echo $postingCharacterLevel; ?><?php if ($postingGuildName !== ''): ?> · &lt;<?php echo htmlspecialchars($postingGuildName); ?>&gt;<?php endif; ?>
        <?php else: ?>
          No eligible character is available for this forum realm.
        <?php endif; ?>
      </div>
    </div>
    <div class="posting-context-item">
      <span class="posting-context-label">Forum Scope</span>
      <div class="posting-context-value">
        <?php echo $postingScopeLabel !== '' ? htmlspecialchars($postingScopeLabel) : 'General'; ?>
      </div>
      <div class="posting-context-subvalue">
        <?php echo $postingRealmName !== '' ? htmlspecialchars($postingRealmName) . ' posting rules apply here.' : 'This forum uses the current forum rules.'; ?>
      </div>
    </div>
  </div>
</section>

<?php if (!$is_newtopic && !empty($posts)): ?>
<section class="reply-context">
  <h3>Thread Context</h3>
  <div class="reply-posts">
    <?php foreach ($posts as $post): ?>
      <article class="reply-post">
        <div class="reply-post-avatar">
          <img src="<?php echo $post['avatar']; ?>" alt="avatar" />
          <div class="reply-post-user"><?php echo htmlspecialchars($post['poster']); ?></div>
          <?php if (!empty($post['guild'])): ?>
            <div class="reply-post-guild">&lt;<?php echo htmlspecialchars($post['guild']); ?>&gt;</div>
          <?php endif; ?>
          <div class="reply-post-level">Lvl <?php echo (int)$post['level']; ?></div>
          <div class="reply-post-count">Post count: <?php echo (int)($post['forum_post_count'] ?? 0); ?></div>
        </div>
        <div class="reply-post-body">
          <div class="reply-post-meta">#<?php echo (int)$post['pos_num']; ?> · <?php echo htmlspecialchars((string)$post['posted']); ?></div>
          <div class="reply-post-message"><?php echo $post['rendered_message'] ?? ''; ?></div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="reply-panel">
  <h2><?php echo $is_newtopic ? 'Start a New Discussion' : 'Write Your Reply'; ?></h2>
  <?php if (!empty($posting_block_reason)): ?>
    <div class="forum-post__error">
      <?php echo htmlspecialchars($posting_block_reason); ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($forum_post_errors)): ?>
    <div class="forum-post__error">
      <?php echo htmlspecialchars($forum_post_errors[0]); ?>
    </div>
  <?php endif; ?>
  <form method="post" action="<?php echo $formAction; ?>" id="forum-post-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('forum_actions')); ?>">
    <input type="hidden" name="posting_character_id" value="<?php echo (int)($forum_post_form['posting_character_id'] ?? 0); ?>">
    <input type="hidden" name="publisher_identity" value="<?php echo htmlspecialchars((string)($forum_post_form['publisher_identity'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($is_newtopic): ?>
      <label for="subject">Subject:</label>
      <input type="text" id="subject" name="subject" maxlength="80" value="<?php echo htmlspecialchars((string)($forum_post_form['subject'] ?? '')); ?>" placeholder="Enter your topic title..." class="subject-input" <?php echo !$canPost ? 'disabled' : ''; ?> />
    <?php endif; ?>

    <label for="message">Message:</label>

  <div class="editor-toolbar">
      <button type="button" data-forum-tag="b"><b>B</b></button>
      <button type="button" data-forum-tag="i"><i>I</i></button>
      <button type="button" data-forum-tag="u">U</button>
      <button type="button" data-forum-tag="url">Link</button>
      <button type="button" data-forum-tag="img">Img</button>
      <button type="button" data-forum-tag="color=red">Color</button>
    </div>

    <textarea id="message" name="text" class="editor" placeholder="Write your message..." <?php echo !$canPost ? 'disabled' : ''; ?>><?php echo htmlspecialchars((string)($forum_post_form['text'] ?? '')); ?></textarea>

    <div class="reply-actions">
      <button type="submit" class="btn primary" <?php echo !$canPost ? 'disabled' : ''; ?>><?php echo $is_newtopic ? 'Post Topic' : 'Add Reply'; ?></button>
      <button type="reset" class="btn secondary">Clear</button>
    </div>
  </form>
</section>

<?php builddiv_end(); ?>
