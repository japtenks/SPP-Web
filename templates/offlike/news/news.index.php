<?php builddiv_start(1, 'News'); ?>
<div class="news-page feature-shell">
  <div class="news-page__stack">
    <?php if (empty($news_items)): ?>
      <div class="feature-panel feature-empty">No published news posts yet.</div>
    <?php else: ?>
      <?php foreach ($news_items as $newsItem): ?>
        <article class="news-page__card feature-panel">
          <h2 class="news-page__title"><?php echo htmlspecialchars($newsItem['topic_name']); ?></h2>
          <div class="news-page__meta">
            Posted by <strong><?php echo htmlspecialchars($newsItem['topic_poster']); ?></strong>
            on <?php echo date('M d, Y', (int)$newsItem['topic_posted']); ?>
          </div>
          <div class="news-page__body"><?php echo $newsItem['rendered_message']; ?></div>
          <a class="news-page__link feature-link" href="<?php echo htmlspecialchars($newsItem['permalink']); ?>">Open News Post</a>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php builddiv_end(); ?>
