<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cache.php';

function mineacle_fetch_discord_uncached(): array
{
    $config = mineacle_config();
    $code = (string) ($config['site']['discord_invite_code'] ?? '');
    if ($code === '') {
        return ['available' => false, 'members' => 0, 'online' => 0];
    }

    $url = 'https://discord.com/api/v10/invites/' . rawurlencode($code) . '?with_counts=true';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 2,
            'header' => "User-Agent: Mineacle-Bans/1.0\r\nAccept: application/json\r\n",
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    if ($body === false || $body === '') {
        return ['available' => false, 'members' => 0, 'online' => 0];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['available' => false, 'members' => 0, 'online' => 0];
    }

    return [
        'available' => true,
        'members' => (int) ($data['approximate_member_count'] ?? 0),
        'online' => (int) ($data['approximate_presence_count'] ?? 0),
        'guild' => (string) ($data['guild']['name'] ?? 'Mineacle'),
    ];
}

try {
    $config = mineacle_config();
    $ttl = (int) ($config['cache']['discord_ttl'] ?? 300);
    $key = mineacle_cache_key('discord', [$config['site']['discord_invite_code'] ?? '']);
    $cached = mineacle_cache_get($key);

    if (is_array($cached)) {
        $cached['cached'] = true;
        mineacle_json_response(['success' => true] + $cached, 200, min(60, $ttl));
    }

    $data = mineacle_fetch_discord_uncached();
    $data['cached'] = false;
    mineacle_cache_set($key, $data, $ttl);
    mineacle_json_response(['success' => true] + $data, 200, min(60, $ttl));
} catch (Throwable $e) {
    error_log('[Mineacle Discord API] ' . $e->getMessage());
    mineacle_json_response([
        'success' => false,
        'available' => false,
        'members' => 0,
        'online' => 0,
    ], 200, 60);
}
