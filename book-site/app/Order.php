<?php
declare(strict_types=1);

namespace App;

final class Order
{
    public const STATUSES = [
        'received'        => ['bg' => 'Получена',                  'en' => 'Received'],
        'paid'            => ['bg' => 'Платена',                   'en' => 'Paid'],
        'payment_failed'  => ['bg' => 'Неуспешно плащане',         'en' => 'Payment failed'],
        'confirmed'       => ['bg' => 'Потвърдена',                'en' => 'Confirmed'],
        'preparing'       => ['bg' => 'Подготвя се за изпращане',  'en' => 'Preparing for shipment'],
        'shipped'         => ['bg' => 'Изпратена',                 'en' => 'Shipped'],
        'delivered'       => ['bg' => 'Доставена',                 'en' => 'Delivered'],
        'cancelled'       => ['bg' => 'Отменена',                  'en' => 'Cancelled'],
    ];

    public const PRICE_PER_UNIT = 69.00;
    public const SHIP_COURIER   = 5.00;
    public const SHIP_OFFICE    = 3.50;
    public const FREE_SHIP_THRESHOLD = 40.00;
    public const SKU = 'ACN-BG';
    public const ITEM_NAME = 'Разширена химия на ноктите';
    public const CURRENCY = 'EUR';

    public static function statusLabel(string $code, string $locale = 'bg'): string
    {
        return self::STATUSES[$code][$locale] ?? $code;
    }

    public static function generateOrderNumber(): string
    {
        // 6-digit number, retry on collision (unique constraint will catch dupes anyway)
        for ($i = 0; $i < 10; $i++) {
            $candidate = 'ACN-' . str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $exists = Db::fetchOne('SELECT 1 FROM orders WHERE order_number = ?', [$candidate]);
            if (!$exists) return $candidate;
        }
        // Fallback: timestamp-based
        return 'ACN-' . date('YmdHis');
    }

