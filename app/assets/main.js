const banList = document.getElementById("banList");
const banSearch = document.getElementById("banSearch");
const clearSearch = document.getElementById("clearSearch");
const banCount = document.getElementById("banCount");
const banPagination = document.getElementById("banPagination");
const prevPageButton = document.getElementById("prevPage");
const nextPageButton = document.getElementById("nextPage");
const pageInfo = document.getElementById("pageInfo");

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

function badgeClass(ban) {
  return ban.status_type || "active";
}

function statusLabel(ban) {
  if (ban.ipban) {
    return "IP Ban";
  }

  if (ban.temporary) {
    return "Temp Ban";
  }

  return "Perm Ban";
}

function typePillClass(ban) {
  if (ban.ipban) {
    return "ip";
  }

  if (ban.temporary) {
    return "temp";
  }

  return "perm";
}

function removeExistingModal() {
  document.querySelectorAll("#banModal").forEach((modal) => modal.remove());
}

function createBanModal() {
  removeExistingModal();

  const modal = document.createElement("div");
  modal.className = "modal mineacle-js-modal";
  modal.id = "banModal";
  modal.setAttribute("aria-hidden", "true");

  modal.innerHTML = `
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalName">
      <button class="modal-close" type="button" data-close-modal aria-label="Close">×</button>

      <div class="modal-hero">
        <div class="modal-avatar-wrap">
          <img id="modalAvatar" src="" alt="">
        </div>

        <div class="modal-title">
          <span class="eyebrow">Ban Details</span>
          <h2 id="modalName">Player</h2>
          <div class="modal-badges">
            <span id="modalStatus" class="status-badge">Active</span>
            <span id="modalTypeBadge" class="type-badge">Player Ban</span>
          </div>
        </div>
      </div>

      <div class="detail-grid">
        <article class="detail-card reason-card">
          <span>Reason</span>
          <strong id="modalReason">No reason provided</strong>
        </article>

        <article class="detail-card">
          <span>Duration</span>
          <strong id="modalDuration">Unknown</strong>
        </article>

        <article class="detail-card">
          <span>Date</span>
          <strong id="modalDate">Unknown</strong>
        </article>

        <article class="detail-card">
          <span>Appeal ID</span>
          <strong id="modalAppeal">MCL-000000</strong>
        </article>

        <article class="detail-card">
          <span>Support Email</span>
          <strong id="modalEmail">support@mineacle.net</strong>
        </article>

        <article class="detail-card">
          <span>Discord</span>
          <strong id="modalDiscord">discord.gg/4xrYFxdSWg</strong>
        </article>
      </div>

      <div class="modal-actions" id="modalActions"></div>

      <p class="modal-note" id="modalNote">
        Use the payment option for eligible bans, or contact support if you believe this punishment is incorrect.
      </p>
    </div>
  `;

  document.body.appendChild(modal);
  return modal;
}

function getBanModal() {
  return document.getElementById("banModal") || createBanModal();
}


function buildAppealMailto(ban) {
  const email = ban.appeal_email || "support@mineacle.net";
  const name = ban.name || "Unknown";
  const reason = ban.reason || "Not listed";
  const subject = encodeURIComponent(`Mineacle Ban Appeal - ${name}`);
  const body = encodeURIComponent(
    `Minecraft username: ${name}\nPunishment ID: ${ban.id || "Unknown"}\nReason: ${reason}\n\nExplain your appeal here:\n`
  );

  return `mailto:${email}?subject=${subject}&body=${body}`;
}

function appealButtonsHtml(ban) {
  const discord = ban.appeal_discord || "https://discord.gg/VwbwWftefM";

  return `
    <div class="appeal-button-row">
      <a class="btn red appeal-contact-btn" href="${buildAppealMailto(ban)}">Email Appeal</a>
      <a class="btn soft appeal-contact-btn" href="${escapeHtml(discord)}" target="_blank" rel="noopener">Discord Appeal</a>
    </div>
  `;
}

function actionButton(ban, index) {
  if (ban.ipban) {
    return "";
  }

  if (ban.temporary || ban.action_type === "wait") {
    return `<button class="btn soft disabled wait-btn compact-action" type="button" disabled>Wait It Out</button>`;
  }

  if (ban.can_pay && ban.action_type === "pay") {
    return `<a class="btn red compact-action" href="${escapeHtml(ban.unban_url)}">${escapeHtml(ban.price)} Unban</a>`;
  }

  return `<button class="btn soft js-info-button compact-action" type="button" data-info-index="${index}">View</button>`;
}

function resetPagination() {
  currentPagination = {
    page: 1,
    per_page: 25,
    total: 0,
    total_pages: 1,
    has_prev: false,
    has_next: false
  };
}

