<?php
declare(strict_types=1);


if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle): bool {
        $haystack = (string) $haystack;
        $needle = (string) $needle;
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle): bool {
        $haystack = (string) $haystack;
        $needle = (string) $needle;
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

function mineacle_config(): array {
    static $config = null;
    if ($config !== null) return $config;
    $path = __DIR__ . '/config.php';
    if (!is_file($path)) throw new RuntimeException('Missing includes/config.php');
    $config = require $path;
    if (!is_array($config)) throw new RuntimeException('includes/config.php must return an array');
    return $config;
}

function h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mineacle_security_headers(bool $api = false): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' https://mc-heads.net data:; connect-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; font-src 'self' data:; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    if ($api) header('Content-Type: application/json; charset=utf-8');
}

function mineacle_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $mysql = mineacle_config()['mysql'] ?? [];
    $host = trim((string) ($mysql['host'] ?? ''));
    $port = (int) ($mysql['port'] ?? 3306);
    $database = trim((string) ($mysql['database'] ?? ''));
    $username = trim((string) ($mysql['username'] ?? ''));
    $password = (string) ($mysql['password'] ?? '');
    $charset = trim((string) ($mysql['charset'] ?? 'utf8mb4'));

    if ($host === '' || $database === '' || $username === '' || $password === '') {
        throw new RuntimeException('Database configuration is incomplete');
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 4,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('Database connection failed', 0, $e);
    }
    return $pdo;
}
