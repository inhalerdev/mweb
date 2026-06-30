<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

function mineacle_discord_json(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

$config = mineacle_config();
$inviteCode = trim((string) ($config['site']['discord_invite_code'] ?? ''));

if ($inviteCode === '') {
    mineacle_discord_json([
        'success' => false,
        'message' => 'Discord invite code is not configured',
    ], 500);
}

$cacheFile = sys_get_temp_dir() . '/mineacle_discord_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $inviteCode) . '.json';
$cacheTtl = 90;

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
    $cached = file_get_contents($cacheFile);
    if (is_string($cached) && $cached !== '') {
        echo $cached;
        exit;
    }
}

$url = 'https://discord.com/api/v10/invites/' . rawurlencode($inviteCode) . '?with_counts=true&with_expiration=true';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 4,
        'header' => implode("\r\n", [
            'Accept: application/json',
            'User-Agent: MineacleWebsite/1.0',
        ]),
    ],
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    mineacle_discord_json([
        'success' => false,
        'message' => 'Discord count unavailable',
    ], 502);
}

$data = json_decode($response, true);

if (!is_array($data)) {
    mineacle_discord_json([
        'success' => false,
        'message' => 'Discord returned invalid data',
    ], 502);
}

$memberCount = isset($data['approximate_member_count']) ? (int) $data['approximate_member_count'] : null;
$onlineCount = isset($data['approximate_presence_count']) ? (int) $data['approximate_presence_count'] : null;
$guildName = isset($data['guild']['name']) ? (string) $data['guild']['name'] : 'Mineacle Network';

if ($memberCount === null || $memberCount < 0) {
    mineacle_discord_json([
        'success' => false,
        'message' => 'Discord member count unavailable',
    ], 502);
}

$payload = [
    'success' => true,
    'member_count' => $memberCount,
    'online_count' => $onlineCount,
    'guild_name' => $guildName,
    'invite_code' => $inviteCode,
    'updated_at' => time(),
];

$json = json_encode($payload, JSON_UNESCAPED_SLASHES);

if ($json === false) {
    mineacle_discord_json([
        'success' => false,
        'message' => 'Discord response encode failed',
    ], 500);
}

@file_put_contents($cacheFile, $json);
echo $json;
