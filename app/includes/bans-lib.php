<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cache.php';

function mineacle_litebans_table(string $name): string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException('Invalid LiteBans table name');
    }
    return '`' . $name . '`';
}

function mineacle_table_columns(PDO $pdo, string $table): array
{
    $config = mineacle_config();
    $ttl = (int) ($config['cache']['schema_ttl'] ?? 86400);
    $key = mineacle_cache_key('columns', [$table]);
    $cached = mineacle_cache_get($key);
    if (is_array($cached)) {
        return $cached;
    }

    $safeTable = mineacle_litebans_table($table);
    $stmt = $pdo->query('DESCRIBE ' . $safeTable);
    $columns = [];
    foreach ($stmt->fetchAll() as $row) {
        $field = strtolower((string) ($row['Field'] ?? ''));
        if ($field !== '') {
            $columns[$field] = true;
        }
    }

    mineacle_cache_set($key, $columns, $ttl);
    return $columns;
}

function mineacle_has_column(array $columns, string $name): bool
{
    return isset($columns[strtolower($name)]);
}

function mineacle_ban_time_to_seconds(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    $raw = (int) $value;
    if ($raw <= 0) {
        return null;
    }

    $digits = strlen((string) abs($raw));
    if ($digits >= 18) {
        return (int) floor($raw / 1000000000);
    }
    if ($digits >= 13) {
        return (int) floor($raw / 1000);
    }
    return $raw;
}

function mineacle_format_datetime(mixed $value): string
{
    $seconds = mineacle_ban_time_to_seconds($value);
    if ($seconds === null) {
        return 'Unknown';
    }

    $config = mineacle_config();
    try {
        $date = (new DateTimeImmutable('@' . $seconds))->setTimezone(new DateTimeZone((string) $config['site']['display_timezone']));
        return $date->format('M j, Y g:i A T');
    } catch (Throwable) {
        return 'Unknown';
    }
}

function mineacle_format_duration(mixed $timeValue, mixed $untilValue): string
{
    $untilRaw = (int) ($untilValue ?? 0);
    if ($untilRaw <= 0 || $untilRaw >= 9999999999999) {
        return 'Permanent';
    }

    $start = mineacle_ban_time_to_seconds($timeValue);
    $end = mineacle_ban_time_to_seconds($untilValue);
    if ($start === null || $end === null || $end <= $start) {
        return 'Temporary';
    }

    $diff = $end - $start;
    $days = intdiv($diff, 86400);
    if ($days >= 365) {
        $years = round($days / 365, 1);
        return rtrim(rtrim((string) $years, '0'), '.') . ' years';
    }
    if ($days >= 1) {
        return $days . ' day' . ($days === 1 ? '' : 's');
    }
    $hours = intdiv($diff, 3600);
    if ($hours >= 1) {
        return $hours . ' hour' . ($hours === 1 ? '' : 's');
    }
    $minutes = max(1, intdiv($diff, 60));
    return $minutes . ' minute' . ($minutes === 1 ? '' : 's');
}

function mineacle_is_permanent(mixed $untilValue): bool
{
    $untilRaw = (int) ($untilValue ?? 0);
    return $untilRaw <= 0 || $untilRaw >= 9999999999999;
}

function mineacle_skin_url(?string $uuid, ?string $username = null): string
{
    $uuid = trim((string) $uuid);
    if ($uuid !== '' && strtolower($uuid) !== 'console' && strtolower($uuid) !== 'ipban') {
        return 'https://crafatar.com/avatars/' . rawurlencode($uuid) . '?size=64&overlay=true&default=MHF_Steve';
    }

    $username = trim((string) $username);
    if ($username !== '') {
        return 'https://minotar.net/avatar/' . rawurlencode($username) . '/64';
    }

    return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2264%22 height=%2264%22 viewBox=%220 0 64 64%22%3E%3Crect width=%2264%22 height=%2264%22 fill=%22%23210d30%22/%3E%3Crect x=%2212%22 y=%2210%22 width=%2240%22 height=%2244%22 rx=%226%22 fill=%22%23ff55ff%22 opacity=%22.45%22/%3E%3C/svg%3E';
}

