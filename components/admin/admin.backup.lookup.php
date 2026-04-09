<?php
session_start();

define('INCLUDED', true);

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../../config/config-protected.php');

require_once(__DIR__ . '/../../core/common.php');
require_once(__DIR__ . '/../../core/request.php');
require_once(__DIR__ . '/../../core/security.php');
require_once(__DIR__ . '/../../core/mangos.class.php');
require_once(__DIR__ . '/admin.backup.helpers.php');
require_once(__DIR__ . '/admin.backup.read.php');

$user = spp_admin_backup_current_admin_user();
$isAdmin = !empty($user['g_is_admin']) || !empty($user['g_is_supadmin']);

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(array('error' => 'forbidden'));
    exit;
}

$request = array_merge($_GET, $_POST);
$view = spp_admin_backup_build_view($realmDbMap, $request);

echo json_encode(array(
    'realm_options' => array_values((array)$view['realm_options']),
    'xfer_route_options' => array_values((array)$view['xfer_route_options']),
    'xfer_entity_options' => (array)($view['xfer_entity_options'] ?? array()),
    'source_account_options' => array_values((array)$view['source_account_options']),
    'source_bot_options' => array_values((array)($view['source_bot_options'] ?? array())),
    'source_character_options' => array_values((array)$view['source_character_options']),
    'source_bot_character_options' => array_values((array)($view['source_bot_character_options'] ?? array())),
    'source_guild_options' => array_values((array)$view['source_guild_options']),
    'target_account_options' => array_values((array)$view['target_account_options']),
    'selected_account_id' => (int)$view['selected_account_id'],
    'selected_bot_account_id' => (int)($view['selected_bot_account_id'] ?? 0),
    'selected_character_guid' => (int)$view['selected_character_guid'],
    'selected_guild_id' => (int)$view['selected_guild_id'],
    'selected_target_account_id' => (int)$view['selected_target_account_id'],
    'selected_xfer_route_id' => (string)$view['selected_xfer_route_id'],
    'selected_xfer_entity_type' => (string)($view['selected_xfer_entity_type'] ?? $view['xfer_entity_type'] ?? 'character'),
    'target_realm_id' => (int)$view['target_realm_id'],
    'source_realm_id' => (int)$view['source_realm_id'],
    'xfer_route_help' => (string)($view['xfer_route_help'] ?? ''),
    'source_character_count' => count((array)($view['source_character_options'] ?? array())),
    'source_bot_character_count' => count((array)($view['source_bot_character_options'] ?? array())),
    'selected_account_username' => (string)(($view['selected_account_row']['username'] ?? '') ?: ''),
    'selected_bot_username' => (string)(($view['selected_bot_account_row']['username'] ?? '') ?: ''),
    'selected_guild_summary' => (array)($view['selected_guild_summary'] ?? array()),
));
