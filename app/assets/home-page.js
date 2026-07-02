(() => {
  'use strict';

  const serverButton = document.querySelector('.mineacle-copy-ip');
  const serverIpLabel = serverButton ? serverButton.querySelector('.mineacle-home-server-ip') : null;
  const onlineCountNode = document.getElementById('mineaclePlayerCountValue');
  let copiedTimer = null;

  const normalizeOnlineCount = (payload) => {
    if (!payload || typeof payload !== 'object') return 0;

    const candidates = [
      payload.players_online,
      payload.online_players,
      payload.onlineCount,
      payload.player_count,
      payload.count,
      payload.players && payload.players.online
    ];

    for (const candidate of candidates) {
      if (typeof candidate === 'number' && Number.isFinite(candidate)) return Math.max(0, Math.floor(candidate));
      if (typeof candidate === 'string' && candidate.trim() !== '') {
        const value = Number(candidate);
        if (Number.isFinite(value)) return Math.max(0, Math.floor(value));
      }
    }

    return 0;
  };

  const updateOnlineCount = async () => {
    if (!onlineCountNode) return;

    try {
      const response = await fetch('api/server-status.php', {
        headers: { Accept: 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) return;
      const payload = await response.json();
      onlineCountNode.textContent = String(normalizeOnlineCount(payload));
    } catch (_) {
      onlineCountNode.textContent = '0';
    }
  };

  const fallbackCopy = (text) => {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', 'readonly');
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.select();

    try {
      document.execCommand('copy');
    } finally {
      textarea.remove();
    }
  };

  const copyText = async (text) => {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text);
      return;
    }

    fallbackCopy(text);
  };

  const showCopied = () => {
    if (!serverButton || !serverIpLabel) return;
    const defaultLabel = serverButton.dataset.defaultLabel || 'MINEACLE.NET';
    if (copiedTimer) window.clearTimeout(copiedTimer);

    serverButton.classList.add('is-copied');
    serverIpLabel.textContent = 'IP COPIED';

    copiedTimer = window.setTimeout(() => {
      serverButton.classList.remove('is-copied');
      serverIpLabel.textContent = defaultLabel;
    }, 1300);
  };

  if (serverButton) {
    serverButton.addEventListener('click', async () => {
      try {
        await copyText(serverButton.dataset.copyIp || 'mineacle.net');
        showCopied();
      } catch (_) {
        showCopied();
      }
    });
  }

  updateOnlineCount();
  window.setInterval(updateOnlineCount, 60000);
})();
