<?php
declare(strict_types=1);

function mineacle_security_headers(bool $json = false): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');

    $csp = "default-src 'self'; "
        . "base-uri 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'none'; "
        . "object-src 'none'; "
        . "script-src 'self'; "
        . "style-src 'self'; "
        . "img-src 'self' https://mc-heads.net data:; "
        . "connect-src 'self'; "
        . "upgrade-insecure-requests";

    header('Content-Security-Policy: ' . $csp);

    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

function mineacle_config(): array {
    static $config = null;

    if (is_array($config)) {
        return $config;
    }

    $path = __DIR__ . '/config.php';
    if (!is_file($path)) {
        throw new RuntimeException('Missing includes/config.php');
    }

    $config = require $path;

    if (!is_array($config)) {
        throw new RuntimeException('Invalid includes/config.php');
    }

    return $config;
}

function mineacle_required_env_value(?string $value, string $name): string {
    $value = trim((string) $value);
    if ($value === '') {
        throw new RuntimeException("Missing required environment variable: {$name}");
    }
    return $value;
}

function mineacle_pdo(): PDO {
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = mineacle_config();
    $db = $config['mysql'];

    $host = mineacle_required_env_value($db['host'] ?? null, 'DB_HOST');
    $database = mineacle_required_env_value($db['database'] ?? null, 'DB_NAME');
    $username = mineacle_required_env_value($db['username'] ?? null, 'DB_USERNAME');
    $password = mineacle_required_env_value($db['password'] ?? null, 'DB_PASSWORD');
    $port = (int) ($db['port'] ?? 3306);
    $charset = $db['charset'] ?? 'utf8mb4';

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $host,
        $port,
        $database,
        $charset
    );

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function h(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function quote_ident(string $identifier): string {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
        throw new InvalidArgumentException('Unsafe SQL identifier');
    }
    return '`' . $identifier . '`';
}

function col(array $config, string $group, string $name): string {
    return $config['litebans'][$group][$name];
}

function table_name(array $config, string $name): string {
    return $config['litebans'][$name];
}
