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

function mineacle_status_varint(int $value): string
{
    $bytes = '';

    do {
        $byte = $value & 0x7F;
        $value >>= 7;

        if ($value !== 0) {
            $byte |= 0x80;
        }

        $bytes .= chr($byte);
    } while ($value !== 0);

    return $bytes;
}

function mineacle_status_read_exact($socket, int $length): ?string
{
    $data = '';

    while (strlen($data) < $length && !feof($socket)) {
        $chunk = fread($socket, $length - strlen($data));

        if ($chunk === false || $chunk === '') {
            return null;
        }

        $data .= $chunk;
    }

    return strlen($data) === $length ? $data : null;
}

function mineacle_status_read_socket_varint($socket): ?int
{
    $value = 0;
    $shift = 0;

    for ($i = 0; $i < 5; $i++) {
        $byte = fread($socket, 1);

        if ($byte === false || $byte === '') {
            return null;
        }

        $current = ord($byte);
        $value |= ($current & 0x7F) << $shift;

        if (($current & 0x80) === 0) {
            return $value;
        }

        $shift += 7;
    }

    return null;
}

function mineacle_status_read_buffer_varint(string $buffer, int &$offset): ?int
{
    $value = 0;
    $shift = 0;
    $length = strlen($buffer);

    for ($i = 0; $i < 5; $i++) {
        if ($offset >= $length) {
            return null;
        }

        $current = ord($buffer[$offset]);
        $offset++;
        $value |= ($current & 0x7F) << $shift;

        if (($current & 0x80) === 0) {
            return $value;
        }

        $shift += 7;
    }

    return null;
}

function mineacle_status_server_parts(string $serverIp): array
{
    $host = preg_replace('/^minecraft:\/\//i', '', trim($serverIp));
    $port = 25565;

    if (preg_match('/^([^:]+):(\d+)$/', $host, $matches)) {
        $host = $matches[1];
        $port = max(1, min(65535, (int) $matches[2]));
    } elseif (function_exists('dns_get_record')) {
        $records = @dns_get_record('_minecraft._tcp.' . $host, DNS_SRV);

        if (is_array($records) && isset($records[0]['target'], $records[0]['port'])) {
            $host = rtrim((string) $records[0]['target'], '.');
            $port = max(1, min(65535, (int) $records[0]['port']));
        }
    }

    return [$host, $port];
}

function mineacle_status_direct_ping(string $serverIp): ?array
{
    [$host, $port] = mineacle_status_server_parts($serverIp);
    $socket = @fsockopen($host, $port, $errno, $error, 2.0);

    if (!$socket) {
        return null;
    }

    stream_set_timeout($socket, 2);

    $handshake = mineacle_status_varint(0)
        . mineacle_status_varint(767)
        . mineacle_status_varint(strlen($host))
        . $host
        . pack('n', $port)
        . mineacle_status_varint(1);

    fwrite($socket, mineacle_status_varint(strlen($handshake)) . $handshake);
    fwrite($socket, mineacle_status_varint(1) . mineacle_status_varint(0));

    $packetLength = mineacle_status_read_socket_varint($socket);

    if ($packetLength === null || $packetLength <= 0) {
        fclose($socket);
        return null;
    }

    $packet = mineacle_status_read_exact($socket, $packetLength);
    fclose($socket);

    if ($packet === null) {
        return null;
    }

    $offset = 0;
    $packetId = mineacle_status_read_buffer_varint($packet, $offset);
    $jsonLength = mineacle_status_read_buffer_varint($packet, $offset);

    if ($packetId !== 0 || $jsonLength === null || $jsonLength <= 0) {
        return null;
    }

    $json = substr($packet, $offset, $jsonLength);
    $data = json_decode($json, true);

    return is_array($data) ? $data : null;
}

function mineacle_status_normalize(array $data, string $source): array
{
    $players = is_array($data['players'] ?? null) ? $data['players'] : [];

    return [
        'online' => $source === 'direct' ? true : (bool) ($data['online'] ?? false),
        'players_online' => mineacle_status_number($players['online'] ?? $data['players_online'] ?? $data['online_players'] ?? null),
        'players_max' => mineacle_status_number($players['max'] ?? $data['players_max'] ?? $data['max_players'] ?? null),
        'source' => $source,
    ];
}

$directData = mineacle_status_direct_ping($serverIp);

if ($directData) {
    $direct = mineacle_status_normalize($directData, 'direct');
    $payload['online'] = $direct['online'];
    $payload['players_online'] = $direct['players_online'];
    $payload['players_max'] = $direct['players_max'];
    $payload['source'] = $direct['source'];
    $payload['checked'] = true;

    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
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
