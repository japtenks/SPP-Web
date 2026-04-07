<?php

require_once __DIR__ . '/../../components/account/account.helpers.php';
require_once __DIR__ . '/../../components/account/account.register.actions.php';

if (!function_exists('spp_account_register_load_page_state')) {
    function spp_account_register_load_page_state(array $ctx = array()): array
    {
        $user = $ctx['user'] ?? ($GLOBALS['user'] ?? array());
        $realmDbMap = $ctx['realmDbMap'] ?? ($GLOBALS['realmDbMap'] ?? array());
        $server = $ctx['server'] ?? $_SERVER;

        $pathwayInfo = $GLOBALS['pathway_info'] ?? array();
        $pathwayInfo[] = array('title' => 'Register', 'link' => '');
        $GLOBALS['pathway_info'] = $pathwayInfo;

        if ((int)($user['id'] ?? 0) > 0) {
            redirect('index.php?n=account&sub=manage', 1);
            return array('__stop' => true);
        }

        $registerState = spp_account_register_build_state($realmDbMap);

        if ((int)spp_config_generic('site_register', 0) === 0) {
            $registerState['register_closed'] = true;
            $registerState['message_type'] = 'error';
            $registerState['message_html'] = '<strong>Registration is currently locked.</strong><br><small>Please try again later.</small>';
        } elseif (($server['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $registerState = spp_account_register_handle_submission($registerState);
        }

        return array(
            '__stop' => false,
            'registerRealmId' => (int)$registerState['realm_id'],
            'registerExpansion' => (int)$registerState['expansion'],
            'registerRealmlistHost' => (string)$registerState['realmlist_host'],
            'registerMessageType' => (string)$registerState['message_type'],
            'registerMessageHtml' => (string)$registerState['message_html'],
            'registerUsername' => (string)$registerState['username'],
            'registerCsrfToken' => (string)$registerState['csrf_token'],
            'registerClosed' => !empty($registerState['register_closed']),
        );
    }
}
