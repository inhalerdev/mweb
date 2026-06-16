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
  <section class="bans-v3-hero" aria-label="Mineacle public ban list">
    <div class="bans-v3-hero-overlay"></div>

    <div class="bans-v3-center">
      <span class="bans-v3-kicker">Mineacle Network</span>
      <h1>Public Ban List</h1>
      <p>Search active punishments, check appeal information, and review current LiteBans records.</p>

      <form class="bans-v3-search js-ban-search-form" role="search">
        <label class="sr-only" for="ban-search">Search bans</label>
        <input id="ban-search" class="js-ban-search" type="search" name="q" autocomplete="off" placeholder="Search Minecraft username">
        <button class="btn red" type="submit">Search</button>
        <button class="btn soft js-ban-clear" type="button">Clear</button>
      </form>

      <div class="bans-v3-actions">
        <button class="btn red" type="button" data-copy-ip="<?= h($serverIp) ?>">Copy Server IP</button>
        <a class="btn soft" href="<?= h($discordUrl) ?>" target="_blank" rel="noopener">Appeal on Discord</a>
      </div>

      <div class="bans-v3-quick-info" aria-label="Quick page information">
        <div><strong>Active</strong><span>punishments only</span></div>
        <div><strong>Central</strong><span>server time</span></div>
        <div><strong>Appeals</strong><span>email or Discord</span></div>
      </div>
    </div>

    <a class="scroll-cue" href="#ban-results" aria-label="Go to ban results">
      <span></span>
      <em>Scroll</em>
    </a>
  </section>

  <section class="bans-v3-results" id="ban-results" aria-label="Active ban results">
    <div class="bans-v3-results-head">
      <div>
        <span class="bans-v3-kicker">Results</span>
        <h2>Active bans</h2>
      </div>
      <div class="bans-list-meta js-ban-meta">Loading records</div>
    </div>

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
