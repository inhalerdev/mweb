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
    echo '<link rel="icon" type="image/png" href="assets/favicon.png?v=10.1">';
    echo '<link rel="stylesheet" href="assets/styles.css?v=10.1">';
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
    echo '<a class="back-link" href="/"><span>←</span> Mineacle Bans</a>';

    echo '<nav class="navlinks" aria-label="Primary">';
    echo '<a href="' . $store . '"><span class="nav-icon"><img src="assets/basket.svg" alt=""></span><span><strong>Store</strong><small>Support Mineacle</small></span></a>';
    echo '<a href="' . $vote . '"><span class="nav-icon"><img src="assets/vote.svg" alt=""></span><span><strong>Vote</strong><small>Claim rewards</small></span></a>';
    echo '<a class="active" href="/"><span class="nav-icon"><img src="assets/hammer.svg" alt=""></span><span><strong>Bans</strong><small>Public records</small></span></a>';
    echo '<a href="' . $discord . '" target="_blank" rel="noopener"><span class="nav-icon"><img src="assets/discord.svg" alt=""></span><span><strong>Discord</strong><small>Get support</small></span></a>';
    echo '</nav>';

    echo '<button class="copy-top copy-ip" data-copy="' . $ip . '"><span class="nav-icon"><img src="assets/copy.svg" alt=""></span><span><strong>Copy IP</strong><small>' . $ip . '</small></span></button>';

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

    echo '<script src="assets/main.js?v=10.1"></script>';
}
