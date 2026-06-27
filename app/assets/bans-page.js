(() => {
  "use strict";

  const form = document.getElementById("banSearchForm");
  const input = document.getElementById("banSearch");
  const action = document.getElementById("banSearchAction");

  if (!form || !input || !action) return;

  let controller = null;
  let typingTimer = null;
  let queryTimer = null;

  const setHasValue = () => {
    const hasValue = input.value.trim().length > 0;
    form.classList.toggle("has-value", hasValue);
    action.setAttribute("aria-label", "Search");
    action.setAttribute("title", "Search");
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
    setHasValue();
    input.focus();
    queryDatabase();
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    setHasValue();
    queryDatabase();
  });

  setHasValue();
})();
