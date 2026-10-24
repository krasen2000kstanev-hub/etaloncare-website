<?php
declare(strict_types=1);

// Single bootstrap entry — included from every PHP front controller.

const APP_ROOT = __DIR__;
const PROJECT_ROOT = __DIR__ . '/..';

if (!is_file(PROJECT_ROOT . '/config.php')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Missing config.php. Copy config.example.php to config.php and edit DB credentials.\n";
    exit;
}

$GLOBALS['__config'] = require PROJECT_ROOT . '/config.php';

// PSR-4-style autoloader for the App\ namespace
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = APP_ROOT . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});

require APP_ROOT . '/helpers.php';

// Error reporting per env
$env = $GLOBALS['__config']['app']['env'] ?? 'production';
if ($env === 'dev') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Init core services
\App\Db::init($GLOBALS['__config']['db']);
\App\Session::start($GLOBALS['__config']['session']);
