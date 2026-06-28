<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function mineacle_table(string $name): string {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) throw new RuntimeException('Invalid table name');
    return $name;
}

function mineacle_table_columns(PDO $pdo, string $table): array {
    static $cache = [];
    $table = mineacle_table($table);
    if (isset($cache[$table])) return $cache[$table];
    try { $rows = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(); }
    catch (Throwable) { return $cache[$table] = []; }
    $columns = [];
    foreach ($rows as $row) {
        $field = (string) ($row['Field'] ?? '');
        if ($field !== '') $columns[strtolower($field)] = $field;
    }
    return $cache[$table] = $columns;
}

function mineacle_column(array $columns, $names): ?string {
    foreach ((array) $names as $name) {
        $key = strtolower((string) $name);
        if (isset($columns[$key])) return $columns[$key];
    }
    return null;
}

function mineacle_select_column(string $alias, array $columns, $names, string $as, string $fallback = 'NULL'): string {
    $column = mineacle_column($columns, $names);
    return ($column === null ? $fallback : $alias . '.`' . $column . '`') . ' AS `' . $as . '`';
}

function mineacle_epoch_seconds($value): int {
    $raw = trim((string) $value);
    if ($raw === '' || $raw === '0' || !preg_match('/^-?\d+$/', $raw) || $raw[0] === '-') return 0;
    $digits = strlen(ltrim($raw, '0'));
    if ($digits === 0) return 0;
    $number = (float) $raw;
    if ($digits >= 18) return (int) floor($number / 1000000000);
    if ($digits >= 15) return (int) floor($number / 1000000);
    if ($digits >= 13) return (int) floor($number / 1000);
    return (int) $number;
}

function mineacle_litebans_until_seconds_sql(string $field): string {
    return '(CASE WHEN ' . $field . ' >= 100000000000000000 THEN FLOOR(' . $field . ' / 1000000000) WHEN ' . $field . ' >= 100000000000000 THEN FLOOR(' . $field . ' / 1000000) WHEN ' . $field . ' >= 100000000000 THEN FLOOR(' . $field . ' / 1000) ELSE ' . $field . ' END)';
}

function mineacle_litebans_is_permanent($until): bool {
    $raw = trim((string) $until);
    return $raw === '' || $raw === '0' || in_array($raw, ['2147483647', '2147483647000'], true);
}

function mineacle_database_now_seconds(PDO $pdo): int {
    try { $value = $pdo->query('SELECT UNIX_TIMESTAMP()')->fetchColumn(); if ((float) $value > 0) return (int) floor((float) $value); }
    catch (Throwable) {}
    return time();
}

function mineacle_display_timezone(): DateTimeZone {
    $timezone = (string) (mineacle_config()['site']['display_timezone'] ?? 'America/Chicago');
    try { return new DateTimeZone($timezone); } catch (Throwable) { return new DateTimeZone('America/Chicago'); }
}

function mineacle_format_litebans_date($value): string {
    $seconds = mineacle_epoch_seconds($value);
    if ($seconds <= 0) return 'Unknown';
    return (new DateTimeImmutable('@' . $seconds))->setTimezone(mineacle_display_timezone())->format('M j, Y g:i A T');
}

function mineacle_format_duration($until, $time): string {
    if (mineacle_litebans_is_permanent($until)) return 'Permanent';
    $seconds = max(0, mineacle_epoch_seconds($until) - max(1, mineacle_epoch_seconds($time)));
    if ($seconds <= 0) return 'Expired';
    $days = intdiv($seconds, 86400); if ($days > 0) return $days . ' day' . ($days === 1 ? '' : 's');
    $hours = intdiv($seconds, 3600); if ($hours > 0) return $hours . ' hour' . ($hours === 1 ? '' : 's');
    $minutes = max(1, intdiv($seconds, 60)); return $minutes . ' minute' . ($minutes === 1 ? '' : 's');
}

function mineacle_bind(PDOStatement $stmt, array $params): void {
    foreach ($params as $key => $value) $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}

function mineacle_history_name_sql(PDO $pdo, string $historyTable, array $banColumns): string {
    $historyColumns = mineacle_table_columns($pdo, $historyTable);
    $historyUuid = mineacle_column($historyColumns, 'uuid');
    $historyName = mineacle_column($historyColumns, 'name');
    $historyDate = mineacle_column($historyColumns, ['date', 'time']);
    $banUuid = mineacle_column($banColumns, 'uuid');
    if ($historyUuid === null || $historyName === null || $banUuid === null) return "'' AS `player_name`";
    $order = $historyDate === null ? '' : ' ORDER BY h.`' . $historyDate . '` DESC';
    return 'COALESCE((SELECT h.`' . $historyName . '` FROM `' . mineacle_table($historyTable) . '` h WHERE h.`' . $historyUuid . '` = b.`' . $banUuid . '`' . $order . ' LIMIT 1), \'\') AS `player_name`';
}

