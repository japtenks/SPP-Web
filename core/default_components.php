<?php

require_once dirname(__DIR__) . '/app/bootstrap/route-registry.php';

$mainnav_links = spp_main_navigation_registry();
$allowed_ext = spp_active_components();
$com_content = spp_seed_public_router_metadata();

?>
