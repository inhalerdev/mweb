<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$adminPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
if (preg_match('/\/admin\.php$/', $adminPath) === 1) {
    header('Location: /admin', true, 302);
    exit;
}
header('X-Robots-Tag: noindex, nofollow', false);

function mineacle_admin_start_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function mineacle_admin_config(): array
{
    $config = mineacle_config();

    return is_array($config['admin'] ?? null) ? $config['admin'] : [];
}

function mineacle_admin_configured_username(): string
{
    $admin = mineacle_admin_config();

    return trim((string) ($admin['username'] ?? ''));
}

function mineacle_admin_username_configured(): bool
{
    return mineacle_admin_configured_username() !== '';
}

function mineacle_admin_password_configured(): bool
{
    $admin = mineacle_admin_config();

    return trim((string) ($admin['password_hash'] ?? '')) !== '' || trim((string) ($admin['password'] ?? '')) !== '';
}

function mineacle_admin_credentials_configured(): bool
{
    return mineacle_admin_username_configured() && mineacle_admin_password_configured();
}

function mineacle_admin_username_matches(string $username): bool
{
    $configured = mineacle_admin_configured_username();
    $submitted = trim($username);

    if ($configured === '' || $submitted === '') {
        return false;
    }

    return hash_equals(strtolower($configured), strtolower($submitted));
}

function mineacle_admin_password_matches(string $password): bool
{
    $admin = mineacle_admin_config();
    $hash = trim((string) ($admin['password_hash'] ?? ''));
    $plain = (string) ($admin['password'] ?? '');

    if ($hash !== '') {
        return password_verify($password, $hash);
    }

    return $plain !== '' && hash_equals($plain, $password);
}

function mineacle_admin_login_delay_seconds(): int
{
    $lockedUntil = (int) ($_SESSION['mineacle_admin_login_locked_until'] ?? 0);

    return max(0, $lockedUntil - time());
}

function mineacle_admin_record_failed_login(): void
{
    $attempts = (int) ($_SESSION['mineacle_admin_login_attempts'] ?? 0) + 1;
    $_SESSION['mineacle_admin_login_attempts'] = $attempts;

    if ($attempts >= 5) {
        $_SESSION['mineacle_admin_login_locked_until'] = time() + 300;
    }
}

function mineacle_admin_clear_failed_login(): void
{
    unset($_SESSION['mineacle_admin_login_attempts'], $_SESSION['mineacle_admin_login_locked_until']);
}

function mineacle_admin_authenticated(): bool
{
    return !empty($_SESSION['mineacle_admin']);
}

function mineacle_admin_csrf(): string
{
    if (empty($_SESSION['mineacle_admin_csrf'])) {
        $_SESSION['mineacle_admin_csrf'] = bin2hex(random_bytes(24));
    }

    return (string) $_SESSION['mineacle_admin_csrf'];
}

function mineacle_admin_valid_csrf(): bool
{
    return hash_equals((string) ($_SESSION['mineacle_admin_csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''));
}

function mineacle_admin_table_sql(): string
{
    $tables = mineacle_config()['tables'] ?? [];
    $table = (string) ($tables['announcements'] ?? 'home_announcements');

    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        throw new RuntimeException('Invalid announcements table name.');
    }

    return '`' . $table . '`';
}

function mineacle_admin_ensure_announcements_table(PDO $pdo, string $tableSql): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS {$tableSql} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            announcement_key VARCHAR(64) NOT NULL,
            title VARCHAR(120) NOT NULL,
            eyebrow VARCHAR(48) DEFAULT 'Update',
            body VARCHAR(420) NOT NULL,
            image_url VARCHAR(512) DEFAULT NULL,
            content TEXT NULL,
            link_url VARCHAR(512) DEFAULT '#',
            sort_order INT NOT NULL DEFAULT 0,
            is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY home_announcements_key_unique (announcement_key),
            KEY home_announcements_sort_order_index (sort_order)
        )"
    );

    $columns = [];
    foreach ($pdo->query("SHOW COLUMNS FROM {$tableSql}") as $column) {
        $columns[strtolower((string) ($column['Field'] ?? ''))] = true;
    }

    if (!isset($columns['image_url'])) {
        $pdo->exec("ALTER TABLE {$tableSql} ADD COLUMN image_url VARCHAR(512) DEFAULT NULL AFTER body");
    }

    if (!isset($columns['content'])) {
        $pdo->exec("ALTER TABLE {$tableSql} ADD COLUMN content TEXT NULL AFTER image_url");
    }
}

function mineacle_admin_trim(string $key, int $limit): string
{
    return substr(trim((string) ($_POST[$key] ?? '')), 0, $limit);
}

