<?php
declare(strict_types=1);

namespace App;

/**
 * Key-value settings storage. Used for SMTP credentials, Speedy API
 * credentials, content overrides — anything editable from admin UI.
 */
final class Settings
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $row = Db::fetchOne('SELECT setting_value FROM settings WHERE setting_key = ?', [$key]);
        return $row['setting_value'] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        Db::execute(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [$key, $value]
        );
    }

    public static function getMany(array $keys): array
    {
        if (!$keys) return [];
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $rows = Db::fetchAll(
            "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)",
            $keys
        );
        $out = array_fill_keys($keys, null);
        foreach ($rows as $r) {
            $out[$r['setting_key']] = $r['setting_value'];
        }
        return $out;
    }

    public static function setMany(array $kv): void
    {
        Db::transaction(function () use ($kv) {
            foreach ($kv as $k => $v) {
                self::set($k, $v === '' ? null : $v);
            }
        });
    }
}
