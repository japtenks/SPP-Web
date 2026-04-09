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

if (!function_exists('spp_admin_forum_redirect_url_with_notice')) {
    function spp_admin_forum_redirect_url_with_notice(array $request, string $notice): string
    {
        $baseUrl = spp_admin_forum_redirect_url($request);
        $separator = strpos($baseUrl, '?') === false ? '?' : '&';
        return $baseUrl . $separator . http_build_query(array('forum_notice' => $notice));
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
        $allowed = array('cat_id', 'forum_name', 'forum_desc', 'disp_position', 'scope_type', 'scope_value');
        return spp_filter_allowed_fields($data, $allowed);
    }
}

if (!function_exists('spp_admin_forum_realm_options')) {
    function spp_admin_forum_realm_options(array $realmDbMap): array
    {
        $options = array();
        foreach ($realmDbMap as $realmId => $realmInfo) {
            $realmId = (int)$realmId;
            if ($realmId <= 0) {
                continue;
            }

            $options[] = array(
                'realm_id' => $realmId,
                'realm_name' => (string)(spp_get_armory_realm_name($realmId) ?? ('Realm #' . $realmId)),
                'expansion_key' => function_exists('spp_realm_to_expansion') ? (string)spp_realm_to_expansion($realmId) : '',
            );
        }

        usort($options, static function (array $left, array $right): int {
            return (int)$left['realm_id'] <=> (int)$right['realm_id'];
        });

        return $options;
    }
}

if (!function_exists('spp_admin_forum_standard_section_templates')) {
    function spp_admin_forum_standard_section_templates(): array
    {
        return array(
            'General' => array(
                'category_name' => 'General',
                'scope_type' => 'realm',
                'forum_desc_format' => 'General discussion and updates for the %s realm.',
            ),
            'Guild' => array(
                'category_name' => 'Guild',
                'scope_type' => 'guild_recruitment',
                'forum_desc_format' => 'Guild recruitment and guild-focused posts for the %s realm.',
            ),
        );
    }
}

if (!function_exists('spp_admin_forum_find_category_id_by_name')) {
    function spp_admin_forum_find_category_id_by_name(PDO $pdo, string $categoryName): int
    {
        $stmt = $pdo->prepare("SELECT `cat_id` FROM `f_categories` WHERE LOWER(`cat_name`) = LOWER(?) ORDER BY `cat_disp_position`, `cat_id` LIMIT 1");
        $stmt->execute(array(trim($categoryName)));
        return (int)$stmt->fetchColumn();
    }
}

if (!function_exists('spp_admin_forum_next_position_for_category')) {
    function spp_admin_forum_next_position_for_category(PDO $pdo, int $catId): int
    {
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(`disp_position`), 0) FROM `f_forums` WHERE `cat_id` = ?");
        $stmt->execute(array($catId));
        return max(1, (int)$stmt->fetchColumn() + 1);
    }
}

if (!function_exists('spp_admin_forum_insert_forum')) {
    function spp_admin_forum_insert_forum(PDO $pdo, array $data): void
    {
        $payload = spp_admin_forum_filter_forum_fields($data);
        if (empty($payload)) {
            return;
        }

        $setClause = implode(',', array_map(static function ($key) {
            return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $key) . '`=?';
        }, array_keys($payload)));
        $stmt = $pdo->prepare("INSERT INTO f_forums SET $setClause");
        $stmt->execute(array_values($payload));
    }
}

