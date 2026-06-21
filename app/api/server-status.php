<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

ini_set('display_errors', '0');

$configPath = __DIR__ . '/../includes/config.php';
if (is_file($configPath)) {
    require_once $configPath;
}

function mineacle_status_json(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function mineacle_status_varint(int $value): string {
    $bytes = '';

    do {
        $temp = $value & 0x7F;
        $value >>= 7;

        if ($value !== 0) {
            $temp |= 0x80;
        }

        $bytes .= chr($temp);
    } while ($value !== 0);

    return $bytes;
}

function mineacle_status_read_varint($socket): ?int {
    $value = 0;
    $position = 0;

    while (true) {
        $byte = fread($socket, 1);

        if ($byte === false || $byte === '') {
            return null;
        }

        $current = ord($byte);
        $value |= ($current & 0x7F) << $position;

        if (($current & 0x80) !== 0x80) {
            break;
        }

        $position += 7;

        if ($position >= 35) {
            return null;
        }
    }

    return $value;
}

function mineacle_status_packet(string $payload): string {
    return mineacle_status_varint(strlen($payload)) . $payload;
}

function mineacle_status_string(string $value): string {
    return mineacle_status_varint(strlen($value)) . $value;
}

function mineacle_status_read_exact($socket, int $length): ?string {
    $buffer = '';

    while (strlen($buffer) < $length) {
        $chunk = fread($socket, $length - strlen($buffer));

        if ($chunk === false || $chunk === '') {
            return null;
        }

        $buffer .= $chunk;
    }

    return $buffer;
}

function mineacle_status_config(): array {
    if (function_exists('mineacle_config')) {
        $config = mineacle_config();

        if (is_array($config)) {
            return $config;
        }
    }

    return [];
}

function mineacle_status_parse_host(string $rawHost): array {
    $host = trim($rawHost);
    $port = 25565;

    if (preg_match('/^([^:]+):([0-9]{1,5})$/', $host, $matches) === 1) {
        $host = $matches[1];
        $port = max(1, min(65535, (int) $matches[2]));
    }

    return [$host, $port];
}

function mineacle_status_ping(string $connectHost, int $connectPort, string $publicHost, int $publicPort): array {
    $errno = 0;
    $error = '';
    $socket = @stream_socket_client(
        'tcp://' . $connectHost . ':' . $connectPort,
        $errno,
        $error,
        1.5,
        STREAM_CLIENT_CONNECT
    );

    if (!is_resource($socket)) {
        throw new RuntimeException('Unable to connect to Minecraft server');
    }

    stream_set_timeout($socket, 2);

    $handshake = mineacle_status_varint(0)
        . mineacle_status_varint(763)
        . mineacle_status_string($publicHost)
        . pack('n', $publicPort)
        . mineacle_status_varint(1);

    fwrite($socket, mineacle_status_packet($handshake));
    fwrite($socket, mineacle_status_packet(mineacle_status_varint(0)));

    $packetLength = mineacle_status_read_varint($socket);

    if ($packetLength === null) {
        fclose($socket);
        throw new RuntimeException('Invalid Minecraft status response');
    }

    $packetId = mineacle_status_read_varint($socket);

    if ($packetId !== 0) {
        fclose($socket);
        throw new RuntimeException('Unexpected Minecraft status packet');
    }

    $jsonLength = mineacle_status_read_varint($socket);

    if ($jsonLength === null || $jsonLength < 1) {
        fclose($socket);
        throw new RuntimeException('Missing Minecraft status payload');
    }

    $json = mineacle_status_read_exact($socket, $jsonLength);
    fclose($socket);

    if ($json === null) {
        throw new RuntimeException('Incomplete Minecraft status payload');
    }

    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
        throw new RuntimeException('Malformed Minecraft status payload');
    }

    $players = is_array($decoded['players'] ?? null) ? $decoded['players'] : [];

    return [
        'online' => true,
        'host' => $publicHost,
        'port' => $publicPort,
        'query_host' => $connectHost,
        'query_port' => $connectPort,
        'players_online' => (int) ($players['online'] ?? 0),
        'players_max' => (int) ($players['max'] ?? 0),
    ];
}

// Public navbar count must come from the live Minecraft status ping only.
// Display/copy IP stays mineacle.net, but the status socket queries the actual Lagless backend.
// Do not read LiteBans, stats, Discord, cached website data, DNS SRV records, or any database here.
$publicHost = 'mineacle.net';
$publicPort = 25565;
$queryHost = '59-GN03.DFW.lagless.gg';
$queryPort = 19136;

try {
    mineacle_status_json(mineacle_status_ping($queryHost, $queryPort, $publicHost, $publicPort));
} catch (Throwable $exception) {
    mineacle_status_json([
        'online' => false,
        'host' => $publicHost,
        'port' => $publicPort,
        'query_host' => $queryHost,
        'query_port' => $queryPort,
        'players_online' => 0,
        'players_max' => 0,
    ]);
}
