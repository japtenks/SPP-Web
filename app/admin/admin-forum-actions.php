<?php

if (!function_exists('spp_admin_forum_handle_action')) {
    function spp_admin_forum_handle_action(PDO $forumPdo): void
    {
        $requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $action = $requestMethod === 'POST'
            ? (string)($_POST['action'] ?? $_GET['action'] ?? '')
            : (string)($_GET['action'] ?? '');
        if ($action === '' || $action === '0') {
            return;
        }

        if ($requestMethod !== 'POST') {
            return;
        }

        $requestData = array_merge($_GET, $_POST);
        $returnUrl = spp_admin_forum_redirect_url($requestData);

        if ($action === 'moveup') {
            spp_require_csrf('admin_forum');
            spp_admin_forum_move_up($forumPdo, (int)($_POST['cat_id'] ?? 0), (int)($_POST['forum_id'] ?? 0));
            redirect('index.php?n=admin&sub=forum', 1);
            exit;
        }

        if ($action === 'movedown') {
            spp_require_csrf('admin_forum');
            spp_admin_forum_move_down($forumPdo, (int)($_POST['cat_id'] ?? 0), (int)($_POST['forum_id'] ?? 0));
            redirect('index.php?n=admin&sub=forum', 1);
            exit;
        }

        if ($action === 'open' || $action === 'close') {
            spp_require_csrf('admin_forum');
            $closed = $action === 'close' ? 1 : 0;
            $stmt = $forumPdo->prepare("UPDATE f_forums SET closed=? WHERE forum_id=? LIMIT 1");
            $stmt->execute(array($closed, (int)($_POST['forum_id'] ?? 0)));
            redirect($returnUrl, 1);
            exit;
        }

        if ($action === 'show' || $action === 'hide') {
            spp_require_csrf('admin_forum');
            $hidden = $action === 'hide' ? 1 : 0;
            $stmt = $forumPdo->prepare("UPDATE f_forums SET hidden=? WHERE forum_id=? LIMIT 1");
            $stmt->execute(array($hidden, (int)($_POST['forum_id'] ?? 0)));
            redirect($returnUrl, 1);
            exit;
        }

        if ($action === 'updforumsorder') {
            spp_require_csrf('admin_forum');
            $stmt = $forumPdo->prepare("UPDATE f_forums SET disp_position=? WHERE forum_id=? LIMIT 1");
            foreach (($_POST['forumorder'] ?? array()) as $forumId => $order) {
                $stmt->execute(array((int)$order, (int)$forumId));
            }
            redirect($returnUrl, 1);
            exit;
        }

        if ($action === 'newcat') {
            spp_require_csrf('admin_forum');
            $data = spp_admin_forum_filter_category_fields($_POST);
            if (!empty($data)) {
                $setClause = implode(',', array_map(function ($key) {
                    return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $key) . '`=?';
                }, array_keys($data)));
                $stmt = $forumPdo->prepare("INSERT INTO f_categories SET $setClause");
                $stmt->execute(array_values($data));
            }
            redirect($returnUrl, 1);
            exit;
        }

        if ($action === 'renamecat') {
            spp_require_csrf('admin_forum');
            $catId = (int)($_POST['cat_id'] ?? 0);
            $catName = trim((string)($_POST['cat_name'] ?? ''));
            if ($catId > 0 && $catName !== '') {
                $stmt = $forumPdo->prepare("UPDATE f_categories SET cat_name=? WHERE cat_id=? LIMIT 1");
                $stmt->execute(array($catName, $catId));
            }
            redirect($returnUrl, 1);
            exit;
        }

        if ($action === 'newforum') {
            spp_require_csrf('admin_forum');
            $data = spp_admin_forum_filter_forum_fields($_POST);
            if (!empty($data)) {
                $setClause = implode(',', array_map(function ($key) {
                    return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $key) . '`=?';
                }, array_keys($data)));
                $stmt = $forumPdo->prepare("INSERT INTO f_forums SET $setClause");
                $stmt->execute(array_values($data));
            }
            redirect($returnUrl, 1);
            exit;
        }

        if ($action === 'spawnrealmforums') {
            spp_require_csrf('admin_forum');
            $realmDbMap = (array)($GLOBALS['realmDbMap'] ?? array());
            $realmId = (int)($_POST['realm_id'] ?? 0);
            $result = spp_admin_forum_create_realm_forum_set($forumPdo, $realmDbMap, $realmId);
            $notice = 'Realm forum set updated.';
            if (!empty($result['missing_categories'])) {
                $notice = 'Missing forum sections: ' . implode(', ', array_map('strval', (array)$result['missing_categories'])) . '.';
            } elseif ((int)($result['created'] ?? 0) > 0 && (int)($result['skipped'] ?? 0) > 0) {
                $notice = 'Created ' . (int)$result['created'] . ' realm forums for ' . (string)$result['realm_name'] . ' and skipped existing ones.';
            } elseif ((int)($result['created'] ?? 0) > 0) {
                $notice = 'Created ' . (int)$result['created'] . ' realm forums for ' . (string)$result['realm_name'] . '.';
            } elseif ((int)($result['skipped'] ?? 0) > 0) {
                $notice = 'Realm forums for ' . (string)$result['realm_name'] . ' already exist in the standard sections.';
            }
            redirect(spp_admin_forum_redirect_url_with_notice($requestData, $notice), 1);
            exit;
        }

        if ($action === 'removerealmforums') {
            spp_require_csrf('admin_forum');
            $realmDbMap = (array)($GLOBALS['realmDbMap'] ?? array());
            $realmId = (int)($_POST['realm_id'] ?? 0);
            $result = spp_admin_forum_remove_realm_forum_set($forumPdo, $realmDbMap, $realmId);
            $notice = (int)($result['removed'] ?? 0) > 0
                ? 'Removed ' . (int)$result['removed'] . ' managed realm forums for ' . (string)$result['realm_name'] . '.'
                : 'No managed realm forums were found for ' . (string)$result['realm_name'] . '.';
            redirect(spp_admin_forum_redirect_url_with_notice($requestData, $notice), 1);
            exit;
        }

        if ($action === 'resetrealmforums') {
            spp_require_csrf('admin_forum');
            $realmDbMap = (array)($GLOBALS['realmDbMap'] ?? array());
            $realmId = (int)($_POST['realm_id'] ?? 0);
            $removed = spp_admin_forum_remove_realm_forum_set($forumPdo, $realmDbMap, $realmId);
            $created = spp_admin_forum_create_realm_forum_set($forumPdo, $realmDbMap, $realmId);
            $notice = 'Reset realm forums for ' . (string)($created['realm_name'] ?? $removed['realm_name'] ?? ('Realm #' . $realmId)) . '.';
            if (!empty($created['missing_categories'])) {
                $notice = 'Reset removed ' . (int)($removed['removed'] ?? 0) . ' realm forums, but these sections are missing: ' . implode(', ', array_map('strval', (array)$created['missing_categories'])) . '.';
            } else {
                $notice = 'Reset realm forums for ' . (string)($created['realm_name'] ?? ('Realm #' . $realmId)) . ': removed ' . (int)($removed['removed'] ?? 0) . ', created ' . (int)($created['created'] ?? 0) . '.';
            }
            redirect(spp_admin_forum_redirect_url_with_notice($requestData, $notice), 1);
            exit;
        }

        if ($action === 'renameforum') {
            spp_require_csrf('admin_forum');
            $forumId = (int)($_POST['forum_id'] ?? 0);
            $forumName = trim((string)($_POST['forum_name'] ?? ''));
            if ($forumId > 0 && $forumName !== '') {
                $stmt = $forumPdo->prepare("UPDATE f_forums SET forum_name=? WHERE forum_id=? LIMIT 1");
                $stmt->execute(array($forumName, $forumId));
            }
            redirect($returnUrl, 1);
            exit;
        }

        if ($action === 'recount') {
            spp_require_csrf('admin_forum');
            spp_admin_forum_recount($forumPdo, (int)($_POST['forum_id'] ?? 0));
            redirect($returnUrl, 1);
            exit;
        }

        if ($action === 'deleteforum') {
            spp_require_csrf('admin_forum');
            spp_admin_forum_delete_forum($forumPdo, (int)($_POST['forum_id'] ?? 0));
            redirect('index.php?n=admin&sub=forum', 1);
            exit;
        }

        if ($action === 'deletecat') {
            spp_require_csrf('admin_forum');
            spp_admin_forum_delete_category($forumPdo, (int)($_POST['cat_id'] ?? 0));
            redirect('index.php?n=admin&sub=forum', 1);
            exit;
        }

        if ($action === 'deletetopic') {
            spp_require_csrf('admin_forum');
            $topicId = (int)($_POST['topic_id'] ?? 0);
            $forumId = (int)($_POST['forum_id'] ?? 0);
            $stmt = $forumPdo->prepare("SELECT num_replies FROM f_topics WHERE topic_id=? LIMIT 1");
            $stmt->execute(array($topicId));
            $numReplies = (int)$stmt->fetchColumn();

            $stmt = $forumPdo->prepare("DELETE FROM f_posts WHERE topic_id=?");
            $stmt->execute(array($topicId));
            $stmt = $forumPdo->prepare("DELETE FROM f_topics WHERE topic_id=? LIMIT 1");
            $stmt->execute(array($topicId));
            $stmt = $forumPdo->prepare("UPDATE f_forums SET num_topics=GREATEST(0,num_topics-1), num_posts=GREATEST(0,num_posts-(?+1)) WHERE forum_id=? LIMIT 1");
            $stmt->execute(array($numReplies, $forumId));
            redirect('index.php?n=admin&sub=forum&forum_id=' . $forumId, 1);
            exit;
        }

        if ($action === 'deletepost') {
            spp_require_csrf('admin_forum');
            $postId = (int)($_POST['post_id'] ?? 0);
            $topicId = (int)($_POST['topic_id'] ?? 0);
            $forumId = (int)($_POST['forum_id'] ?? 0);

            $stmt = $forumPdo->prepare("DELETE FROM f_posts WHERE post_id=? LIMIT 1");
            $stmt->execute(array($postId));
            $stmt = $forumPdo->prepare("UPDATE f_topics SET num_replies=GREATEST(0,num_replies-1) WHERE topic_id=? LIMIT 1");
            $stmt->execute(array($topicId));
            $stmt = $forumPdo->prepare("UPDATE f_forums SET num_posts=GREATEST(0,num_posts-1) WHERE forum_id=? LIMIT 1");
            $stmt->execute(array($forumId));
            redirect('index.php?n=admin&sub=forum&forum_id=' . $forumId . '&topic_id=' . $topicId, 1);
            exit;
        }
    }
}
