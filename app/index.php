<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$config = mineacle_config();
$site = $config['site'] ?? [];
$home = h((string) ($site['home'] ?? 'https://mineacle.net'));
$store = h((string) ($site['store'] ?? 'https://store.mineacle.net'));
$bans = h((string) ($site['bans'] ?? 'https://bans.mineacle.net'));
$stats = h((string) ($site['stats'] ?? 'https://stats.mineacle.net'));
$vote = h((string) ($site['vote'] ?? 'https://vote.mineacle.net'));
$discord = h((string) ($site['discord'] ?? 'https://discord.gg/VwbwWftefM'));
$x = h((string) ($site['x'] ?? 'https://x.com/mineaclenetwork'));
$serverIpRaw = (string) ($site['ip'] ?? 'mineacle.net');
$serverIp = h($serverIpRaw);
$serverIpDisplay = h(strtoupper($serverIpRaw));

mineacle_page_head('Home', 'home');

?>
<main class="mineacle-home-shell" data-mineacle-home>
    <aside class="mineacle-home-rail" aria-label="Mineacle navigation">
        <a class="mineacle-home-logo" href="<?php echo $home; ?>" aria-label="Mineacle Home">
            <img src="assets/mineacle-logo-purple.png?v=figma1.0.0" alt="Mineacle">
        </a>

        <div class="mineacle-home-rail-label" aria-hidden="true">Mineacle</div>

        <nav class="mineacle-home-nav" aria-label="Primary links">
            <a class="mineacle-home-nav-link mineacle-home-nav-stats" href="<?php echo $stats; ?>" aria-label="Stats" title="Stats">
                <span class="mineacle-home-icon mineacle-home-icon-stats"></span>
            </a>
            <a class="mineacle-home-nav-link mineacle-home-nav-store" href="<?php echo $store; ?>" aria-label="Store" title="Store">
                <span class="mineacle-home-icon mineacle-home-icon-store"></span>
            </a>
            <a class="mineacle-home-nav-link mineacle-home-nav-vote" href="<?php echo $vote; ?>" aria-label="Vote" title="Vote">
                <span class="mineacle-home-icon mineacle-home-icon-vote"></span>
            </a>
            <a class="mineacle-home-nav-link mineacle-home-nav-bans" href="<?php echo $bans; ?>" aria-label="Bans" title="Bans">
                <span class="mineacle-home-icon mineacle-home-icon-bans"></span>
            </a>
        </nav>

        <div class="mineacle-home-rail-bottom">
            <a class="mineacle-home-discord" href="<?php echo $discord; ?>" target="_blank" rel="noopener" aria-label="Discord" title="Discord">
                <span class="mineacle-home-icon mineacle-home-icon-discord"></span>
            </a>
            <a class="mineacle-home-x" href="<?php echo $x; ?>" target="_blank" rel="noopener" aria-label="X / Twitter" title="X / Twitter">
                <span class="mineacle-home-icon mineacle-home-icon-x"></span>
            </a>
        </div>
    </aside>

    <section class="mineacle-home-canvas" aria-label="Mineacle home">
        <section class="mineacle-home-top-row">
            <article class="mineacle-panel mineacle-hero-panel" aria-labelledby="mineacleHomeTitle">
                <div>
                    <p class="mineacle-panel-kicker">Mineacle Network</p>
                    <h1 id="mineacleHomeTitle">Hero Banner</h1>
                    <p>Server highlights, seasonal updates, and the main callout live here.</p>
                </div>
                <button class="mineacle-copy-ip" type="button" data-copy-ip="<?php echo $serverIp; ?>" data-default-label="<?php echo $serverIpDisplay; ?>">
                    <span class="mineacle-home-server-ip"><?php echo $serverIpDisplay; ?></span>
                    <small>Click to copy</small>
                </button>
            </article>

            <aside class="mineacle-panel mineacle-player-panel" aria-labelledby="mineaclePlayerTitle">
                <img src="assets/mineacle-square-logo.png?v=figma1.0.0" alt="" aria-hidden="true">
                <h2 id="mineaclePlayerTitle">Player Skin<br>+ Basic Stats</h2>
                <dl>
                    <div><dt>Online</dt><dd id="mineaclePlayerCountValue">0</dd></div>
                    <div><dt>Server</dt><dd><?php echo $serverIpDisplay; ?></dd></div>
                </dl>
            </aside>
        </section>

        <section class="mineacle-home-tile-row" aria-label="Network shortcuts">
            <a class="mineacle-tile mineacle-tile-store" href="<?php echo $store; ?>">
                <span class="mineacle-home-icon mineacle-home-icon-store"></span>
                <strong>Store</strong>
            </a>
            <a class="mineacle-tile mineacle-tile-bans" href="<?php echo $bans; ?>">
                <span class="mineacle-home-icon mineacle-home-icon-bans"></span>
                <strong>Bans</strong>
            </a>
            <a class="mineacle-tile mineacle-tile-vote" href="<?php echo $vote; ?>">
                <span class="mineacle-home-icon mineacle-home-icon-vote"></span>
                <strong>Vote</strong>
            </a>
            <a class="mineacle-tile mineacle-tile-stats" href="<?php echo $stats; ?>">
                <span class="mineacle-home-icon mineacle-home-icon-stats"></span>
                <strong>Stats</strong>
            </a>
        </section>

        <section class="mineacle-home-world-row" aria-label="World status">
            <article class="mineacle-panel mineacle-world-card">
                <h2>Players In<br>Overworld + Info</h2>
                <p>Live world population and useful world details will sit here.</p>
            </article>
            <article class="mineacle-panel mineacle-world-card">
                <h2>Players In<br>Nether + Info</h2>
                <p>Nether activity, events, and location notes can populate from the database.</p>
            </article>
            <article class="mineacle-panel mineacle-world-card">
                <h2>Players In<br>End + Info</h2>
                <p>End activity and server state details complete the row.</p>
            </article>
        </section>

        <section class="mineacle-community-panel" aria-labelledby="mineacleCommunityTitle">
            <div class="mineacle-community-copy">
                <p class="mineacle-panel-kicker">Community</p>
                <h2 id="mineacleCommunityTitle">Stay connected with the Minemen <span>community</span></h2>
                <p>Stay connected across social platforms for updates, clips, announcements, and community highlights.</p>
                <a href="<?php echo $discord; ?>" target="_blank" rel="noopener">Join the community</a>
            </div>
            <div class="mineacle-social-list" aria-label="Social links">
                <a href="<?php echo $discord; ?>" target="_blank" rel="noopener"><span class="mineacle-social-icon mineacle-home-icon mineacle-home-icon-discord"></span><strong>Discord</strong><small>Chat with players</small></a>
                <a href="<?php echo $x; ?>" target="_blank" rel="noopener"><span class="mineacle-social-icon mineacle-home-icon mineacle-home-icon-x"></span><strong>X/Twitter</strong><small>Follow for updates</small></a>
                <a href="<?php echo $home; ?>" rel="noopener"><span class="mineacle-social-icon mineacle-social-youtube">YT</span><strong>YouTube</strong><small>Watch highlights</small></a>
                <a href="<?php echo $home; ?>" rel="noopener"><span class="mineacle-social-icon mineacle-social-tiktok">TK</span><strong>TikTok</strong><small>Short clips</small></a>
            </div>
        </section>

        <footer class="mineacle-footer-panel">
            <img src="assets/m-studios.png?v=figma1.0.0" alt="Mineacle Studios">
            <span>Footer</span>
        </footer>
    </section>
</main>
<?php mineacle_page_end('home'); ?>
