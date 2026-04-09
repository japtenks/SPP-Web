<?php
session_start();

define('INCLUDED', true);

require_once(__DIR__ . '/../../config/config-protected.php');

require_once(__DIR__ . '/../../core/common.php');
require_once(__DIR__ . '/../../core/request.php');
require_once(__DIR__ . '/../../core/security.php');
require_once(__DIR__ . '/../../core/mangos.class.php');
require_once(__DIR__ . '/admin.backup.helpers.php');

$user = spp_admin_backup_current_admin_user();
$isAdmin = !empty($user['g_is_admin']) || !empty($user['g_is_supadmin']);

if (!$isAdmin) {
    http_response_code(403);
    exit('Forbidden');
}

$file = spp_admin_backup_basename((string)($_GET['file'] ?? ''));
if ($file === '' || !preg_match('/\.(sql|txt|bat|vbs)$/i', $file)) {
    http_response_code(404);
    exit('File not found');
}

$path = spp_admin_backup_output_dir() . DIRECTORY_SEPARATOR . $file;
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('File not found');
}

$extension = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
$contentType = 'text/plain; charset=utf-8';
if ($extension === 'sql') {
    $contentType = 'application/sql; charset=utf-8';
}

header('Content-Type: ' . $contentType);
header('Content-Length: ' . (string)filesize($path));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $file) . '"');
readfile($path);
exit;
