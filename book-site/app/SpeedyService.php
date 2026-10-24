<?php
declare(strict_types=1);

namespace App;

/**
 * Higher-level Speedy operations:
 *   • Sync cities/offices into local cache (admin-triggered).
 *   • Serve cities/offices to the public site from cache.
 *   • Create waybill from an order.
 */
final class SpeedyService
{
    private const OFFICES_CACHE_TTL = 86400; // 24h
    private const BG_LETTERS = [
        'А','Б','В','Г','Д','Е','Ж','З','И','Й','К','Л','М','Н','О',
        'П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ь','Ю','Я',
    ];
    // If a name-prefix query returns at/above this many results, the API may
    // be capping us — recurse with longer prefixes to make sure we caught all.
    // Speedy's /location/site/ returns only ~10 results without a name filter
    // and capped responses don't expose a "hasMore" flag, so we recurse
    // aggressively whenever we hit anything close to that cap.
    private const PREFIX_PROBE_THRESHOLD = 8;
    private const PREFIX_PROBE_MAX_DEPTH = 4;

    /**
     * Static fallback list — used when admin hasn't synced from Speedy yet,
     * so the public site stays functional. Negative IDs to avoid collision
     * with real Speedy site_ids.
     */
    public static function staticFallbackCities(): array
    {
        $fallback = [
            'София', 'Пловдив', 'Варна', 'Бургас', 'Перник', 'Русе',
            'Стара Загора', 'Плевен', 'Велико Търново', 'Сливен',
            'Шумен', 'Добрич', 'Хасково', 'Благоевград', 'Ямбол',
        ];
        $out = [];
        $i = 1;
        foreach ($fallback as $name) {
            $out[] = ['id' => -$i, 'name' => $name, 'post_code' => null];
            $i++;
        }
        return $out;
    }

    public static function staticFallbackOffices(string $cityName): array
    {
        $map = [
            'София' => [
                'Офис Център — ул. Раковски 96',
                'Офис Витоша — бул. Витоша 89Б',
                'Офис Младост 1 — бул. Александър Малинов 23',
                'Офис Студентски град — ул. Акад. Стефан Младенов 12',
                'Офис Лозенец — бул. Джеймс Баучер 51',
                'Офис Люлин — бул. Захари Стоянов 47',
            ],
            'Пловдив' => [
                'Офис Център — ул. Иван Вазов 38',
                'Офис Тракия — ул. Съединение 5',
                'Офис Кючук Париж — бул. Никола Вапцаров 102',
            ],
            'Варна' => [
                'Офис Център — бул. Сливница 33',
                'Офис Левски — ул. Подвис 25',
                'Офис Чайка — бул. Цар Освободител 75',
            ],
            'Бургас' => ['Офис Център — ул. Александровска 25', 'Офис Меден рудник — бул. Захари Стоянов 1'],
            'Перник' => ['Офис Център — ул. Кракра 12', 'Офис Изток — ул. Юрий Гагарин 5'],
            'Русе'   => ['Офис Център — ул. Александровска 89', 'Офис Дружба — бул. Липник 121'],
        ];
        $items = $map[$cityName] ?? ['Офис Център'];
        $out = [];
        $i = 1;
        foreach ($items as $name) {
            $out[] = ['id' => -$i, 'name' => $name, 'address' => null];
            $i++;
        }
        return $out;
    }

    public static function getCachedCities(): array
    {
        $rows = Db::fetchAll(
            'SELECT city_id AS id, name, post_code FROM speedy_cities ORDER BY name ASC'
        );
        if ($rows) return array_map(fn($r) => [
            'id' => (int) $r['id'],
            'name' => $r['name'],
            'post_code' => $r['post_code'],
        ], $rows);
        return self::staticFallbackCities();
    }

