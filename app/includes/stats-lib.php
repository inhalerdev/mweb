<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function mineacle_stats_table_sql(string $table): ?string
{
    if (!in_array($table, ['mineacle_web_profiles', 'mineacle_web_teams'], true) || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return null;
    }

    return '`' . $table . '`';
}

function mineacle_stats_column_sql(string $column): ?string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return null;
    }

    return '`' . $column . '`';
}

function mineacle_stats_columns(PDO $pdo, string $tableSql): array
{
    $columns = [];
    $rows = $pdo->query('SHOW COLUMNS FROM ' . $tableSql)->fetchAll();

    foreach ($rows as $row) {
        $field = (string) ($row['Field'] ?? '');

        if ($field !== '' && mineacle_stats_column_sql($field) !== null) {
            $columns[strtolower($field)] = $field;
        }
    }

    return $columns;
}

function mineacle_stats_select_list(array $columns): string
{
    $defaults = [
        'uuid' => "''",
        'username' => "''",
        'display_name' => "''",
        'rank_name' => "''",
        'rank_weight' => '0',
        'team_id' => "''",
        'team_name' => "''",
        'team_role' => "''",
        'team_joined_at' => '0',
        'balance_cents' => '0',
        'balance_formatted' => "''",
        'playtime_seconds' => '0',
        'playtime_formatted' => "''",
        'kills' => '0',
        'deaths' => '0',
        'kd_ratio' => '0',
        'money_rank' => '0',
        'kills_rank' => '0',
        'playtime_rank' => '0',
        'first_joined_at' => '0',
        'last_seen' => '0',
        'online' => '0',
        'updated_at' => '0',
    ];
    $select = [];

    foreach ($defaults as $column => $default) {
        $aliasSql = mineacle_stats_column_sql($column);
        $actual = $columns[strtolower($column)] ?? null;
        $actualSql = is_string($actual) ? mineacle_stats_column_sql($actual) : null;

        $select[] = ($actualSql ?? $default) . ' AS ' . $aliasSql;
    }

    return implode(', ', $select);
}

function mineacle_stats_column_from_candidates(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        $actual = $columns[strtolower((string) $candidate)] ?? null;

        if (is_string($actual) && mineacle_stats_column_sql($actual) !== null) {
            return $actual;
        }
    }

    return null;
}

function mineacle_stats_team_select_list(array $columns): string
{
    $map = [
        'team_id' => [['team_id', 'id', 'uuid'], "''"],
        'name' => [['team_name', 'name', 'display_name'], "''"],
        'tag' => [['tag', 'team_tag', 'prefix'], "''"],
        'owner_uuid' => [['owner_uuid', 'founder_uuid', 'leader_uuid', 'owner'], "''"],
        'owner_name' => [['owner_name', 'leader_name', 'leader', 'owner_username'], "''"],
        'members' => [['members', 'member_count', 'total_members', 'players'], '0'],
        'online_members' => [['online_members', 'online_count', 'members_online', 'online'], '0'],
        'balance_cents' => [['total_balance_cents', 'balance_cents', 'bank_balance_cents', 'team_balance_cents'], '0'],
        'balance_formatted' => [['total_balance_formatted', 'balance_formatted', 'bank_balance_formatted', 'team_balance_formatted'], "''"],
        'balance' => [['balance', 'bank_balance', 'team_balance'], '0'],
        'kills' => [['kills', 'team_kills', 'total_kills'], '0'],
        'deaths' => [['deaths', 'team_deaths', 'total_deaths'], '0'],
        'kd_ratio' => [['kd_ratio', 'kdr', 'team_kd_ratio'], '0'],
        'capital_rank' => [['capital_rank', 'money_rank', 'balance_rank'], '0'],
        'kd_rank' => [['kd_rank', 'kdr_rank'], '0'],
        'playtime_seconds' => [['playtime_seconds', 'total_playtime_seconds', 'team_playtime_seconds'], '0'],
        'wins' => [['wins', 'team_wins'], '0'],
        'losses' => [['losses', 'team_losses'], '0'],
        'points' => [['points', 'score', 'rating', 'elo'], '0'],
        'created_at' => [['created_at', 'created', 'founded_at'], '0'],
        'updated_at' => [['updated_at', 'updated', 'last_updated'], '0'],
    ];
    $select = [];

    foreach ($map as $alias => [$candidates, $default]) {
        $actual = mineacle_stats_column_from_candidates($columns, $candidates);
        $actualSql = is_string($actual) ? mineacle_stats_column_sql($actual) : null;
        $aliasSql = mineacle_stats_column_sql((string) $alias);

        $select[] = ($actualSql ?? $default) . ' AS ' . $aliasSql;
    }

    return implode(', ', $select);
}

