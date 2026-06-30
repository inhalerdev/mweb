(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
      return;
    }
    fn();
  }

  function findBanTarget() {
    return document.querySelector('.bans-section, .bans-v3-results, .ban-list-wrap, #bansModule, #banRecordsModule');
  }

  function findHero() {
    return document.querySelector('.bans-records-hero, .bans-v3-hero, .bans-v34-background-fold, .bans-v32-logo-only-fold');
  }

  function createClientGuardSection() {
    var section = document.createElement('section');
    section.className = 'client-guard-detail-section';
    section.setAttribute('aria-label', 'Mineacle Client Guard overview');

    section.innerHTML = '' +
      '<div class="client-guard-title-wrap">' +
        '<img class="client-guard-title-img" src="assets/client-guard-title.png?v=banssingle3.9.05" alt="Client Guard">' +
      '</div>' +
      '<div class="client-guard-panel">' +
        '<div class="client-guard-panel-inner">' +
          '<div class="client-guard-copy">' +
            '<h2>Fair survival, reviewed by pattern</h2>' +
            '<p>MineacleClientGuard helps staff review client, movement, combat, and building behavior before action is taken. It is designed around repeated evidence instead of one strange moment.</p>' +
          '</div>' +
          '<ol class="client-guard-list">' +
            '<li><b>01</b><div><strong>Allowed client checks</strong><span>Compares launcher or loader signals when readable, then flags clients outside the allowed Mineacle client list for staff review.</span></div></li>' +
            '<li><b>02</b><div><strong>Movement review</strong><span>Watches fly-like movement, odd vertical changes, impossible recovery, and knockback behavior that does not line up with normal play.</span></div></li>' +
            '<li><b>03</b><div><strong>Combat patterns</strong><span>Looks for repeated attack timing, aura-like hit flow, reach-like consistency, and behavior that stays suspicious over multiple moments.</span></div></li>' +
            '<li><b>04</b><div><strong>Building evidence</strong><span>Reviews fast placement, auto-placement, scaffold-style patterns, and building flow that appears automated instead of player-controlled.</span></div></li>' +
            '<li><b>05</b><div><strong>Evidence over time</strong><span>One alert is not enough. Staff and ClientGuard look for repeated clear patterns so punishments stay fair and defensible.</span></div></li>' +
          '</ol>' +
        '</div>' +
      '</div>';

    return section;
  }

  function ensureClientGuardSection() {
    if (document.querySelector('.client-guard-detail-section')) {
      return;
    }

    var hero = findHero();
    var target = findBanTarget();
    var section = createClientGuardSection();

    if (hero && hero.parentNode) {
      hero.parentNode.insertBefore(section, hero.nextSibling);
      return;
    }

    if (target && target.parentNode) {
      target.parentNode.insertBefore(section, target);
    }
  }



  function installHeaderScrollState() {
    var header = document.getElementById('siteHeader');
    if (!header) {
      return;
    }

    var ticking = false;
    var threshold = 42;

    function apply() {
      ticking = false;
      var scrolled = window.scrollY > threshold;
      header.classList.toggle('is-scrolled', scrolled);
      document.documentElement.classList.toggle('mineacle-nav-scrolled', scrolled);
      document.body.classList.toggle('mineacle-nav-scrolled', scrolled);
    }

    function requestApply() {
      if (ticking) {
        return;
      }
      ticking = true;
      window.requestAnimationFrame(apply);
    }

    apply();
    window.addEventListener('scroll', requestApply, { passive: true });
    window.addEventListener('resize', requestApply, { passive: true });
  }

  ready(function () {
    installHeaderScrollState();
    var heroCopy = document.querySelector('.ban-hero-copy');
    if (!heroCopy || heroCopy.querySelector('.ban-hero-scroll-btn')) {
      ensureClientGuardSection();
      return;
    }

    var target = findBanTarget();
    if (target && !target.id) {
      target.id = 'banRecordsModule';
    }

    var actions = document.createElement('div');
    actions.className = 'ban-hero-actions';

    var button = document.createElement('button');
    button.className = 'ban-hero-scroll-btn';
    button.type = 'button';
    button.textContent = 'Click To See Bans';
    button.setAttribute('aria-label', 'Scroll to active ban records');

    button.addEventListener('click', function () {
      var scrollTarget = findBanTarget();
      if (!scrollTarget) {
        return;
      }
      if (!scrollTarget.id) {
        scrollTarget.id = 'banRecordsModule';
      }
      scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    actions.appendChild(button);
    heroCopy.appendChild(actions);
    ensureClientGuardSection();
  });
})();
