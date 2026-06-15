<?php
declare(strict_types=1);

return [
    'site' => [
        'name' => 'Mineacle Network',
        'ip' => getenv('SERVER_IP') ?: 'mineacle.net',
        'discord' => getenv('DISCORD_URL') ?: 'https://discord.gg/4xrYFxdSWg',
        'home' => getenv('HOME_URL') ?: 'https://mineacle.net/home',
        'store' => getenv('STORE_URL') ?: 'https://store.mineacle.net',
        'vote' => getenv('VOTE_URL') ?: 'https://vote.mineacle.net',
        'support_email' => getenv('SUPPORT_EMAIL') ?: 'support@mineacle.net',
        'timezone' => getenv('APP_TIMEZONE') ?: 'America/New_York',
        'server_online' => strtolower((string) getenv('SERVER_ONLINE')) !== 'false',
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
    ],
    'payments' => [
        'temporary_unban_price' => getenv('TEMP_UNBAN_PRICE') ?: '$9.99',
        'permanent_unban_price' => getenv('PERM_UNBAN_PRICE') ?: '$19.99',
    ],
    'page' => [
        'limit' => (int) (getenv('BAN_PAGE_LIMIT') ?: 25),
    ],
    'security' => [
        'debug' => strtolower((string) getenv('APP_DEBUG')) === 'true',
    ],
];
