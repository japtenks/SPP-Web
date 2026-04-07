<br>
<?php builddiv_start(1, 'User List'); ?>

<?php if ($user['id'] <= 0): ?>
  <center>
    <div class="alert-denied">
      <strong>Access denied.</strong>
    </div>
  </center>

<?php else: ?>
<div class="modern-content userlist feature-shell">

  <div class="userlist-header feature-hero">
    <div class="alphabet-filter">
      <form method="get" action="index.php" class="letter-filter-form">
        <input type="hidden" name="n" value="account">
        <input type="hidden" name="sub" value="userlist">
        <label class="filter-label feature-eyebrow" for="letterSelect">Filter:</label>
        <select id="letterSelect" name="char" class="inline-filter-select">
          <option value="">All</option>
          <?php
            $activeLetter = isset($_GET['char']) && strlen($_GET['char']) === 1 ? strtolower($_GET['char']) : '';
            foreach (range('a', 'z') as $l):
          ?>
            <option value="<?php echo $l; ?>"<?php if ($activeLetter === $l) echo ' selected'; ?>><?php echo strtoupper($l); ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
  </div>

    <div class="userlist-table feature-panel">
      <div class="userlist-row userlist-row--header">
        <div class="userlist-table__cell userlist-table__cell--icon"></div>
        <div class="userlist-table__cell userlist-table__cell--name">Username</div>
        <div class="userlist-table__cell userlist-table__cell--action">Profile</div>
      </div>

    <?php if (is_array($items)): ?>
      <?php foreach ($items as $item): ?>
        <div class="userlist-row">
          <div class="userlist-table__cell userlist-table__cell--icon">
            <a href="index.php?n=account&sub=pms&action=add&to=<?php echo $item['username']; ?>"
               class="pm-btn" title="Personal Message">
              &#9993;
            </a>
          </div>
          <div class="userlist-table__cell userlist-table__cell--name">
            <a href="index.php?n=account&sub=view&action=find&name=<?php echo $item['username']; ?>">
              <?php echo $item['username']; ?>
            </a>
          </div>
          <div class="userlist-table__cell userlist-table__cell--action">
            <a class="profile-link" href="index.php?n=account&sub=view&action=find&name=<?php echo $item['username']; ?>">
              View
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="userlist-empty">No members found for this filter.</div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php builddiv_end(); ?>
