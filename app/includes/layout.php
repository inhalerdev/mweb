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
    echo '<link rel="stylesheet" href="assets/styles.css?v=banssingle4.1.0">';
    echo '<link rel="stylesheet" href="assets/bans-redesign.css?v=bansredesign1.0.0">';
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
    echo '<span class="mcx-discord-wrap" id="navDiscordWrap" aria-label="Discord members">';
    echo '<span class="mcx-discord-members" id="navDiscordOnline" aria-hidden="true">MEMBERS</span>';
    echo '<a class="mcx-discord" href="' . $discord . '" target="_blank" rel="noopener" aria-label="Join Discord">';
    echo '<img src="assets/discord.svg?v=bansfull3.8.27.277.266.255.244.233.222.211.200.199" alt="">';
    echo '</a>';
    echo '</span>';
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
    echo '<script src="assets/main.js?v=bansredesign1.0.0"></script>';
    echo '<script src="assets/nav-server-status.js?v=banssingle4.1.0"></script>';
    
    
    echo <<<'HTML'
<script>
(function(){
  var scheduled = false;
  var observerInstalled = false;

  function normalizeLabel(value){
    return String(value || '').trim().toLowerCase().replace(/\s+/g, ' ');
  }

  function tagModalDetailCards(){
    scheduled = false;

    var modal = document.getElementById('banModal');
    if (!modal) return;

    var status = document.getElementById('singleModalStatus');
    var typeValue = document.getElementById('singleModalType');
    var durationValue = document.getElementById('singleModalDuration');
    var appealValue = document.getElementById('singleModalAppeal');
    var emailValue = document.getElementById('singleModalEmail');

    var statusText = normalizeLabel(status ? status.textContent : '');
    var durationText = normalizeLabel(durationValue ? durationValue.textContent : '');
    var isPermanent = statusText.indexOf('permanent') !== -1 || durationText === 'permanent';

    modal.classList.toggle('is-permanent-modal', isPermanent);

    modal.querySelectorAll('.mineacle-ban-info-pill-single, .mineacle-punish-detail, article').forEach(function(card){
      var labelNode = card.querySelector('span');
      var label = normalizeLabel(labelNode ? labelNode.textContent : '');

      card.classList.toggle('mineacle-modal-reason-card', label === 'reason');
      card.classList.toggle('mineacle-modal-type-card', label === 'type');
      card.classList.toggle('mineacle-modal-duration-card', label === 'duration');
      card.classList.toggle('mineacle-modal-date-card', label === 'date');
      card.classList.toggle('mineacle-modal-appeal-card', label === 'appeal id');
      card.classList.toggle('mineacle-modal-email-card', label === 'support email');

      if (label === 'duration') {
        card.hidden = isPermanent;
        card.setAttribute('aria-hidden', isPermanent ? 'true' : 'false');
      }
    });

    if (status && typeValue) {
      var statusDisplay = (status.textContent || '').trim();
      if (statusDisplay && (typeValue.textContent || '').trim() !== statusDisplay) {
        typeValue.textContent = statusDisplay;
      }
      typeValue.classList.add('mineacle-modal-type-status-pill');
    }

    if (appealValue) {
      appealValue.classList.add('mineacle-modal-appeal-value');
    }

    if (emailValue) {
      emailValue.classList.add('mineacle-email-copy-value');
      var emailCard = emailValue.closest('.mineacle-ban-info-pill-single, .mineacle-punish-detail, article');
      if (emailCard) {
        emailCard.classList.add('mineacle-email-copy-field');
        if (!emailCard.hasAttribute('tabindex')) {
          emailCard.setAttribute('tabindex', '0');
        }
        emailCard.setAttribute('role', 'button');
        emailCard.setAttribute('aria-label', 'Copy support email');
        emailCard.setAttribute('title', 'Click to copy support email');
      }
    }
  }

  function scheduleTag(){
    if (scheduled) return;
    scheduled = true;
    window.requestAnimationFrame(tagModalDetailCards);
  }

  function installObserver(){
    var modal = document.getElementById('banModal');
    if (!modal || observerInstalled) return;

    observerInstalled = true;

    new MutationObserver(scheduleTag).observe(modal, {
      childList: true,
      subtree: true,
      characterData: true
    });
  }

  document.addEventListener('click', function(event){
    if (event.target && event.target.closest && event.target.closest('.info-btn, .js-info-button, button.mineacle-row-info-button, [data-info-index]')) {
      window.setTimeout(function(){
        installObserver();
        scheduleTag();
      }, 0);
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){
      installObserver();
      scheduleTag();
    });
  } else {
    installObserver();
    scheduleTag();
  }
})();
</script>
HTML;

