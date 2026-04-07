<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/news/news-view-page.php';

$newsViewState = spp_news_load_view_page_state(array(
    'get' => $_GET,
));
$newsViewStop = !empty($newsViewState['__stop']);
unset($newsViewState['__stop']);
extract($newsViewState, EXTR_SKIP);

if ($newsViewStop) return;
?>
