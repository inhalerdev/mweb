<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$config = mineacle_config();
$site = $config['site'] ?? [];
$bans = h((string) ($site['bans'] ?? 'https://bans.mineacle.net'));
$stats = h((string) ($site['stats'] ?? 'https://stats.mineacle.net'));
$vote = h((string) ($site['vote'] ?? 'https://vote.mineacle.net'));
$store = h((string) ($site['store'] ?? 'https://store.mineacle.net'));
$discord = h((string) ($site['discord'] ?? 'https://discord.gg/VwbwWftefM'));

mineacle_page_head('Bans');

?>
<main class="mineacle-bans-shell" data-mineacle-bans-app>
    <aside class="mineacle-bans-rail" aria-label="Mineacle navigation">
        <a class="mineacle-bans-rail-logo" href="<?php echo $bans; ?>" aria-label="Mineacle Bans">
            <img src="assets/mineacle-logo-purple.png?v=bansclean1.0.6" alt="Mineacle">
        </a>

        <nav class="mineacle-bans-rail-nav" aria-label="Primary links">
            <a class="mineacle-bans-rail-link is-active" href="<?php echo $bans; ?>" aria-label="Bans" title="Bans">
                <img src="assets/hammer.svg?v=bansclean1.0.6" alt="">
            </a>
            <a class="mineacle-bans-rail-link" href="<?php echo $stats; ?>" aria-label="Stats" title="Stats">
                <img src="assets/users.svg?v=bansclean1.0.6" alt="">
            </a>
            <a class="mineacle-bans-rail-link" href="<?php echo $vote; ?>" aria-label="Vote" title="Vote">
                <img src="assets/vote.svg?v=bansclean1.0.6" alt="">
            </a>
            <a class="mineacle-bans-rail-link" href="<?php echo $store; ?>" aria-label="Store" title="Store">
                <img src="assets/store.svg?v=bansclean1.0.6" alt="">
            </a>
            <a class="mineacle-bans-rail-link mineacle-bans-rail-discord" href="<?php echo $discord; ?>" target="_blank" rel="noopener" aria-label="Discord" title="Discord">
                <img src="assets/discord.svg?v=bansclean1.0.6" alt="">
            </a>
        </nav>
    </aside>

    <div class="mineacle-bans-main">
        <header class="mineacle-bans-topbar">
            <div class="mineacle-bans-search-modules">
                <form class="mineacle-bans-search mineacle-bans-search-module" id="banSearchForm" role="search">
                    <label class="sr-only" for="banSearch">Search punishments</label>
                    <div class="mineacle-bans-search-field">
                        <input id="banSearch" type="text" name="q" autocomplete="off" placeholder="Search active banned players, UUID, staff, reason, or server">
                        <button class="mineacle-bans-search-action" id="banSearchAction" type="button" aria-label="Search" title="Search">Search</button>
                    </div>
                </form>

                <button class="mineacle-player-count-module" id="mineaclePlayerCountModule" type="button" data-copy-ip="mineacle.net" data-display-ip="MINEACLE.NET" aria-label="Mineacle server status">
                    <span class="mineacle-player-count-ip" id="mineaclePlayerCountIp">MINEACLE.NET</span>
                    <span class="mineacle-player-count-online">CURRENTLY ONLINE: <b id="mineaclePlayerCountValue">0</b></span>
                </button>
            </div>
        </header>
    </div>
</main>
<?php mineacle_footer(); ?>
