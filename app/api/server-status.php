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

$url = 'https://api.mcsrvstat.us/3/' . rawurlencode($serverIp);
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 3,
        'header' => "Accept: application/json\r\nUser-Agent: Mineacle-Web/1.0\r\n",
    ],
]);

$response = @file_get_contents($url, false, $context);

if (is_string($response) && $response !== '') {
    $data = json_decode($response, true);

    if (is_array($data)) {
        $players = is_array($data['players'] ?? null) ? $data['players'] : [];
        $payload['online'] = (bool) ($data['online'] ?? false);
        $payload['players_online'] = max(0, (int) ($players['online'] ?? 0));
        $payload['players_max'] = max(0, (int) ($players['max'] ?? 0));
        $payload['checked'] = true;
    }
}

echo json_encode($payload, JSON_UNESCAPED_SLASHES);
