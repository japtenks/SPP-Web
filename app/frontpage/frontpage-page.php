<?php

require_once __DIR__ . '/../../components/news/news.helpers.php';
require_once __DIR__ . '/frontpage-runtime.php';
require_once __DIR__ . '/frontpage-server-info.php';

if (!function_exists('spp_frontpage_load_page_state')) {
    function spp_frontpage_load_page_state(array $args): array
    {
        $realmDbMap = (array)($args['realm_map'] ?? []);
        $config = $args['config'] ?? spp_runtime_config();

        $alltopics = array();
        foreach (spp_news_fetch_recent(6) as $index => $topic) {
            $topic['row_class'] = ($index % 2 === 0) ? 'alt' : '';
            $alltopics[] = $topic;
        }

        $realmId = spp_resolve_realm_id($realmDbMap);
        $realmPdo = spp_get_pdo('realmd', $realmId);

        spp_frontpage_increment_hit_counter($config);
        $servers = spp_frontpage_build_server_list($realmPdo, $realmDbMap, $config);
        $usersonhomepage = spp_frontpage_count_users_on_homepage($realmPdo, $config);

        return array(
            'alltopics' => $alltopics,
            'realm_id' => $realmId,
            'realm_pdo' => $realmPdo,
            'servers' => $servers,
            'usersonhomepage' => $usersonhomepage,
        );
    }
}