function mineacle_stats_profile_by_query(string $query): ?array
{
    $pdo = mineacle_core_db();

    if (!$pdo instanceof PDO) {
        throw new RuntimeException('Stats database is not connected.');
    }

    $config = mineacle_config();
    $tables = $config['tables'] ?? [];
    $table = (string) ($tables['player_profiles'] ?? 'mineacle_web_profiles');
    $tableSql = mineacle_stats_table_sql($table);

    if ($tableSql === null) {
        throw new RuntimeException('Player profiles table is not available.');
    }

    $columns = mineacle_stats_columns($pdo, $tableSql);
    $select = mineacle_stats_select_list($columns);
    $where = [];
    $params = [];

    foreach (['uuid', 'username', 'display_name'] as $column) {
        $actual = $columns[$column] ?? null;
        $columnSql = is_string($actual) ? mineacle_stats_column_sql($actual) : null;

        if ($columnSql === null) {
            continue;
        }

        $param = ':' . $column;
        $where[] = 'LOWER(' . $columnSql . ') = LOWER(' . $param . ')';
        $params[$param] = $query;
    }

    if ($where === []) {
        return null;
    }

    $sql = 'SELECT ' . $select . ' FROM ' . $tableSql . ' WHERE ' . implode(' OR ', $where) . ' LIMIT 1';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $row = $statement->fetch();

    if (!is_array($row)) {
        return null;
    }

    $row['skin'] = mineacle_stats_skin_assets((string) ($row['uuid'] ?? ''), mineacle_stats_username($row));
    $row['punishment_status'] = mineacle_stats_punishment_status($pdo, (string) ($row['uuid'] ?? ''));

    return $row;
}

