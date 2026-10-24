<?php
declare(strict_types=1);

namespace App;

/**
 * Runs unrun *.sql files from app/Migrations/ in alphabetical order.
 * Tracks applied migrations in the `migrations` table. Idempotent.
 */
final class Migrate
{
    public static function run(): array
    {
        Db::execute("CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(190) NOT NULL UNIQUE,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $applied = array_column(
            Db::fetchAll('SELECT filename FROM migrations'),
            'filename'
        );

        $files = glob(APP_ROOT . '/Migrations/*.sql') ?: [];
        sort($files);

        $ran = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) continue;

            $sql = (string) file_get_contents($file);
            $statements = array_filter(
                array_map('trim', preg_split('/;\s*\R+/', $sql) ?: []),
                'strlen'
            );

            // DO NOT wrap in a DB transaction — MySQL DDL (CREATE/ALTER/DROP)
            // implicitly commits, which invalidates any surrounding transaction
            // and makes commit()/rollBack() throw "no active transaction".
            //
            // We tolerate "already exists" / "duplicate column" errors so a
            // partial previous run can be safely retried.
            foreach ($statements as $s) {
                try {
                    Db::execute($s);
                } catch (\Throwable $e) {
                    $msg = $e->getMessage();
                    if (preg_match('/Duplicate column name|already exists|Duplicate key name/i', $msg)) {
                        continue;
                    }
                    throw new \RuntimeException("Migration {$name} failed: " . $msg, 0, $e);
                }
            }

            Db::execute('INSERT INTO migrations (filename) VALUES (?)', [$name]);
            $ran[] = $name;
        }
        return $ran;
    }
}
