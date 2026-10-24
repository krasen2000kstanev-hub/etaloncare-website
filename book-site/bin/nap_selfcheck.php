<?php
declare(strict_types=1);
// Строи примерен одиторски файл БЕЗ база и го валидира срещу app/nap/schema.xsd.
// Употреба: php bin/nap_selfcheck.php
require __DIR__ . '/../app/NapAudit.php';

use App\NapAudit;

$header = [
    'eik' => '207123456', 'e_shop_n' => 'RF0000001', 'domain_name' => 'etaloncare.com',
    'e_shop_type' => 1, 'creation_date' => '2026-06-10', 'mon' => '06', 'god' => 2026,
];
$orders = [
    [ // картова поръчка
        'ord_n' => 'ACN-123456', 'ord_d' => '2026-06-01', 'doc_n' => '12', 'doc_date' => '2026-06-01',
        'paym' => 2, 'pos_n' => 'V5402893', 'trans_n' => '123456789012', 'proc_id' => 'UNICREDIT',
        'items' => [['name' => 'Разширена химия на ноктите', 'qty' => 1, 'unit_price' => 69.00]],
    ],
    [ // наложен платеж
        'ord_n' => 'ACN-654321', 'ord_d' => '2026-06-03', 'doc_n' => '13', 'doc_date' => '2026-06-03',
        'paym' => 3, 'pos_n' => '', 'trans_n' => '', 'proc_id' => '',
        'items' => [['name' => 'Разширена химия на ноктите', 'qty' => 2, 'unit_price' => 69.00]],
    ],
];

$dom = NapAudit::buildDom($header, $orders, 9);
$errors = NapAudit::validate($dom);

echo "--- XML (UTF-8 preview) ---\n", $dom->saveXML(), "\n";
if ($errors) {
    echo "INVALID:\n - ", implode("\n - ", $errors), "\n";
    exit(1);
}
echo "VALID срещу app/nap/schema.xsd\n";
$out = NapAudit::build($header, $orders, 9);
echo "encoding declared: ", (strpos($out, 'windows-1251') !== false ? 'windows-1251 OK' : 'MISSING'), "\n";