function mineacle_stats_players(int $limit = 25, int $offset = 0, string $sort = 'playtime', string $search = ''): array
{
    $pdo = mineacle_core_db();

    if (!$pdo instanceof PDO) {
        throw new RuntimeException('Stats database is not connected.');
    }

    $config = mineacle_config();
    $tables = $config['tables'] ?? [];
    $table = (string) ($tables['player_profiles'] ?? 'mineacle_web_profiles');
    $tableSql = mineacle_stats_table_sql($table);

    if ($tableSql === null) {
        throw new RuntimeException('Player profiles table is not available.');
    }

    $columns = mineacle_stats_columns($pdo, $tableSql);
    $select = mineacle_stats_select_list($columns);
    $allowedSorts = [
        'playtime' => 'playtime_seconds',
        'money' => 'balance_cents',
        'kd' => 'kd_ratio',
        'kills' => 'kills',
        'deaths' => 'deaths',
        'rank' => 'rank_weight',
        'team' => 'team_name',
        'first_joined' => 'first_joined_at',
        'last_seen' => 'last_seen',
        'online' => 'online',
        'updated' => 'updated_at',
    ];
    $sortColumn = $allowedSorts[$sort] ?? 'playtime_seconds';
    $orderColumn = isset($columns[$sortColumn]) ? mineacle_stats_column_sql($columns[$sortColumn]) : null;
    $usernameSql = isset($columns['username']) ? mineacle_stats_column_sql($columns['username']) : null;
    $searchColumns = [];

    foreach (['username', 'display_name', 'team_name'] as $column) {
        if (isset($columns[$column])) {
            $columnSql = mineacle_stats_column_sql($columns[$column]);

            if ($columnSql !== null) {
                $searchColumns[] = $columnSql;
            }
        }
    }

    $limit = max(1, min(200, $limit));
    $offset = max(0, $offset);
    $search = trim(substr($search, 0, 64));
    $params = [];
    $sql = 'SELECT ' . $select . ' FROM ' . $tableSql;

    if ($search !== '' && $searchColumns !== []) {
        $searchParts = [];

        foreach ($searchColumns as $index => $columnSql) {
            $param = ':search' . $index;
            $searchParts[] = $columnSql . ' LIKE ' . $param;
            $params[$param] = '%' . $search . '%';
        }

        $sql .= ' WHERE ' . implode(' OR ', $searchParts);
    }

    $order = [];

    if ($orderColumn !== null) {
        $order[] = $orderColumn . ' DESC';
    }

    if ($usernameSql !== null) {
        $order[] = $usernameSql . ' ASC';
    }

    if ($order === []) {
        $order[] = '1 ASC';
    }

    $sql .= ' ORDER BY ' . implode(', ', $order) . ' LIMIT :limit OFFSET :offset';
    $statement = $pdo->prepare($sql);

    foreach ($params as $param => $value) {
        $statement->bindValue($param, $value, PDO::PARAM_STR);
    }

    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $players = [];

    foreach ($statement->fetchAll() as $row) {
        if (!is_array($row)) {
            continue;
        }

        $row['skin'] = mineacle_stats_skin_assets((string) ($row['uuid'] ?? ''), mineacle_stats_username($row));
        $row['punishment_status'] = mineacle_stats_empty_status();
        $players[] = $row;
    }

    return $players;
}

