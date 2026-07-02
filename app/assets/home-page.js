(() => {
  'use strict';

  document.documentElement.classList.add('is-ready');

  const searchInput = document.getElementById('homeSearch');
  const clearButton = document.querySelector('.search-clear');
  const statusNode = document.querySelector('[data-server-status]');
  const statusCount = document.querySelector('[data-server-status-count]');
  const serverIp = statusNode ? statusNode.dataset.serverIp || 'mineacle.net' : 'mineacle.net';

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

  const setServerStatus = (online, onlineCount, maxCount) => {
    if (!statusNode || !statusCount) return;

    statusNode.classList.remove('is-loading', 'is-online', 'is-offline');
    statusNode.classList.add(online ? 'is-online' : 'is-offline');

    if (!online) {
      statusCount.textContent = 'Server offline';
      return;
    }

    const count = Number.isFinite(onlineCount) ? onlineCount : 0;
    const max = Number.isFinite(maxCount) && maxCount > 0 ? ` / ${maxCount}` : '';
    statusCount.textContent = `${count}${max} online`;
  };

  const loadLocalServerStatus = async () => {
    try {
      const response = await fetch('api/server-status.php', {
        headers: { Accept: 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) return null;
      const payload = await response.json();

      if (payload && payload.checked === false) {
        return null;
      }

      return {
        online: Boolean(payload && payload.online),
        onlineCount: Number(payload && payload.players_online),
        maxCount: Number(payload && payload.players_max)
      };
    } catch (_) {
      return null;
    }
  };

  const loadFallbackServerStatus = async () => {
    try {
      const response = await fetch(`https://api.mcsrvstat.us/3/${encodeURIComponent(serverIp)}`, {
        headers: { Accept: 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) return null;
      const payload = await response.json();
      const players = payload && payload.players ? payload.players : {};

      return {
        online: Boolean(payload && payload.online),
        onlineCount: Number(players.online),
        maxCount: Number(players.max)
      };
    } catch (_) {
      return null;
    }
  };

  const loadServerStatus = async () => {
    if (!statusNode || !statusCount) return;

    const payload = await loadLocalServerStatus() || await loadFallbackServerStatus();

    if (!payload) {
      setServerStatus(false, 0, 0);
      return;
    }

    setServerStatus(payload.online, payload.onlineCount, payload.maxCount);
  };

  loadServerStatus();
  window.setInterval(loadServerStatus, 60000);
})();