function mineacle_admin_clean_url(string $value, string $fallback): string
{
    $value = trim($value);

    if ($value === '') {
        return $fallback;
    }

    if ($value === '#' || str_starts_with($value, '/') || str_starts_with($value, './') || str_starts_with($value, 'assets/')) {
        return substr($value, 0, 512);
    }

    return filter_var($value, FILTER_VALIDATE_URL) ? substr($value, 0, 512) : $fallback;
}

function mineacle_admin_announcement_key(string $title): string
{
    $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-'));

    if ($slug === '') {
        $slug = 'announcement';
    }

    return substr($slug, 0, 42) . '-' . date('YmdHis');
}

function mineacle_admin_save_announcement(PDO $pdo, string $tableSql): void
{
    $title = mineacle_admin_trim('title', 120);
    $eyebrow = mineacle_admin_trim('eyebrow', 48);
    $body = mineacle_admin_trim('body', 420);
    $content = mineacle_admin_trim('content', 10000);
    $imageUrl = mineacle_admin_clean_url(mineacle_admin_trim('image_url', 512), '');
    $linkUrl = mineacle_admin_clean_url(mineacle_admin_trim('link_url', 512), '#');
    $sortOrder = max(0, min(9999, (int) ($_POST['sort_order'] ?? 10)));
    $enabled = isset($_POST['is_enabled']) ? 1 : 0;

    if ($title === '' || $body === '') {
        throw new RuntimeException('Title and short summary are required.');
    }

    if ($eyebrow === '') {
        $eyebrow = 'Update';
    }

    if ($content === '') {
        $content = $body;
    }

    $statement = $pdo->prepare(
        "INSERT INTO {$tableSql} (announcement_key, title, eyebrow, body, image_url, content, link_url, sort_order, is_enabled)
        VALUES (:announcement_key, :title, :eyebrow, :body, :image_url, :content, :link_url, :sort_order, :is_enabled)"
    );
    $statement->execute([
        ':announcement_key' => mineacle_admin_announcement_key($title),
        ':title' => $title,
        ':eyebrow' => $eyebrow,
        ':body' => $body,
        ':image_url' => $imageUrl !== '' ? $imageUrl : null,
        ':content' => $content,
        ':link_url' => $linkUrl,
        ':sort_order' => $sortOrder,
        ':is_enabled' => $enabled,
    ]);
}

function mineacle_admin_recent_announcements(PDO $pdo, string $tableSql): array
{
    $statement = $pdo->query(
        "SELECT title, eyebrow, is_enabled, sort_order, updated_at
        FROM {$tableSql}
        ORDER BY sort_order ASC, id DESC
        LIMIT 12"
    );
    $rows = $statement->fetchAll();

    return is_array($rows) ? $rows : [];
}

mineacle_admin_start_session();

$site = mineacle_config()['site'] ?? [];
$config = mineacle_config();
$messages = [];
$errors = [];
$recentAnnouncements = [];
$pdo = mineacle_db();
$tableSql = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if (!mineacle_admin_valid_csrf()) {
            throw new RuntimeException('Your session expired. Refresh and try again.');
        }

        if ($action === 'login') {
            $username = (string) ($_POST['username'] ?? '');
            $password = (string) ($_POST['password'] ?? '');

            if (!mineacle_admin_credentials_configured()) {
                throw new RuntimeException('Admin username or password is not configured.');
            }

            $delay = mineacle_admin_login_delay_seconds();
            if ($delay > 0) {
                $minutes = (int) ceil($delay / 60);
                throw new RuntimeException('Too many login attempts. Try again in ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . '.');
            }

            if (!mineacle_admin_username_matches($username) || !mineacle_admin_password_matches($password)) {
                mineacle_admin_record_failed_login();
                throw new RuntimeException('Invalid username or password.');
            }

            session_regenerate_id(true);
            mineacle_admin_clear_failed_login();
            $_SESSION['mineacle_admin'] = true;
            $_SESSION['mineacle_admin_user'] = mineacle_admin_configured_username();
            unset($_SESSION['mineacle_admin_csrf']);
            $messages[] = 'Logged in.';
        } elseif ($action === 'logout') {
            unset($_SESSION['mineacle_admin'], $_SESSION['mineacle_admin_user'], $_SESSION['mineacle_admin_csrf']);
            mineacle_admin_clear_failed_login();
            session_regenerate_id(true);
            $messages[] = 'Logged out.';
        } elseif ($action === 'save_announcement') {
            if (!mineacle_admin_authenticated()) {
                throw new RuntimeException('Please log in first.');
            }

            if (!$pdo instanceof PDO) {
                throw new RuntimeException('Database connection is not available.');
            }

            $tableSql = mineacle_admin_table_sql();
            mineacle_admin_ensure_announcements_table($pdo, $tableSql);
            mineacle_admin_save_announcement($pdo, $tableSql);
            $messages[] = 'Announcement saved.';
        }
    } catch (Throwable $error) {
        $errors[] = $error->getMessage();
    }
}

