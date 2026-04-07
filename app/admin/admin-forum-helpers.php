<?php

if (!function_exists('spp_admin_forum_action_url')) {
    function spp_admin_forum_action_url(array $params)
    {
        return spp_action_url('index.php', $params, 'admin_forum');
    }
}

if (!function_exists('spp_admin_forum_action_button')) {
    function spp_admin_forum_action_button(array $params, string $label, string $csrfToken, string $className = 'forum-admin__pill', string $confirmMessage = '')
    {
        $html = '<form method="post" action="index.php?n=admin&amp;sub=forum" class="forum-admin__inline-form">';
        $html .= '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';

        foreach ($params as $key => $value) {
            $html .= '<input type="hidden" name="' . htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') . '" value="'
                . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '">';
        }

        $confirmAttr = '';
        if ($confirmMessage !== '') {
            $confirmAttr = ' onclick="return confirm(' . htmlspecialchars((string)json_encode($confirmMessage), ENT_QUOTES, 'UTF-8') . ');"';
        }

        $html .= '<button type="submit" class="' . htmlspecialchars($className, ENT_QUOTES, 'UTF-8') . '"' . $confirmAttr . '>'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</button>';
        $html .= '</form>';

        return $html;
    }
}

if (!function_exists('spp_admin_forum_redirect_url')) {
    function spp_admin_forum_redirect_url(array $request = array())
    {
        $params = array(
            'n' => 'admin',
            'sub' => 'forum',
        );

        $catId = (int)($request['cat_id'] ?? 0);
        $forumId = (int)($request['forum_id'] ?? 0);
        $topicId = (int)($request['topic_id'] ?? 0);

        if ($catId > 0) {
            $params['cat_id'] = $catId;
        }

        if ($forumId > 0) {
            $params['forum_id'] = $forumId;
        }

        if ($topicId > 0) {
            $params['topic_id'] = $topicId;
        }

        return 'index.php?' . http_build_query($params);
    }
}

if (!function_exists('spp_admin_forum_filter_category_fields')) {
    function spp_admin_forum_filter_category_fields(array $data)
    {
        $allowed = array('cat_name', 'cat_disp_position');
        return spp_filter_allowed_fields($data, $allowed);
    }
}

if (!function_exists('spp_admin_forum_filter_forum_fields')) {
    function spp_admin_forum_filter_forum_fields(array $data)
    {
        $allowed = array('cat_id', 'forum_name', 'forum_desc', 'disp_position');
        return spp_filter_allowed_fields($data, $allowed);
    }
}

if (!function_exists('spp_admin_forum_recount')) {
    function spp_admin_forum_recount(PDO $pdo, int $forumId)
    {
        $stmt = $pdo->prepare("SELECT count(*) FROM f_topics WHERE forum_id=?");
        $stmt->execute(array($forumId));
        $topicCount = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT count(*) FROM f_topics RIGHT JOIN f_posts ON f_topics.topic_id=f_posts.topic_id WHERE forum_id=?");
        $stmt->execute(array($forumId));
        $postCount = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT topic_id FROM f_topics WHERE forum_id=? ORDER BY last_post DESC LIMIT 1");
        $stmt->execute(array($forumId));
        $lastTopicId = $stmt->fetchColumn();

        $stmt = $pdo->prepare("UPDATE f_forums SET num_topics=?,num_posts=?,last_topic_id=? WHERE forum_id=? LIMIT 1");
        $stmt->execute(array($topicCount, $postCount, $lastTopicId, $forumId));
    }
}

