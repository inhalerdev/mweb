<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$config = mineacle_config();
$discordUrl = (string) ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM');
$serverIp = (string) ($config['site']['ip'] ?? 'mineacle.net');

mineacle_page_head('Bans | Mineacle Network');
mineacle_header('bans');
?>

<main class="bans-v3-page">
  <section class="bans-v3-hero bans-v31-logo-fold" aria-label="Mineacle public ban list">
    <div class="bans-v3-hero-overlay"></div>

    <div class="bans-v31-logo-center">
      <img src="assets/mineacle-hero-logo.png?v=bansfull3.1" alt="Mineacle">
    </div>

    <a class="scroll-cue" href="#ban-results" aria-label="Go to ban results">
      <span></span>
      <em>Scroll</em>
    </a>
  </section>

  <section class="bans-v31-info-strip" aria-label="Bans page information">
    <article>
      <strong>Active Records</strong>
      <span>Expired punishments are filtered out automatically</span>
    </article>
    <article>
      <strong>Appeals</strong>
      <span>Use the info popup for Email or Discord appeal options</span>
    </article>
    <article>
      <strong>Central Time</strong>
      <span>Dates display in Arkansas server time</span>
    </article>
  </section>

  <section class="bans-v3-results" id="ban-results" aria-label="Active ban results">
    <div class="bans-v3-results-head">
      <div>
        <span class="bans-v3-kicker">Search Records</span>
        <h2>Active bans</h2>
      </div>
      <div class="bans-list-meta js-ban-meta">Loading records</div>
    </div>

    <form class="bans-v3-search js-ban-search-form bans-v31-results-search" role="search">
      <label class="sr-only" for="ban-search">Search bans</label>
      <input id="ban-search" class="js-ban-search" type="search" name="q" autocomplete="off" placeholder="Search Minecraft username">
      <button class="btn red" type="submit">Search</button>
      <button class="btn soft js-ban-clear" type="button">Clear</button>
    </form>

    <div class="ban-table-shell">
      <div class="ban-table js-ban-table" aria-live="polite">
        <div class="ban-loading">Loading active bans</div>
      </div>
    </div>

    <div class="pagination-row">
      <button class="btn soft js-ban-prev" type="button">Previous</button>
      <span class="page-indicator js-ban-page">Page 1</span>
      <button class="btn soft js-ban-next" type="button">Next</button>
    </div>
  </section>
</main>

<?php mineacle_footer(); ?>
