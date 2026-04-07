<?php $GLOBALS['builddiv_header_actions'] = '<a href="index.php?n=account&sub=userlist" class="btn secondary">Back to User List</a>'; ?>
<?php builddiv_start(1, 'Personal Messages'); ?>

<?php if($user['id']>0): ?>
<div class="modern-content pm-container feature-shell">

  <nav class="pm-nav feature-panel">
    <a href="index.php?n=account&sub=pms&action=add" class="<?php echo ($_GET['action']=='add'?'active':''); ?>">Write</a>
    <a href="index.php?n=account&sub=pms&action=view" class="<?php echo ($_GET['action']=='view' || $_GET['action']=='viewpm'?'active':''); ?>">Messages</a>
  </nav>

  <?php if($_GET['action']=='add'): ?>
  <div class="pm-compose">
    <form method="post" action="index.php?n=account&sub=pms&action=add" id="pm-compose-form">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($pms_csrf_token ?? spp_csrf_token('account_pms'))); ?>">
      <div class="pm-recipient-row">
        <div>
          <label for="pm-owner">Who:</label>
          <?php if(!empty($isReplyMode)): ?>
          <div class="compose-help">Reply is locked to this conversation.</div>
          <input type="text" id="pm-owner" name="owner" value="<?php echo htmlspecialchars($content['sender']); ?>" maxlength="80" readonly="readonly" required>
          <?php else: ?>
          <div class="compose-help">Type an account name, or pick one below when fewer than 20 visible members are available.</div>
          <input type="text" id="pm-owner" name="owner" value="<?php echo htmlspecialchars($content['sender']); ?>" maxlength="80" placeholder="Enter account name" required>
          <?php endif; ?>
        </div>

        <?php if(empty($isReplyMode) && !empty($pmRecipientOptions)): ?>
        <div class="pm-recipient-quickpick">
          <label for="pm-owner-picker">Choose a member</label>
          <select id="pm-owner-picker">
            <option value="">Select a recipient</option>
            <?php foreach($pmRecipientOptions as $pmRecipient): ?>
              <option value="<?php echo htmlspecialchars($pmRecipient); ?>"><?php echo htmlspecialchars($pmRecipient); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
      </div>

      <div class="compose-help">Plain text works best here right now.</div>

      <textarea name="message" id="input_comment" required><?php echo htmlspecialchars($content['message']); ?></textarea>

      <div class="pm-buttons">
        <button type="submit" class="btn-primary">Send</button>
        <button type="reset">Clear</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <?php if($_GET['action']=='view'): ?>
  <div class="pm-view-list">
    <?php if (!empty($items)): ?>
      <?php foreach ($items as $item): ?>
      <?php
        $isIncoming = (($item['latest_box'] ?? '') === 'in');
        $peerLabel = !empty($item['peer_name']) ? $item['peer_name'] : 'Unknown';
        $previewText = trim(strip_tags((string)my_preview(my_previewreverse($item['latest_message'] ?? ''))));
        if ($previewText === '') {
          $previewText = 'No message preview available.';
        } elseif (function_exists('mb_substr')) {
          $previewText = mb_substr($previewText, 0, 180) . (mb_strlen($previewText) > 180 ? '...' : '');
        } else {
          $previewText = substr($previewText, 0, 180) . (strlen($previewText) > 180 ? '...' : '');
        }
      ?>
      <a class="pm-conversation-card" href="index.php?n=account&sub=pms&action=viewpm&iid=<?php echo (int)$item['latest_id']; ?>">
        <div class="pm-conversation-main">
          <div class="pm-conversation-top">
            <span class="pm-direction"><?php echo $isIncoming ? 'Received' : 'Sent'; ?></span>
            <span class="pm-conversation-name"><?php echo htmlspecialchars($peerLabel); ?></span>
          </div>
          <div class="pm-conversation-preview"><?php echo htmlspecialchars($previewText); ?></div>
        </div>
        <div class="pm-conversation-meta">
          <div class="pm-time"><?php echo date('d-m-Y, H:i', (int)($item['latest_posted'] ?? 0)); ?></div>
          <?php if (!empty($item['unread_count'])): ?>
          <span class="pm-unread-badge"><?php echo (int)$item['unread_count']; ?></span>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="pm-card pm-empty"><em>No messages yet.</em></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if($_GET['action']=='viewpm' && !empty($threadPeer)): ?>
  <div class="pm-thread-shell">
    <div class="pm-thread-head">
      <div class="pm-thread-title">
        <div class="pm-thread-kicker">Conversation</div>
        <div class="pm-thread-peer"><?php echo htmlspecialchars($threadPeer); ?></div>
      </div>
      <div class="pm-thread-actions">
        <a href="index.php?n=account&sub=pms&action=view" class="pm-thread-link secondary">All Messages</a>
      </div>
    </div>

    <form method="post" action="index.php?n=account&sub=pms&action=viewpm&iid=<?php echo (int)($_GET['iid'] ?? 0); ?>" class="pm-thread-reply">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($pms_csrf_token ?? spp_csrf_token('account_pms'))); ?>">
      <div class="compose-help">Replying to <?php echo htmlspecialchars($threadPeer); ?>.</div>
      <textarea name="reply_message" placeholder="Write your reply..." required></textarea>
      <div class="pm-thread-reply-actions">
        <button type="submit" class="pm-thread-send">Reply</button>
      </div>
    </form>

    <div class="pm-thread-list">
      <?php foreach (array_reverse($threadItems) as $threadItem): ?>
      <?php
        $isIncoming = (($threadItem['pm_box'] ?? '') === 'in');
        $previewHtml = my_preview(my_previewreverse($threadItem['message'] ?? ''));
      ?>
      <div class="pm-card <?php echo !empty($threadItem['showed']) ? 'read' : 'unread'; ?> <?php echo $isIncoming ? 'incoming' : 'outgoing'; ?>">
        <div class="pm-row-header">
          <div class="pm-row-main">
            <span class="pm-direction"><?php echo $isIncoming ? 'Received' : 'Sent'; ?></span>
          </div>
        </div>
        <div class="pm-preview"><?php echo $previewHtml; ?></div>
        <div class="pm-card-footer">
          <div class="pm-time"><?php echo date('d-m-Y, H:i', (int)$threadItem['posted']); ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php endif; ?>

<?php builddiv_end(); ?>
