<?php

if (!function_exists('spp_db_table_exists')) {
    function spp_db_table_exists(PDO $pdo, string $tableName): bool
    {
        static $cache = array();

        $key = spl_object_hash($pdo) . ':' . $tableName;
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $stmt = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
        $stmt->execute(array($tableName));

        return $cache[$key] = (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('spp_db_column_exists')) {
    function spp_db_column_exists(PDO $pdo, string $tableName, string $columnName): bool
    {
        static $cache = array();

        $key = spl_object_hash($pdo) . ':' . $tableName . ':' . $columnName;
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $stmt = $pdo->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
        $stmt->execute(array($tableName, $columnName));

        return $cache[$key] = (bool)$stmt->fetchColumn();
    }
}
