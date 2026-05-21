<?php

function app_config(): array
{
    $configPath = __DIR__ . '/config.php';
    if (!is_file($configPath)) {
        http_response_code(500);
        echo 'Missing config.php (copy config.sample.php to config.php)';
        exit;
    }
    /** @var array $cfg */
    $cfg = require $configPath;
    return $cfg;
}

function is_valid_email(string $email): bool
{
    $email = trim($email);
    if ($email === '' || strlen($email) > 254) {
        return false;
    }
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function db_create_schema(PDO $pdo, string $dbName): void
{
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `recipients` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(254) NOT NULL,
            `is_opt_in` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = app_config()['db'] ?? null;
    if (!is_array($cfg)) {
        http_response_code(500);
        echo 'Invalid db config';
        exit;
    }

    $host = (string)($cfg['host'] ?? '127.0.0.1');
    $port = (int)($cfg['port'] ?? 3306);
    $dbName = (string)($cfg['dbname'] ?? 'mailtest');
    $user = (string)($cfg['user'] ?? '');
    $pass = (string)($cfg['pass'] ?? '');
    $charset = (string)($cfg['charset'] ?? 'utf8mb4');

    $serverDsn = "mysql:host={$host};port={$port};charset={$charset}";
    $dbDsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dbDsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        // 1049 Unknown database: 自動建立 schema
        if ((int)($e->errorInfo[1] ?? 0) !== 1049) {
            throw $e;
        }
        $pdo = new PDO($serverDsn, $user, $pass, $options);
        db_create_schema($pdo, $dbName);
        return $pdo;
    }
}

