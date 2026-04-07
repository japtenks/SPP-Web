<?php

if (!function_exists('spp_ensure_website_account_row')) {
    function spp_ensure_website_account_row(PDO $pdo, $accountId) {
        $accountId = (int)$accountId;
        if ($accountId <= 0) {
            return;
        }

        $stmtEnsure = $pdo->prepare("
            INSERT INTO website_accounts (account_id)
            SELECT ?
            WHERE NOT EXISTS (
                SELECT 1 FROM website_accounts WHERE account_id = ?
            )
        ");
        $stmtEnsure->execute([$accountId, $accountId]);
    }
}

if (!function_exists('spp_account_avatar_fallback_url')) {
    function spp_account_avatar_fallback_url(PDO $charsPdo, array $profile, array $accountCharacters = []) {
        $selectedGuid = (int)($profile['character_id'] ?? 0);
        $selectedRealmId = (int)($profile['character_realm_id'] ?? 0);
        if ($selectedGuid <= 0 && !empty($accountCharacters[0]['guid'])) {
            $selectedGuid = (int)$accountCharacters[0]['guid'];
            $selectedRealmId = (int)($accountCharacters[0]['realm_id'] ?? 0);
        }
        if ($selectedGuid <= 0) {
            return '';
        }

        $realmCandidates = [];
        if ($selectedRealmId > 0) {
            $realmCandidates[] = $selectedRealmId;
        }

        $realmDbMap = $GLOBALS['realmDbMap'] ?? [];
        foreach ($realmDbMap as $realmId => $realmInfo) {
            $realmId = (int)$realmId;
            if ($realmId > 0 && !in_array($realmId, $realmCandidates, true)) {
                $realmCandidates[] = $realmId;
            }
        }

        foreach ($realmCandidates as $realmId) {
            try {
                $lookupPdo = $realmId === $selectedRealmId && $selectedRealmId > 0 ? $charsPdo : spp_get_pdo('chars', $realmId);
                $stmt = $lookupPdo->prepare("SELECT guid, race, class, gender FROM characters WHERE guid=? AND account=? LIMIT 1");
                $stmt->execute([$selectedGuid, (int)($profile['id'] ?? 0)]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    continue;
                }

                if (!function_exists('get_character_portrait_path')) {
                    require_once(dirname(__DIR__) . '/forum/forum.func.php');
                }

                if (function_exists('get_character_portrait_path')) {
                    return (string)get_character_portrait_path(
                        (int)$row['guid'],
                        (int)$row['gender'],
                        (int)$row['race'],
                        (int)$row['class']
                    );
                }
            } catch (Throwable $e) {
                error_log('[account.helpers] Avatar fallback lookup failed: ' . $e->getMessage());
            }
        }

        return '';
    }
}

if (!function_exists('spp_sanitize_user_signature_text')) {
    function spp_sanitize_user_signature_text($signature) {
        $signature = str_replace(array("\r\n", "\r"), "\n", (string)$signature);
        $signature = trim(strip_tags($signature));

        return htmlspecialchars($signature, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('spp_avatar_storage_filename')) {
    function spp_avatar_storage_filename($ownerId, $extension) {
        $ownerId = (int)$ownerId;
        $extension = strtolower(trim((string)$extension));
        $extension = ltrim($extension, '.');
        if ($ownerId <= 0 || $extension === '') {
            return '';
        }

        return $ownerId . '.' . $extension;
    }
}

if (!function_exists('spp_avatar_normalize_mime_type')) {
    function spp_avatar_normalize_mime_type($mimeType) {
        $mimeType = strtolower(trim((string)$mimeType));
        $mimeMap = array(
            'image/gif' => 'image/gif',
            'image/jpeg' => 'image/jpeg',
            'image/jpg' => 'image/jpeg',
            'image/pjpeg' => 'image/jpeg',
            'image/png' => 'image/png',
            'image/x-png' => 'image/png',
        );

        return $mimeMap[$mimeType] ?? $mimeType;
    }
}

if (!function_exists('spp_avatar_validate_upload')) {
    function spp_avatar_validate_upload(array $upload, $maxBytes, $maxAvatarSize, &$error = '') {
        $error = '';

        if (empty($upload) || !isset($upload['tmp_name']) || !is_uploaded_file((string)$upload['tmp_name'])) {
            $error = 'Please choose a valid image file.';
            return false;
        }

        $uploadError = (int)($upload['error'] ?? UPLOAD_ERR_OK);
        if ($uploadError !== UPLOAD_ERR_OK) {
            $error = 'The avatar upload failed.';
            return false;
        }

        $uploadSize = (int)($upload['size'] ?? 0);
        $maxBytes = (int)$maxBytes;
        if ($uploadSize <= 0 || ($maxBytes > 0 && $uploadSize > $maxBytes)) {
            $error = 'The selected avatar is too large.';
            return false;
        }

        $imageInfo = @getimagesize((string)$upload['tmp_name']);
        if (empty($imageInfo) || empty($imageInfo[0]) || empty($imageInfo[1])) {
            $error = 'The selected file is not a valid image.';
            return false;
        }

        $width = (int)$imageInfo[0];
        $height = (int)$imageInfo[1];
        $maxAvatarSize = strtolower(trim((string)$maxAvatarSize));
        if ($maxAvatarSize !== '' && strpos($maxAvatarSize, 'x') !== false) {
            list($maxWidth, $maxHeight) = array_map('intval', explode('x', $maxAvatarSize, 2));
            if (($maxWidth > 0 && $width > $maxWidth) || ($maxHeight > 0 && $height > $maxHeight)) {
                $error = 'The selected avatar exceeds the allowed dimensions.';
                return false;
            }
        }

        $mimeType = '';
        try {
            if (class_exists('finfo')) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = (string)$finfo->file((string)$upload['tmp_name']);
            } elseif (function_exists('mime_content_type')) {
                $mimeType = (string)@mime_content_type((string)$upload['tmp_name']);
            }
        } catch (Throwable $e) {
            $mimeType = '';
        }

        if ($mimeType === '' && !empty($imageInfo['mime'])) {
            $mimeType = (string)$imageInfo['mime'];
        }

        $mimeType = spp_avatar_normalize_mime_type($mimeType);
        $allowedMimeMap = array(
            'image/gif' => 'gif',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        );

        if (!isset($allowedMimeMap[$mimeType])) {
            $error = 'Only GIF, JPG, and PNG avatars are allowed.';
            return false;
        }

        return array(
            'mime' => $mimeType,
            'extension' => $allowedMimeMap[$mimeType],
            'width' => $width,
            'height' => $height,
        );
    }
}

if (!function_exists('spp_avatar_store_upload')) {
    function spp_avatar_store_upload(array $upload, $ownerId, $avatarDir, $maxBytes, $maxAvatarSize, &$error = '') {
        $error = '';
        $validation = spp_avatar_validate_upload($upload, $maxBytes, $maxAvatarSize, $error);
        if ($validation === false) {
            return false;
        }

        $ownerId = (int)$ownerId;
        $avatarDir = rtrim((string)$avatarDir, "\\/");
        if ($ownerId <= 0 || $avatarDir === '') {
            $error = 'Avatar storage is not configured.';
            return false;
        }

        if (!is_dir($avatarDir) && !@mkdir($avatarDir, 0775, true) && !is_dir($avatarDir)) {
            $error = 'Avatar storage directory is not writable.';
            return false;
        }

        $finalName = spp_avatar_storage_filename($ownerId, (string)$validation['extension']);
        if ($finalName === '') {
            $error = 'Unable to determine avatar filename.';
            return false;
        }

        $finalPath = $avatarDir . DIRECTORY_SEPARATOR . $finalName;
        $tempPath = $avatarDir . DIRECTORY_SEPARATOR . '.upload-' . bin2hex(random_bytes(8)) . '.' . $validation['extension'];

        if (!move_uploaded_file((string)$upload['tmp_name'], $tempPath)) {
            $error = 'The avatar upload could not be saved.';
            return false;
        }

        if (is_file($finalPath)) {
            @unlink($finalPath);
        }

        if (!@rename($tempPath, $finalPath)) {
            @unlink($tempPath);
            $error = 'The avatar upload could not be finalized.';
            return false;
        }

        $validation['filename'] = $finalName;
        $validation['path'] = $finalPath;

        return $validation;
    }
}

if (!function_exists('spp_character_portrait_url')) {
    function spp_character_portrait_url($realmId, $characterGuid, $accountId = 0) {
        $realmId = (int)$realmId;
        $characterGuid = (int)$characterGuid;
        $accountId = (int)$accountId;

        if ($realmId <= 0 || $characterGuid <= 0) {
            return '';
        }

        try {
            $charPdo = spp_get_pdo('chars', $realmId);
            $sql = "SELECT guid, race, class, gender FROM characters WHERE guid=? LIMIT 1";
            $params = [$characterGuid];
            if ($accountId > 0) {
                $sql = "SELECT guid, race, class, gender FROM characters WHERE guid=? AND account=? LIMIT 1";
                $params[] = $accountId;
            }

            $stmt = $charPdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return '';
            }

            if (!function_exists('get_character_portrait_path')) {
                require_once(dirname(__DIR__) . '/forum/forum.func.php');
            }

            if (function_exists('get_character_portrait_path')) {
                return (string)get_character_portrait_path(
                    (int)$row['guid'],
                    (int)$row['gender'],
                    (int)$row['race'],
                    (int)$row['class']
                );
            }
        } catch (Throwable $e) {
            error_log('[account.helpers] Character portrait lookup failed: ' . $e->getMessage());
        }

        return '';
    }
}

if (!function_exists('spp_manage_allowed_profile_fields')) {
    function spp_manage_allowed_profile_fields($backgroundPreferencesAvailable, $canHideProfile, $canManageHiddenForums = false, $hiddenForumPreferenceAvailable = false) {
        $allowedFields = array(
            'theme',
            'display_name',
            'fname',
            'lname',
            'city',
            'location',
            'hidelocation',
            'gmt',
            'msn',
            'icq',
            'aim',
            'yahoo',
            'skype',
            'homepage',
            'gender',
            'signature',
        );

        if ($canHideProfile) {
            $allowedFields[] = 'hideprofile';
        }

        if ($backgroundPreferencesAvailable) {
            $allowedFields[] = 'background_mode';
            $allowedFields[] = 'background_image';
        }

        if ($canManageHiddenForums && $hiddenForumPreferenceAvailable) {
            $allowedFields[] = 'show_hidden_forums';
        }

        return spp_allowed_field_map($allowedFields);
    }
}

if (!function_exists('spp_account_can_manage_hidden_forums')) {
    function spp_account_can_manage_hidden_forums(array $user = array(), array $profile = array()): bool
    {
        return (int)($user['gmlevel'] ?? $profile['gmlevel'] ?? 0) >= 3
            || (int)($user['g_is_admin'] ?? $profile['g_is_admin'] ?? 0) === 1
            || (int)($user['g_is_supadmin'] ?? $profile['g_is_supadmin'] ?? 0) === 1
            || (int)($user['g_forum_moderate'] ?? $profile['g_forum_moderate'] ?? 0) === 1
            || (int)($profile['g_id'] ?? 0) >= 3;
    }
}

if (!function_exists('spp_account_login_redirect_target')) {
    function spp_account_login_redirect_target($requestedTarget, $fallbackTarget = 'index.php') {
        $target = trim((string)$requestedTarget);
        if ($target === '') {
            $target = $fallbackTarget;
        }

        $target = str_replace(array("\r", "\n"), '', $target);
        if (preg_match('#^https?://#i', $target) || strpos($target, '//') === 0) {
            return $fallbackTarget;
        }

        if ($target !== '' && $target[0] === '/') {
            $target = ltrim($target, '/');
        }

        if ($target === '' || stripos($target, 'index.php?n=account&sub=login') !== false) {
            return $fallbackTarget;
        }

        return $target;
    }
}

if (!function_exists('spp_account_logout_redirect_target')) {
    function spp_account_logout_redirect_target($requestedTarget, $fallbackTarget = 'index.php') {
        $target = spp_account_login_redirect_target($requestedTarget, $fallbackTarget);
        $targetLower = strtolower($target);

        if (strpos($targetLower, 'index.php?n=admin') !== false) {
            return $fallbackTarget;
        }

        if (strpos($targetLower, 'index.php?n=account') !== false) {
            if (strpos($targetLower, 'sub=login') !== false || strpos($targetLower, 'sub=register') !== false) {
                return $target;
            }

            return $fallbackTarget;
        }

        return $target;
    }
}

if (!function_exists('spp_format_total_played')) {
    function spp_format_total_played($seconds) {
        $seconds = max(0, (int)$seconds);
        $days = (int)floor($seconds / 86400);
        $hours = (int)floor(($seconds % 86400) / 3600);
        $minutes = (int)floor(($seconds % 3600) / 60);

        $parts = array();
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0 || $days > 0) {
            $parts[] = $hours . 'h';
        }
        $parts[] = $minutes . 'm';

        return implode(' ', $parts);
    }
}

