<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bans-lib.php';

try {
    $search = isset($_GET['search']) ? (string) $_GET['search'] : (isset($_GET['q']) ? (string) $_GET['q'] : '');
    $search = trim(preg_replace('/[^A-Za-z0-9_\- .]/', '', $search) ?? '');
    $search = substr($search, 0, 32);

    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
    $page = max(1, min(5000, $page));

    $data = fetch_litebans_bans_page($search, $page);
    $config = mineacle_config();
    mineacle_json_response([
        'success' => true,
        'bans' => $data['bans'],
        'pagination' => $data['pagination'],
        'cached' => (bool) ($data['cached'] ?? false),
    ], 200, min(5, (int) ($config['cache']['bans_ttl'] ?? 8)));
} catch (Throwable $e) {
    error_log('[Mineacle Bans API] ' . $e->getMessage());
    $config = mineacle_config();
    $payload = [
        'success' => false,
        'error' => 'Unable to load bans right now',
        'bans' => [],
        'pagination' => [
            'page' => 1,
            'limit' => (int) ($config['page']['limit'] ?? 25),
            'has_next' => false,
            'has_previous' => false,
            'next_page' => null,
            'previous_page' => null,
            'total' => null,
            'pages' => null,
        ],
    ];
    if ((bool) ($config['security']['debug'] ?? false)) {
        $payload['debug'] = $e->getMessage();
    }
    mineacle_json_response($payload, 500, 0);
}
