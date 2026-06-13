<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function mineacle_page_head(string $title): void {
    mineacle_security_headers(false);
    $config = mineacle_config();
    $name = h($config['site']['name']);

    echo '<!doctype html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . h($title) . ' | ' . $name . '</title>';
    echo '<meta name="description" content="Mineacle public bans portal">';
    echo '<link rel="icon" type="image/png" href="assets/favicon.png?v=9">';
    echo '<link rel="stylesheet" href="assets/styles.css?v=9">';
    echo '</head>';
}

function mineacle_header(): void {
    $config = mineacle_config();

    $ip = h($config['site']['ip']);
    $discord = h($config['site']['discord']);
    $store = h($config['site']['store'] ?? 'https://store.mineacle.net');
    $vote = h($config['site']['vote'] ?? 'https://vote.mineacle.net');

    echo '<header class="topbar">';
    echo '<div class="shell nav">';
    echo '<a class="brand" href="/" aria-label="Mineacle bans">';
    echo '<img class="brand-logo" src="assets/mineacle-logo-small.png?v=9" alt="Mineacle" width="118" height="72">';
    echo '</a>';

    echo '<nav class="navlinks" aria-label="Primary">';
    echo '<a href="' . $store . '"><img src="assets/basket.svg" alt=""> Store</a>';
    echo '<a href="' . $vote . '"><img src="assets/vote.svg" alt=""> Vote</a>';
    echo '<a class="active" href="/"><img src="assets/hammer.svg" alt=""> Bans</a>';
    echo '</nav>';

    echo '<div class="nav-actions">';
    echo '<a class="pill discord" href="' . $discord . '" target="_blank" rel="noopener"><img src="assets/discord.svg" alt=""> Discord</a>';
    echo '<button class="pill copy-ip" data-copy="' . $ip . '"><img src="assets/copy.svg" alt=""> ' . $ip . '</button>';
    echo '</div>';

    echo '</div>';
    echo '</header>';
}

function mineacle_footer(): void {
    echo '<footer class="footer"><div class="shell footer-inner">';
    echo '<span>Mineacle is not affiliated with Mojang Studios or Microsoft. All trademarks belong to their respective owners.</span>';
    echo '<span>Public punishment records</span>';
    echo '</div></footer>';

    echo '<div class="achievement-toast" id="toast" role="status" aria-live="polite">';
    echo '<div class="achievement-icon"><img src="assets/copy.svg" alt=""></div>';
    echo '<div><strong>Server IP copied</strong><span>mineacle.net</span></div>';
    echo '</div>';

    echo '<script src="assets/main.js?v=9"></script>';
}
