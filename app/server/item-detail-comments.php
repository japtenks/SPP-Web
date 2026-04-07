<?php

if (!function_exists('spp_item_detail_enrich_comment_posts')) {
    function spp_item_detail_enrich_comment_posts(array $commentPosts, int $realmId, array $classNames): array
    {
        if (empty($commentPosts)) {
            return $commentPosts;
        }

        $characterIds = [];
        foreach ($commentPosts as $commentPost) {
            $characterId = (int)($commentPost['poster_character_id'] ?? 0);
            if ($characterId > 0) {
                $characterIds[$characterId] = $characterId;
            }
        }

        if (empty($characterIds)) {
            return $commentPosts;
        }

        try {
            $charsPdo = spp_get_pdo('chars', $realmId);
            $idList = array_values($characterIds);
            $placeholders = implode(',', array_fill(0, count($idList), '?'));
            $stmt = $charsPdo->prepare("SELECT `guid`, `level`, `class` FROM `characters` WHERE `guid` IN ({$placeholders})");
            $stmt->execute($idList);
            $characterMap = [];
            foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
                $guid = (int)($row['guid'] ?? 0);
                if ($guid <= 0) {
                    continue;
                }
                $classId = (int)($row['class'] ?? 0);
                $className = trim((string)($classNames[$classId] ?? ''));
                $characterMap[$guid] = [
                    'level' => (int)($row['level'] ?? 0),
                    'class_slug' => $className !== '' ? strtolower(str_replace(' ', '', $className)) : '',
                ];
            }

            foreach ($commentPosts as &$commentPost) {
                $characterId = (int)($commentPost['poster_character_id'] ?? 0);
                if ($characterId > 0 && isset($characterMap[$characterId])) {
                    $commentPost['poster_level'] = (int)$characterMap[$characterId]['level'];
                    $commentPost['poster_class_slug'] = (string)$characterMap[$characterId]['class_slug'];
                }
            }
            unset($commentPost);
        } catch (Throwable $e) {
            error_log('[item comments] Failed enriching poster metadata: ' . $e->getMessage());
        }

        return $commentPosts;
    }
}

