<?php

declare(strict_types=1);

function mineacle_env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return (string) $value;
}

function mineacle_env_bool(string $key, bool $default = false): bool
{
    $value = mineacle_env($key);
    if ($value === null) {
        return $default;
    }
    return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
}

function mineacle_env_int(string $key, int $default, int $min = 0, int $max = PHP_INT_MAX): int
{
    $value = mineacle_env($key);
    if ($value === null || !is_numeric($value)) {
        return $default;
    }
    return max($min, min($max, (int) $value));
}

function mineacle_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $baseUrl = rtrim((string) mineacle_env('SITE_URL', 'https://bans.mineacle.net'), '/');
    $discordUrl = (string) mineacle_env('DISCORD_URL', 'https://discord.gg/VwbwWftefM');

    $config = [
        'site' => [
            'name' => 'Mineacle Network',
            'short_name' => 'Mineacle',
            'title' => 'Mineacle Bans',
            'description' => 'View active Mineacle Network ban records, appeal information, and MineacleClientGuard enforcement details',
            'url' => $baseUrl,
            'canonical' => $baseUrl . '/',
            'ip' => (string) mineacle_env('SERVER_IP', 'mineacle.net'),
            'minecraft_host' => (string) mineacle_env('MC_STATUS_HOST', (string) mineacle_env('SERVER_IP', 'mineacle.net')),
            'minecraft_port' => mineacle_env_int('MC_STATUS_PORT', 25565, 1, 65535),
            'home' => (string) mineacle_env('HOME_URL', 'https://mineacle.net/home'),
            'vote' => (string) mineacle_env('VOTE_URL', 'https://vote.mineacle.net'),
            'bans' => (string) mineacle_env('BANS_URL', $baseUrl),
            'stats' => (string) mineacle_env('STATS_URL', 'https://stats.mineacle.net'),
            'store' => (string) mineacle_env('STORE_URL', 'https://store.mineacle.net'),
            'discord' => $discordUrl,
            'discord_invite_code' => (string) mineacle_env('DISCORD_INVITE_CODE', 'VwbwWftefM'),
            'x' => (string) mineacle_env('X_URL', 'https://x.com/mineaclenetwork'),
            'support_email' => (string) mineacle_env('SUPPORT_EMAIL', 'support@mineacle.net'),
            'appeal_email' => (string) mineacle_env('APPEAL_EMAIL', 'support@mineacle.net'),
            'appeal_discord' => (string) mineacle_env('APPEAL_DISCORD', $discordUrl),
            'unban_checkout_url' => (string) mineacle_env('UNBAN_CHECKOUT_URL', 'https://store.mineacle.net/checkout/unban?ban={id}&uuid={uuid}&username={username}'),
            'database_timezone' => (string) mineacle_env('DATABASE_TIMEZONE', 'UTC'),
            'display_timezone' => (string) mineacle_env('DISPLAY_TIMEZONE', 'America/Chicago'),
        ],
        'mysql' => [
            'host' => mineacle_env('DB_HOST'),
            'port' => mineacle_env_int('DB_PORT', 3306, 1, 65535),
            'database' => mineacle_env('DB_NAME'),
            'username' => mineacle_env('DB_USERNAME'),
            'password' => mineacle_env('DB_PASSWORD', ''),
            'charset' => (string) mineacle_env('DB_CHARSET', 'utf8mb4'),
        ],
        'litebans' => [
            'bans_table' => (string) mineacle_env('LITEBANS_BANS_TABLE', 'litebans_bans'),
            'history_table' => (string) mineacle_env('LITEBANS_HISTORY_TABLE', 'litebans_history'),
        ],
        'payments' => [
            'temporary_unban_price' => (string) mineacle_env('TEMP_UNBAN_PRICE', '$9.99'),
            'permanent_unban_price' => (string) mineacle_env('PERM_UNBAN_PRICE', '$19.99'),
        ],
        'page' => [
            'limit' => mineacle_env_int('BAN_PAGE_LIMIT', 25, 5, 50),
        ],
        'cache' => [
            'enabled' => !mineacle_env_bool('CACHE_DISABLED', false),
            'bans_ttl' => mineacle_env_int('BANS_CACHE_TTL', 8, 0, 120),
            'discord_ttl' => mineacle_env_int('DISCORD_CACHE_TTL', 300, 30, 3600),
            'server_status_ttl' => mineacle_env_int('SERVER_STATUS_CACHE_TTL', 25, 5, 300),
            'schema_ttl' => mineacle_env_int('SCHEMA_CACHE_TTL', 86400, 60, 604800),
        ],
        'security' => [
            'debug' => mineacle_env_bool('APP_DEBUG', false),
        ],
    ];

    return $config;
}
