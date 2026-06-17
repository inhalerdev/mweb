(() => {
  "use strict";

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
  const byIdOrClass = (id, selector) => document.getElementById(id) || $(selector);

  const banList = byIdOrClass("banList", ".js-ban-table");
  const banSearchForm = byIdOrClass("banSearchForm", ".js-ban-search-form");
  const banSearch = byIdOrClass("banSearch", ".js-ban-search");
  const clearSearch = byIdOrClass("clearSearch", ".js-ban-clear");
  const banCount = byIdOrClass("banCount", ".js-ban-meta");
  const banPagination = byIdOrClass("banPagination", ".pagination-row");
  const prevPageButton = byIdOrClass("prevPage", ".js-ban-prev");
  const nextPageButton = byIdOrClass("nextPage", ".js-ban-next");
  const pageInfo = byIdOrClass("pageInfo", ".js-ban-page");

  const currentScript = document.currentScript || document.querySelector('script[src$="assets/main.js"], script[src$="/main.js"]');
  const assetRoot = currentScript ? new URL(".", currentScript.src) : new URL("assets/", window.location.href);

  function assetUrl(filename) {
    return new URL(filename, assetRoot).toString();
  }

  let currentPage = 1;
  let currentRows = [];
  let currentPagination = {
    page: 1,
    per_page: 25,
    total: 0,
    total_pages: 1,
    has_prev: false,
    has_next: false
  };

  function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, (char) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      "\"": "&quot;",
      "'": "&#039;"
    }[char]));
  }

  function normalizeBan(raw = {}) {
    const username = raw.username || raw.name || raw.player || "Unknown";
    const discord = raw.discord || raw.appeal_discord || "https://discord.gg/VwbwWftefM";
    const temporary = Boolean(raw.temporary || raw.temp || raw.type === "Temp Ban" || raw.status_type === "temp");
    const ipban = Boolean(raw.ipban || raw.ip_ban || raw.type === "IP Ban");
    const canPay = Boolean(raw.can_pay || raw.canPay || raw.action_type === "pay");

    return {
      ...raw,
      username,
      skin: raw.skin || raw.avatar || `https://minotar.net/avatar/${encodeURIComponent(username)}/64`,
      reason: raw.reason || "No reason provided",
      date: raw.date || raw.created_at || raw.created || "Unknown",
      duration: raw.duration || raw.remaining || raw.expires_in || (temporary ? "Temporary" : "Permanent"),
      expires: raw.expires || raw.expires_at || "",
      status: raw.status || (ipban ? "IP Ban" : temporary ? "Temporary Ban" : "Permanent Ban"),
      type: raw.type || (ipban ? "IP Ban" : temporary ? "Temp Ban" : "Perm Ban"),
      status_type: raw.status_type || (ipban ? "ip" : temporary ? "temp" : "perm"),
      appeal_id: raw.appeal_id || raw.id || raw.uuid || "MCL-000000",
      support_email: raw.support_email || raw.appeal_email || "support@mineacle.net",
      discord,
      appeal_discord: discord,
      unban_url: raw.unban_url || raw.pay_url || "https://store.mineacle.net",
      price: raw.price || "Pay",
      temporary,
      ipban,
      can_pay: canPay
    };
  }

  function statusLabel(ban) {
    if (ban.ipban) return "IP Ban";
    if (ban.temporary) return "Temp Ban";
    return "Perm Ban";
  }

  function renderPagination() {
    if (!banPagination || !prevPageButton || !nextPageButton || !pageInfo) return;

    const totalPages = Math.max(1, Number(currentPagination.total_pages || 1));
    const total = Number(currentPagination.total || 0);
    const perPage = Math.max(1, Number(currentPagination.per_page || 25));
    const shouldShow = total > perPage && totalPages > 1;

    banPagination.hidden = !shouldShow;
    banPagination.style.display = shouldShow ? "flex" : "none";
    pageInfo.textContent = shouldShow ? `Page ${currentPagination.page || 1} of ${totalPages}` : "";

    prevPageButton.disabled = !currentPagination.has_prev;
    nextPageButton.disabled = !currentPagination.has_next;
    prevPageButton.classList.toggle("disabled", !currentPagination.has_prev);
    nextPageButton.classList.toggle("disabled", !currentPagination.has_next);
  }

  function setCountText(text) {
    if (banCount) banCount.textContent = text;
  }

  function renderEmptyState() {
    if (!banList) return;

    currentRows = [];
    banList.innerHTML = `
      <div class="empty compact-state">
        <strong>No matching active bans found</strong>
        <span>Check the username spelling or clear the search</span>
      </div>
    `;
    setCountText("0 shown");
    renderPagination();
  }

  function renderLoadError() {
    if (!banList) return;

    currentRows = [];
    currentPagination = { page: 1, per_page: 25, total: 0, total_pages: 1, has_prev: false, has_next: false };
    banList.innerHTML = `
      <div class="error compact-state">
        <strong>Unable to load bans right now</strong>
      </div>
    `;
    setCountText("0 shown");
    renderPagination();
  }

  function actionButton(ban, index) {
    if (ban.ipban) return `<button class="btn soft disabled" type="button" disabled>IP Ban</button>`;
    if (ban.temporary || ban.action_type === "wait") return `<button class="btn soft disabled wait-btn" type="button" disabled>Wait It Out</button>`;
    if (ban.can_pay && ban.action_type !== "view") {
      return `<a class="btn red" href="${escapeHtml(ban.unban_url)}">${escapeHtml(ban.price)} Unban</a>`;
    }
    return `<button class="btn soft info-btn js-info-button" type="button" data-info-index="${index}">View</button>`;
  }

  function renderBans(rows) {
    if (!banList) return;

    currentRows = Array.isArray(rows) ? rows.map(normalizeBan) : [];
    window.mineacleCurrentBans = currentRows;

    if (!currentRows.length) {
      renderEmptyState();
      return;
    }

    banList.innerHTML = currentRows.map((ban, index) => `
      <article class="ban-row" data-ban-index="${index}">
        <img class="ban-avatar" src="${escapeHtml(ban.skin)}" alt="${escapeHtml(ban.username)}" loading="lazy">
        <div class="ban-player">
          <div class="ban-player-line">
            <strong class="ban-name">${escapeHtml(ban.username)}</strong>
            <button class="info-btn js-info-button" type="button" data-info-index="${index}" aria-label="View ${escapeHtml(ban.username)} ban details">i</button>
          </div>
          <span class="ban-date">${escapeHtml(ban.date)}</span>
        </div>
        <div class="ban-reason">${escapeHtml(ban.reason)}</div>
        <div class="ban-status">
          <span class="badge ${escapeHtml(ban.status_type)}">${escapeHtml(statusLabel(ban))}</span>
          <span class="ban-meta">${escapeHtml(ban.duration)}</span>
        </div>
        <div class="ban-action">${actionButton(ban, index)}</div>
      </article>
    `).join("");

    const total = Number(currentPagination.total || currentRows.length);
    const perPage = Number(currentPagination.per_page || 25);
    const page = Number(currentPagination.page || 1);
    const start = total === 0 ? 0 : ((page - 1) * perPage) + 1;
    const end = Math.min(page * perPage, total);
    setCountText(`${start}-${end} of ${total}`);
    renderPagination();
  }

  async function readJson(response) {
    const text = await response.text();
    try {
      return JSON.parse(text);
    } catch (error) {
      console.error("Mineacle bans API returned invalid JSON", error, text.slice(0, 300));
      return null;
    }
  }

  async function loadBans(page = currentPage) {
    if (!banList) return;

    currentPage = Math.max(1, Number(page) || 1);
    const search = banSearch ? banSearch.value.trim() : "";
    const url = `api/bans.php?search=${encodeURIComponent(search)}&page=${encodeURIComponent(currentPage)}`;

    try {
      const response = await fetch(url, { headers: { "Accept": "application/json" }, cache: "no-store" });
      const payload = await readJson(response);

      if (!response.ok || !payload || !payload.success) {
        console.error("Mineacle bans failed", { status: response.status, payload });
        renderLoadError();
        return;
      }

      currentPagination = payload.pagination || currentPagination;
      currentPage = Number(currentPagination.page || currentPage);
      renderBans(payload.bans || []);
    } catch (error) {
      console.error("Mineacle bans request failed", error);
      renderLoadError();
    }
  }

  function updateClearButton() {
    if (!clearSearch || !banSearch) return;
    const hasValue = banSearch.value.trim().length > 0;
    clearSearch.classList.toggle("show", hasValue);
    clearSearch.hidden = !hasValue;
    banSearch.classList.toggle("has-value", hasValue);
  }

  function createBanModal() {
    const existing = document.getElementById("banModal");
    if (existing) return existing;

    const modal = document.createElement("div");
    modal.className = "modal mineacle-js-modal";
    modal.id = "banModal";
    modal.setAttribute("aria-hidden", "true");
    modal.innerHTML = `
      <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalName">
        <div class="modal-head">
          <img id="modalAvatar" src="" alt="">
          <div class="modal-title">
            <h2 id="modalName">Player</h2>
            <span class="status-badge" id="modalStatus">Active Player Ban</span>
          </div>
          <button class="close-modal" type="button" data-close-modal aria-label="Close ban details">×</button>
        </div>
        <div class="detail-grid">
          <article class="detail"><span>Type</span><strong id="modalTypeBadge">Ban</strong></article>
          <article class="detail"><span>Reason</span><strong id="modalReason">No reason provided</strong></article>
          <article class="detail"><span>Duration</span><strong id="modalDuration">Unknown</strong></article>
          <article class="detail"><span>Date</span><strong id="modalDate">Unknown</strong></article>
          <article class="detail"><span>Appeal ID</span><strong id="modalAppeal">MCL-000000</strong></article>
          <article class="detail"><span>Support Email</span><strong id="modalEmail">support@mineacle.net</strong></article>
          <article class="detail"><span>Discord</span><strong id="modalDiscord">discord.gg/4xrYFxdSWg</strong></article>
        </div>
        <div class="modal-actions" id="modalActions"></div>
        <p class="modal-note" id="modalNote">Use the payment option for eligible bans, or contact support if you believe this punishment is incorrect.</p>
      </div>
    `;
    document.body.appendChild(modal);

    const fallbackAvatar = modal.querySelector("#modalAvatar");
    if (fallbackAvatar) {
      fallbackAvatar.src = assetUrl("mineacle-square-logo.png");
      fallbackAvatar.alt = "Mineacle";
    }

    return modal;
  }

  function safeText(id, value) {
    const element = document.getElementById(id);
    if (element) element.textContent = value ?? "";
  }

  function openBanInfoByIndex(index) {
    const numericIndex = Number(index);
    if (!Number.isInteger(numericIndex) || numericIndex < 0) return;
    const ban = currentRows[numericIndex];
    if (!ban) return;
    openBanInfo(ban);
  }

  function openBanInfo(rawBan) {
    const ban = normalizeBan(rawBan);
    const modal = createBanModal();
    const avatar = document.getElementById("modalAvatar");

    if (avatar) {
      avatar.src = ban.skin;
      avatar.alt = ban.username;
    }

    safeText("modalName", ban.username);
    safeText("modalStatus", ban.status);
    safeText("modalTypeBadge", ban.type);
    safeText("modalReason", ban.reason);
    safeText("modalDuration", ban.duration);
    safeText("modalDate", ban.date);
    safeText("modalAppeal", ban.appeal_id);
    safeText("modalEmail", ban.support_email);
    safeText("modalDiscord", ban.discord);

    const status = document.getElementById("modalStatus");
    if (status) status.className = `status-badge ${ban.status_type}`;

    const actions = document.getElementById("modalActions");
    const note = document.getElementById("modalNote");

    if (actions && note) {
      if (ban.ipban) {
        actions.innerHTML = `<button class="btn soft disabled" type="button" disabled>Permanent IP Ban</button>`;
        note.textContent = "This is an IP ban. It has no public dispute or paid-unban option.";
      } else if (ban.temporary) {
        actions.innerHTML = `<button class="btn soft disabled wait-btn" type="button" disabled>Wait It Out</button>`;
        note.textContent = `Temporary bans cannot be paid for. This punishment expires on ${ban.expires || "the listed expiration date"}.`;
      } else if (ban.can_pay) {
        actions.innerHTML = `
          <a class="btn red" href="${escapeHtml(ban.unban_url)}">${escapeHtml(ban.price)} Pay to be unbanned</a>
          <a class="btn soft" href="${escapeHtml(ban.discord)}" target="_blank" rel="noopener">Contact Discord</a>
        `;
        note.textContent = "Use the payment option for eligible permanent bans, or contact support if you believe this punishment is incorrect.";
      } else {
        actions.innerHTML = `<a class="btn soft" href="${escapeHtml(ban.discord)}" target="_blank" rel="noopener">Contact Support</a>`;
        note.textContent = "This punishment is not currently eligible for paid unban. Contact support if you need more information.";
      }
    }

    modal.classList.add("show");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
  }

  function closeModal() {
    const modal = document.getElementById("banModal");
    if (!modal) return;
    modal.classList.remove("show");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
  }

  function bindBanUi() {
    if (banSearchForm) {
      banSearchForm.addEventListener("submit", (event) => {
        event.preventDefault();
        currentPage = 1;
        updateClearButton();
        loadBans(1);
      });
    }

    if (banSearch) {
      let timer = null;
      banSearch.addEventListener("input", () => {
        window.clearTimeout(timer);
        updateClearButton();
        timer = window.setTimeout(() => loadBans(1), 180);
      });
      banSearch.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
          banSearch.value = "";
          updateClearButton();
          loadBans(1);
        }
      });
      updateClearButton();
    }

    if (clearSearch && banSearch) {
      clearSearch.addEventListener("click", () => {
        banSearch.value = "";
        banSearch.focus();
        updateClearButton();
        loadBans(1);
      });
    }

    if (prevPageButton) {
      prevPageButton.addEventListener("click", () => {
        if (currentPagination.has_prev) loadBans(Number(currentPagination.page || 1) - 1);
      });
    }

    if (nextPageButton) {
      nextPageButton.addEventListener("click", () => {
        if (currentPagination.has_next) loadBans(Number(currentPagination.page || 1) + 1);
      });
    }

    document.addEventListener("click", (event) => {
      const infoButton = event.target.closest(".info-btn, .js-info-button, [data-info-index]");
      if (infoButton) {
        event.preventDefault();
        openBanInfoByIndex(infoButton.dataset.infoIndex);
        return;
      }

      if (event.target.closest("[data-close-modal]")) {
        event.preventDefault();
        closeModal();
        return;
      }

      const modal = document.getElementById("banModal");
      if (modal && event.target === modal) closeModal();
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") closeModal();
    });
  }

  function showCopyToast(ip) {
    const toast = document.getElementById("toast");
    const toastValue = document.getElementById("toastValue");
    if (!toast) return;

    if (toastValue) toastValue.textContent = ip;
    toast.classList.add("show", "center-popup");
    window.clearTimeout(window.mineacleToastTimer);
    window.mineacleToastTimer = window.setTimeout(() => toast.classList.remove("show", "center-popup"), 2300);
  }

  function bindCopyIpButtons() {
    $$('[data-copy-ip]').forEach((button) => {
      button.addEventListener("click", async () => {
        const ip = button.dataset.copyIp || button.getAttribute("data-copy-ip") || "mineacle.net";
        try {
          if (navigator.clipboard && navigator.clipboard.writeText) await navigator.clipboard.writeText(ip);
        } catch (error) {
          console.error("Mineacle copy IP failed", error);
        }
        button.classList.add("copied");
        showCopyToast(ip);
        window.setTimeout(() => button.classList.remove("copied"), 1400);
      });
    });
  }

  function bindMobileNavigation() {
    const header = document.getElementById("siteHeader");
    const toggle = header ? $(".mobile-nav-toggle", header) : null;
    const menu = document.getElementById("mainNav");

    if (!header || !toggle || !menu) return;

    const setOpen = (open) => {
      header.classList.toggle("mobile-open", open);
      document.documentElement.classList.toggle("mineacle-mobile-menu-open", open);
      document.body.classList.toggle("mobile-nav-open", open);
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      toggle.setAttribute("aria-label", open ? "Close navigation" : "Open navigation");
      menu.setAttribute("aria-hidden", open ? "false" : "true");
    };

    toggle.addEventListener("pointerdown", () => {
      toggle.dataset.nextOpen = header.classList.contains("mobile-open") ? "false" : "true";
    }, { capture: true });

    toggle.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();
      const nextOpen = toggle.dataset.nextOpen === "true";
      setOpen(nextOpen);
    }, { capture: true });

    $$('a, button', menu).forEach((item) => {
      item.addEventListener("click", () => setOpen(false));
    });

    document.addEventListener("click", (event) => {
      if (!header.contains(event.target)) setOpen(false);
    }, { capture: true });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") setOpen(false);
    }, { capture: true });

    window.addEventListener("resize", () => {
      if (window.innerWidth > 980) setOpen(false);
    });

    setOpen(false);
  }

  function floatingHeaderScroll() {
    const header = document.getElementById("siteHeader");
    if (!header) return;

    const update = () => header.classList.toggle("is-scrolled", window.scrollY > 28);
    update();
    window.addEventListener("scroll", update, { passive: true });
  }

  function init() {
    createBanModal();
    bindBanUi();
    bindCopyIpButtons();
    bindMobileNavigation();
    floatingHeaderScroll();
    loadBans(1);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init, { once: true });
  } else {
    init();
  }

  window.mineacleOpenBanInfoByIndex = openBanInfoByIndex;
  window.mineacleOpenBanInfo = openBanInfo;
  window.mineacleCloseBanInfo = closeModal;
})();
