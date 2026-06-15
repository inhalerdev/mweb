<?php
declare(strict_types=1);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (!defined('MINEACLE_INTERNAL_RENDER') && preg_match('~/bans\.php$~i', $requestPath)) {
    header('Location: /', true, 301);
    exit;
}

require_once __DIR__ . '/includes/layout.php';
$config = mineacle_config();
mineacle_page_head('Bans');
?>
<body>
<main class="page-shell">
  <?php mineacle_header('bans'); ?>

  <section class="hero-shell" aria-label="Mineacle public bans hero">
    <div class="hero-grid">
      <div class="hero-main-logo-wrap" aria-label="Mineacle main logo">
        <img class="hero-main-logo" src="assets/mineacle-main-logo.png?v=foundation1.26" alt="Mineacle">
      </div>

      <div class="hero-copy">
        <h1>Public Ban List</h1>
        <p>Search active punishments, review public LiteBans records, and keep Mineacle safe for every player</p>

        <a class="discord-panel" href="<?= h($config['site']['discord']) ?>" target="_blank" rel="noopener">
          <div class="discord-character-wrap">
            <img class="discord-character" src="assets/discord-character.webp?v=foundation1.26" alt="">
          </div>
          <div>
            <span>Official Discord</span>
            <strong>Appeals, updates, and support</strong>
            <p>Join the Mineacle community for ban help, server news, event updates, and player support.</p>
          </div>
          <div class="discord-arrow" aria-hidden="true">→</div>
        </a>
      </div>
    </div>
  </section>

  <section class="bans-section" id="bans">
    <div class="section-heading">
      <span>Public Records</span>
      <h2>Active Bans</h2>
      <p>Newest punishments are shown first. Unbanned and expired players disappear automatically.</p>
    </div>

    <div class="ban-list-wrap">
      <div class="ban-toolbar">
        <div class="ban-title">
          <span class="tag">25 per page</span>
          <h3>Search the ban list</h3>
          <p>Search by username</p>
        </div>

        <div class="searchbar">
          <img class="search-icon" src="assets/search-icon.png?v=foundation1.26" alt="" aria-hidden="true">
          <input id="banSearch" type="search" placeholder="Search username..." autocomplete="off" maxlength="32">
          <button class="search-clear" id="clearSearch" type="button" aria-label="Clear search">×</button>
        </div>

        <div class="ban-count" id="banCount">Loading...</div>
      </div>

      <div class="ban-list" id="banList">
        <div class="empty">Loading LiteBans data...</div>
      </div>

      <div class="ban-pagination" id="banPagination" hidden>
        <button class="btn soft" type="button" id="prevPage">Previous</button>
        <span id="pageInfo">Page 1 of 1</span>
        <button class="btn soft" type="button" id="nextPage">Next</button>
      </div>
    </div>
  </section>

  <?php mineacle_footer(); ?>
</main>

<div class="modal" id="banModal" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalName">
    <div class="modal-head">
      <img id="modalAvatar" src="" alt="">
      <div class="modal-title">
        <h2 id="modalName">Player</h2>
        <span id="modalStatus" class="badge active">Active</span>
      </div>
      <button class="close-modal" type="button" data-close-modal aria-label="Close">×</button>
    </div>
    <div class="modal-body">
      <div class="detail-grid">
        <div class="detail"><span>Reason</span><span id="modalReason"></span></div>
        <div class="detail"><span>Type</span><span id="modalType"></span></div>
        <div class="detail"><span>Duration</span><span id="modalDuration"></span></div>
        <div class="detail"><span>Date</span><span id="modalDate"></span></div>
        <div class="detail"><span>Appeal ID</span><span id="modalAppeal"></span></div>
        <div class="detail"><span>Email</span><span id="modalEmail"></span></div>
        <div class="detail"><span>Discord</span><span id="modalDiscord"></span></div>
      </div>
      <div class="modal-actions" id="modalActions"></div>
      <div class="modal-note" id="modalNote"></div>
    </div>
  </div>
</div>
</body>
</html>
