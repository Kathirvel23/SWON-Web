<?php
declare(strict_types=1);

/**
 * One-time local setup: creates the schema and demo data.
 * Usage: php bin/setup.php
 *
 * Reads DB_* from .env, creates the database if needed, then applies
 * database/schema.sql and database/seed.sql. Safe to re-run.
 */

$root = dirname(__DIR__);

$envPath = $root . '/.env';
if (!is_file($envPath)) {
    fwrite(STDERR, "Missing .env - copy .env.example to .env first.\n");
    exit(1);
}

$values = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $values[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
}

$host = $values['DB_HOST'] ?? '127.0.0.1';
$port = $values['DB_PORT'] ?? '3306';
$name = $values['DB_NAME'] ?? 'swon_web';
$user = $values['DB_USER'] ?? 'root';
$pass = $values['DB_PASS'] ?? '';

if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
    fwrite(STDERR, "DB_NAME may only contain letters, digits and underscores.\n");
    exit(1);
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "Cannot connect to MySQL: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Start MySQL (XAMPP control panel) and check DB_* in .env.\n");
    exit(1);
}

$pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$name`");

/** Execute a .sql file statement by statement (no multi-statement flag needed). */
$applyFile = static function (PDO $pdo, string $path): void {
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException("Cannot read $path");
    }

    // Drop full-line comments, then split on semicolons outside quotes.
    $lines = [];
    foreach (preg_split('/\R/', $sql) ?: [] as $line) {
        if (preg_match('/^\s*--/', $line) === 1) {
            continue;
        }
        $lines[] = $line;
    }

    $statements = [];
    $buffer     = '';
    $inString   = false;
    $quote      = '';

    foreach (str_split(implode("\n", $lines)) as $char) {
        if ($inString) {
            $buffer .= $char;
            if ($char === $quote && !str_ends_with($buffer, '\\' . $quote)) {
                $inString = false;
            }
            continue;
        }
        if ($char === "'" || $char === '"') {
            $inString = true;
            $quote    = $char;
            $buffer  .= $char;
            continue;
        }
        if ($char === ';') {
            $statement = trim($buffer);
            if ($statement !== '' && !preg_match('/^(USE|CREATE DATABASE)\b/i', $statement)) {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }
        $buffer .= $char;
    }

    $statement = trim($buffer);
    if ($statement !== '' && !preg_match('/^(USE|CREATE DATABASE)\b/i', $statement)) {
        $statements[] = $statement;
    }

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
};

foreach (['schema.sql', 'seed.sql'] as $file) {
    echo "Applying $file ...\n";
    $applyFile($pdo, $root . '/database/' . $file);
}

$counts = [
    'accounts'     => (int) $pdo->query('SELECT COUNT(*) FROM account')->fetchColumn(),
    'jobs'         => (int) $pdo->query('SELECT COUNT(*) FROM job')->fetchColumn(),
    'applications' => (int) $pdo->query('SELECT COUNT(*) FROM application')->fetchColumn(),
];

echo "\nDone. Database `$name` is ready.\n";
echo "  accounts     : {$counts['accounts']}\n";
echo "  jobs         : {$counts['jobs']}\n";
echo "  applications : {$counts['applications']}\n\n";
echo "Start the app:  php -S 127.0.0.1:8080 -t public public/router.php\n";
echo "Then open:      http://127.0.0.1:8080\n";
echo "Demo numbers:   9000000001 (worker), 9000000002 (host)\n";
