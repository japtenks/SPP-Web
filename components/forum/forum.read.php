<?php

if (!function_exists('spp_forum_has_markread_table')) {
    function spp_forum_has_markread_table(PDO $forumPdo): bool
    {
        return function_exists('spp_db_table_exists') && spp_db_table_exists($forumPdo, 'f_markread');
    }
}

function spp_forum_profile_url($primaryName)
{
    $profileName = trim((string)$primaryName);
    if ($profileName === '') {
        return '';
    }

    return spp_config_temp_string('site_href', '') . spp_route_url('account', 'view', array('action' => 'find', 'name' => $profileName), false);
}

function spp_forum_identity_schema_support(PDO $forumPdo): array
{
    static $cache = array();

    $key = spl_object_hash($forumPdo);
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $supports = array(
        'f_posts.poster_identity_id' => false,
        'website_identity_profiles' => false,
        'website_identity_profiles.identity_id' => false,
        'website_identity_profiles.signature' => false,
    );

    if (function_exists('spp_db_column_exists')) {
        $supports['f_posts.poster_identity_id'] = spp_db_column_exists($forumPdo, 'f_posts', 'poster_identity_id');
        $supports['website_identity_profiles.identity_id'] = spp_db_column_exists($forumPdo, 'website_identity_profiles', 'identity_id');
        $supports['website_identity_profiles.signature'] = spp_db_column_exists($forumPdo, 'website_identity_profiles', 'signature');
    }
    if (function_exists('spp_db_table_exists')) {
        $supports['website_identity_profiles'] = spp_db_table_exists($forumPdo, 'website_identity_profiles');
    }

    return $cache[$key] = $supports;
}

function spp_forum_prepare_post_rows(PDO $forumPdo, int $topicId, int $limitStart, int $itemsPerPage): array
{
    $identitySupport = spp_forum_identity_schema_support($forumPdo);

    $selectParts = array(
        'f_posts.*',
        'account.*',
        'website_accounts.*',
        'website_accounts.avatar AS website_avatar',
        'website_accounts.signature AS website_signature',
        "'' AS identity_signature",
    );
    $joins = array(
        'LEFT JOIN account ON f_posts.poster_id=account.id',
        'LEFT JOIN website_accounts ON f_posts.poster_id=website_accounts.account_id',
        'LEFT JOIN website_account_groups ON website_accounts.g_id = website_account_groups.g_id',
    );

    if (
        !empty($identitySupport['f_posts.poster_identity_id'])
        && !empty($identitySupport['website_identity_profiles'])
        && !empty($identitySupport['website_identity_profiles.identity_id'])
        && !empty($identitySupport['website_identity_profiles.signature'])
    ) {
        $selectParts[count($selectParts) - 1] = 'website_identity_profiles.signature AS identity_signature';
        $joins[] = 'LEFT JOIN website_identity_profiles ON f_posts.poster_identity_id=website_identity_profiles.identity_id';
    }

    $sql = "
        SELECT
            " . implode(",\n            ", $selectParts) . "
        FROM f_posts
        " . implode("\n        ", $joins) . "
        WHERE topic_id=?
        ORDER BY posted
        LIMIT " . (int)$limitStart . ',' . (int)$itemsPerPage;

    $stmtPosts = $forumPdo->prepare($sql);
    $stmtPosts->execute(array($topicId));
    $rows = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);

    if (empty($identitySupport['f_posts.poster_identity_id'])) {
        foreach ($rows as &$row) {
            if (!array_key_exists('poster_identity_id', $row)) {
                $row['poster_identity_id'] = 0;
            }
        }
        unset($row);
    }

    return $rows ?: array();
}