    public static function getOfficesForCity(int $cityId, ?string $cityName = null): array
    {
        // Negative IDs come from static fallback — return static offices.
        if ($cityId < 0) {
            return self::staticFallbackOffices((string) $cityName);
        }

        // Check cache freshness
        $fresh = Db::fetchOne(
            'SELECT MAX(refreshed_at) AS r FROM speedy_offices WHERE city_id = ?',
            [$cityId]
        );
        $needsRefresh = !$fresh['r'] || (time() - strtotime($fresh['r'])) > self::OFFICES_CACHE_TTL;

        if ($needsRefresh) {
            try {
                self::refreshOfficesForCity($cityId);
            } catch (\Throwable $e) {
                error_log('Speedy office refresh failed: ' . $e->getMessage());
                // Continue — return whatever we have cached (even if stale).
            }
        }

        $rows = Db::fetchAll(
            'SELECT office_id AS id, name, address FROM speedy_offices WHERE city_id = ? ORDER BY name ASC',
            [$cityId]
        );
        return array_map(fn($r) => [
            'id' => (int) $r['id'],
            'name' => $r['name'],
            'address' => $r['address'],
        ], $rows);
    }

    public static function refreshOfficesForCity(int $cityId): int
    {
        $api = new Speedy();
        $offices = $api->officesBySite($cityId);
        Db::transaction(function () use ($cityId, $offices) {
            Db::execute('DELETE FROM speedy_offices WHERE city_id = ?', [$cityId]);
            foreach ($offices as $o) {
                $address = self::formatAddress($o['address'] ?? []);
                Db::execute(
                    'INSERT INTO speedy_offices (office_id, city_id, name, address, working_time)
                     VALUES (?, ?, ?, ?, ?)',
                    [
                        (int) $o['id'],
                        $cityId,
                        (string) ($o['name'] ?? ('Офис ' . ($o['id'] ?? '?'))),
                        $address,
                        $o['workingTimeFrom'] ?? null,
                    ]
                );
            }
        });
        return count($offices);
    }