if (!function_exists('spp_admin_forum_realm_managed_forum_rows')) {
    function spp_admin_forum_realm_managed_forum_rows(PDO $pdo, array $realmDbMap, int $realmId): array
    {
        $realmName = (string)(spp_get_armory_realm_name($realmId) ?? ('Realm #' . $realmId));
        $expansionKey = function_exists('spp_realm_to_expansion') ? (string)spp_realm_to_expansion($realmId) : '';
        $categoryNames = array_keys(spp_admin_forum_standard_section_templates());

        $stmt = $pdo->query("
            SELECT f.*, c.`cat_name`
            FROM `f_forums` f
            INNER JOIN `f_categories` c ON c.`cat_id` = f.`cat_id`
            ORDER BY c.`cat_disp_position`, f.`disp_position`, f.`forum_id`
        ");
        $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: array()) : array();

        $managed = array();
        foreach ($rows as $row) {
            $categoryName = (string)($row['cat_name'] ?? '');
            if (!in_array($categoryName, $categoryNames, true)) {
                continue;
            }

            $scopeType = (string)($row['scope_type'] ?? 'all');
            $scopeValue = strtolower(trim((string)($row['scope_value'] ?? '')));
            $forumName = strtolower(trim((string)($row['forum_name'] ?? '')));
            $forumDesc = strtolower(trim((string)($row['forum_desc'] ?? '')));
            $realmNameNeedle = strtolower($realmName);
            $matchesRealm = false;

            if ($scopeType === 'realm' && (int)$scopeValue === $realmId) {
                $matchesRealm = true;
            } elseif ($scopeType === 'expansion' && $expansionKey !== '' && $scopeValue === strtolower($expansionKey)) {
                $matchesRealm = true;
            } elseif ($scopeType === 'guild_recruitment' && ($scopeValue === (string)$realmId || ($expansionKey !== '' && $scopeValue === strtolower($expansionKey)))) {
                $matchesRealm = true;
            } elseif ($realmNameNeedle !== '' && (strpos($forumName, $realmNameNeedle) !== false || strpos($forumDesc, $realmNameNeedle) !== false)) {
                $matchesRealm = true;
            } elseif (function_exists('spp_detect_forum_realm_hint') && spp_detect_forum_realm_hint($row, $realmDbMap, 0) === $realmId) {
                $matchesRealm = true;
            }

            if ($matchesRealm) {
                $managed[] = $row;
            }
        }

        return $managed;
    }
}

if (!function_exists('spp_admin_forum_create_realm_forum_set')) {
    function spp_admin_forum_create_realm_forum_set(PDO $pdo, array $realmDbMap, int $realmId): array
    {
        $realmName = (string)(spp_get_armory_realm_name($realmId) ?? ('Realm #' . $realmId));
        $created = 0;
        $skipped = 0;
        $missingCategories = array();

        foreach (spp_admin_forum_standard_section_templates() as $template) {
            $categoryName = (string)$template['category_name'];
            $catId = spp_admin_forum_find_category_id_by_name($pdo, $categoryName);
            if ($catId <= 0) {
                $missingCategories[] = $categoryName;
                continue;
            }

            $managedRows = spp_admin_forum_realm_managed_forum_rows($pdo, $realmDbMap, $realmId);
            $alreadyExists = false;
            foreach ($managedRows as $row) {
                if ((int)($row['cat_id'] ?? 0) === $catId) {
                    $alreadyExists = true;
                    break;
                }
            }
            if ($alreadyExists) {
                $skipped++;
                continue;
            }

            spp_admin_forum_insert_forum($pdo, array(
                'cat_id' => $catId,
                'forum_name' => $realmName,
                'forum_desc' => sprintf((string)$template['forum_desc_format'], $realmName),
                'disp_position' => spp_admin_forum_next_position_for_category($pdo, $catId),
                'scope_type' => (string)$template['scope_type'],
                'scope_value' => (string)$realmId,
            ));
            $created++;
        }

        return array(
            'created' => $created,
            'skipped' => $skipped,
            'missing_categories' => $missingCategories,
            'realm_name' => $realmName,
        );
    }
}

if (!function_exists('spp_admin_forum_remove_realm_forum_set')) {
    function spp_admin_forum_remove_realm_forum_set(PDO $pdo, array $realmDbMap, int $realmId): array
    {
        $rows = spp_admin_forum_realm_managed_forum_rows($pdo, $realmDbMap, $realmId);
        $removed = 0;
        foreach ($rows as $row) {
            $forumId = (int)($row['forum_id'] ?? 0);
            if ($forumId <= 0) {
                continue;
            }
            spp_admin_forum_delete_forum($pdo, $forumId);
            $removed++;
        }

        return array(
            'removed' => $removed,
            'realm_name' => (string)(spp_get_armory_realm_name($realmId) ?? ('Realm #' . $realmId)),
        );
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
