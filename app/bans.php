<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$config = mineacle_config();
$discordUrl = (string) ($config['site']['discord'] ?? 'https://discord.gg/VwbwWftefM');
$appealEmail = (string) ($config['site']['appeal_email'] ?? 'support@mineacle.net');
$serverIp = (string) ($config['site']['ip'] ?? 'mineacle.net');

mineacle_page_head('Bans | Mineacle Network');
mineacle_header('bans');
?>

<main class="bans-page-shell">
  <section class="bans-hero-redesign" aria-label="Mineacle punishments information">
        <div class="bans-hero-left">
      <span class="eyebrow">Mineacle Safety Center</span>
      <h1>Public Ban List</h1>
      <p>Search active punishments, review LiteBans records, and find the right appeal option without digging through Discord messages.</p>

      <div class="bans-hero-actions">
        <a class="btn red" href="#bans-list">Search Bans</a>
        <a class="btn soft" href="<?= h($discordUrl) ?>" target="_blank" rel="noopener">Discord Appeal</a>
      </div>
    </div>

    <div class="bans-hero-info-grid">
      <article class="bans-info-card">
        <span>Live Records</span>
        <strong>Active Only</strong>
        <p>Expired punishments are filtered out automatically.</p>
      </article>
      <article class="bans-info-card">
        <span>Appeals</span>
        <strong>Email + Discord</strong>
        <p>Use the info popup on any punishment to appeal.</p>
      </article>
      <article class="bans-info-card">
        <span>Payments</span>
        <strong>Permanent Only</strong>
        <p>Temporary bans show Wait It Out instead of payment.</p>
      </article>
      <article class="bans-info-card">
        <span>Timezone</span>
        <strong>Central Time</strong>
        <p>Dates are displayed in Arkansas server time.</p>
      </article>
    </div>
  </section>

  <section class="bans-toolbar-redesign" id="bans-list" aria-label="Search active punishments">
    <div class="bans-toolbar-copy">
      <span class="eyebrow">Search Records</span>
      <h2>Active punishments</h2>
      <p>Search by Minecraft username, then open the info button for reason, date, duration, appeal links, and status.</p>
    </div>

    <form class="search-card bans-search-form js-ban-search-form" role="search">
      <label class="sr-only" for="ban-search">Search bans</label>
      <div class="search-input-wrap">
        <img src="assets/search-icon.png?v=bansfull2.2" alt="" aria-hidden="true">
        <input id="ban-search" class="ban-search-input js-ban-search" type="search" name="q" autocomplete="off" placeholder="Search a Minecraft username">
      </div>
      <button class="btn red" type="submit">Search</button>
      <button class="btn soft ban-search-clear js-ban-clear" type="button">Clear</button>
    </form>
  </section>

  <section class="bans-list-section" aria-label="Ban results">
    <div class="bans-list-header">
      <div>
        <span class="eyebrow">Results</span>
        <h2>Ban records</h2>
      </div>
      <div class="bans-list-meta bans-result-count js-ban-meta">Loading records</div>
    </div>

    <div class="ban-table bans-table js-ban-table">
      <div class="ban-table bans-table js-ban-table" aria-live="polite">
        <div class="ban-loading">Loading active bans</div>
      </div>
    </div>

    <div class="pagination-row">
      <button class="btn soft bans-prev js-ban-prev" type="button">Previous</button>
      <span class="page-indicator bans-page-label js-ban-page">Page 1</span>
      <button class="btn soft bans-next js-ban-next" type="button">Next</button>
    </div>
  </section>
</main>

<?php mineacle_footer(); ?>
