<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/stats-lib.php';

$site = mineacle_config()['site'] ?? [];
$query = trim((string) ($_GET['name'] ?? $_GET['player'] ?? ''));
$pathInfo = trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');

if ($query === '' && $pathInfo !== '') {
    $query = rawurldecode($pathInfo);
}

$query = substr(trim($query), 0, 64);
$player = null;
$loadError = false;

if ($query !== '') {
    try {
        $player = mineacle_stats_profile_by_query($query);
    } catch (Throwable) {
        $loadError = true;
    }
}

function mineacle_profile_link(mixed $url): string
{
    $value = trim((string) $url);

    return $value !== '' ? $value : '#';
}

function mineacle_profile_stat_row(string $label, string $value): void
{
    echo '<div class="profile-stat-row">';
    echo '<span>' . h($label) . '</span>';
    echo '<strong>' . h($value) . '</strong>';
    echo '</div>';
}

function mineacle_profile_quick_stat(string $label, string $value): void
{
    echo '<article class="profile-quick-stat">';
    echo '<span>' . h($label) . '</span>';
    echo '<strong>' . h($value) . '</strong>';
    echo '</article>';
}

function mineacle_profile_punishment(array $player): array
{
    $status = is_array($player['punishment_status'] ?? null) ? $player['punishment_status'] : [];
    $ban = is_array($status['ban'] ?? null) ? $status['ban'] : [];
    $mute = is_array($status['mute'] ?? null) ? $status['mute'] : [];

    if (!empty($ban['active'])) {
        return [
            'label' => ($ban['kind'] ?? '') === 'temporary' ? 'Temp Banned' : 'Perm Banned',
            'class' => ($ban['kind'] ?? '') === 'temporary' ? 'is-warning' : 'is-danger',
        ];
    }

    if (!empty($mute['active'])) {
        return [
            'label' => ($mute['kind'] ?? '') === 'temporary' ? 'Temp Muted' : 'Perm Muted',
            'class' => 'is-muted',
        ];
    }

    return [
        'label' => 'Clear',
        'class' => 'is-clear',
    ];
}

$navLinks = [
    ['key' => 'home', 'url' => $site['home_url'] ?? '/'],
    ['key' => 'stats', 'label' => 'Leaderboards', 'url' => $site['stats_url'] ?? '/players'],
    ['key' => 'bans', 'url' => $site['bans_url'] ?? '#'],
];
$storeLink = ['key' => 'store', 'url' => $site['store_url'] ?? '#'];
$currentNavKey = 'stats';

$pageTitle = $player ? mineacle_stats_display_name($player) : 'Player';

mineacle_page_head($pageTitle);
?>
<div class="site-shell">
    <aside class="rail" aria-label="Primary navigation">
        <a class="rail-logo" href="<?php echo h(mineacle_profile_link($site['home_url'] ?? '/')); ?>" aria-label="Home">
            <img src="/assets/brand/nav-logo.png" alt="">
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
        <?php mineacle_page_search_header($site); ?>

        <?php if ($query === ''): ?>
            <section class="panel profile-message">
                <h1>Player Profile</h1>
                <p>Search for a player from the home page to open their profile.</p>
            </section>
        <?php elseif ($loadError): ?>
            <section class="panel profile-message">
                <h1>Unable to load player stats right now</h1>
                <p>Please try again shortly.</p>
            </section>
        <?php elseif (!$player): ?>
            <section class="panel profile-message">
                <h1>Player not found</h1>
                <p>No stored profile was found for <?php echo h($query); ?>.</p>
            </section>
        <?php else: ?>
            <?php
            $skin = is_array($player['skin'] ?? null) ? $player['skin'] : [];
            $skinChest = trim((string) ($skin['chest'] ?? ''));
            $punishment = mineacle_profile_punishment($player);
            $online = mineacle_stats_online($player);
            ?>
            <section class="panel profile-hero">
                <div class="profile-skin-card">
                    <?php if ($skinChest !== ''): ?>
                        <img src="<?php echo h($skinChest); ?>" alt="" draggable="false" aria-hidden="true">
                    <?php endif; ?>
                </div>

                <div class="profile-identity">
                    <p class="profile-kicker">Player Profile</p>
                    <span class="profile-online <?php echo $online ? 'is-online' : 'is-offline'; ?>">
                        <span aria-hidden="true"></span>
                        <?php echo $online ? 'Online' : 'Offline'; ?>
                    </span>
                    <h1><?php echo h(mineacle_stats_display_name($player)); ?></h1>
                    <p>@<?php echo h(mineacle_stats_username($player)); ?></p>
                    <div class="profile-pills">
                        <span class="profile-pill"><?php echo h(mineacle_stats_rank_name($player)); ?></span>
                        <span class="profile-pill <?php echo h($punishment['class']); ?>"><?php echo h($punishment['label']); ?></span>
                    </div>
                    <div class="profile-quick-stats" aria-label="Player highlights">
                        <?php
                        mineacle_profile_quick_stat('Playtime', mineacle_stats_playtime_label($player));
                        mineacle_profile_quick_stat('Kills', number_format(mineacle_stats_int($player['kills'] ?? 0)));
                        mineacle_profile_quick_stat('Balance', mineacle_stats_money_label($player));
                        mineacle_profile_quick_stat('K/D', number_format(mineacle_stats_float($player['kd_ratio'] ?? 0), 2));
                        ?>
                    </div>
                </div>
            </section>

            <section class="profile-content-grid" aria-label="Player stats">
                <article class="panel profile-section">
                    <h2>Overview</h2>
                    <div class="profile-stat-list">
                        <?php
                        mineacle_profile_stat_row('Rank', mineacle_stats_rank_name($player));
                        mineacle_profile_stat_row('Team', mineacle_stats_team_name($player));
                        mineacle_profile_stat_row('Team Role', mineacle_stats_team_role($player));
                        mineacle_profile_stat_row('Deaths', number_format(mineacle_stats_int($player['deaths'] ?? 0)));
                        ?>
                    </div>
                </article>

                <article class="panel profile-section">
                    <h2>Rankings</h2>
                    <div class="profile-stat-list">
                        <?php
                        mineacle_profile_stat_row('Money Rank', mineacle_stats_rank_label($player['money_rank'] ?? 0));
                        mineacle_profile_stat_row('Kills Rank', mineacle_stats_rank_label($player['kills_rank'] ?? 0));
                        mineacle_profile_stat_row('Playtime Rank', mineacle_stats_rank_label($player['playtime_rank'] ?? 0));
                        ?>
                    </div>
                </article>

                <article class="panel profile-section profile-section-wide">
                    <h2>Activity</h2>
                    <div class="profile-stat-list is-grid">
                        <?php
                        mineacle_profile_stat_row('First Joined', mineacle_stats_date_label($player['first_joined_at'] ?? 0));
                        mineacle_profile_stat_row('Last Seen', mineacle_stats_last_seen_label($player));
                        mineacle_profile_stat_row('Team Joined', mineacle_stats_date_label($player['team_joined_at'] ?? 0));
                        mineacle_profile_stat_row('Updated', mineacle_stats_date_label($player['updated_at'] ?? 0));
                        ?>
                    </div>
                </article>
            </section>
        <?php endif; ?>

        <?php mineacle_page_footer($site); ?>
    </main>
</div>
<?php mineacle_page_end(); ?>
