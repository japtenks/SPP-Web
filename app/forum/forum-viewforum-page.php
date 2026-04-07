<?php

require_once __DIR__ . '/../../components/forum/forum.func.php';

if (!function_exists('spp_forum_load_viewforum_page_state')) {
    function spp_forum_load_viewforum_page_state(array $args = array()): array
    {
        $realmMap = (array)($args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $user = (array)($args['user'] ?? ($GLOBALS['user'] ?? array()));
        $page = max(1, (int)($args['page'] ?? 1));
        $get = (array)($args['get'] ?? $_GET);

        $state = array(
            '__stop' => false,
            'this_forum' => array(),
            'topics' => array(),
            'p' => $page,
        );

        $forumId = isset($get['fid']) ? (int)$get['fid'] : 0;
        $state['this_forum'] = get_forum_byid($forumId);
        if ((int)($state['this_forum']['forum_id'] ?? 0) <= 0) {
            output_message('alert', 'This forum does not exist.');
            $state['__stop'] = true;
            return $state;
        }

        $state['this_forum']['linktonewtopic'] = spp_forum_url_with_site_href('post', array(
            'action' => 'newtopic',
            'f' => (int)$state['this_forum']['forum_id'],
        ));
        $state['this_forum']['linktomarkread'] = spp_forum_url_with_site_href('viewforum', array(
            'fid' => (int)$state['this_forum']['forum_id'],
            'markread' => 1,
        ));

        $GLOBALS['pathway_info'][] = array(
            'title' => $state['this_forum']['forum_name'],
            'link' => '',
        );

        $realmId = spp_forum_target_realm_id($state['this_forum'], $realmMap, spp_resolve_realm_id($realmMap));
        $forumPdo = spp_get_pdo('realmd', $realmId);
        $postingBlockedReason = '';

        $newsForumId = (int)spp_config_forum('news_forum_id', 0);
        $isNewsForum = $newsForumId > 0 && (int)$state['this_forum']['forum_id'] === $newsForumId;

        if ((int)($user['id'] ?? 0) <= 0) {
            $postingBlockedReason = 'You must be logged in to start a topic.';
        } elseif ($isNewsForum && !spp_forum_can_publish_news($user)) {
            $postingBlockedReason = 'This is the News forum. Only GMs can start topics here.';
        }

        $state['this_forum']['can_start_topic'] = ($postingBlockedReason === '') && empty($state['this_forum']['closed']);
        $state['this_forum']['posting_block_reason'] = $postingBlockedReason;
        $state['this_forum']['posting_realm_name'] = (string)(spp_get_armory_realm_name($realmId) ?? ('Realm ' . $realmId));

        list($topicsmark, $mark) = spp_forum_prepare_viewforum_marker($forumPdo, $user, $state['this_forum']);

        $allowedTopicPageSizes = array(10, 25, 50);
        $requestedTopicPageSize = isset($get['per_page']) ? (int)$get['per_page'] : 0;
        $itemsPerPage = in_array($requestedTopicPageSize, $allowedTopicPageSizes, true)
            ? $requestedTopicPageSize
            : (int)spp_config_generic('topics_per_page', 25);
        if (!in_array($itemsPerPage, $allowedTopicPageSizes, true)) {
            $itemsPerPage = 25;
        }

        $itemCount = (int)($state['this_forum']['num_topics'] ?? 0);
        $pageCount = max(1, (int)ceil($itemCount / $itemsPerPage));
        $limitStart = max(0, ($page - 1) * $itemsPerPage);

        $state['this_forum']['pnum'] = $pageCount;
        $state['this_forum']['items_per_page'] = $itemsPerPage;
        $state['this_forum']['allowed_page_sizes'] = $allowedTopicPageSizes;

        $allowedSortFields = array(
            'subject' => 'f_topics.topic_name',
            'author' => 'topic_author_display',
            'posted' => 'f_topics.topic_posted',
            'replies' => 'f_topics.num_replies',
            'views' => 'f_topics.num_views',
            'last_reply' => 'f_topics.last_post',
        );
        $requestedSort = isset($get['sort']) ? (string)$get['sort'] : 'posted';
        $sortField = isset($allowedSortFields[$requestedSort]) ? $requestedSort : 'posted';
        $requestedDir = isset($get['dir']) ? strtolower((string)$get['dir']) : 'desc';
        $sortDir = ($requestedDir === 'asc') ? 'ASC' : 'DESC';

        $state['this_forum']['sort_field'] = $sortField;
        $state['this_forum']['sort_dir'] = strtolower($sortDir);

        $state['topics'] = spp_forum_build_viewforum_topics(
            $forumPdo,
            $state['this_forum'],
            $user,
            $topicsmark,
            $mark,
            $itemsPerPage,
            $limitStart,
            $sortField,
            $sortDir
        );

        return $state;
    }
}
