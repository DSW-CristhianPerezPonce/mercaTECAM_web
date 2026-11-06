<?php
declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));

// Carga .env en local (en Vercel las variables vienen del panel del proyecto).
$envFile = ROOT_DIR . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ProductRepository.php';
require_once __DIR__ . '/helpers.php';
