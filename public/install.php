<?php
declare(strict_types=1);

/**
 * SWON — database setup wizard (shared hosting).
 *
 * Open:  https://yourdomain/install.php
 *
 * The form collects the database details, writes them to .env, then imports
 * the schema and data. It locks itself afterwards. Delete this file when done.
 */

$root = dirname(__DIR__);

require $root . '/app/Env.php';
require $root . '/app/SqlRunner.php';

\App\Env::load($root . '/.env');

$h = static fn (mixed $value): string
    => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Content-Type: text/html; charset=UTF-8');

$envFile   = $root . '/.env';
$lockFile  = $root . '/storage/installed.lock';
$sqlFiles  = [
    'schema' => $root . '/database/live/schema.sql',
    'seed'   => $root . '/database/live/seed.sql',
    'remove' => $root . '/database/live/remove-demo-data.sql',
];
$sqlReady  = !in_array(false, array_map('is_file', $sqlFiles), true);
$storageOk = is_writable($root . '/storage');
$envOk     = is_file($envFile) ? is_writable($envFile) : is_writable($root);

$locked = is_file($lockFile);

$currentPass = (string) \App\Env::get('DB_PASS', '');
$defaultUrl  = (string) \App\Env::get('APP_URL', '');
if ($defaultUrl === '') {
    $scheme     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $defaultUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.swon.in');
}

$form = [
    'db_host' => (string) \App\Env::get('DB_HOST', 'localhost'),
    'db_port' => (string) \App\Env::get('DB_PORT', '3306'),
    'db_name' => (string) \App\Env::get('DB_NAME', ''),
    'db_user' => (string) \App\Env::get('DB_USER', ''),
    'app_url' => $defaultUrl,
];

$error   = '';
$success = null;

/** Write/merge key=value pairs into .env, preserving comments and other keys. */
function env_line(string $key, string $value): string
{
    $value = str_replace(["\r", "\n"], '', $value);
    if ($value === '') {
        return $key . '=';
    }
    if (preg_match('/\s/', $value) === 1 && !str_contains($value, '"')) {
        return $key . '="' . $value . '"';
    }

    return $key . '=' . $value;
}

