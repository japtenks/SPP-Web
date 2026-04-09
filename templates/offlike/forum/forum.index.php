<?php
builddiv_start(1, 'Forums');
?>
<div class="forum-page feature-shell">
  <img src="<?php echo htmlspecialchars(spp_modern_forum_image_url('bannerbg.jpg'), ENT_QUOTES); ?>" alt="Forums" class="forum-header"/>

  <div class="forum-container">
    <?php if (empty($items) || !is_array($items)): ?>
      <div class="feature-panel feature-empty">No forums available.</div>
    <?php endif; ?>

    <?php foreach ($items as $catitem): ?>
      <?php if (empty($catitem) || !is_array($catitem)) continue; ?>
      <?php
        $categoryLabel = preg_replace('/^\s*Category\s+/i', '', (string)$catitem[0]['cat_name']);
        $categoryKey = strtolower(trim((string)$categoryLabel));
        $forumNewPostsIcon = spp_modern_forum_image_url('news-community.gif');
        $forumNoNewPostsIcon = spp_modern_forum_image_url('no-news-community.gif');
        $forumLockIcon = spp_modern_forum_image_url('lock-icon.gif');
        $categoryIconMap = array(
          'news' => spp_modern_image_url('badges/forum-news.png'),
          'general' => spp_modern_image_url('badges/forum-general.png'),
          'guild' => spp_modern_image_url('badges/forum-guild.png'),
          'help' => spp_modern_image_url('badges/forum-help.png'),
        );
        $categoryIcon = $categoryIconMap[$categoryKey] ?? '';
      ?>
      <section class="forum-category feature-panel">
        <div class="modern-title">
          <a class="forum-title" href="<?php echo spp_forum_url('viewcategory', array('realm' => (int)spp_resolve_realm_id($realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array())), 'catid' => (int)$catitem[0]['cat_id'])); ?>">
            <?php if ($categoryIcon !== ''): ?><img class="forum-category-badge" src="<?php echo htmlspecialchars($categoryIcon); ?>" alt=""><?php endif; ?>
            <?php echo htmlspecialchars($categoryLabel); ?>
          </a>
        </div>

        <?php foreach ($catitem as $forumitem): ?>
          <article class="forum-entry">
            <div class="forum-icon">
              <img src="<?php echo htmlspecialchars(
                $forumitem['closed']
                  ? $forumLockIcon
                  : (((int)($user['id'] ?? 0) <= 0 || ($forumitem['isnew'] ?? false))
                    ? $forumNewPostsIcon
                    : $forumNoNewPostsIcon),
                ENT_QUOTES
              ); ?>" alt="Status"/>
            </div>

            <div class="forum-details">
              <a class="forum-title" href="<?php echo $forumitem['linktothis']; ?>">
                <?php echo htmlspecialchars($forumitem['forum_name']); ?>
              </a>
              <p class="forum-desc"><?php echo htmlspecialchars($forumitem['forum_desc']); ?></p>

              <?php if (!empty($forumitem['topic_name']) && !empty($forumitem['last_topic_id'])): ?>
                <div class="lastreply">
                  Last reply in
                  <a href="<?php echo $forumitem['linktolastpost']; ?>">
                    <?php echo htmlspecialchars($forumitem['topic_name']); ?>
                  </a><br/>
                  from
                  <span><?php echo htmlspecialchars($forumitem['last_poster']); ?></span>
                  <?php echo $forumitem['last_post']; ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="forum-stats">
              <div><?php echo (int)$forumitem['num_topics']; ?> topics</div>
              <div><?php echo (int)$forumitem['num_posts']; ?> posts</div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>

    <div class="forum-legend feature-panel">
      <div><img src="<?php echo htmlspecialchars(spp_modern_forum_image_url('news-community.gif'), ENT_QUOTES); ?>" alt=""/> New Posts</div>
      <div><img src="<?php echo htmlspecialchars(spp_modern_forum_image_url('no-news-community.gif'), ENT_QUOTES); ?>" alt=""/> No New Posts</div>
      <div><img src="<?php echo htmlspecialchars(spp_modern_forum_image_url('lock-icon.gif'), ENT_QUOTES); ?>" alt=""/> Closed</div>
    </div>
  </div>
</div>
<?php builddiv_end(); ?>