function renderPagination() {
  if (!banPagination || !prevPageButton || !nextPageButton || !pageInfo) {
    return;
  }

  const totalPages = Number(currentPagination.total_pages || 1);
  const shouldShow = totalPages > 1;

  banPagination.hidden = !shouldShow;
  banPagination.style.display = shouldShow ? "flex" : "none";

  pageInfo.textContent = `Page ${currentPagination.page || 1} of ${totalPages}`;
  prevPageButton.disabled = !currentPagination.has_prev;
  nextPageButton.disabled = !currentPagination.has_next;
  prevPageButton.classList.toggle("disabled", !currentPagination.has_prev);
  nextPageButton.classList.toggle("disabled", !currentPagination.has_next);
}

function renderEmptyState() {
  if (!banList) {
    return;
  }

  banList.innerHTML = `
    <div class="empty compact-state">
      <strong>No matching active bans found</strong>
      <span>Check the username spelling or clear the search</span>
    </div>
  `;

  if (banCount) {
    banCount.textContent = "0 shown";
  }

  renderPagination();
}

function renderLoadError() {
  if (!banList) {
    return;
  }

  currentRows = [];
  resetPagination();

  banList.innerHTML = `
    <div class="error compact-state">
      <strong>Unable to load bans right now</strong>
    </div>
  `;

  if (banCount) {
    banCount.textContent = "0 shown";
  }

  renderPagination();
}

function bindInfoButtons() {
  document.querySelectorAll(".info-btn, .js-info-button, [data-info-index]").forEach((button) => {
    button.onpointerdown = (event) => {
      event.preventDefault();
      event.stopPropagation();
      openBanInfoByIndex(button.dataset.infoIndex);
    };

    button.onclick = (event) => {
      event.preventDefault();
      event.stopPropagation();
      openBanInfoByIndex(button.dataset.infoIndex);
      return false;
    };
  });
}

function renderBans(rows) {
  if (!banList) {
    return;
  }

  currentRows = Array.isArray(rows) ? rows : [];
  window.mineacleCurrentBans = currentRows;

  if (!currentRows.length) {
    renderEmptyState();
    return;
  }

  banList.innerHTML = currentRows.map((ban, index) => `
    <article class="ban-row" data-ban-index="${index}">
      <img class="ban-avatar" src="${escapeHtml(ban.skin)}" alt="${escapeHtml(ban.username)}">

      <div class="ban-player">
        <div class="ban-player-line">
          <span class="ban-name">${escapeHtml(ban.username)}</span>
          <button
            class="info-btn js-info-button"
            type="button"
            data-info-index="${index}"
            aria-label="View ${escapeHtml(ban.username)} ban details"
          >i</button>
        </div>
        <span class="ban-date">${escapeHtml(ban.date)}</span>
      </div>

      <div class="ban-reason">
        <strong class="ban-reason-title">${escapeHtml(ban.reason)}</strong>
        <div class="ban-meta">
          <span class="ban-mini-pill ${escapeHtml(typePillClass(ban))}">${escapeHtml(statusLabel(ban))}</span>
          <span class="ban-mini-pill duration">${escapeHtml(ban.duration)}</span>
        </div>
      </div>

      <div class="ban-action">${actionButton(ban, index)}</div>
    </article>
  `).join("");

  bindInfoButtons();

  if (banCount) {
    const start = currentPagination.total === 0 ? 0 : ((currentPagination.page - 1) * currentPagination.per_page) + 1;
    const end = Math.min(currentPagination.page * currentPagination.per_page, currentPagination.total);
    banCount.textContent = `${start}-${end} of ${currentPagination.total}`;
  }

  renderPagination();
}

async function readJson(response) {
  const text = await response.text();

  try {
    return JSON.parse(text);
  } catch (error) {
    console.error("Mineacle bans API returned invalid JSON", error);
    return null;
  }
}

async function loadBans(page = currentPage) {
  if (!banList) {
    return;
  }

  currentPage = Math.max(1, page);

  const search = banSearch ? banSearch.value.trim() : "";
  const url = `api/bans.php?search=${encodeURIComponent(search)}&page=${encodeURIComponent(currentPage)}`;

  let response;

  try {
    response = await fetch(url, {
      headers: { "Accept": "application/json" },
      cache: "no-store"
    });
  } catch (error) {
    console.error("Mineacle bans request failed", error);
    renderLoadError();
    return;
  }

  const payload = await readJson(response);

  if (!response.ok || !payload || !payload.success) {
    console.error("Mineacle bans failed", {
      status: response.status,
      payload
    });
    renderLoadError();
    return;
  }

  currentPagination = payload.pagination || currentPagination;
  currentPage = currentPagination.page || currentPage;
  renderBans(payload.bans || []);
}

function updateClearButton() {
  if (!clearSearch || !banSearch) {
    return;
  }

  clearSearch.classList.toggle("show", banSearch.value.length > 0);
}

