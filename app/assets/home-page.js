(() => {
  'use strict';

  document.documentElement.classList.add('is-ready');

  const searchInput = document.getElementById('homeSearch');
  const clearButton = document.querySelector('.search-clear');
  const statusNode = document.querySelector('[data-server-status]');
  const statusCount = document.querySelector('[data-server-status-count]');
  const serverIp = statusNode ? statusNode.dataset.serverIp || 'mineacle.net' : 'mineacle.net';
  const statusRefreshMs = 5000;
  let statusRequestActive = false;

  const updateClearButton = () => {
    if (!searchInput || !clearButton) return;
    clearButton.hidden = searchInput.value.trim() === '';
  };

  if (searchInput && clearButton) {
    searchInput.addEventListener('input', updateClearButton);
    clearButton.addEventListener('click', () => {
      searchInput.value = '';
      updateClearButton();
      searchInput.focus();
    });
    updateClearButton();
  }

  const setServerStatus = (online, onlineCount) => {
    if (!statusNode || !statusCount) return;

    statusNode.classList.remove('is-loading', 'is-online', 'is-offline');
    statusNode.classList.add(online ? 'is-online' : 'is-offline');

    if (!online) {
      statusCount.textContent = 'Server offline';
      return;
    }

    const count = Number.isFinite(onlineCount) ? onlineCount : 0;
    statusCount.textContent = `${count} Currently Playing`;
  };

  const readNumber = (value) => {
    const number = Number(value);
    return Number.isFinite(number) && number > 0 ? Math.floor(number) : 0;
  };

  const normalizeStatusPayload = (payload) => {
    if (!payload || typeof payload !== 'object') return null;

    const players = payload.players && typeof payload.players === 'object' ? payload.players : {};

    return {
      online: Boolean(payload.online),
      onlineCount: readNumber(payload.players_online ?? payload.online_players ?? players.online),
      source: typeof payload.source === 'string' ? payload.source : ''
    };
  };

  const loadLocalServerStatus = async () => {
    try {
      const response = await fetch(`api/server-status.php?t=${Date.now()}`, {
        headers: { Accept: 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) return null;
      const payload = await response.json();

      if (payload && payload.checked === false) {
        return null;
      }

      return normalizeStatusPayload(payload);
    } catch (_) {
      return null;
    }
  };

  const loadFallbackServerStatus = async (url) => {
    try {
      const response = await fetch(url, {
        headers: { Accept: 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) return null;
      const payload = await response.json();

      return normalizeStatusPayload(payload);
    } catch (_) {
      return null;
    }
  };

  const loadServerStatus = async () => {
    if (!statusNode || !statusCount) return;
    if (statusRequestActive) return;

    statusRequestActive = true;

    try {
      const fallbacks = [
        `https://api.mcstatus.io/v2/status/java/${encodeURIComponent(serverIp)}`,
        `https://api.mcsrvstat.us/3/${encodeURIComponent(serverIp)}`
      ];
      let payload = await loadLocalServerStatus();

      for (const url of fallbacks) {
        if (payload && payload.source === 'direct') break;
        if (payload && (!payload.online || payload.onlineCount > 0)) break;

        const fallbackPayload = await loadFallbackServerStatus(url);
        if (!fallbackPayload) continue;

        payload = !payload || fallbackPayload.onlineCount > payload.onlineCount ? fallbackPayload : payload;
      }

      if (!payload) {
        setServerStatus(false, 0);
        return;
      }

      setServerStatus(payload.online, payload.onlineCount);
    } finally {
      statusRequestActive = false;
    }
  };

  loadServerStatus();
  window.setInterval(loadServerStatus, statusRefreshMs);
  window.addEventListener('focus', loadServerStatus);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) loadServerStatus();
  });
})();