function spp_forum_fetch_identity_meta(PDO $forumPdo, array $rows, string $context = 'forum.read'): array
{
    $posterIdentityMeta = array();
    if (empty($rows)) {
        return $posterIdentityMeta;
    }

    $identityIds = array();
    foreach ($rows as $row) {
        $identityId = (int)($row['poster_identity_id'] ?? 0);
        if ($identityId > 0) {
            $identityIds[] = $identityId;
        }
    }

    $identityIds = array_values(array_unique($identityIds));
    if (empty($identityIds)) {
        return $posterIdentityMeta;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($identityIds), '?'));
        $stmtIdentityMeta = $forumPdo->prepare("
            SELECT identity_id, identity_type, is_bot
            FROM website_identities
            WHERE identity_id IN ({$placeholders})
        ");
        $stmtIdentityMeta->execute($identityIds);
        foreach ($stmtIdentityMeta->fetchAll(PDO::FETCH_ASSOC) as $identityRow) {
            $posterIdentityMeta[(int)$identityRow['identity_id']] = array(
                'identity_type' => (string)($identityRow['identity_type'] ?? ''),
                'is_bot' => (int)($identityRow['is_bot'] ?? 0),
            );
        }
    } catch (Throwable $e) {
        error_log('[' . $context . '] Identity meta lookup failed: ' . $e->getMessage());
    }

    return $posterIdentityMeta;
}

