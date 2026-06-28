(() => {
  'use strict';

  const app = document.querySelector('[data-app]');
  if (!app) return;

  const $ = (id) => document.getElementById(id);
  const state = { page: 1, search: '', loading: false, lastRows: [] };

  const form = $('banSearchForm');
  const input = $('banSearch');
  const tbody = $('bansTableBody');
  const meta = $('tableMeta');
  const pagination = $('pagination');
  const prev = $('prevPage');
  const next = $('nextPage');
  const pageInfo = $('pageInfo');
  const modal = $('modalBackdrop');
  const modalContent = $('modalContent');
  const modalClose = $('modalClose');
  const copyBtn = $('copyIpButton');

  const infoText = {
    client: {
      title: 'Mineacle Client Guard',
      body: 'Mineacle Client Guard helps verify that players are joining from an acceptable client environment. The public site explains what we protect without exposing bypass-sensitive detection logic.',
      points: [
        ['Client brand review', 'Looks for missing, unreadable, or suspicious client brand signals'],
        ['Allowed client focus', 'Designed to keep normal Minecraft clients smooth while flagging risky environments'],
        ['Private thresholds', 'Exact checks, thresholds, and internal enforcement logic stay private for server security'],
        ['Staff visibility', 'Suspicious signals can be surfaced to staff for review and enforcement']
      ]
    },
    combat: {
      title: 'Combat Protection',
      body: 'Mineacle protects PvP, duels, and survival combat from behavior that gives players an unfair advantage.',
      points: [
        ['Combat fairness', 'Reviews impossible or highly suspicious combat interaction patterns'],
        ['Movement checks', 'Helps detect unsafe movement patterns like unauthorized flight or extreme velocity'],
        ['PvP integrity', 'Protects the value of real fights, item risk, and player-earned progression'],
        ['Evidence-first flow', 'Designed around useful signals instead of noisy public accusations']
      ]
    },
    community: {
      title: 'Community Safety',
      body: 'Mineacle enforcement protects more than leaderboards. It protects builds, trades, the economy, and the trust players need in a long-term survival server.',
      points: [
        ['Economy protection', 'Reduces unfair automation and client advantages that can distort player progress'],
        ['World protection', 'Discourages risky tools that threaten survival worlds and player builds'],
        ['Transparent records', 'Public active records help players see that enforcement is active'],
        ['Community trust', 'Keeps the server fair for new players, competitive players, and long-term grinders']
      ]
    }
  };

  const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
  }[char]));

  const setModal = (html) => {
    if (!modal || !modalContent) return;
    modalContent.innerHTML = html;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  };

  const closeModal = () => {
    if (!modal || !modalContent) return;
    modal.hidden = true;
    modalContent.innerHTML = '';
    document.body.style.overflow = '';
  };

  const bindProfileTabs = () => {
    const tabs = modalContent?.querySelectorAll('[data-profile-tab]');
    const panes = modalContent?.querySelectorAll('[data-profile-pane]');
    if (!tabs || !panes) return;

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const target = tab.getAttribute('data-profile-tab');
        tabs.forEach((item) => item.classList.toggle('active', item === tab));
        panes.forEach((pane) => pane.classList.toggle('active', pane.getAttribute('data-profile-pane') === target));
      });
    });
  };

  const renderInfoModal = (item) => {
    const points = item.points.map(([label, text]) => (
      `<div class="detail-item"><span>${esc(label)}</span><strong>${esc(text)}</strong></div>`
    )).join('');

    setModal(`<h2 id="modalTitle">${esc(item.title)}</h2><p>${esc(item.body)}</p><div class="detail-grid">${points}</div>`);
  };

  const updateStatusCount = (value) => {
    const online = $('onlineCount');
    if (online) online.textContent = String(value ?? 0);
  };

  const loadStatus = async () => {
    try {
      const response = await fetch('api/server-status.php', { cache: 'no-store' });
      const data = await response.json();
      updateStatusCount(data.players_online);
    } catch (error) {
      updateStatusCount(0);
    }
  };

  const renderRows = (rows) => {
    if (!tbody) return;
    state.lastRows = Array.isArray(rows) ? rows : [];

    if (state.lastRows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="loading-cell">No active bans found</td></tr>';
      return;
    }

    tbody.innerHTML = state.lastRows.map((ban) => `<tr>
      <td><div class="player-cell"><img loading="lazy" src="${esc(ban.skin)}" alt=""><span>${esc(ban.username)}</span></div></td>
      <td class="reason-cell" title="${esc(ban.reason)}">${esc(ban.reason)}</td>
      <td>${esc(ban.staff)}</td>
      <td>${esc(ban.server)}</td>
      <td>${esc(ban.date)}</td>
      <td><span class="status-pill ${esc(ban.status_type)}">${esc(ban.status)}</span></td>
      <td><button class="row-button" type="button" data-ban-id="${esc(ban.id)}">View Profile</button></td>
    </tr>`).join('');
  };

  const renderPagination = (paginationData) => {
    if (!pagination || !pageInfo || !prev || !next) return;

    if (!paginationData || (paginationData.total_pages ?? 1) <= 1) {
      pagination.hidden = true;
      return;
    }

    pagination.hidden = false;
    pageInfo.textContent = `Page ${paginationData.page} of ${paginationData.total_pages}`;
    prev.disabled = !paginationData.has_prev;
    next.disabled = !paginationData.has_next;
  };

  const renderTableError = () => {
    if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="loading-cell">Unable to load bans right now</td></tr>';
    if (meta) meta.textContent = 'Records unavailable';
  };

  const renderNoProfileModal = (query) => {
    setModal(`<h2 id="modalTitle">No active public record</h2><p>No active ban profile was found for <strong>${esc(query)}</strong>. Future profile tabs will support connected bans, mutes, kicks, warnings, and in-game stats once those data sources are linked.</p><div class="detail-grid"><div class="detail-item"><span>Search</span><strong>${esc(query)}</strong></div><div class="detail-item"><span>Status</span><strong>No active ban result</strong></div></div>`);
  };

  const loadBans = async (options = {}) => {
    if (state.loading) return;
    state.loading = true;
    if (meta) meta.textContent = 'Loading records';

    const params = new URLSearchParams({ page: String(state.page) });
    if (state.search) params.set('search', state.search);

    try {
      const response = await fetch(`api/bans.php?${params.toString()}`, { cache: 'no-store' });
      const data = await response.json();

      if (!data.success) {
        renderTableError();
        return;
      }

      renderRows(data.bans);
      renderPagination(data.pagination);

      const total = data.pagination?.total ?? 0;
      const active = data.stats?.active_bans ?? total;
      if (meta) {
        meta.textContent = `${total} result${total === 1 ? '' : 's'} • ${active} active ban${active === 1 ? '' : 's'}`;
      }

      if (options.openProfile === true && state.search !== '') {
        if (Array.isArray(data.bans) && data.bans.length > 0 && data.bans[0].id) {
          await loadBanDetail(data.bans[0].id, true);
        } else {
          renderNoProfileModal(state.search);
        }
      }
    } catch (error) {
      renderTableError();
    } finally {
      state.loading = false;
    }
  };

  const renderBanProfile = (ban) => {
    const pay = ban.can_pay
      ? `<a href="${esc(ban.unban_url)}" target="_blank" rel="noopener">${esc(ban.price)} Unban Checkout</a>`
      : '';

    setModal(`<div class="profile-modal">
      <header class="profile-header">
        <img class="profile-avatar" src="${esc(ban.skin)}" alt="${esc(ban.username)}">
        <div>
          <p class="eyebrow">Player Profile</p>
          <h2 id="modalTitle">${esc(ban.username)}</h2>
          <p>Public Mineacle profile shell for punishments and future connected in-game stats</p>
          <div class="profile-chips">
            <span class="profile-chip">${esc(ban.status)}</span>
            <span class="profile-chip">${esc(ban.type)}</span>
            <span class="profile-chip">${esc(ban.duration)}</span>
          </div>
        </div>
      </header>

      <nav class="profile-tabs" aria-label="Player profile sections">
        <button class="profile-tab active" type="button" data-profile-tab="overview">Overview</button>
        <button class="profile-tab" type="button" data-profile-tab="bans">Bans</button>
        <button class="profile-tab" type="button" data-profile-tab="mutes">Mutes</button>
        <button class="profile-tab" type="button" data-profile-tab="kicks">Kicks</button>
        <button class="profile-tab" type="button" data-profile-tab="stats">Stats</button>
      </nav>

      <section class="profile-pane active" data-profile-pane="overview">
        <h3>Record Overview</h3>
        <div class="detail-grid">
          <div class="detail-item"><span>Appeal ID</span><strong>${esc(ban.appeal_id)}</strong></div>
          <div class="detail-item"><span>Status</span><strong>${esc(ban.status)} ${esc(ban.type)}</strong></div>
          <div class="detail-item"><span>Reason</span><strong>${esc(ban.reason)}</strong></div>
          <div class="detail-item"><span>Staff</span><strong>${esc(ban.staff)}</strong></div>
          <div class="detail-item"><span>Server</span><strong>${esc(ban.server)}</strong></div>
          <div class="detail-item"><span>Issued</span><strong>${esc(ban.date)}</strong></div>
          <div class="detail-item"><span>Expires</span><strong>${esc(ban.expires)}</strong></div>
          <div class="detail-item"><span>Flags</span><strong>${esc(ban.flags_text)}</strong></div>
        </div>
      </section>

      <section class="profile-pane" data-profile-pane="bans">
        <h3>Active Ban History</h3>
        <div class="profile-list">
          <div class="profile-record"><strong>${esc(ban.date)}</strong><span>${esc(ban.reason)}</span><span class="status-pill ${esc(ban.status_type)}">${esc(ban.status)}</span></div>
        </div>
      </section>

      <section class="profile-pane" data-profile-pane="mutes">
        <h3>Mutes</h3>
        <div class="profile-placeholder">This tab is ready for the LiteBans mute connection. Once connected, it can show active and recent mutes, mute reasons, staff, dates, and expiration data.</div>
      </section>

      <section class="profile-pane" data-profile-pane="kicks">
        <h3>Kicks / Warnings</h3>
        <div class="profile-placeholder">This tab is ready for LiteBans kicks and warnings. Once connected, it can show moderation events that do not belong in the active ban table.</div>
      </section>

      <section class="profile-pane" data-profile-pane="stats">
        <h3>In-Game Stats</h3>
        <div class="stats-grid">
          <div class="stat-box"><span>Kills</span><strong>—</strong></div>
          <div class="stat-box"><span>Deaths</span><strong>—</strong></div>
          <div class="stat-box"><span>Playtime</span><strong>—</strong></div>
          <div class="stat-box"><span>Balance</span><strong>—</strong></div>
        </div>
        <div class="profile-placeholder" style="margin-top:14px">This player stats tab is prepared for the MineacleCore stats database. Once connected, it can show trackable survival stats in a larger profile layout like competitive stats sites.</div>
      </section>

      <div class="modal-actions"><a href="${esc(ban.discord)}" target="_blank" rel="noopener">Discord Support</a>${pay}</div>
    </div>`);

    bindProfileTabs();
  };

  const loadBanDetail = async (id) => {
    setModal('<h2 id="modalTitle">Loading profile</h2><p>Fetching player punishment profile</p>');

    try {
      const response = await fetch(`api/bans.php?id=${encodeURIComponent(id)}`, { cache: 'no-store' });
      const data = await response.json();

      if (!data.success || !data.detail) {
        setModal('<h2 id="modalTitle">Profile unavailable</h2><p>That active ban profile could not be loaded right now</p>');
        return;
      }

      renderBanProfile(data.detail);
    } catch (error) {
      setModal('<h2 id="modalTitle">Profile unavailable</h2><p>That active ban profile could not be loaded right now</p>');
    }
  };

  modalClose?.addEventListener('click', closeModal);
  modal?.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal && !modal.hidden) closeModal(); });

  document.querySelectorAll('[data-info]').forEach((button) => {
    button.addEventListener('click', () => {
      const item = infoText[button.dataset.info];
      if (item) renderInfoModal(item);
    });
  });

  copyBtn?.addEventListener('click', async () => {
    const ip = copyBtn.dataset.copyIp || app.dataset.serverIp || 'mineacle.net';

    try {
      await navigator.clipboard.writeText(ip);
    } catch (error) {
      const fallback = document.createElement('textarea');
      fallback.value = ip;
      fallback.setAttribute('readonly', 'readonly');
      fallback.style.position = 'fixed';
      fallback.style.left = '-9999px';
      document.body.appendChild(fallback);
      fallback.select();
      document.execCommand('copy');
      fallback.remove();
    }

    copyBtn.classList.add('copied');
    const copyText = $('copyIpMain');
    if (copyText) copyText.textContent = 'IP COPIED';

    clearTimeout(copyBtn._timer);
    copyBtn._timer = setTimeout(() => {
      copyBtn.classList.remove('copied');
      if (copyText) copyText.textContent = 'MINEACLE.NET';
    }, 1400);
  });

  form?.addEventListener('submit', (event) => {
    event.preventDefault();
    state.search = input ? input.value.trim() : '';
    state.page = 1;
    loadBans({ openProfile: state.search !== '' });
  });

  let searchTimer;
  input?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      state.search = input.value.trim();
      state.page = 1;
      loadBans({ openProfile: false });
    }, 420);
  });

  prev?.addEventListener('click', () => {
    if (state.page > 1) {
      state.page -= 1;
      loadBans({ openProfile: false });
    }
  });

  next?.addEventListener('click', () => {
    state.page += 1;
    loadBans({ openProfile: false });
  });

  tbody?.addEventListener('click', (event) => {
    const button = event.target.closest('[data-ban-id]');
    if (!button) return;
    loadBanDetail(button.dataset.banId);
  });

  loadStatus();
  loadBans({ openProfile: false });
  setInterval(loadStatus, 30000);
})();
