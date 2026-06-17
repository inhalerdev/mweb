(() => {
    "use strict";

    const MODAL_ID = "banModal";
    const DISCORD_FALLBACK = "https://discord.gg/VwbwWftefM";
    const SUPPORT_FALLBACK = "support@mineacle.net";
    const LOGO_FALLBACK = "assets/mineacle-square-logo.png";
    const MASCOT_FALLBACK = "assets/discord-character.png";

    let discordOnlineMembersText = "Online members";

    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

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

    function currentAssetRoot() {
        const script = document.currentScript || document.querySelector('script[src*="bans-modal-single.js"]');
        return script ? new URL(".", script.src) : new URL("assets/", window.location.href);
    }

    function assetUrl(filename) {
        return new URL(filename, currentAssetRoot()).toString();
    }

    function isTemporary(raw = {}) {
        return Boolean(raw.temporary || raw.temp || raw.type === "Temp Ban" || raw.status_type === "temp");
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
            type: raw.type || (ipban ? "IP Ban" : temporary ? "Temp Ban" : "Perm Ban"),
            status: raw.status || (ipban ? "IP Ban" : temporary ? "Temporary Ban" : "Permanent Ban"),
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
        if (ban.temporary) return "Temp Ban";
        return "Perm Ban";
    }

    function removeLegacyBanModals() {
        $$("#banModal, .mineacle-js-modal, .ban-popup-v70").forEach((node) => {
            if (!node.classList.contains("mineacle-ban-modal-single")) node.remove();
        });
    }

    function ensureModal() {
        const existing = document.getElementById(MODAL_ID);
        if (existing && existing.classList.contains("mineacle-ban-modal-single")) return existing;

        removeLegacyBanModals();

        const modal = document.createElement("div");
        modal.id = MODAL_ID;
        modal.className = "mineacle-ban-modal-single";
        modal.setAttribute("aria-hidden", "true");
        modal.innerHTML = `
            <section class="mineacle-ban-card-single" role="dialog" aria-modal="true" aria-labelledby="singleModalName">
                <div class="mineacle-ban-rail-single">
                    <header class="mineacle-ban-head-single">
                        <div class="mineacle-ban-player-single">
                            <img class="mineacle-ban-avatar-single" id="singleModalAvatar" src="${escapeHtml(LOGO_FALLBACK)}" alt="">
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
                        <span class="mineacle-ban-appeal-art-single"><img id="singleModalMascot" src="${escapeHtml(MASCOT_FALLBACK)}" alt=""></span>
                        <span class="mineacle-ban-appeal-copy-single"><small>Appeal Support</small><strong>Join Discord to appeal</strong></span>
                        <span class="mineacle-ban-appeal-count-single" id="singleModalDiscordCount">Online members</span>
                    </a>

                    <div class="mineacle-ban-actions-single" id="singleModalActions"></div>
                    <p class="mineacle-ban-note-single" id="singleModalNote">Use Discord if you need staff to review the punishment</p>
                </div>
            </section>
        `;

        document.body.appendChild(modal);
        const mascot = document.getElementById("singleModalMascot");
        if (mascot) mascot.src = assetUrl("discord-character.png");
        return modal;
    }

    function setText(id, value) {
        const node = document.getElementById(id);
        if (node) node.textContent = String(value ?? "");
    }

    function banFromIndex(index) {
        const numericIndex = Number(index);
        if (!Number.isInteger(numericIndex) || numericIndex < 0) return null;
        const rows = Array.isArray(window.mineacleCurrentBans) ? window.mineacleCurrentBans : [];
        return rows[numericIndex] || null;
    }

    function openBanInfo(rawBan) {
        const ban = normalizeBan(rawBan || {});
        const modal = ensureModal();

        const avatar = document.getElementById("singleModalAvatar");
        if (avatar) {
            avatar.src = ban.skin || assetUrl("mineacle-square-logo.png");
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
        if (status) status.className = `mineacle-ban-status-single ${ban.status_type}`;

        const discordButton = document.getElementById("singleModalDiscord");
        if (discordButton) discordButton.href = safeUrl(ban.discord, DISCORD_FALLBACK);

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

    function openBanInfoByIndex(index) {
        const ban = banFromIndex(index);
        if (ban) openBanInfo(ban);
    }

    function closeBanInfo() {
        const modal = document.getElementById(MODAL_ID);
        if (!modal) return;
        modal.classList.remove("is-open", "show");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");
    }

    function bindSingleModalClicks() {
        document.addEventListener("click", (event) => {
            const closeButton = event.target.closest("[data-single-ban-close]");
            if (closeButton) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                closeBanInfo();
                return;
            }

            const modal = document.getElementById(MODAL_ID);
            if (modal && event.target === modal) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                closeBanInfo();
                return;
            }

            const trigger = event.target.closest(".info-btn, .js-info-button, [data-info-index], .ban-type-pill");
            if (!trigger) return;

            const index = trigger.dataset ? trigger.dataset.infoIndex : null;
            if (index === undefined || index === null || index === "") return;

            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            openBanInfoByIndex(index);
        }, true);

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") closeBanInfo();
        }, true);
    }

    async function readJson(response) {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch {
            return null;
        }
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

    async function loadDiscordMemberCount() {
        try {
            const response = await fetch("api/discord.php", { headers: { "Accept": "application/json" }, cache: "no-store" });
            if (!response.ok) return;
            const payload = await readJson(response);
            const count = extractDiscordCount(payload);
            if (!count) return;
            discordOnlineMembersText = `${count.toLocaleString()} online members`;
            setText("singleModalDiscordCount", discordOnlineMembersText);
        } catch {
            // Non-critical; keep the fallback text.
        }
    }

    function installSingleBanModal() {
        removeLegacyBanModals();
        bindSingleModalClicks();
        loadDiscordMemberCount();

        window.mineacleOpenBanInfo = openBanInfo;
        window.mineacleOpenBanInfoByIndex = openBanInfoByIndex;
        window.mineacleCloseBanInfo = closeBanInfo;
        window.mineacleRemoveLegacyBanModals = removeLegacyBanModals;
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", installSingleBanModal, { once: true });
    } else {
        installSingleBanModal();
    }
})();
