<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function mineacle_skin_url(string $usernameOrUuid): string {
    $safe = rawurlencode($usernameOrUuid !== '' ? $usernameOrUuid : 'Steve');
    return "https://mc-heads.net/avatar/{$safe}/128";
}

function format_litebans_time($ms): string {
    $ms = (int) $ms;
    if ($ms <= 0) {
        return 'Unknown';
    }

    return date('M j, Y g:i A', (int) floor($ms / 1000));
}

function format_litebans_duration($until): string {
    $until = (int) $until;

    if ($until <= 0) {
        return 'Permanent';
    }

    $nowMs = (int) round(microtime(true) * 1000);
    $remaining = max(0, $until - $nowMs);

    if ($remaining <= 0) {
        return 'Expired';
    }

    $days = (int) floor($remaining / 86400000);
    if ($days >= 1) {
        return $days . ' day' . ($days === 1 ? '' : 's');
    }

    $hours = (int) floor($remaining / 3600000);
    if ($hours >= 1) {
        return $hours . ' hour' . ($hours === 1 ? '' : 's');
    }

    $minutes = (int) floor($remaining / 60000);
    return max(1, $minutes) . ' minute' . ($minutes === 1 ? '' : 's');
}

function mineacle_unban_price(array $ban, array $config): string {
    $until = (int) ($ban['until'] ?? 0);
    return $until <= 0 ? $config['payments']['permanent_unban_price'] : $config['payments']['temporary_unban_price'];
}

function mineacle_unban_url(array $ban, array $config): string {
    return strtr($config['site']['unban_checkout_url'], [
        '{id}' => rawurlencode((string) ($ban['id'] ?? '')),
        '{uuid}' => rawurlencode((string) ($ban['uuid'] ?? '')),
        '{username}' => rawurlencode((string) ($ban['username'] ?? '')),
    ]);
}

function mineacle_public_ban(array $row, array $config): array {
    $nowMs = (int) round(microtime(true) * 1000);

    $until = (int) ($row['until'] ?? 0);
    $activeRaw = (int) ($row['active'] ?? 0);
    $active = $activeRaw === 1 && ($until <= 0 || $until > $nowMs);
    $ipban = (int) ($row['ipban'] ?? 0) === 1;

    $username = trim((string) ($row['username'] ?? ''));
    if ($username === '' || strtolower($username) === 'null') {
        $username = (string) ($row['uuid'] ?? 'Unknown');
    }

    if ($ipban) {
        $status = 'Permanently Banned';
        $statusType = 'ip';
        $canPay = false;
        $canDispute = false;
        $duration = 'Permanent';
        $type = 'IP Ban';
    } elseif ($active) {
        $status = 'Active';
        $statusType = 'active';
        $canPay = true;
        $canDispute = true;
        $duration = format_litebans_duration($until);
        $type = 'Player Ban';
    } else {
        $status = 'Expired';
        $statusType = 'expired';
        $canPay = false;
        $canDispute = true;
        $duration = format_litebans_duration($until);
        $type = 'Player Ban';
    }

    $ban = [
        'id' => (string) ($row['id'] ?? ''),
        'uuid' => (string) ($row['uuid'] ?? ''),
        'username' => $username,
        'reason' => (string) ($row['reason'] ?? 'No reason provided'),
        'staff' => (string) ($row['staff_name'] ?? 'Console'),
        'time' => (int) ($row['time'] ?? 0),
        'until' => $until,
        'date' => format_litebans_time($row['time'] ?? 0),
        'duration' => $duration,
        'type' => $type,
        'status' => $status,
        'status_type' => $statusType,
        'active' => $active,
        'ipban' => $ipban,
        'can_pay' => $canPay,
        'can_dispute' => $canDispute,
        'price' => '',
        'support_email' => $config['site']['support_email'],
        'discord' => $config['site']['discord'],
        'skin' => mineacle_skin_url((string) ($row['uuid'] ?? $username)),
        'appeal_id' => 'MCL-' . str_pad((string) ($row['id'] ?? '0'), 6, '0', STR_PAD_LEFT),
        'unban_url' => '',
    ];

    if ($canPay) {
        $ban['price'] = mineacle_unban_price($row, $config);
        $ban['unban_url'] = mineacle_unban_url($ban, $config);
    }

    return $ban;
}

