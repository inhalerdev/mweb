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
  <section class="web-panel" aria-label="Mineacle public bans hero">
    <?php mineacle_header('bans'); ?>

    <section class="hero">
      <div class="hero-bg" aria-hidden="true"></div>
      <div class="hero-shade" aria-hidden="true"></div>

      <div class="hero-content">
        <img class="hero-logo" src="assets/mineacle-logo.png?v=12" alt="Mineacle">

        <h1>Public <span>Ban List</span></h1>

        <div class="divider" aria-hidden="true">
          <span></span><i></i><span></span>
        </div>

        <p>Search active punishments, review public records, and keep Mineacle safe for everyone</p>

        <div class="hero-actions">
          <a class="hero-action" href="<?= h($config['site']['discord']) ?>" target="_blank" rel="noopener">
            <img src="assets/discord.svg" alt=""> Discord
          </a>
          <button class="hero-action copy-ip" type="button" data-copy="<?= h($config['site']['ip']) ?>">
            <img src="assets/copy.svg" alt=""> Copy IP
          </button>
        </div>
      </div>

      <a class="scroll-down" href="#bans" aria-label="Scroll to bans">
        <img src="assets/down.svg" alt="">
      </a>
    </section>
  </section>

  <section class="bans-section" id="bans">
    <div class="bans-heading">
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
          <img src="assets/search.svg" alt="">
          <input id="banSearch" type="search" placeholder="Search username..." autocomplete="off" maxlength="32">
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

<?php mineacle_footer(); ?>
</body>
</html>
