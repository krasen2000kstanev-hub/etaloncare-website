<?php
declare(strict_types=1);

namespace App;

/**
 * Editable content overrides. Whitelist of editable keys is defined here —
 * the admin UI cannot create arbitrary keys (XSS / typo guard).
 *
 * Complex nested content (about.body[], faq.items[], privacy sections, etc.)
 * stays code-managed in translations.js. Only flat strings are exposed here.
 */
final class Content
{
    public const LOCALES = ['bg', 'en'];

    /**
     * Editable keys grouped by section. Format:
     *   'section.key' => ['label_bg' => '...', 'long' => bool]
     */
    public const EDITABLE = [
        // Hero
        'hero.eyebrow'      => ['label' => 'Hero — eyebrow (над заглавието)', 'long' => false, 'group' => 'Hero'],
        'hero.title'        => ['label' => 'Hero — заглавие',                  'long' => false, 'group' => 'Hero'],
        'hero.subtitle'     => ['label' => 'Hero — подзаглавие',               'long' => true,  'group' => 'Hero'],
        'hero.tagline'      => ['label' => 'Hero — slogan',                    'long' => true,  'group' => 'Hero'],
        'hero.author'       => ['label' => 'Hero — автор (име)',               'long' => false, 'group' => 'Hero'],
        'hero.authorSub'    => ['label' => 'Hero — автор (подпис)',            'long' => false, 'group' => 'Hero'],
        'hero.cta'          => ['label' => 'Hero — главен бутон',              'long' => false, 'group' => 'Hero'],
        'hero.ctaSecondary' => ['label' => 'Hero — вторичен бутон',            'long' => false, 'group' => 'Hero'],
        'hero.price'        => ['label' => 'Hero — цена',                      'long' => false, 'group' => 'Hero'],
        'hero.priceNote'    => ['label' => 'Hero — бележка под цената',        'long' => false, 'group' => 'Hero'],
        'hero.featured'    => ['label' => 'Hero — featured badge',            'long' => false, 'group' => 'Hero'],

        // About
        'about.kicker'      => ['label' => 'За книгата — kicker',              'long' => false, 'group' => 'За книгата'],
        'about.title'       => ['label' => 'За книгата — заглавие',            'long' => true,  'group' => 'За книгата'],

        // Author section
        'author.kicker'     => ['label' => 'За автора — kicker',               'long' => false, 'group' => 'За автора'],
        'author.name'       => ['label' => 'За автора — име',                  'long' => false, 'group' => 'За автора'],
        'author.role'       => ['label' => 'За автора — роля',                 'long' => false, 'group' => 'За автора'],

        // Newsletter
        'newsletter.kicker' => ['label' => 'Newsletter — kicker',              'long' => false, 'group' => 'Newsletter'],
        'newsletter.title'  => ['label' => 'Newsletter — заглавие',            'long' => true,  'group' => 'Newsletter'],
        'newsletter.body'   => ['label' => 'Newsletter — текст',               'long' => true,  'group' => 'Newsletter'],

        // Footer
        'footer.rights'     => ['label' => 'Footer — copyright',               'long' => false, 'group' => 'Footer'],
        'footer.paymentsLabel' => ['label' => 'Footer — текст за плащания',    'long' => true,  'group' => 'Footer'],

        // Cookie banner
        'cookie.message'    => ['label' => 'Cookie banner — текст',            'long' => true,  'group' => 'Cookie / поверителност'],
        'cookie.accept'     => ['label' => 'Cookie banner — Приемам',          'long' => false, 'group' => 'Cookie / поверителност'],
        'cookie.decline'    => ['label' => 'Cookie banner — Отказвам',         'long' => false, 'group' => 'Cookie / поверителност'],

        // Order modal
        'order.title'       => ['label' => 'Поръчка — заглавие',               'long' => false, 'group' => 'Поръчка'],
        'order.body'        => ['label' => 'Поръчка — описание',               'long' => true,  'group' => 'Поръчка'],
        'order.success'     => ['label' => 'Поръчка успех — заглавие',         'long' => false, 'group' => 'Поръчка'],
        'order.successBody' => ['label' => 'Поръчка успех — текст',            'long' => true,  'group' => 'Поръчка'],
    ];

    public static function isEditable(string $key): bool
    {
        return isset(self::EDITABLE[$key]);
    }

    public static function get(string $key, string $locale): ?string
    {
        $row = Db::fetchOne(
            'SELECT value FROM content WHERE content_key = ? AND locale = ?',
            [$key, $locale]
        );
        return $row['value'] ?? null;
    }

    public static function set(string $key, string $locale, ?string $value): void
    {
        if (!self::isEditable($key)) {
            throw new \InvalidArgumentException("Key not editable: $key");
        }
        if (!in_array($locale, self::LOCALES, true)) {
            throw new \InvalidArgumentException("Invalid locale: $locale");
        }
        $value = $value !== null && $value !== '' ? $value : null;
        Db::execute(
            'INSERT INTO content (content_key, locale, value) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)',
            [$key, $locale, $value]
        );
    }

    /**
     * Build the override object exported to the public site as window.__CONTENT.
     * Only includes keys that have a saved override (per locale). Frontend deep-merges into TRANSLATIONS.
     *
     * Output shape:
     *   ['bg' => ['hero' => ['title' => '...']], 'en' => [...]]
     */
    public static function exportOverrides(): array
    {
        $rows = Db::fetchAll('SELECT content_key, locale, value FROM content WHERE value IS NOT NULL');
        $out = ['bg' => [], 'en' => []];
        foreach ($rows as $r) {
            $loc = $r['locale'];
            if (!isset($out[$loc])) continue;
            self::setNested($out[$loc], $r['content_key'], $r['value']);
        }
        return $out;
    }

    public static function getAllForAdmin(): array
    {
        $rows = Db::fetchAll('SELECT content_key, locale, value FROM content');
        $bag = [];
        foreach (array_keys(self::EDITABLE) as $k) {
            $bag[$k] = ['bg' => '', 'en' => ''];
        }
        foreach ($rows as $r) {
            if (isset($bag[$r['content_key']])) {
                $bag[$r['content_key']][$r['locale']] = (string) ($r['value'] ?? '');
            }
        }
        return $bag;
    }

    private static function setNested(array &$arr, string $dottedKey, mixed $value): void
    {
        $parts = explode('.', $dottedKey);
        $ref =& $arr;
        foreach ($parts as $i => $p) {
            if ($i === count($parts) - 1) {
                $ref[$p] = $value;
            } else {
                if (!isset($ref[$p]) || !is_array($ref[$p])) $ref[$p] = [];
                $ref =& $ref[$p];
            }
        }
    }
}