function litebans_query_parts(array $config, string $search): array {
    $bActive = quote_ident(col($config, 'bans', 'active'));
    $bUntil = quote_ident(col($config, 'bans', 'until'));
    $hName = quote_ident(col($config, 'history', 'name'));
    $bUuid = quote_ident(col($config, 'bans', 'uuid'));

    $showExpired = (bool) ($config['page']['show_expired'] ?? false);
    $nowMs = (int) round(microtime(true) * 1000);
    $params = [];

    if ($showExpired) {
        $where = '1=1';
    } else {
        $where = "b.{$bActive} = 1 AND (b.{$bUntil} <= 0 OR b.{$bUntil} > :now)";
        $params[':now'] = $nowMs;
    }

    $search = trim($search);
    if ($search !== '') {
        $where .= " AND (h.{$hName} LIKE :search OR b.{$bUuid} LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    return [$where, $params];
}

function fetch_litebans_bans_page(string $search = '', int $page = 1): array {
    $config = mineacle_config();
    $pdo = mineacle_pdo();

    $bansTable = quote_ident(table_name($config, 'bans_table'));
    $historyTable = quote_ident(table_name($config, 'history_table'));

    $bId = quote_ident(col($config, 'bans', 'id'));
    $bUuid = quote_ident(col($config, 'bans', 'uuid'));
    $bIp = quote_ident(col($config, 'bans', 'ip'));
    $bReason = quote_ident(col($config, 'bans', 'reason'));
    $bStaff = quote_ident(col($config, 'bans', 'staff_name'));
    $bTime = quote_ident(col($config, 'bans', 'time'));
    $bUntil = quote_ident(col($config, 'bans', 'until'));
    $bActive = quote_ident(col($config, 'bans', 'active'));
    $bIpban = quote_ident(col($config, 'bans', 'ipban'));

    $hUuid = quote_ident(col($config, 'history', 'uuid'));
    $hName = quote_ident(col($config, 'history', 'name'));
    $hDate = quote_ident(col($config, 'history', 'date'));

    $limit = max(1, min((int) ($config['page']['limit'] ?? 25), 100));
    $page = max(1, $page);
    $offset = ($page - 1) * $limit;

    [$where, $params] = litebans_query_parts($config, $search);

    $join = "
        LEFT JOIN {$historyTable} h
          ON h.{$hUuid} = b.{$bUuid}
          AND h.{$hDate} = (
            SELECT MAX(h2.{$hDate})
            FROM {$historyTable} h2
            WHERE h2.{$hUuid} = b.{$bUuid}
          )
    ";

    $countSql = "
        SELECT COUNT(*) AS total
        FROM {$bansTable} b
        {$join}
        WHERE {$where}
    ";

    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, $key === ':now' ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = (int) ($countStmt->fetch()['total'] ?? 0);

    $totalPages = max(1, (int) ceil($total / $limit));
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $limit;
    }

    $sql = "
        SELECT
          b.{$bId} AS id,
          b.{$bUuid} AS uuid,
          b.{$bIp} AS ip,
          b.{$bReason} AS reason,
          b.{$bStaff} AS staff_name,
          b.{$bTime} AS time,
          b.{$bUntil} AS until,
          b.{$bActive} AS active,
          b.{$bIpban} AS ipban,
          COALESCE(h.{$hName}, b.{$bUuid}) AS username
        FROM {$bansTable} b
        {$join}
        WHERE {$where}
        ORDER BY b.{$bTime} DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, $key === ':now' ? PDO::PARAM_INT : PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $bans = array_map(
        fn(array $row) => mineacle_public_ban($row, $config),
        $stmt->fetchAll()
    );

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

// Backwards-compatible helper.
function fetch_litebans_bans(string $search = ''): array {
    return fetch_litebans_bans_page($search, 1)['bans'];
}
