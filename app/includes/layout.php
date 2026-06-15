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
    echo '<link rel="icon" type="image/png" href="assets/mineacle-square-logo.png?v=foundation1.59">';
    echo '<link rel="stylesheet" href="assets/styles.css?v=foundation1.59">';
    echo '</head>';
}

function mineacle_header(string $active = 'bans'): void {
    $config = mineacle_config();
    $home = h((string) ($config['site']['home'] ?? 'https://mineacle.net'));
    $vote = h((string) ($config['site']['vote'] ?? 'https://vote.mineacle.net'));
    $bans = h((string) ($config['site']['bans'] ?? 'https://bans.mineacle.net'));
    $store = h((string) ($config['site']['store'] ?? 'https://store.mineacle.net'));
    $ip = h((string) ($config['site']['ip'] ?? 'mineacle.net'));
    $online = (($config['site']['server_online'] ?? true) === true);

    echo '<header class="site-header" id="siteHeader"><div class="header-inner">';
    echo '<a class="header-logo-link" href="' . $home . '" aria-label="Go to Mineacle home"><img src="assets/mineacle-square-logo.png?v=foundation1.59" alt="Mineacle"></a>';
    echo '<button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-controls="mainNav" aria-expanded="false"><span></span><span></span><span></span></button>';
    echo '<nav class="main-nav" id="mainNav" aria-label="Primary navigation">';
    echo '<a class="' . ($active === 'home' ? 'active' : '') . '" href="' . $home . '"><img class="nav-icon icon-white" src="assets/home.svg?v=foundation1.59" alt=""><span>Home</span></a>';
    echo '<a class="' . ($active === 'vote' ? 'active' : '') . '" href="' . $vote . '"><img class="nav-icon icon-white" src="assets/vote.svg?v=foundation1.59" alt=""><span>Vote</span></a>';
    echo '<a class="' . ($active === 'bans' ? 'active' : '') . '" href="' . $bans . '"><img class="nav-icon icon-white" src="assets/hammer.svg?v=foundation1.59" alt=""><span>Bans</span></a>';
    echo '<a class="store-link ' . ($active === 'store' ? 'active' : '') . '" href="' . $store . '"><img class="nav-icon icon-white" src="assets/store.svg?v=foundation1.59" alt=""><span>Store</span></a>';
    echo '</nav>';
    echo '<button class="copy-ip-button" type="button" data-copy-ip="' . $ip . '" aria-label="Copy server IP"><span class="status-dot ' . ($online ? 'online' : 'offline') . '"></span><span>Copy IP</span></button>';
    echo '</div></header>';
}

function mineacle_footer(): void {
    $config = mineacle_config();
    $discord = h((string) ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM'));
    $x = h((string) ($config['site']['x'] ?? 'https://x.com/mineaclenetwork'));

    echo '<footer class="site-footer redesigned-footer">';
    echo '<div class="footer-inner">';
    echo '<div class="footer-brand"><img class="footer-brand-logo" src="assets/mineacle-main-logo.png?v=foundation1.59" alt="Mineacle Network"></div>';
    echo '<div class="footer-legal">';
    echo '<p class="footer-copy">Copyright © Mineacle Network 2026. All Rights Reserved.</p>';
    echo '<p class="footer-disclaimer">We are not affiliated with Microsoft or Mojang AB.</p>';
    echo '<div class="footer-socials" aria-label="Mineacle social links">';
    echo '<a class="footer-social-link" href="' . $discord . '" target="_blank" rel="noopener" aria-label="Join Mineacle Discord"><img src="assets/discord.svg?v=foundation1.59" alt=""></a>';
    echo '<a class="footer-social-link" href="' . $x . '" target="_blank" rel="noopener" aria-label="Follow Mineacle on X"><img src="assets/x.svg?v=foundation1.59" alt=""></a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</footer>';

    echo '<div class="mineacle-toast" id="toast" role="status" aria-live="polite"><div class="toast-mark">✓</div><div><small>Mineacle Network</small><strong>Server IP copied</strong><span>Join with <b id="toastValue">mineacle.net</b></span></div></div>';
    echo '<script src="assets/main.js?v=foundation1.59"></script>';
}
