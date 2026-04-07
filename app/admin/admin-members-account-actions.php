<?php

require_once __DIR__ . '/../../components/account/account.helpers.php';

if (!function_exists('spp_admin_members_handle_account_action')) {
    function spp_admin_members_handle_account_action(array $context): bool
    {
        $action = (string)($context['action'] ?? '');
        $accountId = (int)($context['account_id'] ?? 0);
        $selectedRealmId = (int)($context['selected_realm_id'] ?? 0);
        $membersPdo = $context['members_pdo'];
        $realmDbMap = (array)($context['realm_db_map'] ?? array());
        $user = (array)($context['user'] ?? array());

        if ($accountId <= 0 || $action === '' || $action === '0') {
            return false;
        }

        if ($action === 'changepass') {
            spp_require_csrf('admin_members');
            $newpass = trim((string)($_POST['new_pass'] ?? ''));
            $confirmPass = trim((string)($_POST['confirm_new_pass'] ?? ''));
            if (strlen($newpass) > 3) {
                if ($confirmPass === '' || $newpass !== $confirmPass) {
                    redirect('index.php?n=admin&sub=members&id=' . $accountId . '&pwreset=mismatch', 1);
                    exit;
                }

                $stmt = $membersPdo->prepare("SELECT username FROM account WHERE id=?");
                $stmt->execute([$accountId]);
                $username = $stmt->fetchColumn();
                if (!$username) {
                    redirect('index.php?n=admin&sub=members&id=' . $accountId . '&pwreset=missing', 1);
                    exit;
                }

                $stmt = $membersPdo->prepare("UPDATE account SET sessionkey = NULL WHERE id=?");
                $stmt->execute([$accountId]);
                list($salt, $verifier) = getRegistrationData((string)$username, $newpass);
                $stmt = $membersPdo->prepare("UPDATE account SET s=?, v=? WHERE id=?");
                $stmt->execute([$salt, $verifier, $accountId]);

                $stmt = $membersPdo->prepare("SELECT s, v FROM account WHERE id=? LIMIT 1");
                $stmt->execute([$accountId]);
                $updatedAccount = $stmt->fetch(PDO::FETCH_ASSOC);
                if (empty($updatedAccount['s']) || empty($updatedAccount['v'])) {
                    redirect('index.php?n=admin&sub=members&id=' . $accountId . '&pwreset=failed', 1);
                    exit;
                }

                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&pwreset=1', 1);
                exit;
            }

            output_message('alert', '<b>Your new password must be at least 4 characters long.</b><meta http-equiv=refresh content="2;url=index.php?n=admin&sub=members&id=' . $accountId . '">');
            return true;
        }

        if ($action === 'ban') {
            spp_require_csrf('admin_members');
            $stmt = $membersPdo->prepare("INSERT into account_banned (id, bandate, unbandate, bannedby, banreason, active) values (?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()-10, 'WEBSERVER', 'WEBSERVER', 1)");
            $stmt->execute([$accountId]);
            $stmt = $membersPdo->prepare("SELECT last_ip FROM account WHERE id=?");
            $stmt->execute([$accountId]);
            $lastIp = $stmt->fetchColumn();
            $stmt = $membersPdo->prepare("INSERT into ip_banned (ip, bandate, unbandate, bannedby, banreason) values (?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()-10, 'WEBSERVER', 'WEBSERVER')");
            $stmt->execute([$lastIp]);
            $stmt = $membersPdo->prepare("UPDATE website_accounts SET g_id=5 WHERE account_id=?");
            $stmt->execute([$accountId]);
            redirect('index.php?n=admin&sub=members&id=' . $accountId, 1);
            exit;
        }

        if ($action === 'unban') {
            spp_require_csrf('admin_members');
            $stmt = $membersPdo->prepare("UPDATE account_banned SET active=0 WHERE id=?");
            $stmt->execute([$accountId]);
            $stmt = $membersPdo->prepare("SELECT last_ip FROM account WHERE id=?");
            $stmt->execute([$accountId]);
            $lastIp = $stmt->fetchColumn();
            $stmt = $membersPdo->prepare("DELETE FROM ip_banned WHERE ip=?");
            $stmt->execute([$lastIp]);
            $stmt = $membersPdo->prepare("UPDATE website_accounts SET g_id=2 WHERE account_id=?");
            $stmt->execute([$accountId]);
            redirect('index.php?n=admin&sub=members&id=' . $accountId, 1);
            exit;
        }

        if ($action === 'change') {
            spp_require_csrf('admin_members');
            $profile = isset($_POST['profile']) && is_array($_POST['profile']) ? $_POST['profile'] : array();
            $allowedFields = spp_admin_members_account_fields((int)($user['gmlevel'] ?? 0) === 3);
            $profile = spp_filter_allowed_fields($profile, $allowedFields);
            if (!empty($profile)) {
                $setClause = implode(',', array_map(function ($k) {
                    return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?';
                }, array_keys($profile)));
                $values = array_values($profile);
                $values[] = $accountId;
                $stmt = $membersPdo->prepare("UPDATE account SET $setClause WHERE id=? LIMIT 1");
                $stmt->execute($values);
            }
            redirect('index.php?n=admin&sub=members&id=' . $accountId, 1);
            exit;
        }

        if ($action === 'change2') {
            spp_require_csrf('admin_members');
            spp_ensure_website_account_row($membersPdo, $accountId);
            $avatarDir = rtrim((string)spp_config_generic('avatar_path', 'uploads/avatars/'), "\\/");
            if ($avatarDir === 'images/avatars' || $avatarDir === 'images/avatars/') {
                $avatarDir = 'uploads/avatars';
            }
            $currentAvatar = '';
            $stmtCurrentAvatar = $membersPdo->prepare("SELECT avatar FROM website_accounts WHERE account_id=? LIMIT 1");
            $stmtCurrentAvatar->execute([$accountId]);
            $currentAvatar = trim((string)$stmtCurrentAvatar->fetchColumn());

            if (is_array($_FILES['avatar'] ?? null) && !empty($_FILES['avatar']['tmp_name'])) {
                $avatarError = '';
                $storedAvatar = spp_avatar_store_upload(
                    (array)$_FILES['avatar'],
                    $accountId,
                    $avatarDir,
                    (int)spp_config_generic('max_avatar_file', 102400),
                    (string)spp_config_generic('max_avatar_size', '64x64'),
                    $avatarError
                );

                if ($storedAvatar !== false) {
                    $stmt = $membersPdo->prepare("UPDATE website_accounts SET avatar=? WHERE account_id=? LIMIT 1");
                    $stmt->execute([$storedAvatar['filename'], $accountId]);
                    if ($currentAvatar !== '' && $currentAvatar !== $storedAvatar['filename']) {
                        @unlink($avatarDir . DIRECTORY_SEPARATOR . basename($currentAvatar));
                    }
                } elseif ($avatarError !== '') {
                    output_message('alert', '<b>' . htmlspecialchars($avatarError, ENT_QUOTES, 'UTF-8') . '</b><meta http-equiv=refresh content="2;url=index.php?n=admin&sub=members&id=' . $accountId . '">');
                }
            } elseif ((int)($_POST['deleteavatar'] ?? 0) === 1) {
                if ($currentAvatar !== '' && @unlink($avatarDir . DIRECTORY_SEPARATOR . basename($currentAvatar))) {
                    $stmt = $membersPdo->prepare("UPDATE website_accounts SET avatar=NULL WHERE account_id=? LIMIT 1");
                    $stmt->execute([$accountId]);
                }
            }

            $profile = isset($_POST['profile']) && is_array($_POST['profile']) ? $_POST['profile'] : array();
            $allowedWebsiteFields = spp_admin_members_website_fields((int)spp_config_generic('change_template', 0) === 1);
            $profile = spp_filter_allowed_fields($profile, $allowedWebsiteFields);
            if (isset($profile['signature'])) {
                $profile['signature'] = spp_sanitize_user_signature_text($profile['signature']);
            }
            if (!empty($profile)) {
                $setClause = implode(',', array_map(function ($k) {
                    return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?';
                }, array_keys($profile)));
                $values = array_values($profile);
                $values[] = $accountId;
                $stmt = $membersPdo->prepare("UPDATE website_accounts SET $setClause WHERE account_id=? LIMIT 1");
                $stmt->execute($values);
            }
            redirect('index.php?n=admin&sub=members&id=' . $accountId, 1);
            exit;
        }

        if ($action === 'setbotsignatures') {
            spp_require_csrf('admin_members');
            $stmtBot = $membersPdo->prepare("SELECT username FROM account WHERE id=? LIMIT 1");
            $stmtBot->execute([$accountId]);
            $botUsername = (string)$stmtBot->fetchColumn();
            if (stripos($botUsername, 'rndbot') === 0) {
                $postedSignatures = $_POST['character_signature'] ?? array();
                foreach ($postedSignatures as $signatureKey => $signature) {
                    $signatureKey = (string)$signatureKey;
                    $keyParts = explode(':', $signatureKey, 2);
                    $signatureRealmId = isset($keyParts[1]) ? (int)$keyParts[0] : $selectedRealmId;
                    $characterGuid = isset($keyParts[1]) ? (int)$keyParts[1] : (int)$signatureKey;
                    if ($signatureRealmId <= 0 || empty($realmDbMap[$signatureRealmId]) || $characterGuid <= 0) {
                        continue;
                    }

                    $signatureCharsPdo = spp_get_pdo('chars', $signatureRealmId);
                    $stmtChar = $signatureCharsPdo->prepare("SELECT guid, name FROM characters WHERE guid=? AND account=? LIMIT 1");
                    $stmtChar->execute([$characterGuid, $accountId]);
                    $charRow = $stmtChar->fetch(PDO::FETCH_ASSOC);
                    if (!$charRow) {
                        continue;
                    }

                    $cleanSignature = spp_sanitize_user_signature_text($signature);
                    $identityId = spp_ensure_char_identity($signatureRealmId, (int)$charRow['guid'], $accountId, (string)$charRow['name']);
                    if ($identityId > 0) {
                        spp_update_identity_signature($identityId, $cleanSignature);
                    }
                }
            }
            redirect('index.php?n=admin&sub=members&id=' . $accountId, 1);
            exit;
        }

        if ($action === 'transferchar' || $action === 'transferbotchar') {
            spp_require_csrf('admin_members');
            $stmtBot = $membersPdo->prepare("SELECT username FROM account WHERE id=? LIMIT 1");
            $stmtBot->execute([$accountId]);

            $characterGuid = (int)($_POST['transfer_character_guid'] ?? 0);
            $targetAccountId = (int)($_POST['target_account_id'] ?? 0);
            if ($characterGuid <= 0) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=missing_character', 1);
                exit;
            }

            $stmtTarget = $membersPdo->prepare("
                SELECT id, username
                FROM account
                WHERE id = ?
                  AND LOWER(username) NOT LIKE 'rndbot%'
                LIMIT 1
            ");
            $stmtTarget->execute([$targetAccountId]);
            $targetAccount = $stmtTarget->fetch(PDO::FETCH_ASSOC) ?: null;
            $targetAccountId = (int)($targetAccount['id'] ?? 0);
            $targetUsername = (string)($targetAccount['username'] ?? '');

            if ($targetAccountId <= 0) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=missing_target', 1);
                exit;
            }
            if ($targetAccountId === $accountId) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=same_target', 1);
                exit;
            }

            $selectedCharsPdo = spp_get_pdo('chars', $selectedRealmId);
            $stmtChar = $selectedCharsPdo->prepare("SELECT guid, name FROM characters WHERE guid=? AND account=? LIMIT 1");
            $stmtChar->execute([$characterGuid, $accountId]);
            $charRow = $stmtChar->fetch(PDO::FETCH_ASSOC);
            if (!$charRow) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=missing_character', 1);
                exit;
            }

            $sourceAccountOnline = !empty(spp_admin_online_characters_for_account($selectedCharsPdo, $accountId));
            $targetAccountOnline = !empty(spp_admin_online_characters_for_account($selectedCharsPdo, $targetAccountId));
            $characterOnline = spp_admin_character_is_online($selectedCharsPdo, $characterGuid, $accountId);

            if ($characterOnline) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&character_realm_id=' . $selectedRealmId . '&xfer=char_online', 1);
                exit;
            }
            if ($sourceAccountOnline) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&character_realm_id=' . $selectedRealmId . '&xfer=source_online', 1);
                exit;
            }
            if ($targetAccountOnline) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&character_realm_id=' . $selectedRealmId . '&xfer=target_online', 1);
                exit;
            }

            try {
                $selectedCharsPdo->beginTransaction();
                $membersPdo->beginTransaction();

                $stmtMove = $selectedCharsPdo->prepare("UPDATE characters SET account=? WHERE guid=? AND account=? LIMIT 1");
                $stmtMove->execute([$targetAccountId, $characterGuid, $accountId]);
                if ($stmtMove->rowCount() <= 0) {
                    throw new RuntimeException('Character account update failed.');
                }

                spp_ensure_website_account_row($membersPdo, $targetAccountId);

                $identity = function_exists('spp_get_char_identity') ? spp_get_char_identity($selectedRealmId, $characterGuid) : null;
                if (!empty($identity['identity_id'])) {
                    $targetIsBot = stripos($targetUsername, 'rndbot') === 0;
                    $stmtIdentity = $membersPdo->prepare("
                        UPDATE website_identities
                        SET owner_account_id = ?, identity_type = ?, is_bot = ?, is_active = 1, updated_at = NOW()
                        WHERE identity_id = ?
                        LIMIT 1
                    ");
                    $stmtIdentity->execute([$targetAccountId, $targetIsBot ? 'bot_character' : 'character', $targetIsBot ? 1 : 0, (int)$identity['identity_id']]);
                }

                $stmtWebsiteSource = $membersPdo->prepare("
                    UPDATE website_accounts
                    SET character_id = CASE WHEN character_id = ? THEN NULL ELSE character_id END,
                        character_name = CASE WHEN character_id = ? THEN NULL ELSE character_name END
                    WHERE account_id = ?
                ");
                $stmtWebsiteSource->execute([$characterGuid, $characterGuid, $accountId]);

                $stmtWebsiteTarget = $membersPdo->prepare("SELECT character_id FROM website_accounts WHERE account_id=? LIMIT 1");
                $stmtWebsiteTarget->execute([$targetAccountId]);
                $targetSelectedCharacter = (int)$stmtWebsiteTarget->fetchColumn();
                if ($targetSelectedCharacter <= 0) {
                    $stmtWebsiteAssign = $membersPdo->prepare("UPDATE website_accounts SET character_id=?, character_name=? WHERE account_id=?");
                    $stmtWebsiteAssign->execute([$characterGuid, (string)$charRow['name'], $targetAccountId]);
                }

                $selectedCharsPdo->commit();
                $membersPdo->commit();
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&character_realm_id=' . $selectedRealmId . '&xfer=success', 1);
                exit;
            } catch (Throwable $e) {
                if ($selectedCharsPdo->inTransaction()) {
                    $selectedCharsPdo->rollBack();
                }
                if ($membersPdo->inTransaction()) {
                    $membersPdo->rollBack();
                }
                error_log('[admin.members] Bot character transfer failed: ' . $e->getMessage());
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&character_realm_id=' . $selectedRealmId . '&xfer=failed', 1);
                exit;
            }
        }

        if ($action === 'deletechar') {
            spp_require_csrf('admin_members');
            $characterGuid = (int)($_POST['delete_character_guid'] ?? 0);
            if ($characterGuid <= 0) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&chardelete=missing', 1);
                exit;
            }

            $selectedCharsPdo = spp_get_pdo('chars', $selectedRealmId);
            $stmtChar = $selectedCharsPdo->prepare("SELECT guid, name FROM characters WHERE guid=? AND account=? LIMIT 1");
            $stmtChar->execute([$characterGuid, $accountId]);
            $charRow = $stmtChar->fetch(PDO::FETCH_ASSOC);
            if (!$charRow) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&chardelete=missing', 1);
                exit;
            }

            try {
                $selectedCharsPdo->beginTransaction();
                $membersPdo->beginTransaction();
                foreach (spp_admin_character_delete_tables() as $table => $column) {
                    if (function_exists('spp_admin_members_table_exists') && !spp_admin_members_table_exists($selectedCharsPdo, $table)) {
                        continue;
                    }
                    $stmtDelete = $selectedCharsPdo->prepare("DELETE FROM `$table` WHERE `$column` = ?");
                    $stmtDelete->execute([$characterGuid]);
                }
                $identity = function_exists('spp_get_char_identity') ? spp_get_char_identity($selectedRealmId, $characterGuid) : null;
                if (!empty($identity['identity_id'])) {
                    $stmtIdentity = $membersPdo->prepare("UPDATE website_identities SET is_active = 0, updated_at = NOW() WHERE identity_id = ? LIMIT 1");
                    $stmtIdentity->execute([(int)$identity['identity_id']]);
                }
                $stmtWebsiteSource = $membersPdo->prepare("
                    UPDATE website_accounts
                    SET character_id = CASE WHEN character_id = ? THEN NULL ELSE character_id END,
                        character_name = CASE WHEN character_id = ? THEN NULL ELSE character_name END
                    WHERE account_id = ?
                ");
                $stmtWebsiteSource->execute([$characterGuid, $characterGuid, $accountId]);
                $selectedCharsPdo->commit();
                $membersPdo->commit();
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&character_realm_id=' . $selectedRealmId . '&chardelete=success', 1);
                exit;
            } catch (Throwable $e) {
                if ($selectedCharsPdo->inTransaction()) {
                    $selectedCharsPdo->rollBack();
                }
                if ($membersPdo->inTransaction()) {
                    $membersPdo->rollBack();
                }
                error_log('[admin.members] Character delete failed: ' . $e->getMessage());
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&character_realm_id=' . $selectedRealmId . '&chardelete=failed', 1);
                exit;
            }
        }

        if ($action === 'dodeleteacc') {
            spp_require_csrf('admin_members');
            $deleteRealmId = spp_resolve_realm_id($realmDbMap);
            if (function_exists('spp_deactivate_account_identities')) {
                spp_deactivate_account_identities($deleteRealmId, $accountId);
            }
            $stmt = $membersPdo->prepare("DELETE FROM account WHERE id=? LIMIT 1");
            $stmt->execute([$accountId]);
            $stmt = $membersPdo->prepare("DELETE FROM website_accounts WHERE account_id=? LIMIT 1");
            $stmt->execute([$accountId]);
            $stmt = $membersPdo->prepare("DELETE FROM pms WHERE owner_id=? LIMIT 1");
            $stmt->execute([$accountId]);
            redirect('index.php?n=admin&sub=members', 1);
            exit;
        }

        return false;
    }
}
