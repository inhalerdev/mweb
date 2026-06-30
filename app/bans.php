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
$supportEmail = h((string) ($site['support_email'] ?? 'support@mineacle.net'));

mineacle_page_head('Bans');

?>
<main class="mineacle-bans-shell" data-mineacle-bans-app>
    <aside class="mineacle-bans-rail" aria-label="Mineacle navigation">
        <a class="mineacle-bans-rail-logo" href="<?php echo $bans; ?>" aria-label="Mineacle Bans">
            <img src="assets/mineacle-logo-purple.png?v=fold1.0.0" alt="Mineacle">
        </a>

        <nav class="mineacle-bans-rail-nav" aria-label="Primary links">
            <div class="mineacle-nav-block is-solo">
                <a class="mineacle-bans-rail-link" href="<?php echo $store; ?>" aria-label="Store" title="Store">
                    <span class="mineacle-nav-icon mineacle-nav-icon-store"></span>
                </a>
            </div>

            <div class="mineacle-nav-block">
                <a class="mineacle-bans-rail-link" href="<?php echo $vote; ?>" aria-label="Vote" title="Vote">
                    <span class="mineacle-nav-icon mineacle-nav-icon-vote"></span>
                </a>
                <a class="mineacle-bans-rail-link is-active" href="<?php echo $bans; ?>" aria-label="Bans" title="Bans">
                    <span class="mineacle-nav-icon mineacle-nav-icon-bans"></span>
                </a>
                <a class="mineacle-bans-rail-link" href="<?php echo $stats; ?>" aria-label="Stats" title="Stats">
                    <span class="mineacle-nav-icon mineacle-nav-icon-stats"></span>
                </a>
            </div>
        </nav>

        <a class="mineacle-bans-rail-support" href="<?php echo $discord; ?>" target="_blank" rel="noopener" aria-label="Discord" title="Discord">
            <span class="mineacle-nav-icon mineacle-nav-icon-discord"></span>
        </a>
    </aside>

    <section class="mineacle-bans-main" aria-label="Mineacle bans overview">
        <div class="mineacle-fold-top">
            <section class="mineacle-card mineacle-hero" aria-labelledby="mineacleHeroTitle">
                <div class="mineacle-hero-inner">
                    <p class="mineacle-kicker">Public ban records</p>
                    <h1 id="mineacleHeroTitle">Mineacle Bans</h1>
                    <p class="mineacle-hero-copy">Search active banned players, UUIDs, staff actions, reasons, and server records from the live punishment database.</p>

                    <form class="mineacle-bans-search" id="banSearchForm" role="search">
                        <label class="sr-only" for="banSearch">Search punishments</label>
                        <div class="mineacle-bans-search-field">
                            <input id="banSearch" type="text" name="q" autocomplete="off" placeholder="Search player, UUID, staff, reason, or server">
                            <button class="mineacle-bans-search-action" id="banSearchAction" type="button" aria-label="Search" title="Search">Search</button>
                        </div>
                    </form>

                    <div class="mineacle-search-results" id="banSearchResults" aria-live="polite">
                        <div class="mineacle-search-empty">Start typing to search active bans from the database.</div>
                    </div>

                    <button class="mineacle-player-count-module" id="mineaclePlayerCountModule" type="button" data-copy-ip="mineacle.net" data-display-ip="MINEACLE.NET" aria-label="Copy Mineacle server IP">
                        <span class="mineacle-player-count-ip" id="mineaclePlayerCountIp">MINEACLE.NET</span>
                        <span class="mineacle-player-count-online">CURRENTLY ONLINE: <b id="mineaclePlayerCountValue">0</b></span>
                    </button>
                </div>
            </section>

            <aside class="mineacle-card mineacle-login" aria-labelledby="mineacleLoginTitle">
                <h2 id="mineacleLoginTitle">Log In</h2>
                <p>Use Discord for staff tools, player support, and punishment appeal access.</p>
                <a class="mineacle-login-button" href="<?php echo $discord; ?>" target="_blank" rel="noopener">Open Discord</a>
            </aside>
        </div>

        <div class="mineacle-fold-bottom" aria-label="Ban tools">
            <article class="mineacle-card mineacle-info-card">
                <h2>Active Bans</h2>
                <p>Review live active ban records and quickly confirm whether a player is currently restricted.</p>
                <a class="mineacle-card-link" href="<?php echo $bans; ?>">Search bans</a>
            </article>

            <article class="mineacle-card mineacle-info-card">
                <h2>Network Stats</h2>
                <p>Check voting, stats, and server activity without leaving the Mineacle network tools.</p>
                <a class="mineacle-card-link" href="<?php echo $stats; ?>">View stats</a>
            </article>

            <article class="mineacle-card mineacle-info-card">
                <h2>Appeals</h2>
                <p>Need help with a punishment? Contact support or start an appeal through the community hub.</p>
                <a class="mineacle-card-link" href="mailto:<?php echo $supportEmail; ?>">Contact support</a>
            </article>
        </div>
    </section>
</main>
<?php mineacle_page_end(); ?>
