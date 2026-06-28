<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function mineacle_page_head(string $title, string $description = 'Search active bans on Mineacle Network'): void {
    mineacle_security_headers(false);
    $safeTitle = h($title);
    $safeDescription = h($description);
    echo '<!doctype html><html lang="en"><head>';
    echo '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Mineacle | ' . $safeTitle . '</title>';
    echo '<meta name="description" content="' . $safeDescription . '">';
    echo '<meta name="robots" content="index,follow">';
    echo '<link rel="icon" type="image/svg+xml" href="assets/icons/mineacle-m.svg?v=6.4.0">';
    echo '<link rel="preconnect" href="https://mc-heads.net">';
    echo '<link rel="stylesheet" href="assets/styles.css?v=6.4.0">';
    echo '</head><body>';
}

function mineacle_page_end(): void {
    echo '<script src="assets/app.js?v=6.4.0" defer></script>';
    echo '</body></html>';
}
