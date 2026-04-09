<?php
$topicTitle = htmlspecialchars(html_entity_decode((string)$this_topic['topic_name'], ENT_QUOTES, 'UTF-8'));
$forumTitle = htmlspecialchars(html_entity_decode((string)$this_forum['forum_name'], ENT_QUOTES, 'UTF-8'));
$topicRealmId = (int)($realm_id ?? ($_GET['realm'] ?? 0));
$topicRealmMap = $GLOBALS['realmDbMap'] ?? array();
$topicBannerUrl = function_exists('spp_forum_badge_url')
    ? spp_forum_badge_url($this_forum, is_array($topicRealmMap) ? $topicRealmMap : array(), 1)
    : spp_modern_forum_image_url('banner_top.png');
builddiv_start(1, $forumTitle, 0, false, $this_forum['forum_id'], $this_forum['closed']);
?>

<div class="topic-page feature-shell">
  <img src="<?php echo htmlspecialchars($topicBannerUrl, ENT_QUOTES, 'UTF-8'); ?>"
       alt="<?php echo htmlspecialchars($forumTitle, ENT_QUOTES, 'UTF-8'); ?>"
       class="forum-header" />

  <header class="forum-topic__header feature-panel">
    <div class="forum-topic__titlebar">
      <h1><?php echo $topicTitle; ?></h1>
      <p class="forum-topic__meta">
        Started by <strong><?php echo htmlspecialchars(html_entity_decode((string)$this_topic['topic_poster'], ENT_QUOTES, 'UTF-8')); ?></strong> ·
        <?php echo date('M d, Y H:i', $this_topic['topic_posted']); ?>
      </p>
    </div>
    <div class="forum-topic__controls">
      <div class="forum-topic__control-group forum-topic__control-group--primary">
        <?php if (!empty($this_topic['linktoreply'])): ?>
          <a href="<?php echo $this_topic['linktoreply']; ?>" class="feature-button is-primary">Reply</a>
        <?php endif; ?>
        <a href="<?php echo $this_forum['linktothis']; ?>" class="feature-button">Back to Forums</a>
        <?php if ((int)($this_topic['page_count'] ?? 1) > 1 && !empty($this_topic['linktolastpost'])): ?>
          <a href="<?php echo htmlspecialchars($this_topic['linktolastpost'], ENT_QUOTES, 'UTF-8'); ?>" class="feature-button">Most Recent Post</a>
        <?php endif; ?>
      </div>
      <?php if ((int)($user['g_forum_moderate'] ?? 0) === 1): ?>
        <div class="forum-topic__control-group forum-topic__control-group--admin">
          <?php if (!empty($this_topic['sticky'])): ?>
            <form method="post" action="<?php echo htmlspecialchars(spp_forum_url('post', array('realm' => $topicRealmId, 't' => (int)$this_topic['topic_id'])), ENT_QUOTES, 'UTF-8'); ?>" class="forum-topic__inline-form forum-topic__inline-form--admin">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('forum_actions')); ?>">
              <input type="hidden" name="action" value="unsticktopic">
              <button type="submit" class="feature-button forum-topic__admin-button">Unpin Topic</button>
            </form>
          <?php else: ?>
            <form method="post" action="<?php echo htmlspecialchars(spp_forum_url('post', array('realm' => $topicRealmId, 't' => (int)$this_topic['topic_id'])), ENT_QUOTES, 'UTF-8'); ?>" class="forum-topic__inline-form forum-topic__inline-form--admin">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('forum_actions')); ?>">
              <input type="hidden" name="action" value="sticktopic">
              <button type="submit" class="feature-button forum-topic__admin-button">Pin Topic</button>
            </form>
          <?php endif; ?>
          <?php if (!empty($this_topic['closed'])): ?>
            <form method="post" action="<?php echo htmlspecialchars(spp_forum_url('post', array('realm' => $topicRealmId, 't' => (int)$this_topic['topic_id'])), ENT_QUOTES, 'UTF-8'); ?>" class="forum-topic__inline-form forum-topic__inline-form--admin">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('forum_actions')); ?>">
              <input type="hidden" name="action" value="opentopic">
              <button type="submit" class="feature-button forum-topic__admin-button">Unlock Topic</button>
            </form>
          <?php else: ?>
            <form method="post" action="<?php echo htmlspecialchars(spp_forum_url('post', array('realm' => $topicRealmId, 't' => (int)$this_topic['topic_id'])), ENT_QUOTES, 'UTF-8'); ?>" class="forum-topic__inline-form forum-topic__inline-form--admin">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('forum_actions')); ?>">
              <input type="hidden" name="action" value="closetopic">
              <button type="submit" class="feature-button forum-topic__admin-button">Lock Topic</button>
            </form>
          <?php endif; ?>
          <form method="post" action="<?php echo htmlspecialchars(spp_forum_url('post', array('realm' => $topicRealmId, 't' => (int)$this_topic['topic_id'])), ENT_QUOTES, 'UTF-8'); ?>" class="forum-topic__inline-form forum-topic__inline-form--admin" onsubmit="return confirm('Delete this topic and all of its posts?');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('forum_actions')); ?>">
            <input type="hidden" name="action" value="dodeletetopic">
            <button type="submit" class="feature-button forum-topic__admin-button">Delete Topic</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <section class="forum-topic__posts">
    <?php if (!empty($posts)): ?>
      <?php foreach ($posts as $post): ?>
        <?php $postProfileLink = (string)($post['linktocharacter_social'] ?? $post['linktoprofile'] ?? ''); ?>
        <article class="forum-topic__post">
          <div class="forum-topic__post-head">
            <div class="forum-topic__author-block">
              <div class="forum-topic__avatar">
                <?php if ($postProfileLink !== ''): ?>
                  <a href="<?php echo htmlspecialchars($postProfileLink, ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="<?php echo $post['avatar']; ?>" alt="avatar" />
                  </a>
                <?php else: ?>
                  <img src="<?php echo $post['avatar']; ?>" alt="avatar" />
                <?php endif; ?>
              </div>
              <div class="forum-topic__author-meta-block">
                <h3><?php echo htmlspecialchars($post['poster']); ?></h3>
                <?php if (!empty($post['guild'])): ?>
                  <div class="forum-topic__author-line">&lt;<?php echo htmlspecialchars($post['guild']); ?>&gt;</div>
                <?php endif; ?>
                <?php if (!empty($post['level'])): ?>
                  <div class="forum-topic__author-line">Lvl <?php echo (int)$post['level']; ?></div>
                <?php endif; ?>
                <div class="forum-topic__author-line">Post count: <?php echo (int)($post['forum_post_count'] ?? 0); ?></div>
              </div>
            </div>

            <div class="forum-topic__body">
              <div class="forum-topic__post-bar">
                <div class="forum-topic__post-title"><?php echo $topicTitle; ?></div>
                <div class="forum-topic__post-time">
                  <span class="forum-topic__post-number">#<?php echo (int)$post['pos_num']; ?></span>
                  <span class="forum-topic__post-time-sep">·</span>
                  <span><?php echo htmlspecialchars((string)$post['posted']); ?></span>
                </div>
              </div>

              <div class="forum-topic__message"><?php echo $post['rendered_message']; ?></div>
              <div class="forum-topic__signature-spacer"></div>

              <?php if (!empty(trim((string)$post['rendered_signature']))): ?>
                <div class="forum-topic__signature">
                  <?php echo $post['rendered_signature']; ?>
                </div>
              <?php endif; ?>

              <?php if ($post['edited']): ?>
                <footer class="forum-topic__edit-note">
                  Edited by <?php echo htmlspecialchars($post['edited_by']); ?> on
                  <?php echo date('M d, Y H:i', $post['edited']); ?>
                </footer>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="feature-empty forum-topic__empty">No posts yet.</p>
    <?php endif; ?>
  </section>

  <div class="forum-topic__pagination">
    <?php echo $pages_str; ?>
  </div>
</div>

<?php builddiv_end(); ?>
