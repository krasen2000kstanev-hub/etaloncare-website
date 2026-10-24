<?php
declare(strict_types=1);

namespace App;

/**
 * Tiny PHP-template renderer. Each view captures into $content and includes
 * the layout at the bottom — keeps things explicit without a templating engine.
 */
final class View
{
    private static string $base = '';

    public static function setBase(string $path): void
    {
        self::$base = rtrim($path, '/');
    }

    public static function display(string $__template, array $__data = []): void
    {
        // Params are underscore-prefixed so a view's data key (e.g. 'data', 'template')
        // cannot collide with them — extract(EXTR_SKIP) would otherwise silently drop it.
        extract($__data, EXTR_SKIP);
        require self::$base . '/' . $__template . '.php';
    }
}
