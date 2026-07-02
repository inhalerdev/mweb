<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

mineacle_security_headers();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

$site = mineacle_config()['site'] ?? [];
$serverIp = trim((string) ($site['minecraft_ip'] ?? 'mineacle.net'));

if ($serverIp === '') {
    $serverIp = 'mineacle.net';
}

$payload = [
    'online' => false,
    'players_online' => 0,
    'players_max' => 0,
    'server_ip' => $serverIp,
    'checked' => false,
];

function mineacle_status_number(mixed $value): int
{
    if (is_int($value)) {
        return max(0, $value);
    }

    if (is_float($value)) {
        return max(0, (int) round($value));
    }

    if (is_string($value) && is_numeric($value)) {
        return max(0, (int) $value);
    }

    return 0;
}

function mineacle_status_read_url(string $url): ?array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 3,
            'header' => "Accept: application/json\r\nUser-Agent: Mineacle-Web/1.0\r\n",
        ],
    ]);

    $response = @file_get_contents($url, false, $context);

    if (!is_string($response) || $response === '') {
        return null;
    }

    $data = json_decode($response, true);

    return is_array($data) ? $data : null;
}

function mineacle_status_normalize(array $data, string $source): array
{
    $players = is_array($data['players'] ?? null) ? $data['players'] : [];

    return [
        'online' => (bool) ($data['online'] ?? false),
        'players_online' => mineacle_status_number($players['online'] ?? $data['players_online'] ?? $data['online_players'] ?? null),
        'players_max' => mineacle_status_number($players['max'] ?? $data['players_max'] ?? $data['max_players'] ?? null),
        'source' => $source,
    ];
}

$providers = [
    'mcsrvstat' => 'https://api.mcsrvstat.us/3/' . rawurlencode($serverIp),
    'mcstatus' => 'https://api.mcstatus.io/v2/status/java/' . rawurlencode($serverIp),
];

foreach ($providers as $source => $url) {
    $data = mineacle_status_read_url($url);

    if (!$data) {
        continue;
    }

    $next = mineacle_status_normalize($data, $source);
    $payload['checked'] = true;

    if (!$payload['online'] || $next['players_online'] > $payload['players_online']) {
        $payload['online'] = $next['online'];
        $payload['players_online'] = $next['players_online'];
        $payload['players_max'] = $next['players_max'];
        $payload['source'] = $next['source'];
    }
}

echo json_encode($payload, JSON_UNESCAPED_SLASHES);
