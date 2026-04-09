<?php

if (!function_exists('spp_csrf_token')) {
    function spp_csrf_token($formName = 'default') {
        if (!isset($_SESSION['spp_csrf_tokens']) || !is_array($_SESSION['spp_csrf_tokens'])) {
            $_SESSION['spp_csrf_tokens'] = array();
        }

        if (empty($_SESSION['spp_csrf_tokens'][$formName])) {
            $_SESSION['spp_csrf_tokens'][$formName] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION['spp_csrf_tokens'][$formName];
    }
}

if (!function_exists('spp_require_csrf')) {
    function spp_require_csrf($formName = 'default', $failureMessage = 'Security check failed. Please refresh the page and try again.', $redirectUrl = '') {
        $submittedToken = (string)($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
        $sessionToken = (string)($_SESSION['spp_csrf_tokens'][$formName] ?? '');

        if ($submittedToken !== '' && $sessionToken !== '' && hash_equals($sessionToken, $submittedToken)) {
            return true;
        }

        if (function_exists('output_message')) {
            $message = '<b>' . $failureMessage . '</b>';
            if ($redirectUrl !== '') {
                $message .= '<meta http-equiv=refresh content="2;url=' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">';
            }
            output_message('alert', $message);
        }

        exit;
    }
}

if (!function_exists('spp_action_url')) {
    function spp_action_url($path, array $params = array(), $formName = 'default') {
        $params['csrf_token'] = spp_csrf_token($formName);
        return $path . (strpos($path, '?') === false ? '?' : '&') . http_build_query($params);
    }
}

if (!function_exists('spp_has_valid_csrf')) {
    function spp_has_valid_csrf($formName = 'default') {
        $submittedToken = (string)($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
        $sessionToken = (string)($_SESSION['spp_csrf_tokens'][$formName] ?? '');

        return $submittedToken !== '' && $sessionToken !== '' && hash_equals($sessionToken, $submittedToken);
    }
}

if (!function_exists('spp_request_is_https')) {
    function spp_request_is_https() {
        return (
            (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || (string)($_SERVER['SERVER_PORT'] ?? '') === '443'
        );
    }
}

if (!function_exists('spp_cookie_options')) {
    function spp_cookie_options($expires = 0, $path = '') {
        $cookiePath = trim((string)$path);
        if ($cookiePath === '') {
            $cookiePath = (string)spp_config_temp('site_href', '/');
        }
        if ($cookiePath === '') {
            $cookiePath = '/';
        }
        if ($cookiePath[0] !== '/') {
            $cookiePath = '/' . ltrim($cookiePath, '/');
        }

        $options = array(
            'expires' => (int)$expires,
            'path' => $cookiePath,
            'secure' => spp_request_is_https(),
            'httponly' => true,
        );

        if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
            $options['samesite'] = 'Lax';
        }

        return $options;
    }
}

if (!function_exists('spp_set_site_cookie')) {
    function spp_set_site_cookie($name, $value, $expires = 0, $path = '') {
        $options = spp_cookie_options($expires, $path);

        if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
            return setcookie($name, $value, $options);
        }

        return setcookie(
            $name,
            $value,
            (int)$options['expires'],
            (string)$options['path'],
            '',
            (bool)$options['secure'],
            (bool)$options['httponly']
        );
    }
}

if (!function_exists('spp_parse_account_cookie_payload')) {
    function spp_parse_account_cookie_payload($rawCookie) {
        $cookieValue = is_string($rawCookie) ? stripslashes($rawCookie) : '';
        if ($cookieValue === '') {
            return null;
        }

        $decoded = @unserialize($cookieValue, array('allowed_classes' => false));
        if (!is_array($decoded)) {
            return null;
        }

        $userId = (int)($decoded[0] ?? 0);
        $accountKey = (string)($decoded[1] ?? '');
        if ($userId < 1 || $accountKey === '') {
            return null;
        }

        return array(
            'user_id' => $userId,
            'account_key' => $accountKey,
        );
    }
}

if (!function_exists('spp_login_throttle_key')) {
    function spp_login_throttle_key($username = '') {
        $normalizedUsername = strtoupper(trim((string)$username));
        $ipAddress = (string)($_SERVER['REMOTE_ADDR'] ?? '');

        return hash('sha256', $normalizedUsername . '|' . $ipAddress);
    }
}

if (!function_exists('spp_login_throttle_defaults')) {
    function spp_login_throttle_defaults() {
        return array(
            'first_failure_at' => 0,
            'last_failure_at' => 0,
            'failure_count' => 0,
            'lock_until' => 0,
        );
    }
}

if (!function_exists('spp_login_throttle_window_seconds')) {
    function spp_login_throttle_window_seconds() {
        return 900;
    }
}

if (!function_exists('spp_login_throttle_normalize_username')) {
    function spp_login_throttle_normalize_username($username = '') {
        return strtoupper(trim((string)$username));
    }
}

if (!function_exists('spp_login_throttle_client_ip')) {
    function spp_login_throttle_client_ip() {
        return trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    }
}

if (!function_exists('spp_login_throttle_context')) {
    function spp_login_throttle_context($username = '') {
        $normalizedUsername = spp_login_throttle_normalize_username($username);
        $clientIpHash = hash('sha256', spp_login_throttle_client_ip());

        return array(
            'lookup_key' => hash('sha256', $normalizedUsername . '|' . $clientIpHash),
            'normalized_username' => $normalizedUsername,
            'client_ip_hash' => $clientIpHash,
        );
    }
}

if (!function_exists('spp_login_throttle_pdo')) {
    function spp_login_throttle_pdo() {
        static $pdo = null;
        static $resolved = false;

        if ($resolved) {
            return $pdo;
        }

        $resolved = true;

        try {
            $pdo = function_exists('spp_canonical_auth_pdo') ? spp_canonical_auth_pdo() : spp_get_pdo('realmd', 1);
        } catch (Throwable $e) {
            error_log('[security.login_throttle] PDO bootstrap failed: ' . $e->getMessage());
            $pdo = null;
        }

        return $pdo;
    }
}

if (!function_exists('spp_login_throttle_cleanup_state')) {
    function spp_login_throttle_cleanup_state(array $state, $now = null) {
        $now = $now !== null ? (int)$now : time();
        $defaults = spp_login_throttle_defaults();

        $state['first_failure_at'] = (int)($state['first_failure_at'] ?? 0);
        $state['last_failure_at'] = (int)($state['last_failure_at'] ?? 0);
        $state['failure_count'] = max(0, (int)($state['failure_count'] ?? 0));
        $state['lock_until'] = max(0, (int)($state['lock_until'] ?? 0));

        if ($state['first_failure_at'] > 0
            && ($now - $state['first_failure_at']) > spp_login_throttle_window_seconds()
            && $state['lock_until'] <= $now
        ) {
            return $defaults;
        }

        return $state;
    }
}

if (!function_exists('spp_login_throttle_db_state')) {
    function spp_login_throttle_db_state($username = '') {
        $pdo = spp_login_throttle_pdo();
        if (!$pdo instanceof PDO) {
            return null;
        }

        $context = spp_login_throttle_context($username);
        try {
            $stmt = $pdo->prepare("
                SELECT first_failure_at, last_failure_at, failure_count, lock_until
                FROM website_login_throttle
                WHERE lookup_key = ?
                LIMIT 1
            ");
            $stmt->execute(array($context['lookup_key']));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return spp_login_throttle_defaults();
            }

            $state = spp_login_throttle_cleanup_state($row);
            if ($state['failure_count'] === 0 && $state['lock_until'] === 0 && $row !== $state) {
                $deleteStmt = $pdo->prepare("DELETE FROM website_login_throttle WHERE lookup_key = ? LIMIT 1");
                $deleteStmt->execute(array($context['lookup_key']));
            }

            return $state;
        } catch (Throwable $e) {
            error_log('[security.login_throttle] DB state read failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('spp_login_throttle_session_state')) {
    function spp_login_throttle_session_state($username = '') {
        if (!isset($_SESSION['spp_login_throttle']) || !is_array($_SESSION['spp_login_throttle'])) {
            $_SESSION['spp_login_throttle'] = array();
        }

        $key = spp_login_throttle_key($username);
        $state = $_SESSION['spp_login_throttle'][$key] ?? array();
        $state = spp_login_throttle_cleanup_state($state);
        $_SESSION['spp_login_throttle'][$key] = $state;

        return $state;
    }
}

if (!function_exists('spp_login_throttle_state')) {
    function spp_login_throttle_state($username = '') {
        $dbState = spp_login_throttle_db_state($username);
        if ($dbState !== null) {
            return $dbState;
        }

        return spp_login_throttle_session_state($username);
    }
}

if (!function_exists('spp_login_throttle_is_locked')) {
    function spp_login_throttle_is_locked($username = '') {
        $state = spp_login_throttle_state($username);
        $now = time();
        $lockUntil = (int)($state['lock_until'] ?? 0);

        return array(
            'locked' => $lockUntil > $now,
            'retry_after' => max(0, $lockUntil - $now),
            'failure_count' => (int)($state['failure_count'] ?? 0),
        );
    }
}

if (!function_exists('spp_login_throttle_record_failure')) {
    function spp_login_throttle_record_failure($username = '') {
        $pdo = spp_login_throttle_pdo();
        if ($pdo instanceof PDO) {
            $context = spp_login_throttle_context($username);
            $now = time();

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    SELECT first_failure_at, last_failure_at, failure_count, lock_until
                    FROM website_login_throttle
                    WHERE lookup_key = ?
                    LIMIT 1
                    FOR UPDATE
                ");
                $stmt->execute(array($context['lookup_key']));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                $state = $row ? spp_login_throttle_cleanup_state($row, $now) : spp_login_throttle_defaults();
                if (empty($state['first_failure_at'])) {
                    $state['first_failure_at'] = $now;
                }

                $state['last_failure_at'] = $now;
                $state['failure_count'] = max(0, (int)$state['failure_count']) + 1;

                if ($state['failure_count'] >= 5) {
                    $backoffSeconds = min(300, (int)(pow(2, $state['failure_count'] - 5) * 5));
                    $state['lock_until'] = max((int)$state['lock_until'], $now + $backoffSeconds);
                }

                if ($row) {
                    $writeStmt = $pdo->prepare("
                        UPDATE website_login_throttle
                        SET normalized_username = ?, client_ip_hash = ?, first_failure_at = ?, last_failure_at = ?, failure_count = ?, lock_until = ?, updated_at = NOW()
                        WHERE lookup_key = ?
                        LIMIT 1
                    ");
                    $writeStmt->execute(array(
                        $context['normalized_username'],
                        $context['client_ip_hash'],
                        $state['first_failure_at'],
                        $state['last_failure_at'],
                        $state['failure_count'],
                        $state['lock_until'],
                        $context['lookup_key'],
                    ));
                } else {
                    $writeStmt = $pdo->prepare("
                        INSERT INTO website_login_throttle
                            (lookup_key, normalized_username, client_ip_hash, first_failure_at, last_failure_at, failure_count, lock_until, created_at, updated_at)
                        VALUES
                            (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $writeStmt->execute(array(
                        $context['lookup_key'],
                        $context['normalized_username'],
                        $context['client_ip_hash'],
                        $state['first_failure_at'],
                        $state['last_failure_at'],
                        $state['failure_count'],
                        $state['lock_until'],
                    ));
                }

                $pdo->commit();
                return $state;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('[security.login_throttle] DB failure record failed: ' . $e->getMessage());
            }
        }

        if (!isset($_SESSION['spp_login_throttle']) || !is_array($_SESSION['spp_login_throttle'])) {
            $_SESSION['spp_login_throttle'] = array();
        }

        $key = spp_login_throttle_key($username);
        $state = spp_login_throttle_session_state($username);
        $now = time();

        if (empty($state['first_failure_at'])) {
            $state['first_failure_at'] = $now;
        }

        $state['last_failure_at'] = $now;
        $state['failure_count'] = max(0, (int)($state['failure_count'] ?? 0)) + 1;

        if ($state['failure_count'] >= 5) {
            $backoffSeconds = min(300, (int)(pow(2, $state['failure_count'] - 5) * 5));
            $state['lock_until'] = max((int)($state['lock_until'] ?? 0), $now + $backoffSeconds);
        }

        $_SESSION['spp_login_throttle'][$key] = $state;

        return $state;
    }
}

if (!function_exists('spp_login_throttle_clear')) {
    function spp_login_throttle_clear($username = '') {
        $pdo = spp_login_throttle_pdo();
        if ($pdo instanceof PDO) {
            $context = spp_login_throttle_context($username);
            try {
                $stmt = $pdo->prepare("DELETE FROM website_login_throttle WHERE lookup_key = ? LIMIT 1");
                $stmt->execute(array($context['lookup_key']));
            } catch (Throwable $e) {
                error_log('[security.login_throttle] DB clear failed: ' . $e->getMessage());
            }
        }

        if (isset($_SESSION['spp_login_throttle']) && is_array($_SESSION['spp_login_throttle'])) {
            $key = spp_login_throttle_key($username);
            unset($_SESSION['spp_login_throttle'][$key]);
        }
    }
}

if (!function_exists('spp_login_feedback_set')) {
    function spp_login_feedback_set($message) {
        $_SESSION['spp_login_feedback'] = (string)$message;
    }
}

if (!function_exists('spp_login_feedback_pull')) {
    function spp_login_feedback_pull() {
        $message = (string)($_SESSION['spp_login_feedback'] ?? '');
        unset($_SESSION['spp_login_feedback']);

        return $message;
    }
}