function spp_forum_hydrate_topic_posts(
    PDO $forumPdo,
    PDO $charPdo,
    array $rows,
    int $realmId,
    string $topicLinkBase,
    int $pageOffset = 0,
    bool $includeRichMeta = false
): array {
    $posts = array();
    $posterPostCountCache = array();
    $posterIdentityMeta = spp_forum_fetch_identity_meta($forumPdo, $rows, 'forum.read');
    $postIndex = 0;

    foreach ($rows as $curPost) {
        if ($includeRichMeta) {
            $pmTarget = trim((string)($curPost['username'] ?? ''));
            $curPost['linktoprofile'] = spp_forum_profile_url($pmTarget);
            $curPost['linktopms'] = $pmTarget !== ''
                ? spp_config_temp_string('site_href', '') . spp_route_url('account', 'pms', array('action' => 'add', 'to' => $pmTarget), false)
                : '';
            $curPost['linktoedit'] = spp_forum_url_with_site_href('post', array('action' => 'editpost', 'post' => (int)$curPost['post_id']));
            $curPost['linktodelete'] = spp_forum_action_url(spp_forum_url_with_site_href('post', array('action' => 'dodeletepost', 'post' => (int)$curPost['post_id'])));
        }

        $curPost['linktothis'] = $topicLinkBase . '&to=' . $curPost['post_id'];
        if (!empty($curPost['poster_character_id'])) {
            $curPost['linktocharacter_social'] = spp_config_temp_string('site_href', '')
                . spp_route_url('server', 'character', array(
                    'realm' => (int)$realmId,
                    'guid' => (int)$curPost['poster_character_id'],
                    'tab' => 'social',
                ), false);
        } else {
            $curPost['linktocharacter_social'] = $curPost['linktoprofile'] ?? '';
        }

        $curPost['avatar'] = '';
        $stmtChar = $charPdo->prepare("
            SELECT c.race, c.class, c.level, c.gender, g.name AS guild
            FROM characters c
            LEFT JOIN guild_member gm ON c.guid = gm.guid
            LEFT JOIN guild g ON gm.guildid = g.guildid
            WHERE c.guid = ?
        ");
        $stmtChar->execute([(int)$curPost['poster_character_id']]);
        $charinfo = $stmtChar->fetch(PDO::FETCH_ASSOC);

        $uploadedAvatar = '';
        if (!empty($curPost['website_avatar'])) {
            $uploadedAvatar = (string)$curPost['website_avatar'];
        }

        if ($uploadedAvatar !== '') {
            $curPost['avatar'] = 'uploads/avatars/' . rawurlencode(basename($uploadedAvatar));
        }

        if (!empty($curPost['identity_signature'])) {
            $curPost['signature'] = $curPost['identity_signature'];
        } elseif (!empty($curPost['website_signature'])) {
            $curPost['signature'] = $curPost['website_signature'];
        }

        if ($curPost['avatar'] === '' && !empty($charinfo)) {
            $curPost['avatar'] = get_character_portrait_path(
                $curPost['poster_character_id'],
                $charinfo['gender'],
                $charinfo['race'],
                $charinfo['class']
            );
        }

        if (!empty($charinfo)) {
            $curPost['level'] = (int)$charinfo['level'];
            $curPost['guild'] = $charinfo['guild'] ?? '';
            if ($includeRichMeta) {
                $curPost['mini_race'] = $charinfo['race'] . '-' . $charinfo['gender'] . '.gif';
                $curPost['mini_class'] = $charinfo['class'] . '.gif';
                $curPost['faction'] = in_array((int)$charinfo['race'], array(1, 3, 4, 7, 11), true) ? 'alliance.gif' : 'horde.gif';
            }
        } else {
            if ($curPost['avatar'] === '') {
                $curPost['avatar'] = get_forum_avatar_fallback($curPost['poster'] ?? '');
            }
            $curPost['level'] = 0;
            $curPost['guild'] = '';
            if ($includeRichMeta) {
                $curPost['mini_race'] = '';
                $curPost['mini_class'] = '';
                $curPost['faction'] = '';
            }
        }

        $posterId = (int)($curPost['poster_id'] ?? 0);
        $posterIdentityId = (int)($curPost['poster_identity_id'] ?? 0);
        $identityMeta = $posterIdentityMeta[$posterIdentityId] ?? null;
        $countByIdentity = $posterIdentityId > 0 && !empty($identityMeta)
            && (((int)$identityMeta['is_bot']) === 1 || ($identityMeta['identity_type'] ?? '') === 'bot_character');

        if ($countByIdentity) {
            $cacheKey = 'identity:' . $posterIdentityId;
            if (!isset($posterPostCountCache[$cacheKey])) {
                $stmtPostCount = $forumPdo->prepare("SELECT COUNT(*) FROM f_posts WHERE poster_identity_id = ?");
                $stmtPostCount->execute([$posterIdentityId]);
                $posterPostCountCache[$cacheKey] = (int)$stmtPostCount->fetchColumn();
            }
            $curPost['forum_post_count'] = $posterPostCountCache[$cacheKey];
        } elseif ($posterId > 0) {
            $cacheKey = 'account:' . $posterId;
            if (!isset($posterPostCountCache[$cacheKey])) {
                $stmtPostCount = $forumPdo->prepare("SELECT COUNT(*) FROM f_posts WHERE poster_id = ?");
                $stmtPostCount->execute([$posterId]);
                $posterPostCountCache[$cacheKey] = (int)$stmtPostCount->fetchColumn();
            }
            $curPost['forum_post_count'] = $posterPostCountCache[$cacheKey];
        } else {
            $curPost['forum_post_count'] = 0;
        }

        $postIndex++;
        $curPost['pos_num'] = $pageOffset + $postIndex;

        $postedTs = (int)($curPost['posted'] ?? 0);
        if ($includeRichMeta) {
            global $yesterday_ts;
            if (date('d', $postedTs) == date('d') && $_SERVER['REQUEST_TIME'] - $postedTs < 86400) {
                $curPost['posted'] = 'Today at ' . date('H:i', $postedTs);
            } elseif (date('d', $postedTs) == date('d', $yesterday_ts) && $_SERVER['REQUEST_TIME'] - $postedTs < 2 * 86400) {
                $curPost['posted'] = 'Yesterday at ' . date('H:i', $postedTs);
            } else {
                $curPost['posted'] = date('M d, Y H:i', $postedTs);
            }
        }

        $rawMessage = (string)($curPost['message'] ?? '');
        $normalizedMessage = str_replace(
            array('<br />', '<br/>', '<br>'),
            "\n",
            html_entity_decode($rawMessage, ENT_QUOTES, 'UTF-8')
        );
        $normalizedMessage = spp_forum_normalize_legacy_markup($normalizedMessage);
        $curPost['rendered_message'] = bbcode($normalizedMessage, true, true, true, false);

        if ($includeRichMeta) {
            $rawSignature = (string)($curPost['signature'] ?? '');
            if (!empty($rawSignature)) {
                $normalizedSignature = str_replace(
                    array('<br />', '<br/>', '<br>'),
                    "\n",
                    html_entity_decode($rawSignature, ENT_QUOTES, 'UTF-8')
                );
                $normalizedSignature = spp_forum_normalize_legacy_markup($normalizedSignature);
                $curPost['rendered_signature'] = bbcode($normalizedSignature, true, true, true, false);
            } else {
                $curPost['rendered_signature'] = '';
            }
        }

        $posts[] = $curPost;
    }

    return $posts;
}

function spp_forum_build_index_items(PDO $realmPdo, array $user, bool $respectHiddenForumPreference = true, ?int $realmId = null): array
{
    global $yesterday_ts, $realmDbMap;

    if (($user['id'] ?? 0) > 0 && spp_forum_has_markread_table($realmPdo)) {
        $queryparts = "
            SELECT f_categories.*,f_forums.*,f_topics.topic_name,f_topics.last_poster,f_topics.last_post,f_markread.* FROM f_categories
            JOIN f_forums ON f_categories.cat_id=f_forums.cat_id
            LEFT JOIN f_topics ON f_forums.last_topic_id=f_topics.topic_id
            LEFT JOIN f_markread ON (f_markread.marker_forum_id=f_forums.forum_id AND f_markread.marker_member_id=?)
        ";
        $queryParams = [(int)$user['id']];
    } else {
        $queryparts = "
            SELECT f_categories.*,f_forums.*,f_topics.topic_name,f_topics.last_poster,f_topics.last_post FROM f_categories
            JOIN f_forums ON f_categories.cat_id=f_forums.cat_id
            LEFT JOIN f_topics ON f_forums.last_topic_id=f_topics.topic_id
        ";
        $queryParams = [];
    }

    $showHiddenForums = $respectHiddenForumPreference
        ? (
            function_exists('spp_forum_should_show_hidden_forums')
                ? spp_forum_should_show_hidden_forums($realmPdo, $user)
                : (
                    ((int)($user['g_forum_moderate'] ?? 0) === 1)
                    || ((int)($user['gmlevel'] ?? 0) >= 3)
                    || ((int)($user['g_is_admin'] ?? 0) === 1)
                    || ((int)($user['g_is_supadmin'] ?? 0) === 1)
                )
        )
        : spp_forum_can_manage_hidden_forums($user);

    if (!$showHiddenForums) {
        $queryparts .= " WHERE hidden!=1 ";
    }
    $queryparts .= " ORDER BY cat_disp_position,cat_name,disp_position,forum_name ";

    $stmt = $realmPdo->prepare($queryparts);
    $stmt->execute($queryParams);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = array();
    foreach ($result as $item) {
        if (function_exists('spp_forum_is_comments_context') && spp_forum_is_comments_context($item)) {
            continue;
        }

        if (($user['id'] ?? 0) > 0) {
            if (($item['last_post'] ?? 0) > ($item['marker_last_cleared'] ?? 0)) {
                $item['isnew'] = true;
            } else {
                $item['isnew'] = ((int)($item['marker_unread'] ?? 0) > 0);
            }
        } else {
            $item['isnew'] = true;
        }

        $lastPostTs = (int)($item['last_post'] ?? 0);
        if (date('d', $lastPostTs) == date('d') && $_SERVER['REQUEST_TIME'] - $lastPostTs < 86400) {
            $item['last_post'] = 'Today at ' . date('H:i', $lastPostTs);
        } elseif (date('d', $lastPostTs) == date('d', $yesterday_ts) && $_SERVER['REQUEST_TIME'] - $lastPostTs < 2 * 86400) {
            $item['last_post'] = 'Yesterday at ' . date('H:i', $lastPostTs);
        } else {
            $item['last_post'] = date('d-m-Y, H:i', $lastPostTs);
        }

        $linkParams = array("realm" => (int)$realmId);
        $item['linktothis'] = spp_forum_url("viewforum", array_merge($linkParams, array("fid" => $item['forum_id'])));
        $item['linktolastpost'] = spp_forum_url("viewtopic", array_merge($linkParams, array("tid" => $item['last_topic_id'], "to" => "lastpost")));
        $item['linktoprofile'] = '';

        $items[$item['cat_id']][] = $item;
    }

    return $items;
}

function spp_forum_prepare_viewforum_marker(PDO $vfPdo, array $user, array $forum): array
{
    $topicsmark = array();
    $mark = array(
        'marker_topics_read' => serialize(array()),
        'marker_last_update' => 0,
        'marker_unread' => 0,
        'marker_last_cleared' => 0,
    );

    if (($user['id'] ?? 0) <= 0) {
        return array($topicsmark, $mark);
    }

    if (!spp_forum_has_markread_table($vfPdo)) {
        return array($topicsmark, $mark);
    }

    if (($_GETVARS['markread'] ?? null) == 1) {
        $stmtMr = $vfPdo->prepare("UPDATE f_markread SET marker_topics_read=?,marker_last_update=?,marker_unread=0,marker_last_cleared=? WHERE marker_member_id=? AND marker_forum_id=?");
        $stmtMr->execute([serialize($topicsmark), (int)$_SERVER['REQUEST_TIME'], (int)$_SERVER['REQUEST_TIME'], (int)$user['id'], (int)$forum['forum_id']]);
        redirect(spp_forum_url_with_site_href('viewforum', array('realm' => (int)($_GET['realm'] ?? 0), 'fid' => (int)$forum['forum_id'])), 1);
        return array($topicsmark, $mark);
    }

    $stmtGetMr = $vfPdo->prepare("SELECT * FROM f_markread WHERE marker_member_id=? AND marker_forum_id=?");
    $stmtGetMr->execute([(int)$user['id'], (int)$forum['forum_id']]);
    $mark = $stmtGetMr->fetch(PDO::FETCH_ASSOC);
    if (!$mark) {
        $stmtInsMr = $vfPdo->prepare("INSERT INTO f_markread SET marker_member_id=?,marker_forum_id=?,marker_topics_read=?");
        $stmtInsMr->execute([(int)$user['id'], (int)$forum['forum_id'], serialize(array())]);
    }
    if (!empty($mark['marker_topics_read'])) {
        $topicsmark = unserialize($mark['marker_topics_read']);
    }

    return array($topicsmark, $mark ?: array(
        'marker_topics_read' => serialize(array()),
        'marker_last_update' => 0,
        'marker_unread' => 0,
        'marker_last_cleared' => 0,
    ));
}

function spp_forum_build_viewforum_topics(
    PDO $vfPdo,
    array $forum,
    array $user,
    array $topicsmark,
    array $mark,
    int $itemsPerPage,
    int $limitStart,
    string $sortField,
    string $sortDir,
    ?int $realmId = null
): array {
    global $yesterday_ts;

    $allowedSortFields = array(
        'subject' => 'f_topics.topic_name',
        'author' => 'topic_author_display',
        'posted' => 'f_topics.topic_posted',
        'replies' => 'f_topics.num_replies',
        'views' => 'f_topics.num_views',
        'last_reply' => 'f_topics.last_post',
    );

    $stmtAt = $vfPdo->prepare("
        SELECT f_topics.*,account.username,
               COALESCE(NULLIF(f_topics.topic_poster, ''), account.username) AS topic_author_display
        FROM f_topics
        LEFT JOIN account ON f_topics.topic_poster_id=account.id
        WHERE forum_id=?
        ORDER BY sticky DESC, " . $allowedSortFields[$sortField] . " " . $sortDir . ", f_topics.last_post DESC, f_topics.topic_id DESC
        LIMIT " . (int)$limitStart . "," . (int)$itemsPerPage);
    $stmtAt->execute([(int)$forum['forum_id']]);
    $alltopics = $stmtAt->fetchAll(PDO::FETCH_ASSOC);

    $topics = array();
    foreach ($alltopics as $cur_topic) {
        $topicLastRead = isset($topicsmark[$cur_topic['topic_id']]) ? (int)$topicsmark[$cur_topic['topic_id']] : 0;
        if (($user['id'] ?? 0) > 0 && $cur_topic['last_post'] > (int)$mark['marker_last_cleared']) {
            $cur_topic['isnew'] = $cur_topic['last_post'] > $topicLastRead;
        } else {
            $cur_topic['isnew'] = true;
        }

        $pnum = max(1, (int)ceil(((int)$cur_topic['num_replies'] + 1) / (int)spp_config_generic('posts_per_page', 25)));
        if ($pnum > 1) {
            $cur_topic['pages_str'] = '&laquo; ';
            for ($pi = 1; $pi <= $pnum; $pi++) {
                $cur_topic['pages_str'] .= '<a href="' . htmlspecialchars(spp_forum_url('viewtopic', array('realm' => (int)$realmId, 'tid' => (int)$cur_topic['topic_id'], 'p' => $pi)), ENT_QUOTES, 'UTF-8') . '">' . $pi . '</a> ';
            }
            $cur_topic['pages_str'] .= ' &raquo;';
        }
        $cur_topic['pnum'] = $pnum;

        if (date('d', $cur_topic['topic_posted']) == date('d') && $_SERVER['REQUEST_TIME'] - $cur_topic['topic_posted'] < 86400) {
            $cur_topic['topic_posted'] = 'Today at ' . date('H:i', $cur_topic['topic_posted']);
        } elseif (date('d', $cur_topic['topic_posted']) == date('d', $yesterday_ts) && $_SERVER['REQUEST_TIME'] - $cur_topic['topic_posted'] < 2 * 86400) {
            $cur_topic['topic_posted'] = 'Yesterday at ' . date('H:i', $cur_topic['topic_posted']);
        } else {
            $cur_topic['topic_posted'] = date('M d, Y H:i', $cur_topic['topic_posted']);
        }

        if (date('d', $cur_topic['last_post']) == date('d') && $_SERVER['REQUEST_TIME'] - $cur_topic['last_post'] < 86400) {
            $cur_topic['last_post'] = 'Today at ' . date('H:i', $cur_topic['last_post']);
        } elseif (date('d', $cur_topic['last_post']) == date('d', $yesterday_ts) && $_SERVER['REQUEST_TIME'] - $cur_topic['last_post'] < 2 * 86400) {
            $cur_topic['last_post'] = 'Yesterday at ' . date('H:i', $cur_topic['last_post']);
        } else {
            $cur_topic['last_post'] = date('M d, Y H:i', $cur_topic['last_post']);
        }

        $cur_topic['linktothis'] = spp_forum_url_with_site_href('viewtopic', array('realm' => (int)$realmId, 'tid' => (int)$cur_topic['topic_id']));
        $cur_topic['linktolastpost'] = spp_forum_url_with_site_href('viewtopic', array('realm' => (int)$realmId, 'tid' => (int)$cur_topic['topic_id'], 'to' => 'lastpost'));
        $cur_topic['linktoprofile1'] = spp_forum_profile_url($cur_topic['username'] ?? '');
        $cur_topic['linktoprofile2'] = '';
        $topics[] = $cur_topic;
    }

    return $topics;
}

function spp_forum_prepare_viewtopic_links(array $forum, array $topic, string $siteHref, bool $canPost, ?int $realmId = null): array
{
    $forum['linktothis'] = spp_forum_url_with_site_href('viewforum', array('realm' => (int)$realmId, 'fid' => (int)$forum['forum_id']));
    $forum['linktonewtopic'] = spp_forum_url_with_site_href('post', array('realm' => (int)$realmId, 'action' => 'newtopic', 'f' => (int)$forum['forum_id']));

    $topic['linktothis'] = spp_forum_url_with_site_href('viewtopic', array('realm' => (int)$realmId, 'tid' => (int)$topic['topic_id']));
    $topic['linktoreply'] = $canPost
        ? spp_forum_url_with_site_href('post', array('realm' => (int)$realmId, 'action' => 'newpost', 't' => (int)$topic['topic_id'], 'fid' => (int)$forum['forum_id']))
        : '';
    $topic['linktopostreply'] = spp_forum_url_with_site_href('post', array('realm' => (int)$realmId, 'action' => 'newpost', 't' => (int)$topic['topic_id'], 'fid' => (int)$forum['forum_id']));
    $topic['linktodelete'] = spp_forum_action_url(spp_forum_url_with_site_href('post', array('realm' => (int)$realmId, 'action' => 'dodeletetopic', 't' => (int)$topic['topic_id'])));
    $topic['linktoclose'] = spp_forum_action_url(spp_forum_url_with_site_href('post', array('realm' => (int)$realmId, 'action' => 'closetopic', 't' => (int)$topic['topic_id'])));
    $topic['linktoopen'] = spp_forum_action_url(spp_forum_url_with_site_href('post', array('realm' => (int)$realmId, 'action' => 'opentopic', 't' => (int)$topic['topic_id'])));
    $topic['linktostick'] = spp_forum_action_url(spp_forum_url_with_site_href('post', array('realm' => (int)$realmId, 'action' => 'sticktopic', 't' => (int)$topic['topic_id'])));
    $topic['linktounstick'] = spp_forum_action_url(spp_forum_url_with_site_href('post', array('realm' => (int)$realmId, 'action' => 'unsticktopic', 't' => (int)$topic['topic_id'])));

    return array($forum, $topic);
}

function spp_forum_prepare_viewtopic_pagination(array $topic, int $page, int $itemsPerPage, ?int $realmId = null): array
{
    $itemCount = (int)($topic['num_replies'] ?? 0) + 1;
    $pageCount = max(1, (int)ceil($itemCount / $itemsPerPage));
    $limitStart = ($page - 1) * $itemsPerPage;
    $pagesStr = default_paginate($pageCount, $page, spp_forum_url('viewtopic', array('realm' => (int)$realmId, 'tid' => (int)$topic['topic_id'])));

    $topic['page_count'] = $pageCount;
    $topic['linktolastpost'] = (string)$topic['linktothis'] . '&to=lastpost';

    return array($topic, $pageCount, $limitStart, $pagesStr);
}

function spp_forum_handle_viewtopic_jump(array $topic, int $itemsPerPage): void
{
    $target = $_GETVARS['to'] ?? null;
    if ($target === 'lastpost') {
        $lastPage = max(1, (int)ceil(((int)$topic['num_replies'] + 1) / $itemsPerPage));
        redirect((string)$topic['linktothis'] . '&p=' . $lastPage . '#post' . (int)$topic['last_post_id'], 1);
    }

    if (is_numeric($target)) {
        $postId = (int)$target;
        if ($postId > 0) {
            $postPos = get_post_pos((int)$topic['topic_id'], $postId);
            $postPage = floor($postPos / $itemsPerPage) + 1;
            redirect((string)$topic['linktothis'] . '&p=' . $postPage . '#post' . $postId, 1);
        }
    }
}

function spp_forum_mark_viewtopic_read(PDO $forumPdo, array $user, array $forum, array $topic): void
{
    if (($user['id'] ?? 0) <= 0) {
        return;
    }

    if (!spp_forum_has_markread_table($forumPdo)) {
        return;
    }

    $topicsmark = array();
    $stmtMr = $forumPdo->prepare("SELECT * FROM f_markread WHERE marker_member_id=? AND marker_forum_id=?");
    $stmtMr->execute([(int)$user['id'], (int)$forum['forum_id']]);
    $mark = $stmtMr->fetch(PDO::FETCH_ASSOC);
    if (!$mark) {
        $stmtIns = $forumPdo->prepare("INSERT INTO f_markread SET marker_member_id=?,marker_forum_id=?,marker_topics_read=?");
        $stmtIns->execute([(int)$user['id'], (int)$forum['forum_id'], serialize(array())]);
        $mark = array(
            'marker_topics_read' => serialize(array()),
            'marker_last_update' => 0,
            'marker_unread' => 0,
            'marker_last_cleared' => 0,
        );
    }

    if (!empty($mark['marker_topics_read'])) {
        $topicsmark = unserialize($mark['marker_topics_read']);
        if (!is_array($topicsmark)) {
            $topicsmark = array();
        }
    }

    $topicLastRead = isset($topicsmark[$topic['topic_id']])
        ? (int)$topicsmark[$topic['topic_id']]
        : 0;
    $timeCheck = max($topicLastRead, (int)($mark['marker_last_cleared'] ?? 0));
    if ((int)$topic['last_post'] < $timeCheck) {
        return;
    }

    $readTopicIds = array((int)$topic['topic_id']);
    foreach ($topicsmark as $topicId => $date) {
        if ((int)$date > (int)$mark['marker_last_cleared']) {
            $readTopicIds[] = (int)$topicId;
        }
    }

    $unread = (int)$mark['marker_unread'] - 1;
    $topicsmark[(int)$topic['topic_id']] = (int)$_SERVER['REQUEST_TIME'];

    if ($unread <= 0) {
        $inPlaceholders = implode(',', array_fill(0, count($readTopicIds), '?'));
        $stmtCnt = $forumPdo->prepare("SELECT count(*) as count, MIN(last_post) as min_last_post FROM f_topics WHERE last_post>? AND topic_id NOT IN ($inPlaceholders) AND forum_id=?");
        $stmtCnt->execute(array_merge(
            array((int)$mark['marker_last_cleared']),
            $readTopicIds,
            array((int)$forum['forum_id'])
        ));
        $count = $stmtCnt->fetch(PDO::FETCH_ASSOC);
        $unread = (int)($count['count'] ?? 0);

        if ($unread > 0 && !empty($topicsmark)) {
            $readCutoff = (int)($count['min_last_post'] ?? 0) - 1;
            $topicsmark = array_filter($topicsmark, function ($value) use ($readCutoff) {
                return (int)$value > $readCutoff;
            });
            $saveMarkers = serialize($topicsmark);
        } else {
            $saveMarkers = serialize(array());
            $mark['marker_last_cleared'] = (int)$_SERVER['REQUEST_TIME'];
            $unread = 0;
        }
    } else {
        $saveMarkers = serialize($topicsmark);
    }

    $stmtUpMr = $forumPdo->prepare("UPDATE f_markread SET marker_topics_read=?,marker_last_update=?,marker_unread=?,marker_last_cleared=? WHERE marker_member_id=? AND marker_forum_id=?");
    $stmtUpMr->execute([
        $saveMarkers,
        (int)$_SERVER['REQUEST_TIME'],
        (int)$unread,
        (int)$mark['marker_last_cleared'],
        (int)$user['id'],
        (int)$forum['forum_id'],
    ]);
}

function spp_forum_fetch_viewtopic_posts(PDO $forumPdo, PDO $charPdo, int $topicId, int $realmId, int $limitStart, int $itemsPerPage, string $topicLinkBase, int $pageOffset = 0): array
{
    $rows = spp_forum_prepare_post_rows($forumPdo, $topicId, $limitStart, $itemsPerPage);

    return spp_forum_hydrate_topic_posts(
        $forumPdo,
        $charPdo,
        $rows,
        $realmId,
        $topicLinkBase,
        $pageOffset,
        true
    );
}
