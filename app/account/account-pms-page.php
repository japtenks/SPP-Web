<?php

require_once __DIR__ . '/account-pms-actions.php';
require_once __DIR__ . '/account-pms-read.php';

if (!function_exists('spp_account_pms_load_page_state')) {
    function spp_account_pms_load_page_state(array $ctx = array()): array
    {
        $user = $ctx['user'] ?? ($GLOBALS['user'] ?? array());
        $realmDbMap = $ctx['realmDbMap'] ?? ($GLOBALS['realmDbMap'] ?? null);
        $get = $ctx['get'] ?? $_GET;
        $post = $ctx['post'] ?? $_POST;
        $cookie = $ctx['cookie'] ?? $_COOKIE;
        $realmId = function_exists('spp_current_realm_id')
            ? spp_current_realm_id(is_array($realmDbMap) ? $realmDbMap : array())
            : spp_resolve_realm_id(is_array($realmDbMap) ? $realmDbMap : array());
        $pmsPdo = spp_get_pdo('realmd', $realmId);
        $currentAction = (string)($get['action'] ?? '');
        $currentDir = (string)($get['dir'] ?? '');

        $state = array(
            '__stop' => false,
            'items' => array(),
            'threadItems' => array(),
            'threadPeer' => null,
            'items_per_page' => 16,
            'page' => isset($get['p']) ? max(1, (int)$get['p']) : 1,
            'limit_start' => 0,
            'pmsPdo' => $pmsPdo,
            'currentAction' => $currentAction,
            'currentDir' => $currentDir,
            'pms_csrf_token' => spp_csrf_token('account_pms'),
            'isReplyMode' => !empty($get['reply']),
            'content' => array('message' => '', 'sender' => ''),
            'pmRecipientOptions' => array(),
            'pathway_info' => array(),
        );

        if (empty($state['currentAction'])) {
            $state['currentAction'] = 'view';
            $state['currentDir'] = 'all';
        }

        $state['limit_start'] = ($state['page'] - 1) * $state['items_per_page'];
        $state['pathway_info'][] = array(
            'title' => 'Personal Messages',
            'link'  => 'index.php?n=account&sub=pms'
        );

        if ((int)($user['id'] ?? 0) <= 0) {
            redirect('index.php?n=account&sub=login', 1);
            $state['__stop'] = true;
            return $state;
        }

        if (empty($get['action'])) {
            $get['action'] = 'view';
            $get['dir'] = 'all';
        }

        if (spp_account_pms_handle_action(array(
            'action' => $state['currentAction'],
            'dir' => $state['currentDir'],
            'pmsPdo' => $pmsPdo,
            'user' => $user,
            'realmDbMap' => $realmDbMap,
        ))) {
            $state['__stop'] = true;
            return $state;
        }

        if ($get['action'] == 'view') {
            $get['dir'] = 'all';
            $state['pathway_info'][] = array('title' => 'Messages', 'link' => '');

            $stmt = $pmsPdo->prepare("
                SELECT
                    pms.*,
                    s.username AS sender,
                    r.username AS receiver,
                    CASE
                        WHEN pms.owner_id = ? THEN 'in'
                        ELSE 'out'
                    END AS pm_box
                FROM website_pms AS pms
                LEFT JOIN account AS s ON pms.sender_id = s.id
                LEFT JOIN account AS r ON pms.owner_id = r.id
                WHERE pms.owner_id = ? OR pms.sender_id = ?
                ORDER BY pms.posted DESC
            ");
            $stmt->execute([(int)$user['id'], (int)$user['id'], (int)$user['id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $state['items'] = spp_account_pms_build_timeline_items($rows);
            $itemnum = count($state['items']);
            $pnum = (int)ceil(max(1, $itemnum) / $state['items_per_page']);
            if ($state['limit_start'] > 0 || $state['items_per_page'] > 0) {
                $state['items'] = array_slice($state['items'], $state['limit_start'], $state['items_per_page']);
            }
            $state['page_count'] = $pnum;
        } elseif (
            $get['action'] == 'viewpm'
            && isset($get['iid'])
        ) {
            $get['dir'] = 'all';
            $state['pathway_info'][] = array('title' => 'Messages', 'link' => 'index.php?n=account&sub=pms&action=view');

            $stmt = $pmsPdo->prepare("
                SELECT
                    pms.*,
                    s.username AS sender,
                    r.username AS receiver,
                    CASE
                        WHEN pms.owner_id = ? THEN 'in'
                        ELSE 'out'
                    END AS pm_box
                FROM website_pms AS pms
                LEFT JOIN account AS s ON pms.sender_id = s.id
                LEFT JOIN account AS r ON pms.owner_id = r.id
                WHERE (pms.owner_id = ? OR pms.sender_id = ?)
                  AND pms.id = ?
                LIMIT 1
            ");
            $stmt->execute([(int)$user['id'], (int)$user['id'], (int)$user['id'], (int)$get['iid']]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item) {
                $threadPeerId = ((string)($item['pm_box'] ?? '') === 'in')
                    ? (int)($item['sender_id'] ?? 0)
                    : (int)($item['owner_id'] ?? 0);

                if ((string)($item['pm_box'] ?? '') === 'in') {
                    $identId = (int)($item['sender_identity_id'] ?? 0);
                    $state['threadPeer'] = $identId && function_exists('spp_resolve_identity_names')
                        ? (spp_resolve_identity_names([$identId])[$identId] ?? (string)($item['sender'] ?? ''))
                        : (string)($item['sender'] ?? '');
                } else {
                    $identId = (int)($item['recipient_identity_id'] ?? 0);
                    $state['threadPeer'] = $identId && function_exists('spp_resolve_identity_names')
                        ? (spp_resolve_identity_names([$identId])[$identId] ?? (string)($item['receiver'] ?? ''))
                        : (string)($item['receiver'] ?? '');
                }

                if ($threadPeerId > 0) {
                    $stmtThread = $pmsPdo->prepare("
                        SELECT
                            pms.*,
                            s.username AS sender,
                            r.username AS receiver,
                            CASE
                                WHEN pms.owner_id = ? THEN 'in'
                                ELSE 'out'
                            END AS pm_box
                        FROM website_pms AS pms
                        LEFT JOIN account AS s ON pms.sender_id = s.id
                        LEFT JOIN account AS r ON pms.owner_id = r.id
                        WHERE (pms.owner_id = ? AND pms.sender_id = ?)
                           OR (pms.sender_id = ? AND pms.owner_id = ?)
                        ORDER BY pms.posted ASC, pms.id ASC
                    ");
                    $stmtThread->execute([
                        (int)$user['id'],
                        (int)$user['id'],
                        $threadPeerId,
                        (int)$user['id'],
                        $threadPeerId
                    ]);
                    $state['threadItems'] = $stmtThread->fetchAll(PDO::FETCH_ASSOC);
                    $state['threadItems'] = spp_account_pms_enrich_thread_items($state['threadItems']);

                    $stmtMarkThreadRead = $pmsPdo->prepare("
                        UPDATE website_pms
                        SET showed = 1
                        WHERE owner_id = ? AND sender_id = ? AND showed = 0
                    ");
                    $stmtMarkThreadRead->execute([(int)$user['id'], $threadPeerId]);

                    foreach ($state['threadItems'] as &$threadRow) {
                        if ((int)($threadRow['owner_id'] ?? 0) === (int)$user['id']) {
                            $threadRow['showed'] = 1;
                        }
                    }
                    unset($threadRow);
                }
            }

            $state['pathway_info'][] = array('title' => ($state['threadPeer'] ?: 'Message'), 'link' => '');
        } elseif ($get['action'] == 'add') {
            $state['content'] = array('message' => '', 'sender' => '');
            $state['isReplyMode'] = !empty($get['reply']);

            try {
                $stmtRecipientCount = $pmsPdo->prepare("
                    SELECT COUNT(*)
                    FROM account
                    LEFT JOIN website_accounts ON account.id = website_accounts.account_id
                    WHERE account.id <> ?
                      AND LOWER(account.username) NOT LIKE 'rndbot%'
                      AND (website_accounts.hideprofile IS NULL OR website_accounts.hideprofile = 0)
                ");
                $stmtRecipientCount->execute([(int)$user['id']]);
                $recipientCount = (int)$stmtRecipientCount->fetchColumn();

                if ($recipientCount > 0 && $recipientCount < 20) {
                    $stmtRecipients = $pmsPdo->prepare("
                        SELECT account.username
                        FROM account
                        LEFT JOIN website_accounts ON account.id = website_accounts.account_id
                        WHERE account.id <> ?
                          AND LOWER(account.username) NOT LIKE 'rndbot%'
                          AND (website_accounts.hideprofile IS NULL OR website_accounts.hideprofile = 0)
                        ORDER BY account.username ASC
                    ");
                    $stmtRecipients->execute([(int)$user['id']]);
                    $state['pmRecipientOptions'] = $stmtRecipients->fetchAll(PDO::FETCH_COLUMN, 0) ?: array();
                }
            } catch (Throwable $e) {
                error_log('[account.pms] Recipient picker lookup failed: ' . $e->getMessage());
            }

            if ($state['isReplyMode']) {
                $stmt = $pmsPdo->prepare("
                    SELECT pms.*, s.username AS sender, r.username AS receiver
                    FROM website_pms AS pms
                    LEFT JOIN account AS s ON pms.sender_id = s.id
                    LEFT JOIN account AS r ON pms.owner_id = r.id
                    WHERE pms.id = ?
                    LIMIT 1
                ");
                $stmt->execute([(int)$get['reply']]);
                $state['content'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: array('message' => '', 'sender' => '');

                if ($state['content']) {
                    if ((int)($state['content']['owner_id'] ?? 0) === (int)$user['id'] && empty($state['content']['showed'])) {
                        $stmtMarkReplyRead = $pmsPdo->prepare("UPDATE website_pms SET showed = 1 WHERE id = ? AND owner_id = ? LIMIT 1");
                        $stmtMarkReplyRead->execute([(int)$state['content']['id'], (int)$user['id']]);
                        $state['content']['showed'] = 1;
                    }
                    $state['content']['sender'] = $state['content']['sender'];
                    $state['content']['message'] = '';
                }
            } else {
                $state['pathway_info'][] = array('title' => 'New Message', 'link' => '');
                if (!empty($_GETVARS['to'])) {
                    $state['content']['sender'] = RemoveXSS($_GETVARS['to']);
                }
            }
        }

        return $state;
    }
}
