<?php

require_once __DIR__ . '/realm-capabilities.php';

if (!function_exists('spp_server_realmlist_public_choices')) {
    function spp_server_realmlist_public_choices(array $realmMap): array
    {
        $resolved = function_exists('spp_public_realm_choices')
            ? (array)spp_public_realm_choices($realmMap)
            : array();

        return (array)($resolved['choices'] ?? array());
    }
}

if (!function_exists('spp_server_realmlist_choice')) {
    function spp_server_realmlist_choice(array $realmMap, int $requestedChoiceId = 0): ?array
    {
        if (function_exists('spp_public_realm_choice')) {
            return spp_public_realm_choice($realmMap, $requestedChoiceId);
        }

        $choices = spp_server_realmlist_public_choices($realmMap);
        if ($requestedChoiceId > 0 && isset($choices[$requestedChoiceId])) {
            return $choices[$requestedChoiceId];
        }

        $firstChoice = reset($choices);
        return is_array($firstChoice) ? $firstChoice : null;
    }
}

if (!function_exists('spp_server_realmlist_enabled_realm_map')) {
    function spp_server_realmlist_enabled_realm_map(array $realmMap): array
    {
        if (function_exists('spp_public_realm_enabled_runtime_map')) {
            return spp_public_realm_enabled_runtime_map($realmMap);
        }

        return $realmMap;
    }
}

if (!function_exists('spp_server_realmlist_download_options')) {
    function spp_server_realmlist_download_options(array $realmMap, ?int $selectedChoiceId = null): array
    {
        $options = array();
        $choices = spp_server_realmlist_public_choices($realmMap);
        $choiceIds = array_keys($choices);
        sort($choiceIds, SORT_NUMERIC);

        foreach ($choiceIds as $choiceId) {
            $choiceId = (int)$choiceId;
            $choice = (array)($choices[$choiceId] ?? array());
            if ($choiceId <= 0 || empty($choice)) {
                continue;
            }

            $filenameId = max(1, $choiceId);
            $options[] = array(
                'realm_id' => $choiceId,
                'public_choice_id' => $choiceId,
                'realm_name' => (string)($choice['label'] ?? ('Realm ' . $choiceId)),
                'host' => (string)($choice['host'] ?? ''),
                'href' => 'index.php?n=server&sub=realmlist&nobody=1&realm=' . $choiceId,
                'filename' => $filenameId === 1 ? 'realmlist.wtf' : ('realmlist-' . $filenameId . '.wtf'),
                'is_selected' => $selectedChoiceId !== null && $choiceId === $selectedChoiceId,
                'is_download_available' => !empty($choice['is_download_available']),
                'metadata_state' => (string)($choice['metadata_state'] ?? 'incomplete'),
                'missing_reasons' => (array)($choice['missing_reasons'] ?? array()),
            );
        }

        return $options;
    }
}

if (!function_exists('spp_server_realmlist_endpoint_state')) {
    function spp_server_realmlist_endpoint_state(array $args = array()): array
    {
        $query = is_array($args['query'] ?? null) ? $args['query'] : $_GET;
        $realmMap = spp_server_realmlist_enabled_realm_map((array)($GLOBALS['realmDbMap'] ?? array()));
        $requestedChoiceId = isset($query['realm']) ? (int)$query['realm'] : 0;
        $choice = spp_server_realmlist_choice($realmMap, $requestedChoiceId);
        $choiceId = (int)($choice['public_choice_id'] ?? 0);
        $realmCapabilities = !empty($choice['source_slot_id'])
            ? spp_realm_capabilities($realmMap, (int)$choice['source_slot_id'])
            : array();
        $filenameId = max(1, $choiceId > 0 ? $choiceId : 1);
        $filename = ($filenameId === 1) ? 'realmlist.wtf' : ('realmlist-' . $filenameId . '.wtf');
        $host = trim((string)($choice['host'] ?? ''));
        $realmLabel = trim((string)($choice['label'] ?? ''));
        if ($realmLabel !== '') {
            $realmLabel = str_ireplace(
                array('Tbc', 'Wotlk', 'Vmangos'),
                array('TBC', 'WotLK', 'vMaNGOS'),
                $realmLabel
            );
        }
        $headerComment = '# ' . ($realmLabel !== '' ? $realmLabel : 'SPP-Web');
        $isDownloadAvailable = $host !== '';
        $statusCode = $isDownloadAvailable ? 200 : 409;
        $body = $isDownloadAvailable
            ? $headerComment . "\r\n" . 'set realmlist ' . $host . "\r\n"
            : $headerComment . "\r\n" . '# realmlist download unavailable: missing authority host' . "\r\n";

        return array(
            'realmId' => $choiceId,
            'publicChoice' => $choice,
            'filename' => $filename,
            'contentType' => 'text/plain; charset=UTF-8',
            'contentDisposition' => 'attachment; filename="' . $filename . '"',
            'body' => $body,
            'statusCode' => $statusCode,
            'isDownloadAvailable' => $isDownloadAvailable,
            'realmCapabilities' => $realmCapabilities,
        );
    }
}

if (!function_exists('spp_server_emit_realmlist_endpoint')) {
    function spp_server_emit_realmlist_endpoint(array $args = array()): void
    {
        $state = spp_server_realmlist_endpoint_state($args);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code((int)($state['statusCode'] ?? 200));
        header('Content-Type: ' . $state['contentType']);
        header('Content-Disposition: ' . $state['contentDisposition']);

        echo $state['body'];
        exit;
    }
}
