<?php
if (INCLUDED !== true) {
    exit;
}

require_once __DIR__ . '/../news/news.helpers.php';

$news_admin_errors = array();
$news_admin_success = '';
$news_admin_edit_id = (int)($_GET['edit'] ?? ($_POST['news_id'] ?? 0));
$news_admin_form = array(
    'title' => trim((string)($_POST['title'] ?? '')),
    'excerpt' => trim((string)($_POST['excerpt'] ?? '')),
    'body' => trim((string)($_POST['body'] ?? '')),
    'publisher_identity' => trim((string)($_POST['publisher_identity'] ?? 'spp_team')),
);
$news_publisher_options = spp_forum_news_publisher_options();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['save_news'])) {
    spp_require_csrf('admin_news');
    try {
        if ($news_admin_edit_id > 0) {
            spp_news_update($news_admin_edit_id, $news_admin_form, $user);
            redirect('index.php?n=admin&sub=news&updated=' . $news_admin_edit_id, 1);
            exit;
        }

        $newNewsId = spp_news_create($news_admin_form, $user);
        $news_admin_form = array(
            'title' => '',
            'excerpt' => '',
            'body' => '',
            'publisher_identity' => 'spp_team',
        );
        redirect('index.php?n=admin&sub=news&created=' . $newNewsId, 1);
        exit;
    } catch (Throwable $e) {
        $news_admin_errors[] = $e->getMessage();
    }
}

if (!empty($_GET['created'])) {
    $news_admin_success = 'News post published.';
}
if (!empty($_GET['updated'])) {
    $news_admin_success = 'News post updated.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_news'])) {
    spp_require_csrf('admin_news');
    $deleteNewsId = (int)($_POST['news_id'] ?? 0);
    if ($deleteNewsId > 0) {
        spp_news_delete($deleteNewsId);
        redirect('index.php?n=admin&sub=news&deleted=' . $deleteNewsId, 1);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['reset_news'])) {
    spp_require_csrf('admin_news');
    spp_news_reset_all();
    redirect('index.php?n=admin&sub=news&reset=1', 1);
    exit;
}

if (!empty($_GET['deleted'])) {
    $news_admin_success = 'News post deleted.';
}
if (!empty($_GET['reset'])) {
    $news_admin_success = 'All news posts were cleared.';
}

if ($news_admin_edit_id > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $editingRow = spp_news_fetch_row_by_id($news_admin_edit_id);
    if (!empty($editingRow)) {
        $publisherIdentity = 'spp_team';
        if (strtolower(trim((string)($editingRow['publisher_label'] ?? ''))) === 'web dev') {
            $publisherIdentity = 'web_dev';
        }
        $news_admin_form = array(
            'title' => (string)($editingRow['title'] ?? ''),
            'excerpt' => (string)($editingRow['excerpt'] ?? ''),
            'body' => (string)($editingRow['body'] ?? ''),
            'publisher_identity' => $publisherIdentity,
        );
    } else {
        $news_admin_edit_id = 0;
        $news_admin_errors[] = 'News post not found.';
    }
}

$news_admin_items = spp_news_fetch_admin_rows(25);
?>
