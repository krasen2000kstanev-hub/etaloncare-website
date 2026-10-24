<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Db;
use App\Flash;
use App\Cache;
use App\Content;
use App\Mailer;
use App\Media;
use App\Migrate;
use App\Order;
use App\SpeedyService;
use App\User;
use App\Session;
use App\Settings;
use App\View;

View::setBase(__DIR__ . '/views');

$path   = trim((string) ($_GET['path'] ?? ''), '/');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') Csrf::requirePost();

// Idempotent: applies any unrun migrations on every admin hit.
try {
    Migrate::run();
} catch (\Throwable $e) {
    http_response_code(500);
    echo "Migration error: " . e($e->getMessage());
    exit;
}

// First-run setup wizard if no users yet.
$hasUsers = (int) (Db::fetchOne('SELECT COUNT(*) c FROM users')['c'] ?? 0) > 0;

if (!$hasUsers) {
    if ($path !== 'setup') redirect(adminUrl('setup'));

    $errors = [];
    $email = $name = '';
    if ($method === 'POST') {
        $email    = trim((string) ($_POST['email'] ?? ''));
        $name     = trim((string) ($_POST['name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Невалиден имейл.';
        if (mb_strlen($name) < 2)        $errors['name'] = 'Въведете име.';
        if (strlen($password) < 8)       $errors['password'] = 'Минимум 8 символа.';

        if (!$errors) {
            Db::execute(
                'INSERT INTO users (email, name, password_hash, role) VALUES (?, ?, ?, ?)',
                [$email, $name, Auth::hash($password), 'admin']
            );
            Auth::attempt($email, $password);
            Flash::set('success', 'Акаунтът е създаден. Добре дошли.');
            redirect(adminUrl('dashboard'));
        }
    }
    View::display('setup', ['errors' => $errors, 'email' => $email, 'name' => $name]);
    exit;
}

// Login
if ($path === 'login') {
    if (Auth::check()) redirect(adminUrl('dashboard'));

    $email = '';
    if ($method === 'POST') {
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (Auth::attempt($email, $password)) {
            $next = (string) (Session::get('redirect_after_login') ?? adminUrl('dashboard'));
            Session::forget('redirect_after_login');
            redirect($next);
        }
        Flash::set('error', 'Грешен имейл или парола.');
    }
    View::display('login', ['email' => $email]);
    exit;
}

// Logout
if ($path === 'logout') {
    Auth::logout();
    redirect(adminUrl('login'));
}

// === Below this line: auth required ===
Auth::requireLogin();

if ($path === '' || $path === 'dashboard') {
    $userCount = (int) (Db::fetchOne('SELECT COUNT(*) c FROM users')['c'] ?? 0);
    $counts = Order::counts();
    $recent = Order::listFiltered([]);
    View::display('dashboard', [
        'userCount' => $userCount,
        'counts'    => $counts,
        'recent'    => array_slice($recent, 0, 8),
    ]);
    exit;
}

// Orders list
if ($path === 'orders') {
    $orders = Order::listFiltered([
        'status' => $_GET['status'] ?? null,
        'q'      => trim((string) ($_GET['q'] ?? '')),
    ]);
    $counts = Order::counts();
    View::display('orders', [
        'orders' => $orders,
        'counts' => $counts,
        'currentStatus' => $_GET['status'] ?? '',
        'q' => $_GET['q'] ?? '',
    ]);
    exit;
}

// Waybill PDF — streams Speedy's PDF back to the user.
if (preg_match('#^orders/(\d+)/waybill\.pdf$#', $path, $m) && $method === 'GET') {
    Auth::requireLogin();
    $orderId = (int) $m[1];
    try {
        $pdf = SpeedyService::fetchWaybillPdf($orderId);
    } catch (\Throwable $e) {
        Flash::set('error', 'Грешка при сваляне на товарителница: ' . $e->getMessage());
        redirect(adminUrl('orders/' . $orderId));
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="waybill-' . $orderId . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    echo $pdf;
    exit;
}

// Order detail + status change
if (preg_match('#^orders/(\d+)$#', $path, $m)) {
    $orderId = (int) $m[1];
    $order = Order::find($orderId);
    if (!$order) { http_response_code(404); View::display('404'); exit; }

    if ($method === 'POST' && ($_POST['action'] ?? '') === 'create_waybill') {
        try {
            $result = SpeedyService::createWaybillForOrder($orderId);
            // Refresh order + auto-bump status to 'shipped' with waybill note
            $note = 'Товарителница Speedy: ' . $result['tracking_number'];
            $order = Order::changeStatus($orderId, 'shipped', $note, (int) Auth::user()['id']);
            try {
                Mailer::send(
                    'status_change',
                    $order['customer_email'],
                    'Поръчка № ' . $order['order_number'] . ' — Изпратена',
                    [
                        'order'       => $order,
                        'newStatus'   => 'shipped',
                        'statusLabel' => Order::statusLabel('shipped'),
                        'note'        => $note,
                    ],
                    $orderId
                );
            } catch (\Throwable $e) { /* email failure logged in Mailer */ }
            Flash::set('success', 'Товарителница ' . $result['tracking_number'] . ' е създадена. Клиентът е уведомен.');
        } catch (\Throwable $e) {
            Flash::set('error', 'Грешка при създаване на товарителница: ' . $e->getMessage());
        }
        redirect(adminUrl('orders/' . $orderId));
    }

    if ($method === 'POST' && ($_POST['action'] ?? '') === 'change_status') {
        $newStatus = (string) ($_POST['status'] ?? '');
        $note = trim((string) ($_POST['note'] ?? '')) ?: null;
        if (!isset(Order::STATUSES[$newStatus])) {
            Flash::set('error', 'Невалиден статус.');
            redirect(adminUrl('orders/' . $orderId));
        }
        if ($newStatus === $order['status'] && !$note) {
            Flash::set('error', 'Изберете нов статус или въведете бележка.');
            redirect(adminUrl('orders/' . $orderId));
        }
        $order = Order::changeStatus($orderId, $newStatus, $note, (int) Auth::user()['id']);

        // Email customer
        try {
            Mailer::send(
                'status_change',
                $order['customer_email'],
                'Поръчка № ' . $order['order_number'] . ' — ' . Order::statusLabel($newStatus),
                [
                    'order'       => $order,
                    'newStatus'   => $newStatus,
                    'statusLabel' => Order::statusLabel($newStatus),
                    'note'        => $note,
                ],
                $orderId
            );
            Flash::set('success', 'Статусът е променен. Имейлът е изпратен на клиента.');
        } catch (\Throwable $e) {
            Flash::set('error', 'Статусът е променен, но имейлът не успя: ' . $e->getMessage());
        }
        redirect(adminUrl('orders/' . $orderId));
    }

    View::display('order', [
        'order'   => $order,
        'items'   => Order::items($orderId),
        'log'     => Order::statusLog($orderId),
        'payment' => Db::fetchOne('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1', [$orderId]),
    ]);
    exit;
}

// === Profile (own account) — any logged-in user ===
if ($path === 'profile') {
    $me = Auth::user();
    $errors = [];
    $passErrors = [];

    if ($method === 'POST') {
        $action = (string) ($_POST['action'] ?? 'profile');
        if ($action === 'profile') {
            $errors = User::updateOwnProfile(
                (int) $me['id'],
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? '')
            );
            if (!$errors) {
                Flash::set('success', 'Профилът е обновен.');
                redirect(adminUrl('profile'));
            }
        } elseif ($action === 'password') {
            $passErrors = User::changeOwnPassword(
                (int) $me['id'],
                (string) ($_POST['current'] ?? ''),
                (string) ($_POST['new'] ?? '')
            );
            if (!$passErrors) {
                Flash::set('success', 'Паролата е променена.');
                redirect(adminUrl('profile'));
            }
        }
    }

    View::display('profile', [
        'me'         => Auth::user(),
        'errors'     => $errors,
        'passErrors' => $passErrors,
        'form'       => $_POST ?: [],
    ]);
    exit;
}

// === Users CRUD — admin only ===
if ($path === 'users') {
    Auth::requireRole('admin');
    View::display('users', [
        'users'      => User::listAll(),
        'me'         => Auth::user(),
        'adminCount' => User::adminCount(),
    ]);
    exit;
}

if ($path === 'users/new') {
    Auth::requireRole('admin');
    $errors = [];
    $form = ['email' => '', 'name' => '', 'role' => 'manager', 'password' => ''];

    if ($method === 'POST') {
        $form = [
            'email'    => (string) ($_POST['email'] ?? ''),
            'name'     => (string) ($_POST['name'] ?? ''),
            'role'     => (string) ($_POST['role'] ?? 'manager'),
            'password' => (string) ($_POST['password'] ?? ''),
        ];
        $errors = User::validate($form, isCreate: true);
        if (!$errors) {
            $newId = User::create($form);
            Flash::set('success', 'Потребителят е създаден.');
            redirect(adminUrl('users/' . $newId));
        }
    }

    View::display('user_edit', ['mode' => 'create', 'user' => null, 'form' => $form, 'errors' => $errors]);
    exit;
}

if (preg_match('#^users/(\d+)/delete$#', $path, $m) && $method === 'POST') {
    Auth::requireRole('admin');
    try {
        User::delete((int) $m[1], (int) Auth::user()['id']);
        Flash::set('success', 'Потребителят е изтрит.');
    } catch (\Throwable $e) {
        Flash::set('error', $e->getMessage());
    }
    redirect(adminUrl('users'));
}

if (preg_match('#^users/(\d+)$#', $path, $m)) {
    Auth::requireRole('admin');
    $userId = (int) $m[1];
    $user = User::find($userId);
    if (!$user) { http_response_code(404); View::display('404'); exit; }

    // Self-edit goes to /admin/profile
    if ((int) Auth::user()['id'] === $userId) {
        redirect(adminUrl('profile'));
    }

    $errors = [];
    $form = [
        'email' => $user['email'],
        'name'  => $user['name'],
        'role'  => $user['role'],
        'password' => '',
    ];

    if ($method === 'POST') {
        $form = [
            'email'    => (string) ($_POST['email'] ?? ''),
            'name'     => (string) ($_POST['name'] ?? ''),
            'role'     => (string) ($_POST['role'] ?? 'manager'),
            'password' => (string) ($_POST['password'] ?? ''),
        ];
        $errors = User::validate($form, isCreate: false, currentId: $userId);
        if (!$errors) {
            User::update($userId, $form);
            Flash::set('success', 'Потребителят е обновен.');
            redirect(adminUrl('users/' . $userId));
        }
    }

    View::display('user_edit', ['mode' => 'edit', 'user' => $user, 'form' => $form, 'errors' => $errors]);
    exit;
}

// Content editor
if ($path === 'content') {
    Auth::requireRole('admin');
    if ($method === 'POST') {
        $values = $_POST['values'] ?? [];
        foreach ($values as $key => $byLocale) {
            if (!Content::isEditable($key)) continue;
            foreach (Content::LOCALES as $loc) {
                $v = trim((string) ($byLocale[$loc] ?? ''));
                Content::set($key, $loc, $v);
            }
        }
        Flash::set('success', 'Текстовете са запазени.');
        redirect(adminUrl('content'));
    }
    View::display('content', ['data' => Content::getAllForAdmin()]);
    exit;
}

// Media manager
if ($path === 'media') {
    Auth::requireRole('admin');
    if ($method === 'POST') {
        $action = $_POST['action'] ?? '';
        $slot = (string) ($_POST['slot'] ?? '');
        try {
            if ($action === 'upload' && Media::isSlot($slot)) {
                Media::upload($slot, $_FILES['file'] ?? []);
                Flash::set('success', 'Файлът за „' . Media::SLOTS[$slot]['label'] . '" е качен.');
            } elseif ($action === 'reset' && Media::isSlot($slot)) {
                Media::reset($slot);
                Flash::set('success', 'Възстановен е оригиналът за „' . Media::SLOTS[$slot]['label'] . '".');
            }
        } catch (\Throwable $e) {
            Flash::set('error', $e->getMessage());
        }
        redirect(adminUrl('media'));
    }
    View::display('media');
    exit;
}

// SEO
if ($path === 'seo') {
    Auth::requireRole('admin');
    $keys = ['seo_title', 'seo_description', 'seo_og_title', 'seo_og_description', 'seo_og_image'];
    if ($method === 'POST') {
        $kv = [];
        foreach ($keys as $k) $kv[$k] = (string) ($_POST[$k] ?? '');
        Settings::setMany($kv);
        Flash::set('success', 'SEO настройките са запазени.');
        redirect(adminUrl('seo'));
    }
    View::display('seo', ['values' => Settings::getMany($keys)]);
    exit;
}

// Cache bust — clears static asset cache + Speedy cache + opcache.
if ($path === 'cache/bust' && $method === 'POST') {
    Auth::requireRole('admin');
    $r = Cache::bust();
    $msg = "Кешът е изчистен (v{$r['version']}). Изтрити: {$r['speedy_offices']} Speedy офиса.";
    if ($r['opcache_avail']) {
        $msg .= ' PHP opcache: ' . ($r['opcache_cleared'] ? 'reset' : 'недостъпен на този хост');
    }
    Flash::set('success', $msg);
    redirect(adminUrl('settings'));
}

if ($path === 'settings') {
    Auth::requireRole('admin');
    $keys = [
        'speedy_username', 'speedy_password', 'speedy_client_id',
        'speedy_sender_object_id', 'speedy_service_id', 'speedy_default_weight',
        'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass',
        'smtp_from_email', 'smtp_from_name', 'smtp_secure',
        'nap_company_eik', 'nap_shop_uid', 'nap_shop_domain', 'nap_shop_type',
        'nap_pos_terminal', 'nap_pos_provider_id', 'nap_vat_rate',
        'nap_company_name', 'nap_company_vat', 'nap_company_address',
    ];
    if ($method === 'POST') {
        if (($_POST['action'] ?? '') === 'sync_speedy') {
            try {
                $r = SpeedyService::syncCities();
                Flash::set('success', 'Синхронизирани ' . $r['cities'] . ' града от Speedy.');
            } catch (\Throwable $e) {
                Flash::set('error', 'Грешка при синхронизация: ' . $e->getMessage());
            }
            redirect(adminUrl('settings'));
        }
        $kv = [];
        foreach ($keys as $k) $kv[$k] = (string) ($_POST[$k] ?? '');
        Settings::setMany($kv);
        Flash::set('success', 'Настройките са запазени.');
        redirect(adminUrl('settings'));
    }
    $cityCount = (int) (Db::fetchOne('SELECT COUNT(*) c FROM speedy_cities')['c'] ?? 0);
    View::display('settings', ['values' => Settings::getMany($keys), 'cityCount' => $cityCount]);
    exit;
}

// НАП одиторски файл — форма + резултати
if ($path === 'nap-audit') {
    Auth::requireRole('admin');
    $from = (string) ($_GET['from'] ?? '');
    $to   = (string) ($_GET['to'] ?? '');
    $pay  = in_array($_GET['pay'] ?? 'all', ['card', 'cod', 'all'], true) ? $_GET['pay'] : 'all';
    $data = null; $error = '';
    if ($from !== '' && $to !== '') {
        if (date('Y-m', (int) strtotime($from)) !== date('Y-m', (int) strtotime($to))) {
            $error = 'Периодът трябва да е в рамките на един календарен месец.';
        } else {
            try {
                $data = \App\NapAudit::collect($from, $to, $pay);
            } catch (\Throwable $e) {
                error_log('NAP collect failed: ' . $e->getMessage());
                $error = 'Грешка при извличане на данните: ' . $e->getMessage();
            }
        }
    }
    View::display('nap_audit', ['from' => $from, 'to' => $to, 'pay' => $pay, 'report' => $data, 'error' => $error]);
    exit;
}

// НАП одиторски файл — генериране, валидация и сваляне
if ($path === 'nap-audit/export' && $method === 'GET') {
    Auth::requireRole('admin');
    $from = (string) ($_GET['from'] ?? '');
    $to   = (string) ($_GET['to'] ?? '');
    $pay  = in_array($_GET['pay'] ?? 'all', ['card', 'cod', 'all'], true) ? $_GET['pay'] : 'all';
    if ($from === '' || $to === '' || date('Y-m', (int) strtotime($from)) !== date('Y-m', (int) strtotime($to))) {
        Flash::set('error', 'Невалиден период (трябва да е един календарен месец).');
        redirect(adminUrl('nap-audit'));
    }
    try {
        $data = \App\NapAudit::collect($from, $to, $pay);
        $dom  = \App\NapAudit::buildDom($data['header'], $data['orders'], $data['vat']);
    } catch (\Throwable $e) {
        error_log('NAP export failed: ' . $e->getMessage());
        Flash::set('error', 'Грешка при генериране: ' . $e->getMessage());
        redirect(adminUrl('nap-audit?from=' . urlencode($from) . '&to=' . urlencode($to) . '&pay=' . $pay));
    }
    $errs = \App\NapAudit::validate($dom);
    if ($errs) {
        Flash::set('error', 'Файлът не е валиден по XSD: ' . implode('; ', array_slice($errs, 0, 3)));
        redirect(adminUrl('nap-audit?from=' . urlencode($from) . '&to=' . urlencode($to) . '&pay=' . $pay));
    }
    $xml = \App\NapAudit::build($data['header'], $data['orders'], $data['vat']);
    $fname = 'nap-audit-' . $data['header']['god'] . '-' . $data['header']['mon'] . '.xml';
    header('Content-Type: application/xml; charset=windows-1251');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Content-Length: ' . strlen($xml));
    header('Cache-Control: private, no-store');
    echo $xml;
    exit;
}

http_response_code(404);
View::display('404');
