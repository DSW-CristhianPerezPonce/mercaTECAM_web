<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    /**
     * Conexión única a PostgreSQL a partir de DATABASE_URL
     * (formato Neon: postgresql://usuario:clave@host/base?sslmode=require).
     */
    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $url = getenv('DATABASE_URL');
        if (!$url) {
            throw new RuntimeException('Falta la variable de entorno DATABASE_URL.');
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            throw new RuntimeException('DATABASE_URL no es válida.');
        }

        parse_str($parts['query'] ?? '', $query);
        $host = $parts['host'];
        $dsn  = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $host,
            $parts['port'] ?? 5432,
            ltrim($parts['path'] ?? '/postgres', '/'),
            $query['sslmode'] ?? 'prefer'
        );

        if (!empty($query['channel_binding'])) {
            $dsn .= ';channel_binding=' . $query['channel_binding'];
        }

        // Neon: indica el endpoint para clientes libpq sin soporte SNI.
        // Debe ser el primer segmento exacto del host (incluido "-pooler" si lo tiene).
        if (str_starts_with($host, 'ep-')) {
            $endpoint = explode('.', $host)[0];
            $dsn .= ";options='endpoint=$endpoint'";
        }

        self::$pdo = new PDO(
            $dsn,
            urldecode($parts['user'] ?? ''),
            urldecode($parts['pass'] ?? ''),
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        return self::$pdo;
    }
}
