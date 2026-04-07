<?php
if (INCLUDED !== true) exit;

$categoryLabel = preg_replace('/^\s*Category\s+/i', '', (string)$categoryTitle);
$categoryKey = strtolower(trim((string)$categoryLabel));
$categoryIconMap = array(
  'news' => spp_modern_image_url('badges/forum-news.png'),
  'general' => spp_modern_image_url('badges/forum-general.png'),
  'guild' => spp_modern_image_url('badges/forum-guild.png'),
  'help' => spp_modern_image_url('badges/forum-help.png'),
);
$forumNewPostsIcon = spp_modern_forum_image_url('news-community.gif');
$forumNoNewPostsIcon = spp_modern_forum_image_url('no-news-community.gif');
$forumLockIcon = spp_modern_forum_image_url('lock-icon.gif');
$categoryIcon = $categoryIconMap[$categoryKey] ?? '';
$categoryTitleSafe = htmlspecialchars((string)$categoryLabel, ENT_QUOTES, 'UTF-8');
?>
<?php builddiv_start(1, $categoryTitleSafe); ?>
<div class="forum-page feature-shell">
  <img src="<?php echo htmlspecialchars(spp_modern_forum_image_url('bannerbg.jpg'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo $categoryTitleSafe; ?>" class="forum-header"/>

  <div class="forum-container">
    <div class="feature-panel forum-view">
      <div class="forum-header">
        <div class="forum-header-inner">
          <h1 class="forum-category-heading">
            <?php if ($categoryIcon !== ''): ?><img class="forum-category-badge" src="<?php echo htmlspecialchars($categoryIcon, ENT_QUOTES, 'UTF-8'); ?>" alt=""><?php endif; ?>
            <span><?php echo $categoryTitleSafe; ?></span>
          </h1>
          <div class="forum-actions right">
            <a href="<?php echo spp_forum_url('index'); ?>" class="btn secondary">Back to Forums</a>
          </div>
        </div>
      </div>

      <section class="forum-category">
        <?php foreach ($categoryItems as $forumitem): ?>
          <article class="forum-entry">
            <div class="forum-icon">
              <img src="<?php echo htmlspecialchars(
                $forumitem['closed']
                  ? $forumLockIcon
                  : (((int)($user['id'] ?? 0) <= 0 || ($forumitem['isnew'] ?? false))
                    ? $forumNewPostsIcon
                    : $forumNoNewPostsIcon),
                ENT_QUOTES,
                'UTF-8'
              ); ?>" alt="Status"/>
            </div>

            <div class="forum-details">
              <a class="forum-title" href="<?php echo $forumitem['linktothis']; ?>">
                <?php echo htmlspecialchars((string)$forumitem['forum_name'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
              <p class="forum-desc"><?php echo htmlspecialchars((string)$forumitem['forum_desc'], ENT_QUOTES, 'UTF-8'); ?></p>

              <?php if (!empty($forumitem['topic_name']) && !empty($forumitem['last_topic_id'])): ?>
                <div class="lastreply">
                  Last reply in
                  <a href="<?php echo $forumitem['linktolastpost']; ?>">
                    <?php echo htmlspecialchars((string)$forumitem['topic_name'], ENT_QUOTES, 'UTF-8'); ?>
                  </a><br/>
                  from
                  <span><?php echo htmlspecialchars((string)$forumitem['last_poster'], ENT_QUOTES, 'UTF-8'); ?></span>
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
    </div>
  </div>
</div>
<?php builddiv_end(); ?>
