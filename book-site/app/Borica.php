<?php
declare(strict_types=1);

namespace App;

/**
 * БОРИКА APGW (e-Commerce CGI interface v7) — low-level layer.
 * ALL MAC/signature logic lives HERE and nowhere else.
 *
 * Field lists VERIFIED 2026-06-10 against the official v7 doc §6.1 (macFields /
 * macSourceValue examples): request = [TERMINAL,TRTYPE,AMOUNT,CURRENCY,ORDER,TIMESTAMP,
 * NONCE,RFU]; response = [ACTION,RC,APPROVAL,TERMINAL,TRTYPE,AMOUNT,CURRENCY,ORDER,RRN,
 * INT_REF,PARES_STATUS,ECI,TIMESTAMP,NONCE,RFU]. Length-encoding = strlen+value, '-' when
 * empty; SHA256 + RSA, hex P_SIGN. (signature_valid=1 on a live БОРИКА response confirms the
 * response list + encoding.) If БОРИКА rejects with RC -17, re-check SIGN_REQUEST here.
 * Inspect a signed request with: php bin/borica_selfcheck.php
 */
final class Borica
{
    private const ENDPOINTS = [
        'test'       => 'https://3dsgate-dev.borica.bg/cgi-bin/cgi_link',
        'production' => 'https://3dsgate.borica.bg/cgi-bin/cgi_link',
    ];

