<?php

require_once __DIR__ . '/../../components/account/account.helpers.php';
require_once __DIR__ . '/../../components/account/account.view.read.php';

if (!function_exists('spp_account_view_load_page_state')) {
    function spp_account_view_load_page_state(array $ctx = array()): array
    {
        $get = $ctx['get'] ?? $_GET;
        $auth = $ctx['auth'] ?? ($GLOBALS['auth'] ?? null);
        $user = $ctx['user'] ?? ($GLOBALS['user'] ?? array());
        $realmDbMap = $ctx['realmDbMap'] ?? ($GLOBALS['realmDbMap'] ?? array());

        $pathwayInfo = $GLOBALS['pathway_info'] ?? array();
        $pathwayInfo[] = array('title' => 'View Profile', 'link' => '');

        $profile = null;

        if (($get['action'] ?? '') === 'find') {
            $requestedName = trim((string)($get['name'] ?? ''));
            if ($requestedName === '') {
                output_message('alert', 'That profile link is missing a username.');
                $pathwayInfo[] = array('title' => 'Users', 'link' => '');
            } else {
                $uid = $auth ? (int)$auth->getid($requestedName) : 0;
                $profile = ($uid > 0 && $auth) ? $auth->getprofile($uid) : null;

                if (!is_array($profile) || empty($profile)) {
                    output_message('alert', 'That member profile could not be found.');
                    $pathwayInfo[] = array('title' => 'Users', 'link' => '');
                    $profile = null;
                } elseif ((int)($profile['hideprofile'] ?? 0) === 1) {
                    $profile = null;
                    $pathwayInfo[] = array('title' => 'Forbidden', 'link' => '');
                } else {
                    $profile = spp_account_view_build_profile($profile, (int)$uid, $user, $realmDbMap);
                    $pathwayInfo[] = array('title' => (string)$profile['username'], 'link' => '');
                }
            }
        }

        $GLOBALS['pathway_info'] = $pathwayInfo;

        return array(
            '__stop' => false,
            'profile' => $profile,
            'user' => $user,
        );
    }
}
