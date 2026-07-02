<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function mineacle_page_head(string $title = 'Home'): void
{
    mineacle_security_headers();
    $config = mineacle_config();
    $site = $config['site'] ?? [];
    $name = (string) ($site['name'] ?? 'Mineacle');

    echo '<!doctype html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($name . ' | ' . $title) . '</title>';
    echo '<meta name="description" content="' . h($name . ' Minecraft server home page') . '">';
    echo '<link rel="icon" type="image/png" href="assets/fav.png?v=base12">';
    echo '<link rel="stylesheet" href="assets/home-page.css?v=base12">';
    echo '</head>';
    echo '<body>';
}

function mineacle_page_end(): void
{
    echo '<script src="assets/home-page.js?v=base12"></script>';
    echo '</body>';
    echo '</html>';
}
