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
    echo '<link rel="icon" type="image/png" href="assets/fav.png?v=bansclean1.0.6">';
    mineacle_critical_styles();
    echo '<link rel="stylesheet" href="assets/bans-page.css?v=bansclean1.0.10">';
    echo '<link rel="stylesheet" href="assets/bans-rail-dock.css?v=bansclean1.0.19">';
    echo '<link rel="stylesheet" href="assets/bans-search-modules.css?v=bansclean1.0.8">';
    echo '</head>';
    echo '<body>';
}

function mineacle_critical_styles(): void {
    echo <<<'HTML'
<style>
:root{--page-bg:#090314;--island:#0d0718;--rail:#10002f;--line:rgba(188,77,255,.22);--line-strong:rgba(218,121,255,.42);--text:#fff7ff;--muted:#b6a8c6;--button:#8d23ff;--page-pad:24px;--content-right:32px;--shell-width:calc(100vw - 48px);--rail-width:124px;--rail-gap:38px;--content-left:calc(var(--page-pad) + var(--rail-width) + var(--rail-gap));--content-width:calc(100vw - var(--content-left) - var(--content-right));--radius:8px}
*{box-sizing:border-box}
html{min-height:100%;background-color:#07020d;background:radial-gradient(circle at 50% -12rem,rgba(179,42,255,.22),transparent 34rem),linear-gradient(180deg,var(--page-bg),#08020f 58%,#07020d);background-attachment:fixed}
body{min-height:100vh;margin:0;position:relative;overflow-x:hidden;background-color:#07020d;background:radial-gradient(circle at 42% -8rem,rgba(225,98,255,.16),transparent 34rem),linear-gradient(180deg,rgba(12,3,28,.94),rgba(10,3,18,.98) 68%,rgba(7,2,13,1)),#090314;background-attachment:fixed;color:var(--text);font-family:Inter,"Open Sans",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
body:before{content:"";position:fixed;inset:0;pointer-events:none;background:linear-gradient(90deg,rgba(122,33,255,.14),transparent 18%,transparent 82%,rgba(231,97,255,.12))}
a{color:inherit}.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}
.mineacle-bans-shell{width:var(--shell-width);min-height:58vh;margin:0 auto;display:grid;grid-template-columns:var(--rail-width) minmax(0,1fr);gap:var(--rail-gap);align-items:start;padding:28px 0 72px}
.mineacle-bans-main{grid-column:2;width:100%}
.mineacle-bans-rail{position:fixed;top:var(--page-pad);bottom:var(--page-pad);left:calc((100vw - var(--shell-width)) / 2);z-index:10;width:var(--rail-width);height:auto;min-height:0;display:grid;grid-template-rows:auto 1fr;justify-items:center;gap:0;padding:22px 0 28px;border:1px solid var(--line-strong);border-radius:var(--radius);background:linear-gradient(180deg,rgba(25,4,66,.98),rgba(8,2,28,.98));box-shadow:0 28px 70px rgba(0,0,0,.46),inset 0 1px 0 rgba(255,255,255,.06)}
.mineacle-bans-rail-logo{width:64px;height:64px;display:grid;place-items:center;border-radius:var(--radius);background:rgba(255,255,255,.05);overflow:hidden}.mineacle-bans-rail-logo img{width:56px;height:56px;display:block;margin:auto;object-fit:contain}
.mineacle-bans-rail-nav{width:100%;display:flex;flex-direction:column;justify-content:flex-start;align-items:center;gap:14px;margin-top:32px}.mineacle-bans-rail-link{width:56px;height:56px;display:grid;place-items:center;border:1px solid rgba(188,77,255,.2);border-radius:var(--radius);background:rgba(32,8,78,.76);text-decoration:none}.mineacle-bans-rail-link.is-active{border-color:rgba(245,178,255,.58);background:linear-gradient(135deg,#ff99f1,#9b25ff 54%,#4b00d8);box-shadow:0 0 0 5px rgba(157,37,255,.18),0 18px 34px rgba(147,26,235,.36)}.mineacle-bans-rail-link img{width:27px;height:27px;display:block;margin:auto;object-fit:contain;object-position:center;filter:brightness(0) invert(1)}
.mineacle-bans-topbar{width:100%;display:flex;justify-content:flex-start}
.mineacle-bans-search{width:min(100%,1120px);min-height:76px;display:grid;grid-template-columns:minmax(0,1fr) 64px;align-items:center;margin:0;padding:0 10px 0 0;border:1px solid rgba(188,77,255,.2);border-radius:var(--radius);background:rgba(9,3,19,.96);box-shadow:0 22px 52px rgba(0,0,0,.36),inset 0 1px 0 rgba(255,255,255,.045);transition:border-color .14s ease,background .14s ease,box-shadow .14s ease}.mineacle-bans-search:focus-within,.mineacle-bans-search.has-value{border-color:rgba(218,121,255,.48);background:rgba(12,4,28,.98);box-shadow:0 24px 56px rgba(0,0,0,.4),0 0 0 5px rgba(157,37,255,.12),inset 0 1px 0 rgba(255,255,255,.06)}.mineacle-bans-search.is-typing{border-color:rgba(255,165,247,.6)}
.mineacle-bans-search-field{min-width:0;height:74px;display:grid;grid-template-columns:minmax(0,1fr);align-items:center}.mineacle-bans-search-field>img{display:none}.mineacle-bans-search-field input{width:100%;height:74px;min-width:0;border:0;outline:0;background:transparent;color:var(--text);padding:0 12px 0 30px;font:inherit;font-size:18px;font-weight:800}.mineacle-bans-search-field input::placeholder{color:rgba(219,222,246,.55)}
.mineacle-bans-search-action{width:50px;height:50px;display:grid;place-items:center;justify-self:end;border:1px solid rgba(245,178,255,.24);border-radius:7px;background:linear-gradient(135deg,#ffa7f4,var(--button) 58%,#4b00d8);box-shadow:0 12px 24px rgba(151,34,255,.34),inset 0 2px 0 rgba(255,255,255,.22);color:transparent;font-size:0;cursor:pointer}.mineacle-bans-search-action:before{content:"";width:23px;height:23px;display:block;background:url("assets/search.svg") center/contain no-repeat;filter:brightness(0) invert(1);transition:width .14s ease,height .14s ease,background-image .14s ease}.mineacle-bans-search.has-value .mineacle-bans-search-action:before{width:18px;height:18px;background-image:url("assets/xmark.svg")}
.mineacle-footer-island{width:var(--content-width);min-height:332px;display:grid;grid-template-columns:minmax(220px,.95fr) repeat(3,minmax(150px,.66fr));gap:42px;align-items:start;margin:0 var(--content-right) 36px var(--content-left);padding:46px 52px 28px;overflow:hidden;border:1px solid rgba(188,77,255,.2);border-radius:var(--radius);background:linear-gradient(180deg,rgba(18,5,39,.96),rgba(9,3,19,.98));box-shadow:0 28px 76px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.035)}
.mineacle-footer-brand{min-width:0;overflow:hidden}.mineacle-footer-brand-logo{width:fit-content;max-width:100%;display:inline-flex;align-items:flex-start;justify-content:flex-start;margin:0 0 18px;padding:0;overflow:visible}.mineacle-footer-brand-logo img{width:clamp(150px,12vw,205px);max-width:100%;height:auto;max-height:108px;display:block;object-fit:contain;object-position:left center;filter:grayscale(1) brightness(1.16) contrast(1.05);opacity:.72;transition:filter .26s ease,opacity .26s ease,transform .26s ease}.mineacle-footer-brand-logo img:hover{filter:grayscale(0) brightness(1) contrast(1);opacity:1;transform:translateY(-1px)}.mineacle-footer-brand h2{margin:0 0 14px;color:#fff;font-size:27px;line-height:1.05;font-weight:950}.mineacle-footer-brand p{width:min(100%,310px);margin:0;color:var(--muted);font-size:15px;line-height:1.55;font-weight:780}
.mineacle-footer-socials{display:flex;align-items:center;gap:12px;margin-top:24px}.mineacle-footer-socials a{width:42px;height:42px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.08);border-radius:8px;background:rgba(255,255,255,.05)}.mineacle-footer-socials img{width:20px;height:20px;object-fit:contain;filter:brightness(0) invert(1)}
.mineacle-footer-column{display:grid;gap:17px}.mineacle-footer-column h3{position:relative;margin:0 0 14px;color:#fff;font-size:14px;line-height:1;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.mineacle-footer-column h3:after{content:"";position:absolute;left:0;bottom:-13px;width:34px;height:2px;border-radius:999px;background:linear-gradient(90deg,#ff99f1,rgba(157,37,255,0))}.mineacle-footer-column a{width:fit-content;color:var(--muted);font-size:16px;line-height:1.2;font-weight:850;text-decoration:none}
.mineacle-footer-bottom{grid-column:1/-1;min-height:48px;display:flex;align-items:end;justify-content:space-between;gap:22px;padding-top:28px;color:rgba(154,157,175,.86);font-size:13px;line-height:1.35;font-weight:850}.mineacle-footer-bottom span{display:inline-flex;align-items:center;gap:12px}.mineacle-footer-bottom img{display:none}
@media(max-width:1100px){:root{--page-pad:20px;--content-right:20px;--shell-width:calc(100vw - 40px);--rail-width:108px;--rail-gap:28px}.mineacle-footer-island{grid-template-columns:1.2fr 1fr 1fr;gap:36px 30px}.mineacle-footer-brand{grid-column:1/-1}}
@media(max-width:760px){:root{--page-pad:16px;--content-right:16px;--shell-width:calc(100vw - 32px);--rail-width:100%;--rail-gap:0px;--content-left:16px;--content-width:calc(100vw - 32px)}.mineacle-bans-shell{width:var(--shell-width);min-height:calc(100vh - 430px);display:block;padding:96px 0 40px}.mineacle-bans-rail{position:fixed;top:16px;left:var(--page-pad);right:var(--page-pad);bottom:auto;width:auto;height:64px;min-height:0;grid-template-columns:1fr;grid-template-rows:1fr;padding:8px}.mineacle-bans-rail-logo,.mineacle-bans-rail-support{display:none}.mineacle-bans-rail-nav{width:100%;flex-direction:row;justify-content:space-between;align-items:center;gap:8px;margin-top:0}.mineacle-bans-rail-link{width:46px;height:46px}.mineacle-bans-rail-link img{width:22px;height:22px}.mineacle-bans-search{min-height:62px;grid-template-columns:minmax(0,1fr) 56px;padding-right:7px}.mineacle-bans-search-field,.mineacle-bans-search-field input{height:60px}.mineacle-bans-search-field{grid-template-columns:minmax(0,1fr)}.mineacle-bans-search-field input{padding-left:18px;font-size:15px}.mineacle-bans-search-action{width:44px;height:44px}.mineacle-bans-search-action:before{width:21px;height:21px}.mineacle-footer-island{width:var(--content-width);margin:0 var(--content-right) 32px var(--content-left);min-height:0;grid-template-columns:1fr;gap:28px;padding:30px 22px 24px}.mineacle-footer-brand{grid-column:auto}.mineacle-footer-brand-logo{width:min(100%,210px)}.mineacle-footer-bottom{align-items:start;flex-direction:column;padding-top:8px}}
</style>
HTML;
}

function mineacle_header(string $active = 'bans'): void {
    unset($active);
}

function mineacle_footer(): void {
    $config = mineacle_config();
    $site = $config['site'] ?? [];
    $store = h((string) ($site['store'] ?? 'https://store.mineacle.net'));
    $bans = h((string) ($site['bans'] ?? 'https://bans.mineacle.net'));
    $stats = h((string) ($site['stats'] ?? 'https://stats.mineacle.net'));
    $vote = h((string) ($site['vote'] ?? 'https://vote.mineacle.net'));
    $discord = h((string) ($site['discord'] ?? 'https://discord.gg/VwbwWftefM'));
    $x = h((string) ($site['x'] ?? 'https://x.com/mineaclenetwork'));
    $supportEmail = h((string) ($site['support_email'] ?? 'support@mineacle.net'));

    echo '<footer class="mineacle-footer-island" aria-label="Mineacle footer">';
    echo '<section class="mineacle-footer-brand">';
    echo '<div class="mineacle-footer-brand-logo" aria-label="Mineacle Studios">';
    echo '<img src="assets/m-studios-logo-new.png?v=bansclean1.0.9" alt="Mineacle Studios">';
    echo '</div>';
    echo '<h2>Mineacle</h2>';
    echo '<p>Search active banned players from the public bans database.</p>';
    echo '<div class="mineacle-footer-socials">';
    echo '<a href="' . $discord . '" target="_blank" rel="noopener" aria-label="Discord"><img src="assets/discord.svg?v=bansclean1.0.6" alt=""></a>';
    echo '<a href="' . $x . '" target="_blank" rel="noopener" aria-label="X"><img src="assets/x.svg?v=bansclean1.0.6" alt=""></a>';
    echo '</div>';
    echo '</section>';

    echo '<nav class="mineacle-footer-column" aria-label="Quick links">';
    echo '<h3>Quick Links</h3>';
    echo '<a href="' . $bans . '">Bans</a>';
    echo '<a href="' . $stats . '">Stats</a>';
    echo '<a href="' . $vote . '">Vote</a>';
    echo '<a href="' . $store . '">Store</a>';
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
    echo '<span>Copyright © 2026 Mineacle Network. All Rights Reserved.</span>';
    echo '<span>Not affiliated with Microsoft or Mojang AB.</span>';
    echo '</div>';
    echo '</footer>';
    echo '<script src="assets/bans-page.js?v=bansclean1.0.11"></script>';
    echo '</body></html>';
}
