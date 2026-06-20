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
    echo '<link rel="icon" type="image/png" href="assets/mineacle-square-logo.png?v=bansfull3.8.27.277.266.255.244.233.222.211.200.199.188.177.166.144.8.7.6.5.4.3.2">';
    echo '<link rel="stylesheet" href="assets/styles.css?v=banssingle3.9.14">';
    echo '</head>';
}

function mineacle_header(string $active = 'bans'): void {
    $config = mineacle_config();
    $vote = h((string) ($config['site']['vote'] ?? 'https://vote.mineacle.net'));
    $bans = h((string) ($config['site']['bans'] ?? 'https://bans.mineacle.net'));
    $store = h((string) ($config['site']['store'] ?? 'https://store.mineacle.net'));
    $discord = h((string) ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM'));
    $ip = h((string) ($config['site']['ip'] ?? 'mineacle.net'));

    echo '<header class="site-header blocaria-style-header mineacle-floating-header mcx-header" id="siteHeader">';
    echo '<div class="mcx-nav-shell">';
    echo '<a class="mcx-link mcx-vote ' . ($active === 'vote' ? 'is-active' : '') . '" href="' . $vote . '">Vote</a>';
    echo '<a class="mcx-link mcx-bans ' . ($active === 'bans' ? 'is-active' : '') . '" href="' . $bans . '">Bans</a>';
    echo '<a class="mcx-button mcx-store ' . ($active === 'store' ? 'is-active' : '') . '" href="' . $store . '">Store</a>';
    echo '<a class="mcx-logo" href="' . $bans . '" aria-label="Refresh Mineacle Bans">';
    echo '<img src="assets/mineacle-bans-hero-logo.png?v=bansfull3.8.27.277.266.255.244.233.222.211.200.199" alt="Mineacle Bans">';
    echo '</a>';
    echo '<a class="mcx-discord" href="' . $discord . '" target="_blank" rel="noopener" aria-label="Join Discord">';
    echo '<span class="mcx-discord-members" id="navDiscordOnline" aria-hidden="true">Members Online</span>';
    echo '<img src="assets/discord.svg?v=bansfull3.8.27.277.266.255.244.233.222.211.200.199" alt="">';
    echo '</a>';
    echo '<button class="mcx-play" type="button" data-copy-ip="' . $ip . '">Play</button>';
    echo '<button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-controls="mainNav" aria-expanded="false"><span></span><span></span><span></span></button>';
    echo '<nav class="main-nav mcx-mobile-menu" id="mainNav" aria-label="Mobile navigation">';
    echo '<a class="mcx-mobile-link ' . ($active === 'vote' ? 'active' : '') . '" href="' . $vote . '">Vote</a>';
    echo '<a class="mcx-mobile-link ' . ($active === 'bans' ? 'active' : '') . '" href="' . $bans . '">Bans</a>';
    echo '<a class="mcx-mobile-store ' . ($active === 'store' ? 'active' : '') . '" href="' . $store . '">Store</a>';
    echo '<a class="mcx-mobile-discord" href="' . $discord . '" target="_blank" rel="noopener"><img src="assets/discord.svg?v=bansfull3.8.27.277.266.255.244.233.222" alt=""><span>Discord</span></a>';
    echo '</nav>';
    echo '</div>';
    echo '</header>';
}

function mineacle_footer(): void {
    $config = mineacle_config();
    $discord = h((string) ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM'));
    $x = h((string) ($config['site']['x'] ?? 'https://x.com/mineaclenetwork'));

    echo '<footer class="site-footer redesigned-footer">';
    echo '<div class="footer-inner">';
    echo '<div class="footer-brand"><img class="footer-brand-logo" src="assets/mineacle-main-logo.png?v=bansfull3.8.27.277.266.255.244.233.222.211.200.199.188.177.166.144.8.7.6.5.4.3.2" alt="Mineacle Network"></div>';
    echo '<div class="footer-legal">';
    echo '<p class="footer-copy">Copyright © Mineacle Network 2026. All Rights Reserved.</p>';
    echo '<p class="footer-disclaimer">We are not affiliated with Microsoft or Mojang AB.</p>';
    echo '<div class="footer-socials" aria-label="Mineacle social links">';
    echo '<a class="footer-social-link" href="' . $discord . '" target="_blank" rel="noopener" aria-label="Join Mineacle Discord"><img src="assets/discord.svg?v=bansfull3.8.27.277.266.255.244.233.222.211.200.199.188.177.166.144.8.7.6.5.4.3.2" alt=""></a>';
    echo '<a class="footer-social-link" href="' . $x . '" target="_blank" rel="noopener" aria-label="Follow Mineacle on X"><img src="assets/x.svg?v=bansfull3.8.27.277.266.255.244.233.222.211.200.199.188.177.166.144.8.7.6.5.4.3.2" alt=""></a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</footer>';
    echo '<script src="assets/main.js?v=banssingle3.8.99"></script>';
    echo '<script src="assets/hero-scroll.js?v=banssingle3.9.11"></script>';
}
