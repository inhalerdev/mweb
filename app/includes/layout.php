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
    echo '<link rel="icon" type="image/png" href="assets/mineacle-square-logo.png?v=bansfull3.7">';
    echo '<link rel="stylesheet" href="assets/styles.css?v=bansfull3.7">';
    echo '</head>';
}

function mineacle_header(string $active = 'bans'): void {
    $config = mineacle_config();
    $vote = h((string) ($config['site']['vote'] ?? 'https://vote.mineacle.net'));
    $bans = h((string) ($config['site']['bans'] ?? 'https://bans.mineacle.net'));
    $store = h((string) ($config['site']['store'] ?? 'https://store.mineacle.net'));
    $discord = h((string) ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM'));
    $ip = h((string) ($config['site']['ip'] ?? 'mineacle.net'));

    echo '<header class="site-header blocaria-style-header mineacle-floating-header" id="siteHeader"><div class="blocaria-nav-inner">';
    echo '<nav class="blocaria-nav-left" aria-label="Primary navigation">';
    echo '<a class="nav-text-link ' . ($active === 'vote' ? 'active' : '') . '" href="' . $vote . '">Vote</a>';
    echo '<a class="nav-text-link ' . ($active === 'bans' ? 'active' : '') . '" href="' . $bans . '">Bans</a>';
    echo '<a class="nav-mc-button nav-store-button ' . ($active === 'store' ? 'active' : '') . '" href="' . $store . '">Store</a>';
    echo '</nav>';
    echo '<a class="header-bans-logo" href="" aria-label="Refresh Mineacle Bans"><img src="assets/mineacle-bans-hero-logo.png?v=bansfull3.7" alt="Mineacle Bans"></a>';
    echo '<nav class="blocaria-nav-right" aria-label="Actions">';
    echo '<a class="discord-square nav-mc-icon-button header-discord-button" href="' . $discord . '" target="_blank" rel="noopener" aria-label="Join Discord"><img src="assets/discord.svg?v=bansfull3.7" alt=""></a>';
    echo '<button class="play-copy-button nav-mc-button header-play-button" type="button" data-copy-ip="' . $ip . '">Play</button>';
    echo '<div class="players-online-copy header-player-count">Join <strong>300</strong> Players<br>Online Now</div>';
    echo '<button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-controls="mainNav" aria-expanded="false"><span></span><span></span><span></span></button>';
    echo '</nav>';
    echo '<nav class="main-nav" id="mainNav" aria-label="Mobile navigation">';
    echo '<a class="' . ($active === 'vote' ? 'active' : '') . '" href="' . $vote . '"><img class="nav-icon icon-white" src="assets/vote.svg?v=bansfull3.7" alt=""><span>Vote</span></a>';
    echo '<a class="' . ($active === 'bans' ? 'active' : '') . '" href="' . $bans . '"><img class="nav-icon icon-white" src="assets/hammer.svg?v=bansfull3.7" alt=""><span>Bans</span></a>';
    echo '<a class="store-link ' . ($active === 'store' ? 'active' : '') . '" href="' . $store . '"><img class="nav-icon icon-white" src="assets/store.svg?v=bansfull3.7" alt=""><span>Store</span></a>';
    echo '</nav>';
    echo '</div></header>';
}

function mineacle_footer(): void {
    $config = mineacle_config();
    $discord = h((string) ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM'));
    $x = h((string) ($config['site']['x'] ?? 'https://x.com/mineaclenetwork'));

    echo '<footer class="site-footer redesigned-footer">';
    echo '<div class="footer-inner">';
    echo '<div class="footer-brand"><img class="footer-brand-logo" src="assets/mineacle-main-logo.png?v=bansfull3.7" alt="Mineacle Network"></div>';
    echo '<div class="footer-legal">';
    echo '<p class="footer-copy">Copyright © Mineacle Network 2026. All Rights Reserved.</p>';
    echo '<p class="footer-disclaimer">We are not affiliated with Microsoft or Mojang AB.</p>';
    echo '<div class="footer-socials" aria-label="Mineacle social links">';
    echo '<a class="footer-social-link" href="' . $discord . '" target="_blank" rel="noopener" aria-label="Join Mineacle Discord"><img src="assets/discord.svg?v=bansfull3.7" alt=""></a>';
    echo '<a class="footer-social-link" href="' . $x . '" target="_blank" rel="noopener" aria-label="Follow Mineacle on X"><img src="assets/x.svg?v=bansfull3.7" alt=""></a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</footer>';

    echo '<div class="mineacle-toast" id="toast" role="status" aria-live="polite"><div class="toast-mark">✓</div><div><small>Mineacle Network</small><strong>Server IP copied</strong><span>Join with <b id="toastValue">mineacle.net</b></span></div></div>';
    echo '<script src="assets/main.js?v=bansfull3.7"></script>';
}
