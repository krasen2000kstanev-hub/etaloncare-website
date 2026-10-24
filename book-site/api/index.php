<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Db;
use App\Mailer;
use App\Migrate;
use App\Order;
use App\RateLimit;
use App\Settings;
use App\SpeedyService;

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Apply unrun migrations on first hit (cheap if already up-to-date).
try {
    Migrate::run();
} catch (\Throwable $e) {
    json_response(500, ['ok' => false, 'message' => 'Server not initialized.']);
}

$path   = trim((string) ($_GET['path'] ?? ''), '/');
$method = $_SERVER['REQUEST_METHOD'];

// --- Routing ---
if ($path === 'orders' && $method === 'POST') {
    handle_create_order();
}
if ($path === 'speedy/cities' && $method === 'GET') {
    json_response(200, ['cities' => SpeedyService::getCachedCities()]);
}
if (preg_match('#^speedy/offices/(-?\d+)$#', $path, $m) && $method === 'GET') {
    $cityId = (int) $m[1];
    $cityName = (string) ($_GET['name'] ?? '');
    try {
        json_response(200, ['offices' => SpeedyService::getOfficesForCity($cityId, $cityName)]);
    } catch (\Throwable $e) {
        error_log('Speedy offices error: ' . $e->getMessage());
        json_response(200, ['offices' => SpeedyService::staticFallbackOffices($cityName)]);
    }
}
if ($path === 'speedy/calculate' && $method === 'POST') {
    handle_calculate_shipping();
}
if (preg_match('#^pay/borica/start/([A-Za-z0-9\-]+)$#', $path, $m) && $method === 'GET') {
    handle_borica_start($m[1]);
}
if ($path === 'pay/borica/return') {
    handle_borica_return();
}

json_response(404, ['ok' => false, 'message' => 'Not Found']);

// ---------- Handlers ----------

function handle_create_order(): never
{
    // Same-origin guard (mitigates simple CSRF). On a public order endpoint
    // this is a soft check — full CSRF tokens would require the SPA to fetch one first.
    same_origin_or_die();

    $ip = RateLimit::clientIp();
    if (!RateLimit::check('order_create', $ip, 10, 3600)) {
        json_response(429, ['ok' => false, 'message' => 'Прекалено много опити. Опитайте по-късно.']);
    }

    $payload = read_json_body();
    if (!is_array($payload)) {
        json_response(400, ['ok' => false, 'message' => 'Невалидно тяло на заявката.']);
    }

    $errors = validate_order($payload);
    if ($errors) {
        json_response(422, ['ok' => false, 'errors' => $errors]);
    }

    try {
        $order = Order::create($payload, $ip);
    } catch (\Throwable $e) {
        error_log('Order creation failed: ' . $e->getMessage());
        json_response(500, ['ok' => false, 'message' => 'Грешка при създаване на поръчката.']);
    }

    // Card orders pay via БОРИКА: defer the confirmation emails until payment
    // succeeds (sent from the Return handler) and redirect the browser to the
    // signed redirect form instead of showing the success modal.
    if (($payload['payment']['method'] ?? '') === 'card' && \App\Borica::isEnabled()) {
        json_response(200, [
            'ok'           => true,
            'order_number' => $order['order_number'],
            'redirect'     => apiUrl('pay/borica/start/' . rawurlencode($order['order_number'])),
        ]);
    }

    // COD (or card while БОРИКА is disabled): notify immediately, as before.
    send_new_order_emails($order);

    json_response(200, ['ok' => true, 'order_number' => $order['order_number']]);
}

/**
 * Sends the two new-order emails (customer confirmation + admin notification).
 * Each is independent; failure is logged and never bubbles up (rule 16).
 */
/**
 * Изпраща не-фискален документ за продажба (стокова разписка по чл. 52о) на клиента.
 * Само за картови поръчки. Own try/catch — провал не чупи плащането/другите имейли (rule 16).
 */
