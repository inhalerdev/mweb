<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$config = mineacle_config();
$voteSites = $config['vote_sites'] ?? [];
$serverIp = (string) ($config['site']['ip'] ?? 'mineacle.net');

mineacle_head('Vote | Mineacle Network', 'Vote for Mineacle Network and earn in-game rewards');
mineacle_header('vote');
?>

<section class="vote-page-hero" aria-label="Vote for Mineacle">
  <div class="vote-page-hero-inner">
    <div class="vote-page-copy">
      <span class="eyebrow">Mineacle Rewards</span>
      <h1>Vote for Mineacle</h1>
      <p>Support the network, help new players find the server, and earn vote rewards in-game.</p>

      <div class="vote-page-actions">
        <button class="btn red" type="button" data-copy-ip="<?= h($serverIp) ?>">Copy Server IP</button>
        <a class="btn soft" href="#vote-sites">View Vote Links</a>
      </div>
    </div>

    <div class="vote-page-card">
      <img class="vote-page-logo" src="assets/mineacle-main-logo.png?v=foundation1.59" alt="Mineacle Network">
      <div class="vote-reward-box">
        <span>Reward</span>
        <strong>Vote Key</strong>
        <p>Vote daily and claim your reward once your vote is confirmed.</p>
      </div>
    </div>
  </div>
</section>

<section class="vote-page-section" id="vote-sites">
  <div class="section-heading">
    <span>Daily Vote Links</span>
    <h2>Choose a vote site</h2>
    <p>Use your exact Minecraft username on each vote site so rewards can be delivered correctly.</p>
  </div>

  <div class="vote-site-grid">
    <?php foreach ($voteSites as $index => $site):
        $name = (string) ($site['name'] ?? ('Vote Site ' . ($index + 1)));
        $url = (string) ($site['url'] ?? '#');
        $reward = (string) ($site['reward'] ?? 'Vote Key');
        $disabled = trim($url) === '' || trim($url) === '#';
    ?>
      <article class="vote-site-card<?= $disabled ? ' disabled' : '' ?>">
        <div class="vote-site-number"><?= (int) $index + 1 ?></div>

        <div class="vote-site-content">
          <span>Vote Site</span>
          <h3><?= h($name) ?></h3>
          <p>Reward: <strong><?= h($reward) ?></strong></p>
        </div>

        <?php if ($disabled): ?>
          <button class="btn soft disabled" type="button" disabled>Coming Soon</button>
        <?php else: ?>
          <a class="btn red" href="<?= h($url) ?>" target="_blank" rel="noopener">Vote Now</a>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="vote-page-section vote-help-section" aria-label="How voting works">
  <div class="vote-help-card">
    <div>
      <span class="eyebrow">How It Works</span>
      <h2>Vote, join, collect</h2>
      <p>Voting helps Mineacle grow and gives players simple daily rewards.</p>
    </div>

    <div class="vote-help-steps">
      <div class="vote-help-step">
        <strong>1</strong>
        <span>Open a vote link</span>
      </div>
      <div class="vote-help-step">
        <strong>2</strong>
        <span>Enter your Minecraft username</span>
      </div>
      <div class="vote-help-step">
        <strong>3</strong>
        <span>Join Mineacle and claim rewards</span>
      </div>
    </div>
  </div>
</section>

<?php mineacle_footer(); ?>