    /**
     * Validates and creates an order with items + initial status_log entry.
     * Returns the saved order row (with id and order_number).
     *
     * @param array $payload  Validated payload from API.
     * @return array          Saved order row.
     */
    public static function create(array $payload, ?string $ip = null): array
    {
        $qty = max(1, min(20, (int) ($payload['qty'] ?? 1)));
        $subtotal = round(self::PRICE_PER_UNIT * $qty, 2);
        $d = $payload['delivery'] ?? [];

        // Authoritative shipping cost — frontend value is ignored on purpose.
        // SpeedyService::calculate handles the free-shipping threshold and
        // falls back to static prices on any API failure.
        $calc = SpeedyService::calculate([
            'method'           => $d['method'] ?? '',
            'office_site_id'   => (int) ($d['office_city_id']   ?? 0),
            'office_speedy_id' => (int) ($d['office_speedy_id'] ?? 0),
            'city_name'        => (string) ($d['office_city'] ?? $d['city'] ?? ''),
            'post_code'        => $d['zip'] ?? null,
            'subtotal'         => $subtotal,
            'cod'              => ($payload['payment']['method'] ?? '') === 'cod',
            'cod_amount'       => $subtotal,
            'qty'              => $qty,
        ]);
        $shipping = (float) $calc['amount'];
        $total = round($subtotal + $shipping, 2);

        $orderNumber = self::generateOrderNumber();
        $now = date('Y-m-d H:i:s');

        return Db::transaction(function () use ($payload, $d, $qty, $subtotal, $shipping, $total, $orderNumber, $ip) {
            Db::execute(
                'INSERT INTO orders (
                    order_number, status,
                    customer_name, customer_email, customer_phone,
                    delivery_method,
                    delivery_city, delivery_zip, delivery_street, delivery_number,
                    delivery_block, delivery_entrance, delivery_floor, delivery_apartment,
                    delivery_office_city, delivery_office_id,
                    delivery_office_site_id, delivery_office_speedy_id,
                    delivery_cost,
                    payment_method,
                    subtotal, total, currency,
                    locale, notes, customer_ip
                 ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?,
                    ?, ?,
                    ?,
                    ?,
                    ?, ?, ?,
                    ?, ?, ?
                 )',
                [
                    $orderNumber, 'received',
                    $payload['customer']['name'], $payload['customer']['email'], $payload['customer']['phone'],
                    $d['method'],
                    $d['city'] ?? null, $d['zip'] ?? null, $d['street'] ?? null, $d['number'] ?? null,
                    $d['block'] ?? null, $d['entrance'] ?? null, $d['floor'] ?? null, $d['apartment'] ?? null,
                    $d['office_city'] ?? null, $d['office_name'] ?? ($d['office_id'] ?? null),
                    !empty($d['office_city_id']) && $d['office_city_id'] > 0 ? (int) $d['office_city_id'] : null,
                    !empty($d['office_speedy_id']) && $d['office_speedy_id'] > 0 ? (int) $d['office_speedy_id'] : null,
                    $shipping,
                    $payload['payment']['method'],
                    $subtotal, $total, self::CURRENCY,
                    $payload['locale'] ?? 'bg', $payload['notes'] ?? null, $ip,
                ]
            );

            $orderId = Db::lastInsertId();

            Db::execute(
                'INSERT INTO order_items (order_id, sku, name, qty, unit_price, line_total)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$orderId, self::SKU, self::ITEM_NAME, $qty, self::PRICE_PER_UNIT, $subtotal]
            );

            Db::execute(
                'INSERT INTO order_status_log (order_id, from_status, to_status, note, user_id)
                 VALUES (?, NULL, ?, ?, NULL)',
                [$orderId, 'received', 'Поръчката е приета от уебсайта']
            );

            return self::find($orderId);
        });
    }

    public static function find(int $id): ?array
    {
        return Db::fetchOne('SELECT * FROM orders WHERE id = ?', [$id]);
    }

    public static function findByNumber(string $number): ?array
    {
        return Db::fetchOne('SELECT * FROM orders WHERE order_number = ?', [$number]);
    }

    public static function items(int $orderId): array
    {
        return Db::fetchAll('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
    }

    public static function statusLog(int $orderId): array
    {
        return Db::fetchAll(
            'SELECT l.*, u.name AS user_name
               FROM order_status_log l
               LEFT JOIN users u ON u.id = l.user_id
              WHERE l.order_id = ?
              ORDER BY l.id DESC',
            [$orderId]
        );
    }

    public static function changeStatus(int $orderId, string $newStatus, ?string $note, ?int $userId): array
    {
        if (!isset(self::STATUSES[$newStatus])) {
            throw new \InvalidArgumentException("Invalid status: $newStatus");
        }
        $order = self::find($orderId);
        if (!$order) throw new \RuntimeException('Order not found');

        Db::transaction(function () use ($orderId, $order, $newStatus, $note, $userId) {
            Db::execute('UPDATE orders SET status = ? WHERE id = ?', [$newStatus, $orderId]);
            Db::execute(
                'INSERT INTO order_status_log (order_id, from_status, to_status, note, user_id)
                 VALUES (?, ?, ?, ?, ?)',
                [$orderId, $order['status'], $newStatus, $note, $userId]
            );
        });

        return self::find($orderId);
    }

    public static function listFiltered(array $filters = []): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['status']) && isset(self::STATUSES[$filters['status']])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        $sql = 'SELECT * FROM orders';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY id DESC LIMIT 200';
        return Db::fetchAll($sql, $params);
    }

    public static function counts(): array
    {
        $rows = Db::fetchAll('SELECT status, COUNT(*) c FROM orders GROUP BY status');
        $out = array_fill_keys(array_keys(self::STATUSES), 0);
        $out['_total'] = 0;
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['c'];
            $out['_total'] += (int) $r['c'];
        }
        return $out;
    }

    public static function deliveryAddressText(array $order): string
    {
        if ($order['delivery_method'] === 'office') {
            return sprintf('%s — %s', $order['delivery_office_city'], $order['delivery_office_id']);
        }
        $parts = [];
        if ($order['delivery_zip'])     $parts[] = $order['delivery_zip'];
        if ($order['delivery_city'])    $parts[] = 'гр. ' . $order['delivery_city'];
        if ($order['delivery_street'])  $parts[] = $order['delivery_street'] . ' ' . ($order['delivery_number'] ?? '');
        $extra = [];
        foreach (['block' => 'бл.', 'entrance' => 'вх.', 'floor' => 'ет.', 'apartment' => 'ап.'] as $k => $label) {
            $v = $order['delivery_' . $k] ?? null;
            if ($v) $extra[] = $label . ' ' . $v;
        }
        if ($extra) $parts[] = implode(', ', $extra);
        return implode(', ', $parts);
    }

    public static function formatMoney(float $amount, string $currency = 'EUR'): string
    {
        $symbol = $currency === 'EUR' ? '€' : $currency;
        return number_format($amount, 2, ',', ' ') . ' ' . $symbol;
    }
}
