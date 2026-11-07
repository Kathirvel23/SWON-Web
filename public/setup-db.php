<?php
declare(strict_types=1);
/**
 * SWON — easy database setup (one file, no dependencies)
 * ------------------------------------------------------------------
 *   open  https://yourdomain/setup-db.php
 *   type the database details cPanel gave you → Test → Save
 *
 * Save does everything: writes .env (the old one is backed up), creates the
 * tables from database/live/schema.sql and locks this page.
 * To open it again later, delete storage/install.lock. Delete this file
 * once the site is live.
 */
$ROOT = dirname(__DIR__);
$ENV  = $ROOT . '/.env';
$EX   = $ROOT . '/.env.example';
$LOCK = $ROOT . '/storage/install.lock';
$LOGD = $ROOT . '/storage/logs';

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

function h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function env_read(string $p): array {
    $out = [];
    if (!is_file($p)) return $out;
    foreach (preg_split('/\r\n|\r|\n/', (string) file_get_contents($p)) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        if (strlen($v) > 1 && (($v[0] === '"' && str_ends_with($v, '"')) || ($v[0] === "'" && str_ends_with($v, "'")))) {
            $v = substr($v, 1, -1);
        }
        $out[$k] = $v;
    }
    return $out;
}

function env_write(string $envPath, string $template, array $values): ?string {
    $src = is_file($envPath) ? (string) file_get_contents($envPath) : (is_file($template) ? (string) file_get_contents($template) : "# SWON environment\n");
    $lines = preg_split('/\r\n|\r|\n/', $src) ?: [];
    $seen = [];
    foreach ($lines as $i => $line) {
        $t = ltrim($line);
        if ($t === '' || $t[0] === '#' || !str_contains($t, '=')) continue;
        $k = trim(explode('=', $t, 2)[0]);
        if (array_key_exists($k, $values)) {
            $v = (string) $values[$k];
            $lines[$i] = $k . '=' . (($v !== '' && preg_match('/[\s#"\'$]/', $v)) ? '"' . str_replace('"', '\\"', $v) . '"' : $v);
            $seen[$k] = true;
        }
    }
    $extra = array_diff_key($values, $seen);
    if ($extra !== []) {
        $lines[] = ''; $lines[] = '# ---- written by setup-db.php ----';
        foreach ($extra as $k => $v) $lines[] = $k . '=' . $v;
    }
    if (is_file($envPath)) @copy($envPath, $envPath . '.bak-' . date('Ymd-His'));
    if (@file_put_contents($envPath, rtrim(implode(PHP_EOL, $lines)) . PHP_EOL, LOCK_EX) === false) return null;
    @chmod($envPath, 0600);
    return (string) file_get_contents($envPath);
}

function db_connect(string $host, string $port, string $db, string $user, string $pass): PDO {
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4" . ($db !== '' ? ";dbname=$db" : '');
    return new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 8]);
}

/** split a phpMyAdmin-style dump into statements (quote/comment aware) */
function sql_split(string $sql): array {
    $out = []; $buf = ''; $q = null; $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i]; $n = $i + 1 < $len ? $sql[$i + 1] : '';
        if ($q === null) {
            if (($c === '-' && $n === '-') || $c === '#') { while ($i < $len && $sql[$i] !== "\n") $i++; continue; }
            if ($c === '/' && $n === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $inner = $end === false ? '' : substr($sql, $i + 2, $end - $i - 2);
                if ($inner !== '' && $inner[0] === '!') $buf .= preg_replace('/^!\d*\s*/', '', $inner);
                $i = $end === false ? $len : $end + 1; continue;
            }
            if ($c === "'" || $c === '"' || $c === '`') { $q = $c; $buf .= $c; continue; }
            if ($c === ';') { $s = trim($buf); if ($s !== '') $out[] = $s; $buf = ''; continue; }
            $buf .= $c; continue;
        }
        if ($c === '\\' && $q !== '`') { $buf .= $c . $n; $i++; continue; }
        if ($c === $q) $q = null;
        $buf .= $c;
    }
    if (trim($buf) !== '') $out[] = trim($buf);
    return $out;
}

