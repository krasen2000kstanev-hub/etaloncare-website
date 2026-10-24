<?php
declare(strict_types=1);

namespace App;

final class Auth
{
    private const RATE_LIMIT_MAX = 8;            // failed attempts within window
    private const RATE_LIMIT_WINDOW = 600;       // 10 minutes
    private const DUMMY_HASH = '$2y$12$6Z6w2qXmU5KeqQ3kFqVvO.ZzJ0bX.JHpZpXm5g7PpAH9GU2mVgq6S';

    public static function user(): ?array
    {
        $id = Session::get('user_id');
        if (!$id) return null;
        return Db::fetchOne(
            'SELECT id, email, name, role, last_login_at FROM users WHERE id = ?',
            [(int) $id]
        );
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function attempt(string $email, string $password): bool
    {
        if (self::isRateLimited()) return false;

        $user = Db::fetchOne(
            'SELECT id, password_hash FROM users WHERE email = ? LIMIT 1',
            [$email]
        );
        if (!$user) {
            // mitigate timing attacks — always perform a verify
            password_verify($password, self::DUMMY_HASH);
            self::recordFailure();
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            self::recordFailure();
            return false;
        }

        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        self::clearFailures();

        Db::execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Db::execute(
                'UPDATE users SET password_hash = ? WHERE id = ?',
                [self::hash($password), $user['id']]
            );
        }
        return true;
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Session::set('redirect_after_login', $_SERVER['REQUEST_URI'] ?? adminUrl());
            redirect(adminUrl('login'));
        }
    }

    /**
     * Requires the logged-in user to have one of the given roles. Renders 403
     * if not. Use `Auth::requireRole('admin')` at the top of admin-only routes.
     */
    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        $user = self::user();
        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            header('Content-Type: text/html; charset=utf-8');
            $cssHref = htmlspecialchars(assetUrl('admin/assets/admin.css'), ENT_QUOTES);
            $back    = htmlspecialchars(adminUrl('dashboard'), ENT_QUOTES);
            echo '<!DOCTYPE html><html lang="bg"><head><meta charset="UTF-8"><title>403</title>'
               . '<link rel="stylesheet" href="' . $cssHref . '"></head>'
               . '<body><main class="container narrow"><div class="card auth-card">'
               . '<h1>403 — Нямате достъп</h1>'
               . '<p class="muted">Тази страница е достъпна само за администратори.</p>'
               . '<a class="btn btn-primary" href="' . $back . '">Към таблото</a>'
               . '</div></main></body></html>';
            exit;
        }
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && $u['role'] === 'admin';
    }

    public static function is(string $role): bool
    {
        $u = self::user();
        return $u !== null && $u['role'] === $role;
    }

    public static function hash(string $password): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID);
        }
        return password_hash($password, PASSWORD_DEFAULT);
    }

    private static function isRateLimited(): bool
    {
        $key = '_login_failures';
        $f = Session::get($key, ['count' => 0, 'first' => 0]);
        if ($f['count'] >= self::RATE_LIMIT_MAX
            && (time() - $f['first']) < self::RATE_LIMIT_WINDOW) {
            return true;
        }
        return false;
    }

    private static function recordFailure(): void
    {
        $key = '_login_failures';
        $f = Session::get($key, ['count' => 0, 'first' => 0]);
        if ((time() - $f['first']) > self::RATE_LIMIT_WINDOW) {
            $f = ['count' => 0, 'first' => time()];
        }
        $f['count']++;
        Session::set($key, $f);
    }

    private static function clearFailures(): void
    {
        Session::forget('_login_failures');
    }
}
