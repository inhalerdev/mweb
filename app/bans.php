<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

mineacle_page_head('Bans | Mineacle Network');
mineacle_header('bans');
?>

<main class="bans-v3-page">
  <section class="bans-v3-hero bans-v32-logo-only-fold bans-v34-background-fold" aria-label="Mineacle bans">
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
