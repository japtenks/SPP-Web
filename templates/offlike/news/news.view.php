<?php builddiv_start(1, !empty($news_article['topic_name']) ? $news_article['topic_name'] : 'News'); ?>
<div class="news-view feature-shell">
  <article class="feature-panel">
    <?php if (empty($news_article)): ?>
      <p class="feature-empty">That news post could not be found.</p>
    <?php else: ?>
      <div class="news-view__meta">
        Posted by <strong><?php echo htmlspecialchars($news_article['topic_poster']); ?></strong>
        on <?php echo date('M d, Y', (int)$news_article['topic_posted']); ?>
      </div>
      <div class="news-view__body"><?php echo $news_article['rendered_message']; ?></div>
      <a class="news-view__back feature-link" href="<?php echo htmlspecialchars(spp_route_url('news', 'index', array(), false)); ?>">Back to News</a>
    <?php endif; ?>
  </article>
</div>
<?php builddiv_end(); ?>
