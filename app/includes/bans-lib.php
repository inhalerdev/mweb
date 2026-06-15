<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function mineacle_table(string $name): string {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException('Invalid table name');
    }

    return $name;
}

function mineacle_skin_url(string $value): string {
    $value = trim($value);
    if ($value === '' || str_starts_with($value, '#')) {
        $value = 'Steve';
    }

    return 'https://mc-heads.net/avatar/' . rawurlencode($value) . '/128';
}

function mineacle_epoch_seconds(mixed $value): int {
    $value = (int) $value;

    if ($value <= 0) {
        return 0;
    }

    return $value > 100000000000 ? (int) floor($value / 1000) : $value;
}

function mineacle_format_date(mixed $value): string {
    $seconds = mineacle_epoch_seconds($value);

    if ($seconds <= 0) {
        return 'Unknown';
    }

    return date('M j, Y g:i A', $seconds);
}

function mineacle_format_duration(mixed $until, mixed $time): string {
    $untilSeconds = mineacle_epoch_seconds($until);
    $timeSeconds = mineacle_epoch_seconds($time);

    if ($untilSeconds <= 0) {
        return 'Permanent';
    }

    $seconds = max(0, $untilSeconds - max(1, $timeSeconds));

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

function mineacle_bind(PDOStatement $stmt, array $params): void {
    foreach ($params as $key => $value) {
        if (is_int($value)) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':' . $key, $value);
        }
    }
}

function fetch_litebans_bans_page(string $search = '', int $page = 1): array {
    $config = mineacle_config();
    $pdo = mineacle_db();

    $bansTable = mineacle_table((string) (($config['litebans']['bans_table'] ?? null) ?: 'litebans_bans'));
    $historyTable = mineacle_table((string) (($config['litebans']['history_table'] ?? null) ?: 'litebans_history'));

    $limit = max(1, min(100, (int) ($config['page']['limit'] ?? 25)));
    $page = max(1, $page);
    $offset = ($page - 1) * $limit;

    $nowSeconds = time();
    $nowMillis = $nowSeconds * 1000;

    $where = [
        'b.`active` = 1',
        '(b.`until` <= 0 OR b.`until` > :now_seconds OR b.`until` > :now_millis)',
    ];

    $params = [
        'now_seconds' => $nowSeconds,
        'now_millis' => $nowMillis,
    ];

    $search = trim($search);
    if ($search !== '') {
        $like = '%' . $search . '%';

        $where[] = '(
            b.`uuid` LIKE :search_uuid
            OR b.`reason` LIKE :search_reason
            OR EXISTS (
                SELECT 1
                FROM `' . $historyTable . '` hs
                WHERE hs.`uuid` = b.`uuid`
                AND hs.`name` LIKE :search_name
            )
        )';

        $params['search_uuid'] = $like;
        $params['search_reason'] = $like;
        $params['search_name'] = $like;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $countSql = 'SELECT COUNT(*)
        FROM `' . $bansTable . '` b
        ' . $whereSql;

    $countStmt = $pdo->prepare($countSql);
    mineacle_bind($countStmt, $params);
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $sql = 'SELECT
            b.`id`,
            b.`uuid`,
            b.`reason`,
            b.`time`,
            b.`until`,
            b.`active`,
            b.`ipban`,
            COALESCE((
                SELECT h.`name`
                FROM `' . $historyTable . '` h
                WHERE h.`uuid` = b.`uuid`
                ORDER BY h.`date` DESC
                LIMIT 1
            ), \'\') AS player_name
        FROM `' . $bansTable . '` b
        ' . $whereSql . '
        ORDER BY b.`time` DESC
        LIMIT :limit OFFSET :offset';

    $stmtParams = $params;
    $stmtParams['limit'] = $limit;
    $stmtParams['offset'] = $offset;

    $stmt = $pdo->prepare($sql);
    mineacle_bind($stmt, $stmtParams);
    $stmt->execute();

    $bans = [];

    foreach ($stmt->fetchAll() as $row) {
        $uuid = trim((string) ($row['uuid'] ?? ''));
        $name = trim((string) ($row['player_name'] ?? ''));

        if ($name === '') {
            $name = '#offline#';
        }

        $isIpBan = ((int) ($row['ipban'] ?? 0)) === 1;
        $until = (int) ($row['until'] ?? 0);
        $time = (int) ($row['time'] ?? 0);
        $permanent = $until <= 0;

        $bans[] = [
            'id' => (string) ($row['id'] ?? ''),
            'uuid' => $uuid,
            'username' => $isIpBan && $name === '#offline#' ? '#offline#' : $name,
            'skin' => mineacle_skin_url($uuid !== '' ? $uuid : $name),
            'reason' => (string) ($row['reason'] ?? 'No reason provided'),
            'type' => $isIpBan ? 'IP Ban' : 'Player Ban',
            'duration' => mineacle_format_duration($until, $time),
            'date' => mineacle_format_date($time),
            'status' => $isIpBan || $permanent ? 'Permanently Banned' : 'Active',
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
                '{username}' => $name,
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
