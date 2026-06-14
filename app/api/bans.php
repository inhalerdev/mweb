<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bans-lib.php';

mineacle_security_headers(true);

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

    $payload = [
        'success' => false,
        'error' => 'Unable to load bans right now',
    ];

    try {
        $config = mineacle_config();
        if (!empty($config['security']['debug'])) {
            $payload['debug'] = $e->getMessage();
        }
    } catch (Throwable $ignored) {
        // Keep public error generic.
    }

    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
}
