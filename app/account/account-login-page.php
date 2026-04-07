<?php

require_once __DIR__ . '/../../components/account/account.helpers.php';

if (!function_exists('spp_account_login_load_page_state')) {
    function spp_account_login_load_page_state(array $ctx = array()): array
    {
        $request = $ctx['request'] ?? $_REQUEST;
        $server = $ctx['server'] ?? $_SERVER;
        $user = $ctx['user'] ?? ($GLOBALS['user'] ?? array());
        $auth = $ctx['auth'] ?? ($GLOBALS['auth'] ?? null);

        $pathwayInfo = $GLOBALS['pathway_info'] ?? array();
        $pathwayInfo[] = array('title' => 'Login', 'link' => '');
        $GLOBALS['pathway_info'] = $pathwayInfo;

        $loginMessage = '';
        $loginMessageClass = '';
        $loginFormUsername = trim((string)($request['login'] ?? ''));
        $loginCsrfToken = spp_csrf_token('account_login');
        $logoutCsrfToken = spp_csrf_token('account_logout');

        $action = (string)($request['action'] ?? '');
        if ($action === 'login' && strtoupper((string)($server['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $login = (string)($request['login'] ?? '');
            $pass = (string)($request['pass'] ?? '');
            $returnTo = spp_account_login_redirect_target(
                $request['returnto'] ?? '',
                'index.php?n=forum'
            );

            if (!spp_has_valid_csrf('account_login')) {
                $loginMessage = 'Security check failed. Please refresh the page and try again.';
                $loginMessageClass = ' is-error';
            } else {
            if ($auth && $auth->login(array('username' => $login, 'password' => $pass))) {
                redirect($returnTo, 1);
                return array('__stop' => true);
            }

                $loginMessage = function_exists('spp_login_feedback_pull')
                    ? spp_login_feedback_pull()
                    : '';
                if ($loginMessage === '') {
                    if ($login === '' || $pass === '') {
                        $loginMessage = 'Please enter both your username and password.';
                    } else {
                        $loginMessage = 'Login failed. Check your username and password and try again.';
                    }
                }
                $loginMessageClass = ' is-error';
            }
        } elseif ($action === 'logout' && strtoupper((string)($server['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            if (!spp_has_valid_csrf('account_logout')) {
                $loginMessage = 'Security check failed. Please refresh the page and try again.';
                $loginMessageClass = ' is-error';
            } else {
            if ($auth) {
                $auth->logout();
            }

            $returnTo = spp_account_logout_redirect_target(
                $request['returnto'] ?? ($server['HTTP_REFERER'] ?? ''),
                'index.php?n=forum'
            );
            redirect($returnTo, 1);
            return array('__stop' => true);
            }
        }

        $loginReturnTo = trim((string)($request['returnto'] ?? ''));
        if ($loginReturnTo === '' && !empty($server['HTTP_REFERER'])) {
            $loginReturnTo = (string)$server['HTTP_REFERER'];
        }
        if ($loginReturnTo === '' || stripos($loginReturnTo, 'index.php?n=account&sub=login') !== false) {
            $loginReturnTo = 'index.php?n=forum';
        }

        return array(
            '__stop' => false,
            'user' => $user,
            'login_message' => $loginMessage,
            'login_message_class' => $loginMessageClass,
            'login_form_username' => $loginFormUsername,
            'login_return_to' => $loginReturnTo,
            'login_csrf_token' => $loginCsrfToken,
            'logout_csrf_token' => $logoutCsrfToken,
        );
    }
}