if (mineacle_admin_authenticated() && $pdo instanceof PDO) {
    try {
        $tableSql = $tableSql !== '' ? $tableSql : mineacle_admin_table_sql();
        mineacle_admin_ensure_announcements_table($pdo, $tableSql);
        $recentAnnouncements = mineacle_admin_recent_announcements($pdo, $tableSql);
    } catch (Throwable $error) {
        $errors[] = $error->getMessage();
    }
}

$csrf = mineacle_admin_csrf();
$homeDatabaseEnabled = (bool) ($config['home']['database_enabled'] ?? false);

mineacle_page_head('Admin');
?>
<main class="admin-page" aria-label="Mineacle admin">
    <section class="admin-panel admin-hero">
        <div>
            <p>Mineacle Admin</p>
            <h1>Announcements</h1>
            <span>Post homepage updates, attach an image URL, and write the full read-more copy.</span>
        </div>
        <?php if (mineacle_admin_authenticated()): ?>
            <form method="post" action="/admin">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="action" value="logout">
                <button class="admin-button admin-button-secondary" type="submit">Log Out</button>
            </form>
        <?php endif; ?>
    </section>

    <?php foreach ($messages as $message): ?>
        <p class="admin-notice is-success"><?php echo h($message); ?></p>
    <?php endforeach; ?>

    <?php foreach ($errors as $error): ?>
        <p class="admin-notice is-error"><?php echo h($error); ?></p>
    <?php endforeach; ?>

    <?php if (!mineacle_admin_credentials_configured()): ?>
        <section class="admin-panel">
            <h2>Admin Credentials Needed</h2>
            <p class="admin-muted">Set <code>ADMIN_USERNAME</code> and <code>ADMIN_PASSWORD</code> or <code>ADMIN_PASSWORD_HASH</code> in Wasmer environment variables before this page can publish updates.</p>
        </section>
    <?php elseif (!mineacle_admin_authenticated()): ?>
        <section class="admin-panel admin-login">
            <h2>Log In</h2>
            <form method="post" action="/admin">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="action" value="login">
                <label>
                    <span>Username</span>
                    <input name="username" type="text" autocomplete="username" required>
                </label>
                <label>
                    <span>Password</span>
                    <input name="password" type="password" autocomplete="current-password" required>
                </label>
                <button class="admin-button" type="submit">Log In</button>
            </form>
        </section>
    <?php else: ?>
        <?php if (!$homeDatabaseEnabled): ?>
            <p class="admin-notice">Set <code>HOME_DATABASE_ENABLED=true</code> in Wasmer so saved announcements appear on the public homepage.</p>
        <?php endif; ?>

        <section class="admin-grid">
            <form class="admin-panel admin-form" method="post" action="/admin">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="action" value="save_announcement">
                <h2>New Announcement</h2>
                <label>
                    <span>Small Label</span>
                    <input name="eyebrow" type="text" maxlength="48" placeholder="Latest" value="Latest">
                </label>
                <label>
                    <span>Title</span>
                    <input name="title" type="text" maxlength="120" placeholder="Network Update" required>
                </label>
                <label>
                    <span>Image URL</span>
                    <input name="image_url" type="text" maxlength="512" placeholder="https://... or /assets/...">
                </label>
                <label>
                    <span>Optional Link</span>
                    <input name="link_url" type="text" maxlength="512" placeholder="https://...">
                </label>
                <label>
                    <span>Short Summary</span>
                    <textarea name="body" maxlength="420" rows="3" placeholder="Short card text shown on the homepage." required></textarea>
                </label>
                <label>
                    <span>Full Read More Copy</span>
                    <textarea name="content" maxlength="10000" rows="8" placeholder="Full update text shown in the pop up."></textarea>
                </label>
                <div class="admin-form-row">
                    <label>
                        <span>Order</span>
                        <input name="sort_order" type="number" min="0" max="9999" value="10">
                    </label>
                    <label class="admin-checkbox">
                        <input name="is_enabled" type="checkbox" checked>
                        <span>Visible</span>
                    </label>
                </div>
                <button class="admin-button" type="submit">Post Announcement</button>
            </form>

            <section class="admin-panel admin-recent">
                <h2>Recent Announcements</h2>
                <?php if ($recentAnnouncements === []): ?>
                    <p class="admin-muted">No announcements are saved yet.</p>
                <?php else: ?>
                    <div class="admin-recent-list">
                        <?php foreach ($recentAnnouncements as $announcement): ?>
                            <article>
                                <span><?php echo h((string) ($announcement['eyebrow'] ?? 'Update')); ?></span>
                                <strong><?php echo h((string) ($announcement['title'] ?? 'Announcement')); ?></strong>
                                <small><?php echo ((int) ($announcement['is_enabled'] ?? 0)) === 1 ? 'Visible' : 'Hidden'; ?> · Order <?php echo h((string) ($announcement['sort_order'] ?? 0)); ?></small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    <?php endif; ?>
</main>
<?php mineacle_page_end(); ?>
