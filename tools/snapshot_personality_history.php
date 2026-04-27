<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$siteRoot = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $siteRoot;

require_once $siteRoot . '/config/config-protected.php';

function personality_log(string $message): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}

function personality_parse_realms(array $argv): array
{
    foreach ($argv as $arg) {
        if (strpos($arg, '--realm=') !== 0) {
            continue;
        }

        $realmIds = array();
        foreach (preg_split('/[\s,;]+/', (string)substr($arg, 8)) as $piece) {
            $realmId = (int)$piece;
            if ($realmId > 0) {
                $realmIds[] = $realmId;
            }
        }

        return array_values(array_unique($realmIds));
    }

    return array();
}

function personality_arg_value(array $argv, string $prefix, ?string $default = null): ?string
{
    foreach ($argv as $arg) {
        if (strpos($arg, $prefix) === 0) {
            return (string)substr($arg, strlen($prefix));
        }
    }

    return $default;
}

function personality_table_exists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute(array($tableName));
    return (bool)$stmt->fetchColumn();
}

function personality_enabled_realm_map(): array
{
    if (function_exists('spp_public_realm_enabled_runtime_map')) {
        $map = (array)spp_public_realm_enabled_runtime_map((array)($GLOBALS['realmDbMap'] ?? array()));
        if (!empty($map)) {
            return $map;
        }
    }

    return (array)($GLOBALS['realmDbMap'] ?? array());
}

$dryRun = in_array('--dry-run', $argv, true);
$limit = max(0, (int)(personality_arg_value($argv, '--limit=', '0') ?? '0'));
$explicitBatch = trim((string)(personality_arg_value($argv, '--batch=', '') ?? ''));
$historyRealmId = max(1, (int)(personality_arg_value($argv, '--history-realm=', '1') ?? '1'));
$requestedRealms = personality_parse_realms($argv);
$enabledRealmMap = personality_enabled_realm_map();

if (empty($enabledRealmMap)) {
    fwrite(STDERR, "No realm configuration is available.\n");
    exit(1);
}

$targetRealmIds = !empty($requestedRealms)
    ? array_values(array_filter($requestedRealms, static function (int $realmId) use ($enabledRealmMap): bool {
        return isset($enabledRealmMap[$realmId]);
    }))
    : array_values(array_map('intval', array_keys($enabledRealmMap)));

if (empty($targetRealmIds)) {
    fwrite(STDERR, "No valid realms were selected.\n");
    exit(1);
}

$capturedAtUnix = time();
$capturedAt = date('Y-m-d H:i:s', $capturedAtUnix);
$snapshotBatch = $explicitBatch !== ''
    ? $explicitBatch
    : gmdate('YmdHis', $capturedAtUnix) . '-' . substr(bin2hex(random_bytes(4)), 0, 8);

$historyPdo = function_exists('spp_canonical_auth_pdo')
    ? spp_canonical_auth_pdo()
    : spp_get_pdo('realmd', $historyRealmId);

if (!personality_table_exists($historyPdo, 'website_personality_history')) {
    fwrite(
        STDERR,
        "Missing table `website_personality_history`. Apply db-updates/05_personality_history.sql to the website-owned realmd database first.\n"
    );
    exit(1);
}

$insertSql = "
    INSERT INTO website_personality_history (
        snapshot_batch, captured_at, captured_unix, realm_id, guid, character_name, level, online,
        affiliation, archetype, w_quest, w_grind, w_pvp, w_dungeon, w_farm,
        is_leader, leader_charisma, sociability, group_time_seconds, signatures_pledged,
        tz_offset_minutes, play_window_start, play_window_end, session_minutes, weekend_bonus, play_day_mask,
        last_drift_time, last_login_at, last_logout_at, first_eligible_login_at,
        is_tourist, tourist_session_count, tourist_sessions_used, is_dormant, pvp_lean, craft_for_guild, version
    ) VALUES (
        :snapshot_batch, :captured_at, :captured_unix, :realm_id, :guid, :character_name, :level, :online,
        :affiliation, :archetype, :w_quest, :w_grind, :w_pvp, :w_dungeon, :w_farm,
        :is_leader, :leader_charisma, :sociability, :group_time_seconds, :signatures_pledged,
        :tz_offset_minutes, :play_window_start, :play_window_end, :session_minutes, :weekend_bonus, :play_day_mask,
        :last_drift_time, :last_login_at, :last_logout_at, :first_eligible_login_at,
        :is_tourist, :tourist_session_count, :tourist_sessions_used, :is_dormant, :pvp_lean, :craft_for_guild, :version
    )
";
$insertStmt = $historyPdo->prepare($insertSql);

personality_log(
    ($dryRun ? '[dry-run] ' : '') .
    'Snapshot batch ' . $snapshotBatch .
    ' at ' . $capturedAt .
    ' for realms: ' . implode(', ', $targetRealmIds)
);