if (!function_exists('spp_item_detail_sync_comments')) {
    function spp_item_detail_sync_comments(array $args): array
    {
        $realmId = (int)($args['realm_id'] ?? 1);
        $realmLabel = (string)($args['realm_label'] ?? '');
        $itemId = (int)($args['item_id'] ?? 0);
        $item = (array)($args['item'] ?? []);
        $user = (array)($args['user'] ?? []);
        $classNames = (array)($args['class_names'] ?? []);
        $commentPosterOptions = (array)($args['comment_poster_options'] ?? []);
        $commentPosterSelection = (string)($args['comment_poster_selection'] ?? '');
        $itemCommentForumContext = (array)($args['item_comment_forum_context'] ?? []);

        $commentTopic = null;
        $commentPosts = [];
        $commentError = '';

        $itemCommentForumId = (int)($itemCommentForumContext['forum_id'] ?? 0);
        $itemCommentForumRealmId = (int)($itemCommentForumContext['realm_id'] ?? 1);
        if ($itemCommentForumId <= 0) {
            return [
                'comment_topic' => $commentTopic,
                'comment_posts' => $commentPosts,
                'comment_error' => $commentError,
            ];
        }

        $forumPdo = spp_get_pdo('realmd', $itemCommentForumRealmId);
        $topicTitle = '[' . $realmLabel . '][Item #' . $itemId . '] ' . (string)($item['name'] ?? ('Item #' . $itemId));

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['item_comment_action'] ?? '') === 'submit_comment') {
            spp_require_csrf('item_comments');
            $commentBody = trim((string)($_POST['comment_body'] ?? ''));

            if ((int)($user['id'] ?? 0) <= 0) {
                $commentError = 'You must be logged in to post item comments.';
            } elseif ($commentBody === '') {
                $commentError = 'Comment text cannot be empty.';
            } elseif (mb_strlen($commentBody) < 3) {
                $commentError = 'Comment text must be at least 3 characters.';
            } elseif (!isset($commentPosterOptions[$commentPosterSelection])) {
                $commentError = 'Choose a character from this realm to post as.';
            } else {
                $posterOption = $commentPosterOptions[$commentPosterSelection];
                $posterName = (string)($posterOption['poster'] ?? ($user['username'] ?? $user['login'] ?? 'Adventurer'));
                $posterCharacterId = !empty($posterOption['character_id']) ? (int)$posterOption['character_id'] : 0;
                $posterIdentityId = !empty($posterOption['identity_id']) ? (int)$posterOption['identity_id'] : null;

                try {
                    $forumPdo->beginTransaction();

                    $topicStmt = $forumPdo->prepare('SELECT * FROM `f_topics` WHERE `forum_id` = ? AND `topic_name` = ? LIMIT 1');
                    $topicStmt->execute([$itemCommentForumId, $topicTitle]);
                    $commentTopic = $topicStmt->fetch(PDO::FETCH_ASSOC) ?: null;
                    $commentTopicHasPosts = false;

                    if ($commentTopic) {
                        $postCountStmt = $forumPdo->prepare('SELECT COUNT(*) FROM `f_posts` WHERE `topic_id` = ?');
                        $postCountStmt->execute([(int)$commentTopic['topic_id']]);
                        $commentTopicHasPosts = ((int)$postCountStmt->fetchColumn() > 0);
                    }

                    if (!$commentTopic) {
                        $topicPostTime = time();
                        $newTopicStmt = $forumPdo->prepare(
                            "INSERT INTO f_topics (topic_poster, topic_poster_id, topic_poster_identity_id, topic_name, topic_posted, forum_id, num_replies)
                             VALUES (?, ?, ?, ?, ?, ?, 0)"
                        );
                        $newTopicStmt->execute([
                            $posterName,
                            (int)$user['id'],
                            $posterIdentityId,
                            $topicTitle,
                            $topicPostTime,
                            $itemCommentForumId,
                        ]);
                        $newTopicId = (int)$forumPdo->lastInsertId();

                        $newPostStmt = $forumPdo->prepare(
                            "INSERT INTO f_posts (poster, poster_id, poster_character_id, poster_identity_id, poster_ip, message, posted, topic_id)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        $newPostStmt->execute([
                            $posterName,
                            (int)$user['id'],
                            $posterCharacterId,
                            $posterIdentityId,
                            (string)($user['ip'] ?? ''),
                            $commentBody,
                            $topicPostTime,
                            $newTopicId,
                        ]);
                        $newPostId = (int)$forumPdo->lastInsertId();

                        $topicUpdateStmt = $forumPdo->prepare("UPDATE f_topics SET last_post = ?, last_post_id = ?, last_poster = ? WHERE topic_id = ?");
                        $topicUpdateStmt->execute([$topicPostTime, $newPostId, $posterName, $newTopicId]);

                        $forumUpdateStmt = $forumPdo->prepare("UPDATE f_forums SET num_topics = num_topics + 1, num_posts = num_posts + 1, last_topic_id = ? WHERE forum_id = ?");
                        $forumUpdateStmt->execute([$newTopicId, $itemCommentForumId]);
                    } elseif (!$commentTopicHasPosts) {
                        $topicPostTime = time();
                        $firstPostStmt = $forumPdo->prepare(
                            "INSERT INTO f_posts (poster, poster_id, poster_character_id, poster_identity_id, poster_ip, message, posted, topic_id)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        $firstPostStmt->execute([
                            $posterName,
                            (int)$user['id'],
                            $posterCharacterId,
                            $posterIdentityId,
                            (string)($user['ip'] ?? ''),
                            $commentBody,
                            $topicPostTime,
                            (int)$commentTopic['topic_id'],
                        ]);
                        $newPostId = (int)$forumPdo->lastInsertId();

                        $topicUpdateStmt = $forumPdo->prepare("UPDATE f_topics SET topic_poster = ?, topic_poster_id = ?, topic_poster_identity_id = ?, topic_posted = ?, last_post = ?, last_post_id = ?, last_poster = ?, num_replies = 0 WHERE topic_id = ?");
                        $topicUpdateStmt->execute([
                            $posterName,
                            (int)$user['id'],
                            $posterIdentityId,
                            $topicPostTime,
                            $topicPostTime,
                            $newPostId,
                            $posterName,
                            (int)$commentTopic['topic_id'],
                        ]);

                        $forumUpdateStmt = $forumPdo->prepare("UPDATE f_forums SET num_posts = num_posts + 1, last_topic_id = ? WHERE forum_id = ?");
                        $forumUpdateStmt->execute([(int)$commentTopic['topic_id'], $itemCommentForumId]);
                    } else {
                        $topicPostTime = time();
                        $replyStmt = $forumPdo->prepare(
                            "INSERT INTO f_posts (poster, poster_id, poster_character_id, poster_identity_id, poster_ip, message, posted, topic_id)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        $replyStmt->execute([
                            $posterName,
                            (int)$user['id'],
                            $posterCharacterId,
                            $posterIdentityId,
                            (string)($user['ip'] ?? ''),
                            $commentBody,
                            $topicPostTime,
                            (int)$commentTopic['topic_id'],
                        ]);
                        $newPostId = (int)$forumPdo->lastInsertId();

                        $topicUpdateStmt = $forumPdo->prepare("UPDATE f_topics SET last_post = ?, last_post_id = ?, last_poster = ?, num_replies = num_replies + 1 WHERE topic_id = ?");
                        $topicUpdateStmt->execute([$topicPostTime, $newPostId, $posterName, (int)$commentTopic['topic_id']]);

                        $forumUpdateStmt = $forumPdo->prepare("UPDATE f_forums SET num_posts = num_posts + 1, last_topic_id = ? WHERE forum_id = ?");
                        $forumUpdateStmt->execute([(int)$commentTopic['topic_id'], $itemCommentForumId]);
                    }

                    $forumPdo->commit();
                    spp_item_detail_post_redirect(spp_item_detail_comment_redirect_url(['comment_posted' => 1]));
                } catch (Throwable $e) {
                    if ($forumPdo->inTransaction()) {
                        $forumPdo->rollBack();
                    }
                    error_log('[item comments] Failed posting item comment: ' . $e->getMessage());
                    $commentError = 'Unable to post item comment right now.';
                }
            }
        }

        $topicStmt = $forumPdo->prepare('SELECT * FROM `f_topics` WHERE `forum_id` = ? AND `topic_name` = ? LIMIT 1');
        $topicStmt->execute([$itemCommentForumId, $topicTitle]);
        $commentTopic = $topicStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($commentTopic) {
            $postStmt = $forumPdo->prepare('SELECT * FROM `f_posts` WHERE `topic_id` = ? ORDER BY `posted` ASC LIMIT 25');
            $postStmt->execute([(int)$commentTopic['topic_id']]);
            $commentPosts = $postStmt->fetchAll(PDO::FETCH_ASSOC);
            $commentPosts = spp_item_detail_enrich_comment_posts($commentPosts, (int)$realmId, $classNames);
        }

        return [
            'comment_topic' => $commentTopic,
            'comment_posts' => $commentPosts,
            'comment_error' => $commentError,
        ];
    }
}
