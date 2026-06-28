<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function mineacle_cache_enabled(): bool {
    return (bool) ((mineacle_config()['cache']['enabled'] ?? true) === true);
}

function mineacle_cache_dir(): string {
    $dir = dirname(__DIR__) . '/storage/cache';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return $dir;
}

function mineacle_cache_file(string $key): string {
    return mineacle_cache_dir() . '/' . hash('sha256', $key) . '.json';
}

function mineacle_cache_get(string $key, int $ttl): ?array {
    if (!mineacle_cache_enabled() || $ttl <= 0) return null;
    $file = mineacle_cache_file($key);
    if (!is_file($file) || (time() - filemtime($file)) > $ttl) return null;
    $raw = @file_get_contents($file);
    if (!is_string($raw) || $raw === '') return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function mineacle_cache_set(string $key, array $payload): void {
    if (!mineacle_cache_enabled()) return;
    $file = mineacle_cache_file($key);
    @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function mineacle_json(array $payload, int $status = 200, int $publicMaxAge = 0): void {
    http_response_code($status);
    mineacle_security_headers(true);
    header('Cache-Control: ' . ($publicMaxAge > 0 ? 'public, max-age=' . $publicMaxAge : 'no-store, no-cache, must-revalidate, max-age=0'));
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}