function mineacle_stats_teams(int $limit = 50, int $offset = 0, string $sort = 'kd', string $search = ''): array
{
    $pdo = mineacle_core_db();

    if (!$pdo instanceof PDO) {
        throw new RuntimeException('Stats database is not connected.');
    }

    $config = mineacle_config();
    $tables = $config['tables'] ?? [];
    $table = (string) ($tables['teams'] ?? 'mineacle_web_teams');
    $tableSql = mineacle_stats_table_sql($table);

    if ($tableSql === null) {
        throw new RuntimeException('Teams table is not available.');
    }

    $columns = mineacle_stats_columns($pdo, $tableSql);
    $select = mineacle_stats_team_select_list($columns);
    $sortCandidates = [
        'kd' => ['kd_rank', 'kdr_rank', 'kd_ratio', 'kdr', 'team_kd_ratio'],
        'capital' => ['capital_rank', 'money_rank', 'balance_rank', 'total_balance_cents', 'balance_cents', 'bank_balance_cents', 'team_balance_cents'],
        'points' => ['points', 'score', 'rating', 'elo'],
        'balance' => ['total_balance_cents', 'balance_cents', 'bank_balance_cents', 'team_balance_cents', 'balance', 'bank_balance', 'team_balance'],
        'kills' => ['kills', 'team_kills', 'total_kills'],
        'members' => ['members', 'member_count', 'total_members', 'players'],
        'wins' => ['wins', 'team_wins'],
        'playtime' => ['playtime_seconds', 'total_playtime_seconds', 'team_playtime_seconds'],
        'updated' => ['updated_at', 'updated', 'last_updated'],
        'name' => ['team_name', 'name', 'display_name'],
    ];
    $sort = array_key_exists($sort, $sortCandidates) ? $sort : 'kd';
    $sortColumn = mineacle_stats_column_from_candidates($columns, $sortCandidates[$sort]);
    $orderColumn = is_string($sortColumn) ? mineacle_stats_column_sql($sortColumn) : null;
    $nameColumn = mineacle_stats_column_from_candidates($columns, ['team_name', 'name', 'display_name']);
    $nameSql = is_string($nameColumn) ? mineacle_stats_column_sql($nameColumn) : null;
    $killsColumn = mineacle_stats_column_from_candidates($columns, ['total_kills', 'kills', 'team_kills']);
    $killsSql = is_string($killsColumn) ? mineacle_stats_column_sql($killsColumn) : null;
    $balanceColumn = mineacle_stats_column_from_candidates($columns, ['total_balance_cents', 'balance_cents', 'bank_balance_cents', 'team_balance_cents']);
    $balanceSql = is_string($balanceColumn) ? mineacle_stats_column_sql($balanceColumn) : null;
    $searchColumns = [];

    foreach (['team_name', 'name', 'display_name', 'tag', 'team_tag', 'owner_name', 'leader_name'] as $column) {
        if (isset($columns[$column])) {
            $columnSql = mineacle_stats_column_sql($columns[$column]);

            if ($columnSql !== null) {
                $searchColumns[] = $columnSql;
            }
        }
    }

    $limit = max(1, min(50, $limit));
    $offset = max(0, $offset);
    $search = trim(substr($search, 0, 64));
    $params = [];
    $sql = 'SELECT ' . $select . ' FROM ' . $tableSql;

    if ($search !== '' && $searchColumns !== []) {
        $searchParts = [];

        foreach ($searchColumns as $index => $columnSql) {
            $param = ':search' . $index;
            $searchParts[] = $columnSql . ' LIKE ' . $param;
            $params[$param] = '%' . $search . '%';
        }

        $sql .= ' WHERE ' . implode(' OR ', $searchParts);
    }

    $order = [];
    $appendOrder = static function (string $expression) use (&$order): void {
        if (!in_array($expression, $order, true)) {
            $order[] = $expression;
        }
    };

    if ($sort === 'kd') {
        $kdRankColumn = mineacle_stats_column_from_candidates($columns, ['kd_rank', 'kdr_rank']);
        $kdRankSql = is_string($kdRankColumn) ? mineacle_stats_column_sql($kdRankColumn) : null;

        if ($kdRankSql !== null) {
            $appendOrder('CASE WHEN ' . $kdRankSql . ' > 0 THEN 0 ELSE 1 END ASC');
            $appendOrder($kdRankSql . ' ASC');
        } elseif ($orderColumn !== null) {
            $appendOrder($orderColumn . ' DESC');
        }

        if ($killsSql !== null) {
            $appendOrder($killsSql . ' DESC');
        }

        if ($balanceSql !== null) {
            $appendOrder($balanceSql . ' DESC');
        }
    } elseif ($sort === 'capital') {
        $capitalRankColumn = mineacle_stats_column_from_candidates($columns, ['capital_rank', 'money_rank', 'balance_rank']);
        $capitalRankSql = is_string($capitalRankColumn) ? mineacle_stats_column_sql($capitalRankColumn) : null;

        if ($capitalRankSql !== null) {
            $appendOrder('CASE WHEN ' . $capitalRankSql . ' > 0 THEN 0 ELSE 1 END ASC');
            $appendOrder($capitalRankSql . ' ASC');
        } elseif ($balanceSql !== null) {
            $appendOrder($balanceSql . ' DESC');
        } elseif ($orderColumn !== null) {
            $appendOrder($orderColumn . ' DESC');
        }

        if ($killsSql !== null) {
            $appendOrder($killsSql . ' DESC');
        }
    } elseif ($sort === 'name' && $orderColumn !== null) {
        $appendOrder($orderColumn . ' ASC');
    } elseif ($orderColumn !== null) {
        $appendOrder($orderColumn . ' DESC');
    }

    if ($nameSql !== null) {
        $appendOrder($nameSql . ' ASC');
    }

    if ($order === []) {
        $order[] = '1 ASC';
    }

    $sql .= ' ORDER BY ' . implode(', ', $order) . ' LIMIT :limit OFFSET :offset';
    $statement = $pdo->prepare($sql);

    foreach ($params as $param => $value) {
        $statement->bindValue($param, $value, PDO::PARAM_STR);
    }

    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $teams = [];

    foreach ($statement->fetchAll() as $index => $row) {
        if (!is_array($row)) {
            continue;
        }

        $row['rank'] = $offset + $index + 1;
        $teams[] = $row;
    }

    return $teams;
}

