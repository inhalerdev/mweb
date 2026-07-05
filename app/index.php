<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/home-data.php';

$site = mineacle_config()['site'] ?? [];
$home = mineacle_home_data();
$supportEmail = (string) ($site['support_email'] ?? 'support@mineacle.net');
$minecraftIp = (string) ($site['minecraft_ip'] ?? 'mineacle.net');
$year = date('Y');

function mineacle_icon(string $name): string
{
    $officialIcons = [
        'home' => 'assets/icons/home.svg',
        'stats' => 'assets/icons/leaderboard.svg',
        'store' => 'assets/icons/basket-shopping.svg',
        'bans' => 'assets/icons/gavel.svg',
        'staff' => 'assets/icons/gavel.svg',
        'discord' => 'assets/icons/discord.svg',
        'x' => 'assets/icons/x-twitter.svg',
    ];

    if (isset($officialIcons[$name])) {
        return '<img class="site-icon" src="' . h($officialIcons[$name]) . '" alt="" aria-hidden="true">';
    }

    $icons = [
        'logo' => '<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M4 9 16 3l12 6v14l-12 6-12-6V9Zm12 5 7-3.5L16 7l-7 3.5L16 14Zm-8 7 6 3v-7l-6-3v7Zm10 3 6-3v-7l-6 3v7Z"/></svg>',
        'vote' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18-5-5 2.2-2.2L9 13.6l8.8-8.8L20 7 9 18Z"/></svg>',
        'youtube' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 8.5c.3 2.3.3 4.7 0 7-.2 1.4-1.2 2.4-2.6 2.6-4.2.4-8.6.4-12.8 0-1.4-.2-2.4-1.2-2.6-2.6-.3-2.3-.3-4.7 0-7 .2-1.4 1.2-2.4 2.6-2.6 4.2-.4 8.6-.4 12.8 0 1.4.2 2.4 1.2 2.6 2.6ZM10 15l5-3-5-3v6Z"/></svg>',
        'tiktok' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3h3c.2 2 1.4 3.4 3.4 3.8v3.1c-1.3-.1-2.4-.5-3.4-1.2V15a5 5 0 1 1-5-5h.6v3.2c-.2 0-.4-.1-.6-.1a1.9 1.9 0 1 0 1.9 1.9L14 3Z"/></svg>',
    ];

    return $icons[$name] ?? $icons['logo'];
}

function mineacle_home_is_video_url(string $url): bool
{
    $path = parse_url($url, PHP_URL_PATH);

    return is_string($path) && preg_match('/\.(m4v|mp4|mov|webm)$/i', $path) === 1;
}

function mineacle_home_versioned_url(string $url, string $version): string
{
    if ($url === '' || $url === '#') {
        return $url;
    }

    $separator = strpos($url, '?') === false ? '?' : '&';

    return $url . $separator . 'v=' . rawurlencode($version);
}

function mineacle_footer_link(mixed $url): string
{
    $value = trim((string) $url);

    if (str_starts_with($value, 'mailto:')) {
        $email = substr($value, 7);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $value : '#';
    }

    return mineacle_home_link($value);
}

$navLinks = [
    ['key' => 'home', 'url' => (string) ($site['home_url'] ?? '/')],
    ['key' => 'stats', 'label' => 'Leaderboards', 'url' => (string) ($site['stats_url'] ?? '/players')],
    ['key' => 'bans', 'url' => (string) ($site['bans_url'] ?? '#')],
];

$storeLink = ['key' => 'store', 'url' => (string) ($site['store_url'] ?? '#')];
$currentNavKey = 'home';

$footerQuickLinks = [
    ['label' => 'Home', 'url' => (string) ($site['home_url'] ?? '/')],
    ['label' => 'Leaderboards', 'url' => (string) ($site['stats_url'] ?? '/players')],
    ['label' => 'Store', 'url' => (string) ($site['store_url'] ?? '#')],
    ['label' => 'Vote', 'url' => (string) ($site['vote_url'] ?? '#')],
];

