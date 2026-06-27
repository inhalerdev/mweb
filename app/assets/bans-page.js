(() => {
  "use strict";

  const form = document.getElementById("banSearchForm");
  const input = document.getElementById("banSearch");
  const action = document.getElementById("banSearchAction");
  const playerModule = document.getElementById("mineaclePlayerCountModule");
  const ipNode = document.getElementById("mineaclePlayerCountIp");
  const onlineCountNode = document.getElementById("mineaclePlayerCountValue");

  if (!form || !input || !action) return;

  let controller = null;
  let typingTimer = null;
  let queryTimer = null;
  let copiedTimer = null;

  const copyIp = playerModule ? (playerModule.dataset.copyIp || "mineacle.net") : "mineacle.net";
  const displayIp = playerModule ? (playerModule.dataset.displayIp || copyIp).toUpperCase() : "MINEACLE.NET";

  const setHasValue = () => {
    const hasValue = input.value.trim().length > 0;
    form.classList.toggle("has-value", hasValue);
    action.setAttribute("aria-label", hasValue ? "Clear search" : "Search");
    action.setAttribute("title", hasValue ? "Clear search" : "Search");
  };

  const clearSearch = () => {
    input.value = "";
    input.focus();
    form.classList.remove("is-typing");
    setHasValue();
    scheduleQuery();
  };

  const setTyping = () => {
    form.classList.add("is-typing");
    if (typingTimer) window.clearTimeout(typingTimer);
    typingTimer = window.setTimeout(() => {
      form.classList.remove("is-typing");
    }, 450);
  };

  const queryDatabase = async () => {
    const search = input.value.trim();
    if (controller) controller.abort();
    controller = new AbortController();
    const activeController = controller;
    form.classList.add("is-loading");
    form.setAttribute("aria-busy", "true");

    try {
      const url = `api/bans.php?search=${encodeURIComponent(search)}&page=1&scope=active`;
      const response = await fetch(url, {
        headers: { "Accept": "application/json" },
        cache: "no-store",
        signal: controller.signal
      });

      if (!response.ok) return null;
      return await response.json();
    } catch (error) {
      if (error && error.name === "AbortError") return null;
      return null;
    } finally {
      if (controller === activeController) {
        form.classList.remove("is-loading");
        form.setAttribute("aria-busy", "false");
      }
    }
  };

  const scheduleQuery = () => {
    if (queryTimer) window.clearTimeout(queryTimer);
    queryTimer = window.setTimeout(() => {
      queryDatabase();
    }, 300);
  };

  const normalizeOnlineCount = (payload) => {
    if (!payload || typeof payload !== "object") return 0;

    const candidates = [
      payload.players_online,
      payload.online_players,
      payload.onlineCount,
      payload.player_count,
      payload.count,
      payload.players && payload.players.online
    ];

    for (const candidate of candidates) {
      if (typeof candidate === "number" && Number.isFinite(candidate)) {
        return Math.max(0, Math.floor(candidate));
      }

      if (typeof candidate === "string" && candidate.trim() !== "") {
        const value = Number(candidate);
        if (Number.isFinite(value)) return Math.max(0, Math.floor(value));
      }
    }

    return 0;
  };

  const updateOnlineCount = async () => {
    if (!onlineCountNode) return;

    try {
      const response = await fetch("api/server-status.php", {
        headers: { "Accept": "application/json" },
        cache: "no-store"
      });

      if (!response.ok) return;
      const payload = await response.json();
      onlineCountNode.textContent = String(normalizeOnlineCount(payload));
    } catch (_) {
      onlineCountNode.textContent = "0";
    }
  };

  const fallbackCopy = (text) => {
    const textarea = document.createElement("textarea");
    textarea.value = text;
    textarea.setAttribute("readonly", "readonly");
    textarea.style.position = "fixed";
    textarea.style.left = "-9999px";
    document.body.appendChild(textarea);
    textarea.select();

    try {
      document.execCommand("copy");
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
    if (!playerModule || !ipNode) return;
    if (copiedTimer) window.clearTimeout(copiedTimer);

    playerModule.classList.add("is-copied");
    ipNode.textContent = "IP COPIED";

    copiedTimer = window.setTimeout(() => {
      playerModule.classList.remove("is-copied");
      ipNode.textContent = displayIp;
    }, 1400);
  };

  input.addEventListener("input", () => {
    setHasValue();
    setTyping();
    scheduleQuery();
  });

  input.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    clearSearch();
  });

  action.addEventListener("click", () => {
    if (input.value.trim().length > 0) {
      clearSearch();
      return;
    }

    setHasValue();
    input.focus();
    queryDatabase();
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    setHasValue();
    queryDatabase();
  });

  if (playerModule) {
    playerModule.addEventListener("click", async () => {
      try {
        await copyText(copyIp);
      } finally {
        showCopied();
      }
    });
  }

  if (ipNode) ipNode.textContent = displayIp;
  setHasValue();
  updateOnlineCount();
  window.setInterval(updateOnlineCount, 30000);
})();
