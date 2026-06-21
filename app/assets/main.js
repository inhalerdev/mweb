(() => {
    "use strict";

    const MODAL_ID = "banModal";
    const DISCORD_FALLBACK = "https://discord.gg/VwbwWftefM";
    const SUPPORT_FALLBACK = "support@mineacle.net";
    const LOGO_FALLBACK = "mineacle-square-logo.png";
    const MASCOT_FALLBACK = "appeal-wumpus.webp";

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

    const currentScript = document.currentScript || document.querySelector('script[src$="assets/main.js"], script[src*="/main.js"]');
    const assetRoot = currentScript ? new URL(".", currentScript.src) : new URL("assets/", window.location.href);

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
    let discordOnlineMembersText = "Members Online";

    function assetUrl(filename) {
        return new URL(filename, assetRoot).toString();
    }

    function escapeHtml(value) {
        return String(value ?? "").replace(/[&<>"']/g, (char) => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            "\"": "&quot;",
            "'": "&#039;"
        }[char]));
    }

    function safeUrl(value, fallback = "#") {
        const raw = String(value || "").trim();
        if (!raw) return fallback;
        if (/^(https?:)?\/\//i.test(raw) || raw.startsWith("mailto:")) return raw;
        return fallback;
    }

    function isTemporary(raw = {}) {
        return Boolean(raw.temporary || raw.temp || raw.type === "Temp Ban" || raw.type === "Temporary Ban" || raw.status_type === "temp");
    }

    function isIpBan(raw = {}) {
        return Boolean(raw.ipban || raw.ip_ban || raw.type === "IP Ban" || raw.status_type === "ip");
    }

    function normalizeBan(raw = {}) {
        const username = raw.username || raw.name || raw.player || "Unknown";
        const temporary = isTemporary(raw);
        const ipban = isIpBan(raw);
        const statusType = raw.status_type || (ipban ? "ip" : temporary ? "temp" : "perm");
        const discord = raw.discord || raw.appeal_discord || DISCORD_FALLBACK;
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
            type: raw.type || (ipban ? "IP Ban" : temporary ? "Temporary Ban" : "Permanent Ban"),
            status_type: statusType,
            appeal_id: raw.appeal_id || raw.id || raw.uuid || "MCL-000000",
            support_email: raw.support_email || raw.appeal_email || SUPPORT_FALLBACK,
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
        if (ban.temporary) return "Temporary Ban";
        return "Permanent Ban";
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
        window.mineacleCurrentBans = currentRows;
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
        window.mineacleCurrentBans = currentRows;
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
        if (ban.ipban) {
            return `<button class="ban-type-pill ban-type-ip js-info-button" type="button" data-info-index="${index}" aria-label="View IP ban details">IP Ban</button>`;
        }

        if (ban.temporary || ban.action_type === "wait") {
            return `<button class="ban-type-pill ban-type-temp js-info-button" type="button" data-info-index="${index}" aria-label="View temporary ban details">Wait It Out</button>`;
        }

        if (ban.can_pay && ban.action_type !== "view") {
            return `<a class="btn red" href="${escapeHtml(safeUrl(ban.unban_url, "https://store.mineacle.net"))}">${escapeHtml(ban.price)} Unban</a>`;
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
                    <button class="badge ban-type-pill ${escapeHtml(ban.status_type)} js-info-button" type="button" data-info-index="${index}" aria-label="View ${escapeHtml(statusLabel(ban))} details">${escapeHtml(statusLabel(ban))}</button>
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
        const existing = document.getElementById(MODAL_ID);
        if (existing && existing.classList.contains("mineacle-ban-modal-single")) return existing;

        if (existing) existing.remove();

        const modal = document.createElement("div");
        modal.id = MODAL_ID;
        modal.className = "mineacle-ban-modal-single";
        modal.setAttribute("aria-hidden", "true");
        modal.innerHTML = `
            <section class="mineacle-ban-card-single" role="dialog" aria-modal="true" aria-labelledby="singleModalName">
                <div class="mineacle-ban-rail-single">
                    <header class="mineacle-ban-head-single">
                        <div class="mineacle-ban-player-single">
                            <img class="mineacle-ban-avatar-single" id="singleModalAvatar" src="${escapeHtml(assetUrl(LOGO_FALLBACK))}" alt="">
                            <div class="mineacle-ban-title-single">
                                <h2 class="mineacle-ban-name-single" id="singleModalName">Player</h2>
                                <span class="mineacle-ban-status-single" id="singleModalStatus">Ban</span>
                            </div>
                        </div>
                        <button class="mineacle-ban-close-single" type="button" data-single-ban-close aria-label="Close ban details">×</button>
                    </header>

                    <section class="mineacle-ban-details-single" aria-label="Ban details">
                        <article class="mineacle-ban-info-pill-single"><span>Type</span><strong id="singleModalType">Ban</strong></article>
                        <article class="mineacle-ban-info-pill-single"><span>Reason</span><strong id="singleModalReason">No reason provided</strong></article>
                        <article class="mineacle-ban-info-pill-single"><span>Duration</span><strong id="singleModalDuration">Unknown</strong></article>
                        <article class="mineacle-ban-info-pill-single"><span>Date</span><strong id="singleModalDate">Unknown</strong></article>
                        <article class="mineacle-ban-info-pill-single"><span>Appeal ID</span><strong id="singleModalAppeal">MCL-000000</strong></article>
                        <article class="mineacle-ban-info-pill-single"><span>Support Email</span><strong id="singleModalEmail">${escapeHtml(SUPPORT_FALLBACK)}</strong></article>
                    </section>

                    <a class="mineacle-ban-appeal-single" id="singleModalDiscord" href="${escapeHtml(DISCORD_FALLBACK)}" target="_blank" rel="noopener">
                        <span class="mineacle-ban-appeal-art-single"><img id="singleModalMascot" src="${escapeHtml(assetUrl(MASCOT_FALLBACK))}" alt="Discord appeal support"></span>
                        <span class="mineacle-ban-appeal-copy-single"><small>Appeal Support</small><strong>Join Discord to appeal</strong></span>
                        <span class="mineacle-ban-appeal-count-single" id="singleModalDiscordCount">Online members</span>
                    </a>

                    <div class="mineacle-ban-actions-single" id="singleModalActions"></div>
                    <p class="mineacle-ban-note-single" id="singleModalNote">Use Discord if you need staff to review the punishment</p>
                </div>
            </section>
        `;

        document.body.appendChild(modal);
        return modal;
    }

    function setText(id, value) {
        const node = document.getElementById(id);
        if (node) node.textContent = String(value ?? "");
    }

    function openBanInfoByIndex(index) {
        const numericIndex = Number(index);
        if (!Number.isInteger(numericIndex) || numericIndex < 0) return;

        const ban = currentRows[numericIndex];
        if (!ban) return;

        openBanInfo(ban);
    }

    function extractDiscordCount(payload) {
        if (!payload || typeof payload !== "object") return null;

        const candidates = [
            payload.online_members,
            payload.online,
            payload.presence_count,
            payload.presenceCount,
            payload.approximate_presence_count,
            payload.member_count,
            payload.members,
            payload.approximate_member_count,
            payload.count,
            payload.data && payload.data.online_members,
            payload.data && payload.data.online,
            payload.data && payload.data.presence_count,
            payload.data && payload.data.member_count,
            payload.data && payload.data.members
        ];

        for (const value of candidates) {
            const number = Number(value);
            if (Number.isFinite(number) && number > 0) return Math.round(number);
        }

        return null;
    }

    function formatDiscordCount(count) {
        if (!count) return "Members Online";
        return `${count.toLocaleString()} Members Online`;
    }

    function updateDiscordMemberDisplays(text) {
        discordOnlineMembersText = text || "Members Online";
        setText("singleModalDiscordCount", discordOnlineMembersText);
        setText("navDiscordOnline", discordOnlineMembersText);

        const navDiscord = document.querySelector(".mcx-discord");
        if (navDiscord) {
            navDiscord.setAttribute("aria-label", `Join Discord — ${discordOnlineMembersText}`);
            navDiscord.removeAttribute("title");
        }
    }

    async function loadDiscordMemberCount() {
        updateDiscordMemberDisplays(discordOnlineMembersText);

        try {
            const response = await fetch("api/discord.php", { headers: { "Accept": "application/json" }, cache: "no-store" });
            if (!response.ok) return;

            const payload = await readJson(response);
            const count = extractDiscordCount(payload);
            if (!count) return;

            updateDiscordMemberDisplays(formatDiscordCount(count));
        } catch (error) {
            console.error("Mineacle Discord count failed", error);
        }
    }

    function openBanInfo(rawBan) {

        const ban = normalizeBan(rawBan || {});
        const modal = createBanModal();

        const avatar = document.getElementById("singleModalAvatar");
        if (avatar) {
            avatar.src = ban.skin || assetUrl(LOGO_FALLBACK);
            avatar.alt = ban.username;
        }

        setText("singleModalName", ban.username);
        setText("singleModalStatus", statusLabel(ban));
        setText("singleModalType", ban.type);
        setText("singleModalReason", ban.reason);
        setText("singleModalDuration", ban.duration);
        setText("singleModalDate", ban.date);
        setText("singleModalAppeal", ban.appeal_id);
        setText("singleModalEmail", ban.support_email);
        setText("singleModalDiscordCount", discordOnlineMembersText);

        const status = document.getElementById("singleModalStatus");
        if (status) status.className = `mineacle-ban-status-single ${escapeHtml(ban.status_type)}`;

        const discordButton = document.getElementById("singleModalDiscord");
        if (discordButton) discordButton.href = safeUrl(ban.discord, DISCORD_FALLBACK);

        const mascot = document.getElementById("singleModalMascot");
        if (mascot) mascot.src = assetUrl(MASCOT_FALLBACK);

        const actions = document.getElementById("singleModalActions");
        const note = document.getElementById("singleModalNote");

        if (actions) actions.innerHTML = "";
        if (note) {
            if (ban.ipban) {
                note.textContent = "This is an IP ban. Use Discord if you need staff to review the punishment";
            } else if (ban.temporary) {
                note.textContent = `Temporary bans cannot be paid for. This punishment expires on ${ban.expires || "the listed expiration date"}`;
            } else if (ban.can_pay) {
                if (actions) {
                    actions.innerHTML = `<a class="mineacle-ban-pay-single" href="${escapeHtml(safeUrl(ban.unban_url, "https://store.mineacle.net"))}">${escapeHtml(ban.price)} Pay to be unbanned</a>`;
                }
                note.textContent = "Eligible permanent bans may use the payment option, or Discord if the punishment should be reviewed";
            } else {
                note.textContent = "This punishment is not currently eligible for paid unban. Use Discord if you need more information";
            }
        }

        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");

        const close = modal.querySelector("[data-single-ban-close]");
        if (close) close.focus({ preventScroll: true });
    }

    function closeModal() {
        const modal = document.getElementById(MODAL_ID);
        if (!modal) return;

        modal.classList.remove("is-open", "show");
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
            const closeButton = event.target.closest("[data-single-ban-close]");
            if (closeButton) {
                event.preventDefault();
                closeModal();
                return;
            }

            const modal = document.getElementById(MODAL_ID);
            if (modal && event.target === modal) {
                event.preventDefault();
                closeModal();
                return;
            }

            const infoButton = event.target.closest(".info-btn, .js-info-button, [data-info-index], .ban-type-pill");
            if (!infoButton) return;

            const index = infoButton.dataset ? infoButton.dataset.infoIndex : null;
            if (index === undefined || index === null || index === "") return;

            event.preventDefault();
            openBanInfoByIndex(index);
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") closeModal();
        });
    }

    function bindCopyIpButtons() {
        $$('[data-copy-ip]').forEach((button) => {
            const originalLabel = (button.textContent || "PLAY").trim() || "PLAY";
            button.dataset.originalCopyLabel = button.dataset.originalCopyLabel || originalLabel;

            button.addEventListener("click", async () => {
                const ip = button.dataset.copyIp || button.getAttribute("data-copy-ip") || "mineacle.net";

                try {
                    if (navigator.clipboard && navigator.clipboard.writeText) await navigator.clipboard.writeText(ip);
                } catch (error) {
                    console.error("Mineacle copy IP failed", error);
                }

                button.classList.add("copied");
                button.textContent = "COPIED";

                window.clearTimeout(button.mineacleCopiedTimer);
                button.mineacleCopiedTimer = window.setTimeout(() => {
                    button.classList.remove("copied");
                    button.textContent = button.dataset.originalCopyLabel || "PLAY";
                }, 1500);
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
        loadDiscordMemberCount();
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


/* Mineacle Bans v3.9.59: final copy cleanup for records labels and punishment labels */
(function () {
  'use strict';

  const labelMap = new Map([
    ['PERM BAN', 'PERMANENT BAN'],
    ['PERMANENT', 'PERMANENT BAN'],
    ['TEMP BAN', 'TEMPORARY BAN'],
    ['TEMPORARY', 'TEMPORARY BAN'],
    ['IP BAN', 'IP BAN'],
    ['WARN', 'WARNING'],
    ['WARNING', 'WARNING'],
    ['MUTE', 'MUTE'],
    ['KICK', 'KICK']
  ]);

  const normalizeLabel = (value) => {
    const key = String(value || '').trim().toUpperCase();
    return labelMap.get(key) || value;
  };

  const replaceExactText = (root, from, to) => {
    root.querySelectorAll('span, h1, h2, h3, b, strong, button, a, div').forEach((node) => {
      if (node.children.length > 0) {
        return;
      }

      if (node.textContent.trim() === from) {
        node.textContent = to;
      }
    });
  };

  const cleanupBansCopy = () => {
    const root = document.body;

    replaceExactText(root, 'SEARCH RECORDS', 'BAN LOOKUP');
    replaceExactText(root, 'Search Records', 'Ban Lookup');
    replaceExactText(root, 'Active bans', 'Public Ban Records');
    replaceExactText(root, 'Active Bans', 'Public Ban Records');
    replaceExactText(root, 'Active Ban Records', 'Public Ban Records');

    root.querySelectorAll('.ban-type-pill, .ban-status, .status-pill, .ban-pill, .mineacle-ban-status-single, .mineacle-ban-type-single').forEach((node) => {
      const next = normalizeLabel(node.textContent);
      if (next && next !== node.textContent) {
        node.textContent = next;
      }
    });
  };

  const run = () => window.requestAnimationFrame(cleanupBansCopy);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }

  document.addEventListener('click', () => {
    window.setTimeout(cleanupBansCopy, 0);
  });

  const observer = new MutationObserver(cleanupBansCopy);
  observer.observe(document.documentElement, { childList: true, subtree: true });
})();