/** @param array<string, string> $updates */
function write_env(string $path, array $updates): void
{
    $lines = is_file($path) ? (file($path, FILE_IGNORE_NEW_LINES) ?: []) : [];
    $out   = [];
    $left  = $updates;

    foreach ($lines as $line) {
        $trim = ltrim($line);
        if ($trim === '' || $trim[0] === '#' || !str_contains($trim, '=')) {
            $out[] = $line;
            continue;
        }
        [$key] = explode('=', $trim, 2);
        $key   = trim($key);
        if (array_key_exists($key, $left)) {
            $out[] = env_line($key, (string) $left[$key]);
            unset($left[$key]);
        } else {
            $out[] = $line;
        }
    }

    if ($lines === []) {
        $out = [];
    }
    foreach ($left as $key => $value) {
        $out[] = env_line($key, (string) $value);
    }

    if (@file_put_contents($path, implode(PHP_EOL, $out) . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Could not write .env. Make the project root writable.');
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $form['db_host'] = trim((string) ($_POST['db_host'] ?? ''));
    $form['db_port'] = trim((string) ($_POST['db_port'] ?? '3306'));
    $form['db_name'] = trim((string) ($_POST['db_name'] ?? ''));
    $form['db_user'] = trim((string) ($_POST['db_user'] ?? ''));
    $form['app_url'] = trim((string) ($_POST['app_url'] ?? ''));

    $submittedPass = (string) ($_POST['db_pass'] ?? '');
    $password      = $submittedPass !== '' ? $submittedPass : $currentPass;
    $mode          = ($_POST['mode'] ?? 'demo') === 'reference' ? 'reference' : 'demo';

    if ($locked) {
        $error = 'The installer is already locked. Delete storage/installed.lock only if you intentionally need to re-run it.';
    } elseif (!$storageOk) {
        $error = 'The storage/ folder is not writable. Set it to 755 or 775, then reload.';
    } elseif (!$envOk) {
        $error = 'The project root / .env is not writable. Fix permissions, then reload.';
    } elseif (!$sqlReady) {
        $error = 'The database/live/ SQL files are missing from the upload.';
    } elseif (!isset($_POST['confirm'])) {
        $error = 'Please tick the confirmation checkbox.';
    } elseif (preg_match('/^[A-Za-z0-9._:-]+$/', $form['db_host']) !== 1) {
        $error = 'The database host looks invalid. Use localhost or the host from cPanel.';
    } elseif (!ctype_digit($form['db_port']) || (int) $form['db_port'] < 1 || (int) $form['db_port'] > 65535) {
        $error = 'The database port must be a number between 1 and 65535.';
    } elseif (preg_match('/^[A-Za-z0-9_]+$/', $form['db_name']) !== 1) {
        $error = 'The database name may only contain letters, digits and underscores.';
    } elseif (preg_match('/^[A-Za-z0-9_]+$/', $form['db_user']) !== 1) {
        $error = 'The database username may only contain letters, digits and underscores.';
    } elseif (str_contains($password, "\n") || str_contains($password, "\r")) {
        $error = 'The database password cannot contain a line break.';
    } elseif (filter_var($form['app_url'], FILTER_VALIDATE_URL) === false
        || preg_match('#^https?://#i', $form['app_url']) !== 1) {
        $error = 'Enter the full site URL, for example https://www.swon.in.';
    } else {
        try {
            // `localhost` uses a socket; `127.0.0.1` forces TCP. Shared hosts
            // differ, so try the submitted host first, then both fallbacks.
            $hosts = array_values(array_unique([$form['db_host'], '127.0.0.1', 'localhost']));
            $pdo = null;
            $usedHost = $form['db_host'];
            $connectionErrors = [];

            foreach ($hosts as $host) {
                try {
                    $pdo = new PDO(
                        sprintf(
                            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                            $host,
                            (int) $form['db_port'],
                            $form['db_name']
                        ),
                        $form['db_user'],
                        $password,
                        [
                            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_TIMEOUT            => 5,
                        ]
                    );
                    $usedHost = $host;
                    break;
                } catch (Throwable $connectionError) {
                    $connectionErrors[$host] = $connectionError->getMessage();
                }
            }

            if ($pdo === null) {
                $detail = $connectionErrors[$form['db_host']] ?? (end($connectionErrors) ?: 'Connection refused');
                throw new RuntimeException(
                    'Could not connect to MySQL at ' . $form['db_host'] . ':' . $form['db_port'] . '. '
                    . $detail
                    . ' Tried hosts: ' . implode(', ', $hosts) . '.'
                    . ' Try Database host 127.0.0.1 (or the exact host from cPanel), port 3306,'
                    . ' and make sure MySQL is running.'
                );
            }

            // Save the host that actually connected.
            $form['db_host'] = $usedHost;

            $updates = [
                'APP_ENV'     => 'production',
                'APP_URL'     => rtrim($form['app_url'], '/'),
                'PWA_ENABLED' => 'true',
                'DB_HOST'     => $form['db_host'],
                'DB_PORT'     => (string) (int) $form['db_port'],
                'DB_NAME'     => $form['db_name'],
                'DB_USER'     => $form['db_user'],
                'DB_PASS'     => $password,
            ];

            if (!is_file($envFile) || (int) @filesize($envFile) === 0) {
                $updates = array_merge([
                    'APP_NAME'             => '"SWON"',
                    'APP_KEY'              => bin2hex(random_bytes(32)),
                    'SESSION_NAME'         => 'swon_session',
                    'OTP_DEV_MODE'         => 'false',
                    'OTP_DELIVERY'         => 'auto',
                    'OTP_DEV_PHONES'       => '',
                    'OTP_TTL_SECONDS'      => '120',
                    'OTP_COOLDOWN_SECONDS' => '60',
                    'OTP_MAX_ATTEMPTS'     => '5',
                    'OTP_MAX_PER_15MIN'    => '5',
                ], $updates);
            } else {
                if ((string) \App\Env::get('APP_KEY', '') === '') {
                    $updates['APP_KEY'] = bin2hex(random_bytes(32));
                }
                if ((string) \App\Env::get('APP_NAME', '') === '') {
                    $updates['APP_NAME'] = '"SWON"';
                }
            }

            $ran = [];
            $ran['schema'] = \App\SqlRunner::applyFile($pdo, $sqlFiles['schema']);
            $ran['seed']   = \App\SqlRunner::applyFile($pdo, $sqlFiles['seed']);
            if ($mode === 'reference') {
                $ran['cleanup'] = \App\SqlRunner::applyFile($pdo, $sqlFiles['remove']);
            }

            // Save .env only after the SQL succeeds, so a failed import never
            // changes the live configuration.
            write_env($envFile, $updates);

            $counts = [];
            foreach (['account', 'job', 'application', 'area', 'job_category'] as $table) {
                $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
            }

            $lockWritten = @file_put_contents($lockFile, 'installed ' . date('c') . PHP_EOL) !== false;
            $locked      = true;
            $success     = ['mode' => $mode, 'counts' => $counts, 'lock' => $lockWritten];
        } catch (Throwable $e) {
            $error = 'Could not finish: ' . $e->getMessage();
        }
    }
}

$countLabels = [
    'account'      => 'Accounts',
    'job'          => 'Opportunities',
    'application'  => 'Flight Requests',
    'area'         => 'Areas',
    'job_category' => 'Categories',
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#F6F3EC">
<title>SWON — Installer</title>
<style>
    :root { --ink:#10201e; --muted:#4f5f5c; --canvas:#f6f3ec; --card:#fff; --line:#e4ddd0; --primary:#0f766e; --primary-dark:#115e59; --danger:#b91c1c; --danger-soft:#fde8e8; --success:#15803d; --success-soft:#e6f4ea; --warning:#b45309; --warning-soft:#fdf1e3; }
    * { box-sizing:border-box; }
    body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; background:radial-gradient(120% 90% at 84% 4%, rgba(194,118,43,.10), rgba(194,118,43,0) 46%), var(--canvas); color:var(--ink); font-family:"Manrope","Segoe UI",system-ui,sans-serif; }
    .card { width:100%; max-width:620px; background:var(--card); border:1px solid var(--line); border-radius:18px; box-shadow:0 18px 40px -16px rgba(16,59,43,.28); padding:30px; }
    .brand { margin-bottom:18px; }
    .brand img { height:32px; width:auto; }
    h1 { margin:0 0 6px; font-size:1.45rem; letter-spacing:-.03em; }
    .sub { margin:0 0 18px; color:var(--muted); font-size:.9rem; line-height:1.55; }
    .grid { display:grid; grid-template-columns:1fr 1fr; gap:13px; }
    .span2 { grid-column:span 2; }
    .field { display:flex; flex-direction:column; gap:5px; }
    label { font-size:.8rem; font-weight:800; }
    .hint { font-size:.74rem; color:var(--muted); font-weight:500; }
    input[type="text"], input[type="password"], input[type="url"], input[type="number"] { width:100%; min-height:46px; padding:10px 13px; border:1.5px solid var(--line); border-radius:11px; font:inherit; background:var(--card); color:var(--ink); }
    input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 4px rgba(15,118,110,.13); }
    fieldset { border:1px solid var(--line); border-radius:11px; padding:12px 14px; margin:16px 0 0; }
    legend { font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); padding:0 6px; }
    label.row { display:flex; align-items:flex-start; gap:9px; font-weight:600; font-size:.85rem; margin:7px 0; }
    label.row input { margin-top:3px; accent-color:var(--primary); }
    button { width:100%; min-height:48px; margin-top:16px; padding:12px 20px; border:0; border-radius:13px; background:var(--primary); color:#fff; font:inherit; font-weight:800; cursor:pointer; }
    button:hover { background:var(--primary-dark); }
    .box { padding:12px 14px; border-radius:11px; margin:14px 0; font-size:.86rem; line-height:1.5; }
    .box-error { background:var(--danger-soft); color:#7f1d1d; border:1px solid #fca5a5; }
    .box-warn { background:var(--warning-soft); color:#8a4b06; border:1px solid #fcd34d; }
    .box-ok { background:var(--success-soft); color:#14522b; border:1px solid #86efac; }
    .counts { width:100%; border-collapse:collapse; margin:14px 0; font-size:.88rem; }
    .counts td { padding:8px 10px; border-bottom:1px solid var(--line); }
    .counts td:last-child { text-align:right; font-weight:800; }
    .actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:16px; }
    .actions a { display:inline-flex; align-items:center; justify-content:center; min-height:44px; padding:10px 18px; border-radius:12px; border:1px solid var(--line); color:var(--ink); text-decoration:none; font-weight:800; }
    .actions a.primary { background:var(--primary); border-color:var(--primary); color:#fff; }
    .foot { margin-top:20px; font-size:.76rem; color:var(--muted); }
    code { background:var(--canvas); padding:1px 5px; border-radius:5px; }
    :focus-visible { outline:3px solid var(--primary); outline-offset:2px; }
    @media (max-width:560px) { .grid { grid-template-columns:1fr; } .span2 { grid-column:span 1; } }
</style>
</head>
<body>
<main class="card">
    <div class="brand"><img src="assets/img/brand/swon-wordmark.png" alt="SWON" width="559" height="161"></div>

    <?php if ($success !== null): ?>
        <h1>Installation complete</h1>
        <p class="sub">The database details were saved to <code>.env</code> and the tables were imported.</p>
        <table class="counts">
            <?php foreach ($countLabels as $key => $label): ?>
                <tr><td><?= $h($label) ?></td><td><?= (int) ($success['counts'][$key] ?? 0) ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php if ($success['mode'] === 'reference'): ?>
            <div class="box box-ok">Demo accounts and opportunities were removed. Reference areas and categories are ready.</div>
        <?php else: ?>
            <div class="box box-ok">Demo data is installed so you can explore the site immediately.</div>
        <?php endif; ?>
        <?php if (!$success['lock']): ?>
            <div class="box box-warn">Could not write <code>storage/installed.lock</code>. Delete <code>public/install.php</code> immediately.</div>
        <?php endif; ?>
        <div class="box box-warn"><strong>Now delete <code>public/install.php</code>.</strong> The installer is locked by <code>storage/installed.lock</code>.</div>
        <div class="actions">
            <a class="primary" href="home">Go to SWON home</a>
            <a href="login">Sign in</a>
        </div>
    <?php elseif ($locked): ?>
        <h1>Already installed</h1>
        <p class="sub">This installer is locked by <code>storage/installed.lock</code>.</p>
        <div class="actions"><a class="primary" href="home">Go to SWON home</a></div>
    <?php else: ?>
        <h1>Database setup</h1>
        <p class="sub">Enter the database details from cPanel. Saving writes them to <code>.env</code> and installs the tables.</p>
        <div class="box box-warn"><strong>No setup key.</strong> Delete <code>public/install.php</code> immediately after installation. The installer locks itself with <code>storage/installed.lock</code>.</div>

        <?php if ($error !== ''): ?>
            <div class="box box-error"><?= $h($error) ?></div>
        <?php endif; ?>

        <form method="post" action="install.php">
            <div class="grid">
                <div class="field">
                    <label for="db_host">Database host</label>
                    <input type="text" id="db_host" name="db_host" value="<?= $h($form['db_host']) ?>" placeholder="localhost" required>
                    <span class="hint">Usually <code>localhost</code> on cPanel.</span>
                </div>
                <div class="field">
                    <label for="db_port">Port</label>
                    <input type="number" id="db_port" name="db_port" value="<?= $h($form['db_port']) ?>" min="1" max="65535" required>
                </div>

                <div class="field">
                    <label for="db_name">Database name</label>
                    <input type="text" id="db_name" name="db_name" value="<?= $h($form['db_name']) ?>" placeholder="u331818589_Swon_webApp" required>
                </div>
                <div class="field">
                    <label for="db_user">Database username</label>
                    <input type="text" id="db_user" name="db_user" value="<?= $h($form['db_user']) ?>" placeholder="u331818589_Swon_webApp" required>
                </div>

                <div class="field span2">
                    <label for="db_pass">Database password</label>
                    <input type="password" id="db_pass" name="db_pass" autocomplete="off" placeholder="<?= $currentPass !== '' ? 'Leave blank to keep the saved password' : 'Database password' ?>">
                    <?php if ($currentPass !== ''): ?><span class="hint">Leave blank to keep the password already in <code>.env</code>.</span><?php endif; ?>
                </div>

                <div class="field span2">
                    <label for="app_url">Site URL</label>
                    <input type="url" id="app_url" name="app_url" value="<?= $h($form['app_url']) ?>" placeholder="https://www.swon.in" required>
                </div>
            </div>

            <fieldset>
                <legend>Data to install</legend>
                <label class="row"><input type="radio" name="mode" value="demo" checked><span><strong>Demo data</strong> — areas, categories, 5 demo accounts, 10 opportunities, 7 Flight Requests.</span></label>
                <label class="row"><input type="radio" name="mode" value="reference"><span><strong>Reference data only</strong> — areas and categories, then remove the demo accounts and opportunities.</span></label>
            </fieldset>

            <label class="row"><input type="checkbox" name="confirm" value="1" required><span>I understand that saving will overwrite the database settings in <code>.env</code> and create tables in <strong><?= $h($form['db_name'] !== '' ? $form['db_name'] : 'the selected database') ?></strong>.</span></label>

            <button type="submit">Save &amp; install</button>
        </form>

        <p class="foot">The installer locks itself after a successful run. Delete <code>public/install.php</code> afterwards.</p>
    <?php endif; ?>
</main>
</body>
</html>
