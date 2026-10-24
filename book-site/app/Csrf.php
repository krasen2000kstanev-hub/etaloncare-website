<?php
declare(strict_types=1);

namespace App;

final class Csrf
{
    public static function token(): string
    {
        $t = Session::get('csrf_token');
        if (!is_string($t) || strlen($t) < 32) {
            $t = bin2hex(random_bytes(32));
            Session::set('csrf_token', $t);
        }
        return $t;
    }

    public static function check(?string $submitted): bool
    {
        $expected = Session::get('csrf_token');
        if (!is_string($expected) || !is_string($submitted)) return false;
        return hash_equals($expected, $submitted);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;
        if (!self::check($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            header('Content-Type: text/plain; charset=utf-8');
            echo "419 — Невалиден CSRF token. Опитайте отново.";
            exit;
        }
    }
}
