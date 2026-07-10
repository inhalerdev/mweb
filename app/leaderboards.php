<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/stats-lib.php';

$site = mineacle_config()['site'] ?? [];
$homeUrl = mineacle_page_home_url($site);
$leaderboardsUrl = mineacle_page_leaderboards_url($site);
$requestedSort = trim((string) ($_GET['sort'] ?? 'kd'));
$search = trim((string) ($_GET['search'] ?? ''));
$leaderboardTabs = [
    'kd' => [
        'label' => 'K/D Ratio',
        'eyebrow' => 'Combat',
        'description' => 'Top player combat performance ranked by kill/death ratio.',
    ],
    'money' => [
        'label' => 'Balance Top',
        'eyebrow' => 'Economy',
        'description' => 'The richest players on Mineacle, sorted by stored balance.',
    ],
    'team' => [
        'label' => 'Top Teams',
        'eyebrow' => 'Teams',
        'description' => 'Collective team standings built from stored member stats.',
    ],
];
$sort = isset($leaderboardTabs[$requestedSort]) ? $requestedSort : 'kd';
$querySort = $sort === 'team' ? 'money' : $sort;
$players = [];
$loadError = false;

try {
    $players = mineacle_stats_players($sort === 'team' ? 100 : 50, 0, $querySort, $search);
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

function mineacle_players_tab_url(string $sort, string $search): string
{
    $params = ['sort' => $sort];

    if ($search !== '') {
        $params['search'] = $search;
    }

    return 'https://mineacle.net/leaderboards.php?' . http_build_query($params);
}

function mineacle_players_sort_label(string $sort, array $tabs): string
{
    return (string) ($tabs[$sort]['label'] ?? $tabs['kd']['label'] ?? 'K/D Ratio');
}

function mineacle_players_visible_online_count(array $players): int
{
    $count = 0;

    foreach ($players as $player) {
        if (is_array($player) && mineacle_stats_online($player)) {
            $count++;
        }
    }

    return $count;
}

function mineacle_players_sum_int(array $players, string $column): int
{
    $total = 0;

    foreach ($players as $player) {
        if (!is_array($player)) {
            continue;
        }

        $total += mineacle_stats_int($player[$column] ?? 0);
    }

    return $total;
}

function mineacle_players_money_from_cents(int $cents): string
{
    return '$' . number_format($cents / 100, 2);
}

function mineacle_players_sort_value(array $player, string $sort): string
{
    return match ($sort) {
        'money' => mineacle_stats_money_label($player),
        'team' => mineacle_stats_team_name($player),
        default => number_format(mineacle_stats_float($player['kd_ratio'] ?? 0), 2) . ' K/D',
    };
}

function mineacle_players_table_primary_label(string $sort): string
{
    return match ($sort) {
        'money' => 'Balance',
        'team' => 'Team',
        default => 'K/D',
    };
}

function mineacle_players_metric(string $label, string $value, string $note): void
{
    echo '<article class="leaderboard-metric">';
    echo '<span>' . h($label) . '</span>';
    echo '<strong>' . h($value) . '</strong>';
    echo '<small>' . h($note) . '</small>';
    echo '</article>';
}

function mineacle_players_team_rows(array $players): array
{
    $teams = [];

    foreach ($players as $player) {
        if (!is_array($player)) {
            continue;
        }

        $teamName = mineacle_stats_team_name($player);

        if ($teamName === 'No Team') {
            continue;
        }

        $key = strtolower(trim($teamName));

        if (!isset($teams[$key])) {
            $teams[$key] = [
                'name' => $teamName,
                'members' => 0,
                'online' => 0,
                'balance_cents' => 0,
                'kills' => 0,
                'deaths' => 0,
                'playtime_seconds' => 0,
                'featured_player' => null,
                'featured_balance' => -1,
            ];
        }

        $balance = mineacle_stats_int($player['balance_cents'] ?? 0);
        $teams[$key]['members']++;
        $teams[$key]['online'] += mineacle_stats_online($player) ? 1 : 0;
        $teams[$key]['balance_cents'] += $balance;
        $teams[$key]['kills'] += mineacle_stats_int($player['kills'] ?? 0);
        $teams[$key]['deaths'] += mineacle_stats_int($player['deaths'] ?? 0);
        $teams[$key]['playtime_seconds'] += mineacle_stats_int($player['playtime_seconds'] ?? 0);

        if ($balance > $teams[$key]['featured_balance']) {
            $teams[$key]['featured_player'] = $player;
            $teams[$key]['featured_balance'] = $balance;
        }
    }

    $teams = array_values($teams);

    usort($teams, static function (array $a, array $b): int {
        $balance = ($b['balance_cents'] ?? 0) <=> ($a['balance_cents'] ?? 0);

        if ($balance !== 0) {
            return $balance;
        }

        return ($b['kills'] ?? 0) <=> ($a['kills'] ?? 0);
    });

    foreach ($teams as $index => $team) {
        $teams[$index]['rank'] = $index + 1;
        $teams[$index]['kd_ratio'] = ((int) $team['kills']) / max(1, (int) $team['deaths']);
    }

    return $teams;
}

function mineacle_players_rank_class(int $rank): string
{
    return match ($rank) {
        1 => 'is-rank-1',
        2 => 'is-rank-2',
        3 => 'is-rank-3',
        default => '',
    };
}

function mineacle_players_order_podium(array $items): array
{
    if (count($items) < 3) {
        return $items;
    }

    return [$items[1], $items[0], $items[2]];
}

function mineacle_players_render_player_podium(array $entry, string $sort): void
{
    $player = is_array($entry['player'] ?? null) ? $entry['player'] : [];
    $rank = (int) ($entry['rank'] ?? 0);
    $skin = is_array($player['skin'] ?? null) ? $player['skin'] : [];
    $head = trim((string) ($skin['head'] ?? ''));

    echo '<a class="leaderboard-podium-card ' . h(mineacle_players_rank_class($rank)) . '" href="' . h(mineacle_players_profile_url($player)) . '">';
    echo '<span class="leaderboard-podium-rank">#' . h((string) $rank) . '</span>';
    echo '<span class="leaderboard-podium-head">';

    if ($head !== '') {
        echo '<img src="' . h($head) . '" alt="" aria-hidden="true" loading="lazy" decoding="async" draggable="false">';
    }

    echo '</span>';
    echo '<span class="leaderboard-podium-copy">';
    echo '<strong>' . h(mineacle_stats_display_name($player)) . '</strong>';
    echo '<small>' . h(mineacle_players_sort_value($player, $sort)) . '</small>';
    echo '</span>';
    echo '</a>';
}

function mineacle_players_render_team_podium(array $team): void
{
    $rank = (int) ($team['rank'] ?? 0);
    $featuredPlayer = is_array($team['featured_player'] ?? null) ? $team['featured_player'] : [];
    $skin = is_array($featuredPlayer['skin'] ?? null) ? $featuredPlayer['skin'] : [];
    $head = trim((string) ($skin['head'] ?? ''));

    echo '<article class="leaderboard-podium-card leaderboard-team-podium-card ' . h(mineacle_players_rank_class($rank)) . '">';
    echo '<span class="leaderboard-podium-rank">#' . h((string) $rank) . '</span>';
    echo '<span class="leaderboard-podium-head">';

    if ($head !== '') {
        echo '<img src="' . h($head) . '" alt="" aria-hidden="true" loading="lazy" decoding="async" draggable="false">';
    }

    echo '</span>';
    echo '<span class="leaderboard-podium-copy">';
    echo '<strong>' . h((string) ($team['name'] ?? 'Team')) . '</strong>';
    echo '<small>' . h(mineacle_players_money_from_cents((int) ($team['balance_cents'] ?? 0))) . ' collective balance</small>';
    echo '</span>';
    echo '</article>';
}

$navLinks = [
    ['key' => 'home', 'url' => $homeUrl],
    ['key' => 'vote', 'url' => $site['vote_url'] ?? '#'],
    ['key' => 'stats', 'label' => 'Leaderboards', 'url' => $leaderboardsUrl],
    ['key' => 'bans', 'url' => $site['bans_url'] ?? '#'],
];
$storeLink = ['key' => 'store', 'url' => $site['store_url'] ?? '#'];
$currentNavKey = 'stats';
$sortLabel = mineacle_players_sort_label($sort, $leaderboardTabs);
$teamRows = mineacle_players_team_rows($players);
$playerPodium = [];

foreach (array_slice($players, 0, 3) as $index => $player) {
    if (is_array($player)) {
        $playerPodium[] = [
            'rank' => $index + 1,
            'player' => $player,
        ];
    }
}

$teamPodium = array_slice($teamRows, 0, 3);
$hasResults = $sort === 'team' ? $teamRows !== [] : $players !== [];
$visiblePlayerCount = count($players);
$visibleOnlineCount = mineacle_players_visible_online_count($players);
$visibleKills = mineacle_players_sum_int($players, 'kills');
$visiblePlaytime = mineacle_stats_playtime_label(['playtime_seconds' => mineacle_players_sum_int($players, 'playtime_seconds')]);

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
                <p><?php echo h((string) ($leaderboardTabs[$sort]['eyebrow'] ?? 'Rankings')); ?></p>
                <h1>Leaderboards</h1>
                <span><?php echo h((string) ($leaderboardTabs[$sort]['description'] ?? 'Track player rankings across Mineacle.')); ?></span>
            </div>

            <div class="leaderboard-controls">
                <nav class="leaderboard-tabs" aria-label="Leaderboard filters">
                    <?php foreach ($leaderboardTabs as $tabKey => $tab): ?>
                        <?php $isActiveTab = $tabKey === $sort; ?>
                        <a class="<?php echo $isActiveTab ? 'is-active' : ''; ?>" href="<?php echo h(mineacle_players_tab_url($tabKey, $search)); ?>"<?php echo $isActiveTab ? ' aria-current="page"' : ''; ?>>
                            <?php echo h((string) $tab['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <form class="players-filter" method="get" action="<?php echo h($leaderboardsUrl); ?>">
                    <input type="hidden" name="sort" value="<?php echo h($sort); ?>">
                    <label class="sr-only" for="playersSearch">Search players or teams</label>
                    <input id="playersSearch" name="search" type="search" placeholder="<?php echo $sort === 'team' ? 'Search teams..' : 'Search players..'; ?>" value="<?php echo h($search); ?>" autocomplete="off">
                    <button type="submit">Search</button>
                </form>
            </div>
        </section>

        <?php if ($loadError): ?>
            <section class="panel profile-message">
                <h1>Unable to load leaderboards right now</h1>
                <p>Please try again shortly.</p>
            </section>
        <?php elseif (!$hasResults): ?>
            <section class="panel profile-message">
                <h1>No leaderboard data found yet</h1>
                <p>Rankings will appear here once Mineacle has enough stored player stats.</p>
            </section>
        <?php else: ?>
            <section class="leaderboard-dashboard" aria-label="Leaderboard dashboard">
                <div class="leaderboard-metrics" aria-label="Visible leaderboard stats">
                    <?php if ($sort === 'team'): ?>
                        <?php
                        mineacle_players_metric('Teams Listed', number_format(count($teamRows)), 'Built from stored profiles');
                        mineacle_players_metric('Players Counted', number_format($visiblePlayerCount), 'Members in ranked teams');
                        mineacle_players_metric('Total Kills', number_format($visibleKills), 'Across ranked members');
                        mineacle_players_metric('Playtime Counted', $visiblePlaytime, 'Across ranked members');
                        ?>
                    <?php else: ?>
                        <?php
                        mineacle_players_metric('Players Listed', number_format($visiblePlayerCount), 'Showing ranked players');
                        mineacle_players_metric('Current Board', $sortLabel, 'Active leaderboard tab');
                        mineacle_players_metric('Online Listed', number_format($visibleOnlineCount), 'Updated from profiles');
                        mineacle_players_metric('Playtime Listed', $visiblePlaytime, 'Across visible players');
                        ?>
                    <?php endif; ?>
                </div>

                <section class="leaderboard-podium" aria-label="Top three <?php echo h($sortLabel); ?>">
                    <?php if ($sort === 'team'): ?>
                        <?php foreach (mineacle_players_order_podium($teamPodium) as $team): ?>
                            <?php mineacle_players_render_team_podium($team); ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach (mineacle_players_order_podium($playerPodium) as $entry): ?>
                            <?php mineacle_players_render_player_podium($entry, $sort); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <?php if ($sort === 'team'): ?>
                    <section class="panel leaderboard-board" aria-label="Team standings">
                        <div class="leaderboard-board-header">
                            <div>
                                <h2>Team Standings</h2>
                                <p>Collective totals from stored member profiles</p>
                            </div>
                            <span><?php echo h(number_format(count($teamRows))); ?> teams</span>
                        </div>

                        <div class="leaderboard-table-head leaderboard-team-table-head" aria-hidden="true">
                            <span>#</span>
                            <span>Team</span>
                            <span>Members</span>
                            <span>Balance</span>
                            <span>Kills</span>
                            <span>K/D</span>
                            <span>Online</span>
                        </div>

                        <div class="players-list">
                            <?php foreach (array_slice($teamRows, 0, 25) as $team): ?>
                                <article class="player-card leaderboard-team-row">
                                    <span class="leaderboard-team-rank">#<?php echo h((string) ($team['rank'] ?? 0)); ?></span>
                                    <span class="player-card-main">
                                        <strong><?php echo h((string) ($team['name'] ?? 'Team')); ?></strong>
                                        <span>Collective team profile</span>
                                    </span>
                                    <span class="player-card-stat"><?php echo h(number_format((int) ($team['members'] ?? 0))); ?> members</span>
                                    <span class="player-card-stat"><?php echo h(mineacle_players_money_from_cents((int) ($team['balance_cents'] ?? 0))); ?></span>
                                    <span class="player-card-stat"><?php echo h(number_format((int) ($team['kills'] ?? 0))); ?> kills</span>
                                    <span class="player-card-stat"><?php echo h(number_format((float) ($team['kd_ratio'] ?? 0), 2)); ?></span>
                                    <span class="player-card-status <?php echo ((int) ($team['online'] ?? 0)) > 0 ? 'is-online' : 'is-offline'; ?>">
                                        <span aria-hidden="true"></span>
                                        <?php echo h(number_format((int) ($team['online'] ?? 0))); ?> online
                                    </span>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <section class="panel leaderboard-board" aria-label="Player list">
                        <div class="leaderboard-board-header">
                            <div>
                                <h2>Player Standings</h2>
                                <p>Sorted by <?php echo h($sortLabel); ?></p>
                            </div>
                            <span><?php echo h(number_format($visiblePlayerCount)); ?> results</span>
                        </div>

                        <div class="leaderboard-table-head" aria-hidden="true">
                            <span></span>
                            <span>Player</span>
                            <span>Rank</span>
                            <span>Team</span>
                            <span><?php echo h(mineacle_players_table_primary_label($sort)); ?></span>
                            <span>Playtime</span>
                            <span>Status</span>
                        </div>

                        <div class="players-list">
                            <?php foreach (array_slice($players, 0, 25) as $player): ?>
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
                                    <span class="player-card-stat"><?php echo h(mineacle_players_sort_value($player, $sort)); ?></span>
                                    <span class="player-card-stat"><?php echo h(mineacle_stats_playtime_label($player)); ?></span>
                                    <span class="player-card-status <?php echo $online ? 'is-online' : 'is-offline'; ?>">
                                        <span aria-hidden="true"></span>
                                        <?php echo h(mineacle_stats_last_seen_label($player)); ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php mineacle_page_footer($site); ?>
    </main>
</div>
<?php mineacle_page_end(); ?>