function mineacle_resolve_names(PDO $pdo, string $historyTable, array $uuids): array
{
    $uuids = array_values(array_unique(array_filter(array_map('strval', $uuids), static fn($v) => $v !== '')));
    if (!$uuids) {
        return [];
    }

    try {
        $historyColumns = mineacle_table_columns($pdo, $historyTable);
        if (!mineacle_has_column($historyColumns, 'uuid') || !mineacle_has_column($historyColumns, 'name')) {
            return [];
        }

        $dateColumn = mineacle_has_column($historyColumns, 'date') ? '`date`' : (mineacle_has_column($historyColumns, 'id') ? '`id`' : '`uuid`');
        $placeholders = [];
        $params = [];
        foreach ($uuids as $index => $uuid) {
            $key = ':u' . $index;
            $placeholders[] = $key;
            $params[$key] = $uuid;
        }

        $sql = 'SELECT `uuid`, `name` FROM ' . mineacle_litebans_table($historyTable) . ' WHERE `uuid` IN (' . implode(',', $placeholders) . ') ORDER BY ' . $dateColumn . ' DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $names = [];
        foreach ($stmt->fetchAll() as $row) {
            $uuid = (string) ($row['uuid'] ?? '');
            if ($uuid !== '' && !isset($names[$uuid])) {
                $name = trim((string) ($row['name'] ?? ''));
                if ($name !== '') {
                    $names[$uuid] = $name;
                }
            }
        }
        return $names;
    } catch (Throwable) {
        return [];
    }
}

function mineacle_public_ban_row(array $row, array $names): array
{
    $config = mineacle_config();
    $id = (int) ($row['id'] ?? 0);
    $uuid = trim((string) ($row['uuid'] ?? ''));
    $ipban = ((int) ($row['ipban'] ?? 0)) === 1 || strcasecmp($uuid, 'IPBAN') === 0;
    $username = $ipban ? 'IP Ban' : ($names[$uuid] ?? ($uuid !== '' ? 'Player' : 'Unknown'));
    $reason = trim((string) ($row['reason'] ?? ''));
    if ($reason === '') {
        $reason = 'Rule violation';
    }

    $permanent = mineacle_is_permanent($row['until'] ?? null);
    $temporary = !$permanent;
    $canPay = !$temporary && !$ipban;

    $checkout = (string) $config['site']['unban_checkout_url'];
    $checkout = str_replace(
        ['{id}', '{uuid}', '{username}'],
        [rawurlencode((string) $id), rawurlencode($uuid), rawurlencode($username)],
        $checkout
    );

    return [
        'id' => $id,
        'username' => $username,
        'name' => $username,
        'uuid' => $ipban ? '' : $uuid,
        'skin' => $ipban ? '' : mineacle_skin_url($uuid, $username),
        'reason' => $reason,
        'type' => $ipban ? 'IP Ban' : 'Player Ban',
        'ipban' => $ipban,
        'temporary' => $temporary,
        'permanent' => $permanent,
        'duration' => mineacle_format_duration($row['time'] ?? null, $row['until'] ?? null),
        'date' => mineacle_format_datetime($row['time'] ?? null),
        'expires' => $permanent ? 'Never' : mineacle_format_datetime($row['until'] ?? null),
        'status' => 'Active',
        'appeal_id' => $id > 0 ? 'MCL-' . strtoupper(base_convert((string) $id, 10, 36)) : 'MCL-PENDING',
        'email' => (string) $config['site']['appeal_email'],
        'discord' => (string) $config['site']['appeal_discord'],
        'can_pay' => $canPay,
        'action' => $canPay ? 'Pay to Unban' : ($temporary ? 'Wait It Out' : 'Appeal Only'),
        'price' => $canPay ? (string) $config['payments']['permanent_unban_price'] : '',
        'unban_url' => $canPay ? $checkout : '',
    ];
}

