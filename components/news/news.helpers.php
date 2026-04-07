<?php
if (INCLUDED !== true) {
    exit;
}

require_once __DIR__ . '/../forum/forum.guard.php';

if (!function_exists('spp_news_table_name')) {
    function spp_news_table_name() {
        return 'website_news';
    }
}

if (!function_exists('spp_news_pdo')) {
    function spp_news_pdo() {
        return spp_get_pdo('realmd', 1);
    }
}

if (!function_exists('spp_news_ensure_table')) {
    function spp_news_ensure_table() {
        static $ensured = false;
        if ($ensured) {
            return true;
        }

        try {
            $pdo = spp_news_pdo();
            $table = spp_news_table_name();
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `{$table}` (
                  `news_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `source_forum_topic_id` INT UNSIGNED NULL DEFAULT NULL,
                  `slug` VARCHAR(120) NOT NULL DEFAULT '',
                  `title` VARCHAR(160) NOT NULL DEFAULT '',
                  `excerpt` TEXT DEFAULT NULL,
                  `body` MEDIUMTEXT DEFAULT NULL,
                  `publisher_label` VARCHAR(64) NOT NULL DEFAULT '',
                  `publisher_identity_id` INT UNSIGNED NULL DEFAULT NULL,
                  `created_by_account_id` INT UNSIGNED NULL DEFAULT NULL,
                  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
                  `published_at` INT UNSIGNED NOT NULL DEFAULT 0,
                  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`news_id`),
                  UNIQUE KEY `uq_slug` (`slug`),
                  UNIQUE KEY `uq_source_forum_topic` (`source_forum_topic_id`),
                  KEY `idx_published` (`is_published`, `published_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            try {
                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `source_forum_topic_id` INT UNSIGNED NULL DEFAULT NULL AFTER `news_id`");
            } catch (Throwable $e) {}
            try {
                $pdo->exec("ALTER TABLE `{$table}` ADD UNIQUE KEY `uq_source_forum_topic` (`source_forum_topic_id`)");
            } catch (Throwable $e) {}
            $ensured = true;
            return true;
        } catch (Throwable $e) {
            error_log('[news] Failed ensuring news table: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('spp_news_slugify')) {
    function spp_news_slugify($value) {
        $value = strtolower(trim((string)$value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
        $value = trim((string)$value, '-');
        return $value !== '' ? $value : 'news';
    }
}

if (!function_exists('spp_news_unique_slug')) {
    function spp_news_unique_slug(PDO $pdo, $title, $excludeId = 0) {
        $baseSlug = spp_news_slugify($title);
        $slug = $baseSlug;
        $suffix = 2;
        $table = spp_news_table_name();
        do {
            $sql = "SELECT news_id FROM `{$table}` WHERE slug = ?";
            $params = [$slug];
            if ((int)$excludeId > 0) {
                $sql .= " AND news_id <> ?";
                $params[] = (int)$excludeId;
            }
            $sql .= " LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $exists = (int)$stmt->fetchColumn();
            if ($exists <= 0) {
                return $slug;
            }
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        } while (true);
    }
}

if (!function_exists('spp_news_render_body')) {
    function spp_news_render_body($rawBody) {
        $normalized = str_replace(
            array('<br />', '<br/>', '<br>'),
            "\n",
            html_entity_decode((string)$rawBody, ENT_QUOTES, 'UTF-8')
        );
        if (function_exists('bbcode')) {
            return bbcode($normalized, true, true, true, false);
        }
        return nl2br(htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8'));
    }
}

if (!function_exists('spp_news_build_summary')) {
    function spp_news_build_summary($body) {
        $text = trim(strip_tags((string)$body));
        if ($text === '') {
            return '';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, 180, 'UTF-8') . (mb_strlen($text, 'UTF-8') > 180 ? '...' : '');
        }
        return substr($text, 0, 180) . (strlen($text) > 180 ? '...' : '');
    }
}

if (!function_exists('spp_news_map_row')) {
    function spp_news_map_row(array $row) {
        $newsId = (int)($row['news_id'] ?? 0);
        $slug = (string)($row['slug'] ?? '');
        $body = (string)($row['body'] ?? '');
        return array(
            'news_id' => $newsId,
            'topic_id' => $newsId,
            'slug' => $slug,
            'topic_name' => (string)($row['title'] ?? ''),
            'topic_poster' => (string)($row['publisher_label'] ?? ''),
            'last_poster' => (string)($row['publisher_label'] ?? ''),
            'topic_posted' => (int)($row['published_at'] ?? 0),
            'rendered_message' => spp_news_render_body($body),
            'summary' => (string)($row['excerpt'] ?? spp_news_build_summary($body)),
            'source_type' => 'news',
            'permalink' => spp_route_url('news', 'view', array('id' => $newsId, 'slug' => $slug), false),
        );
    }
}

if (!function_exists('spp_news_has_published_rows')) {
    function spp_news_has_published_rows() {
        if (!spp_news_ensure_table()) {
            return false;
        }

        $pdo = spp_news_pdo();
        $table = spp_news_table_name();
        $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}` WHERE `is_published` = 1");
        return ((int)$stmt->fetchColumn() > 0);
    }
}

if (!function_exists('spp_news_forum_id')) {
    function spp_news_forum_id(): int
    {
        return (int)spp_config_forum('news_forum_id', 1);
    }
}

if (!function_exists('spp_news_map_forum_row')) {
    function spp_news_map_forum_row(array $row): array
    {
        $topicId = (int)($row['topic_id'] ?? 0);
        $title = (string)($row['topic_name'] ?? '');
        $body = (string)($row['message'] ?? '');
        $slug = spp_news_slugify($title !== '' ? $title : ('news-' . $topicId));

        return array(
            'news_id' => $topicId,
            'topic_id' => $topicId,
            'slug' => $slug,
            'topic_name' => $title,
            'topic_poster' => (string)($row['topic_poster'] ?? ''),
            'last_poster' => (string)($row['last_poster'] ?? ($row['topic_poster'] ?? '')),
            'topic_posted' => (int)($row['topic_posted'] ?? 0),
            'rendered_message' => spp_news_render_body($body),
            'summary' => spp_news_build_summary($body),
            'source_type' => 'forum',
            'permalink' => spp_route_url('forum', 'viewtopic', array('tid' => $topicId), false),
        );
    }
}

if (!function_exists('spp_news_fetch_recent_from_forum')) {
    function spp_news_fetch_recent_from_forum($limit = 8): array
    {
        $limit = max(1, (int)$limit);
        $newsForumId = spp_news_forum_id();
        if ($newsForumId <= 0) {
            return array();
        }

        try {
            $pdo = spp_get_pdo('realmd', 1);
            $stmt = $pdo->prepare("
                SELECT
                  t.`topic_id`,
                  t.`topic_name`,
                  t.`topic_poster`,
                  t.`topic_posted`,
                  t.`last_poster`,
                  (
                    SELECT p.`message`
                    FROM `f_posts` p
                    WHERE p.`topic_id` = t.`topic_id`
                    ORDER BY p.`posted` ASC, p.`post_id` ASC
                    LIMIT 1
                  ) AS `message`
                FROM `f_topics` t
                WHERE t.`forum_id` = ?
                ORDER BY t.`topic_posted` DESC, t.`topic_id` DESC
                LIMIT {$limit}
            ");
            $stmt->execute([$newsForumId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
            return array_map('spp_news_map_forum_row', $rows);
        } catch (Throwable $e) {
            error_log('[news] Failed fetching recent forum news: ' . $e->getMessage());
            return array();
        }
    }
}

if (!function_exists('spp_news_fetch_forum_article_by_id_or_slug')) {
    function spp_news_fetch_forum_article_by_id_or_slug($id = 0, $slug = '')
    {
        $newsForumId = spp_news_forum_id();
        if ($newsForumId <= 0) {
            return null;
        }

        try {
            $pdo = spp_get_pdo('realmd', 1);
            if ((int)$id > 0) {
                $stmt = $pdo->prepare("
                    SELECT
                      t.`topic_id`,
                      t.`topic_name`,
                      t.`topic_poster`,
                      t.`topic_posted`,
                      t.`last_poster`,
                      (
                        SELECT p.`message`
                        FROM `f_posts` p
                        WHERE p.`topic_id` = t.`topic_id`
                        ORDER BY p.`posted` ASC, p.`post_id` ASC
                        LIMIT 1
                      ) AS `message`
                    FROM `f_topics` t
                    WHERE t.`forum_id` = ? AND t.`topic_id` = ?
                    LIMIT 1
                ");
                $stmt->execute([$newsForumId, (int)$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                return $row ? spp_news_map_forum_row($row) : null;
            }

            $stmt = $pdo->prepare("
                SELECT
                  t.`topic_id`,
                  t.`topic_name`,
                  t.`topic_poster`,
                  t.`topic_posted`,
                  t.`last_poster`,
                  (
                    SELECT p.`message`
                    FROM `f_posts` p
                    WHERE p.`topic_id` = t.`topic_id`
                    ORDER BY p.`posted` ASC, p.`post_id` ASC
                    LIMIT 1
                  ) AS `message`
                FROM `f_topics` t
                WHERE t.`forum_id` = ?
                ORDER BY t.`topic_posted` DESC, t.`topic_id` DESC
            ");
            $stmt->execute([$newsForumId]);
            foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: array()) as $row) {
                $mapped = spp_news_map_forum_row($row);
                if ((string)($mapped['slug'] ?? '') === (string)$slug) {
                    return $mapped;
                }
            }
        } catch (Throwable $e) {
            error_log('[news] Failed fetching forum news article: ' . $e->getMessage());
        }

        return null;
    }
}

if (!function_exists('spp_news_fetch_recent')) {
    function spp_news_fetch_recent($limit = 8) {
        if (!spp_news_ensure_table()) {
            return spp_news_fetch_recent_from_forum($limit);
        }
        if (!spp_news_has_published_rows()) {
            return spp_news_fetch_recent_from_forum($limit);
        }
        $limit = max(1, (int)$limit);
        $pdo = spp_news_pdo();
        $table = spp_news_table_name();
        $stmt = $pdo->prepare("
            SELECT *
            FROM `{$table}`
            WHERE is_published = 1
            ORDER BY published_at DESC, news_id DESC
            LIMIT {$limit}
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map('spp_news_map_row', $rows);
    }
}

if (!function_exists('spp_news_fetch_by_id_or_slug')) {
    function spp_news_fetch_by_id_or_slug($id = 0, $slug = '') {
        if (!spp_news_ensure_table()) {
            return spp_news_fetch_forum_article_by_id_or_slug($id, $slug);
        }
        if (!spp_news_has_published_rows()) {
            return spp_news_fetch_forum_article_by_id_or_slug($id, $slug);
        }
        $pdo = spp_news_pdo();
        $table = spp_news_table_name();
        if ((int)$id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE news_id = ? LIMIT 1");
            $stmt->execute([(int)$id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE slug = ? LIMIT 1");
            $stmt->execute([(string)$slug]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? spp_news_map_row($row) : null;
    }
}

if (!function_exists('spp_news_fetch_row_by_id')) {
    function spp_news_fetch_row_by_id($id = 0) {
        if (!spp_news_ensure_table() || (int)$id <= 0) {
            return null;
        }
        $pdo = spp_news_pdo();
        $table = spp_news_table_name();
        $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE news_id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('spp_news_fetch_admin_rows')) {
    function spp_news_fetch_admin_rows($limit = 25) {
        if (!spp_news_ensure_table()) {
            return array();
        }
        $limit = max(1, (int)$limit);
        $pdo = spp_news_pdo();
        $table = spp_news_table_name();
        $stmt = $pdo->prepare("
            SELECT *
            FROM `{$table}`
            ORDER BY published_at DESC, news_id DESC
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('spp_news_create')) {
    function spp_news_create(array $payload, array $user) {
        if (!spp_news_ensure_table()) {
            throw new RuntimeException('News storage is unavailable.');
        }
        $pdo = spp_news_pdo();
        $title = trim((string)($payload['title'] ?? ''));
        $body = trim((string)($payload['body'] ?? ''));
        $excerpt = trim((string)($payload['excerpt'] ?? ''));
        $publisherKey = trim((string)($payload['publisher_identity'] ?? ''));
        if ($title === '' || $body === '') {
            throw new RuntimeException('Title and body are required.');
        }

        $publisher = spp_forum_resolve_news_publisher($user, 1, $publisherKey);
        if (empty($publisher['label'])) {
            throw new RuntimeException('A publisher identity is required.');
        }

        $slug = spp_news_unique_slug($pdo, $title);
        $publishedAt = time();
        $table = spp_news_table_name();
        $stmt = $pdo->prepare("
            INSERT INTO `{$table}`
              (`source_forum_topic_id`, `slug`, `title`, `excerpt`, `body`, `publisher_label`, `publisher_identity_id`, `created_by_account_id`, `is_published`, `published_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([
            !empty($payload['source_forum_topic_id']) ? (int)$payload['source_forum_topic_id'] : null,
            $slug,
            $title,
            $excerpt,
            $body,
            (string)$publisher['label'],
            !empty($publisher['identity_id']) ? (int)$publisher['identity_id'] : null,
            (int)($user['id'] ?? 0),
            $publishedAt,
        ]);

        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('spp_news_update')) {
    function spp_news_update($newsId, array $payload, array $user) {
        $newsId = (int)$newsId;
        if ($newsId <= 0 || !spp_news_ensure_table()) {
            throw new RuntimeException('News post not found.');
        }

        $existing = spp_news_fetch_row_by_id($newsId);
        if (empty($existing)) {
            throw new RuntimeException('News post not found.');
        }

        $pdo = spp_news_pdo();
        $title = trim((string)($payload['title'] ?? ''));
        $body = trim((string)($payload['body'] ?? ''));
        $excerpt = trim((string)($payload['excerpt'] ?? ''));
        $publisherKey = trim((string)($payload['publisher_identity'] ?? ''));
        if ($title === '' || $body === '') {
            throw new RuntimeException('Title and body are required.');
        }

        $publisher = spp_forum_resolve_news_publisher($user, 1, $publisherKey);
        if (empty($publisher['label'])) {
            throw new RuntimeException('A publisher identity is required.');
        }

        $slug = spp_news_unique_slug($pdo, $title, $newsId);
        $table = spp_news_table_name();
        $stmt = $pdo->prepare("
            UPDATE `{$table}`
            SET `slug` = ?,
                `title` = ?,
                `excerpt` = ?,
                `body` = ?,
                `publisher_label` = ?,
                `publisher_identity_id` = ?,
                `created_by_account_id` = ?
            WHERE `news_id` = ?
            LIMIT 1
        ");
        $stmt->execute([
            $slug,
            $title,
            $excerpt,
            $body,
            (string)$publisher['label'],
            !empty($publisher['identity_id']) ? (int)$publisher['identity_id'] : null,
            (int)($user['id'] ?? 0),
            $newsId,
        ]);

        return true;
    }
}

if (!function_exists('spp_news_delete')) {
    function spp_news_delete($newsId) {
        $newsId = (int)$newsId;
        if ($newsId <= 0 || !spp_news_ensure_table()) {
            return false;
        }

        $pdo = spp_news_pdo();
        $table = spp_news_table_name();
        $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE `news_id` = ? LIMIT 1");
        return $stmt->execute([$newsId]);
    }
}

if (!function_exists('spp_news_reset_all')) {
    function spp_news_reset_all() {
        if (!spp_news_ensure_table()) {
            return false;
        }

        $pdo = spp_news_pdo();
        $table = spp_news_table_name();
        $pdo->exec("TRUNCATE TABLE `{$table}`");
        return true;
    }
}

if (!function_exists('spp_news_import_legacy_forum_topics')) {
    function spp_news_import_legacy_forum_topics(array $user, $limit = 25) {
        if (!spp_news_ensure_table()) {
            throw new RuntimeException('News storage is unavailable.');
        }

        $newsForumId = (int)spp_config_forum('news_forum_id', 0);
        if ($newsForumId <= 0) {
            throw new RuntimeException('No legacy news forum is configured.');
        }

        $forumPdo = spp_get_pdo('realmd', 1);
        $stmt = $forumPdo->prepare("
            SELECT
              t.topic_id,
              t.topic_name,
              t.topic_poster,
              t.topic_posted,
              (
                SELECT p.message
                FROM f_posts p
                WHERE p.topic_id = t.topic_id
                ORDER BY p.posted ASC
                LIMIT 1
              ) AS message
            FROM f_topics t
            WHERE t.forum_id = ?
            ORDER BY t.topic_posted DESC
            LIMIT " . max(1, (int)$limit)
        );
        $stmt->execute([$newsForumId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $imported = 0;
        foreach ($rows as $row) {
            $topicId = (int)($row['topic_id'] ?? 0);
            if ($topicId <= 0) {
                continue;
            }

            try {
                spp_news_create([
                    'source_forum_topic_id' => $topicId,
                    'title' => (string)($row['topic_name'] ?? ''),
                    'excerpt' => '',
                    'body' => (string)($row['message'] ?? ''),
                    'publisher_identity' => strtolower(trim((string)($row['topic_poster'] ?? ''))) === 'web dev' ? 'web_dev' : 'spp_team',
                ], $user);
                $imported++;
            } catch (Throwable $e) {
                if (stripos($e->getMessage(), 'Duplicate') !== false || stripos($e->getMessage(), 'uq_source_forum_topic') !== false) {
                    continue;
                }
                throw $e;
            }
        }

        return $imported;
    }
}
