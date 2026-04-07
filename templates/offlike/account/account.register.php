<?php
if (INCLUDED !== true) exit;
require_once __DIR__ . '/account.template.php';
$registerMessageClass = $registerMessageType === 'success'
  ? ' is-success'
  : (($registerMessageType === 'error') ? ' is-error' : '');
builddiv_start(1, 'Create Account');
?>
<div class="register-panel">
<?php if ($registerMessageHtml !== ''): ?>
  <div class="register-message feature-note<?php echo $registerMessageClass; ?>">
    <?php echo $registerMessageHtml; ?>
    <?php if ($registerMessageClass === ' is-success'): ?>
      <div class="register-message-actions">
        <a class="feature-button" href="index.php?n=server&amp;sub=realmlist&amp;nobody=1&amp;realm=<?php echo (int)$registerRealmId; ?>">Download `realmlist.wtf`</a>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php header_image_account(); ?>

<div class="register-shell feature-panel">
<div class="account-auth-layout">
  <img src="templates/offlike/images/orc2.jpg" alt="Orc Warrior">
  <?php if (!$registerClosed): ?>
  <form method="post" action="index.php?n=account&sub=register" class="register-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$registerCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="form-group">
      <label for="username">Username:</label>
      <input type="text" id="username" name="username" maxlength="16" required value="<?php echo htmlspecialchars((string)$registerUsername, ENT_QUOTES, 'UTF-8'); ?>">
    </div>

    <div class="form-group">
      <label for="password">Password:</label>
      <input type="password" id="password" name="password" maxlength="16" required>
    </div>

    <div class="form-group">
      <label for="verify">Confirm Password:</label>
      <input type="password" id="verify" name="verify" maxlength="16" required>
    </div>

    <div class="form-actions feature-actions">
      <button type="submit" class="feature-button is-primary">Create Account</button>
      <div class="account-auth-copy">
        <p class="account-note">You&rsquo;ll use this account name and password every time you log in to play. Keep them private and separate from any character names you create later in-game.</p>
      </div>
    </div>
  </form>
  <?php else: ?>
  <div class="register-form">
    <div class="account-note feature-copy">
      Account creation is unavailable right now. If you believe this is a mistake, contact an administrator.
    </div>
  </div>
  <?php endif; ?>
</div>
</div>

<div class="account-note feature-copy">
  You will be asked for this Account Name and Password each time you log in to play the game.
  Keep them safe and private. If you ever forget your credentials, contact the administrator directly.<br><br>
  Your Account Name is <b>not</b> your Character Name; you will choose a Character Name in-game after logging in.
</div>

</div>
<?php builddiv_end(); ?>
