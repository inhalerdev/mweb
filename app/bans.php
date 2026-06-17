<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

mineacle_page_head('Bans | Mineacle Network');
mineacle_header('bans');

?>
<main class="bans-v3-page">
    <section class="bans-v3-hero bans-v32-logo-only-fold bans-v34-background-fold bans-records-hero" aria-label="Public ban records">
        <div class="ban-hero-content">
            <div class="ban-hero-copy">
                <h1>Public Ban Records</h1>
                <p>Active bans are listed publicly so players can see how Mineacle keeps Survival fair. Staff and MineacleClientGuard review client, movement, combat, and building patterns before action is taken.</p>
            </div>

            <div class="guard-info-tiles" aria-label="What MineacleClientGuard watches for">
                <article class="guard-info-tile">
                    <span class="guard-info-number">01</span>
                    <h2>Allowed clients</h2>
                    <p>Checks the launcher or loader signal when it is available, then compares it with clients allowed on Mineacle.</p>
                </article>
                <article class="guard-info-tile">
                    <span class="guard-info-number">02</span>
                    <h2>Movement review</h2>
                    <p>Reviews fly-like movement, odd vertical changes, and knockback that does not match normal play.</p>
                </article>
                <article class="guard-info-tile">
                    <span class="guard-info-number">03</span>
                    <h2>Combat &amp; building</h2>
                    <p>Looks for repeated attack timing, aura-like hits, fast placement, auto placement, and scaffold-style building.</p>
                </article>
                <article class="guard-info-tile">
                    <span class="guard-info-number">04</span>
                    <h2>Evidence over time</h2>
                    <p>One strange moment is not enough. Alerts build history first; repeated clear patterns can lead to stronger action.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="bans-v3-results" id="ban-results" aria-label="Active ban results">
        <div class="bans-v3-results-head">
            <div>
                <span class="bans-v3-kicker">Search Records</span>
                <h2>Active bans</h2>
            </div>
            <div class="bans-list-meta js-ban-meta" id="banCount">Loading records</div>
        </div>

        <form class="bans-v3-search js-ban-search-form bans-v31-results-search" id="banSearchForm" role="search">
            <label class="sr-only" for="banSearch">Search bans</label>
            <div class="ban-search-field">
                <input id="banSearch" class="js-ban-search" type="text" name="q" autocomplete="off" placeholder="Search Minecraft username">
                <button class="ban-search-clear js-ban-clear" id="clearSearch" type="button" aria-label="Clear search" title="Clear search">×</button>
            </div>
            <button class="btn red" type="submit">Search</button>
        </form>

        <div class="ban-table-shell">
            <div class="ban-table js-ban-table" id="banList" aria-live="polite">
                <div class="ban-loading">Loading active bans</div>
            </div>
        </div>

        <div class="pagination-row" id="banPagination">
            <button class="btn soft js-ban-prev" id="prevPage" type="button">Previous</button>
            <span class="page-indicator js-ban-page" id="pageInfo">Page 1</span>
            <button class="btn soft js-ban-next" id="nextPage" type="button">Next</button>
        </div>
    </section>
</main>
<?php mineacle_footer(); ?>
