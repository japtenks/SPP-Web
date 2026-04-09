<?php

if (!function_exists('spp_admin_forum_build_view')) {
    function spp_admin_forum_build_view(PDO $forumPdo): array
    {
        $catId = (int)($_GET['cat_id'] ?? 0);
        $forumId = (int)($_GET['forum_id'] ?? 0);
        $topicId = (int)($_GET['topic_id'] ?? 0);
        $realmDbMap = (array)($GLOBALS['realmDbMap'] ?? array());
        $realmOptions = spp_admin_forum_realm_options($realmDbMap);
        $realmForumSummaries = array();

        foreach ($realmOptions as $realmOption) {
            $realmOptionId = (int)($realmOption['realm_id'] ?? 0);
            $managedRows = $realmOptionId > 0 ? spp_admin_forum_realm_managed_forum_rows($forumPdo, $realmDbMap, $realmOptionId) : array();
            $realmForumSummaries[] = array(
                'realm_id' => $realmOptionId,
                'realm_name' => (string)($realmOption['realm_name'] ?? ('Realm #' . $realmOptionId)),
                'expansion_key' => (string)($realmOption['expansion_key'] ?? ''),
                'managed_forum_count' => count($managedRows),
                'managed_forums' => $managedRows,
            );
        }

        $view = array(
            'view_mode' => 'categories',
            'items' => array(),
            'this_forum' => null,
            'this_topic' => null,
            'forum_notice' => trim((string)($_GET['forum_notice'] ?? '')),
            'realm_forum_tools' => array(
                'realm_options' => $realmOptions,
                'realm_summaries' => $realmForumSummaries,
            ),
            'request' => array(
                'cat_id' => $catId,
                'forum_id' => $forumId,
                'topic_id' => $topicId,
            ),
            'pathway_info' => array(
                array('title' => 'Forum Management', 'link' => 'index.php?n=admin&sub=forum'),
            ),
        );

        if ($catId > 0) {
            $view['view_mode'] = 'category';
            $stmt = $forumPdo->prepare("
                SELECT * FROM f_forums
                JOIN f_categories ON f_forums.cat_id=f_categories.cat_id
                WHERE f_forums.cat_id=? ORDER BY disp_position,forum_name");
            $stmt->execute(array($catId));
            $view['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($view['items'])) {
                $view['pathway_info'][] = array('title' => $view['items'][0]['cat_name'], 'link' => '');
            }
            return $view;
        }

        if ($forumId > 0 && $topicId > 0) {
            $view['view_mode'] = 'topic';
            $stmt = $forumPdo->prepare("SELECT * FROM f_topics WHERE topic_id=? LIMIT 1");
            $stmt->execute(array($topicId));
            $view['this_topic'] = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $forumPdo->prepare("SELECT * FROM f_forums WHERE forum_id=? LIMIT 1");
            $stmt->execute(array($forumId));
            $view['this_forum'] = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $forumPdo->prepare("SELECT post_id, poster, posted, LEFT(message,120) AS excerpt FROM f_posts WHERE topic_id=? ORDER BY posted");
            $stmt->execute(array($topicId));
            $view['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($view['this_forum'])) {
                $view['pathway_info'][] = array('title' => $view['this_forum']['forum_name'], 'link' => 'index.php?n=admin&sub=forum&forum_id=' . $forumId);
            }
            if (!empty($view['this_topic'])) {
                $view['pathway_info'][] = array('title' => $view['this_topic']['topic_name'], 'link' => '');
            }
            return $view;
        }

        if ($forumId > 0) {
            $view['view_mode'] = 'forum';
            $stmt = $forumPdo->prepare("SELECT * FROM f_forums WHERE forum_id=? LIMIT 1");
            $stmt->execute(array($forumId));
            $view['this_forum'] = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $forumPdo->prepare("SELECT topic_id, topic_name, topic_poster, topic_posted, num_replies FROM f_topics WHERE forum_id=? ORDER BY topic_posted DESC");
            $stmt->execute(array($forumId));
            $view['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($view['this_forum'])) {
                $view['pathway_info'][] = array('title' => $view['this_forum']['forum_name'], 'link' => '');
            }
            return $view;
        }

        $view['pathway_info'][] = array('title' => 'Categories', 'link' => '');
        $stmt = $forumPdo->query("SELECT * FROM f_categories ORDER BY cat_disp_position,cat_name");
        $view['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $view;
    }
}
