<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function mineacle_page_head(string $title): void {
    mineacle_security_headers(false);

    $config = mineacle_config();
    $name = h($config['site']['name'] ?? 'Mineacle Network');
    $version = 'bansfull3.8.69-compact-ban-modal-rebuild';

    echo '<!doctype html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">';
    echo '<title>' . h($title) . ' | ' . $name . '</title>';
    echo '<meta name="description" content="Mineacle public bans portal">';
    echo '<link rel="icon" type="image/png" href="assets/mineacle-square-logo.png?v=' . $version . '">';
    echo '<link rel="stylesheet" href="assets/styles.css?v=' . $version . '">';
    echo '</head>';
}

function mineacle_header(string $active = 'bans'): void {
    $config = mineacle_config();

    $vote = h((string) ($config['site']['vote'] ?? 'https://vote.mineacle.net'));
    $bans = h((string) ($config['site']['bans'] ?? 'https://bans.mineacle.net'));
    $store = h((string) ($config['site']['store'] ?? 'https://store.mineacle.net'));
    $discord = h((string) ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM'));
    $ip = h((string) ($config['site']['ip'] ?? 'mineacle.net'));

    echo '<header class="site-header blocaria-style-header mineacle-floating-header mcx-header mcx-edge-v68" id="siteHeader">';

    echo '<div class="mcx-nav-shell">';
    echo '<a class="mcx-link mcx-vote ' . ($active === 'vote' ? 'is-active' : '') . '" href="' . $vote . '">Vote</a>';
    echo '<a class="mcx-link mcx-bans ' . ($active === 'bans' ? 'is-active' : '') . '" href="' . $bans . '">Bans</a>';
    echo '<a class="mcx-button mcx-store ' . ($active === 'store' ? 'is-active' : '') . '" href="' . $store . '">Store</a>';

    echo '<a class="mcx-logo" href="' . $bans . '" aria-label="Refresh Mineacle Bans">';
    echo '<img src="assets/mineacle-bans-hero-logo.png?v=bansfull3.8.69-compact-ban-modal-rebuild" alt="Mineacle Bans">';
    echo '</a>';

    echo '<a class="mcx-discord" href="' . $discord . '" target="_blank" rel="noopener" aria-label="Join Discord">';
    echo '<img src="assets/discord.svg?v=bansfull3.8.69-compact-ban-modal-rebuild" alt="">';
    echo '</a>';

    echo '<button class="mcx-play" type="button" data-copy-ip="' . $ip . '">Play</button>';
    echo '<button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-controls="mainNav" aria-expanded="false"><span></span><span></span><span></span></button>';
    echo '</div>';

    echo '<nav class="main-nav mcx-mobile-menu" id="mainNav" aria-label="Mobile navigation" aria-hidden="true">';
    echo '<div class="mcx-mobile-menu-stage">';
    echo '<a class="mcx-mobile-item mcx-mobile-text mcx-mobile-vote ' . ($active === 'vote' ? 'active' : '') . '" href="' . $vote . '"><span>Vote</span></a>';
    echo '<a class="mcx-mobile-item mcx-mobile-text mcx-mobile-bans ' . ($active === 'bans' ? 'active' : '') . '" href="' . $bans . '"><span>Bans</span></a>';
    echo '<a class="mcx-mobile-item mcx-mobile-store ' . ($active === 'store' ? 'active' : '') . '" href="' . $store . '"><span>Store</span></a>';
    echo '<a class="mcx-mobile-item mcx-mobile-discord" href="' . $discord . '" target="_blank" rel="noopener"><img src="assets/discord.svg?v=bansfull3.8.69-compact-ban-modal-rebuild" alt=""><span>Discord</span></a>';
    echo '</div>';
    echo '</nav>';

    echo '</header>';
}

function mineacle_footer(): void {
    $config = mineacle_config();
    $discord = h((string) ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM'));
    $x = h((string) ($config['site']['x'] ?? 'https://x.com/mineaclenetwork'));
    $version = 'bansfull3.8.69-compact-ban-modal-rebuild';

    echo '<footer class="site-footer redesigned-footer">';
    echo '<div class="footer-inner">';
    echo '<div class="footer-brand"><img class="footer-brand-logo" src="assets/mineacle-main-logo.png?v=' . $version . '" alt="Mineacle Network"></div>';
    echo '<div class="footer-legal">';
    echo '<p class="footer-copy">Copyright © Mineacle Network 2026. All Rights Reserved.</p>';
    echo '<p class="footer-disclaimer">We are not affiliated with Microsoft or Mojang AB.</p>';
    echo '<div class="footer-socials" aria-label="Mineacle social links">';
    echo '<a class="footer-social-link" href="' . $discord . '" target="_blank" rel="noopener" aria-label="Join Mineacle Discord"><img src="assets/discord.svg?v=' . $version . '" alt=""></a>';
    echo '<a class="footer-social-link" href="' . $x . '" target="_blank" rel="noopener" aria-label="Follow Mineacle on X"><img src="assets/x.svg?v=' . $version . '" alt=""></a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</footer>';
    echo '<div class="mineacle-toast" id="toast" role="status" aria-live="polite"><div class="toast-mark">✓</div><div><small>Mineacle Network</small><strong>Server IP copied</strong><span>Join with <b id="toastValue">mineacle.net</b></span></div></div>';
    echo '<script src="assets/main.js?v=' . $version . '"></script>';
}
