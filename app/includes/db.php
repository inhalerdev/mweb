<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mineacle_security_headers(string $type = 'html'): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

    if ($type === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Vary: Accept-Encoding');
        return;
    }

    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://crafatar.com https://minotar.net https://mc-heads.net; connect-src 'self' https://discord.com https://discordapp.com; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
}

function mineacle_json_response(array $payload, int $status = 200, int $maxAge = 0): never
{
    mineacle_security_headers('json');
    http_response_code($status);

    if ($maxAge > 0) {
        header('Cache-Control: public, max-age=' . $maxAge . ', stale-while-revalidate=20');
    } else {
        header('Cache-Control: no-store, max-age=0');
    }

    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function mineacle_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = mineacle_config();
    $db = $config['mysql'];

    foreach (['host', 'database', 'username'] as $required) {
        if (empty($db[$required])) {
            throw new RuntimeException('Database configuration is incomplete');
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        (int) $db['port'],
        $db['database'],
        $db['charset'] ?: 'utf8mb4'
    );

    try {
        $pdo = new PDO($dsn, (string) $db['username'], (string) $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('Database connection failed', 0, $e);
    }

    return $pdo;
}
