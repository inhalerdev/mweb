<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function mineacle_cache_enabled(): bool
{
    $config = mineacle_config();
    return (bool) ($config['cache']['enabled'] ?? true);
}

function mineacle_cache_key(string $namespace, array $parts = []): string
{
    $raw = $namespace . ':' . json_encode($parts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return 'mineacle_' . $namespace . '_' . sha1($raw);
}

function mineacle_cache_dir(): string
{
    return dirname(__DIR__) . '/storage/cache';
}

function mineacle_cache_get(string $key): mixed
{
    if (!mineacle_cache_enabled()) {
        return null;
    }

    if (function_exists('apcu_fetch') && ini_get('apc.enabled')) {
        $success = false;
        $value = apcu_fetch($key, $success);
        if ($success) {
            return $value;
        }
    }

    $file = mineacle_cache_dir() . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key) . '.cache.php';
    if (!is_file($file)) {
        return null;
    }

    $data = @include $file;
    if (!is_array($data) || !isset($data['expires'])) {
        @unlink($file);
        return null;
    }

    if ((int) $data['expires'] < time()) {
        @unlink($file);
        return null;
    }

    return $data['value'] ?? null;
}

function mineacle_cache_set(string $key, mixed $value, int $ttl): void
{
    if (!mineacle_cache_enabled() || $ttl <= 0) {
        return;
    }

    if (function_exists('apcu_store') && ini_get('apc.enabled')) {
        @apcu_store($key, $value, $ttl);
    }

    $dir = mineacle_cache_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        return;
    }

    $file = $dir . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key) . '.cache.php';
    $payload = [
        'expires' => time() + $ttl,
        'value' => $value,
    ];

    $encoded = var_export($payload, true);
    @file_put_contents($file, "<?php\nreturn " . $encoded . ";\n", LOCK_EX);
}
