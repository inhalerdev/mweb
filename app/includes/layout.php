<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function mineacle_page_asset_version(): string
{
    return 'base70';
}

function mineacle_page_public_link(mixed $url): string
{
    $value = trim((string) $url);

    if ($value === '') {
        return '#';
    }

    if (str_starts_with($value, 'mailto:')) {
        $email = substr($value, 7);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $value : '#';
    }

    if ($value === '#' || str_starts_with($value, '/') || str_starts_with($value, './')) {
        return $value;
    }

    return filter_var($value, FILTER_VALIDATE_URL) ? $value : '#';
}

function mineacle_page_icon(string $name): string
{
    $officialIcons = [
        'home' => '/assets/icons/home.svg',
        'stats' => '/assets/icons/leaderboard.svg',
        'store' => '/assets/icons/basket-shopping.svg',
        'bans' => '/assets/icons/gavel.svg',
        'discord' => '/assets/icons/discord.svg',
        'x' => '/assets/icons/x-twitter.svg',
    ];

    if (isset($officialIcons[$name])) {
        return '<img class="site-icon" src="' . h($officialIcons[$name]) . '" alt="" aria-hidden="true">';
    }

    $icons = [
        'youtube' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 8.5c.3 2.3.3 4.7 0 7-.2 1.4-1.2 2.4-2.6 2.6-4.2.4-8.6.4-12.8 0-1.4-.2-2.4-1.2-2.6-2.6-.3-2.3-.3-4.7 0-7 .2-1.4 1.2-2.4 2.6-2.6 4.2-.4 8.6-.4 12.8 0 1.4.2 2.4 1.2 2.6 2.6ZM10 15l5-3-5-3v6Z"/></svg>',
        'tiktok' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3h3c.2 2 1.4 3.4 3.4 3.8v3.1c-1.3-.1-2.4-.5-3.4-1.2V15a5 5 0 1 1-5-5h.6v3.2c-.2 0-.4-.1-.6-.1a1.9 1.9 0 1 0 1.9 1.9L14 3Z"/></svg>',
    ];

    return $icons[$name] ?? '';
}

function mineacle_page_search_header(array $site): void
{
    $minecraftIp = (string) ($site['minecraft_ip'] ?? 'mineacle.net');

    echo '<section class="search-row" aria-label="Search">';
    echo '<div class="server-status is-loading" data-server-status data-server-ip="' . h($minecraftIp) . '" aria-live="polite">';
    echo '<span class="server-status-dot" aria-hidden="true"></span>';
    echo '<span class="server-status-main">';
    echo '<span class="server-status-label">Server Status</span>';
    echo '<span class="server-status-count" data-server-status-count>Checking server</span>';
    echo '</span>';
    echo '</div>';
    echo '<label class="sr-only" for="homeSearch">Search</label>';
    echo '<div class="player-search" data-player-search>';
    echo '<form class="search-box" action="/player" method="get" role="search" data-player-search-form>';
    echo '<img src="/assets/icons/search.png" alt="" aria-hidden="true" draggable="false">';
    echo '<input id="homeSearch" name="name" type="search" placeholder="Search players.." autocomplete="off" aria-autocomplete="list" aria-expanded="false" aria-controls="playerSearchResults">';
    echo '<button class="search-clear" type="button" aria-label="Clear search" hidden>';
    echo '<img src="/assets/icons/clear-search.svg" alt="" aria-hidden="true" draggable="false">';
    echo '</button>';
    echo '</form>';
    echo '<div class="player-search-results" id="playerSearchResults" data-player-search-results role="listbox" aria-label="Player search results" hidden></div>';
    echo '</div>';
    echo '</section>';
}

