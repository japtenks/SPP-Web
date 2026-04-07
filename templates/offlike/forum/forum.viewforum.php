<?php
if (INCLUDED !== true) exit;
if (empty($this_forum) || (int)$this_forum['forum_id'] <= 0) {
    output_message('alert', 'Invalid forum.');
    return;
}

$forumPageCount = max(1, (int)($this_forum['pnum'] ?? 1));
$forumItemsPerPage = (int)($this_forum['items_per_page'] ?? 25);
$forumSortField = (string)($this_forum['sort_field'] ?? 'posted');
$forumSortDir = (string)($this_forum['sort_dir'] ?? 'desc');
$forumPageBaseUrl = spp_forum_url('viewforum', array(
  'fid' => (int)$this_forum['forum_id'],
  'per_page' => $forumItemsPerPage,
  'sort' => $forumSortField,
  'dir' => $forumSortDir,
));

$forumSortUrl = function (string $field) use ($this_forum, $forumItemsPerPage, $forumSortField, $forumSortDir): string {
    $nextDir = ($forumSortField === $field && $forumSortDir === 'asc') ? 'desc' : 'asc';
    return spp_forum_url('viewforum', array(
        'fid' => (int)$this_forum['forum_id'],
        'per_page' => $forumItemsPerPage,
        'sort' => $field,
        'dir' => $nextDir,
    ));
};

$forumSortLabel = function (string $field, string $label) use ($forumSortUrl, $forumSortField, $forumSortDir): string {
    $indicator = '';
    if ($forumSortField === $field) {
        $indicator = '<span class="sort-indicator">' . ($forumSortDir === 'asc' ? '&#9650;' : '&#9660;') . '</span>';
    }
    return '<a href="' . htmlspecialchars($forumSortUrl($field), ENT_QUOTES, 'UTF-8') . '">' . $label . $indicator . '</a>';
};

$forumRealmMap = $GLOBALS['realmDbMap'] ?? array();
$forumBannerUrl = function_exists('spp_forum_badge_url')
    ? spp_forum_badge_url($this_forum, is_array($forumRealmMap) ? $forumRealmMap : array(), 1)
    : spp_modern_forum_image_url('banner_top.png');
$forumNewPostsIcon = spp_modern_forum_image_url('news-community.gif');
$forumNoNewPostsIcon = spp_modern_forum_image_url('no-news-community.gif');
$forumLockIcon = spp_modern_forum_image_url('lock-icon.gif');
?>

<?php builddiv_start(1, $this_forum['forum_name'], 0, true, $this_forum['forum_id'], $this_forum['closed']); ?>

<img src="<?php echo htmlspecialchars($forumBannerUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string)$this_forum['forum_name'], ENT_QUOTES, 'UTF-8'); ?>" class="forum-header"/>

<div class="forum-page forum-page--wide feature-shell">
<div class="feature-panel forum-view">
  <?php if (!empty($this_forum['can_start_topic'])): ?>
  <div class="forum-new-topic">
    <a href="<?php echo spp_forum_url('post', array('action' => 'newtopic', 'fid' => (int)$this_forum['forum_id'])); ?>" class="feature-button is-primary">New Topic</a>
  </div>
  <?php elseif (!empty($this_forum['posting_block_reason'])): ?>
  <div class="forum-posting-note">
    <strong>Posting unavailable.</strong>
    <?php echo htmlspecialchars((string)$this_forum['posting_block_reason'], ENT_QUOTES, 'UTF-8'); ?>
  </div>
  <?php endif; ?>

  <div class="forum-toolbar">
    <div></div>
    <form method="get" action="index.php" class="forum-page-size">
      <input type="hidden" name="n" value="forum" />
      <input type="hidden" name="sub" value="viewforum" />
      <input type="hidden" name="fid" value="<?php echo (int)$this_forum['forum_id']; ?>" />
      <input type="hidden" name="sort" value="<?php echo htmlspecialchars($forumSortField, ENT_QUOTES, 'UTF-8'); ?>" />
      <input type="hidden" name="dir" value="<?php echo htmlspecialchars($forumSortDir, ENT_QUOTES, 'UTF-8'); ?>" />
      <label for="forum_per_page">Show</label>
      <select id="forum_per_page" name="per_page" onchange="this.form.submit()">
        <?php foreach (($this_forum['allowed_page_sizes'] ?? array(10, 25, 50)) as $pageSize): ?>
          <option value="<?php echo (int)$pageSize; ?>"<?php if ((int)$pageSize === $forumItemsPerPage) echo ' selected'; ?>><?php echo (int)$pageSize; ?></option>
        <?php endforeach; ?>
      </select>
      <span>topics</span>
    </form>
  </div>

  <div class="forum-list-head">
    <div></div>
    <div><?php echo $forumSortLabel('subject', 'Subject'); ?></div>
    <div><?php echo $forumSortLabel('author', 'Author'); ?></div>
    <div><?php echo $forumSortLabel('posted', 'Posted'); ?></div>
    <div><?php echo $forumSortLabel('replies', 'Replies'); ?></div>
    <div><?php echo $forumSortLabel('views', 'Views'); ?></div>
    <div><?php echo $forumSortLabel('last_reply', 'Last Reply'); ?></div>
  </div>

  <?php if (empty($topics)): ?>
    <div class="forum-row">
      <div class="col-subject forum-empty-cell">No topics found.</div>
    </div>
  <?php else: ?>
    <?php foreach ($topics as $t): ?>
      <div class="forum-row">
        <div><img src="<?php echo htmlspecialchars(((int)($user['id'] ?? 0) <= 0 || !empty($t['isnew'])) ? $forumNewPostsIcon : $forumNoNewPostsIcon, ENT_QUOTES); ?>" alt="Status"></div>
        <div class="col-subject">
          <a href="<?php echo $t['linktothis']; ?>">
            <?php echo htmlspecialchars($t['topic_name']); ?>
          </a>
          <?php if ($t['closed']): ?><span class="new-tag">Closed</span><?php endif; ?>
        </div>
        <div><?php echo htmlspecialchars($t['topic_author_display']); ?></div>
        <div><?php echo htmlspecialchars((string)$t['topic_posted']); ?></div>
        <div><?php echo (int)$t['num_replies']; ?></div>
        <div><?php echo (int)$t['num_views']; ?></div>
        <div>
          <?php echo htmlspecialchars($t['last_poster']); ?><br>
          <?php echo $t['last_post']; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($forumPageCount > 1): ?>
  <div class="pagination">
    <?php echo default_paginate($forumPageCount, (int)$p, $forumPageBaseUrl); ?>
  </div>
  <?php endif; ?>

  <div class="forum-legend">
    <div>
      <img src="<?php echo htmlspecialchars($forumNewPostsIcon, ENT_QUOTES); ?>" alt="New Posts"/>
      New Posts
    </div>
    <div>
      <img src="<?php echo htmlspecialchars($forumNoNewPostsIcon, ENT_QUOTES); ?>" alt="No New Posts"/>
      No New Posts
    </div>
    <div>
      <img src="<?php echo htmlspecialchars($forumLockIcon, ENT_QUOTES); ?>" alt="Forum Closed"/>
      Closed
    </div>
  </div>
</div>
</div>
<?php builddiv_end(); ?>