$footerCommunityLinks = [
    ['label' => 'Discord', 'url' => (string) ($site['discord_url'] ?? '#')],
    ['label' => 'X/Twitter', 'url' => (string) ($site['x_url'] ?? '#')],
    ['label' => 'YouTube', 'url' => (string) ($site['youtube_url'] ?? '#')],
    ['label' => 'TikTok', 'url' => (string) ($site['tiktok_url'] ?? '#')],
];

$footerSocialLinks = [
    ['key' => 'discord', 'label' => 'Discord', 'url' => (string) ($site['discord_url'] ?? '#')],
    ['key' => 'x', 'label' => 'X/Twitter', 'url' => (string) ($site['x_url'] ?? '#')],
    ['key' => 'youtube', 'label' => 'YouTube', 'url' => (string) ($site['youtube_url'] ?? '#')],
];

$supportLink = trim((string) ($site['support_url'] ?? ''));

if ($supportLink === '') {
    $supportLink = filter_var($supportEmail, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $supportEmail : '#';
}

$footerLegalLinks = [
    ['label' => 'Terms of Service', 'url' => (string) ($site['terms_url'] ?? '#')],
    ['label' => 'Privacy Policy', 'url' => (string) ($site['privacy_url'] ?? '#')],
    ['label' => 'Refund Policy', 'url' => (string) ($site['refund_url'] ?? '#')],
    ['label' => 'Support', 'url' => $supportLink],
];

$tiles = array_slice($home['tiles'], 0, 4);
$worlds = array_slice(array_values($home['worlds']), 0, 3);
$socialLinks = array_slice($home['social_links'], 0, 4);
$heroBackground = trim((string) ($home['hero']['background_image_url'] ?? ''));
$heroBackgroundUrl = mineacle_home_safe_url($heroBackground);
$heroBackgroundIsVideo = mineacle_home_is_video_url($heroBackgroundUrl);
$heroAssetVersion = mineacle_page_asset_version();

mineacle_page_head('Home');
?>
<div class="site-shell">
    <aside class="rail" aria-label="Primary navigation">
        <a class="rail-logo" href="<?php echo h(mineacle_home_link($site['home_url'] ?? '/')); ?>" aria-label="Home">
            <img src="assets/brand/nav-logo.png" alt="">
        </a>

        <nav class="rail-nav" aria-label="Server links">
            <?php foreach ($navLinks as $link): ?>
                <?php $isActiveNavLink = (string) $link['key'] === $currentNavKey; ?>
                <a class="rail-link<?php echo $isActiveNavLink ? ' is-active' : ''; ?>" href="<?php echo h(mineacle_home_link($link['url'])); ?>" aria-label="<?php echo h((string) ($link['label'] ?? $link['key'])); ?>"<?php echo $isActiveNavLink ? ' aria-current="page"' : ''; ?>>
                    <?php echo mineacle_icon((string) $link['key']); ?>
                </a>
            <?php endforeach; ?>
            <?php $isStoreActive = (string) $storeLink['key'] === $currentNavKey; ?>
            <a class="rail-link rail-store-button<?php echo $isStoreActive ? ' is-active' : ''; ?>" href="<?php echo h(mineacle_home_link($storeLink['url'])); ?>" aria-label="Store"<?php echo $isStoreActive ? ' aria-current="page"' : ''; ?>>
                <?php echo mineacle_icon((string) $storeLink['key']); ?>
            </a>
        </nav>

        <div class="rail-social" aria-label="Social links">
            <a class="rail-link" href="<?php echo h(mineacle_home_link($site['discord_url'] ?? '#')); ?>" aria-label="Discord">
                <?php echo mineacle_icon('discord'); ?>
            </a>
            <a class="rail-link" href="<?php echo h(mineacle_home_link($site['x_url'] ?? '#')); ?>" aria-label="X">
                <?php echo mineacle_icon('x'); ?>
            </a>
        </div>
    </aside>

    <main class="home-grid" aria-label="Home layout">
        <section class="search-row" aria-label="Search">
            <div class="server-status is-loading" data-server-status data-server-ip="<?php echo h($minecraftIp); ?>" aria-live="polite">
                <span class="server-status-dot" aria-hidden="true"></span>
                <span class="server-status-main">
                    <span class="server-status-label">Server Status</span>
                    <span class="server-status-count" data-server-status-count>Checking server</span>
                </span>
            </div>

            <label class="sr-only" for="homeSearch">Search</label>
            <div class="player-search" data-player-search>
                <form class="search-box" action="/player" method="get" role="search" data-player-search-form>
                    <img src="assets/icons/search.png" alt="" aria-hidden="true">
                    <input id="homeSearch" name="name" type="search" placeholder="Search players.." autocomplete="off" aria-autocomplete="list" aria-controls="playerSearchResults" aria-expanded="false">
                    <button class="search-clear" type="button" aria-label="Clear search" hidden>
                        <img src="assets/icons/clear-search.svg" alt="" aria-hidden="true">
                    </button>
                </form>
                <div class="player-search-results" id="playerSearchResults" data-player-search-results role="listbox" aria-label="Player search results" hidden></div>
            </div>
        </section>

        <section class="top-row">
            <article class="panel hero-panel"<?php echo $heroBackgroundIsVideo ? '' : mineacle_home_image_style($home['hero']['background_image_url'] ?? ''); ?> aria-label="Hero">
                <?php if ($heroBackground !== ''): ?>
                    <?php if ($heroBackgroundIsVideo): ?>
                        <video class="hero-background hero-background-video" autoplay muted loop playsinline preload="auto" controlslist="nodownload noplaybackrate" disablepictureinpicture draggable="false" aria-hidden="true">
                            <source src="<?php echo h(mineacle_home_versioned_url($heroBackgroundUrl, $heroAssetVersion)); ?>" type="video/mp4">
                        </video>
                    <?php else: ?>
                        <img class="hero-background" src="<?php echo h($heroBackgroundUrl); ?>" alt="" draggable="false" aria-hidden="true">
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (trim((string) ($home['hero']['image_url'] ?? '')) !== ''): ?>
                    <span class="panel-media"<?php echo mineacle_home_image_style($home['hero']['image_url'] ?? '', '--media-image'); ?>></span>
                <?php endif; ?>
                <div class="hero-copy">
                    <h1 class="hero-logo-title">
                        <img src="<?php echo h(mineacle_home_versioned_url('/assets/brand/hero-logo.png', $heroAssetVersion)); ?>" alt="Mineacle Network">
                    </h1>
                    <p class="hero-text">Java Edition support for Minecraft 1.21.11 to 26+. Copy the server IP, add Mineacle to Multiplayer, and join from desktop.</p>
                    <div class="hero-actions" aria-label="Server actions">
                        <button class="hero-action hero-action-primary hero-play-now" type="button" data-copy-server-ip data-server-ip="<?php echo h($minecraftIp); ?>" data-default-label="Join on Java Edition" aria-label="Copy Mineacle server IP">
                            <img class="hero-action-icon" src="assets/icons/play-button-arrowhead.png" alt="" aria-hidden="true">
                            <span data-copy-server-label>Join on Java Edition</span>
                        </button>
                        <button class="hero-action hero-action-secondary" type="button" data-open-join-modal>
                            How to Join
                        </button>
                    </div>
                </div>
                <span class="sr-only">Hero banner</span>
            </article>
        </section>

        <section class="tile-row" aria-label="Feature links">
            <?php foreach ($tiles as $index => $tile): ?>
                <article class="panel feature-tile feature-tile-<?php echo h((string) ($index + 1)); ?>"<?php echo mineacle_home_image_style($tile['image_url'] ?? ''); ?> aria-label="<?php echo h((string) ($tile['tile_key'] ?? 'Feature')); ?>">
                    <span></span>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="world-row" aria-label="World status">
            <?php foreach ($worlds as $world): ?>
                <article class="panel world-card"<?php echo mineacle_home_panel_style($world['image_url'] ?? '', $world['players_online'] ?? 0, $world['max_players'] ?? 0); ?> aria-label="<?php echo h((string) ($world['world_key'] ?? 'world')); ?>">
                    <span></span>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="community-panel"<?php echo mineacle_home_image_style($home['community']['background_image_url'] ?? ''); ?> aria-label="Community">
            <div class="community-art">
                <span class="panel-media"<?php echo mineacle_home_image_style($home['community']['image_url'] ?? '', '--media-image'); ?>></span>
            </div>

            <div class="social-stack" aria-label="Social destinations">
                <?php foreach ($socialLinks as $link): ?>
                    <div class="social-row" aria-label="<?php echo h((string) ($link['platform_key'] ?? 'Social')); ?>">
                        <?php echo mineacle_icon((string) ($link['platform_key'] ?? 'logo')); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <footer class="footer-panel" aria-label="Footer">
            <div class="footer-inner">
                <section class="footer-about" aria-label="Mineacle Studios">
                    <div class="footer-brand">
                        <img src="assets/brand/m-studios.png" alt="Mineacle Studios">
                    </div>
                    <p>Mineacle Studios is a small team of Minecraft developers building the custom systems behind the Mineacle Network. After over a year of trial, error, and refinement, we are creating a smooth, polished, community-driven survival experience while staying true to the Minecraft everyone already loves.</p>
                    <div class="footer-socials" aria-label="Social links">
                        <?php foreach ($footerSocialLinks as $link): ?>
                            <a href="<?php echo h(mineacle_home_link($link['url'])); ?>" aria-label="<?php echo h($link['label']); ?>">
                                <?php echo mineacle_icon((string) $link['key']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <nav class="footer-links" aria-label="Quick links">
                    <h2>Quick Links:</h2>
                    <?php foreach ($footerQuickLinks as $link): ?>
                        <a href="<?php echo h(mineacle_home_link($link['url'])); ?>"><?php echo h($link['label']); ?></a>
                    <?php endforeach; ?>
                </nav>

                <nav class="footer-links" aria-label="Community links">
                    <h2>Community:</h2>
                    <?php foreach ($footerCommunityLinks as $link): ?>
                        <a href="<?php echo h(mineacle_home_link($link['url'])); ?>"><?php echo h($link['label']); ?></a>
                    <?php endforeach; ?>
                </nav>

                <p class="footer-bottom">
                    <img src="assets/brand/nav-logo.png" alt="" aria-hidden="true">
                    <span>
                        Copyright © <?php echo h((string) $year); ?> Mineacle Studios. All Rights Reserved. The Mineacle Network is not affiliated with or endorsed by Mojang Studios or Microsoft.
                        <span class="footer-policy-links">
                            <?php foreach ($footerLegalLinks as $link): ?>
                                <a href="<?php echo h(mineacle_footer_link($link['url'])); ?>"><?php echo h($link['label']); ?></a>
                            <?php endforeach; ?>
                        </span>
                    </span>
                </p>
            </div>
        </footer>
    </main>
</div>
<div class="join-modal" data-join-modal hidden>
    <div class="join-modal-backdrop" data-close-join-modal></div>
    <section class="join-modal-panel" role="dialog" aria-modal="true" aria-labelledby="joinModalTitle" tabindex="-1">
        <button class="join-modal-close" type="button" data-close-join-modal aria-label="Close how to join">
            <img src="assets/icons/clear-search.svg" alt="" aria-hidden="true">
        </button>
        <div class="join-modal-copy">
            <p>Java Edition 1.21.11 to 26+</p>
            <h2 id="joinModalTitle">Join Mineacle</h2>
        </div>
        <div class="join-modal-media">
            <img data-join-gif data-src="<?php echo h(mineacle_home_versioned_url('/assets/brand/mineacle-how-to-join.gif', $heroAssetVersion)); ?>" alt="How to join Mineacle on Java Edition">
        </div>
        <div class="join-modal-actions">
            <p class="join-modal-ip"><span>Server IP:</span> <strong><?php echo h($minecraftIp); ?></strong></p>
            <button class="hero-action hero-action-primary join-modal-copy-ip" type="button" data-copy-server-ip data-server-ip="<?php echo h($minecraftIp); ?>" data-default-label="Copy" aria-label="Copy Mineacle server IP">
                <span data-copy-server-label>Copy</span>
            </button>
        </div>
    </section>
</div>
<?php mineacle_page_end(); ?>
