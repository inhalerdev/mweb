<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function mineacle_table_name(string $name): string {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException('Invalid table name');
    }

    return $name;
}

function mineacle_column_exists(PDO $pdo, string $table, string $column): bool {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    $stmt = $pdo->prepare("SHOW COLUMNS FROM `" . mineacle_table_name($table) . "` LIKE :column");
    $stmt->execute(['column' => $column]);

    return (bool) $stmt->fetch();
}

function mineacle_first_existing_column(PDO $pdo, string $table, array $columns, ?string $fallback = null): string {
    foreach ($columns as $column) {
        if (is_string($column) && mineacle_column_exists($pdo, $table, $column)) {
            return $column;
        }
    }

    if ($fallback !== null) {
        return $fallback;
    }

    throw new RuntimeException('Missing required LiteBans column on ' . $table);
}

function mineacle_skin_url(string $uuidOrName): string {
    $value = trim($uuidOrName);
    if ($value === '') {
        $value = 'Steve';
    }

    return 'https://mc-heads.net/avatar/' . rawurlencode($value) . '/128';
}

function mineacle_format_date(mixed $millis): string {
    $time = (int) $millis;

    if ($time <= 0) {
        return 'Unknown';
    }

    $seconds = (int) floor($time / 1000);
    return date('M j, Y g:i A', $seconds);
}

function mineacle_format_duration(mixed $until, mixed $time): string {
    $until = (int) $until;
    $time = (int) $time;

    if ($until <= 0) {
        return 'Permanent';
    }

    $seconds = max(0, (int) floor(($until - $time) / 1000));

    if ($seconds <= 0) {
        return 'Expired';
    }

    $days = intdiv($seconds, 86400);
    if ($days > 0) {
        return $days . ' day' . ($days === 1 ? '' : 's');
    }

    $hours = intdiv($seconds, 3600);
    if ($hours > 0) {
        return $hours . ' hour' . ($hours === 1 ? '' : 's');
    }

    $minutes = max(1, intdiv($seconds, 60));
    return $minutes . ' minute' . ($minutes === 1 ? '' : 's');
}

function mineacle_public_username(PDO $pdo, string $historyTable, string $uuid, ?string $fallback = null): string {
    $fallback = trim((string) $fallback);

    if ($uuid === '') {
        return $fallback !== '' ? $fallback : '#offline#';
    }

    try {
        if (!mineacle_column_exists($pdo, $historyTable, 'uuid') || !mineacle_column_exists($pdo, $historyTable, 'name')) {
            return $fallback !== '' ? $fallback : '#offline#';
        }

        $dateColumn = mineacle_column_exists($pdo, $historyTable, 'date') ? 'date' : null;
        $order = $dateColumn ? " ORDER BY `$dateColumn` DESC" : "";

        $stmt = $pdo->prepare("SELECT `name` FROM `" . mineacle_table_name($historyTable) . "` WHERE `uuid` = :uuid$order LIMIT 1");
        $stmt->execute(['uuid' => $uuid]);
        $name = trim((string) ($stmt->fetchColumn() ?: ''));

        if ($name !== '') {
            return $name;
        }
    } catch (Throwable $ignored) {
        // Public page should still render even if history lookup fails.
    }

    return $fallback !== '' ? $fallback : '#offline#';
}

