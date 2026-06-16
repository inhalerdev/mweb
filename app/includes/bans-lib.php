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

    // LiteBans/MySQL timestamp storage may be seconds, milliseconds,
    // microseconds, or nanoseconds depending on version/import path.
    if ($value >= 100000000000000000) {
        return (int) floor($value / 1000000000);
    }

    if ($value >= 100000000000000) {
        return (int) floor($value / 1000000);
    }

    if ($value >= 100000000000) {
        return (int) floor($value / 1000);
    }

    return $value;
}

function mineacle_database_now_seconds(PDO $pdo): int {
    try {
        $value = $pdo->query('SELECT UNIX_TIMESTAMP()')->fetchColumn();
        $seconds = (int) floor((float) $value);

        if ($seconds > 0) {
            return $seconds;
        }
    } catch (Throwable) {
        // Fall back to PHP only if MySQL cannot provide current database time.
    }

    return time();
}

function mineacle_database_timezone(): DateTimeZone {
    $config = mineacle_config();
    $timezoneName = (string) ($config['site']['database_timezone'] ?? 'UTC');

    try {
        return new DateTimeZone($timezoneName);
    } catch (Throwable) {
        return new DateTimeZone('UTC');
    }
}



function mineacle_is_litebans_permanent_until(mixed $until): bool {
    if ($until === null || $until === '') {
        return true;
    }

    $seconds = mineacle_normalize_litebans_timestamp($until);

    if ($seconds <= 0) {
        return true;
    }

    /*
     * LiteBans / Java-era permanent or max-time sentinel values can normalize to
     * 2147483647 seconds, which renders as Jan 19, 2038 03:14:07 UTC.
     * That is not a real temporary-ban expiration and must never be displayed
     * as a temporary ban date.
     */
    return $seconds >= 2147480000;
}

function mineacle_has_real_temporary_until(mixed $until, int $databaseNow): bool {
    if (mineacle_is_litebans_permanent_until($until)) {
        return false;
    }

    $seconds = mineacle_normalize_litebans_timestamp($until);

    return $seconds > $databaseNow;
}

function mineacle_format_date(mixed $value): string {
    $seconds = mineacle_epoch_seconds($value);

    if ($seconds <= 0) {
        return 'Unknown';
    }

    return (new DateTimeImmutable('@' . $seconds))
        ->setTimezone(mineacle_database_timezone())
        ->format('M j, Y g:i:s A T');
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
        $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
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

    // Match LiteBans use_database_time behavior by using MySQL's current epoch for active temp-ban filtering.
    $nowSeconds = mineacle_database_now_seconds($pdo);
    $nowMillis = $nowSeconds * 1000;
    $nowMicros = $nowSeconds * 1000000;
    $nowNanos = $nowSeconds * 1000000000;

    $where = [
        'b.`active` = 1',
        '(
            b.`until` <= 0
            OR b.`until` > :now_seconds
            OR b.`until` > :now_millis
            OR b.`until` > :now_micros
            OR b.`until` > :now_nanos
        )',
    ];

    $params = [
        'now_seconds' => $nowSeconds,
        'now_millis' => $nowMillis,
        'now_micros' => $nowMicros,
        'now_nanos' => $nowNanos,
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

    $countSql = 'SELECT COUNT(*) FROM `' . $bansTable . '` b ' . $whereSql;
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
        $untilSeconds = mineacle_epoch_seconds($until);
        $timeSeconds = mineacle_epoch_seconds($time);
        $permanent = $until <= 0 || $untilSeconds <= 0;
        $temporary = !$isIpBan && !$permanent;

        if (mineacle_is_litebans_permanent_until($row['until'] ?? null)) { $temporary = false; }
        if ($isIpBan) {
            $type = 'IP Ban';
            $status = 'Permanently Banned';
            $statusType = 'ip';
            $canPay = false;
            $actionLabel = 'No Action';
            $actionType = 'ip';
        } elseif ($temporary) {
            $type = 'Temporary Ban';
            $status = 'Temporarily Banned';
            $statusType = 'temporary';
            $canPay = false;
            $actionLabel = 'Wait It Out';
            $actionType = 'wait';
        } else {
            $type = 'Player Ban';
            $status = 'Permanently Banned';
            $statusType = 'active';
            $canPay = true;
            $actionLabel = 'Pay to Unban';
            $actionType = 'pay';
        }

        $bans[] = [
            'id' => (string) ($row['id'] ?? ''),
            'uuid' => $uuid,
            'username' => $isIpBan && $name === '#offline#' ? '#offline#' : $name,
            'skin' => mineacle_skin_url($uuid !== '' ? $uuid : $name),
            'reason' => (string) ($row['reason'] ?? 'No reason provided'),
            'type' => $type,
            'duration' => mineacle_format_duration($until, $time),
            'date' => mineacle_format_date($time),
            'expires' => $temporary ? mineacle_format_date($until) : ($permanent ? 'Never' : 'Unknown'),
            'status' => $status,
            'status_type' => $statusType,
            'ipban' => $isIpBan,
            'temporary' => $temporary,
            'permanent' => $permanent,
            'appeal_id' => 'MCL-' . str_pad((string) ($row['id'] ?? '0'), 6, '0', STR_PAD_LEFT),
            'support_email' => (string) ($config['site']['support_email'] ?? 'support@mineacle.net'),
            'discord' => (string) ($config['site']['discord'] ?? 'https://discord.gg/4xrYFxdSWg'),
            'can_pay' => $canPay,
            'action_label' => $actionLabel,
            'action_type' => $actionType,
            'price' => $canPay
                ? (string) ($config['payments']['permanent_unban_price'] ?? '$19.99')
                : '',
            'unban_url' => $canPay ? strtr((string) ($config['site']['unban_checkout_url'] ?? '#'), [
                '{id}' => (string) ($row['id'] ?? ''),
                '{uuid}' => $uuid,
                '{username}' => $name,
            ]) : '#',

            // Debug-safe timestamp fields. These are not shown directly in the UI, but can be inspected
            // in DevTools with window.mineacleCurrentBans[0] if a displayed date looks wrong.
            'time_raw' => (string) $time,
            'until_raw' => (string) $until,
            'time_seconds' => $timeSeconds,
            'until_seconds' => $untilSeconds,
            'database_now_seconds' => $nowSeconds,
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
