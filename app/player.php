<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/stats-lib.php';

$site = mineacle_config()['site'] ?? [];
$homeUrl = mineacle_page_home_url($site);
$leaderboardsUrl = mineacle_page_leaderboards_url($site);
$directPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$directUsername = trim((string) ($_GET['username'] ?? ''));

if ($directPath === '/player.php' && preg_match('/^[A-Za-z0-9_]{1,32}$/', $directUsername) === 1) {
    header('Location: https://mineacle.net/player/' . rawurlencode($directUsername), true, 301);
    exit;
}

function mineacle_profile_requested_username(): string
{
    $query = trim((string) ($_GET['username'] ?? $_GET['name'] ?? $_GET['player'] ?? $_GET['search'] ?? ''));
    $pathInfo = trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
    $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);

    if ($query === '' && $pathInfo !== '') {
        $query = rawurldecode($pathInfo);
    }

    if ($query === '' && is_string($requestPath) && preg_match('#^/player/([^/]+)/?$#', $requestPath, $match) === 1) {
        $query = rawurldecode($match[1]);
    }

    return substr(trim($query), 0, 64);
}

function mineacle_profile_link(mixed $url): string
{
    $value = trim((string) $url);

    return $value !== '' ? $value : '#';
}

function mineacle_profile_kd(array $player): string
{
    return mineacle_stats_kd_label(
        mineacle_stats_int($player['kills'] ?? 0),
        mineacle_stats_int($player['deaths'] ?? 0),
        $player['kd_ratio'] ?? 0
    );
}

function mineacle_profile_team_view_model(array $player, ?array $team): array
{
    $profileTeamName = mineacle_stats_team_name($player);
    $teamName = $team !== null ? trim((string) ($team['name'] ?? '')) : '';

    if ($teamName === '') {
        $teamName = $profileTeamName;
    }

    $hasTeam = $teamName !== '' && strcasecmp($teamName, 'No Team') !== 0;

    return [
        'has_team' => $hasTeam,
        'name' => $hasTeam ? $teamName : 'No Team',
        'role' => $hasTeam ? mineacle_stats_team_role($player) : 'None',
    ];
}

function mineacle_profile_view_model(array $player, ?array $team): array
{
    $skin = is_array($player['skin'] ?? null) ? $player['skin'] : [];
    $online = mineacle_stats_online($player);
    $teamView = mineacle_profile_team_view_model($player, $team);
    $rankName = mineacle_stats_rank_name($player);
    $displayName = mineacle_stats_display_name($player);

    return [
        'username' => mineacle_stats_username($player),
        'display_name' => $displayName,
        'ranked_name_html' => mineacle_stats_ranked_name_html($player, 'profile-ranked-name'),
        'rank_name' => $rankName,
        'skin_head' => trim((string) ($skin['head'] ?? '')),
        'skin_bust' => trim((string) (($skin['bust'] ?? '') ?: ($skin['chest'] ?? ''))),
        'online' => $online,
        'status_label' => $online ? 'Online' : 'Offline',
        'location_label' => $online ? 'Located in Survival' : 'Last seen ' . mineacle_stats_last_seen_label($player),
        'last_seen' => mineacle_stats_last_seen_label($player),
        'balance' => mineacle_stats_money_label($player),
        'kills' => number_format(mineacle_stats_int($player['kills'] ?? 0)),
        'deaths' => number_format(mineacle_stats_int($player['deaths'] ?? 0)),
        'kd' => mineacle_profile_kd($player),
        'playtime' => mineacle_stats_playtime_label($player),
        'money_rank' => mineacle_stats_rank_label($player['money_rank'] ?? 0),
        'kills_rank' => mineacle_stats_rank_label($player['kills_rank'] ?? 0),
        'playtime_rank' => mineacle_stats_rank_label($player['playtime_rank'] ?? 0),
        'first_joined' => mineacle_stats_date_label($player['first_joined_at'] ?? 0),
        'team' => $teamView,
    ];
}

function mineacle_profile_stat_item(string $label, string $value, string $icon, string $alt = ''): void
{
    echo '<article class="profile-summary-item">';
    echo '<span>' . h($label) . '</span>';
    echo '<strong><img src="' . h($icon) . '" alt="' . h($alt) . '" draggable="false"> ' . h($value) . '</strong>';
    echo '</article>';
}

$query = mineacle_profile_requested_username();
$validUsername = preg_match('/^[A-Za-z0-9_]{1,32}$/', $query) === 1;
$player = null;
$team = null;
$loadError = false;

if ($validUsername) {
    try {
        $player = mineacle_stats_profile_by_username($query);
        $team = is_array($player) ? mineacle_stats_team_by_profile($player) : null;
    } catch (Throwable) {
        $loadError = true;
    }
}

if ($loadError) {
    http_response_code(503);
} elseif (!$validUsername || !$player) {
    http_response_code(404);
}

$navLinks = [
    ['key' => 'home', 'url' => $homeUrl],
    ['key' => 'vote', 'url' => $site['vote_url'] ?? '#'],
    ['key' => 'stats', 'label' => 'Leaderboards', 'url' => $leaderboardsUrl],
    ['key' => 'bans', 'url' => $site['bans_url'] ?? '#'],
];
$storeLink = ['key' => 'store', 'url' => $site['store_url'] ?? '#'];
$currentNavKey = 'stats';
$assetVersion = mineacle_page_asset_version();
$viewModel = $player ? mineacle_profile_view_model($player, $team) : null;
$pageTitle = $viewModel ? (string) $viewModel['display_name'] : 'Player';
$metaOptions = [];

