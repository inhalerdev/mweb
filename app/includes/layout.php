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
    echo '<link rel="icon" type="image/png" href="assets/mineacle-logo-purple.png?v=bansclean1.0.4">';
    mineacle_critical_styles();
    echo '<link rel="stylesheet" href="assets/bans-page.css?v=bansclean1.0.4">';
    echo '</head>';
    echo '<body>';
}

function mineacle_critical_styles(): void {
    echo <<<'HTML'
<style>
:root{--page-bg:#090314;--island:#0d0718;--rail:#10002f;--line:rgba(188,77,255,.22);--line-strong:rgba(218,121,255,.42);--text:#fff7ff;--muted:#b6a8c6;--button:#8d23ff;--content-left:218px;--content-right:44px;--radius:8px}
*{box-sizing:border-box}
html{min-height:100%;background-color:#07020d;background:radial-gradient(circle at 50% -12rem,rgba(179,42,255,.22),transparent 34rem),linear-gradient(180deg,var(--page-bg),#08020f 58%,#07020d);background-attachment:fixed}
body{min-height:100vh;margin:0;position:relative;overflow-x:hidden;background-color:#07020d;background:radial-gradient(circle at 42% -8rem,rgba(225,98,255,.16),transparent 34rem),linear-gradient(180deg,rgba(12,3,28,.94),rgba(10,3,18,.98) 68%,rgba(7,2,13,1)),#090314;background-attachment:fixed;color:var(--text);font-family:Inter,"Open Sans",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
body:before{content:"";position:fixed;inset:0;pointer-events:none;background:linear-gradient(90deg,rgba(122,33,255,.14),transparent 18%,transparent 82%,rgba(231,97,255,.12))}
a{color:inherit}.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}
.mineacle-bans-shell{min-height:calc(100vh - 378px);padding:46px var(--content-right) 84px var(--content-left)}
.mineacle-bans-main{width:min(100%,1620px)}
.mineacle-bans-rail{position:absolute;top:34px;left:88px;bottom:40px;z-index:10;width:104px;display:grid;grid-template-rows:auto 1fr auto;justify-items:center;gap:24px;padding:22px 0;border:1px solid var(--line-strong);border-radius:var(--radius);background:linear-gradient(180deg,rgba(25,4,66,.98),rgba(8,2,28,.98));box-shadow:0 28px 70px rgba(0,0,0,.46),inset 0 1px 0 rgba(255,255,255,.06)}
.mineacle-bans-rail-logo{width:64px;height:64px;display:grid;place-items:center;border-radius:var(--radius);background:rgba(255,255,255,.05);overflow:hidden}.mineacle-bans-rail-logo img{width:56px;height:56px;object-fit:contain}
.mineacle-bans-rail-nav{display:flex;flex-direction:column;justify-content:center;gap:14px}.mineacle-bans-rail-link{width:56px;height:56px;display:grid;place-items:center;border:1px solid rgba(188,77,255,.2);border-radius:var(--radius);background:rgba(32,8,78,.76);text-decoration:none}.mineacle-bans-rail-link.is-active{border-color:rgba(245,178,255,.58);background:linear-gradient(135deg,#ff99f1,#9b25ff 54%,#4b00d8);box-shadow:0 0 0 5px rgba(157,37,255,.18),0 18px 34px rgba(147,26,235,.36)}.mineacle-bans-rail-link img{width:27px;height:27px;object-fit:contain;filter:brightness(0) invert(1)}.mineacle-bans-rail-link:first-child img{width:34px;height:34px;filter:none}.mineacle-bans-rail-support{margin-top:auto}
.mineacle-bans-search{width:min(100%,1120px);min-height:76px;display:grid;grid-template-columns:minmax(0,1fr) 64px;align-items:center;padding:0 10px 0 0;border:1px solid rgba(188,77,255,.2);border-radius:var(--radius);background:rgba(9,3,19,.96);box-shadow:0 22px 52px rgba(0,0,0,.36),inset 0 1px 0 rgba(255,255,255,.045);transition:border-color .14s ease,background .14s ease,box-shadow .14s ease}.mineacle-bans-search:focus-within,.mineacle-bans-search.has-value{border-color:rgba(218,121,255,.48);background:rgba(12,4,28,.98);box-shadow:0 24px 56px rgba(0,0,0,.4),0 0 0 5px rgba(157,37,255,.12),inset 0 1px 0 rgba(255,255,255,.06)}.mineacle-bans-search.is-typing{border-color:rgba(255,165,247,.6)}
.mineacle-bans-search-field{min-width:0;height:74px;display:grid;grid-template-columns:minmax(0,1fr) 40px;align-items:center}.mineacle-bans-search-field>img{display:none}.mineacle-bans-search-field input{width:100%;height:74px;min-width:0;border:0;outline:0;background:transparent;color:var(--text);padding:0 12px 0 30px;font:inherit;font-size:18px;font-weight:800}.mineacle-bans-search-field input::placeholder{color:rgba(219,222,246,.55)}
.mineacle-bans-clear{width:32px;height:32px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.1);border-radius:7px;background:rgba(255,255,255,.06);color:transparent;font:inherit;font-size:0;font-weight:900;cursor:pointer;opacity:0;pointer-events:none}.mineacle-bans-clear:before{content:"";width:15px;height:15px;background:url("assets/icon-xmark.svg") center/contain no-repeat;filter:brightness(0) invert(1)}.mineacle-bans-search.has-value .mineacle-bans-clear{opacity:1;pointer-events:auto}.mineacle-bans-clear:hover,.mineacle-bans-clear:focus-visible{border-color:rgba(255,151,239,.44);background:rgba(255,90,183,.2);outline:0}
.mineacle-bans-search-action{width:50px;height:50px;display:grid;place-items:center;justify-self:end;border:1px solid rgba(245,178,255,.24);border-radius:7px;background:linear-gradient(135deg,#ffa7f4,var(--button) 58%,#4b00d8);box-shadow:0 12px 24px rgba(151,34,255,.34),inset 0 2px 0 rgba(255,255,255,.22);color:transparent;font-size:0;cursor:pointer}.mineacle-bans-search-action:before{content:"";width:23px;height:23px;background:url("assets/icon-search.svg") center/contain no-repeat;filter:brightness(0) invert(1)}
.mineacle-footer-island{width:min(calc(100% - var(--content-left) - var(--content-right)),1620px);min-height:338px;display:grid;grid-template-columns:minmax(280px,1.35fr) repeat(3,minmax(180px,.72fr));gap:54px;align-items:start;margin:0 var(--content-right) 40px var(--content-left);padding:52px 56px 30px;border:1px solid rgba(188,77,255,.2);border-radius:var(--radius);background:linear-gradient(180deg,rgba(18,5,39,.96),rgba(9,3,19,.98));box-shadow:0 28px 76px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.035)}
.mineacle-footer-brand img{width:60px;height:60px;display:block;object-fit:contain;margin-bottom:20px}.mineacle-footer-brand h2{margin:0 0 14px;color:#fff;font-size:31px;line-height:1.05;font-weight:950}.mineacle-footer-brand p{width:min(100%,360px);margin:0;color:var(--muted);font-size:16px;line-height:1.55;font-weight:780}
.mineacle-footer-socials{display:flex;align-items:center;gap:12px;margin-top:24px}.mineacle-footer-socials a{width:42px;height:42px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.08);border-radius:8px;background:rgba(255,255,255,.05)}.mineacle-footer-socials img{width:20px;height:20px;object-fit:contain;filter:brightness(0) invert(1)}
.mineacle-footer-column{display:grid;gap:17px}.mineacle-footer-column h3{position:relative;margin:0 0 14px;color:#fff;font-size:14px;line-height:1;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.mineacle-footer-column h3:after{content:"";position:absolute;left:0;bottom:-13px;width:34px;height:2px;border-radius:999px;background:linear-gradient(90deg,#ff99f1,rgba(157,37,255,0))}.mineacle-footer-column a{width:fit-content;color:var(--muted);font-size:16px;line-height:1.2;font-weight:850;text-decoration:none}
.mineacle-footer-bottom{grid-column:1/-1;min-height:48px;display:flex;align-items:end;justify-content:space-between;gap:22px;padding-top:28px;color:rgba(154,157,175,.86);font-size:13px;line-height:1.35;font-weight:850}.mineacle-footer-bottom span{display:inline-flex;align-items:center;gap:12px}.mineacle-footer-bottom img{width:24px;height:24px;object-fit:contain;opacity:.62}
@media(max-width:1100px){:root{--content-left:148px;--content-right:24px}.mineacle-bans-rail{left:24px}.mineacle-footer-island{grid-template-columns:1.2fr 1fr 1fr;gap:36px 30px}.mineacle-footer-brand{grid-column:1/-1}}
@media(max-width:760px){:root{--content-left:16px;--content-right:16px}.mineacle-bans-shell{min-height:calc(100vh - 430px);padding-top:96px;padding-bottom:40px}.mineacle-bans-rail{top:16px;left:16px;right:16px;bottom:auto;width:auto;height:64px;grid-template-columns:1fr;grid-template-rows:1fr;padding:8px}.mineacle-bans-rail-logo,.mineacle-bans-rail-support{display:none}.mineacle-bans-rail-nav{width:100%;flex-direction:row;justify-content:space-between;gap:8px}.mineacle-bans-rail-link{width:46px;height:46px}.mineacle-bans-rail-link img{width:22px;height:22px}.mineacle-bans-search{min-height:62px;grid-template-columns:minmax(0,1fr) 56px;padding-right:7px}.mineacle-bans-search-field,.mineacle-bans-search-field input{height:60px}.mineacle-bans-search-field{grid-template-columns:minmax(0,1fr) 36px}.mineacle-bans-search-field input{padding-left:18px;font-size:15px}.mineacle-bans-search-action{width:44px;height:44px}.mineacle-bans-search-action:before{width:21px;height:21px}.mineacle-footer-island{min-height:0;grid-template-columns:1fr;gap:28px;padding:30px 22px 24px}.mineacle-footer-brand{grid-column:auto}.mineacle-footer-brand img{width:52px;height:52px}.mineacle-footer-bottom{align-items:start;flex-direction:column;padding-top:8px}}
</style>
HTML;
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
    echo '<img src="assets/mineacle-logo-purple.png?v=bansclean1.0.4" alt="Mineacle">';
    echo '<h2>Mineacle</h2>';
    echo '<p>Search active banned players from the public bans database.</p>';
    echo '<div class="mineacle-footer-socials">';
    echo '<a href="' . $discord . '" target="_blank" rel="noopener" aria-label="Discord"><img src="assets/icon-discord.svg?v=bansclean1.0.4" alt=""></a>';
    echo '<a href="' . $x . '" target="_blank" rel="noopener" aria-label="X"><img src="assets/icon-x-twitter.svg?v=bansclean1.0.4" alt=""></a>';
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
    echo '<span><img src="assets/mineacle-logo-purple.png?v=bansclean1.0.4" alt=""> Copyright © 2026 Mineacle Network. All Rights Reserved.</span>';
    echo '<span>Not affiliated with Microsoft or Mojang AB.</span>';
    echo '</div>';
    echo '</footer>';
    echo '<script src="assets/bans-page.js?v=bansclean1.0.4"></script>';
    echo '</body></html>';
}
