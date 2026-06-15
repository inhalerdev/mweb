<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function mineacle_page_head(string $title): void {
    mineacle_security_headers(false);
    $config = mineacle_config();
    $name = h($config['site']['name'] ?? 'Mineacle Network');

    echo '<!doctype html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . h($title) . ' | ' . $name . '</title>';
    echo '<meta name="description" content="Mineacle public bans portal">';
    echo '<link rel="icon" type="image/png" href="assets/mineacle-square-logo.png?v=foundation1.25">';
    echo '<link rel="stylesheet" href="assets/styles.css?v=foundation1.25">';
    echo '</head>';
}

function mineacle_header(string $active = 'bans'): void {
    $config = mineacle_config();
    $home = h($config['site']['home'] ?? 'https://mineacle.net/home');
    $store = h($config['site']['store'] ?? 'https://store.mineacle.net');
    $vote = h($config['site']['vote'] ?? 'https://vote.mineacle.net');

    echo '<header class="site-header">';
    echo '<div class="header-inner">';
    echo '<a class="header-logo-link" href="' . $home . '" aria-label="Go to Mineacle home">';
    echo '<img src="assets/mineacle-square-logo.png?v=foundation1.25" alt="Mineacle">';
    echo '</a>';
    echo '<nav class="main-nav" aria-label="Primary navigation">';
    echo '<a class="' . ($active === 'home' ? 'active' : '') . '" href="' . $home . '"><img class="nav-icon icon-white" src="assets/home.svg?v=foundation1.25" alt=""><span>Home</span></a>';
    echo '<a class="' . ($active === 'vote' ? 'active' : '') . '" href="' . $vote . '"><img class="nav-icon icon-white" src="assets/vote.svg?v=foundation1.25" alt=""><span>Vote</span></a>';
    echo '<a class="' . ($active === 'bans' ? 'active' : '') . '" href="/"><img class="nav-icon icon-white" src="assets/hammer.svg?v=foundation1.25" alt=""><span>Bans</span></a>';
    echo '<a class="store-link ' . ($active === 'store' ? 'active' : '') . '" href="' . $store . '"><img class="nav-icon icon-white" src="assets/store.svg?v=foundation1.25" alt=""><span>Store</span></a>';
    echo '</nav>';
    echo '</div>';
    echo '</header>';
}

function mineacle_footer(): void {
    echo '<footer class="site-footer logo-only-footer">';
    echo '<img class="footer-only-logo" src="assets/mineacle-main-logo.png?v=foundation1.25" alt="Mineacle">';
    echo '</footer>';
    echo '<div class="mineacle-toast" id="toast" role="status" aria-live="polite">';
    echo '<div class="toast-mark">✓</div>';
    echo '<div><small>Mineacle Network</small><strong>Server IP copied</strong><span>Join with <b id="toastValue">mineacle.net</b></span></div>';
    echo '</div>';
    echo '<script src="assets/main.js?v=foundation1.25"></script>';
}
