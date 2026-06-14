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
    echo '<link rel="stylesheet" href="assets/styles.css?v=foundation1.14">';
    echo '<link rel="icon" type="image/png" href="assets/mineacle-square-logo.png?v=foundation1.14"></head>';
}

function mineacle_header(string $active = 'bans'): void {
    $config = mineacle_config();

    $home = h($config['site']['home'] ?? '/');
    $store = h($config['site']['store'] ?? 'https://store.mineacle.net');
    $vote = h($config['site']['vote'] ?? 'https://vote.mineacle.net');
    $ip = h($config['site']['ip'] ?? 'mineacle.net');

    echo '<header class="site-header">';
    echo '<div class="header-inner">';
    echo '<a class="header-logo-link" href="https://mineacle.net/home" aria-label="Go to Mineacle home"><img src="assets/mineacle-square-logo.png?v=foundation1.14" alt="Mineacle"></a>';
    echo '<nav class="main-nav" aria-label="Primary navigation">';
    echo '<a class="' . ($active === 'home' ? 'active' : '') . '" href="' . $home . '"><img class="nav-icon icon-white" src="assets/home.svg?v=foundation1.14" alt=""><span>Home</span></a>';
    echo '<a class="' . ($active === 'vote' ? 'active' : '') . '" href="' . $vote . '"><img class="nav-icon icon-white" src="assets/vote.svg?v=foundation1.14" alt=""><span>Vote</span></a>';
    echo '<a class="' . ($active === 'bans' ? 'active' : '') . '" href="/"><img class="nav-icon icon-white" src="assets/hammer.svg?v=foundation1.14" alt=""><span>Bans</span></a>';
    echo '<a class="store-link ' . ($active === 'store' ? 'active' : '') . '" href="' . $store . '"><img class="nav-icon icon-white" src="assets/store.svg?v=foundation1.14" alt=""><span>Store</span></a>';
    echo '</nav>';
    echo '</div>';
    echo '</header>';
}

function mineacle_footer(): void {
    $config = mineacle_config();
    $vote = h($config['site']['vote']);
    $store = h($config['site']['store']);
    $discord = h($config['site']['discord']);
    $ip = h($config['site']['ip']);

    echo '<footer class="site-footer">';
    echo '<div class="footer-bg-logo" aria-hidden="true"><img src="assets/mineacle-main-logo.png?v=foundation1.14" alt=""></div>';
    echo '<div class="footer-grid">';

    echo '<div class="footer-brand-copy">';
    echo '<span>Mineacle Network</span>';
    echo '<strong>Stay connected</strong>';
    echo '<p>Join the community, copy the server IP, and use the quick links below to keep playing.</p>';
    echo '</div>';

    echo '<div class="footer-links">';
    echo '<span>Quick links</span>';
    echo '<a href="https://mineacle.net/home">Home</a>';
    echo '<a href="' . $vote . '">Vote</a>';
    echo '<a href="/">Bans</a>';
    echo '<a href="' . $store . '">Store</a>';
    echo '</div>';

    echo '<div class="footer-actions">';
    echo '<span>Community</span>';
    echo '<button class="footer-btn copy-ip" type="button" data-copy="' . $ip . '"><img class="footer-btn-icon icon-white" src="assets/copy.svg?v=foundation1.14" alt=""><span>Copy IP</span></button>';
    echo '<a class="footer-btn discord-btn" href="' . $discord . '" target="_blank" rel="noopener"><img class="footer-btn-icon icon-white" src="assets/discord.svg?v=foundation1.14" alt=""><span>Join Discord</span></a>';
    echo '</div>';

    echo '</div>';
    echo '</footer>';

    echo '<div class="mineacle-toast" id="toast" role="status" aria-live="polite">';
    echo '<div class="toast-mark">✓</div>';
    echo '<div><small>Mineacle Network</small><strong>Server IP copied</strong><span>Join with <b id="toastValue">mineacle.net</b></span></div>';
    echo '</div>';

    echo '<script src="assets/main.js?v=foundation1.14"></script>';
}


