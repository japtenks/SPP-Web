<?php
if (!defined('INCLUDED') || INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/marketplace-page.php';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=UTF-8');

$context = spp_marketplace_context($realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null));
$action = strtolower(trim((string)($_GET['action'] ?? '')));

if ($action === 'profession') {
    $skillId = isset($_GET['skill']) ? (int)$_GET['skill'] : 0;
    if ($skillId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing profession skill.']);
        exit;
    }

    $detailSnapshot = spp_marketplace_fetch_profession_detail($context, $skillId);
    $detail = is_array($detailSnapshot['value']) ? $detailSnapshot['value'] : [];
    echo json_encode([
        'realmId' => (int)$context['realmId'],
        'tierOrder' => $context['tierOrder'],
        'profession' => $detail,
        'cacheMeta' => $detailSnapshot['meta'] ?? null,
        'error' => (string)($detailSnapshot['pageError'] ?? ''),
    ]);
    exit;
}

if ($action === 'search') {
    $query = trim((string)($_GET['q'] ?? ''));
    $results = spp_marketplace_search_results($context, $query);
    echo json_encode([
        'realmId' => (int)$context['realmId'],
        'results' => $results,
        'error' => '',
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown marketplace action.']);
exit;
