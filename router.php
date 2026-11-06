<?php
// Router para el servidor embebido de PHP en local:
//   php -S localhost:8000 router.php
// Sirve los archivos estáticos tal cual y envía el resto a api/index.php (igual que vercel.json).

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && str_starts_with($path, '/assets/') && is_file(__DIR__ . $path)) {
    return false;
}

require __DIR__ . '/api/index.php';
