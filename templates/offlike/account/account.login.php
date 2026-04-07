<?php require_once __DIR__ . '/account.template.php'; ?>





<?php builddiv_start(1, 'Login'); ?>

<div class="login-panel feature-shell">
<?php if (!empty($login_message)): ?>
  <div class="login-message feature-note<?php echo $login_message_class; ?>">
    <?php echo htmlspecialchars($login_message); ?>
  </div>
<?php endif; ?>
<?php header_image_account(); ?>
<?php if ($user['id'] <= 0): ?>
<div class="feature-panel login-shell">
<div class="account-auth-layout">
    <img src="templates/offlike/images/twoheaded-ogre.jpg" alt="Orc Warrior">
  <form method="post" action="index.php?n=account&sub=login" class="login-form">
    <input type="hidden" name="action" value="login">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$login_csrf_token); ?>">
    <input type="hidden" name="returnto" value="<?php echo htmlspecialchars($login_return_to); ?>">

    <div class="login-field">
<label for="login"><b>Username</b></label>
<input type="text" id="login" name="login" value="<?php echo htmlspecialchars($login_form_username); ?>" placeholder="Username" required>
    </div>

    <div class="login-field">
<label for="pass"><b>Password</b></label>
<input type="password" id="pass" name="pass" placeholder="Password" required>
    </div>

    <div class="login-actions feature-actions">
<input type="submit" value="Login" class="feature-button is-primary">
      <div class="account-auth-copy">
        <p class="account-note">Use the same account name and password you enter in-game. If you were trying to reach a protected page, you&rsquo;ll be sent back there after signing in.</p>
      </div>
    </div>
  </form>
</div>

<?php else: ?>
  <div class="login-welcome feature-panel login-shell">
<h3>You are already logged in</h3>
    <p class="feature-copy"><strong>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</strong></p>
  </div>
<?php endif; ?>

</div>
<?php builddiv_end(); ?>
