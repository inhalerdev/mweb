<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$config = mineacle_config();
$site = $config['site'] ?? [];
$home = h((string) ($site['home'] ?? 'https://mineacle.net/home'));
$vote = h((string) ($site['vote'] ?? 'https://vote.mineacle.net'));
$bans = h((string) ($site['bans'] ?? 'https://bans.mineacle.net'));
$stats = h((string) ($site['stats'] ?? 'https://stats.mineacle.net'));
$store = h((string) ($site['store'] ?? 'https://store.mineacle.net'));
$discord = h((string) ($site['discord'] ?? 'https://discord.gg/VwbwWftefM'));
$ip = h((string) ($site['ip'] ?? 'mineacle.net'));
$supportEmail = h((string) ($site['support_email'] ?? 'support@mineacle.net'));

mineacle_page_head('Bans');

?>
<main class="mineacle-bans-shell" data-mineacle-bans-app>
    <aside class="mineacle-bans-rail" aria-label="Mineacle navigation">
        <a class="mineacle-bans-rail-logo" href="<?php echo $bans; ?>" aria-label="Mineacle Bans">
            <img src="assets/mineacle-square-logo.png" alt="Mineacle">
        </a>

        <nav class="mineacle-bans-rail-nav" aria-label="Primary links">
            <a class="mineacle-bans-rail-link" href="<?php echo $home; ?>" aria-label="Home" title="Home">
                <img src="assets/mineacle-main-logo.png" alt="">
            </a>
            <a class="mineacle-bans-rail-link" href="<?php echo $store; ?>" aria-label="Store" title="Store">
                <img src="assets/store.svg" alt="">
            </a>
            <a class="mineacle-bans-rail-link is-active" href="<?php echo $bans; ?>" aria-label="Bans" title="Bans">
                <img src="assets/hammer.svg" alt="">
            </a>
            <a class="mineacle-bans-rail-link" href="<?php echo $vote; ?>" aria-label="Vote" title="Vote">
                <img src="assets/vote.svg" alt="">
            </a>
            <a class="mineacle-bans-rail-link" href="<?php echo $stats; ?>" aria-label="Stats" title="Stats">
                <img src="assets/players-online-icon.png" alt="">
            </a>
        </nav>

        <a class="mineacle-bans-rail-link mineacle-bans-rail-support" href="<?php echo $discord; ?>" target="_blank" rel="noopener" aria-label="Discord support" title="Discord support">
            <img src="assets/discord.svg" alt="">
        </a>
    </aside>

    <div class="mineacle-bans-main">
        <header class="mineacle-bans-topbar">
            <form class="mineacle-lb-search js-ban-search-form mineacle-bans-search" id="banSearchForm" role="search">
                <label class="sr-only" for="banSearch">Search punishments</label>
                <div class="mineacle-lb-search-field mineacle-bans-search-field">
                    <img src="assets/search-icon.png" alt="" aria-hidden="true">
                    <input id="banSearch" class="js-ban-search" type="text" name="q" autocomplete="off" placeholder="Search player, UUID, staff, reason, or server">
                    <button class="ban-search-clear js-ban-clear" id="clearSearch" type="button" aria-label="Clear search" title="Clear search">x</button>
                </div>
                <button class="mineacle-lb-primary-action mineacle-bans-search-action" type="submit">Search</button>
            </form>

            <section class="mineacle-bans-user-panel" aria-label="Bans portal status">
                <img src="assets/mineacle-bans-hero-logo.png" alt="">
                <div>
                    <span>Bans Portal</span>
                    <strong>Mineacle</strong>
                </div>
                <button type="button" data-copy-ip="<?php echo $ip; ?>" aria-label="Copy server IP">Play</button>
            </section>
        </header>

        <section class="mineacle-bans-dashboard" aria-label="Mineacle bans overview">
            <section class="mineacle-bans-hero-panel" aria-label="Mineacle punishments">
                <div class="mineacle-bans-hero-copy">
                    <span class="mineacle-bans-kicker">Public Records</span>
                    <h1>Mineacle Bans</h1>
                    <p>Search active and historical LiteBans records, review punishment details, and find the right appeal path without digging through clutter.</p>

                    <div class="mineacle-bans-server-pill">
                        <strong><?php echo $ip; ?></strong>
                        <span><i aria-hidden="true"></i> Server records online</span>
                    </div>

                    <a class="mineacle-bans-help-link" href="mailto:<?php echo $supportEmail; ?>">Need help? Contact support</a>
                </div>

                <img class="mineacle-bans-hero-logo" src="assets/mineacle-bans-hero-logo.png" alt="Mineacle Bans">
            </section>

            <aside class="mineacle-bans-side-panel" aria-label="Appeals and recent status">
                <div class="mineacle-bans-vote-line">
                    <span>You have records to review</span>
                    <a href="<?php echo $discord; ?>" target="_blank" rel="noopener">Appeal</a>
                </div>

                <div class="mineacle-bans-appeal-art">
                    <img src="assets/appeal-wumpus.webp" alt="">
                    <div>
                        <strong>Discord Appeals</strong>
                        <span id="navDiscordOnline">Members</span>
                    </div>
                </div>

                <div class="mineacle-bans-mini-list" id="recentBanList">
                    <div class="mineacle-lb-loading">Loading recent bans</div>
                </div>
            </aside>
        </section>

        <section class="mineacle-lb-stats mineacle-bans-stat-row" aria-label="LiteBans statistics">
            <div class="mineacle-lb-stat-grid" id="mineacleStatsGrid">
                <article class="mineacle-lb-stat-card is-red"><strong>--</strong><span>Active Bans</span><small>of --</small></article>
                <article class="mineacle-lb-stat-card is-gold"><strong>--</strong><span>Active Mutes</span><small>of --</small></article>
                <article class="mineacle-lb-stat-card is-cyan"><strong>--</strong><span>Total Warnings</span><small>all time</small></article>
                <article class="mineacle-lb-stat-card is-slate"><strong>--</strong><span>Total Kicks</span><small>all time</small></article>
            </div>
        </section>

        <section class="mineacle-lb-table-section mineacle-bans-records bans-v3-results" id="ban-results" aria-label="Punishment results">
            <div class="mineacle-lb-table-head mineacle-bans-section-head">
                <div>
                    <span class="bans-v3-kicker mineacle-bans-kicker">Latest Activity</span>
                    <h2>Ban Records</h2>
                </div>
                <div class="mineacle-bans-record-meta">
                    <span class="mineacle-lb-count-pill" id="recentBanCount">0</span>
                    <span class="bans-list-meta js-ban-meta" id="banCount">Loading records</span>
                </div>
            </div>

            <div class="mineacle-lb-table-shell">
                <div class="ban-table js-ban-table mineacle-lb-table" id="banList" aria-live="polite">
                    <div class="mineacle-lb-loading">Loading bans</div>
                </div>
            </div>

            <div class="pagination-row mineacle-lb-pagination" id="banPagination">
                <button class="btn soft js-ban-prev" id="prevPage" type="button">Previous</button>
                <span class="page-indicator js-ban-page" id="pageInfo">Page 1</span>
                <button class="btn soft js-ban-next" id="nextPage" type="button">Next</button>
            </div>
        </section>

        <section class="mineacle-bans-support-strip" aria-label="Support information">
            <div>
                <span class="mineacle-bans-kicker">Support</span>
                <h2>Appeal or review a punishment</h2>
            </div>
            <p>Every record keeps its original reason, staff action, date, server scope, duration, and appeal information when LiteBans provides it.</p>
            <a href="<?php echo $discord; ?>" target="_blank" rel="noopener">Open Discord</a>
        </section>
    </div>
</main>
<?php mineacle_footer(); ?>
