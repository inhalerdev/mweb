(() => {
  'use strict';

  document.documentElement.classList.add('is-ready');

  const searchInput = document.getElementById('homeSearch');
  const clearButton = document.querySelector('.search-clear');
  const playerSearchRoot = document.querySelector('[data-player-search]');
  const playerSearchResults = document.querySelector('[data-player-search-results]');
  const statusNode = document.querySelector('[data-server-status]');
  const statusCount = document.querySelector('[data-server-status-count]');
  const copyServerIpButtons = document.querySelectorAll('[data-copy-server-ip]');
  const joinModal = document.querySelector('[data-join-modal]');
  const joinModalPanel = joinModal ? joinModal.querySelector('.join-modal-panel') : null;
  const joinGif = document.querySelector('[data-join-gif]');
  const openJoinModalButtons = document.querySelectorAll('[data-open-join-modal]');
  const closeJoinModalButtons = document.querySelectorAll('[data-close-join-modal]');
  const serverIp = statusNode ? statusNode.dataset.serverIp || 'mineacle.net' : 'mineacle.net';
  const statusRefreshMs = 5000;
  const statusFetchTimeoutMs = 1800;
  const statusCacheKey = `mineacle:server-status:${serverIp}`;
  const statusCacheMaxAgeMs = 15000;
  const playerSearchDelayMs = 160;
  let statusRequestActive = false;
  let playerSearchTimer = 0;
  let playerSearchAbort = null;
  let playerSearchRun = 0;
  let joinModalLastFocus = null;

  const fallbackCopyText = (text) => {
    const input = document.createElement('textarea');
    input.value = text;
    input.setAttribute('readonly', '');
    input.style.position = 'fixed';
    input.style.left = '-999px';
    document.body.append(input);
    input.select();

    try {
      return document.execCommand('copy');
    } finally {
      input.remove();
    }
  };

  const copyServerIp = async (event) => {
    const button = event.currentTarget;
    if (!(button instanceof HTMLElement)) return;

    const label = button.querySelector('[data-copy-server-label]');
    const defaultLabel = button.dataset.defaultLabel || (label ? label.textContent : 'Copy Server IP');
    const ip = button.dataset.serverIp || serverIp || 'mineacle.net';
    let copied;

    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(ip);
        copied = true;
      } else {
        copied = fallbackCopyText(ip);
      }
    } catch (_) {
      copied = fallbackCopyText(ip);
    }

    if (label) {
      label.textContent = copied ? 'IP Copied' : 'Copy Failed';
    }

    button.classList.toggle('is-copied', copied);
    button.classList.toggle('is-copy-failed', !copied);

    window.setTimeout(() => {
      if (label) label.textContent = defaultLabel || 'Copy Server IP';
      button.classList.remove('is-copied', 'is-copy-failed');
    }, 2200);
  };

  const openJoinModal = () => {
    if (!joinModal || !joinModalPanel) return;

    joinModalLastFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;

    if (joinGif && !joinGif.getAttribute('src')) {
      joinGif.setAttribute('src', joinGif.dataset.src || '');
    }

    joinModal.hidden = false;
    document.body.classList.add('has-join-modal');
    joinModalPanel.focus({ preventScroll: true });
  };

  const closeJoinModal = () => {
    if (!joinModal) return;

    joinModal.hidden = true;
    document.body.classList.remove('has-join-modal');

    if (joinModalLastFocus) {
      joinModalLastFocus.focus({ preventScroll: true });
      joinModalLastFocus = null;
    }
  };

  copyServerIpButtons.forEach((button) => {
    button.addEventListener('click', copyServerIp);
  });

  openJoinModalButtons.forEach((button) => {
    button.addEventListener('click', openJoinModal);
  });

  closeJoinModalButtons.forEach((button) => {
    button.addEventListener('click', closeJoinModal);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && joinModal && !joinModal.hidden) {
      closeJoinModal();
    }
  });

  const updateClearButton = () => {
    if (!searchInput || !clearButton) return;
    clearButton.hidden = searchInput.value.trim() === '';
  };

  const setPlayerSearchExpanded = (expanded) => {
    if (!searchInput) return;
    searchInput.setAttribute('aria-expanded', expanded ? 'true' : 'false');
  };

  const stopPlayerSearchRequest = () => {
    if (!playerSearchAbort) return;
    playerSearchAbort.abort();
    playerSearchAbort = null;
  };

  const hidePlayerResults = () => {
    window.clearTimeout(playerSearchTimer);
    stopPlayerSearchRequest();
    playerSearchRun += 1;

    if (playerSearchResults) {
      playerSearchResults.hidden = true;
      playerSearchResults.textContent = '';
    }

    setPlayerSearchExpanded(false);
  };

  const applyPlayerStatusClass = (row, player) => {
    const status = player && player.punishment_status && typeof player.punishment_status === 'object'
      ? player.punishment_status
      : {};

    if (status.search_state === 'permanent_ban') {
      row.classList.add('is-perm-banned');
    } else if (status.search_state === 'temporary_ban') {
      row.classList.add('is-temp-banned');
    } else if (status.search_state === 'permanent_mute' || status.search_state === 'temporary_mute') {
      row.classList.add('is-muted');
    }
  };

  const playerHeadUrl = (player) => {
    const skin = player && player.skin && typeof player.skin === 'object' ? player.skin : {};
    const url = typeof skin.head === 'string' && skin.head !== '' ? skin.head : '';

    if (url === '') {
      return '';
    }

    return url;
  };

  const playerProfileUrl = (name) => {
    return `/player/${encodeURIComponent(name)}`;
  };

  const renderPlayerResults = (players) => {
    if (!playerSearchResults) {
      return;
    }

    if (!Array.isArray(players) || players.length === 0) {
      playerSearchResults.hidden = true;
      playerSearchResults.textContent = '';
      setPlayerSearchExpanded(false);
      return;
    }

    playerSearchResults.textContent = '';

    players.slice(0, 8).forEach((player) => {
      const name = typeof player.name === 'string' ? player.name.trim() : '';
      if (name === '') return;

      const row = document.createElement('a');
      const nameNode = document.createElement('span');
      const actionNode = document.createElement('span');
      const headUrl = playerHeadUrl(player);

      row.className = 'player-search-option';
      row.href = playerProfileUrl(name);
      row.setAttribute('role', 'option');
      applyPlayerStatusClass(row, player);

      if (headUrl !== '') {
        const head = document.createElement('img');
        head.className = 'player-search-head';
        head.src = headUrl;
        head.alt = '';
        head.loading = 'lazy';
        head.decoding = 'async';
        head.setAttribute('aria-hidden', 'true');
        head.addEventListener('error', () => {
          head.remove();
          row.classList.add('has-no-avatar');
        }, { once: true });
        row.append(head);
      } else {
        row.classList.add('has-no-avatar');
      }

      nameNode.className = 'player-search-name';
      nameNode.textContent = name;
      row.append(nameNode);

      actionNode.className = 'player-search-action';
      actionNode.textContent = 'View Stats';
      row.append(actionNode);

      playerSearchResults.append(row);
    });

    if (playerSearchResults.children.length === 0) {
      hidePlayerResults();
      return;
    }

    playerSearchResults.hidden = false;
    setPlayerSearchExpanded(true);
  };

  const loadPlayerResults = async (query) => {
    if (!playerSearchResults || query === '') return;

    stopPlayerSearchRequest();

    const run = playerSearchRun + 1;
    const controller = new AbortController();
    playerSearchRun = run;
    playerSearchAbort = controller;

    try {
      const response = await fetch(`/api/player-search.php?q=${encodeURIComponent(query)}&limit=8&t=${Date.now()}`, {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
        signal: controller.signal
      });

      if (!response.ok || run !== playerSearchRun) return;

      const payload = await response.json();
      if (run !== playerSearchRun) return;
      if (!searchInput || searchInput.value.trim() !== query) return;

      renderPlayerResults(payload && payload.success ? payload.players : []);
    } catch (error) {
      if (!error || error.name !== 'AbortError') {
        hidePlayerResults();
      }
    } finally {
      if (playerSearchAbort === controller) {
        playerSearchAbort = null;
      }
    }
  };

  const queuePlayerSearch = () => {
    if (!searchInput) return;

    window.clearTimeout(playerSearchTimer);

    const query = searchInput.value.trim();

    if (query === '') {
      hidePlayerResults();
      return;
    }

    playerSearchTimer = window.setTimeout(() => {
      loadPlayerResults(query);
    }, playerSearchDelayMs);
  };

  if (searchInput && clearButton) {
    searchInput.addEventListener('input', () => {
      updateClearButton();
      queuePlayerSearch();
    });
    searchInput.addEventListener('focus', () => {
      if (searchInput.value.trim() !== '') queuePlayerSearch();
    });
    searchInput.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') hidePlayerResults();
    });
    clearButton.addEventListener('click', () => {
      searchInput.value = '';
      updateClearButton();
      hidePlayerResults();
      searchInput.focus();
    });
    updateClearButton();
  }

  document.addEventListener('click', (event) => {
    if (!playerSearchRoot || playerSearchRoot.contains(event.target)) return;
    hidePlayerResults();
  });

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

  const saveServerStatusCache = (payload) => {
    if (!payload || !payload.online) return;

    try {
      window.localStorage.setItem(statusCacheKey, JSON.stringify({
        online: Boolean(payload.online),
        onlineCount: Number.isFinite(payload.onlineCount) ? payload.onlineCount : 0,
        updatedAt: Date.now()
      }));
    } catch (_) {
      // Local storage can be unavailable in private or restricted browsing.
    }
  };

  const loadServerStatusCache = () => {
    try {
      const cached = JSON.parse(window.localStorage.getItem(statusCacheKey) || 'null');

      if (!cached || Date.now() - cached.updatedAt > statusCacheMaxAgeMs) {
        return null;
      }

      return {
        online: Boolean(cached.online),
        onlineCount: Number.isFinite(Number(cached.onlineCount)) ? Number(cached.onlineCount) : 0
      };
    } catch (_) {
      return null;
    }
  };

  const applyCachedServerStatus = () => {
    const cached = loadServerStatusCache();

    if (!cached) return;

    setServerStatus(cached.online, cached.onlineCount);
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

  const fetchStatusJson = async (url, timeoutMs = statusFetchTimeoutMs) => {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => {
      controller.abort();
    }, timeoutMs);

    try {
      const response = await fetch(url, {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
        signal: controller.signal
      });

      if (!response.ok) return null;

      return await response.json();
    } catch (_) {
      return null;
    } finally {
      window.clearTimeout(timeout);
    }
  };

  const loadLocalServerStatus = async () => {
    const payload = await fetchStatusJson(`/api/server-status.php?fast=1&t=${Date.now()}`);

    if (payload && payload.checked === false) {
      return null;
    }

    return normalizeStatusPayload(payload);
  };

  const loadServerStatus = async () => {
    if (!statusNode || !statusCount) return;
    if (statusRequestActive) return;

    statusRequestActive = true;

    try {
      const payload = await loadLocalServerStatus();

      if (!payload) {
        return;
      }

      setServerStatus(payload.online, payload.onlineCount);
      saveServerStatusCache(payload);
    } finally {
      statusRequestActive = false;
    }
  };

  applyCachedServerStatus();
  loadServerStatus();
  window.setInterval(loadServerStatus, statusRefreshMs);
  window.addEventListener('focus', loadServerStatus);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) loadServerStatus();
  });
})();
