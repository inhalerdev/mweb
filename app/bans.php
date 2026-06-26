<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$config = mineacle_config();
$site = $config['site'];
mineacle_head('Public Ban Records', 'Search active Mineacle Network ban records and view appeal details for player bans handled by staff and MineacleClientGuard');
mineacle_header('bans');
?>
<main class="page-shell">
    <section class="hero" aria-labelledby="hero-title">
        <div class="hero-bg" aria-hidden="true"></div>
        <div class="hero-content">
            <p class="eyebrow">Mineacle Network Enforcement</p>
            <h1 id="hero-title">Public Ban Records</h1>
            <p class="hero-copy">Active bans are listed publicly so players can see how Mineacle keeps Survival fair. Staff and MineacleClientGuard review client, movement, combat, and building patterns before action is taken.</p>
            <div class="hero-actions">
                <a href="#active-bans" class="btn btn-primary">Search Records</a>
                <a href="<?= h((string) $site['discord']) ?>" class="btn btn-secondary" rel="noopener">Appeal on Discord</a>
            </div>
        </div>
        <div class="hero-card" aria-label="MineacleClientGuard review stages">
            <div class="review-card">
                <span>01</span>
                <h2>Allowed clients</h2>
                <p>Checks launcher and loader signals when available, then compares them with clients allowed on Mineacle.</p>
            </div>
            <div class="review-card">
                <span>02</span>
                <h2>Movement review</h2>
                <p>Reviews fly-like movement, odd vertical changes, and knockback that does not match normal play.</p>
            </div>
            <div class="review-card">
                <span>03</span>
                <h2>Combat & building</h2>
                <p>Looks for repeated attack timing, aura-like hits, fast placement, auto placement, and scaffold-style building.</p>
            </div>
            <div class="review-card">
                <span>04</span>
                <h2>Evidence over time</h2>
                <p>One strange moment is not enough. Alerts build history first; repeated clear patterns can lead to stronger action.</p>
            </div>
        </div>
    </section>

    <section class="bans-panel" id="active-bans" aria-labelledby="active-bans-title">
        <div class="panel-topline">
            <div>
                <p class="eyebrow">Search Records</p>
                <h2 id="active-bans-title">Active bans</h2>
            </div>
            <div class="records-state" data-records-state>Loading records</div>
        </div>

        <form class="ban-search js-ban-search-form" role="search" autocomplete="off">
            <label class="sr-only" for="ban-search-input">Search bans</label>
            <input id="ban-search-input" class="js-ban-search" type="search" name="search" maxlength="32" placeholder="Search player, UUID, or reason" aria-label="Search bans">
            <button class="search-clear js-ban-clear" type="button" aria-label="Clear search" hidden>×</button>
            <button class="search-submit" type="submit">Search</button>
        </form>

        <div class="ban-table-wrap">
            <div class="ban-table js-ban-table" aria-live="polite">
                <div class="table-loading">Loading active bans</div>
            </div>
        </div>

        <div class="ban-pagination" aria-label="Ban table pagination">
            <button class="js-ban-prev" type="button" disabled>Previous</button>
            <span class="js-ban-meta">Page <span class="js-ban-page">1</span></span>
            <button class="js-ban-next" type="button" disabled>Next</button>
        </div>
    </section>

    <section class="seo-section" aria-labelledby="about-bans-title">
        <h2 id="about-bans-title">About Mineacle bans</h2>
        <p>Mineacle publishes active ban records for transparency while keeping private moderation details protected. Temporary bans expire automatically when their timer ends. Permanent player bans may include appeal or unban options when eligible. IP bans are shown without exposing private address data.</p>
    </section>
</main>
<?php
mineacle_footer();