function db_import(PDO $pdo, string $file): array {
    $n = 0;
    foreach (sql_split((string) file_get_contents($file)) as $stmt) {
        try { $pdo->exec($stmt); $n++; }
        catch (Throwable $e) { return ['ok' => false, 'n' => $n, 'err' => $e->getMessage(), 'stmt' => mb_substr(preg_replace('/\s+/', ' ', $stmt), 0, 140)]; }
    }
    return ['ok' => true, 'n' => $n, 'err' => '', 'stmt' => ''];
}

function tables(PDO $pdo): array { try { return $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: []; } catch (Throwable $e) { return []; } }

/* ------------------------------------------------------------------ state */
session_name('swon_setup'); session_start();
$cur  = env_read($ENV);
$csrf = $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
$locked = is_file($LOCK);
$keyOk  = $locked && !empty($cur['SETUP_KEY']) && hash_equals((string) $cur['SETUP_KEY'], (string) ($_GET['key'] ?? $_POST['setup_key'] ?? ''));
$canRun = !$locked || $keyOk;

$f = [
    'APP_NAME' => $cur['APP_NAME'] ?? '"SWON"',
    'APP_ENV'  => $cur['APP_ENV'] ?? 'production',
    'APP_URL'  => $cur['APP_URL'] ?? (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost')),
    'DB_HOST'  => $cur['DB_HOST'] ?? 'localhost',
    'DB_PORT'  => $cur['DB_PORT'] ?? '3306',
    'DB_NAME'  => $cur['DB_NAME'] ?? '',
    'DB_USER'  => $cur['DB_USER'] ?? '',
    'OTP_DEV_PHONES' => $cur['OTP_DEV_PHONES'] ?? '',
];
$hasPass = ($cur['DB_PASS'] ?? '') !== '';
$flash = null; $preview = '';
$schemaFile = is_file($ROOT . '/database/live/schema.sql') ? $ROOT . '/database/live/schema.sql' : $ROOT . '/database/schema.sql';
$seedFile   = is_file($ROOT . '/database/live/seed.sql') ? $ROOT . '/database/live/seed.sql' : $ROOT . '/database/seed.sql';

/* ------------------------------------------------------------------- post */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canRun) {
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        $flash = ['err', 'Session expired', ['Reload the page and try again.']];
    } else {
        foreach (array_keys($f) as $k) if (isset($_POST[$k])) $f[$k] = trim((string) $_POST[$k]);
        $pass = (string) ($_POST['DB_PASS'] ?? '');
        if ($pass === '' && $hasPass) $pass = (string) $cur['DB_PASS'];
        $action = (string) ($_POST['action'] ?? 'save');
        $steps = []; $pdo = null; $fail = false;

        try { $pdo = db_connect($f['DB_HOST'], $f['DB_PORT'], $f['DB_NAME'], $f['DB_USER'], $pass); $steps[] = ['ok', 'Connected to ' . $f['DB_HOST'] . ':' . $f['DB_PORT'] . ' as ' . $f['DB_USER']]; }
        catch (Throwable $e) {
            if (isset($_POST['create_db']) && stripos($e->getMessage(), 'Unknown database') !== false) {
                try {
                    db_connect($f['DB_HOST'], $f['DB_PORT'], '', $f['DB_USER'], $pass)
                        ->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $f['DB_NAME']) . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                    $pdo = db_connect($f['DB_HOST'], $f['DB_PORT'], $f['DB_NAME'], $f['DB_USER'], $pass);
                    $steps[] = ['ok', 'Database ' . $f['DB_NAME'] . ' created'];
                } catch (Throwable $e2) { $fail = true; $steps[] = ['err', 'Could not create the database: ' . $e2->getMessage()]; }
            } else { $fail = true; $steps[] = ['err', 'Connection failed: ' . $e->getMessage()]; }
        }

        if (!$fail && $pdo instanceof PDO) {
            $steps[] = ['ok', count(tables($pdo)) . ' tables found in ' . $f['DB_NAME']];
            foreach ([['do_schema', $schemaFile, 'tables (schema.sql)'], ['do_seed', $seedFile, 'demo data (seed.sql)']] as [$field, $file, $label]) {
                if ($fail || !isset($_POST[$field]) || !is_file($file)) continue;
                $r = db_import($pdo, $file);
                if ($r['ok']) $steps[] = ['ok', $label . ': ' . $r['n'] . ' statements applied'];
                else { $fail = true; $steps[] = ['err', $label . ' stopped after ' . $r['n'] . ': ' . $r['err']]; $steps[] = ['warn', 'statement: ' . $r['stmt']]; }
            }
            if (!$fail) $steps[] = ['ok', count(tables($pdo)) . ' tables now: ' . implode(', ', array_slice(tables($pdo), 0, 9))];
        }

        if (!$fail && $action === 'save') {
            $written = env_write($ENV, $EX, array_map('strval', [
                'APP_NAME' => $f['APP_NAME'] !== '' ? $f['APP_NAME'] : '"SWON"',
                'APP_ENV'  => $f['APP_ENV'] === 'local' ? 'local' : 'production',
                'APP_URL'  => rtrim($f['APP_URL'], '/'),
                'DB_HOST'  => $f['DB_HOST'], 'DB_PORT' => $f['DB_PORT'], 'DB_NAME' => $f['DB_NAME'],
                'DB_USER'  => $f['DB_USER'], 'DB_PASS'  => $pass,
                'SESSION_NAME' => $cur['SESSION_NAME'] ?? 'swon_session',
                'PWA_ENABLED' => 'true',
                'OTP_DEV_MODE' => $cur['OTP_DEV_MODE'] ?? 'false',
                'OTP_DELIVERY' => $cur['OTP_DELIVERY'] ?? 'auto',
                'OTP_DEV_PHONES' => $f['OTP_DEV_PHONES'],
                'APP_KEY' => (isset($cur['APP_KEY']) && strlen((string) $cur['APP_KEY']) >= 32 && !str_starts_with((string) $cur['APP_KEY'], 'change-me'))
                    ? (string) $cur['APP_KEY'] : bin2hex(random_bytes(32)),
            ]));
            if ($written === null) { $fail = true; $steps[] = ['err', '.env could not be written — the folder is not writable.']; }
            else {
                $preview = preg_replace(['/^(DB_PASS=).*$/m', '/^(APP_KEY=).*$/m'], ['$1********', '$1 (hidden)'], $written);
                $steps[] = ['ok', '.env written (old file backed up)'];
                @mkdir(dirname($LOCK), 0775, true);
                @file_put_contents($LOCK, 'installed ' . date('c') . PHP_EOL);
                $steps[] = ['ok', 'Page locked (storage/install.lock)'];
                if (($cur['APP_KEY'] ?? '') === '' || strlen((string) $cur['APP_KEY']) < 32) $steps[] = ['warn', 'A new APP_KEY was generated — keep this .env file safe.'];
                if ($f['APP_ENV'] !== 'production') $steps[] = ['warn', 'APP_ENV is not production — errors will be visible to visitors.'];
                if ($f['OTP_DEV_PHONES'] === '') $steps[] = ['warn', 'No mobile number set: on a live site nobody (not even you) can read a login code until SMS is connected.'];
                $locked = is_file($LOCK);
            }
        }
        $flash = [$fail ? 'err' : 'ok', $action === 'save' ? ($fail ? 'Finished with problems' : 'Saved — the site is ready') : ($fail ? 'Connection test failed' : 'Connection test passed'), array_map(fn($s) => $s[1], $steps), array_map(fn($s) => $s[0], $steps)];
    }
}
$req = [
    ['PHP 8.1+', PHP_VERSION_ID >= 80100, PHP_VERSION],
    ['pdo_mysql', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'loaded' : 'missing'],
    ['storage/ writable', is_writable($ROOT . '/storage'), 'chmod 775 if not'],
    ['schema.sql', is_file($schemaFile), is_file($schemaFile) ? 'found' : 'missing'],
    ['.env writable', is_file($ENV) ? is_writable($ENV) : is_writable($ROOT), is_file($ENV) ? 'found' : 'will be created'],
];
@mkdir($LOGD, 0775, true);
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex"><title>SWON — database setup</title>
<style>
:root{--c:#F6F3EC;--gd:#0B4540;--t:#0F766E;--ink:#10201E;--muted:#4F5F5C;--line:#E4DDD0;--err:#B4232B}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at 50% 0%,#FFFDF8,#F6F3EC 55%,#EDF4EB 100%);
 color:var(--ink);font:15px/1.55 Manrope,Inter,system-ui,"Segoe UI",sans-serif;padding-bottom:92px}
