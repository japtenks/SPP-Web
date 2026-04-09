<?php

require_once __DIR__ . '/../../components/forum/forum.func.php';
require_once __DIR__ . '/../../components/forum/forum.read.php';

if (!function_exists('spp_forum_load_topic_page_state')) {
    function spp_forum_load_topic_page_state(array $args = array()): array
    {
        $user = $args['user'] ?? ($GLOBALS['user'] ?? array());
        $realmMap = $args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? ($GLOBALS['realm_map'] ?? null));
        $p = (int)($args['page'] ?? ($_GET['p'] ?? 1));
        $siteHref = (string)($args['site_href'] ?? spp_config_temp_string('site_href', ''));
        global $pathway_info, $_GETVARS;
        $getVars = $args['get_vars'] ?? ($_GETVARS ?? array());

        if (!is_array($realmMap) || empty($realmMap)) {
            die('Realm DB map not loaded');
        }

        $state = array(
            '__stop' => false,
            'this_topic' => array(),
            'this_forum' => array(),
            'posts' => array(),
            'pnum' => 1,
            'limit_start' => 0,
            'pages_str' => '',
            'realm_id' => 0,
        );

        $requestRealmId = (int)($getVars['realm'] ?? ($_GET['realm'] ?? 0));
        $state['this_topic'] = get_topic_byid((int)($getVars['tid'] ?? ($_GET['tid'] ?? 0)), $requestRealmId > 0 ? $requestRealmId : null);
        $state['this_forum'] = !empty($state['this_topic']['forum_id'])
            ? get_forum_byid((int)$state['this_topic']['forum_id'], $requestRealmId > 0 ? $requestRealmId : null)
            : array();

        $realmId = spp_forum_target_realm_id($state['this_forum'], $realmMap, spp_resolve_realm_id($realmMap));
        $state['realm_id'] = $realmId;

        $_vtNewsFid = (int)spp_config_forum('news_forum_id', 0);
        $_vtIsNewsForum = $_vtNewsFid > 0 && (int)($state['this_forum']['forum_id'] ?? 0) === $_vtNewsFid;
        $_vtCanPost = !$_vtIsNewsForum || spp_forum_can_publish_news($user);

        $state['this_topic']['show_qr'] = ((int)($state['this_forum']['quick_reply'] ?? 0) === 1 && $_vtCanPost) ? true : false;

        if ((int)($state['this_forum']['forum_id'] ?? 0) <= 0 || (int)($state['this_topic']['topic_id'] ?? 0) <= 0) {
            output_message('alert', 'This forum or topic does not exist.');
            $state['__stop'] = true;
            return $state;
        }
        if (!spp_forum_can_view_forum($state['this_forum'], $user)) {
            output_message('alert', 'This forum or topic does not exist.');
            $state['__stop'] = true;
            return $state;
        }

        list($state['this_forum'], $state['this_topic']) = spp_forum_prepare_viewtopic_links(
            $state['this_forum'],
            $state['this_topic'],
            $siteHref,
            $_vtCanPost,
            $realmId
        );

        $pathway_info[] = array('title' => $state['this_forum']['forum_name'], 'link' => $state['this_forum']['linktothis']);
        $pathway_info[] = array('title' => $state['this_topic']['topic_name'], 'link' => '');

        $itemsPerPage = (int)spp_config_generic('posts_per_page', 25);
        list($state['this_topic'], $state['pnum'], $state['limit_start'], $state['pages_str']) = spp_forum_prepare_viewtopic_pagination(
            $state['this_topic'],
            $p,
            $itemsPerPage,
            $realmId
        );

        $forumPdo = spp_get_pdo('realmd', $realmId);
        $charPdoVt = spp_get_pdo('chars', $realmId);

        if (!empty($getVars['to'])) {
            spp_forum_handle_viewtopic_jump($state['this_topic'], $itemsPerPage);
        }

        spp_forum_mark_viewtopic_read($forumPdo, $user, $state['this_forum'], $state['this_topic']);
        $stmtView = $forumPdo->prepare("UPDATE f_topics SET num_views=num_views+1 WHERE topic_id=? LIMIT 1");
        $stmtView->execute([(int)$state['this_topic']['topic_id']]);

        $state['posts'] = spp_forum_fetch_viewtopic_posts(
            $forumPdo,
            $charPdoVt,
            (int)$state['this_topic']['topic_id'],
            $realmId,
            $state['limit_start'],
            $itemsPerPage,
            spp_forum_url_with_site_href('viewtopic', array('realm' => $realmId, 'tid' => (int)$state['this_topic']['topic_id'])),
            (($p - 1) * $itemsPerPage)
        );

        return $state;
    }
}
