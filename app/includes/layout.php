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
    echo '<link rel="icon" type="image/png" href="assets/favicon.png?v=12">';
    echo '<link rel="stylesheet" href="assets/styles.css?v=12">';
    echo '</head>';
}

function mineacle_header(string $active = 'bans'): void {
    $config = mineacle_config();

    $home = h($config['site']['home'] ?? '/');
    $store = h($config['site']['store'] ?? 'https://store.mineacle.net');
    $vote = h($config['site']['vote'] ?? 'https://vote.mineacle.net');

    echo '<header class="site-header">';
    echo '<div class="header-inner">';

    echo '<a class="brand" href="' . $home . '" aria-label="Mineacle Network">';
    echo '<img src="assets/brand-mark.png?v=12" alt="">';
    echo '<span>Mineacle Network</span>';
    echo '</a>';

    echo '<nav class="main-nav" aria-label="Primary navigation">';
    echo '<a class="' . ($active === 'home' ? 'active' : '') . '" href="' . $home . '"><img src="assets/home.svg" alt=""><span>Home</span></a>';
    echo '<a class="' . ($active === 'vote' ? 'active' : '') . '" href="' . $vote . '"><img src="assets/vote.svg" alt=""><span>Vote</span></a>';
    echo '<a class="' . ($active === 'bans' ? 'active' : '') . '" href="/"><img src="assets/hammer-ban.png" alt=""><span>Bans</span></a>';
    echo '</nav>';

    echo '<a class="store-button" href="' . $store . '"><img src="assets/basket.svg" alt=""><span>Store</span></a>';

    echo '</div>';
    echo '</header>';
}

function mineacle_footer(): void {
    echo '<div class="mineacle-toast" id="toast" role="status" aria-live="polite">';
    echo '<div class="mineacle-toast-icon"><img src="assets/copy.svg" alt=""></div>';
    echo '<div class="mineacle-toast-copy">';
    echo '<small>Mineacle Network</small>';
    echo '<strong>Server IP copied</strong>';
    echo '<span>Join with <b id="toastValue">mineacle.net</b></span>';
    echo '</div>';
    echo '</div>';

    echo '<script src="assets/main.js?v=12"></script>';
}
