(() => {
  'use strict';

  const statusEl = document.getElementById('navPlayersOnline');
  const valueEl = document.getElementById('navPlayersOnlineValue');

  if (!statusEl || !valueEl) {
    return;
  }

  const endpoint = statusEl.getAttribute('data-status-url') || 'api/server-status.php';

  const setStatus = (count, state) => {
    statusEl.classList.remove('is-live', 'is-offline', 'is-unknown');

    if (Number.isFinite(count) && count >= 0) {
      valueEl.textContent = count.toLocaleString();
      statusEl.classList.add(count > 0 ? 'is-live' : 'is-offline');
      return;
    }

    valueEl.textContent = '0';
    statusEl.classList.add(state || 'is-unknown');
  };

  const readCount = (payload) => {
    if (!payload || typeof payload !== 'object') {
      return null;
    }

    const direct = payload.players_online ?? payload.online_players ?? payload.online;
    const nested = payload.players && typeof payload.players === 'object'
      ? (payload.players.online ?? payload.players.now)
      : null;

    const value = direct ?? nested;
    const count = Number(value);

    return Number.isFinite(count) ? count : null;
  };

  const refresh = async () => {
    try {
      const response = await fetch(endpoint, {
        method: 'GET',
        cache: 'no-store',
        headers: {
          'Accept': 'application/json'
        }
      });

      if (!response.ok) {
        setStatus(null, 'is-unknown');
        return;
      }

      const payload = await response.json();
      const count = readCount(payload);

      if (count !== null) {
        setStatus(count);
        return;
      }

      if (payload && payload.online === false) {
        setStatus(0, 'is-offline');
        return;
      }

      setStatus(null, 'is-unknown');
    } catch (_error) {
      setStatus(null, 'is-unknown');
    }
  };

  refresh();
  window.setInterval(refresh, 60000);

  const setupDiscordCollisionGuard = () => {
    const navShell = document.querySelector('#siteHeader.mcx-header .mcx-nav-shell');
    const playersOnline = document.getElementById('navPlayersOnline');
    const discordButton = document.querySelector('#siteHeader.mcx-header .mcx-discord');

    if (!navShell || !playersOnline || !discordButton) {
      return;
    }

    const getCssNumber = (name, fallback) => {
      const raw = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      const parsed = Number.parseFloat(raw);

      return Number.isFinite(parsed) ? parsed : fallback;
    };

    const refreshCollisionState = () => {
      const playersRect = playersOnline.getBoundingClientRect();
      const discordRect = discordButton.getBoundingClientRect();
      const expandedWidth = getCssNumber('--mcx-discord-expanded-w', 218);
      const safetyGap = 12;
      const expandedLeft = discordRect.right - expandedWidth;
      const wouldCollide = playersRect.right + safetyGap > expandedLeft;

      navShell.classList.toggle('discord-would-collide', wouldCollide);
    };

    discordButton.addEventListener('mouseenter', refreshCollisionState);
    discordButton.addEventListener('focus', refreshCollisionState);
    discordButton.addEventListener('pointerdown', refreshCollisionState);
    window.addEventListener('resize', refreshCollisionState, { passive: true });

    refreshCollisionState();
  };

  setupDiscordCollisionGuard();

})();