if ($viewModel !== null) {
    $metaOptions = [
        'meta_title' => $viewModel['display_name'] . ' (@' . $viewModel['username'] . ') - Mineacle Player Profile',
        'meta_description' => 'View ' . $viewModel['display_name'] . '\'s Mineacle stats, team, balance, combat record, playtime, and status.',
        'canonical_url' => 'https://mineacle.net/player/' . rawurlencode((string) $viewModel['username']),
    ];
} elseif (!$loadError) {
    $metaOptions = [
        'robots' => 'noindex,follow',
        'meta_description' => 'The requested Mineacle player profile could not be found.',
    ];
}

mineacle_page_head($pageTitle, $metaOptions);
?>
<div class="site-shell">
    <aside class="rail" aria-label="Primary navigation">
        <a class="rail-logo" href="<?php echo h($homeUrl); ?>" aria-label="Home">
            <img src="/assets/brand/nav-logo-web.png" alt="">
        </a>

        <nav class="rail-nav" aria-label="Server links">
            <?php foreach ($navLinks as $link): ?>
                <?php $isActiveNavLink = (string) $link['key'] === $currentNavKey; ?>
                <a class="rail-link<?php echo $isActiveNavLink ? ' is-active' : ''; ?>" href="<?php echo h(mineacle_profile_link($link['url'])); ?>" aria-label="<?php echo h((string) ($link['label'] ?? $link['key'])); ?>"<?php echo $isActiveNavLink ? ' aria-current="page"' : ''; ?>>
                    <?php echo mineacle_page_icon((string) $link['key']); ?>
                </a>
            <?php endforeach; ?>
            <?php $isStoreActive = (string) $storeLink['key'] === $currentNavKey; ?>
            <a class="rail-link rail-store-button<?php echo $isStoreActive ? ' is-active' : ''; ?>" href="<?php echo h(mineacle_profile_link($storeLink['url'])); ?>" aria-label="Store"<?php echo $isStoreActive ? ' aria-current="page"' : ''; ?>>
                <?php echo mineacle_page_icon((string) $storeLink['key']); ?>
            </a>
        </nav>

        <div class="rail-social" aria-label="Social links">
            <a class="rail-link" href="<?php echo h(mineacle_profile_link($site['discord_url'] ?? '#')); ?>" aria-label="Discord">
                <?php echo mineacle_page_icon('discord'); ?>
            </a>
            <a class="rail-link" href="<?php echo h(mineacle_profile_link($site['x_url'] ?? '#')); ?>" aria-label="X">
                <?php echo mineacle_page_icon('x'); ?>
            </a>
        </div>
    </aside>

    <main class="home-grid profile-page" aria-label="Player profile">
        <?php if ($loadError): ?>
            <section class="panel profile-message">
                <h1>Unable to load player stats right now</h1>
                <p>Please check the Mineacle Core database connection, then try again.</p>
            </section>
        <?php elseif ($viewModel === null): ?>
            <section class="panel profile-message">
                <h1>Player not found</h1>
                <p>No stored Mineacle profile was found for <?php echo h($query !== '' ? $query : 'that player'); ?>.</p>
                <a class="profile-message-link" href="<?php echo h($leaderboardsUrl); ?>">Back to leaderboards</a>
            </section>
        <?php else: ?>
            <section class="panel profile-main-panel" aria-label="<?php echo h((string) $viewModel['display_name']); ?> stats">
                <div class="profile-bust-stage">
                    <?php if ($viewModel['skin_bust'] !== ''): ?>
                        <img src="<?php echo h((string) $viewModel['skin_bust']); ?>" alt="" draggable="false" aria-hidden="true">
                    <?php else: ?>
                        <span><?php echo h(strtoupper(substr((string) $viewModel['display_name'], 0, 1))); ?></span>
                    <?php endif; ?>
                </div>

                <div class="profile-player-lockup">
                    <h1><?php echo $viewModel['ranked_name_html']; ?></h1>
                    <div class="profile-state-lines">
                        <p>
                            <strong class="<?php echo $viewModel['online'] ? 'is-online' : 'is-offline'; ?>"><?php echo h((string) $viewModel['status_label']); ?></strong>
                            <span><?php echo h((string) $viewModel['location_label']); ?></span>
                        </p>
                    </div>
                </div>

                <div class="profile-summary-bar" aria-label="Player stat summary">
                    <?php
                    mineacle_profile_stat_item('Player Balance', (string) $viewModel['balance'], '/assets/icons/player-money.png?v=' . rawurlencode($assetVersion), 'Balance');
                    mineacle_profile_stat_item('Player Kills', (string) $viewModel['kills'], '/assets/icons/player-sword.png?v=' . rawurlencode($assetVersion), 'Kills');
                    mineacle_profile_stat_item('Player Deaths', (string) $viewModel['deaths'], '/assets/icons/player-heart.png?v=' . rawurlencode($assetVersion), 'Deaths');
                    mineacle_profile_stat_item('Player Playtime', (string) $viewModel['playtime'], '/assets/icons/player-clock.png?v=' . rawurlencode($assetVersion), 'Playtime');
                    mineacle_profile_stat_item('Player Team', (string) $viewModel['team']['name'], '/assets/icons/player-castle.png?v=' . rawurlencode($assetVersion), 'Team');
                    mineacle_profile_stat_item('Team Role', (string) $viewModel['team']['role'], '/assets/icons/player-person.png?v=' . rawurlencode($assetVersion), 'Role');
                    ?>
                </div>
            </section>

            <section class="panel profile-recent-panel" aria-label="Recent fights">
                <span class="sr-only">Recent fights will appear here once fight events are connected.</span>
            </section>
        <?php endif; ?>

        <?php mineacle_page_footer($site); ?>
    </main>
</div>
<?php mineacle_page_end(); ?>
