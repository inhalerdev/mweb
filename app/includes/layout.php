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
    echo '<meta name="description" content="Mineacle public bans portal">';
    echo '<link rel="icon" type="image/png" href="assets/mineacle-square-logo.png?v=bansfull3.8.27.277.266.255.244.233.222.211.200.199.188.177.166.144.8.7.6.5.4.3.2">';
    echo '<link rel="stylesheet" href="assets/styles.css?v=banssingle4.0.34">';
    echo '</head>';
}

function mineacle_header(string $active = 'bans'): void {
    $config = mineacle_config();
    $vote = h((string) ($config['site']['vote'] ?? 'https://vote.mineacle.net'));
    $bans = h((string) ($config['site']['bans'] ?? 'https://bans.mineacle.net'));
    $stats = h((string) ($config['site']['stats'] ?? 'https://stats.mineacle.net'));
    $store = h((string) ($config['site']['store'] ?? 'https://store.mineacle.net'));
    $discord = h((string) ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM'));
    $ip = h((string) ($config['site']['ip'] ?? 'mineacle.net'));

    echo '<header class="site-header blocaria-style-header mineacle-floating-header mcx-header" id="siteHeader">';
    echo '<div class="mcx-nav-shell">';
    echo '<a class="mcx-logo" href="' . $bans . '" aria-label="Refresh Mineacle Bans">';
    echo '<img src="assets/mineacle-bans-hero-logo.png?v=bansfull3.8.27.277.266.255.244.233.222.211.200.199" alt="Mineacle Bans">';
    echo '</a>';
    echo '<div class="mcx-desktop-links" aria-label="Primary navigation">';
    echo '<a class="mcx-link mcx-vote ' . ($active === 'vote' ? 'is-active' : '') . '" href="' . $vote . '">Vote</a>';
    echo '<a class="mcx-link mcx-bans ' . ($active === 'bans' ? 'is-active' : '') . '" href="' . $bans . '">Bans</a>';
    echo '<a class="mcx-link mcx-stats ' . ($active === 'stats' ? 'is-active' : '') . '" href="' . $stats . '">Stats</a>';
    echo '<a class="mcx-button mcx-store ' . ($active === 'store' ? 'is-active' : '') . '" href="' . $store . '">Store</a>';
    echo '</div>';
    echo '<div class="mcx-desktop-actions">';
    echo '<a class="mcx-discord" href="' . $discord . '" target="_blank" rel="noopener" aria-label="Join Discord">';
    echo '<span class="mcx-discord-members" id="navDiscordOnline" aria-hidden="true">MEMBERS</span>';
    echo '<img src="assets/discord.svg?v=bansfull3.8.27.277.266.255.244.233.222.211.200.199" alt="">';
    echo '</a>';
    echo '<button class="mcx-play" type="button" data-copy-ip="' . $ip . '">Play</button>';
    echo '</div>';
    echo '<button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-controls="mainNav" aria-expanded="false"><span></span><span></span><span></span></button>';
    echo '<nav class="main-nav mcx-mobile-menu" id="mainNav" aria-label="Mobile navigation">';
    echo '<a class="mcx-mobile-link ' . ($active === 'vote' ? 'active' : '') . '" href="' . $vote . '">Vote</a>';
    echo '<a class="mcx-mobile-link ' . ($active === 'bans' ? 'active' : '') . '" href="' . $bans . '">Bans</a>';
    echo '<a class="mcx-mobile-link ' . ($active === 'stats' ? 'active' : '') . '" href="' . $stats . '">Stats</a>';
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
    echo '<script src="assets/main.js?v=banssingle4.0.34"></script>';
    echo '<script src="assets/hero-scroll.js?v=banssingle4.0.34"></script>';
    echo '<script src="assets/nav-server-status.js?v=banssingle4.0.34"></script>';
    echo <<<'HTML'
<script>
(function(){
  function normalizeDiscordMembers(){
    var el = document.getElementById('navDiscordOnline');
    if (!el) return;

    var scheduled = false;
    var updating = false;

    function format(raw){
      var text = String(raw || '').trim();
      var match = text.match(/\d+/);
      return match ? (match[0] + ' MEMBERS') : 'MEMBERS';
    }

    function apply(){
      scheduled = false;
      if (updating) return;
      var next = format(el.textContent);
      if (el.textContent !== next) {
        updating = true;
        el.textContent = next;
        updating = false;
      }
    }

    function schedule(){
      if (scheduled || updating) return;
      scheduled = true;
      window.requestAnimationFrame(apply);
    }

    apply();

    if (!el.dataset.mineacleJoinMembersObserver) {
      el.dataset.mineacleJoinMembersObserver = '1';
      new MutationObserver(schedule).observe(el, {
        childList: true,
        characterData: true,
        subtree: true
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', normalizeDiscordMembers);
  } else {
    normalizeDiscordMembers();
  }
})();
</script>
HTML;


    echo <<<'HTML'
<script>
(function(){
  function polishBanSectionLabels(){
    document.querySelectorAll('.section-heading > span, .records-kicker').forEach(function(el){
      if (/search\s+records/i.test(el.textContent || '')) {
        el.textContent = 'Public Records';
      }
    });
    document.querySelectorAll('.section-heading h2, .bans-v3-results-head h2, .records-title').forEach(function(el){
      if (/active\s+bans/i.test(el.textContent || '')) {
        el.textContent = 'Active Ban Records';
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', polishBanSectionLabels);
  } else {
    polishBanSectionLabels();
  }
})();
</script>
HTML;

    echo <<<'HTML'
<script>
(function(){
  function applyClientGuardRefresh(){
    var section = document.querySelector('.client-guard-detail-section, .client-guard-section');
    if (!section) return;

    var panelInner = section.querySelector('.client-guard-panel-inner');
    var titleWrap = section.querySelector('.client-guard-title-wrap, .client-guard-section-title');
    var copy = section.querySelector('.client-guard-copy');

    if (panelInner) {
      panelInner.classList.add('client-guard-panel-rebuilt');
    }

    if (panelInner && titleWrap && titleWrap.parentNode !== panelInner) {
      panelInner.insertBefore(titleWrap, panelInner.firstChild);
    }

    var img = section.querySelector('.client-guard-title-img, .client-guard-section-title img');
    if (img) {
      img.src = 'assets/mineacle-clientguard-logo-v2.png?v=banssingle4.0.34';
      img.alt = 'Mineacle Client Guard';
      img.classList.add('client-guard-title-img');
    }

    if (copy) {
      var heading = copy.querySelector('h2');
      var para = copy.querySelector('p');
      if (heading) {
        heading.textContent = 'Fair play, clear proof';
      }
      if (para) {
        para.textContent = 'Client Guard reviews repeated movement, combat, and building patterns\nbefore staff act, helping keep punishments fair and clear';
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyClientGuardRefresh);
  } else {
    applyClientGuardRefresh();
  }
})();
</script>
HTML;
}