$totalInserted = 0;

foreach ($targetRealmIds as $realmId) {
    $charsPdo = spp_get_pdo('chars', $realmId);
    if (!personality_table_exists($charsPdo, 'ai_playerbot_personality')) {
        personality_log('Skipping realm ' . $realmId . ': ai_playerbot_personality not found.');
        continue;
    }

    $sql = "
        SELECT
            p.guid,
            c.name AS character_name,
            c.level,
            c.online,
            p.affiliation,
            p.archetype,
            p.w_quest,
            p.w_grind,
            p.w_pvp,
            p.w_dungeon,
            p.w_farm,
            p.is_leader,
            p.leader_charisma,
            p.sociability,
            p.group_time_seconds,
            p.signatures_pledged,
            p.tz_offset_minutes,
            p.play_window_start,
            p.play_window_end,
            p.session_minutes,
            p.weekend_bonus,
            p.play_day_mask,
            p.last_drift_time,
            p.last_login_at,
            p.last_logout_at,
            p.first_eligible_login_at,
            p.is_tourist,
            p.tourist_session_count,
            p.tourist_sessions_used,
            p.is_dormant,
            p.pvp_lean,
            p.craft_for_guild,
            p.version
        FROM ai_playerbot_personality p
        LEFT JOIN characters c ON c.guid = p.guid
        ORDER BY p.guid ASC
    ";
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int)$limit;
    }

    $rows = $charsPdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: array();
    personality_log('Realm ' . $realmId . ': fetched ' . count($rows) . ' personality rows.');
    $totalInserted += count($rows);

    if ($dryRun || empty($rows)) {
        continue;
    }

    $historyPdo->beginTransaction();
    try {
        foreach ($rows as $row) {
            $insertStmt->execute(array(
                'snapshot_batch' => $snapshotBatch,
                'captured_at' => $capturedAt,
                'captured_unix' => $capturedAtUnix,
                'realm_id' => $realmId,
                'guid' => (int)($row['guid'] ?? 0),
                'character_name' => $row['character_name'] !== null ? (string)$row['character_name'] : null,
                'level' => (int)($row['level'] ?? 0),
                'online' => !empty($row['online']) ? 1 : 0,
                'affiliation' => (string)($row['affiliation'] ?? 'SOLO'),
                'archetype' => (string)($row['archetype'] ?? 'CASUAL'),
                'w_quest' => (int)($row['w_quest'] ?? 0),
                'w_grind' => (int)($row['w_grind'] ?? 0),
                'w_pvp' => (int)($row['w_pvp'] ?? 0),
                'w_dungeon' => (int)($row['w_dungeon'] ?? 0),
                'w_farm' => (int)($row['w_farm'] ?? 0),
                'is_leader' => !empty($row['is_leader']) ? 1 : 0,
                'leader_charisma' => (int)($row['leader_charisma'] ?? 0),
                'sociability' => (int)($row['sociability'] ?? 0),
                'group_time_seconds' => (int)($row['group_time_seconds'] ?? 0),
                'signatures_pledged' => (int)($row['signatures_pledged'] ?? 0),
                'tz_offset_minutes' => (int)($row['tz_offset_minutes'] ?? 0),
                'play_window_start' => (int)($row['play_window_start'] ?? 0),
                'play_window_end' => (int)($row['play_window_end'] ?? 0),
                'session_minutes' => (int)($row['session_minutes'] ?? 0),
                'weekend_bonus' => (int)($row['weekend_bonus'] ?? 0),
                'play_day_mask' => (int)($row['play_day_mask'] ?? 0),
                'last_drift_time' => (int)($row['last_drift_time'] ?? 0),
                'last_login_at' => (int)($row['last_login_at'] ?? 0),
                'last_logout_at' => (int)($row['last_logout_at'] ?? 0),
                'first_eligible_login_at' => (int)($row['first_eligible_login_at'] ?? 0),
                'is_tourist' => !empty($row['is_tourist']) ? 1 : 0,
                'tourist_session_count' => (int)($row['tourist_session_count'] ?? 0),
                'tourist_sessions_used' => (int)($row['tourist_sessions_used'] ?? 0),
                'is_dormant' => !empty($row['is_dormant']) ? 1 : 0,
                'pvp_lean' => !empty($row['pvp_lean']) ? 1 : 0,
                'craft_for_guild' => !empty($row['craft_for_guild']) ? 1 : 0,
                'version' => (int)($row['version'] ?? 1),
            ));
        }
        $historyPdo->commit();
    } catch (Throwable $e) {
        $historyPdo->rollBack();
        throw $e;
    }

    personality_log('Realm ' . $realmId . ': inserted ' . count($rows) . ' history rows.');
}

personality_log(($dryRun ? '[dry-run] ' : '') . 'Done. Total rows ' . ($dryRun ? 'scanned' : 'inserted') . ': ' . $totalInserted);