.w{max-width:860px;margin:0 auto;padding:26px 20px 40px}
img{width:128px;height:auto;display:block;margin-bottom:16px}
h1{font-size:clamp(21px,3vw,28px);color:var(--gd);margin:0 0 6px}p.lede{color:var(--muted);margin:0 0 20px;max-width:70ch}
.card{background:rgba(255,253,248,.94);border:1px solid var(--line);border-radius:16px;padding:18px;margin-bottom:14px;box-shadow:0 8px 28px rgba(11,69,64,.05)}
h2{font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#115E59;margin:0 0 12px}
ul.ck{list-style:none;margin:0;padding:0;display:grid;gap:6px;font-size:14px}ul.ck .ok{color:#0F766E}ul.ck .no{color:var(--err)}
.g{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px}
label span{display:block;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:4px}
input,select{width:100%;padding:11px 12px;border:1px solid var(--line);border-radius:10px;background:#fff;font:inherit;color:inherit}
input:focus,select:focus{outline:3px solid rgba(15,118,110,.35);border-color:var(--t)}
.hint{font-size:12px;color:var(--muted);margin-top:4px;display:block}
.chk{display:grid;gap:8px;margin-top:14px;font-size:14px}.chk label{display:flex;gap:9px;align-items:flex-start}.chk input{margin-top:3px;width:auto}
.bar{position:fixed;left:0;right:0;bottom:0;background:rgba(246,243,236,.96);border-top:1px solid var(--line);box-shadow:0 -6px 22px rgba(11,69,64,.07)}
.bar div{max-width:860px;margin:0 auto;padding:12px 20px;display:flex;gap:10px;align-items:center;flex-wrap:wrap}
button{border-radius:999px;padding:12px 20px;font:inherit;font-weight:700;cursor:pointer;border:1px solid var(--gd);white-space:nowrap}
.p{background:var(--gd);color:#fff}.p:hover{background:#0d534c}.g{background:transparent;color:var(--gd)}
.note{margin-left:auto;font-size:12px;color:var(--muted)}
.f{border:1px solid var(--line);background:#fff;border-radius:14px;padding:14px 16px;margin-bottom:14px}
.f.ok{border-color:rgba(15,118,110,.35);background:rgba(15,118,110,.06)}.f.err{border-color:rgba(180,35,43,.35);background:rgba(180,35,43,.05)}
.f h3{margin:0 0 8px;font-size:15px}.f ul{margin:0;padding-left:18px}.f li{margin:2px 0;word-break:break-word}
li.warn{color:#B45309;font-weight:600}
pre{background:#0B4540;color:#EAF4EF;border-radius:12px;padding:14px;max-height:300px;overflow:auto;font-size:12.5px}
a.btn{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:12px 20px;background:var(--gd);color:#fff;text-decoration:none;font-weight:700}
</style></head><body>
<div class="w">
<img src="assets/img/brand/swon-wordmark.png" alt="SWON">
<h1>Database setup</h1>

<?php if (!$canRun): ?>
  <p class="lede">This site is already configured, so setup is locked.</p>
  <div class="card"><h2>To change it again</h2>
    <p style="margin:0">Delete <code>storage/install.lock</code> in your host's File Manager, then reload this page.
    When the site is live and working, delete <code>setup-db.php</code>. Open with
    <code>setup-db.php?key=YOUR_SETUP_KEY</code> if you kept the key.</p></div>
<?php else: ?>

<p class="lede"><?= $flash ? 'Setup result below.' : 'Type the database details your host gave you, test them, then save. Nothing is written until you press <strong>Save &amp; install</strong>.' ?></p>

<?php if ($flash): [$kind, $title, $lines, $kinds] = array_pad($flash, 4, []); ?>
  <div class="f <?= $kind === 'err' ? 'err' : 'ok' ?>">
    <h3><?= h($title) ?></h3>
    <ul><?php foreach ($lines as $i => $l): ?><li class="<?= ($kinds[$i] ?? '') === 'warn' ? 'warn' : '' ?>"><?= ($kinds[$i] ?? '') === 'warn' ? '⚠ ' : '' ?><?= h($l) ?></li><?php endforeach; ?></ul>
    <?php if ($preview !== ''): ?><h3 style="margin-top:12px">.env</h3><pre><?= h($preview) ?></pre>
      <p style="margin:12px 0 0"><a class="btn" href="./home">Open the site — /home</a></p><?php endif; ?>
  </div>
<?php endif; ?>

<?php if (!$flash || $flash[0] === 'err'): ?>
  <div class="card"><h2>Server check</h2>
    <ul class="ck"><?php foreach ($req as [$n, $ok, $note]): ?>
      <li class="<?= $ok ? 'ok' : 'no' ?>"><?= $ok ? '✓' : '✗' ?> <?= h($n) ?> <span class="hint" style="display:inline">— <?= h($note) ?></span></li>
    <?php endforeach; ?></ul></div>

  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <div class="card"><h2>Database</h2>
      <div class="g">
        <label><span>Host</span><input name="DB_HOST" value="<?= h($f['DB_HOST']) ?>"><span class="hint">usually localhost</span></label>
        <label><span>Port</span><input name="DB_PORT" value="<?= h($f['DB_PORT']) ?>"></label>
        <label><span>Database name</span><input name="DB_NAME" value="<?= h($f['DB_NAME']) ?>" placeholder="u331818589_Swon_webApp"><span class="hint">exactly as cPanel shows it</span></label>
        <label><span>Database user</span><input name="DB_USER" value="<?= h($f['DB_USER']) ?>" placeholder="u331818589_Swon_webApp"></label>
        <label><span>Database password</span><input type="password" name="DB_PASS" value="" placeholder="<?= $hasPass ? 'keep the saved password' : 'database password' ?>"><span class="hint"><?= $hasPass ? 'leave blank to keep the current one' : 'stored in .env (permissions 600)' ?></span></label>
      </div>
      <div class="chk">
        <label><input type="checkbox" name="create_db" value="1" checked><span>Create the database if it does not exist</span></label>
        <label><input type="checkbox" name="do_schema" value="1" checked><span>Create the tables (schema.sql)</span></label>
        <label><input type="checkbox" name="do_seed" value="1" checked><span>Load demo areas, categories and sample accounts</span></label>
      </div>
    </div>
    <div class="card"><h2>Site</h2>
      <div class="g">
        <label><span>Site URL</span><input name="APP_URL" value="<?= h($f['APP_URL']) ?>"></label>
        <label><span>Mode</span><select name="APP_ENV">
          <option value="production" <?= $f['APP_ENV'] === 'production' ? 'selected' : '' ?>>production — errors hidden</option>
          <option value="local" <?= $f['APP_ENV'] === 'local' ? 'selected' : '' ?>>local — errors shown (dev)</option></select></label>
        <label><span>Your mobile (10 digits)</span><input name="OTP_DEV_PHONES" value="<?= h($f['OTP_DEV_PHONES']) ?>" placeholder="9876543210"><span class="hint">only this number sees its login code on the live site</span></label>
      </div>
    </div>
    <div class="bar"><div>
      <button class="p" type="submit" name="action" value="save">Save &amp; install</button>
      <button class="g" type="submit" name="action" value="test">Test connection</button>
      <span class="note">The old .env is backed up before anything is replaced.</span>
    </div></div>
  </form>
<?php endif; ?>
<?php endif; ?>
</div></body></html>
