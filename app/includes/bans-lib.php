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
    /*
     * LiteBans stores punishment timestamps as Java milliseconds.
     * Some PHP hosts/environments can clamp a direct `(int)` cast of a 13-digit
     * millisecond timestamp to 2147483647, which displays as Jan 19, 2038.
     * Keep the raw value as a string first, choose the unit by digit length,
     * then divide before casting to int.
     */
    $raw = trim((string) $value);

    if ($raw === '' || $raw === '0') {
        return 0;
    }

    if (!preg_match('/^-?\d+$/', $raw)) {
        return 0;
    }

    if ($raw[0] === '-') {
        return 0;
    }

    $digits = strlen(ltrim($raw, '0'));
    if ($digits === 0) {
        return 0;
    }

    $number = (float) $raw;

    if ($digits >= 18) {
        return (int) floor($number / 1000000000);
    }

    if ($digits >= 15) {
        return (int) floor($number / 1000000);
    }

    if ($digits >= 13) {
        return (int) floor($number / 1000);
    }

    return (int) $number;
}


function mineacle_litebans_until_seconds_sql(string $field): string {
    return '(CASE
        WHEN ' . $field . ' >= 100000000000000000 THEN FLOOR(' . $field . ' / 1000000000)
        WHEN ' . $field . ' >= 100000000000000 THEN FLOOR(' . $field . ' / 1000000)
        WHEN ' . $field . ' >= 100000000000 THEN FLOOR(' . $field . ' / 1000)
        ELSE ' . $field . '
    END)';
}

function mineacle_litebans_is_permanent(mixed $until): bool {
    $raw = trim((string) $until);

    if ($raw === '' || $raw === '0') {
        return true;
    }

    /*
     * LiteBans permanent punishments normally use until <= 0.
     * Treat exact 2038 sentinel forms as permanent only when the raw database
     * value is actually a 2038 sentinel, not when PHP overflow created one.
     */
    return in_array($raw, ['2147483647', '2147483647000'], true);
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


function mineacle_display_timezone(): DateTimeZone {
    $config = mineacle_config();
    $timezone = (string) ($config['site']['display_timezone'] ?? 'America/Chicago');

    try {
        return new DateTimeZone($timezone);
    } catch (Throwable) {
        return new DateTimeZone('America/Chicago');
    }
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

function mineacle_format_date(mixed $value): string {
    $seconds = mineacle_epoch_seconds($value);

    if ($seconds <= 0) {
        return 'Unknown';
    }

    return (new DateTimeImmutable('@' . $seconds))
        ->setTimezone(mineacle_display_timezone())
        ->format('M j, Y g:i:s A T');
}

function mineacle_format_duration(mixed $until, mixed $time): string {
    if (mineacle_litebans_is_permanent($until)) {
        return 'Permanent';
    }

    $untilSeconds = mineacle_epoch_seconds($until);
    $timeSeconds = mineacle_epoch_seconds($time);

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
    $untilSecondsSql = mineacle_litebans_until_seconds_sql('b.`until`');

    $where = [
        'b.`active` = 1',
        '(
            b.`until` <= 0
            OR ' . $untilSecondsSql . ' > :now_seconds
            OR b.`until` IN (2147483647, 2147483647000)
        )',
    ];

    $params = [
        'now_seconds' => $nowSeconds,
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
        $until = (string) ($row['until'] ?? '0');
        $time = (string) ($row['time'] ?? '0');
        $untilSeconds = mineacle_epoch_seconds($until);
        $timeSeconds = mineacle_epoch_seconds($time);
        $permanent = mineacle_litebans_is_permanent($until);
        $temporary = !$isIpBan && !$permanent && $untilSeconds > $nowSeconds;

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
            'expires' => $temporary ? mineacle_format_date($until) : 'Never',
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
            'appeal_email' => (string) (($config['site']['appeal_email'] ?? 'support@mineacle.net')),
            'appeal_discord' => (string) (($config['site']['appeal_discord'] ?? ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM'))),
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