function mineacle_players(int $limit = 25, int $offset = 0, string $sort = 'playtime', string $search = ''): array
{
    return mineacle_stats_players($limit, $offset, $sort, $search);
}

function mineacle_player(string $query): ?array
{
    return mineacle_stats_profile_by_query($query);
}

function mineacle_stats_username(array $player): string
{
    $username = trim((string) ($player['username'] ?? ''));

    return $username !== '' ? $username : 'unknown';
}

function mineacle_stats_display_name(array $player): string
{
    $displayName = trim((string) ($player['display_name'] ?? ''));

    return $displayName !== '' ? $displayName : mineacle_stats_username($player);
}

function mineacle_stats_rank_name(array $player): string
{
    $rankName = trim((string) ($player['rank_name'] ?? ''));

    return $rankName !== '' ? $rankName : 'Member';
}

function mineacle_stats_team_name(array $player): string
{
    $teamName = trim((string) ($player['team_name'] ?? ''));

    return $teamName !== '' ? $teamName : 'No Team';
}

function mineacle_stats_team_role(array $player): string
{
    $teamRole = trim((string) ($player['team_role'] ?? ''));

    return $teamRole !== '' ? $teamRole : 'Member';
}

function mineacle_stats_int(mixed $value): int
{
    return is_numeric($value) ? (int) $value : 0;
}

function mineacle_stats_float(mixed $value): float
{
    return is_numeric($value) ? (float) $value : 0.0;
}

function mineacle_stats_money_label(array $player): string
{
    $formatted = trim((string) ($player['balance_formatted'] ?? ''));

    if ($formatted !== '') {
        return $formatted;
    }

    return '$' . number_format(mineacle_stats_int($player['balance_cents'] ?? 0) / 100, 2);
}

function mineacle_stats_playtime_label(array $player): string
{
    $formatted = trim((string) ($player['playtime_formatted'] ?? ''));

    if ($formatted !== '') {
        return $formatted;
    }

    $seconds = max(0, mineacle_stats_int($player['playtime_seconds'] ?? 0));
    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);

    if ($days > 0) {
        return $days . 'd ' . $hours . 'h';
    }

    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'm';
    }

    return max(1, $minutes) . 'm';
}

function mineacle_stats_rank_label(mixed $rank): string
{
    $value = mineacle_stats_int($rank);

    return $value > 0 ? '#' . $value : 'Unranked';
}

function mineacle_stats_date_label(mixed $millis): string
{
    $value = mineacle_stats_int($millis);

    if ($value <= 0) {
        return 'Unknown';
    }

    return date('M j, Y', (int) floor($value / 1000));
}

function mineacle_stats_online(array $player): bool
{
    return mineacle_stats_int($player['online'] ?? 0) === 1;
}

function mineacle_stats_last_seen_label(array $player): string
{
    return mineacle_stats_online($player) ? 'Online now' : mineacle_stats_date_label($player['last_seen'] ?? 0);
}

function mineacle_stats_skin_identifier(?string $uuid, string $username, bool $uuidOnly = false): ?string
{
    $uuidValue = strtolower(trim((string) $uuid));
    $uuidKey = preg_replace('/[^a-f0-9]/', '', $uuidValue);

    if (is_string($uuidKey) && $uuidKey !== '') {
        return rawurlencode($uuidKey);
    }

    if ($uuidOnly || $username === '') {
        return null;
    }

    return rawurlencode($username);
}