if (!function_exists('spp_admin_forum_move_up')) {
    function spp_admin_forum_move_up(PDO $pdo, int $catId, int $forumId = 0)
    {
        if ($forumId > 0) {
            $stmt = $pdo->prepare("SELECT disp_position FROM f_forums WHERE forum_id=?");
            $stmt->execute(array($forumId));
            $currentPosition = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM f_forums WHERE disp_position<? AND cat_id=? ORDER BY disp_position DESC LIMIT 1");
            $stmt->execute(array($currentPosition, $catId));
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (empty($target['forum_id'])) {
                return;
            }

            $stmt = $pdo->prepare("UPDATE f_forums SET disp_position=? WHERE forum_id=? LIMIT 1");
            $stmt->execute(array($target['disp_position'], $forumId));
            $stmt->execute(array($currentPosition, (int)$target['forum_id']));
            return;
        }

        $stmt = $pdo->prepare("SELECT cat_disp_position FROM f_categories WHERE cat_id=?");
        $stmt->execute(array($catId));
        $currentPosition = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM f_categories WHERE cat_disp_position<? ORDER BY cat_disp_position DESC LIMIT 1");
        $stmt->execute(array($currentPosition));
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($target['cat_id'])) {
            return;
        }

        $stmt = $pdo->prepare("UPDATE f_categories SET cat_disp_position=? WHERE cat_id=? LIMIT 1");
        $stmt->execute(array($target['cat_disp_position'], $catId));
        $stmt->execute(array($currentPosition, (int)$target['cat_id']));
    }
}

if (!function_exists('spp_admin_forum_move_down')) {
    function spp_admin_forum_move_down(PDO $pdo, int $catId, int $forumId = 0)
    {
        if ($forumId > 0) {
            $stmt = $pdo->prepare("SELECT disp_position FROM f_forums WHERE forum_id=?");
            $stmt->execute(array($forumId));
            $currentPosition = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM f_forums WHERE disp_position>? AND cat_id=? ORDER BY disp_position ASC LIMIT 1");
            $stmt->execute(array($currentPosition, $catId));
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (empty($target['forum_id'])) {
                return;
            }

            $stmt = $pdo->prepare("UPDATE f_forums SET disp_position=? WHERE forum_id=? LIMIT 1");
            $stmt->execute(array($target['disp_position'], $forumId));
            $stmt->execute(array($currentPosition, (int)$target['forum_id']));
            return;
        }

        $stmt = $pdo->prepare("SELECT cat_disp_position FROM f_categories WHERE cat_id=?");
        $stmt->execute(array($catId));
        $currentPosition = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM f_categories WHERE cat_disp_position>? ORDER BY cat_disp_position ASC LIMIT 1");
        $stmt->execute(array($currentPosition));
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($target['cat_id'])) {
            return;
        }

        $stmt = $pdo->prepare("UPDATE f_categories SET cat_disp_position=? WHERE cat_id=? LIMIT 1");
        $stmt->execute(array($target['cat_disp_position'], $catId));
        $stmt->execute(array($currentPosition, (int)$target['cat_id']));
    }
}

if (!function_exists('spp_admin_forum_delete_forum')) {
    function spp_admin_forum_delete_forum(PDO $pdo, int $forumId)
    {
        $stmt = $pdo->prepare("SELECT topic_id FROM f_topics WHERE forum_id=?");
        $stmt->execute(array($forumId));
        $forumTopics = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!empty($forumTopics)) {
            $placeholders = implode(',', array_fill(0, count($forumTopics), '?'));
            $stmt = $pdo->prepare("DELETE FROM f_posts WHERE topic_id IN ($placeholders)");
            $stmt->execute(array_map('intval', $forumTopics));
        }

        $stmt = $pdo->prepare("DELETE FROM f_topics WHERE forum_id=?");
        $stmt->execute(array($forumId));

        $stmt = $pdo->prepare("DELETE FROM f_forums WHERE forum_id=?");
        $stmt->execute(array($forumId));
    }
}

if (!function_exists('spp_admin_forum_delete_category')) {
    function spp_admin_forum_delete_category(PDO $pdo, int $catId)
    {
        $stmt = $pdo->prepare("SELECT forum_id FROM f_forums WHERE cat_id=?");
        $stmt->execute(array($catId));
        $forumIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($forumIds as $forumId) {
            spp_admin_forum_delete_forum($pdo, (int)$forumId);
        }

        $stmt = $pdo->prepare("DELETE FROM f_categories WHERE cat_id=?");
        $stmt->execute(array($catId));
    }
}
