<?php
/**
 * Mineacle LiteBans Web Config
 *
 * This file uses your host's environment variables:
 * DB_HOST
 * DB_NAME
 * DB_PASSWORD
 * DB_PORT
 * DB_USERNAME
 */

return [
    'site' => [
        'name' => 'Mineacle Network',
        'ip' => 'mineacle.net',
        'discord' => 'https://discord.gg/4xrYFxdSWg',
        'support_email' => 'support@mineacle.net',

        // Later, replace this with your real checkout URL
        // Available placeholders: {id}, {uuid}, {username}
        'unban_checkout_url' => 'https://store.mineacle.net/checkout/unban?ban={id}&uuid={uuid}&username={username}',
    ],

    'mysql' => [
        'host' => getenv('DB_HOST'),
        'port' => (int) getenv('DB_PORT'),
        'database' => getenv('DB_NAME'),
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
        'charset' => 'utf8mb4',
    ],

    'litebans' => [
        'bans_table' => 'litebans_bans',
        'history_table' => 'litebans_history',

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
        'temporary_unban_price' => '$9.99',
        'permanent_unban_price' => '$19.99',
    ],

    'page' => [
        'limit' => 50,
    ],
];