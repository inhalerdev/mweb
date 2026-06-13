<?php
/**
 * Mineacle bans-only website config
 *
 * This file intentionally reads database credentials from environment variables.
 * Do not hardcode DB passwords in files that can be committed or downloaded.
 */

return [
    'site' => [
        'name' => 'Mineacle Network',
        'ip' => getenv('SERVER_IP') ?: 'mineacle.net',
        'discord' => getenv('DISCORD_URL') ?: 'https://discord.gg/4xrYFxdSWg',
        'store' => getenv('STORE_URL') ?: 'https://store.mineacle.net',
        'vote' => getenv('VOTE_URL') ?: 'https://vote.mineacle.net',
        'support_email' => getenv('SUPPORT_EMAIL') ?: 'support@mineacle.net',

        // Replace with your real checkout route later.
        // Available placeholders: {id}, {uuid}, {username}
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

        'bans' => [
            'id' => 'id',
            'uuid' => 'uuid',
            'ip' => 'ip',
            'reason' => 'reason',
            'staff_name' => 'banned_by_name',
            'time' => 'time',
            'until' => 'until',
            'active' => 'active',
            'ipban' => 'ipban',
            'server_origin' => 'server_origin',
        ],

        'history' => [
            'uuid' => 'uuid',
            'name' => 'name',
            'date' => 'date',
        ],
    ],

    'payments' => [
        'temporary_unban_price' => getenv('TEMP_UNBAN_PRICE') ?: '$9.99',
        'permanent_unban_price' => getenv('PERM_UNBAN_PRICE') ?: '$19.99',
    ],

    'page' => [
        'limit' => (int) (getenv('BAN_PAGE_LIMIT') ?: 25),

        // Public page should normally show active punishments only.
        // Set SHOW_EXPIRED_BANS=true only if you intentionally want public history.
        'show_expired' => strtolower((string) getenv('SHOW_EXPIRED_BANS')) === 'true',
    ],

    'security' => [
        // Never enable in production unless actively debugging.
        'debug' => strtolower((string) getenv('APP_DEBUG')) === 'true',
    ],
];
