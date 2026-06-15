<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bans-lib.php';

mineacle_security_headers(true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $search = trim((string) ($_GET['search'] ?? ''));
    if (mb_strlen($search) > 32) {
        $search = mb_substr($search, 0, 32);
    }

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $payload = fetch_litebans_bans_page($search, $page);

    echo json_encode([
        'success' => true,
        'bans' => $payload['bans'],
        'pagination' => $payload['pagination'],
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[MineacleBans] ' . $e->getMessage());

    $config = [];
    try {
        $config = mineacle_config();
    } catch (Throwable) {
    }

    $debug = (bool) (($config['security']['debug'] ?? false) === true);

    echo json_encode([
        'success' => false,
        'error' => 'Unable to load bans right now',
        'debug' => $debug ? $e->getMessage() : null,
    ], JSON_UNESCAPED_SLASHES);
}
