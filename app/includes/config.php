<?php
declare(strict_types=1);

return [
    'site' => [
        'name' => 'Mineacle Network',
        'ip' => getenv('SERVER_IP') ?: 'mineacle.net',
        'discord' => getenv('DISCORD_URL') ?: 'https://discord.gg/VwbwWftefM',
        'discord_invite_code' => getenv('DISCORD_INVITE_CODE') ?: 'VwbwWftefM',
        'home' => getenv('HOME_URL') ?: 'https://mineacle.net/home',
        'store' => getenv('STORE_URL') ?: 'https://store.mineacle.net',
        'x' => getenv('X_URL') ?: 'https://x.com/mineaclenetwork',
        'vote' => getenv('VOTE_URL') ?: 'https://vote.mineacle.net',
        'bans' => getenv('BANS_URL') ?: 'https://bans.mineacle.net',
        'support_email' => getenv('SUPPORT_EMAIL') ?: 'support@mineacle.net',
        'server_online' => strtolower((string) getenv('SERVER_ONLINE')) !== 'false',
        'database_timezone' => getenv('DATABASE_TIMEZONE') ?: 'UTC',
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

    'vote_sites' => [
        [
            'name' => 'Minecraft Server List',
            'url' => getenv('VOTE_SITE_1') ?: '#',
            'reward' => 'Vote Key',
        ],
        [
            'name' => 'Top Minecraft Servers',
            'url' => getenv('VOTE_SITE_2') ?: '#',
            'reward' => 'Vote Key',
        ],
        [
            'name' => 'Minecraft MP',
            'url' => getenv('VOTE_SITE_3') ?: '#',
            'reward' => 'Vote Key',
        ],
        [
            'name' => 'Planet Minecraft',
            'url' => getenv('VOTE_SITE_4') ?: '#',
            'reward' => 'Vote Key',
        ],
    ],
];
