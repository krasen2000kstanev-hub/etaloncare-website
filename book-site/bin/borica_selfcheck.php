<?php
declare(strict_types=1);

/**
 * Manual check (no PHPUnit in this project): loads config/keys, signs a sample
 * sale request, prints the fields + P_SIGN. Use to compare the MAC against the
 * v7 PDF example before the first real test transaction.
 *
 * Usage: php bin/borica_selfcheck.php
 */
require __DIR__ . '/../app/bootstrap.php';

use App\Borica;

echo 'enabled : ', (Borica::isEnabled() ? 'yes' : 'no'), PHP_EOL;
echo 'endpoint: ', Borica::endpointUrl(), PHP_EOL, PHP_EOL;

$order = [
    'order_number'   => 'ACN-123456',
    'total'          => 69.00,
    'customer_email' => 'test@example.com',
    'locale'         => 'bg',
];

try {
    $fields = Borica::buildSaleFields($order, '000123', 'https://enter.bg/etaloncare/api/pay/borica/return');
    foreach ($fields as $k => $v) {
        echo str_pad($k, 12), ' = ', $v, PHP_EOL;
    }
    echo PHP_EOL, 'P_SIGN length (hex): ', strlen($fields['P_SIGN']), PHP_EOL;
    echo 'OK — signature generated.', PHP_EOL;
} catch (\Throwable $e) {
    echo 'ERROR: ', $e->getMessage(), PHP_EOL;
    exit(1);
}