function fetch_litebans_bans_page(string $search = '', int $page = 1): array {
    $config = mineacle_config();
    $pdo = mineacle_db();

    $litebans = $config['litebans'] ?? [];
    $bansTable = mineacle_table_name((string) ($litebans['bans_table'] ?? 'litebans_bans'));
    $historyTable = mineacle_table_name((string) ($litebans['history_table'] ?? 'litebans_history'));

    // Validate table exists early with a clean exception.
    $tableStmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($bansTable));
    if (!$tableStmt->fetchColumn()) {
        throw new RuntimeException('LiteBans bans table not found: ' . $bansTable);
    }

    $idCol = mineacle_first_existing_column($pdo, $bansTable, ['id'], 'id');
    $uuidCol = mineacle_first_existing_column($pdo, $bansTable, ['uuid'], 'uuid');
    $reasonCol = mineacle_first_existing_column($pdo, $bansTable, ['reason'], 'reason');
    $timeCol = mineacle_first_existing_column($pdo, $bansTable, ['time'], 'time');
    $untilCol = mineacle_first_existing_column($pdo, $bansTable, ['until'], 'until');

    $activeCol = mineacle_first_existing_column($pdo, $bansTable, ['active', 'removed_by_uuid'], 'active');
    $ipbanCol = mineacle_first_existing_column($pdo, $bansTable, ['ipban', 'ipban_wildcard'], 'ipban');

    $ipCol = mineacle_column_exists($pdo, $bansTable, 'ip') ? 'ip' : null;
    $nameCol = null;
    foreach (['name', 'username', 'player_name'] as $candidate) {
        if (mineacle_column_exists($pdo, $bansTable, $candidate)) {
            $nameCol = $candidate;
            break;
        }
    }

    $limit = max(1, min(100, (int) ($config['page']['limit'] ?? 25)));
    $page = max(1, $page);
    $offset = ($page - 1) * $limit;
    $nowMillis = (int) floor(microtime(true) * 1000);

    $where = [];
    $params = [];

    if ($activeCol === 'active') {
        $where[] = "`$activeCol` = 1";
    } else {
        // Older fallback: removed_by_uuid is null/empty means active.
        $where[] = "(`$activeCol` IS NULL OR `$activeCol` = '')";
    }

    // Only current active punishments. Permanent is until <= 0, temporary is until future.
    $where[] = "(`$untilCol` <= 0 OR `$untilCol` > :now)";
    $params['now'] = $nowMillis;

    $search = trim($search);
    if ($search !== '') {
        $searchLike = '%' . $search . '%';

        if ($nameCol !== null) {
            $where[] = "(`$uuidCol` LIKE :search OR `$nameCol` LIKE :search)";
        } else {
            $where[] = "`$uuidCol` LIKE :search";
        }

        $params['search'] = $searchLike;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) FROM `$bansTable` $whereSql";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue(':' . $key, $value);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $select = [
        "`$idCol` AS id",
        "`$uuidCol` AS uuid",
        "`$reasonCol` AS reason",
        "`$timeCol` AS time",
        "`$untilCol` AS until_time",
        "`$activeCol` AS active_value",
        "`$ipbanCol` AS ipban",
    ];

    if ($ipCol !== null) {
        $select[] = "`$ipCol` AS ip";
    } else {
        $select[] = "NULL AS ip";
    }

    if ($nameCol !== null) {
        $select[] = "`$nameCol` AS name";
    } else {
        $select[] = "NULL AS name";
    }

    $sql = "SELECT " . implode(', ', $select) . " FROM `$bansTable` $whereSql ORDER BY `$timeCol` DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();
    $bans = [];

    foreach ($rows as $row) {
        $uuid = trim((string) ($row['uuid'] ?? ''));
        $fallbackName = trim((string) ($row['name'] ?? ''));
        $username = mineacle_public_username($pdo, $historyTable, $uuid, $fallbackName);

        $isIpBan = ((int) ($row['ipban'] ?? 0)) === 1;
        $until = (int) ($row['until_time'] ?? 0);
        $time = (int) ($row['time'] ?? 0);
        $permanent = $until <= 0;

        $status = $isIpBan || $permanent ? 'Permanently Banned' : 'Active';
        $type = $isIpBan ? 'IP Ban' : 'Ban';
        $duration = mineacle_format_duration($until, $time);

        $bans[] = [
            'id' => (string) ($row['id'] ?? ''),
            'uuid' => $uuid,
            'username' => $isIpBan && $username === '#offline#' ? '#offline#' : $username,
            'skin' => mineacle_skin_url($uuid !== '' ? $uuid : $username),
            'reason' => (string) ($row['reason'] ?? 'No reason provided'),
            'type' => $type,
            'duration' => $duration,
            'date' => mineacle_format_date($time),
            'status' => $status,
            'status_type' => $isIpBan ? 'ip' : 'active',
            'ipban' => $isIpBan,
            'appeal_id' => 'MCL-' . str_pad((string) ($row['id'] ?? '0'), 6, '0', STR_PAD_LEFT),
            'support_email' => (string) ($config['site']['support_email'] ?? 'support@mineacle.net'),
            'discord' => (string) ($config['site']['discord'] ?? 'https://discord.gg/4xrYFxdSWg'),
            'can_pay' => !$isIpBan,
            'price' => $permanent
                ? (string) ($config['payments']['permanent_unban_price'] ?? '$19.99')
                : (string) ($config['payments']['temporary_unban_price'] ?? '$9.99'),
            'unban_url' => strtr((string) ($config['site']['unban_checkout_url'] ?? '#'), [
                '{id}' => (string) ($row['id'] ?? ''),
                '{uuid}' => $uuid,
                '{username}' => $username,
            ]),
        ];
    }

    $totalPages = max(1, (int) ceil($total / $limit));

    return [
        'bans' => $bans,
        'pagination' => [
            'page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
        ],
    ];
}
