<?php

declare(strict_types=1);

return [
    'site' => [
        'name' => getenv('SITE_NAME') ?: 'Mineacle',
        'home_url' => getenv('HOME_URL') ?: '/',
        'stats_url' => getenv('STATS_URL') ?: '#',
        'store_url' => getenv('STORE_URL') ?: '#',
        'bans_url' => getenv('BANS_URL') ?: '#',
        'vote_url' => getenv('VOTE_URL') ?: '#',
        'staff_url' => getenv('STAFF_URL') ?: '#',
        'discord_url' => getenv('DISCORD_URL') ?: '#',
        'x_url' => getenv('X_URL') ?: '#',
        'youtube_url' => getenv('YOUTUBE_URL') ?: '#',
        'tiktok_url' => getenv('TIKTOK_URL') ?: '#',
        'support_email' => getenv('SUPPORT_EMAIL') ?: 'support@mineacle.net',
    ],
    'mysql' => [
        'host' => getenv('DB_HOST') ?: '',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_NAME') ?: '',
        'username' => getenv('DB_USERNAME') ?: '',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
    'tables' => [
        'sections' => getenv('HOME_SECTIONS_TABLE') ?: 'home_sections',
        'tiles' => getenv('HOME_TILES_TABLE') ?: 'home_feature_tiles',
        'worlds' => getenv('HOME_WORLDS_TABLE') ?: 'home_world_stats',
        'player_summary' => getenv('HOME_PLAYER_TABLE') ?: 'home_player_summary',
        'social_links' => getenv('HOME_SOCIAL_TABLE') ?: 'home_social_links',
    ],
    'security' => [
        'debug' => strtolower((string) getenv('APP_DEBUG')) === 'true',
    ],
];
