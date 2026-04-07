<?php

require_once __DIR__ . '/../../components/news/news.helpers.php';

if (!function_exists('spp_news_load_index_page_state')) {
    function spp_news_load_index_page_state(array $args = array()): array
    {
        $pathwayInfo = $GLOBALS['pathway_info'] ?? array();
        $pathwayInfo[] = array('title' => 'News', 'link' => '');
        $GLOBALS['pathway_info'] = $pathwayInfo;

        return array(
            '__stop' => false,
            'news_items' => spp_news_fetch_recent(20),
        );
    }
}
