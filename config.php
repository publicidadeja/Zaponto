<?php
// config.php

if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(sprintf('%s=%s', trim($key), trim($value)));
        }
    }
}

// Definir constantes
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY'));
define('STRIPE_PUBLIC_KEY', getenv('STRIPE_PUBLIC_KEY'));
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET'));
define('SITE_URL', getenv('SITE_URL'));