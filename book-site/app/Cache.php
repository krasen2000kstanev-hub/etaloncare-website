<?php
declare(strict_types=1);

namespace App;

/**
 * Cache busting + cache clearing.
 *
 * Strategy: every static asset URL gets `?v=<version>` appended. The version
 * is an integer stored in `settings`. Bumping it forces browsers to refetch
 * (because the URL changes), which is the only reliable way to invalidate
 * client-side caches on shared hosting where we can't push cache-control
 * directives per file.
 *
 * `bust()` also clears server-side caches (Speedy office cache, PHP opcache).
 */
final class Cache
{
    private const SETTING_KEY = 'cache_version';

    public static function version(): int
    {
        static $cached = null;
        if ($cached !== null) return $cached;
        $v = Settings::get(self::SETTING_KEY);
        return $cached = ($v !== null && $v !== '') ? (int) $v : 1;
    }

    /**
     * Clear all caches. Returns a summary suitable for showing in a flash message.
     */
    public static function bust(): array
    {
        $newVersion = self::version() + 1;
        Settings::set(self::SETTING_KEY, (string) $newVersion);

        $speedyDeleted = Db::execute('DELETE FROM speedy_offices');

        $opcacheCleared = false;
        if (function_exists('opcache_reset')) {
            $opcacheCleared = (bool) @opcache_reset();
        }

        return [
            'version'         => $newVersion,
            'speedy_offices'  => $speedyDeleted,
            'opcache_cleared' => $opcacheCleared,
            'opcache_avail'   => function_exists('opcache_reset'),
        ];
    }

    /**
     * App-rooted URL for an asset, with cache-busting query string.
     * Use for: styles.css, JSX files, admin.css, default /assets/*.
     */
    public static function assetUrl(string $path): string
    {
        $url = publicUrl($path);
        return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . self::version();
    }
}