    /**
     * Fields signed for the REQUEST P_SIGN. Per the v7 doc §6.1 (macFields) the request MAC is
     * [TERMINAL, TRTYPE, AMOUNT, CURRENCY, ORDER, TIMESTAMP, NONCE, RFU] — note MERCHANT is a sent
     * field but is NOT part of the MAC. Including it caused RC -17 (Access denied / P_SIGN error).
     */
    private const SIGN_REQUEST = ['TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'TIMESTAMP', 'NONCE', 'RFU'];

    /** Fields signed in the RESPONSE (for verify). Verify against v7 PDF. */
    private const SIGN_RESPONSE = ['ACTION', 'RC', 'APPROVAL', 'TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'RRN', 'INT_REF', 'PARES_STATUS', 'ECI', 'TIMESTAMP', 'NONCE', 'RFU'];

    private static function cfg(string $key, string $default = ''): string
    {
        return (string) ($GLOBALS['__config']['borica'][$key] ?? $default);
    }

    public static function isEnabled(): bool
    {
        return !empty($GLOBALS['__config']['borica']['enabled']);
    }

    public static function endpointUrl(): string
    {
        $env = self::cfg('environment', 'test');
        return self::ENDPOINTS[$env] ?? self::ENDPOINTS['test'];
    }

    /**
     * MAC_EXTENDED concatenation: for each field — length of the value followed by
     * the value; an empty/absent field is represented by a single '-'.
     */
    private static function macString(array $fields, array $order): string
    {
        $out = '';
        foreach ($order as $name) {
            $v = $fields[$name] ?? '';
            $out .= ($v === '' || $v === null) ? '-' : (strlen((string) $v) . (string) $v);
        }
        return $out;
    }

    public static function signRequest(array $fields): string
    {
        $data    = self::macString($fields, self::SIGN_REQUEST);
        $keyPath = self::cfg('private_key_path');
        if ($keyPath === '' || !is_readable($keyPath)) {
            throw new \RuntimeException('БОРИКА: липсва или нечетим private_key_path (' . $keyPath . ')');
        }
        $pkey = openssl_pkey_get_private((string) file_get_contents($keyPath), self::cfg('private_key_password'));
        if ($pkey === false) {
            throw new \RuntimeException('БОРИКА: невалиден частен ключ: ' . openssl_error_string());
        }
        $signature = '';
        if (!openssl_sign($data, $signature, $pkey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('БОРИКА: подписването се провали: ' . openssl_error_string());
        }
        return strtoupper(bin2hex($signature));
    }

    public static function verifyResponse(array $fields): bool
    {
        if (empty($fields['P_SIGN'])) {
            return false;
        }
        $data     = self::macString($fields, self::SIGN_RESPONSE);
        $certPath = self::cfg('borica_public_cert_path');
        if ($certPath === '' || !is_readable($certPath)) {
            throw new \RuntimeException('БОРИКА: липсва или нечетим borica_public_cert_path (' . $certPath . ')');
        }
        $pubkey = openssl_pkey_get_public((string) file_get_contents($certPath));
        if ($pubkey === false) {
            throw new \RuntimeException('БОРИКА: невалиден публичен сертификат: ' . openssl_error_string());
        }
        $signature = @hex2bin((string) $fields['P_SIGN']);
        if ($signature === false) {
            return false;
        }
        return openssl_verify($data, $signature, $pubkey, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * Builds the signed field set for a TRTYPE 1 (direct sale) request.
     *
     * @param array  $order    Order row (needs total, order_number, customer_*, delivery_*, locale).
     * @param string $orderRef 6-digit ORDER reference (unique per terminal).
     * @param string $backref  Absolute Return URL БОРИКА posts the response to.
     */
    public static function buildSaleFields(array $order, string $orderRef, string $backref): array
    {
        $fields = [
            'TERMINAL'   => self::cfg('terminal'),
            'TRTYPE'     => '1',
            'AMOUNT'     => number_format((float) $order['total'], 2, '.', ''),
            'CURRENCY'   => self::cfg('currency', 'EUR'),
            'ORDER'      => $orderRef,
            'DESC'       => 'Поръчка ' . ($order['order_number'] ?? $orderRef),
            'MERCHANT'   => self::cfg('merchant'),
            'MERCH_NAME' => self::cfg('merchant_name'),
            'MERCH_URL'  => self::cfg('merchant_url'),
            'MERCH_GMT'  => self::cfg('merchant_gmt', '+03'),
            'EMAIL'      => (string) ($order['customer_email'] ?? ''),
            'COUNTRY'    => self::cfg('country', 'BG'),
            'M_INFO'     => self::buildMInfo($order),
            'TIMESTAMP'  => gmdate('YmdHis'),
            'NONCE'      => strtoupper(bin2hex(random_bytes(16))),
            'BACKREF'    => $backref,
            'LANG'       => strtoupper((string) ($order['locale'] ?? 'BG')),
        ];
        $fields['P_SIGN'] = self::signRequest($fields);
        return $fields;
    }

    /**
     * M_INFO — EMV 3DS v2 cardholder data (v7 doc §3.1): Base64 of a JSON object.
     * Required by БОРИКА: cardholderName (Latin, ≤45), email and/or mobilePhone,
     * shipping/billing address (≤50; Cyrillic allowed per the doc's own example).
     * NOT part of the request MAC (SIGN_REQUEST stays unchanged).
     */
    public static function buildMInfo(array $order): string
    {
        $info = [];

        $name = self::transliterate(trim((string) ($order['customer_name'] ?? '')));
        if ($name !== '') {
            $info['cardholderName'] = mb_substr($name, 0, 45, 'UTF-8');
        }

        $email = trim((string) ($order['customer_email'] ?? ''));
        if ($email !== '') {
            $info['email'] = $email;
        }

        $phone = self::bgMobilePhone((string) ($order['customer_phone'] ?? ''));
        if ($phone !== null) {
            $info['mobilePhone'] = $phone;
        }

        $addr = self::shippingAddressLine($order);
        if ($addr !== '') {
            $info['shipAddrLine1'] = mb_substr($addr, 0, 50, 'UTF-8');
        }

        return base64_encode((string) json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** BG Cyrillic → Latin per Закона за транслитерацията (cardholderName must be Latin). */
    private static function transliterate(string $s): string
    {
        static $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
            'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sht', 'ъ' => 'a',
            'ь' => 'y', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ж' => 'Zh',
            'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F',
            'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sht', 'Ъ' => 'A',
            'Ь' => 'Y', 'Ю' => 'Yu', 'Я' => 'Ya',
        ];
        $s = strtr($s, $map);
        // Keep only what a Latin cardholder name may contain.
        $s = (string) preg_replace('/[^A-Za-z \'\-\.]+/u', ' ', $s);
        return trim((string) preg_replace('/\s{2,}/', ' ', $s));
    }

    /** Parses a BG phone into БОРИКА's {cc, subscriber} shape; null if not confidently parseable. */
    private static function bgMobilePhone(string $raw): ?array
    {
        $digits = (string) preg_replace('/\D+/', '', $raw);
        if (str_starts_with($digits, '00359')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '359')) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }
        // BG subscriber numbers are 8–9 digits after the country code.
        if (!preg_match('/^\d{8,9}$/', $digits)) {
            return null;
        }
        return ['cc' => '359', 'subscriber' => $digits];
    }

    /** One-line shipping address from the order's delivery fields (office label or street address). */
    private static function shippingAddressLine(array $order): string
    {
        if (($order['delivery_method'] ?? '') === 'office') {
            $parts = [
                (string) ($order['delivery_office_city'] ?? ''),
                (string) ($order['delivery_office_id'] ?? ''),
            ];
        } else {
            $parts = [
                (string) ($order['delivery_city'] ?? ''),
                trim((string) ($order['delivery_street'] ?? '') . ' ' . (string) ($order['delivery_number'] ?? '')),
            ];
        }
        $parts = array_filter(array_map('trim', $parts), static fn (string $p): bool => $p !== '');
        return implode(', ', $parts);
    }
}
