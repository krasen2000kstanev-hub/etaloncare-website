<?php
declare(strict_types=1);

namespace App;

/**
 * Media slots — each slot is a single image (logo, cover, etc.). Admin
 * uploads replace the file. Public site reads slot → URL via Media::exportPublic().
 */
final class Media
{
    public const SLOTS = [
        'logo'       => ['label' => 'Лого',                'default' => '/assets/logo.png'],
        'cover'      => ['label' => 'Корица на книгата',   'default' => '/assets/cover.png'],
        'author'     => ['label' => 'Снимка на автора',    'default' => '/assets/author.png'],
        'background' => ['label' => 'Фоново изображение',  'default' => '/assets/background.jpg'],
    ];

    public const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
    ];

    public const MAX_BYTES = 5 * 1024 * 1024;     // 5 MB
    private const UPLOAD_DIR = '/uploads/media';   // web-relative

    public static function isSlot(string $slot): bool
    {
        return isset(self::SLOTS[$slot]);
    }

    /** @return array{path:string,original:?string,mime:?string,size:?int}|null */
    public static function get(string $slot): ?array
    {
        $row = Db::fetchOne('SELECT * FROM media WHERE slot = ?', [$slot]);
        if (!$row) return null;
        return [
            'path'     => $row['file_path'],
            'original' => $row['original_name'] ?? null,
            'mime'     => $row['mime_type'] ?? null,
            'size'     => isset($row['file_size']) ? (int) $row['file_size'] : null,
        ];
    }

    /**
     * App-rooted path for the slot (no base, no host) — e.g. "/uploads/media/foo.png".
     * Used internally; consumers usually want urlFor() or absoluteUrlFor().
     */
    public static function pathFor(string $slot): string
    {
        $row = self::get($slot);
        if ($row) return $row['path'];
        return self::SLOTS[$slot]['default'] ?? '';
    }

    /** URL for use in HTML on the same site (includes base path). */
    public static function urlFor(string $slot): string
    {
        return basePath() . self::pathFor($slot);
    }

    /** Absolute URL with host — for emails, og:image, etc. */
    public static function absoluteUrlFor(string $slot): string
    {
        return appUrl() . self::pathFor($slot);
    }

    /**
     * Save uploaded $_FILES entry into the slot. Replaces existing file.
     * Throws on validation failure.
     */
    public static function upload(string $slot, array $file): void
    {
        if (!self::isSlot($slot)) {
            throw new \InvalidArgumentException("Unknown slot: $slot");
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_OK) {
            throw new \RuntimeException(self::uploadErrorMessage((int) $file['error']));
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Невалиден upload.');
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new \RuntimeException('Файлът е твърде голям (макс. 5MB).');
        }

        // Detect MIME from contents (don't trust client-provided type)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new \RuntimeException("Неподдържан тип файл: $mime. Позволени: jpg, png, webp, svg.");
        }
        $ext = self::ALLOWED_MIME[$mime];

        // Ensure upload dir exists
        $absDir = PROJECT_ROOT . self::UPLOAD_DIR;
        if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
            throw new \RuntimeException('Не мога да създам директория за качване: ' . $absDir);
        }

        // Sanitized filename: <slot>-<random>.<ext>
        $filename = $slot . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $absPath = $absDir . '/' . $filename;
        $webPath = self::UPLOAD_DIR . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $absPath)) {
            throw new \RuntimeException('Не успях да запиша файла.');
        }

        // Remove previous file
        $previous = self::get($slot);
        if ($previous && $previous['path']) {
            $oldAbs = PROJECT_ROOT . $previous['path'];
            if (is_file($oldAbs) && str_starts_with($previous['path'], self::UPLOAD_DIR . '/')) {
                @unlink($oldAbs);
            }
        }

        Db::execute(
            'INSERT INTO media (slot, file_path, original_name, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                file_path = VALUES(file_path),
                original_name = VALUES(original_name),
                mime_type = VALUES(mime_type),
                file_size = VALUES(file_size)',
            [$slot, $webPath, (string) ($file['name'] ?? ''), $mime, (int) ($file['size'] ?? 0)]
        );
    }

    public static function reset(string $slot): void
    {
        $previous = self::get($slot);
        if ($previous && $previous['path'] && str_starts_with($previous['path'], self::UPLOAD_DIR . '/')) {
            $abs = PROJECT_ROOT . $previous['path'];
            if (is_file($abs)) @unlink($abs);
        }
        Db::execute('DELETE FROM media WHERE slot = ?', [$slot]);
    }

    /**
     * Map of slot → URL (override or default). Exposed to frontend as window.__MEDIA.
     */
    /**
     * Map of slot → URL (with base path) — emitted to frontend as window.__MEDIA.
     */
    public static function exportPublic(): array
    {
        $out = [];
        foreach (array_keys(self::SLOTS) as $slot) {
            $out[$slot] = self::urlFor($slot);
        }
        return $out;
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Файлът е твърде голям.',
            UPLOAD_ERR_PARTIAL    => 'Качването прекъсна.',
            UPLOAD_ERR_NO_FILE    => 'Не е избран файл.',
            UPLOAD_ERR_NO_TMP_DIR => 'Сървърът няма временна директория.',
            UPLOAD_ERR_CANT_WRITE => 'Сървърът не може да запише файла.',
            default => 'Грешка при качване (' . $code . ').',
        };
    }
}

if (!defined('UPLOAD_OK')) define('UPLOAD_OK', UPLOAD_ERR_OK);
