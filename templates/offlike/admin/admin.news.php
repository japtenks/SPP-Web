<?php builddiv_start(0, 'News Editor'); ?>
<div class="admin-news feature-shell">
  <div class="admin-news__grid">
    <section class="admin-news__card feature-panel">
      <h3><?php echo !empty($news_admin_edit_id) ? 'Edit News' : 'Publish News'; ?></h3>
      <p class="admin-news__subtext">Create official homepage news without routing it through the general forums.</p>
      <?php if (!empty($news_admin_success)): ?>
        <div class="admin-news__alert admin-news__alert--ok"><?php echo htmlspecialchars($news_admin_success); ?></div>
      <?php endif; ?>
      <?php if (!empty($news_admin_errors)): ?>
        <div class="admin-news__alert admin-news__alert--bad"><?php echo htmlspecialchars($news_admin_errors[0]); ?></div>
      <?php endif; ?>
      <form method="post" action="index.php?n=admin&amp;sub=news">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('admin_news')); ?>">
        <input type="hidden" name="save_news" value="1">
        <input type="hidden" name="news_id" value="<?php echo (int)($news_admin_edit_id ?? 0); ?>">
        <div class="admin-news__field">
          <label>Publisher</label>
          <select name="publisher_identity">
            <?php foreach ($news_publisher_options as $publisherKey => $publisherLabel): ?>
              <option value="<?php echo htmlspecialchars((string)$publisherKey, ENT_QUOTES, 'UTF-8'); ?>"<?php if ((string)$publisherKey === (string)($news_admin_form['publisher_identity'] ?? '')) echo ' selected'; ?>>
                <?php echo htmlspecialchars((string)$publisherLabel); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="admin-news__field">
          <label>Title</label>
          <input type="text" name="title" maxlength="160" value="<?php echo htmlspecialchars((string)($news_admin_form['title'] ?? '')); ?>">
        </div>
        <div class="admin-news__field">
          <label>Excerpt</label>
          <input type="text" name="excerpt" maxlength="255" value="<?php echo htmlspecialchars((string)($news_admin_form['excerpt'] ?? '')); ?>">
        </div>
        <div class="admin-news__field">
          <label>Body</label>
          <textarea name="body"><?php echo htmlspecialchars((string)($news_admin_form['body'] ?? '')); ?></textarea>
        </div>
        <button class="admin-news__button" type="submit"><?php echo !empty($news_admin_edit_id) ? 'Save Changes' : 'Publish News'; ?></button>
        <?php if (!empty($news_admin_edit_id)): ?>
          <a class="admin-news__button is-inline-spaced" href="index.php?n=admin&amp;sub=news">Cancel Edit</a>
        <?php endif; ?>
      </form>
    </section>

    <section class="admin-news__card feature-panel">
      <h3>Recent News</h3>
      <div class="admin-news__toolbar">
        <div class="admin-news__subtext is-tight">Manage published news and clear the list before release if needed.</div>
        <?php if (!empty($news_admin_items)): ?>
          <form method="post" action="index.php?n=admin&amp;sub=news" onsubmit="return confirm('Clear all dedicated news posts? This cannot be undone.');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('admin_news')); ?>">
            <input type="hidden" name="reset_news" value="1">
            <button class="admin-news__button admin-news__button--danger" type="submit">Reset News</button>
          </form>
        <?php endif; ?>
      </div>
      <table class="admin-news__table">
        <thead><tr><th>Title</th><th>Publisher</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($news_admin_items)): ?>
            <tr><td colspan="4"><em>No dedicated news posts yet.</em></td></tr>
          <?php else: ?>
            <?php foreach ($news_admin_items as $newsItem): ?>
              <tr>
                <td><?php echo htmlspecialchars((string)$newsItem['title']); ?></td>
                <td><?php echo htmlspecialchars((string)$newsItem['publisher_label']); ?></td>
                <td><?php echo !empty($newsItem['published_at']) ? date('M d, Y H:i', (int)$newsItem['published_at']) : '-'; ?></td>
                <td>
                  <a class="admin-news__link" href="index.php?n=admin&amp;sub=news&amp;edit=<?php echo (int)$newsItem['news_id']; ?>">Edit</a>
                  &nbsp;|&nbsp;
                  <a class="admin-news__link" href="<?php echo htmlspecialchars(spp_route_url('news', 'view', array('id' => (int)$newsItem['news_id'], 'slug' => (string)$newsItem['slug']), false)); ?>" target="_blank">View</a>
                  &nbsp;|&nbsp;
                  <form method="post" action="index.php?n=admin&amp;sub=news" class="admin-news__inline-form" onsubmit="return confirm('Delete this news post?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('admin_news')); ?>">
                    <input type="hidden" name="delete_news" value="1">
                    <input type="hidden" name="news_id" value="<?php echo (int)$newsItem['news_id']; ?>">
                    <button type="submit" class="admin-news__link admin-news__link-button">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </div>
</div>
<?php builddiv_end(); ?>