function fetch_litebans_bans_page(string $search = '', int $page = 1): array
{
    $config = mineacle_config();
    $limit = (int) ($config['page']['limit'] ?? 25);
    $page = max(1, $page);
    $search = trim(substr($search, 0, 32));

    $cacheKey = mineacle_cache_key('bans_page_v3', [$search, $page, $limit]);
    $cached = mineacle_cache_get($cacheKey);
    if (is_array($cached)) {
        $cached['cached'] = true;
        return $cached;
    }

    $pdo = mineacle_pdo();
    $bansTable = (string) $config['litebans']['bans_table'];
    $historyTable = (string) $config['litebans']['history_table'];
    $bansColumns = mineacle_table_columns($pdo, $bansTable);

    foreach (['id', 'uuid', 'reason', 'time', 'until', 'active'] as $required) {
        if (!mineacle_has_column($bansColumns, $required)) {
            throw new RuntimeException('LiteBans bans table is missing required column: ' . $required);
        }
    }

    $offset = ($page - 1) * $limit;
    $fetchLimit = $limit + 1;
    $nowSeconds = time();
    $nowMillis = $nowSeconds * 1000;

    $where = ['`active` = 1', '(`until` <= 0 OR `until` >= :now_seconds OR `until` >= :now_millis)'];
    $params = [
        ':now_seconds' => $nowSeconds,
        ':now_millis' => $nowMillis,
    ];

    if ($search !== '') {
        $searchClauses = ['`uuid` LIKE :search_prefix'];
        $params[':search_prefix'] = $search . '%';
        $params[':search_contains'] = '%' . $search . '%';

        if (mineacle_has_column($bansColumns, 'reason')) {
            $searchClauses[] = '`reason` LIKE :search_contains';
        }

        try {
            $historyColumns = mineacle_table_columns($pdo, $historyTable);
            if (mineacle_has_column($historyColumns, 'uuid') && mineacle_has_column($historyColumns, 'name')) {
                $searchClauses[] = 'EXISTS (SELECT 1 FROM ' . mineacle_litebans_table($historyTable) . ' h WHERE h.`uuid` = b.`uuid` AND h.`name` LIKE :search_prefix LIMIT 1)';
            }
        } catch (Throwable) {
            // Search still works by uuid/reason if history is unavailable.
        }

        $where[] = '(' . implode(' OR ', $searchClauses) . ')';
    }

    $select = ['`id`', '`uuid`', '`reason`', '`time`', '`until`'];
    if (mineacle_has_column($bansColumns, 'ipban')) {
        $select[] = '`ipban`';
    } else {
        $select[] = '0 AS `ipban`';
    }

    $sql = 'SELECT ' . implode(', ', $select) . ' FROM ' . mineacle_litebans_table($bansTable) . ' b WHERE ' . implode(' AND ', $where) . ' ORDER BY `time` DESC LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $fetchLimit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();
    $hasNext = count($rows) > $limit;
    if ($hasNext) {
        array_pop($rows);
    }

    $names = mineacle_resolve_names($pdo, $historyTable, array_column($rows, 'uuid'));
    $bans = array_map(static fn(array $row): array => mineacle_public_ban_row($row, $names), $rows);

    $result = [
        'bans' => $bans,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'has_next' => $hasNext,
            'has_previous' => $page > 1,
            'next_page' => $hasNext ? $page + 1 : null,
            'previous_page' => $page > 1 ? $page - 1 : null,
            'total' => null,
            'pages' => null,
        ],
        'cached' => false,
    ];

    mineacle_cache_set($cacheKey, $result, (int) ($config['cache']['bans_ttl'] ?? 8));
    return $result;
}