echo <<<'HTML'
<script>
(function(){
  var observerInstalled = false;
  var scheduled = false;

  function syncModalTypeStatusPill(){
    scheduled = false;

    var status = document.getElementById('singleModalStatus');
    var type = document.getElementById('singleModalType');
    if (!status || !type) return;

    var statusText = (status.textContent || '').trim();
    if (!statusText) return;

    var normalizedClass = (status.className || '')
      .replace(/\bmineacle-ban-status-single\b/g, '')
      .replace(/\bmineacle-modal-status-pill\b/g, '')
      .trim();

    status.setAttribute('aria-hidden', 'true');
    status.classList.add('mineacle-modal-status-hidden');

    if ((type.textContent || '').trim() !== statusText) {
      type.textContent = statusText;
    }

    type.className = ('mineacle-modal-type-status-pill ' + normalizedClass).trim();

    var field = type.closest('.mineacle-ban-info-pill-single, article');
    if (field) {
      field.classList.add('mineacle-modal-type-status-field');
    }
  }

  function scheduleSync(){
    if (scheduled) return;
    scheduled = true;
    window.requestAnimationFrame(syncModalTypeStatusPill);
  }

  function installObserver(){
    var modal = document.getElementById('banModal');
    if (!modal || observerInstalled) return;

    observerInstalled = true;
    new MutationObserver(scheduleSync).observe(modal, {
      childList: true,
      subtree: true,
      characterData: true
    });
  }

  document.addEventListener('click', function(event){
    if (event.target && event.target.closest && event.target.closest('.info-btn, .js-info-button, button.mineacle-row-info-button, [data-info-index]')) {
      window.setTimeout(function(){
        installObserver();
        scheduleSync();
      }, 0);
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){
      installObserver();
      scheduleSync();
    });
  } else {
    installObserver();
    scheduleSync();
  }
})();
</script>
HTML;

echo <<<'HTML'
<script>
(function(){
  var scheduled = false;

  function normalizeBanControlSizing(){
    scheduled = false;

    document.querySelectorAll('a.ban-unban-cta, a.mineacle-ban-pay-single, .ban-action a, .mineacle-ban-actions-single a, .mineacle-punish-actions a').forEach(function(button){
      var classMatch = button.classList.contains('ban-unban-cta') || button.classList.contains('mineacle-ban-pay-single');
      var text = (button.textContent || '').trim().toLowerCase();

      if (!classMatch && text !== 'unban' && text !== 'buy unban') return;

      if (button.textContent.trim() !== 'BUY UNBAN') {
        button.textContent = 'BUY UNBAN';
      }

      if (!button.classList.contains('mineacle-buy-unban-size-lock')) {
        button.classList.add('mineacle-buy-unban-size-lock');
      }
    });

    document.querySelectorAll('.info-btn, .js-info-button, button.mineacle-row-info-button').forEach(function(button){
      if (!button.classList.contains('mineacle-info-hitbox-small')) {
        button.classList.add('mineacle-info-hitbox-small');
      }
    });
  }

  function scheduleNormalize(){
    if (scheduled) return;
    scheduled = true;
    window.requestAnimationFrame(normalizeBanControlSizing);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scheduleNormalize);
  } else {
    scheduleNormalize();
  }

  document.addEventListener('click', function(event){
    if (event.target && event.target.closest && event.target.closest('.js-info-button, .info-btn, [data-info-index], .js-ban-search-form, .js-ban-table')) {
      window.setTimeout(scheduleNormalize, 0);
    }
  });

  /*
    Safe observer:
    - Watches only the ban table/results region when available
    - Throttled by requestAnimationFrame
    - Does not repeatedly overwrite unchanged text/classes
  */
  function installObserver(){
    var target = document.querySelector('.js-ban-table, #banList, .bans-section, body');
    if (!target || target.dataset.mineacleControlObserver === '1') return;

    target.dataset.mineacleControlObserver = '1';

    new MutationObserver(scheduleNormalize).observe(target, {
      childList: true,
      subtree: true
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installObserver);
  } else {
    installObserver();
  }
})();
</script>
HTML;


