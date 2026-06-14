document.querySelectorAll("img[data-fallback]").forEach((image) => {
  image.addEventListener("error", () => {
    if (image.dataset.fallbackUsed === "true") return;
    image.dataset.fallbackUsed = "true";
    image.src = image.dataset.fallback;
  });
});

const toast = document.getElementById("toast");
const toastValue = document.getElementById("toastValue");
const banList = document.getElementById("banList");
const banSearch = document.getElementById("banSearch");
const banCount = document.getElementById("banCount");
const banModal = document.getElementById("banModal");
const banPagination = document.getElementById("banPagination");
const prevPageButton = document.getElementById("prevPage");
const nextPageButton = document.getElementById("nextPage");
const pageInfo = document.getElementById("pageInfo");

let currentPage = 1;
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
    '"': "&quot;",
    "'": "&#039;"
  }[char]));
}

function showToast(serverIp) {
  if (!toast) return;
  if (toastValue) toastValue.textContent = serverIp;
  toast.classList.remove("show");
  void toast.offsetWidth;
  toast.classList.add("show");
  clearTimeout(showToast.timer);
  showToast.timer = setTimeout(() => toast.classList.remove("show"), 3200);
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

function renderBans(rows) {
  if (!banList) return;

  if (!rows.length) {
    banList.innerHTML = `
      <div class="empty">
        <strong>No active bans found</strong>
        <span>Try another username or check again later</span>
      </div>
    `;
    if (banCount) banCount.textContent = "0 shown";
    renderPagination();
    return;
  }

  banList.innerHTML = rows.map(ban => `
    <article class="ban-row">
      <img class="ban-avatar" src="${escapeHtml(ban.skin)}" alt="${escapeHtml(ban.username)}">
      <div class="ban-player">
        <div class="ban-player-line">
          <span class="ban-name">${escapeHtml(ban.username)}</span>
          <button class="info-btn" type="button" data-info="${escapeHtml(ban.id)}" aria-label="View ${escapeHtml(ban.username)} ban info">
            i
          </button>
        </div>
        <span class="ban-date">${escapeHtml(ban.date)}</span>
      </div>
      <div class="ban-reason">
        ${escapeHtml(ban.reason)}
        <div class="ban-meta">${escapeHtml(ban.type)} • ${escapeHtml(ban.duration)}</div>
      </div>
      <div class="ban-status">
        <span class="badge ${escapeHtml(badgeClass(ban))}">${ban.ipban ? '' : ""}${escapeHtml(ban.status)}</span>
      </div>
      <div class="ban-action">${actionButton(ban)}</div>
    </article>
  `).join("");

  if (banCount) {
    const start = currentPagination.total === 0 ? 0 : ((currentPagination.page - 1) * currentPagination.per_page) + 1;
    const end = Math.min(currentPagination.page * currentPagination.per_page, currentPagination.total);
    banCount.textContent = `${start}-${end} of ${currentPagination.total}`;
  }

  document.querySelectorAll("[data-info]").forEach(button => {
    button.addEventListener("click", () => openBanInfo(button.dataset.info, rows));
  });

  renderPagination();
}

function renderPagination() {
  if (!banPagination || !prevPageButton || !nextPageButton || !pageInfo) return;

  const shouldShow = currentPagination.total_pages > 1;
  banPagination.hidden = !shouldShow;

  pageInfo.textContent = `Page ${currentPagination.page} of ${currentPagination.total_pages}`;
  prevPageButton.disabled = !currentPagination.has_prev;
  nextPageButton.disabled = !currentPagination.has_next;

  prevPageButton.classList.toggle("disabled", !currentPagination.has_prev);
  nextPageButton.classList.toggle("disabled", !currentPagination.has_next);
}

async function loadBans(page = currentPage) {
  if (!banList) return;

  currentPage = Math.max(1, page);

  const search = banSearch ? banSearch.value.trim() : "";
  const url = `api/bans.php?search=${encodeURIComponent(search)}&page=${encodeURIComponent(currentPage)}`;

  try {
    const response = await fetch(url, { headers: { "Accept": "application/json" } });
    const payload = await response.json();

    if (!payload.success) {
      banList.innerHTML = `<div class="error">${escapeHtml(payload.error || "Unable to load bans")}</div>`;
      if (banCount) banCount.textContent = "0 shown";
      currentPagination = { page: 1, per_page: 25, total: 0, total_pages: 1, has_prev: false, has_next: false };
      renderPagination();
      return;
    }

    window.mineacleBans = payload.bans || [];
    currentPagination = payload.pagination || currentPagination;
    currentPage = currentPagination.page || currentPage;
    renderBans(window.mineacleBans);
  } catch (error) {
    banList.innerHTML = `<div class="error">Unable to load bans right now</div>`;
    if (banCount) banCount.textContent = "0 shown";
    currentPagination = { page: 1, per_page: 25, total: 0, total_pages: 1, has_prev: false, has_next: false };
    renderPagination();
  }
}

function openBanInfo(id, rows = window.mineacleBans || []) {
  const ban = rows.find(row => String(row.id) === String(id));
  if (!ban || !banModal) return;

  document.getElementById("modalAvatar").src = ban.skin;
  document.getElementById("modalName").textContent = ban.username;
  document.getElementById("modalStatus").className = `badge ${badgeClass(ban)}`;
  document.getElementById("modalStatus").innerHTML = `${ban.ipban ? '' : ""}${escapeHtml(ban.status)}`;

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
    note.textContent = "This is an IP ban. It is marked permanently banned and has no public dispute or paid-unban option.";
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
}

function closeModal() {
  if (banModal) banModal.classList.remove("show");
}

document.querySelectorAll(".copy-ip").forEach(button => {
  button.addEventListener("click", async () => {
    const value = button.dataset.copy || "mineacle.net";
    try {
      await navigator.clipboard.writeText(value);
    } catch {}
    showToast(value);
  });
});

if (banSearch) {
  let timer = null;
  banSearch.addEventListener("input", () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      currentPage = 1;
      loadBans(1);
    }, 180);
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

document.querySelectorAll("[data-close-modal]").forEach(button => {
  button.addEventListener("click", closeModal);
});

document.addEventListener("keydown", event => {
  if (event.key === "Escape") closeModal();
});

if (banModal) {
  banModal.addEventListener("click", event => {
    if (event.target === banModal) closeModal();
  });
}

loadBans(1);