function send_sale_document(array $order): void
{
    if (($order['payment_method'] ?? '') !== 'card') return;
    try {
        $s = Settings::getMany([
            'nap_company_name', 'nap_company_eik', 'nap_company_vat',
            'nap_company_address', 'nap_shop_domain', 'nap_vat_rate',
        ]);
        $company = [
            'name'    => $s['nap_company_name'] ?: ($GLOBALS['__config']['app']['name'] ?? 'ÉTALON'),
            'eik'     => $s['nap_company_eik'] ?? '',
            'vat'     => $s['nap_company_vat'] ?? '',
            'address' => $s['nap_company_address'] ?? '',
            'domain'  => $s['nap_shop_domain'] ?: 'etaloncare.com',
        ];
        $p = Db::fetchOne(
            'SELECT rrn FROM payments WHERE order_id = ? AND action = \'0\' AND signature_valid = 1 ORDER BY id DESC LIMIT 1',
            [(int) $order['id']]
        );
        Mailer::send(
            'sale_document',
            $order['customer_email'],
            'Документ за продажба № ' . $order['id'] . ' — поръчка ' . $order['order_number'],
            [
                'order'   => $order,
                'items'   => Order::items((int) $order['id']),
                'company' => $company,
                'vatRate' => (int) ($s['nap_vat_rate'] ?? 9),
                'rrn'     => $p['rrn'] ?? '',
                'docNo'   => (string) $order['id'],
            ],
            (int) $order['id']
        );
    } catch (\Throwable $e) {
        error_log('Sale document email failed: ' . $e->getMessage());
    }
}

function send_new_order_emails(array $order): void
{
    $items = Order::items((int) $order['id']);
    try {
        Mailer::send(
            'new_order',
            $order['customer_email'],
            'Поръчка № ' . $order['order_number'] . ' — потвърждение',
            ['order' => $order, 'items' => $items],
            (int) $order['id']
        );
    } catch (\Throwable $e) {
        error_log('Order email failed: ' . $e->getMessage());
    }

    try {
        $adminEmail = Settings::get('notify_admin_email') ?: 'linavalerieva7@gmail.com';
        if ($adminEmail !== '') {
            Mailer::send(
                'admin_new_order',
                $adminEmail,
                'Нова поръчка № ' . $order['order_number'] . ' — ' . $order['customer_name'],
                ['order' => $order, 'items' => $items],
                (int) $order['id']
            );
        }
    } catch (\Throwable $e) {
        error_log('Admin notification email failed: ' . $e->getMessage());
    }
}

/**
 * Renders the auto-submitting form that redirects the customer to БОРИКА's 3DS page.
 * No same-origin guard needed — it's a top-level browser navigation, and the
 * security boundary is БОРИКА's signature verification on the way back.
 */
