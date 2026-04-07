<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/news/news-index-page.php';

$newsIndexState = spp_news_load_index_page_state();
$newsIndexStop = !empty($newsIndexState['__stop']);
unset($newsIndexState['__stop']);
extract($newsIndexState, EXTR_SKIP);

if ($newsIndexStop) return;
?>
