<?php

require_once __DIR__ . '/../../components/forum/forum.func.php';

if (!function_exists('spp_forum_load_viewcategory_page_state')) {
    function spp_forum_load_viewcategory_page_state(array $args = array()): array
    {
        $realmMap = (array)($args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $user = (array)($args['user'] ?? ($GLOBALS['user'] ?? array()));
        $get = (array)($args['get'] ?? $_GET);

        $state = array(
            '__stop' => false,
            'category_id' => isset($get['catid']) ? (int)$get['catid'] : 0,
            'categoryTitle' => '',
            'categoryItems' => array(),
        );

        $realmPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmMap));
        $allForumItems = spp_forum_build_index_items($realmPdo, $user);

        if ($state['category_id'] > 0 && !empty($allForumItems[$state['category_id']]) && is_array($allForumItems[$state['category_id']])) {
            $state['categoryItems'] = $allForumItems[$state['category_id']];
        }

        if (empty($state['categoryItems'])) {
            output_message('alert', 'This forum category does not exist.');
            $state['__stop'] = true;
            return $state;
        }

        $state['categoryTitle'] = preg_replace(
            '/^\s*Category\s+/i',
            '',
            (string)($state['categoryItems'][0]['cat_name'] ?? 'Forums')
        );

        $GLOBALS['pathway_info'][] = array(
            'title' => $state['categoryTitle'],
            'link' => '',
        );

        return $state;
    }
}