function handle_borica_start(string $orderNumber): never
{
    $order = Order::findByNumber($orderNumber);
    if (!$order || $order['payment_method'] !== 'card' || $order['payment_status'] !== 'pending') {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Невалидна поръчка за плащане.');
    }

    // Return URL must be absolute — БОРИКА calls it from the outside.
    $backref = appUrl() . '/api/pay/borica/return';

    try {
        $start = \App\Payment::startForOrder($order, $backref);
    } catch (\Throwable $e) {
        error_log('БОРИКА start failed: ' . $e->getMessage());
        redirect(publicUrl('?payment=failed&order=' . rawurlencode($order['order_number'])));
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="bg"><head><meta charset="utf-8">'
       . '<meta name="robots" content="noindex"><title>Пренасочване към плащане…</title></head>'
       . '<body onload="document.forms[0].submit()">'
       . '<form method="post" action="' . e($start['url']) . '">';
    foreach ($start['fields'] as $k => $v) {
        echo '<input type="hidden" name="' . e($k) . '" value="' . e((string) $v) . '">';
    }
    echo '<noscript><p>Натиснете бутона, за да продължите към плащане.</p>'
       . '<button type="submit">Към плащане</button></noscript>'
       . '</form></body></html>';
    exit;
}

/**
 * Receives БОРИКА's signed response, finalizes the order, sends success emails
 * (once), and redirects the browser back to the SPA with a ?payment= flag.
 */
function handle_borica_return(): never
{
    $post  = !empty($_POST) ? $_POST : $_GET; // БОРИКА posts; GET only for manual testing
    $res   = \App\Payment::handleReturn($post);
    $order = $res['order'];

    if ($order && !empty($res['first_success'])) {
        send_new_order_emails($order);
        send_sale_document($order);
    }

    $num  = $order['order_number'] ?? '';
    $flag = $res['success'] ? 'success' : 'failed';
    redirect(publicUrl('?payment=' . $flag . ($num !== '' ? '&order=' . rawurlencode($num) : '')));
}

function handle_calculate_shipping(): never
{
    same_origin_or_die();

    $ip = RateLimit::clientIp();
    // Lightweight throttle — users may trigger this repeatedly while choosing a city.
    if (!RateLimit::check('ship_calc', $ip, 120, 3600)) {
        json_response(429, ['ok' => false, 'message' => 'Прекалено много заявки.']);
    }

    $payload = read_json_body();
    if (!is_array($payload)) {
        json_response(400, ['ok' => false, 'message' => 'Невалидно тяло на заявката.']);
    }

    $qty = max(1, min(20, (int) ($payload['qty'] ?? 1)));
    $subtotal = round(Order::PRICE_PER_UNIT * $qty, 2);

    $result = SpeedyService::calculate([
        'method'           => $payload['method'] ?? '',
        'office_site_id'   => (int) ($payload['office_site_id']   ?? 0),
        'office_speedy_id' => (int) ($payload['office_speedy_id'] ?? 0),
        'city_name'        => (string) ($payload['city_name']  ?? ''),
        'post_code'        => $payload['post_code'] ?? null,
        'subtotal'         => $subtotal,
        'cod'              => ($payload['payment'] ?? '') === 'cod',
        'cod_amount'       => $subtotal,
        'qty'              => $qty,
    ]);

    json_response(200, ['ok' => true] + $result);
}

// ---------- Helpers ----------

function read_json_body(): mixed
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return null;
    $data = json_decode($raw, true);
    return json_last_error() === JSON_ERROR_NONE ? $data : null;
}

function json_response(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function same_origin_or_die(): void
{
    $expected = $GLOBALS['__config']['app']['url'] ?? '';
    if (!$expected) return;
    $expectedHost = parse_url($expected, PHP_URL_HOST);

    $origin  = $_SERVER['HTTP_ORIGIN']  ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';

    foreach ([$origin, $referer] as $candidate) {
        if (!$candidate) continue;
        $host = parse_url($candidate, PHP_URL_HOST);
        if ($host && $host === $expectedHost) return;
    }
    // No matching origin — soft-fail only if we got SOMETHING (curl etc. send neither)
    if ($origin || $referer) {
        json_response(403, ['ok' => false, 'message' => 'Forbidden origin.']);
    }
}

function validate_order(array $p): array
{
    $err = [];

    $customer = $p['customer'] ?? [];
    if (empty(trim($customer['name'] ?? ''))) $err['name'] = 'Задължително поле';
    if (!filter_var($customer['email'] ?? '', FILTER_VALIDATE_EMAIL)) $err['email'] = 'Невалиден имейл';
    if (strlen(preg_replace('/\D/', '', $customer['phone'] ?? '')) < 7) $err['phone'] = 'Невалиден телефон';

    $delivery = $p['delivery'] ?? [];
    $method = $delivery['method'] ?? '';
    if (!in_array($method, ['office', 'address'], true)) {
        $err['delivery'] = 'Невалиден метод за доставка';
    } elseif ($method === 'office') {
        if (empty(trim($delivery['office_city'] ?? ''))) $err['officeCity'] = 'Задължително поле';
        $officeName = trim((string) ($delivery['office_name'] ?? $delivery['office_id'] ?? ''));
        if ($officeName === '') $err['officeId'] = 'Изберете офис';
    } else {
        if (empty(trim($delivery['city'] ?? '')))   $err['city']   = 'Задължително поле';
        if (empty(trim($delivery['street'] ?? ''))) $err['street'] = 'Задължително поле';
        if (empty(trim($delivery['number'] ?? ''))) $err['number'] = 'Задължително поле';
    }

    $payment = $p['payment'] ?? [];
    if (!in_array($payment['method'] ?? '', ['cod', 'card'], true)) {
        $err['payment'] = 'Невалиден метод на плащане';
    }

    return $err;
}