function mineacle_ban_select_sql(PDO $pdo, string $historyTable, array $banColumns): string {
    return implode(",\n", [
        mineacle_select_column('b', $banColumns, 'id', 'id', '0'),
        mineacle_select_column('b', $banColumns, 'uuid', 'uuid', "''"),
        mineacle_select_column('b', $banColumns, 'reason', 'reason', "'No reason provided'"),
        mineacle_select_column('b', $banColumns, 'time', 'time', '0'),
        mineacle_select_column('b', $banColumns, 'until', 'until', '0'),
        mineacle_select_column('b', $banColumns, 'active', 'active', '1'),
        mineacle_select_column('b', $banColumns, 'ipban', 'ipban', '0'),
        mineacle_select_column('b', $banColumns, ['banned_by_name','staff_name','executor_name'], 'staff_name', "''"),
        mineacle_select_column('b', $banColumns, ['banned_by_uuid','staff_uuid','executor_uuid'], 'staff_uuid', "''"),
        mineacle_select_column('b', $banColumns, ['removed_by_name','removed_by'], 'removed_by_name', "''"),
        mineacle_select_column('b', $banColumns, ['removed_by_uuid'], 'removed_by_uuid', "''"),
        mineacle_select_column('b', $banColumns, ['removed_by_date','removed_date'], 'removed_by_date', '0'),
        mineacle_select_column('b', $banColumns, ['server_origin','server'], 'server_origin', "''"),
        mineacle_select_column('b', $banColumns, ['server_scope','server'], 'server_scope', "''"),
        mineacle_select_column('b', $banColumns, 'server', 'server', "''"),
        mineacle_select_column('b', $banColumns, 'silent', 'silent', '0'),
        mineacle_history_name_sql($pdo, $historyTable, $banColumns),
    ]);
}

function mineacle_active_condition(array $columns, string $alias, string $nowParam = 'now_seconds'): string {
    $parts = [];
    $active = mineacle_column($columns, 'active'); if ($active !== null) $parts[] = $alias . '.`' . $active . '` = 1';
    $until = mineacle_column($columns, 'until');
    if ($until !== null) {
        $untilRef = $alias . '.`' . $until . '`';
        $parts[] = '(' . $untilRef . ' <= 0 OR ' . mineacle_litebans_until_seconds_sql($untilRef) . ' > :' . $nowParam . ' OR ' . $untilRef . ' IN (2147483647,2147483647000))';
    }
    return $parts === [] ? '0 = 1' : implode(' AND ', $parts);
}

function mineacle_add_ban_search(array &$where, array &$params, array $banColumns, string $historyTable, string $search): void {
    $search = trim($search); if ($search === '') return;
    $like = '%' . $search . '%'; $conditions = []; $i = 0;
    foreach (['uuid','reason','banned_by_name','banned_by_uuid','removed_by_name','server_origin','server_scope','server'] as $columnName) {
        $column = mineacle_column($banColumns, $columnName); if ($column === null) continue;
        $param = 'search_' . $i++; $conditions[] = 'b.`' . $column . '` LIKE :' . $param; $params[$param] = $like;
    }
    $pdo = mineacle_db(); $historyColumns = mineacle_table_columns($pdo, $historyTable);
    $historyUuid = mineacle_column($historyColumns, 'uuid'); $historyName = mineacle_column($historyColumns, 'name'); $banUuid = mineacle_column($banColumns, 'uuid');
    if ($historyUuid !== null && $historyName !== null && $banUuid !== null) {
        $param = 'search_' . $i++;
        $conditions[] = 'EXISTS (SELECT 1 FROM `' . mineacle_table($historyTable) . '` hs WHERE hs.`' . $historyUuid . '` = b.`' . $banUuid . '` AND hs.`' . $historyName . '` LIKE :' . $param . ')';
        $params[$param] = $like;
    }
    if ($conditions !== []) $where[] = '(' . implode(' OR ', $conditions) . ')';
}

