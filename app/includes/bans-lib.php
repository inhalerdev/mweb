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

function mineacle_table_columns(PDO $pdo, string $table): array {
    static $cache = [];

    $table = mineacle_table($table);
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    try {
        $rows = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll();
    } catch (Throwable) {
        return $cache[$table] = [];
    }

    $columns = [];
    foreach ($rows as $row) {
        $field = (string) ($row['Field'] ?? '');
        if ($field !== '') {
            $columns[strtolower($field)] = $field;
        }
    }

    return $cache[$table] = $columns;
}

function mineacle_column(array $columns, string|array $names): ?string {
    foreach ((array) $names as $name) {
        $key = strtolower((string) $name);
        if (isset($columns[$key])) {
            return $columns[$key];
        }
    }

    return null;
}

function mineacle_has_column(array $columns, string|array $names): bool {
    return mineacle_column($columns, $names) !== null;
}

function mineacle_select_column(string $alias, array $columns, string|array $names, string $as, string $fallback = 'NULL'): string {
    $column = mineacle_column($columns, $names);
    $expr = $column === null ? $fallback : $alias . '.`' . $column . '`';

    return $expr . ' AS `' . $as . '`';
}

function mineacle_history_name_sql(PDO $pdo, string $historyTable, array $banColumns): string {
    $historyColumns = mineacle_table_columns($pdo, $historyTable);
    $historyUuid = mineacle_column($historyColumns, 'uuid');
    $historyName = mineacle_column($historyColumns, 'name');
    $historyDate = mineacle_column($historyColumns, ['date', 'time']);
    $banUuid = mineacle_column($banColumns, 'uuid');

    if ($historyUuid === null || $historyName === null || $banUuid === null) {
        return '\'\' AS `player_name`';
    }

    $order = $historyDate === null ? '' : ' ORDER BY h.`' . $historyDate . '` DESC';

    return 'COALESCE((
        SELECT h.`' . $historyName . '`
        FROM `' . mineacle_table($historyTable) . '` h
        WHERE h.`' . $historyUuid . '` = b.`' . $banUuid . '`
        ' . $order . '
        LIMIT 1
    ), \'\') AS `player_name`';
}

function mineacle_ban_select_sql(PDO $pdo, string $historyTable, array $banColumns): string {
    return implode(",\n            ", [
        mineacle_select_column('b', $banColumns, 'id', 'id', '0'),
        mineacle_select_column('b', $banColumns, 'uuid', 'uuid', '\'\''),
        mineacle_select_column('b', $banColumns, 'reason', 'reason', '\'No reason provided\''),
        mineacle_select_column('b', $banColumns, 'time', 'time', '0'),
        mineacle_select_column('b', $banColumns, 'until', 'until', '0'),
        mineacle_select_column('b', $banColumns, 'active', 'active', '1'),
        mineacle_select_column('b', $banColumns, 'ipban', 'ipban', '0'),
        mineacle_select_column('b', $banColumns, ['banned_by_name', 'staff_name', 'executor_name'], 'staff_name', '\'\''),
        mineacle_select_column('b', $banColumns, ['banned_by_uuid', 'staff_uuid', 'executor_uuid'], 'staff_uuid', '\'\''),
        mineacle_select_column('b', $banColumns, ['removed_by_name', 'removed_by'], 'removed_by_name', '\'\''),
        mineacle_select_column('b', $banColumns, ['removed_by_uuid'], 'removed_by_uuid', '\'\''),
        mineacle_select_column('b', $banColumns, ['removed_by_date', 'removed_date'], 'removed_by_date', '0'),
        mineacle_select_column('b', $banColumns, ['server_origin', 'server'], 'server_origin', '\'\''),
        mineacle_select_column('b', $banColumns, ['server_scope', 'server'], 'server_scope', '\'\''),
        mineacle_select_column('b', $banColumns, 'server', 'server', '\'\''),
        mineacle_select_column('b', $banColumns, 'silent', 'silent', '0'),
        mineacle_history_name_sql($pdo, $historyTable, $banColumns),
    ]);
}

function mineacle_active_condition(array $columns, string $alias, string $nowParam = 'now_seconds'): string {
    $parts = [];

    $active = mineacle_column($columns, 'active');
    if ($active !== null) {
        $parts[] = $alias . '.`' . $active . '` = 1';
    }

    $until = mineacle_column($columns, 'until');
    if ($until !== null) {
        $untilRef = $alias . '.`' . $until . '`';
        $parts[] = '(' . $untilRef . ' <= 0
            OR ' . mineacle_litebans_until_seconds_sql($untilRef) . ' > :' . $nowParam . '
            OR ' . $untilRef . ' IN (2147483647, 2147483647000)
        )';
    }

    return $parts === [] ? '1 = 1' : implode(' AND ', $parts);
}

