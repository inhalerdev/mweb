<?php
declare(strict_types=1);

/*
 * Public canonical route enforcement.
 * If someone manually visits /bans.php, redirect them to /
 */
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
<?php mineacle_header(); ?>

<main class="page">
  <section class="hero shell">
    <div class="hero-banner">
      <div class="hero-image" aria-hidden="true"></div>
      <div class="hero-overlay" aria-hidden="true"></div>

      <div class="hero-content">
        <span class="tag">Mineacle Network</span>
        <img class="hero-logo" src="assets/mineacle-logo.png?v=9" alt="Mineacle">
        <h1>Public Ban List</h1>
        <p>
          Search active Mineacle punishments, copy the server IP, visit the store, vote for rewards,
          or join Discord for help.
        </p>

        <div class="hero-link-grid" aria-label="Mineacle quick links">
          <a class="hero-link store" href="<?= h($config['site']['store'] ?? 'https://store.mineacle.net') ?>">
            <span class="hero-link-icon"><img src="assets/basket.svg" alt=""></span>
            <span><strong>Store</strong><small>Ranks, perks, and support</small></span>
          </a>

          <a class="hero-link vote" href="<?= h($config['site']['vote'] ?? 'https://vote.mineacle.net') ?>">
            <span class="hero-link-icon"><img src="assets/vote.svg" alt=""></span>
            <span><strong>Vote</strong><small>Claim rewards and help Mineacle grow</small></span>
          </a>

          <a class="hero-link bans active" href="/">
            <span class="hero-link-icon"><img src="assets/hammer.svg" alt=""></span>
            <span><strong>Bans</strong><small>View public punishment records</small></span>
          </a>

          <a class="hero-link discord" href="<?= h($config['site']['discord']) ?>" target="_blank" rel="noopener">
            <span class="hero-link-icon"><img src="assets/discord.svg" alt=""></span>
            <span><strong>Discord</strong><small>Appeals, help, and updates</small></span>
          </a>

          <button class="hero-link copy copy-ip" type="button" data-copy="<?= h($config['site']['ip']) ?>">
            <span class="hero-link-icon"><img src="assets/copy.svg" alt=""></span>
            <span><strong>Copy IP</strong><small><?= h($config['site']['ip']) ?></small></span>
          </button>
        </div>
      </div>
    </div>
  </section>

  <section class="shell bans-section" id="bans">
    <div class="ban-list-wrap">
      <div class="ban-toolbar">
        <div class="ban-title">
          <span class="tag compact">Newest First</span>
          <h2>Active Bans</h2>
          <p>Unbanned and expired players disappear automatically</p>
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

    <div class="rule-grid">
      <article class="rule-card">
        <img src="assets/hammer.svg" alt="">
        <strong>Player Bans</strong>
        <span>Eligible active player bans may show an unban payment option</span>
      </article>
      <article class="rule-card">
        <img src="assets/lock.svg" alt="">
        <strong>IP Bans</strong>
        <span>Shown as permanently banned with no public dispute or payment option</span>
      </article>
      <article class="rule-card">
        <img src="assets/info.svg" alt="">
        <strong>Info Popup</strong>
        <span>Shows reason, duration, date, appeal ID, email, Discord, and action</span>
      </article>
    </div>
  </section>
</main>

<div class="modal" id="banModal" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalName">
    <div class="modal-head">
      <img id="modalAvatar" src="" alt="">
      <div>
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