function mineacle_staff_display($name, $uuid): string {
    $name = trim((string) $name); if ($name !== '') return $name;
    $uuid = trim((string) $uuid); return ($uuid === '' || strcasecmp($uuid, 'console') === 0) ? 'Console' : $uuid;
}

function mineacle_server_display($value, string $fallback = 'Global'): string {
    $server = trim((string) $value); return ($server === '' || $server === '*' || strcasecmp($server, 'global') === 0) ? $fallback : $server;
}

function mineacle_skin_url(string $value): string {
    $value = trim($value); if ($value === '' || str_starts_with($value, '#')) $value = 'Steve';
    return 'https://mc-heads.net/avatar/' . rawurlencode($value) . '/96';
}

function mineacle_map_ban_row(array $row, array $config, int $nowSeconds): array {
    $id = (string) ($row['id'] ?? '');
    $uuid = trim((string) ($row['uuid'] ?? ''));
    $name = trim((string) ($row['player_name'] ?? ''));
    if ($name === '') $name = $uuid === '' || str_starts_with($uuid, '#') ? '#offline#' : $uuid;
    $time = (string) ($row['time'] ?? '0'); $until = (string) ($row['until'] ?? '0');
    $untilSeconds = mineacle_epoch_seconds($until); $timeSeconds = mineacle_epoch_seconds($time);
    $isIpBan = ((int) ($row['ipban'] ?? 0)) === 1; $activeRaw = ((int) ($row['active'] ?? 1)) === 1; $permanent = mineacle_litebans_is_permanent($until);
    $temporary = !$permanent && $untilSeconds > 0; $currentlyActive = $activeRaw && ($permanent || $untilSeconds <= 0 || $untilSeconds > $nowSeconds);
    $expired = $activeRaw && $temporary && $untilSeconds > 0 && $untilSeconds <= $nowSeconds;
    $status = !$activeRaw ? 'Removed' : ($expired ? 'Expired' : 'Active');
    $statusState = strtolower($status);
    $serverOrigin = mineacle_server_display($row['server_origin'] ?? '', 'Global'); $serverScope = mineacle_server_display($row['server_scope'] ?? '', $serverOrigin); $server = mineacle_server_display($row['server'] ?? '', $serverOrigin);
    $flags = []; if ($isIpBan) $flags[] = 'IP Ban'; if (((int) ($row['silent'] ?? 0)) === 1) $flags[] = 'Silent';
    $staff = mineacle_staff_display($row['staff_name'] ?? '', $row['staff_uuid'] ?? '');
    $canPay = $currentlyActive && !$isIpBan && $permanent;
    return [
        'id' => $id, 'uuid' => $uuid, 'username' => $name, 'skin' => mineacle_skin_url($uuid !== '' ? $uuid : $name),
        'reason' => (string) (($row['reason'] ?? '') !== '' ? $row['reason'] : 'No reason provided'),
        'type' => $isIpBan ? 'IP Ban' : 'Ban', 'duration' => $permanent ? 'Permanent' : mineacle_format_duration($until, $time),
        'date' => mineacle_format_litebans_date($time), 'expires' => !$activeRaw ? '-' : ($permanent ? 'Permanent' : mineacle_format_litebans_date($until)),
        'status' => $status, 'status_state' => $statusState, 'status_type' => $isIpBan ? 'ip' : ($temporary ? 'temporary' : 'permanent'),
        'ipban' => $isIpBan, 'temporary' => $temporary && $currentlyActive, 'permanent' => $permanent, 'active' => $currentlyActive,
        'staff' => $staff, 'server' => $server, 'server_origin' => $serverOrigin, 'server_scope' => $serverScope,
        'flags' => $flags, 'flags_text' => $flags === [] ? 'None' : implode(', ', $flags),
        'appeal_id' => 'MCL-' . str_pad($id === '' ? '0' : $id, 6, '0', STR_PAD_LEFT),
        'support_email' => (string) ($config['site']['support_email'] ?? 'support@mineacle.net'),
        'discord' => (string) ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM'),
        'can_pay' => $canPay, 'action_label' => $canPay ? 'Pay to Unban' : 'View Details',
        'price' => $canPay ? (string) ($config['payments']['permanent_unban_price'] ?? '$19.99') : '',
        'unban_url' => $canPay ? strtr((string) ($config['site']['unban_checkout_url'] ?? '#'), ['{id}'=>$id,'{uuid}'=>$uuid,'{username}'=>$name]) : '#',
        'time_seconds' => $timeSeconds, 'until_seconds' => $untilSeconds, 'database_now_seconds' => $nowSeconds,
    ];
}

