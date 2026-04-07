<?php
if (!defined('INCLUDED') || INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/realmlist-endpoint.php';

spp_server_emit_realmlist_endpoint(array(
    'query' => $_GET,
    'server' => $_SERVER,
));
?>