function mineacle_add_ban_search(array &$where, array &$params, array $banColumns, string $historyTable, string $search): void {
    $search = trim($search);
    if ($search === '') {
        return;
    }

    $like = '%' . $search . '%';
    $conditions = [];
    $index = 0;

    foreach ([
        'uuid',
        'reason',
        'banned_by_name',
        'banned_by_uuid',
        'removed_by_name',
        'server_origin',
        'server_scope',
        'server',
    ] as $columnName) {
        $column = mineacle_column($banColumns, $columnName);
        if ($column === null) {
            continue;
        }

        $param = 'search_' . $index++;
        $conditions[] = 'b.`' . $column . '` LIKE :' . $param;
        $params[$param] = $like;
    }

    $pdo = mineacle_db();
    $historyColumns = mineacle_table_columns($pdo, $historyTable);
    $historyUuid = mineacle_column($historyColumns, 'uuid');
    $historyName = mineacle_column($historyColumns, 'name');
    $banUuid = mineacle_column($banColumns, 'uuid');

    if ($historyUuid !== null && $historyName !== null && $banUuid !== null) {
        $param = 'search_' . $index++;
        $conditions[] = 'EXISTS (
            SELECT 1
            FROM `' . mineacle_table($historyTable) . '` hs
            WHERE hs.`' . $historyUuid . '` = b.`' . $banUuid . '`
            AND hs.`' . $historyName . '` LIKE :' . $param . '
        )';
        $params[$param] = $like;
    }

    if ($conditions !== []) {
        $where[] = '(' . implode(' OR ', $conditions) . ')';
    }
}

function mineacle_staff_display(mixed $name, mixed $uuid): string {
    $name = trim((string) $name);
    if ($name !== '') {
        return $name;
    }

    $uuid = trim((string) $uuid);
    if ($uuid === '' || strcasecmp($uuid, 'console') === 0) {
        return 'Console';
    }

    return $uuid;
}

function mineacle_server_display(mixed $value, string $fallback = 'Global'): string {
    $server = trim((string) $value);
    if ($server === '' || $server === '*' || strcasecmp($server, 'global') === 0) {
        return $fallback;
    }

    return $server;
}

function mineacle_format_litebans_date(mixed $value): string {
    $seconds = mineacle_epoch_seconds($value);
    if ($seconds <= 0) {
        return 'Unknown';
    }

    return (new DateTimeImmutable('@' . $seconds))
        ->setTimezone(mineacle_display_timezone())
        ->format('Y-m-d H:i:s');
}

