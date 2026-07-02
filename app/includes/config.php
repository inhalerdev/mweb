<?php

declare(strict_types=1);

return [
    'site' => [
        'name' => getenv('SITE_NAME') ?: 'Mineacle',
        'home_url' => getenv('HOME_URL') ?: '/',
        'stats_url' => getenv('STATS_URL') ?: '/players',
        'store_url' => getenv('STORE_URL') ?: '#',
        'bans_url' => getenv('BANS_URL') ?: '#',
        'vote_url' => getenv('VOTE_URL') ?: '#',
        'staff_url' => getenv('STAFF_URL') ?: '#',
        'discord_url' => getenv('DISCORD_URL') ?: '#',
        'x_url' => getenv('X_URL') ?: '#',
        'youtube_url' => getenv('YOUTUBE_URL') ?: '#',
        'tiktok_url' => getenv('TIKTOK_URL') ?: '#',
        'support_email' => getenv('SUPPORT_EMAIL') ?: 'support@mineacle.net',
        'minecraft_ip' => getenv('MINECRAFT_IP') ?: 'mineacle.net',
    ],
    'mysql' => [
        'host' => getenv('DB_HOST') ?: 'db.fr-pari1.bengt.wasmernet.com',
        'port' => (int) (getenv('DB_PORT') ?: 10272),
        'database' => getenv('DB_NAME') ?: 'litebans_mineacle',
        'username' => getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'user_9ca32e8a'),
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
        'timeout' => (int) (getenv('DB_TIMEOUT') ?: 1),
    ],
    'tables' => [
        'sections' => getenv('HOME_SECTIONS_TABLE') ?: 'home_sections',
        'tiles' => getenv('HOME_TILES_TABLE') ?: 'home_feature_tiles',
        'worlds' => getenv('HOME_WORLDS_TABLE') ?: 'home_world_stats',
        'player_summary' => getenv('HOME_PLAYER_TABLE') ?: 'home_player_summary',
        'social_links' => getenv('HOME_SOCIAL_TABLE') ?: 'home_social_links',
        'player_profiles' => getenv('PLAYER_PROFILES_TABLE') ?: 'mineacle_web_profiles',
        'litebans_bans' => getenv('LITEBANS_BANS_TABLE') ?: 'litebans_bans',
        'litebans_mutes' => getenv('LITEBANS_MUTES_TABLE') ?: 'litebans_mutes',
    ],
    'home' => [
        'database_enabled' => strtolower((string) getenv('HOME_DATABASE_ENABLED')) === 'true',
    ],
    'skins' => [
        'provider' => strtolower((string) (getenv('SKIN_PROVIDER') ?: 'mineskin')),
        'head_size' => (int) (getenv('SKIN_HEAD_SIZE') ?: 64),
        'chest_size' => (int) (getenv('SKIN_CHEST_SIZE') ?: 180),
    ],
    'security' => [
        'debug' => strtolower((string) getenv('APP_DEBUG')) === 'true',
    ],
];
