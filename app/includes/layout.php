<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function mineacle_page_head(string $title): void {
    mineacle_security_headers(false);

    echo '<!doctype html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Mineacle | ' . h($title) . '</title>';
    echo '<meta name="description" content="Mineacle active bans search">';
    echo '<link rel="icon" type="image/png" href="assets/mineacle-square-logo.png?v=bansclean1.0.0">';
    echo '<link rel="stylesheet" href="assets/bans-page.css?v=bansclean1.0.0">';
    echo '</head>';
    echo '<body>';
}

function mineacle_header(string $active = 'bans'): void {
    unset($active);
}

function mineacle_footer(): void {
    $config = mineacle_config();
    $site = $config['site'] ?? [];
    $home = h((string) ($site['home'] ?? 'https://mineacle.net/home'));
    $store = h((string) ($site['store'] ?? 'https://store.mineacle.net'));
    $bans = h((string) ($site['bans'] ?? 'https://bans.mineacle.net'));
    $stats = h((string) ($site['stats'] ?? 'https://stats.mineacle.net'));
    $discord = h((string) ($site['discord'] ?? 'https://discord.gg/VwbwWftefM'));
    $x = h((string) ($site['x'] ?? 'https://x.com/mineaclenetwork'));
    $supportEmail = h((string) ($site['support_email'] ?? 'support@mineacle.net'));

    echo '<footer class="mineacle-footer-island" aria-label="Mineacle footer">';
    echo '<section class="mineacle-footer-brand">';
    echo '<img src="assets/mineacle-bans-hero-logo.png?v=bansclean1.0.0" alt="Mineacle">';
    echo '<h2>Mineacle</h2>';
    echo '<p>Search active banned players from the public bans database.</p>';
    echo '<div class="mineacle-footer-socials">';
    echo '<a href="' . $discord . '" target="_blank" rel="noopener" aria-label="Discord"><img src="assets/discord.svg?v=bansclean1.0.0" alt=""></a>';
    echo '<a href="' . $x . '" target="_blank" rel="noopener" aria-label="X"><img src="assets/x.svg?v=bansclean1.0.0" alt=""></a>';
    echo '</div>';
    echo '</section>';

    echo '<nav class="mineacle-footer-column" aria-label="Quick links">';
    echo '<h3>Quick Links</h3>';
    echo '<a href="' . $home . '">Home</a>';
    echo '<a href="' . $store . '">Store</a>';
    echo '<a href="' . $bans . '">Bans</a>';
    echo '<a href="' . $stats . '">Stats</a>';
    echo '</nav>';

    echo '<nav class="mineacle-footer-column" aria-label="Support">';
    echo '<h3>Support</h3>';
    echo '<a href="' . $discord . '" target="_blank" rel="noopener">Discord</a>';
    echo '<a href="mailto:' . $supportEmail . '">Contact</a>';
    echo '<a href="' . $bans . '">Records</a>';
    echo '</nav>';

    echo '<nav class="mineacle-footer-column" aria-label="Legal">';
    echo '<h3>Legal</h3>';
    echo '<a href="#">Terms of Use</a>';
    echo '<a href="#">Privacy Policy</a>';
    echo '<a href="#">Appeal Policy</a>';
    echo '</nav>';

    echo '<div class="mineacle-footer-bottom">';
    echo '<span><img src="assets/mineacle-square-logo.png?v=bansclean1.0.0" alt=""> Copyright © 2026 Mineacle Network. All Rights Reserved.</span>';
    echo '<span>Not affiliated with Microsoft or Mojang AB.</span>';
    echo '</div>';
    echo '</footer>';
    echo '<script src="assets/bans-page.js?v=bansclean1.0.0"></script>';
    echo '</body></html>';
}
