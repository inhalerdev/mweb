<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bans-lib.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $search = trim((string) ($_GET['search'] ?? $_GET['q'] ?? ''));

    if (strlen($search) > 32) {
        $search = substr($search, 0, 32);
    }

    $id = max(0, (int) ($_GET['id'] ?? $_GET['ban_id'] ?? 0));
    if ($id > 0) {
        $detail = fetch_litebans_ban_detail($id);

        if ($detail === null) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Ban record not found',
            ], JSON_UNESCAPED_SLASHES);
            exit;
        }

        echo json_encode([
            'success' => true,
            'detail' => $detail,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $scope = strtolower(trim((string) ($_GET['scope'] ?? 'all')));
    if (!in_array($scope, ['all', 'active'], true)) {
        $scope = 'all';
    }

    $payload = fetch_litebans_bans_page($search, $page, $scope);

    echo json_encode([
        'success' => true,
        'bans' => $payload['bans'],
        'stats' => $payload['stats'] ?? [],
        'pagination' => $payload['pagination'],
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[MineacleBans] ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => 'Unable to load bans right now',
    ], JSON_UNESCAPED_SLASHES);
}