function mineacle_map_ban_row(array $row, array $config, int $nowSeconds): array {
    $id = (string) ($row['id'] ?? '');
    $uuid = trim((string) ($row['uuid'] ?? ''));
    $name = trim((string) ($row['player_name'] ?? ''));
    if ($name === '') {
        $name = $uuid === '' || str_starts_with($uuid, '#') ? '#offline#' : $uuid;
    }

    $time = (string) ($row['time'] ?? '0');
    $until = (string) ($row['until'] ?? '0');
    $untilSeconds = mineacle_epoch_seconds($until);
    $timeSeconds = mineacle_epoch_seconds($time);
    $isIpBan = ((int) ($row['ipban'] ?? 0)) === 1;
    $activeRaw = ((int) ($row['active'] ?? 1)) === 1;
    $permanent = mineacle_litebans_is_permanent($until);
    $temporary = !$permanent && $untilSeconds > 0;
    $currentlyActive = $activeRaw && ($permanent || $untilSeconds <= 0 || $untilSeconds > $nowSeconds);
    $expired = $activeRaw && $temporary && $untilSeconds > 0 && $untilSeconds <= $nowSeconds;

    if (!$activeRaw) {
        $status = 'Removed';
        $statusState = 'removed';
    } elseif ($expired) {
        $status = 'Expired';
        $statusState = 'expired';
    } else {
        $status = 'Active';
        $statusState = 'active';
    }

    $serverOrigin = mineacle_server_display($row['server_origin'] ?? '', 'Global');
    $serverScope = mineacle_server_display($row['server_scope'] ?? '', $serverOrigin);
    $server = mineacle_server_display($row['server'] ?? '', $serverOrigin);

    $flags = [];
    if ($isIpBan) {
        $flags[] = 'IP Ban';
    }
    if (((int) ($row['silent'] ?? 0)) === 1) {
        $flags[] = 'Silent';
    }

    $canPay = $currentlyActive && !$isIpBan && $permanent;
    $staff = mineacle_staff_display($row['staff_name'] ?? '', $row['staff_uuid'] ?? '');
    $removedBy = !$activeRaw ? mineacle_staff_display($row['removed_by_name'] ?? '', $row['removed_by_uuid'] ?? '') : '';

    return [
        'id' => $id,
        'uuid' => $uuid,
        'username' => $name,
        'skin' => mineacle_skin_url($uuid !== '' ? $uuid : $name),
        'reason' => (string) (($row['reason'] ?? '') !== '' ? $row['reason'] : 'No reason provided'),
        'type' => $isIpBan ? 'IP Ban' : 'Ban',
        'duration' => $permanent ? 'Permanent' : mineacle_format_duration($until, $time),
        'date' => mineacle_format_litebans_date($time),
        'expires' => !$activeRaw ? '-' : ($permanent ? 'Permanent' : mineacle_format_litebans_date($until)),
        'status' => $status,
        'status_state' => $statusState,
        'status_type' => $isIpBan ? 'ip' : ($temporary ? 'temporary' : 'permanent'),
        'ipban' => $isIpBan,
        'temporary' => $temporary && $currentlyActive,
        'permanent' => $permanent,
        'active' => $currentlyActive,
        'removed' => !$activeRaw,
        'expired' => $expired,
        'staff' => $staff,
        'removed_by' => $removedBy,
        'removed_date' => !$activeRaw ? mineacle_format_litebans_date($row['removed_by_date'] ?? 0) : '',
        'server' => $server,
        'server_origin' => $serverOrigin,
        'server_scope' => $serverScope,
        'flags' => $flags,
        'flags_text' => $flags === [] ? 'None' : implode(', ', $flags),
        'appeal_id' => 'MCL-' . str_pad($id === '' ? '0' : $id, 6, '0', STR_PAD_LEFT),
        'support_email' => (string) ($config['site']['support_email'] ?? 'support@mineacle.net'),
        'discord' => (string) ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM'),
        'appeal_email' => (string) (($config['site']['appeal_email'] ?? 'support@mineacle.net')),
        'appeal_discord' => (string) (($config['site']['appeal_discord'] ?? ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM'))),
        'can_pay' => $canPay,
        'action_type' => $canPay ? 'pay' : 'view',
        'action_label' => $canPay ? 'Pay to Unban' : 'View',
        'price' => $canPay ? (string) ($config['payments']['permanent_unban_price'] ?? '$19.99') : '',
        'unban_url' => $canPay ? strtr((string) ($config['site']['unban_checkout_url'] ?? '#'), [
            '{id}' => $id,
            '{uuid}' => $uuid,
            '{username}' => $name,
        ]) : '#',
        'time_raw' => $time,
        'until_raw' => $until,
        'time_seconds' => $timeSeconds,
        'until_seconds' => $untilSeconds,
        'database_now_seconds' => $nowSeconds,
    ];
}

function mineacle_count_table(PDO $pdo, string $table, string $where = '', array $params = []): int {
    $table = mineacle_table($table);
    if (mineacle_table_columns($pdo, $table) === []) {
        return 0;
    }

    $sql = 'SELECT COUNT(*) FROM `' . $table . '` t' . ($where === '' ? '' : ' ' . $where);

    try {
        $stmt = $pdo->prepare($sql);
        mineacle_bind($stmt, $params);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
}

function fetch_litebans_stats(): array {
    $config = mineacle_config();
    $pdo = mineacle_db();
    $litebans = $config['litebans'] ?? [];
    $nowSeconds = mineacle_database_now_seconds($pdo);

    $bansTable = mineacle_table((string) (($litebans['bans_table'] ?? null) ?: 'litebans_bans'));
    $mutesTable = mineacle_table((string) (($litebans['mutes_table'] ?? null) ?: 'litebans_mutes'));
    $warningsTable = mineacle_table((string) (($litebans['warnings_table'] ?? null) ?: 'litebans_warnings'));
    $kicksTable = mineacle_table((string) (($litebans['kicks_table'] ?? null) ?: 'litebans_kicks'));

    $banColumns = mineacle_table_columns($pdo, $bansTable);
    $muteColumns = mineacle_table_columns($pdo, $mutesTable);
    $activeBansWhere = mineacle_active_condition($banColumns, 't');
    $activeMutesWhere = mineacle_active_condition($muteColumns, 't');
    $activeBansParams = str_contains($activeBansWhere, ':now_seconds') ? ['now_seconds' => $nowSeconds] : [];
    $activeMutesParams = str_contains($activeMutesWhere, ':now_seconds') ? ['now_seconds' => $nowSeconds] : [];

    return [
        'active_bans' => mineacle_count_table($pdo, $bansTable, 'WHERE ' . $activeBansWhere, $activeBansParams),
        'total_bans' => mineacle_count_table($pdo, $bansTable),
        'active_mutes' => mineacle_count_table($pdo, $mutesTable, 'WHERE ' . $activeMutesWhere, $activeMutesParams),
        'total_mutes' => mineacle_count_table($pdo, $mutesTable),
        'total_warnings' => mineacle_count_table($pdo, $warningsTable),
        'total_kicks' => mineacle_count_table($pdo, $kicksTable),
    ];
}

function fetch_litebans_bans_page(string $search = '', int $page = 1, string $scope = 'all'): array {
    $config = mineacle_config();
    $pdo = mineacle_db();
    $litebans = $config['litebans'] ?? [];

    $bansTable = mineacle_table((string) (($litebans['bans_table'] ?? null) ?: 'litebans_bans'));
    $historyTable = mineacle_table((string) (($litebans['history_table'] ?? null) ?: 'litebans_history'));
    $banColumns = mineacle_table_columns($pdo, $bansTable);

    $limit = max(1, min(100, (int) ($config['page']['limit'] ?? 25)));
    $page = max(1, $page);
    $offset = ($page - 1) * $limit;
    $nowSeconds = mineacle_database_now_seconds($pdo);

    $where = [];
    $params = [];

    if ($scope === 'active') {
        $activeWhere = mineacle_active_condition($banColumns, 'b');
        $where[] = $activeWhere;
        if (str_contains($activeWhere, ':now_seconds')) {
            $params['now_seconds'] = $nowSeconds;
        }
    }

    mineacle_add_ban_search($where, $params, $banColumns, $historyTable, $search);
    $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

    $countSql = 'SELECT COUNT(*) FROM `' . $bansTable . '` b ' . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    mineacle_bind($countStmt, $params);
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $orderColumn = mineacle_column($banColumns, 'time') ?? mineacle_column($banColumns, 'id') ?? '';
    $orderSql = $orderColumn === '' ? '' : 'ORDER BY b.`' . $orderColumn . '` DESC';

    $sql = 'SELECT
            ' . mineacle_ban_select_sql($pdo, $historyTable, $banColumns) . '
        FROM `' . $bansTable . '` b
        ' . $whereSql . '
        ' . $orderSql . '
        LIMIT :limit OFFSET :offset';

    $stmtParams = $params;
    $stmtParams['limit'] = $limit;
    $stmtParams['offset'] = $offset;

    $stmt = $pdo->prepare($sql);
    mineacle_bind($stmt, $stmtParams);
    $stmt->execute();

    $bans = [];
    foreach ($stmt->fetchAll() as $row) {
        $bans[] = mineacle_map_ban_row($row, $config, $nowSeconds);
    }

    $totalPages = max(1, (int) ceil($total / $limit));

    return [
        'bans' => $bans,
        'stats' => fetch_litebans_stats(),
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

function fetch_litebans_ban_detail(int $id): ?array {
    if ($id <= 0) {
        return null;
    }

    $config = mineacle_config();
    $pdo = mineacle_db();
    $litebans = $config['litebans'] ?? [];
    $bansTable = mineacle_table((string) (($litebans['bans_table'] ?? null) ?: 'litebans_bans'));
    $historyTable = mineacle_table((string) (($litebans['history_table'] ?? null) ?: 'litebans_history'));
    $banColumns = mineacle_table_columns($pdo, $bansTable);
    $idColumn = mineacle_column($banColumns, 'id');

    if ($idColumn === null) {
        return null;
    }

    $nowSeconds = mineacle_database_now_seconds($pdo);
    $sql = 'SELECT
            ' . mineacle_ban_select_sql($pdo, $historyTable, $banColumns) . '
        FROM `' . $bansTable . '` b
        WHERE b.`' . $idColumn . '` = :id
        LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $ban = mineacle_map_ban_row($row, $config, $nowSeconds);
    $other = [];
    $uuidColumn = mineacle_column($banColumns, 'uuid');

    if ($uuidColumn !== null && $ban['uuid'] !== '') {
        $orderColumn = mineacle_column($banColumns, 'time') ?? $idColumn;
        $otherSql = 'SELECT
                ' . mineacle_ban_select_sql($pdo, $historyTable, $banColumns) . '
            FROM `' . $bansTable . '` b
            WHERE b.`' . $uuidColumn . '` = :uuid
            AND b.`' . $idColumn . '` <> :id
            ORDER BY b.`' . $orderColumn . '` DESC
            LIMIT 12';

        $otherStmt = $pdo->prepare($otherSql);
        $otherStmt->bindValue(':uuid', $ban['uuid'], PDO::PARAM_STR);
        $otherStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $otherStmt->execute();

        foreach ($otherStmt->fetchAll() as $otherRow) {
            $other[] = mineacle_map_ban_row($otherRow, $config, $nowSeconds);
        }
    }

    return [
        'ban' => $ban,
        'other_punishments' => $other,
        'stats' => fetch_litebans_stats(),
    ];
}
