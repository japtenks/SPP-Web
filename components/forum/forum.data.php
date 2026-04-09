<?php

if (!function_exists('spp_forum_data_resolve_realm_id')) {
    function spp_forum_data_resolve_realm_id(?int $realmId = null): int
    {
        if ((int)$realmId > 0) {
            return (int)$realmId;
        }

        $realmMap = (array)($GLOBALS['realmDbMap'] ?? array());
        $requestedRealmId = isset($_GET['realm']) ? (int)$_GET['realm'] : 0;
        if ($requestedRealmId > 0 && isset($realmMap[$requestedRealmId])) {
            return $requestedRealmId;
        }

        return !empty($realmMap) ? (int)spp_resolve_realm_id($realmMap) : 1;
    }
}

function get_forum_byid($id, ?int $realmId = null){
    $realmId = spp_forum_data_resolve_realm_id($realmId);
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_forums WHERE forum_id=?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_topic_byid($id, ?int $realmId = null){
    $realmId = spp_forum_data_resolve_realm_id($realmId);
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_topics WHERE topic_id=?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_post_byid($id, ?int $realmId = null){
    $realmId = spp_forum_data_resolve_realm_id($realmId);
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_posts WHERE post_id=?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_last_forum_topic($id, ?int $realmId = null){
    $realmId = spp_forum_data_resolve_realm_id($realmId);
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_topics WHERE forum_id=? ORDER BY last_post DESC LIMIT 1");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_last_topic_post($id, ?int $realmId = null){
    $realmId = spp_forum_data_resolve_realm_id($realmId);
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_posts WHERE topic_id=? ORDER BY posted DESC LIMIT 1");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_post_pos($tid,$pid, ?int $realmId = null){
    $realmId = spp_forum_data_resolve_realm_id($realmId);
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT count(*) FROM f_posts WHERE topic_id=? AND post_id<? ORDER BY posted");
    $stmt->execute([(int)$tid, (int)$pid]);
    return $stmt->fetchColumn();
}
