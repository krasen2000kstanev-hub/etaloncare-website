<?php
declare(strict_types=1);

// Global helper functions (loaded from bootstrap.php).

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

/**
 * Base path of the deployed app (the URL path part of app.url).
 * Empty for root deployments, "/projects/etaloncare" for subdir deployments.
 */
function basePath(): string
{
    static $cached = null;
    if ($cached !== null) return $cached;
    $url  = (string) ($GLOBALS['__config']['app']['url'] ?? '');
    $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
    return $cached = rtrim($path, '/');
}

/** Full URL with host & base, from config (e.g. https://enter.bg/projects/etaloncare). */
function appUrl(): string
{
    return rtrim((string) ($GLOBALS['__config']['app']['url'] ?? ''), '/');
}

/** App-rooted URL — prepends base path. Use for HTML hrefs/srcs and form actions. */
function publicUrl(string $path = ''): string
{
    return basePath() . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

function adminUrl(string $path = ''): string
{
    return basePath() . '/admin' . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

function apiUrl(string $path = ''): string
{
    return basePath() . '/api' . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

/** Asset URL with cache-busting `?v=N`. */
function assetUrl(string $path): string
{
    return \App\Cache::assetUrl($path);
}

function old(string $key, ?string $default = null): ?string
{
    return $_SESSION['_old'][$key] ?? $default;
}

function setOld(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clearOld(): void
{
    unset($_SESSION['_old']);
}