function fetch_litebans_stats(): array {
    $config = mineacle_config(); $pdo = mineacle_db(); $litebans = $config['litebans'] ?? []; $now = mineacle_database_now_seconds($pdo);
    $bansTable = mineacle_table((string) (($litebans['bans_table'] ?? null) ?: 'litebans_bans')); $cols = mineacle_table_columns($pdo, $bansTable); $where = mineacle_active_condition($cols, 't');
    try { $stmt = $pdo->prepare('SELECT COUNT(*) FROM `' . $bansTable . '` t WHERE ' . $where); if (str_contains($where, ':now_seconds')) $stmt->bindValue(':now_seconds', $now, PDO::PARAM_INT); $stmt->execute(); $active = (int) $stmt->fetchColumn(); }
    catch (Throwable) { $active = 0; }
    return ['active_bans' => $active];
}

function fetch_litebans_bans_page(string $search = '', int $page = 1): array {
    $config = mineacle_config(); $pdo = mineacle_db(); $litebans = $config['litebans'] ?? [];
    $bansTable = mineacle_table((string) (($litebans['bans_table'] ?? null) ?: 'litebans_bans')); $historyTable = mineacle_table((string) (($litebans['history_table'] ?? null) ?: 'litebans_history'));
    $cols = mineacle_table_columns($pdo, $bansTable); $limit = max(1, min(50, (int) ($config['page']['limit'] ?? 25))); $page = max(1, $page); $offset = ($page - 1) * $limit; $now = mineacle_database_now_seconds($pdo);
    $where = []; $params = []; $activeWhere = mineacle_active_condition($cols, 'b'); $where[] = $activeWhere; if (str_contains($activeWhere, ':now_seconds')) $params['now_seconds'] = $now;
    mineacle_add_ban_search($where, $params, $cols, $historyTable, $search); $whereSql = 'WHERE ' . implode(' AND ', $where);
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM `' . $bansTable . '` b ' . $whereSql); mineacle_bind($countStmt, $params); $countStmt->execute(); $total = (int) $countStmt->fetchColumn();
    $orderColumn = mineacle_column($cols, 'time') ?? mineacle_column($cols, 'id') ?? ''; $orderSql = $orderColumn === '' ? '' : 'ORDER BY b.`' . $orderColumn . '` DESC';
    $sql = 'SELECT ' . mineacle_ban_select_sql($pdo, $historyTable, $cols) . ' FROM `' . $bansTable . '` b ' . $whereSql . ' ' . $orderSql . ' LIMIT :limit OFFSET :offset';
    $params['limit'] = $limit; $params['offset'] = $offset; $stmt = $pdo->prepare($sql); mineacle_bind($stmt, $params); $stmt->execute();
    $bans = []; foreach ($stmt->fetchAll() as $row) $bans[] = mineacle_map_ban_row($row, $config, $now);
    $pages = max(1, (int) ceil($total / $limit));
    return ['bans' => $bans, 'stats' => fetch_litebans_stats(), 'pagination' => ['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>$pages,'has_prev'=>$page>1,'has_next'=>$page<$pages]];
}

function fetch_litebans_ban_detail(int $id): ?array {
    if ($id <= 0) return null;
    $config = mineacle_config(); $pdo = mineacle_db(); $litebans = $config['litebans'] ?? [];
    $bansTable = mineacle_table((string) (($litebans['bans_table'] ?? null) ?: 'litebans_bans')); $historyTable = mineacle_table((string) (($litebans['history_table'] ?? null) ?: 'litebans_history'));
    $cols = mineacle_table_columns($pdo, $bansTable); $idColumn = mineacle_column($cols, 'id'); if ($idColumn === null) return null;
    $now = mineacle_database_now_seconds($pdo); $activeWhere = mineacle_active_condition($cols, 'b');
    $sql = 'SELECT ' . mineacle_ban_select_sql($pdo, $historyTable, $cols) . ' FROM `' . $bansTable . '` b WHERE b.`' . $idColumn . '` = :id AND ' . $activeWhere . ' LIMIT 1';
    $stmt = $pdo->prepare($sql); $stmt->bindValue(':id', $id, PDO::PARAM_INT); if (str_contains($activeWhere, ':now_seconds')) $stmt->bindValue(':now_seconds', $now, PDO::PARAM_INT); $stmt->execute(); $row = $stmt->fetch();
    return $row ? mineacle_map_ban_row($row, $config, $now) : null;
}