echo <<<'HTML'
<script>
(function(){
  function copyText(value){
    var text = String(value || '').trim();
    if (!text) return Promise.resolve(false);

    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text).then(function(){ return true; }).catch(function(){ return false; });
    }

    var input = document.createElement('textarea');
    input.value = text;
    input.setAttribute('readonly', '');
    input.style.position = 'fixed';
    input.style.left = '-9999px';
    input.style.top = '0';
    document.body.appendChild(input);
    input.select();

    var ok = false;
    try {
      ok = document.execCommand('copy');
    } catch (error) {
      ok = false;
    }

    input.remove();
    return Promise.resolve(ok);
  }

  function prepareMoreInfoModal(){
    var email = document.getElementById('singleModalEmail');
    if (email) {
      var field = email.closest('.mineacle-ban-info-pill-single, .mineacle-punish-detail, article');
      if (field) {
        field.classList.add('mineacle-email-copy-field');
        field.setAttribute('role', 'button');
        field.setAttribute('tabindex', '0');
        field.setAttribute('aria-label', 'Copy support email');
        field.setAttribute('title', 'Click to copy support email');
      }
      email.classList.add('mineacle-email-copy-value');
    }

    var status = document.getElementById('singleModalStatus');
    if (status) {
      status.classList.add('mineacle-modal-status-pill');
    }
  }

  function handleEmailCopy(event){
    var target = event.target && event.target.closest ? event.target.closest('.mineacle-email-copy-field') : null;
    if (!target) return;

    var value = target.querySelector('#singleModalEmail, .mineacle-email-copy-value, strong');
    var text = value ? value.textContent : target.textContent;

    copyText(text).then(function(ok){
      target.classList.toggle('is-copied', Boolean(ok));
      if (ok) {
        target.setAttribute('title', 'Copied support email');
        window.setTimeout(function(){
          target.classList.remove('is-copied');
          target.setAttribute('title', 'Click to copy support email');
        }, 1100);
      }
    });
  }

  function handleEmailKey(event){
    if (event.key !== 'Enter' && event.key !== ' ') return;
    var target = event.target && event.target.closest ? event.target.closest('.mineacle-email-copy-field') : null;
    if (!target) return;

    event.preventDefault();
    handleEmailCopy({ target: target });
  }

  document.addEventListener('click', handleEmailCopy);
  document.addEventListener('keydown', handleEmailKey);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', prepareMoreInfoModal);
  } else {
    prepareMoreInfoModal();
  }

  new MutationObserver(prepareMoreInfoModal).observe(document.documentElement, {
    childList: true,
    subtree: true
  });
})();
</script>
HTML;

echo <<<'HTML'
<script>
(function(){
  function disableMobileLogoClick(){
    var mq = window.matchMedia('(max-width: 980px)');
    var logo = document.querySelector('#siteHeader .mcx-logo');
    if (!logo) return;

    function apply(){
      if (mq.matches) {
        logo.setAttribute('aria-disabled', 'true');
        logo.setAttribute('tabindex', '-1');
      } else {
        logo.removeAttribute('aria-disabled');
        logo.removeAttribute('tabindex');
      }
    }

    logo.addEventListener('click', function(event){
      if (mq.matches) {
        event.preventDefault();
        event.stopPropagation();
      }
    }, true);

    apply();

    if (mq.addEventListener) {
      mq.addEventListener('change', apply);
    } else if (mq.addListener) {
      mq.addListener(apply);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', disableMobileLogoClick);
  } else {
    disableMobileLogoClick();
  }
})();
</script>
HTML;

echo <<<'HTML'
<script>
(function(){
  function installDiscordHoverGate(){
    var wrap = document.getElementById('navDiscordWrap') || document.querySelector('#siteHeader .mcx-discord-wrap');
    if (!wrap || wrap.dataset.mineacleDiscordHoverGate === '4') return;

    var link = wrap.querySelector('.mcx-discord');
    wrap.dataset.mineacleDiscordHoverGate = '4';

    function squareHit(event){
      var rect = wrap.getBoundingClientRect();
      var squareWidth = 44;
      return event.clientX >= rect.right - squareWidth && event.clientX <= rect.right &&
             event.clientY >= rect.top && event.clientY <= rect.bottom;
    }

    function openFromSquare(event){
      if (wrap.classList.contains('is-open')) return;
      if (squareHit(event)) {
        wrap.classList.add('is-open');
      }
    }

    function openDiscord(){
      if (!link || !link.href) return;
      if ((link.getAttribute('target') || '').toLowerCase() === '_blank') {
        window.open(link.href, '_blank', 'noopener,noreferrer');
        return;
      }
      window.location.href = link.href;
    }

    wrap.addEventListener('pointerenter', openFromSquare);
    wrap.addEventListener('pointermove', openFromSquare);
    wrap.addEventListener('pointerleave', function(){
      wrap.classList.remove('is-open');
    });
    wrap.addEventListener('focusin', function(){
      wrap.classList.add('is-open');
    });
    wrap.addEventListener('focusout', function(){
      wrap.classList.remove('is-open');
    });

    /*
      The left extension is not an <a> while closed, so the browser does not show
      a Discord URL there. Once the square opens the button, this makes the full
      visible extension act like the same Discord action.
    */
    wrap.addEventListener('click', function(event){
      if (event.target.closest && event.target.closest('a')) return;
      if (!wrap.classList.contains('is-open')) return;
      event.preventDefault();
      event.stopPropagation();
      openDiscord();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installDiscordHoverGate);
  } else {
    installDiscordHoverGate();
  }
})();
</script>
HTML;


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
      var match = text.match(/[\d,.\s]+/);
      var count = match ? match[0].replace(/[^\d]/g, '') : '';
      return count ? (Number(count).toLocaleString() + ' MEMBERS') : 'MEMBERS';
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
      img.src = 'assets/mineacle-clientguard-logo-v2.png?v=banssingle4.1.0';
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

    echo '</body></html>';
}
