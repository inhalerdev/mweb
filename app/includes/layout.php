<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function mineacle_asset(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

function mineacle_head(string $title = 'Public Ban Records', string $description = ''): void
{
    $config = mineacle_config();
    $site = $config['site'];
    $fullTitle = $title === '' ? $site['title'] : $site['title'] . ' | ' . $title;
    $description = $description !== '' ? $description : (string) $site['description'];
    $canonical = (string) $site['canonical'];
    mineacle_security_headers('html');
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($fullTitle) ?></title>
    <meta name="description" content="<?= h($description) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="<?= h($canonical) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= h($fullTitle) ?>">
    <meta property="og:description" content="<?= h($description) ?>">
    <meta property="og:url" content="<?= h($canonical) ?>">
    <meta property="og:image" content="<?= h(rtrim((string) $site['url'], '/') . '/' . mineacle_asset('images/mineacle-bans-logo.svg')) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#13071c">
    <link rel="icon" href="<?= h(mineacle_asset('images/mineacle-square-logo.svg')) ?>" type="image/svg+xml">
    <link rel="preload" href="<?= h(mineacle_asset('styles.css')) ?>" as="style">
    <link rel="stylesheet" href="<?= h(mineacle_asset('styles.css')) ?>">
    <script type="application/ld+json">
<?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $site['title'],
        'url' => $canonical,
        'description' => $description,
        'publisher' => [
            '@type' => 'Organization',
            'name' => $site['name'],
            'url' => $site['home'],
            'logo' => rtrim((string) $site['url'], '/') . '/' . mineacle_asset('images/mineacle-square-logo.svg'),
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
</head>
<body data-server-ip="<?= h((string) $site['ip']) ?>">
    <?php
}

function mineacle_header(string $active = 'bans'): void
{
    $config = mineacle_config();
    $site = $config['site'];
    $items = [
        'vote' => ['Vote', (string) $site['vote']],
        'bans' => ['Bans', (string) $site['bans']],
        'stats' => ['Stats', (string) $site['stats']],
        'store' => ['Store', (string) $site['store']],
    ];
    ?>
<header class="mcx-header" data-header>
    <nav class="mcx-nav" aria-label="Primary navigation">
        <a class="mcx-logo-link" href="<?= h((string) $site['home']) ?>" aria-label="Mineacle home">
            <img src="<?= h(mineacle_asset('images/mineacle-bans-logo.svg')) ?>" alt="Mineacle Bans" class="mcx-logo" width="328" height="96">
        </a>

        <div class="mcx-links" aria-label="Desktop navigation">
            <?php foreach ($items as $key => [$label, $href]): ?>
                <a class="mcx-link <?= $key === $active ? 'is-active' : '' ?> <?= $key === 'store' ? 'is-store' : '' ?>" href="<?= h($href) ?>"><?= h($label) ?></a>
            <?php endforeach; ?>
            <a class="mcx-link is-discord" href="<?= h((string) $site['discord']) ?>" rel="noopener">Discord</a>
        </div>

        <div class="mcx-actions">
            <div class="mcx-status" data-server-status aria-live="polite">
                <span class="mcx-status-dot" data-server-dot></span>
                <span data-server-count>Checking</span>
            </div>
            <button class="mcx-play js-copy-ip" type="button" data-copy-text="<?= h((string) $site['ip']) ?>">
                <span>Play</span>
            </button>
        </div>

        <button class="mcx-menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-menu" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>
    </nav>

    <div class="mcx-mobile-panel" id="mobile-menu" data-mobile-menu hidden>
        <a href="<?= h((string) $site['vote']) ?>">Vote</a>
        <a href="<?= h((string) $site['bans']) ?>">Bans</a>
        <a href="<?= h((string) $site['stats']) ?>">Stats</a>
        <a class="is-store" href="<?= h((string) $site['store']) ?>">Store</a>
        <a class="is-discord" href="<?= h((string) $site['discord']) ?>" rel="noopener">Discord</a>
        <button class="js-copy-ip" type="button" data-copy-text="<?= h((string) $site['ip']) ?>">Copy IP</button>
    </div>
</header>
<div class="copy-toast" data-copy-toast role="status" aria-live="polite" hidden>
    <div class="copy-toast-icon">✓</div>
    <div>
        <strong>Server IP copied</strong>
        <span><?= h((string) $site['ip']) ?></span>
    </div>
</div>
    <?php
}

function mineacle_footer(): void
{
    $config = mineacle_config();
    $site = $config['site'];
    ?>
<footer class="site-footer">
    <div class="footer-inner">
        <img src="<?= h(mineacle_asset('images/mineacle-main-logo.svg')) ?>" alt="Mineacle Network" width="260" height="74">
        <p>Copyright © Mineacle Network 2026. All Rights Reserved.</p>
        <p class="footer-note">We are not affiliated with Microsoft or Mojang AB</p>
        <div class="footer-links">
            <a href="<?= h((string) $site['discord']) ?>" rel="noopener">Discord</a>
            <a href="<?= h((string) $site['x']) ?>" rel="noopener">X</a>
        </div>
    </div>
</footer>
<script src="<?= h(mineacle_asset('main.js')) ?>" defer></script>
</body>
</html>
    <?php
}
