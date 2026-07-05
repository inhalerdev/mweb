<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/stats-lib.php';

$site = mineacle_config()['site'] ?? [];
$sort = trim((string) ($_GET['sort'] ?? 'playtime'));
$search = trim((string) ($_GET['search'] ?? ''));
$players = [];
$loadError = false;

try {
    $players = mineacle_stats_players(25, 0, $sort, $search);
} catch (Throwable) {
    $loadError = true;
}

function mineacle_players_link(mixed $url): string
{
    $value = trim((string) $url);

    return $value !== '' ? $value : '#';
}

function mineacle_players_profile_url(array $player): string
{
    return '/player/' . rawurlencode(mineacle_stats_username($player));
}

$navLinks = [
    ['key' => 'home', 'url' => $site['home_url'] ?? '/'],
    ['key' => 'stats', 'label' => 'Leaderboards', 'url' => $site['stats_url'] ?? '/players'],
    ['key' => 'bans', 'url' => $site['bans_url'] ?? '#'],
];
$storeLink = ['key' => 'store', 'url' => $site['store_url'] ?? '#'];
$currentNavKey = 'stats';
$sortOptions = [
    'playtime' => 'Playtime',
    'online' => 'Online',
    'money' => 'Money',
    'kills' => 'Kills',
    'team' => 'Team',
    'first_joined' => 'First Joined',
    'last_seen' => 'Last Seen',
];

mineacle_page_head('Leaderboards');
?>
<div class="site-shell">
    <aside class="rail" aria-label="Primary navigation">
        <a class="rail-logo" href="<?php echo h(mineacle_players_link($site['home_url'] ?? '/')); ?>" aria-label="Home">
            <img src="/assets/brand/nav-logo.png" alt="">
        </a>

        <nav class="rail-nav" aria-label="Server links">
            <?php foreach ($navLinks as $link): ?>
                <?php $isActiveNavLink = (string) $link['key'] === $currentNavKey; ?>
                <a class="rail-link<?php echo $isActiveNavLink ? ' is-active' : ''; ?>" href="<?php echo h(mineacle_players_link($link['url'])); ?>" aria-label="<?php echo h((string) ($link['label'] ?? $link['key'])); ?>"<?php echo $isActiveNavLink ? ' aria-current="page"' : ''; ?>>
                    <?php echo mineacle_page_icon((string) $link['key']); ?>
                </a>
            <?php endforeach; ?>
            <?php $isStoreActive = (string) $storeLink['key'] === $currentNavKey; ?>
            <a class="rail-link rail-store-button<?php echo $isStoreActive ? ' is-active' : ''; ?>" href="<?php echo h(mineacle_players_link($storeLink['url'])); ?>" aria-label="Store"<?php echo $isStoreActive ? ' aria-current="page"' : ''; ?>>
                <?php echo mineacle_page_icon((string) $storeLink['key']); ?>
            </a>
        </nav>

        <div class="rail-social" aria-label="Social links">
            <a class="rail-link" href="<?php echo h(mineacle_players_link($site['discord_url'] ?? '#')); ?>" aria-label="Discord">
                <?php echo mineacle_page_icon('discord'); ?>
            </a>
            <a class="rail-link" href="<?php echo h(mineacle_players_link($site['x_url'] ?? '#')); ?>" aria-label="X">
                <?php echo mineacle_page_icon('x'); ?>
            </a>
        </div>
    </aside>

    <main class="home-grid players-page" aria-label="Player stats">
        <?php mineacle_page_search_header($site); ?>

        <section class="panel players-header">
            <div>
                <h1>Leaderboards</h1>
                <p>Browse Mineacle player stats and open a full profile.</p>
            </div>

            <form class="players-filter" method="get" action="/players">
                <label class="sr-only" for="playersSearch">Search players</label>
                <input id="playersSearch" name="search" type="search" placeholder="Search players.." value="<?php echo h($search); ?>" autocomplete="off">

                <label class="sr-only" for="playersSort">Sort players</label>
                <select id="playersSort" name="sort">
                    <?php foreach ($sortOptions as $value => $label): ?>
                        <option value="<?php echo h($value); ?>"<?php echo $sort === $value ? ' selected' : ''; ?>>
                            <?php echo h($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Apply</button>
            </form>
        </section>

        <?php if ($loadError): ?>
            <section class="panel profile-message">
                <h1>Unable to load player stats right now</h1>
                <p>Please try again shortly.</p>
            </section>
        <?php elseif ($players === []): ?>
            <section class="panel profile-message">
                <h1>No player stats found yet</h1>
                <p>Stats will appear here once players join the server.</p>
            </section>
        <?php else: ?>
            <section class="players-list" aria-label="Player list">
                <?php foreach ($players as $player): ?>
                    <?php
                    $skin = is_array($player['skin'] ?? null) ? $player['skin'] : [];
                    $head = trim((string) ($skin['head'] ?? ''));
                    $online = mineacle_stats_online($player);
                    ?>
                    <a class="player-card" href="<?php echo h(mineacle_players_profile_url($player)); ?>">
                        <span class="player-card-head">
                            <?php if ($head !== ''): ?>
                                <img src="<?php echo h($head); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async" draggable="false">
                            <?php endif; ?>
                        </span>
                        <span class="player-card-main">
                            <strong><?php echo h(mineacle_stats_display_name($player)); ?></strong>
                            <span>@<?php echo h(mineacle_stats_username($player)); ?></span>
                        </span>
                        <span class="player-card-stat"><?php echo h(mineacle_stats_rank_name($player)); ?></span>
                        <span class="player-card-stat"><?php echo h(mineacle_stats_team_name($player)); ?></span>
                        <span class="player-card-stat"><?php echo h(mineacle_stats_playtime_label($player)); ?></span>
                        <span class="player-card-stat"><?php echo h(number_format(mineacle_stats_int($player['kills'] ?? 0))); ?> kills</span>
                        <span class="player-card-status <?php echo $online ? 'is-online' : 'is-offline'; ?>">
                            <span aria-hidden="true"></span>
                            <?php echo h(mineacle_stats_last_seen_label($player)); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php mineacle_page_footer($site); ?>
    </main>
</div>
<?php mineacle_page_end(); ?>