    /**
     * Sync all BG cities ("гр.") into local cache.
     *
     * Speedy's /location/site/ returns only ~10 results without a `name`
     * filter, so we sweep the cyrillic alphabet and dedupe by site id. If a
     * single-letter prefix returns near the API cap, we recurse with 2-letter
     * prefixes — covers letters like "В", "С", "Г" that have many towns.
     */
    public static function syncCities(): array
    {
        @set_time_limit(300);
        $api = new Speedy();

        $allSites = [];
        foreach (self::BG_LETTERS as $letter) {
            self::collectSitesByPrefix($api, $letter, $allSites, 1);
        }

        $synced = ['cities' => 0, 'with_offices' => 0];
        Db::transaction(function () use ($allSites, &$synced) {
            Db::execute('DELETE FROM speedy_cities');
            foreach ($allSites as $s) {
                Db::execute(
                    'INSERT INTO speedy_cities (city_id, name, type, post_code, region, municipality)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [
                        (int) $s['id'],
                        (string) ($s['name'] ?? ''),
                        $s['type'] ?? null,
                        $s['postCode'] ?? null,
                        $s['region'] ?? null,
                        $s['municipality'] ?? null,
                    ]
                );
                $synced['cities']++;
            }
        });
        return $synced;
    }

    private static function collectSitesByPrefix(Speedy $api, string $prefix, array &$bucket, int $depth): void
    {
        try {
            $sites = $api->findSites(['type' => 'гр.', 'name' => $prefix]);
        } catch (\Throwable $e) {
            error_log("Speedy syncCities prefix '$prefix' failed: " . $e->getMessage());
            return;
        }
        foreach ($sites as $s) {
            $id = (int) ($s['id'] ?? 0);
            if ($id && !isset($bucket[$id])) {
                $bucket[$id] = $s;
            }
        }
        // If we hit (or got close to) the API cap, the result is likely
        // truncated — drill deeper to catch the rest.
        if (count($sites) >= self::PREFIX_PROBE_THRESHOLD && $depth < self::PREFIX_PROBE_MAX_DEPTH) {
            foreach (self::BG_LETTERS as $next) {
                self::collectSitesByPrefix($api, $prefix . mb_strtolower($next, 'UTF-8'), $bucket, $depth + 1);
            }
        }
    }

    /**
     * Calculate shipping price for the given delivery params.
     *
     * Returns ['amount' => float, 'currency' => 'EUR', 'source' => 'speedy'|'fallback']
     * Always returns a value (never throws) — falls back to static prices on any
     * Speedy error so the order flow can never be blocked.
     *
     * @param array $params Required keys depending on method:
     *   - method: 'office' | 'address'
     *   - office_site_id: int (positive Speedy site id, required for office)
     *   - office_speedy_id: int (positive Speedy office id, required for office)
     *   - city_name: string (for address delivery)
     *   - post_code: ?string (for address delivery)
     *   - subtotal: float (item subtotal in EUR — for free-shipping threshold)
     *   - cod: bool (whether this is COD; affects price)
     *   - cod_amount: float (in EUR — converted to BGN for Speedy if needed)
     *   - qty: int (multiplies default weight)
     */
    public static function calculate(array $params): array
    {
        $method   = $params['method'] ?? '';
        $subtotal = (float) ($params['subtotal'] ?? 0.0);
        $isCod    = !empty($params['cod']);
        $qty      = max(1, (int) ($params['qty'] ?? 1));

        // Free-shipping rule lives here, not at Speedy.
        if ($subtotal >= Order::FREE_SHIP_THRESHOLD) {
            return ['amount' => 0.0, 'currency' => 'EUR', 'source' => 'free'];
        }

        $api = new Speedy();
        if (!$api->isConfigured()) {
            return self::staticShippingPrice($method, $isCod);
        }

        $sender = self::senderConfig();
        if (empty($sender['client_id'])) {
            return self::staticShippingPrice($method, $isCod);
        }

        // Build recipient block.
        $recipient = ['privatePerson' => true];
        if ($method === 'office') {
            $siteId   = (int) ($params['office_site_id']   ?? 0);
            $officeId = (int) ($params['office_speedy_id'] ?? 0);
            if ($siteId <= 0 || $officeId <= 0) {
                return self::staticShippingPrice($method, $isCod);
            }
            $recipient['pickupOfficeId'] = $officeId;
            $recipient['address'] = ['siteId' => $siteId];
        } elseif ($method === 'address') {
            $cityName = (string) ($params['city_name'] ?? '');
            if ($cityName === '') {
                return self::staticShippingPrice($method, $isCod);
            }
            $recipient['address'] = [
                'siteName' => $cityName,
                'postCode' => $params['post_code'] ?? null,
            ];
        } else {
            return self::staticShippingPrice($method, $isCod);
        }

        $weight = round($sender['weight_kg'] * $qty, 2);

        $payment = [
            'courierServicePayer' => 'RECIPIENT',
            'declaredValuePayer'  => 'RECIPIENT',
        ];
        if ($isCod) {
            $codBgn = self::eurToBgn((float) ($params['cod_amount'] ?? 0.0));
            $payment['cod'] = ['amount' => $codBgn, 'processingType' => 'CASH'];
        }

        $payload = [
            'service' => [
                'serviceIds' => [$sender['service_id']],
                'autoAdjustPickupDate' => true,
            ],
            'content' => [
                'parcelsCount' => 1,
                'totalWeight'  => $weight,
                'contents'     => Order::ITEM_NAME,
                'package'      => 'BOX',
            ],
            'payment'   => $payment,
            'sender'    => ['clientId' => $sender['client_id']],
            'recipient' => $recipient,
        ];

        try {
            $resp = $api->calculate($payload);
        } catch (\Throwable $e) {
            error_log('Speedy calculate failed: ' . $e->getMessage());
            return self::staticShippingPrice($method, $isCod);
        }

        // Speedy returns { calculations: [{ price: { total, totalNoVat, currency }, ... }] }
        // We use the total inclusive of VAT.
        $first = $resp['calculations'][0] ?? null;
        if (!$first || !isset($first['price']['total'])) {
            return self::staticShippingPrice($method, $isCod);
        }

        $rawAmount   = (float) $first['price']['total'];
        $rawCurrency = strtoupper((string) ($first['price']['currency'] ?? 'BGN'));

        $amountEur = $rawCurrency === 'EUR' ? $rawAmount : self::bgnToEur($rawAmount);

        return [
            'amount'   => round($amountEur, 2),
            'currency' => 'EUR',
            'source'   => 'speedy',
        ];
    }

    private static function staticShippingPrice(string $method, bool $isCod): array
    {
        $base = $method === 'office' ? Order::SHIP_OFFICE : Order::SHIP_COURIER;
        // Small COD surcharge to roughly mirror Speedy's behavior.
        if ($isCod) $base += 0.50;
        return ['amount' => round($base, 2), 'currency' => 'EUR', 'source' => 'fallback'];
    }

    private const EUR_TO_BGN_RATE = 1.95583;

    private static function eurToBgn(float $eur): float
    {
        return round($eur * self::EUR_TO_BGN_RATE, 2);
    }

    private static function bgnToEur(float $bgn): float
    {
        return round($bgn / self::EUR_TO_BGN_RATE, 2);
    }

    /**
     * Build + send a Speedy createShipment for an order. On success, stores
     * tracking_number + tracking_url on the order and returns them.
     */
    public static function createWaybillForOrder(int $orderId): array
    {
        $order = Order::find($orderId);
        if (!$order) throw new \RuntimeException('Order not found');
        if ($order['tracking_number']) {
            throw new \RuntimeException('Поръчката вече има товарителница: ' . $order['tracking_number']);
        }

        $sender = self::senderConfig();
        if (empty($sender['client_id'])) {
            throw new \RuntimeException('Конфигурирайте „Speedy Client ID" в админ настройките (от договора с Speedy).');
        }

        $payload = self::buildShipmentPayload($order, $sender);

        $api = new Speedy();
        $resp = $api->createShipment($payload);

        // Speedy may return id at top level or in parcels[0].id
        $tracking = (string) ($resp['id'] ?? $resp['parcels'][0]['id'] ?? '');
        if ($tracking === '') {
            throw new \RuntimeException('Speedy не върна номер на товарителница: ' . json_encode($resp));
        }
        $url = self::trackingUrlFor($tracking);

        Db::execute(
            'UPDATE orders SET tracking_number = ?, tracking_url = ? WHERE id = ?',
            [$tracking, $url, $orderId]
        );

        return ['tracking_number' => $tracking, 'tracking_url' => $url];
    }

    /**
     * Public Speedy tracking URL — works without login on speedy.bg.
     * Used both at waybill creation and as a runtime fallback for orders
     * created before the URL format changed.
     */
    public static function trackingUrlFor(string $trackingNumber): string
    {
        return 'https://www.speedy.bg/bg/track-shipment?shipmentNumber=' . urlencode($trackingNumber);
    }

    /**
     * Fetch the printable PDF waybill for an order. Streams the bytes back —
     * caller is responsible for setting headers. Throws on Speedy errors.
     */
    public static function fetchWaybillPdf(int $orderId): string
    {
        $order = Order::find($orderId);
        if (!$order)                         throw new \RuntimeException('Order not found');
        if (empty($order['tracking_number'])) {
            throw new \RuntimeException('Поръчката няма товарителница.');
        }
        $api = new Speedy();
        return $api->printWaybill([$order['tracking_number']]);
    }

    private static function senderConfig(): array
    {
        $s = Settings::getMany(['speedy_client_id', 'speedy_sender_object_id', 'speedy_service_id', 'speedy_default_weight']);
        // Speedy `sender.clientId` is the contract holder's client id (from
        // your contract). Some users may have entered it under "Sender Object
        // ID" historically — fall back to that when the dedicated field is empty.
        $clientId = (int) ($s['speedy_client_id'] ?? 0);
        $objectId = (int) ($s['speedy_sender_object_id'] ?? 0);
        return [
            'client_id'  => $clientId > 0 ? $clientId : $objectId,
            'object_id'  => $objectId,
            'service_id' => (int) ($s['speedy_service_id'] ?? 505),
            'weight_kg'  => (float) ($s['speedy_default_weight'] ?? 0.5),
        ];
    }

    private static function buildShipmentPayload(array $order, array $sender): array
    {
        $isOffice = $order['delivery_method'] === 'office';
        $isCod    = $order['payment_method'] === 'cod';
        $codAmount = $isCod ? (float) $order['total'] : 0.0;

        $recipient = [
            'phone1'        => ['number' => $order['customer_phone']],
            'clientName'    => $order['customer_name'],
            'email'         => $order['customer_email'],
            'privatePerson' => true,
        ];

        if ($isOffice) {
            // Office pickup: send pickupOfficeId only. If we also send an
            // `address` block, Speedy treats it as door-delivery and demands
            // street/quarter fields → "Address: fill in street…" error.
            $recipient['pickupOfficeId'] = (int) $order['delivery_office_speedy_id'];
        } else {
            // Door delivery: build address with siteId (preferred) or siteName
            // and put the street + extras into `addressNote` (Speedy's free-form
            // clarification field). `fullAddressString` is non-standard and
            // triggers the same validation error as a partial structured address.
            $address = ['countryId' => 100];
            $siteId = (int) ($order['delivery_office_site_id'] ?? 0);
            if ($siteId > 0) {
                $address['siteId'] = $siteId;
            } else {
                $address['siteName'] = (string) ($order['delivery_city'] ?? '');
            }
            if (!empty($order['delivery_zip'])) {
                $address['postCode'] = (string) $order['delivery_zip'];
            }
            $address['addressNote'] = self::buildAddressNote($order);
            $recipient['address'] = $address;
        }

        $payment = [
            'courierServicePayer' => 'RECIPIENT',
            'declaredValuePayer'  => 'RECIPIENT',
        ];
        if ($isCod) {
            $payment['cod'] = [
                'amount'        => $codAmount,
                'processingType'=> 'CASH',
            ];
        }

        return [
            'service' => [
                'serviceId'             => $sender['service_id'],
                'autoAdjustPickupDate'  => true,
            ],
            'content' => [
                'parcelsCount' => 1,
                'totalWeight'  => $sender['weight_kg'],
                'contents'     => Order::ITEM_NAME,
                'package'      => 'BOX',
            ],
            'payment'   => $payment,
            'sender'    => ['clientId' => $sender['client_id']],
            'recipient' => $recipient,
            'ref1'      => $order['order_number'],
        ];
    }

    private static function formatAddress(array $addr): string
    {
        if (!$addr) return '';
        $parts = [];
        foreach (['streetType', 'streetName', 'streetNo', 'siteType', 'siteName'] as $k) {
            if (!empty($addr[$k])) $parts[] = $addr[$k];
        }
        return implode(' ', $parts);
    }

    private static function buildAddressNote(array $order): string
    {
        $parts = [];
        if (!empty($order['delivery_street'])) {
            $street = 'ул. ' . $order['delivery_street'];
            if (!empty($order['delivery_number'])) $street .= ' ' . $order['delivery_number'];
            $parts[] = $street;
        }
        $extras = [];
        foreach (['block' => 'бл.', 'entrance' => 'вх.', 'floor' => 'ет.', 'apartment' => 'ап.'] as $k => $label) {
            $v = $order['delivery_' . $k] ?? null;
            if ($v) $extras[] = $label . ' ' . $v;
        }
        if ($extras) $parts[] = implode(', ', $extras);
        return implode(', ', $parts);
    }
}
