<?php
if(INCLUDED!==true)exit;
$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/admin/admin-chartools-page.php';

$chartoolsState = spp_admin_chartools_load_page_state(array(
    'dbs' => $DBS,
    'charcfg_pdo' => $charcfgPdo,
    'messages' => array(
        'empty_field' => $empty_field,
        'character_1' => $character_1,
        'doesntexist' => $doesntexist,
        'alreadyexist' => $alreadyexist,
        'isonline' => $isonline,
        'renamesuccess' => $renamesuccess,
    ),
));

extract($chartoolsState, EXTR_SKIP);