function mineacle_stats_skin_size(mixed $value, int $fallback): int
{
    $size = (int) $value;

    return max(16, min(600, $size > 0 ? $size : $fallback));
}

function mineacle_stats_skin_assets(?string $uuid, string $username): array
{
    $config = mineacle_config();
    $skin = is_array($config['skins'] ?? null) ? $config['skins'] : [];
    $provider = strtolower(trim((string) ($skin['provider'] ?? 'mineskin')));
    $headSize = mineacle_stats_skin_size($skin['head_size'] ?? null, 64);
    $chestSize = mineacle_stats_skin_size($skin['chest_size'] ?? null, 180);

    if (!in_array($provider, ['mineskin', 'minotar', 'mc-heads', 'crafatar'], true)) {
        $provider = 'mineskin';
    }

    $identifier = mineacle_stats_skin_identifier($uuid, $username, $provider === 'crafatar');

    if ($identifier === null) {
        return [
            'provider' => $provider,
            'head' => null,
            'chest' => null,
        ];
    }

    if ($provider === 'minotar') {
        return [
            'provider' => $provider,
            'head' => 'https://minotar.net/helm/' . $identifier . '/' . $headSize . '.png',
            'chest' => 'https://minotar.net/armor/bust/' . $identifier . '/' . $chestSize . '.png',
        ];
    }

    if ($provider === 'mc-heads') {
        return [
            'provider' => $provider,
            'head' => 'https://mc-heads.net/avatar/' . $identifier . '/' . $headSize . '.png',
            'chest' => 'https://mc-heads.net/body/' . $identifier . '/' . $chestSize . '.png',
        ];
    }

    if ($provider === 'crafatar') {
        return [
            'provider' => $provider,
            'head' => 'https://crafatar.com/renders/head/' . $identifier . '?scale=4&overlay',
            'chest' => 'https://crafatar.com/renders/body/' . $identifier . '?scale=4&overlay',
        ];
    }

    return [
        'provider' => $provider,
        'head' => 'https://mineskin.eu/helm/' . $identifier . '/' . $headSize . '.png',
        'chest' => 'https://mineskin.eu/armor/bust/' . $identifier . '/' . $chestSize . '.png',
    ];
}

function mineacle_stats_empty_punishment(string $type): array
{
    return [
        'active' => false,
        'type' => $type,
        'kind' => null,
        'permanent' => false,
        'until' => null,
        'reason' => null,
    ];
}

function mineacle_stats_empty_status(): array
{
    return [
        'ban' => mineacle_stats_empty_punishment('ban'),
        'mute' => mineacle_stats_empty_punishment('mute'),
        'search_state' => 'clear',
    ];
}

