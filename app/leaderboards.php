<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/stats-lib.php';

$site = mineacle_config()['site'] ?? [];
$homeUrl = mineacle_page_home_url($site);
$leaderboardsUrl = mineacle_page_leaderboards_url($site);
$view = strtolower(trim((string) ($_GET['view'] ?? 'players')));
$view = in_array($view, ['players', 'teams'], true) ? $view : 'players';
$search = trim((string) ($_GET['search'] ?? ''));
$players = [];
$teams = [];
$loadError = false;

try {
    if ($view === 'teams') {
        $teams = mineacle_stats_teams(50, 0, 'kd', $search);
    } else {
        $players = mineacle_stats_players(200, 0, 'playtime', $search);
    }
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

function mineacle_leaderboards_view_url(string $view, string $search = ''): string
{
    $params = ['view' => $view];

    if ($search !== '') {
        $params['search'] = $search;
    }

    return 'https://mineacle.net/leaderboards.php?' . http_build_query($params);
}

function mineacle_leaderboards_money_from_cents(int $cents): string
{
    return '$' . number_format($cents / 100, 2);
}

function mineacle_leaderboards_team_name(array $team): string
{
    $name = trim((string) ($team['name'] ?? ''));

    return $name !== '' ? $name : 'Unnamed Team';
}

function mineacle_leaderboards_team_money(array $team): string
{
    $formatted = trim((string) ($team['balance_formatted'] ?? ''));

    if ($formatted !== '') {
        return $formatted;
    }

    $cents = mineacle_stats_int($team['balance_cents'] ?? 0);

    if ($cents > 0) {
        return mineacle_leaderboards_money_from_cents($cents);
    }

    $balance = mineacle_stats_float($team['balance'] ?? 0);

    return '$' . number_format($balance, 2);
}

function mineacle_leaderboards_kd(int $kills, int $deaths, mixed $stored = null): string
{
    $ratio = mineacle_stats_float($stored);

    if ($ratio <= 0 && ($kills > 0 || $deaths > 0)) {
        $ratio = $kills / max(1, $deaths);
    }

    return number_format($ratio, 2);
}

$navLinks = [
    ['key' => 'home', 'url' => $homeUrl],
    ['key' => 'vote', 'url' => $site['vote_url'] ?? '#'],
    ['key' => 'stats', 'label' => 'Leaderboards', 'url' => $leaderboardsUrl],
    ['key' => 'bans', 'url' => $site['bans_url'] ?? '#'],
];
$storeLink = ['key' => 'store', 'url' => $site['store_url'] ?? '#'];
$currentNavKey = 'stats';
$hasResults = $view === 'teams' ? $teams !== [] : $players !== [];
$resultCount = $view === 'teams' ? count($teams) : count($players);
$viewTitle = $view === 'teams' ? 'Teams' : 'Survival Players';
$viewDescription = $view === 'teams'
    ? 'Top 50 teams from Mineacle Core team standings, ranked by team K/D performance.'
    : 'Top 200 Survival players from Mineacle Core profile stats.';

mineacle_page_head('Leaderboards');
?>
<div class="site-shell">
    <aside class="rail" aria-label="Primary navigation">
        <a class="rail-logo" href="<?php echo h($homeUrl); ?>" aria-label="Home">
            <img src="/assets/brand/nav-logo-web.png" alt="">
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

    <main class="home-grid players-page leaderboard-page" aria-label="Leaderboards">
        <?php mineacle_page_search_header($site); ?>

        <section class="panel players-header leaderboard-hero">
            <div class="leaderboard-copy">
                <p>Survival Rankings</p>
                <h1>Leaderboards</h1>
                <span><?php echo h($viewDescription); ?></span>
            </div>

            <div class="leaderboard-controls">
                <nav class="leaderboard-tabs leaderboard-mode-tabs" aria-label="Leaderboard views">
                    <a class="<?php echo $view === 'players' ? 'is-active' : ''; ?>" href="<?php echo h(mineacle_leaderboards_view_url('players', $search)); ?>"<?php echo $view === 'players' ? ' aria-current="page"' : ''; ?>>
                        Survival Players
                    </a>
                    <a class="<?php echo $view === 'teams' ? 'is-active' : ''; ?>" href="<?php echo h(mineacle_leaderboards_view_url('teams', $search)); ?>"<?php echo $view === 'teams' ? ' aria-current="page"' : ''; ?>>
                        Teams
                    </a>
                </nav>

                <form class="players-filter" method="get" action="<?php echo h($leaderboardsUrl); ?>">
                    <input type="hidden" name="view" value="<?php echo h($view); ?>">
                    <label class="sr-only" for="playersSearch"><?php echo $view === 'teams' ? 'Search teams' : 'Search players'; ?></label>
                    <input id="playersSearch" name="search" type="search" placeholder="<?php echo $view === 'teams' ? 'Search teams..' : 'Search players..'; ?>" value="<?php echo h($search); ?>" autocomplete="off">
                    <button type="submit">Search</button>
                </form>
            </div>
        </section>

        <?php if ($loadError): ?>
            <section class="panel profile-message">
                <h1>Unable to load leaderboards right now</h1>
                <p>Check the Mineacle Core and LiteBans database variables, then try again.</p>
            </section>
        <?php elseif (!$hasResults): ?>
            <section class="panel profile-message">
                <h1>No leaderboard data found yet</h1>
                <p><?php echo $view === 'teams' ? 'Teams will appear here once Mineacle Core writes team standings.' : 'Players will appear here once Mineacle Core writes profile stats.'; ?></p>
            </section>
        <?php else: ?>
            <section class="panel leaderboard-board leaderboard-simple-board" aria-label="<?php echo h($viewTitle); ?>">
                <div class="leaderboard-board-header">
                    <div>
                        <h2><?php echo h($viewTitle); ?></h2>
                        <p><?php echo $view === 'teams' ? 'Showing top 50 teams' : 'Showing top 200 players'; ?></p>
                    </div>
                    <span><?php echo h(number_format($resultCount)); ?> results</span>
                </div>

                <?php if ($view === 'teams'): ?>
                    <div class="leaderboard-table-head leaderboard-table-head-teams" aria-hidden="true">
                        <span>#</span>
                        <span>Team</span>
                        <span>Members</span>
                        <span>Online</span>
                        <span>Balance</span>
                        <span>Kills</span>
                        <span>K/D</span>
                        <span>K/D Rank</span>
                        <span>Capital Rank</span>
                    </div>

                    <div class="players-list">
                        <?php foreach ($teams as $team): ?>
                            <?php
                            $rank = mineacle_stats_int($team['rank'] ?? 0);
                            $kills = mineacle_stats_int($team['kills'] ?? 0);
                            $deaths = mineacle_stats_int($team['deaths'] ?? 0);
                            ?>
                            <article class="player-card leaderboard-table-row leaderboard-team-row">
                                <span class="leaderboard-team-rank">#<?php echo h((string) $rank); ?></span>
                                <span class="player-card-main">
                                    <strong><?php echo h(mineacle_leaderboards_team_name($team)); ?></strong>
                                    <span><?php echo trim((string) ($team['owner_name'] ?? '')) !== '' ? 'Owner: ' . h((string) $team['owner_name']) : 'Team profile'; ?></span>
                                </span>
                                <span class="player-card-stat"><?php echo h(number_format(mineacle_stats_int($team['members'] ?? 0))); ?></span>
                                <span class="player-card-status <?php echo mineacle_stats_int($team['online_members'] ?? 0) > 0 ? 'is-online' : 'is-offline'; ?>">
                                    <span aria-hidden="true"></span>
                                    <?php echo h(number_format(mineacle_stats_int($team['online_members'] ?? 0))); ?>
                                </span>
                                <span class="player-card-stat"><?php echo h(mineacle_leaderboards_team_money($team)); ?></span>
                                <span class="player-card-stat"><?php echo h(number_format($kills)); ?></span>
                                <span class="player-card-stat"><?php echo h(mineacle_leaderboards_kd($kills, $deaths, $team['kd_ratio'] ?? 0)); ?></span>
                                <span class="player-card-stat"><?php echo h(mineacle_stats_rank_label($team['kd_rank'] ?? 0)); ?></span>
                                <span class="player-card-stat"><?php echo h(mineacle_stats_rank_label($team['capital_rank'] ?? 0)); ?></span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="leaderboard-table-head leaderboard-table-head-players" aria-hidden="true">
                        <span>#</span>
                        <span>Player</span>
                        <span>Team</span>
                        <span>Balance</span>
                        <span>Kills</span>
                        <span>Deaths</span>
                        <span>K/D</span>
                        <span>Playtime</span>
                        <span>Status</span>
                    </div>

                    <div class="players-list">
                        <?php foreach ($players as $index => $player): ?>
                            <?php
                            $skin = is_array($player['skin'] ?? null) ? $player['skin'] : [];
                            $head = trim((string) ($skin['head'] ?? ''));
                            $online = mineacle_stats_online($player);
                            ?>
                            <a class="player-card leaderboard-table-row leaderboard-player-row" href="<?php echo h(mineacle_players_profile_url($player)); ?>">
                                <span class="leaderboard-team-rank">#<?php echo h((string) ($index + 1)); ?></span>
                                <span class="player-card-main leaderboard-player-main">
                                    <span class="player-card-head">
                                        <?php if ($head !== ''): ?>
                                            <img src="<?php echo h($head); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async" draggable="false">
                                        <?php endif; ?>
                                    </span>
                                    <span>
                                        <strong><?php echo h(mineacle_stats_display_name($player)); ?></strong>
                                        <span>@<?php echo h(mineacle_stats_username($player)); ?></span>
                                    </span>
                                </span>
                                <span class="player-card-stat"><?php echo h(mineacle_stats_team_name($player)); ?></span>
                                <span class="player-card-stat"><?php echo h(mineacle_stats_money_label($player)); ?></span>
                                <span class="player-card-stat"><?php echo h(number_format(mineacle_stats_int($player['kills'] ?? 0))); ?></span>
                                <span class="player-card-stat"><?php echo h(number_format(mineacle_stats_int($player['deaths'] ?? 0))); ?></span>
                                <span class="player-card-stat"><?php echo h(mineacle_leaderboards_kd(mineacle_stats_int($player['kills'] ?? 0), mineacle_stats_int($player['deaths'] ?? 0), $player['kd_ratio'] ?? 0)); ?></span>
                                <span class="player-card-stat"><?php echo h(mineacle_stats_playtime_label($player)); ?></span>
                                <span class="player-card-status <?php echo $online ? 'is-online' : 'is-offline'; ?>">
                                    <span aria-hidden="true"></span>
                                    <?php echo h(mineacle_stats_last_seen_label($player)); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php mineacle_page_footer($site); ?>
    </main>
</div>
<?php mineacle_page_end(); ?>
