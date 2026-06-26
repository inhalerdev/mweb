(() => {
  'use strict';

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

  const state = {
    page: 1,
    search: '',
    loading: false,
    controller: null,
    bans: []
  };

  const els = {
    form: $('.js-ban-search-form'),
    search: $('.js-ban-search'),
    clear: $('.js-ban-clear'),
    table: $('.js-ban-table'),
    prev: $('.js-ban-prev'),
    next: $('.js-ban-next'),
    meta: $('.js-ban-meta'),
    page: $('.js-ban-page'),
    recordsState: $('[data-records-state]'),
    header: $('[data-header]'),
    menuToggle: $('[data-menu-toggle]'),
    mobileMenu: $('[data-mobile-menu]'),
    toast: $('[data-copy-toast]'),
    serverStatus: $('[data-server-status]'),
    serverCount: $('[data-server-count]'),
    serverDot: $('[data-server-dot]')
  };

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (ch) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
  }[ch]));

  const normalizeBan = (ban) => ({
    id: ban.id ?? 0,
    username: ban.username || ban.name || 'Unknown',
    uuid: ban.uuid || '',
    skin: ban.skin || '',
    reason: ban.reason || 'Rule violation',
    type: ban.type || (ban.ipban ? 'IP Ban' : 'Player Ban'),
    duration: ban.duration || (ban.permanent ? 'Permanent' : 'Temporary'),
    date: ban.date || 'Unknown',
    expires: ban.expires || (ban.permanent ? 'Never' : 'Unknown'),
    status: ban.status || 'Active',
    ipban: Boolean(ban.ipban),
    temporary: Boolean(ban.temporary),
    permanent: Boolean(ban.permanent),
    appeal_id: ban.appeal_id || (ban.id ? `MCL-${ban.id}` : 'MCL-PENDING'),
    email: ban.email || 'support@mineacle.net',
    discord: ban.discord || 'https://discord.gg/VwbwWftefM',
    can_pay: Boolean(ban.can_pay),
    action: ban.action || (ban.temporary ? 'Wait It Out' : 'Appeal Only'),
    price: ban.price || '',
    unban_url: ban.unban_url || ''
  });

  function setLoading(loading) {
    state.loading = loading;
    if (els.recordsState) els.recordsState.textContent = loading ? 'Loading records' : `${state.bans.length} shown`;
    if (loading) {
      if (els.prev) els.prev.disabled = true;
      if (els.next) els.next.disabled = true;
    }
  }

  function renderEmpty(message = 'No active bans found') {
    if (!els.table) return;
    els.table.innerHTML = `<div class="empty-state"><strong>${escapeHtml(message)}</strong><span>Try a different player name or check back later.</span></div>`;
  }

  function renderError(message = 'Unable to load bans right now') {
    if (!els.table) return;
    els.table.innerHTML = `<div class="empty-state is-error"><strong>${escapeHtml(message)}</strong><span>The public table is temporarily unavailable.</span></div>`;
  }

  function renderBans(bans, pagination = {}) {
    if (!els.table) return;
    state.bans = bans.map(normalizeBan);
    window.mineacleCurrentBans = state.bans;

    if (!state.bans.length) {
      renderEmpty(state.search ? 'No matching active bans found' : 'No active bans found');
    } else {
      const rows = state.bans.map((ban, index) => {
        const badgeClass = ban.ipban ? 'is-ip' : (ban.permanent ? 'is-permanent' : 'is-temp');
        const avatar = ban.ipban || !ban.skin
          ? `<div class="avatar-placeholder" aria-hidden="true">!</div>`
          : `<img src="${escapeHtml(ban.skin)}" alt="" width="42" height="42" loading="lazy" decoding="async">`;

        return `
          <article class="ban-row">
            <div class="ban-player">
              <div class="ban-avatar">${avatar}</div>
              <div>
                <h3>${escapeHtml(ban.username)}</h3>
                <span>${escapeHtml(ban.date)}</span>
              </div>
            </div>
            <div class="ban-reason">
              <span class="mobile-label">Reason</span>
              <p>${escapeHtml(ban.reason)}</p>
            </div>
            <div class="ban-duration">
              <span class="mobile-label">Duration</span>
              <strong>${escapeHtml(ban.duration)}</strong>
            </div>
            <button class="ban-type-pill ${badgeClass}" type="button" data-info-index="${index}" aria-label="View ban details for ${escapeHtml(ban.username)}">
              <span>${escapeHtml(ban.type)}</span>
              <b>i</b>
            </button>
          </article>`;
      }).join('');

      els.table.innerHTML = `
        <div class="ban-table-head" aria-hidden="true">
          <span>Player</span><span>Reason</span><span>Duration</span><span>Info</span>
        </div>
        ${rows}`;
    }

    state.page = Number(pagination.page || state.page || 1);
    if (els.page) els.page.textContent = String(state.page);
    if (els.meta) els.meta.textContent = `Page ${state.page}`;
    if (els.prev) els.prev.disabled = !pagination.has_previous;
    if (els.next) els.next.disabled = !pagination.has_next;
    if (els.recordsState) els.recordsState.textContent = state.bans.length ? `${state.bans.length} shown` : 'No records';
  }

  async function loadBans({ page = state.page, search = state.search } = {}) {
    if (!els.table) return;

    if (state.controller) {
      state.controller.abort();
    }
    state.controller = new AbortController();

    state.page = Math.max(1, Number(page) || 1);
    state.search = String(search || '').trim().slice(0, 32);
    setLoading(true);

    const params = new URLSearchParams({ page: String(state.page) });
    if (state.search) params.set('search', state.search);

    try {
      const response = await fetch(`api/bans.php?${params.toString()}`, {
        headers: { 'Accept': 'application/json' },
        signal: state.controller.signal
      });
      const json = await response.json();
      if (!response.ok || !json.success) {
        renderError(json.error || 'Unable to load bans right now');
        return;
      }
      renderBans(Array.isArray(json.bans) ? json.bans : [], json.pagination || {});
    } catch (error) {
      if (error.name !== 'AbortError') {
        renderError(error.message || 'Unable to load bans right now');
      }
    } finally {
      setLoading(false);
    }
  }

  function createModal() {
    let modal = $('#banModal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'banModal';
    modal.className = 'ban-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'banModalTitle');
    modal.hidden = true;
    modal.innerHTML = `
      <div class="ban-modal-backdrop" data-modal-close></div>
      <div class="ban-modal-card">
        <button class="ban-modal-close" type="button" data-modal-close aria-label="Close ban details">×</button>
        <div class="modal-player">
          <div class="modal-avatar" data-modal-avatar></div>
          <div>
            <span class="eyebrow">Ban Details</span>
            <h2 id="banModalTitle" data-modal-name>Player</h2>
            <p data-modal-status>Active</p>
          </div>
        </div>
        <div class="modal-grid">
          <div><span>Type</span><strong data-modal-type></strong></div>
          <div><span>Duration</span><strong data-modal-duration></strong></div>
          <div><span>Date</span><strong data-modal-date></strong></div>
          <div><span>Expires</span><strong data-modal-expires></strong></div>
        </div>
        <div class="modal-reason"><span>Reason</span><p data-modal-reason></p></div>
        <div class="modal-appeal">
          <div><span>Appeal ID</span><strong data-modal-appeal></strong></div>
          <div><span>Email</span><strong data-modal-email></strong></div>
        </div>
        <div class="modal-actions" data-modal-actions></div>
      </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', (event) => {
      if (event.target.matches('[data-modal-close]')) closeModal();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !modal.hidden) closeModal();
    });
    return modal;
  }

  function openBanInfo(ban) {
    const modal = createModal();
    const data = normalizeBan(ban);
    const avatar = $('[data-modal-avatar]', modal);
    $('[data-modal-name]', modal).textContent = data.username;
    $('[data-modal-status]', modal).textContent = data.status;
    $('[data-modal-type]', modal).textContent = data.type;
    $('[data-modal-duration]', modal).textContent = data.duration;
    $('[data-modal-date]', modal).textContent = data.date;
    $('[data-modal-expires]', modal).textContent = data.expires;
    $('[data-modal-reason]', modal).textContent = data.reason;
    $('[data-modal-appeal]', modal).textContent = data.appeal_id;
    $('[data-modal-email]', modal).textContent = data.email;

    avatar.innerHTML = data.ipban || !data.skin
      ? '<div class="avatar-placeholder big" aria-hidden="true">!</div>'
      : `<img src="${escapeHtml(data.skin)}" alt="" width="64" height="64" loading="lazy" decoding="async">`;

    const actions = $('[data-modal-actions]', modal);
    const discord = `<a class="btn btn-secondary" href="${escapeHtml(data.discord)}" rel="noopener">Appeal on Discord</a>`;
    if (data.can_pay && data.unban_url) {
      actions.innerHTML = `<a class="btn btn-primary" href="${escapeHtml(data.unban_url)}" rel="noopener">${escapeHtml(data.action)} ${escapeHtml(data.price)}</a>${discord}`;
    } else if (data.temporary) {
      actions.innerHTML = `<button class="btn btn-muted" type="button" disabled>Wait It Out</button>${discord}`;
    } else {
      actions.innerHTML = discord;
    }

    modal.hidden = false;
    document.documentElement.classList.add('modal-open');
    const close = $('.ban-modal-close', modal);
    if (close) close.focus({ preventScroll: true });
  }

  function openBanInfoByIndex(index) {
    const ban = state.bans[Number(index)];
    if (ban) openBanInfo(ban);
  }

  function closeModal() {
    const modal = $('#banModal');
    if (modal) modal.hidden = true;
    document.documentElement.classList.remove('modal-open');
  }

  window.openBanInfo = openBanInfo;
  window.openBanInfoByIndex = openBanInfoByIndex;

  function setupSearch() {
    if (!els.form || !els.search) return;

    let debounce = null;
    els.form.addEventListener('submit', (event) => {
      event.preventDefault();
      loadBans({ page: 1, search: els.search.value });
    });

    els.search.addEventListener('input', () => {
      const value = els.search.value.trim();
      if (els.clear) els.clear.hidden = !value;
      window.clearTimeout(debounce);
      debounce = window.setTimeout(() => loadBans({ page: 1, search: value }), 260);
    });

    if (els.clear) {
      els.clear.addEventListener('click', () => {
        els.search.value = '';
        els.clear.hidden = true;
        loadBans({ page: 1, search: '' });
        els.search.focus();
      });
    }
  }

  function setupPagination() {
    if (els.prev) els.prev.addEventListener('click', () => loadBans({ page: Math.max(1, state.page - 1), search: state.search }));
    if (els.next) els.next.addEventListener('click', () => loadBans({ page: state.page + 1, search: state.search }));
  }

  function setupTableClicks() {
    if (!els.table) return;
    els.table.addEventListener('click', (event) => {
      const trigger = event.target.closest('[data-info-index], .info-btn, .js-info-button, .ban-type-pill');
      if (!trigger) return;
      const index = trigger.getAttribute('data-info-index');
      if (index !== null) openBanInfoByIndex(index);
    });
  }

  function setupMenu() {
    if (!els.menuToggle || !els.mobileMenu) return;
    els.menuToggle.addEventListener('click', () => {
      const open = els.menuToggle.getAttribute('aria-expanded') !== 'true';
      els.menuToggle.setAttribute('aria-expanded', String(open));
      els.menuToggle.classList.toggle('is-open', open);
      els.mobileMenu.hidden = !open;
      document.documentElement.classList.toggle('menu-open', open);
    });
  }

  function setupCopyIp() {
    $$('.js-copy-ip').forEach((button) => {
      button.addEventListener('click', async () => {
        const text = button.getAttribute('data-copy-text') || document.body.getAttribute('data-server-ip') || 'mineacle.net';
        try {
          if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
          } else {
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
          }
          showToast();
        } catch (_) {
          showToast('Copy failed', text);
        }
      });
    });
  }

  let toastTimer = null;
  function showToast(title = 'Server IP copied', subtitle = document.body.getAttribute('data-server-ip') || 'mineacle.net') {
    if (!els.toast) return;
    const strong = $('strong', els.toast);
    const span = $('span', els.toast);
    if (strong) strong.textContent = title;
    if (span) span.textContent = subtitle;
    els.toast.hidden = false;
    els.toast.classList.add('is-visible');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => {
      els.toast.classList.remove('is-visible');
      window.setTimeout(() => { els.toast.hidden = true; }, 180);
    }, 2600);
  }

  function setupHeaderScroll() {
    if (!els.header) return;
    const update = () => els.header.classList.toggle('is-scrolled', window.scrollY > 8);
    update();
    window.addEventListener('scroll', update, { passive: true });
  }

  async function loadStatus() {
    if (!els.serverStatus || !els.serverCount) return;
    try {
      const response = await fetch('api/server-status.php', { headers: { 'Accept': 'application/json' } });
      const json = await response.json();
      const players = Number(json.players ?? json.online_players ?? 0);
      els.serverCount.textContent = json.online ? `${players} Online` : 'Offline';
      if (els.serverDot) els.serverDot.classList.toggle('is-online', Boolean(json.online));
    } catch (_) {
      els.serverCount.textContent = 'Status';
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    setupSearch();
    setupPagination();
    setupTableClicks();
    setupMenu();
    setupCopyIp();
    setupHeaderScroll();
    loadBans({ page: 1, search: '' });
    loadStatus();
    window.setInterval(loadStatus, 30000);
  });
})();
