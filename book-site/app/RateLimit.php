<?php
declare(strict_types=1);

namespace App;

/**
 * Tiny DB-backed sliding-window rate limiter. Counts hits in the rate_limits
 * table per (bucket, ip) within a window. Periodically prunes old entries.
 */
final class RateLimit
{
    public static function check(string $bucket, string $ip, int $max, int $windowSeconds): bool
    {
        // Prune old (cheap, runs only on a fraction of calls)
        if (random_int(1, 50) === 1) {
            Db::execute(
                'DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)',
                [$windowSeconds * 4]
            );
        }
        $count = (int) (Db::fetchOne(
            'SELECT COUNT(*) c FROM rate_limits
             WHERE bucket = ? AND ip = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$bucket, $ip, $windowSeconds]
        )['c'] ?? 0);
        if ($count >= $max) return false;
        Db::execute(
            'INSERT INTO rate_limits (bucket, ip) VALUES (?, ?)',
            [$bucket, $ip]
        );
        return true;
    }

    public static function clientIp(): string
    {
        // Trust proxy headers only behind a known reverse proxy. On shared
        // hosting REMOTE_ADDR is generally accurate; check forwarded only if set
        // and when the host explicitly trusts it.
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