if (!function_exists('spp_account_view_avatar_fallback_url')) {
    function spp_account_view_avatar_fallback_url($profile, $realmDbMap) {
        $characterGuid = (int)($profile['character_id'] ?? 0);
        if ($characterGuid <= 0) {
            return '';
        }

        foreach ($realmDbMap as $realmId => $realmInfo) {
            try {
                $charPdo = spp_get_pdo('chars', (int)$realmId);
                $stmt = $charPdo->prepare("SELECT guid, race, class, gender FROM characters WHERE guid=? LIMIT 1");
                $stmt->execute([$characterGuid]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    continue;
                }

                if (!function_exists('get_character_portrait_path')) {
                    require_once(dirname(__DIR__) . '/forum/forum.func.php');
                }

                if (function_exists('get_character_portrait_path')) {
                    return (string)get_character_portrait_path(
                        (int)$row['guid'],
                        (int)$row['gender'],
                        (int)$row['race'],
                        (int)$row['class']
                    );
                }
            } catch (Throwable $e) {
                error_log('[account.helpers] Account view avatar fallback lookup failed: ' . $e->getMessage());
            }
        }

        return '';
    }
}

if (!function_exists('spp_account_view_open_named_pdo')) {
    function spp_account_view_open_named_pdo($dbName) {
        $db = $GLOBALS['db'] ?? null;
        if (!is_array($db) || empty($db['host']) || empty($db['user'])) {
            throw new RuntimeException('Database config not available.');
        }

        return new PDO(
            "mysql:host={$db['host']};port={$db['port']};dbname={$dbName};charset=utf8mb4",
            $db['user'],
            $db['pass'],
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            )
        );
    }
}
