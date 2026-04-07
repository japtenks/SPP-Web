<?php builddiv_start(1, 'News'); ?>
<img class="frontpage-hero-banner" src="<?php echo htmlspecialchars(spp_modern_forum_image_url('forum_top.png'), ENT_QUOTES); ?>" alt="News Banner" />

<div class="news-container">
  <?php
  if (!empty($alltopics)):
    foreach ($alltopics as $topic):
  ?>
    <div class="news-expand<?php echo !empty($topic['row_class']) ? ' ' . htmlspecialchars((string)$topic['row_class'], ENT_QUOTES) : ''; ?>" id="news<?php echo $topic['topic_id']; ?>">
      <div
        class="news-listing"
        role="button"
        tabindex="0"
        data-frontpage-news-toggle
        data-frontpage-news-id="<?php echo (int)$topic['topic_id']; ?>"
      >
        <div class="news-top">
          <ul>
            <li class="item-icon">
              <img src="<?php echo htmlspecialchars(spp_modern_image_url('misc/news-contests.gif'), ENT_QUOTES); ?>" alt="icon" />
            </li>
            <li class="news-entry">
              <h1><?php echo htmlspecialchars($topic['topic_name']); ?></h1>
              <span class="user">
                Posted by: <b><?php echo htmlspecialchars($topic['topic_poster']); ?></b> |
                <?php echo date('d-m-Y', $topic['topic_posted']); ?>
              </span>
            </li>
          </ul>
        </div>
      </div>

      <div class="news-item">
        <blockquote>
          <div class="blog-post">
            <?php echo $topic['rendered_message'] ?? ''; ?>
            <div class="news-meta-links">
              <?php if (($topic['source_type'] ?? 'forum') === 'news'): ?>
                <a href="<?php echo htmlspecialchars((string)($topic['permalink'] ?? spp_route_url('news', 'view', array('id' => $topic['topic_id']), false))); ?>">
                  Read full article
                </a>
              <?php else: ?>
                <a href="<?php echo spp_route_url('forum', 'viewtopic', array('tid' => $topic['topic_id'], 'to' => 'lastpost')); ?>">
                  Last comment
                </a>
                from
                <a href="<?php echo spp_route_url('account', 'view', array('action' => 'find', 'name' => $topic['last_poster'])); ?>">
                  <?php echo htmlspecialchars($topic['last_poster']); ?>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </blockquote>
      </div>
    </div>
  <?php endforeach; endif; ?>

  <?php if (empty($alltopics)): ?>
    <div class="news-expand">
      <div class="news-item">
        <blockquote>
          <div class="blog-post">
            Installation notes are available in the
            <a href="index.php?n=html&amp;text=readme">project README</a>.
            Make sure both <code>php</code> and <code>mysql</code> are available on <code>PATH</code> before running the patch tools.
            If you are finishing setup now, run:
            <br /><br />
            <code>powershell -File tools/install_db_patches.ps1 -Patch classicrealmd</code>
            <br />
            <code>powershell -File tools/install_db_patches.ps1 -Patch armory</code>
          </div>
        </blockquote>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php builddiv_end(); ?>

