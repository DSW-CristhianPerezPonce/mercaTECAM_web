<?php
declare(strict_types=1);

/**
 * Aplica database/schema.sql sobre la base de DATABASE_URL (no requiere psql).
 * Uso:  php scripts/migrar.php
 * Es idempotente (CREATE ... IF NOT EXISTS / ON CONFLICT DO NOTHING).
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

$db = Database::connection();
$db->exec(file_get_contents(ROOT_DIR . '/database/schema.sql'));

$tablas = $db->query(
    "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name"
)->fetchAll(PDO::FETCH_COLUMN);

echo "Esquema aplicado. Tablas: " . implode(', ', $tablas) . "\n";
echo "Categorías: " . implode(', ', $db->query('SELECT slug FROM categorias ORDER BY id')->fetchAll(PDO::FETCH_COLUMN)) . "\n";
