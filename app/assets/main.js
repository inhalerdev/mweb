const banList = document.getElementById("banList");
const banSearch = document.getElementById("banSearch");
const clearSearch = document.getElementById("clearSearch");
const banCount = document.getElementById("banCount");
const banModal = document.getElementById("banModal");
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

function actionButton(ban) {
  if (ban.ipban) {
    return `<button class="btn soft disabled" type="button" disabled>No Action</button>`;
  }

  if (ban.can_pay) {
    return `<a class="btn red" href="${escapeHtml(ban.unban_url)}">${escapeHtml(ban.price)} Unban</a>`;
  }

  return `<button class="btn soft" type="button" data-info="${escapeHtml(ban.id)}">View</button>`;
}

function renderPagination() {
  if (!banPagination || !prevPageButton || !nextPageButton || !pageInfo) return;

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

function renderBans(rows) {
  if (!banList) return;

  currentRows = Array.isArray(rows) ? rows : [];

  if (!currentRows.length) {
    banList.innerHTML = `
      <div class="empty compact-state">
        <strong>No matching active bans found</strong>
        <span>Check the username spelling or clear the search</span>
      </div>
    `;
    if (banCount) banCount.textContent = "0 shown";
    renderPagination();
    return;
  }

  banList.innerHTML = currentRows.map((ban) => `
    <article class="ban-row">
      <img class="ban-avatar" src="${escapeHtml(ban.skin)}" alt="${escapeHtml(ban.username)}">

      <div class="ban-player">
        <div class="ban-player-line">
          <span class="ban-name">${escapeHtml(ban.username)}</span>
          <button class="info-btn" type="button" data-info="${escapeHtml(ban.id)}" aria-label="View ${escapeHtml(ban.username)} ban info">i</button>
        </div>
        <span class="ban-date">${escapeHtml(ban.date)}</span>
      </div>

      <div class="ban-reason">
        ${escapeHtml(ban.reason)}
        <div class="ban-meta">${escapeHtml(ban.type)} • ${escapeHtml(ban.duration)}</div>
      </div>

      <div class="ban-status">
        <span class="badge ${escapeHtml(badgeClass(ban))}">${escapeHtml(ban.status)}</span>
      </div>

      <div class="ban-action">${actionButton(ban)}</div>
    </article>
  `).join("");

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
  } catch {
    throw new Error("Invalid API response");
  }
}

async function loadBans(page = currentPage) {
  if (!banList) return;

  currentPage = Math.max(1, page);

  const search = banSearch ? banSearch.value.trim() : "";
  const url = `api/bans.php?search=${encodeURIComponent(search)}&page=${encodeURIComponent(currentPage)}`;

  try {
    const response = await fetch(url, {
      headers: { "Accept": "application/json" },
      cache: "no-store"
    });

    const payload = await readJson(response);

    if (!response.ok || !payload.success) {
      throw new Error(payload.error || "Unable to load bans right now");
    }

    currentPagination = payload.pagination || currentPagination;
    currentPage = currentPagination.page || currentPage;
    renderBans(payload.bans || []);
  } catch (error) {
    console.error("Mineacle bans failed:", error);

    currentRows = [];
    currentPagination = {
      page: 1,
      per_page: 25,
      total: 0,
      total_pages: 1,
      has_prev: false,
      has_next: false
    };

    banList.innerHTML = `
      <div class="error compact-state">
        <strong>Unable to load bans right now</strong>
      </div>
    `;

    if (banCount) banCount.textContent = "0 shown";
    renderPagination();
  }
}

function updateClearButton() {
  if (!clearSearch || !banSearch) return;
  clearSearch.classList.toggle("show", banSearch.value.length > 0);
}

function openBanInfo(id) {
  const ban = currentRows.find((row) => String(row.id) === String(id));
  if (!ban || !banModal) return;

  document.getElementById("modalAvatar").src = ban.skin;
  document.getElementById("modalName").textContent = ban.username;
  document.getElementById("modalStatus").className = `badge ${badgeClass(ban)}`;
  document.getElementById("modalStatus").textContent = ban.status;
  document.getElementById("modalReason").textContent = ban.reason;
  document.getElementById("modalType").textContent = ban.type;
  document.getElementById("modalDuration").textContent = ban.duration;
  document.getElementById("modalDate").textContent = ban.date;
  document.getElementById("modalAppeal").textContent = ban.appeal_id;
  document.getElementById("modalEmail").textContent = ban.support_email;
  document.getElementById("modalDiscord").textContent = ban.discord;

  const actions = document.getElementById("modalActions");
  const note = document.getElementById("modalNote");

  if (ban.ipban) {
    actions.innerHTML = `<button class="btn soft disabled" disabled>Permanent IP Ban</button>`;
    note.textContent = "This is an IP ban. It has no public dispute or paid-unban option.";
  } else if (ban.can_pay) {
    actions.innerHTML = `
      <a class="btn red" href="${escapeHtml(ban.unban_url)}">${escapeHtml(ban.price)} Pay to be unbanned</a>
      <a class="btn soft" href="${escapeHtml(ban.discord)}" target="_blank" rel="noopener">Contact Discord</a>
    `;
    note.textContent = "Use the payment option for eligible bans, or contact support if you believe this punishment is incorrect.";
  } else {
    actions.innerHTML = `<a class="btn soft" href="${escapeHtml(ban.discord)}" target="_blank" rel="noopener">Contact Support</a>`;
    note.textContent = "This punishment is not currently eligible for paid unban. Contact support if you need more information.";
  }

  banModal.classList.add("show");
  banModal.setAttribute("aria-hidden", "false");
}

function closeModal() {
  if (!banModal) return;
  banModal.classList.remove("show");
  banModal.setAttribute("aria-hidden", "true");
}

document.addEventListener("click", (event) => {
  const infoButton = event.target.closest(".info-btn, [data-info]");
  if (infoButton && infoButton.dataset.info) {
    event.preventDefault();
    event.stopPropagation();
    openBanInfo(infoButton.dataset.info);
    return;
  }

  const modalClose = event.target.closest("[data-close-modal]");
  if (modalClose) {
    event.preventDefault();
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
    if (currentPagination.has_prev) loadBans(currentPagination.page - 1);
  });
}

if (nextPageButton) {
  nextPageButton.addEventListener("click", () => {
    if (currentPagination.has_next) loadBans(currentPagination.page + 1);
  });
}

document.querySelectorAll("[data-close-modal]").forEach((button) => {
  button.addEventListener("click", closeModal);
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") closeModal();
});

if (banModal) {
  banModal.addEventListener("click", (event) => {
    if (event.target === banModal) closeModal();
  });
}

loadBans(1);
