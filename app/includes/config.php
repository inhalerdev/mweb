<?php
declare(strict_types=1);

return [
    'site' => [
        'name' => 'Mineacle Network',
        'ip' => getenv('SERVER_IP') ?: 'mineacle.net',
        'discord' => getenv('DISCORD_URL') ?: 'https://discord.gg/VwbwWftefM',
        'discord_invite_code' => getenv('DISCORD_INVITE_CODE') ?: 'VwbwWftefM',
        'home' => getenv('HOME_URL') ?: 'https://mineacle.net',
        'store' => getenv('STORE_URL') ?: 'https://store.mineacle.net',
        'x' => getenv('X_URL') ?: 'https://x.com/mineaclenetwork',
        'vote' => getenv('VOTE_URL') ?: 'https://vote.mineacle.net',
        'bans' => getenv('BANS_URL') ?: 'https://bans.mineacle.net',
        'support_email' => getenv('SUPPORT_EMAIL') ?: 'support@mineacle.net',
        'database_timezone' => getenv('DATABASE_TIMEZONE') ?: 'UTC',
        'display_timezone' => getenv('DISPLAY_TIMEZONE') ?: 'America/Chicago',
        'appeal_email' => getenv('APPEAL_EMAIL') ?: 'support@mineacle.net',
        'appeal_discord' => getenv('APPEAL_DISCORD') ?: (getenv('DISCORD_URL') ?: 'https://discord.gg/VwbwWftefM'),
        'unban_checkout_url' => getenv('UNBAN_CHECKOUT_URL') ?: 'https://store.mineacle.net/checkout/unban?ban={id}&uuid={uuid}&username={username}',
    ],
    'mysql' => [
        'host' => getenv('DB_HOST'),
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_NAME'),
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
        'charset' => 'utf8mb4',
    ],
    'litebans' => [
        'bans_table' => getenv('LITEBANS_BANS_TABLE') ?: 'litebans_bans',
        'history_table' => getenv('LITEBANS_HISTORY_TABLE') ?: 'litebans_history',
        'mutes_table' => getenv('LITEBANS_MUTES_TABLE') ?: 'litebans_mutes',
        'warnings_table' => getenv('LITEBANS_WARNINGS_TABLE') ?: 'litebans_warnings',
        'kicks_table' => getenv('LITEBANS_KICKS_TABLE') ?: 'litebans_kicks',
    ],
    'minecraft_status' => [
        'public_host' => getenv('MC_PUBLIC_HOST') ?: 'mineacle.net',
        'public_port' => (int) (getenv('MC_PUBLIC_PORT') ?: 25565),
        'query_host' => getenv('MC_QUERY_HOST') ?: '59-GN03.DFW.lagless.gg',
        'query_port' => (int) (getenv('MC_QUERY_PORT') ?: 19136),
        'timeout' => (float) (getenv('MC_QUERY_TIMEOUT') ?: 1.5),
    ],
    'payments' => [
        'permanent_unban_price' => getenv('PERM_UNBAN_PRICE') ?: '$19.99',
    ],
    'page' => [
        'limit' => max(10, min(50, (int) (getenv('BAN_PAGE_LIMIT') ?: 25))),
    ],
    'cache' => [
        'enabled' => strtolower((string) getenv('APP_CACHE')) !== 'false',
        'list_ttl' => (int) (getenv('BAN_LIST_CACHE_TTL') ?: 12),
        'detail_ttl' => (int) (getenv('BAN_DETAIL_CACHE_TTL') ?: 45),
        'status_ttl' => (int) (getenv('SERVER_STATUS_CACHE_TTL') ?: 20),
    ],
    'security' => [
        'debug' => strtolower((string) getenv('APP_DEBUG')) === 'true',
    ],
];