function mineacle_stats_bool(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return (int) $value === 1;
    }

    if (is_string($value)) {
        if ($value === "\x01") {
            return true;
        }

        if ($value === "\x00") {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    return false;
}

function mineacle_stats_litebans_status(PDO $pdo, string $table, string $type, string $uuid): array
{
    if (!in_array($table, ['litebans_bans', 'litebans_mutes'], true)) {
        return mineacle_stats_empty_punishment($type);
    }

    $tableSql = mineacle_stats_column_sql($table);

    if ($tableSql === null) {
        return mineacle_stats_empty_punishment($type);
    }

    try {
        $columns = mineacle_stats_columns($pdo, $tableSql);
    } catch (Throwable) {
        return mineacle_stats_empty_punishment($type);
    }

    $uuidColumn = $columns['uuid'] ?? null;
    $uuidSql = is_string($uuidColumn) ? mineacle_stats_column_sql($uuidColumn) : null;

    if ($uuidSql === null) {
        return mineacle_stats_empty_punishment($type);
    }

    $uuidKey = preg_replace('/[^a-f0-9]/', '', strtolower($uuid));

    if (!is_string($uuidKey) || $uuidKey === '') {
        return mineacle_stats_empty_punishment($type);
    }

    $untilColumn = $columns['until'] ?? null;
    $timeColumn = $columns['time'] ?? null;
    $reasonColumn = $columns['reason'] ?? null;
    $activeColumn = $columns['active'] ?? null;
    $removedColumn = $columns['removed_by_date'] ?? ($columns['removed_at'] ?? null);
    $select = [
        mineacle_stats_column_sql((string) $untilColumn) ? mineacle_stats_column_sql((string) $untilColumn) . ' AS until_value' : 'NULL AS until_value',
        mineacle_stats_column_sql((string) $timeColumn) ? mineacle_stats_column_sql((string) $timeColumn) . ' AS time_value' : 'NULL AS time_value',
        mineacle_stats_column_sql((string) $reasonColumn) ? mineacle_stats_column_sql((string) $reasonColumn) . ' AS reason_value' : 'NULL AS reason_value',
        mineacle_stats_column_sql((string) $activeColumn) ? mineacle_stats_column_sql((string) $activeColumn) . ' AS active_value' : 'NULL AS active_value',
        mineacle_stats_column_sql((string) $removedColumn) ? mineacle_stats_column_sql((string) $removedColumn) . ' AS removed_value' : 'NULL AS removed_value',
    ];
    $orderSql = is_string($timeColumn) ? mineacle_stats_column_sql($timeColumn) : null;
    $uuidKeySql = sprintf("REPLACE(LOWER(%s), '-', '')", $uuidSql);
    $sql = 'SELECT ' . implode(', ', $select) . ' FROM ' . $tableSql
        . ' WHERE ' . $uuidKeySql . ' = :uuid';

    if ($orderSql !== null) {
        $sql .= ' ORDER BY ' . $orderSql . ' DESC';
    }

    $sql .= ' LIMIT 10';
    $statement = $pdo->prepare($sql);
    $statement->execute([':uuid' => $uuidKey]);
    $now = time() * 1000;

    foreach ($statement->fetchAll() as $row) {
        $until = mineacle_stats_int($row['until_value'] ?? 0);
        $removed = mineacle_stats_int($row['removed_value'] ?? 0);
        $active = array_key_exists('active_value', $row) && $row['active_value'] !== null
            ? mineacle_stats_bool($row['active_value'])
            : true;

        if ($removed > 0 || ($until > 0 && $until <= $now)) {
            continue;
        }

        if (!$active) {
            continue;
        }

        $kind = $until > $now ? 'temporary' : 'permanent';
        $reason = trim((string) ($row['reason_value'] ?? ''));

        return [
            'active' => true,
            'type' => $type,
            'kind' => $kind,
            'permanent' => $kind === 'permanent',
            'until' => $kind === 'temporary' ? $until : null,
            'reason' => $reason !== '' ? $reason : null,
        ];
    }

    return mineacle_stats_empty_punishment($type);
}

function mineacle_stats_punishment_status(PDO $pdo, string $uuid): array
{
    $litebansPdo = mineacle_litebans_db();

    if (!$litebansPdo instanceof PDO) {
        return mineacle_stats_empty_status();
    }

    $config = mineacle_config();
    $tables = $config['tables'] ?? [];
    $ban = mineacle_stats_litebans_status($litebansPdo, (string) ($tables['litebans_bans'] ?? 'litebans_bans'), 'ban', $uuid);
    $mute = mineacle_stats_litebans_status($litebansPdo, (string) ($tables['litebans_mutes'] ?? 'litebans_mutes'), 'mute', $uuid);
    $state = 'clear';

    if ($ban['active']) {
        $state = $ban['kind'] === 'temporary' ? 'temporary_ban' : 'permanent_ban';
    } elseif ($mute['active']) {
        $state = $mute['kind'] === 'temporary' ? 'temporary_mute' : 'permanent_mute';
    }

    return [
        'ban' => $ban,
        'mute' => $mute,
        'search_state' => $state,
    ];
}
