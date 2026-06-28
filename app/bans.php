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
$x = h((string) ($site['x'] ?? 'https://x.com/mineaclenetwork'));
$ip = h((string) ($site['ip'] ?? 'mineacle.net'));
$support = h((string) ($site['support_email'] ?? 'support@mineacle.net'));

mineacle_page_head('Bans', 'Search Mineacle active ban records and learn how Mineacle Client Guard protects the server community');
?>
<main class="app-shell" data-app data-server-ip="<?php echo $ip; ?>">
    <aside class="rail" aria-label="Mineacle navigation">
        <a class="rail-logo" href="<?php echo $bans; ?>" aria-label="Mineacle Bans">
            <img src="assets/brand/mineacle-m.webp?v=6.10.0" alt="Mineacle">
        </a>

        <nav class="rail-nav" aria-label="Primary links">
            <a class="rail-link active" href="<?php echo $bans; ?>" aria-label="Bans" title="Bans"><img src="assets/icons/hammer.svg?v=6.10.0" alt=""></a>
            <a class="rail-link" href="<?php echo $stats; ?>" aria-label="Stats" title="Stats"><img src="assets/icons/users.svg?v=6.10.0" alt=""></a>
            <a class="rail-link" href="<?php echo $vote; ?>" aria-label="Vote" title="Vote"><img src="assets/icons/star.svg?v=6.10.0" alt=""></a>
            <a class="rail-link" href="<?php echo $store; ?>" aria-label="Store" title="Store"><img src="assets/icons/store.svg?v=6.10.0" alt=""></a>
        </nav>

        <a class="rail-link rail-discord" href="<?php echo $discord; ?>" target="_blank" rel="noopener" aria-label="Discord" title="Discord">
            <img src="assets/icons/discord.svg?v=6.10.0" alt="">
        </a>
    </aside>

    <section class="content-lane">
        <header class="topbar" aria-label="Search Mineacle records">
            <form class="search-module module" id="banSearchForm" role="search">
                <label class="sr-only" for="banSearch">Search active records</label>
                <input id="banSearch" name="q" autocomplete="off" placeholder="Search player, UUID, staff, reason, or server">
                <button class="search-button" id="banSearchAction" type="submit" aria-label="Search and open player profile"><img src="assets/icons/search.svg?v=6.10.0" alt=""></button>
            </form>
        </header>

        <section class="top-stage" aria-label="Mineacle overview">
            <section class="hero-module module" aria-labelledby="heroTitle">
                <div class="hero-media" aria-hidden="true">
                    <img src="assets/brand/hero-world.webp?v=6.10.0" alt="" loading="eager">
                </div>
                <div class="hero-copy">
                    <img class="hero-brand-logo" src="assets/brand/mineacle-logo-full.webp?v=6.10.0" alt="Mineacle">
                    <p class="eyebrow">End protected survival network</p>
                    <h1 id="heroTitle">Public Active Ban Records</h1>
                    <p>Search public active records, review player profile history, and see how Mineacle Client Guard protects fair PvP, survival economy, builds, and community trust</p>
                    <div class="hero-actions">
                        <button class="ip-pill" id="copyIpButton" type="button" data-copy-ip="<?php echo $ip; ?>">
                            <span class="ip-pill-main" id="copyIpMain">MINEACLE.NET</span>
                            <span class="ip-pill-sub">CURRENTLY ONLINE: <b id="onlineCount">0</b></span>
                        </button>
                        <a class="hero-support" href="<?php echo $discord; ?>" target="_blank" rel="noopener">Discord Support</a>
                    </div>
                </div>
                <div class="hero-mark" aria-hidden="true">
                    <img src="assets/brand/mineacle-m.webp?v=6.10.0" alt="">
                </div>
            </section>

            <section class="info-grid" aria-label="Mineacle Client Guard protection modules">
                <button class="info-card module" type="button" data-info="client" aria-haspopup="dialog">
                    <span class="info-index">01</span>
                    <strong>Client Guard</strong>
                    <small>Allowed-client enforcement, suspicious brand review, and safer login signals</small>
                    <span class="click-cue">Open details</span>
                </button>
                <button class="info-card module" type="button" data-info="combat" aria-haspopup="dialog">
                    <span class="info-index">02</span>
                    <strong>Combat Protection</strong>
                    <small>Detection support for unfair combat, movement, reach, and impossible interactions</small>
                    <span class="click-cue">Open details</span>
                </button>
                <button class="info-card module" type="button" data-info="community" aria-haspopup="dialog">
                    <span class="info-index">03</span>
                    <strong>Community Safety</strong>
                    <small>Protection for the economy, PvP outcomes, builds, and long-term server trust</small>
                    <span class="click-cue">Open details</span>
                </button>
            </section>
        </section>

        <section class="table-module module" aria-labelledby="recentBansTitle">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Records</p>
                    <h2 id="recentBansTitle">Recent Active Bans</h2>
                </div>
                <div class="table-meta" id="tableMeta">Loading records</div>
            </div>
            <div class="table-wrap">
                <table class="bans-table">
                    <thead>
                        <tr><th>Player</th><th>Reason</th><th>Staff</th><th>Server</th><th>Date</th><th>Status</th><th>Profile</th></tr>
                    </thead>
                    <tbody id="bansTableBody"><tr><td colspan="7" class="loading-cell">Loading active bans</td></tr></tbody>
                </table>
            </div>
            <div class="pagination" id="pagination" hidden>
                <button id="prevPage" type="button">Previous</button>
                <span id="pageInfo">Page 1</span>
                <button id="nextPage" type="button">Next</button>
            </div>
        </section>

        <footer class="footer-module module" aria-label="Mineacle footer">
            <section class="footer-brand">
                <img class="footer-logo" src="assets/brand/mineacle-studios.webp?v=6.10.0" alt="Mineacle Studios">
                <h2>Mineacle</h2>
                <p>Transparent active ban records for a protected Minecraft survival community</p>
                <div class="footer-socials">
                    <a href="<?php echo $discord; ?>" target="_blank" rel="noopener" aria-label="Discord"><img src="assets/icons/discord.svg?v=6.10.0" alt=""></a>
                    <a href="<?php echo $x; ?>" target="_blank" rel="noopener" aria-label="X"><img src="assets/icons/x.svg?v=6.10.0" alt=""></a>
                </div>
            </section>
            <nav class="footer-column" aria-label="Quick links"><h3>Quick Links</h3><a href="<?php echo $bans; ?>">Bans</a><a href="<?php echo $stats; ?>">Stats</a><a href="<?php echo $vote; ?>">Vote</a><a href="<?php echo $store; ?>">Store</a></nav>
            <nav class="footer-column" aria-label="Support"><h3>Support</h3><a href="<?php echo $discord; ?>" target="_blank" rel="noopener">Discord</a><a href="mailto:<?php echo $support; ?>">Contact</a><a href="<?php echo $bans; ?>">Records</a></nav>
            <nav class="footer-column" aria-label="Legal"><h3>Legal</h3><a href="#">Terms of Use</a><a href="#">Privacy Policy</a><a href="#">Appeal Policy</a></nav>
            <div class="footer-bottom"><span>Copyright © 2026 Mineacle Network. All Rights Reserved.</span><span>Not affiliated with Microsoft or Mojang AB.</span></div>
        </footer>
    </section>
</main>

<div class="modal-backdrop" id="modalBackdrop" hidden>
    <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <button class="modal-close" id="modalClose" type="button" aria-label="Close">×</button>
        <div id="modalContent"></div>
    </section>
</div>
<?php mineacle_page_end(); ?>
