<?php
/**
 * Copy this file to config.php and edit. Never commit config.php.
 *
 * SMTP and Speedy credentials are stored in the `settings` table (DB),
 * editable from /admin/settings — they do NOT belong here.
 */
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'etaloncare',
        'user'    => 'etaloncare',
        'pass'    => 'change-me',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name'    => 'ÉTALON',
        // FULL public URL of the site INCLUDING any subdirectory deployment.
        // Examples:
        //   Root deployment:    'https://www.etaloncare.bg'
        //   Subdir deployment:  'https://enter.bg/projects/etaloncare'
        // The base path is derived from this — no separate setting required.
        'url'     => 'https://etaloncare.bg',
        'env'     => 'production',  // 'dev' shows errors
    ],
    'session' => [
        'name'     => 'ec_sess',
        'lifetime' => 28800,        // 8 hours in seconds
        'secure'   => true,         // require HTTPS in production
    ],
];