function mineacle_page_footer(array $site): void
{
    $year = date('Y');
    $supportEmail = (string) ($site['support_email'] ?? 'support@mineacle.net');
    $supportLink = trim((string) ($site['support_url'] ?? ''));

    if ($supportLink === '') {
        $supportLink = filter_var($supportEmail, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $supportEmail : '#';
    }

    $quickLinks = [
        ['label' => 'Home', 'url' => (string) ($site['home_url'] ?? '/')],
        ['label' => 'Leaderboards', 'url' => (string) ($site['stats_url'] ?? '/players')],
        ['label' => 'Store', 'url' => (string) ($site['store_url'] ?? '#')],
        ['label' => 'Vote', 'url' => (string) ($site['vote_url'] ?? '#')],
    ];
    $communityLinks = [
        ['label' => 'Discord', 'url' => (string) ($site['discord_url'] ?? '#')],
        ['label' => 'X/Twitter', 'url' => (string) ($site['x_url'] ?? '#')],
        ['label' => 'YouTube', 'url' => (string) ($site['youtube_url'] ?? '#')],
        ['label' => 'TikTok', 'url' => (string) ($site['tiktok_url'] ?? '#')],
    ];
    $socialLinks = [
        ['key' => 'discord', 'label' => 'Discord', 'url' => (string) ($site['discord_url'] ?? '#')],
        ['key' => 'x', 'label' => 'X/Twitter', 'url' => (string) ($site['x_url'] ?? '#')],
        ['key' => 'youtube', 'label' => 'YouTube', 'url' => (string) ($site['youtube_url'] ?? '#')],
    ];
    $legalLinks = [
        ['label' => 'Terms of Service', 'url' => (string) ($site['terms_url'] ?? '#')],
        ['label' => 'Privacy Policy', 'url' => (string) ($site['privacy_url'] ?? '#')],
        ['label' => 'Refund Policy', 'url' => (string) ($site['refund_url'] ?? '#')],
        ['label' => 'Support', 'url' => $supportLink],
    ];

    echo '<footer class="footer-panel" aria-label="Footer">';
    echo '<div class="footer-inner">';
    echo '<section class="footer-about" aria-label="Mineacle Studios">';
    echo '<div class="footer-brand"><img src="/assets/brand/m-studios.png" alt="Mineacle Studios" draggable="false"></div>';
    echo '<p>Mineacle Studios is a small team of Minecraft developers building the custom systems behind the Mineacle Network. After over a year of trial, error, and refinement, we are creating a smooth, polished, community-driven survival experience while staying true to the Minecraft everyone already loves.</p>';
    echo '<div class="footer-socials" aria-label="Social links">';
    foreach ($socialLinks as $link) {
        echo '<a href="' . h(mineacle_page_public_link($link['url'])) . '" aria-label="' . h($link['label']) . '">' . mineacle_page_icon((string) $link['key']) . '</a>';
    }
    echo '</div>';
    echo '</section>';
    echo '<nav class="footer-links" aria-label="Quick links"><h2>Quick Links:</h2>';
    foreach ($quickLinks as $link) {
        echo '<a href="' . h(mineacle_page_public_link($link['url'])) . '">' . h($link['label']) . '</a>';
    }
    echo '</nav>';
    echo '<nav class="footer-links" aria-label="Community links"><h2>Community:</h2>';
    foreach ($communityLinks as $link) {
        echo '<a href="' . h(mineacle_page_public_link($link['url'])) . '">' . h($link['label']) . '</a>';
    }
    echo '</nav>';
    echo '<p class="footer-bottom"><img src="/assets/brand/nav-logo.png" alt="" aria-hidden="true" draggable="false"><span>';
    echo 'Copyright © ' . h((string) $year) . ' Mineacle Studios. All Rights Reserved. The Mineacle Network is not affiliated with or endorsed by Mojang Studios or Microsoft.';
    echo ' <span class="footer-policy-links">';
    foreach ($legalLinks as $link) {
        echo '<a href="' . h(mineacle_page_public_link($link['url'])) . '">' . h($link['label']) . '</a>';
    }
    echo '</span></span></p>';
    echo '</div>';
    echo '</footer>';
}

function mineacle_page_head(string $title = 'Home'): void
{
    mineacle_security_headers();
    $config = mineacle_config();
    $site = $config['site'] ?? [];
    $name = (string) ($site['name'] ?? 'Mineacle');

    echo '<!doctype html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($name . ' | ' . $title) . '</title>';
    echo '<meta name="description" content="' . h($name . ' Minecraft server home page') . '">';
    $assetVersion = mineacle_page_asset_version();

    echo '<link rel="icon" type="image/png" href="/assets/fav.png?v=' . h($assetVersion) . '">';
    echo '<link rel="stylesheet" href="/assets/home-page.css?v=' . h($assetVersion) . '">';
    echo '</head>';
    echo '<body>';
}

function mineacle_page_end(): void
{
    echo '<script src="/assets/home-page.js?v=' . h(mineacle_page_asset_version()) . '"></script>';
    echo '</body>';
    echo '</html>';
}
