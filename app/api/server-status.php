<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/server-status-lib.php';

try {
    $config = mineacle_config();
    $status = mineacle_fetch_server_status();
    mineacle_json_response([
        'success' => true,
        'online' => (bool) ($status['online'] ?? false),
        'players' => (int) ($status['players'] ?? 0),
        'online_players' => (int) ($status['players'] ?? 0),
        'max_players' => (int) ($status['max_players'] ?? 0),
        'host' => (string) ($status['host'] ?? $config['site']['minecraft_host']),
        'cached' => (bool) ($status['cached'] ?? false),
    ], 200, min(15, (int) ($config['cache']['server_status_ttl'] ?? 25)));
} catch (Throwable $e) {
    error_log('[Mineacle Status API] ' . $e->getMessage());
    mineacle_json_response([
        'success' => false,
        'online' => false,
        'players' => 0,
        'online_players' => 0,
        'max_players' => 0,
        'error' => 'Unable to load server status right now',
    ], 200, 10);
}
