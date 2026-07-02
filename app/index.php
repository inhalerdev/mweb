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

$navLinks = [
    ['key' => 'home', 'url' => (string) ($site['home_url'] ?? '/')],
    ['key' => 'stats', 'url' => (string) ($site['stats_url'] ?? '#')],
    ['key' => 'store', 'url' => (string) ($site['store_url'] ?? '#')],
    ['key' => 'bans', 'url' => (string) ($site['bans_url'] ?? '#')],
];

$tiles = array_slice($home['tiles'], 0, 4);
$worlds = array_slice(array_values($home['worlds']), 0, 3);
$socialLinks = array_slice($home['social_links'], 0, 4);

mineacle_page_head('Home');
?>
<div class="site-shell">
    <aside class="rail" aria-label="Primary navigation">
        <a class="rail-logo" href="<?php echo h(mineacle_home_link($site['home_url'] ?? '/')); ?>" aria-label="Home">
            <img src="assets/brand/nav-logo.png" alt="">
        </a>

        <nav class="rail-nav" aria-label="Server links">
            <?php foreach ($navLinks as $link): ?>
                <a class="rail-link" href="<?php echo h(mineacle_home_link($link['url'])); ?>" aria-label="<?php echo h($link['key']); ?>">
                    <?php echo mineacle_icon((string) $link['key']); ?>
                </a>
            <?php endforeach; ?>
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
                <span class="server-status-count" data-server-status-count>Checking server</span>
            </div>

            <label class="sr-only" for="homeSearch">Search</label>
            <div class="search-box">
                <img src="assets/icons/search.png" alt="" aria-hidden="true">
                <input id="homeSearch" name="search" type="search" placeholder="Search players.." autocomplete="off">
                <button class="search-clear" type="button" aria-label="Clear search" hidden>
                    <img src="assets/icons/clear-search.svg" alt="" aria-hidden="true">
                </button>
            </div>
        </section>

        <section class="top-row">
            <article class="panel hero-panel"<?php echo mineacle_home_image_style($home['hero']['background_image_url'] ?? ''); ?> aria-label="Hero">
                <span class="panel-media"<?php echo mineacle_home_image_style($home['hero']['image_url'] ?? '', '--media-image'); ?>></span>
                <span class="sr-only">Hero banner</span>
            </article>

            <aside class="panel player-panel" aria-label="Player summary">
                <span class="skin-frame"<?php echo mineacle_home_image_style($home['player']['skin_url'] ?? '', '--skin-image'); ?>></span>
                <span class="stat-bars"<?php echo mineacle_home_ratio_style($home['player']['players_online'] ?? 0, $home['player']['max_players'] ?? 0); ?>>
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </aside>
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

        <footer class="footer-panel"<?php echo mineacle_home_image_style($home['footer']['background_image_url'] ?? ''); ?> aria-label="Legal notice">
            <div class="footer-inner">
                <div class="footer-brand" aria-label="Mineacle Studios">
                    <img src="assets/brand/m-studios.png" alt="Mineacle Studios">
                </div>

                <section class="footer-legal" aria-labelledby="footerLegalTitle">
                    <h2 id="footerLegalTitle">Legal Notice</h2>
                    <p>The Mineacle Network is not affiliated with Mojang Studios or Microsoft, nor should it be considered endorsed by Mojang Studios or Microsoft. Any contributions or purchases made through Mineacle support the Mineacle Studios team.</p>
                    <p>For support or purchase history, please contact Mineacle Support at <?php echo h($supportEmail); ?>.</p>
                    <p><em>Minecraft, Mojang Studios, and related marks are property of their respective owners. Copyright 2009-<?php echo h((string) $year); ?>.</em></p>
                </section>
            </div>
        </footer>
    </main>
</div>
<?php mineacle_page_end(); ?>
