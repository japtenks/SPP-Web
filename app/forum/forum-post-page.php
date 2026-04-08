<?php

require_once __DIR__ . '/../../components/forum/forum.func.php';
require_once __DIR__ . '/../../components/forum/forum.read.php';
require_once __DIR__ . '/forum-post-actions.php';

if (!function_exists('spp_forum_load_post_page_state')) {
    function spp_forum_load_post_page_state(array $args = array()): array
    {
        $user = $args['user'] ?? ($GLOBALS['user'] ?? array());
        $realmMap = $args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? ($GLOBALS['realm_map'] ?? null));
        $get = $args['get'] ?? $_GET;
        $post = $args['post'] ?? $_POST;
        $cookie = $args['cookie'] ?? $_COOKIE;
        $requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $requestAction = $requestMethod === 'POST'
            ? (string)($post['action'] ?? ($get['action'] ?? ''))
            : (string)($get['action'] ?? '');

        $state = array(
            '__stop' => false,
            'post_time' => time(),
            'action' => $requestAction,
            'this_post' => array(),
            'this_topic' => array(),
            'this_forum' => array(),
            'posts' => array(),
            'canPost' => true,
            'posting_block_reason' => '',
            'forum_post_errors' => array(),
            'forum_post_form' => array(
                'subject' => trim((string)($post['subject'] ?? '')),
                'text' => trim((string)($post['text'] ?? '')),
                'posting_character_id' => (int)($post['posting_character_id'] ?? ($get['posting_character_id'] ?? 0)),
                'publisher_identity' => trim((string)($post['publisher_identity'] ?? ($get['publisher_identity'] ?? ''))),
            ),
            'forum_post_mode' => (($requestAction === 'newtopic' || $requestAction === 'donewtopic') ? 'newtopic' : 'reply'),
            'posting_context' => array(
                'forum_name' => '',
                'forum_scope_label' => '',
                'realm_id' => 0,
                'realm_name' => '',
                'character_name' => '',
                'character_level' => 0,
                'guild_name' => '',
                'publisher_label' => '',
            ),
            'posting_character_options' => array(),
            'news_publisher_options' => array(),
        );

        if (!is_array($realmMap) || empty($realmMap)) {
            die('Realm DB map not loaded');
        }

        $cookieRealmId = (int)($cookie['cur_selected_realmd'] ?? ($cookie['cur_selected_realm'] ?? 0));
        $realmId = ($cookieRealmId > 0 && isset($realmMap[$cookieRealmId]))
            ? $cookieRealmId
            : spp_resolve_realm_id($realmMap);

        if (!empty($get['fid']) && empty($get['f'])) {
            $get['f'] = $get['fid'];
        }

        if (!empty($get['post'])) {
            $state['this_post'] = get_post_byid($get['post']);
            if (!empty($state['this_post']['topic_id'])) {
                $get['t'] = $state['this_post']['topic_id'];
            }
        }
        if (!empty($get['t'])) {
            $state['this_topic'] = get_topic_byid($get['t']);
            if (!empty($state['this_topic']['forum_id'])) {
                $get['f'] = $state['this_topic']['forum_id'];
            }
        }
        if (!empty($get['f'])) {
            $state['this_forum'] = get_forum_byid($get['f']);
        }

        $realmId = spp_forum_target_realm_id($state['this_forum'], $realmMap, $realmId);
        $charDbName = $realmMap[$realmId]['chars'];
        $selectedPostingCharacterId = (int)($state['forum_post_form']['posting_character_id'] ?? 0);

        foreach (($GLOBALS['account_characters'] ?? array()) as $character) {
            if ((int)($character['realm_id'] ?? 0) !== $realmId) {
                continue;
            }

            $state['posting_character_options'][] = array(
                'guid' => (int)($character['guid'] ?? 0),
                'name' => (string)($character['name'] ?? ''),
                'level' => (int)($character['level'] ?? 0),
                'guild' => (string)($character['guild_name'] ?? ''),
                'class' => (int)($character['class'] ?? 0),
            );
        }

        $activeForumCharacter = null;
        if ($selectedPostingCharacterId > 0) {
            foreach ($state['posting_character_options'] as $postingCharacterOption) {
                if ((int)$postingCharacterOption['guid'] === $selectedPostingCharacterId) {
                    $activeForumCharacter = $postingCharacterOption;
                    break;
                }
            }
        }

        if ($activeForumCharacter === null) {
            $activeForumCharacter = resolve_forum_character_for_realm($user, $realmId);
        }

        if ($activeForumCharacter === null && !empty($state['posting_character_options'])) {
            $activeForumCharacter = $state['posting_character_options'][0];
        }

        if ($activeForumCharacter) {
            $user['character_id'] = (int)$activeForumCharacter['guid'];
            $user['character_name'] = $activeForumCharacter['name'];
            $user['character_realm_id'] = $realmId;
            $state['forum_post_form']['posting_character_id'] = (int)$activeForumCharacter['guid'];
        }

        $state['posting_context']['forum_name'] = (string)($state['this_forum']['forum_name'] ?? '');
        $state['posting_context']['realm_id'] = $realmId;
        $state['posting_context']['realm_name'] = (string)(spp_get_armory_realm_name($realmId) ?? ('Realm ' . $realmId));
        $state['posting_context']['character_name'] = (string)($user['character_name'] ?? '');
        $state['posting_context']['character_level'] = (int)($activeForumCharacter['level'] ?? 0);

        if (($state['this_forum']['scope_type'] ?? '') === 'guild_recruitment') {
            $state['posting_context']['forum_scope_label'] = 'Guild Recruitment';
        } elseif (($state['this_forum']['scope_type'] ?? '') === 'expansion') {
            $state['posting_context']['forum_scope_label'] = strtoupper((string)($state['this_forum']['scope_value'] ?? ''));
        } elseif (($state['this_forum']['scope_type'] ?? '') === 'realm') {
            $state['posting_context']['forum_scope_label'] = 'Realm ' . (string)($state['this_forum']['scope_value'] ?? $realmId);
        }

        if ($state['forum_post_mode'] === 'newtopic' && empty($state['this_forum']['forum_id'])) {
            $state['canPost'] = false;
            $state['posting_block_reason'] = 'The forum you are trying to post in was not found.';
            output_message('alert', $state['posting_block_reason']);
        } elseif ($state['forum_post_mode'] === 'reply' && empty($state['this_topic']['topic_id'])) {
            $state['canPost'] = false;
            $state['posting_block_reason'] = 'The topic you are trying to reply to was not found.';
            output_message('alert', $state['posting_block_reason']);
        } elseif (!empty($state['this_forum']) && !spp_forum_can_view_forum($state['this_forum'], $user)) {
            $state['canPost'] = false;
            $state['posting_block_reason'] = 'The forum you are trying to post in was not found.';
            output_message('alert', $state['posting_block_reason']);
        }

        $_newsFid = (int)spp_config_forum('news_forum_id', 0);
        $_isNewsForum = $_newsFid > 0 && (int)($state['this_forum']['forum_id'] ?? $get['f'] ?? 0) === $_newsFid;
        if ($_isNewsForum && spp_forum_can_publish_news($user)) {
            $state['news_publisher_options'] = spp_forum_news_publisher_options();
            if (!empty($state['news_publisher_options'])) {
                $selectedPublisherKey = (string)($state['forum_post_form']['publisher_identity'] ?? '');
                if ($selectedPublisherKey === '' || !isset($state['news_publisher_options'][$selectedPublisherKey])) {
                    $selectedPublisherKey = (string)spp_array_first_key($state['news_publisher_options']);
                }
                $state['forum_post_form']['publisher_identity'] = $selectedPublisherKey;
                $state['posting_context']['publisher_label'] = (string)($state['news_publisher_options'][$selectedPublisherKey] ?? '');
            }
        }

        if (($user['id'] ?? 0) <= 0) {
            $state['canPost'] = false;
            $state['posting_block_reason'] = 'You must be logged in to post.';
        } elseif ($_isNewsForum && !spp_forum_can_publish_news($user)) {
            $state['canPost'] = false;
            $state['posting_block_reason'] = 'Only GMs may post in the News forum.';
            output_message('alert', $state['posting_block_reason']);
        } elseif (!$_isNewsForum && empty($activeForumCharacter['guid'])) {
            $state['canPost'] = false;
            $state['posting_block_reason'] = 'You must select a valid character before posting. This account currently has no available character loaded for the selected realm.';
            output_message('alert', $state['posting_block_reason']);
        } elseif (!$_isNewsForum && !empty($state['this_forum']) && !check_forum_scope($state['this_forum'], $realmId)) {
            $state['canPost'] = false;
            $state['posting_block_reason'] = 'Your selected character cannot post in this forum. Please switch to the correct realm.';
            output_message('alert', $state['posting_block_reason']);
        }

        $isRecruitmentForum = !empty($state['this_forum']) && ($state['this_forum']['scope_type'] ?? '') === 'guild_recruitment';
        $recruitmentGuild = null;
        if ($isRecruitmentForum && !empty($user['character_id'])) {
            $recruitmentGuild = get_char_recruitment_guild($realmId, (int)$user['character_id'], (int)($user['id'] ?? 0));
        }
        if (!empty($recruitmentGuild['name'])) {
            $state['posting_context']['guild_name'] = (string)$recruitmentGuild['name'];
        }

        if (spp_forum_handle_topic_moderation($state['action'], $user, $realmId, $state['this_topic'], $state['this_forum'])) {
            $state['__stop'] = true;
            return $state;
        }

        $topicSubmitResult = spp_forum_handle_new_topic_submission(
            $state['action'],
            $state['canPost'],
            $user,
            $realmId,
            $state['post_time'],
            $state['this_forum'],
            $state['forum_post_form'],
            $state['forum_post_errors'],
            $isRecruitmentForum,
            $recruitmentGuild
        );
        if (!empty($topicSubmitResult['handled'])) {
            $state['action'] = $topicSubmitResult['action'];
            $state['forum_post_mode'] = $topicSubmitResult['forum_post_mode'];
            $state['forum_post_errors'] = $topicSubmitResult['forum_post_errors'];
            if (!empty($topicSubmitResult['stop'])) {
                $state['__stop'] = true;
                return $state;
            }
        }

        $replySubmitResult = spp_forum_handle_new_reply_submission(
            $state['action'],
            $state['canPost'],
            $user,
            $realmId,
            $state['post_time'],
            $state['this_forum'],
            $state['this_topic'],
            $state['forum_post_form'],
            $state['forum_post_errors'],
            $isRecruitmentForum
        );
        if (!empty($replySubmitResult['handled'])) {
            $state['action'] = $replySubmitResult['action'];
            $state['forum_post_mode'] = $replySubmitResult['forum_post_mode'];
            $state['forum_post_errors'] = $replySubmitResult['forum_post_errors'];
            if (!empty($replySubmitResult['stop'])) {
                $state['__stop'] = true;
                return $state;
            }
        }

        if (!empty($state['this_topic']['topic_id'])) {
            $forumReadPdo = spp_get_pdo('realmd', $realmId);
            $result = function_exists('spp_forum_prepare_post_rows')
                ? spp_forum_prepare_post_rows($forumReadPdo, (int)$state['this_topic']['topic_id'], 0, 1000000)
                : array();
            $charReadPdo = spp_get_pdo('chars', $realmId);
            $state['posts'] = spp_forum_hydrate_topic_posts(
                $forumReadPdo,
                $charReadPdo,
                $result,
                $realmId,
                spp_forum_url('viewtopic', array('tid' => (int)$state['this_topic']['topic_id'])),
                0,
                false
            );
        }

        $state['is_newtopic'] = ($state['action'] === 'newtopic');

        return $state;
    }
}
