<?php
declare(strict_types=1);

namespace App;

/**
 * Thin REST client for Speedy Web API v1 (https://api.speedy.bg/v1).
 *
 * Auth: every request body carries userName + password (per Speedy spec).
 * No client-side caching here — see SpeedyService for the cached helpers.
 */
final class Speedy
{
    private const BASE_URL = 'https://api.speedy.bg/v1';
    private const COUNTRY_BG = 100;

    private string $username;
    private string $password;
    private string $language = 'BG';

    public function __construct(?string $username = null, ?string $password = null)
    {
        if ($username !== null && $password !== null) {
            $this->username = $username;
            $this->password = $password;
            return;
        }
        $s = Settings::getMany(['speedy_username', 'speedy_password']);
        $this->username = (string) ($s['speedy_username'] ?? '');
        $this->password = (string) ($s['speedy_password'] ?? '');
    }

    public function isConfigured(): bool
    {
        return $this->username !== '' && $this->password !== '';
    }

    /**
     * Search/list sites (cities/villages). Without filters returns the first
     * page; pass `name` / `postCode` to narrow.
     */
    public function findSites(array $filters = []): array
    {
        $payload = array_merge(['countryId' => self::COUNTRY_BG], $filters);
        $resp = $this->call('/location/site/', $payload);
        return $resp['sites'] ?? [];
    }

    public function officesBySite(int $siteId): array
    {
        $resp = $this->call('/location/office/', ['siteId' => $siteId, 'countryId' => self::COUNTRY_BG]);
        return $resp['offices'] ?? [];
    }

    public function createShipment(array $shipmentPayload): array
    {
        return $this->call('/shipment/', $shipmentPayload);
    }

    public function calculate(array $payload): array
    {
        return $this->call('/calculation/', $payload);
    }

    /**
     * Fetch the printable waybill PDF for one or more parcel ids.
     * Returns the raw PDF bytes — caller streams them to the browser.
     */
    public function printWaybill(array $parcelIds, string $paperSize = 'A4'): string
    {
        return $this->callBinary('/print/', [
            'parcels'   => array_map(fn($id) => ['parcel' => ['id' => (string) $id]], $parcelIds),
            'paperSize' => $paperSize,
        ]);
    }

    public function trackShipment(string $parcelId): array
    {
        return $this->call('/track/', ['parcels' => [['id' => $parcelId]]]);
    }

    /**
     * Like call() but returns the raw response body (used for PDF endpoints).
     * Speedy /print/ may respond as either:
     *   1) raw PDF bytes (Content-Type: application/pdf) — return as-is
     *   2) JSON with base64-encoded PDF — decode and return bytes
     *   3) JSON with `error` — throw
     * We send a wildcard Accept to avoid 406 (Speedy rejects narrow Accepts).
     */
    private function callBinary(string $endpoint, array $body): string
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Speedy credentials not configured. Set them in Admin → Settings.');
        }

        $url = self::BASE_URL . $endpoint;
        $payload = array_merge([
            'userName' => $this->username,
            'password' => $this->password,
            'language' => $this->language,
        ], $body);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: */*'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException("Speedy network error: $err");
        }
        $headers = substr((string) $raw, 0, $headerSize);
        $response = substr((string) $raw, $headerSize);
        $contentType = '';
        if (preg_match('/^Content-Type:\s*([^;\r\n]+)/im', $headers, $m)) {
            $contentType = strtolower(trim($m[1]));
        }

        // Direct PDF — return bytes.
        if (str_contains($contentType, 'application/pdf') || (strlen($response) > 4 && substr($response, 0, 4) === '%PDF')) {
            if ($code >= 400) throw new \RuntimeException("Speedy HTTP $code");
            return $response;
        }

        // JSON — either base64 PDF or error.
        $data = json_decode($response, true);
        if (is_array($data)) {
            if (!empty($data['error'])) {
                $e = $data['error'];
                throw new \RuntimeException(
                    'Speedy API error ' . ($e['code'] ?? '?') . ': ' . ($e['message'] ?? 'Unknown')
                );
            }
            // Some Speedy responses wrap the PDF as base64.
            foreach (['file', 'data', 'pdf', 'content'] as $k) {
                if (!empty($data[$k]) && is_string($data[$k])) {
                    $decoded = base64_decode($data[$k], true);
                    if ($decoded !== false && strlen($decoded) > 100) return $decoded;
                }
            }
        }

        if ($code >= 400) {
            throw new \RuntimeException("Speedy HTTP $code: " . substr($response, 0, 200));
        }
        throw new \RuntimeException('Speedy returned unexpected response (Content-Type: ' . $contentType . ')');
    }

    private function call(string $endpoint, array $body): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Speedy credentials not configured. Set them in Admin → Settings.');
        }

        $url = self::BASE_URL . $endpoint;
        $payload = array_merge([
            'userName' => $this->username,
            'password' => $this->password,
            'language' => $this->language,
        ], $body);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("Speedy network error: $err");
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new \RuntimeException("Speedy returned non-JSON response (HTTP $code): " . substr($response, 0, 200));
        }

        if (!empty($data['error'])) {
            $e = $data['error'];
            throw new \RuntimeException(
                'Speedy API error ' . ($e['code'] ?? '?') . ': ' . ($e['message'] ?? 'Unknown')
            );
        }
        if ($code >= 400) {
            throw new \RuntimeException("Speedy HTTP $code");
        }

        return $data;
    }
}