function safeText(id, value) {
  const element = document.getElementById(id);
  if (element) {
    element.textContent = value ?? "";
  }
}

function openBanInfoByIndex(index) {
  const numericIndex = Number(index);

  if (!Number.isInteger(numericIndex) || numericIndex < 0) {
    console.warn("Mineacle modal blocked: invalid index", index);
    return;
  }

  const ban = currentRows[numericIndex];

  if (!ban) {
    console.warn("Mineacle modal blocked: no ban row for index", numericIndex, currentRows);
    return;
  }

  openBanInfo(ban);
}

function openBanInfo(ban) {
  if (!ban) {
    return;
  }

  const modal = getBanModal();

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
  if (status) {
    status.className = `status-badge ${badgeClass(ban)}`;
  }

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
  modal.style.display = "grid";
  modal.style.pointerEvents = "auto";
  document.body.classList.add("modal-open");
}

function closeModal() {
  const modal = document.getElementById("banModal");
  if (!modal) {
    return;
  }

  modal.classList.remove("show");
  modal.setAttribute("aria-hidden", "true");
  modal.style.display = "none";
  modal.style.pointerEvents = "none";
  document.body.classList.remove("modal-open");
}

function captureInfoClick(event) {
  const infoButton = event.target.closest(".info-btn, .js-info-button, [data-info-index]");

  if (!infoButton) {
    return;
  }

  event.preventDefault();
  event.stopPropagation();
  openBanInfoByIndex(infoButton.dataset.infoIndex);
}

window.mineacleOpenBanInfoByIndex = openBanInfoByIndex;
window.mineacleOpenBanInfo = openBanInfo;
window.mineacleCloseBanInfo = closeModal;

document.addEventListener("pointerdown", captureInfoClick, true);
document.addEventListener("click", (event) => {
  const modalClose = event.target.closest("[data-close-modal]");
  if (modalClose) {
    event.preventDefault();
    closeModal();
    return;
  }

  captureInfoClick(event);
}, true);

document.addEventListener("click", (event) => {
  const modal = document.getElementById("banModal");
  if (modal && event.target === modal) {
    closeModal();
  }
});

if (banSearch) {
  let timer = null;

  const runSearch = () => {
    currentPage = 1;
    updateClearButton();
    loadBans(1);
  };

  banSearch.addEventListener("input", () => {
    clearTimeout(timer);
    timer = setTimeout(runSearch, 180);
    updateClearButton();
  });

  banSearch.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
      event.preventDefault();
      clearTimeout(timer);
      runSearch();
    }

    if (event.key === "Escape") {
      banSearch.value = "";
      clearTimeout(timer);
      runSearch();
    }
  });

  banSearch.addEventListener("search", () => {
    clearTimeout(timer);
    runSearch();
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
    if (currentPagination.has_prev) {
      loadBans(currentPagination.page - 1);
    }
  });
}

if (nextPageButton) {
  nextPageButton.addEventListener("click", () => {
    if (currentPagination.has_next) {
      loadBans(currentPagination.page + 1);
    }
  });
}

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    closeModal();
  }
});



function showCopyToast(ip) {
  const toast = document.getElementById("toast");
  const toastValue = document.getElementById("toastValue");

  if (!toast) {
    return;
  }

  if (toastValue) {
    toastValue.textContent = ip;
  }

  toast.classList.add("show", "center-popup");
  clearTimeout(window.mineacleToastTimer);
  window.mineacleToastTimer = setTimeout(() => {
    toast.classList.remove("show", "center-popup");
  }, 2200);
}

function bindCopyIpButtons() {
  document.querySelectorAll("[data-copy-ip]").forEach((button) => {
    button.addEventListener("click", async () => {
      const ip = button.dataset.copyIp || "mineacle.net";

      try {
        await navigator.clipboard.writeText(ip);
        button.classList.add("copied");
        showCopyToast(ip);
        setTimeout(() => button.classList.remove("copied"), 1400);
      } catch (error) {
        console.error("Mineacle copy IP failed", error);
        showCopyToast(ip);
      }
    });
  });
}

bindCopyIpButtons();



function formatCompactNumber(value) {
  const number = Number(value);

  if (!Number.isFinite(number) || number < 0) {
    return "Unavailable";
  }

  return new Intl.NumberFormat("en-US", {
    notation: number >= 10000 ? "compact" : "standard",
    maximumFractionDigits: 1
  }).format(number);
}

function formatDiscordMemberCount(value) {
  const number = Number(value);

  if (!Number.isFinite(number) || number < 0) {
    return "Unavailable";
  }

  return number >= 100 ? "100+" : number.toLocaleString("en-US");
}

