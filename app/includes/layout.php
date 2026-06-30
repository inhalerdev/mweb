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
    echo '<link rel="icon" type="image/png" href="assets/fav.png?v=fold1.0.0">';
    mineacle_critical_styles();
    echo '<link rel="stylesheet" href="assets/bans-page.css?v=fold1.0.0">';
    echo '</head>';
    echo '<body>';
}

function mineacle_critical_styles(): void {
    echo <<<'HTML'
<style>
:root{--page:#181b20;--text:#f6f4f8}*{box-sizing:border-box}html{min-height:100%;background:#181b20}body{min-height:100vh;margin:0;background:#181b20;color:var(--text);font-family:Inter,"Open Sans",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}a{color:inherit}.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}
</style>
HTML;
}

function mineacle_header(string $active = 'bans'): void {
    unset($active);
}

function mineacle_page_end(): void {
    echo '<script src="assets/bans-page.js?v=fold1.0.0"></script>';
    echo '</body></html>';
}

function mineacle_footer(): void {
    mineacle_page_end();
}
