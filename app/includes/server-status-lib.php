<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cache.php';

function mineacle_read_varint($socket): ?int
{
    $numRead = 0;
    $result = 0;
    do {
        $byte = fread($socket, 1);
        if ($byte === false || $byte === '') {
            return null;
        }
        $value = ord($byte);
        $result |= ($value & 0x7F) << (7 * $numRead);
        $numRead++;
        if ($numRead > 5) {
            return null;
        }
    } while (($value & 0x80) !== 0);
    return $result;
}

function mineacle_write_varint(int $value): string
{
    $out = '';
    do {
        $temp = $value & 0x7F;
        $value >>= 7;
        if ($value !== 0) {
            $temp |= 0x80;
        }
        $out .= chr($temp);
    } while ($value !== 0);
    return $out;
}

function mineacle_packet_string(string $value): string
{
    return mineacle_write_varint(strlen($value)) . $value;
}

function mineacle_fetch_server_status_uncached(): array
{
    $config = mineacle_config();
    $host = (string) $config['site']['minecraft_host'];
    $port = (int) $config['site']['minecraft_port'];

    $socket = @fsockopen($host, $port, $errno, $errstr, 1.25);
    if (!$socket) {
        return ['online' => false, 'players' => 0, 'max_players' => 0, 'host' => $host];
    }

    stream_set_timeout($socket, 2);

    $payload = mineacle_write_varint(0x00)
        . mineacle_write_varint(760)
        . mineacle_packet_string($host)
        . pack('n', $port)
        . mineacle_write_varint(1);

    fwrite($socket, mineacle_write_varint(strlen($payload)) . $payload);
    fwrite($socket, mineacle_write_varint(1) . mineacle_write_varint(0x00));

    $length = mineacle_read_varint($socket);
    if ($length === null) {
        fclose($socket);
        return ['online' => false, 'players' => 0, 'max_players' => 0, 'host' => $host];
    }

    $packetId = mineacle_read_varint($socket);
    $jsonLength = mineacle_read_varint($socket);
    if ($packetId !== 0 || $jsonLength === null || $jsonLength <= 0) {
        fclose($socket);
        return ['online' => false, 'players' => 0, 'max_players' => 0, 'host' => $host];
    }

    $json = '';
    while (strlen($json) < $jsonLength && !feof($socket)) {
        $json .= fread($socket, $jsonLength - strlen($json));
    }
    fclose($socket);

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return ['online' => false, 'players' => 0, 'max_players' => 0, 'host' => $host];
    }

    return [
        'online' => true,
        'players' => (int) ($data['players']['online'] ?? 0),
        'max_players' => (int) ($data['players']['max'] ?? 0),
        'host' => $host,
    ];
}

function mineacle_fetch_server_status(): array
{
    $config = mineacle_config();
    $ttl = (int) ($config['cache']['server_status_ttl'] ?? 25);
    $key = mineacle_cache_key('server_status', [$config['site']['minecraft_host'], $config['site']['minecraft_port']]);
    $cached = mineacle_cache_get($key);
    if (is_array($cached)) {
        $cached['cached'] = true;
        return $cached;
    }

    $status = mineacle_fetch_server_status_uncached();
    $status['cached'] = false;
    mineacle_cache_set($key, $status, $ttl);
    return $status;
}