async function updateDiscordCounts() {
  const memberTarget = document.querySelector("[data-discord-member-count]");
  const onlineTarget = document.querySelector("[data-discord-online-count]");
  const discordCard = document.querySelector("[data-discord-card]");

  if (!memberTarget && !onlineTarget) {
    return;
  }

  const fallback = () => {
    if (memberTarget) {
      memberTarget.textContent = "Join";
      memberTarget.title = "Discord member count unavailable";
    }

    if (onlineTarget) {
      onlineTarget.textContent = "Live";
      onlineTarget.title = "Discord online count unavailable";
    }

    if (discordCard) {
      discordCard.classList.add("discord-unavailable");
      discordCard.classList.remove("discord-live");
    }
  };

  try {
    const response = await fetch("api/discord.php", {
      headers: { "Accept": "application/json" },
      cache: "no-store"
    });

    const text = await response.text();
    let payload = null;

    try {
      payload = JSON.parse(text);
    } catch {
      fallback();
      return;
    }

    if (!response.ok || !payload || !payload.success) {
      fallback();
      return;
    }

    if (memberTarget) {
      const members = Number(payload.member_count);
      memberTarget.textContent = formatDiscordMemberCount(members);
      memberTarget.title = `${members.toLocaleString("en-US")} members`;
    }

    if (onlineTarget) {
      if (payload.online_count === null || payload.online_count === undefined) {
        onlineTarget.textContent = "Live";
        onlineTarget.title = "Discord online count unavailable";
      } else {
        const online = Number(payload.online_count);
        onlineTarget.textContent = formatCompactNumber(online);
        onlineTarget.title = `${online.toLocaleString("en-US")} online`;
      }
    }

    if (discordCard) {
      discordCard.classList.add("discord-live");
      discordCard.classList.remove("discord-unavailable");
    }
  } catch {
    fallback();
  }
}

updateDiscordCounts();
setInterval(updateDiscordCounts, 300000);



function initMobileNavigation() {
  const header = document.getElementById("siteHeader");
  const toggle = document.querySelector(".mobile-nav-toggle");
  const nav = document.getElementById("mainNav");

  if (!header || !toggle || !nav) {
    return;
  }

  const closeMenu = () => {
    header.classList.remove("mobile-open");
    document.body.classList.remove("mobile-nav-open");
    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-label", "Open navigation");
  };

  const openMenu = () => {
    header.classList.add("mobile-open");
    document.body.classList.add("mobile-nav-open");
    toggle.setAttribute("aria-expanded", "true");
    toggle.setAttribute("aria-label", "Close navigation");
  };

  toggle.addEventListener("click", () => {
    if (header.classList.contains("mobile-open")) {
      closeMenu();
    } else {
      openMenu();
    }
  });

  nav.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", closeMenu);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeMenu();
    }
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth > 980) {
      closeMenu();
    }
  });
}

initMobileNavigation();

createBanModal();
loadBans(1);


function mineacleFloatingHeaderScroll() {
  const header = document.getElementById("siteHeader");
  if (!header) {
    return;
  }

  const update = () => {
    header.classList.toggle("is-scrolled", window.scrollY > 28);
  };

  update();
  window.addEventListener("scroll", update, { passive: true });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", mineacleFloatingHeaderScroll);
} else {
  mineacleFloatingHeaderScroll();
}


function mineacleNavClickFallback() {
  const nav = document.getElementById("siteHeader");
  if (!nav) {
    return;
  }

  nav.addEventListener("click", (event) => {
    const link = event.target.closest("a.nav-text-link, a.nav-store-button, a.header-discord-button, a.header-bans-logo");
    if (link && link.href) {
      return;
    }

    const play = event.target.closest("button.header-play-button");
    if (play) {
      const ip = play.getAttribute("data-copy-ip") || "mineacle.net";
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(ip).catch(() => {});
      }
    }
  }, true);
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", mineacleNavClickFallback);
} else {
  mineacleNavClickFallback();
}


function mineacleHardNavLinkFix() {
  const header = document.getElementById("siteHeader");
  if (!header) {
    return;
  }

  header.addEventListener("click", (event) => {
    const navLink = event.target.closest(".blocaria-nav-left a[data-nav-href], .blocaria-nav-left a[href]");
    if (navLink) {
      const target = navLink.getAttribute("data-nav-href") || navLink.getAttribute("href");
      if (target) {
        event.preventDefault();
        event.stopPropagation();
        window.location.href = target;
      }
      return;
    }

    const rightLink = event.target.closest(".blocaria-nav-right a[href]");
    if (rightLink && rightLink.href) {
      return;
    }

    const playButton = event.target.closest(".header-play-button");
    if (playButton) {
      const ip = playButton.getAttribute("data-copy-ip") || "mineacle.net";
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(ip).catch(() => {});
      }
    }
  }, true);
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", mineacleHardNavLinkFix);
} else {
  mineacleHardNavLinkFix();
}
