<?php
declare(strict_types=1);

namespace App;

/**
 * Minimal, dependency-free SQL file runner used by bin/setup.php and the
 * one-time web installer. It understands the SQL shipped in database/live/:
 * full-line "--" comments and semicolon-separated statements, including
 * semicolons inside quoted strings.
 */
final class SqlRunner
{
    /** Apply a .sql file. Returns the number of statements executed. */
    public static function applyFile(\PDO $pdo, string $path): int
    {
        $sql = @file_get_contents($path);
        if ($sql === false) {
            throw new \RuntimeException('Cannot read SQL file: ' . basename($path));
        }

        return self::apply($pdo, $sql);
    }

    /** Apply raw SQL. Returns the number of statements executed. */
    public static function apply(\PDO $pdo, string $sql): int
    {
        $count = 0;
        foreach (self::split($sql) as $statement) {
            // Shared hosting already created the database; never try to
            // create or switch it from the app.
            if (preg_match('/^(USE|CREATE\s+DATABASE)\b/i', $statement) === 1) {
                continue;
            }
            $pdo->exec($statement);
            $count++;
        }

        return $count;
    }

    /** @return list<string> */
    private static function split(string $sql): array
    {
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
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }
            $buffer .= $char;
        }

        $statement = trim($buffer);
        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }
}
