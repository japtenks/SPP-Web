<?php

require_once __DIR__ . '/../../components/news/news.helpers.php';

if (!function_exists('spp_news_load_view_page_state')) {
    function spp_news_load_view_page_state(array $args = array()): array
    {
        $get = (array)($args['get'] ?? $_GET);

        $state = array(
            '__stop' => false,
            'news_article' => spp_news_fetch_by_id_or_slug(
                (int)($get['id'] ?? 0),
                (string)($get['slug'] ?? '')
            ),
        );

        if (empty($state['news_article'])) {
            output_message('alert', 'News post not found.');
            $state['__stop'] = true;
            return $state;
        }

        $pathwayInfo = $GLOBALS['pathway_info'] ?? array();
        $pathwayInfo[] = array('title' => 'News', 'link' => spp_route_url('news', 'index'));

        if (!empty($state['news_article']['topic_name'])) {
            $pathwayInfo[] = array(
                'title' => (string)$state['news_article']['topic_name'],
                'link' => '',
            );
        }

        $GLOBALS['pathway_info'] = $pathwayInfo;

        return $state;
    }
}
